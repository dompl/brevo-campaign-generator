<?php
/**
 * Credits database table definition.
 *
 * Provides the table name and CREATE TABLE SQL schema for the bcg_credits
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
 * Class BCG_Credits_Table
 *
 * Database schema definition for the credits table.
 *
 * @since 1.0.0
 */
class BCG_Credits_Table {

	/**
	 * Base table name (without WordPress prefix).
	 *
	 * @var string
	 */
	const TABLE_NAME = 'bcg_credits';

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
	 * Get the CREATE TABLE SQL for the credits table.
	 *
	 * Returns SQL compatible with dbDelta() which requires:
	 * - Each field on its own line
	 * - Two spaces between PRIMARY KEY and the opening parenthesis
	 * - UNIQUE KEY syntax for unique indexes
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
			balance DECIMAL(10,4) DEFAULT 0.0000,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY idx_user (user_id)
		) {$charset_collate};";
	}
}
