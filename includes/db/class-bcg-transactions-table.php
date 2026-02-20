<?php
/**
 * Transactions database table definition.
 *
 * Provides the table name and CREATE TABLE SQL schema for the bcg_transactions
 * table. Used by BCG_Activator during plugin activation to create or update
 * the table via dbDelta().
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Transactions_Table
 *
 * Database schema definition for the transactions table.
 *
 * @since 1.0.0
 */
class BCG_Transactions_Table {

	/**
	 * Base table name (without WordPress prefix).
	 *
	 * @var string
	 */
	const TABLE_NAME = 'bcg_transactions';

	/**
	 * Get the full table name including the WordPress prefix.
	 *
	 * @since  1.0.0
	 * @return string The prefixed table name.
	 */
	public static function get_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Get the CREATE TABLE SQL for the transactions table.
	 *
	 * Returns SQL compatible with dbDelta() which requires:
	 * - Each field on its own line
	 * - Two spaces between PRIMARY KEY and the opening parenthesis
	 * - KEY syntax for non-unique indexes
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
		) {$charset_collate};";
	}
}
