<?php
/**
 * Stats handler.
 *
 * Manages the Brevo campaign statistics dashboard including data fetching,
 * formatting, caching, and AJAX endpoints for the stats admin page.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Stats
 *
 * Fetches and displays campaign statistics from the Brevo API with
 * transient-based caching and filtering support.
 *
 * @since 1.0.0
 */
class BCG_Stats {

	/**
	 * Transient key for aggregate stats.
	 *
	 * @var string
	 */
	const CACHE_KEY_AGGREGATE = 'bcg_brevo_aggregate_stats';

	/**
	 * Transient key prefix for campaigns list cache.
	 *
	 * @var string
	 */
	const CACHE_KEY_CAMPAIGNS = 'bcg_stats_campaigns';

	/**
	 * Cache TTL in seconds (15 minutes).
	 *
	 * @var int
	 */
	const CACHE_TTL = 15 * MINUTE_IN_SECONDS;

	/**
	 * Constructor.
	 *
	 * Registers AJAX hooks for stats operations.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_bcg_get_stats', array( $this, 'handle_get_stats' ) );
		add_action( 'wp_ajax_bcg_refresh_stats', array( $this, 'handle_refresh_stats' ) );
		add_action( 'wp_ajax_bcg_get_campaign_detail', array( $this, 'handle_get_campaign_detail' ) );
	}

	/**
	 * Get aggregate and campaign stats data with optional filters.
	 *
	 * Fetches campaigns from Brevo API, calculates aggregate metrics,
	 * and returns a structured array suitable for rendering.
	 *
	 * @since 1.0.0
	 *
	 * @param array $filters {
	 *     Optional. Filter parameters.
	 *
	 *     @type string $status    Campaign status filter ('all', 'sent', 'draft', 'queued'). Default 'all'.
	 *     @type string $date_from Start date in Y-m-d format. Default empty.
	 *     @type string $date_to   End date in Y-m-d format. Default empty.
	 *     @type int    $limit     Number of campaigns to fetch. Default 50.
	 *     @type int    $offset    Pagination offset. Default 0.
	 * }
	 * @param bool  $force_refresh Whether to bypass transient cache. Default false.
	 * @return array|WP_Error Formatted stats data or WP_Error on failure.
	 */
	public function get_stats_data( array $filters = array(), bool $force_refresh = false ) {
		$defaults = array(
			'status'    => 'all',
			'date_from' => '',
			'date_to'   => '',
			'limit'     => 50,
			'offset'    => 0,
		);

		$filters   = wp_parse_args( $filters, $defaults );
		$cache_key = self::CACHE_KEY_CAMPAIGNS . '_' . md5( wp_json_encode( $filters ) );

		if ( ! $force_refresh ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$brevo = new BCG_Brevo();

		// Fetch aggregate stats.
		$aggregate = $brevo->get_aggregate_stats( $force_refresh );

		if ( is_wp_error( $aggregate ) ) {
			return $aggregate;
		}

		// Fetch campaigns with status filter.
		$status = in_array( $filters['status'], array( 'sent', 'draft', 'queued', 'archive', 'suspended' ), true )
			? $filters['status']
			: 'all';

		$campaigns_response = $brevo->get_campaigns(
			$status,
			absint( $filters['limit'] ),
			absint( $filters['offset'] ),
			$force_refresh
		);

		if ( is_wp_error( $campaigns_response ) ) {
			return $campaigns_response;
		}

		$campaigns = isset( $campaigns_response['campaigns'] ) ? $campaigns_response['campaigns'] : array();
		$total     = isset( $campaigns_response['count'] ) ? (int) $campaigns_response['count'] : 0;

		// Apply date filters client-side.
		if ( ! empty( $filters['date_from'] ) || ! empty( $filters['date_to'] ) ) {
			$campaigns = $this->filter_by_date( $campaigns, $filters['date_from'], $filters['date_to'] );
		}

		// Format campaign rows.
		$formatted_campaigns = array();
		foreach ( $campaigns as $campaign ) {
			$formatted_campaigns[] = $this->format_campaign_row( $campaign );
		}

		$result = array(
			'aggregate'  => array(
				'total_campaigns'   => (int) ( $aggregate['total_campaigns'] ?? 0 ),
				'avg_open_rate'     => $this->format_rate( $aggregate['avg_open_rate'] ?? 0 ),
				'avg_open_rate_raw' => (float) ( $aggregate['avg_open_rate'] ?? 0 ),
				'avg_click_rate'    => $this->format_rate( $aggregate['avg_click_rate'] ?? 0 ),
				'avg_click_rate_raw' => (float) ( $aggregate['avg_click_rate'] ?? 0 ),
				'total_emails_sent' => (int) ( $aggregate['total_emails_sent'] ?? 0 ),
			),
			'campaigns'  => $formatted_campaigns,
			'total'      => $total,
			'limit'      => absint( $filters['limit'] ),
			'offset'     => absint( $filters['offset'] ),
			'cached_at'  => current_time( 'mysql' ),
		);

		set_transient( $cache_key, $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Format a decimal value as a percentage string.
	 *
	 * @since 1.0.0
	 *
	 * @param float|int $value The decimal or percentage value.
	 * @return string Formatted percentage (e.g. "23.45%").
	 */
	public function format_rate( $value ): string {
		$value = (float) $value;

		return number_format( $value, 2 ) . '%';
	}

	/**
	 * Format a campaign from the Brevo API response into a table-ready row.
	 *
	 * @since 1.0.0
	 *
	 * @param array $campaign Raw campaign data from Brevo API.
	 * @return array Formatted campaign row.
	 */
	private function format_campaign_row( array $campaign ): array {
		$stats = isset( $campaign['statistics']['globalStats'] ) ? $campaign['statistics']['globalStats'] : array();

		$sent         = (int) ( $stats['sent'] ?? 0 );
		$unique_views = (int) ( $stats['uniqueViews'] ?? 0 );
		$unique_clicks = (int) ( $stats['uniqueClicks'] ?? 0 );
		$unsubscribes = (int) ( $stats['unsubscriptions'] ?? 0 );
		$delivered    = (int) ( $stats['delivered'] ?? 0 );

		$open_rate  = $sent > 0 ? round( ( $unique_views / $sent ) * 100, 2 ) : 0;
		$click_rate = $sent > 0 ? round( ( $unique_clicks / $sent ) * 100, 2 ) : 0;

		$sent_date = '';
		if ( ! empty( $campaign['sentDate'] ) ) {
			$timestamp = strtotime( $campaign['sentDate'] );
			if ( false !== $timestamp ) {
				$sent_date = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
			}
		}

		return array(
			'id'            => (int) ( $campaign['id'] ?? 0 ),
			'name'          => sanitize_text_field( $campaign['name'] ?? '' ),
			'subject'       => sanitize_text_field( $campaign['subject'] ?? '' ),
			'status'        => sanitize_text_field( $campaign['status'] ?? 'draft' ),
			'sent_date'     => $sent_date,
			'sent_date_raw' => $campaign['sentDate'] ?? '',
			'recipients'    => $sent,
			'delivered'     => $delivered,
			'opens'         => $unique_views,
			'open_rate'     => $this->format_rate( $open_rate ),
			'open_rate_raw' => $open_rate,
			'clicks'        => $unique_clicks,
			'click_rate'    => $this->format_rate( $click_rate ),
			'click_rate_raw' => $click_rate,
			'unsubscribes'  => $unsubscribes,
			'soft_bounces'  => (int) ( $stats['softBounces'] ?? 0 ),
			'hard_bounces'  => (int) ( $stats['hardBounces'] ?? 0 ),
		);
	}

	/**
	 * Filter campaigns array by date range.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $campaigns Array of campaign data.
	 * @param string $date_from Start date in Y-m-d format.
	 * @param string $date_to   End date in Y-m-d format.
	 * @return array Filtered campaigns.
	 */
	private function filter_by_date( array $campaigns, string $date_from, string $date_to ): array {
		return array_filter(
			$campaigns,
			function ( $campaign ) use ( $date_from, $date_to ) {
				$sent_date = $campaign['sentDate'] ?? $campaign['createdAt'] ?? '';
				if ( empty( $sent_date ) ) {
					return true;
				}

				$timestamp = strtotime( $sent_date );
				if ( false === $timestamp ) {
					return true;
				}

				$campaign_date = gmdate( 'Y-m-d', $timestamp );

				if ( ! empty( $date_from ) && $campaign_date < $date_from ) {
					return false;
				}

				if ( ! empty( $date_to ) && $campaign_date > $date_to ) {
					return false;
				}

				return true;
			}
		);
	}

	/**
	 * Get the timestamp of the last stats cache update.
	 *
	 * @since 1.0.0
	 *
	 * @return string|false Human-readable time ago string, or false if no cache.
	 */
	public function get_cache_age(): string|false {
		$cached = get_transient( self::CACHE_KEY_AGGREGATE );

		if ( false === $cached ) {
			return false;
		}

		// We can not determine exact age from transient, use the stored time.
		$cache_time = get_option( '_transient_timeout_' . self::CACHE_KEY_AGGREGATE, 0 );

		if ( $cache_time > 0 ) {
			$set_time    = (int) $cache_time - self::CACHE_TTL;
			$minutes_ago = max( 0, round( ( time() - $set_time ) / 60 ) );

			if ( $minutes_ago < 1 ) {
				return __( 'just now', 'brevo-campaign-generator' );
			}

			return sprintf(
				/* translators: %d: number of minutes */
				_n( '%d minute ago', '%d minutes ago', $minutes_ago, 'brevo-campaign-generator' ),
				$minutes_ago
			);
		}

		return false;
	}

	/**
	 * Clear all stats caches.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		$brevo = new BCG_Brevo();
		$brevo->clear_cache();

		// Clear our own filtered caches by deleting transients with our prefix.
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . self::CACHE_KEY_CAMPAIGNS . '%',
				'_transient_timeout_' . self::CACHE_KEY_CAMPAIGNS . '%'
			)
		);
	}

	// ─── AJAX Handlers ──────────────────────────────────────────────────

	/**
	 * Handle AJAX request to fetch stats data.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_get_stats(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) )
			);
		}

		$filters = array(
			'status'    => isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'all',
			'date_from' => isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '',
			'date_to'   => isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '',
			'limit'     => isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 50,
			'offset'    => isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0,
		);

		$data = $this->get_stats_data( $filters );

		if ( is_wp_error( $data ) ) {
			wp_send_json_error(
				array( 'message' => $data->get_error_message() )
			);
		}

		wp_send_json_success( $data );
	}

	/**
	 * Handle AJAX request to force-refresh stats (clears cache first).
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_refresh_stats(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) )
			);
		}

		$this->clear_cache();

		$filters = array(
			'status'    => isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'all',
			'date_from' => isset( $_POST['date_from'] ) ? sanitize_text_field( wp_unslash( $_POST['date_from'] ) ) : '',
			'date_to'   => isset( $_POST['date_to'] ) ? sanitize_text_field( wp_unslash( $_POST['date_to'] ) ) : '',
			'limit'     => isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 50,
			'offset'    => isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0,
		);

		$data = $this->get_stats_data( $filters, true );

		if ( is_wp_error( $data ) ) {
			wp_send_json_error(
				array( 'message' => $data->get_error_message() )
			);
		}

		wp_send_json_success( $data );
	}

	/**
	 * Handle AJAX request to fetch detailed stats for a single campaign.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_get_campaign_detail(): void {
		check_ajax_referer( 'bcg_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to perform this action.', 'brevo-campaign-generator' ) )
			);
		}

		$campaign_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;

		if ( ! $campaign_id ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid campaign ID.', 'brevo-campaign-generator' ) )
			);
		}

		$brevo = new BCG_Brevo();
		$stats = $brevo->get_campaign_stats( $campaign_id );

		if ( is_wp_error( $stats ) ) {
			wp_send_json_error(
				array( 'message' => $stats->get_error_message() )
			);
		}

		wp_send_json_success( $stats );
	}
}
