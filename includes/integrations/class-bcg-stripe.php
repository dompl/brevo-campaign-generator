<?php
/**
 * Stripe payment integration.
 *
 * Handles communication with the Stripe API for credit top-up payments.
 * Uses wp_remote_post() / wp_remote_get() rather than the Stripe PHP SDK
 * to minimise external dependencies.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Stripe
 *
 * Provides methods for creating PaymentIntents, confirming payments,
 * retrieving credit pack configuration, and processing webhooks.
 *
 * @since 1.0.0
 */
class BCG_Stripe {

	/**
	 * Stripe API base URL.
	 *
	 * @var string
	 */
	const API_BASE = 'https://api.stripe.com/v1/';

	/**
	 * The Stripe secret key.
	 *
	 * @var string
	 */
	private string $secret_key;

	/**
	 * Constructor.
	 *
	 * Retrieves the Stripe secret key from plugin options and registers
	 * the webhook listener on the REST API.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->secret_key = get_option( 'bcg_stripe_secret_key', '' );

		// Register webhook endpoint.
		add_action( 'rest_api_init', array( $this, 'register_webhook_route' ) );
	}

	/**
	 * Register the REST API route for Stripe webhooks.
	 *
	 * Endpoint: POST /wp-json/bcg/v1/stripe-webhook
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register_webhook_route(): void {
		register_rest_route(
			'bcg/v1',
			'/stripe-webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_webhook' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Get the configured credit packs from plugin options.
	 *
	 * Returns an array of three credit packs, each containing:
	 * - credits: (int) number of credits
	 * - price:   (float) price in the configured currency
	 *
	 * Falls back to default packs if no custom configuration is saved.
	 *
	 * @since  1.0.0
	 * @return array Array of credit pack arrays.
	 */
	public function get_credit_packs(): array {
		$raw   = get_option( 'bcg_stripe_credit_packs', '' );
		$packs = json_decode( $raw, true );

		if ( ! is_array( $packs ) || count( $packs ) < 1 ) {
			$packs = array(
				array( 'credits' => 100, 'price' => 5.00 ),
				array( 'credits' => 300, 'price' => 12.00 ),
				array( 'credits' => 1000, 'price' => 35.00 ),
			);
		}

		// Ensure exactly 3 packs with valid values.
		$sanitised = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$sanitised[] = array(
				'credits' => isset( $packs[ $i ]['credits'] ) ? max( 1, absint( $packs[ $i ]['credits'] ) ) : 100,
				'price'   => isset( $packs[ $i ]['price'] ) ? max( 0.01, (float) $packs[ $i ]['price'] ) : 5.00,
			);
		}

