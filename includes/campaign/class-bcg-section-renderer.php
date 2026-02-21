<?php
/**
 * Section Renderer.
 *
 * Converts an array of section objects (as stored in sections_json) into
 * complete, email-client-safe HTML using table-based layout with fully
 * inlined CSS. No class names or external stylesheets in the output.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.5.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Section_Renderer
 *
 * Renders section arrays to email-safe HTML.
 *
 * @since 1.5.0
 */
class BCG_Section_Renderer {

	/**
	 * Maximum email content width in pixels.
	 *
	 * @var int
	 */
	const MAX_WIDTH = 600;

	/**
	 * Default font family.
	 *
	 * @var string
	 */
	const FONT_FAMILY = "Arial, 'Helvetica Neue', Helvetica, sans-serif";

	/**
	 * Render an array of sections to a full email HTML string.
	 *
	 * @since  1.5.0
	 * @param  array $sections       Array of section objects (each with 'id', 'type', 'settings').
	 * @param  array $global_settings Optional global settings (logo_url, store_url, max_width, font_family).
	 * @return string Complete email HTML.
	 */
	public static function render_sections( array $sections, array $global_settings = array() ): string {
		$max_width   = (int) ( $global_settings['max_width'] ?? self::MAX_WIDTH );
		$font_family = $global_settings['font_family'] ?? self::FONT_FAMILY;
		$store_url   = $global_settings['store_url'] ?? get_bloginfo( 'url' );

		$sections_html = '';
		foreach ( $sections as $section ) {
			if ( ! isset( $section['type'] ) ) {
				continue;
			}
			$type     = sanitize_key( $section['type'] );
			$settings = is_array( $section['settings'] ) ? $section['settings'] : array();
			$sections_html .= self::render_section( $type, $settings, $max_width, $font_family );
		}

		return self::wrap_in_email_shell( $sections_html, $max_width, $font_family, $store_url );
	}

	/**
	 * Render a single section by type.
	 *
	 * @since  1.5.0
	 * @param  string $type        Section type slug.
	 * @param  array  $s           Section settings.
	 * @param  int    $max_width   Maximum width in pixels.
	 * @param  string $font_family Email font family string.
	 * @return string HTML for this section.
	 */
	private static function render_section( string $type, array $s, int $max_width, string $font_family ): string {
		// Merge defaults.
		$defaults = BCG_Section_Registry::get_defaults( $type );
		$s        = array_merge( $defaults, $s );

		switch ( $type ) {
			case 'header':
				return self::render_header( $s, $max_width, $font_family );
			case 'hero':
				return self::render_hero( $s, $max_width, $font_family );
			case 'text':
				return self::render_text( $s, $max_width, $font_family );
			case 'image':
				return self::render_image( $s, $max_width );
			case 'products':
				return self::render_products( $s, $max_width, $font_family );
			case 'banner':
				return self::render_banner( $s, $max_width, $font_family );
			case 'cta':
				return self::render_cta( $s, $max_width, $font_family );
			case 'coupon':
				return self::render_coupon( $s, $max_width, $font_family );
			case 'divider':
				return self::render_divider( $s, $max_width );
			case 'spacer':
				return self::render_spacer( $s );
			case 'heading':
				return self::render_heading( $s, $max_width, $font_family );
			case 'list':
				return self::render_list( $s, $max_width, $font_family );
			case 'footer':
				return self::render_footer( $s, $max_width, $font_family );
			default:
				return '';
		}
	}

	// ── Section Renderers ─────────────────────────────────────────────

