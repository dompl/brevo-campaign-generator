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

		// Settings.
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Settings', 'brevo-campaign-generator' ),
			__( 'Settings', 'brevo-campaign-generator' ),
			self::CAPABILITY,
			'bcg-settings',
			array( $this, 'render_settings_page' )
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
			BCG_VERSION
		);

		// WordPress colour picker for template/settings.
		wp_enqueue_style( 'wp-color-picker' );

		// ── Global bcgData object (available on every BCG page) ─────────

		$settings_obj   = new BCG_Settings();
		$credit_balance = $this->get_current_user_credit_balance();

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
		);

		// Register a minimal inline script handle to localise bcgData against.
		wp_register_script( 'bcg-admin-global', '', array(), BCG_VERSION, false );
		wp_enqueue_script( 'bcg-admin-global' );
		wp_localize_script( 'bcg-admin-global', 'bcgData', $bcg_data );

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
					'ajax_url'        => admin_url( 'admin-ajax.php' ),
					'nonce'           => wp_create_nonce( 'bcg_nonce' ),
					'edit_url'        => admin_url( 'admin.php?page=bcg-edit-campaign' ),
					'currency_symbol' => $currency_symbol,
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
						'subscribers'             => __( 'subscribers', 'brevo-campaign-generator' ),
						'sales'                   => __( 'sales', 'brevo-campaign-generator' ),
						'remove'                  => __( 'Remove', 'brevo-campaign-generator' ),
						'already_added'           => __( 'Already added', 'brevo-campaign-generator' ),
					),
				)
			);
		}

		// Settings page JS.
		if ( str_contains( $hook_suffix, 'bcg-settings' ) ) {
			wp_enqueue_script(
				'bcg-settings',
				BCG_PLUGIN_URL . 'admin/js/bcg-settings.js',
				array( 'jquery', 'wp-color-picker' ),
				BCG_VERSION,
				true
			);

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
				BCG_VERSION,
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
	private function is_bcg_page( string $hook_suffix ): bool {
		$bcg_pages = array(
			'toplevel_page_bcg-dashboard',
			'brevo-campaigns_page_bcg-new-campaign',
			'brevo-campaigns_page_bcg-template-editor',
			'brevo-campaigns_page_bcg-stats',
			'brevo-campaigns_page_bcg-credits',
			'brevo-campaigns_page_bcg-settings',
			'brevo-campaigns_page_bcg-edit-campaign',
			'admin_page_bcg-edit-campaign',
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

		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';

		if ( empty( $title ) ) {
			wp_send_json_error( array( 'message' => __( 'Campaign title is required.', 'brevo-campaign-generator' ) ) );
		}

		$subject         = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
		$preview_text    = isset( $_POST['preview_text'] ) ? sanitize_text_field( wp_unslash( $_POST['preview_text'] ) ) : '';
		$mailing_list_id = isset( $_POST['mailing_list_id'] ) ? sanitize_text_field( wp_unslash( $_POST['mailing_list_id'] ) ) : '';

		// Product selection config.
		$product_source  = isset( $_POST['product_source'] ) ? sanitize_text_field( wp_unslash( $_POST['product_source'] ) ) : 'bestsellers';
		$product_count   = isset( $_POST['product_count'] ) ? absint( $_POST['product_count'] ) : (int) get_option( 'bcg_default_products_per_campaign', 3 );
		$category_ids    = isset( $_POST['category_ids'] ) && is_array( $_POST['category_ids'] ) ? array_map( 'absint', $_POST['category_ids'] ) : array();
		$manual_ids      = isset( $_POST['manual_ids'] ) && is_array( $_POST['manual_ids'] ) ? array_map( 'absint', $_POST['manual_ids'] ) : array();

		// Coupon config.
		$generate_coupon  = ! empty( $_POST['generate_coupon'] );
		$discount_type    = isset( $_POST['discount_type'] ) ? sanitize_text_field( wp_unslash( $_POST['discount_type'] ) ) : 'percent';
		$discount_value   = isset( $_POST['discount_value'] ) ? (float) $_POST['discount_value'] : (float) get_option( 'bcg_default_coupon_discount', 10 );
		$expiry_days      = isset( $_POST['expiry_days'] ) ? absint( $_POST['expiry_days'] ) : (int) get_option( 'bcg_default_coupon_expiry_days', 7 );
		$coupon_prefix    = isset( $_POST['coupon_prefix'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_prefix'] ) ) : '';

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

		// ── 2. Create draft campaign ────────────────────────────────────

		$campaign_handler = new BCG_Campaign();
		$template_engine  = new BCG_Template();

		$draft_data = array(
			'title'             => $title,
			'subject'           => $subject,
			'preview_text'      => $preview_text,
			'mailing_list_id'   => $mailing_list_id,
			'template_html'     => $template_engine->get_default_template(),
			'template_settings' => wp_json_encode( $template_engine->get_default_settings() ),
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

		// ── 5. Generate campaign copy via AI ─────────────────────────────

		$ai_manager = new BCG_AI_Manager();
		$copy_result = $ai_manager->generate_campaign_copy(
			$campaign_id,
			$product_data_for_ai,
			array(
				'theme'           => $theme,
				'tone'            => $tone,
				'language'        => $language,
				'generate_coupon' => false, // Coupon already handled above.
			)
		);

		if ( is_wp_error( $copy_result ) ) {
			wp_send_json_error( array( 'message' => $copy_result->get_error_message() ) );
		}

		// ── 6. Generate images via AI (if enabled) ───────────────────────

		$main_image_url = '';
		$product_images = array();

		if ( $generate_images ) {
			$image_result = $ai_manager->generate_campaign_images(
				$campaign_id,
				$product_data_for_ai,
				array(
					'theme'                   => $theme,
					'style'                   => $image_style,
					'generate_product_images' => true,
				)
			);

			if ( ! is_wp_error( $image_result ) ) {
				$main_image_url = $image_result['main_image_url'] ?? '';
				$product_images = $image_result['product_images'] ?? array();
			}
		}

		// ── 7. Update campaign with generated content ────────────────────

		$update_data = array(
			'subject'          => ! empty( $copy_result['subject_line'] ) ? $copy_result['subject_line'] : $subject,
			'preview_text'     => ! empty( $copy_result['preview_text'] ) ? $copy_result['preview_text'] : $preview_text,
			'main_headline'    => $copy_result['main_headline'] ?? '',
			'main_description' => $copy_result['main_description'] ?? '',
			'main_image_url'   => $main_image_url,
			'status'           => 'draft',
		);

		$campaign_handler->update( $campaign_id, $update_data );

		// Add products to campaign with AI content.
		$enriched_products = $copy_result['products'] ?? array();

		foreach ( $enriched_products as $index => $product_entry ) {
			$product_id = isset( $product_entry['id'] ) ? absint( $product_entry['id'] ) : 0;

			if ( $product_id <= 0 ) {
				continue;
			}

			$ai_data = array(
				'sort_order'          => $index,
				'ai_headline'         => $product_entry['ai_headline'] ?? '',
				'ai_short_desc'       => $product_entry['ai_short_desc'] ?? '',
				'use_product_image'   => $generate_images ? 0 : 1,
				'show_buy_button'     => 1,
			);

			// Attach AI-generated product image if available.
			if ( isset( $product_images[ $index ] ) && ! empty( $product_images[ $index ] ) ) {
				$ai_data['generated_image_url'] = $product_images[ $index ];
			}

			$campaign_handler->add_product( $campaign_id, $product_id, $ai_data );
		}

		// ── 8. Return success with redirect URL ──────────────────────────

		$edit_url = admin_url( 'admin.php?page=bcg-edit-campaign&campaign_id=' . $campaign_id );

		wp_send_json_success( array(
			'message'       => __( 'Campaign generated successfully!', 'brevo-campaign-generator' ),
			'campaign_id'   => $campaign_id,
			'redirect_url'  => $edit_url,
			'credits_used'  => ( $copy_result['total_credits_used'] ?? 0 ) + ( $image_result['total_credits_used'] ?? 0 ),
			'new_balance'   => $ai_manager->get_credit_balance(),
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

		if ( ! $campaign_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid campaign ID.', 'brevo-campaign-generator' ) ) );
		}

		if ( empty( $field ) ) {
			wp_send_json_error( array( 'message' => __( 'No field specified for regeneration.', 'brevo-campaign-generator' ) ) );
		}

		// Load the campaign to build context.
		$campaign_handler = new BCG_Campaign();
		$campaign         = $campaign_handler->get( $campaign_id );

		if ( is_wp_error( $campaign ) ) {
			wp_send_json_error( array( 'message' => $campaign->get_error_message() ) );
		}

		// Build product data for AI context.
		$product_selector = new BCG_Product_Selector();
		$products_data    = array();

		foreach ( $campaign->products as $product_row ) {
			$wc_product = wc_get_product( (int) $product_row->product_id );
			if ( $wc_product instanceof \WC_Product ) {
				$products_data[] = $product_selector->format_product_preview( $wc_product );
			}
		}

		// Build single product data if this is a product-level field.
		$single_product = array();
		$product_row_id = isset( $_POST['product_row_id'] ) ? absint( $_POST['product_row_id'] ) : 0;

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

		// Retrieve tone/theme/language from POST or fall back to defaults.
		$tone     = isset( $_POST['tone'] ) ? sanitize_text_field( wp_unslash( $_POST['tone'] ) ) : 'Professional';
		$theme    = isset( $_POST['theme'] ) ? sanitize_text_field( wp_unslash( $_POST['theme'] ) ) : '';
		$language = isset( $_POST['language'] ) ? sanitize_text_field( wp_unslash( $_POST['language'] ) ) : 'English';
		$style    = isset( $_POST['image_style'] ) ? sanitize_text_field( wp_unslash( $_POST['image_style'] ) ) : 'Photorealistic';

		$context = array(
			'products' => $products_data,
			'product'  => $single_product,
			'theme'    => $theme,
			'tone'     => $tone,
			'language' => $language,
			'subject'  => $campaign->subject ?? '',
			'style'    => $style,
		);

		$ai_manager = new BCG_AI_Manager();
		$result     = $ai_manager->regenerate_field( $campaign_id, $field, $context );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Map field name to campaign/product DB column for auto-saving.
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

		// Auto-save the regenerated content to the DB.
		if ( isset( $campaign_field_map[ $field ] ) ) {
			$campaign_handler->update( $campaign_id, array(
				$campaign_field_map[ $field ] => $result['content'],
			) );
		} elseif ( isset( $product_field_map[ $field ] ) && $product_row_id > 0 ) {
			$campaign_handler->update_product( $product_row_id, array(
				$product_field_map[ $field ] => $result['content'],
			) );
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

		$config = array(
			'count'        => $count,
			'source'       => $source,
			'category_ids' => $category_ids,
			'manual_ids'   => $manual_ids,
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

		// Template HTML — allow full HTML including <style> tags for email templates.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Full email HTML stored for campaign. Capability-gated to manage_woocommerce admins only.
		if ( isset( $_POST['template_html'] ) ) {
			$update_data['template_html'] = wp_unslash( $_POST['template_html'] );
		}

		// Template settings (JSON string).
		if ( isset( $_POST['template_settings'] ) ) {
			$settings_raw = sanitize_text_field( wp_unslash( $_POST['template_settings'] ) );
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
		$settings_raw  = isset( $_POST['template_settings'] ) ? sanitize_text_field( wp_unslash( $_POST['template_settings'] ) ) : '{}';
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
		$settings_raw  = isset( $_POST['template_settings'] ) ? sanitize_text_field( wp_unslash( $_POST['template_settings'] ) ) : '{}';

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
}
