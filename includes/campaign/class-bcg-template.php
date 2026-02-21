<?php
/**
 * HTML email template engine.
 *
 * Handles the rendering of email campaigns by combining a base HTML template
 * with dynamic content (headlines, product blocks, coupon data, etc.) and
 * visual settings (colours, fonts, layout). Provides full rendering for
 * campaign dispatch and live preview rendering for the template editor.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Template
 *
 * Email template engine with token replacement, conditional blocks,
 * product rendering, and CSS inlining from template settings.
 *
 * @since 1.0.0
 */
class BCG_Template {

	/**
	 * Option key for the default template HTML.
	 *
	 * @var string
	 */
	const OPTION_DEFAULT_HTML = 'bcg_default_template_html';

	/**
	 * Option key for the default template settings JSON.
	 *
	 * @var string
	 */
	const OPTION_DEFAULT_SETTINGS = 'bcg_default_template_settings';

	/**
	 * The campaigns database table name (without prefix).
	 *
	 * @var string
	 */
	const CAMPAIGNS_TABLE = 'bcg_campaigns';

	/**
	 * The campaign products database table name (without prefix).
	 *
	 * @var string
	 */
	const PRODUCTS_TABLE = 'bcg_campaign_products';

	// ─── Full Campaign Rendering ──────────────────────────────────────

