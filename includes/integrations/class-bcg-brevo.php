<?php
/**
 * Brevo (formerly Sendinblue) API client.
 *
 * Provides a comprehensive wrapper around the Brevo v3 REST API for managing
 * contact lists, email campaigns, templates, and sending test emails.
 *
 * All HTTP requests are made using wp_remote_request(). Responses are cached
 * via WordPress transients where appropriate. On failure, methods return a
 * WP_Error and the error is logged to the bcg_error_log option (FIFO, max 50).
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Brevo
 *
 * Brevo v3 API integration for the Brevo Campaign Generator plugin.
 *
 * @since 1.0.0
 */
class BCG_Brevo {

	/**
	 * Brevo API v3 base URL.
	 *
	 * @var string
	 */
	const API_BASE_URL = 'https://api.brevo.com/v3/';

	/**
	 * HTTP request timeout in seconds.
	 *
	 * @var int
	 */
	const REQUEST_TIMEOUT = 30;

	/**
	 * Maximum number of errors to keep in the error log.
	 *
	 * @var int
	 */
	const MAX_ERROR_LOG_ENTRIES = 50;

	/**
	 * Transient TTL for contact lists cache (1 hour).
	 *
	 * @var int
	 */
	const LISTS_CACHE_TTL = HOUR_IN_SECONDS;

	/**
	 * Transient TTL for campaigns cache (15 minutes).
	 *
	 * @var int
	 */
	const CAMPAIGNS_CACHE_TTL = 15 * MINUTE_IN_SECONDS;

	/**
	 * Transient TTL for campaign stats cache (15 minutes).
	 *
	 * @var int
	 */
	const STATS_CACHE_TTL = 15 * MINUTE_IN_SECONDS;

	/**
	 * Brevo API key.
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * Constructor.
	 *
	 * Retrieves the Brevo API key from WordPress options. If no API key is
	 * configured, methods will return WP_Error when called.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $api_key Optional. Override the stored API key.
	 */
	public function __construct( ?string $api_key = null ) {
		$this->api_key = $api_key ?? bcg_get_api_key( 'bcg_brevo_api_key' );
	}

	// -------------------------------------------------------------------------
	// Public API Methods — Senders
	// -------------------------------------------------------------------------

	/**
	 * Retrieve all verified senders from Brevo.
	 *
	 * Returns the list of senders that have been verified in the Brevo account.
	 * Only verified senders can be used to send campaigns.
	 *
	 * @since 1.1.0
	 *
	 * @return array|WP_Error Array of sender objects on success, WP_Error on failure.
	 */
	public function get_senders() {
		$result = $this->request( 'GET', 'senders' );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return isset( $result['senders'] ) ? $result['senders'] : array();
	}

	// -------------------------------------------------------------------------
	// Public API Methods — Lists
	// -------------------------------------------------------------------------

