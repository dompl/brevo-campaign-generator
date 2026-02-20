<?php
/**
 * Product selector for campaign creation.
 *
 * Provides methods to query WooCommerce products based on various sorting
 * strategies (bestsellers, least sold, latest, manual) with optional
 * category filtering. Used by the campaign wizard to select products for
 * inclusion in email campaigns.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Product_Selector
 *
 * Queries WooCommerce products using various selection strategies.
 *
 * @since 1.0.0
 */
class BCG_Product_Selector {

	/**
	 * Valid product source types.
	 *
	 * @var array
	 */
	const VALID_SOURCES = array( 'bestsellers', 'leastsold', 'latest', 'manual' );

	/**
	 * Default number of products to return.
	 *
	 * @var int
	 */
	const DEFAULT_COUNT = 3;

	/**
	 * Maximum number of products that can be requested.
	 *
	 * @var int
	 */
	const MAX_COUNT = 10;

	/**
	 * Get WooCommerce products based on the provided configuration.
	 *
	 * This is the main query method that dispatches to the appropriate
	 * selection strategy based on the 'source' parameter.
	 *
	 * @since 1.0.0
	 *
	 * @param array $config {
	 *     Product selection configuration.
	 *
	 *     @type int    $count        Number of products to return (1-10). Default from settings.
	 *     @type string $source       Selection source: 'bestsellers', 'leastsold', 'latest', or 'manual'.
	 *     @type array  $category_ids Optional. Array of WooCommerce category term IDs to filter by.
	 *     @type array  $manual_ids   Optional. Array of product IDs for manual selection.
	 * }
	 * @return WC_Product[] Array of WC_Product objects.
	 */
	public function get_products( array $config ): array {
		$config = $this->sanitise_config( $config );

		$source = $config['source'];

		switch ( $source ) {
			case 'bestsellers':
				return $this->get_bestsellers( $config );

			case 'leastsold':
				return $this->get_least_sold( $config );

			case 'latest':
				return $this->get_latest( $config );

			case 'manual':
				return $this->get_manual( $config );

			default:
				return $this->get_bestsellers( $config );
		}
	}

	/**
	 * Preview products based on the provided configuration.
	 *
	 * Returns a lightweight array of product data suitable for AJAX preview
	 * responses. Each element contains only the data needed to display a
	 * product preview card, avoiding the overhead of full WC_Product objects.
	 *
	 * @since 1.0.0
	 *
	 * @param array $config Product selection configuration (same as get_products).
	 * @return array Array of associative arrays with product preview data:
	 *               'id', 'name', 'price', 'price_html', 'short_description',
	 *               'image_url', 'image_id', 'permalink', 'category',
	 *               'total_sales', 'stock_status'.
	 */
	public function preview_products( array $config ): array {
		$products = $this->get_products( $config );
		$preview  = array();

		foreach ( $products as $product ) {
			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			$preview[] = $this->format_product_preview( $product );
		}

		return $preview;
	}

	/**
	 * Format a single WC_Product into a lightweight preview array.
	 *
	 * Extracts only the data needed for display and AI prompt building,
	 * sanitising and escaping all values for safe output.
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Product $product The WooCommerce product object.
	 * @return array Associative array of product preview data.
	 */
	public function format_product_preview( WC_Product $product ): array {
		$image_id  = $product->get_image_id();
		$image_url = $image_id
			? wp_get_attachment_image_url( $image_id, 'medium' )
			: wc_placeholder_img_src( 'medium' );

		// Get the primary category name.
		$category = '';
		$terms    = get_the_terms( $product->get_id(), 'product_cat' );
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$category = $terms[0]->name;
		}

