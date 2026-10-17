<?php
/**
 * Ledger, Stripe and Gemini code is gone.
 *
 * Note: no declare(strict_types=1) here - wp eval-file runs this file's
 * contents through eval(), and PHP forbids that declaration inside eval().
 * lib.php (required normally, not eval()'d) keeps the declaration.
 */
require_once __DIR__ . '/lib.php';

foreach ( array( 'BCG_Credits', 'BCG_Stripe', 'BCG_Gemini' ) as $class ) {
	bcg_assert( ! class_exists( $class ), "{$class} class gone" );
}
foreach ( array(
	'includes/admin/class-bcg-credits.php',
	'includes/integrations/class-bcg-stripe.php',
	'includes/db/class-bcg-credits-table.php',
	'includes/db/class-bcg-transactions-table.php',
	'admin/views/page-credits.php',
	'admin/views/partials/credit-widget.php',
	'admin/js/bcg-stripe.js',
	'includes/ai/class-bcg-gemini.php',
) as $file ) {
	bcg_assert( ! file_exists( BCG_PLUGIN_DIR . $file ), "{$file} deleted" );
}
bcg_assert( false === has_action( 'wp_ajax_bcg_stripe_create_intent' ), 'stripe create-intent AJAX unhooked' );
bcg_assert( false === has_action( 'wp_ajax_bcg_stripe_confirm' ), 'stripe confirm AJAX unhooked' );
bcg_assert( false === has_action( 'wp_ajax_bcg_get_credit_balance' ), 'credit balance AJAX unhooked' );
bcg_assert( ! method_exists( 'BCG_Plugin', 'get_credits' ), 'BCG_Plugin::get_credits() gone' );
bcg_done( 'test-removed' );
