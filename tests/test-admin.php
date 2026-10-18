<?php
/**
 * BCG_Admin shows the portal balance and localises pound-based data.
 *
 * Note: no declare(strict_types=1) here - wp eval-file runs this file's
 * contents through eval(), and PHP forbids that declaration inside eval().
 * lib.php (required normally, not eval()'d) keeps the declaration.
 */
require_once __DIR__ . '/lib.php';

use RedFrogStudio\AtsAiPortal\Portal;

// Run as an administrator so capability checks pass.
$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
bcg_assert( ! empty( $admins ), 'an administrator exists' );
wp_set_current_user( (int) $admins[0] );

// Mock the portal's /v1/account response. Portal::balance()/account() key off
// client_key(), which tries the images feature before email - on a box with a
// real sealed images secret, a hand-seeded transient keyed to the dummy email
// constant never matches, so only HTTP mocking reliably avoids a live call.
bcg_mock_http( array(
	bcg_response( 200, array( 'balance' => 12.5, 'activity' => array(), 'topups' => array() ) ),
) );

$admin = new BCG_Admin();

// 1. The admin-bar balance now belongs to ats-ai-portal (one balance, one top-up place).
bcg_assert( ! method_exists( $admin, 'add_credit_widget_to_admin_bar' ), 'add_credit_widget_to_admin_bar() removed' );
$header = file_get_contents( BCG_PLUGIN_DIR . 'admin/views/partials/plugin-header.php' );
bcg_assert( false === strpos( $header, 'ats_ai_portal_credits_bar' ), 'header no longer prints the second credits bar' );
$dash = file_get_contents( BCG_PLUGIN_DIR . 'admin/views/page-dashboard.php' );
bcg_assert( false === strpos( $dash, 'AI credit remaining' ), 'dashboard no longer repeats the balance' );

// 2. bcgData is pound based.
$admin->enqueue_assets( 'toplevel_page_bcg-dashboard' );
$data = wp_scripts()->get_data( 'bcg-admin-global', 'data' );
bcg_assert( is_string( $data ) && '' !== $data, 'bcgData localised' );
// WP_Scripts::localize() passes JSON_UNESCAPED_SLASHES, so the URL's
// slashes come through bare, not backslash-escaped.
bcg_assert( false !== strpos( $data, '"credits_url":"' . Portal::credits_url() . '"' ), 'bcgData.credits_url is the portal page' );
// balance_gbp/connected were dropped: the pound balance is rendered server-side in
// plugin-header.php and neither field has a JS consumer (see M3, final fix round).
foreach ( array( 'credit_balance', 'stripe_pub_key', 'gemini_models', 'gemini_model', 'balance_gbp', 'connected' ) as $gone ) {
	bcg_assert( false === strpos( $data, '"' . $gone . '"' ), "bcgData.{$gone} removed" );
}

// 3. Removed members.
foreach ( array( 'render_credits_page', 'get_current_user_credit_balance', 'add_credit_widget_to_admin_bar' ) as $m ) {
	bcg_assert( ! method_exists( $admin, $m ), "{$m}() removed" );
}
$src = file_get_contents( BCG_PLUGIN_DIR . 'includes/admin/class-bcg-admin.php' );
bcg_assert( false === strpos( $src, "bcg-credits'" ) || false === strpos( $src, 'page=bcg-credits' ), 'no links to the old credits page' );
bcg_assert( false === strpos( $src, 'credits_used' ), 'no credits_used payloads' );
bcg_assert( false === strpos( $src, 'new_balance' ), 'no new_balance payloads' );
bcg_assert( false === strpos( $src, 'js.stripe.com' ), 'Stripe.js no longer enqueued' );
bcg_assert( false === strpos( $src, 'ats_ai_portal_credits_bar' ), 'admin class does not fire the old credits bar (one balance: the header pill)' );

// 4. handle_add_product() stops on the first out-of-credit AI result: no
// second AI call, no error swallowed, no product row inserted (Finding 1,
// task-4 fix round 1). DOING_AJAX makes wp_send_json_error() route through
// wp_die() via the 'wp_die_ajax_handler' filter instead of a bare die(),
// which we intercept below so the test script can keep running.
if ( ! defined( 'DOING_AJAX' ) ) {
	define( 'DOING_AJAX', true );
}

global $wpdb;

$campaign_handler = new BCG_Campaign();
$test_campaign_id  = $campaign_handler->create_draft( array( 'title' => 'bcg-fix1-test-campaign' ) );
bcg_assert( ! is_wp_error( $test_campaign_id ), 'test campaign draft created for handle_add_product() test' );

$product_ids = wc_get_products( array( 'limit' => 1, 'status' => 'publish', 'return' => 'ids' ) );
bcg_assert( ! empty( $product_ids ), 'a published WooCommerce product exists to test handle_add_product() with' );
$test_product_id = (int) $product_ids[0];

// A single 402 "insufficient_balance" response - the same shape Portal::error()
// is already proven (tests/test-openai-client.php, tests/test-images.php) to map
// to a WP_Error coded ats_portal_out_of_credit. bcg_mock_http() repeats the last
// (only) queued response forever, so a second AI call would hit this too if the
// handler didn't stop after the first.
bcg_mock_http( array(
	bcg_response( 402, array( 'error' => array( 'code' => 'insufficient_balance', 'message' => 'Out of credit' ) ) ),
) );

$_POST = array(
	'nonce'       => wp_create_nonce( 'bcg_nonce' ),
	'campaign_id' => $test_campaign_id,
	'product_id'  => $test_product_id,
	'tone'        => 'Professional',
	'language'    => 'English',
);
$_REQUEST['nonce'] = $_POST['nonce'];

$die_interceptor = function () {
	return function ( $message = '', $title = '', $args = array() ) {
		throw new RuntimeException( 'wp_die intercepted' );
	};
};

$buffer              = '';
$product_count_after = null;

try {
	add_filter( 'wp_die_ajax_handler', $die_interceptor );
	add_filter( 'wp_die_handler', $die_interceptor );

	ob_start();
	try {
		$admin->handle_add_product();
	} catch ( RuntimeException $e ) {
		// Expected: wp_send_json_error() reached wp_die().
	}
	$buffer = ob_get_clean();

	$product_count_after = $campaign_handler->get_product_count( $test_campaign_id );
} finally {
	remove_filter( 'wp_die_ajax_handler', $die_interceptor );
	remove_filter( 'wp_die_handler', $die_interceptor );
	// Always clean up the test campaign (and any product rows it ended up
	// with), even if an unexpected exception escaped the inner try above.
	$campaign_handler->delete( $test_campaign_id );
	$_POST    = array();
	$_REQUEST = array();
}

$response = json_decode( $buffer, true );
bcg_assert( is_array( $response ) && false === ( $response['success'] ?? null ), 'handle_add_product() returns success=false when the first AI call is out of credit' );
bcg_assert_same( 'ats_portal_out_of_credit', $response['data']['code'] ?? null, 'handle_add_product() error payload code is ats_portal_out_of_credit' );
bcg_assert_same( 1, bcg_call_count(), 'handle_add_product() stops after the first AI call (no second call made)' );
bcg_assert_same( 0, $product_count_after, 'handle_add_product() did not insert a product row' );

bcg_done( 'test-admin' );