		return array(
			'id'                => $product->get_id(),
			'name'              => $product->get_name(),
			'price'             => $product->get_price(),
			'price_html'        => $product->get_price_html(),
			'regular_price'     => $product->get_regular_price(),
			'sale_price'        => $product->get_sale_price(),
			'short_description' => wp_strip_all_tags( $product->get_short_description() ),
			'image_url'         => $image_url ? $image_url : '',
			'image_id'          => $image_id ? $image_id : 0,
			'permalink'         => $product->get_permalink(),
			'category'          => $category,
			'total_sales'       => (int) $product->get_total_sales(),
			'stock_status'      => $product->get_stock_status(),
		);
	}

	/**
	 * Search products by keyword for the manual product picker.
	 *
	 * Returns a lightweight array suitable for AJAX autocomplete/search
	 * responses in the campaign wizard.
	 *
	 * @since 1.0.0
	 *
	 * @param string $keyword The search keyword.
	 * @param int    $limit   Maximum number of results. Default 20.
	 * @return array Array of product preview arrays.
	 */
	public function search_products( string $keyword, int $limit = 20 ): array {
		$keyword = sanitize_text_field( $keyword );

		if ( empty( $keyword ) ) {
			return array();
		}

		$args = array(
			'status'  => 'publish',
			'limit'   => min( absint( $limit ), 50 ),
			's'       => $keyword,
			'orderby' => 'title',
			'order'   => 'ASC',
			'type'    => array( 'simple', 'variable', 'grouped', 'external' ),
		);

		$query    = new WC_Product_Query( $args );
		$products = $query->get_products();
		$results  = array();

		foreach ( $products as $product ) {
			$results[] = $this->format_product_preview( $product );
		}

		return $results;
	}

	/**
	 * Get the available WooCommerce product categories.
	 *
	 * Returns a hierarchical array of product categories suitable for
	 * rendering a multi-select checkbox tree in the campaign wizard.
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of category data: 'term_id', 'name', 'slug',
	 *               'parent', 'count'.
	 */
	public function get_categories(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$categories = array();

		foreach ( $terms as $term ) {
			$categories[] = array(
				'term_id' => $term->term_id,
				'name'    => $term->name,
				'slug'    => $term->slug,
				'parent'  => $term->parent,
				'count'   => $term->count,
			);
		}

		return $categories;
	}

	// ─── Private Query Methods ────────────────────────────────────────

	/**
	 * Get best-selling products sorted by total sales descending.
	 *
	 * @since 1.0.0
	 *
	 * @param array $config Sanitised configuration array.
	 * @return WC_Product[] Array of WC_Product objects.
	 */
	private function get_bestsellers( array $config ): array {
		$args = $this->build_base_query_args( $config );

		$args['orderby']  = 'meta_value_num';
		$args['order']    = 'DESC';
		$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key

		return $this->execute_query( $args );
	}

	/**
	 * Get least-sold products sorted by total sales ascending.
	 *
	 * Excludes products with zero sales to avoid returning products that
	 * have never been purchased and likely have no customer data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $config Sanitised configuration array.
	 * @return WC_Product[] Array of WC_Product objects.
	 */
	private function get_least_sold( array $config ): array {
		$args = $this->build_base_query_args( $config );

		$args['orderby']  = 'meta_value_num';
		$args['order']    = 'ASC';
		$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key

		return $this->execute_query( $args );
	}

	/**
	 * Get the latest products sorted by date descending.
	 *
	 * @since 1.0.0
	 *
	 * @param array $config Sanitised configuration array.
	 * @return WC_Product[] Array of WC_Product objects.
	 */
	private function get_latest( array $config ): array {
		$args = $this->build_base_query_args( $config );

		$args['orderby'] = 'date';
		$args['order']   = 'DESC';

		return $this->execute_query( $args );
	}

	/**
	 * Get specific products by their IDs (manual selection).
	 *
	 * Preserves the order of the manual_ids array so the admin sees
	 * products in the order they selected them.
	 *
	 * @since 1.0.0
	 *
	 * @param array $config Sanitised configuration array.
	 * @return WC_Product[] Array of WC_Product objects.
	 */
	private function get_manual( array $config ): array {
		$manual_ids = $config['manual_ids'];

		if ( empty( $manual_ids ) ) {
			return array();
		}

		$args = array(
			'status'  => 'publish',
			'include' => $manual_ids,
			'limit'   => count( $manual_ids ),
			'orderby' => 'include',
			'type'    => array( 'simple', 'variable', 'grouped', 'external' ),
		);

		return $this->execute_query( $args );
	}

	// ─── Query Helpers ────────────────────────────────────────────────

	/**
	 * Build the base WC_Product_Query arguments.
	 *
	 * Sets common query parameters shared across all selection strategies,
	 * including product status, count limit, type filter, and optional
	 * category filtering.
	 *
	 * @since 1.0.0
	 *
	 * @param array $config Sanitised configuration array.
	 * @return array WC_Product_Query compatible arguments.
	 */
	private function build_base_query_args( array $config ): array {
		$args = array(
			'status' => 'publish',
			'limit'  => $config['count'],
			'type'   => array( 'simple', 'variable', 'grouped', 'external' ),
		);

		// Apply category filter if categories are specified.
		if ( ! empty( $config['category_ids'] ) ) {
			$args['category'] = $this->get_category_slugs( $config['category_ids'] );
		}

		return $args;
	}

	/**
	 * Execute a WC_Product_Query and return the results.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args WC_Product_Query arguments.
	 * @return WC_Product[] Array of WC_Product objects.
	 */
	private function execute_query( array $args ): array {
		$query    = new WC_Product_Query( $args );
		$products = $query->get_products();

		return is_array( $products ) ? $products : array();
	}

	/**
	 * Convert category term IDs to their corresponding slugs.
	 *
	 * WC_Product_Query accepts category slugs in the 'category' parameter,
	 * not term IDs. This method converts IDs to slugs.
	 *
	 * @since 1.0.0
	 *
	 * @param array $category_ids Array of term IDs.
	 * @return array Array of category slugs.
	 */
	private function get_category_slugs( array $category_ids ): array {
		$slugs = array();

		foreach ( $category_ids as $term_id ) {
			$term = get_term( absint( $term_id ), 'product_cat' );

			if ( $term instanceof WP_Term ) {
				$slugs[] = $term->slug;
			}
		}

		return $slugs;
	}

	/**
	 * Sanitise and normalise the product selection configuration.
	 *
	 * Applies defaults, clamps values to acceptable ranges, and sanitises
	 * all input values.
	 *
	 * @since 1.0.0
	 *
	 * @param array $config Raw configuration array.
	 * @return array Sanitised configuration.
	 */
	private function sanitise_config( array $config ): array {
		$default_count = (int) get_option( 'bcg_default_products_per_campaign', self::DEFAULT_COUNT );

		$defaults = array(
			'count'        => $default_count,
			'source'       => 'bestsellers',
			'category_ids' => array(),
			'manual_ids'   => array(),
		);

		$config = wp_parse_args( $config, $defaults );

		// Clamp count between 1 and MAX_COUNT.
		$config['count'] = max( 1, min( self::MAX_COUNT, absint( $config['count'] ) ) );

		// Validate source.
		if ( ! in_array( $config['source'], self::VALID_SOURCES, true ) ) {
			$config['source'] = 'bestsellers';
		}

		// Sanitise category IDs.
		if ( ! is_array( $config['category_ids'] ) ) {
			$config['category_ids'] = array();
		}
		$config['category_ids'] = array_map( 'absint', $config['category_ids'] );
		$config['category_ids'] = array_filter( $config['category_ids'] );

		// Sanitise manual IDs.
		if ( ! is_array( $config['manual_ids'] ) ) {
			$config['manual_ids'] = array();
		}
		$config['manual_ids'] = array_map( 'absint', $config['manual_ids'] );
		$config['manual_ids'] = array_filter( $config['manual_ids'] );

		return $config;
	}
}
