<?php
/**
 * The 1.6.0 upgrade drops the ledger tables and retired options, and is idempotent.
 *
 * Note: no declare(strict_types=1) here - wp eval-file runs this file's
 * contents through eval(), and PHP forbids that declaration inside eval().
 * lib.php (required normally, not eval()'d) keeps the declaration.
 */
require_once __DIR__ . '/lib.php';

global $wpdb;
$retired = array(
	'bcg_openai_api_key', 'bcg_gemini_api_key', 'bcg_gemini_model', 'bcg_stripe_publishable_key', 'bcg_stripe_secret_key',
	'bcg_stripe_currency', 'bcg_stripe_credit_packs', 'bcg_credit_cost_openai_gpt4o', 'bcg_credit_cost_openai_gpt4o_mini',
	'bcg_credit_cost_gemini_pro', 'bcg_credit_cost_gemini_flash', 'bcg_credit_value', 'bcg_test_mode',
);

// Simulate a 1.5.50 install: version option, a retired option and the two ledger tables.
$previous = get_option( 'bcg_version' );
update_option( 'bcg_version', '1.5.50' );
update_option( 'bcg_gemini_model', 'gemini-1.5-flash' );
update_option( 'bcg_openai_model', 'gpt-4o' );
$wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bcg_credits ( id INT )" );
$wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bcg_transactions ( id INT )" );

BCG_Activator::maybe_upgrade();

foreach ( $retired as $opt ) {
	bcg_assert( false === get_option( $opt, false ), "{$opt} deleted" );
}
bcg_assert_same( 'gpt-4o', get_option( 'bcg_openai_model' ), 'bcg_openai_model kept' );
bcg_assert_same( null, $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'bcg_credits' ) ), 'bcg_credits table dropped' );
bcg_assert_same( null, $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'bcg_transactions' ) ), 'bcg_transactions table dropped' );

// Running again (same stored version) must not error.
BCG_Activator::maybe_upgrade();
bcg_assert( true, 'second maybe_upgrade() run completes' );

update_option( 'bcg_version', BCG_VERSION );
bcg_assert_same( '1.6.0', BCG_VERSION, 'BCG_VERSION is 1.6.0' );
$header = file_get_contents( BCG_PLUGIN_DIR . 'brevo-campaign-generator.php' );
bcg_assert( false !== strpos( $header, 'Version: 1.6.0' ), 'plugin header says 1.6.0' );

// Restore whatever bcg_version was before this test ran, so the real site state is untouched.
if ( false === $previous ) {
	delete_option( 'bcg_version' );
} else {
	update_option( 'bcg_version', $previous );
}

bcg_done( 'test-upgrade' );
