<?php
/**
 * Shared helpers for brevo-campaign-generator test scripts.
 * Run each test with: wp eval-file wp-content/plugins/brevo-campaign-generator/tests/<file>.php --skip-themes
 */

declare(strict_types=1);

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	fwrite( STDERR, "Run with: wp eval-file tests/<file>.php --skip-themes\n" );
	exit( 1 );
}

// Dummy gateway key so Portal::has_key('email') is true and no sealed secret is touched.
if ( ! defined( 'ATS_PORTAL_KEY_EMAIL' ) ) {
	define( 'ATS_PORTAL_KEY_EMAIL', 'rfs_testemail00_' . str_repeat( 'c', 40 ) );
}

foreach ( array( \RedFrogStudio\AtsAiPortal\Portal::CACHE, \RedFrogStudio\AtsAiPortal\Portal::BACKOFF, \RedFrogStudio\AtsAiPortal\Portal::ACCOUNT ) as $t ) {
	delete_transient( $t );
}

function bcg_assert( bool $ok, string $what ): void {
	if ( ! $ok ) {
		\WP_CLI::error( "FAIL: {$what}" );
	}
	\WP_CLI::log( "ok - {$what}" );
}

function bcg_assert_same( $expected, $actual, string $what ): void {
	if ( $expected !== $actual ) {
		\WP_CLI::error( "FAIL: {$what}\n  expected: " . var_export( $expected, true ) . "\n  actual:   " . var_export( $actual, true ) );
	}
	\WP_CLI::log( "ok - {$what}" );
}

/** Build a wp_remote_* style response array. Header names lowercase. */
function bcg_response( int $code, $body, array $headers = array() ): array {
	return array(
		'headers'  => $headers,
		'body'     => is_string( $body ) ? $body : wp_json_encode( $body ),
		'response' => array( 'code' => $code, 'message' => 'OK' ),
		'cookies'  => array(),
		'filename' => null,
	);
}

/**
 * Queue responses for pre_http_request. Each entry is a response array, a WP_Error,
 * or a callable( $url, $args ). The last entry repeats forever.
 */
function bcg_mock_http( array $responses ): void {
	$GLOBALS['bcg_http_calls'] = array();
	$GLOBALS['bcg_http_queue'] = array_values( $responses );
	remove_all_filters( 'pre_http_request' );
	add_filter( 'pre_http_request', function ( $pre, $args, $url ) {
		$GLOBALS['bcg_http_calls'][] = array( 'url' => $url, 'args' => $args );
		$queue = $GLOBALS['bcg_http_queue'];
		$next  = count( $queue ) > 1 ? array_shift( $GLOBALS['bcg_http_queue'] ) : $queue[0];
		return is_callable( $next ) ? $next( $url, $args ) : $next;
	}, 10, 3 );
}

function bcg_last_call(): array {
	$calls = $GLOBALS['bcg_http_calls'] ?? array();
	return $calls ? $calls[ count( $calls ) - 1 ] : array();
}

function bcg_call_count(): int {
	return count( $GLOBALS['bcg_http_calls'] ?? array() );
}

function bcg_done( string $name ): void {
	remove_all_filters( 'pre_http_request' );
	\WP_CLI::success( "{$name} passed" );
}
