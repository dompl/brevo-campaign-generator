<?php
/**
 * Plugin Name: Brevo Campaign Generator for WooCommerce
 * Plugin URI: https://github.com/red-frog-studio/brevo-campaign-generator
 * Description: Automatically generate and send Brevo email campaigns from WooCommerce using AI.
 * Version: 1.5.31
 * Author: Red Frog Studio
 * Author URI: https://redfrogstudio.co.uk
 * License: Proprietary
 * Text Domain: brevo-campaign-generator
 * Domain Path: /languages
 * Requires at least: 6.3
 * Requires PHP: 8.1
 * WC requires at least: 8.0
 *
 * @package Brevo_Campaign_Generator
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 *
 * @var string
 */
define( 'BCG_VERSION', '1.5.31' );

/**
 * Plugin directory path (with trailing slash).
 *
 * @var string
 */
define( 'BCG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL (with trailing slash).
 *
 * @var string
 */
define( 'BCG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Full path to the main plugin file.
 *
 * @var string
 */
define( 'BCG_PLUGIN_FILE', __FILE__ );

/**
 * Plugin basename (e.g. brevo-campaign-generator/brevo-campaign-generator.php).
 *
 * @var string
 */
define( 'BCG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check if WooCommerce is active.
 *
 * Inspects the active_plugins option to determine whether WooCommerce is
 * present and active. If it is not, an admin notice is displayed and the
 * plugin does not proceed with loading.
 *
 * @return bool True if WooCommerce is active, false otherwise.
 */
function bcg_is_woocommerce_active() {
	$active_plugins = (array) get_option( 'active_plugins', array() );

	if ( is_multisite() ) {
		$active_plugins = array_merge(
			$active_plugins,
			array_keys( get_site_option( 'active_sitewide_plugins', array() ) )
		);
	}

	return in_array( 'woocommerce/woocommerce.php', $active_plugins, true );
}

/**
 * Display an admin notice when WooCommerce is not active.
 *
 * @return void
 */
function bcg_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error is-dismissible">
		<p>
			<strong><?php esc_html_e( 'Brevo Campaign Generator for WooCommerce', 'brevo-campaign-generator' ); ?></strong>:
			<?php
			esc_html_e(
				'This plugin requires WooCommerce 8.0 or later to be installed and active. Please install and activate WooCommerce.',
				'brevo-campaign-generator'
			);
			?>
		</p>
	</div>
	<?php
}

// Bail early if WooCommerce is not active.
if ( ! bcg_is_woocommerce_active() ) {
	add_action( 'admin_notices', 'bcg_woocommerce_missing_notice' );
	return;
}

// Load Composer autoloader if available.
$bcg_autoloader = BCG_PLUGIN_DIR . 'vendor/autoload.php';
if ( file_exists( $bcg_autoloader ) ) {
	require_once $bcg_autoloader;
}

// Load the activator and deactivator early so hooks are registered immediately.
require_once BCG_PLUGIN_DIR . 'includes/class-bcg-activator.php';
require_once BCG_PLUGIN_DIR . 'includes/class-bcg-deactivator.php';

// Register activation/deactivation hooks directly from the main file so they
// are available before plugins_loaded fires. This is the WordPress-recommended
// pattern to ensure hooks fire reliably during activation/deactivation.
register_activation_hook( __FILE__, array( 'BCG_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'BCG_Deactivator', 'deactivate' ) );

// Load the main plugin class.
require_once BCG_PLUGIN_DIR . 'includes/class-bcg-plugin.php';

/**
 * Return the singleton instance of the plugin.
 *
 * This function serves as a convenient accessor to the main plugin object
 * and can be called from anywhere after the plugins_loaded hook.
 *
 * @return BCG_Plugin The plugin singleton instance.
 */
function bcg() {
	return BCG_Plugin::get_instance();
}

// Boot the plugin on plugins_loaded to ensure WooCommerce and other
// dependencies are fully available.
add_action( 'plugins_loaded', 'bcg' );
