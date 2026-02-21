<?php
/**
 * Campaign management class.
 *
 * Provides CRUD operations for campaigns and their associated products.
 * Handles creation, updating, retrieval, deletion, product management,
 * reordering, and template storage for the bcg_campaigns and
 * bcg_campaign_products database tables.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Campaign
 *
 * Core campaign CRUD and product association logic.
 *
 * @since 1.0.0
 */
class BCG_Campaign {

	/**
	 * Campaigns table name (without prefix).
	 *
	 * @var string
	 */
	const CAMPAIGNS_TABLE = 'bcg_campaigns';

	/**
	 * Campaign products table name (without prefix).
	 *
	 * @var string
	 */
	const PRODUCTS_TABLE = 'bcg_campaign_products';

	/**
	 * Valid campaign statuses.
	 *
	 * @var array
	 */
	const VALID_STATUSES = array( 'draft', 'ready', 'sent', 'scheduled' );

	/**
	 * Valid coupon types.
	 *
	 * @var array
	 */
	const VALID_COUPON_TYPES = array( 'percent', 'fixed_cart' );

	/**
	 * Columns allowed for update operations on the campaigns table.
	 *
	 * @var array
	 */
	const UPDATABLE_CAMPAIGN_FIELDS = array(
		'title',
		'status',
		'brevo_campaign_id',
		'subject',
		'preview_text',
		'main_image_url',
		'main_headline',
		'main_description',
		'coupon_code',
		'coupon_discount',
		'coupon_type',
		'template_html',
		'template_settings',
		'mailing_list_id',
		'scheduled_at',
		'sent_at',
		'builder_type',
		'sections_json',
		'section_template_id',
	);

	/**
	 * Columns allowed for update operations on the products table.
	 *
	 * @var array
	 */
	const UPDATABLE_PRODUCT_FIELDS = array(
		'sort_order',
		'ai_headline',
		'ai_short_desc',
		'custom_headline',
		'custom_short_desc',
		'generated_image_url',
		'use_product_image',
		'show_buy_button',
	);

	// ─── Campaign CRUD ────────────────────────────────────────────────

	/**
	 * Create a new draft campaign.
	 *
	 * Inserts a new row into the bcg_campaigns table with the provided
	 * data and a status of 'draft'. At minimum, a title is required.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data {
	 *     Campaign data.
	 *
	 *     @type string $title           Required. Campaign title.
	 *     @type string $subject         Email subject line.
	 *     @type string $preview_text    Email preview text.
	 *     @type string $main_headline   Campaign headline.
	 *     @type string $main_description Campaign description.
	 *     @type string $main_image_url  URL to the main campaign image.
	 *     @type string $coupon_code     Coupon code.
	 *     @type float  $coupon_discount Coupon discount value.
	 *     @type string $coupon_type     'percent' or 'fixed_cart'.
	 *     @type string $template_html   Full HTML template.
	 *     @type string $template_settings JSON template settings.
	 *     @type string $mailing_list_id Brevo mailing list ID.
	 * }
	 * @return int|WP_Error The new campaign ID on success, WP_Error on failure.
	 */
	public function create_draft( array $data ): int|\WP_Error {
		global $wpdb;

		// Validate required fields.
		if ( empty( $data['title'] ) ) {
			return new WP_Error(
				'bcg_campaign_missing_title',
				__( 'Campaign title is required.', 'brevo-campaign-generator' )
			);
		}

		$table = $wpdb->prefix . self::CAMPAIGNS_TABLE;

		// Build the insert data array with sanitised values.
		$insert_data = array(
			'title'      => sanitize_text_field( $data['title'] ),
			'status'     => 'draft',
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		);

		$format = array( '%s', '%s', '%s', '%s' );

		// Add optional fields if provided.
		$optional_text_fields = array(
			'subject',
			'preview_text',
			'main_image_url',
			'main_headline',
			'coupon_code',
			'mailing_list_id',
		);

		foreach ( $optional_text_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$insert_data[ $field ] = sanitize_text_field( $data[ $field ] );
				$format[]              = '%s';
			}
		}

		// Main description allows HTML.
		if ( isset( $data['main_description'] ) ) {
			$insert_data['main_description'] = wp_kses_post( $data['main_description'] );
			$format[]                        = '%s';
		}

		// Template HTML — stored as-is (full email HTML).
		if ( isset( $data['template_html'] ) ) {
			$insert_data['template_html'] = $data['template_html'];
			$format[]                     = '%s';
		}

