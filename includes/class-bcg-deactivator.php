<?php
/**
 * Plugin deactivator.
 *
 * Handles cleanup tasks that should run when the plugin is deactivated
 * but not uninstalled. Data is preserved so that re-activation is seamless.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Deactivator
 *
 * Fired during plugin deactivation via register_deactivation_hook().
 *
 * @since 1.0.0
 */
class BCG_Deactivator {

	/**
	 * Run deactivation routines.
	 *
	 * Flushes rewrite rules and clears all plugin-specific transients
	 * so that stale cached data does not cause issues if the plugin is
	 * later re-activated.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function deactivate(): void {
		self::flush_rewrite_rules();
		self::clear_transients();
	}

	/**
	 * Flush WordPress rewrite rules.
	 *
	 * Ensures any custom rewrite rules added by the plugin are removed
	 * from the rewrite rule cache.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private static function flush_rewrite_rules(): void {
		flush_rewrite_rules();
	}

	/**
	 * Delete all bcg_* transients from the database.
	 *
	 * Searches for both standard transients and their timeout counterparts
	 * to ensure a clean removal.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private static function clear_transients(): void {
		global $wpdb;

		// Delete standard transients with the bcg_ prefix.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_bcg_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_bcg_' ) . '%'
			)
		);

		// If multisite, also clean site transients.
		if ( is_multisite() ) {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
					$wpdb->esc_like( '_site_transient_bcg_' ) . '%',
					$wpdb->esc_like( '_site_transient_timeout_bcg_' ) . '%'
				)
			);
		}
	}
}