		return $sanitised;
	}

	/**
	 * Create a Stripe PaymentIntent for a credit pack purchase.
	 *
	 * Communicates with the Stripe API to create a PaymentIntent with the
	 * amount derived from the selected credit pack. Stores the pack credits
	 * in the PaymentIntent metadata for verification at confirmation time.
	 *
	 * @since  1.0.0
	 * @param  int $pack_key The index of the credit pack (0, 1, or 2).
	 * @return array|WP_Error Array with 'client_secret' and 'pack' on success, or WP_Error.
	 */
	public function create_payment_intent( int $pack_key ): array|\WP_Error {
		if ( empty( $this->secret_key ) ) {
			return new \WP_Error(
				'stripe_not_configured',
				__( 'Stripe is not configured. Please add your Stripe secret key in Settings.', 'brevo-campaign-generator' )
			);
		}

		$packs = $this->get_credit_packs();

		if ( ! isset( $packs[ $pack_key ] ) ) {
			return new \WP_Error(
				'invalid_pack',
				__( 'Invalid credit pack selected.', 'brevo-campaign-generator' )
			);
		}

		$pack     = $packs[ $pack_key ];
		$currency = strtolower( get_option( 'bcg_stripe_currency', 'GBP' ) );
		$user_id  = get_current_user_id();

		// Stripe expects amounts in the smallest currency unit (e.g. pence for GBP).
		$amount_minor = (int) round( $pack['price'] * 100 );

		if ( $amount_minor < 1 ) {
			return new \WP_Error(
				'invalid_amount',
				__( 'Pack price results in an invalid Stripe amount.', 'brevo-campaign-generator' )
			);
		}

		$response = $this->api_request(
			'payment_intents',
			array(
				'amount'                    => $amount_minor,
				'currency'                  => $currency,
				'automatic_payment_methods[enabled]' => 'true',
				'metadata[bcg_credits]'     => $pack['credits'],
				'metadata[bcg_pack_key]'    => $pack_key,
				'metadata[bcg_user_id]'     => $user_id,
				'metadata[bcg_site_url]'    => home_url(),
				'description'               => sprintf(
					/* translators: 1: number of credits, 2: site name */
					__( 'BCG Credit Top-Up: %1$d credits (%2$s)', 'brevo-campaign-generator' ),
					$pack['credits'],
					get_bloginfo( 'name' )
				),
			),
			'POST'
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['client_secret'] ) ) {
			return new \WP_Error(
				'stripe_error',
				__( 'Stripe did not return a client secret. Please check your API keys.', 'brevo-campaign-generator' )
			);
		}

		return array(
			'client_secret' => $response['client_secret'],
			'pack'          => $pack,
			'amount_minor'  => $amount_minor,
			'currency'      => $currency,
		);
	}

	/**
	 * Confirm a Stripe payment by verifying the PaymentIntent status.
	 *
	 * Retrieves the PaymentIntent from Stripe and checks that it has
	 * the 'succeeded' status. Returns the credit amount from metadata.
	 *
	 * @since  1.0.0
	 * @param  string $payment_intent_id The Stripe PaymentIntent ID (pi_xxx).
	 * @return array|WP_Error Array with payment details on success, or WP_Error.
	 */
	public function confirm_payment( string $payment_intent_id ): array|\WP_Error {
		if ( empty( $this->secret_key ) ) {
			return new \WP_Error(
				'stripe_not_configured',
				__( 'Stripe is not configured.', 'brevo-campaign-generator' )
			);
		}

		// Validate the payment intent ID format.
		if ( ! preg_match( '/^pi_[a-zA-Z0-9]+$/', $payment_intent_id ) ) {
			return new \WP_Error(
				'invalid_payment_intent',
				__( 'Invalid PaymentIntent ID format.', 'brevo-campaign-generator' )
			);
		}

		$response = $this->api_request(
			'payment_intents/' . $payment_intent_id,
			array(),
			'GET'
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Check the PaymentIntent status.
		if ( ! isset( $response['status'] ) || 'succeeded' !== $response['status'] ) {
			$status = isset( $response['status'] ) ? $response['status'] : 'unknown';

			return new \WP_Error(
				'payment_not_succeeded',
				sprintf(
					/* translators: %s: payment status */
					__( 'Payment has not succeeded. Current status: %s', 'brevo-campaign-generator' ),
					$status
				)
			);
		}

		// Verify metadata.
		$metadata = isset( $response['metadata'] ) ? $response['metadata'] : array();
		$credits  = isset( $metadata['bcg_credits'] ) ? (int) $metadata['bcg_credits'] : 0;

		if ( $credits <= 0 ) {
			return new \WP_Error(
				'invalid_metadata',
				__( 'PaymentIntent metadata does not contain valid credit information.', 'brevo-campaign-generator' )
			);
		}

		// Verify this payment is for the current user.
		$meta_user_id = isset( $metadata['bcg_user_id'] ) ? (int) $metadata['bcg_user_id'] : 0;
		$current_user = get_current_user_id();

		if ( $meta_user_id !== $current_user && $current_user > 0 ) {
			return new \WP_Error(
				'user_mismatch',
				__( 'This payment does not belong to your account.', 'brevo-campaign-generator' )
			);
		}

		// Check for duplicate processing — prevent the same PI from adding credits twice.
		global $wpdb;
		$transactions_table = $wpdb->prefix . 'bcg_transactions';
		$already_processed  = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$transactions_table} WHERE stripe_payment_intent = %s AND type = 'topup'",
				$payment_intent_id
			)
		);

		if ( (int) $already_processed > 0 ) {
			return new \WP_Error(
				'already_processed',
				__( 'This payment has already been processed.', 'brevo-campaign-generator' )
			);
		}

		return array(
			'payment_intent_id' => $payment_intent_id,
			'credits'           => $credits,
			'amount_paid'       => isset( $response['amount'] ) ? (int) $response['amount'] : 0,
			'currency'          => isset( $response['currency'] ) ? $response['currency'] : '',
			'status'            => $response['status'],
		);
	}

	/**
	 * Handle a Stripe webhook event.
	 *
	 * Verifies the webhook signature, then processes supported event types.
	 * Currently handles: payment_intent.succeeded.
	 *
	 * @since  1.0.0
	 * @param  WP_REST_Request $request The incoming REST request.
	 * @return WP_REST_Response The response.
	 */
	public function handle_webhook( \WP_REST_Request $request ): \WP_REST_Response {
		$payload   = $request->get_body();
		$sig_header = $request->get_header( 'stripe-signature' );
		$secret     = get_option( 'bcg_stripe_webhook_secret', '' );

		// If no webhook secret is configured, accept on trust (development only).
		if ( ! empty( $secret ) && ! empty( $sig_header ) ) {
			$is_valid = $this->verify_webhook_signature( $payload, $sig_header, $secret );

			if ( ! $is_valid ) {
				return new \WP_REST_Response(
					array( 'error' => 'Invalid signature' ),
					400
				);
			}
		}

		$event = json_decode( $payload, true );

		if ( ! isset( $event['type'] ) || ! isset( $event['data']['object'] ) ) {
			return new \WP_REST_Response(
				array( 'error' => 'Invalid event payload' ),
				400
			);
		}

		$event_type = sanitize_text_field( $event['type'] );
		$object     = $event['data']['object'];

		switch ( $event_type ) {
			case 'payment_intent.succeeded':
				$this->process_webhook_payment_succeeded( $object );
				break;

			default:
				// We only handle payment_intent.succeeded for now.
				break;
		}

		return new \WP_REST_Response(
			array( 'received' => true ),
			200
		);
	}

	/**
	 * Process a payment_intent.succeeded webhook event.
	 *
	 * Extracts credit information from the PaymentIntent metadata and adds
	 * credits to the user's account if not already processed.
	 *
	 * @since  1.0.0
	 * @param  array $payment_intent The PaymentIntent object from the event.
	 * @return void
	 */
	private function process_webhook_payment_succeeded( array $payment_intent ): void {
		$metadata = isset( $payment_intent['metadata'] ) ? $payment_intent['metadata'] : array();
		$credits  = isset( $metadata['bcg_credits'] ) ? (int) $metadata['bcg_credits'] : 0;
		$user_id  = isset( $metadata['bcg_user_id'] ) ? (int) $metadata['bcg_user_id'] : 0;
		$pi_id    = isset( $payment_intent['id'] ) ? sanitize_text_field( $payment_intent['id'] ) : '';

		if ( $credits <= 0 || $user_id <= 0 || empty( $pi_id ) ) {
			// Not a BCG payment or invalid data — skip silently.
			return;
		}

		// Check for duplicate processing.
		global $wpdb;
		$transactions_table = $wpdb->prefix . 'bcg_transactions';
		$already_processed  = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$transactions_table} WHERE stripe_payment_intent = %s AND type = 'topup'",
				$pi_id
			)
		);

		if ( (int) $already_processed > 0 ) {
			return;
		}

		$amount_paid = isset( $payment_intent['amount'] ) ? (int) $payment_intent['amount'] : 0;
		$currency    = strtoupper( isset( $payment_intent['currency'] ) ? $payment_intent['currency'] : 'GBP' );

		$description = sprintf(
			/* translators: 1: number of credits, 2: formatted price, 3: currency code */
			__( 'Top-up: %1$s credits purchased for %2$s %3$s via Stripe (webhook)', 'brevo-campaign-generator' ),
			number_format( $credits, 0 ),
			number_format( $amount_paid / 100, 2 ),
			$currency
		);

		$credits_handler = new BCG_Credits();
		$credits_handler->add_credits( $user_id, (float) $credits, $description, $pi_id );
	}

	/**
	 * Verify a Stripe webhook signature.
	 *
	 * Implements the Stripe signature verification algorithm using HMAC-SHA256.
	 *
	 * @since  1.0.0
	 * @param  string $payload    The raw request body.
	 * @param  string $sig_header The Stripe-Signature header value.
	 * @param  string $secret     The webhook endpoint secret.
	 * @return bool True if the signature is valid.
	 */
	private function verify_webhook_signature( string $payload, string $sig_header, string $secret ): bool {
		// Parse the header: t=timestamp,v1=signature.
		$parts     = explode( ',', $sig_header );
		$timestamp = null;
		$signature = null;

		foreach ( $parts as $part ) {
			$pair = explode( '=', $part, 2 );
			if ( count( $pair ) !== 2 ) {
				continue;
			}

			$key   = trim( $pair[0] );
			$value = trim( $pair[1] );

			if ( 't' === $key ) {
				$timestamp = $value;
			} elseif ( 'v1' === $key ) {
				$signature = $value;
			}
		}

		if ( null === $timestamp || null === $signature ) {
			return false;
		}

		// Reject events older than 5 minutes to prevent replay attacks.
		$tolerance = 300; // 5 minutes.
		if ( abs( time() - (int) $timestamp ) > $tolerance ) {
			return false;
		}

		// Compute the expected signature.
		$signed_payload    = $timestamp . '.' . $payload;
		$expected_signature = hash_hmac( 'sha256', $signed_payload, $secret );

		return hash_equals( $expected_signature, $signature );
	}

	/**
	 * Make a request to the Stripe API.
	 *
	 * Uses wp_remote_post() or wp_remote_get() with Basic authentication.
	 * The secret key is used as the username with an empty password.
	 *
	 * @since  1.0.0
	 * @param  string $endpoint The API endpoint path (relative to base URL).
	 * @param  array  $data     The request data (form-encoded for POST, query params for GET).
	 * @param  string $method   The HTTP method: 'POST' or 'GET'.
	 * @return array|WP_Error The decoded response body on success, or WP_Error.
	 */
	private function api_request( string $endpoint, array $data = array(), string $method = 'POST' ): array|\WP_Error {
		$url = self::API_BASE . ltrim( $endpoint, '/' );

		$headers = array(
			'Authorization' => 'Basic ' . base64_encode( $this->secret_key . ':' ),
			'Content-Type'  => 'application/x-www-form-urlencoded',
		);

		$args = array(
			'headers' => $headers,
			'timeout' => 30,
		);

		if ( 'POST' === $method ) {
			$args['body'] = $data;
			$response = wp_remote_post( $url, $args );
		} else {
			if ( ! empty( $data ) ) {
				$url = add_query_arg( $data, $url );
			}
			$response = wp_remote_get( $url, $args );
		}

		if ( is_wp_error( $response ) ) {
			$this->log_error(
				'stripe_api_request',
				$response->get_error_message(),
				array( 'endpoint' => $endpoint, 'method' => $method )
			);

			return new \WP_Error(
				'stripe_connection_error',
				sprintf(
					/* translators: %s: error message */
					__( 'Failed to connect to Stripe: %s', 'brevo-campaign-generator' ),
					$response->get_error_message()
				)
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) ) {
			return new \WP_Error(
				'stripe_invalid_response',
				__( 'Received an invalid response from Stripe.', 'brevo-campaign-generator' )
			);
		}

		// Handle Stripe API errors.
		if ( $code < 200 || $code >= 300 ) {
			$error_message = __( 'An unknown Stripe error occurred.', 'brevo-campaign-generator' );
			$error_type    = 'stripe_api_error';

			if ( isset( $body['error'] ) ) {
				if ( isset( $body['error']['message'] ) ) {
					$error_message = $body['error']['message'];
				}
				if ( isset( $body['error']['type'] ) ) {
					$error_type = 'stripe_' . $body['error']['type'];
				}
			}

			$this->log_error(
				$error_type,
				$error_message,
				array(
					'endpoint'    => $endpoint,
					'http_code'   => $code,
					'stripe_code' => isset( $body['error']['code'] ) ? $body['error']['code'] : '',
				)
			);

			return new \WP_Error( $error_type, $error_message );
		}

		return $body;
	}

	/**
	 * Log an error to the plugin error log.
	 *
	 * Stores the last 50 errors in a WordPress option using a FIFO queue.
	 *
	 * @since  1.0.0
	 * @param  string $type    The error type identifier.
	 * @param  string $message The error message.
	 * @param  array  $context Additional context data.
	 * @return void
	 */
	private function log_error( string $type, string $message, array $context = array() ): void {
		$log_raw = get_option( 'bcg_error_log', '[]' );
		$log     = json_decode( $log_raw, true );

		if ( ! is_array( $log ) ) {
			$log = array();
		}

		$log[] = array(
			'type'      => $type,
			'message'   => $message,
			'context'   => $context,
			'timestamp' => current_time( 'mysql' ),
			'source'    => 'stripe',
		);

		// Keep only the last 50 entries.
		if ( count( $log ) > 50 ) {
			$log = array_slice( $log, -50 );
		}

		update_option( 'bcg_error_log', wp_json_encode( $log ) );
	}
}
