<?php
/**
 * Settings handler.
 *
 * Registers all plugin settings using the WordPress Settings API, organises
 * them into five tabs (API Keys, AI Models, Brevo, Stripe, Defaults), and
 * provides sanitisation callbacks and the AJAX handler for API key testing.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Settings
 *
 * Manages the plugin settings page, registration, sanitisation, and
 * API connection testing.
 *
 * @since 1.0.0
 */
class BCG_Settings {

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'bcg-settings';

	/**
	 * Available tabs and their labels.
	 *
	 * @var array<string, string>
	 */
	private array $tabs = array();

	/**
	 * Constructor.
	 *
	 * Hooks into admin_init for settings registration and wp_ajax for
	 * the API key test handler.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->tabs = array(
			'api-keys'  => __( 'API Keys', 'brevo-campaign-generator' ),
			'ai-models' => __( 'AI Models', 'brevo-campaign-generator' ),
			'brevo'     => __( 'Brevo', 'brevo-campaign-generator' ),
			'stripe'    => __( 'Stripe', 'brevo-campaign-generator' ),
			'defaults'  => __( 'Defaults', 'brevo-campaign-generator' ),
		);

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_bcg_test_api_key', array( $this, 'handle_test_api_key' ) );
		add_action( 'wp_ajax_bcg_get_brevo_lists', array( $this, 'handle_get_brevo_lists' ) );
	}

	/**
	 * Get the available tabs.
	 *
	 * @since  1.0.0
	 * @return array<string, string>
	 */
	public function get_tabs(): array {
		return $this->tabs;
	}

