<?php
/**
 * BCG_OpenAI posts to the portal and maps gateway errors.
 *
 * Note: no declare(strict_types=1) here - wp eval-file runs this file's
 * contents through eval(), and PHP forbids that declaration inside eval().
 * lib.php (required normally, not eval()'d) keeps the declaration.
 */
require_once __DIR__ . '/lib.php';

use RedFrogStudio\AtsAiPortal\Portal;

$ok_body = array(
	'choices' => array( array( 'message' => array( 'content' => 'Hello subject' ) ) ),
	'usage'   => array( 'total_tokens' => 42 ),
);

// 1. Happy path: URL, headers, body shape, charge recorded.
bcg_mock_http( array( bcg_response( 200, $ok_body, array( 'x-charged-gbp' => '0.0123', 'x-balance-gbp' => '19.50' ) ) ) );
$client = new BCG_OpenAI( 'gpt-4o-mini', 'ats:email:copy', '77' );
$text   = $client->generate_subject_line( array( array( 'name' => 'Diamond blade' ) ), 'Autumn sale', 'Friendly', 'English' );
bcg_assert( ! is_wp_error( $text ), 'subject line generated: ' . ( is_wp_error( $text ) ? $text->get_error_message() : '' ) );
$call = bcg_last_call();
bcg_assert_same( Portal::openai_url( '/chat/completions' ), $call['url'], 'posts to the portal chat/completions URL' );
bcg_assert_same( 'Bearer ' . ATS_PORTAL_KEY_EMAIL, $call['args']['headers']['Authorization'], 'bearer is the email key' );
bcg_assert_same( 'ats:email:copy', $call['args']['headers']['X-Purpose'], 'purpose header' );
bcg_assert_same( '77', $call['args']['headers']['X-Reference'], 'reference header' );
$body = json_decode( $call['args']['body'], true );
bcg_assert_same( 'gpt-4o-mini', $body['model'], 'model in body' );
bcg_assert_same( 'system', $body['messages'][0]['role'], 'system message first' );
bcg_assert( ! array_key_exists( 'api_key', $body ), 'no key in body' );
bcg_assert_same( 0.0123, $client->last_charged(), 'charge read from x-charged-gbp' );
bcg_assert_same( 42, $client->get_last_tokens_used(), 'tokens read from usage' );

// 2. Out of credit → ats_portal_out_of_credit.
bcg_mock_http( array( bcg_response( 402, array( 'error' => array( 'code' => 'insufficient_balance', 'message' => 'Out of credit' ) ) ) ) );
$res = $client->generate_subject_line( array( array( 'name' => 'Diamond blade' ) ), 'Autumn sale', 'Friendly', 'English' );
bcg_assert( is_wp_error( $res ), '402 returns WP_Error' );
bcg_assert_same( 'ats_portal_out_of_credit', $res->get_error_code(), '402 maps to ats_portal_out_of_credit' );
bcg_assert( str_contains( $res->get_error_message(), Portal::credits_url() ), '402 message links to the AI Credits page' );

// 3. Unreachable twice → one retry, then ats_portal_unavailable.
bcg_mock_http( array( new WP_Error( 'http_request_failed', 'cURL error 28' ) ) );
$res = $client->generate_subject_line( array( array( 'name' => 'Diamond blade' ) ), 'Autumn sale', 'Friendly', 'English' );
bcg_assert_same( 'ats_portal_unavailable', is_wp_error( $res ) ? $res->get_error_code() : 'no-error', 'network failure maps to ats_portal_unavailable' );
bcg_assert_same( 2, bcg_call_count(), 'network failure is retried exactly once' );
bcg_assert_same( 'AI service unavailable, try again.', $res->get_error_message(), 'unavailable copy' );

// 4. 502 then 200 → retry succeeds.
bcg_mock_http( array( bcg_response( 502, '<html>bad gateway</html>' ), bcg_response( 200, $ok_body ) ) );
$res = $client->generate_subject_line( array( array( 'name' => 'Diamond blade' ) ), 'Autumn sale', 'Friendly', 'English' );
bcg_assert( ! is_wp_error( $res ), '502 then 200 succeeds on retry' );
bcg_assert_same( null, $client->last_charged(), 'no charge header → last_charged() null' );

// 5. 200 with a body that has no choices → bcg_openai_invalid_response.
bcg_mock_http( array( bcg_response( 200, array( 'id' => 'x' ) ) ) );
$res = $client->generate_subject_line( array( array( 'name' => 'Diamond blade' ) ), 'Autumn sale', 'Friendly', 'English' );
bcg_assert_same( 'bcg_openai_invalid_response', is_wp_error( $res ) ? $res->get_error_code() : 'no-error', 'bad body maps to bcg_openai_invalid_response' );

// 6. 403 model_not_allowed → ats_portal_model_not_allowed (not retried).
bcg_mock_http( array( bcg_response( 403, array( 'error' => array( 'code' => 'model_not_allowed', 'message' => 'Model not allowed' ) ) ) ) );
$res = $client->generate_subject_line( array( array( 'name' => 'Diamond blade' ) ), 'Autumn sale', 'Friendly', 'English' );
bcg_assert_same( 'ats_portal_model_not_allowed', is_wp_error( $res ) ? $res->get_error_code() : 'no-error', '403 maps to ats_portal_model_not_allowed' );
bcg_assert_same( 1, bcg_call_count(), '403 is not retried' );

// 7. test_connection() is a free GET /models with the test purpose.
bcg_mock_http( array( bcg_response( 200, array( 'data' => array( array( 'id' => 'gpt-4o' ) ) ) ) ) );
bcg_assert_same( true, $client->test_connection(), 'test_connection() true on 200' );
$call = bcg_last_call();
bcg_assert_same( Portal::openai_url( '/models' ), $call['url'], 'test_connection() hits /models' );
bcg_assert_same( 'GET', strtoupper( $call['args']['method'] ?? 'GET' ), 'test_connection() uses GET' );
bcg_assert_same( 'ats:email:test', $call['args']['headers']['X-Purpose'], 'test purpose header' );

// 8. set_reference() changes the header on the next call.
bcg_mock_http( array( bcg_response( 200, $ok_body ) ) );
$client->set_reference( '88' );
$client->generate_subject_line( array( array( 'name' => 'Diamond blade' ) ), 'Autumn sale', 'Friendly', 'English' );
bcg_assert_same( '88', bcg_last_call()['args']['headers']['X-Reference'], 'set_reference() updates X-Reference' );

// 9. has_api_key() reflects the portal key.
bcg_assert_same( true, $client->has_api_key(), 'has_api_key() true when the email key constant is defined' );

bcg_done( 'test-openai-client' );
