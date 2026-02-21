<?php
/**
 * Plugin activator.
 *
 * Handles all tasks that must run when the plugin is activated, including
 * database table creation and setting sensible default option values.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Activator
 *
 * Fired during plugin activation via register_activation_hook().
 *
 * @since 1.0.0
 */
class BCG_Activator {

	/**
	 * Run activation routines.
	 *
	 * Creates custom database tables and stores default option values.
	 * This method is the single entry-point called by
	 * register_activation_hook().
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function activate(): void {
		self::create_tables();
		self::set_default_options();
		self::create_upload_directory();
		self::maybe_upgrade();

		// Store the version for future upgrade comparisons.
		update_option( 'bcg_version', BCG_VERSION );

		// Flush rewrite rules so any custom endpoints become available.
		flush_rewrite_rules();
	}

	/**
	 * Create all plugin database tables using dbDelta.
	 *
	 * Uses the WordPress dbDelta() function which handles both initial
	 * creation and future schema migrations. The four tables created are:
	 *
	 * - bcg_campaigns         — stores campaign data
	 * - bcg_campaign_products — products associated with a campaign
	 * - bcg_credits           — per-user credit balances
	 * - bcg_transactions      — transaction/audit log
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$prefix          = $wpdb->prefix;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// ── bcg_campaigns ────────────────────────────────────────────
		$sql_campaigns = "CREATE TABLE {$prefix}bcg_campaigns (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title VARCHAR(255) NOT NULL,
			status ENUM('draft','ready','sent','scheduled') DEFAULT 'draft',
			brevo_campaign_id BIGINT UNSIGNED NULL,
			subject VARCHAR(255),
			preview_text VARCHAR(255),
			main_image_url TEXT,
			main_headline TEXT,
			main_description TEXT,
			coupon_code VARCHAR(100),
			coupon_discount DECIMAL(5,2),
			coupon_type ENUM('percent','fixed_cart') DEFAULT 'percent',
			template_slug VARCHAR(50) DEFAULT 'classic',
			template_html LONGTEXT,
			template_settings LONGTEXT,
			mailing_list_id VARCHAR(100),
			scheduled_at DATETIME NULL,
			sent_at DATETIME NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;";

		dbDelta( $sql_campaigns );

		// ── bcg_campaign_products ────────────────────────────────────
		$sql_campaign_products = "CREATE TABLE {$prefix}bcg_campaign_products (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			campaign_id BIGINT UNSIGNED NOT NULL,
			product_id BIGINT UNSIGNED NOT NULL,
			sort_order INT DEFAULT 0,
			ai_headline TEXT,
			ai_short_desc TEXT,
			custom_headline TEXT,
			custom_short_desc TEXT,
			generated_image_url TEXT,
			use_product_image TINYINT(1) DEFAULT 1,
			show_buy_button TINYINT(1) DEFAULT 1,
			PRIMARY KEY  (id),
			KEY idx_campaign (campaign_id)
		) $charset_collate;";

		dbDelta( $sql_campaign_products );

		// ── bcg_credits ─────────────────────────────────────────────
		$sql_credits = "CREATE TABLE {$prefix}bcg_credits (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			balance DECIMAL(10,4) DEFAULT 0.0000,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY idx_user (user_id)
		) $charset_collate;";

		dbDelta( $sql_credits );

		// ── bcg_transactions ────────────────────────────────────────
		$sql_transactions = "CREATE TABLE {$prefix}bcg_transactions (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			type ENUM('topup','usage','refund') NOT NULL,
			amount DECIMAL(10,4) NOT NULL,
			balance_after DECIMAL(10,4) NOT NULL,
			description VARCHAR(255),
			stripe_payment_intent VARCHAR(255) NULL,
			ai_service ENUM('openai','gemini-pro','gemini-flash') NULL,
			ai_task VARCHAR(100) NULL,
			tokens_used INT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY idx_user (user_id),
			KEY idx_type (type)
		) $charset_collate;";

		dbDelta( $sql_transactions );

		// ── bcg_section_templates ────────────────────────────────────
		$sql_section_templates = "CREATE TABLE {$prefix}bcg_section_templates (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			description TEXT,
			sections LONGTEXT NOT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;";

		dbDelta( $sql_section_templates );
	}

	/**
	 * Set all default plugin options.
	 *
	 * Uses add_option() so that existing values are never overwritten on
	 * re-activation. Options are grouped by their Settings tab for clarity.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function set_default_options(): void {

		// ── API Keys ────────────────────────────────────────────────
		add_option( 'bcg_openai_api_key', '' );
		add_option( 'bcg_gemini_api_key', '' );
		add_option( 'bcg_brevo_api_key', '' );
		add_option( 'bcg_stripe_publishable_key', '' );
		add_option( 'bcg_stripe_secret_key', '' );

		// ── AI Models ───────────────────────────────────────────────
		add_option( 'bcg_openai_model', 'gpt-4o' );
		add_option( 'bcg_gemini_model', 'gemini-1.5-flash' );

		// ── Credit costs per AI operation ───────────────────────────
		add_option( 'bcg_credit_cost_openai_gpt4o', 5 );
		add_option( 'bcg_credit_cost_openai_gpt4o_mini', 1 );
		add_option( 'bcg_credit_cost_gemini_pro', 10 );
		add_option( 'bcg_credit_cost_gemini_flash', 3 );

		// ── Credit value ratio ──────────────────────────────────────
		// 1 credit = GBP value.
		add_option( 'bcg_credit_value', '0.05' );

		// ── Brevo settings ──────────────────────────────────────────
		add_option( 'bcg_brevo_default_list_id', '' );
		add_option( 'bcg_brevo_sender_name', get_bloginfo( 'name' ) );
		add_option( 'bcg_brevo_sender_email', get_bloginfo( 'admin_email' ) );
		add_option( 'bcg_brevo_campaign_prefix', '[WC]' );

		// ── Stripe settings ─────────────────────────────────────────
		add_option( 'bcg_stripe_currency', 'GBP' );

		// Credit packs: each pack is an array with credits and price.
		add_option(
			'bcg_stripe_credit_packs',
			wp_json_encode(
				array(
					array(
						'credits' => 100,
						'price'   => 5.00,
					),
					array(
						'credits' => 300,
						'price'   => 12.00,
					),
					array(
						'credits' => 1000,
						'price'   => 35.00,
					),
				)
			)
		);

		// ── Default campaign settings ───────────────────────────────
		add_option( 'bcg_default_products_per_campaign', 3 );
		add_option( 'bcg_default_coupon_discount', 10 );
		add_option( 'bcg_default_coupon_expiry_days', 7 );
		add_option( 'bcg_default_auto_generate_coupon', 'yes' );

		// ── Default template settings (JSON) ────────────────────────
		$default_template_settings = array(
			'logo_url'              => '',
			'logo_width'            => 180,
			'nav_links'             => array(
				array(
					'label' => __( 'Shop', 'brevo-campaign-generator' ),
					'url'   => '',
				),
				array(
					'label' => __( 'About', 'brevo-campaign-generator' ),
					'url'   => '',
				),
			),
			'show_nav'              => true,
			'primary_color'         => '#e84040',
			'background_color'      => '#f5f5f5',
			'content_background'    => '#ffffff',
			'text_color'            => '#333333',
			'link_color'            => '#e84040',
			'button_color'          => '#e84040',
			'button_text_color'     => '#ffffff',
			'button_border_radius'  => 4,
			'font_family'           => 'Arial, sans-serif',
			'header_text'           => '',
			'footer_text'           => __( 'You received this email because you subscribed to our newsletter.', 'brevo-campaign-generator' ),
			'footer_links'          => array(
				array(
					'label' => __( 'Privacy Policy', 'brevo-campaign-generator' ),
					'url'   => '',
				),
				array(
					'label' => __( 'Unsubscribe', 'brevo-campaign-generator' ),
					'url'   => '{{unsubscribe_url}}',
				),
			),
			'max_width'             => 600,
			'show_coupon_block'     => true,
			'product_layout'        => 'stacked',
			'products_per_row'      => 1,
		);

		add_option( 'bcg_default_template_settings', wp_json_encode( $default_template_settings ) );

		// ── Error log ───────────────────────────────────────────────
		add_option( 'bcg_error_log', wp_json_encode( array() ) );
	}

	/**
	 * Run upgrade routines for schema changes between versions.
	 *
	 * Checks the stored version against the current version and applies
	 * any necessary database migrations.
	 *
	 * @since  1.1.0
	 * @return void
	 */
	public static function maybe_upgrade(): void {
		$installed_version = get_option( 'bcg_version', '0.0.0' );

		// Add template_slug column (introduced in 1.1.0).
		if ( version_compare( $installed_version, '1.1.0', '<' ) ) {
			global $wpdb;

			$table = $wpdb->prefix . 'bcg_campaigns';

			// Check if the column already exists.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$column = $wpdb->get_results(
				$wpdb->prepare(
					"SHOW COLUMNS FROM {$table} LIKE %s",
					'template_slug'
				)
			);

			if ( empty( $column ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query(
					"ALTER TABLE {$table} ADD COLUMN template_slug VARCHAR(50) DEFAULT 'classic' AFTER coupon_type"
				);
			}
		}

		// Add section builder columns (introduced in 1.5.0).
		if ( version_compare( $installed_version, '1.5.0', '<' ) ) {
			global $wpdb;

			$table = $wpdb->prefix . 'bcg_campaigns';

			// builder_type column.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$col = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'builder_type' ) );
			if ( empty( $col ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query( "ALTER TABLE {$table} ADD COLUMN builder_type ENUM('flat','sections') NOT NULL DEFAULT 'flat' AFTER template_slug" );
			}

			// sections_json column.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$col = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'sections_json' ) );
			if ( empty( $col ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query( "ALTER TABLE {$table} ADD COLUMN sections_json LONGTEXT NULL AFTER builder_type" );
			}

			// section_template_id column.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$col = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'section_template_id' ) );
			if ( empty( $col ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$wpdb->query( "ALTER TABLE {$table} ADD COLUMN section_template_id BIGINT UNSIGNED NULL AFTER sections_json" );
			}
		}

		// Ensure bcg_section_templates table exists (may be missing on sites
		// that updated without re-activating — introduced in 1.5.0).
		if ( version_compare( $installed_version, '1.5.3', '<' ) ) {
			global $wpdb;

			$charset_collate      = $wpdb->get_charset_collate();
			$section_tpl_table    = $wpdb->prefix . 'bcg_section_templates';

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$sql_section_templates = "CREATE TABLE {$section_tpl_table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				name VARCHAR(255) NOT NULL,
				description TEXT,
				sections LONGTEXT NOT NULL,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY  (id)
			) {$charset_collate};";

			dbDelta( $sql_section_templates );
		}
	}

	/**
	 * Create the uploads directory for AI-generated images.
	 *
	 * Creates wp-content/uploads/bcg/ if it does not already exist and
	 * adds an index.php file to prevent directory listing.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function create_upload_directory(): void {
		$upload_dir = wp_upload_dir();
		$bcg_dir    = $upload_dir['basedir'] . '/bcg';

		if ( ! file_exists( $bcg_dir ) ) {
			wp_mkdir_p( $bcg_dir );
		}

		// Prevent directory listing.
		$index_file = $bcg_dir . '/index.php';
		if ( ! file_exists( $index_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $index_file, '<?php // Silence is golden.' );
		}
	}
}