		// Template settings — must be valid JSON.
		if ( isset( $data['template_settings'] ) ) {
			$settings = $data['template_settings'];
			// Accept both arrays and JSON strings.
			if ( is_array( $settings ) ) {
				$settings = wp_json_encode( $settings );
			}
			$insert_data['template_settings'] = $settings;
			$format[]                         = '%s';
		}

		// Builder type — 'flat' or 'sections'.
		if ( isset( $data['builder_type'] ) ) {
			$insert_data['builder_type'] = in_array( $data['builder_type'], array( 'flat', 'sections' ), true ) ? $data['builder_type'] : 'flat';
			$format[]                    = '%s';
		}

		// Sections JSON — stored as-is (full JSON).
		if ( isset( $data['sections_json'] ) ) {
			$insert_data['sections_json'] = $data['sections_json'];
			$format[]                     = '%s';
		}

		// Section template ID — reference to bcg_section_templates.
		if ( isset( $data['section_template_id'] ) && null !== $data['section_template_id'] ) {
			$insert_data['section_template_id'] = absint( $data['section_template_id'] );
			$format[]                           = '%d';
		}

		// Coupon discount (decimal).
		if ( isset( $data['coupon_discount'] ) ) {
			$insert_data['coupon_discount'] = (float) $data['coupon_discount'];
			$format[]                       = '%f';
		}

		// Coupon type (enum).
		if ( isset( $data['coupon_type'] ) && in_array( $data['coupon_type'], self::VALID_COUPON_TYPES, true ) ) {
			$insert_data['coupon_type'] = $data['coupon_type'];
			$format[]                   = '%s';
		}

		// Scheduled at (datetime).
		if ( ! empty( $data['scheduled_at'] ) ) {
			$insert_data['scheduled_at'] = sanitize_text_field( $data['scheduled_at'] );
			$format[]                    = '%s';
		}

		$result = $wpdb->insert( $table, $insert_data, $format );

		if ( false === $result ) {
			return new WP_Error(
				'bcg_campaign_insert_failed',
				__( 'Failed to create the campaign. Database insert failed.', 'brevo-campaign-generator' )
			);
		}

		$campaign_id = (int) $wpdb->insert_id;

		/**
		 * Fires after a new campaign draft is created.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $campaign_id The new campaign ID.
		 * @param array $data        The campaign data that was inserted.
		 */
		do_action( 'bcg_campaign_created', $campaign_id, $data );