	/**
	 * Retrieve all contact lists from Brevo.
	 *
	 * Results are cached in a transient for 1 hour to reduce API calls.
	 * Pass $force_refresh = true to bypass the cache.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $force_refresh Optional. Whether to bypass the transient cache. Default false.
	 * @return array|WP_Error Array of list objects on success, WP_Error on failure.
	 */
	public function get_contact_lists( bool $force_refresh = false ) {
		if ( ! $force_refresh ) {
			$cached = get_transient( 'bcg_brevo_lists' );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$all_lists = array();
		$limit     = 50;
		$offset    = 0;

		// Paginate through all lists.
		do {
			$response = $this->request(
				'GET',
				'contacts/lists',
				array(
					'limit'  => $limit,
					'offset' => $offset,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$lists = isset( $response['lists'] ) ? $response['lists'] : array();
			$count = isset( $response['count'] ) ? (int) $response['count'] : 0;

			$all_lists = array_merge( $all_lists, $lists );
			$offset   += $limit;
		} while ( $offset < $count );

		set_transient( 'bcg_brevo_lists', $all_lists, self::LISTS_CACHE_TTL );

		return $all_lists;
	}

	/**
	 * Retrieve a single contact list by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $list_id The Brevo list ID.
	 * @return array|WP_Error List data array on success, WP_Error on failure.
	 */
	public function get_list( int $list_id ) {
		if ( $list_id <= 0 ) {
			return new WP_Error(
				'bcg_brevo_invalid_list_id',
				__( 'Invalid list ID provided.', 'brevo-campaign-generator' )
			);
		}

		return $this->request( 'GET', 'contacts/lists/' . absint( $list_id ) );
	}

	// -------------------------------------------------------------------------
	// Public API Methods — Campaigns
	// -------------------------------------------------------------------------

	/**
	 * Create a new email campaign in Brevo.
	 *
	 * The $data array should conform to the Brevo campaign creation payload:
	 *
	 *     [
	 *         'name'        => '[WC] Campaign Title',
	 *         'subject'     => 'Subject line here',
	 *         'sender'      => [ 'name' => 'Store Name', 'email' => 'store@example.com' ],
	 *         'type'        => 'classic',
	 *         'htmlContent' => '...full HTML...',
	 *         'recipients'  => [ 'listIds' => [ 12 ] ],
	 *     ]
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Campaign creation payload.
	 * @return array|WP_Error Created campaign data (includes 'id') on success, WP_Error on failure.
	 */
	public function create_campaign( array $data ) {
		$required_fields = array( 'name', 'subject', 'sender', 'htmlContent', 'recipients' );

		foreach ( $required_fields as $field ) {
			if ( empty( $data[ $field ] ) ) {
				return new WP_Error(
					'bcg_brevo_missing_field',
					/* translators: %s: the missing field name */
					sprintf( __( 'Missing required campaign field: %s', 'brevo-campaign-generator' ), $field )
				);
			}
		}

		// Ensure campaign type defaults to 'classic'.
		if ( empty( $data['type'] ) ) {
			$data['type'] = 'classic';
		}

		$response = $this->request( 'POST', 'emailCampaigns', null, $data );

		if ( ! is_wp_error( $response ) ) {
			// Invalidate campaigns cache on creation.
			delete_transient( 'bcg_brevo_campaigns' );
		}

		return $response;
	}

	/**
	 * Update an existing email campaign in Brevo.
	 *
	 * Only fields included in $data will be updated. The campaign must be in
	 * draft status to be updated.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $campaign_id The Brevo campaign ID.
	 * @param array $data        Campaign fields to update.
	 * @return array|WP_Error Response array on success, WP_Error on failure.
	 */
	public function update_campaign( int $campaign_id, array $data ) {
		if ( $campaign_id <= 0 ) {
			return new WP_Error(
				'bcg_brevo_invalid_campaign_id',
				__( 'Invalid campaign ID provided.', 'brevo-campaign-generator' )
			);
		}

		$response = $this->request( 'PUT', 'emailCampaigns/' . absint( $campaign_id ), null, $data );

		if ( ! is_wp_error( $response ) ) {
			// Invalidate campaigns cache on update.
			delete_transient( 'bcg_brevo_campaigns' );
			delete_transient( 'bcg_stats_' . absint( $campaign_id ) );
		}

		return $response;
	}

	/**
	 * Send a campaign immediately.
	 *
	 * The campaign must already exist in Brevo and be in a sendable state
	 * (draft with valid content and recipients).
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id The Brevo campaign ID.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function send_campaign_now( int $campaign_id ) {
		if ( $campaign_id <= 0 ) {
			return new WP_Error(
				'bcg_brevo_invalid_campaign_id',
				__( 'Invalid campaign ID provided.', 'brevo-campaign-generator' )
			);
		}

		$response = $this->request(
			'POST',
			'emailCampaigns/' . absint( $campaign_id ) . '/sendNow'
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Invalidate caches after sending.
		delete_transient( 'bcg_brevo_campaigns' );
		delete_transient( 'bcg_stats_' . absint( $campaign_id ) );

		return true;
	}

	/**
	 * Schedule a campaign for a specific date and time.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $campaign_id The Brevo campaign ID.
	 * @param string $datetime    ISO 8601 datetime string (e.g. '2025-03-01T10:00:00Z').
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function schedule_campaign( int $campaign_id, string $datetime ) {
		if ( $campaign_id <= 0 ) {
			return new WP_Error(
				'bcg_brevo_invalid_campaign_id',
				__( 'Invalid campaign ID provided.', 'brevo-campaign-generator' )
			);
		}

		if ( empty( $datetime ) ) {
			return new WP_Error(
				'bcg_brevo_invalid_datetime',
				__( 'A valid scheduled datetime is required.', 'brevo-campaign-generator' )
			);
		}

		// Validate the datetime format.
		$timestamp = strtotime( $datetime );
		if ( false === $timestamp || $timestamp <= time() ) {
			return new WP_Error(
				'bcg_brevo_invalid_datetime',
				__( 'The scheduled datetime must be a valid future date.', 'brevo-campaign-generator' )
			);
		}

		// Format as ISO 8601 for Brevo.
		$iso_datetime = gmdate( 'Y-m-d\TH:i:s\Z', $timestamp );

		$response = $this->request(
			'PUT',
			'emailCampaigns/' . absint( $campaign_id ),
			null,
			array( 'scheduledAt' => $iso_datetime )
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Invalidate caches.
		delete_transient( 'bcg_brevo_campaigns' );
		delete_transient( 'bcg_stats_' . absint( $campaign_id ) );

		return true;
	}

	/**
	 * Retrieve a list of email campaigns from Brevo.
	 *
	 * Results are cached in a transient for 15 minutes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status        Optional. Filter by status: 'draft', 'sent', 'archive',
	 *                              'queued', 'suspended', or 'all'. Default 'all'.
	 * @param int    $limit         Optional. Maximum number of campaigns to return. Default 50.
	 * @param int    $offset        Optional. Pagination offset. Default 0.
	 * @param bool   $force_refresh Optional. Bypass transient cache. Default false.
	 * @return array|WP_Error Array with 'campaigns' and 'count' keys on success, WP_Error on failure.
	 */
	public function get_campaigns( string $status = 'all', int $limit = 50, int $offset = 0, bool $force_refresh = false ) {
		$cache_key    = 'bcg_brevo_campaigns';
		$variant_key  = $status . '_' . $limit . '_' . $offset;

		if ( ! $force_refresh ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached && is_array( $cached ) && isset( $cached[ $variant_key ] ) ) {
				return $cached[ $variant_key ];
			}
		}

		$query_params = array(
			'type'   => 'classic',
			'limit'  => min( absint( $limit ), 1000 ),
			'offset' => absint( $offset ),
		);

		// Brevo accepts 'status' as a filter — only add it for specific statuses.
		$valid_statuses = array( 'draft', 'sent', 'archive', 'queued', 'suspended' );
		if ( in_array( $status, $valid_statuses, true ) ) {
			$query_params['status'] = $status;
		}

		$response = $this->request( 'GET', 'emailCampaigns', $query_params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$result = array(
			'campaigns' => isset( $response['campaigns'] ) ? $response['campaigns'] : array(),
			'count'     => isset( $response['count'] ) ? (int) $response['count'] : 0,
		);

		// Store to transient cache under the specific query variant key.
		$cached_data = get_transient( $cache_key );
		if ( false === $cached_data || ! is_array( $cached_data ) ) {
			$cached_data = array();
		}
		$cached_data[ $variant_key ] = $result;
		set_transient( $cache_key, $cached_data, self::CAMPAIGNS_CACHE_TTL );

		return $result;
	}

	/**
	 * Retrieve a single email campaign by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id The Brevo campaign ID.
	 * @return array|WP_Error Campaign data array on success, WP_Error on failure.
	 */
	public function get_campaign( int $campaign_id ) {
		if ( $campaign_id <= 0 ) {
			return new WP_Error(
				'bcg_brevo_invalid_campaign_id',
				__( 'Invalid campaign ID provided.', 'brevo-campaign-generator' )
			);
		}

		return $this->request( 'GET', 'emailCampaigns/' . absint( $campaign_id ) );
	}

	/**
	 * Retrieve statistics for a specific email campaign.
	 *
	 * Results are cached in a transient for 15 minutes.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $campaign_id   The Brevo campaign ID.
	 * @param bool $force_refresh Optional. Bypass transient cache. Default false.
	 * @return array|WP_Error Campaign statistics array on success, WP_Error on failure.
	 */
	public function get_campaign_stats( int $campaign_id, bool $force_refresh = false ) {
		if ( $campaign_id <= 0 ) {
			return new WP_Error(
				'bcg_brevo_invalid_campaign_id',
				__( 'Invalid campaign ID provided.', 'brevo-campaign-generator' )
			);
		}

		$cache_key = 'bcg_stats_' . absint( $campaign_id );

		if ( ! $force_refresh ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		// Fetch the full campaign data which includes statistics.
		$campaign = $this->request( 'GET', 'emailCampaigns/' . absint( $campaign_id ) );

		if ( is_wp_error( $campaign ) ) {
			return $campaign;
		}

		// Extract the statistics block from the campaign response.
		$stats = array(
			'campaignId' => $campaign_id,
			'name'       => isset( $campaign['name'] ) ? $campaign['name'] : '',
			'status'     => isset( $campaign['status'] ) ? $campaign['status'] : '',
			'sentDate'   => isset( $campaign['sentDate'] ) ? $campaign['sentDate'] : null,
			'statistics' => isset( $campaign['statistics'] ) ? $campaign['statistics'] : array(),
			'recipients' => isset( $campaign['recipients'] ) ? $campaign['recipients'] : array(),
		);

		// Parse global statistics if available.
		if ( isset( $campaign['statistics']['globalStats'] ) ) {
			$global                   = $campaign['statistics']['globalStats'];
			$stats['globalStats'] = array(
				'uniqueClicks'    => isset( $global['uniqueClicks'] ) ? (int) $global['uniqueClicks'] : 0,
				'clickers'        => isset( $global['clickers'] ) ? (int) $global['clickers'] : 0,
				'complaints'      => isset( $global['complaints'] ) ? (int) $global['complaints'] : 0,
				'delivered'       => isset( $global['delivered'] ) ? (int) $global['delivered'] : 0,
				'sent'            => isset( $global['sent'] ) ? (int) $global['sent'] : 0,
				'softBounces'     => isset( $global['softBounces'] ) ? (int) $global['softBounces'] : 0,
				'hardBounces'     => isset( $global['hardBounces'] ) ? (int) $global['hardBounces'] : 0,
				'uniqueViews'     => isset( $global['uniqueViews'] ) ? (int) $global['uniqueViews'] : 0,
				'trackableViews'  => isset( $global['trackableViews'] ) ? (int) $global['trackableViews'] : 0,
				'unsubscriptions' => isset( $global['unsubscriptions'] ) ? (int) $global['unsubscriptions'] : 0,
				'viewed'          => isset( $global['viewed'] ) ? (int) $global['viewed'] : 0,
			);

			// Calculate rates.
			$sent = $stats['globalStats']['sent'];
			if ( $sent > 0 ) {
				$stats['globalStats']['openRate']  = round( ( $stats['globalStats']['uniqueViews'] / $sent ) * 100, 2 );
				$stats['globalStats']['clickRate'] = round( ( $stats['globalStats']['uniqueClicks'] / $sent ) * 100, 2 );
			} else {
				$stats['globalStats']['openRate']  = 0.0;
				$stats['globalStats']['clickRate'] = 0.0;
			}
		}

		set_transient( $cache_key, $stats, self::STATS_CACHE_TTL );

		return $stats;
	}

	// -------------------------------------------------------------------------
	// Public API Methods — Templates
	// -------------------------------------------------------------------------

	/**
	 * Create an email template in Brevo.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Template creation data. Expected keys:
	 *                    - 'templateName' (string) Required. Template name.
	 *                    - 'htmlContent'  (string) Required. Full HTML content.
	 *                    - 'subject'      (string) Required. Template subject line.
	 *                    - 'sender'       (array)  Optional. [ 'name' => '', 'email' => '' ].
	 *                    - 'isActive'     (bool)   Optional. Default true.
	 * @return array|WP_Error Created template data (includes 'id') on success, WP_Error on failure.
	 */
	public function create_template( array $data ) {
		$required_fields = array( 'templateName', 'htmlContent', 'subject' );

		foreach ( $required_fields as $field ) {
			if ( empty( $data[ $field ] ) ) {
				return new WP_Error(
					'bcg_brevo_missing_field',
					/* translators: %s: the missing field name */
					sprintf( __( 'Missing required template field: %s', 'brevo-campaign-generator' ), $field )
				);
			}
		}

		// Default to active template.
		if ( ! isset( $data['isActive'] ) ) {
			$data['isActive'] = true;
		}

		return $this->request( 'POST', 'smtp/templates', null, $data );
	}

	// -------------------------------------------------------------------------
	// Public API Methods — Testing
	// -------------------------------------------------------------------------

	/**
	 * Send a test email for a specific campaign.
	 *
	 * The campaign must exist in Brevo and have valid HTML content.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $campaign_id The Brevo campaign ID.
	 * @param string $email       The email address to send the test to.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function send_test_email( int $campaign_id, string $email ) {
		if ( $campaign_id <= 0 ) {
			return new WP_Error(
				'bcg_brevo_invalid_campaign_id',
				__( 'Invalid campaign ID provided.', 'brevo-campaign-generator' )
			);
		}

		if ( ! is_email( $email ) ) {
			return new WP_Error(
				'bcg_brevo_invalid_email',
				__( 'A valid email address is required to send a test.', 'brevo-campaign-generator' )
			);
		}

		$response = $this->request(
			'POST',
			'emailCampaigns/' . absint( $campaign_id ) . '/sendTest',
			null,
			array(
				'emailTo' => array( sanitize_email( $email ) ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * Test the API connection by calling the Brevo Account endpoint.
	 *
	 * This is used by the Settings page "Test Connection" button to verify
	 * that the stored API key is valid.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if the connection succeeds and the API key is valid, false otherwise.
	 */
	public function test_connection(): bool {
		$response = $this->request( 'GET', 'account' );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		// The account endpoint returns an email field on success.
		return isset( $response['email'] );
	}

	// -------------------------------------------------------------------------
	// Cache Helpers
	// -------------------------------------------------------------------------

	/**
	 * Clear all Brevo-related transient caches.
	 *
	 * Useful after settings changes or when the user explicitly requests
	 * a refresh of Brevo data.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		delete_transient( 'bcg_brevo_lists' );
		delete_transient( 'bcg_brevo_campaigns' );
		delete_transient( 'bcg_brevo_aggregate_stats' );

		// Clear individual stats caches — we fetch known campaign IDs from our DB.
		global $wpdb;
		$table_name = $wpdb->prefix . 'bcg_campaigns';

		// Only query if the table exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
		);

		if ( $table_exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$brevo_ids = $wpdb->get_col(
				"SELECT brevo_campaign_id FROM {$table_name} WHERE brevo_campaign_id IS NOT NULL"
			);

			if ( $brevo_ids ) {
				foreach ( $brevo_ids as $brevo_id ) {
					delete_transient( 'bcg_stats_' . absint( $brevo_id ) );
				}
			}
		}
	}

	/**
	 * Invalidate the contact lists cache.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function clear_lists_cache(): void {
		delete_transient( 'bcg_brevo_lists' );
	}

	/**
	 * Invalidate the campaigns cache.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function clear_campaigns_cache(): void {
		delete_transient( 'bcg_brevo_campaigns' );
	}

	/**
	 * Invalidate the stats cache for a specific campaign.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id The Brevo campaign ID.
	 * @return void
	 */
	public function clear_stats_cache( int $campaign_id ): void {
		delete_transient( 'bcg_stats_' . absint( $campaign_id ) );
	}

	// -------------------------------------------------------------------------
	// Convenience / Helper Methods
	// -------------------------------------------------------------------------

	/**
	 * Get account information from Brevo.
	 *
	 * Returns the full account object including plan details and remaining
	 * email credits.
	 *
	 * @since 1.0.0
	 *
	 * @return array|WP_Error Account data on success, WP_Error on failure.
	 */
	public function get_account() {
		return $this->request( 'GET', 'account' );
	}

	/**
	 * Build a full campaign creation payload with sensible defaults.
	 *
	 * Merges user-provided data with default sender info and campaign prefix
	 * from the plugin settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name         Campaign name.
	 * @param string $subject      Email subject line.
	 * @param string $html_content Full rendered HTML content.
	 * @param array  $list_ids     Array of Brevo list IDs.
	 * @param array  $extra        Optional. Additional payload fields.
	 * @return array Campaign creation payload ready for create_campaign().
	 */
	public function build_campaign_payload(
		string $name,
		string $subject,
		string $html_content,
		array $list_ids,
		array $extra = array()
	): array {
		$prefix = (string) get_option( 'bcg_brevo_campaign_prefix', '[WC]' );

		// Read sender from the new unified JSON option, fall back to legacy separate options.
		$sender_json  = get_option( 'bcg_brevo_sender', '' );
		$sender_data  = is_string( $sender_json ) ? json_decode( $sender_json, true ) : null;
		$sender_id    = ( is_array( $sender_data ) && ! empty( $sender_data['id'] ) )
			? (int) $sender_data['id']
			: 0;
		$sender_name  = ( is_array( $sender_data ) && ! empty( $sender_data['name'] ) )
			? (string) $sender_data['name']
			: (string) get_option( 'bcg_brevo_sender_name', get_bloginfo( 'name' ) );
		$sender_email = ( is_array( $sender_data ) && ! empty( $sender_data['email'] ) )
			? (string) $sender_data['email']
			: (string) get_option( 'bcg_brevo_sender_email', get_bloginfo( 'admin_email' ) );

		// Prepend the campaign prefix to the name if not already present.
		$prefixed_name = $name;
		if ( ! empty( $prefix ) && 0 !== strpos( $name, $prefix ) ) {
			$prefixed_name = trim( $prefix ) . ' ' . $name;
		}

		// Build sender array — Brevo requires EITHER id alone OR email+name, never both.
		if ( $sender_id > 0 ) {
			$sender_payload = array( 'id' => $sender_id );
		} else {
			$sender_payload = array(
				'name'  => sanitize_text_field( $sender_name ),
				'email' => sanitize_email( $sender_email ),
			);
		}

		$payload = array(
			'name'        => sanitize_text_field( $prefixed_name ),
			'subject'     => sanitize_text_field( $subject ),
			'sender'      => $sender_payload,
			'type'        => 'classic',
			'htmlContent' => $html_content,
			'recipients'  => array(
				'listIds' => array_map( 'absint', $list_ids ),
			),
		);

		// Merge any additional fields (e.g. scheduledAt, previewText, tag).
		if ( ! empty( $extra ) ) {
			$payload = array_merge( $payload, $extra );
		}

		return $payload;
	}

	/**
	 * Retrieve aggregate statistics across all sent campaigns.
	 *
	 * Fetches all sent campaigns and calculates totals and averages.
	 * Results are cached for 15 minutes.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $force_refresh Optional. Bypass transient cache. Default false.
	 * @return array|WP_Error Aggregate stats array on success, WP_Error on failure.
	 */
	public function get_aggregate_stats( bool $force_refresh = false ) {
		$cache_key = 'bcg_brevo_aggregate_stats';

		if ( ! $force_refresh ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$all_campaigns = array();
		$limit         = 50;
		$offset        = 0;

		// Paginate through all sent campaigns.
		do {
			$response = $this->request(
				'GET',
				'emailCampaigns',
				array(
					'type'   => 'classic',
					'status' => 'sent',
					'limit'  => $limit,
					'offset' => $offset,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$campaigns = isset( $response['campaigns'] ) ? $response['campaigns'] : array();
			$count     = isset( $response['count'] ) ? (int) $response['count'] : 0;

			$all_campaigns = array_merge( $all_campaigns, $campaigns );
			$offset       += $limit;
		} while ( $offset < $count );

		// Calculate aggregates.
		$total_campaigns    = count( $all_campaigns );
		$total_sent         = 0;
		$total_opens        = 0;
		$total_clicks       = 0;
		$total_delivered    = 0;
		$total_unsubscribes = 0;

		foreach ( $all_campaigns as $campaign ) {
			if ( isset( $campaign['statistics']['globalStats'] ) ) {
				$gs                  = $campaign['statistics']['globalStats'];
				$total_sent         += isset( $gs['sent'] ) ? (int) $gs['sent'] : 0;
				$total_opens        += isset( $gs['uniqueViews'] ) ? (int) $gs['uniqueViews'] : 0;
				$total_clicks       += isset( $gs['uniqueClicks'] ) ? (int) $gs['uniqueClicks'] : 0;
				$total_delivered    += isset( $gs['delivered'] ) ? (int) $gs['delivered'] : 0;
				$total_unsubscribes += isset( $gs['unsubscriptions'] ) ? (int) $gs['unsubscriptions'] : 0;
			}
		}

		$aggregate = array(
			'total_campaigns'    => $total_campaigns,
			'total_emails_sent'  => $total_sent,
			'total_delivered'    => $total_delivered,
			'total_opens'        => $total_opens,
			'total_clicks'       => $total_clicks,
			'total_unsubscribes' => $total_unsubscribes,
			'avg_open_rate'      => $total_sent > 0 ? round( ( $total_opens / $total_sent ) * 100, 2 ) : 0.0,
			'avg_click_rate'     => $total_sent > 0 ? round( ( $total_clicks / $total_sent ) * 100, 2 ) : 0.0,
		);

		set_transient( $cache_key, $aggregate, self::STATS_CACHE_TTL );

		return $aggregate;
	}

	// -------------------------------------------------------------------------
	// Private HTTP Client
	// -------------------------------------------------------------------------

	/**
	 * Make an authenticated HTTP request to the Brevo API.
	 *
	 * Handles request building, execution, response parsing, and error logging.
	 * All public methods delegate to this method for actual API communication.
	 *
	 * @since 1.0.0
	 *
	 * @param string     $method       HTTP method: 'GET', 'POST', 'PUT', 'DELETE'.
	 * @param string     $endpoint     API endpoint path relative to the base URL (e.g. 'emailCampaigns').
	 * @param array|null $query_params Optional. Query string parameters for GET requests.
	 * @param array|null $body         Optional. Request body (will be JSON-encoded) for POST/PUT requests.
	 * @return array|WP_Error Decoded response body on success, WP_Error on failure.
	 */
	private function request( string $method, string $endpoint, ?array $query_params = null, ?array $body = null ) {
		// Validate API key availability.
		if ( empty( $this->api_key ) ) {
			$error = new WP_Error(
				'bcg_brevo_no_api_key',
				__( 'Brevo API key is not configured. Please add your API key in Settings.', 'brevo-campaign-generator' )
			);
			$this->log_error( 'no_api_key', $endpoint, $error->get_error_message() );
			return $error;
		}

		// Build URL.
		$url = self::API_BASE_URL . ltrim( $endpoint, '/' );
		if ( ! empty( $query_params ) ) {
			$url = add_query_arg( $query_params, $url );
		}

		// Build request arguments.
		$args = array(
			'method'  => strtoupper( $method ),
			'timeout' => self::REQUEST_TIMEOUT,
			'headers' => array(
				'api-key'      => $this->api_key,
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			),
		);

		// Attach JSON body for POST and PUT requests.
		if ( null !== $body && in_array( $args['method'], array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		/**
		 * Filter the Brevo API request arguments before the request is sent.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $args     wp_remote_request() arguments.
		 * @param string $endpoint The API endpoint being called.
		 * @param string $method   The HTTP method.
		 */
		$args = apply_filters( 'bcg_brevo_request_args', $args, $endpoint, $method );

		// Execute the request.
		$response = wp_remote_request( $url, $args );

		// Handle WordPress HTTP errors (connection failures, timeouts, etc.).
		if ( is_wp_error( $response ) ) {
			$this->log_error( $method, $endpoint, $response->get_error_message() );
			return new WP_Error(
				'bcg_brevo_request_failed',
				sprintf(
					/* translators: %s: the underlying error message */
					__( 'Brevo API request failed: %s', 'brevo-campaign-generator' ),
					$response->get_error_message()
				),
				array(
					'endpoint' => $endpoint,
					'method'   => $method,
				)
			);
		}

		// Parse the response.
		$status_code   = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Decode JSON response body.
		$decoded = json_decode( $response_body, true );

		// Handle HTTP error status codes.
		if ( $status_code < 200 || $status_code >= 300 ) {
			$error_message = $this->extract_error_message( $decoded, $status_code );

			$this->log_error( $method, $endpoint, $error_message, $status_code, $decoded );

			return new WP_Error(
				'bcg_brevo_api_error',
				$error_message,
				array(
					'status_code' => $status_code,
					'endpoint'    => $endpoint,
					'method'      => $method,
					'response'    => $decoded,
				)
			);
		}

		// Some successful Brevo responses return 204 No Content (e.g. sendNow, sendTest).
		if ( 204 === $status_code || empty( $response_body ) ) {
			return array( 'success' => true );
		}

		// If JSON decode failed on a 2xx response, return an error.
		if ( null === $decoded && JSON_ERROR_NONE !== json_last_error() ) {
			$json_error_msg = sprintf(
				/* translators: %s: the JSON error message */
				__( 'Failed to parse Brevo API response: %s', 'brevo-campaign-generator' ),
				json_last_error_msg()
			);
			$this->log_error( $method, $endpoint, $json_error_msg, $status_code );
			return new WP_Error(
				'bcg_brevo_json_error',
				$json_error_msg,
				array(
					'status_code' => $status_code,
					'endpoint'    => $endpoint,
					'raw_body'    => substr( $response_body, 0, 500 ),
				)
			);
		}

		return $decoded;
	}

	/**
	 * Extract a human-readable error message from a Brevo API error response.
	 *
	 * Brevo error responses typically include a 'message' field. This method
	 * handles various response formats gracefully.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $decoded     The decoded JSON response body.
	 * @param int   $status_code The HTTP status code.
	 * @return string A descriptive error message.
	 */
	private function extract_error_message( $decoded, int $status_code ): string {
		// Try standard Brevo error format.
		if ( is_array( $decoded ) && isset( $decoded['message'] ) ) {
			return sprintf(
				/* translators: 1: HTTP status code, 2: Brevo error message */
				__( 'Brevo API error (%1$d): %2$s', 'brevo-campaign-generator' ),
				$status_code,
				sanitize_text_field( $decoded['message'] )
			);
		}

		// Try the 'code' + 'message' format some endpoints use.
		if ( is_array( $decoded ) && isset( $decoded['code'] ) ) {
			$code_message = isset( $decoded['message'] ) ? $decoded['message'] : $decoded['code'];
			return sprintf(
				/* translators: 1: HTTP status code, 2: Brevo error code/message */
				__( 'Brevo API error (%1$d): %2$s', 'brevo-campaign-generator' ),
				$status_code,
				sanitize_text_field( (string) $code_message )
			);
		}

		// Fallback for non-JSON or unexpected responses.
		$status_messages = array(
			400 => __( 'Bad request — the data sent to Brevo was invalid.', 'brevo-campaign-generator' ),
			401 => __( 'Unauthorised — your Brevo API key is invalid or expired.', 'brevo-campaign-generator' ),
			403 => __( 'Forbidden — you do not have permission for this action.', 'brevo-campaign-generator' ),
			404 => __( 'Not found — the requested resource does not exist in Brevo.', 'brevo-campaign-generator' ),
			405 => __( 'Method not allowed for this endpoint.', 'brevo-campaign-generator' ),
			429 => __( 'Rate limit exceeded — please wait and try again.', 'brevo-campaign-generator' ),
			500 => __( 'Brevo internal server error — please try again later.', 'brevo-campaign-generator' ),
		);

		if ( isset( $status_messages[ $status_code ] ) ) {
			return $status_messages[ $status_code ];
		}

		return sprintf(
			/* translators: %d: HTTP status code */
			__( 'Brevo API returned unexpected status code: %d', 'brevo-campaign-generator' ),
			$status_code
		);
	}

	// -------------------------------------------------------------------------
	// Error Logging
	// -------------------------------------------------------------------------

	/**
	 * Log an error to the bcg_error_log WordPress option.
	 *
	 * Maintains a FIFO log of the most recent errors (max 50 entries). Each
	 * entry includes a timestamp, the HTTP method, endpoint, error message,
	 * and optional status code and response data.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $method      The HTTP method or error context.
	 * @param string   $endpoint    The API endpoint that was called.
	 * @param string   $message     The error message.
	 * @param int|null $status_code Optional. HTTP status code.
	 * @param mixed    $response    Optional. Response data for debugging.
	 * @return void
	 */
	private function log_error( string $method, string $endpoint, string $message, ?int $status_code = null, $response = null ): void {
		$log = get_option( 'bcg_error_log', array() );

		if ( ! is_array( $log ) ) {
			$log = array();
		}

		$entry = array(
			'timestamp'   => current_time( 'mysql' ),
			'service'     => 'brevo',
			'method'      => $method,
			'endpoint'    => $endpoint,
			'message'     => $message,
			'status_code' => $status_code,
		);

		// Include a truncated response for debugging, but avoid storing huge payloads.
		if ( null !== $response ) {
			$response_string = is_array( $response ) ? wp_json_encode( $response ) : (string) $response;
			$entry['response_excerpt'] = substr( $response_string, 0, 500 );
		}

		// Add to the end of the log.
		$log[] = $entry;

		// Trim to the maximum number of entries (FIFO).
		if ( count( $log ) > self::MAX_ERROR_LOG_ENTRIES ) {
			$log = array_slice( $log, -self::MAX_ERROR_LOG_ENTRIES );
		}

		update_option( 'bcg_error_log', $log, false );
	}
}
