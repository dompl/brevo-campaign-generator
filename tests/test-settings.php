<?php
/**
 * BCG_Settings no longer exposes Gemini, Stripe or credit settings.
 *
 * Note: no declare(strict_types=1) here - wp eval-file runs this file's
 * contents through eval(), and PHP forbids that declaration inside eval().
 * lib.php (required normally, not eval()'d) keeps the declaration.
 */
require_once __DIR__ . '/lib.php';

$settings = new BCG_Settings();

bcg_assert_same( array( 'api-keys', 'ai-models', 'brevo', 'defaults' ), array_keys( $settings->get_tabs() ), 'tabs are api-keys, ai-models, brevo, defaults' );

foreach ( array( 'register_stripe_settings', 'render_gemini_model_field', 'render_currency_field', 'render_credit_packs_field', 'sanitize_gemini_model', 'sanitize_currency', 'sanitize_credit_packs', 'test_gemini_connection', 'test_stripe_pub_connection', 'test_stripe_secret_connection', 'get_gemini_models', 'get_currency_symbol' ) as $m ) {
	bcg_assert( ! method_exists( $settings, $m ), "{$m}() removed" );
}

$rows = $settings->get_pricing_reference();
bcg_assert_same( 3, count( $rows ), 'pricing reference has 3 rows' );
foreach ( $rows as $row ) {
	bcg_assert_same( 'OpenAI', $row['service'], 'every pricing row is OpenAI' );
	bcg_assert( str_starts_with( $row['cost'], '£' ) || str_starts_with( $row['cost'], '~£' ), 'cost is in pounds: ' . $row['cost'] );
}

// Registered settings: only Brevo key + OpenAI model + brevo/defaults groups; no Gemini/Stripe/credit options.
do_action( 'admin_init' );
$registered = array_keys( get_registered_settings() );
foreach ( array( 'bcg_openai_api_key', 'bcg_gemini_api_key', 'bcg_gemini_model', 'bcg_stripe_publishable_key', 'bcg_stripe_secret_key', 'bcg_stripe_currency', 'bcg_stripe_credit_packs', 'bcg_credit_value', 'bcg_credit_cost_openai_gpt4o', 'bcg_credit_cost_openai_gpt4o_mini', 'bcg_credit_cost_gemini_pro', 'bcg_credit_cost_gemini_flash', 'bcg_test_mode' ) as $opt ) {
	bcg_assert( ! in_array( $opt, $registered, true ), "{$opt} no longer registered" );
}
foreach ( array( 'bcg_brevo_api_key', 'bcg_openai_model' ) as $opt ) {
	bcg_assert( in_array( $opt, $registered, true ), "{$opt} still registered" );
}

// The OpenAI "test connection" pings the portal, not api.openai.com.
bcg_mock_http( array( bcg_response( 200, array( 'data' => array( array( 'id' => 'gpt-4o' ) ) ) ) ) );
$ref = new ReflectionMethod( BCG_Settings::class, 'test_openai_connection' );
$ref->setAccessible( true );
$result = $ref->invoke( $settings );
bcg_assert( ! is_wp_error( $result ), 'openai test succeeds via the portal: ' . ( is_wp_error( $result ) ? $result->get_error_message() : '' ) );
bcg_assert( str_contains( bcg_last_call()['url'], '/openai/v1/models' ), 'openai test hits the portal models endpoint' );

// ─── AJAX handler: every error payload carries a 'code' key ─────────────
//
// handle_test_api_key() ends in wp_send_json_error()/wp_send_json_success(),
// which call wp_die(). DOING_AJAX makes wp_send_json_*() route through the
// 'wp_die_ajax_handler' filter instead of a bare die(), which we intercept
// below (same pattern as tests/test-admin.php) so the test script can keep
// running and we can inspect the buffered JSON payload.
$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
bcg_assert( ! empty( $admins ), 'an administrator exists' );
wp_set_current_user( (int) $admins[0] );

if ( ! defined( 'DOING_AJAX' ) ) {
	define( 'DOING_AJAX', true );
}

$die_interceptor = function () {
	return function ( $message = '', $title = '', $args = array() ) {
		throw new RuntimeException( 'wp_die intercepted' );
	};
};

/**
 * Fire handle_test_api_key() for the given service and return the decoded
 * wp_send_json_*() payload.
 *
 * @param  string $service Service slug posted to the handler.
 * @return array Decoded JSON payload.
 */
$call_test_api_key = function ( string $service ) use ( $settings, $die_interceptor ): array {
	$_POST             = array(
		'nonce'   => wp_create_nonce( 'bcg_nonce' ),
		'service' => $service,
	);
	$_REQUEST['nonce'] = $_POST['nonce'];

	$buffer = '';

	try {
		add_filter( 'wp_die_ajax_handler', $die_interceptor );
		add_filter( 'wp_die_handler', $die_interceptor );

		ob_start();
		try {
			$settings->handle_test_api_key();
		} catch ( RuntimeException $e ) {
			// Expected: wp_send_json_error()/wp_send_json_success() reached wp_die().
		}
		$buffer = ob_get_clean();
	} finally {
		remove_filter( 'wp_die_ajax_handler', $die_interceptor );
		remove_filter( 'wp_die_handler', $die_interceptor );
		$_POST    = array();
		$_REQUEST = array();
	}

	$decoded = json_decode( $buffer, true );
	bcg_assert( is_array( $decoded ), "handle_test_api_key('{$service}') returned JSON: {$buffer}" );

	return $decoded;
};

// Out of credit: 402 maps to ats_portal_out_of_credit, surfaced in the AJAX payload's 'code'.
bcg_mock_http( array( bcg_response( 402, array( 'error' => array( 'code' => 'insufficient_balance', 'message' => 'Out of credit' ) ) ) ) );
$payload = $call_test_api_key( 'openai' );
bcg_assert_same( false, $payload['success'] ?? null, '402: handle_test_api_key() reports failure' );
bcg_assert_same( 'ats_portal_out_of_credit', $payload['data']['code'] ?? null, '402: AJAX payload code is ats_portal_out_of_credit' );

// Gateway 5xx: surfaces its mapped error code in the payload, never a raw HTML body.
bcg_mock_http( array( bcg_response( 500, 'Internal Server Error' ) ) );
$payload = $call_test_api_key( 'openai' );
bcg_assert_same( false, $payload['success'] ?? null, '5xx: handle_test_api_key() reports failure' );
bcg_assert_same( 'ats_portal_http_500', $payload['data']['code'] ?? null, '5xx: AJAX payload code is ats_portal_http_500' );

bcg_done( 'test-settings' );
