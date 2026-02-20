<?php
/**
 * Uninstall handler.
 *
 * Fired when the plugin is deleted from the WordPress admin. Removes all
 * database tables and options created by the plugin so that no orphaned
 * data remains in the database.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Abort if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

/*
|--------------------------------------------------------------------------
| Drop custom database tables
|--------------------------------------------------------------------------
|
| Remove the four custom tables in reverse-dependency order. Campaign
| products reference campaigns, so they are dropped first.
|
*/

// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bcg_campaign_products" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bcg_transactions" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bcg_credits" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bcg_campaigns" );
// phpcs:enable WordPress.DB.DirectDatabaseQuery.SchemaChange

/*
|--------------------------------------------------------------------------
| Delete all plugin options
|--------------------------------------------------------------------------
|
| Every option created by the plugin is prefixed with bcg_. This query
| removes them all in a single operation.
|
*/

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( 'bcg_' ) . '%'
	)
);

/*
|--------------------------------------------------------------------------
| Delete all plugin transients
|--------------------------------------------------------------------------
|
| Clean up any cached data stored as transients with the bcg_ prefix.
|
*/

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_bcg_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_bcg_' ) . '%'
	)
);

/*
|--------------------------------------------------------------------------
| Clean up uploads directory
|--------------------------------------------------------------------------
|
| Remove the bcg/ directory inside wp-content/uploads/ where AI-generated
| images are stored. Uses WP_Filesystem for safety.
|
*/

$upload_dir = wp_upload_dir();
$bcg_upload = $upload_dir['basedir'] . '/bcg';

if ( is_dir( $bcg_upload ) ) {
	// Recursively delete the directory and its contents.
	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();
	global $wp_filesystem;

	if ( $wp_filesystem instanceof WP_Filesystem_Base ) {
		$wp_filesystem->delete( $bcg_upload, true );
	}
}

/*
|--------------------------------------------------------------------------
| Multisite cleanup
|--------------------------------------------------------------------------
|
| If running on a multisite network and the plugin was network-activated,
| repeat the table and option cleanup for each site.
|
*/

if ( is_multisite() ) {
	// Clean site-level transients.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
			$wpdb->esc_like( '_site_transient_bcg_' ) . '%',
			$wpdb->esc_like( '_site_transient_timeout_bcg_' ) . '%'
		)
	);
}
