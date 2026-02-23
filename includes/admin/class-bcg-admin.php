<?php
/**
 * Admin handler.
 *
 * Registers the top-level admin menu, all submenus, enqueues admin assets,
 * registers all AJAX handlers, and renders the credit balance widget in the
 * WordPress admin bar.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Admin
 *
 * Responsible for the admin-facing side of the plugin: menus, asset
 * enqueuing, AJAX handler registration, and the persistent credit
 * balance widget.
 *
 * @since 1.0.0
 */
class BCG_Admin {

	/**
	 * The capability required to access plugin pages.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_woocommerce';

	/**
	 * Top-level menu slug.
	 *
	 * @var string
	 */
	const MENU_SLUG = 'bcg-dashboard';

	/**
	 * Constructor.
	 *
	 * Registers all admin hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'admin_body_class', array( $this, 'add_bcg_body_class' ) );
		add_action( 'admin_bar_menu', array( $this, 'add_credit_widget_to_admin_bar' ), 100 );

		$this->register_ajax_handlers();
	}

	// ─── AJAX Handler Registration ─────────────────────────────────────

	/**
	 * Register all AJAX action handlers for the plugin.
	 *
	 * Campaign generation, regeneration, product management, template
	 * editing, Brevo integration, and utility endpoints are all wired up
	 * here. The actual handler methods reside in this class; handlers
	 * already registered by other classes are noted below.
	 *
	 * Handlers registered elsewhere (not duplicated here):
	 *  - bcg_test_api_key          (BCG_Settings)
	 *  - bcg_get_brevo_lists       (BCG_Settings)
	 *  - bcg_stripe_create_intent  (BCG_Credits)
	 *  - bcg_stripe_confirm        (BCG_Credits)
	 *  - bcg_get_credit_balance    (BCG_Credits)
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function register_ajax_handlers(): void {
		// Campaign generation.
		add_action( 'wp_ajax_bcg_generate_campaign', array( $this, 'handle_generate_campaign' ) );
		add_action( 'wp_ajax_bcg_regenerate_field', array( $this, 'handle_regenerate_field' ) );
		add_action( 'wp_ajax_bcg_regenerate_product', array( $this, 'handle_regenerate_product' ) );

		// Product management.
		add_action( 'wp_ajax_bcg_add_product', array( $this, 'handle_add_product' ) );
		add_action( 'wp_ajax_bcg_preview_products', array( $this, 'handle_preview_products' ) );
		add_action( 'wp_ajax_bcg_search_products', array( $this, 'handle_search_products' ) );

		// Campaign CRUD.
		add_action( 'wp_ajax_bcg_save_campaign', array( $this, 'handle_save_campaign' ) );
		add_action( 'wp_ajax_bcg_delete_campaign', array( $this, 'handle_delete_campaign' ) );
		add_action( 'wp_ajax_bcg_duplicate_campaign', array( $this, 'handle_duplicate_campaign' ) );

		// Email & Brevo.
		add_action( 'wp_ajax_bcg_send_test', array( $this, 'handle_send_test' ) );
		add_action( 'wp_ajax_bcg_create_brevo_campaign', array( $this, 'handle_create_brevo_campaign' ) );
		add_action( 'wp_ajax_bcg_send_campaign', array( $this, 'handle_send_campaign' ) );
		add_action( 'wp_ajax_bcg_schedule_campaign', array( $this, 'handle_schedule_campaign' ) );

		// Template.
		add_action( 'wp_ajax_bcg_update_template', array( $this, 'handle_update_template' ) );
		add_action( 'wp_ajax_bcg_preview_template', array( $this, 'handle_preview_template' ) );
		add_action( 'wp_ajax_bcg_reset_template', array( $this, 'handle_reset_template' ) );
		add_action( 'wp_ajax_bcg_load_template', array( $this, 'handle_load_template' ) );

		// Coupon.
		add_action( 'wp_ajax_bcg_generate_coupon', array( $this, 'handle_generate_coupon' ) );

		// Brevo senders.
		add_action( 'wp_ajax_bcg_get_brevo_senders', array( $this, 'handle_get_brevo_senders' ) );

		// Template Builder.
		add_action( 'wp_ajax_bcg_sb_preview',          array( $this, 'handle_sb_preview' ) );
		add_action( 'wp_ajax_bcg_sb_save_template',    array( $this, 'handle_sb_save_template' ) );
		add_action( 'wp_ajax_bcg_sb_get_templates',    array( $this, 'handle_sb_get_templates' ) );
		add_action( 'wp_ajax_bcg_get_section_templates', array( $this, 'handle_get_section_templates' ) );
		add_action( 'wp_ajax_bcg_sb_load_template',    array( $this, 'handle_sb_load_template' ) );
		add_action( 'wp_ajax_bcg_sb_delete_template',  array( $this, 'handle_sb_delete_template' ) );
		add_action( 'wp_ajax_bcg_sb_generate_all',     array( $this, 'handle_sb_generate_all' ) );
		add_action( 'wp_ajax_bcg_sb_generate_section', array( $this, 'handle_sb_generate_section' ) );
		add_action( 'wp_ajax_bcg_request_section',     array( $this, 'handle_request_section' ) );

		// Section campaign editor.
		add_action( 'wp_ajax_bcg_regen_campaign_section', array( $this, 'handle_regen_campaign_section' ) );
	}

	/**
	 * Register the top-level menu and all submenus.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register_menus(): void {
		// Top-level menu.
		add_menu_page(
			__( 'Brevo Campaigns', 'brevo-campaign-generator' ),
			__( 'Brevo Campaigns', 'brevo-campaign-generator' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_dashboard_page' ),
			'dashicons-email-alt2',
			58
		);

		// Dashboard (replaces the auto-generated top-level duplicate).
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Dashboard', 'brevo-campaign-generator' ),
			__( 'Dashboard', 'brevo-campaign-generator' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_dashboard_page' )
		);

		// New Campaign.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'New Campaign', 'brevo-campaign-generator' ),
			__( 'New Campaign', 'brevo-campaign-generator' ),
			self::CAPABILITY,
			'bcg-new-campaign',
			array( $this, 'render_new_campaign_page' )
		);

		// Template Editor.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Template Editor', 'brevo-campaign-generator' ),
			__( 'Template Editor', 'brevo-campaign-generator' ),
			self::CAPABILITY,
			'bcg-template-editor',
			array( $this, 'render_template_editor_page' )
		);

		// Brevo Stats.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Brevo Stats', 'brevo-campaign-generator' ),
			__( 'Brevo Stats', 'brevo-campaign-generator' ),
			self::CAPABILITY,
			'bcg-stats',
			array( $this, 'render_stats_page' )
		);

		// Credits & Billing.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Credits & Billing', 'brevo-campaign-generator' ),
			__( 'Credits & Billing', 'brevo-campaign-generator' ),
			self::CAPABILITY,
			'bcg-credits',
			array( $this, 'render_credits_page' )
		);

		// Settings — restricted to the Red Frog Studio admin account only.
		if ( $this->is_rfs_admin() ) {
			add_submenu_page(
				self::MENU_SLUG,
				__( 'Settings', 'brevo-campaign-generator' ),
				__( 'Settings', 'brevo-campaign-generator' ),
				self::CAPABILITY,
				'bcg-settings',
				array( $this, 'render_settings_page' )
			);
		}

		// Template Builder.
		$sb_hook = add_submenu_page(
			self::MENU_SLUG,
			__( 'Template Builder', 'brevo-campaign-generator' ),
			__( 'Template Builder', 'brevo-campaign-generator' ),
			self::CAPABILITY,
			'bcg-template-builder',
			array( $this, 'render_section_builder_page' )
		);
		add_action( 'load-' . $sb_hook, array( $this, 'add_section_builder_help_tabs' ) );

		// AI Trainer.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'AI Trainer', 'brevo-campaign-generator' ),
			__( 'AI Trainer', 'brevo-campaign-generator' ),
			self::CAPABILITY,
			'bcg-ai-trainer',
			array( $this, 'render_ai_trainer_page' )
		);

		// Help & Documentation.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Help & Docs', 'brevo-campaign-generator' ),
			__( 'Help & Docs', 'brevo-campaign-generator' ),
			self::CAPABILITY,
			'bcg-help',
			array( $this, 'render_help_page' )
		);

		// Edit Campaign (hidden — not shown in the nav menu).
		add_submenu_page(
			null,
			__( 'Edit Campaign', 'brevo-campaign-generator' ),
			__( 'Edit Campaign', 'brevo-campaign-generator' ),
			self::CAPABILITY,
			'bcg-edit-campaign',
			array( $this, 'render_edit_campaign_page' )
		);
	}

	/**
	 * Enqueue admin CSS and JS on plugin pages only.
	 *
	 * @since  1.0.0
	 * @param  string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		// Only load on our plugin pages.
		if ( ! $this->is_bcg_page( $hook_suffix ) ) {
			return;
		}

		// Admin CSS.
		wp_enqueue_style(
			'bcg-admin',
			BCG_PLUGIN_URL . 'admin/css/bcg-admin.css',
			array(),
			filemtime( BCG_PLUGIN_DIR . 'admin/css/bcg-admin.css' )
		);

		// Google Material Icons (Outlined variant).
		wp_enqueue_style(
			'bcg-material-icons',
			'https://fonts.googleapis.com/icon?family=Material+Icons+Outlined',
			array(),
			null
		);

		// Flowbite UI components (tooltips, modals, dropdowns).
		wp_enqueue_script(
			'bcg-flowbite',
			'https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js',
			array(),
			'2.3.0',
			true
		);

		// WordPress colour picker for template/settings.
		wp_enqueue_style( 'wp-color-picker' );

		// ── Global bcgData object (available on every BCG page) ─────────

		$settings_obj   = new BCG_Settings();
		$credit_balance = $this->get_current_user_credit_balance();

		$whats_new_items = array(
			array( 'icon' => 'new_releases',  'text' => __( 'Version number now shows in the header — click it any time to reopen this panel', 'brevo-campaign-generator' ) ),
			array( 'icon' => 'auto_awesome',  'text' => __( 'AI Prompt modal redesigned — better layout, clearer hints, and a single "Generate with AI" action', 'brevo-campaign-generator' ) ),
			array( 'icon' => 'check_circle',  'text' => __( 'Section Builder modals now display correctly — Preview Email and AI Prompt no longer appear behind the blurred overlay', 'brevo-campaign-generator' ) ),
			array( 'icon' => 'auto_awesome',  'text' => __( 'AI Prompt — describe your email brief before generating for fully tailored, on-brand results every time', 'brevo-campaign-generator' ) ),
			array( 'icon' => 'tune',          'text' => __( 'Section Builder tone and language dropdowns now work reliably', 'brevo-campaign-generator' ) ),
			array( 'icon' => 'school',        'text' => __( 'AI now uses your AI Trainer store and product context in every Section Builder generation', 'brevo-campaign-generator' ) ),
		);

		$bcg_data = array(
			'ajax_url'         => admin_url( 'admin-ajax.php' ),
			'nonce'            => wp_create_nonce( 'bcg_nonce' ),
			'credit_balance'   => $credit_balance,
			'stripe_pub_key'   => get_option( 'bcg_stripe_publishable_key', '' ),
			'openai_models'    => $settings_obj->get_openai_models(),
			'gemini_models'    => $settings_obj->get_gemini_models(),
			'openai_model'     => get_option( 'bcg_openai_model', 'gpt-4o' ),
			'gemini_model'     => get_option( 'bcg_gemini_model', 'gemini-1.5-flash' ),
			'credits_url'      => admin_url( 'admin.php?page=bcg-credits' ),
			'new_campaign_url' => admin_url( 'admin.php?page=bcg-new-campaign' ),
			'dashboard_url'    => admin_url( 'admin.php?page=bcg-dashboard' ),
			'version'          => BCG_VERSION,
			'whats_new'        => array(
				'version' => BCG_VERSION,
				'items'   => $whats_new_items,
			),
		);

		// Register a minimal inline script handle to localise bcgData against.
		wp_register_script( 'bcg-admin-global', '', array(), BCG_VERSION, false );
		wp_enqueue_script( 'bcg-admin-global' );
		wp_localize_script( 'bcg-admin-global', 'bcgData', $bcg_data );

		// Global UI helpers — custom selects, etc. Loaded on every BCG page.
		wp_enqueue_script(
			'bcg-settings',
			BCG_PLUGIN_URL . 'admin/js/bcg-settings.js',
			array( 'jquery', 'wp-color-picker' ),
			filemtime( BCG_PLUGIN_DIR . 'admin/js/bcg-settings.js' ),
			true
		);

		// ── Per-page scripts ───────────────────────────────────────────

		// Dashboard page JS.
		if ( 'toplevel_page_bcg-dashboard' === $hook_suffix ) {
			wp_enqueue_script(
				'bcg-dashboard',
				BCG_PLUGIN_URL . 'admin/js/bcg-dashboard.js',
				array( 'jquery' ),
				BCG_VERSION,
				true
			);
		}

		// New Campaign page — campaign wizard JS.
		if ( str_contains( $hook_suffix, 'bcg-new-campaign' ) ) {
			wp_enqueue_script(
				'bcg-campaign-builder',
				BCG_PLUGIN_URL . 'admin/js/bcg-campaign-builder.js',
				array( 'jquery' ),
				BCG_VERSION,
				true
			);

			$currency_code   = get_option( 'bcg_stripe_currency', 'GBP' );
			$currency_symbol = $settings_obj->get_currency_symbol( $currency_code );

			wp_localize_script(
				'bcg-campaign-builder',
				'bcg_campaign_builder',
				array(
					'ajax_url'             => admin_url( 'admin-ajax.php' ),
					'nonce'                => wp_create_nonce( 'bcg_nonce' ),
					'edit_url'             => admin_url( 'admin.php?page=bcg-edit-campaign' ),
					'template_builder_url' => admin_url( 'admin.php?page=bcg-template-builder' ),
					'currency_symbol'      => $currency_symbol,
					'i18n'            => array(
						'title_required'          => __( 'Campaign title is required.', 'brevo-campaign-generator' ),
						'manual_products_required' => __( 'Please select at least one product for manual selection.', 'brevo-campaign-generator' ),
						'discount_required'       => __( 'Please enter a valid discount value.', 'brevo-campaign-generator' ),
						'generation_error'        => __( 'AI generation failed. Please try again.', 'brevo-campaign-generator' ),
						'generation_failed'       => __( 'Campaign generation failed. Please try again.', 'brevo-campaign-generator' ),
						'generation_timeout'      => __( 'Campaign generation timed out. Please try again.', 'brevo-campaign-generator' ),
						'preview_error'           => __( 'Failed to load product preview. Please try again.', 'brevo-campaign-generator' ),
						'no_products_found'       => __( 'No products found.', 'brevo-campaign-generator' ),
						'select_list'             => __( '-- Select a mailing list --', 'brevo-campaign-generator' ),
						'error_loading_lists'     => __( 'Error loading lists — click Refresh to retry', 'brevo-campaign-generator' ),
						'subscribers'             => __( 'subscribers', 'brevo-campaign-generator' ),
						'sales'                   => __( 'sales', 'brevo-campaign-generator' ),
						'remove'                  => __( 'Remove', 'brevo-campaign-generator' ),
						'already_added'           => __( 'Already added', 'brevo-campaign-generator' ),
					),
				)
			);
		}

		// Settings page — localise bcg_settings for test-connection / list-refresh handlers.
		if ( str_contains( $hook_suffix, 'bcg-settings' ) ) {
			wp_localize_script(
				'bcg-settings',
				'bcg_settings',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'bcg_nonce' ),
					'i18n'     => array(
						'testing'    => __( 'Testing...', 'brevo-campaign-generator' ),
						'success'    => __( 'Connection successful!', 'brevo-campaign-generator' ),
						'error'      => __( 'Connection failed.', 'brevo-campaign-generator' ),
						'save_first' => __( 'Please save your settings before testing.', 'brevo-campaign-generator' ),
					),
				)
			);
		}

		// Edit Campaign page — campaign editor JS.
		if ( str_contains( $hook_suffix, 'bcg-edit-campaign' ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_style( 'wp-jquery-ui-dialog' );

			wp_enqueue_script(
				'bcg-regenerate',
				BCG_PLUGIN_URL . 'admin/js/bcg-regenerate.js',
				array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-datepicker', 'wp-util' ),
				BCG_VERSION,
				true
			);

			$campaign_id = isset( $_GET['campaign_id'] ) ? absint( $_GET['campaign_id'] ) : 0;

			wp_localize_script(
				'bcg-regenerate',
				'bcg_editor',
				array(
					'ajax_url'    => admin_url( 'admin-ajax.php' ),
					'nonce'       => wp_create_nonce( 'bcg_nonce' ),
					'campaign_id' => $campaign_id,
					'i18n'        => array(
						'regenerating'      => __( 'Regenerating...', 'brevo-campaign-generator' ),
						'saving'            => __( 'Saving...', 'brevo-campaign-generator' ),
						'saved'             => __( 'Campaign saved.', 'brevo-campaign-generator' ),
						'save_error'        => __( 'Failed to save campaign.', 'brevo-campaign-generator' ),
						'regen_error'       => __( 'Regeneration failed.', 'brevo-campaign-generator' ),
						'confirm_remove'    => __( 'Remove this product from the campaign?', 'brevo-campaign-generator' ),
						'confirm_send'      => __( 'Are you sure you want to send this campaign immediately? This cannot be undone.', 'brevo-campaign-generator' ),
						'sending'           => __( 'Sending...', 'brevo-campaign-generator' ),
						'scheduling'        => __( 'Scheduling...', 'brevo-campaign-generator' ),
						'creating_brevo'    => __( 'Creating in Brevo...', 'brevo-campaign-generator' ),
						'brevo_created'     => __( 'Campaign created in Brevo!', 'brevo-campaign-generator' ),
						'send_test_label'   => __( 'Sending test email...', 'brevo-campaign-generator' ),
						'test_sent'         => __( 'Test email sent successfully.', 'brevo-campaign-generator' ),
						'searching'         => __( 'Searching products...', 'brevo-campaign-generator' ),
						'no_results'        => __( 'No products found.', 'brevo-campaign-generator' ),
						'adding_product'    => __( 'Adding product...', 'brevo-campaign-generator' ),
						'product_added'     => __( 'Product added to campaign.', 'brevo-campaign-generator' ),
						'order_saved'       => __( 'Product order updated.', 'brevo-campaign-generator' ),
						'schedule_title'    => __( 'Schedule Campaign', 'brevo-campaign-generator' ),
						'scheduled'         => __( 'Campaign scheduled successfully.', 'brevo-campaign-generator' ),
						'campaign_sent'     => __( 'Campaign sent successfully!', 'brevo-campaign-generator' ),
						'select_datetime'   => __( 'Please select a date and time.', 'brevo-campaign-generator' ),
						'insufficient_credits' => __( 'Insufficient credits. Please top up before regenerating.', 'brevo-campaign-generator' ),
					),
				)
			);
		}

		// Template Editor page.
		if ( str_contains( $hook_suffix, 'bcg-template-editor' ) ) {
			// WordPress code editor (CodeMirror) — soft dependency.
			// wp_enqueue_code_editor() returns false when user has syntax
			// highlighting disabled, so it must not be a hard dependency
			// of the main template-editor script.
			$code_editor_settings = wp_enqueue_code_editor( array( 'type' => 'text/html' ) );

			// WordPress media uploader.
			wp_enqueue_media();

			// Core dependencies only — CodeMirror is loaded lazily at
			// runtime inside initCodeMirror() with a typeof check.
			wp_enqueue_script(
				'bcg-template-editor',
				BCG_PLUGIN_URL . 'admin/js/bcg-template-editor.js',
				array( 'jquery', 'wp-util' ),
				filemtime( BCG_PLUGIN_DIR . 'admin/js/bcg-template-editor.js' ),
				true
			);

			wp_localize_script(
				'bcg-template-editor',
				'bcg_template_editor',
				array(
					'ajax_url'      => admin_url( 'admin-ajax.php' ),
					'nonce'         => wp_create_nonce( 'bcg_nonce' ),
					'section_names' => array(
						'header'   => __( 'Header', 'brevo-campaign-generator' ),
						'hero'     => __( 'Hero Image', 'brevo-campaign-generator' ),
						'headline' => __( 'Headline', 'brevo-campaign-generator' ),
						'coupon'   => __( 'Coupon', 'brevo-campaign-generator' ),
						'products' => __( 'Products', 'brevo-campaign-generator' ),
						'cta'      => __( 'CTA Button', 'brevo-campaign-generator' ),
						'divider'  => __( 'Divider', 'brevo-campaign-generator' ),
						'footer'   => __( 'Footer', 'brevo-campaign-generator' ),
					),
					'i18n'          => array(
						'updating'              => __( 'Updating...', 'brevo-campaign-generator' ),
						'preview_error'         => __( 'Preview error', 'brevo-campaign-generator' ),
						'saving'                => __( 'Saving...', 'brevo-campaign-generator' ),
						'saved'                 => __( 'Template saved.', 'brevo-campaign-generator' ),
						'save_error'            => __( 'Failed to save template.', 'brevo-campaign-generator' ),
						'resetting'             => __( 'Resetting...', 'brevo-campaign-generator' ),
						'reset_success'         => __( 'Template reset to default.', 'brevo-campaign-generator' ),
						'reset_error'           => __( 'Failed to reset template.', 'brevo-campaign-generator' ),
						'confirm_reset'         => __( 'Are you sure you want to reset the template to the default? This cannot be undone.', 'brevo-campaign-generator' ),
						'select_image'          => __( 'Select Image', 'brevo-campaign-generator' ),
						'use_image'             => __( 'Use this image', 'brevo-campaign-generator' ),
						'confirm_switch'        => __( 'Switching templates will replace your current HTML and settings. Any unsaved changes will be lost. Continue?', 'brevo-campaign-generator' ),
						'loading_template'      => __( 'Loading template...', 'brevo-campaign-generator' ),
						'template_loaded'       => __( 'Template loaded successfully.', 'brevo-campaign-generator' ),
						'template_error'        => __( 'Failed to load template.', 'brevo-campaign-generator' ),
						'current'               => __( 'Current', 'brevo-campaign-generator' ),
						'confirm_delete_section' => __( 'Delete this section?', 'brevo-campaign-generator' ),
					),
				)
			);
		}

		// Section Builder page.
		if ( str_contains( $hook_suffix, 'bcg-template-builder' ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'jquery-ui-sortable' );

			wp_enqueue_script(
				'bcg-section-builder',
				BCG_PLUGIN_URL . 'admin/js/bcg-section-builder.js',
				array( 'jquery', 'jquery-ui-sortable', 'wp-util' ),
				filemtime( BCG_PLUGIN_DIR . 'admin/js/bcg-section-builder.js' ),
				true
			);

			$currency_code   = get_option( 'bcg_stripe_currency', 'GBP' );
			$currency_symbol = $settings_obj->get_currency_symbol( $currency_code );

			wp_localize_script(
				'bcg-section-builder',
				'bcg_section_builder',
				array(
					'ajax_url'        => admin_url( 'admin-ajax.php' ),
					'nonce'           => wp_create_nonce( 'bcg_nonce' ),
					'section_types'   => BCG_Section_Registry::get_all_for_js(),
					'presets'         => BCG_Section_Presets::get_all_for_js(),
					'currency_symbol' => $currency_symbol,
					'current_user'    => array(
						'name'  => wp_get_current_user()->display_name,
						'email' => wp_get_current_user()->user_email,
					),
					'site_url'        => get_bloginfo( 'url' ),
					'i18n'          => array(
						'confirm_delete'   => __( 'Delete this section?', 'brevo-campaign-generator' ),
						'unsaved_changes'  => __( 'You have unsaved changes. Leave anyway?', 'brevo-campaign-generator' ),
						'saving'           => __( 'Saving...', 'brevo-campaign-generator' ),
						'saved'            => __( 'Template saved.', 'brevo-campaign-generator' ),
						'save_error'       => __( 'Failed to save template.', 'brevo-campaign-generator' ),
						'loading'          => __( 'Loading...', 'brevo-campaign-generator' ),
						'generating'       => __( 'Generating AI content...', 'brevo-campaign-generator' ),
						'generate_error'   => __( 'AI generation failed. Please try again.', 'brevo-campaign-generator' ),
						'preview_error'    => __( 'Preview error.', 'brevo-campaign-generator' ),
						'name_required'    => __( 'Template name is required.', 'brevo-campaign-generator' ),
						'no_sections'      => __( 'Add at least one section before saving.', 'brevo-campaign-generator' ),
						'confirm_load'     => __( 'Loading a template will replace your current canvas. Continue?', 'brevo-campaign-generator' ),
						'confirm_del_tmpl' => __( 'Delete this saved template? This cannot be undone.', 'brevo-campaign-generator' ),
						'select_image'     => __( 'Select Image', 'brevo-campaign-generator' ),
						'use_image'        => __( 'Use this image', 'brevo-campaign-generator' ),
						'move_up'          => __( 'Move up', 'brevo-campaign-generator' ),
						'move_down'        => __( 'Move down', 'brevo-campaign-generator' ),
						'edit_settings'    => __( 'Edit settings', 'brevo-campaign-generator' ),
						'remove'           => __( 'Remove', 'brevo-campaign-generator' ),
						'generate_section' => __( 'Generate with AI', 'brevo-campaign-generator' ),
					),
				)
			);
		}

		// Credits & Billing page — Stripe.js integration.
		if ( str_contains( $hook_suffix, 'bcg-credits' ) ) {
			// Load Stripe.js from CDN.
			wp_enqueue_script(
				'stripe-js',
				'https://js.stripe.com/v3/',
				array(),
				null, // Stripe manages its own versioning.
				true
			);

			// Load our Stripe handler.
			wp_enqueue_script(
				'bcg-stripe',
				BCG_PLUGIN_URL . 'admin/js/bcg-stripe.js',
				array( 'jquery', 'stripe-js' ),
				BCG_VERSION,
				true
			);

			$currency_code   = get_option( 'bcg_stripe_currency', 'GBP' );
			$currency_symbol = $settings_obj->get_currency_symbol( $currency_code );

			wp_localize_script(
				'bcg-stripe',
				'bcg_stripe',
				array(
					'ajax_url'        => admin_url( 'admin-ajax.php' ),
					'nonce'           => wp_create_nonce( 'bcg_nonce' ),
					'publishable_key' => get_option( 'bcg_stripe_publishable_key', '' ),
					'currency'        => strtolower( $currency_code ),
					'currency_symbol' => $currency_symbol,
					'i18n'            => array(
						'purchase'              => __( 'Purchase', 'brevo-campaign-generator' ),
						'preparing'             => __( 'Preparing...', 'brevo-campaign-generator' ),
						'processing'            => __( 'Processing...', 'brevo-campaign-generator' ),
						'pay_now'               => __( 'Pay Now', 'brevo-campaign-generator' ),
						'adding_credits'        => __( 'Adding credits...', 'brevo-campaign-generator' ),
						'payment_failed'        => __( 'Payment could not be completed. Please try again.', 'brevo-campaign-generator' ),
						'generic_error'         => __( 'An unexpected error occurred. Please try again.', 'brevo-campaign-generator' ),
						'error_prefix'          => __( 'Error:', 'brevo-campaign-generator' ),
						'stripe_not_configured' => __( 'Stripe is not configured. Please add your Stripe API keys in the Settings page.', 'brevo-campaign-generator' ),
					),
				)
			);
		}
	}

	/**
	 * Determine if the current admin page belongs to this plugin.
	 *
	 * @since  1.0.0
	 * @param  string $hook_suffix The current admin page hook suffix.
	 * @return bool
	 */
	/**
	 * Check whether the current user is the Red Frog Studio admin account.
	 *
	 * Only the account with email address info@redfrogstudio.co.uk has access
	 * to the Settings page and its related AJAX handlers.
	 *
	 * @since  1.5.17
	 * @return bool
	 */
	private function is_rfs_admin(): bool {
		$user = wp_get_current_user();
		return $user instanceof \WP_User && 'info@redfrogstudio.co.uk' === $user->user_email;
	}

