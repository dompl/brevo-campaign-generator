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
			case 'hero_split':
				return self::render_hero_split( $s, $max_width, $font_family );
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
			case 'coupon_banner':
				return self::render_coupon_banner( $s, $max_width, $font_family );
			case 'coupon_card':
				return self::render_coupon_card( $s, $max_width, $font_family );
			case 'coupon_split':
				return self::render_coupon_split( $s, $max_width, $font_family );
			case 'coupon_minimal':
				return self::render_coupon_minimal( $s, $max_width, $font_family );
			case 'coupon_ribbon':
				return self::render_coupon_ribbon( $s, $max_width, $font_family );
			case 'divider':
				return self::render_divider( $s, $max_width );
			case 'spacer':
				return self::render_spacer( $s );
			case 'heading':
				return self::render_heading( $s, $max_width, $font_family );
			case 'list':
				return self::render_list( $s, $max_width, $font_family );
			case 'social':
				return self::render_social( $s, $max_width, $font_family );
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
		$bg         = esc_attr( $s['bg_color'] );
		$text_color = esc_attr( $s['text_color'] ?? '#333333' );
		$logo_url   = esc_url( $s['logo_url'] );
		$logo_w     = (int) $s['logo_width'];

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
				'<span style="font-family:%s;font-size:22px;font-weight:700;color:%s;">%s</span>',
				esc_attr( $font ),
				$text_color,
				esc_html( get_bloginfo( 'name' ) )
			);
		}

		$nav_html = '';
		if ( ! empty( $s['show_nav'] ) ) {
			$links    = array();
			$nav_data = is_string( $s['nav_links'] ) ? json_decode( $s['nav_links'], true ) : $s['nav_links'];
			if ( is_array( $nav_data ) ) {
				foreach ( $nav_data as $link ) {
					if ( ! empty( $link['label'] ) ) {
						$href    = ! empty( $link['url'] ) ? esc_url( $link['url'] ) : '#';
						$links[] = sprintf(
							'<a href="%s" style="font-family:%s;font-size:14px;color:%s;text-decoration:none;margin-left:16px;">%s</a>',
							$href,
							esc_attr( $font ),
							$text_color,
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
		$cta_radius = (int) ( $s['cta_border_radius'] ?? 4 );
		$s_fsize    = (int) ( $s['subtext_font_size'] ?? 16 );
		$cta_html = '';
		if ( $cta_text ) {
			$cta_html = sprintf(
				'<tr><td style="padding-top:24px;text-align:center;">
					<a href="%s" style="display:inline-block;padding:%dpx %dpx;background-color:%s;color:%s;font-family:%s;font-size:%dpx;font-weight:700;text-decoration:none;border-radius:%dpx;">%s</a>
				</td></tr>',
				$cta_url, $cta_pad_v, $cta_pad_h, $cta_bg, $cta_tc, esc_attr( $font ), $cta_fsize, $cta_radius, $cta_text
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
	 * Render hero split section — image on one side, text on the other.
	 *
	 * @since  1.5.39
	 * @param  array  $s     Settings.
	 * @param  int    $mw    Max width.
	 * @param  string $font  Font family.
	 * @return string
	 */
	private static function render_hero_split( array $s, int $mw, string $font ): string {
		$img_url   = esc_url( $s['image_url'] ?? '' );
		$img_side  = in_array( $s['image_side'] ?? 'right', array( 'left', 'right' ), true ) ? $s['image_side'] : 'right';
		$text_bg   = esc_attr( $s['text_bg_color'] ?? '#1a1a2e' );
		$headline  = esc_html( $s['headline'] ?? '' );
		$hsize     = (int) ( $s['headline_size'] ?? 32 );
		$hcolor    = esc_attr( $s['headline_color'] ?? '#ffffff' );
		$subtext   = esc_html( $s['subtext'] ?? '' );
		$scolor    = esc_attr( $s['subtext_color'] ?? '#cccccc' );
		$sfsize    = (int) ( $s['subtext_font_size'] ?? 15 );
		$cta_text  = esc_html( $s['cta_text'] ?? '' );
		$cta_url   = esc_url( $s['cta_url'] ?: '#' );
		$cta_bg    = esc_attr( $s['cta_bg_color'] ?? '#e63529' );
		$cta_tc    = esc_attr( $s['cta_text_color'] ?? '#ffffff' );
		$cta_r     = (int) ( $s['cta_border_radius'] ?? 4 );
		$tp        = (int) ( $s['text_padding'] ?? $s['padding_top'] ?? 48 );
		$half_w    = (int) round( $mw / 2 );

		$cta_html = '';
		if ( $cta_text ) {
			$cta_html = sprintf(
				'<tr><td style="padding-top:22px;"><a href="%s" style="display:inline-block;padding:12px 28px;background-color:%s;color:%s;font-family:%s;font-size:14px;font-weight:700;text-decoration:none;border-radius:%dpx;">%s</a></td></tr>',
				$cta_url, $cta_bg, $cta_tc, esc_attr( $font ), $cta_r, $cta_text
			);
		}

		$text_td = sprintf(
			'<td width="%d" valign="middle" bgcolor="%s" style="background-color:%s;padding:%dpx 28px;width:%dpx;">
				<table width="100%%" cellpadding="0" cellspacing="0" border="0">
					<tr><td><h2 style="font-family:%s;font-size:%dpx;font-weight:700;color:%s;margin:0;padding:0;line-height:1.2;">%s</h2></td></tr>
					<tr><td style="padding-top:14px;"><p style="font-family:%s;font-size:%dpx;color:%s;margin:0;padding:0;line-height:1.65;">%s</p></td></tr>
					%s
				</table>
			</td>',
			$half_w, $text_bg, $text_bg, $tp, $half_w,
			esc_attr( $font ), $hsize, $hcolor, $headline,
			esc_attr( $font ), $sfsize, $scolor, $subtext,
			$cta_html
		);

		// Image column: use background-image so it scales to the row height.
		// For Outlook compatibility, a VML fallback fills with the bg colour.
		if ( $img_url ) {
			$img_td = sprintf(
				'<!--[if mso]><td width="%1$d" valign="middle" bgcolor="%2$s" style="background-color:%2$s;width:%1$dpx;">&nbsp;</td><![endif]-->
				<!--[if !mso]><!-->'
				. '<td width="%1$d" valign="middle" bgcolor="%2$s" style="background-color:%2$s;background-image:url(\'%3$s\');background-size:cover;background-position:center center;width:%1$dpx;">'
				. '<div style="font-size:0;line-height:0;">&nbsp;</div></td>'
				. '<!--<![endif]-->',
				$half_w, $text_bg, $img_url
			);
		} else {
			$img_td = sprintf(
				'<td width="%d" valign="middle" bgcolor="%s" style="background-color:%s;width:%dpx;">&nbsp;</td>',
				$half_w, $text_bg, $text_bg, $half_w
			);
		}

		$cells = ( 'left' === $img_side ) ? $img_td . $text_td : $text_td . $img_td;

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;">
				<tr>%s</tr>
			</table>',
			$mw, $mw, $mw, $cells
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
		$bg       = esc_attr( $s['bg_color'] );
		$tc       = esc_attr( $s['text_color'] );
		$pt       = (int) ( $s['padding_top']    ?? $s['padding'] ?? 30 );
		$pb       = (int) ( $s['padding_bottom'] ?? $s['padding'] ?? 30 );
		$fsize    = (int) $s['font_size'];
		$hsize    = (int) ( $s['heading_size']  ?? 22 );
		$lh       = sprintf( '%.1f', (int) ( $s['line_height'] ?? 170 ) / 100 );
		$align    = in_array( $s['alignment'], array( 'left', 'center', 'right' ), true ) ? $s['alignment'] : 'left';

		$heading_html = '';
		if ( ! empty( $s['heading'] ) ) {
			$heading_html = sprintf(
				'<tr><td style="padding-bottom:12px;"><h2 style="font-family:%s;font-size:%dpx;font-weight:700;color:%s;margin:0;padding:0;text-align:%s;">%s</h2></td></tr>',
				esc_attr( $font ), $hsize, $tc, $align, esc_html( $s['heading'] )
			);
		}

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;background-color:%s;">
				<tr>
					<td style="padding:%dpx 30px %dpx;">
						<table width="100%%" cellpadding="0" cellspacing="0" border="0">
							%s
							<tr>
								<td>
									<p style="font-family:%s;font-size:%dpx;color:%s;margin:0;padding:0;line-height:%s;text-align:%s;">%s</p>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>',
			$mw, $mw, $mw, $bg,
			$pt, $pb,
			$heading_html,
			esc_attr( $font ), $fsize, $tc, $lh, $align,
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
		$section_headline = $s['section_headline'] ?? '';
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
		$btn_tc     = esc_attr( $s['button_text_color'] ?? '#ffffff' );
		$btn_radius = (int) ( $s['button_border_radius'] ?? 4 );
		$prod_gap    = (int) ( $s['product_gap'] ?? 15 );
		$text_align  = in_array( $s['text_align'] ?? 'left', array( 'left', 'center', 'right' ), true ) ? $s['text_align'] : 'left';
		$square_imgs = ! empty( $s['square_images'] );
		$img_size    = (int) ( $s['image_size'] ?? 200 );

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
					'<a href="%s" style="display:inline-block;margin-top:12px;padding:%dpx %dpx;background-color:%s;color:%s;font-family:%s;font-size:%dpx;font-weight:700;text-decoration:none;border-radius:%dpx;">%s</a>',
					esc_url( $product->get_permalink() ),
					$btn_pad_v,
					$btn_pad_h,
					$btn_color,
					$btn_tc,
					esc_attr( $font ),
					$btn_fsize,
					$btn_radius,
					$btn_text
				);
			}

			// Build image attributes — square crop or natural height.
			$img_style = $square_imgs
				? sprintf( 'display:block;%swidth:%dpx;height:%dpx;object-fit:cover;border:0;outline:none;', 'center' === $text_align ? 'margin:0 auto;' : '', $img_size, $img_size )
				: sprintf( 'display:block;%smax-width:100%%;height:auto;border:0;outline:none;',             'center' === $text_align ? 'margin:0 auto;' : '' );
			$img_attrs = $square_imgs
				? sprintf( 'width="%d" height="%d"', $img_size, $img_size )
				: 'width="200"';

			$cells[] = sprintf(
				'<td style="vertical-align:top;padding:%dpx;width:%d%%;">
					<table width="100%%" cellpadding="0" cellspacing="0" border="0">
						<tr><td style="text-align:%s;">
							<img src="%s" alt="%s" %s style="%s" />
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
				$img_attrs,
				$img_style,
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
				$chunk[] = '<td class="bcg-p" style="width:' . (int) floor( 100 / $columns ) . '%;font-size:0;">&nbsp;</td>';
			}
			$rows_html .= '<tr>' . implode( '', $chunk ) . '</tr>';
		}

		$section_headline_html = '';
		if ( ! empty( $section_headline ) ) {
			$section_headline_html = sprintf(
				'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;background-color:%s;">
					<tr><td style="padding:16px 20px 0;">
						<h2 style="font-family:%s;font-size:22px;font-weight:700;color:#333333;margin:0;padding:0;">%s</h2>
					</td></tr>
				</table>',
				$mw, $mw, $mw, $bg,
				esc_attr( $font ),
				esc_html( $section_headline )
			);
		}

		return $section_headline_html . sprintf(
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
		$pt         = (int) ( $s['padding_top']    ?? $s['padding'] ?? 30 );
		$pb         = (int) ( $s['padding_bottom'] ?? $s['padding'] ?? 30 );
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
					<td style="padding:%dpx 30px %dpx;text-align:%s;">
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
			$pt, $pb, $align,
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
		$pt         = (int) ( $s['padding_top']    ?? $s['padding'] ?? 40 );
		$pb         = (int) ( $s['padding_bottom'] ?? $s['padding'] ?? 40 );
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
		$btn_radius = (int) ( $s['button_border_radius'] ?? 4 );

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
					<td style="padding:%dpx 30px %dpx;text-align:center;">
						<table width="100%%" cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td style="padding-bottom:16px;text-align:center;">
									<h2 style="font-family:%s;font-size:%dpx;font-weight:700;color:%s;margin:0;padding:0;">%s</h2>
								</td>
							</tr>
							%s
							<tr>
								<td style="text-align:center;">
									<a href="%s" style="display:inline-block;padding:%dpx %dpx;background-color:%s;color:%s;font-family:%s;font-size:%dpx;font-weight:700;text-decoration:none;border-radius:%dpx;">%s</a>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>',
			$mw, $mw, $mw, $bg,
			$pt, $pb,
			esc_attr( $font ), $h_fsize, $tc, $heading,
			$subtext_html,
			$btn_url, $btn_pad_v, $btn_pad_h, $btn_bg, $btn_tc, esc_attr( $font ), $btn_fsize, $btn_radius, $btn_lbl
		);
	}

	/**
	 * Render coupon section (Classic variant).
	 *
	 * @since  1.5.0
	 * @param  array  $s     Settings.
	 * @param  int    $mw    Max width.
	 * @param  string $font  Font family.
	 * @return string
	 */
	private static function render_coupon( array $s, int $mw, string $font ): string {
		$bg      = esc_attr( $s['bg_color'] ?? '#fff8e6' );
		$accent  = esc_attr( $s['accent_color'] ?? '#e63529' );
		$text_c  = esc_attr( $s['text_color'] ?? '#333333' );
		$code    = esc_html( $s['coupon_code'] ?? '' );
		$disc    = esc_html( $s['coupon_text'] ?? $s['discount_text'] ?? '' );
		$head    = esc_html( $s['headline'] ?? '' );
		$sub     = esc_html( $s['subtext'] ?? '' );
		$pt      = absint( $s['padding_top'] ?? 30 );
		$pb      = absint( $s['padding_bottom'] ?? 30 );

		$expiry_date = $s['expiry_date'] ?? '';
		$expiry_html = '';
		if ( ! empty( $expiry_date ) ) {
			$formatted   = date( 'd M Y', strtotime( $expiry_date ) );
			$expiry_html = sprintf(
				'<tr><td style="padding-top:8px;text-align:center;"><span style="font-family:%s;font-size:12px;color:#999999;">%s</span></td></tr>',
				esc_attr( $font ),
				esc_html( $formatted )
			);
		}

		$headline_html = $head ? sprintf(
			'<tr><td style="text-align:center;padding-bottom:8px;"><p style="font-family:%s;font-size:13px;font-weight:600;color:%s;margin:0;text-transform:uppercase;letter-spacing:1px;">%s</p></td></tr>',
			esc_attr( $font ), $text_c, $head
		) : '';

		$subtext_html = $sub ? sprintf(
			'<tr><td style="padding-top:6px;text-align:center;"><p style="font-family:%s;font-size:13px;color:%s;margin:0;">%s</p></td></tr>',
			esc_attr( $font ), $text_c, $sub
		) : '';

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;background-color:%s;">
				<tr><td style="padding:%dpx 30px %dpx;">
					<table width="100%%" cellpadding="0" cellspacing="0" border="0" style="border:2px dashed %s;border-radius:8px;">
						<tr><td style="padding:24px;text-align:center;">
							<table width="100%%" cellpadding="0" cellspacing="0" border="0">
								%s
								<tr><td style="text-align:center;%s"><p style="font-family:%s;font-size:15px;color:%s;margin:0;">%s</p></td></tr>
								<tr><td style="padding-top:12px;text-align:center;"><span style="font-family:%s;font-size:28px;font-weight:900;letter-spacing:6px;color:%s;background-color:rgba(230,53,41,0.08);padding:10px 20px;border-radius:4px;display:inline-block;">%s</span></td></tr>
								%s
								%s
							</table>
						</td></tr>
					</table>
				</td></tr>
			</table>',
			$mw, $mw, $mw, $bg,
			$pt, $pb,
			$accent,
			$headline_html,
			$head ? 'padding-top:8px;' : '',
			esc_attr( $font ), $text_c, $disc,
			esc_attr( $font ), $accent, $code,
			$subtext_html,
			$expiry_html
		);
	}

	/**
	 * Render coupon banner section — dark full-width horizontal layout.
	 *
	 * @since  1.5.2
	 * @param  array  $s     Settings.
	 * @param  int    $mw    Max width.
	 * @param  string $font  Font family.
	 * @return string
	 */
	private static function render_coupon_banner( array $s, int $mw, string $font ): string {
		$bg       = esc_attr( $s['bg_color'] ?? '#1a1a2e' );
		$accent   = esc_attr( $s['accent_color'] ?? '#e63529' );
		$text_c   = esc_attr( $s['text_color'] ?? '#ffffff' );
		$head     = esc_html( $s['headline'] ?? '' );
		$disc     = esc_html( $s['coupon_text'] ?? $s['discount_text'] ?? '' );
		$code     = esc_html( $s['coupon_code'] ?? '' );
		$sub      = esc_html( $s['subtext'] ?? '' );
		$pt       = absint( $s['padding_top'] ?? 28 );
		$pb       = absint( $s['padding_bottom'] ?? 28 );

		$expiry_date = $s['expiry_date'] ?? '';
		$expiry_row  = '';
		if ( ! empty( $expiry_date ) ) {
			$formatted  = date( 'd M Y', strtotime( $expiry_date ) );
			$expiry_row = sprintf(
				'<tr><td style="font-family:%s;font-size:11px;color:rgba(255,255,255,0.5);padding-top:8px;">%s</td></tr>',
				esc_attr( $font ),
				esc_html( $formatted )
			);
		}

		$left_w  = (int) round( $mw * 0.55 );
		$right_w = $mw - $left_w;

		$head_row = $head ? sprintf(
			'<tr><td style="font-family:%s;font-size:11px;font-weight:700;color:%s;text-transform:uppercase;letter-spacing:1.5px;padding-bottom:6px;">%s</td></tr>',
			esc_attr( $font ), $text_c, $head
		) : '';

		$sub_row = $sub ? sprintf(
			'<tr><td style="font-family:%s;font-size:12px;color:rgba(255,255,255,0.7);padding-top:4px;">%s</td></tr>',
			esc_attr( $font ), $sub
		) : '';

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;background-color:%s;">
				<tr>
					<td width="%d" valign="middle" style="padding:%dpx 24px %dpx 30px;width:%dpx;">
						<table cellpadding="0" cellspacing="0" border="0">
							%s
							<tr><td style="font-family:%s;font-size:22px;font-weight:800;color:%s;line-height:1.1;">%s</td></tr>
							%s
							%s
						</table>
					</td>
					<td width="%d" valign="middle" align="center" style="padding:%dpx 30px %dpx 24px;width:%dpx;border-left:1px solid rgba(255,255,255,0.1);">
						<p style="font-family:%s;font-size:11px;color:rgba(255,255,255,0.6);margin:0 0 6px;text-transform:uppercase;letter-spacing:1px;">Use code</p>
						<span style="font-family:%s;font-size:22px;font-weight:900;letter-spacing:4px;color:%s;background:%s;padding:8px 14px;border-radius:4px;display:inline-block;">%s</span>
					</td>
				</tr>
			</table>',
			$mw, $mw, $mw, $bg,
			$left_w, $pt, $pb, $left_w,
			$head_row,
			esc_attr( $font ), $text_c, $disc,
			$sub_row, $expiry_row,
			$right_w, $pt, $pb, $right_w,
			esc_attr( $font ),
			esc_attr( $font ), $accent, 'rgba(230,53,41,0.25)', $code
		);
	}

	/**
	 * Render coupon card section — white card with decorative left border.
	 *
	 * @since  1.5.2
	 * @param  array  $s     Settings.
	 * @param  int    $mw    Max width.
	 * @param  string $font  Font family.
	 * @return string
	 */
	private static function render_coupon_card( array $s, int $mw, string $font ): string {
		$bg       = esc_attr( $s['bg_color'] ?? '#ffffff' );
		$card_bg  = esc_attr( $s['card_bg'] ?? '#f8f9ff' );
		$accent   = esc_attr( $s['accent_color'] ?? '#e63529' );
		$text_c   = esc_attr( $s['text_color'] ?? '#222222' );
		$head     = esc_html( $s['headline'] ?? '' );
		$disc     = esc_html( $s['coupon_text'] ?? $s['discount_text'] ?? '' );
		$code     = esc_html( $s['coupon_code'] ?? '' );
		$sub      = esc_html( $s['subtext'] ?? '' );
		$pt       = absint( $s['padding_top'] ?? 24 );
		$pb       = absint( $s['padding_bottom'] ?? 24 );

		$expiry_date = $s['expiry_date'] ?? '';
		$expiry_row  = '';
		if ( ! empty( $expiry_date ) ) {
			$formatted  = date( 'd M Y', strtotime( $expiry_date ) );
			$expiry_row = sprintf(
				'<tr><td style="font-family:%s;font-size:12px;color:#aaaaaa;padding-top:8px;">%s</td></tr>',
				esc_attr( $font ),
				esc_html( $formatted )
			);
		}

		$inner_w = $mw - 48;

		$head_row = $head ? sprintf(
			'<tr><td style="font-family:%s;font-size:12px;font-weight:700;color:%s;text-transform:uppercase;letter-spacing:1px;padding-bottom:10px;">%s</td></tr>',
			esc_attr( $font ), $accent, $head
		) : '';

		$sub_row = $sub ? sprintf(
			'<tr><td style="font-family:%s;font-size:13px;color:#888888;padding-top:6px;">%s</td></tr>',
			esc_attr( $font ), $sub
		) : '';

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;background-color:%s;">
				<tr><td style="padding:%dpx 24px %dpx;">
					<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;background-color:%s;border-left:4px solid %s;border-radius:0 6px 6px 0;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
						<tr><td style="padding:24px 28px;">
							<table width="100%%" cellpadding="0" cellspacing="0" border="0">
								%s
								<tr><td style="font-family:%s;font-size:18px;font-weight:700;color:%s;padding-bottom:14px;">%s</td></tr>
								<tr>
									<td style="font-family:%s;font-size:11px;color:#999999;text-transform:uppercase;letter-spacing:1px;padding-bottom:4px;display:block;">Coupon code</td>
								</tr>
								<tr>
									<td><table cellpadding="0" cellspacing="0" border="0"><tr>
										<td style="font-family:%s;font-size:20px;font-weight:900;letter-spacing:5px;color:%s;background:rgba(230,53,41,0.07);border:1.5px dashed %s;padding:10px 18px;border-radius:4px;">%s</td>
									</tr></table></td>
								</tr>
								%s
								%s
							</table>
						</td></tr>
					</table>
				</td></tr>
			</table>',
			$mw, $mw, $mw, $bg,
			$pt, $pb,
			$inner_w, $inner_w, $card_bg, $accent,
			$head_row,
			esc_attr( $font ), $text_c, $disc,
			esc_attr( $font ),
			esc_attr( $font ), $accent, $accent, $code,
			$sub_row, $expiry_row
		);
	}

	/**
	 * Render coupon split section — two-column layout with large discount on left.
	 *
	 * @since  1.5.2
	 * @param  array  $s     Settings.
	 * @param  int    $mw    Max width.
	 * @param  string $font  Font family.
	 * @return string
	 */
	private static function render_coupon_split( array $s, int $mw, string $font ): string {
		$left_bg   = esc_attr( $s['left_bg'] ?? '#e63529' );
		$right_bg  = esc_attr( $s['right_bg'] ?? '#ffffff' );
		$lt        = esc_attr( $s['left_text_color'] ?? '#ffffff' );
		$rt        = esc_attr( $s['right_text_color'] ?? '#222222' );
		$accent    = esc_attr( $s['accent_color'] ?? '#e63529' );
		$head      = esc_html( $s['headline'] ?? '' );
		$disc      = esc_html( $s['discount_text'] ?? '' );
		$disc_lbl  = esc_html( $s['discount_label'] ?? 'OFF' );
		$code      = esc_html( $s['coupon_code'] ?? '' );
		$sub       = esc_html( $s['subtext'] ?? '' );
		$pt        = absint( $s['padding_top'] ?? 0 );
		$pb        = absint( $s['padding_bottom'] ?? 0 );

		$expiry_date = $s['expiry_date'] ?? '';
		$expiry_row  = '';
		if ( ! empty( $expiry_date ) ) {
			$formatted  = date( 'd M Y', strtotime( $expiry_date ) );
			$expiry_row = sprintf(
				'<tr><td style="font-family:%s;font-size:12px;color:#aaaaaa;padding-top:8px;">%s</td></tr>',
				esc_attr( $font ),
				esc_html( $formatted )
			);
		}

		$half_w = (int) round( $mw / 2 );

		$head_row = $head ? sprintf(
			'<tr><td style="font-family:%s;font-size:12px;font-weight:700;color:%s;text-transform:uppercase;letter-spacing:1px;padding-bottom:8px;opacity:0.85;">%s</td></tr>',
			esc_attr( $font ), $rt, $head
		) : '';

		$sub_row = $sub ? sprintf(
			'<tr><td style="font-family:%s;font-size:13px;color:#666666;padding-top:6px;">%s</td></tr>',
			esc_attr( $font ), $sub
		) : '';

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;">
				<tr>
					<td width="%d" valign="middle" align="center" bgcolor="%s" style="background-color:%s;padding:%dpx 20px %dpx;width:%dpx;">
						<p style="font-family:%s;font-size:56px;font-weight:900;color:%s;margin:0;line-height:1;">%s</p>
						<p style="font-family:%s;font-size:18px;font-weight:700;color:%s;margin:0;letter-spacing:3px;opacity:0.9;">%s</p>
					</td>
					<td width="%d" valign="middle" bgcolor="%s" style="background-color:%s;padding:%dpx 28px %dpx;width:%dpx;">
						<table width="100%%" cellpadding="0" cellspacing="0" border="0">
							%s
							<tr><td style="font-family:%s;font-size:11px;color:#999999;text-transform:uppercase;letter-spacing:1px;padding-bottom:4px;">Your code</td></tr>
							<tr><td style="font-family:%s;font-size:20px;font-weight:900;letter-spacing:4px;color:%s;background:rgba(0,0,0,0.04);border:1.5px dashed %s;padding:8px 14px;border-radius:4px;display:inline-block;">%s</td></tr>
							%s
							%s
						</table>
					</td>
				</tr>
			</table>',
			$mw, $mw, $mw,
			$half_w, $left_bg, $left_bg, max(30, $pt + 30), max(30, $pb + 30), $half_w,
			esc_attr( $font ), $lt, $disc,
			esc_attr( $font ), $lt, $disc_lbl,
			$half_w, $right_bg, $right_bg, max(24, $pt + 24), max(24, $pb + 24), $half_w,
			$head_row,
			esc_attr( $font ),
			esc_attr( $font ), $accent, $accent, $code,
			$sub_row, $expiry_row
		);
	}

	/**
	 * Render coupon minimal section — clean borderless minimal design.
	 *
	 * @since  1.6.0
	 * @param  array  $s     Settings.
	 * @param  int    $mw    Max width.
	 * @param  string $font  Font family.
	 * @return string
	 */
	private static function render_coupon_minimal( array $s, int $mw, string $font ): string {
		$bg      = esc_attr( $s['bg_color'] ?? '#f9f9f9' );
		$text_c  = esc_attr( $s['text_color'] ?? '#222222' );
		$accent  = esc_attr( $s['accent_color'] ?? '#e63529' );
		$code    = esc_html( $s['coupon_code'] ?? '' );
		$head    = esc_html( $s['headline'] ?? '' );
		$coupon_text = esc_html( $s['coupon_text'] ?? '' );
		$sub     = esc_html( $s['subtext'] ?? '' );
		$pt      = absint( $s['padding_top'] ?? 40 );
		$pb      = absint( $s['padding_bottom'] ?? 40 );

		$expiry_date = $s['expiry_date'] ?? '';
		$expiry_html = '';
		if ( ! empty( $expiry_date ) ) {
			$formatted   = date( 'd M Y', strtotime( $expiry_date ) );
			$expiry_html = sprintf(
				'<tr><td style="text-align:center;padding-top:8px;"><span style="font-family:%s;font-size:11px;color:%s;opacity:0.5;">%s</span></td></tr>',
				esc_attr( $font ),
				$text_c,
				esc_html( $formatted )
			);
		}

		$head_html = $head ? sprintf(
			'<tr><td style="text-align:center;padding-bottom:8px;"><p style="margin:0 0 8px;font-family:%s;font-size:13px;color:%s;opacity:0.7;">%s</p></td></tr>',
			esc_attr( $font ), $text_c, $head
		) : '';

		$coupon_text_html = $coupon_text ? sprintf(
			'<tr><td style="text-align:center;padding-bottom:12px;"><p style="margin:0 0 12px;font-family:%s;font-size:18px;font-weight:700;color:%s;">%s</p></td></tr>',
			esc_attr( $font ), $text_c, $coupon_text
		) : '';

		$sub_html = $sub ? sprintf(
			'<tr><td style="text-align:center;padding-top:12px;"><p style="margin:0;font-family:%s;font-size:12px;color:%s;opacity:0.6;">%s</p></td></tr>',
			esc_attr( $font ), $text_c, $sub
		) : '';

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;background-color:%s;">
				<tr>
					<td style="padding:%dpx 40px %dpx;text-align:center;">
						<table width="100%%" cellpadding="0" cellspacing="0" border="0">
							%s
							%s
							<tr><td style="text-align:center;padding-bottom:12px;">
								<div style="display:inline-block;border:2px dashed %s;border-radius:6px;padding:10px 28px;">
									<span style="font-family:%s;font-size:24px;font-weight:900;letter-spacing:4px;color:%s;">%s</span>
								</div>
							</td></tr>
							%s
							%s
						</table>
					</td>
				</tr>
			</table>',
			$mw, $mw, $mw, $bg,
			$pt, $pb,
			$head_html,
			$coupon_text_html,
			$accent,
			esc_attr( $font ), $accent, $code,
			$sub_html,
			$expiry_html
		);
	}

	/**
	 * Render coupon ribbon section — dark background with ribbon badge style.
	 *
	 * @since  1.6.0
	 * @param  array  $s     Settings.
	 * @param  int    $mw    Max width.
	 * @param  string $font  Font family.
	 * @return string
	 */
	private static function render_coupon_ribbon( array $s, int $mw, string $font ): string {
		$bg          = esc_attr( $s['bg_color'] ?? '#1a1a2e' );
		$text_c      = esc_attr( $s['text_color'] ?? '#ffffff' );
		$accent      = esc_attr( $s['accent_color'] ?? '#e63529' );
		$ribbon      = esc_attr( $s['ribbon_color'] ?? '#f5c518' );
		$code        = esc_html( $s['coupon_code'] ?? '' );
		$head        = esc_html( $s['headline'] ?? '' );
		$coupon_text = esc_html( $s['coupon_text'] ?? '' );
		$sub         = esc_html( $s['subtext'] ?? '' );
		$pt          = absint( $s['padding_top'] ?? 48 );
		$pb          = absint( $s['padding_bottom'] ?? 48 );

		$expiry_date = $s['expiry_date'] ?? '';
		$expiry_html = '';
		if ( ! empty( $expiry_date ) ) {
			$formatted   = date( 'd M Y', strtotime( $expiry_date ) );
			$expiry_html = sprintf(
				'<tr><td style="text-align:center;padding-top:16px;"><span style="font-family:%s;font-size:12px;color:%s;opacity:0.5;">%s</span></td></tr>',
				esc_attr( $font ),
				$text_c,
				esc_html( $formatted )
			);
		}

		$head_html = $head ? sprintf(
			'<tr><td style="text-align:center;padding-bottom:8px;"><h2 style="margin:0 0 8px;font-family:%s;font-size:28px;font-weight:900;color:%s;">%s</h2></td></tr>',
			esc_attr( $font ), $text_c, $head
		) : '';

		$coupon_text_html = $coupon_text ? sprintf(
			'<tr><td style="text-align:center;padding-bottom:16px;"><p style="margin:0 0 16px;font-family:%s;font-size:16px;color:%s;opacity:0.85;">%s</p></td></tr>',
			esc_attr( $font ), $text_c, $coupon_text
		) : '';

		$sub_html = $sub ? sprintf(
			'<tr><td style="text-align:center;padding-top:16px;"><p style="margin:0;font-family:%s;font-size:13px;color:%s;opacity:0.7;">%s</p></td></tr>',
			esc_attr( $font ), $text_c, $sub
		) : '';

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;background-color:%s;">
				<tr>
					<td style="padding:%dpx 40px %dpx;text-align:center;">
						<table width="100%%" cellpadding="0" cellspacing="0" border="0">
							<tr><td style="text-align:center;padding-bottom:16px;">
								<div style="display:inline-block;background:%s;color:#000;font-family:%s;font-size:11px;font-weight:800;letter-spacing:2px;padding:4px 20px;text-transform:uppercase;">EXCLUSIVE OFFER</div>
							</td></tr>
							%s
							%s
							<tr><td style="text-align:center;padding-bottom:16px;">
								<div style="display:inline-block;background:%s;border-radius:4px;padding:12px 32px;">
									<span style="font-family:%s;font-size:28px;font-weight:900;letter-spacing:5px;color:#fff;">%s</span>
								</div>
							</td></tr>
							%s
							%s
						</table>
					</td>
				</tr>
			</table>',
			$mw, $mw, $mw, $bg,
			$pt, $pb,
			$ribbon, esc_attr( $font ),
			$head_html,
			$coupon_text_html,
			$accent,
			esc_attr( $font ), $code,
			$sub_html,
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
		$color      = esc_attr( $s['color'] );
		$thick      = (int) $s['thickness'];
		$mt         = (int) $s['margin_top'];
		$mb         = (int) $s['margin_bottom'];
		$line_style = in_array( $s['line_style'] ?? 'solid', array( 'solid', 'dashed', 'dotted', 'double' ), true )
			? ( $s['line_style'] ?? 'solid' )
			: 'solid';

		if ( 'solid' === $line_style ) {
			// Original solid background approach (most compatible).
			$line_html = sprintf(
				'<td style="height:%dpx;background-color:%s;font-size:0;line-height:0;">&nbsp;</td>',
				$thick, $color
			);
		} else {
			// Dashed / dotted / double — use border-top CSS (works in all major email clients except Outlook).
			// MSO fallback: solid line via VML-like background.
			$border_style = sprintf( '%dpx %s %s', $thick, $line_style, $color );
			$line_html = sprintf(
				'<!--[if !mso]><!----><td style="height:0;border-top:%s;font-size:0;line-height:0;">&nbsp;</td><!--<![endif]-->' .
				'<!--[if mso]><td style="height:%dpx;background-color:%s;font-size:0;line-height:0;">&nbsp;</td><![endif]-->',
				$border_style,
				max( 1, $thick ),
				$color
			);
		}

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;">
				<tr>
					<td style="padding:%dpx 0 %dpx;">
						<table width="100%%" cellpadding="0" cellspacing="0" border="0">
							<tr>%s</tr>
						</table>
					</td>
				</tr>
			</table>',
			$mw, $mw, $mw,
			$mt, $mb,
			$line_html
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
	 * Get SVG path markup for a social media platform.
	 *
	 * Returns the inner SVG content (path/polygon elements) for embedding
	 * in an inline SVG element. Falls back to empty string for unknown platforms.
	 *
	 * @since  1.5.5
	 * @param  string $platform  Lowercase platform name (e.g. 'facebook', 'instagram').
	 * @param  string $color     Fill colour (hex or named).
	 * @return string            Inner SVG path markup, or empty string.
	 */
	private static function get_social_svg( string $platform, string $color ): string {
		$c = esc_attr( $color );
		$paths = array(
			'facebook'  => '<path fill="' . $c . '" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>',
			'instagram' => '<path fill="' . $c . '" d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>',
			'twitter'   => '<path fill="' . $c . '" d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>',
			'x'         => '<path fill="' . $c . '" d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>',
			'linkedin'  => '<path fill="' . $c . '" d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>',
			'youtube'   => '<path fill="' . $c . '" d="M23.495 6.205a3.007 3.007 0 0 0-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 0 0 .527 6.205a31.247 31.247 0 0 0-.522 5.805 31.247 31.247 0 0 0 .522 5.783 3.007 3.007 0 0 0 2.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 0 0 2.088-2.088 31.247 31.247 0 0 0 .5-5.783 31.247 31.247 0 0 0-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"/>',
			'tiktok'    => '<path fill="' . $c . '" d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>',
			'pinterest' => '<path fill="' . $c . '" d="M12 0C5.373 0 0 5.373 0 12c0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.937 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738.098.119.112.224.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.632-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/>',
			'snapchat'  => '<path fill="' . $c . '" d="M12 .5C5.65.5.5 5.65.5 12S5.65 23.5 12 23.5 23.5 18.35 23.5 12 18.35.5 12 .5zm5.28 15.72c-.26.06-.52.06-.78.06-1.08 0-2.16-.28-3.08-.83-.24.83-.92 1.47-1.78 1.67v.83c0 .5-.42.92-.92.92s-.92-.42-.92-.92v-.83c-.86-.2-1.54-.84-1.78-1.67-.92.55-2 .83-3.08.83-.26 0-.52 0-.78-.06-.34-.07-.56-.41-.49-.75.07-.34.41-.56.75-.49.18.04.36.05.52.05.75 0 1.5-.2 2.13-.58.1-.06.22-.09.34-.08.34.03.63.28.68.63.11.79.77 1.39 1.56 1.39h2.1c.79 0 1.45-.6 1.56-1.39.05-.35.34-.6.68-.63.12-.01.24.02.34.08.63.38 1.38.58 2.13.58.16 0 .34-.01.52-.05.34-.07.68.15.75.49.07.34-.15.68-.49.75zm-5.28-9.22c-2.1 0-3.82 1.72-3.82 3.82s1.72 3.82 3.82 3.82 3.82-1.72 3.82-3.82S14.1 7 12 7z"/>',
			'whatsapp'  => '<path fill="' . $c . '" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/>',
			'threads'   => '<path fill="' . $c . '" d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.5 12.068V12c0-3.463.856-6.306 2.554-8.436C5.868 1.212 8.647.022 12.261.022c3.489 0 6.199 1.176 8.051 3.498C21.988 5.493 22.74 8.28 22.74 12v.113c0 3.466-.797 6.271-2.37 8.34-1.648 2.17-4.175 3.44-7.522 3.54l-.662.007zm-.234-4.007c1.56-.094 2.823-.661 3.853-1.733 1.07-1.113 1.587-2.526 1.536-4.199-.046-1.517-.746-2.74-2.023-3.537-.747-.47-1.603-.714-2.524-.714-.158 0-.317.007-.476.022-.953.092-1.748.497-2.363 1.205-.703.812-.987 1.86-.843 3.115.226 1.949 1.586 3.052 3.84 3.052zm.39-4.961c.582 0 1.086.16 1.498.476.437.336.683.813.717 1.38.049.822-.286 1.484-.944 1.862-.383.221-.818.333-1.27.333-1.232 0-1.962-.671-2.032-1.839-.046-.763.249-1.38.832-1.733.325-.198.712-.479 1.199-.479z"/>',
		);
		return $paths[ $platform ] ?? '';
	}

	/**
	 * Render a single social icon as a table cell with inline SVG.
	 *
	 * Uses <!--[if !mso]> conditional to hide SVG from Outlook and fall back
	 * to a coloured circle with text initial.
	 *
	 * @since  1.5.5
	 * @param  string $href      Link URL.
	 * @param  string $platform  Platform key (lowercase).
	 * @param  string $icon_bg   Icon background colour.
	 * @param  string $icon_tc   Icon foreground / SVG fill colour.
	 * @param  int    $size      Icon circle diameter in px.
	 * @param  string $abbr      Text fallback (1-2 chars).
	 * @return string
	 */
	private static function render_social_icon_html( string $href, string $platform, string $icon_bg, string $icon_tc, int $size, string $abbr ): string {
		$svg_inner = self::get_social_svg( $platform, $icon_tc );
		$padding   = max( 4, (int) round( $size * 0.2 ) );
		$inner_size = $size - ( $padding * 2 );
		if ( $inner_size < 1 ) { $inner_size = $size; $padding = 0; }

		if ( $svg_inner ) {
			// Clients that support inline SVG (Gmail post-2024, Apple Mail, etc.)
			$icon_content = '<!--[if !mso]><!-->' .
				'<svg xmlns="http://www.w3.org/2000/svg" width="' . $inner_size . '" height="' . $inner_size . '" viewBox="0 0 24 24" role="img" aria-label="' . esc_attr( ucfirst( $platform ) ) . '" style="display:block;width:' . $inner_size . 'px;height:' . $inner_size . 'px;">' .
				$svg_inner .
				'</svg>' .
				'<!--<![endif]-->' .
				// MSO fallback: plain text abbr
				'<!--[if mso]><span style="font-family:Arial,sans-serif;font-size:' . (int) round( $inner_size * 0.5 ) . 'px;font-weight:700;color:' . esc_attr( $icon_tc ) . ';text-transform:uppercase;">' . esc_html( $abbr ) . '</span><![endif]-->';
		} else {
			$icon_content = '<span style="font-family:Arial,sans-serif;font-size:' . (int) round( $inner_size * 0.5 ) . 'px;font-weight:700;color:' . esc_attr( $icon_tc ) . ';text-transform:uppercase;">' . esc_html( $abbr ) . '</span>';
		}

		return sprintf(
			'<a href="%s" target="_blank" style="display:inline-table;width:%dpx;height:%dpx;border-radius:50%%;background-color:%s;text-decoration:none;margin:0 5px;vertical-align:middle;" title="%s">' .
			'<span style="display:table-cell;width:%dpx;height:%dpx;text-align:center;vertical-align:middle;padding:%dpx;">%s</span>' .
			'</a>',
			esc_url( $href ),
			$size, $size,
			esc_attr( $icon_bg ),
			esc_attr( ucfirst( $platform ) ),
			$size, $size,
			$padding,
			$icon_content
		);
	}

	/**
	 * Render social media section.
	 *
	 * @since  1.5.5
	 * @param  array  $s     Settings.
	 * @param  int    $mw    Max width.
	 * @param  string $font  Font family.
	 * @return string
	 */
	private static function render_social( array $s, int $mw, string $font ): string {
		$bg        = esc_attr( $s['bg_color']    ?? '#ffffff' );
		$tc        = esc_attr( $s['text_color']  ?? '#333333' );
		$icon_bg   = $s['icon_bg']    ?? '#e63529';
		$icon_tc   = $s['icon_color'] ?? '#ffffff';
		$heading   = esc_html( $s['heading']     ?? '' );
		$pt        = (int) ( $s['padding_top']    ?? 24 );
		$pb        = (int) ( $s['padding_bottom'] ?? 24 );
		$font_size = (int) ( $s['font_size']      ?? 13 );
		$icon_size = (int) ( $s['icon_size']      ?? 40 );
		$icon_side = $s['icon_side']  ?? 'centre';
		$logo_side = $s['logo_side']  ?? 'none';
		$logo_url  = esc_url( $s['logo_url']  ?? '' );
		$logo_link = esc_url( $s['logo_link'] ?? '' );

		// Map icon_side to CSS text-align.
		$align_map = array( 'left' => 'left', 'centre' => 'center', 'right' => 'right' );
		$icons_align = $align_map[ $icon_side ] ?? 'center';

		// Build heading row.
		$heading_html = '';
		if ( $heading ) {
			$heading_html = sprintf(
				'<tr><td style="text-align:%s;padding-bottom:12px;"><p style="font-family:%s;font-size:%dpx;font-weight:700;color:%s;margin:0;text-transform:uppercase;letter-spacing:2px;">%s</p></td></tr>',
				$icons_align,
				esc_attr( $font ),
				$font_size,
				$tc,
				$heading
			);
		}

		// Build social icon cells.
		$social_data = is_string( $s['social_links'] ) ? json_decode( $s['social_links'], true ) : ( $s['social_links'] ?? array() );
		$abbrevs = array(
			'facebook'  => 'f',  'fb'        => 'f',
			'instagram' => 'in', 'ig'        => 'in',
			'twitter'   => 'x',  'x'         => 'x',
			'tiktok'    => 'tt', 'youtube'   => 'yt',
			'pinterest' => 'p',  'linkedin'  => 'li',
			'snapchat'  => 'sc', 'threads'   => 'th',
			'whatsapp'  => 'wa',
		);
		$icons_html_parts = array();
		if ( is_array( $social_data ) ) {
			foreach ( $social_data as $item ) {
				if ( empty( $item['label'] ) ) { continue; }
				$key   = strtolower( trim( $item['label'] ) );
				$abbr  = $abbrevs[ $key ] ?? strtoupper( substr( $key, 0, 2 ) );
				$href  = ! empty( $item['url'] ) ? $item['url'] : '#';
				$icons_html_parts[] = self::render_social_icon_html( $href, $key, $icon_bg, $icon_tc, $icon_size, $abbr );
			}
		}
		$icons_html = '';
		if ( $icons_html_parts ) {
			$icons_html = '<tr><td style="text-align:' . $icons_align . ';padding:4px 0;">' . implode( '', $icons_html_parts ) . '</td></tr>';
		}

		// Build logo HTML (if logo_side is not 'none' and we have a URL).
		$logo_html = '';
		if ( 'none' !== $logo_side && $logo_url ) {
			$img_tag = sprintf(
				'<img src="%s" alt="Logo" style="display:block;max-height:50px;width:auto;border:0;" />',
				$logo_url
			);
			$logo_inner = $logo_link
				? '<a href="' . $logo_link . '" style="display:block;text-decoration:none;">' . $img_tag . '</a>'
				: $img_tag;

			// Logo left: logo in left column, icons in right column.
			// Logo right: icons in left column, logo in right column.
			// Each in a side-by-side table row.
			if ( in_array( $logo_side, array( 'left', 'right' ), true ) ) {
				$logo_td   = '<td style="vertical-align:middle;width:auto;padding-right:16px;">' . $logo_inner . '</td>';
				$icons_td  = '<td style="vertical-align:middle;text-align:' . $icons_align . ';">' . implode( '', $icons_html_parts ) . '</td>';
				$row_cells = ( 'left' === $logo_side ) ? $logo_td . $icons_td : $icons_td . $logo_td;
				$logo_html = '<tr>' . $row_cells . '</tr>';
				// Override single-column icons_html since we used side-by-side.
				$icons_html = '';
			}
		}

		$inner = $heading_html . $logo_html . $icons_html;

		return sprintf(
			'<table width="%d" cellpadding="0" cellspacing="0" border="0" style="width:%dpx;max-width:%dpx;background-color:%s;">
				<tr>
					<td style="padding:%dpx 30px %dpx;">
						<table width="100%%" cellpadding="0" cellspacing="0" border="0">
							%s
						</table>
					</td>
				</tr>
			</table>',
			$mw, $mw, $mw, $bg,
			$pt, $pb,
			$inner
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

		$social_html = '';
		if ( ! empty( $s['show_social'] ) ) {
			$social_data    = is_string( $s['social_links'] ) ? json_decode( $s['social_links'], true ) : $s['social_links'];
			$social_abbrevs = array(
				'facebook'  => 'f',  'fb'        => 'f',
				'instagram' => 'in', 'ig'        => 'in',
				'twitter'   => 'x',  'x'         => 'x',
				'tiktok'    => 'tt', 'youtube'   => 'yt',
				'pinterest' => 'p',  'linkedin'  => 'li',
				'snapchat'  => 'sc', 'threads'   => 'th',
				'whatsapp'  => 'wa',
			);
			$icon_bg            = $s['icon_bg']    ?? '#444444';
			$icon_tc            = $s['icon_color'] ?? '#ffffff';
			$social_icon_parts  = array();
			if ( is_array( $social_data ) ) {
				foreach ( $social_data as $item ) {
					if ( empty( $item['label'] ) ) { continue; }
					$skey  = strtolower( trim( $item['label'] ) );
					$sabb  = $social_abbrevs[ $skey ] ?? strtoupper( substr( $skey, 0, 2 ) );
					$surl  = ! empty( $item['url'] ) ? $item['url'] : '#';
					$social_icon_parts[] = self::render_social_icon_html( $surl, $skey, $icon_bg, $icon_tc, 36, $sabb );
				}
			}
			$social_html = $social_icon_parts
				? '<tr><td style="text-align:center;padding-top:16px;">' . implode( '', $social_icon_parts ) . '</td></tr>'
				: '';
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
							%s
						</table>
					</td>
				</tr>
			</table>',
			$mw, $mw, $mw, $bg,
			esc_attr( $font ), $tc, $text,
			$links_html,
			$social_html
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
		$pt      = (int) ( $s['padding_top']    ?? $s['padding'] ?? 30 );
		$pb      = (int) ( $s['padding_bottom'] ?? $s['padding'] ?? 30 );
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
					<td style="padding:%dpx 30px %dpx;">
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
			$pt, $pb,
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
		$pt      = (int) ( $s['padding_top']    ?? $s['padding'] ?? 30 );
		$pb      = (int) ( $s['padding_bottom'] ?? $s['padding'] ?? 30 );
		$text_align = in_array( $s['text_align'] ?? 'left', array( 'left', 'center', 'right' ), true ) ? ( $s['text_align'] ?? 'left' ) : 'left';
		$style   = $s['list_style'] ?? 'bullets';
		$item_gap = (int) ( $s['item_gap'] ?? 8 );

		// Parse items: one plain-text line per item (new format).
		// Also handles legacy JSON array format for backwards compatibility.
		$raw = $s['items'] ?? '';
		if ( is_array( $raw ) ) {
			// Already an array (legacy or pre-parsed).
			$items_data = $raw;
		} elseif ( is_string( $raw ) && str_starts_with( ltrim( $raw ), '[' ) ) {
			// Legacy JSON format: [{"text":"..."}] or ["..."].
			$decoded = json_decode( $raw, true );
			$items_data = is_array( $decoded ) ? $decoded : array_filter( array_map( 'trim', explode( "\n", $raw ) ) );
		} else {
			// New format: plain newline-separated text.
			$items_data = array_values( array_filter( array_map( 'trim', explode( "\n", (string) $raw ) ) ) );
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
			} elseif ( 'arrows' === $style ) {
				$marker = '<span style="color:' . $accent . ';font-size:' . $fsize . 'px;line-height:1;">&#8594;</span>';
			} elseif ( 'stars' === $style ) {
				$marker = '<span style="color:' . $accent . ';font-size:' . $fsize . 'px;line-height:1;">&#9733;</span>';
			} elseif ( 'dashes' === $style ) {
				$marker = '<span style="color:' . $accent . ';font-size:' . $fsize . 'px;line-height:1;">&#8211;</span>';
			} elseif ( 'heart' === $style ) {
				$marker = '<span style="color:' . $accent . ';font-size:' . $fsize . 'px;line-height:1;">&#9829;</span>';
			} elseif ( 'diamond' === $style ) {
				$marker = '<span style="color:' . $accent . ';font-size:' . $fsize . 'px;line-height:1;">&#9670;</span>';
			} else {
				$marker = '';
			}

			$rows .= sprintf(
				'<tr>
					<td style="padding:%dpx 0 0;vertical-align:top;">
						<table width="100%%" cellpadding="0" cellspacing="0" border="0">
							<tr>
								%s
								<td style="padding-left:%s;font-family:%s;font-size:%dpx;color:%s;line-height:1.6;">%s</td>
							</tr>
						</table>
					</td>
				</tr>',
				$i > 0 ? $item_gap : 0,
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
					<td style="padding:%dpx 30px %dpx;">
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
			$pt, $pb,
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
<style type="text/css">
body { margin: 0; padding: 0; }
img  { border: 0; height: auto; line-height: 100%%; outline: none; text-decoration: none; }
table td { border-collapse: collapse; }
@media only screen and (max-width: 620px) {
  /* Section-level tables go full width */
  table.bcg-s { width: 100%% !important; max-width: 100%% !important; }
  /* Content wrapper goes full width */
  table.bcg-w { width: 100%% !important; }
  /* Product cells stack vertically */
  td.bcg-p { display: block !important; width: 100%% !important; box-sizing: border-box !important; }
  /* Hide header nav on mobile */
  td.bcg-nav { display: none !important; }
  /* All images in sections become fluid */
  table.bcg-s img { max-width: 100%% !important; height: auto !important; }
}
</style>
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
<table class="bcg-w" width="%d" cellpadding="0" cellspacing="0" border="0" style="width:100%%;max-width:%dpx;">
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
			$max_width, $max_width,
			$sections_html
		);
	}
}
