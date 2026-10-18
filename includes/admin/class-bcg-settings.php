<?php
/**
 * Settings handler.
 *
 * Registers all plugin settings using the WordPress Settings API, organises
 * them into four tabs (API Keys, AI Models, Brevo, Defaults), and
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
		$this->register_defaults_settings();
		$this->register_ai_trainer_settings();
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
			__( 'API Keys', 'brevo-campaign-generator' ),
			array( $this, 'render_api_keys_section_description' ),
			$page
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

		// Verified Sender (replaces separate name/email fields).
		register_setting( $page, 'bcg_brevo_sender', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_brevo_sender' ),
			'default'           => '',
		) );

		add_settings_field(
			'bcg_brevo_sender',
			__( 'Default Sender', 'brevo-campaign-generator' ),
			array( $this, 'render_brevo_sender_field' ),
			$page,
			$section,
			array(
				'label_for'   => 'bcg_brevo_sender',
				'option_name' => 'bcg_brevo_sender',
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
	}

	// ─── AI Trainer Tab ─────────────────────────────────────────────────

	/**
	 * Register AI Trainer tab settings.
	 *
	 * @since  1.6.0
	 * @return void
	 */
	private function register_ai_trainer_settings(): void {
		$section = 'bcg_ai_trainer_section';
		$page    = 'bcg_settings_ai_trainer';

		add_settings_section(
			$section,
			__( 'AI Context for Generation', 'brevo-campaign-generator' ),
			function() {
				echo '<p class="bcg-section-desc">' . esc_html__( 'Provide background information about your store and products. The AI will use this context when generating campaign copy, headlines, and descriptions.', 'brevo-campaign-generator' ) . '</p>';
			},
			$page
		);

		add_settings_field(
			'bcg_ai_trainer_company',
			__( 'About Your Store', 'brevo-campaign-generator' ),
			array( $this, 'render_ai_trainer_company_field' ),
			$page,
			$section
		);

		add_settings_field(
			'bcg_ai_trainer_products',
			__( 'About Your Products', 'brevo-campaign-generator' ),
			array( $this, 'render_ai_trainer_products_field' ),
			$page,
			$section
		);

		register_setting( $page, 'bcg_ai_trainer_company', array(
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		) );

		register_setting( $page, 'bcg_ai_trainer_products', array(
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		) );
	}

	// ─── Section Description Callbacks ──────────────────────────────────

	/**
	 * Render the API Keys section description.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_api_keys_section_description(): void {
		echo '<p>' . esc_html__( 'Only the Brevo key is stored here. AI text and images run through the Red Frog AI portal; its keys are sealed on the server with "wp ats secret set gateway_key_email" and billed from the shared AI Credits page.', 'brevo-campaign-generator' ) . '</p>';
	}

	/**
	 * Render the AI Models section description.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render_ai_models_section_description(): void {
		echo '<p>' . esc_html__(
			'Select which AI models to use for text and image generation. Higher-quality models cost more per operation; usage is charged to the AI Credits wallet.',
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
		$value       = bcg_get_api_key( $option_name );
		$masked      = $this->mask_api_key( $value );
		$description = $args['description'] ?? '';

		// Check if key is defined via wp-config.php constant.
		static $constant_map = array(
			'bcg_brevo_api_key' => 'BCG_BREVO_API_KEY',
		);
		$constant         = $constant_map[ $option_name ] ?? null;
		$is_from_constant = $constant && defined( $constant );

		if ( $is_from_constant ) {
			?>
			<div class="bcg-api-key-wrapper">
				<div class="bcg-api-key-from-constant">
					<span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;color:var(--bcg-success,#2ecc71);margin-right:6px;">lock</span>
					<?php
					printf(
						/* translators: %s: constant name */
						esc_html__( 'Defined in wp-config.php via %s — stored key: %s', 'brevo-campaign-generator' ),
						'<code>' . esc_html( $constant ) . '</code>',
						'<code>' . esc_html( $masked ) . '</code>'
					);
					?>
				</div>
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
			<?php if ( $description ) : ?>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
			<?php
			return;
		}

		// Normal field rendering (not from constant).
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
		<select id="bcg_openai_model" name="bcg_openai_model" class="bcg-select-styled">
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
		<label class="bcg-settings-toggle" for="<?php echo esc_attr( $option_name ); ?>">
			<span class="bcg-toggle-switch">
				<input
					type="checkbox"
					id="<?php echo esc_attr( $option_name ); ?>"
					name="<?php echo esc_attr( $option_name ); ?>"
					value="yes"
					<?php checked( $value, 'yes' ); ?>
				/>
				<span class="bcg-toggle-thumb"></span>
			</span>
			<?php if ( $label ) : ?>
				<span class="bcg-settings-toggle-desc"><?php echo esc_html( $label ); ?></span>
			<?php endif; ?>
		</label>
		<?php
	}

	/**
	 * Render the Brevo verified sender dropdown field.
	 *
	 * Populated via AJAX from the Brevo Senders API. Stores the selected
	 * sender as JSON {"name":"...","email":"..."} in a single option.
	 *
	 * @since  1.3.2
	 * @param  array $args Field arguments.
	 * @return void
	 */
	public function render_brevo_sender_field( array $args ): void {
		$option_name = $args['option_name'];
		$raw_value   = get_option( $option_name, '' );
		$sender_data = is_string( $raw_value ) ? json_decode( $raw_value, true ) : null;
		$has_api_key = ! empty( bcg_get_api_key( 'bcg_brevo_api_key' ) );

		$current_name  = ( is_array( $sender_data ) && ! empty( $sender_data['name'] ) ) ? $sender_data['name'] : '';
		$current_email = ( is_array( $sender_data ) && ! empty( $sender_data['email'] ) ) ? $sender_data['email'] : '';

		// Build current value string for the hidden input.
		$current_json = '';
		if ( $current_name && $current_email ) {
			$current_json = wp_json_encode( array( 'name' => $current_name, 'email' => $current_email ) );
		}

		?>
		<div class="bcg-brevo-sender-wrapper">
			<select
				id="bcg_brevo_sender_select"
				class="bcg-brevo-sender-select bcg-select-styled regular-text"
				data-current-name="<?php echo esc_attr( $current_name ); ?>"
				data-current-email="<?php echo esc_attr( $current_email ); ?>"
			>
				<option value="">
					<?php esc_html_e( '-- Select a verified sender --', 'brevo-campaign-generator' ); ?>
				</option>
				<?php if ( $current_name && $current_email ) : ?>
					<option value="<?php echo esc_attr( $current_json ); ?>" selected>
						<?php echo esc_html( $current_name . ' <' . $current_email . '>' ); ?>
					</option>
				<?php endif; ?>
			</select>
			<input
				type="hidden"
				id="<?php echo esc_attr( $option_name ); ?>"
				name="<?php echo esc_attr( $option_name ); ?>"
				value="<?php echo esc_attr( $current_json ); ?>"
			/>
			<button type="button" class="button bcg-refresh-brevo-senders" <?php echo $has_api_key ? '' : 'disabled'; ?>>
				<?php esc_html_e( 'Refresh Senders', 'brevo-campaign-generator' ); ?>
			</button>
			<?php if ( ! $has_api_key ) : ?>
				<p class="description bcg-notice-warning">
					<?php esc_html_e( 'Please save a valid Brevo API key in the API Keys tab first.', 'brevo-campaign-generator' ); ?>
				</p>
			<?php else : ?>
				<p class="description">
					<?php esc_html_e( 'Select a verified sender from your Brevo account. Only senders verified in Brevo will appear. Click Refresh Senders to reload the list.', 'brevo-campaign-generator' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<script>
		jQuery(function($) {
			var $wrapper = $('.bcg-brevo-sender-wrapper');
			var $select  = $wrapper.find('.bcg-brevo-sender-select');
			var $hidden  = $wrapper.find('#<?php echo esc_js( $option_name ); ?>');

			function loadSenders() {
				var currentName  = $select.data('current-name') || '';
				var currentEmail = $select.data('current-email') || '';

				$select.prop('disabled', true);
				$select.html('<option value=""><?php echo esc_js( __( 'Loading senders...', 'brevo-campaign-generator' ) ); ?></option>');

				$.post(ajaxurl, {
					action: 'bcg_get_brevo_senders',
					nonce: '<?php echo esc_js( wp_create_nonce( 'bcg_nonce' ) ); ?>'
				}, function(response) {
					$select.empty().append('<option value=""><?php echo esc_js( __( '-- Select a verified sender --', 'brevo-campaign-generator' ) ); ?></option>');

					if (response.success && response.data.senders) {
						$.each(response.data.senders, function(i, sender) {
							var val = JSON.stringify({ id: sender.id, name: sender.name, email: sender.email });
							var label = sender.name + ' <' + sender.email + '>';
							var selected = (sender.email === currentEmail) ? ' selected' : '';
							$select.append('<option value=\'' + val.replace(/'/g, '&#39;') + '\'' + selected + '>' + $('<span>').text(label).html() + '</option>');
						});

						// Update hidden input if something is selected.
						if ($select.val()) {
							$hidden.val($select.val());
						}
					} else {
						$select.append('<option value=""><?php echo esc_js( __( 'Failed to load senders', 'brevo-campaign-generator' ) ); ?></option>');
					}

					// Rebuild custom dropdown UI to show new senders.
					if ( typeof window.bcgRebuildCustomSelect === 'function' ) {
						window.bcgRebuildCustomSelect( $select );
					}

					$select.prop('disabled', false);
				}).fail(function() {
					$select.html('<option value=""><?php echo esc_js( __( 'Error loading senders', 'brevo-campaign-generator' ) ); ?></option>');
					$select.prop('disabled', false);
				});
			}

			// Sync hidden input on change.
			$select.on('change', function() {
				$hidden.val($(this).val());
			});

			// Refresh button.
			$wrapper.find('.bcg-refresh-brevo-senders').on('click', function() {
				loadSenders();
			});

			// Auto-load if API key is present.
			<?php if ( $has_api_key ) : ?>
			loadSenders();
			<?php endif; ?>
		});
		</script>
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
		$has_api_key = ! empty( bcg_get_api_key( 'bcg_brevo_api_key' ) );

		?>
		<div class="bcg-brevo-list-wrapper">
			<select
				id="<?php echo esc_attr( $option_name ); ?>"
				name="<?php echo esc_attr( $option_name ); ?>"
				class="bcg-brevo-list-select bcg-select-styled"
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
	 * Render the AI trainer company context field.
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function render_ai_trainer_company_field(): void {
		$value = get_option( 'bcg_ai_trainer_company', '' );
		echo '<textarea name="bcg_ai_trainer_company" id="bcg_ai_trainer_company" rows="6" class="bcg-textarea large-text" placeholder="' . esc_attr__( 'E.g. We are a UK-based diamond tools supplier specialising in professional cutting and grinding equipment for the construction industry. Our customers are tradespeople and contractors...', 'brevo-campaign-generator' ) . '">' . esc_textarea( $value ) . '</textarea>';
		echo '<p class="bcg-field-desc">' . esc_html__( 'Describe your store, brand voice, target audience, and any unique selling points. The more detail you provide, the more accurate and on-brand the AI copy will be.', 'brevo-campaign-generator' ) . '</p>';
	}

	/**
	 * Render the AI trainer products context field.
	 *
	 * @since  1.6.0
	 * @return void
	 */
	public function render_ai_trainer_products_field(): void {
		$value = get_option( 'bcg_ai_trainer_products', '' );
		echo '<textarea name="bcg_ai_trainer_products" id="bcg_ai_trainer_products" rows="6" class="bcg-textarea large-text" placeholder="' . esc_attr__( 'E.g. Our best-selling products include diamond core drill bits, angle grinder discs, and SDS drill bits. Key features include long life, professional grade quality, and competitive pricing...', 'brevo-campaign-generator' ) . '">' . esc_textarea( $value ) . '</textarea>';
		echo '<p class="bcg-field-desc">' . esc_html__( 'Describe your product range, key features, bestsellers, price points, and anything else the AI should know when writing product copy.', 'brevo-campaign-generator' ) . '</p>';
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

	/**
	 * Sanitise the Brevo sender JSON value.
	 *
	 * Expects a JSON string with "name" and "email" keys.
	 *
	 * @since  1.3.2
	 * @param  string $value The raw JSON string from the hidden field.
	 * @return string Sanitised JSON string.
	 */
	public function sanitize_brevo_sender( string $value ): string {
		if ( empty( $value ) ) {
			return '';
		}

		$data = json_decode( $value, true );

		if ( ! is_array( $data ) || empty( $data['email'] ) ) {
			return '';
		}

		$sanitised = array(
			'id'    => isset( $data['id'] ) ? absint( $data['id'] ) : 0,
			'name'  => sanitize_text_field( $data['name'] ?? '' ),
			'email' => sanitize_email( $data['email'] ),
		);

		if ( empty( $sanitised['email'] ) ) {
			return '';
		}

		return wp_json_encode( $sanitised );
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
			'openai' => $this->test_openai_connection(),
			'brevo'  => $this->test_brevo_connection(),
			default  => new \WP_Error( 'invalid_service', __( 'Unknown service.', 'brevo-campaign-generator' ) ),
		};

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
					'code'    => $result->get_error_code(),
				)
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

		$api_key = bcg_get_api_key( 'bcg_brevo_api_key' );

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

		$lists = array();

		if ( isset( $body['lists'] ) && is_array( $body['lists'] ) ) {
			foreach ( $body['lists'] as $list ) {
				$lists[] = array(
					'id'               => $list['id'],
					'name'             => $list['name'],
					'totalSubscribers' => (int) ( $list['totalSubscribers'] ?? 0 ),
				);
			}
		}

		wp_send_json_success( array( 'lists' => $lists ) );
	}

	// ─── API Connection Tests ───────────────────────────────────────────

	/**
	 * Ping the portal's free /models endpoint with the email key.
	 *
	 * @return array|\WP_Error ['message' => string].
	 */
	private function test_openai_connection(): array|\WP_Error {
		$client = new BCG_OpenAI( '', 'ats:email:test' );
		$result = $client->test_connection();

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'message' => __( 'Connected to the Red Frog AI portal.', 'brevo-campaign-generator' ),
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
		$api_key = bcg_get_api_key( 'bcg_brevo_api_key' );

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
	 * Approximate cost per task in pounds, as charged by the gateway.
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
				'cost'    => '~£0.10-£0.40',
			),
			array(
				'service' => 'OpenAI',
				'model'   => 'GPT-4o Mini',
				'task'    => __( 'Per campaign copy', 'brevo-campaign-generator' ),
				'cost'    => '~£0.01-£0.04',
			),
			array(
				'service' => 'OpenAI',
				'model'   => 'gpt-image-1 (medium)',
				'task'    => __( 'Per image', 'brevo-campaign-generator' ),
				'cost'    => '~£0.30-£0.50',
			),
		);
	}
}
