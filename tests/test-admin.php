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

require_once ABSPATH . 'wp-includes/class-wp-admin-bar.php';
$admin = new BCG_Admin();

// 1. Admin bar node shows the pound balance and links to the portal credits page.
$bar = new WP_Admin_Bar();
$bar->initialize();
$admin->add_credit_widget_to_admin_bar( $bar );
$node = $bar->get_node( 'bcg-credits' );
bcg_assert( null !== $node, 'admin bar node bcg-credits exists' );
bcg_assert( false !== strpos( $node->title, 'AI credit' ), 'node title mentions AI credit' );
bcg_assert( false !== strpos( $node->title, '£12.50' ), 'node title shows £12.50' );
bcg_assert_same( Portal::credits_url(), $node->href, 'node links to the AI Credits page' );

// 2. bcgData is pound based.
$admin->enqueue_assets( 'toplevel_page_bcg-dashboard' );
$data = wp_scripts()->get_data( 'bcg-admin-global', 'data' );
bcg_assert( is_string( $data ) && '' !== $data, 'bcgData localised' );
// wp_localize_script() stringifies every top-level scalar (see
// WP_Scripts::localize() in wp-includes/class-wp-scripts.php, which runs
// each scalar through (string) before wp_json_encode()), so these come
// through the wire as quoted JSON strings, not a bare number/boolean.
bcg_assert( false !== strpos( $data, '"balance_gbp":"12.5"' ), 'bcgData.balance_gbp = 12.5' );
bcg_assert( false !== strpos( $data, '"connected":"1"' ), 'bcgData.connected true with a key present' );
// WP_Scripts::localize() passes JSON_UNESCAPED_SLASHES, so the URL's
// slashes come through bare, not backslash-escaped.
bcg_assert( false !== strpos( $data, '"credits_url":"' . Portal::credits_url() . '"' ), 'bcgData.credits_url is the portal page' );
foreach ( array( 'credit_balance', 'stripe_pub_key', 'gemini_models', 'gemini_model' ) as $gone ) {
	bcg_assert( false === strpos( $data, '"' . $gone . '"' ), "bcgData.{$gone} removed" );
}

// 3. Removed members.
foreach ( array( 'render_credits_page', 'get_current_user_credit_balance' ) as $m ) {
	bcg_assert( ! method_exists( $admin, $m ), "{$m}() removed" );
}
$src = file_get_contents( BCG_PLUGIN_DIR . 'includes/admin/class-bcg-admin.php' );
bcg_assert( false === strpos( $src, "bcg-credits'" ) || false === strpos( $src, 'page=bcg-credits' ), 'no links to the old credits page' );
bcg_assert( false === strpos( $src, 'credits_used' ), 'no credits_used payloads' );
bcg_assert( false === strpos( $src, 'new_balance' ), 'no new_balance payloads' );
bcg_assert( false === strpos( $src, 'js.stripe.com' ), 'Stripe.js no longer enqueued' );
bcg_assert( false !== strpos( $src, "do_action( 'ats_ai_portal_credits_bar' )" ) || false !== strpos( file_get_contents( BCG_PLUGIN_DIR . 'admin/views/partials/plugin-header.php' ), "do_action( 'ats_ai_portal_credits_bar' )" ), 'credits bar action fired on BCG pages' );

bcg_done( 'test-admin' );
