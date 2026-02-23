<?php
/**
 * WooCommerce coupon generator for campaigns.
 *
 * Creates and manages WooCommerce coupons that are attached to email
 * campaigns. Each coupon is auto-generated with a unique code, a discount
 * type and value, an expiry date, and usage limits.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Coupon
 *
 * Handles WooCommerce coupon creation and deletion for email campaigns.
 *
 * @since 1.0.0
 */
class BCG_Coupon {

	/**
	 * Length of the random portion of generated coupon codes.
	 *
	 * @var int
	 */
	const RANDOM_CODE_LENGTH = 6;

	/**
	 * Characters used to generate random coupon codes.
	 *
	 * Excludes easily confused characters (0, O, I, l, 1).
	 *
	 * @var string
	 */
	const CODE_CHARACTERS = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

	/**
	 * Create a WooCommerce coupon for a campaign.
	 *
	 * Generates a unique coupon code in the format {PREFIX}-{RANDOM6},
	 * creates the coupon via the WC_Coupon class, and updates the campaign
	 * record with the coupon code.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $campaign_id    The campaign ID to associate the coupon with.
	 * @param float  $discount_value The discount amount (percentage or fixed).
	 * @param string $discount_type  The discount type: 'percent' or 'fixed_cart'.
	 * @param int    $expiry_days    Number of days from today until the coupon expires.
	 * @param string $prefix         Optional. Custom prefix for the coupon code. Default 'SALE'.
	 * @return string|WP_Error The generated coupon code on success, WP_Error on failure.
	 */
	public function create_coupon(
		int $campaign_id,
		float $discount_value,
		string $discount_type = 'percent',
		int $expiry_days = 7,
		string $prefix = ''
	): string|\WP_Error {
		// Validate inputs.
		$validation = $this->validate_coupon_inputs( $discount_value, $discount_type, $expiry_days );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Normalise the discount type.
		$discount_type = $this->normalise_discount_type( $discount_type );

		// Use default prefix if not provided.
		if ( empty( $prefix ) ) {
			$prefix = 'SALE';
		}

		// Sanitise the prefix: uppercase, alphanumeric, dashes allowed.
		$prefix = strtoupper( preg_replace( '/[^A-Za-z0-9\-]/', '', sanitize_text_field( $prefix ) ) );

		// Generate a unique coupon code.
		$coupon_code = $this->generate_unique_code( $prefix );

		if ( is_wp_error( $coupon_code ) ) {
			return $coupon_code;
		}

		// Create the WooCommerce coupon.
		$coupon = new WC_Coupon();

		$coupon->set_code( $coupon_code );
		$coupon->set_discount_type( $discount_type );
		$coupon->set_amount( $discount_value );
		$coupon->set_individual_use( true );
		$coupon->set_usage_limit_per_user( 1 );
		$coupon->set_description(
			sprintf(
				/* translators: %d: campaign ID */
				__( 'Auto-generated coupon for campaign #%d', 'brevo-campaign-generator' ),
				$campaign_id
			)
		);

		// Set expiry date.
		$expiry_date = $this->calculate_expiry_date( $expiry_days );
		$coupon->set_date_expires( $expiry_date );

		// Store the campaign ID in coupon meta for reverse lookup.
		$coupon->add_meta_data( '_bcg_campaign_id', $campaign_id, true );

		// Save the coupon.
		$coupon_id = $coupon->save();

		if ( ! $coupon_id || $coupon_id <= 0 ) {
			return new WP_Error(
				'bcg_coupon_save_failed',
				__( 'Failed to save the WooCommerce coupon. Please try again.', 'brevo-campaign-generator' )
			);
		}

		// Update the campaign record with the coupon details.
		$this->update_campaign_coupon( $campaign_id, $coupon_code, $discount_value, $discount_type );

		/**
		 * Fires after a campaign coupon has been successfully created.
		 *
		 * @since 1.0.0
		 *
		 * @param string $coupon_code    The generated coupon code.
		 * @param int    $coupon_id      The WooCommerce coupon post ID.
		 * @param int    $campaign_id    The campaign ID.
		 * @param float  $discount_value The discount amount.
		 * @param string $discount_type  The discount type.
		 */
		do_action( 'bcg_coupon_created', $coupon_code, $coupon_id, $campaign_id, $discount_value, $discount_type );

		return $coupon_code;
	}