		return $campaign_id;
	}

	/**
	 * Update an existing campaign.
	 *
	 * Only fields present in $data and listed in UPDATABLE_CAMPAIGN_FIELDS
	 * will be updated. All other fields remain unchanged.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $campaign_id The campaign ID to update.
	 * @param array $data        Associative array of fields to update.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function update( int $campaign_id, array $data ): true|\WP_Error {
		global $wpdb;

		if ( $campaign_id <= 0 ) {
			return new WP_Error(
				'bcg_campaign_invalid_id',
				__( 'Invalid campaign ID.', 'brevo-campaign-generator' )
			);
		}

		// Verify the campaign exists.
		$exists = $this->campaign_exists( $campaign_id );
		if ( ! $exists ) {
			return new WP_Error(
				'bcg_campaign_not_found',
				__( 'Campaign not found.', 'brevo-campaign-generator' )
			);
		}

		$table       = $wpdb->prefix . self::CAMPAIGNS_TABLE;
		$update_data = array();
		$format      = array();

		foreach ( $data as $key => $value ) {
			if ( ! in_array( $key, self::UPDATABLE_CAMPAIGN_FIELDS, true ) ) {
				continue;
			}

			$sanitised = $this->sanitise_campaign_field( $key, $value );
			if ( null !== $sanitised['value'] || $this->is_nullable_field( $key ) ) {
				$update_data[ $key ] = $sanitised['value'];
				$format[]            = $sanitised['format'];
			}
		}

		if ( empty( $update_data ) ) {
			return new WP_Error(
				'bcg_campaign_no_data',
				__( 'No valid fields provided for update.', 'brevo-campaign-generator' )
			);
		}

		// Always touch updated_at.
		$update_data['updated_at'] = current_time( 'mysql' );
		$format[]                  = '%s';

		$result = $wpdb->update(
			$table,
			$update_data,
			array( 'id' => $campaign_id ),
			$format,
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error(
				'bcg_campaign_update_failed',
				__( 'Failed to update the campaign. Database update failed.', 'brevo-campaign-generator' )
			);
		}

		/**
		 * Fires after a campaign is updated.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $campaign_id The campaign ID.
		 * @param array $data        The fields that were updated.
		 */
		do_action( 'bcg_campaign_updated', $campaign_id, $data );

		return true;
	}

	/**
	 * Retrieve a single campaign with its associated products.
	 *
	 * Returns the campaign row from bcg_campaigns joined with all its
	 * products from bcg_campaign_products, sorted by sort_order.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id The campaign ID.
	 * @return object|WP_Error Campaign object with a 'products' property on success,
	 *                         WP_Error if not found.
	 */
	public function get( int $campaign_id ): \stdClass|\WP_Error {
		global $wpdb;

		if ( $campaign_id <= 0 ) {
			return new WP_Error(
				'bcg_campaign_invalid_id',
				__( 'Invalid campaign ID.', 'brevo-campaign-generator' )
			);
		}

		$campaigns_table = $wpdb->prefix . self::CAMPAIGNS_TABLE;
		$products_table  = $wpdb->prefix . self::PRODUCTS_TABLE;

		// Fetch the campaign.
		$campaign = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$campaigns_table} WHERE id = %d",
				$campaign_id
			)
		);

		if ( null === $campaign ) {
			return new WP_Error(
				'bcg_campaign_not_found',
				__( 'Campaign not found.', 'brevo-campaign-generator' )
			);
		}

		// Decode template_settings from JSON if present.
		if ( ! empty( $campaign->template_settings ) ) {
			$decoded = json_decode( $campaign->template_settings, true );
			$campaign->template_settings_array = is_array( $decoded ) ? $decoded : array();
		} else {
			$campaign->template_settings_array = array();
		}

		// Fetch associated products ordered by sort_order.
		$products = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$products_table} WHERE campaign_id = %d ORDER BY sort_order ASC, id ASC",
				$campaign_id
			)
		);

		$campaign->products = is_array( $products ) ? $products : array();

		// Enrich products with WooCommerce product data.
		foreach ( $campaign->products as &$product_row ) {
			$wc_product = wc_get_product( (int) $product_row->product_id );

			if ( $wc_product instanceof WC_Product ) {
				$product_row->wc_product_name  = $wc_product->get_name();
				$product_row->wc_price_html    = $wc_product->get_price_html();
				$product_row->wc_permalink     = $wc_product->get_permalink();
				$product_row->wc_image_url     = wp_get_attachment_image_url( $wc_product->get_image_id(), 'medium' );
				$product_row->wc_stock_status  = $wc_product->get_stock_status();
			} else {
				$product_row->wc_product_name  = __( 'Product not found', 'brevo-campaign-generator' );
				$product_row->wc_price_html    = '';
				$product_row->wc_permalink     = '';
				$product_row->wc_image_url     = '';
				$product_row->wc_stock_status  = 'outofstock';
			}
		}
		unset( $product_row );

		return $campaign;
	}

	/**
	 * Retrieve a paginated list of campaigns for the dashboard.
	 *
	 * Supports filtering by status, searching by title, and sorting.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     Optional. Query arguments.
	 *
	 *     @type string $status   Filter by campaign status. Default 'all'.
	 *     @type string $search   Search campaigns by title. Default empty.
	 *     @type int    $per_page Number of campaigns per page. Default 20.
	 *     @type int    $page     Current page number (1-based). Default 1.
	 *     @type string $orderby  Column to sort by: 'created_at', 'updated_at',
	 *                            'title', 'status'. Default 'created_at'.
	 *     @type string $order    Sort direction: 'ASC' or 'DESC'. Default 'DESC'.
	 * }
	 * @return array {
	 *     @type array $items       Array of campaign row objects.
	 *     @type int   $total       Total number of matching campaigns.
	 *     @type int   $total_pages Total number of pages.
	 *     @type int   $page        Current page number.
	 *     @type int   $per_page    Items per page.
	 * }
	 */
	public function get_all( array $args = array() ): array {
		global $wpdb;

		$defaults = array(
			'status'   => 'all',
			'search'   => '',
			'per_page' => 20,
			'page'     => 1,
			'orderby'  => 'created_at',
			'order'    => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$campaigns_table = $wpdb->prefix . self::CAMPAIGNS_TABLE;
		$products_table  = $wpdb->prefix . self::PRODUCTS_TABLE;

		$per_page = max( 1, absint( $args['per_page'] ) );
		$page     = max( 1, absint( $args['page'] ) );
		$offset   = ( $page - 1 ) * $per_page;

		// Validate order direction.
		$order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		// Validate orderby column.
		$valid_columns = array( 'created_at', 'updated_at', 'title', 'status', 'id' );
		$orderby       = in_array( $args['orderby'], $valid_columns, true ) ? $args['orderby'] : 'created_at';

		// Build WHERE clause.
		$where      = 'WHERE 1=1';
		$where_args = array();

		// Status filter.
		if ( 'all' !== $args['status'] && in_array( $args['status'], self::VALID_STATUSES, true ) ) {
			$where       .= ' AND c.status = %s';
			$where_args[] = $args['status'];
		}

		// Search filter.
		$search = sanitize_text_field( $args['search'] );
		if ( ! empty( $search ) ) {
			$where       .= ' AND c.title LIKE %s';
			$where_args[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		// Count total matching campaigns.
		$count_sql = "SELECT COUNT(*) FROM {$campaigns_table} AS c {$where}";
		if ( ! empty( $where_args ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$where_args ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $count_sql );
		}

		// Fetch campaigns with product count.
		$query = "SELECT c.*,
			(SELECT COUNT(*) FROM {$products_table} AS p WHERE p.campaign_id = c.id) AS product_count
			FROM {$campaigns_table} AS c
			{$where}
			ORDER BY c.{$orderby} {$order}
			LIMIT %d OFFSET %d";

		$query_args = array_merge( $where_args, array( $per_page, $offset ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$items = $wpdb->get_results( $wpdb->prepare( $query, ...$query_args ) );

		$total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 1;

		return array(
			'items'       => is_array( $items ) ? $items : array(),
			'total'       => $total,
			'total_pages' => $total_pages,
			'page'        => $page,
			'per_page'    => $per_page,
		);
	}

	/**
	 * Delete a campaign and all its associated data.
	 *
	 * Removes the campaign row, all associated product rows, the linked
	 * WooCommerce coupon (if any), and generated images from the uploads
	 * directory.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id The campaign ID to delete.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function delete( int $campaign_id ): true|\WP_Error {
		global $wpdb;

		if ( $campaign_id <= 0 ) {
			return new WP_Error(
				'bcg_campaign_invalid_id',
				__( 'Invalid campaign ID.', 'brevo-campaign-generator' )
			);
		}

		// Verify the campaign exists.
		if ( ! $this->campaign_exists( $campaign_id ) ) {
			return new WP_Error(
				'bcg_campaign_not_found',
				__( 'Campaign not found.', 'brevo-campaign-generator' )
			);
		}

		$campaigns_table = $wpdb->prefix . self::CAMPAIGNS_TABLE;
		$products_table  = $wpdb->prefix . self::PRODUCTS_TABLE;

		// Delete the associated WooCommerce coupon.
		$coupon_handler = new BCG_Coupon();
		$coupon_handler->delete_campaign_coupon( $campaign_id );

		// Delete generated images.
		$gemini = new BCG_Gemini();
		$gemini->delete_campaign_images( $campaign_id );

		// Delete associated products.
		$wpdb->delete(
			$products_table,
			array( 'campaign_id' => $campaign_id ),
			array( '%d' )
		);

		// Delete the campaign.
		$deleted = $wpdb->delete(
			$campaigns_table,
			array( 'id' => $campaign_id ),
			array( '%d' )
		);

		if ( false === $deleted ) {
			return new WP_Error(
				'bcg_campaign_delete_failed',
				__( 'Failed to delete the campaign.', 'brevo-campaign-generator' )
			);
		}

		/**
		 * Fires after a campaign and its associated data are deleted.
		 *
		 * @since 1.0.0
		 *
		 * @param int $campaign_id The deleted campaign ID.
		 */
		do_action( 'bcg_campaign_deleted', $campaign_id );

		return true;
	}

	// ─── Product Management ───────────────────────────────────────────

	/**
	 * Add a product to a campaign.
	 *
	 * Inserts a new row into bcg_campaign_products. The sort_order is
	 * automatically set to the next available position unless specified
	 * in $ai_data.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $campaign_id The campaign ID.
	 * @param int   $product_id  The WooCommerce product ID.
	 * @param array $ai_data {
	 *     Optional. AI-generated content and display options.
	 *
	 *     @type string $ai_headline         AI-generated headline.
	 *     @type string $ai_short_desc       AI-generated short description.
	 *     @type string $custom_headline     Custom user-edited headline.
	 *     @type string $custom_short_desc   Custom user-edited description.
	 *     @type string $generated_image_url URL to the AI-generated image.
	 *     @type int    $use_product_image   1 to use WC product image, 0 for AI image.
	 *     @type int    $show_buy_button     1 to show buy button, 0 to hide.
	 *     @type int    $sort_order          Position in the product list.
	 * }
	 * @return int|WP_Error The new campaign product row ID on success, WP_Error on failure.
	 */
	public function add_product( int $campaign_id, int $product_id, array $ai_data = array() ): int|\WP_Error {
		global $wpdb;

		if ( $campaign_id <= 0 ) {
			return new WP_Error(
				'bcg_campaign_invalid_id',
				__( 'Invalid campaign ID.', 'brevo-campaign-generator' )
			);
		}

		if ( $product_id <= 0 ) {
			return new WP_Error(
				'bcg_product_invalid_id',
				__( 'Invalid product ID.', 'brevo-campaign-generator' )
			);
		}

		// Verify the campaign exists.
		if ( ! $this->campaign_exists( $campaign_id ) ) {
			return new WP_Error(
				'bcg_campaign_not_found',
				__( 'Campaign not found.', 'brevo-campaign-generator' )
			);
		}

		// Verify the WooCommerce product exists.
		$wc_product = wc_get_product( $product_id );
		if ( ! $wc_product ) {
			return new WP_Error(
				'bcg_product_not_found',
				__( 'WooCommerce product not found.', 'brevo-campaign-generator' )
			);
		}

		$products_table = $wpdb->prefix . self::PRODUCTS_TABLE;

		// Determine the sort order.
		$sort_order = isset( $ai_data['sort_order'] )
			? absint( $ai_data['sort_order'] )
			: $this->get_next_sort_order( $campaign_id );

		$insert_data = array(
			'campaign_id'       => $campaign_id,
			'product_id'        => $product_id,
			'sort_order'        => $sort_order,
			'ai_headline'       => isset( $ai_data['ai_headline'] ) ? sanitize_text_field( $ai_data['ai_headline'] ) : null,
			'ai_short_desc'     => isset( $ai_data['ai_short_desc'] ) ? wp_kses_post( $ai_data['ai_short_desc'] ) : null,
			'custom_headline'   => isset( $ai_data['custom_headline'] ) ? sanitize_text_field( $ai_data['custom_headline'] ) : null,
			'custom_short_desc' => isset( $ai_data['custom_short_desc'] ) ? wp_kses_post( $ai_data['custom_short_desc'] ) : null,
			'generated_image_url' => isset( $ai_data['generated_image_url'] ) ? esc_url_raw( $ai_data['generated_image_url'] ) : null,
			'use_product_image' => isset( $ai_data['use_product_image'] ) ? absint( $ai_data['use_product_image'] ) : 1,
			'show_buy_button'   => isset( $ai_data['show_buy_button'] ) ? absint( $ai_data['show_buy_button'] ) : 1,
		);

		$format = array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d' );

		$result = $wpdb->insert( $products_table, $insert_data, $format );

		if ( false === $result ) {
			return new WP_Error(
				'bcg_product_insert_failed',
				__( 'Failed to add the product to the campaign.', 'brevo-campaign-generator' )
			);
		}

		$row_id = (int) $wpdb->insert_id;

		/**
		 * Fires after a product is added to a campaign.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $row_id      The campaign product row ID.
		 * @param int   $campaign_id The campaign ID.
		 * @param int   $product_id  The WooCommerce product ID.
		 * @param array $ai_data     The AI data that was stored.
		 */
		do_action( 'bcg_campaign_product_added', $row_id, $campaign_id, $product_id, $ai_data );

		return $row_id;
	}

	/**
	 * Update a campaign product row.
	 *
	 * Accepts partial updates — only fields present in $data and listed
	 * in UPDATABLE_PRODUCT_FIELDS will be modified.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $product_row_id The bcg_campaign_products row ID.
	 * @param array $data           Associative array of fields to update.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function update_product( int $product_row_id, array $data ): true|\WP_Error {
		global $wpdb;

		if ( $product_row_id <= 0 ) {
			return new WP_Error(
				'bcg_product_row_invalid_id',
				__( 'Invalid product row ID.', 'brevo-campaign-generator' )
			);
		}

		$products_table = $wpdb->prefix . self::PRODUCTS_TABLE;

		// Verify the row exists.
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$products_table} WHERE id = %d",
				$product_row_id
			)
		);

		if ( ! $exists ) {
			return new WP_Error(
				'bcg_product_row_not_found',
				__( 'Campaign product not found.', 'brevo-campaign-generator' )
			);
		}

		$update_data = array();
		$format      = array();

		foreach ( $data as $key => $value ) {
			if ( ! in_array( $key, self::UPDATABLE_PRODUCT_FIELDS, true ) ) {
				continue;
			}

			$sanitised = $this->sanitise_product_field( $key, $value );
			$update_data[ $key ] = $sanitised['value'];
			$format[]            = $sanitised['format'];
		}

		if ( empty( $update_data ) ) {
			return new WP_Error(
				'bcg_product_no_data',
				__( 'No valid fields provided for update.', 'brevo-campaign-generator' )
			);
		}

		$result = $wpdb->update(
			$products_table,
			$update_data,
			array( 'id' => $product_row_id ),
			$format,
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error(
				'bcg_product_update_failed',
				__( 'Failed to update the campaign product.', 'brevo-campaign-generator' )
			);
		}

		/**
		 * Fires after a campaign product is updated.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $product_row_id The product row ID.
		 * @param array $data           The fields that were updated.
		 */
		do_action( 'bcg_campaign_product_updated', $product_row_id, $data );

		return true;
	}

	/**
	 * Remove a product from a campaign.
	 *
	 * Deletes the bcg_campaign_products row. Does not delete the
	 * underlying WooCommerce product.
	 *
	 * @since 1.0.0
	 *
	 * @param int $product_row_id The bcg_campaign_products row ID.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function remove_product( int $product_row_id ): true|\WP_Error {
		global $wpdb;

		if ( $product_row_id <= 0 ) {
			return new WP_Error(
				'bcg_product_row_invalid_id',
				__( 'Invalid product row ID.', 'brevo-campaign-generator' )
			);
		}

		$products_table = $wpdb->prefix . self::PRODUCTS_TABLE;

		// Get campaign_id before deleting (for the action hook).
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT campaign_id, product_id FROM {$products_table} WHERE id = %d",
				$product_row_id
			)
		);

		if ( ! $row ) {
			return new WP_Error(
				'bcg_product_row_not_found',
				__( 'Campaign product not found.', 'brevo-campaign-generator' )
			);
		}

		$deleted = $wpdb->delete(
			$products_table,
			array( 'id' => $product_row_id ),
			array( '%d' )
		);

		if ( false === $deleted ) {
			return new WP_Error(
				'bcg_product_remove_failed',
				__( 'Failed to remove the product from the campaign.', 'brevo-campaign-generator' )
			);
		}

		/**
		 * Fires after a product is removed from a campaign.
		 *
		 * @since 1.0.0
		 *
		 * @param int $product_row_id The deleted product row ID.
		 * @param int $campaign_id    The campaign ID.
		 * @param int $product_id     The WooCommerce product ID.
		 */
		do_action( 'bcg_campaign_product_removed', $product_row_id, (int) $row->campaign_id, (int) $row->product_id );

		return true;
	}

	/**
	 * Reorder products within a campaign.
	 *
	 * Accepts an ordered array of product row IDs and updates their
	 * sort_order values to match the array positions (0-based).
	 *
	 * @since 1.0.0
	 *
	 * @param int   $campaign_id The campaign ID.
	 * @param array $ordered_ids Ordered array of bcg_campaign_products row IDs.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function reorder_products( int $campaign_id, array $ordered_ids ): true|\WP_Error {
		global $wpdb;

		if ( $campaign_id <= 0 ) {
			return new WP_Error(
				'bcg_campaign_invalid_id',
				__( 'Invalid campaign ID.', 'brevo-campaign-generator' )
			);
		}

		if ( empty( $ordered_ids ) ) {
			return new WP_Error(
				'bcg_reorder_empty',
				__( 'No product IDs provided for reordering.', 'brevo-campaign-generator' )
			);
		}

		$products_table = $wpdb->prefix . self::PRODUCTS_TABLE;

		// Sanitise and validate all IDs.
		$ordered_ids = array_map( 'absint', $ordered_ids );
		$ordered_ids = array_filter( $ordered_ids );

		// Verify all IDs belong to this campaign.
		$placeholders = implode( ', ', array_fill( 0, count( $ordered_ids ), '%d' ) );
		$args         = array_merge( array( $campaign_id ), $ordered_ids );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$valid_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$products_table} WHERE campaign_id = %d AND id IN ({$placeholders})",
				...$args
			)
		);

		if ( $valid_count !== count( $ordered_ids ) ) {
			return new WP_Error(
				'bcg_reorder_invalid_ids',
				__( 'One or more product IDs do not belong to this campaign.', 'brevo-campaign-generator' )
			);
		}

		// Update sort_order for each product.
		foreach ( $ordered_ids as $sort_order => $row_id ) {
			$wpdb->update(
				$products_table,
				array( 'sort_order' => $sort_order ),
				array(
					'id'          => $row_id,
					'campaign_id' => $campaign_id,
				),
				array( '%d' ),
				array( '%d', '%d' )
			);
		}

		/**
		 * Fires after campaign products are reordered.
		 *
		 * @since 1.0.0
		 *
		 * @param int   $campaign_id The campaign ID.
		 * @param array $ordered_ids The ordered array of product row IDs.
		 */
		do_action( 'bcg_campaign_products_reordered', $campaign_id, $ordered_ids );

		return true;
	}

	// ─── Template Management ──────────────────────────────────────────

	/**
	 * Save the template HTML and settings for a campaign.
	 *
	 * @since 1.0.0
	 *
	 * @param int          $campaign_id   The campaign ID.
	 * @param string       $html          The full HTML template content.
	 * @param string|array $settings_json The template settings as a JSON string or array.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function save_template( int $campaign_id, string $html, string|array $settings_json ): true|\WP_Error {
		global $wpdb;

		if ( $campaign_id <= 0 ) {
			return new WP_Error(
				'bcg_campaign_invalid_id',
				__( 'Invalid campaign ID.', 'brevo-campaign-generator' )
			);
		}

		if ( ! $this->campaign_exists( $campaign_id ) ) {
			return new WP_Error(
				'bcg_campaign_not_found',
				__( 'Campaign not found.', 'brevo-campaign-generator' )
			);
		}

		// Handle settings as array or JSON string.
		if ( is_array( $settings_json ) ) {
			$settings_json = wp_json_encode( $settings_json );
		}

		// Validate the JSON.
		$decoded = json_decode( $settings_json, true );
		if ( null === $decoded && json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error(
				'bcg_template_invalid_json',
				__( 'Template settings must be valid JSON.', 'brevo-campaign-generator' )
			);
		}

		$campaigns_table = $wpdb->prefix . self::CAMPAIGNS_TABLE;

		$result = $wpdb->update(
			$campaigns_table,
			array(
				'template_html'     => $html,
				'template_settings' => $settings_json,
				'updated_at'        => current_time( 'mysql' ),
			),
			array( 'id' => $campaign_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_Error(
				'bcg_template_save_failed',
				__( 'Failed to save the campaign template.', 'brevo-campaign-generator' )
			);
		}

		/**
		 * Fires after a campaign template is saved.
		 *
		 * @since 1.0.0
		 *
		 * @param int    $campaign_id   The campaign ID.
		 * @param string $html          The HTML template content.
		 * @param string $settings_json The JSON settings string.
		 */
		do_action( 'bcg_campaign_template_saved', $campaign_id, $html, $settings_json );

		return true;
	}

	// ─── Status Management ────────────────────────────────────────────

	/**
	 * Update the campaign status.
	 *
	 * Validates the status against allowed values before updating.
	 * Also records sent_at when status changes to 'sent'.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $campaign_id The campaign ID.
	 * @param string $status      The new status.
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public function set_status( int $campaign_id, string $status ): true|\WP_Error {
		if ( ! in_array( $status, self::VALID_STATUSES, true ) ) {
			return new WP_Error(
				'bcg_campaign_invalid_status',
				sprintf(
					/* translators: %s: the invalid status value */
					__( 'Invalid campaign status: %s', 'brevo-campaign-generator' ),
					esc_html( $status )
				)
			);
		}

		$data = array( 'status' => $status );

		// Automatically record sent_at when marking as sent.
		if ( 'sent' === $status ) {
			$data['sent_at'] = current_time( 'mysql' );
		}

		return $this->update( $campaign_id, $data );
	}

	// ─── Utility Methods ──────────────────────────────────────────────

	/**
	 * Check whether a campaign exists.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id The campaign ID.
	 * @return bool True if the campaign exists.
	 */
	public function campaign_exists( int $campaign_id ): bool {
		global $wpdb;

		$table = $wpdb->prefix . self::CAMPAIGNS_TABLE;

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE id = %d",
				$campaign_id
			)
		);

		return $count > 0;
	}

	/**
	 * Get the number of products associated with a campaign.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id The campaign ID.
	 * @return int The product count.
	 */
	public function get_product_count( int $campaign_id ): int {
		global $wpdb;

		$table = $wpdb->prefix . self::PRODUCTS_TABLE;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE campaign_id = %d",
				$campaign_id
			)
		);
	}

	/**
	 * Get campaign counts grouped by status.
	 *
	 * Useful for displaying status filter tabs on the dashboard.
	 *
	 * @since 1.0.0
	 *
	 * @return array Associative array of status => count, plus 'all' key.
	 */
	public function get_status_counts(): array {
		global $wpdb;

		$table = $wpdb->prefix . self::CAMPAIGNS_TABLE;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			"SELECT status, COUNT(*) AS count FROM {$table} GROUP BY status",
			OBJECT_K
		);

		$counts = array(
			'all'       => 0,
			'draft'     => 0,
			'ready'     => 0,
			'sent'      => 0,
			'scheduled' => 0,
		);

		if ( is_array( $results ) ) {
			foreach ( $results as $status => $row ) {
				$counts[ $status ] = (int) $row->count;
				$counts['all']    += (int) $row->count;
			}
		}

		return $counts;
	}

	// ─── Private Helpers ──────────────────────────────────────────────

	/**
	 * Get the next available sort order for a campaign's products.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id The campaign ID.
	 * @return int The next sort order value.
	 */
	private function get_next_sort_order( int $campaign_id ): int {
		global $wpdb;

		$table = $wpdb->prefix . self::PRODUCTS_TABLE;

		$max_order = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(sort_order) FROM {$table} WHERE campaign_id = %d",
				$campaign_id
			)
		);

		return null !== $max_order ? ( (int) $max_order + 1 ) : 0;
	}

	/**
	 * Sanitise a single campaign field value and determine its format placeholder.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   The field name.
	 * @param mixed  $value The raw value.
	 * @return array Associative array with 'value' and 'format' keys.
	 */
	private function sanitise_campaign_field( string $key, mixed $value ): array {
		switch ( $key ) {
			case 'title':
			case 'subject':
			case 'preview_text':
			case 'main_headline':
			case 'coupon_code':
			case 'mailing_list_id':
			case 'status':
			case 'coupon_type':
				return array(
					'value'  => sanitize_text_field( (string) $value ),
					'format' => '%s',
				);

			case 'main_image_url':
				return array(
					'value'  => esc_url_raw( (string) $value ),
					'format' => '%s',
				);

			case 'main_description':
				return array(
					'value'  => wp_kses_post( (string) $value ),
					'format' => '%s',
				);

			case 'template_html':
				// Full email HTML — stored as-is.
				return array(
					'value'  => $value,
					'format' => '%s',
				);

			case 'template_settings':
				$settings = $value;
				if ( is_array( $settings ) ) {
					$settings = wp_json_encode( $settings );
				}
				return array(
					'value'  => $settings,
					'format' => '%s',
				);

			case 'coupon_discount':
				return array(
					'value'  => (float) $value,
					'format' => '%f',
				);

			case 'brevo_campaign_id':
				return array(
					'value'  => null !== $value ? absint( $value ) : null,
					'format' => '%d',
				);

			case 'scheduled_at':
			case 'sent_at':
				return array(
					'value'  => ! empty( $value ) ? sanitize_text_field( (string) $value ) : null,
					'format' => '%s',
				);

			case 'builder_type':
				return array(
					'value'  => in_array( $value, array( 'flat', 'sections' ), true ) ? $value : 'flat',
					'format' => '%s',
				);

			case 'sections_json':
				// Full sections JSON — stored as-is.
				return array(
					'value'  => $value,
					'format' => '%s',
				);

			case 'section_template_id':
				return array(
					'value'  => null !== $value ? absint( $value ) : null,
					'format' => '%d',
				);

			default:
				return array(
					'value'  => sanitize_text_field( (string) $value ),
					'format' => '%s',
				);
		}
	}

	/**
	 * Sanitise a single product field value and determine its format placeholder.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   The field name.
	 * @param mixed  $value The raw value.
	 * @return array Associative array with 'value' and 'format' keys.
	 */
	private function sanitise_product_field( string $key, mixed $value ): array {
		switch ( $key ) {
			case 'ai_headline':
			case 'custom_headline':
				return array(
					'value'  => sanitize_text_field( (string) $value ),
					'format' => '%s',
				);

			case 'ai_short_desc':
			case 'custom_short_desc':
				return array(
					'value'  => wp_kses_post( (string) $value ),
					'format' => '%s',
				);

			case 'generated_image_url':
				return array(
					'value'  => esc_url_raw( (string) $value ),
					'format' => '%s',
				);

			case 'sort_order':
				return array(
					'value'  => absint( $value ),
					'format' => '%d',
				);

			case 'use_product_image':
			case 'show_buy_button':
				return array(
					'value'  => absint( $value ) ? 1 : 0,
					'format' => '%d',
				);

			default:
				return array(
					'value'  => sanitize_text_field( (string) $value ),
					'format' => '%s',
				);
		}
	}

	/**
	 * Check if a campaign field is nullable in the database.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key The field name.
	 * @return bool True if the field allows NULL values.
	 */
	private function is_nullable_field( string $key ): bool {
		$nullable_fields = array(
			'brevo_campaign_id',
			'scheduled_at',
			'sent_at',
			'main_image_url',
			'coupon_code',
			'coupon_discount',
			'sections_json',
			'section_template_id',
		);

		return in_array( $key, $nullable_fields, true );
	}
}