	/**
	 * Get the current active tab.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_current_tab(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading tab parameter for display only.
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'api-keys';

		return array_key_exists( $tab, $this->tabs ) ? $tab : 'api-keys';
	}

	/**
	 * Register all settings, sections, and fields for each tab.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register_settings(): void {
		$this->register_api_keys_settings();
		$this->register_ai_models_settings();
		$this->register_brevo_settings();
		$this->register_stripe_settings();
		$this->register_defaults_settings();
	}

	// ─── API Keys Tab ───────────────────────────────────────────────────

	/**
	 * Register API Keys tab settings.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function register_api_keys_settings(): void {
		$section = 'bcg_api_keys_section';
		$page    = 'bcg_settings_api_keys';

		add_settings_section(
			$section,
			__( 'API Key Configuration', 'brevo-campaign-generator' ),
			array( $this, 'render_api_keys_section_description' ),
			$page
		);

		// OpenAI API Key.
		register_setting( $page, 'bcg_openai_api_key', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_api_key' ),
			'default'           => '',
		) );

		add_settings_field(
			'bcg_openai_api_key',
			__( 'OpenAI API Key', 'brevo-campaign-generator' ),
			array( $this, 'render_api_key_field' ),
			$page,
			$section,
			array(
				'label_for'   => 'bcg_openai_api_key',
				'option_name' => 'bcg_openai_api_key',
				'service'     => 'openai',
				'description' => __( 'Your OpenAI API key for GPT text generation.', 'brevo-campaign-generator' ),
			)
		);

		// Google Gemini API Key.
		register_setting( $page, 'bcg_gemini_api_key', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_api_key' ),
			'default'           => '',
		) );

		add_settings_field(
			'bcg_gemini_api_key',
			__( 'Google Gemini API Key', 'brevo-campaign-generator' ),
			array( $this, 'render_api_key_field' ),
			$page,
			$section,
			array(
				'label_for'   => 'bcg_gemini_api_key',
				'option_name' => 'bcg_gemini_api_key',
				'service'     => 'gemini',
				'description' => __( 'Your Google Gemini API key for image generation.', 'brevo-campaign-generator' ),
			)
		);

		// Brevo API Key.
		register_setting( $page, 'bcg_brevo_api_key', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_api_key' ),
			'default'           => '',
		) );

		add_settings_field(
			'bcg_brevo_api_key',
			__( 'Brevo API Key', 'brevo-campaign-generator' ),
			array( $this, 'render_api_key_field' ),
			$page,
			$section,
			array(
				'label_for'   => 'bcg_brevo_api_key',
				'option_name' => 'bcg_brevo_api_key',
				'service'     => 'brevo',
				'description' => __( 'Your Brevo (formerly Sendinblue) API key.', 'brevo-campaign-generator' ),
			)
		);

		// Stripe Publishable Key.
		register_setting( $page, 'bcg_stripe_publishable_key', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_api_key' ),
			'default'           => '',
		) );

		add_settings_field(
			'bcg_stripe_publishable_key',
			__( 'Stripe Publishable Key', 'brevo-campaign-generator' ),
			array( $this, 'render_api_key_field' ),
			$page,
			$section,
			array(
				'label_for'   => 'bcg_stripe_publishable_key',
				'option_name' => 'bcg_stripe_publishable_key',
				'service'     => 'stripe_pub',
				'description' => __( 'Your Stripe publishable key (starts with pk_).', 'brevo-campaign-generator' ),
			)
		);

		// Stripe Secret Key.
		register_setting( $page, 'bcg_stripe_secret_key', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_api_key' ),
			'default'           => '',
		) );

		add_settings_field(
			'bcg_stripe_secret_key',
			__( 'Stripe Secret Key', 'brevo-campaign-generator' ),
			array( $this, 'render_api_key_field' ),
			$page,
			$section,
			array(
				'label_for'   => 'bcg_stripe_secret_key',
				'option_name' => 'bcg_stripe_secret_key',
				'service'     => 'stripe_secret',
				'description' => __( 'Your Stripe secret key (starts with sk_). Keep this confidential.', 'brevo-campaign-generator' ),
			)
		);
	}

	// ─── AI Models Tab ──────────────────────────────────────────────────

	/**
	 * Register AI Models tab settings.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function register_ai_models_settings(): void {
		$section = 'bcg_ai_models_section';
		$page    = 'bcg_settings_ai_models';

		add_settings_section(
			$section,
			__( 'AI Model Selection', 'brevo-campaign-generator' ),
			array( $this, 'render_ai_models_section_description' ),
			$page
		);

		// OpenAI Model.
		register_setting( $page, 'bcg_openai_model', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_openai_model' ),
			'default'           => 'gpt-4o',
		) );

		add_settings_field(
			'bcg_openai_model',
			__( 'OpenAI Model', 'brevo-campaign-generator' ),
			array( $this, 'render_openai_model_field' ),
			$page,
			$section,
			array( 'label_for' => 'bcg_openai_model' )
		);

		// Gemini Model.
		register_setting( $page, 'bcg_gemini_model', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_gemini_model' ),
			'default'           => 'gemini-2.0-flash',
		) );

		add_settings_field(
			'bcg_gemini_model',
			__( 'Gemini Model', 'brevo-campaign-generator' ),
			array( $this, 'render_gemini_model_field' ),
			$page,
			$section,
			array( 'label_for' => 'bcg_gemini_model' )
		);

		// ── Credit costs section ────────────────────────────────────────

		$credits_section = 'bcg_credit_costs_section';

		add_settings_section(
			$credits_section,
			__( 'Credit Costs per AI Operation', 'brevo-campaign-generator' ),
			array( $this, 'render_credit_costs_section_description' ),
			$page
		);

		// Credit cost: OpenAI GPT-4o.
		register_setting( $page, 'bcg_credit_cost_openai_gpt4o', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 5,
		) );

		add_settings_field(
			'bcg_credit_cost_openai_gpt4o',
			__( 'GPT-4o (per generation)', 'brevo-campaign-generator' ),
			array( $this, 'render_number_field' ),
			$page,
			$credits_section,
			array(
				'label_for'   => 'bcg_credit_cost_openai_gpt4o',
				'option_name' => 'bcg_credit_cost_openai_gpt4o',
				'min'         => 1,
				'max'         => 100,
				'suffix'      => __( 'credits', 'brevo-campaign-generator' ),
				'description' => __( 'Credits deducted per GPT-4o text generation.', 'brevo-campaign-generator' ),
			)
		);

		// Credit cost: OpenAI GPT-4o Mini.
		register_setting( $page, 'bcg_credit_cost_openai_gpt4o_mini', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 1,
		) );

		add_settings_field(
			'bcg_credit_cost_openai_gpt4o_mini',
			__( 'GPT-4o Mini (per generation)', 'brevo-campaign-generator' ),
			array( $this, 'render_number_field' ),
			$page,
			$credits_section,
			array(
				'label_for'   => 'bcg_credit_cost_openai_gpt4o_mini',
				'option_name' => 'bcg_credit_cost_openai_gpt4o_mini',
				'min'         => 1,
				'max'         => 100,
				'suffix'      => __( 'credits', 'brevo-campaign-generator' ),
				'description' => __( 'Credits deducted per GPT-4o Mini text generation.', 'brevo-campaign-generator' ),
			)
		);

		// Credit cost: Gemini Pro.
		register_setting( $page, 'bcg_credit_cost_gemini_pro', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 10,
		) );

		add_settings_field(
			'bcg_credit_cost_gemini_pro',
			__( 'Gemini Pro (per image)', 'brevo-campaign-generator' ),
			array( $this, 'render_number_field' ),
			$page,
			$credits_section,
			array(
				'label_for'   => 'bcg_credit_cost_gemini_pro',
				'option_name' => 'bcg_credit_cost_gemini_pro',
				'min'         => 1,
				'max'         => 100,
				'suffix'      => __( 'credits', 'brevo-campaign-generator' ),
				'description' => __( 'Credits deducted per Gemini Pro image generation.', 'brevo-campaign-generator' ),
			)
		);

		// Credit cost: Gemini Flash.
		register_setting( $page, 'bcg_credit_cost_gemini_flash', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 3,
		) );

		add_settings_field(
			'bcg_credit_cost_gemini_flash',
			__( 'Gemini Flash (per image)', 'brevo-campaign-generator' ),
			array( $this, 'render_number_field' ),
			$page,
			$credits_section,
			array(
				'label_for'   => 'bcg_credit_cost_gemini_flash',
				'option_name' => 'bcg_credit_cost_gemini_flash',
				'min'         => 1,
				'max'         => 100,
				'suffix'      => __( 'credits', 'brevo-campaign-generator' ),
				'description' => __( 'Credits deducted per Gemini Flash image generation.', 'brevo-campaign-generator' ),
			)
		);

		// Credit value ratio.
		register_setting( $page, 'bcg_credit_value', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_decimal' ),
			'default'           => '0.05',
		) );

		add_settings_field(
			'bcg_credit_value',
			__( 'Credit Value Ratio', 'brevo-campaign-generator' ),
			array( $this, 'render_text_field' ),
			$page,
			$credits_section,
			array(
				'label_for'   => 'bcg_credit_value',
				'option_name' => 'bcg_credit_value',
				'class'       => 'small-text',
				'description' => __( 'Monetary value of 1 credit in your currency (e.g. 0.05 = 5p per credit).', 'brevo-campaign-generator' ),
			)
		);
	}

	// ─── Brevo Tab ──────────────────────────────────────────────────────

	/**
	 * Register Brevo tab settings.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function register_brevo_settings(): void {
		$section = 'bcg_brevo_section';
		$page    = 'bcg_settings_brevo';

		add_settings_section(
			$section,
			__( 'Brevo Configuration', 'brevo-campaign-generator' ),
			array( $this, 'render_brevo_section_description' ),
			$page
		);

		// Default Mailing List.
		register_setting( $page, 'bcg_brevo_default_list_id', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'bcg_brevo_default_list_id',
			__( 'Default Mailing List', 'brevo-campaign-generator' ),
			array( $this, 'render_brevo_list_field' ),
			$page,
			$section,
			array(
				'label_for'   => 'bcg_brevo_default_list_id',
				'option_name' => 'bcg_brevo_default_list_id',
			)
		);

		// Sender Name.
		register_setting( $page, 'bcg_brevo_sender_name', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'bcg_brevo_sender_name',
			__( 'Default Sender Name', 'brevo-campaign-generator' ),
			array( $this, 'render_text_field' ),
			$page,
			$section,
			array(
				'label_for'   => 'bcg_brevo_sender_name',
				'option_name' => 'bcg_brevo_sender_name',
				'class'       => 'regular-text',
				'description' => __( 'The sender name displayed in email clients.', 'brevo-campaign-generator' ),
			)
		);

		// Sender Email.
		register_setting( $page, 'bcg_brevo_sender_email', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_email',
			'default'           => '',
		) );

		add_settings_field(
			'bcg_brevo_sender_email',
			__( 'Default Sender Email', 'brevo-campaign-generator' ),
			array( $this, 'render_text_field' ),
			$page,
			$section,
			array(
				'label_for'   => 'bcg_brevo_sender_email',
				'option_name' => 'bcg_brevo_sender_email',
				'type'        => 'email',
				'class'       => 'regular-text',
				'description' => __( 'The from address for email campaigns. Must be verified in Brevo.', 'brevo-campaign-generator' ),
			)
		);

		// Campaign Prefix.
		register_setting( $page, 'bcg_brevo_campaign_prefix', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '[WC]',
		) );

		add_settings_field(
			'bcg_brevo_campaign_prefix',
			__( 'Campaign Prefix', 'brevo-campaign-generator' ),
			array( $this, 'render_text_field' ),
			$page,
			$section,
			array(
				'label_for'   => 'bcg_brevo_campaign_prefix',
				'option_name' => 'bcg_brevo_campaign_prefix',
				'class'       => 'small-text',
				'description' => __( 'Prefix added to all campaign names in Brevo (e.g. [WC]).', 'brevo-campaign-generator' ),
			)
		);
	}

	// ─── Stripe Tab ─────────────────────────────────────────────────────

	/**
	 * Register Stripe tab settings.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function register_stripe_settings(): void {
		$section = 'bcg_stripe_section';
		$page    = 'bcg_settings_stripe';

		add_settings_section(
			$section,
			__( 'Stripe Configuration', 'brevo-campaign-generator' ),
			array( $this, 'render_stripe_section_description' ),
			$page
		);

		// Currency.
		register_setting( $page, 'bcg_stripe_currency', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_currency' ),
			'default'           => 'GBP',
		) );

		add_settings_field(
			'bcg_stripe_currency',
			__( 'Currency', 'brevo-campaign-generator' ),
			array( $this, 'render_currency_field' ),
			$page,
			$section,
			array( 'label_for' => 'bcg_stripe_currency' )
		);

		// Credit Packs.
		register_setting( $page, 'bcg_stripe_credit_packs', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_credit_packs' ),
			'default'           => '',
		) );

		add_settings_field(
			'bcg_stripe_credit_packs',
			__( 'Credit Packs', 'brevo-campaign-generator' ),
			array( $this, 'render_credit_packs_field' ),
			$page,
			$section,
			array(
				'label_for'   => 'bcg_stripe_credit_packs',
				'option_name' => 'bcg_stripe_credit_packs',
			)
		);
	}

	// ─── Defaults Tab ───────────────────────────────────────────────────

	/**
	 * Register Defaults tab settings.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function register_defaults_settings(): void {
		$section = 'bcg_defaults_section';
		$page    = 'bcg_settings_defaults';

		add_settings_section(
			$section,
			__( 'Campaign Defaults', 'brevo-campaign-generator' ),
			array( $this, 'render_defaults_section_description' ),
			$page
		);

		// Default products per campaign.
		register_setting( $page, 'bcg_default_products_per_campaign', array(
			'type'              => 'integer',
			'sanitize_callback' => array( $this, 'sanitize_products_per_campaign' ),
			'default'           => 3,
		) );

		add_settings_field(
			'bcg_default_products_per_campaign',
			__( 'Products Per Campaign', 'brevo-campaign-generator' ),
			array( $this, 'render_number_field' ),
			$page,
			$section,
			array(
				'label_for'   => 'bcg_default_products_per_campaign',
				'option_name' => 'bcg_default_products_per_campaign',
				'min'         => 1,
				'max'         => 10,
				'description' => __( 'Default number of products included in a new campaign (1-10).', 'brevo-campaign-generator' ),
			)
		);

		// Default coupon discount.
		register_setting( $page, 'bcg_default_coupon_discount', array(
			'type'              => 'number',
			'sanitize_callback' => array( $this, 'sanitize_coupon_discount' ),
			'default'           => 10,
		) );

		add_settings_field(
			'bcg_default_coupon_discount',
			__( 'Default Coupon Discount (%)', 'brevo-campaign-generator' ),
			array( $this, 'render_number_field' ),
			$page,
			$section,
			array(
				'label_for'   => 'bcg_default_coupon_discount',
				'option_name' => 'bcg_default_coupon_discount',
				'min'         => 1,
				'max'         => 100,
				'suffix'      => '%',
				'description' => __( 'Default percentage discount for auto-generated coupons.', 'brevo-campaign-generator' ),
			)
		);

		// Default coupon expiry.
		register_setting( $page, 'bcg_default_coupon_expiry_days', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 7,
		) );

		add_settings_field(
			'bcg_default_coupon_expiry_days',
			__( 'Default Coupon Expiry', 'brevo-campaign-generator' ),
			array( $this, 'render_number_field' ),
			$page,
			$section,
			array(
				'label_for'   => 'bcg_default_coupon_expiry_days',
				'option_name' => 'bcg_default_coupon_expiry_days',
				'min'         => 1,
				'max'         => 365,
				'suffix'      => __( 'days', 'brevo-campaign-generator' ),
				'description' => __( 'Number of days before auto-generated coupons expire.', 'brevo-campaign-generator' ),
			)
		);

		// Auto-generate coupon.
		register_setting( $page, 'bcg_default_auto_generate_coupon', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'default'           => 'yes',
		) );

		add_settings_field(
			'bcg_default_auto_generate_coupon',
			__( 'Auto-Generate Coupon', 'brevo-campaign-generator' ),
			array( $this, 'render_checkbox_field' ),
			$page,
			$section,
			array(
				'label_for'   => 'bcg_default_auto_generate_coupon',
				'option_name' => 'bcg_default_auto_generate_coupon',
				'label'       => __( 'Automatically create a WooCommerce coupon when generating a new campaign.', 'brevo-campaign-generator' ),
			)
		);

		// Test Mode.
		register_setting( $page, 'bcg_test_mode', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'default'           => 'no',
		) );

		add_settings_field(
			'bcg_test_mode',
			__( 'Test Mode', 'brevo-campaign-generator' ),
			array( $this, 'render_checkbox_field' ),
			$page,
			$section,
			array(
				'label_for'   => 'bcg_test_mode',
				'option_name' => 'bcg_test_mode',
				'label'       => __( 'Enable Test Mode — bypass credit requirements for all AI operations.', 'brevo-campaign-generator' ),
			)
		);
	}

	// ─── Section Description Callbacks ──────────────────────────────────

	/**
	 * Render the API Keys section description.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_api_keys_section_description(): void {
		echo '<p>' . esc_html__(
			'Enter your API keys for each service. Keys are stored securely in the WordPress database. Use the Test Connection button to verify each key is valid.',
			'brevo-campaign-generator'
		) . '</p>';
	}

	/**
	 * Render the AI Models section description.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_ai_models_section_description(): void {
		echo '<p>' . esc_html__(
			'Select which AI models to use for text and image generation. Higher-quality models cost more credits per operation.',
			'brevo-campaign-generator'
		) . '</p>';
	}

	/**
	 * Render the Credit Costs section description.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_credit_costs_section_description(): void {
		echo '<p>' . esc_html__(
			'Configure how many credits are deducted for each AI operation. Adjust these values to reflect actual provider costs.',
			'brevo-campaign-generator'
		) . '</p>';
	}

	/**
	 * Render the Brevo section description.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_brevo_section_description(): void {
		echo '<p>' . esc_html__(
			'Configure your Brevo email campaign defaults. Ensure your sender email is verified in your Brevo account.',
			'brevo-campaign-generator'
		) . '</p>';
	}

	/**
	 * Render the Stripe section description.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_stripe_section_description(): void {
		echo '<p>' . esc_html__(
			'Configure your Stripe payment settings for credit top-ups. Define the credit packs available for purchase.',
			'brevo-campaign-generator'
		) . '</p>';
	}

	/**
	 * Render the Defaults section description.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_defaults_section_description(): void {
		echo '<p>' . esc_html__(
			'Set default values used when creating new campaigns. These can be overridden on a per-campaign basis.',
			'brevo-campaign-generator'
		) . '</p>';
	}

	// ─── Field Rendering Callbacks ──────────────────────────────────────

	/**
	 * Render an API key field with masked value and test button.
	 *
	 * @since  1.0.0
	 * @param  array $args Field arguments.
	 * @return void
	 */
	public function render_api_key_field( array $args ): void {
		$option_name = $args['option_name'];
		$service     = $args['service'];
		$value       = get_option( $option_name, '' );
		$masked      = $this->mask_api_key( $value );
		$description = $args['description'] ?? '';

		?>
		<div class="bcg-api-key-wrapper">
			<input
				type="password"
				id="<?php echo esc_attr( $option_name ); ?>"
				name="<?php echo esc_attr( $option_name ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				class="regular-text bcg-api-key-input"
				autocomplete="off"
				spellcheck="false"
			/>
			<button
				type="button"
				class="button bcg-test-connection"
				data-service="<?php echo esc_attr( $service ); ?>"
				data-option="<?php echo esc_attr( $option_name ); ?>"
			>
				<?php esc_html_e( 'Test Connection', 'brevo-campaign-generator' ); ?>
			</button>
			<span class="bcg-test-result" data-service="<?php echo esc_attr( $service ); ?>"></span>
		</div>
		<?php if ( $value ) : ?>
			<p class="bcg-masked-key">
				<?php
				printf(
					/* translators: %s: masked API key */
					esc_html__( 'Stored key: %s', 'brevo-campaign-generator' ),
					'<code>' . esc_html( $masked ) . '</code>'
				);
				?>
			</p>
		<?php endif; ?>
		<?php if ( $description ) : ?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render the OpenAI model select field.
	 *
	 * @since  1.0.0
	 * @param  array $args Field arguments.
	 * @return void
	 */
	public function render_openai_model_field( array $args ): void {
		$value  = get_option( 'bcg_openai_model', 'gpt-4o' );
		$models = $this->get_openai_models();

		?>
		<select id="bcg_openai_model" name="bcg_openai_model">
			<?php foreach ( $models as $model_id => $label ) : ?>
				<option value="<?php echo esc_attr( $model_id ); ?>" <?php selected( $value, $model_id ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'Select the OpenAI model used for text generation (headlines, descriptions, subject lines).', 'brevo-campaign-generator' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the Gemini model select field.
	 *
	 * @since  1.0.0
	 * @param  array $args Field arguments.
	 * @return void
	 */
	public function render_gemini_model_field( array $args ): void {
		$value  = get_option( 'bcg_gemini_model', 'gemini-2.0-flash' );
		$models = $this->get_gemini_models();

		?>
		<select id="bcg_gemini_model" name="bcg_gemini_model">
			<?php foreach ( $models as $model_id => $label ) : ?>
				<option value="<?php echo esc_attr( $model_id ); ?>" <?php selected( $value, $model_id ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'Select the Gemini model used for image generation.', 'brevo-campaign-generator' ); ?>
		</p>
		<?php
	}

	/**
	 * Render a generic text input field.
	 *
	 * @since  1.0.0
	 * @param  array $args Field arguments.
	 * @return void
	 */
	public function render_text_field( array $args ): void {
		$option_name = $args['option_name'];
		$value       = get_option( $option_name, '' );
		$type        = $args['type'] ?? 'text';
		$css_class   = $args['class'] ?? 'regular-text';
		$description = $args['description'] ?? '';

		?>
		<input
			type="<?php echo esc_attr( $type ); ?>"
			id="<?php echo esc_attr( $option_name ); ?>"
			name="<?php echo esc_attr( $option_name ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="<?php echo esc_attr( $css_class ); ?>"
		/>
		<?php if ( $description ) : ?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render a number input field.
	 *
	 * @since  1.0.0
	 * @param  array $args Field arguments.
	 * @return void
	 */
	public function render_number_field( array $args ): void {
		$option_name = $args['option_name'];
		$value       = get_option( $option_name, '' );
		$min         = $args['min'] ?? 0;
		$max         = $args['max'] ?? 9999;
		$suffix      = $args['suffix'] ?? '';
		$description = $args['description'] ?? '';

		?>
		<input
			type="number"
			id="<?php echo esc_attr( $option_name ); ?>"
			name="<?php echo esc_attr( $option_name ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="small-text"
			min="<?php echo esc_attr( $min ); ?>"
			max="<?php echo esc_attr( $max ); ?>"
			step="1"
		/>
		<?php if ( $suffix ) : ?>
			<span class="bcg-field-suffix"><?php echo esc_html( $suffix ); ?></span>
		<?php endif; ?>
		<?php if ( $description ) : ?>
			<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render a checkbox field.
	 *
	 * @since  1.0.0
	 * @param  array $args Field arguments.
	 * @return void
	 */
	public function render_checkbox_field( array $args ): void {
		$option_name = $args['option_name'];
		$value       = get_option( $option_name, 'no' );
		$label       = $args['label'] ?? '';

		?>
		<label for="<?php echo esc_attr( $option_name ); ?>">
			<input
				type="checkbox"
				id="<?php echo esc_attr( $option_name ); ?>"
				name="<?php echo esc_attr( $option_name ); ?>"
				value="yes"
				<?php checked( $value, 'yes' ); ?>
			/>
			<?php echo esc_html( $label ); ?>
		</label>
		<?php
	}

	/**
	 * Render the Brevo mailing list select field.
	 *
	 * The dropdown is populated via AJAX when the page loads (if a Brevo API
	 * key is configured). A manual refresh button is also provided.
	 *
	 * @since  1.0.0
	 * @param  array $args Field arguments.
	 * @return void
	 */
	public function render_brevo_list_field( array $args ): void {
		$option_name = $args['option_name'];
		$value       = get_option( $option_name, '' );
		$has_api_key = ! empty( get_option( 'bcg_brevo_api_key', '' ) );

		?>
		<div class="bcg-brevo-list-wrapper">
			<select
				id="<?php echo esc_attr( $option_name ); ?>"
				name="<?php echo esc_attr( $option_name ); ?>"
				class="bcg-brevo-list-select"
				data-current="<?php echo esc_attr( $value ); ?>"
			>
				<option value="">
					<?php esc_html_e( '-- Select a list --', 'brevo-campaign-generator' ); ?>
				</option>
				<?php if ( $value ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" selected>
						<?php
						printf(
							/* translators: %s: mailing list ID */
							esc_html__( 'List ID: %s (loading...)', 'brevo-campaign-generator' ),
							esc_html( $value )
						);
						?>
					</option>
				<?php endif; ?>
			</select>
			<button type="button" class="button bcg-refresh-brevo-lists" <?php echo $has_api_key ? '' : 'disabled'; ?>>
				<?php esc_html_e( 'Refresh Lists', 'brevo-campaign-generator' ); ?>
			</button>
			<?php if ( ! $has_api_key ) : ?>
				<p class="description bcg-notice-warning">
					<?php esc_html_e( 'Please save a valid Brevo API key in the API Keys tab first.', 'brevo-campaign-generator' ); ?>
				</p>
			<?php else : ?>
				<p class="description">
					<?php esc_html_e( 'Select the default mailing list for new campaigns. Click Refresh Lists to load available lists.', 'brevo-campaign-generator' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the currency select field.
	 *
	 * @since  1.0.0
	 * @param  array $args Field arguments.
	 * @return void
	 */
	public function render_currency_field( array $args ): void {
		$value      = get_option( 'bcg_stripe_currency', 'GBP' );
		$currencies = array(
			'GBP' => __( 'GBP - British Pound Sterling', 'brevo-campaign-generator' ),
			'USD' => __( 'USD - US Dollar', 'brevo-campaign-generator' ),
			'EUR' => __( 'EUR - Euro', 'brevo-campaign-generator' ),
			'CAD' => __( 'CAD - Canadian Dollar', 'brevo-campaign-generator' ),
			'AUD' => __( 'AUD - Australian Dollar', 'brevo-campaign-generator' ),
			'PLN' => __( 'PLN - Polish Zloty', 'brevo-campaign-generator' ),
		);

		?>
		<select id="bcg_stripe_currency" name="bcg_stripe_currency">
			<?php foreach ( $currencies as $code => $label ) : ?>
				<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $value, $code ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">
			<?php esc_html_e( 'The currency used for credit pack purchases via Stripe.', 'brevo-campaign-generator' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the credit packs repeater field.
	 *
	 * Displays three credit pack rows, each with a credits amount and price
	 * input. Stored as JSON in a single option.
	 *
	 * @since  1.0.0
	 * @param  array $args Field arguments.
	 * @return void
	 */
	public function render_credit_packs_field( array $args ): void {
		$raw   = get_option( 'bcg_stripe_credit_packs', '' );
		$packs = json_decode( $raw, true );

		if ( ! is_array( $packs ) || empty( $packs ) ) {
			$packs = array(
				array( 'credits' => 100, 'price' => 5.00 ),
				array( 'credits' => 300, 'price' => 12.00 ),
				array( 'credits' => 1000, 'price' => 35.00 ),
			);
		}

		// Ensure exactly 3 packs.
		while ( count( $packs ) < 3 ) {
			$packs[] = array( 'credits' => 0, 'price' => 0 );
		}

		$currency = get_option( 'bcg_stripe_currency', 'GBP' );
		$symbol   = $this->get_currency_symbol( $currency );

		?>
		<div class="bcg-credit-packs">
			<table class="bcg-credit-packs-table widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Pack', 'brevo-campaign-generator' ); ?></th>
						<th><?php esc_html_e( 'Credits', 'brevo-campaign-generator' ); ?></th>
						<th><?php esc_html_e( 'Price', 'brevo-campaign-generator' ); ?></th>
						<th><?php esc_html_e( 'Per Credit', 'brevo-campaign-generator' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php for ( $i = 0; $i < 3; $i++ ) : ?>
						<?php
						$credits    = isset( $packs[ $i ]['credits'] ) ? (int) $packs[ $i ]['credits'] : 0;
						$price      = isset( $packs[ $i ]['price'] ) ? (float) $packs[ $i ]['price'] : 0;
						$per_credit = $credits > 0 ? $price / $credits : 0;
						?>
						<tr>
							<td>
								<?php
								printf(
									/* translators: %d: pack number */
									esc_html__( 'Pack %d', 'brevo-campaign-generator' ),
									$i + 1
								);
								?>
							</td>
							<td>
								<input
									type="number"
									name="bcg_stripe_credit_packs[<?php echo esc_attr( $i ); ?>][credits]"
									value="<?php echo esc_attr( $credits ); ?>"
									class="small-text"
									min="1"
									step="1"
								/>
							</td>
							<td>
								<span class="bcg-currency-symbol"><?php echo esc_html( $symbol ); ?></span>
								<input
									type="number"
									name="bcg_stripe_credit_packs[<?php echo esc_attr( $i ); ?>][price]"
									value="<?php echo esc_attr( number_format( $price, 2, '.', '' ) ); ?>"
									class="small-text"
									min="0.01"
									step="0.01"
								/>
							</td>
							<td class="bcg-per-credit">
								<?php echo esc_html( $symbol . number_format( $per_credit, 4 ) ); ?>
							</td>
						</tr>
					<?php endfor; ?>
				</tbody>
			</table>
			<p class="description">
				<?php esc_html_e( 'Define three credit top-up packs available for purchase. Each pack specifies the number of credits and the price.', 'brevo-campaign-generator' ); ?>
			</p>
		</div>
		<?php
	}

	// ─── Sanitisation Callbacks ─────────────────────────────────────────

	/**
	 * Sanitise an API key value.
	 *
	 * Trims whitespace and removes any non-printable characters.
	 *
	 * @since  1.0.0
	 * @param  string $value The raw input value.
	 * @return string Sanitised API key.
	 */
	public function sanitize_api_key( string $value ): string {
		$value = trim( $value );

		// Remove non-printable characters but keep typical API key chars.
		$value = preg_replace( '/[^\x20-\x7E]/', '', $value );

		return sanitize_text_field( $value );
	}

	/**
	 * Sanitise OpenAI model selection.
	 *
	 * @since  1.0.0
	 * @param  string $value The raw input value.
	 * @return string Sanitised model ID.
	 */
	public function sanitize_openai_model( string $value ): string {
		$valid = array_keys( $this->get_openai_models() );

		return in_array( $value, $valid, true ) ? $value : 'gpt-4o';
	}

	/**
	 * Sanitise Gemini model selection.
	 *
	 * @since  1.0.0
	 * @param  string $value The raw input value.
	 * @return string Sanitised model ID.
	 */
	public function sanitize_gemini_model( string $value ): string {
		$valid = array_keys( $this->get_gemini_models() );

		return in_array( $value, $valid, true ) ? $value : 'gemini-2.0-flash';
	}

	/**
	 * Sanitise a decimal number string.
	 *
	 * @since  1.0.0
	 * @param  string $value The raw input value.
	 * @return string Sanitised decimal string.
	 */
	public function sanitize_decimal( string $value ): string {
		$value = (float) $value;

		if ( $value <= 0 ) {
			return '0.05';
		}

		return number_format( $value, 4, '.', '' );
	}

	/**
	 * Sanitise currency code.
	 *
	 * @since  1.0.0
	 * @param  string $value The raw input value.
	 * @return string Sanitised 3-letter currency code.
	 */
	public function sanitize_currency( string $value ): string {
		$value = strtoupper( sanitize_text_field( $value ) );
		$valid = array( 'GBP', 'USD', 'EUR', 'CAD', 'AUD', 'PLN' );

		return in_array( $value, $valid, true ) ? $value : 'GBP';
	}

	/**
	 * Sanitise credit packs.
	 *
	 * Accepts either a JSON string or an array from the form submission,
	 * validates each pack, and returns a JSON-encoded string.
	 *
	 * @since  1.0.0
	 * @param  string|array $value The raw input value.
	 * @return string JSON-encoded credit packs.
	 */
	public function sanitize_credit_packs( string|array $value ): string {
		if ( is_string( $value ) ) {
			$packs = json_decode( $value, true );
		} else {
			$packs = $value;
		}

		if ( ! is_array( $packs ) ) {
			$packs = array();
		}

		$sanitised = array();

		for ( $i = 0; $i < 3; $i++ ) {
			$credits = isset( $packs[ $i ]['credits'] ) ? absint( $packs[ $i ]['credits'] ) : 0;
			$price   = isset( $packs[ $i ]['price'] ) ? (float) $packs[ $i ]['price'] : 0;

			$sanitised[] = array(
				'credits' => max( 1, $credits ),
				'price'   => max( 0.01, round( $price, 2 ) ),
			);
		}

		return wp_json_encode( $sanitised );
	}

	/**
	 * Sanitise products per campaign value.
	 *
	 * @since  1.0.0
	 * @param  mixed $value The raw input value.
	 * @return int Clamped between 1 and 10.
	 */
	public function sanitize_products_per_campaign( mixed $value ): int {
		$value = absint( $value );

		return max( 1, min( 10, $value ) );
	}

	/**
	 * Sanitise coupon discount value.
	 *
	 * @since  1.0.0
	 * @param  mixed $value The raw input value.
	 * @return float Clamped between 1 and 100.
	 */
	public function sanitize_coupon_discount( mixed $value ): float {
		$value = (float) $value;

		return max( 1, min( 100, $value ) );
	}

	/**
	 * Sanitise a checkbox value.
	 *
	 * @since  1.0.0
	 * @param  mixed $value The raw input value.
	 * @return string 'yes' or 'no'.
	 */
	public function sanitize_checkbox( mixed $value ): string {
		return 'yes' === $value ? 'yes' : 'no';
	}

	// ─── AJAX Handlers ──────────────────────────────────────────────────

	/**
	 * Handle API key connection test via AJAX.
	 *
	 * Verifies the nonce and capability, determines which service to test,
	 * performs a lightweight API call, and returns success or failure.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_test_api_key(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) )
			);
		}

		$service = isset( $_POST['service'] ) ? sanitize_text_field( wp_unslash( $_POST['service'] ) ) : '';

		if ( empty( $service ) ) {
			wp_send_json_error(
				array( 'message' => __( 'No service specified.', 'brevo-campaign-generator' ) )
			);
		}

		$result = match ( $service ) {
			'openai'        => $this->test_openai_connection(),
			'gemini'        => $this->test_gemini_connection(),
			'brevo'         => $this->test_brevo_connection(),
			'stripe_pub'    => $this->test_stripe_pub_connection(),
			'stripe_secret' => $this->test_stripe_secret_connection(),
			default         => new \WP_Error( 'invalid_service', __( 'Unknown service.', 'brevo-campaign-generator' ) ),
		};

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array( 'message' => $result->get_error_message() )
			);
		}

		wp_send_json_success( $result );
	}

	/**
	 * Handle fetching Brevo mailing lists via AJAX.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_get_brevo_lists(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) )
			);
		}

		$api_key = get_option( 'bcg_brevo_api_key', '' );

		if ( empty( $api_key ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Brevo API key is not configured.', 'brevo-campaign-generator' ) )
			);
		}

		$response = wp_remote_get(
			'https://api.brevo.com/v3/contacts/lists?limit=50&offset=0',
			array(
				'headers' => array(
					'api-key'      => $api_key,
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error(
				array( 'message' => $response->get_error_message() )
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code ) {
			$error_msg = isset( $body['message'] ) ? $body['message'] : __( 'Failed to fetch lists from Brevo.', 'brevo-campaign-generator' );
			wp_send_json_error( array( 'message' => $error_msg ) );
		}

		$lists   = array();
		$api_key = get_option( 'bcg_brevo_api_key', '' );

		if ( isset( $body['lists'] ) && is_array( $body['lists'] ) ) {
			foreach ( $body['lists'] as $list ) {
				// Brevo deprecated totalSubscribers (always returns 0).
				// Fetch actual count per list via the contacts endpoint.
				$count     = 0;
				$count_url = 'https://api.brevo.com/v3/contacts/lists/' . absint( $list['id'] ) . '/contacts?limit=1&offset=0';
				$count_res = wp_remote_get( $count_url, array(
					'headers' => array(
						'api-key'      => $api_key,
						'Content-Type' => 'application/json',
						'Accept'       => 'application/json',
					),
					'timeout' => 10,
				) );

				if ( ! is_wp_error( $count_res ) && 200 === wp_remote_retrieve_response_code( $count_res ) ) {
					$count_body = json_decode( wp_remote_retrieve_body( $count_res ), true );
					$count      = isset( $count_body['count'] ) ? (int) $count_body['count'] : 0;
				}

				$lists[] = array(
					'id'               => $list['id'],
					'name'             => $list['name'],
					'totalSubscribers' => $count,
				);
			}
		}

		wp_send_json_success( array( 'lists' => $lists ) );
	}

	// ─── API Connection Tests ───────────────────────────────────────────

	/**
	 * Test the OpenAI API connection.
	 *
	 * Makes a lightweight call to the models endpoint to verify the key.
	 *
	 * @since  1.0.0
	 * @return array|\WP_Error Success data or error.
	 */
	private function test_openai_connection(): array|\WP_Error {
		$api_key = get_option( 'bcg_openai_api_key', '' );

		if ( empty( $api_key ) ) {
			return new \WP_Error(
				'missing_key',
				__( 'OpenAI API key is not saved. Please save your settings first.', 'brevo-campaign-generator' )
			);
		}

		$response = wp_remote_get(
			'https://api.openai.com/v1/models',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'connection_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'Connection failed: %s', 'brevo-campaign-generator' ),
					$response->get_error_message()
				)
			);
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			$msg  = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Unknown error', 'brevo-campaign-generator' );

			return new \WP_Error(
				'api_error',
				sprintf(
					/* translators: 1: HTTP status code, 2: error message */
					__( 'OpenAI returned HTTP %1$d: %2$s', 'brevo-campaign-generator' ),
					$code,
					$msg
				)
			);
		}

		return array(
			'message' => __( 'OpenAI API connection successful!', 'brevo-campaign-generator' ),
			'service' => 'openai',
		);
	}

	/**
	 * Test the Google Gemini API connection.
	 *
	 * Makes a lightweight call to list available models.
	 *
	 * @since  1.0.0
	 * @return array|\WP_Error Success data or error.
	 */
	private function test_gemini_connection(): array|\WP_Error {
		$api_key = get_option( 'bcg_gemini_api_key', '' );

		if ( empty( $api_key ) ) {
			return new \WP_Error(
				'missing_key',
				__( 'Gemini API key is not saved. Please save your settings first.', 'brevo-campaign-generator' )
			);
		}

		$response = wp_remote_get(
			'https://generativelanguage.googleapis.com/v1beta/models?key=' . $api_key,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'connection_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'Connection failed: %s', 'brevo-campaign-generator' ),
					$response->get_error_message()
				)
			);
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			$msg  = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Unknown error', 'brevo-campaign-generator' );

			return new \WP_Error(
				'api_error',
				sprintf(
					/* translators: 1: HTTP status code, 2: error message */
					__( 'Gemini returned HTTP %1$d: %2$s', 'brevo-campaign-generator' ),
					$code,
					$msg
				)
			);
		}

		return array(
			'message' => __( 'Gemini API connection successful!', 'brevo-campaign-generator' ),
			'service' => 'gemini',
		);
	}

	/**
	 * Test the Brevo API connection.
	 *
	 * Makes a call to the account endpoint to verify the key.
	 *
	 * @since  1.0.0
	 * @return array|\WP_Error Success data or error.
	 */
	private function test_brevo_connection(): array|\WP_Error {
		$api_key = get_option( 'bcg_brevo_api_key', '' );

		if ( empty( $api_key ) ) {
			return new \WP_Error(
				'missing_key',
				__( 'Brevo API key is not saved. Please save your settings first.', 'brevo-campaign-generator' )
			);
		}

		$response = wp_remote_get(
			'https://api.brevo.com/v3/account',
			array(
				'headers' => array(
					'api-key'      => $api_key,
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'connection_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'Connection failed: %s', 'brevo-campaign-generator' ),
					$response->get_error_message()
				)
			);
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			$msg  = isset( $body['message'] ) ? $body['message'] : __( 'Unknown error', 'brevo-campaign-generator' );

			return new \WP_Error(
				'api_error',
				sprintf(
					/* translators: 1: HTTP status code, 2: error message */
					__( 'Brevo returned HTTP %1$d: %2$s', 'brevo-campaign-generator' ),
					$code,
					$msg
				)
			);
		}

		$body    = json_decode( wp_remote_retrieve_body( $response ), true );
		$company = isset( $body['companyName'] ) ? $body['companyName'] : '';

		return array(
			'message' => sprintf(
				/* translators: %s: Brevo company name */
				__( 'Brevo API connection successful! Account: %s', 'brevo-campaign-generator' ),
				$company
			),
			'service' => 'brevo',
		);
	}

	/**
	 * Test the Stripe publishable key.
	 *
	 * Validates format only since publishable keys cannot make server-side
	 * API calls. Checks that it starts with pk_test_ or pk_live_.
	 *
	 * @since  1.0.0
	 * @return array|\WP_Error Success data or error.
	 */
	private function test_stripe_pub_connection(): array|\WP_Error {
		$key = get_option( 'bcg_stripe_publishable_key', '' );

		if ( empty( $key ) ) {
			return new \WP_Error(
				'missing_key',
				__( 'Stripe publishable key is not saved. Please save your settings first.', 'brevo-campaign-generator' )
			);
		}

		if ( ! str_starts_with( $key, 'pk_test_' ) && ! str_starts_with( $key, 'pk_live_' ) ) {
			return new \WP_Error(
				'invalid_format',
				__( 'Invalid format. Stripe publishable keys should start with pk_test_ or pk_live_.', 'brevo-campaign-generator' )
			);
		}

		return array(
			'message' => __( 'Stripe publishable key format is valid!', 'brevo-campaign-generator' ),
			'service' => 'stripe_pub',
		);
	}

	/**
	 * Test the Stripe secret key.
	 *
	 * Makes a lightweight call to the Stripe balance endpoint.
	 *
	 * @since  1.0.0
	 * @return array|\WP_Error Success data or error.
	 */
	private function test_stripe_secret_connection(): array|\WP_Error {
		$key = get_option( 'bcg_stripe_secret_key', '' );

		if ( empty( $key ) ) {
			return new \WP_Error(
				'missing_key',
				__( 'Stripe secret key is not saved. Please save your settings first.', 'brevo-campaign-generator' )
			);
		}

		if ( ! str_starts_with( $key, 'sk_test_' ) && ! str_starts_with( $key, 'sk_live_' ) ) {
			return new \WP_Error(
				'invalid_format',
				__( 'Invalid format. Stripe secret keys should start with sk_test_ or sk_live_.', 'brevo-campaign-generator' )
			);
		}

		$response = wp_remote_get(
			'https://api.stripe.com/v1/balance',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $key,
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error(
				'connection_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'Connection failed: %s', 'brevo-campaign-generator' ),
					$response->get_error_message()
				)
			);
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			$msg  = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Unknown error', 'brevo-campaign-generator' );

			return new \WP_Error(
				'api_error',
				sprintf(
					/* translators: 1: HTTP status code, 2: error message */
					__( 'Stripe returned HTTP %1$d: %2$s', 'brevo-campaign-generator' ),
					$code,
					$msg
				)
			);
		}

		return array(
			'message' => __( 'Stripe secret key connection successful!', 'brevo-campaign-generator' ),
			'service' => 'stripe_secret',
		);
	}

	// ─── Helper Methods ─────────────────────────────────────────────────

	/**
	 * Get available OpenAI models.
	 *
	 * @since  1.0.0
	 * @return array<string, string> Model ID => Label.
	 */
	public function get_openai_models(): array {
		return array(
			'gpt-4o'      => __( 'GPT-4o (recommended, best quality)', 'brevo-campaign-generator' ),
			'gpt-4o-mini' => __( 'GPT-4o Mini (faster, lower cost)', 'brevo-campaign-generator' ),
			'gpt-4-turbo' => __( 'GPT-4 Turbo', 'brevo-campaign-generator' ),
		);
	}

	/**
	 * Get available Gemini models.
	 *
	 * @since  1.0.0
	 * @return array<string, string> Model ID => Label.
	 */
	public function get_gemini_models(): array {
		return array(
			'gemini-2.0-flash'     => __( 'Gemini 2.0 Flash (recommended, fast, cost-efficient)', 'brevo-campaign-generator' ),
			'gemini-2.5-flash-preview-05-20' => __( 'Gemini 2.5 Flash (preview, best quality)', 'brevo-campaign-generator' ),
			'gemini-2.5-pro-preview-05-06'   => __( 'Gemini 2.5 Pro (preview, highest quality)', 'brevo-campaign-generator' ),
		);
	}

	/**
	 * Mask an API key for display.
	 *
	 * Shows the first 4 and last 4 characters with asterisks in between.
	 *
	 * @since  1.0.0
	 * @param  string $key The full API key.
	 * @return string The masked key.
	 */
	private function mask_api_key( string $key ): string {
		if ( empty( $key ) ) {
			return '';
		}

		$length = strlen( $key );

		if ( $length <= 8 ) {
			return str_repeat( '*', $length );
		}

		return substr( $key, 0, 4 ) . str_repeat( '*', $length - 8 ) . substr( $key, -4 );
	}

	/**
	 * Get the currency symbol for a given currency code.
	 *
	 * @since  1.0.0
	 * @param  string $code The 3-letter currency code.
	 * @return string The currency symbol.
	 */
	public function get_currency_symbol( string $code ): string {
		$symbols = array(
			'GBP' => "\xC2\xA3",
			'USD' => '$',
			'EUR' => "\xE2\x82\xAC",
			'CAD' => 'C$',
			'AUD' => 'A$',
			'PLN' => "z\xC5\x82",
		);

		return $symbols[ $code ] ?? $code;
	}

	/**
	 * Get the pricing reference data for the static table.
	 *
	 * @since  1.0.0
	 * @return array Pricing reference rows.
	 */
	public function get_pricing_reference(): array {
		return array(
			array(
				'service' => 'OpenAI',
				'model'   => 'GPT-4o',
				'task'    => __( 'Per campaign copy (approx 2K tokens)', 'brevo-campaign-generator' ),
				'cost'    => '~$0.01-$0.05',
			),
			array(
				'service' => 'OpenAI',
				'model'   => 'GPT-4o Mini',
				'task'    => __( 'Per campaign copy', 'brevo-campaign-generator' ),
				'cost'    => '~$0.001-$0.005',
			),
			array(
				'service' => 'Gemini',
				'model'   => '2.5 Pro',
				'task'    => __( 'Per image generation', 'brevo-campaign-generator' ),
				'cost'    => '~$0.01-$0.04',
			),
			array(
				'service' => 'Gemini',
				'model'   => '2.0 Flash',
				'task'    => __( 'Per image generation', 'brevo-campaign-generator' ),
				'cost'    => '~$0.001-$0.01',
			),
		);
	}
}