	/**
	 * Render the full email HTML for a campaign.
	 *
	 * Loads the campaign data from the database, merges it with the template
	 * HTML and settings, replaces all tokens, processes conditionals, renders
	 * the products block, and inlines CSS from the template settings.
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id The campaign ID.
	 * @return string|\WP_Error The final rendered HTML string, or WP_Error on failure.
	 */
	public function render( int $campaign_id ): string|\WP_Error {
		global $wpdb;

		// Load the campaign.
		$campaign = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}" . self::CAMPAIGNS_TABLE . " WHERE id = %d",
				$campaign_id
			),
			ARRAY_A
		);

		if ( ! $campaign ) {
			return new \WP_Error(
				'bcg_campaign_not_found',
				sprintf(
					/* translators: %d: campaign ID */
					__( 'Campaign #%d not found.', 'brevo-campaign-generator' ),
					$campaign_id
				)
			);
		}

		// Load the campaign products.
		$products = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}" . self::PRODUCTS_TABLE . " WHERE campaign_id = %d ORDER BY sort_order ASC",
				$campaign_id
			),
			ARRAY_A
		);

		// Determine the template HTML to use.
		$template_html = ! empty( $campaign['template_html'] )
			? $campaign['template_html']
			: $this->get_default_template();

		// Parse the template settings.
		$settings = $this->parse_template_settings( $campaign['template_settings'] ?? '' );

		// Build the data array for token replacement.
		$data = $this->build_campaign_data( $campaign, $products );

		// Render the products block.
		$products_html = $this->render_products_block( $products, $settings );
		$data['products_block'] = $products_html;

		// Replace tokens.
		$html = $this->replace_tokens( $template_html, $data );

		// Process conditionals.
		$html = $this->process_conditionals( $html, $data );

		// Apply template settings (CSS inlining).
		$html = $this->apply_settings( $html, $settings );

		// Reorder sections if a custom order is set.
		$section_order = $settings['section_order'] ?? null;
		if ( is_array( $section_order ) && ! empty( $section_order ) ) {
			$html = $this->reorder_sections( $html, $section_order );
		}

		/**
		 * Filter the final rendered email HTML.
		 *
		 * @since 1.0.0
		 *
		 * @param string $html        The rendered HTML.
		 * @param int    $campaign_id The campaign ID.
		 * @param array  $settings    The template settings.
		 */
		return apply_filters( 'bcg_rendered_email', $html, $campaign_id, $settings );
	}

	// ─── Preview Rendering ────────────────────────────────────────────

	/**
	 * Render a preview using custom template HTML, settings, and sample data.
	 *
	 * Used by the template editor for live preview updates. Does not require
	 * a saved campaign — accepts raw data instead.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template_html The raw template HTML with {{tokens}}.
	 * @param string $settings_json The template settings as a JSON string.
	 * @param array  $sample_data   {
	 *     Sample data for rendering.
	 *
	 *     @type string $campaign_headline     Headline text.
	 *     @type string $campaign_description  Description text.
	 *     @type string $campaign_image        Image URL.
	 *     @type string $coupon_code           Coupon code.
	 *     @type string $coupon_text           Coupon promotional text.
	 *     @type array  $products              Array of product data arrays for the repeater.
	 * }
	 * @return string The rendered HTML preview.
	 */
	public function render_preview( string $template_html, string $settings_json, array $sample_data = array() ): string {
		$settings = $this->parse_template_settings( $settings_json );

		// Merge sample data with defaults.
		$data = wp_parse_args( $sample_data, $this->get_sample_data( $settings ) );

		// Build products block from sample data if products are present.
		$products = $data['products'] ?? array();
		unset( $data['products'] );

		$data['products_block'] = $this->render_products_block( $products, $settings );

		// Replace tokens.
		$html = $this->replace_tokens( $template_html, $data );

		// Process conditionals.
		$html = $this->process_conditionals( $html, $data );

		// Apply settings.
		$html = $this->apply_settings( $html, $settings );

		// Reorder sections if a custom order is set.
		$section_order = $settings['section_order'] ?? null;
		if ( is_array( $section_order ) && ! empty( $section_order ) ) {
			$html = $this->reorder_sections( $html, $section_order );
		}

		// Inject data attributes for overlay system in preview mode.
		$html = $this->inject_section_attributes( $html );

		return $html;
	}

	// ─── Token Replacement ────────────────────────────────────────────

	/**
	 * Replace all {{token}} placeholders in the HTML with actual data.
	 *
	 * Supports flat tokens like {{campaign_headline}} as well as nested
	 * settings tokens. All replacements are escaped appropriately for
	 * HTML output.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html The template HTML containing {{tokens}}.
	 * @param array  $data Associative array of token => value pairs.
	 * @return string The HTML with tokens replaced.
	 */
	public function replace_tokens( string $html, array $data ): string {
		// Standard campaign data tokens.
		$token_map = array(
			'{{campaign_headline}}'     => wp_kses_post( $data['campaign_headline'] ?? '' ),
			'{{campaign_description}}'  => wp_kses_post( $data['campaign_description'] ?? '' ),
			'{{campaign_image}}'        => esc_url( $data['campaign_image'] ?? '' ),
			'{{coupon_code}}'           => esc_html( $data['coupon_code'] ?? '' ),
			'{{coupon_text}}'           => esc_html( $data['coupon_text'] ?? '' ),
			'{{products_block}}'        => $data['products_block'] ?? '', // Already escaped during rendering.
			'{{store_name}}'            => esc_html( $data['store_name'] ?? get_bloginfo( 'name' ) ),
			'{{store_url}}'             => esc_url( $data['store_url'] ?? home_url( '/' ) ),
			'{{logo_url}}'              => esc_url( $data['logo_url'] ?? '' ),
			'{{unsubscribe_url}}'       => esc_url( $data['unsubscribe_url'] ?? '{{ unsubscribe }}' ),
			'{{current_year}}'          => esc_html( $data['current_year'] ?? gmdate( 'Y' ) ),
			'{{subject}}'               => esc_html( $data['subject'] ?? '' ),
			'{{preview_text}}'          => esc_html( $data['preview_text'] ?? '' ),
		);

		$html = str_replace( array_keys( $token_map ), array_values( $token_map ), $html );

		/**
		 * Filter the HTML after standard token replacement.
		 *
		 * Allows additional tokens to be replaced by external code.
		 *
		 * @since 1.0.0
		 *
		 * @param string $html The partially-rendered HTML.
		 * @param array  $data The full data array.
		 */
		return apply_filters( 'bcg_template_tokens_replaced', $html, $data );
	}

	// ─── Conditional Processing ───────────────────────────────────────

	/**
	 * Process conditional blocks in the template HTML.
	 *
	 * Supports the syntax: {{#if condition}}...content...{{/if}}
	 * If the condition is truthy in the data array, the content is kept.
	 * If falsy, the entire block (including the tags) is removed.
	 *
	 * Also supports: {{#if condition}}...{{else}}...{{/if}}
	 *
	 * @since 1.0.0
	 *
	 * @param string $html The HTML containing conditional blocks.
	 * @param array  $data Associative data array for evaluating conditions.
	 * @return string The HTML with conditionals resolved.
	 */
	public function process_conditionals( string $html, array $data ): string {
		// Pattern: {{#if variable}}...content...{{else}}...alt content...{{/if}}
		$pattern = '/\{\{#if\s+(\w+)\}\}(.*?)(?:\{\{else\}\}(.*?))?\{\{\/if\}\}/s';

		$html = preg_replace_callback(
			$pattern,
			function ( $matches ) use ( $data ) {
				$condition   = $matches[1];
				$true_block  = $matches[2];
				$false_block = $matches[3] ?? '';

				// Evaluate the condition against the data array.
				$is_truthy = $this->evaluate_condition( $condition, $data );

				return $is_truthy ? $true_block : $false_block;
			},
			$html
		);

		return $html;
	}

	/**
	 * Evaluate whether a condition is truthy.
	 *
	 * Checks the data array for the given key. A condition is truthy if:
	 * - The key exists and is not empty, not zero, and not false.
	 *
	 * Supports special conditions:
	 * - 'show_coupon_block': checks both data and template settings.
	 * - 'show_buy_button': checks product-level data.
	 * - 'show_nav': checks template settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string $condition The condition key to evaluate.
	 * @param array  $data      The data array to check against.
	 * @return bool True if the condition is truthy.
	 */
	private function evaluate_condition( string $condition, array $data ): bool {
		// Check direct data key first.
		if ( isset( $data[ $condition ] ) ) {
			$value = $data[ $condition ];

			if ( is_bool( $value ) ) {
				return $value;
			}

			if ( is_string( $value ) ) {
				return '' !== $value && '0' !== $value && 'false' !== strtolower( $value );
			}

			if ( is_numeric( $value ) ) {
				return (int) $value > 0;
			}

			return ! empty( $value );
		}

		return false;
	}

	// ─── Product Block Rendering ──────────────────────────────────────

	/**
	 * Render the HTML for the products repeater block.
	 *
	 * Generates a table-based layout for all products in the campaign.
	 * Each product is rendered as a responsive table row with image,
	 * headline, description, and optional buy button.
	 *
	 * @since 1.0.0
	 *
	 * @param array $products Array of product data (from DB or sample data).
	 *                        Each element should contain: product_id, ai_headline or
	 *                        custom_headline, ai_short_desc or custom_short_desc,
	 *                        generated_image_url, use_product_image, show_buy_button.
	 * @param array $settings The parsed template settings array.
	 * @return string The rendered products HTML block.
	 */
	public function render_products_block( array $products, array $settings = array() ): string {
		if ( empty( $products ) ) {
			return '';
		}

		$button_color      = esc_attr( $settings['button_color'] ?? '#e84040' );
		$button_text_color = esc_attr( $settings['button_text_color'] ?? '#ffffff' );
		$button_radius     = absint( $settings['button_border_radius'] ?? 4 );
		$link_color        = esc_attr( $settings['link_color'] ?? '#e84040' );
		$text_color        = esc_attr( $settings['text_color'] ?? '#333333' );
		$font_family       = esc_attr( $settings['font_family'] ?? 'Arial, sans-serif' );
		$layout            = $settings['product_layout'] ?? 'stacked';
		$product_gap       = absint( $settings['product_gap'] ?? 24 );

		// Button size presets.
		$button_size_map = array(
			'small'  => array( 'padding' => '8px 16px',  'font_size' => '13px' ),
			'medium' => array( 'padding' => '10px 24px', 'font_size' => '14px' ),
			'large'  => array( 'padding' => '14px 36px', 'font_size' => '16px' ),
		);
		$btn_size_key = $settings['product_button_size'] ?? 'medium';
		$btn_dims     = $button_size_map[ $btn_size_key ] ?? $button_size_map['medium'];

		$html           = '';
		$products_per_row = absint( $settings['products_per_row'] ?? 1 );
		if ( $products_per_row < 1 ) {
			$products_per_row = 1;
		}

		// Grid layout requires special row/column wrapping.
		if ( 'grid' === $layout ) {
			return $this->render_products_grid( $products, $settings, $products_per_row );
		}

		// Feature-first: render first product large, rest compact.
		if ( 'feature-first' === $layout ) {
			return $this->render_products_feature_first( $products, $settings );
		}

		foreach ( $products as $index => $product ) {
			$product_data = $this->normalise_product_data( $product );

			$headline  = esc_html( $product_data['headline'] );
			$desc      = esc_html( $product_data['short_desc'] );
			$image_url = esc_url( $product_data['image_url'] );
			$buy_url   = esc_url( $product_data['buy_url'] );
			$name      = esc_html( $product_data['name'] );
			$show_btn  = $product_data['show_buy_button'];

			$buy_button = '';
			if ( $show_btn && ! empty( $buy_url ) ) {
				$buy_button = sprintf(
					'<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin-top:12px;">' .
					'<tr><td align="center" style="border-radius:%dpx;background-color:%s;">' .
					'<a href="%s" target="_blank" style="display:inline-block;padding:%s;' .
					'font-family:%s;font-size:%s;font-weight:bold;color:%s;' .
					'text-decoration:none;border-radius:%dpx;background-color:%s;">' .
					'%s</a></td></tr></table>',
					$button_radius,
					$button_color,
					$buy_url,
					$btn_dims['padding'],
					$font_family,
					$btn_dims['font_size'],
					$button_text_color,
					$button_radius,
					$button_color,
					esc_html__( 'Buy Now', 'brevo-campaign-generator' )
				);
			}

			$render_args = array( $image_url, $name, $headline, $desc, $buy_button, $text_color, $font_family, $product_gap );

			switch ( $layout ) {
				case 'side-by-side':
					$html .= $this->render_product_side_by_side( ...$render_args );
					break;

				case 'reversed':
					$html .= $this->render_product_reversed( ...$render_args );
					break;

				case 'alternating':
					if ( 0 === $index % 2 ) {
						$html .= $this->render_product_side_by_side( ...$render_args );
					} else {
						$html .= $this->render_product_reversed( ...$render_args );
					}
					break;

				case 'compact':
					$html .= $this->render_product_compact( ...$render_args );
					break;

				case 'full-card':
					$html .= $this->render_product_full_card( ...$render_args );
					break;

				case 'text-only':
					$html .= $this->render_product_text_only( $name, $headline, $desc, $buy_button, $text_color, $font_family, $product_gap );
					break;

				case 'centered':
					$html .= $this->render_product_centered( ...$render_args );
					break;

				default: // stacked
					$html .= $this->render_product_stacked( ...$render_args );
					break;
			}
		}

		return $html;
	}

	/**
	 * Render a single product in stacked layout (image on top, text below).
	 *
	 * @since 1.0.0
	 *
	 * @param string $image_url   Product image URL.
	 * @param string $name        Product name (escaped).
	 * @param string $headline    AI headline (escaped).
	 * @param string $desc        Short description (escaped).
	 * @param string $buy_button  Rendered buy button HTML.
	 * @param string $text_color  Text colour hex.
	 * @param string $font_family Font family string.
	 * @return string Product table HTML.
	 */
	private function render_product_stacked(
		string $image_url,
		string $name,
		string $headline,
		string $desc,
		string $buy_button,
		string $text_color,
		string $font_family,
		int $product_gap = 24
	): string {
		$image_row = '';
		if ( ! empty( $image_url ) ) {
			$image_row = sprintf(
				'<tr><td align="center" style="padding-bottom:12px;">' .
				'<img src="%s" alt="%s" width="560" style="display:block;max-width:100%%;height:auto;border-radius:4px;" />' .
				'</td></tr>',
				$image_url,
				$name
			);
		}

		return sprintf(
			'<table class="bcg-product" width="100%%" border="0" cellpadding="0" cellspacing="0" ' .
			'role="presentation" style="margin-bottom:%dpx;">' .
			'%s' .
			'<tr><td style="padding:0 4px;">' .
			'<h3 style="margin:0 0 6px;font-family:%s;font-size:18px;font-weight:bold;color:%s;">%s</h3>' .
			'<p style="margin:0 0 4px;font-family:%s;font-size:14px;color:%s;line-height:1.5;">%s</p>' .
			'%s' .
			'</td></tr>' .
			'</table>',
			$product_gap,
			$image_row,
			$font_family,
			$text_color,
			$headline,
			$font_family,
			$text_color,
			$desc,
			$buy_button
		);
	}

	/**
	 * Render a single product in side-by-side layout (image left, text right).
	 *
	 * @since 1.0.0
	 *
	 * @param string $image_url   Product image URL.
	 * @param string $name        Product name (escaped).
	 * @param string $headline    AI headline (escaped).
	 * @param string $desc        Short description (escaped).
	 * @param string $buy_button  Rendered buy button HTML.
	 * @param string $text_color  Text colour hex.
	 * @param string $font_family Font family string.
	 * @return string Product table HTML.
	 */
	private function render_product_side_by_side(
		string $image_url,
		string $name,
		string $headline,
		string $desc,
		string $buy_button,
		string $text_color,
		string $font_family,
		int $product_gap = 24
	): string {
		$image_cell = '';
		if ( ! empty( $image_url ) ) {
			$image_cell = sprintf(
				'<td class="bcg-product-image" width="200" valign="top" style="padding-right:16px;">' .
				'<img src="%s" alt="%s" width="200" style="display:block;max-width:100%%;height:auto;border-radius:4px;" />' .
				'</td>',
				$image_url,
				$name
			);
		}

		return sprintf(
			'<table class="bcg-product" width="100%%" border="0" cellpadding="0" cellspacing="0" ' .
			'role="presentation" style="margin-bottom:%dpx;">' .
			'<tr>' .
			'%s' .
			'<td class="bcg-product-content" valign="top">' .
			'<h3 style="margin:0 0 6px;font-family:%s;font-size:18px;font-weight:bold;color:%s;">%s</h3>' .
			'<p style="margin:0 0 4px;font-family:%s;font-size:14px;color:%s;line-height:1.5;">%s</p>' .
			'%s' .
			'</td>' .
			'</tr>' .
			'</table>',
			$product_gap,
			$image_cell,
			$font_family,
			$text_color,
			$headline,
			$font_family,
			$text_color,
			$desc,
			$buy_button
		);
	}

	/**
	 * Render a single product in reversed layout (text left, image right).
	 *
	 * @since 1.1.0
	 *
	 * @param string $image_url   Product image URL.
	 * @param string $name        Product name (escaped).
	 * @param string $headline    AI headline (escaped).
	 * @param string $desc        Short description (escaped).
	 * @param string $buy_button  Rendered buy button HTML.
	 * @param string $text_color  Text colour hex.
	 * @param string $font_family Font family string.
	 * @return string Product table HTML.
	 */
	private function render_product_reversed(
		string $image_url,
		string $name,
		string $headline,
		string $desc,
		string $buy_button,
		string $text_color,
		string $font_family,
		int $product_gap = 24
	): string {
		$image_cell = '';
		if ( ! empty( $image_url ) ) {
			$image_cell = sprintf(
				'<td class="bcg-product-image" width="200" valign="top" style="padding-left:16px;">' .
				'<img src="%s" alt="%s" width="200" style="display:block;max-width:100%%;height:auto;border-radius:4px;" />' .
				'</td>',
				$image_url,
				$name
			);
		}

		return sprintf(
			'<table class="bcg-product" width="100%%" border="0" cellpadding="0" cellspacing="0" ' .
			'role="presentation" style="margin-bottom:%dpx;">' .
			'<tr>' .
			'<td class="bcg-product-content" valign="top">' .
			'<h3 style="margin:0 0 6px;font-family:%s;font-size:18px;font-weight:bold;color:%s;">%s</h3>' .
			'<p style="margin:0 0 4px;font-family:%s;font-size:14px;color:%s;line-height:1.5;">%s</p>' .
			'%s' .
			'</td>' .
			'%s' .
			'</tr>' .
			'</table>',
			$product_gap,
			$font_family,
			$text_color,
			$headline,
			$font_family,
			$text_color,
			$desc,
			$buy_button,
			$image_cell
		);
	}

	/**
	 * Render a single product in compact layout (small thumbnail left, minimal text right).
	 *
	 * @since 1.1.0
	 *
	 * @param string $image_url   Product image URL.
	 * @param string $name        Product name (escaped).
	 * @param string $headline    AI headline (escaped).
	 * @param string $desc        Short description (escaped).
	 * @param string $buy_button  Rendered buy button HTML.
	 * @param string $text_color  Text colour hex.
	 * @param string $font_family Font family string.
	 * @return string Product table HTML.
	 */
	private function render_product_compact(
		string $image_url,
		string $name,
		string $headline,
		string $desc,
		string $buy_button,
		string $text_color,
		string $font_family,
		int $product_gap = 24
	): string {
		$image_cell = '';
		if ( ! empty( $image_url ) ) {
			$image_cell = sprintf(
				'<td width="80" valign="top" style="padding-right:12px;">' .
				'<img src="%s" alt="%s" width="80" height="80" style="display:block;width:80px;height:80px;object-fit:cover;border-radius:4px;" />' .
				'</td>',
				$image_url,
				$name
			);
		}

		return sprintf(
			'<table class="bcg-product" width="100%%" border="0" cellpadding="0" cellspacing="0" ' .
			'role="presentation" style="margin-bottom:%dpx;">' .
			'<tr>' .
			'%s' .
			'<td valign="top">' .
			'<h3 style="margin:0 0 2px;font-family:%s;font-size:15px;font-weight:bold;color:%s;">%s</h3>' .
			'<p style="margin:0;font-family:%s;font-size:13px;color:%s;line-height:1.4;">%s</p>' .
			'%s' .
			'</td>' .
			'</tr>' .
			'</table>',
			$product_gap,
			$image_cell,
			$font_family,
			$text_color,
			$headline,
			$font_family,
			$text_color,
			$desc,
			$buy_button
		);
	}

	/**
	 * Render a single product in full-card layout (bordered card with image on top).
	 *
	 * @since 1.1.0
	 *
	 * @param string $image_url   Product image URL.
	 * @param string $name        Product name (escaped).
	 * @param string $headline    AI headline (escaped).
	 * @param string $desc        Short description (escaped).
	 * @param string $buy_button  Rendered buy button HTML.
	 * @param string $text_color  Text colour hex.
	 * @param string $font_family Font family string.
	 * @return string Product table HTML.
	 */
	private function render_product_full_card(
		string $image_url,
		string $name,
		string $headline,
		string $desc,
		string $buy_button,
		string $text_color,
		string $font_family,
		int $product_gap = 24
	): string {
		$image_row = '';
		if ( ! empty( $image_url ) ) {
			$image_row = sprintf(
				'<tr><td style="padding:0;">' .
				'<img src="%s" alt="%s" width="560" style="display:block;max-width:100%%;height:auto;border-radius:8px 8px 0 0;" />' .
				'</td></tr>',
				$image_url,
				$name
			);
		}

		return sprintf(
			'<table class="bcg-product" width="100%%" border="0" cellpadding="0" cellspacing="0" ' .
			'role="presentation" style="margin-bottom:%dpx;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;">' .
			'%s' .
			'<tr><td style="padding:16px 20px;">' .
			'<h3 style="margin:0 0 6px;font-family:%s;font-size:18px;font-weight:bold;color:%s;">%s</h3>' .
			'<p style="margin:0 0 4px;font-family:%s;font-size:14px;color:%s;line-height:1.5;">%s</p>' .
			'%s' .
			'</td></tr>' .
			'</table>',
			$product_gap,
			$image_row,
			$font_family,
			$text_color,
			$headline,
			$font_family,
			$text_color,
			$desc,
			$buy_button
		);
	}

	/**
	 * Render a single product in text-only layout (no images).
	 *
	 * @since 1.1.0
	 *
	 * @param string $name        Product name (escaped).
	 * @param string $headline    AI headline (escaped).
	 * @param string $desc        Short description (escaped).
	 * @param string $buy_button  Rendered buy button HTML.
	 * @param string $text_color  Text colour hex.
	 * @param string $font_family Font family string.
	 * @return string Product table HTML.
	 */
	private function render_product_text_only(
		string $name,
		string $headline,
		string $desc,
		string $buy_button,
		string $text_color,
		string $font_family,
		int $product_gap = 24
	): string {
		return sprintf(
			'<table class="bcg-product" width="100%%" border="0" cellpadding="0" cellspacing="0" ' .
			'role="presentation" style="margin-bottom:%dpx;border-bottom:1px solid #e0e0e0;padding-bottom:%dpx;">' .
			'<tr><td style="padding:0 0 %dpx;">' .
			'<h3 style="margin:0 0 6px;font-family:%s;font-size:18px;font-weight:bold;color:%s;">%s</h3>' .
			'<p style="margin:0 0 4px;font-family:%s;font-size:14px;color:%s;line-height:1.5;">%s</p>' .
			'%s' .
			'</td></tr>' .
			'</table>',
			$product_gap,
			$product_gap,
			$product_gap,
			$font_family,
			$text_color,
			$headline,
			$font_family,
			$text_color,
			$desc,
			$buy_button
		);
	}

	/**
	 * Render a single product in centered layout (all text center-aligned).
	 *
	 * @since 1.1.0
	 *
	 * @param string $image_url   Product image URL.
	 * @param string $name        Product name (escaped).
	 * @param string $headline    AI headline (escaped).
	 * @param string $desc        Short description (escaped).
	 * @param string $buy_button  Rendered buy button HTML.
	 * @param string $text_color  Text colour hex.
	 * @param string $font_family Font family string.
	 * @return string Product table HTML.
	 */
	private function render_product_centered(
		string $image_url,
		string $name,
		string $headline,
		string $desc,
		string $buy_button,
		string $text_color,
		string $font_family,
		int $product_gap = 24
	): string {
		$image_row = '';
		if ( ! empty( $image_url ) ) {
			$image_row = sprintf(
				'<tr><td align="center" style="padding-bottom:12px;">' .
				'<img src="%s" alt="%s" width="560" style="display:block;max-width:100%%;height:auto;border-radius:4px;" />' .
				'</td></tr>',
				$image_url,
				$name
			);
		}

		return sprintf(
			'<table class="bcg-product" width="100%%" border="0" cellpadding="0" cellspacing="0" ' .
			'role="presentation" style="margin-bottom:%dpx;text-align:center;">' .
			'%s' .
			'<tr><td align="center" style="padding:0 4px;">' .
			'<h3 style="margin:0 0 6px;font-family:%s;font-size:18px;font-weight:bold;color:%s;text-align:center;">%s</h3>' .
			'<p style="margin:0 0 4px;font-family:%s;font-size:14px;color:%s;line-height:1.5;text-align:center;">%s</p>' .
			'%s' .
			'</td></tr>' .
			'</table>',
			$product_gap,
			$image_row,
			$font_family,
			$text_color,
			$headline,
			$font_family,
			$text_color,
			$desc,
			$buy_button
		);
	}

	/**
	 * Render products in a grid layout (2+ columns per row).
	 *
	 * @since 1.1.0
	 *
	 * @param array $products         Array of product data.
	 * @param array $settings         Template settings.
	 * @param int   $products_per_row Number of columns.
	 * @return string Products grid HTML.
	 */
	private function render_products_grid( array $products, array $settings, int $products_per_row ): string {
		$button_color      = esc_attr( $settings['button_color'] ?? '#e84040' );
		$button_text_color = esc_attr( $settings['button_text_color'] ?? '#ffffff' );
		$button_radius     = absint( $settings['button_border_radius'] ?? 4 );
		$text_color        = esc_attr( $settings['text_color'] ?? '#333333' );
		$font_family       = esc_attr( $settings['font_family'] ?? 'Arial, sans-serif' );
		$product_gap       = absint( $settings['product_gap'] ?? 24 );

		// Button size presets.
		$button_size_map = array(
			'small'  => array( 'padding' => '8px 16px',  'font_size' => '13px' ),
			'medium' => array( 'padding' => '10px 24px', 'font_size' => '14px' ),
			'large'  => array( 'padding' => '14px 36px', 'font_size' => '16px' ),
		);
		$btn_size_key = $settings['product_button_size'] ?? 'medium';
		// Grid buttons are inherently smaller — use one step down.
		$grid_btn_map = array( 'small' => 'small', 'medium' => 'small', 'large' => 'medium' );
		$grid_btn_key = $grid_btn_map[ $btn_size_key ] ?? 'small';
		$btn_dims     = $button_size_map[ $grid_btn_key ] ?? $button_size_map['small'];

		if ( $products_per_row < 2 ) {
			$products_per_row = 2;
		}
		if ( $products_per_row > 3 ) {
			$products_per_row = 3;
		}

		$cell_width = (int) floor( 100 / $products_per_row );
		$html       = '<table class="bcg-product-grid" width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">';

		$chunks = array_chunk( $products, $products_per_row );

		foreach ( $chunks as $chunk_index => $row_products ) {
			// Add row gap except before the first row.
			if ( $chunk_index > 0 ) {
				$html .= sprintf(
					'<tr><td colspan="%d" style="height:%dpx;font-size:1px;line-height:1px;">&nbsp;</td></tr>',
					$products_per_row,
					$product_gap
				);
			}
			$html .= '<tr>';

			foreach ( $row_products as $product ) {
				$product_data = $this->normalise_product_data( $product );

				$headline  = esc_html( $product_data['headline'] );
				$desc      = esc_html( $product_data['short_desc'] );
				$image_url = esc_url( $product_data['image_url'] );
				$buy_url   = esc_url( $product_data['buy_url'] );
				$name      = esc_html( $product_data['name'] );
				$show_btn  = $product_data['show_buy_button'];

				$buy_button = '';
				if ( $show_btn && ! empty( $buy_url ) ) {
					$buy_button = sprintf(
						'<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin-top:8px;">' .
						'<tr><td align="center" style="border-radius:%dpx;background-color:%s;">' .
						'<a href="%s" target="_blank" style="display:inline-block;padding:%s;' .
						'font-family:%s;font-size:%s;font-weight:bold;color:%s;' .
						'text-decoration:none;border-radius:%dpx;background-color:%s;">' .
						'%s</a></td></tr></table>',
						$button_radius,
						$button_color,
						$buy_url,
						$btn_dims['padding'],
						$font_family,
						$btn_dims['font_size'],
						$button_text_color,
						$button_radius,
						$button_color,
						esc_html__( 'Buy Now', 'brevo-campaign-generator' )
					);
				}

				$image_html = '';
				if ( ! empty( $image_url ) ) {
					$image_html = sprintf(
						'<img src="%s" alt="%s" style="display:block;width:100%%;max-width:100%%;height:auto;border-radius:4px;margin-bottom:8px;" />',
						$image_url,
						$name
					);
				}

				$html .= sprintf(
					'<td class="bcg-grid-cell" width="%d%%" valign="top" style="padding:8px;text-align:center;">' .
					'%s' .
					'<h3 style="margin:0 0 4px;font-family:%s;font-size:15px;font-weight:bold;color:%s;">%s</h3>' .
					'<p style="margin:0;font-family:%s;font-size:13px;color:%s;line-height:1.4;">%s</p>' .
					'%s' .
					'</td>',
					$cell_width,
					$image_html,
					$font_family,
					$text_color,
					$headline,
					$font_family,
					$text_color,
					$desc,
					$buy_button
				);
			}

			// Fill empty cells if row is incomplete.
			$remaining = $products_per_row - count( $row_products );
			for ( $i = 0; $i < $remaining; $i++ ) {
				$html .= sprintf( '<td width="%d%%" style="padding:8px;">&nbsp;</td>', $cell_width );
			}

			$html .= '</tr>';
		}

		$html .= '</table>';

		return $html;
	}

	/**
	 * Render products in feature-first layout (first product large, rest compact).
	 *
	 * @since 1.1.0
	 *
	 * @param array $products Array of product data.
	 * @param array $settings Template settings.
	 * @return string Products HTML.
	 */
	private function render_products_feature_first( array $products, array $settings ): string {
		$button_color      = esc_attr( $settings['button_color'] ?? '#e84040' );
		$button_text_color = esc_attr( $settings['button_text_color'] ?? '#ffffff' );
		$button_radius     = absint( $settings['button_border_radius'] ?? 4 );
		$text_color        = esc_attr( $settings['text_color'] ?? '#333333' );
		$font_family       = esc_attr( $settings['font_family'] ?? 'Arial, sans-serif' );
		$product_gap       = absint( $settings['product_gap'] ?? 24 );

		// Button size presets.
		$button_size_map = array(
			'small'  => array( 'padding' => '8px 16px',  'font_size' => '13px' ),
			'medium' => array( 'padding' => '10px 24px', 'font_size' => '14px' ),
			'large'  => array( 'padding' => '14px 36px', 'font_size' => '16px' ),
		);
		$btn_size_key = $settings['product_button_size'] ?? 'medium';
		$btn_dims     = $button_size_map[ $btn_size_key ] ?? $button_size_map['medium'];

		$html = '';

		foreach ( $products as $index => $product ) {
			$product_data = $this->normalise_product_data( $product );

			$headline  = esc_html( $product_data['headline'] );
			$desc      = esc_html( $product_data['short_desc'] );
			$image_url = esc_url( $product_data['image_url'] );
			$buy_url   = esc_url( $product_data['buy_url'] );
			$name      = esc_html( $product_data['name'] );
			$show_btn  = $product_data['show_buy_button'];

			$buy_button = '';
			if ( $show_btn && ! empty( $buy_url ) ) {
				// Featured product gets the configured size, compact ones step down.
				if ( 0 === $index ) {
					$feat_btn = $btn_dims;
				} else {
					$compact_map = array( 'small' => 'small', 'medium' => 'small', 'large' => 'medium' );
					$feat_btn    = $button_size_map[ $compact_map[ $btn_size_key ] ?? 'small' ];
				}

				$buy_button = sprintf(
					'<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin-top:12px;">' .
					'<tr><td align="center" style="border-radius:%dpx;background-color:%s;">' .
					'<a href="%s" target="_blank" style="display:inline-block;padding:%s;' .
					'font-family:%s;font-size:%s;font-weight:bold;color:%s;' .
					'text-decoration:none;border-radius:%dpx;background-color:%s;">' .
					'%s</a></td></tr></table>',
					$button_radius,
					$button_color,
					$buy_url,
					$feat_btn['padding'],
					$font_family,
					$feat_btn['font_size'],
					$button_text_color,
					$button_radius,
					$button_color,
					esc_html__( 'Buy Now', 'brevo-campaign-generator' )
				);
			}

			if ( 0 === $index ) {
				// First product: full-width stacked, larger.
				$html .= $this->render_product_stacked( $image_url, $name, $headline, $desc, $buy_button, $text_color, $font_family, $product_gap );
			} else {
				// Remaining products: compact.
				$html .= $this->render_product_compact( $image_url, $name, $headline, $desc, $buy_button, $text_color, $font_family, $product_gap );
			}
		}

		return $html;
	}

	// ─── Section Parsing & Reordering ────────────────────────────────

	/**
	 * Normalise a section comment text into a canonical type.
	 *
	 * Strips variant suffixes: "HEADER — centered" becomes "header",
	 * "HERO IMAGE" becomes "hero", "HEADLINE + DESCRIPTION" becomes "headline".
	 *
	 * @since 1.2.0
	 *
	 * @param string $comment_text Raw comment text from the HTML template.
	 * @return string Canonical section type.
	 */
	private function normalize_section_type( string $comment_text ): string {
		$text = strtolower( trim( $comment_text ) );

		// Order matters: check more-specific keywords first (e.g. 'headline'
		// before 'hero' because "HEADLINE (no hero image)" contains both).
		$map = array(
			'header'   => 'header',
			'headline' => 'headline',
			'hero'     => 'hero',
			'coupon'   => 'coupon',
			'product'  => 'products',
			'cta'      => 'cta',
			'divider'  => 'divider',
			'footer'   => 'footer',
		);

		foreach ( $map as $keyword => $type ) {
			if ( str_contains( $text, $keyword ) ) {
				return $type;
			}
		}

		return 'unknown';
	}

	/**
	 * Parse template HTML into sections based on section comment markers.
	 *
	 * Splits HTML at `<!-- ============ ... ============ -->` boundaries.
	 * Returns an array of sections with type, ID, and HTML content.
	 *
	 * @since 1.2.0
	 *
	 * @param string $html The full template HTML.
	 * @return array {
	 *     @type string $preamble  HTML before the first section.
	 *     @type string $postamble HTML after the last section.
	 *     @type array  $sections  Array of [ 'type' => string, 'id' => string, 'html' => string ].
	 * }
	 */
	public function parse_sections( string $html ): array {
		// Match section comment markers.
		$pattern = '/<!--\s*={4,}\s*(.+?)\s*={4,}\s*-->/';

		// Find all section markers and their positions.
		if ( ! preg_match_all( $pattern, $html, $matches, PREG_OFFSET_CAPTURE ) ) {
			return array(
				'preamble'  => $html,
				'postamble' => '',
				'sections'  => array(),
			);
		}

		$type_counts = array();
		$sections    = array();
		$preamble    = '';

		$marker_count = count( $matches[0] );

		for ( $i = 0; $i < $marker_count; $i++ ) {
			$marker_text = $matches[0][ $i ][0]; // Full match.
			$marker_pos  = $matches[0][ $i ][1]; // Position in HTML.
			$comment     = $matches[1][ $i ][0]; // Inner text.

			$type = $this->normalize_section_type( $comment );

			// Count this type for unique ID.
			if ( ! isset( $type_counts[ $type ] ) ) {
				$type_counts[ $type ] = 0;
			}
			$section_id = $type . '-' . $type_counts[ $type ];
			$type_counts[ $type ]++;

			// Find the start of the section content (right before the marker comment).
			// We want to capture the <tr> or content that follows this comment.
			$content_start = $marker_pos;

			// Find the end — it's the start of the next marker, or end of the main content wrapper.
			if ( $i + 1 < $marker_count ) {
				// Content goes up to the next marker.
				$next_marker_pos = $matches[0][ $i + 1 ][1];
				$content_end     = $next_marker_pos;
			} else {
				// Last section — content goes to end of HTML.
				$content_end = strlen( $html );
			}

			$section_html = substr( $html, $content_start, $content_end - $content_start );

			// For the first marker, everything before it is preamble.
			if ( 0 === $i ) {
				$preamble = substr( $html, 0, $marker_pos );
			}

			$sections[] = array(
				'type' => $type,
				'id'   => $section_id,
				'html' => $section_html,
			);
		}

		// Postamble: anything after the last section's content.
		// In practice the last section extends to end, so postamble is empty.
		// But we need to handle the closing tags that come after the last section.
		$postamble = '';

		return array(
			'preamble'  => $preamble,
			'postamble' => $postamble,
			'sections'  => $sections,
		);
	}

	/**
	 * Reorder sections according to a custom order array.
	 *
	 * @since 1.2.0
	 *
	 * @param string $html          The full template HTML.
	 * @param array  $section_order Array of section IDs in desired order.
	 * @return string Reordered HTML.
	 */
	public function reorder_sections( string $html, array $section_order ): string {
		$parsed = $this->parse_sections( $html );

		if ( empty( $parsed['sections'] ) ) {
			return $html;
		}

		// Build a map of section ID => section data.
		$section_map = array();
		foreach ( $parsed['sections'] as $section ) {
			$section_map[ $section['id'] ] = $section;
		}

		// Build reordered HTML.
		$reordered_html = $parsed['preamble'];

		foreach ( $section_order as $section_id ) {
			// Handle duplicated sections (e.g., "header-1" created by duplication).
			// If the ID has a duplicate suffix, find the original.
			$original_id = $section_id;
			if ( ! isset( $section_map[ $section_id ] ) ) {
				// Check if this is a duplicate — strip trailing duplicate counter.
				$base_id = preg_replace( '/-dup\d+$/', '', $section_id );
				if ( isset( $section_map[ $base_id ] ) ) {
					$original_id = $base_id;
				} else {
					continue; // Skip unknown section.
				}
			}

			$reordered_html .= $section_map[ $original_id ]['html'];
		}

		$reordered_html .= $parsed['postamble'];

		return $reordered_html;
	}

	/**
	 * Inject data-bcg-section attributes into section root elements for the overlay system.
	 *
	 * This is used in preview mode only to allow the JS overlay system to find
	 * and position overlays over each section in the iframe.
	 *
	 * @since 1.2.0
	 *
	 * @param string $html The rendered HTML.
	 * @return string HTML with data attributes injected.
	 */
	public function inject_section_attributes( string $html ): string {
		// Match section comment followed by the first <tr or <table element.
		// Allows optional whitespace and {{#if ...}} conditionals between them.
		// This adds data-bcg-section directly to the element, keeping the HTML
		// valid inside <table> structures where stray <div>s would be ejected.
		$pattern = '/(<!--\s*={4,}\s*(.+?)\s*={4,}\s*-->)((?:\s|\{\{#if\s+[^}]+\}\})*?)(<(?:tr|table)\b)/i';

		$type_counts = array();

		$html = preg_replace_callback( $pattern, function ( $match ) use ( &$type_counts ) {
			$full_comment = $match[1];
			$comment_text = $match[2];
			$between      = $match[3]; // Whitespace and/or {{#if ...}}
			$open_tag     = $match[4]; // "<tr" or "<table"

			$type = $this->normalize_section_type( $comment_text );

			if ( ! isset( $type_counts[ $type ] ) ) {
				$type_counts[ $type ] = 0;
			}

			$section_id = $type . '-' . $type_counts[ $type ];
			$type_counts[ $type ]++;

			// Add data attribute directly to the <tr> or <table> element.
			return $full_comment . $between . $open_tag . ' data-bcg-section="' . esc_attr( $section_id ) . '"';
		}, $html );

		return $html;
	}

	// ─── Product Data Normalisation ──────────────────────────────────

	/**
	 * Normalise product data from various sources into a consistent format.
	 *
	 * Handles both database result arrays (with ai_headline, custom_headline etc.)
	 * and simple sample data arrays (with headline, short_desc, image_url).
	 *
	 * @since 1.0.0
	 *
	 * @param array $product Raw product data.
	 * @return array Normalised product data with keys: name, headline, short_desc,
	 *               image_url, buy_url, show_buy_button.
	 */
	private function normalise_product_data( array $product ): array {
		// Determine the headline: custom overrides AI.
		$headline = '';
		if ( ! empty( $product['custom_headline'] ) ) {
			$headline = $product['custom_headline'];
		} elseif ( ! empty( $product['ai_headline'] ) ) {
			$headline = $product['ai_headline'];
		} elseif ( ! empty( $product['headline'] ) ) {
			$headline = $product['headline'];
		}

		// Determine the short description: custom overrides AI.
		$short_desc = '';
		if ( ! empty( $product['custom_short_desc'] ) ) {
			$short_desc = $product['custom_short_desc'];
		} elseif ( ! empty( $product['ai_short_desc'] ) ) {
			$short_desc = $product['ai_short_desc'];
		} elseif ( ! empty( $product['short_desc'] ) ) {
			$short_desc = $product['short_desc'];
		}

		// Determine the image URL.
		$image_url = '';
		$use_product_image = isset( $product['use_product_image'] ) ? (bool) $product['use_product_image'] : true;

		if ( ! $use_product_image && ! empty( $product['generated_image_url'] ) ) {
			$image_url = $product['generated_image_url'];
		} elseif ( ! empty( $product['image_url'] ) ) {
			$image_url = $product['image_url'];
		} elseif ( ! empty( $product['product_id'] ) ) {
			$image_url = $this->get_wc_product_image_url( absint( $product['product_id'] ) );
		}

		// Determine the buy URL.
		$buy_url = '';
		if ( ! empty( $product['buy_url'] ) ) {
			$buy_url = $product['buy_url'];
		} elseif ( ! empty( $product['product_id'] ) ) {
			$buy_url = get_permalink( absint( $product['product_id'] ) );
		}

		// Determine the product name.
		$name = '';
		if ( ! empty( $product['name'] ) ) {
			$name = $product['name'];
		} elseif ( ! empty( $product['product_id'] ) ) {
			$wc_product = wc_get_product( absint( $product['product_id'] ) );
			$name       = $wc_product ? $wc_product->get_name() : '';
		}

		// Show buy button.
		$show_buy_button = isset( $product['show_buy_button'] ) ? (bool) $product['show_buy_button'] : true;

		return array(
			'name'            => $name,
			'headline'        => $headline,
			'short_desc'      => $short_desc,
			'image_url'       => $image_url,
			'buy_url'         => $buy_url,
			'show_buy_button' => $show_buy_button,
		);
	}

	// ─── CSS Inlining from Settings ───────────────────────────────────

	/**
	 * Apply template settings as inline CSS to the rendered HTML.
	 *
	 * Replaces CSS custom property placeholders and colour/layout tokens
	 * within the template with values from the settings array.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html     The rendered HTML.
	 * @param array  $settings The parsed template settings array.
	 * @return string The HTML with inline styles applied.
	 */
	public function apply_settings( string $html, array $settings ): string {
		// CSS variable replacements.
		// heading_font_family falls back to font_family if not set.
		$heading_font = $settings['heading_font_family'] ?? $settings['font_family'] ?? 'Georgia, serif';

		$css_vars = array(
			'{{setting_primary_color}}'         => esc_attr( $settings['primary_color'] ?? '#e84040' ),
			'{{setting_background_color}}'       => esc_attr( $settings['background_color'] ?? '#f5f5f5' ),
			'{{setting_content_background}}'     => esc_attr( $settings['content_background'] ?? '#ffffff' ),
			'{{setting_text_color}}'             => esc_attr( $settings['text_color'] ?? '#333333' ),
			'{{setting_link_color}}'             => esc_attr( $settings['link_color'] ?? '#e84040' ),
			'{{setting_button_color}}'           => esc_attr( $settings['button_color'] ?? '#e84040' ),
			'{{setting_button_text_color}}'      => esc_attr( $settings['button_text_color'] ?? '#ffffff' ),
			'{{setting_button_border_radius}}'   => absint( $settings['button_border_radius'] ?? 4 ) . 'px',
			'{{setting_font_family}}'            => esc_attr( $settings['font_family'] ?? 'Arial, sans-serif' ),
			'{{setting_heading_font_family}}'    => esc_attr( $heading_font ),
			'{{setting_max_width}}'              => absint( $settings['max_width'] ?? 600 ) . 'px',
			'{{setting_logo_url}}'               => esc_url( $settings['logo_url'] ?? '' ),
			'{{setting_logo_width}}'             => absint( $settings['logo_width'] ?? 180 ) . 'px',
			'{{setting_header_text}}'            => esc_html( $settings['header_text'] ?? '' ),
			'{{setting_footer_text}}'            => wp_kses_post( $settings['footer_text'] ?? '' ),
			'{{setting_logo_alignment}}'         => esc_attr( $settings['logo_alignment'] ?? 'left' ),
			'{{setting_header_bg}}'              => esc_attr( $settings['header_bg'] ?? '#ffffff' ),
		);

		$html = str_replace( array_keys( $css_vars ), array_values( $css_vars ), $html );

		// Render navigation links.
		$html = $this->render_navigation( $html, $settings );

		// Render footer links.
		$html = $this->render_footer_links( $html, $settings );

		/**
		 * Filter the HTML after settings have been applied.
		 *
		 * @since 1.0.0
		 *
		 * @param string $html     The HTML with settings applied.
		 * @param array  $settings The template settings.
		 */
		return apply_filters( 'bcg_template_settings_applied', $html, $settings );
	}

	/**
	 * Render navigation links into the template.
	 *
	 * Replaces the {{navigation_links}} token with rendered anchor tags
	 * from the settings nav_links array.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html     The template HTML.
	 * @param array  $settings The template settings.
	 * @return string The HTML with navigation links rendered.
	 */
	private function render_navigation( string $html, array $settings ): string {
		$show_nav  = ! empty( $settings['show_nav'] );
		$nav_links = $settings['nav_links'] ?? array();

		if ( ! $show_nav || empty( $nav_links ) ) {
			// Remove the navigation placeholder entirely.
			$html = str_replace( '{{navigation_links}}', '', $html );
			return $html;
		}

		$link_color  = esc_attr( $settings['link_color'] ?? '#e84040' );
		$font_family = esc_attr( $settings['font_family'] ?? 'Arial, sans-serif' );

		$links_html = array();
		foreach ( $nav_links as $link ) {
			if ( ! empty( $link['label'] ) && ! empty( $link['url'] ) ) {
				$links_html[] = sprintf(
					'<a href="%s" style="color:%s;text-decoration:none;font-family:%s;font-size:13px;font-weight:600;padding:0 10px;" target="_blank">%s</a>',
					esc_url( $link['url'] ),
					$link_color,
					$font_family,
					esc_html( $link['label'] )
				);
			}
		}

		$nav_output = implode(
			'<span style="color:#cccccc;font-size:13px;"> | </span>',
			$links_html
		);

		return str_replace( '{{navigation_links}}', $nav_output, $html );
	}

	/**
	 * Render footer links into the template.
	 *
	 * Replaces the {{footer_links}} token with rendered anchor tags
	 * from the settings footer_links array.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html     The template HTML.
	 * @param array  $settings The template settings.
	 * @return string The HTML with footer links rendered.
	 */
	private function render_footer_links( string $html, array $settings ): string {
		$footer_links = $settings['footer_links'] ?? array();

		if ( empty( $footer_links ) ) {
			$html = str_replace( '{{footer_links}}', '', $html );
			return $html;
		}

		$link_color  = esc_attr( $settings['link_color'] ?? '#e84040' );
		$font_family = esc_attr( $settings['font_family'] ?? 'Arial, sans-serif' );

		$links_html = array();
		foreach ( $footer_links as $link ) {
			if ( ! empty( $link['label'] ) ) {
				$url = $link['url'] ?? '#';
				// Allow Brevo unsubscribe placeholder.
				if ( false !== strpos( $url, '{{unsubscribe_url}}' ) ) {
					$url = '{{ unsubscribe }}';
				}

				$links_html[] = sprintf(
					'<a href="%s" style="color:%s;text-decoration:underline;font-family:%s;font-size:12px;" target="_blank">%s</a>',
					esc_url( $url ),
					$link_color,
					$font_family,
					esc_html( $link['label'] )
				);
			}
		}

		$footer_output = implode(
			'<span style="color:#999999;font-size:12px;"> &middot; </span>',
			$links_html
		);

		return str_replace( '{{footer_links}}', $footer_output, $html );
	}

	// ─── Default Template ─────────────────────────────────────────────

	/**
	 * Get the default email template HTML.
	 *
	 * First checks for a user-customised default in the database. Falls
	 * back to the bundled file at templates/default-email-template.html.
	 * Accepts an optional slug to load a specific template from the registry.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Optional template slug. If provided, loads from registry.
	 * @return string The default template HTML.
	 */
	public function get_default_template( string $slug = '' ): string {
		// If a slug is provided, load from the registry.
		if ( ! empty( $slug ) ) {
			$registry = BCG_Template_Registry::get_instance();
			$html     = $registry->get_template_html( $slug );

			if ( ! empty( $html ) ) {
				return $html;
			}
		}

		// Check for a user-saved default.
		$saved = get_option( self::OPTION_DEFAULT_HTML, '' );

		if ( ! empty( $saved ) ) {
			return $saved;
		}

		// Fall back to the bundled template file.
		$template_path = BCG_PLUGIN_DIR . 'templates/default-email-template.html';

		if ( file_exists( $template_path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$contents = file_get_contents( $template_path );
			return false !== $contents ? $contents : '';
		}

		return '';
	}

	/**
	 * Get the default template settings.
	 *
	 * Returns the user-customised defaults if saved, otherwise the
	 * hardcoded default settings array. Accepts an optional slug to
	 * load a specific template's settings from the registry.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Optional template slug. If provided, loads from registry.
	 * @return array The default template settings array.
	 */
	public function get_default_settings( string $slug = '' ): array {
		// If a slug is provided, load from the registry and merge with hardcoded defaults.
		if ( ! empty( $slug ) ) {
			$registry = BCG_Template_Registry::get_instance();
			$settings = $registry->get_template_settings( $slug );

			if ( ! empty( $settings ) ) {
				return wp_parse_args( $settings, $this->get_hardcoded_defaults() );
			}
		}

		$saved = get_option( self::OPTION_DEFAULT_SETTINGS, '' );

		if ( ! empty( $saved ) ) {
			$decoded = json_decode( $saved, true );
			if ( is_array( $decoded ) ) {
				return wp_parse_args( $decoded, $this->get_hardcoded_defaults() );
			}
		}

		return $this->get_hardcoded_defaults();
	}

	/**
	 * Get a template by its slug from the registry.
	 *
	 * Returns the full template definition (slug, name, description,
	 * HTML file path, and default settings).
	 *
	 * @since 1.1.0
	 *
	 * @param string $slug The template slug.
	 * @return array|null The template definition, or null if not found.
	 */
	public function get_template_by_slug( string $slug ): ?array {
		$registry = BCG_Template_Registry::get_instance();

		return $registry->get_template( $slug );
	}

	/**
	 * Save a template and settings as the default for new campaigns.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html     The template HTML to save as default.
	 * @param array  $settings The template settings array to save as default.
	 * @return bool True on success.
	 */
	public function save_default_template( string $html, array $settings ): bool {
		update_option( self::OPTION_DEFAULT_HTML, $html, false );
		update_option( self::OPTION_DEFAULT_SETTINGS, wp_json_encode( $settings ), false );

		/**
		 * Fires after the default template has been saved.
		 *
		 * @since 1.0.0
		 *
		 * @param string $html     The template HTML.
		 * @param array  $settings The template settings.
		 */
		do_action( 'bcg_default_template_saved', $html, $settings );

		return true;
	}

	/**
	 * Reset the default template to the bundled version.
	 *
	 * Deletes the user-customised default from the database, falling back
	 * to the bundled template file.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True on success.
	 */
	public function reset_default_template(): bool {
		delete_option( self::OPTION_DEFAULT_HTML );
		delete_option( self::OPTION_DEFAULT_SETTINGS );

		return true;
	}

	// ─── Helpers ──────────────────────────────────────────────────────

	/**
	 * Parse template settings from a JSON string or array.
	 *
	 * Merges with hardcoded defaults to ensure all keys are present.
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $settings_input JSON string or array.
	 * @return array Parsed and merged settings array.
	 */
	private function parse_template_settings( $settings_input ): array {
		$settings = array();

		if ( is_string( $settings_input ) && ! empty( $settings_input ) ) {
			$decoded = json_decode( $settings_input, true );
			if ( is_array( $decoded ) ) {
				$settings = $decoded;
			}
		} elseif ( is_array( $settings_input ) ) {
			$settings = $settings_input;
		}

		return wp_parse_args( $settings, $this->get_hardcoded_defaults() );
	}

	/**
	 * Get the hardcoded default template settings.
	 *
	 * These values match the defaults specified in the CLAUDE.md specification.
	 *
	 * @since 1.0.0
	 *
	 * @return array Default settings array.
	 */
	private function get_hardcoded_defaults(): array {
		return array(
			'logo_url'              => '',
			'logo_width'            => 180,
			'nav_links'             => array(
				array( 'label' => 'Shop', 'url' => home_url( '/shop' ) ),
				array( 'label' => 'About', 'url' => home_url( '/about' ) ),
			),
			'show_nav'              => true,
			'primary_color'         => '#e84040',
			'background_color'      => '#f5f5f5',
			'content_background'    => '#ffffff',
			'text_color'            => '#333333',
			'link_color'            => '#e84040',
			'button_color'          => '#e84040',
			'button_text_color'     => '#ffffff',
			'button_border_radius'  => 4,
			'font_family'           => 'Arial, sans-serif',
			'header_text'           => '',
			'footer_text'           => __( 'You received this email because you subscribed to our newsletter.', 'brevo-campaign-generator' ),
			'footer_links'          => array(
				array( 'label' => 'Privacy Policy', 'url' => home_url( '/privacy-policy' ) ),
				array( 'label' => 'Unsubscribe', 'url' => '{{unsubscribe_url}}' ),
			),
			'max_width'             => 600,
			'show_coupon_block'     => true,
			'product_layout'        => 'stacked',
			'products_per_row'      => 1,
			'product_gap'           => 24,
			'product_button_size'   => 'medium',
			'section_order'         => null,
			'logo_alignment'        => 'left',
			'header_bg'             => '#ffffff',
		);
	}

	/**
	 * Build the data array for a campaign from database rows.
	 *
	 * Extracts all token values from the campaign and product data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $campaign The campaign database row.
	 * @param array $products The campaign products database rows.
	 * @return array Token => value pairs for replacement.
	 */
	private function build_campaign_data( array $campaign, array $products ): array {
		// Resolve coupon text.
		$coupon_code = $campaign['coupon_code'] ?? '';
		$coupon_text = '';

		if ( ! empty( $coupon_code ) && ! empty( $campaign['coupon_discount'] ) ) {
			$discount_value = (float) $campaign['coupon_discount'];
			$discount_type  = $campaign['coupon_type'] ?? 'percent';

			if ( 'percent' === $discount_type ) {
				$coupon_text = sprintf(
					/* translators: %s: discount percentage */
					__( 'Use code %1$s for %2$s%% off!', 'brevo-campaign-generator' ),
					$coupon_code,
					number_format( $discount_value, 0 )
				);
			} else {
				$coupon_text = sprintf(
					/* translators: 1: coupon code, 2: formatted discount amount */
					__( 'Use code %1$s for %2$s off!', 'brevo-campaign-generator' ),
					$coupon_code,
					wc_price( $discount_value )
				);
			}
		}

		// Parse settings for logo and other values.
		$settings = $this->parse_template_settings( $campaign['template_settings'] ?? '' );

		return array(
			'campaign_headline'    => $campaign['main_headline'] ?? '',
			'campaign_description' => $campaign['main_description'] ?? '',
			'campaign_image'       => $campaign['main_image_url'] ?? '',
			'coupon_code'          => $coupon_code,
			'coupon_text'          => $coupon_text,
			'subject'              => $campaign['subject'] ?? '',
			'preview_text'         => $campaign['preview_text'] ?? '',
			'store_name'           => get_bloginfo( 'name' ),
			'store_url'            => home_url( '/' ),
			'logo_url'             => $settings['logo_url'] ?? '',
			'unsubscribe_url'      => '{{ unsubscribe }}',
			'current_year'         => gmdate( 'Y' ),
			'show_coupon_block'    => ! empty( $coupon_code ) && ! empty( $settings['show_coupon_block'] ),
			'show_nav'             => ! empty( $settings['show_nav'] ),
		);
	}

	/**
	 * Get sample data for template preview rendering.
	 *
	 * @since 1.0.0
	 *
	 * @return array Sample data array with realistic placeholder content.
	 */
	private function get_sample_data( array $settings = array() ): array {
		$store_name = get_bloginfo( 'name' );

		if ( empty( $store_name ) ) {
			$store_name = 'Your Store';
		}

		return array(
			'campaign_headline'    => __( 'Discover Our Best Sellers', 'brevo-campaign-generator' ),
			'campaign_description' => __( 'Handpicked products just for you. Explore our latest collection and find something you will love. Limited time offers on selected items.', 'brevo-campaign-generator' ),
			'campaign_image'       => BCG_PLUGIN_URL . 'assets/images/default-placeholder.png',
			'coupon_code'          => 'SAVE20',
			'coupon_text'          => __( 'Use code SAVE20 for 20% off your order!', 'brevo-campaign-generator' ),
			'store_name'           => $store_name,
			'store_url'            => home_url( '/' ),
			'logo_url'             => $settings['logo_url'] ?? '',
			'header_text'          => $settings['header_text'] ?? '',
			'unsubscribe_url'      => '#',
			'current_year'         => gmdate( 'Y' ),
			'subject'              => __( 'Our Top Picks Just for You', 'brevo-campaign-generator' ),
			'preview_text'         => __( 'Discover handpicked products and exclusive discounts.', 'brevo-campaign-generator' ),
			'show_coupon_block'    => true,
			'show_nav'             => true,
			'products'             => array(
				array(
					'name'            => __( 'Premium Diamond Blade', 'brevo-campaign-generator' ),
					'headline'        => __( 'Cut Through Anything with Precision', 'brevo-campaign-generator' ),
					'short_desc'      => __( 'Professional-grade diamond blade designed for clean, precise cuts in concrete, stone, and masonry.', 'brevo-campaign-generator' ),
					'image_url'       => BCG_PLUGIN_URL . 'assets/images/default-placeholder.png',
					'buy_url'         => home_url( '/shop' ),
					'show_buy_button' => true,
				),
				array(
					'name'            => __( 'Core Drill Bit Set', 'brevo-campaign-generator' ),
					'headline'        => __( 'The Professional\'s Choice', 'brevo-campaign-generator' ),
					'short_desc'      => __( 'Complete set of core drill bits for all your drilling needs. Built to last with premium materials.', 'brevo-campaign-generator' ),
					'image_url'       => BCG_PLUGIN_URL . 'assets/images/default-placeholder.png',
					'buy_url'         => home_url( '/shop' ),
					'show_buy_button' => true,
				),
				array(
					'name'            => __( 'Grinding Cup Wheel', 'brevo-campaign-generator' ),
					'headline'        => __( 'Smooth Finishes Every Time', 'brevo-campaign-generator' ),
					'short_desc'      => __( 'High-performance grinding cup wheel for surface preparation and concrete finishing.', 'brevo-campaign-generator' ),
					'image_url'       => BCG_PLUGIN_URL . 'assets/images/default-placeholder.png',
					'buy_url'         => home_url( '/shop' ),
					'show_buy_button' => true,
				),
			),
		);
	}

	/**
	 * Get the featured image URL for a WooCommerce product.
	 *
	 * @since 1.0.0
	 *
	 * @param int $product_id The WooCommerce product ID.
	 * @return string The product image URL, or empty string if none.
	 */
	private function get_wc_product_image_url( int $product_id ): string {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return '';
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return '';
		}

		$image_id = $product->get_image_id();

		if ( $image_id ) {
			$image_url = wp_get_attachment_image_url( $image_id, 'medium' );
			return $image_url ? $image_url : '';
		}

		// Fallback: use the WooCommerce placeholder.
		return wc_placeholder_img_src( 'medium' );
	}
}