	/**
	 * Delete the WooCommerce coupon associated with a campaign.
	 *
	 * Looks up the coupon code from the campaign record, finds the
	 * corresponding WooCommerce coupon post, and deletes it permanently.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id The campaign ID whose coupon should be deleted.
	 * @return bool True if the coupon was deleted or did not exist, false on failure.
	 */
	public function delete_campaign_coupon( int $campaign_id ): bool {
		global $wpdb;

		$campaigns_table = $wpdb->prefix . 'bcg_campaigns';

		// Get the coupon code from the campaign.
		$coupon_code = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT coupon_code FROM {$campaigns_table} WHERE id = %d",
				$campaign_id
			)
		);

		if ( empty( $coupon_code ) ) {
			// No coupon associated — nothing to delete.
			return true;
		}

		// Find the WooCommerce coupon by code.
		$coupon_id = wc_get_coupon_id_by_code( $coupon_code );

		if ( $coupon_id > 0 ) {
			// Verify this coupon belongs to our campaign before deleting.
			$stored_campaign_id = get_post_meta( $coupon_id, '_bcg_campaign_id', true );

			if ( (int) $stored_campaign_id === $campaign_id || empty( $stored_campaign_id ) ) {
				$deleted = wp_delete_post( $coupon_id, true );

				if ( ! $deleted ) {
					return false;
				}
			}
		}

		// Clear the coupon fields from the campaign record.
		$wpdb->update(
			$campaigns_table,
			array(
				'coupon_code'     => null,
				'coupon_discount' => null,
				'coupon_type'     => 'percent',
			),
			array( 'id' => $campaign_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		/**
		 * Fires after a campaign coupon has been deleted.
		 *
		 * @since 1.0.0
		 *
		 * @param int    $campaign_id The campaign ID.
		 * @param string $coupon_code The coupon code that was deleted.
		 */
		do_action( 'bcg_coupon_deleted', $campaign_id, $coupon_code );

		return true;
	}

	/**
	 * Get the WooCommerce coupon object for a campaign.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id The campaign ID.
	 * @return WC_Coupon|null The coupon object or null if not found.
	 */
	public function get_campaign_coupon( int $campaign_id ): ?WC_Coupon {
		global $wpdb;

		$campaigns_table = $wpdb->prefix . 'bcg_campaigns';

		$coupon_code = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT coupon_code FROM {$campaigns_table} WHERE id = %d",
				$campaign_id
			)
		);

		if ( empty( $coupon_code ) ) {
			return null;
		}

		$coupon_id = wc_get_coupon_id_by_code( $coupon_code );

		if ( $coupon_id <= 0 ) {
			return null;
		}

		return new WC_Coupon( $coupon_id );
	}

	// ─── Private Helper Methods ───────────────────────────────────────

	/**
	 * Generate a unique coupon code.
	 *
	 * Creates a code in the format {PREFIX}-{RANDOM6} and verifies that
	 * no existing WooCommerce coupon uses the same code. Makes up to 10
	 * attempts before giving up.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prefix The code prefix.
	 * @return string|WP_Error The unique coupon code or WP_Error on failure.
	 */
	private function generate_unique_code( string $prefix ): string|\WP_Error {
		$max_attempts = 10;

		for ( $i = 0; $i < $max_attempts; $i++ ) {
			$random_part = $this->generate_random_string( self::RANDOM_CODE_LENGTH );
			$code        = $prefix . '-' . $random_part;

			// Check if this code already exists.
			$existing_id = wc_get_coupon_id_by_code( $code );

			if ( 0 === $existing_id ) {
				return $code;
			}
		}

		return new WP_Error(
			'bcg_coupon_code_generation_failed',
			__( 'Failed to generate a unique coupon code after multiple attempts. Please try again.', 'brevo-campaign-generator' )
		);
	}

	/**
	 * Generate a random alphanumeric string.
	 *
	 * Uses a character set that excludes easily confused characters
	 * (0, O, I, l, 1) for better readability.
	 *
	 * @since 1.0.0
	 *
	 * @param int $length The desired string length.
	 * @return string The random string.
	 */
	private function generate_random_string( int $length ): string {
		$chars      = self::CODE_CHARACTERS;
		$chars_len  = strlen( $chars );
		$result     = '';

		for ( $i = 0; $i < $length; $i++ ) {
			$result .= $chars[ wp_rand( 0, $chars_len - 1 ) ];
		}

		return $result;
	}

	/**
	 * Validate coupon creation inputs.
	 *
	 * @since 1.0.0
	 *
	 * @param float  $discount_value The discount amount.
	 * @param string $discount_type  The discount type.
	 * @param int    $expiry_days    Days until expiry.
	 * @return true|WP_Error True if valid, WP_Error otherwise.
	 */
	private function validate_coupon_inputs( float $discount_value, string $discount_type, int $expiry_days ): bool|\WP_Error {
		if ( $discount_value <= 0 ) {
			return new WP_Error(
				'bcg_coupon_invalid_value',
				__( 'Discount value must be greater than zero.', 'brevo-campaign-generator' )
			);
		}

		$normalised_type = $this->normalise_discount_type( $discount_type );

		if ( 'percent' === $normalised_type && $discount_value > 100 ) {
			return new WP_Error(
				'bcg_coupon_invalid_percent',
				__( 'Percentage discount cannot exceed 100%.', 'brevo-campaign-generator' )
			);
		}

		if ( $expiry_days < 1 ) {
			return new WP_Error(
				'bcg_coupon_invalid_expiry',
				__( 'Coupon expiry must be at least 1 day.', 'brevo-campaign-generator' )
			);
		}

		return true;
	}

	/**
	 * Normalise the discount type string.
	 *
	 * Accepts 'percent', 'percentage', 'fixed', 'fixed_cart' and returns
	 * the WooCommerce-compatible discount type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type The input discount type.
	 * @return string The normalised discount type.
	 */
	private function normalise_discount_type( string $type ): string {
		$type = strtolower( trim( $type ) );

		$type_map = array(
			'percent'    => 'percent',
			'percentage' => 'percent',
			'fixed'      => 'fixed_cart',
			'fixed_cart' => 'fixed_cart',
		);

		return isset( $type_map[ $type ] ) ? $type_map[ $type ] : 'percent';
	}

	/**
	 * Calculate the coupon expiry timestamp.
	 *
	 * @since 1.0.0
	 *
	 * @param int $expiry_days Number of days from today.
	 * @return int Unix timestamp for the expiry date (end of day).
	 */
	private function calculate_expiry_date( int $expiry_days ): int {
		$expiry_date = strtotime( '+' . absint( $expiry_days ) . ' days midnight' );

		// Set to end of day (23:59:59).
		return $expiry_date + DAY_IN_SECONDS - 1;
	}

	/**
	 * Update the campaign record with coupon details.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $campaign_id    The campaign ID.
	 * @param string $coupon_code    The generated coupon code.
	 * @param float  $discount_value The discount amount.
	 * @param string $discount_type  The discount type.
	 * @return void
	 */
	private function update_campaign_coupon( int $campaign_id, string $coupon_code, float $discount_value, string $discount_type ): void {
		global $wpdb;

		$campaigns_table = $wpdb->prefix . 'bcg_campaigns';

		$wpdb->update(
			$campaigns_table,
			array(
				'coupon_code'     => $coupon_code,
				'coupon_discount' => $discount_value,
				'coupon_type'     => $discount_type,
			),
			array( 'id' => $campaign_id ),
			array( '%s', '%f', '%s' ),
			array( '%d' )
		);
	}
}