	private function is_bcg_page( string $hook_suffix ): bool {
		$bcg_pages = array(
			'toplevel_page_bcg-dashboard',
			'brevo-campaigns_page_bcg-new-campaign',
			'brevo-campaigns_page_bcg-template-editor',
			'brevo-campaigns_page_bcg-template-builder',
			'brevo-campaigns_page_bcg-stats',
			'brevo-campaigns_page_bcg-credits',
			'brevo-campaigns_page_bcg-settings',
			'brevo-campaigns_page_bcg-edit-campaign',
			'admin_page_bcg-edit-campaign',
			'brevo-campaigns_page_bcg-ai-trainer',
			'brevo-campaigns_page_bcg-section-builder',
			'brevo-campaigns_page_bcg-help',
		);

		return in_array( $hook_suffix, $bcg_pages, true );
	}

	// ─── AJAX Handlers ─────────────────────────────────────────────────

	/**
	 * Handle full campaign generation via AJAX.
	 *
	 * Orchestrates the entire campaign creation flow:
	 * 1. Validate inputs (title, products config, campaign config)
	 * 2. Create a draft campaign via BCG_Campaign
	 * 3. Select products via BCG_Product_Selector
	 * 4. Generate a WooCommerce coupon if requested
	 * 5. Generate campaign copy via BCG_AI_Manager
	 * 6. Generate campaign images via BCG_AI_Manager (if enabled)
	 * 7. Update the campaign record with all generated content
	 * 8. Return campaign ID + redirect URL
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_generate_campaign(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) ) );
		}

		// ── 1. Validate and sanitise inputs ─────────────────────────────

		// Accept both 'title' and 'campaign_title' (Step 1 sends campaign_title).
		$title = isset( $_POST['campaign_title'] ) ? sanitize_text_field( wp_unslash( $_POST['campaign_title'] ) ) : '';
		if ( empty( $title ) && isset( $_POST['title'] ) ) {
			$title = sanitize_text_field( wp_unslash( $_POST['title'] ) );
		}

		if ( empty( $title ) ) {
			// Auto-generate a title based on current date.
			$title = sprintf(
				/* translators: %s: formatted date */
				__( 'Campaign — %s', 'brevo-campaign-generator' ),
				date_i18n( 'j M Y' )
			);
		}

		// Accept both 'subject' and 'subject_line'.
		$subject = isset( $_POST['subject_line'] ) ? sanitize_text_field( wp_unslash( $_POST['subject_line'] ) ) : '';
		if ( empty( $subject ) && isset( $_POST['subject'] ) ) {
			$subject = sanitize_text_field( wp_unslash( $_POST['subject'] ) );
		}

		$preview_text    = isset( $_POST['preview_text'] ) ? sanitize_text_field( wp_unslash( $_POST['preview_text'] ) ) : '';
		$mailing_list_id = isset( $_POST['mailing_list_id'] ) ? sanitize_text_field( wp_unslash( $_POST['mailing_list_id'] ) ) : '';

		// Product selection config.
		$product_source  = isset( $_POST['product_source'] ) ? sanitize_text_field( wp_unslash( $_POST['product_source'] ) ) : 'bestsellers';
		$product_count   = isset( $_POST['product_count'] ) ? absint( $_POST['product_count'] ) : (int) get_option( 'bcg_default_products_per_campaign', 3 );
		$category_ids    = isset( $_POST['category_ids'] ) && is_array( $_POST['category_ids'] ) ? array_map( 'absint', $_POST['category_ids'] ) : array();

		// Accept both 'manual_ids' and 'manual_product_ids'.
		$manual_ids = array();
		if ( isset( $_POST['manual_product_ids'] ) && is_array( $_POST['manual_product_ids'] ) ) {
			$manual_ids = array_map( 'absint', $_POST['manual_product_ids'] );
		} elseif ( isset( $_POST['manual_ids'] ) && is_array( $_POST['manual_ids'] ) ) {
			$manual_ids = array_map( 'absint', $_POST['manual_ids'] );
		}

		// Coupon config — accept both 'generate_coupon' and 'coupon_*' / 'discount_*' names.
		$generate_coupon  = ! empty( $_POST['generate_coupon'] );
		$discount_type    = isset( $_POST['coupon_type'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_type'] ) ) : '';
		if ( empty( $discount_type ) && isset( $_POST['discount_type'] ) ) {
			$discount_type = sanitize_text_field( wp_unslash( $_POST['discount_type'] ) );
		}
		if ( empty( $discount_type ) ) {
			$discount_type = 'percent';
		}

		$discount_value = 0;
		if ( isset( $_POST['coupon_discount'] ) ) {
			$discount_value = (float) $_POST['coupon_discount'];
		} elseif ( isset( $_POST['discount_value'] ) ) {
			$discount_value = (float) $_POST['discount_value'];
		}
		if ( $discount_value <= 0 ) {
			$discount_value = (float) get_option( 'bcg_default_coupon_discount', 10 );
		}

		$expiry_days = 0;
		if ( isset( $_POST['coupon_expiry_days'] ) ) {
			$expiry_days = absint( $_POST['coupon_expiry_days'] );
		} elseif ( isset( $_POST['expiry_days'] ) ) {
			$expiry_days = absint( $_POST['expiry_days'] );
		}
		if ( $expiry_days <= 0 ) {
			$expiry_days = (int) get_option( 'bcg_default_coupon_expiry_days', 7 );
		}

		$coupon_prefix = isset( $_POST['coupon_prefix'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_prefix'] ) ) : '';

		// AI config.
		$tone             = isset( $_POST['tone'] ) ? sanitize_text_field( wp_unslash( $_POST['tone'] ) ) : 'Professional';
		$theme            = isset( $_POST['theme'] ) ? sanitize_text_field( wp_unslash( $_POST['theme'] ) ) : '';
		$language         = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : 'English';
		$generate_images  = ! empty( $_POST['generate_images'] );
		$image_style      = isset( $_POST['image_style'] ) ? sanitize_text_field( wp_unslash( $_POST['image_style'] ) ) : 'Photorealistic';

		// Validate manual selection has products.
		if ( 'manual' === $product_source && empty( $manual_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'Please select at least one product for manual selection.', 'brevo-campaign-generator' ) ) );
		}

		// Template selection.
		$template_slug = isset( $_POST['template_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['template_slug'] ) ) : 'classic';
		$section_template_id = isset( $_POST['section_template_id'] ) ? absint( $_POST['section_template_id'] ) : 0;

		// ── 2. Create draft campaign ────────────────────────────────────

		$campaign_handler  = new BCG_Campaign();
		$template_engine   = new BCG_Template();
		$template_registry = BCG_Template_Registry::get_instance();

		// For section templates, use a placeholder until sections are rendered.
		if ( $section_template_id > 0 ) {
			$template_slug = 'sections';
			$tpl_html      = '';
			$tpl_settings  = $template_engine->get_default_settings();
		} else {
			// Use the selected flat template's HTML and settings, falling back to default.
			$tpl_html     = $template_registry->get_template_html( $template_slug );
			$tpl_settings = $template_registry->get_template_settings( $template_slug );

			if ( empty( $tpl_html ) ) {
				$tpl_html = $template_engine->get_default_template();
			}
			if ( empty( $tpl_settings ) ) {
				$tpl_settings = $template_engine->get_default_settings();
			}
		}

		$draft_data = array(
			'title'               => $title,
			'subject'             => $subject,
			'preview_text'        => $preview_text,
			'mailing_list_id'     => $mailing_list_id,
			'template_slug'       => $template_slug,
			'template_html'       => $tpl_html,
			'template_settings'   => wp_json_encode( $tpl_settings ),
			'builder_type'        => $section_template_id > 0 ? 'sections' : 'flat',
			'section_template_id' => $section_template_id > 0 ? $section_template_id : null,
		);

		$campaign_id = $campaign_handler->create_draft( $draft_data );

		if ( is_wp_error( $campaign_id ) ) {
			wp_send_json_error( array( 'message' => $campaign_id->get_error_message() ) );
		}

		// ── 3. Select products ──────────────────────────────────────────

		$product_selector = new BCG_Product_Selector();
		$products         = $product_selector->get_products( array(
			'count'        => $product_count,
			'source'       => $product_source,
			'category_ids' => $category_ids,
			'manual_ids'   => $manual_ids,
		) );

		if ( empty( $products ) ) {
			wp_send_json_error( array( 'message' => __( 'No products found matching your selection criteria.', 'brevo-campaign-generator' ) ) );
		}

		// Build lightweight product data for AI prompts.
		$product_data_for_ai = array();
		foreach ( $products as $wc_product ) {
			if ( ! $wc_product instanceof \WC_Product ) {
				continue;
			}
			$product_data_for_ai[] = $product_selector->format_product_preview( $wc_product );
		}

		// ── 4. Generate coupon if requested ──────────────────────────────

		$coupon_code = '';
		if ( $generate_coupon && $discount_value > 0 ) {
			$coupon_handler = new BCG_Coupon();
			$coupon_result  = $coupon_handler->create_coupon(
				$campaign_id,
				$discount_value,
				$discount_type,
				$expiry_days,
				$coupon_prefix
			);

			if ( is_wp_error( $coupon_result ) ) {
				// Non-fatal: continue without coupon.
				$coupon_code = '';
			} else {
				$coupon_code = $coupon_result;
			}
		}

		// ── 4b. Section template: inject coupon + products + run AI ──────────

		$sections_json_encoded = '';

		if ( $section_template_id > 0 ) {
			$section_tpl = BCG_Section_Templates_Table::get( $section_template_id );

			if ( ! is_wp_error( $section_tpl ) ) {
				$sections = json_decode( $section_tpl->sections, true );

				if ( is_array( $sections ) ) {
					// Inject coupon data into any Coupon-type sections.
					if ( ! empty( $coupon_code ) ) {
						$currency_sym  = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '£';
						$discount_text = 'percent' === $discount_type
							? sprintf( '%.0f%% off your order', $discount_value )
							: sprintf( '%s%.2f off your order', $currency_sym, $discount_value );
						$expiry_text   = sprintf( 'Valid for %d days', $expiry_days );

						foreach ( $sections as &$section ) {
							if ( isset( $section['type'] ) && 'coupon' === $section['type'] ) {
								$section['settings']['coupon_code']   = $coupon_code;
								$section['settings']['discount_text'] = $discount_text;
								$section['settings']['expiry_text']   = $expiry_text;
							}
						}
						unset( $section );
					}

					// Inject campaign products into any Products sections that have no IDs set.
					if ( ! empty( $product_data_for_ai ) ) {
						$product_ids_str = implode( ',', array_map( fn( $p ) => (int) $p['id'], $product_data_for_ai ) );
						foreach ( $sections as &$section ) {
							if ( isset( $section['type'] ) && 'products' === $section['type'] ) {
								if ( empty( trim( $section['settings']['product_ids'] ?? '' ) ) ) {
									$section['settings']['product_ids'] = $product_ids_str;
								}
							}
						}
						unset( $section );
					}

					// Run AI generation for all AI-capable sections.
					$currency_symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '£';
					$ai_context      = array(
						'products'        => $product_data_for_ai,
						'theme'           => $theme,
						'tone'            => $tone,
						'language'        => $language,
						'currency_symbol' => $currency_symbol,
					);

					$sections = BCG_Section_AI::generate_all( $sections, $ai_context );

					// Render sections to email-safe HTML using global template settings.
					$global_settings = json_decode( get_option( 'bcg_default_template_settings', '{}' ), true );
					if ( ! is_array( $global_settings ) ) {
						$global_settings = array();
					}

					$rendered_html         = BCG_Section_Renderer::render_sections( $sections, $global_settings );
					$sections_json_encoded = wp_json_encode( $sections );

					// Persist rendered HTML and sections JSON to the campaign.
					$campaign_handler->update( $campaign_id, array(
						'template_html'       => $rendered_html,
						'sections_json'       => $sections_json_encoded,
						'section_template_id' => $section_template_id,
					) );
				}
			}
		}

		// ── 5 & 6. AI generation — section vs flat templates ─────────────

		$ai_manager = new BCG_AI_Manager();

		if ( $section_template_id > 0 ) {
			// Section template: sections already AI-populated in step 4b.
			// Only finalise subject/preview_text from user input.
			$campaign_handler->update( $campaign_id, array(
				'subject'      => ! empty( $subject ) ? $subject : $title,
				'preview_text' => $preview_text,
				'status'       => 'draft',
			) );
		} else {
			// Flat template: full AI copy + image generation.
			$copy_result = $ai_manager->generate_campaign_copy(
				$campaign_id,
				$product_data_for_ai,
				array(
					'theme'           => $theme,
					'tone'            => $tone,
					'language'        => $language,
					'generate_coupon' => false,
				)
			);

			if ( is_wp_error( $copy_result ) ) {
				wp_send_json_error( array( 'message' => $copy_result->get_error_message() ) );
			}

			// ── 6. Images ────────────────────────────────────────────────────
			$main_image_url = '';
			$product_images = array();
			$image_result   = array( 'total_credits_used' => 0 );

			if ( $generate_images ) {
				$gen_images = $ai_manager->generate_campaign_images(
					$campaign_id,
					$product_data_for_ai,
					array(
						'theme'                   => $theme,
						'style'                   => $image_style,
						'generate_product_images' => true,
					)
				);

				if ( ! is_wp_error( $gen_images ) ) {
					$main_image_url = $gen_images['main_image_url'] ?? '';
					$product_images = $gen_images['product_images'] ?? array();
					$image_result   = $gen_images;
				}
			}

			// ── 7. Update campaign ─────────────────────────────────────────
			$campaign_handler->update( $campaign_id, array(
				'subject'          => ! empty( $copy_result['subject_line'] ) ? $copy_result['subject_line'] : $subject,
				'preview_text'     => ! empty( $copy_result['preview_text'] ) ? $copy_result['preview_text'] : $preview_text,
				'main_headline'    => $copy_result['main_headline'] ?? '',
				'main_description' => $copy_result['main_description'] ?? '',
				'main_image_url'   => $main_image_url,
				'status'           => 'draft',
			) );

			// Add products to bcg_campaign_products.
			foreach ( $copy_result['products'] ?? array() as $index => $product_entry ) {
				$product_id = absint( $product_entry['id'] ?? 0 );
				if ( $product_id <= 0 ) {
					continue;
				}

				$ai_data = array(
					'sort_order'        => $index,
					'ai_headline'       => $product_entry['ai_headline'] ?? '',
					'ai_short_desc'     => $product_entry['ai_short_desc'] ?? '',
					'use_product_image' => $generate_images ? 0 : 1,
					'show_buy_button'   => 1,
				);

				if ( ! empty( $product_images[ $index ] ) ) {
					$ai_data['generated_image_url'] = $product_images[ $index ];
				}

				$campaign_handler->add_product( $campaign_id, $product_id, $ai_data );
			}
		}

		// ── 8. Return success with redirect URL ──────────────────────────

		$edit_url = admin_url( 'admin.php?page=bcg-edit-campaign&campaign_id=' . $campaign_id );

		wp_send_json_success( array(
			'message'      => __( 'Campaign generated successfully!', 'brevo-campaign-generator' ),
			'campaign_id'  => $campaign_id,
			'redirect_url' => $edit_url,
			'credits_used' => isset( $copy_result ) ? ( ( $copy_result['total_credits_used'] ?? 0 ) + ( $image_result['total_credits_used'] ?? 0 ) ) : 0,
			'new_balance'  => $ai_manager->get_credit_balance(),
		) );
	}

	/**
	 * Handle single field regeneration via AJAX.
	 *
	 * Regenerates a single campaign field (subject line, headline, description,
	 * preview text, product headline, product description, coupon suggestion,
	 * main image, or product image) using the AI manager.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_regenerate_field(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) ) );
		}

		$campaign_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;
		$field       = isset( $_POST['field'] ) ? sanitize_text_field( wp_unslash( $_POST['field'] ) ) : '';

		if ( empty( $field ) ) {
			wp_send_json_error( array( 'message' => __( 'No field specified for regeneration.', 'brevo-campaign-generator' ) ) );
		}

		// Retrieve tone/theme/language from POST or fall back to defaults.
		$tone     = isset( $_POST['tone'] ) ? sanitize_text_field( wp_unslash( $_POST['tone'] ) ) : 'Professional';
		$theme    = isset( $_POST['theme'] ) ? sanitize_text_field( wp_unslash( $_POST['theme'] ) ) : '';
		$language = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : 'English';
		$style    = isset( $_POST['image_style'] ) ? sanitize_text_field( wp_unslash( $_POST['image_style'] ) ) : 'Photorealistic';
		$subject  = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
		$title    = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';

		$product_selector = new BCG_Product_Selector();
		$products_data    = array();
		$single_product   = array();
		$product_row_id   = isset( $_POST['product_row_id'] ) ? absint( $_POST['product_row_id'] ) : 0;
		$campaign_handler = new BCG_Campaign();

		if ( $campaign_id ) {
			// Load the campaign to build context.
			$campaign = $campaign_handler->get( $campaign_id );

			if ( is_wp_error( $campaign ) ) {
				wp_send_json_error( array( 'message' => $campaign->get_error_message() ) );
			}

			// Build product data for AI context.
			foreach ( $campaign->products as $product_row ) {
				$wc_product = wc_get_product( (int) $product_row->product_id );
				if ( $wc_product instanceof \WC_Product ) {
					$products_data[] = $product_selector->format_product_preview( $wc_product );
				}
			}

			// Build single product data if this is a product-level field.
			if ( $product_row_id > 0 ) {
				foreach ( $campaign->products as $product_row ) {
					if ( (int) $product_row->id === $product_row_id ) {
						$wc_product = wc_get_product( (int) $product_row->product_id );
						if ( $wc_product instanceof \WC_Product ) {
							$single_product = $product_selector->format_product_preview( $wc_product );
						}
						break;
					}
				}
			}

			// Use campaign data for context fallbacks.
			if ( empty( $subject ) ) {
				$subject = $campaign->subject ?? '';
			}
			if ( empty( $title ) ) {
				$title = $campaign->title ?? '';
			}
		}

		$context = array(
			'products' => $products_data,
			'product'  => $single_product,
			'theme'    => $theme,
			'tone'     => $tone,
			'language' => $language,
			'subject'  => $subject,
			'title'    => $title,
			'style'    => $style,
		);

		$ai_manager = new BCG_AI_Manager();
		$result     = $ai_manager->regenerate_field( $campaign_id, $field, $context );

		if ( is_wp_error( $result ) ) {
			$err_msg = $result->get_error_message();
			// Gemini image generation is geo-restricted. Provide a clear explanation.
			if ( false !== strpos( $err_msg, 'not available in your country' ) || false !== strpos( $err_msg, 'Image generation' ) ) {
				$err_msg = __( 'AI image generation is not available in your region. Please use the "Use Custom Image" button to upload an image from your media library instead.', 'brevo-campaign-generator' );
			}
			wp_send_json_error( array( 'message' => $err_msg ) );
		}

		// Auto-save the regenerated content to the DB (only if campaign exists).
		if ( $campaign_id ) {
			$campaign_field_map = array(
				'subject_line'     => 'subject',
				'preview_text'     => 'preview_text',
				'main_headline'    => 'main_headline',
				'main_description' => 'main_description',
				'main_image'       => 'main_image_url',
			);

			$product_field_map = array(
				'product_headline'   => 'ai_headline',
				'product_short_desc' => 'ai_short_desc',
				'product_image'      => 'generated_image_url',
			);

			if ( isset( $campaign_field_map[ $field ] ) ) {
				$campaign_handler->update( $campaign_id, array(
					$campaign_field_map[ $field ] => $result['content'],
				) );
			} elseif ( isset( $product_field_map[ $field ] ) && $product_row_id > 0 ) {
				$campaign_handler->update_product( $product_row_id, array(
					$product_field_map[ $field ] => $result['content'],
				) );
			}
		}

		wp_send_json_success( array(
			'content'      => $result['content'],
			'credits_used' => $result['credits_used'] ?? 0,
			'new_balance'  => $ai_manager->get_credit_balance(),
		) );
	}

	/**
	 * Handle product AI content regeneration via AJAX.
	 *
	 * Regenerates all AI content for a single product in a campaign:
	 * headline, short description, and optionally the product image.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_regenerate_product(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) ) );
		}

		$campaign_id    = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;
		$product_row_id = isset( $_POST['product_row_id'] ) ? absint( $_POST['product_row_id'] ) : 0;
		$regen_image    = ! empty( $_POST['regenerate_image'] );

		if ( ! $campaign_id || ! $product_row_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid campaign or product ID.', 'brevo-campaign-generator' ) ) );
		}

		// Load the campaign to find the product row.
		$campaign_handler = new BCG_Campaign();
		$campaign         = $campaign_handler->get( $campaign_id );

		if ( is_wp_error( $campaign ) ) {
			wp_send_json_error( array( 'message' => $campaign->get_error_message() ) );
		}

		// Find the specific product row.
		$target_row = null;
		foreach ( $campaign->products as $product_row ) {
			if ( (int) $product_row->id === $product_row_id ) {
				$target_row = $product_row;
				break;
			}
		}

		if ( null === $target_row ) {
			wp_send_json_error( array( 'message' => __( 'Product not found in this campaign.', 'brevo-campaign-generator' ) ) );
		}

		// Build product data for AI context.
		$product_selector = new BCG_Product_Selector();
		$wc_product       = wc_get_product( (int) $target_row->product_id );

		if ( ! $wc_product instanceof \WC_Product ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce product not found.', 'brevo-campaign-generator' ) ) );
		}

		$product_data = $product_selector->format_product_preview( $wc_product );

		$tone     = isset( $_POST['tone'] ) ? sanitize_text_field( wp_unslash( $_POST['tone'] ) ) : 'Professional';
		$theme    = isset( $_POST['theme'] ) ? sanitize_text_field( wp_unslash( $_POST['theme'] ) ) : '';
		$language = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : 'English';
		$style    = isset( $_POST['image_style'] ) ? sanitize_text_field( wp_unslash( $_POST['image_style'] ) ) : 'Photorealistic';

		$ai_manager     = new BCG_AI_Manager();
		$total_credits  = 0;
		$update_data    = array();
		$errors         = array();

		// Regenerate headline.
		$context = array(
			'product'  => $product_data,
			'tone'     => $tone,
			'language' => $language,
		);

		$headline_result = $ai_manager->regenerate_field( $campaign_id, 'product_headline', $context );

		if ( ! is_wp_error( $headline_result ) ) {
			$update_data['ai_headline'] = $headline_result['content'];
			$total_credits             += $headline_result['credits_used'] ?? 0;
		} else {
			$errors[] = $headline_result->get_error_message();
		}

		// Regenerate short description.
		$desc_result = $ai_manager->regenerate_field( $campaign_id, 'product_short_desc', $context );

		if ( ! is_wp_error( $desc_result ) ) {
			$update_data['ai_short_desc'] = $desc_result['content'];
			$total_credits               += $desc_result['credits_used'] ?? 0;
		} else {
			$errors[] = $desc_result->get_error_message();
		}

		// Regenerate image if requested.
		$generated_image_url = '';
		if ( $regen_image ) {
			$image_context = array(
				'product' => $product_data,
				'theme'   => $theme,
				'style'   => $style,
			);

			$image_result = $ai_manager->regenerate_field( $campaign_id, 'product_image', $image_context );

			if ( ! is_wp_error( $image_result ) ) {
				$generated_image_url                = $image_result['content'];
				$update_data['generated_image_url'] = $generated_image_url;
				$total_credits                     += $image_result['credits_used'] ?? 0;
			} else {
				$errors[] = $image_result->get_error_message();
			}
		}

		// Save updates to the product row.
		if ( ! empty( $update_data ) ) {
			$campaign_handler->update_product( $product_row_id, $update_data );
		}

		// If all three failed, return an error.
		if ( empty( $update_data ) ) {
			wp_send_json_error( array(
				'message' => __( 'Failed to regenerate product content.', 'brevo-campaign-generator' ),
				'errors'  => $errors,
			) );
		}

		wp_send_json_success( array(
			'message'             => __( 'Product content regenerated successfully.', 'brevo-campaign-generator' ),
			'ai_headline'         => $update_data['ai_headline'] ?? '',
			'ai_short_desc'       => $update_data['ai_short_desc'] ?? '',
			'generated_image_url' => $generated_image_url,
			'credits_used'        => $total_credits,
			'new_balance'         => $ai_manager->get_credit_balance(),
			'partial_errors'      => $errors,
		) );
	}

	/**
	 * Handle adding a product to a campaign via AJAX.
	 *
	 * Adds a WooCommerce product to an existing campaign and immediately
	 * generates AI content (headline and short description) for it.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_add_product(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) ) );
		}

		$campaign_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;
		$product_id  = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

		if ( ! $campaign_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid campaign ID.', 'brevo-campaign-generator' ) ) );
		}

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product ID.', 'brevo-campaign-generator' ) ) );
		}

		// Verify the WC product exists.
		$wc_product = wc_get_product( $product_id );
		if ( ! $wc_product instanceof \WC_Product ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce product not found.', 'brevo-campaign-generator' ) ) );
		}

		$product_selector = new BCG_Product_Selector();
		$product_data     = $product_selector->format_product_preview( $wc_product );

		// AI generation context.
		$tone     = isset( $_POST['tone'] ) ? sanitize_text_field( wp_unslash( $_POST['tone'] ) ) : 'Professional';
		$language = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : 'English';

		$ai_manager    = new BCG_AI_Manager();
		$total_credits = 0;
		$ai_data       = array(
			'use_product_image' => 1,
			'show_buy_button'   => 1,
		);

		// Generate headline.
		$context = array(
			'product'  => $product_data,
			'tone'     => $tone,
			'language' => $language,
		);

		$headline_result = $ai_manager->regenerate_field( $campaign_id, 'product_headline', $context );
		if ( ! is_wp_error( $headline_result ) ) {
			$ai_data['ai_headline'] = $headline_result['content'];
			$total_credits         += $headline_result['credits_used'] ?? 0;
		}

		// Generate short description.
		$desc_result = $ai_manager->regenerate_field( $campaign_id, 'product_short_desc', $context );
		if ( ! is_wp_error( $desc_result ) ) {
			$ai_data['ai_short_desc'] = $desc_result['content'];
			$total_credits           += $desc_result['credits_used'] ?? 0;
		}

		// Add the product to the campaign.
		$campaign_handler = new BCG_Campaign();
		$row_id           = $campaign_handler->add_product( $campaign_id, $product_id, $ai_data );

		if ( is_wp_error( $row_id ) ) {
			wp_send_json_error( array( 'message' => $row_id->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message'        => __( 'Product added to campaign.', 'brevo-campaign-generator' ),
			'product_row_id' => $row_id,
			'product_id'     => $product_id,
			'product_name'   => $wc_product->get_name(),
			'product_image'  => wp_get_attachment_image_url( $wc_product->get_image_id(), 'medium' ),
			'product_price'  => $wc_product->get_price_html(),
			'product_url'    => $wc_product->get_permalink(),
			'ai_headline'    => $ai_data['ai_headline'] ?? '',
			'ai_short_desc'  => $ai_data['ai_short_desc'] ?? '',
			'credits_used'   => $total_credits,
			'new_balance'    => $ai_manager->get_credit_balance(),
		) );
	}

	/**
	 * Handle product selection preview via AJAX.
	 *
	 * Returns a lightweight preview of products matching the given selection
	 * criteria (source, count, categories) or a search query. Used by the
	 * campaign wizard to show which products will be included.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_preview_products(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) ) );
		}

		$product_selector = new BCG_Product_Selector();

		// Check if this is a search request (for the product picker).
		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

		if ( ! empty( $search ) ) {
			$results = $product_selector->search_products( $search, 20 );

			wp_send_json_success( array(
				'products' => $results,
				'count'    => count( $results ),
			) );
		}

		// Otherwise this is a selection preview.
		$source       = isset( $_POST['product_source'] ) ? sanitize_text_field( wp_unslash( $_POST['product_source'] ) ) : 'bestsellers';
		$count        = isset( $_POST['product_count'] ) ? absint( $_POST['product_count'] ) : (int) get_option( 'bcg_default_products_per_campaign', 3 );
		$category_ids = isset( $_POST['category_ids'] ) && is_array( $_POST['category_ids'] ) ? array_map( 'absint', $_POST['category_ids'] ) : array();
		$manual_ids   = isset( $_POST['manual_ids'] ) && is_array( $_POST['manual_ids'] ) ? array_map( 'absint', $_POST['manual_ids'] ) : array();
		$exclude_ids  = isset( $_POST['exclude_ids'] ) && is_array( $_POST['exclude_ids'] ) ? array_map( 'absint', $_POST['exclude_ids'] ) : array();

		$config = array(
			'count'        => $count,
			'source'       => $source,
			'category_ids' => $category_ids,
			'manual_ids'   => $manual_ids,
			'exclude_ids'  => $exclude_ids,
		);

		$products = $product_selector->preview_products( $config );

		wp_send_json_success( array(
			'products' => $products,
			'count'    => count( $products ),
		) );
	}

	/**
	 * Handle product search via AJAX.
	 *
	 * Used by the manual product picker on the new campaign page (Step 1).
	 * Searches WooCommerce products by keyword and returns matching results.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_search_products(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) ) );
		}

		$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';

		if ( empty( $keyword ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a search term.', 'brevo-campaign-generator' ) ) );
		}

		$product_selector = new BCG_Product_Selector();
		$products         = $product_selector->search_products( $keyword, 20 );

		wp_send_json_success( array( 'products' => $products ) );
	}

	/**
	 * Handle campaign save (draft) via AJAX.
	 *
	 * Saves all editable campaign fields, product-level edits (custom
	 * headlines, descriptions, image choices, buy button toggles), and
	 * product sort order from the Step 2 editor.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_save_campaign(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) ) );
		}

		$campaign_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;

		if ( ! $campaign_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid campaign ID.', 'brevo-campaign-generator' ) ) );
		}

		$campaign_handler = new BCG_Campaign();

		// Verify the campaign exists.
		$campaign = $campaign_handler->get( $campaign_id );
		if ( is_wp_error( $campaign ) ) {
			wp_send_json_error( array( 'message' => $campaign->get_error_message() ) );
		}

		// ── Build the campaign update data from submitted fields ──────

		$update_data = array();

		// Text fields.
		$text_fields = array( 'title', 'subject', 'preview_text', 'main_headline', 'coupon_code', 'mailing_list_id' );
		foreach ( $text_fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$update_data[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
			}
		}

		// Main description allows HTML.
		if ( isset( $_POST['main_description'] ) ) {
			$update_data['main_description'] = wp_kses_post( wp_unslash( $_POST['main_description'] ) );
		}

		// Main image URL.
		if ( isset( $_POST['main_image_url'] ) ) {
			$update_data['main_image_url'] = esc_url_raw( wp_unslash( $_POST['main_image_url'] ) );
		}

		// Coupon discount (decimal).
		if ( isset( $_POST['coupon_discount'] ) ) {
			$update_data['coupon_discount'] = (float) $_POST['coupon_discount'];
		}

		// Coupon type (enum).
		if ( isset( $_POST['coupon_type'] ) ) {
			$coupon_type = sanitize_text_field( wp_unslash( $_POST['coupon_type'] ) );
			if ( in_array( $coupon_type, array( 'percent', 'fixed_cart' ), true ) ) {
				$update_data['coupon_type'] = $coupon_type;
			}
		}

		// Template slug — when changed, load the new template's HTML and settings.
		if ( isset( $_POST['template_slug'] ) ) {
			$new_slug = sanitize_text_field( wp_unslash( $_POST['template_slug'] ) );
			$registry = BCG_Template_Registry::get_instance();

			if ( $registry->get_template( $new_slug ) ) {
				$update_data['template_slug'] = $new_slug;

				// Only apply new template HTML/settings if the slug actually changed.
				$current_slug = $campaign->template_slug ?? 'classic';
				if ( $new_slug !== $current_slug ) {
					$new_html     = $registry->get_template_html( $new_slug );
					$new_settings = $registry->get_template_settings( $new_slug );

					if ( ! empty( $new_html ) ) {
						$update_data['template_html'] = $new_html;
					}
					if ( ! empty( $new_settings ) ) {
						$update_data['template_settings'] = wp_json_encode( $new_settings );
					}
				}
			}
		}

		// Sections JSON — for section-builder campaigns.
		if ( isset( $_POST['sections_json'] ) ) {
			$raw_sections = wp_unslash( $_POST['sections_json'] );
			$decoded      = json_decode( $raw_sections, true );

			if ( is_array( $decoded ) ) {
				$update_data['sections_json'] = wp_json_encode( $decoded );

				// Re-render sections to email HTML.
				$global_settings = json_decode( get_option( 'bcg_default_template_settings', '{}' ), true );
				if ( ! is_array( $global_settings ) ) {
					$global_settings = array();
				}

				$rendered_html                = BCG_Section_Renderer::render_sections( $decoded, $global_settings );
				$update_data['template_html'] = $rendered_html;
			}
		}

		// Template HTML — allow full HTML including <style> tags for email templates.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Full email HTML stored for campaign. Capability-gated to manage_woocommerce admins only.
		if ( isset( $_POST['template_html'] ) && ! isset( $update_data['template_html'] ) ) {
			$update_data['template_html'] = wp_unslash( $_POST['template_html'] );
		}

		// Template settings (JSON string).
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON decoded then re-encoded; individual values are escaped in apply_settings().
		if ( isset( $_POST['template_settings'] ) && ! isset( $update_data['template_settings'] ) ) {
			$settings_raw = wp_unslash( $_POST['template_settings'] );
			$decoded      = json_decode( $settings_raw, true );
			if ( is_array( $decoded ) ) {
				$update_data['template_settings'] = wp_json_encode( $decoded );
			}
		}

		// Update the campaign if we have data.
		if ( ! empty( $update_data ) ) {
			$result = $campaign_handler->update( $campaign_id, $update_data );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}
		}

		// ── Update product-level data ────────────────────────────────

		if ( isset( $_POST['products'] ) && is_array( $_POST['products'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised per-field below.
			$products_data = wp_unslash( $_POST['products'] );

			foreach ( $products_data as $product_entry ) {
				$product_row_id = isset( $product_entry['product_row_id'] ) ? absint( $product_entry['product_row_id'] ) : 0;

				if ( $product_row_id <= 0 ) {
					continue;
				}

				$product_update = array();

				if ( isset( $product_entry['custom_headline'] ) ) {
					$product_update['custom_headline'] = sanitize_text_field( $product_entry['custom_headline'] );
				}

				if ( isset( $product_entry['custom_short_desc'] ) ) {
					$product_update['custom_short_desc'] = wp_kses_post( $product_entry['custom_short_desc'] );
				}

				if ( isset( $product_entry['use_product_image'] ) ) {
					$product_update['use_product_image'] = absint( $product_entry['use_product_image'] ) ? 1 : 0;
				}

				if ( isset( $product_entry['show_buy_button'] ) ) {
					$product_update['show_buy_button'] = absint( $product_entry['show_buy_button'] ) ? 1 : 0;
				}

				if ( ! empty( $product_update ) ) {
					$campaign_handler->update_product( $product_row_id, $product_update );
				}
			}
		}

		// ── Update product sort order ────────────────────────────────

		if ( isset( $_POST['product_order'] ) && is_array( $_POST['product_order'] ) ) {
			$ordered_ids = array_map( 'absint', $_POST['product_order'] );
			$ordered_ids = array_filter( $ordered_ids );

			if ( ! empty( $ordered_ids ) ) {
				$campaign_handler->reorder_products( $campaign_id, $ordered_ids );
			}
		}

		wp_send_json_success( array(
			'message' => __( 'Campaign saved successfully.', 'brevo-campaign-generator' ),
		) );
	}

	/**
	 * Handle campaign deletion via AJAX.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_delete_campaign(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) ) );
		}

		$campaign_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;

		if ( ! $campaign_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid campaign ID.', 'brevo-campaign-generator' ) ) );
		}

		$campaign = new BCG_Campaign();
		$result   = $campaign->delete( $campaign_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Campaign deleted successfully.', 'brevo-campaign-generator' ) ) );
	}

	/**
	 * Handle campaign duplication via AJAX.
	 *
	 * Creates a copy of an existing campaign including all associated
	 * products and AI content. The coupon code is cleared to avoid
	 * conflicts.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_duplicate_campaign(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) ) );
		}

		$campaign_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;

		if ( ! $campaign_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid campaign ID.', 'brevo-campaign-generator' ) ) );
		}

		$campaign_handler = new BCG_Campaign();
		$source           = $campaign_handler->get( $campaign_id );

		if ( is_wp_error( $source ) ) {
			wp_send_json_error( array( 'message' => $source->get_error_message() ) );
		}

		// Create a duplicate draft.
		$new_id = $campaign_handler->create_draft( array(
			'title'             => sprintf(
				/* translators: %s: original campaign title */
				__( '%s (Copy)', 'brevo-campaign-generator' ),
				$source->title
			),
			'subject'           => $source->subject,
			'preview_text'      => $source->preview_text,
			'main_image_url'    => $source->main_image_url,
			'main_headline'     => $source->main_headline,
			'main_description'  => $source->main_description,
			'coupon_code'       => '',
			'coupon_discount'   => $source->coupon_discount,
			'coupon_type'       => $source->coupon_type,
			'template_html'     => $source->template_html,
			'template_settings' => $source->template_settings,
			'mailing_list_id'   => $source->mailing_list_id,
		) );

		if ( is_wp_error( $new_id ) ) {
			wp_send_json_error( array( 'message' => $new_id->get_error_message() ) );
		}

		// Duplicate associated products.
		if ( ! empty( $source->products ) ) {
			foreach ( $source->products as $product ) {
				$campaign_handler->add_product( $new_id, (int) $product->product_id, array(
					'sort_order'          => (int) $product->sort_order,
					'ai_headline'         => $product->ai_headline,
					'ai_short_desc'       => $product->ai_short_desc,
					'custom_headline'     => $product->custom_headline,
					'custom_short_desc'   => $product->custom_short_desc,
					'generated_image_url' => $product->generated_image_url,
					'use_product_image'   => (int) $product->use_product_image,
					'show_buy_button'     => (int) $product->show_buy_button,
				) );
			}
		}

		wp_send_json_success( array(
			'message'     => __( 'Campaign duplicated successfully.', 'brevo-campaign-generator' ),
			'campaign_id' => $new_id,
			'edit_url'    => admin_url( 'admin.php?page=bcg-edit-campaign&campaign_id=' . $new_id ),
		) );
	}

	/**
	 * Handle sending a test email via AJAX.
	 *
	 * Renders the campaign template, ensures a corresponding Brevo campaign
	 * exists (creates or updates one), then sends a test email to the
	 * specified address through the Brevo API.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_send_test(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) ) );
		}

		$campaign_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;
		$test_email  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( ! $campaign_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid campaign ID.', 'brevo-campaign-generator' ) ) );
		}

		if ( empty( $test_email ) || ! is_email( $test_email ) ) {
			// Default to current user's email.
			$current_user = wp_get_current_user();
			$test_email   = $current_user->user_email;

			if ( empty( $test_email ) || ! is_email( $test_email ) ) {
				wp_send_json_error( array( 'message' => __( 'A valid email address is required.', 'brevo-campaign-generator' ) ) );
			}
		}

		// Load the campaign.
		$campaign_handler = new BCG_Campaign();
		$campaign         = $campaign_handler->get( $campaign_id );

		if ( is_wp_error( $campaign ) ) {
			wp_send_json_error( array( 'message' => $campaign->get_error_message() ) );
		}

		// Ensure a Brevo campaign exists so we can send a test.
		$brevo          = new BCG_Brevo();
		$brevo_campaign = $this->ensure_brevo_campaign( $campaign, $brevo );

		if ( is_wp_error( $brevo_campaign ) ) {
			wp_send_json_error( array( 'message' => $brevo_campaign->get_error_message() ) );
		}

		$brevo_campaign_id = (int) $brevo_campaign['id'];

		// Send the test email.
		$result = $brevo->send_test_email( $brevo_campaign_id, $test_email );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: %s: the test email address */
				__( 'Test email sent to %s.', 'brevo-campaign-generator' ),
				esc_html( $test_email )
			),
		) );
	}

	/**
	 * Handle creating a campaign in Brevo via AJAX.
	 *
	 * Renders the campaign template into final HTML, builds a Brevo API
	 * payload with the campaign name, subject, sender details, and mailing
	 * list, creates the campaign in Brevo, and stores the Brevo campaign
	 * ID back in the local campaign record. Sets the local status to 'ready'.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_create_brevo_campaign(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) ) );
		}

		$campaign_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;

		if ( ! $campaign_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid campaign ID.', 'brevo-campaign-generator' ) ) );
		}

		// Load the campaign.
		$campaign_handler = new BCG_Campaign();
		$campaign         = $campaign_handler->get( $campaign_id );

		if ( is_wp_error( $campaign ) ) {
			wp_send_json_error( array( 'message' => $campaign->get_error_message() ) );
		}

		// Create or update the Brevo campaign.
		$brevo          = new BCG_Brevo();
		$brevo_campaign = $this->ensure_brevo_campaign( $campaign, $brevo );

		if ( is_wp_error( $brevo_campaign ) ) {
			wp_send_json_error( array( 'message' => $brevo_campaign->get_error_message() ) );
		}

		$brevo_campaign_id = (int) $brevo_campaign['id'];

		// Update local campaign with Brevo campaign ID and set status to ready.
		$campaign_handler->update( $campaign_id, array(
			'brevo_campaign_id' => $brevo_campaign_id,
			'status'            => 'ready',
		) );

		wp_send_json_success( array(
			'message'           => __( 'Campaign created in Brevo successfully.', 'brevo-campaign-generator' ),
			'brevo_campaign_id' => $brevo_campaign_id,
			'brevo_url'         => 'https://app.brevo.com/campaign/classic/' . $brevo_campaign_id,
		) );
	}

	/**
	 * Handle sending a campaign immediately via AJAX.
	 *
	 * Ensures the campaign exists in Brevo (creates or updates it), then
	 * triggers an immediate send through the Brevo API. Updates the local
	 * campaign status to 'sent' on success.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_send_campaign(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) ) );
		}

		$campaign_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;

		if ( ! $campaign_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid campaign ID.', 'brevo-campaign-generator' ) ) );
		}

		// Load the campaign.
		$campaign_handler = new BCG_Campaign();
		$campaign         = $campaign_handler->get( $campaign_id );

		if ( is_wp_error( $campaign ) ) {
			wp_send_json_error( array( 'message' => $campaign->get_error_message() ) );
		}

		// Ensure a Brevo campaign exists (create or update).
		$brevo          = new BCG_Brevo();
		$brevo_campaign = $this->ensure_brevo_campaign( $campaign, $brevo );

		if ( is_wp_error( $brevo_campaign ) ) {
			wp_send_json_error( array( 'message' => $brevo_campaign->get_error_message() ) );
		}

		$brevo_campaign_id = (int) $brevo_campaign['id'];

		// Send the campaign immediately.
		$send_result = $brevo->send_campaign_now( $brevo_campaign_id );

		if ( is_wp_error( $send_result ) ) {
			wp_send_json_error( array( 'message' => $send_result->get_error_message() ) );
		}

		// Update local campaign status to sent.
		$campaign_handler->update( $campaign_id, array(
			'brevo_campaign_id' => $brevo_campaign_id,
			'status'            => 'sent',
			'sent_at'           => current_time( 'mysql' ),
		) );

		wp_send_json_success( array(
			'message' => __( 'Campaign sent successfully!', 'brevo-campaign-generator' ),
		) );
	}

	/**
	 * Handle scheduling a campaign via AJAX.
	 *
	 * Ensures the campaign exists in Brevo, then schedules it for the
	 * specified date/time through the Brevo API. Updates the local
	 * campaign status to 'scheduled' and stores the scheduled datetime.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_schedule_campaign(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) ) );
		}

		$campaign_id    = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;
		$scheduled_at   = isset( $_POST['scheduled_at'] ) ? sanitize_text_field( wp_unslash( $_POST['scheduled_at'] ) ) : '';

		if ( ! $campaign_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid campaign ID.', 'brevo-campaign-generator' ) ) );
		}

		if ( empty( $scheduled_at ) ) {
			wp_send_json_error( array( 'message' => __( 'A scheduled date and time is required.', 'brevo-campaign-generator' ) ) );
		}

		// Validate the datetime is in the future.
		$timestamp = strtotime( $scheduled_at );
		if ( false === $timestamp || $timestamp <= time() ) {
			wp_send_json_error( array( 'message' => __( 'The scheduled date must be in the future.', 'brevo-campaign-generator' ) ) );
		}

		// Load the campaign.
		$campaign_handler = new BCG_Campaign();
		$campaign         = $campaign_handler->get( $campaign_id );

		if ( is_wp_error( $campaign ) ) {
			wp_send_json_error( array( 'message' => $campaign->get_error_message() ) );
		}

		// Ensure a Brevo campaign exists (create or update).
		$brevo          = new BCG_Brevo();
		$brevo_campaign = $this->ensure_brevo_campaign( $campaign, $brevo );

		if ( is_wp_error( $brevo_campaign ) ) {
			wp_send_json_error( array( 'message' => $brevo_campaign->get_error_message() ) );
		}

		$brevo_campaign_id = (int) $brevo_campaign['id'];

		// Format as ISO 8601 for Brevo.
		$iso_datetime = gmdate( 'Y-m-d\TH:i:s\Z', $timestamp );

		// Schedule the campaign in Brevo.
		$schedule_result = $brevo->schedule_campaign( $brevo_campaign_id, $iso_datetime );

		if ( is_wp_error( $schedule_result ) ) {
			wp_send_json_error( array( 'message' => $schedule_result->get_error_message() ) );
		}

		// Update local campaign status.
		$campaign_handler->update( $campaign_id, array(
			'brevo_campaign_id' => $brevo_campaign_id,
			'status'            => 'scheduled',
			'scheduled_at'      => gmdate( 'Y-m-d H:i:s', $timestamp ),
		) );

		wp_send_json_success( array(
			'message'      => sprintf(
				/* translators: %s: the formatted scheduled date/time */
				__( 'Campaign scheduled for %s.', 'brevo-campaign-generator' ),
				wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp )
			),
			'scheduled_at' => $iso_datetime,
		) );
	}

	/**
	 * Handle template settings update via AJAX.
	 *
	 * Saves the template HTML and settings either as the default template
	 * or to a specific campaign, depending on the 'target' parameter.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_update_template(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Full email HTML with <style> tags. Capability-gated to manage_woocommerce admins only.
		$template_html = isset( $_POST['template_html'] ) ? wp_unslash( $_POST['template_html'] ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON settings; sanitize_text_field() corrupts JSON. Values are escaped in apply_settings().
		$settings_raw  = isset( $_POST['template_settings'] ) ? wp_unslash( $_POST['template_settings'] ) : '{}';
		$target        = isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : 'default';
		$campaign_id   = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;

		// Validate settings JSON.
		$settings = json_decode( $settings_raw, true );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$template_engine = new BCG_Template();

		if ( 'campaign' === $target && $campaign_id > 0 ) {
			// Save to specific campaign.
			global $wpdb;
			$table = $wpdb->prefix . 'bcg_campaigns';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$updated = $wpdb->update(
				$table,
				array(
					'template_html'     => $template_html,
					'template_settings' => wp_json_encode( $settings ),
				),
				array( 'id' => $campaign_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);

			if ( false === $updated ) {
				wp_send_json_error( array( 'message' => __( 'Failed to save template to campaign.', 'brevo-campaign-generator' ) ) );
			}

			wp_send_json_success( array(
				'message' => sprintf(
					/* translators: %d: campaign ID */
					__( 'Template saved to campaign #%d.', 'brevo-campaign-generator' ),
					$campaign_id
				),
			) );
		} else {
			// Save as default template.
			$template_engine->save_default_template( $template_html, $settings );

			wp_send_json_success( array(
				'message' => __( 'Default template saved successfully.', 'brevo-campaign-generator' ),
			) );
		}
	}

	/**
	 * Handle template preview rendering via AJAX.
	 *
	 * Accepts template HTML and settings JSON, renders a preview using the
	 * BCG_Template engine with sample data, and returns the rendered HTML.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_preview_template(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Full email HTML with <style> tags needed for preview. Capability-gated to manage_woocommerce admins only.
		$template_html = isset( $_POST['template_html'] ) ? wp_unslash( $_POST['template_html'] ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON settings string; sanitize_text_field() must NOT be used here as it strips tags/collapses whitespace and corrupts JSON. Individual values are escaped downstream (esc_attr, esc_url, etc.) in apply_settings().
		$settings_raw  = isset( $_POST['template_settings'] ) ? wp_unslash( $_POST['template_settings'] ) : '{}';
		$campaign_id   = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;

		// If campaign_id is provided and no raw template_html, render the saved campaign.
		if ( empty( $template_html ) && $campaign_id > 0 ) {
			$template_engine = new BCG_Template();
			$rendered_html   = $template_engine->render( $campaign_id );

			if ( is_wp_error( $rendered_html ) ) {
				wp_send_json_error( array( 'message' => $rendered_html->get_error_message() ) );
			}

			wp_send_json_success( array( 'html' => $rendered_html ) );
		}

		if ( empty( $template_html ) ) {
			wp_send_json_error( array( 'message' => __( 'No template HTML provided.', 'brevo-campaign-generator' ) ) );
		}

		$template_engine = new BCG_Template();
		$rendered_html   = $template_engine->render_preview( $template_html, $settings_raw );

		wp_send_json_success( array( 'html' => $rendered_html ) );
	}

	/**
	 * Handle template reset to bundled default via AJAX.
	 *
	 * Deletes the user-customised default template and returns the bundled
	 * template HTML and default settings for the editor to reload.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_reset_template(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) ) );
		}

		$template_engine = new BCG_Template();
		$template_engine->reset_default_template();

		// Return the bundled defaults for the editor to reload.
		$default_html     = $template_engine->get_default_template();
		$default_settings = $template_engine->get_default_settings();

		wp_send_json_success( array(
			'message'  => __( 'Template reset to default successfully.', 'brevo-campaign-generator' ),
			'html'     => $default_html,
			'settings' => $default_settings,
		) );
	}

	/**
	 * Handle loading a template by slug via AJAX.
	 *
	 * Returns the template HTML and default settings for the requested
	 * template slug from the template registry.
	 *
	 * @since  1.1.0
	 * @return void
	 */
	public function handle_load_template(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) ) );
		}

		$slug = isset( $_POST['template_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['template_slug'] ) ) : '';

		if ( empty( $slug ) ) {
			wp_send_json_error( array( 'message' => __( 'Template slug is required.', 'brevo-campaign-generator' ) ) );
		}

		$registry = BCG_Template_Registry::get_instance();
		$template = $registry->get_template( $slug );

		if ( ! $template ) {
			wp_send_json_error( array( 'message' => __( 'Template not found.', 'brevo-campaign-generator' ) ) );
		}

		$html     = $registry->get_template_html( $slug );
		$settings = $registry->get_template_settings( $slug );

		if ( empty( $html ) ) {
			wp_send_json_error( array( 'message' => __( 'Template HTML file not found.', 'brevo-campaign-generator' ) ) );
		}

		wp_send_json_success( array(
			'slug'     => $slug,
			'name'     => $template['name'],
			'html'     => $html,
			'settings' => $settings,
		) );
	}

	/**
	 * Handle WooCommerce coupon generation via AJAX.
	 *
	 * Creates a new WooCommerce coupon using BCG_Coupon, associating it
	 * with the specified campaign. Returns the generated coupon code and
	 * details on success.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_generate_coupon(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) ) );
		}

		$campaign_id    = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;
		$discount_value = isset( $_POST['discount_value'] ) ? (float) $_POST['discount_value'] : (float) get_option( 'bcg_default_coupon_discount', 10 );
		$discount_type  = isset( $_POST['discount_type'] ) ? sanitize_text_field( wp_unslash( $_POST['discount_type'] ) ) : 'percent';
		$expiry_days    = isset( $_POST['expiry_days'] ) ? absint( $_POST['expiry_days'] ) : (int) get_option( 'bcg_default_coupon_expiry_days', 7 );
		$prefix         = isset( $_POST['coupon_prefix'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_prefix'] ) ) : '';

		if ( ! $campaign_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid campaign ID.', 'brevo-campaign-generator' ) ) );
		}

		if ( $discount_value <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Discount value must be greater than zero.', 'brevo-campaign-generator' ) ) );
		}

		// Delete any existing coupon for this campaign before creating a new one.
		$coupon_handler = new BCG_Coupon();
		$coupon_handler->delete_campaign_coupon( $campaign_id );

		// Create the new coupon.
		$coupon_code = $coupon_handler->create_coupon(
			$campaign_id,
			$discount_value,
			$discount_type,
			$expiry_days,
			$prefix
		);

		if ( is_wp_error( $coupon_code ) ) {
			wp_send_json_error( array( 'message' => $coupon_code->get_error_message() ) );
		}

		// Build the coupon display text.
		$normalised_type = strtolower( trim( $discount_type ) );
		if ( in_array( $normalised_type, array( 'percent', 'percentage' ), true ) ) {
			$coupon_text = sprintf(
				/* translators: 1: coupon code, 2: discount percentage */
				__( 'Use code %1$s for %2$s%% off!', 'brevo-campaign-generator' ),
				$coupon_code,
				number_format( $discount_value, 0 )
			);
		} else {
			$coupon_text = sprintf(
				/* translators: 1: coupon code, 2: formatted discount amount */
				__( 'Use code %1$s for %2$s off!', 'brevo-campaign-generator' ),
				$coupon_code,
				function_exists( 'wc_price' ) ? wc_price( $discount_value ) : number_format( $discount_value, 2 )
			);
		}

		wp_send_json_success( array(
			'message'        => __( 'Coupon generated successfully.', 'brevo-campaign-generator' ),
			'coupon_code'    => $coupon_code,
			'discount_value' => $discount_value,
			'discount_type'  => $discount_type,
			'expiry_days'    => $expiry_days,
			'coupon_text'    => $coupon_text,
		) );
	}

	/**
	 * Handle fetching Brevo verified senders via AJAX.
	 *
	 * Returns a list of verified senders from the Brevo account for use
	 * in the sender dropdown on the Settings page.
	 *
	 * @since  1.1.0
	 * @return void
	 */
	public function handle_get_brevo_senders(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) ) );
		}

		$brevo   = new BCG_Brevo();
		$senders = $brevo->get_senders();

		if ( is_wp_error( $senders ) ) {
			wp_send_json_error( array( 'message' => $senders->get_error_message() ) );
		}

		// Simplify the response to just id, name, email.
		$formatted = array();
		foreach ( $senders as $sender ) {
			$formatted[] = array(
				'id'    => isset( $sender['id'] ) ? (int) $sender['id'] : 0,
				'name'  => isset( $sender['name'] ) ? $sender['name'] : '',
				'email' => isset( $sender['email'] ) ? $sender['email'] : '',
			);
		}

		wp_send_json_success( array( 'senders' => $formatted ) );
	}

	// ─── Private Brevo Helpers ─────────────────────────────────────────

	/**
	 * Ensure a Brevo campaign exists for the given local campaign.
	 *
	 * If the campaign already has a brevo_campaign_id, updates the existing
	 * Brevo campaign with the latest rendered HTML, subject, and settings.
	 * Otherwise, creates a new Brevo campaign and returns its data.
	 *
	 * @since  1.0.0
	 *
	 * @param  \stdClass $campaign The local campaign object (from BCG_Campaign::get()).
	 * @param  BCG_Brevo $brevo    The Brevo API client instance.
	 * @return array|\WP_Error The Brevo campaign data array (with 'id' key) on success,
	 *                         WP_Error on failure.
	 */
	private function ensure_brevo_campaign( \stdClass $campaign, BCG_Brevo $brevo ): array|\WP_Error {
		$campaign_id = (int) $campaign->id;

		// Render the template to full HTML.
		$template_engine = new BCG_Template();
		$rendered_html   = $template_engine->render( $campaign_id );

		if ( is_wp_error( $rendered_html ) ) {
			return $rendered_html;
		}

		// Validate required fields.
		if ( empty( $campaign->subject ) ) {
			return new \WP_Error(
				'bcg_missing_subject',
				__( 'Campaign subject line is required before sending to Brevo.', 'brevo-campaign-generator' )
			);
		}

		if ( empty( $campaign->mailing_list_id ) ) {
			return new \WP_Error(
				'bcg_missing_list',
				__( 'A mailing list must be selected before sending to Brevo.', 'brevo-campaign-generator' )
			);
		}

		// Build the list IDs array (supports comma-separated values).
		$list_ids = array_map( 'absint', explode( ',', $campaign->mailing_list_id ) );
		$list_ids = array_filter( $list_ids );

		if ( empty( $list_ids ) ) {
			return new \WP_Error(
				'bcg_invalid_list',
				__( 'Invalid mailing list ID.', 'brevo-campaign-generator' )
			);
		}

		// Resolve sender against Brevo's verified senders list.
		// Always fetch to ensure we have the correct sender ID; this guarantees
		// Brevo accepts the sender even if local config is stale or missing.
		$sender_json  = get_option( 'bcg_brevo_sender', '' );
		$sender_data  = is_string( $sender_json ) ? json_decode( $sender_json, true ) : null;
		$sender_email = ( is_array( $sender_data ) && ! empty( $sender_data['email'] ) )
			? (string) $sender_data['email']
			: (string) get_option( 'bcg_brevo_sender_email', '' );
		$sender_name  = ( is_array( $sender_data ) && ! empty( $sender_data['name'] ) )
			? (string) $sender_data['name']
			: (string) get_option( 'bcg_brevo_sender_name', '' );
		$sender_id    = ( is_array( $sender_data ) && ! empty( $sender_data['id'] ) )
			? (int) $sender_data['id']
			: 0;

		// Fetch verified senders from Brevo and resolve the best match.
		$verified_senders = $brevo->get_senders();
		if ( ! is_wp_error( $verified_senders ) && ! empty( $verified_senders ) ) {
			$matched = null;

			// 1. Match by email (case-insensitive).
			if ( ! empty( $sender_email ) ) {
				foreach ( $verified_senders as $vs ) {
					if ( isset( $vs['email'] ) && strtolower( $vs['email'] ) === strtolower( $sender_email ) ) {
						$matched = $vs;
						break;
					}
				}
			}

			// 2. Match by stored ID if email lookup failed.
			if ( null === $matched && $sender_id > 0 ) {
				foreach ( $verified_senders as $vs ) {
					if ( isset( $vs['id'] ) && (int) $vs['id'] === $sender_id ) {
						$matched = $vs;
						break;
					}
				}
			}

			// 3. Fall back to first verified sender.
			if ( null === $matched ) {
				$matched = $verified_senders[0];
			}

			// Use the resolved sender and persist with correct ID.
			$sender_email = (string) ( $matched['email'] ?? $sender_email );
			$sender_name  = (string) ( $matched['name'] ?? $sender_name );
			$sender_id    = (int) ( $matched['id'] ?? 0 );

			if ( $sender_id > 0 ) {
				update_option( 'bcg_brevo_sender', wp_json_encode( array(
					'id'    => $sender_id,
					'name'  => $sender_name,
					'email' => $sender_email,
				) ) );
			}
		}

		if ( empty( $sender_email ) || ! is_email( $sender_email ) ) {
			return new \WP_Error(
				'bcg_missing_sender',
				__( 'No verified sender found in your Brevo account. Please check your Brevo API key in Settings > API Keys.', 'brevo-campaign-generator' )
			);
		}

		if ( empty( $sender_name ) ) {
			return new \WP_Error(
				'bcg_missing_sender_name',
				__( 'Sender name is not configured. Go to Brevo Campaigns > Settings > Brevo tab and select a verified sender.', 'brevo-campaign-generator' )
			);
		}

		// Build extra payload fields.
		$extra = array();
		if ( ! empty( $campaign->preview_text ) ) {
			$extra['previewText'] = sanitize_text_field( $campaign->preview_text );
		}

		// Build the full campaign payload.
		$payload = $brevo->build_campaign_payload(
			$campaign->title,
			$campaign->subject,
			$rendered_html,
			$list_ids,
			$extra
		);

		// Check if a Brevo campaign already exists.
		$brevo_campaign_id = ! empty( $campaign->brevo_campaign_id ) ? (int) $campaign->brevo_campaign_id : 0;

		if ( $brevo_campaign_id > 0 ) {
			// Update the existing Brevo campaign.
			$update_result = $brevo->update_campaign( $brevo_campaign_id, $payload );

			if ( is_wp_error( $update_result ) ) {
				// If the update fails (e.g. campaign was sent/deleted), try creating a new one.
				$create_result = $brevo->create_campaign( $payload );

				if ( is_wp_error( $create_result ) ) {
					return $create_result;
				}

				return $create_result;
			}

			// Return the existing ID in the expected format.
			return array( 'id' => $brevo_campaign_id );
		}

		// Create a new Brevo campaign.
		$create_result = $brevo->create_campaign( $payload );

		if ( is_wp_error( $create_result ) ) {
			return $create_result;
		}

		return $create_result;
	}

	// ─── Credit Widget ─────────────────────────────────────────────────

	/**
	 * Add the credit balance widget to the WordPress admin bar.
	 *
	 * @since  1.0.0
	 * @param  \WP_Admin_Bar $admin_bar The admin bar instance.
	 * @return void
	 */
	public function add_credit_widget_to_admin_bar( \WP_Admin_Bar $admin_bar ): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$balance = $this->get_current_user_credit_balance();

		$admin_bar->add_node(
			array(
				'id'    => 'bcg-credits',
				'title' => sprintf(
					'<span class="bcg-credit-widget"><span class="bcg-credit-icon">&#x1F4B3;</span> %s: <strong>%s</strong> | <span class="bcg-credit-topup">%s</span></span>',
					esc_html__( 'Credits', 'brevo-campaign-generator' ),
					esc_html( number_format( $balance, 0 ) ),
					esc_html__( 'Top Up', 'brevo-campaign-generator' )
				),
				'href'  => admin_url( 'admin.php?page=bcg-credits' ),
				'meta'  => array(
					'class' => 'bcg-admin-bar-credits',
				),
			)
		);
	}

	/**
	 * Get the credit balance for the current user.
	 *
	 * @since  1.0.0
	 * @return float
	 */
	private function get_current_user_credit_balance(): float {
		global $wpdb;

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return 0.0;
		}

		$table   = $wpdb->prefix . 'bcg_credits';
		$balance = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT balance FROM {$table} WHERE user_id = %d",
				$user_id
			)
		);

		return $balance ? (float) $balance : 0.0;
	}

	/**
	 * Render the Dashboard page.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_dashboard_page(): void {
		require_once BCG_PLUGIN_DIR . 'admin/views/page-dashboard.php';
	}

	/**
	 * Render the New Campaign page.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_new_campaign_page(): void {
		require_once BCG_PLUGIN_DIR . 'admin/views/page-new-campaign.php';
	}

	/**
	 * Render the Template Editor page.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_template_editor_page(): void {
		require_once BCG_PLUGIN_DIR . 'admin/views/page-template-editor.php';
	}

	/**
	 * Render the Brevo Stats page.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_stats_page(): void {
		require_once BCG_PLUGIN_DIR . 'admin/views/page-stats.php';
	}

	/**
	 * Render the Credits & Billing page.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_credits_page(): void {
		require_once BCG_PLUGIN_DIR . 'admin/views/page-credits.php';
	}

	/**
	 * Render the Settings page.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! $this->is_rfs_admin() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'brevo-campaign-generator' ) );
		}
		require_once BCG_PLUGIN_DIR . 'admin/views/page-settings.php';
	}

	/**
	 * Render the Edit Campaign page (Step 2 editor).
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_edit_campaign_page(): void {
		require_once BCG_PLUGIN_DIR . 'admin/views/page-edit-campaign.php';
	}

	/**
	 * Add contextual help tabs to the Template Builder admin screen.
	 *
	 * Registered via load-{hook} to attach before the page renders.
	 *
	 * @since  1.5.3
	 * @return void
	 */
	public function add_section_builder_help_tabs(): void {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$screen->add_help_tab( array(
			'id'      => 'bcg-sb-overview',
			'title'   => __( 'Overview', 'brevo-campaign-generator' ),
			'content' =>
				'<h2>' . __( 'Template Builder', 'brevo-campaign-generator' ) . '</h2>' .
				'<p>' . __( 'The Template Builder lets you compose reusable email templates by combining individual <strong>sections</strong> (blocks) into a named layout. These layouts can then be applied when creating a campaign.', 'brevo-campaign-generator' ) . '</p>' .
				'<p>' . __( 'The builder has three panels:', 'brevo-campaign-generator' ) . '</p>' .
				'<ul>' .
				'<li><strong>' . __( 'Sections (left)', 'brevo-campaign-generator' ) . '</strong> — ' . __( 'Click any section variant to add it to the canvas. Variants are grouped into categories; click a category header to expand or collapse it.', 'brevo-campaign-generator' ) . '</li>' .
				'<li><strong>' . __( 'Canvas (centre)', 'brevo-campaign-generator' ) . '</strong> — ' . __( 'Drag the &#9776; handle to reorder sections. Use the ↑ ↓ buttons for keyboard accessibility. Click ✎ to open the settings panel for a section. Click ✕ to remove it.', 'brevo-campaign-generator' ) . '</li>' .
				'<li><strong>' . __( 'Settings (right)', 'brevo-campaign-generator' ) . '</strong> — ' . __( 'Shows editable fields for the selected section. Every change triggers a live preview update within 300 ms. Colour pickers sync with their hex input field.', 'brevo-campaign-generator' ) . '</li>' .
				'</ul>',
		) );

		$screen->add_help_tab( array(
			'id'      => 'bcg-sb-sections',
			'title'   => __( 'Section Types', 'brevo-campaign-generator' ),
			'content' =>
				'<h2>' . __( 'Available Section Types', 'brevo-campaign-generator' ) . '</h2>' .
				'<table style="width:100%;border-collapse:collapse;">' .
				'<thead><tr><th style="text-align:left;padding:6px 8px;border-bottom:1px solid #ddd;">' . __( 'Type', 'brevo-campaign-generator' ) . '</th>' .
				'<th style="text-align:left;padding:6px 8px;border-bottom:1px solid #ddd;">' . __( 'AI', 'brevo-campaign-generator' ) . '</th>' .
				'<th style="text-align:left;padding:6px 8px;border-bottom:1px solid #ddd;">' . __( 'Description', 'brevo-campaign-generator' ) . '</th></tr></thead>' .
				'<tbody>' .
				'<tr><td style="padding:5px 8px;">Header</td><td style="padding:5px 8px;">—</td><td style="padding:5px 8px;">' . __( 'Logo, site name, optional navigation links.', 'brevo-campaign-generator' ) . '</td></tr>' .
				'<tr><td style="padding:5px 8px;">Hero / Banner</td><td style="padding:5px 8px;">✨</td><td style="padding:5px 8px;">' . __( 'Large banner with headline, subtext, and a CTA button. Supports a background image.', 'brevo-campaign-generator' ) . '</td></tr>' .
				'<tr><td style="padding:5px 8px;">Heading</td><td style="padding:5px 8px;">—</td><td style="padding:5px 8px;">' . __( 'Standalone section headline with optional accent line and subtext.', 'brevo-campaign-generator' ) . '</td></tr>' .
				'<tr><td style="padding:5px 8px;">Text Block</td><td style="padding:5px 8px;">✨</td><td style="padding:5px 8px;">' . __( 'Rich paragraph block with optional heading.', 'brevo-campaign-generator' ) . '</td></tr>' .
				'<tr><td style="padding:5px 8px;">Image</td><td style="padding:5px 8px;">—</td><td style="padding:5px 8px;">' . __( 'Full-width or aligned image block. Optionally linked.', 'brevo-campaign-generator' ) . '</td></tr>' .
				'<tr><td style="padding:5px 8px;">Products</td><td style="padding:5px 8px;">✨</td><td style="padding:5px 8px;">' . __( 'WooCommerce product grid. Enter product IDs; supports 1–3 columns. AI generates per-product copy.', 'brevo-campaign-generator' ) . '</td></tr>' .
				'<tr><td style="padding:5px 8px;">Banner</td><td style="padding:5px 8px;">✨</td><td style="padding:5px 8px;">' . __( 'Coloured announcement strip with heading and subtext.', 'brevo-campaign-generator' ) . '</td></tr>' .
				'<tr><td style="padding:5px 8px;">List</td><td style="padding:5px 8px;">—</td><td style="padding:5px 8px;">' . __( 'Bullet, numbered, checkmark, or plain list. Items defined as JSON.', 'brevo-campaign-generator' ) . '</td></tr>' .
				'<tr><td style="padding:5px 8px;">Call to Action</td><td style="padding:5px 8px;">✨</td><td style="padding:5px 8px;">' . __( 'Centred heading + subtext + prominent button.', 'brevo-campaign-generator' ) . '</td></tr>' .
				'<tr><td style="padding:5px 8px;">Coupon</td><td style="padding:5px 8px;">—</td><td style="padding:5px 8px;">' . __( 'Dashed-border coupon code block with discount text and expiry.', 'brevo-campaign-generator' ) . '</td></tr>' .
				'<tr><td style="padding:5px 8px;">Divider</td><td style="padding:5px 8px;">—</td><td style="padding:5px 8px;">' . __( 'Thin horizontal rule; configurable colour, thickness, and margin.', 'brevo-campaign-generator' ) . '</td></tr>' .
				'<tr><td style="padding:5px 8px;">Spacer</td><td style="padding:5px 8px;">—</td><td style="padding:5px 8px;">' . __( 'Blank vertical gap.', 'brevo-campaign-generator' ) . '</td></tr>' .
				'<tr><td style="padding:5px 8px;">Footer</td><td style="padding:5px 8px;">—</td><td style="padding:5px 8px;">' . __( 'Unsubscribe link, footer text, and optional footer links.', 'brevo-campaign-generator' ) . '</td></tr>' .
				'</tbody></table>' .
				'<p style="margin-top:12px;font-style:italic;">' . __( '✨ = AI can generate or suggest copy for this section type.', 'brevo-campaign-generator' ) . '</p>',
		) );

		$screen->add_help_tab( array(
			'id'      => 'bcg-sb-ai',
			'title'   => __( 'AI Generation', 'brevo-campaign-generator' ),
			'content' =>
				'<h2>' . __( 'AI-Powered Content Generation', 'brevo-campaign-generator' ) . '</h2>' .
				'<p>' . __( '<strong>Generate All with AI</strong> — fills headline, subtext, and copy fields for every AI-capable section in one click. Requires an OpenAI API key (set in Settings → API Keys).', 'brevo-campaign-generator' ) . '</p>' .
				'<p>' . __( 'Before generating, provide context in the toolbar:', 'brevo-campaign-generator' ) . '</p>' .
				'<ul>' .
				'<li><strong>' . __( 'Campaign theme', 'brevo-campaign-generator' ) . '</strong> — ' . __( 'e.g. "Black Friday", "Summer Sale", "New Arrivals". Leave blank for a general email.', 'brevo-campaign-generator' ) . '</li>' .
				'<li><strong>' . __( 'Tone of voice', 'brevo-campaign-generator' ) . '</strong> — ' . __( 'Professional, Friendly, Urgent, Playful, or Luxury.', 'brevo-campaign-generator' ) . '</li>' .
				'<li><strong>' . __( 'Language', 'brevo-campaign-generator' ) . '</strong> — ' . __( 'The target language for the generated copy.', 'brevo-campaign-generator' ) . '</li>' .
				'</ul>' .
				'<p>' . __( 'You can also regenerate a single section by clicking the ✨ icon on its canvas card or in the settings panel.', 'brevo-campaign-generator' ) . '</p>' .
				'<p>' . __( 'For the <strong>Products</strong> section, enter comma-separated WooCommerce product IDs first; AI will generate a headline and short description for each product.', 'brevo-campaign-generator' ) . '</p>',
		) );

		$screen->add_help_tab( array(
			'id'      => 'bcg-sb-save',
			'title'   => __( 'Saving & Loading', 'brevo-campaign-generator' ),
			'content' =>
				'<h2>' . __( 'Templates', 'brevo-campaign-generator' ) . '</h2>' .
				'<p>' . __( 'Named templates let you save a section layout and reuse it for future campaigns.', 'brevo-campaign-generator' ) . '</p>' .
				'<ol>' .
				'<li>' . __( 'Type a name in the <strong>Template Name</strong> field at the top-left.', 'brevo-campaign-generator' ) . '</li>' .
				'<li>' . __( 'Click <strong>Save Template</strong>. The template is stored in the database and the unsaved-changes indicator (●) disappears.', 'brevo-campaign-generator' ) . '</li>' .
				'<li>' . __( 'To reload it later, click <strong>Load Template</strong> and choose from the list.', 'brevo-campaign-generator' ) . '</li>' .
				'<li>' . __( 'Saving again with the same template open performs an <strong>update</strong>; saving with a new name creates a <strong>copy</strong>.', 'brevo-campaign-generator' ) . '</li>' .
				'</ol>' .
				'<p>' . __( 'The unsaved-changes dot (●) in the Canvas panel header shows when there are changes that have not yet been saved.', 'brevo-campaign-generator' ) . '</p>',
		) );

		$screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'brevo-campaign-generator' ) . '</strong></p>' .
			'<p><a href="https://github.com/dompl/brevo-campaign-generator" target="_blank">' . __( 'GitHub Repository', 'brevo-campaign-generator' ) . '</a></p>'
		);
	}

	/**
	 * Render the Section Builder page.
	 *
	 * @since  1.5.0
	 * @return void
	 */
	public function render_section_builder_page(): void {
		require_once BCG_PLUGIN_DIR . 'admin/views/page-section-builder.php';
	}

	/**
	 * Render the AI Trainer page.
	 *
	 * @since 1.5.29
	 * @return void
	 */
	public function render_ai_trainer_page(): void {
		require_once BCG_PLUGIN_DIR . 'admin/views/page-ai-trainer.php';
	}

	/**
	 * Render the Help & Documentation page.
	 *
	 * @since  1.5.20
	 * @return void
	 */
	public function render_help_page(): void {
		require BCG_PLUGIN_DIR . 'admin/views/page-help.php';
	}

	// ─── Template Builder AJAX Handlers ─────────────────────────────────

	/**
	 * Handle bcg_sb_preview — render sections JSON to HTML for preview iframe.
	 *
	 * @since  1.5.0
	 * @return void
	 */
	public function handle_sb_preview(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'brevo-campaign-generator' ) ), 403 );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$sections_raw = isset( $_POST['sections'] ) ? wp_unslash( $_POST['sections'] ) : '[]';
		$sections     = json_decode( $sections_raw, true );

		if ( ! is_array( $sections ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid sections data.', 'brevo-campaign-generator' ) ) );
		}

		$html = BCG_Section_Renderer::render_sections( $sections );

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Handle bcg_sb_save_template — upsert a named section template.
	 *
	 * @since  1.5.0
	 * @return void
	 */
	public function handle_sb_save_template(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'brevo-campaign-generator' ) ), 403 );
		}

		$name        = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$description = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );
		$id          = absint( $_POST['id'] ?? 0 );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$sections_raw = isset( $_POST['sections'] ) ? wp_unslash( $_POST['sections'] ) : '[]';
		$sections     = json_decode( $sections_raw, true );

		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Template name is required.', 'brevo-campaign-generator' ) ) );
		}

		if ( ! is_array( $sections ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid sections data.', 'brevo-campaign-generator' ) ) );
		}

		$result = BCG_Section_Templates_Table::upsert( $name, $description, $sections, $id ?: null );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'id' => $result, 'message' => __( 'Template saved.', 'brevo-campaign-generator' ) ) );
	}

	/**
	 * Handle bcg_get_section_templates — return saved section templates for the campaign wizard.
	 *
	 * @since  1.5.7
	 * @return void
	 */
	public function handle_get_section_templates(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'brevo-campaign-generator' ) ) );
		}

		$templates = BCG_Section_Templates_Table::get_all();

		$formatted = array();
		foreach ( $templates as $tpl ) {
			$formatted[] = array(
				'id'          => (int) $tpl->id,
				'name'        => $tpl->name,
				'description' => $tpl->description ?? '',
				'updated_at'  => $tpl->updated_at ?? '',
			);
		}

		wp_send_json_success( array( 'templates' => $formatted ) );
	}

	/**
	 * Handle bcg_sb_get_templates — list all saved section templates.
	 *
	 * @since  1.5.0
	 * @return void
	 */
	public function handle_sb_get_templates(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'brevo-campaign-generator' ) ), 403 );
		}

		$templates = BCG_Section_Templates_Table::get_all();

		wp_send_json_success( array( 'templates' => $templates ) );
	}

	/**
	 * Handle bcg_sb_load_template — return sections JSON for a given template ID.
	 *
	 * @since  1.5.0
	 * @return void
	 */
	public function handle_sb_load_template(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'brevo-campaign-generator' ) ), 403 );
		}

		$id = absint( $_POST['id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid template ID.', 'brevo-campaign-generator' ) ) );
		}

		$template = BCG_Section_Templates_Table::get( $id );
		if ( is_wp_error( $template ) ) {
			wp_send_json_error( array( 'message' => $template->get_error_message() ) );
		}

		$sections = json_decode( $template->sections, true );
		if ( ! is_array( $sections ) ) {
			$sections = array();
		}

		wp_send_json_success( array(
			'id'          => (int) $template->id,
			'name'        => $template->name,
			'description' => $template->description,
			'sections'    => $sections,
		) );
	}

	/**
	 * Handle bcg_sb_delete_template — delete a section template by ID.
	 *
	 * @since  1.5.0
	 * @return void
	 */
	public function handle_sb_delete_template(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'brevo-campaign-generator' ) ), 403 );
		}

		$id = absint( $_POST['id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid template ID.', 'brevo-campaign-generator' ) ) );
		}

		$result = BCG_Section_Templates_Table::delete( $id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Template deleted.', 'brevo-campaign-generator' ) ) );
	}

	/**
	 * Handle bcg_sb_generate_all — AI-fill all has_ai sections.
	 *
	 * @since  1.5.0
	 * @return void
	 */
	public function handle_sb_generate_all(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'brevo-campaign-generator' ) ), 403 );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$sections_raw = isset( $_POST['sections'] ) ? wp_unslash( $_POST['sections'] ) : '[]';
		$sections     = json_decode( $sections_raw, true );

		if ( ! is_array( $sections ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid sections data.', 'brevo-campaign-generator' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$context_raw = isset( $_POST['context'] ) ? wp_unslash( $_POST['context'] ) : '{}';
		$context     = json_decode( $context_raw, true );
		if ( ! is_array( $context ) ) {
			$context = array();
		}

		$context['tone']     = sanitize_text_field( $context['tone'] ?? 'Professional' );
		$context['language'] = sanitize_text_field( $context['language'] ?? 'English' );
		$context['theme']    = sanitize_text_field( $context['theme'] ?? '' );
		$context['prompt']   = sanitize_textarea_field( $context['prompt'] ?? '' );

		$updated_sections = BCG_Section_AI::generate_all( $sections, $context );

		wp_send_json_success( array( 'sections' => $updated_sections ) );
	}

	/**
	 * Handle bcg_sb_generate_section — AI-fill a single section by index.
	 *
	 * @since  1.5.0
	 * @return void
	 */
	public function handle_sb_generate_section(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'brevo-campaign-generator' ) ), 403 );
		}

		$type = sanitize_key( wp_unslash( $_POST['section_type'] ?? '' ) );
		if ( empty( $type ) ) {
			wp_send_json_error( array( 'message' => __( 'Section type is required.', 'brevo-campaign-generator' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$settings_raw = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : '{}';
		$settings     = json_decode( $settings_raw, true );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$context_raw = isset( $_POST['context'] ) ? wp_unslash( $_POST['context'] ) : '{}';
		$context     = json_decode( $context_raw, true );
		if ( ! is_array( $context ) ) {
			$context = array();
		}

		$context['tone']     = sanitize_text_field( $context['tone'] ?? 'Professional' );
		$context['language'] = sanitize_text_field( $context['language'] ?? 'English' );
		$context['theme']    = sanitize_text_field( $context['theme'] ?? '' );
		$context['prompt']   = sanitize_textarea_field( $context['prompt'] ?? '' );

		$result = BCG_Section_AI::generate( $type, $settings, $context );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'settings' => $result ) );
	}

	/**
	 * Handle section request submission.
	 *
	 * Sends an email to info@redfrogstudio.co.uk with the section details.
	 *
	 * @since  1.5.15
	 * @return void
	 */
	public function handle_request_section(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'brevo-campaign-generator' ) ) );
		}

		$section_type = sanitize_text_field( wp_unslash( $_POST['section_type'] ?? '' ) );
		$description  = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );
		$user_name    = sanitize_text_field( wp_unslash( $_POST['user_name'] ?? '' ) );
		$user_email   = sanitize_email( wp_unslash( $_POST['user_email'] ?? '' ) );

		if ( empty( $section_type ) || empty( $description ) ) {
			wp_send_json_error( array( 'message' => __( 'Section type and description are required.', 'brevo-campaign-generator' ) ) );
		}

		$site_url   = get_bloginfo( 'url' );
		$admin_url  = admin_url();
		$plugin_ver = BCG_VERSION;
		$date       = gmdate( 'Y-m-d H:i:s' ) . ' UTC';

		$subject = sprintf( '[Section Request] %s — %s', $section_type, (string) wp_parse_url( $site_url, PHP_URL_HOST ) );

		$body  = "A new section has been requested via the Brevo Campaign Generator plugin.\r\n\r\n";
		$body .= "Section Type:   {$section_type}\r\n";
		$body .= "Description:\r\n{$description}\r\n\r\n";
		$body .= "--- Requester ---\r\n";
		$body .= "Name:           {$user_name}\r\n";
		$body .= "Email:          {$user_email}\r\n\r\n";
		$body .= "--- Site Info ---\r\n";
		$body .= "Site URL:       {$site_url}\r\n";
		$body .= "Admin URL:      {$admin_url}\r\n";
		$body .= "Plugin Version: {$plugin_ver}\r\n";
		$body .= "Date:           {$date}\r\n";

		$headers = array(
			'Content-Type: text/plain; charset=UTF-8',
			'Reply-To: ' . $user_name . ' <' . $user_email . '>',
		);

		$sent = wp_mail( 'info@redfrogstudio.co.uk', $subject, $body, $headers );

		if ( $sent ) {
			wp_send_json_success( array( 'message' => __( "Your request has been sent. We'll be in touch!", 'brevo-campaign-generator' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to send your request. Please try again.', 'brevo-campaign-generator' ) ) );
		}
	}

	/**
	 * Add custom body class to all BCG admin pages.
	 *
	 * @since  1.4.0
	 * @param  string $classes Space-separated list of body classes.
	 * @return string Modified body classes.
	 */
	/**
	 * Regenerate AI content for one section in a section-builder campaign.
	 *
	 * Loads the campaign's sections_json, finds the section by UUID, runs
	 * BCG_Section_AI::generate() for that section type, then re-renders the
	 * full sections HTML and persists the update.
	 *
	 * @since  1.5.27
	 * @return void
	 */
	public function handle_regen_campaign_section(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission.', 'brevo-campaign-generator' ) ) );
		}

		$campaign_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;
		$section_id  = isset( $_POST['section_id'] )  ? sanitize_text_field( wp_unslash( $_POST['section_id'] ) ) : '';
		$tone        = isset( $_POST['tone'] )        ? sanitize_text_field( wp_unslash( $_POST['tone'] ) ) : 'Professional';
		$language    = isset( $_POST['language'] )    ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : 'English';
		$theme       = isset( $_POST['theme'] )       ? sanitize_text_field( wp_unslash( $_POST['theme'] ) ) : '';

		if ( ! $campaign_id || ! $section_id ) {
			wp_send_json_error( array( 'message' => __( 'Missing campaign or section ID.', 'brevo-campaign-generator' ) ) );
		}

		$campaign_handler = new BCG_Campaign();
		$campaign         = $campaign_handler->get( $campaign_id );

		if ( is_wp_error( $campaign ) ) {
			wp_send_json_error( array( 'message' => $campaign->get_error_message() ) );
		}

		$sections = json_decode( $campaign->sections_json ?? '[]', true );

		if ( ! is_array( $sections ) ) {
			wp_send_json_error( array( 'message' => __( 'No sections data found for this campaign.', 'brevo-campaign-generator' ) ) );
		}

		// Collect product data from any products sections in this campaign.
		$product_data = array();
		$product_selector = new BCG_Product_Selector();

		foreach ( $sections as $sec ) {
			if ( 'products' === ( $sec['type'] ?? '' ) && ! empty( $sec['settings']['product_ids'] ) ) {
				$ids = array_filter( array_map( 'absint', explode( ',', $sec['settings']['product_ids'] ) ) );
				foreach ( $ids as $pid ) {
					$wc_product = wc_get_product( $pid );
					if ( $wc_product ) {
						$product_data[] = $product_selector->format_product_preview( $wc_product );
					}
				}
				break; // Use first products section.
			}
		}

		// Build AI context.
		$currency_symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '£';
		$context         = array(
			'products'        => $product_data,
			'theme'           => $theme,
			'tone'            => $tone,
			'language'        => $language,
			'currency_symbol' => $currency_symbol,
		);

		// Find and regenerate the target section.
		$updated_section = null;
		foreach ( $sections as &$section ) {
			if ( ( $section['id'] ?? '' ) !== $section_id ) {
				continue;
			}

			$result = BCG_Section_AI::generate( $section['type'], $section['settings'] ?? array(), $context );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}

			$section['settings'] = $result;
			$updated_section     = $section;
			break;
		}
		unset( $section );

		if ( null === $updated_section ) {
			wp_send_json_error( array( 'message' => __( 'Section not found.', 'brevo-campaign-generator' ) ) );
		}

		// Re-render and persist.
		$global_settings = json_decode( get_option( 'bcg_default_template_settings', '{}' ), true );
		if ( ! is_array( $global_settings ) ) {
			$global_settings = array();
		}

		$rendered_html = BCG_Section_Renderer::render_sections( $sections, $global_settings );

		$campaign_handler->update( $campaign_id, array(
			'sections_json' => wp_json_encode( $sections ),
			'template_html' => $rendered_html,
		) );

		wp_send_json_success( array(
			'section'  => $updated_section,
			'settings' => $updated_section['settings'],
		) );
	}

	public function add_bcg_body_class( string $classes ): string {
		$screen = get_current_screen();
		if ( $screen && $this->is_bcg_page( $screen->id ) ) {
			$classes .= ' bcg-admin-page';
		}
		return $classes;
	}
}