	/**
	 * Render header section.
	 *
	 * @since  1.5.0
	 * @param  array  $s          Settings.
	 * @param  int    $max_width  Max width.
	 * @param  string $font       Font family.
	 * @return string
	 */
	private static function render_header( array $s, int $max_width, string $font ): string {
		$bg      = esc_attr( $s['bg_color'] );
		$logo_url = esc_url( $s['logo_url'] );
		$logo_w  = (int) $s['logo_width'];

		$logo_html = '';
		if ( $logo_url ) {
			$logo_html = sprintf(
				'<img src="%s" width="%d" alt="%s" style="display:block;border:0;outline:none;text-decoration:none;max-width:%dpx;height:auto;" />',
				$logo_url,
				$logo_w,
				esc_attr( get_bloginfo( 'name' ) ),
				$logo_w
			);
		} else {
			$logo_html = sprintf(
				'<span style="font-family:%s;font-size:22px;font-weight:700;color:#333333;">%s</span>',
				esc_attr( $font ),
				esc_html( get_bloginfo( 'name' ) )
			);
		}

		$nav_html = '';
		if ( ! empty( $s['show_nav'] ) ) {
			$links = array();
			$nav_data = is_string( $s['nav_links'] ) ? json_decode( $s['nav_links'], true ) : $s['nav_links'];
			if ( is_array( $nav_data ) ) {
				foreach ( $nav_data as $link ) {
					if ( ! empty( $link['label'] ) ) {
						$href     = ! empty( $link['url'] ) ? esc_url( $link['url'] ) : '#';
						$links[]  = sprintf(
							'<a href="%s" style="font-family:%s;font-size:14px;color:#555555;text-decoration:none;margin-left:16px;">%s</a>',
							$href,
							esc_attr( $font ),
							esc_html( $link['label'] )
						);
					}
				}
			}
			if ( $links ) {
				$nav_html = '<td style="text-align:right;vertical-align:middle;">' . implode( '', $links ) . '</td>';
			}
		}

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;background-color:%s;">
				<tr>
					<td style="padding:20px 30px;vertical-align:middle;">
						<table width="100%%" cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td style="vertical-align:middle;">%s</td>
								%s
							</tr>
						</table>
					</td>
				</tr>
			</table>',
			$max_width, $max_width, $max_width,
			$bg,
			$logo_html,
			$nav_html
		);
	}

	/**
	 * Render hero section.
	 *
	 * @since  1.5.0
	 * @param  array  $s     Settings.
	 * @param  int    $mw    Max width.
	 * @param  string $font  Font family.
	 * @return string
	 */
	private static function render_hero( array $s, int $mw, string $font ): string {
		$bg         = esc_attr( $s['bg_color'] );
		$pt         = (int) $s['padding_top'];
		$pb         = (int) $s['padding_bottom'];
		$hsize      = (int) $s['headline_size'];
		$hcolor     = esc_attr( $s['headline_color'] );
		$scolor     = esc_attr( $s['subtext_color'] );
		$cta_bg     = esc_attr( $s['cta_bg_color'] );
		$cta_tc     = esc_attr( $s['cta_text_color'] );
		$headline   = esc_html( $s['headline'] );
		$subtext    = esc_html( $s['subtext'] );
		$cta_text   = esc_html( $s['cta_text'] );
		$cta_url    = esc_url( $s['cta_url'] ?: '#' );

		$bg_style = "background-color:{$bg};";
		if ( ! empty( $s['image_url'] ) ) {
			$img_url   = esc_url( $s['image_url'] );
			$bg_style .= "background-image:url('{$img_url}');background-size:cover;background-position:center;";
		}

		$cta_fsize  = (int) ( $s['cta_font_size'] ?? 16 );
		$cta_pad_h  = (int) ( $s['cta_padding_h'] ?? 32 );
		$cta_pad_v  = (int) ( $s['cta_padding_v'] ?? 14 );
		$s_fsize    = (int) ( $s['subtext_font_size'] ?? 16 );
		$cta_html = '';
		if ( $cta_text ) {
			$cta_html = sprintf(
				'<tr><td style="padding-top:24px;text-align:center;">
					<a href="%s" style="display:inline-block;padding:%dpx %dpx;background-color:%s;color:%s;font-family:%s;font-size:%dpx;font-weight:700;text-decoration:none;border-radius:4px;">%s</a>
				</td></tr>',
				$cta_url, $cta_pad_v, $cta_pad_h, $cta_bg, $cta_tc, esc_attr( $font ), $cta_fsize, $cta_text
			);
		}

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;%s">
				<tr>
					<td style="padding:%dpx 30px %dpx;text-align:center;">
						<table width="100%%" cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td style="text-align:center;">
									<h1 style="font-family:%s;font-size:%dpx;font-weight:700;color:%s;margin:0;padding:0;line-height:1.2;">%s</h1>
								</td>
							</tr>
							<tr>
								<td style="padding-top:16px;text-align:center;">
									<p style="font-family:%s;font-size:%dpx;color:%s;margin:0;padding:0;line-height:1.6;">%s</p>
								</td>
							</tr>
							%s
						</table>
					</td>
				</tr>
			</table>',
			$mw, $mw, $mw, $bg_style,
			$pt, $pb,
			esc_attr( $font ), $hsize, $hcolor, $headline,
			esc_attr( $font ), $s_fsize, $scolor, $subtext,
			$cta_html
		);
	}

	/**
	 * Render text block section.
	 *
	 * @since  1.5.0
	 * @param  array  $s     Settings.
	 * @param  int    $mw    Max width.
	 * @param  string $font  Font family.
	 * @return string
	 */
	private static function render_text( array $s, int $mw, string $font ): string {
		$bg      = esc_attr( $s['bg_color'] );
		$tc      = esc_attr( $s['text_color'] );
		$pad     = (int) $s['padding'];
		$fsize   = (int) $s['font_size'];
		$align   = in_array( $s['alignment'], array( 'left', 'center', 'right' ), true ) ? $s['alignment'] : 'left';

		$heading_html = '';
		if ( ! empty( $s['heading'] ) ) {
			$heading_html = sprintf(
				'<tr><td style="padding-bottom:12px;"><h2 style="font-family:%s;font-size:22px;font-weight:700;color:%s;margin:0;padding:0;text-align:%s;">%s</h2></td></tr>',
				esc_attr( $font ), $tc, $align, esc_html( $s['heading'] )
			);
		}

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;background-color:%s;">
				<tr>
					<td style="padding:%dpx;">
						<table width="100%%" cellpadding="0" cellspacing="0" border="0">
							%s
							<tr>
								<td>
									<p style="font-family:%s;font-size:%dpx;color:%s;margin:0;padding:0;line-height:1.7;text-align:%s;">%s</p>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>',
			$mw, $mw, $mw, $bg,
			$pad,
			$heading_html,
			esc_attr( $font ), $fsize, $tc, $align,
			nl2br( esc_html( $s['body'] ) )
		);
	}

	/**
	 * Render image section.
	 *
	 * @since  1.5.0
	 * @param  array $s   Settings.
	 * @param  int   $mw  Max width.
	 * @return string
	 */
	private static function render_image( array $s, int $mw ): string {
		if ( empty( $s['image_url'] ) ) {
			return '';
		}

		$img_url  = esc_url( $s['image_url'] );
		$alt      = esc_attr( $s['alt_text'] );
		$width    = min( 100, (int) $s['width'] );
		$align    = in_array( $s['alignment'], array( 'left', 'center', 'right' ), true ) ? $s['alignment'] : 'center';
		$img_html = sprintf(
			'<img src="%s" alt="%s" width="%s" style="display:block;border:0;outline:none;max-width:100%%;width:%s%%;height:auto;" />',
			$img_url, $alt, $width . '%', $width
		);

		if ( ! empty( $s['link_url'] ) ) {
			$img_html = sprintf( '<a href="%s" style="display:block;">%s</a>', esc_url( $s['link_url'] ), $img_html );
		}

		$caption_html = '';
		if ( ! empty( $s['caption'] ) ) {
			$caption_html = sprintf( '<p style="font-family:Arial,sans-serif;font-size:12px;color:#999999;margin:6px 0 0;text-align:%s;">%s</p>', $align, esc_html( $s['caption'] ) );
		}

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;">
				<tr>
					<td style="padding:0;text-align:%s;">%s%s</td>
				</tr>
			</table>',
			$mw, $mw, $mw, $align, $img_html, $caption_html
		);
	}

	/**
	 * Render products section.
	 *
	 * @since  1.5.0
	 * @param  array  $s     Settings.
	 * @param  int    $mw    Max width.
	 * @param  string $font  Font family.
	 * @return string
	 */
	private static function render_products( array $s, int $mw, string $font ): string {
		$bg         = esc_attr( $s['bg_color'] );
		$btn_color  = esc_attr( $s['button_color'] );
		$btn_text   = esc_html( $s['button_text'] );
		$columns    = max( 1, min( 3, (int) $s['columns'] ) );
		$show_price = ! empty( $s['show_price'] );
		$show_btn   = ! empty( $s['show_button'] );
		$title_fsize = (int) ( $s['title_font_size'] ?? 16 );
		$desc_fsize  = (int) ( $s['desc_font_size'] ?? 14 );
		$btn_fsize   = (int) ( $s['button_font_size'] ?? 14 );
		$btn_pad_h   = (int) ( $s['button_padding_h'] ?? 20 );
		$btn_pad_v   = (int) ( $s['button_padding_v'] ?? 10 );
		$prod_gap    = (int) ( $s['product_gap'] ?? 15 );
		$text_align  = in_array( $s['text_align'] ?? 'left', array( 'left', 'center', 'right' ), true ) ? $s['text_align'] : 'left';

		// Parse product IDs.
		$ids_raw = $s['product_ids'] ?? '';
		if ( is_string( $ids_raw ) ) {
			$product_ids = array_filter( array_map( 'absint', explode( ',', $ids_raw ) ) );
		} else {
			$product_ids = array_filter( array_map( 'absint', (array) $ids_raw ) );
		}

		if ( empty( $product_ids ) || ! function_exists( 'wc_get_product' ) ) {
			return sprintf(
				'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;background-color:%s;">
					<tr><td style="padding:30px;text-align:center;font-family:%s;font-size:14px;color:#999999;">
						%s
					</td></tr>
				</table>',
				$mw, $mw, $mw, $bg, esc_attr( $font ),
				esc_html__( 'Products will appear here. Add product IDs to this section.', 'brevo-campaign-generator' )
			);
		}

		// Build product cells.
		$cells = array();
		foreach ( $product_ids as $pid ) {
			$product = wc_get_product( $pid );
			if ( ! $product ) {
				continue;
			}

			$thumb_id  = $product->get_image_id();
			$thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium' ) : wc_placeholder_img_src();
			$name      = esc_html( $product->get_name() );
			// For variable products, show "from £X" (min price only).
			if ( $product->is_type( 'variable' ) ) {
				/** @var WC_Product_Variable $product */
				$min_price  = wc_price( $product->get_variation_price( 'min', true ) );
				$price_disp = sprintf( /* translators: %s: minimum price */ __( 'from %s', 'brevo-campaign-generator' ), wp_strip_all_tags( $min_price ) );
			} else {
				$price_disp = wp_strip_all_tags( $product->get_price_html() );
			}
			$price_font = (int) ( $s['price_font_size'] ?? 16 );
			$price_html = $show_price ? sprintf( '<p style="font-family:%s;font-size:%dpx;font-weight:700;color:#333333;margin:8px 0;padding:0;">%s</p>', esc_attr( $font ), $price_font, $price_disp ) : '';
			$btn_html   = '';
			if ( $show_btn ) {
				$btn_html = sprintf(
					'<a href="%s" style="display:inline-block;margin-top:12px;padding:%dpx %dpx;background-color:%s;color:#ffffff;font-family:%s;font-size:%dpx;font-weight:700;text-decoration:none;border-radius:4px;">%s</a>',
					esc_url( $product->get_permalink() ),
					$btn_pad_v,
					$btn_pad_h,
					$btn_color,
					esc_attr( $font ),
					$btn_fsize,
					$btn_text
				);
			}

			$cells[] = sprintf(
				'<td style="vertical-align:top;padding:%dpx;width:%d%%;">
					<table width="100%%" cellpadding="0" cellspacing="0" border="0">
						<tr><td style="text-align:%s;">
							<img src="%s" alt="%s" width="200" style="display:block;%smax-width:100%%;height:auto;border:0;outline:none;" />
						</td></tr>
						<tr><td style="padding-top:12px;text-align:%s;">
							<h3 style="font-family:%s;font-size:%dpx;font-weight:700;color:#333333;margin:0;padding:0;text-align:%s;">%s</h3>
							%s
							%s
						</td></tr>
					</table>
				</td>',
				$prod_gap,
				(int) floor( 100 / $columns ),
				$text_align,
				esc_url( $thumb_url ),
				$name,
				'center' === $text_align ? 'margin:0 auto;' : '',
				$text_align,
				esc_attr( $font ),
				$title_fsize,
				$text_align,
				$name,
				$price_html,
				$btn_html
			);
		}

		if ( empty( $cells ) ) {
			return '';
		}

		// Chunk cells into rows based on columns setting.
		$rows_html = '';
		$chunks    = array_chunk( $cells, $columns );
		foreach ( $chunks as $chunk ) {
			// Pad the last row.
			while ( count( $chunk ) < $columns ) {
				$chunk[] = '<td style="width:' . (int) floor( 100 / $columns ) . '%;">&nbsp;</td>';
			}
			$rows_html .= '<tr>' . implode( '', $chunk ) . '</tr>';
		}

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;background-color:%s;">
				<tr><td style="padding:20px;">
					<table width="100%%" cellpadding="0" cellspacing="0" border="0">
						%s
					</table>
				</td></tr>
			</table>',
			$mw, $mw, $mw, $bg, $rows_html
		);
	}

	/**
	 * Render banner section.
	 *
	 * @since  1.5.0
	 * @param  array  $s     Settings.
	 * @param  int    $mw    Max width.
	 * @param  string $font  Font family.
	 * @return string
	 */
	private static function render_banner( array $s, int $mw, string $font ): string {
		$bg         = esc_attr( $s['bg_color'] );
		$tc         = esc_attr( $s['text_color'] );
		$pad        = (int) $s['padding'];
		$heading    = esc_html( $s['heading'] );
		$subtext    = esc_html( $s['subtext'] );
		$h_fsize    = (int) ( $s['heading_font_size'] ?? 26 );
		$s_fsize    = (int) ( $s['subtext_font_size'] ?? 15 );
		$align      = in_array( $s['text_align'] ?? 'center', array( 'left', 'center', 'right' ), true ) ? ( $s['text_align'] ?? 'center' ) : 'center';

		$subtext_html = '';
		if ( $subtext ) {
			$subtext_html = sprintf(
				'<tr><td style="padding-top:10px;text-align:%s;"><p style="font-family:%s;font-size:%dpx;color:%s;margin:0;padding:0;line-height:1.6;">%s</p></td></tr>',
				$align, esc_attr( $font ), $s_fsize, $tc, $subtext
			);
		}

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;background-color:%s;">
				<tr>
					<td style="padding:%dpx 30px;text-align:%s;">
						<table width="100%%" cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td style="text-align:%s;">
									<h2 style="font-family:%s;font-size:%dpx;font-weight:700;color:%s;margin:0;padding:0;">%s</h2>
								</td>
							</tr>
							%s
						</table>
					</td>
				</tr>
			</table>',
			$mw, $mw, $mw, $bg,
			$pad, $align,
			$align,
			esc_attr( $font ), $h_fsize, $tc, $heading,
			$subtext_html
		);
	}

	/**
	 * Render CTA section.
	 *
	 * @since  1.5.0
	 * @param  array  $s     Settings.
	 * @param  int    $mw    Max width.
	 * @param  string $font  Font family.
	 * @return string
	 */
	private static function render_cta( array $s, int $mw, string $font ): string {
		$bg         = esc_attr( $s['bg_color'] );
		$tc         = esc_attr( $s['text_color'] );
		$pad        = (int) $s['padding'];
		$btn_bg     = esc_attr( $s['button_bg'] );
		$btn_tc     = esc_attr( $s['button_text_color'] );
		$heading    = esc_html( $s['heading'] );
		$subtext    = esc_html( $s['subtext'] );
		$btn_lbl    = esc_html( $s['button_text'] );
		$btn_url    = esc_url( $s['button_url'] ?: '#' );
		$h_fsize    = (int) ( $s['heading_font_size'] ?? 26 );
		$s_fsize    = (int) ( $s['subtext_font_size'] ?? 15 );
		$btn_fsize  = (int) ( $s['button_font_size'] ?? 17 );
		$btn_pad_h  = (int) ( $s['button_padding_h'] ?? 40 );
		$btn_pad_v  = (int) ( $s['button_padding_v'] ?? 16 );

		$subtext_html = '';
		if ( $subtext ) {
			$subtext_html = sprintf(
				'<tr><td style="padding-bottom:20px;text-align:center;"><p style="font-family:%s;font-size:%dpx;color:%s;margin:0;padding:0;line-height:1.6;">%s</p></td></tr>',
				esc_attr( $font ), $s_fsize, $tc, $subtext
			);
		}

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;background-color:%s;">
				<tr>
					<td style="padding:%dpx 30px;text-align:center;">
						<table width="100%%" cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td style="padding-bottom:16px;text-align:center;">
									<h2 style="font-family:%s;font-size:%dpx;font-weight:700;color:%s;margin:0;padding:0;">%s</h2>
								</td>
							</tr>
							%s
							<tr>
								<td style="text-align:center;">
									<a href="%s" style="display:inline-block;padding:%dpx %dpx;background-color:%s;color:%s;font-family:%s;font-size:%dpx;font-weight:700;text-decoration:none;border-radius:4px;">%s</a>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>',
			$mw, $mw, $mw, $bg,
			$pad,
			esc_attr( $font ), $h_fsize, $tc, $heading,
			$subtext_html,
			$btn_url, $btn_pad_v, $btn_pad_h, $btn_bg, $btn_tc, esc_attr( $font ), $btn_fsize, $btn_lbl
		);
	}

	/**
	 * Render coupon section.
	 *
	 * @since  1.5.0
	 * @param  array  $s     Settings.
	 * @param  int    $mw    Max width.
	 * @param  string $font  Font family.
	 * @return string
	 */
	private static function render_coupon( array $s, int $mw, string $font ): string {
		$bg      = esc_attr( $s['bg_color'] );
		$accent  = esc_attr( $s['accent_color'] );
		$code    = esc_html( $s['coupon_code'] );
		$disc    = esc_html( $s['discount_text'] );
		$expiry  = esc_html( $s['expiry_text'] );

		$expiry_html = '';
		if ( $expiry ) {
			$expiry_html = sprintf(
				'<tr><td style="padding-top:8px;text-align:center;"><p style="font-family:%s;font-size:12px;color:#999999;margin:0;padding:0;">%s</p></td></tr>',
				esc_attr( $font ), $expiry
			);
		}

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;background-color:%s;">
				<tr>
					<td style="padding:30px;">
						<table width="100%%" cellpadding="0" cellspacing="0" border="0" style="border:2px dashed %s;border-radius:8px;">
							<tr>
								<td style="padding:24px;text-align:center;">
									<table width="100%%" cellpadding="0" cellspacing="0" border="0">
										<tr>
											<td style="text-align:center;">
												<p style="font-family:%s;font-size:15px;color:#333333;margin:0;padding:0;">%s</p>
											</td>
										</tr>
										<tr>
											<td style="padding-top:12px;text-align:center;">
												<span style="font-family:%s;font-size:28px;font-weight:900;letter-spacing:6px;color:%s;background-color:%s;padding:10px 20px;border-radius:4px;display:inline-block;">%s</span>
											</td>
										</tr>
										%s
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>',
			$mw, $mw, $mw, $bg,
			$accent,
			esc_attr( $font ), $disc,
			esc_attr( $font ), $accent, 'rgba(230,53,41,0.08)', $code,
			$expiry_html
		);
	}

	/**
	 * Render divider section.
	 *
	 * @since  1.5.0
	 * @param  array $s   Settings.
	 * @param  int   $mw  Max width.
	 * @return string
	 */
	private static function render_divider( array $s, int $mw ): string {
		$color  = esc_attr( $s['color'] );
		$thick  = (int) $s['thickness'];
		$mt     = (int) $s['margin_top'];
		$mb     = (int) $s['margin_bottom'];

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;">
				<tr>
					<td style="padding:%dpx 0 %dpx;">
						<table width="100%%" cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td style="height:%dpx;background-color:%s;font-size:0;line-height:0;">&nbsp;</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>',
			$mw, $mw, $mw,
			$mt, $mb,
			$thick, $color
		);
	}

	/**
	 * Render spacer section.
	 *
	 * @since  1.5.0
	 * @param  array $s Settings.
	 * @return string
	 */
	private static function render_spacer( array $s ): string {
		$h = (int) $s['height'];
		return sprintf(
			'<table width="100%%" cellpadding="0" cellspacing="0" border="0"><tr><td style="height:%dpx;font-size:0;line-height:0;">&nbsp;</td></tr></table>',
			$h
		);
	}

	/**
	 * Render footer section.
	 *
	 * @since  1.5.0
	 * @param  array  $s     Settings.
	 * @param  int    $mw    Max width.
	 * @param  string $font  Font family.
	 * @return string
	 */
	private static function render_footer( array $s, int $mw, string $font ): string {
		$bg   = esc_attr( $s['bg_color'] );
		$tc   = esc_attr( $s['text_color'] );
		$text = esc_html( $s['footer_text'] );

		$links_html = '';
		if ( ! empty( $s['show_unsubscribe'] ) ) {
			$links_data = is_string( $s['footer_links'] ) ? json_decode( $s['footer_links'], true ) : $s['footer_links'];
			if ( is_array( $links_data ) ) {
				$link_items = array();
				foreach ( $links_data as $link ) {
					if ( ! empty( $link['label'] ) ) {
						$href         = ! empty( $link['url'] ) ? esc_url( $link['url'] ) : '#';
						$link_items[] = sprintf(
							'<a href="%s" style="font-family:%s;font-size:12px;color:%s;text-decoration:underline;">%s</a>',
							$href, esc_attr( $font ), $tc, esc_html( $link['label'] )
						);
					}
				}
				if ( $link_items ) {
					$links_html = '<tr><td style="padding-top:10px;text-align:center;">' . implode( ' &nbsp;|&nbsp; ', $link_items ) . '</td></tr>';
				}
			}
		}

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;background-color:%s;">
				<tr>
					<td style="padding:24px 30px;">
						<table width="100%%" cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td style="text-align:center;">
									<p style="font-family:%s;font-size:12px;color:%s;margin:0;padding:0;line-height:1.6;">%s</p>
								</td>
							</tr>
							%s
						</table>
					</td>
				</tr>
			</table>',
			$mw, $mw, $mw, $bg,
			esc_attr( $font ), $tc, $text,
			$links_html
		);
	}

	/**
	 * Render heading section.
	 *
	 * @since  1.5.0
	 * @param  array  $s     Settings.
	 * @param  int    $mw    Max width.
	 * @param  string $font  Font family.
	 * @return string
	 */
	private static function render_heading( array $s, int $mw, string $font ): string {
		$bg      = esc_attr( $s['bg_color'] );
		$tc      = esc_attr( $s['text_color'] );
		$accent  = esc_attr( $s['accent_color'] );
		$align   = in_array( $s['alignment'], array( 'left', 'center', 'right' ), true ) ? $s['alignment'] : 'center';
		$fsize   = (int) $s['font_size'];
		$pad     = (int) $s['padding'];
		$text    = esc_html( $s['text'] );
		$subtext = esc_html( $s['subtext'] ?? '' );

		$subtext_html = '';
		if ( $subtext ) {
			$subtext_html = sprintf(
				'<tr><td style="padding-top:8px;text-align:%s;"><p style="font-family:%s;font-size:15px;color:%s;margin:0;padding:0;">%s</p></td></tr>',
				$align, esc_attr( $font ), $tc, $subtext
			);
		}

		$accent_html = '';
		if ( ! empty( $s['show_accent'] ) ) {
			$margin_css = 'center' === $align ? 'margin:10px auto 0;' : ( 'right' === $align ? 'margin:10px 0 0 auto;' : 'margin:10px 0 0;' );
			$accent_html = sprintf(
				'<tr><td style="text-align:%s;"><div style="width:48px;height:3px;background-color:%s;border-radius:2px;%s"></div></td></tr>',
				$align, $accent, $margin_css
			);
		}

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;background-color:%s;">
				<tr>
					<td style="padding:%dpx 30px;">
						<table width="100%%" cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td style="text-align:%s;">
									<h2 style="font-family:%s;font-size:%dpx;font-weight:700;color:%s;margin:0;padding:0;line-height:1.2;">%s</h2>
								</td>
							</tr>
							%s
							%s
						</table>
					</td>
				</tr>
			</table>',
			$mw, $mw, $mw, $bg,
			$pad,
			$align,
			esc_attr( $font ), $fsize, $tc, $text,
			$accent_html,
			$subtext_html
		);
	}

	/**
	 * Render list section.
	 *
	 * @since  1.5.0
	 * @param  array  $s     Settings.
	 * @param  int    $mw    Max width.
	 * @param  string $font  Font family.
	 * @return string
	 */
	private static function render_list( array $s, int $mw, string $font ): string {
		$bg      = esc_attr( $s['bg_color'] );
		$tc      = esc_attr( $s['text_color'] );
		$accent  = esc_attr( $s['accent_color'] );
		$fsize   = (int) $s['font_size'];
		$pad     = (int) $s['padding'];
		$text_align = in_array( $s['text_align'] ?? 'left', array( 'left', 'center', 'right' ), true ) ? ( $s['text_align'] ?? 'left' ) : 'left';
		$style   = $s['list_style'] ?? 'bullets';

		// Decode items JSON.
		$items_data = is_string( $s['items'] ) ? json_decode( $s['items'], true ) : $s['items'];
		if ( ! is_array( $items_data ) ) {
			$items_data = array();
		}

		// Heading.
		$heading_html = '';
		if ( ! empty( $s['heading'] ) ) {
			$heading_html = sprintf(
				'<tr><td style="padding-bottom:14px;text-align:%s;"><h3 style="font-family:%s;font-size:18px;font-weight:700;color:%s;margin:0;padding:0;text-align:%s;">%s</h3></td></tr>',
				$text_align, esc_attr( $font ), $tc, $text_align, esc_html( $s['heading'] )
			);
		}

		// Build list items.
		$rows = '';
		foreach ( $items_data as $i => $item ) {
			$item_text = esc_html( is_array( $item ) ? ( $item['text'] ?? '' ) : (string) $item );

			if ( 'checks' === $style ) {
				$marker = '<span style="color:' . $accent . ';font-size:16px;line-height:1;">&#10003;</span>';
			} elseif ( 'numbers' === $style ) {
				$marker = '<span style="font-family:' . esc_attr( $font ) . ';font-size:' . $fsize . 'px;font-weight:700;color:' . $accent . ';">' . ( $i + 1 ) . '.</span>';
			} elseif ( 'bullets' === $style ) {
				$marker = '<span style="color:' . $accent . ';font-size:20px;line-height:1;">&#8226;</span>';
			} else {
				$marker = '';
			}

			$rows .= sprintf(
				'<tr>
					<td style="padding:5px 0;vertical-align:top;">
						<table width="100%%" cellpadding="0" cellspacing="0" border="0">
							<tr>
								%s
								<td style="padding-left:%s;font-family:%s;font-size:%dpx;color:%s;line-height:1.6;">%s</td>
							</tr>
						</table>
					</td>
				</tr>',
				$marker ? '<td style="width:24px;vertical-align:top;padding-top:2px;">' . $marker . '</td>' : '',
				$marker ? '0' : '0px',
				esc_attr( $font ),
				$fsize,
				$tc,
				$item_text
			);
		}

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;background-color:%s;">
				<tr>
					<td style="padding:%dpx 30px;">
						<table width="100%%" cellpadding="0" cellspacing="0" border="0">
							%s
							<tr>
								<td>
									<table width="100%%" cellpadding="0" cellspacing="0" border="0">
										%s
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>',
			$mw, $mw, $mw, $bg,
			$pad,
			$heading_html,
			$rows
		);
	}

	// ── Email Shell ───────────────────────────────────────────────────

	/**
	 * Wrap rendered sections in a complete HTML email shell.
	 *
	 * @since  1.5.0
	 * @param  string $sections_html Rendered inner HTML.
	 * @param  int    $max_width     Max width in pixels.
	 * @param  string $font          Font family string.
	 * @param  string $store_url     Store URL for fallback link.
	 * @return string Complete email HTML document.
	 */
	private static function wrap_in_email_shell( string $sections_html, int $max_width, string $font, string $store_url ): string {
		return sprintf(
			'<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>%s</title>
<!--[if mso]>
<style>
* { font-family: %s !important; }
</style>
<![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#f5f5f5;font-family:%s;">
<center>
<table width="100%%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f5f5;">
<tr>
<td align="center" style="padding:20px 0;">
<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;">
<tr><td>
%s
</td></tr>
</table>
</td>
</tr>
</table>
</center>
</body>
</html>',
			esc_html( get_bloginfo( 'name' ) ),
			esc_html( $font ),
			esc_html( $font ),
			$max_width, $max_width, $max_width,
			$sections_html
		);
	}
}
