<?php
/**
 * Main plugin class.
 *
 * Implements the singleton pattern and bootstraps the entire plugin by
 * loading dependencies, registering activation/deactivation hooks, and
 * wiring up all WordPress hooks.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Plugin
 *
 * Core plugin singleton responsible for initialisation, dependency loading,
 * and hook registration.
 *
 * @since 1.0.0
 */
class BCG_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var BCG_Plugin|null
	 */
	private static ?BCG_Plugin $instance = null;

	/**
	 * Admin handler instance.
	 *
	 * @var BCG_Admin|null
	 */
	private ?object $admin = null;

	/**
	 * Settings handler instance.
	 *
	 * @var BCG_Settings|null
	 */
	private ?object $settings = null;

	/**
	 * Credits handler instance.
	 *
	 * @var BCG_Credits|null
	 */
	private ?object $credits = null;

	/**
	 * Stats handler instance.
	 *
	 * @var BCG_Stats|null
	 */
	private ?object $stats = null;

	/**
	 * Whether the plugin has already been initialised.
	 *
	 * @var bool
	 */
	private bool $initialised = false;

	/**
	 * Private constructor to enforce singleton pattern.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Get the singleton instance of the plugin.
	 *
	 * @since  1.0.0
	 * @return BCG_Plugin
	 */
	public static function get_instance(): BCG_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load all required class files.
	 *
	 * Files are loaded in dependency order so that each class has access
	 * to any classes it depends on.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function load_dependencies(): void {
		$includes = BCG_PLUGIN_DIR . 'includes/';

		// Core classes.
		require_once $includes . 'class-bcg-activator.php';
		require_once $includes . 'class-bcg-deactivator.php';

		// Database table classes.
		require_once $includes . 'db/class-bcg-campaigns-table.php';
		require_once $includes . 'db/class-bcg-credits-table.php';
		require_once $includes . 'db/class-bcg-transactions-table.php';
		require_once $includes . 'db/class-bcg-section-templates-table.php';

		// Admin classes.
		require_once $includes . 'admin/class-bcg-admin.php';
		require_once $includes . 'admin/class-bcg-settings.php';
		require_once $includes . 'admin/class-bcg-credits.php';
		require_once $includes . 'admin/class-bcg-stats.php';

		// Campaign classes.
		require_once $includes . 'campaign/class-bcg-campaign.php';
		require_once $includes . 'campaign/class-bcg-product-selector.php';
		require_once $includes . 'campaign/class-bcg-coupon.php';
		require_once $includes . 'campaign/class-bcg-template-registry.php';
		require_once $includes . 'campaign/class-bcg-template.php';
		require_once $includes . 'campaign/class-bcg-section-registry.php';
		require_once $includes . 'campaign/class-bcg-section-renderer.php';
		require_once $includes . 'campaign/class-bcg-section-ai.php';

		// AI classes.
		require_once $includes . 'ai/class-bcg-openai.php';
		require_once $includes . 'ai/class-bcg-gemini.php';
		require_once $includes . 'ai/class-bcg-ai-manager.php';

		// Integration classes.
		require_once $includes . 'integrations/class-bcg-brevo.php';
		require_once $includes . 'integrations/class-bcg-stripe.php';
	}

	/**
	 * Register core WordPress hooks.
	 *
	 * Activation and deactivation hooks are registered in the main
	 * bootstrap file (brevo-campaign-generator.php) to guarantee they
	 * are available before plugins_loaded fires. This method registers
	 * only the runtime hooks needed by the plugin.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function init_hooks(): void {
		// Initialise the plugin once WordPress is ready.
		add_action( 'init', array( $this, 'run' ) );
	}

	/**
	 * Hook everything up.
	 *
	 * Called on the `init` action. Instantiates admin-facing components
	 * (only within the admin context) and registers any front-end or
	 * global hooks needed by the plugin.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function run(): void {
		// Prevent double-initialisation.
		if ( $this->initialised ) {
			return;
		}
		$this->initialised = true;

		// Load text domain for translations.
		$this->load_textdomain();

		// Admin-only hooks.
		if ( is_admin() ) {
			$this->admin    = new BCG_Admin();
			$this->settings = new BCG_Settings();
			$this->credits  = new BCG_Credits();
			$this->stats    = new BCG_Stats();
		}

		/**
		 * Fires after the Brevo Campaign Generator plugin is fully loaded.
		 *
		 * @since 1.0.0
		 * @param BCG_Plugin $plugin The plugin instance.
		 */
		do_action( 'bcg_loaded', $this );
	}

	/**
	 * Load the plugin text domain for internationalisation.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function load_textdomain(): void {
		load_plugin_textdomain(
			'brevo-campaign-generator',
			false,
			dirname( BCG_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Get the admin handler instance.
	 *
	 * @since  1.0.0
	 * @return BCG_Admin|null
	 */
	public function get_admin(): ?object {
		return $this->admin;
	}

	/**
	 * Get the settings handler instance.
	 *
	 * @since  1.0.0
	 * @return BCG_Settings|null
	 */
	public function get_settings(): ?object {
		return $this->settings;
	}

	/**
	 * Get the credits handler instance.
	 *
	 * @since  1.0.0
	 * @return BCG_Credits|null
	 */
	public function get_credits(): ?object {
		return $this->credits;
	}

	/**
	 * Get the stats handler instance.
	 *
	 * @since  1.0.0
	 * @return BCG_Stats|null
	 */
	public function get_stats(): ?object {
		return $this->stats;
	}

	/**
	 * Prevent cloning of the singleton.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization of the singleton.
	 *
	 * @since  1.0.0
	 * @throws \Exception Always.
	 * @return void
	 */
	public function __wakeup() {
		throw new \Exception(
			esc_html__( 'Cannot unserialize a singleton.', 'brevo-campaign-generator' )
		);
	}
}
