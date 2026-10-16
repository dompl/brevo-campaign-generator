<?php
/**
 * BCG_Images generates through the portal with gpt-image-1.
 *
 * Note: no declare(strict_types=1) here - wp eval-file runs this file's
 * contents through eval(), and PHP forbids that declaration inside eval().
 * lib.php (required normally, not eval()'d) keeps the declaration.
 */
require_once __DIR__ . '/lib.php';

use RedFrogStudio\AtsAiPortal\Portal;

// A 1x1 transparent PNG so save_image() has real bytes to write.
$png_b64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
$ok_body = array( 'data' => array( array( 'b64_json' => $png_b64 ) ) );

$images   = new BCG_Images();
$products = array( array( 'id' => 1, 'name' => 'Diamond blade 230mm', 'slug' => 'diamond-blade-230mm' ) );

// 1. Hero image: URL, model, size, purpose, reference, charge.
bcg_mock_http( array( bcg_response( 200, $ok_body, array( 'x-charged-gbp' => '0.4000' ) ) ) );
$url = $images->generate_main_email_image( $products, 'Autumn sale', 'bold', 9001 );
bcg_assert( ! is_wp_error( $url ), 'hero image generated: ' . ( is_wp_error( $url ) ? $url->get_error_message() : '' ) );
bcg_assert( is_string( $url ) && str_starts_with( $url, 'http' ), 'hero returns an upload URL' );
$call = bcg_last_call();
bcg_assert_same( Portal::openai_url( '/images/generations' ), $call['url'], 'posts to /images/generations' );
$body = json_decode( $call['args']['body'], true );
bcg_assert_same( 'gpt-image-1', $body['model'], 'model gpt-image-1' );
bcg_assert_same( '1536x1024', $body['size'], 'hero size 1536x1024' );
bcg_assert_same( 'medium', $body['quality'], 'quality medium' );
bcg_assert_same( 1, $body['n'], 'n = 1' );
bcg_assert( ! empty( $body['prompt'] ) && str_contains( $body['prompt'], 'Diamond blade 230mm' ), 'prompt mentions the product' );
bcg_assert_same( 'ats:email:hero-image', $call['args']['headers']['X-Purpose'], 'hero purpose header' );
bcg_assert_same( '9001', $call['args']['headers']['X-Reference'], 'reference is the campaign id' );
bcg_assert_same( 'Bearer ' . ATS_PORTAL_KEY_EMAIL, $call['args']['headers']['Authorization'], 'bearer is the email key' );
bcg_assert_same( 0.4, $images->last_charged(), 'charge recorded' );
bcg_assert( (int) $call['args']['timeout'] >= 60, 'image timeout is generous' );

// 2. Product image: size and purpose.
bcg_mock_http( array( bcg_response( 200, $ok_body ) ) );
$url = $images->generate_product_image( $products[0], 'clean', 9001 );
bcg_assert( ! is_wp_error( $url ), 'product image generated' );
$call = bcg_last_call();
$body = json_decode( $call['args']['body'], true );
bcg_assert_same( '1024x1024', $body['size'], 'product size 1024x1024' );
bcg_assert_same( 'ats:email:product-image', $call['args']['headers']['X-Purpose'], 'product purpose header' );

// 3. 200 without b64_json (e.g. a url entry) → bcg_images_no_image.
bcg_mock_http( array( bcg_response( 200, array( 'data' => array( array( 'url' => 'https://example.com/x.png' ) ) ) ) ) );
$res = $images->generate_product_image( $products[0], 'clean', 9001 );
bcg_assert_same( 'bcg_images_no_image', is_wp_error( $res ) ? $res->get_error_code() : 'no-error', 'missing b64_json maps to bcg_images_no_image' );

// 4. 402 → ats_portal_out_of_credit, no retry.
bcg_mock_http( array( bcg_response( 402, array( 'error' => array( 'code' => 'insufficient_balance', 'message' => 'Out of credit' ) ) ) ) );
$res = $images->generate_main_email_image( $products, 'Autumn sale', 'bold', 9001 );
bcg_assert_same( 'ats_portal_out_of_credit', is_wp_error( $res ) ? $res->get_error_code() : 'no-error', '402 maps to ats_portal_out_of_credit' );
bcg_assert_same( 1, bcg_call_count(), 'images are never retried' );

// 5. Transport failure → ats_portal_unavailable, no retry.
bcg_mock_http( array( new WP_Error( 'http_request_failed', 'timeout' ) ) );
$res = $images->generate_main_email_image( $products, 'Autumn sale', 'bold', 9001 );
bcg_assert_same( 'ats_portal_unavailable', is_wp_error( $res ) ? $res->get_error_code() : 'no-error', 'network failure maps to ats_portal_unavailable' );
bcg_assert_same( 1, bcg_call_count(), 'no retry for images' );

// 6. Guards.
$res = $images->generate_main_email_image( array(), 'Autumn sale', 'bold', 9001 );
bcg_assert_same( 'bcg_images_no_products', is_wp_error( $res ) ? $res->get_error_code() : 'no-error', 'empty products guard' );
$res = $images->generate_product_image( array( 'id' => 5 ), 'clean', 9001 );
bcg_assert_same( 'bcg_images_invalid_product', is_wp_error( $res ) ? $res->get_error_code() : 'no-error', 'nameless product guard' );

// 7. test_connection() hits /models with GET.
bcg_mock_http( array( bcg_response( 200, array( 'data' => array() ) ) ) );
bcg_assert_same( true, $images->test_connection(), 'test_connection() true on 200' );
bcg_assert_same( Portal::openai_url( '/models' ), bcg_last_call()['url'], 'test_connection() hits /models' );

// 8. Class facts.
bcg_assert_same( 'gpt-image-1', $images->get_model(), 'get_model() is gpt-image-1' );
bcg_assert( ! class_exists( 'BCG_Gemini', false ), 'BCG_Gemini is gone' );
bcg_assert( ! method_exists( $images, 'get_credit_cost' ), 'get_credit_cost() removed' );

// Clean up the files written by save_image().
$images->delete_campaign_images( 9001 );

bcg_done( 'test-images' );
