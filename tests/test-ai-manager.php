<?php
/**
 * BCG_AI_Manager sums gateway charges and stops a batch when credit runs out.
 *
 * Note: no declare(strict_types=1) here - wp eval-file runs this file's
 * contents through eval(), and PHP forbids that declaration inside eval().
 * lib.php (required normally, not eval()'d) keeps the declaration.
 */
require_once __DIR__ . '/lib.php';

$ok_text = array(
	'choices' => array( array( 'message' => array( 'content' => 'Generated text' ) ) ),
	'usage'   => array( 'total_tokens' => 10 ),
);
$png_b64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
$ok_png  = array( 'data' => array( array( 'b64_json' => $png_b64 ) ) );
$out     = bcg_response( 402, array( 'error' => array( 'code' => 'insufficient_balance', 'message' => 'Out of credit' ) ) );

$products = array( array( 'id' => 1, 'name' => 'Diamond blade 230mm', 'slug' => 'diamond-blade-230mm', 'price' => '29.99' ) );
$config   = array( 'theme' => 'Autumn sale', 'tone' => 'Friendly', 'language' => 'English', 'generate_coupon' => false );

// 1. Constructor shape and removed members.
$manager = new BCG_AI_Manager();
bcg_assert( $manager->get_images() instanceof BCG_Images, 'get_images() returns BCG_Images' );
foreach ( array( 'check_credits', 'deduct_credits', 'refund_credits', 'get_credit_cost', 'estimate_campaign_cost', 'get_credit_balance', 'get_credits', 'get_gemini', 'execute_gemini_task' ) as $m ) {
	bcg_assert( ! method_exists( $manager, $m ), "{$m}() removed" );
}
$ref = new ReflectionMethod( BCG_AI_Manager::class, '__construct' );
bcg_assert_same( 2, $ref->getNumberOfParameters(), 'constructor takes (openai, images) only' );

// 2. Copy batch: every call charged £0.01 → total_charged_gbp sums the successful tasks.
bcg_mock_http( array( bcg_response( 200, $ok_text, array( 'x-charged-gbp' => '0.0100' ) ) ) );
$result = $manager->generate_campaign_copy( 501, $products, $config );
bcg_assert( ! is_wp_error( $result ), 'copy batch returns an array: ' . ( is_wp_error( $result ) ? $result->get_error_message() : '' ) );
bcg_assert( bcg_call_count() >= 6, 'copy batch made at least 4 + 2n calls' );
bcg_assert( array_key_exists( 'total_charged_gbp', $result ), 'result has total_charged_gbp' );
bcg_assert( ! array_key_exists( 'total_credits_used', $result ), 'result has no total_credits_used' );
$successes = bcg_call_count() - count( $result['partial_errors'] ?? array() );
bcg_assert( abs( $result['total_charged_gbp'] - 0.01 * $successes ) < 0.000001, 'total_charged_gbp = £0.01 × successful tasks (' . $successes . ')' );
bcg_assert_same( 'Generated text', $result['subject_line'] ?? null, 'subject line populated' );

// 3. Copy batch stops at the first 402.
bcg_mock_http( array( bcg_response( 200, $ok_text, array( 'x-charged-gbp' => '0.0100' ) ), $out ) );
$result = $manager->generate_campaign_copy( 502, $products, $config );
bcg_assert( is_wp_error( $result ), 'out of credit returns WP_Error' );
bcg_assert_same( 'ats_portal_out_of_credit', $result->get_error_code(), 'error code is ats_portal_out_of_credit' );
bcg_assert_same( 2, bcg_call_count(), 'batch stopped after the 402 (no further calls)' );

// 4. Image batch sums charges and stops on 402.
bcg_mock_http( array( bcg_response( 200, $ok_png, array( 'x-charged-gbp' => '0.4000' ) ) ) );
$result = $manager->generate_campaign_images( 503, $products, array( 'theme' => 'Autumn sale', 'style' => 'bold', 'generate_product_images' => true ) );
bcg_assert( ! is_wp_error( $result ), 'image batch returns an array: ' . ( is_wp_error( $result ) ? $result->get_error_message() : '' ) );
bcg_assert_same( 2, bcg_call_count(), 'hero + one product image' );
bcg_assert( abs( ( $result['total_charged_gbp'] ?? 0 ) - 0.8 ) < 0.000001, 'image total_charged_gbp = 0.8' );
bcg_assert( ! empty( $result['main_image_url'] ), 'main_image_url set' );

bcg_mock_http( array( $out ) );
$result = $manager->generate_campaign_images( 504, $products, array( 'theme' => 'Autumn sale', 'style' => 'bold', 'generate_product_images' => true ) );
bcg_assert_same( 'ats_portal_out_of_credit', is_wp_error( $result ) ? $result->get_error_code() : 'no-error', 'image batch returns out-of-credit error' );
bcg_assert_same( 1, bcg_call_count(), 'image batch stopped after the first 402' );

// 5. regenerate_field() carries charged_gbp.
bcg_mock_http( array( bcg_response( 200, $ok_text, array( 'x-charged-gbp' => '0.0200' ) ) ) );
$res = $manager->regenerate_field( 505, 'subject_line', array( 'products' => $products, 'theme' => 'Autumn sale', 'tone' => 'Friendly', 'language' => 'English' ) );
bcg_assert( ! is_wp_error( $res ), 'regenerate subject_line ok: ' . ( is_wp_error( $res ) ? $res->get_error_message() : '' ) );
bcg_assert_same( 0.02, $res['charged_gbp'] ?? null, 'regenerate result has charged_gbp' );
bcg_assert( ! array_key_exists( 'credits_used', $res ), 'regenerate result has no credits_used' );

// 6. Balance passthrough.
$bal = $manager->get_balance_gbp();
bcg_assert( null === $bal || is_float( $bal ), 'get_balance_gbp() is float|null' );

$manager->get_images()->delete_campaign_images( 503 );
bcg_done( 'test-ai-manager' );
