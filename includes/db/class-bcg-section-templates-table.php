<?php
/**
 * Section Templates DB table handler.
 *
 * Provides CRUD operations for the bcg_section_templates table which stores
 * named, reusable section template layouts created in the Section Builder.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.5.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Section_Templates_Table
 *
 * CRUD for the {prefix}bcg_section_templates database table.
 *
 * @since 1.5.0
 */
class BCG_Section_Templates_Table {

	/**
	 * Get the fully-qualified table name.
	 *
	 * @since  1.5.0
	 * @return string
	 */
	private static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'bcg_section_templates';
	}

	/**
	 * Insert or update a section template.
	 *
	 * When $id is 0 or null a new row is inserted; otherwise the existing
	 * row is updated.
	 *
	 * @since  1.5.0
	 * @param  string   $name        Template name.
	 * @param  string   $description Optional description.
	 * @param  array    $sections    Array of section objects.
	 * @param  int|null $id          Existing template ID for updates.
	 * @return int|\WP_Error Template ID on success, WP_Error on failure.
	 */
	public static function upsert( string $name, string $description, array $sections, ?int $id = null ): int|\WP_Error {
		global $wpdb;

		$sections_json = wp_json_encode( $sections );
		if ( false === $sections_json ) {
			return new \WP_Error( 'bcg_json_encode', __( 'Failed to encode sections to JSON.', 'brevo-campaign-generator' ) );
		}

		$data = array(
			'name'        => sanitize_text_field( $name ),
			'description' => sanitize_textarea_field( $description ),
			'sections'    => $sections_json,
			'updated_at'  => current_time( 'mysql' ),
		);

		$formats = array( '%s', '%s', '%s', '%s' );

		if ( $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$result = $wpdb->update( self::table(), $data, array( 'id' => $id ), $formats, array( '%d' ) );
			if ( false === $result ) {
				return new \WP_Error( 'bcg_db_update', $wpdb->last_error );
			}
			return $id;
		} else {
			$data['created_at'] = current_time( 'mysql' );
			$formats[]          = '%s';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$result = $wpdb->insert( self::table(), $data, $formats );
			if ( false === $result ) {
				return new \WP_Error( 'bcg_db_insert', $wpdb->last_error );
			}
			return (int) $wpdb->insert_id;
		}
	}

	/**
	 * Get all section templates (summary list).
	 *
	 * @since  1.5.0
	 * @return array[]
	 */
	public static function get_all(): array {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT id, name, description, created_at, updated_at FROM {$table} ORDER BY updated_at DESC" );
		return $rows ?: array();
	}

	/**
	 * Get a single section template by ID.
	 *
	 * @since  1.5.0
	 * @param  int $id Template ID.
	 * @return \stdClass|\WP_Error Row object on success, WP_Error if not found.
	 */
	public static function get( int $id ): \stdClass|\WP_Error {
		global $wpdb;
		$table = self::table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
		if ( ! $row ) {
			return new \WP_Error( 'bcg_not_found', __( 'Section template not found.', 'brevo-campaign-generator' ) );
		}
		return $row;
	}

	/**
	 * Delete a section template by ID.
	 *
	 * @since  1.5.0
	 * @param  int $id Template ID.
	 * @return true|\WP_Error True on success, WP_Error on failure.
	 */
	public static function delete( int $id ): true|\WP_Error {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->delete( self::table(), array( 'id' => $id ), array( '%d' ) );
		if ( false === $result ) {
			return new \WP_Error( 'bcg_db_delete', $wpdb->last_error );
		}
		return true;
	}
}
