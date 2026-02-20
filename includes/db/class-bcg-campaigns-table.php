<?php
/**
 * Campaigns database table definition.
 *
 * Provides the table name and CREATE TABLE SQL schema for the bcg_campaigns
 * and bcg_campaign_products tables. Used by BCG_Activator during plugin
 * activation to create or update the tables via dbDelta().
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Campaigns_Table
 *
 * Database schema definition for the campaigns and campaign_products tables.
 *
 * @since 1.0.0
 */
class BCG_Campaigns_Table {

	/**
	 * Base table name for campaigns (without WordPress prefix).
	 *
	 * @var string
	 */
	const TABLE_NAME = 'bcg_campaigns';

	/**
	 * Base table name for campaign products (without WordPress prefix).
	 *
	 * @var string
	 */
	const PRODUCTS_TABLE_NAME = 'bcg_campaign_products';

	/**
	 * Get the full campaigns table name including the WordPress prefix.
	 *
	 * @since  1.0.0
	 * @return string The prefixed table name.
	 */
	public static function get_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Get the full campaign products table name including the WordPress prefix.
	 *
	 * @since  1.0.0
	 * @return string The prefixed table name.
	 */
	public static function get_products_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . self::PRODUCTS_TABLE_NAME;
	}

	/**
	 * Get the CREATE TABLE SQL for the campaigns table.
	 *
	 * Returns SQL compatible with dbDelta() which requires:
	 * - Each field on its own line
	 * - Two spaces between PRIMARY KEY and the opening parenthesis
	 * - KEY instead of INDEX for indexes
	 *
	 * @since  1.0.0
	 * @return string The CREATE TABLE SQL statement.
	 */
	public static function get_schema(): string {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table_name} (
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
			template_html LONGTEXT,
			template_settings LONGTEXT,
			mailing_list_id VARCHAR(100),
			scheduled_at DATETIME NULL,
			sent_at DATETIME NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) {$charset_collate};";
	}

	/**
	 * Get the CREATE TABLE SQL for the campaign products table.
	 *
	 * @since  1.0.0
	 * @return string The CREATE TABLE SQL statement.
	 */
	public static function get_products_schema(): string {
		global $wpdb;

		$table_name      = self::get_products_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table_name} (
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
		) {$charset_collate};";
	}
}
