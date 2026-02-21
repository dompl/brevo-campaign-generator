<?php
/**
 * Section Presets Library.
 *
 * Defines all pre-built, ready-to-use section variations for the Section
 * Builder palette. Each variant ships with curated default settings that
 * produce a visually complete, styled email block immediately — no blank
 * fields. Users can then tweak any setting via the right-panel editor.
 *
 * Organised into named categories that match the palette accordion groups:
 *  Header | Hero | Heading | Text | Banner | Products | List | CTA |
 *  Coupon | Image | Divider | Spacer | Footer
 *
 * @package Brevo_Campaign_Generator
 * @since   1.5.1
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Section_Presets
 *
 * Pre-built section variant library for the Section Builder palette.
 *
 * @since 1.5.1
 */
class BCG_Section_Presets {

	/**
	 * Cached preset data.
	 *
	 * @var array|null
	 */
	private static ?array $presets = null;

	/**
	 * Return all preset categories, each containing their variants.
	 *
	 * @since  1.5.1
	 * @return array[]
	 */
	public static function get_all(): array {
		if ( null === self::$presets ) {
			self::$presets = self::build();
		}
		return self::$presets;
	}

	/**
	 * Find a single variant by its ID across all categories.
	 *
	 * @since  1.5.1
	 * @param  string $id Variant ID (e.g. "hero-bold").
	 * @return array|null
	 */
	public static function get_variant( string $id ): ?array {
		foreach ( self::get_all() as $category ) {
			foreach ( $category['variants'] as $variant ) {
				if ( $variant['id'] === $id ) {
					return $variant;
				}
			}
		}
		return null;
	}

	/**
	 * Return presets serialised for JavaScript wp_localize_script.
	 *
	 * @since  1.5.1
	 * @return array[]
	 */
	public static function get_all_for_js(): array {
		return self::get_all();
	}

	// ── Builder ──────────────────────────────────────────────────────

	/**
	 * Build all preset categories and variants.
	 *
	 * @since  1.5.1
	 * @return array[]
	 */
	private static function build(): array {
		return array(

			// ── HEADER ────────────────────────────────────────────────────
			array(
				'category' => 'header',
				'label'    => __( 'Header', 'brevo-campaign-generator' ),
				'icon'     => 'web_asset',
				'variants' => array(

					array(
						'id'              => 'header-light',
						'label'           => __( 'Light + Nav', 'brevo-campaign-generator' ),
						'description'     => __( 'White background, logo left, navigation links right', 'brevo-campaign-generator' ),
						'type'            => 'header',
						'indicator_color' => '#ffffff',
						'settings'        => array(
							'bg_color'   => '#ffffff',
							'logo_url'   => '',
							'logo_width' => 180,
							'show_nav'   => true,
							'nav_links'  => '[{"label":"Shop","url":""},{"label":"Sale","url":""},{"label":"About","url":""}]',
						),
					),

					array(
						'id'              => 'header-dark',
						'label'           => __( 'Dark Minimal', 'brevo-campaign-generator' ),
						'description'     => __( 'Dark background, centered logo, clean', 'brevo-campaign-generator' ),
						'type'            => 'header',
						'indicator_color' => '#111526',
						'settings'        => array(
							'bg_color'   => '#111526',
							'logo_url'   => '',
							'logo_width' => 160,
							'show_nav'   => false,
							'nav_links'  => '[]',
						),
					),

					array(
						'id'              => 'header-accent',
						'label'           => __( 'Accent Brand', 'brevo-campaign-generator' ),
						'description'     => __( 'Brand accent background, logo only', 'brevo-campaign-generator' ),
						'type'            => 'header',
						'indicator_color' => '#e63529',
						'settings'        => array(
							'bg_color'   => '#e63529',
							'logo_url'   => '',
							'logo_width' => 160,
							'show_nav'   => false,
							'nav_links'  => '[]',
						),
					),
					array(
						'id'              => 'header-split',
						'label'           => __( 'Split Navigation', 'brevo-campaign-generator' ),
						'description'     => __( 'White background with logo left and navigation links', 'brevo-campaign-generator' ),
						'type'            => 'header',
						'indicator_color' => '#f8f9ff',
						'settings'        => array(
							'bg_color'   => '#f8f9ff',
							'logo_url'   => '',
							'logo_width' => 160,
							'show_nav'   => true,
							'nav_links'  => '[{"label":"Shop","url":""},{"label":"About","url":""},{"label":"Contact","url":""}]',
						),
					),

					array(
						'id'              => 'header-minimal-white',
						'label'           => __( 'Minimal White', 'brevo-campaign-generator' ),
						'description'     => __( 'Clean minimal white header, logo centred', 'brevo-campaign-generator' ),
						'type'            => 'header',
						'indicator_color' => '#ffffff',
						'settings'        => array(
							'bg_color'   => '#ffffff',
							'logo_url'   => '',
							'logo_width' => 180,
							'show_nav'   => false,
							'nav_links'  => '[]',
						),
					),

					array(
						'id'              => 'header-forest',
						'label'           => __( 'Forest Green', 'brevo-campaign-generator' ),
						'description'     => __( 'Deep green background for natural/eco brands', 'brevo-campaign-generator' ),
						'type'            => 'header',
						'indicator_color' => '#1a3d2b',
						'settings'        => array(
							'bg_color'   => '#1a3d2b',
							'logo_url'   => '',
							'logo_width' => 160,
							'show_nav'   => false,
							'nav_links'  => '[]',
						),
					),

					array(
						'id'              => 'header-luxury-gold',
						'label'           => __( 'Luxury Gold', 'brevo-campaign-generator' ),
						'description'     => __( 'Black background with gold logo for premium brands', 'brevo-campaign-generator' ),
						'type'            => 'header',
						'indicator_color' => '#c9a84c',
						'settings'        => array(
							'bg_color'   => '#0d0d0d',
							'logo_url'   => '',
							'logo_width' => 160,
							'show_nav'   => false,
							'nav_links'  => '[]',
						),
					),

					array(
						'id'              => 'header-gradient-blue',
						'label'           => __( 'Ocean Blue', 'brevo-campaign-generator' ),
						'description'     => __( 'Deep ocean blue header', 'brevo-campaign-generator' ),
						'type'            => 'header',
						'indicator_color' => '#1e3a5f',
						'settings'        => array(
							'bg_color'   => '#1e3a5f',
							'logo_url'   => '',
							'logo_width' => 160,
							'show_nav'   => false,
							'nav_links'  => '[]',
						),
					),

				),
			),

			// ── HERO ──────────────────────────────────────────────────────
			array(
				'category' => 'hero',
				'label'    => __( 'Hero / Banner', 'brevo-campaign-generator' ),
				'icon'     => 'panorama',
				'variants' => array(

					array(
						'id'              => 'hero-bold',
						'label'           => __( 'Bold Red', 'brevo-campaign-generator' ),
						'description'     => __( 'High-impact accent background, large headline, white CTA', 'brevo-campaign-generator' ),
						'type'            => 'hero',
						'indicator_color' => '#e63529',
						'settings'        => array(
							'bg_color'       => '#e63529',
							'image_url'      => '',
							'headline'       => 'Big Bold Headline Goes Here',
							'headline_size'  => 42,
							'headline_color' => '#ffffff',
							'subtext'        => 'Add your compelling message here. Keep it short and powerful.',
							'subtext_color'  => '#ffe0de',
							'cta_text'       => 'Shop Now',
							'cta_url'        => '',
							'cta_bg_color'   => '#ffffff',
							'cta_text_color' => '#e63529',
							'padding_top'    => 60,
							'padding_bottom' => 60,
						),
					),

					array(
						'id'              => 'hero-clean',
						'label'           => __( 'Clean White', 'brevo-campaign-generator' ),
						'description'     => __( 'White background, dark headline, accent button', 'brevo-campaign-generator' ),
						'type'            => 'hero',
						'indicator_color' => '#ffffff',
						'settings'        => array(
							'bg_color'       => '#ffffff',
							'image_url'      => '',
							'headline'       => 'Your Campaign Headline',
							'headline_size'  => 36,
							'headline_color' => '#111111',
							'subtext'        => 'A brief description that encourages your readers to take action.',
							'subtext_color'  => '#666666',
							'cta_text'       => 'Shop the Collection',
							'cta_url'        => '',
							'cta_bg_color'   => '#e63529',
							'cta_text_color' => '#ffffff',
							'padding_top'    => 48,
							'padding_bottom' => 48,
						),
					),

					array(
						'id'              => 'hero-dark',
						'label'           => __( 'Dark Premium', 'brevo-campaign-generator' ),
						'description'     => __( 'Deep dark background, white headline, accent CTA', 'brevo-campaign-generator' ),
						'type'            => 'hero',
						'indicator_color' => '#0c0e1a',
						'settings'        => array(
							'bg_color'       => '#0c0e1a',
							'image_url'      => '',
							'headline'       => 'Exclusive Offer Inside',
							'headline_size'  => 38,
							'headline_color' => '#ffffff',
							'subtext'        => 'Discover our handpicked selection, available for a limited time.',
							'subtext_color'  => '#8b92be',
							'cta_text'       => 'Explore Now',
							'cta_url'        => '',
							'cta_bg_color'   => '#e63529',
							'cta_text_color' => '#ffffff',
							'padding_top'    => 56,
							'padding_bottom' => 56,
						),
					),

					array(
						'id'              => 'hero-minimal',
						'label'           => __( 'Soft Minimal', 'brevo-campaign-generator' ),
						'description'     => __( 'Soft off-white, understated headline, no button', 'brevo-campaign-generator' ),
						'type'            => 'hero',
						'indicator_color' => '#f8f9ff',
						'settings'        => array(
							'bg_color'       => '#f8f9ff',
							'image_url'      => '',
							'headline'       => 'Something Special for You',
							'headline_size'  => 32,
							'headline_color' => '#1a1a2e',
							'subtext'        => 'We\'ve curated something you\'ll love. Take a look inside.',
							'subtext_color'  => '#555577',
							'cta_text'       => '',
							'cta_url'        => '',
							'cta_bg_color'   => '#e63529',
							'cta_text_color' => '#ffffff',
							'padding_top'    => 44,
							'padding_bottom' => 44,
						),
					),
					array(
						'id'              => 'hero-forest',
						'label'           => __( 'Forest Green', 'brevo-campaign-generator' ),
						'description'     => __( 'Deep green hero for eco or natural brands', 'brevo-campaign-generator' ),
						'type'            => 'hero',
						'indicator_color' => '#1a3d2b',
						'settings'        => array(
							'bg_color'       => '#1a3d2b',
							'image_url'      => '',
							'headline'       => 'Naturally Beautiful',
							'headline_size'  => 38,
							'headline_color' => '#ffffff',
							'subtext'        => 'Discover our sustainably sourced collection.',
							'subtext_color'  => '#a8d5b5',
							'cta_text'       => 'Shop Now',
							'cta_url'        => '',
							'cta_bg_color'   => '#ffffff',
							'cta_text_color' => '#1a3d2b',
							'padding_top'    => 56,
							'padding_bottom' => 56,
						),
					),

					array(
						'id'              => 'hero-luxury',
						'label'           => __( 'Gold Luxury', 'brevo-campaign-generator' ),
						'description'     => __( 'Black hero with gold accent button for premium brands', 'brevo-campaign-generator' ),
						'type'            => 'hero',
						'indicator_color' => '#c9a84c',
						'settings'        => array(
							'bg_color'       => '#0d0d0d',
							'image_url'      => '',
							'headline'       => 'Exclusively Yours',
							'headline_size'  => 40,
							'headline_color' => '#f5e6c8',
							'subtext'        => 'Premium quality, handpicked for the discerning buyer.',
							'subtext_color'  => '#9a8a70',
							'cta_text'       => 'View Collection',
							'cta_url'        => '',
							'cta_bg_color'   => '#c9a84c',
							'cta_text_color' => '#0d0d0d',
							'padding_top'    => 64,
							'padding_bottom' => 64,
						),
					),

					array(
						'id'              => 'hero-split-image',
						'label'           => __( 'Image Background', 'brevo-campaign-generator' ),
						'description'     => __( 'Dark overlay on an image background with white text', 'brevo-campaign-generator' ),
						'type'            => 'hero',
						'indicator_color' => '#2c3e50',
						'settings'        => array(
							'bg_color'       => '#2c3e50',
							'image_url'      => '',
							'headline'       => 'Your Story Starts Here',
							'headline_size'  => 36,
							'headline_color' => '#ffffff',
							'subtext'        => 'Add a background image URL above to bring this hero to life.',
							'subtext_color'  => '#b0bec5',
							'cta_text'       => 'Get Started',
							'cta_url'        => '',
							'cta_bg_color'   => '#e63529',
							'cta_text_color' => '#ffffff',
							'padding_top'    => 72,
							'padding_bottom' => 72,
						),
					),

				),
			),

			// ── HEADING ───────────────────────────────────────────────────
			array(
				'category' => 'heading',
				'label'    => __( 'Heading', 'brevo-campaign-generator' ),
				'icon'     => 'title',
				'variants' => array(

					array(
						'id'              => 'heading-centered',
						'label'           => __( 'Centred + Accent', 'brevo-campaign-generator' ),
						'description'     => __( 'Large centred heading with accent underline', 'brevo-campaign-generator' ),
						'type'            => 'heading',
						'indicator_color' => '#ffffff',
						'settings'        => array(
							'text'         => 'Section Heading',
							'subtext'      => 'A brief tagline or description for this section',
							'font_size'    => 28,
							'text_color'   => '#111111',
							'bg_color'     => '#ffffff',
							'alignment'    => 'center',
							'accent_color' => '#e63529',
							'show_accent'  => true,
							'padding'      => 32,
						),
					),

					array(
						'id'              => 'heading-left',
						'label'           => __( 'Left-Aligned', 'brevo-campaign-generator' ),
						'description'     => __( 'Left-aligned heading with accent line, light background', 'brevo-campaign-generator' ),
						'type'            => 'heading',
						'indicator_color' => '#f8f9ff',
						'settings'        => array(
							'text'         => 'Our Featured Products',
							'subtext'      => '',
							'font_size'    => 24,
							'text_color'   => '#111111',
							'bg_color'     => '#f8f9ff',
							'alignment'    => 'left',
							'accent_color' => '#e63529',
							'show_accent'  => true,
							'padding'      => 24,
						),
					),

					array(
						'id'              => 'heading-dark',
						'label'           => __( 'Dark Background', 'brevo-campaign-generator' ),
						'description'     => __( 'White text on dark, centred, with accent line', 'brevo-campaign-generator' ),
						'type'            => 'heading',
						'indicator_color' => '#111526',
						'settings'        => array(
							'text'         => 'Handpicked For You',
							'subtext'      => 'Exclusive deals from our best sellers',
							'font_size'    => 28,
							'text_color'   => '#ffffff',
							'bg_color'     => '#111526',
							'alignment'    => 'center',
							'accent_color' => '#e63529',
							'show_accent'  => true,
							'padding'      => 32,
						),
					),
					array(
						'id'              => 'heading-minimal',
						'label'           => __( 'Minimal', 'brevo-campaign-generator' ),
						'description'     => __( 'Simple left-aligned heading, no accent, white background', 'brevo-campaign-generator' ),
						'type'            => 'heading',
						'indicator_color' => '#ffffff',
						'settings'        => array(
							'text'         => 'Featured Products',
							'subtext'      => '',
							'font_size'    => 22,
							'text_color'   => '#222222',
							'bg_color'     => '#ffffff',
							'alignment'    => 'left',
							'accent_color' => '#e63529',
							'show_accent'  => false,
							'padding'      => 20,
						),
					),

					array(
						'id'              => 'heading-accent-bg',
						'label'           => __( 'Accent Background', 'brevo-campaign-generator' ),
						'description'     => __( 'Red accent background, white heading, centred', 'brevo-campaign-generator' ),
						'type'            => 'heading',
						'indicator_color' => '#e63529',
						'settings'        => array(
							'text'         => "Today\'s Deals",
							'subtext'      => 'Hand-picked offers just for you',
							'font_size'    => 28,
							'text_color'   => '#ffffff',
							'bg_color'     => '#e63529',
							'alignment'    => 'center',
							'accent_color' => '#ffffff',
							'show_accent'  => false,
							'padding'      => 28,
						),
					),

				),
			),

			// ── TEXT BLOCK ────────────────────────────────────────────────
			array(
				'category' => 'text',
				'label'    => __( 'Text Block', 'brevo-campaign-generator' ),
				'icon'     => 'article',
				'variants' => array(

					array(
						'id'              => 'text-simple',
						'label'           => __( 'Simple', 'brevo-campaign-generator' ),
						'description'     => __( 'Heading + body paragraph, white background', 'brevo-campaign-generator' ),
						'type'            => 'text',
						'indicator_color' => '#ffffff',
						'settings'        => array(
							'heading'    => 'Your Heading Here',
							'body'       => 'Add your message here. Keep your copy focused and engaging. Think about what action you want readers to take after reading this section.',
							'text_color' => '#333333',
							'bg_color'   => '#ffffff',
							'font_size'  => 15,
							'padding'    => 30,
							'alignment'  => 'left',
						),
					),

					array(
						'id'              => 'text-centered',
						'label'           => __( 'Centred', 'brevo-campaign-generator' ),
						'description'     => __( 'Centred body text on a soft background', 'brevo-campaign-generator' ),
						'type'            => 'text',
						'indicator_color' => '#f8f9ff',
						'settings'        => array(
							'heading'    => '',
							'body'       => 'Add your message here. Your readers are busy — keep it clear, concise, and compelling. One focused idea per section works best.',
							'text_color' => '#444455',
							'bg_color'   => '#f8f9ff',
							'font_size'  => 15,
							'padding'    => 36,
							'alignment'  => 'center',
						),
					),

					array(
						'id'              => 'text-dark',
						'label'           => __( 'Dark', 'brevo-campaign-generator' ),
						'description'     => __( 'White text on dark background', 'brevo-campaign-generator' ),
						'type'            => 'text',
						'indicator_color' => '#111526',
						'settings'        => array(
							'heading'    => 'A Note From Us',
							'body'       => 'Add your message here. This style works well for personal messages or brand stories that stand out from the rest of the campaign.',
							'text_color' => '#c8ccee',
							'bg_color'   => '#111526',
							'font_size'  => 15,
							'padding'    => 32,
							'alignment'  => 'left',
						),
					),
					array(
						'id'              => 'text-warm',
						'label'           => __( 'Warm Tint', 'brevo-campaign-generator' ),
						'description'     => __( 'Warm off-white background, left-aligned editorial text', 'brevo-campaign-generator' ),
						'type'            => 'text',
						'indicator_color' => '#fdf8f0',
						'settings'        => array(
							'heading'    => 'A Word From Us',
							'body'       => "We wanted to share something special with you today. Your loyalty means the world to us, and we\'ve put together something we think you\'ll love.",
							'text_color' => '#3d3530',
							'bg_color'   => '#fdf8f0',
							'font_size'  => 15,
							'padding'    => 36,
							'alignment'  => 'left',
						),
					),

					array(
						'id'              => 'text-accent-left',
						'label'           => __( 'Accent Border', 'brevo-campaign-generator' ),
						'description'     => __( 'White background, left-aligned body without heading', 'brevo-campaign-generator' ),
						'type'            => 'text',
						'indicator_color' => '#f5f5f5',
						'settings'        => array(
							'heading'    => '',
							'body'       => 'Your campaign message goes here. Write directly to your customer and keep it personal, clear, and actionable.',
							'text_color' => '#333333',
							'bg_color'   => '#f5f5f5',
							'font_size'  => 15,
							'padding'    => 30,
							'alignment'  => 'left',
						),
					),

				),
			),

			// ── BANNER ────────────────────────────────────────────────────
			array(
				'category' => 'banner',
				'label'    => __( 'Banner', 'brevo-campaign-generator' ),
				'icon'     => 'campaign',
				'variants' => array(

					array(
						'id'              => 'banner-accent',
						'label'           => __( 'Accent Alert', 'brevo-campaign-generator' ),
						'description'     => __( 'Red accent background, white text announcement', 'brevo-campaign-generator' ),
						'type'            => 'banner',
						'indicator_color' => '#e63529',
						'settings'        => array(
							'bg_color'   => '#e63529',
							'text_color' => '#ffffff',
							'heading'    => '🔥 Limited Time Offer',
							'subtext'    => 'Don\'t miss out — this deal ends soon. Shop now.',
							'padding'    => 28,
						),
					),

					array(
						'id'              => 'banner-dark',
						'label'           => __( 'Dark', 'brevo-campaign-generator' ),
						'description'     => __( 'Dark background, white announcement text', 'brevo-campaign-generator' ),
						'type'            => 'banner',
						'indicator_color' => '#111526',
						'settings'        => array(
							'bg_color'   => '#111526',
							'text_color' => '#ffffff',
							'heading'    => 'New Arrivals Just Dropped',
							'subtext'    => 'Be the first to shop our latest collection.',
							'padding'    => 32,
						),
					),

					array(
						'id'              => 'banner-light',
						'label'           => __( 'Light Info', 'brevo-campaign-generator' ),
						'description'     => __( 'Warm light background, informational banner', 'brevo-campaign-generator' ),
						'type'            => 'banner',
						'indicator_color' => '#fff8e6',
						'settings'        => array(
							'bg_color'   => '#fff8e6',
							'text_color' => '#333333',
							'heading'    => 'Free Shipping on Orders Over £50',
							'subtext'    => 'Use code FREESHIP at checkout. Offer ends Sunday.',
							'padding'    => 24,
						),
					),

					array(
						'id'              => 'banner-subtle',
						'label'           => __( 'Subtle', 'brevo-campaign-generator' ),
						'description'     => __( 'Light grey, subtle announcement strip', 'brevo-campaign-generator' ),
						'type'            => 'banner',
						'indicator_color' => '#f0f1f5',
						'settings'        => array(
							'bg_color'   => '#f0f1f5',
							'text_color' => '#555555',
							'heading'    => 'Members get 10% off every order',
							'subtext'    => 'Sign in or create an account to save.',
							'padding'    => 20,
						),
					),
				),
			),

			// ── PRODUCTS ──────────────────────────────────────────────────
			array(
				'category' => 'products',
				'label'    => __( 'Products', 'brevo-campaign-generator' ),
				'icon'     => 'shopping_cart',
				'variants' => array(

					array(
						'id'              => 'products-stack',
						'label'           => __( 'Single Column', 'brevo-campaign-generator' ),
						'description'     => __( 'Stacked single-column product list', 'brevo-campaign-generator' ),
						'type'            => 'products',
						'indicator_color' => '#ffffff',
						'settings'        => array(
							'product_ids'  => '',
							'columns'      => '1',
							'show_price'   => true,
							'show_button'  => true,
							'button_text'  => 'Buy Now',
							'button_color' => '#e63529',
							'bg_color'     => '#ffffff',
						),
					),

					array(
						'id'              => 'products-grid',
						'label'           => __( '2-Column Grid', 'brevo-campaign-generator' ),
						'description'     => __( 'Two-column product grid', 'brevo-campaign-generator' ),
						'type'            => 'products',
						'indicator_color' => '#f9f9f9',
						'settings'        => array(
							'product_ids'  => '',
							'columns'      => '2',
							'show_price'   => true,
							'show_button'  => true,
							'button_text'  => 'Shop Now',
							'button_color' => '#e63529',
							'bg_color'     => '#f9f9f9',
						),
					),

					array(
						'id'              => 'products-dark',
						'label'           => __( 'Dark Cards', 'brevo-campaign-generator' ),
						'description'     => __( 'Dark-themed product grid, 2 columns', 'brevo-campaign-generator' ),
						'type'            => 'products',
						'indicator_color' => '#111526',
						'settings'        => array(
							'product_ids'  => '',
							'columns'      => '2',
							'show_price'   => true,
							'show_button'  => true,
							'button_text'  => 'View Product',
							'button_color' => '#e63529',
							'bg_color'     => '#111526',
						),
					),
					array(
						'id'              => 'products-three-col',
						'label'           => __( '3-Column Grid', 'brevo-campaign-generator' ),
						'description'     => __( 'Three-column product grid, white background', 'brevo-campaign-generator' ),
						'type'            => 'products',
						'indicator_color' => '#ffffff',
						'settings'        => array(
							'product_ids'  => '',
							'columns'      => '3',
							'show_price'   => true,
							'show_button'  => true,
							'button_text'  => 'Buy Now',
							'button_color' => '#e63529',
							'bg_color'     => '#ffffff',
						),
					),

					array(
						'id'              => 'products-minimal',
						'label'           => __( 'Minimal Light', 'brevo-campaign-generator' ),
						'description'     => __( 'Clean single-column, no button, prices shown', 'brevo-campaign-generator' ),
						'type'            => 'products',
						'indicator_color' => '#fafafa',
						'settings'        => array(
							'product_ids'  => '',
							'columns'      => '1',
							'show_price'   => true,
							'show_button'  => false,
							'button_text'  => 'View',
							'button_color' => '#333333',
							'bg_color'     => '#fafafa',
						),
					),

					array(
						'id'              => 'products-warm',
						'label'           => __( 'Warm Cream', 'brevo-campaign-generator' ),
						'description'     => __( 'Warm cream background, 2 columns, accent button', 'brevo-campaign-generator' ),
						'type'            => 'products',
						'indicator_color' => '#fdf8f0',
						'settings'        => array(
							'product_ids'  => '',
							'columns'      => '2',
							'show_price'   => true,
							'show_button'  => true,
							'button_text'  => 'Shop Now',
							'button_color' => '#c9a84c',
							'bg_color'     => '#fdf8f0',
						),
					),

					array(
						'id'              => 'products-featured',
						'label'           => __( 'Featured Single', 'brevo-campaign-generator' ),
						'description'     => __( 'Large single featured product, centred layout', 'brevo-campaign-generator' ),
						'type'            => 'products',
						'indicator_color' => '#f0f4ff',
						'settings'        => array(
							'product_ids'  => '',
							'columns'      => '1',
							'show_price'   => true,
							'show_button'  => true,
							'button_text'  => 'Get Yours',
							'button_color' => '#1e3a5f',
							'bg_color'     => '#f0f4ff',
							'text_align'   => 'center',
						),
					),

					array(
						'id'              => 'products-green',
						'label'           => __( 'Forest Grid', 'brevo-campaign-generator' ),
						'description'     => __( 'Dark green background, 2 columns for natural brands', 'brevo-campaign-generator' ),
						'type'            => 'products',
						'indicator_color' => '#1a3d2b',
						'settings'        => array(
							'product_ids'  => '',
							'columns'      => '2',
							'show_price'   => true,
							'show_button'  => true,
							'button_text'  => 'Buy Now',
							'button_color' => '#a8d5b5',
							'bg_color'     => '#1a3d2b',
						),
					),

				),
			),

			// ── LIST ──────────────────────────────────────────────────────
			array(
				'category' => 'list',
				'label'    => __( 'List', 'brevo-campaign-generator' ),
				'icon'     => 'format_list_bulleted',
				'variants' => array(

					array(
						'id'              => 'list-bullets',
						'label'           => __( 'Bullet Points', 'brevo-campaign-generator' ),
						'description'     => __( 'White background, accent bullet points', 'brevo-campaign-generator' ),
						'type'            => 'list',
						'indicator_color' => '#ffffff',
						'settings'        => array(
							'heading'      => 'Why Shop With Us',
							'items'        => '[{"text":"Free delivery on orders over £50"},{"text":"30-day hassle-free returns"},{"text":"Expert customer support 7 days a week"},{"text":"Exclusive member discounts on every order"}]',
							'list_style'   => 'bullets',
							'text_color'   => '#333333',
							'bg_color'     => '#ffffff',
							'accent_color' => '#e63529',
							'font_size'    => 15,
							'padding'      => 30,
						),
					),

					array(
						'id'              => 'list-checks',
						'label'           => __( 'Checkmarks', 'brevo-campaign-generator' ),
						'description'     => __( 'Feature checklist, soft background', 'brevo-campaign-generator' ),
						'type'            => 'list',
						'indicator_color' => '#f8f9ff',
						'settings'        => array(
							'heading'      => 'What\'s Included',
							'items'        => '[{"text":"Premium quality materials"},{"text":"Handcrafted with care"},{"text":"Sustainably sourced"},{"text":"Satisfaction guaranteed"}]',
							'list_style'   => 'checks',
							'text_color'   => '#333333',
							'bg_color'     => '#f8f9ff',
							'accent_color' => '#22c55e',
							'font_size'    => 15,
							'padding'      => 30,
						),
					),

					array(
						'id'              => 'list-numbered',
						'label'           => __( 'Numbered', 'brevo-campaign-generator' ),
						'description'     => __( 'Numbered steps list, white background', 'brevo-campaign-generator' ),
						'type'            => 'list',
						'indicator_color' => '#ffffff',
						'settings'        => array(
							'heading'      => 'How It Works',
							'items'        => '[{"text":"Choose your products from our store"},{"text":"Add them to your basket and checkout"},{"text":"We pick, pack and dispatch same day"},{"text":"Enjoy fast, free delivery to your door"}]',
							'list_style'   => 'numbers',
							'text_color'   => '#333333',
							'bg_color'     => '#ffffff',
							'accent_color' => '#e63529',
							'font_size'    => 15,
							'padding'      => 30,
						),
					),
				),
			),

			// ── CALL TO ACTION ─────────────────────────────────────────────
			array(
				'category' => 'cta',
				'label'    => __( 'Call to Action', 'brevo-campaign-generator' ),
				'icon'     => 'ads_click',
				'variants' => array(

					array(
						'id'              => 'cta-centered',
						'label'           => __( 'Centred Light', 'brevo-campaign-generator' ),
						'description'     => __( 'Light background, heading + red button', 'brevo-campaign-generator' ),
						'type'            => 'cta',
						'indicator_color' => '#f5f5f5',
						'settings'        => array(
							'heading'          => 'Ready to explore our collection?',
							'subtext'          => 'Shop now and discover the perfect product for you.',
							'button_text'      => 'Shop Now',
							'button_url'       => '',
							'button_bg'        => '#e63529',
							'button_text_color'=> '#ffffff',
							'bg_color'         => '#f5f5f5',
							'text_color'       => '#333333',
							'padding'          => 48,
						),
					),

					array(
						'id'              => 'cta-dark',
						'label'           => __( 'Dark', 'brevo-campaign-generator' ),
						'description'     => __( 'Dark background, white text, red button', 'brevo-campaign-generator' ),
						'type'            => 'cta',
						'indicator_color' => '#111526',
						'settings'        => array(
							'heading'          => 'Don\'t Miss Out',
							'subtext'          => 'This exclusive offer is available for a limited time only.',
							'button_text'      => 'Claim Your Offer',
							'button_url'       => '',
							'button_bg'        => '#e63529',
							'button_text_color'=> '#ffffff',
							'bg_color'         => '#111526',
							'text_color'       => '#ffffff',
							'padding'          => 48,
						),
					),

					array(
						'id'              => 'cta-accent',
						'label'           => __( 'Full Accent', 'brevo-campaign-generator' ),
						'description'     => __( 'Red background, white text + white button', 'brevo-campaign-generator' ),
						'type'            => 'cta',
						'indicator_color' => '#e63529',
						'settings'        => array(
							'heading'          => 'Limited Time — Act Now',
							'subtext'          => 'Grab this deal before it disappears.',
							'button_text'      => 'Get the Deal',
							'button_url'       => '',
							'button_bg'        => '#ffffff',
							'button_text_color'=> '#e63529',
							'bg_color'         => '#e63529',
							'text_color'       => '#ffffff',
							'padding'          => 48,
						),
					),
					array(
						'id'              => 'cta-minimal',
						'label'           => __( 'Minimal White', 'brevo-campaign-generator' ),
						'description'     => __( 'Clean white background, understated heading, bordered button', 'brevo-campaign-generator' ),
						'type'            => 'cta',
						'indicator_color' => '#ffffff',
						'settings'        => array(
							'heading'           => 'Explore the Collection',
							'subtext'           => '',
							'button_text'       => 'Browse All Products',
							'button_url'        => '',
							'button_bg'         => '#222222',
							'button_text_color' => '#ffffff',
							'bg_color'          => '#ffffff',
							'text_color'        => '#222222',
							'padding'           => 40,
						),
					),

					array(
						'id'              => 'cta-warm',
						'label'           => __( 'Warm Gold', 'brevo-campaign-generator' ),
						'description'     => __( 'Warm cream background with a gold button', 'brevo-campaign-generator' ),
						'type'            => 'cta',
						'indicator_color' => '#fdf8f0',
						'settings'        => array(
							'heading'           => 'A Treat Worth Having',
							'subtext'           => "Take a look at what we\'ve put together just for you.",
							'button_text'       => 'See the Range',
							'button_url'        => '',
							'button_bg'         => '#c9a84c',
							'button_text_color' => '#ffffff',
							'bg_color'          => '#fdf8f0',
							'text_color'        => '#3d3530',
							'padding'           => 48,
						),
					),

					array(
						'id'              => 'cta-green',
						'label'           => __( 'Forest Green', 'brevo-campaign-generator' ),
						'description'     => __( 'Dark green background, white text and button for eco brands', 'brevo-campaign-generator' ),
						'type'            => 'cta',
						'indicator_color' => '#1a3d2b',
						'settings'        => array(
							'heading'           => 'Shop Sustainably',
							'subtext'           => 'Every purchase supports our commitment to the planet.',
							'button_text'       => 'Shop Now',
							'button_url'        => '',
							'button_bg'         => '#a8d5b5',
							'button_text_color' => '#1a3d2b',
							'bg_color'          => '#1a3d2b',
							'text_color'        => '#ffffff',
							'padding'           => 48,
						),
					),

				),
			),

			// ── COUPON ────────────────────────────────────────────────────
			array(
				'category' => 'coupon',
				'label'    => __( 'Coupon', 'brevo-campaign-generator' ),
				'icon'     => 'local_offer',
				'variants' => array(

					array(
						'id'              => 'coupon-warm',
						'label'           => __( 'Warm Yellow', 'brevo-campaign-generator' ),
						'description'     => __( 'Classic dashed coupon box, warm background', 'brevo-campaign-generator' ),
						'type'            => 'coupon',
						'indicator_color' => '#fff8e6',
						'settings'        => array(
							'coupon_code'   => 'SAVE10',
							'discount_text' => 'Get 10% off your entire order!',
							'expiry_text'   => 'Offer expires in 7 days',
							'bg_color'      => '#fff8e6',
							'accent_color'  => '#e63529',
						),
					),

					array(
						'id'              => 'coupon-dark',
						'label'           => __( 'Dark', 'brevo-campaign-generator' ),
						'description'     => __( 'Dark background coupon with accent border', 'brevo-campaign-generator' ),
						'type'            => 'coupon',
						'indicator_color' => '#111526',
						'settings'        => array(
							'coupon_code'   => 'VIP20',
							'discount_text' => '20% off — exclusive VIP offer',
							'expiry_text'   => 'Valid until end of this week',
							'bg_color'      => '#111526',
							'accent_color'  => '#e63529',
						),
					),
					array(
						'id'              => 'coupon-minimal-white',
						'label'           => __( 'Clean White', 'brevo-campaign-generator' ),
						'description'     => __( 'Minimal white coupon with grey dashed border', 'brevo-campaign-generator' ),
						'type'            => 'coupon',
						'indicator_color' => '#ffffff',
						'settings'        => array(
							'coupon_code'   => 'WELCOME15',
							'discount_text' => 'Welcome — 15% off your first order!',
							'expiry_text'   => 'Valid for new customers only',
							'bg_color'      => '#ffffff',
							'accent_color'  => '#333333',
						),
					),

					array(
						'id'              => 'coupon-green',
						'label'           => __( 'Green Success', 'brevo-campaign-generator' ),
						'description'     => __( 'Green accent coupon for eco or seasonal promotions', 'brevo-campaign-generator' ),
						'type'            => 'coupon',
						'indicator_color' => '#e8f5e9',
						'settings'        => array(
							'coupon_code'   => 'GREEN25',
							'discount_text' => '25% off — our eco summer special!',
							'expiry_text'   => 'Ends Sunday midnight',
							'bg_color'      => '#e8f5e9',
							'accent_color'  => '#2e7d32',
						),
					),

					array(
						'id'              => 'coupon-premium',
						'label'           => __( 'Premium Black', 'brevo-campaign-generator' ),
						'description'     => __( 'Black background coupon with gold accent for luxury brands', 'brevo-campaign-generator' ),
						'type'            => 'coupon',
						'indicator_color' => '#0d0d0d',
						'settings'        => array(
							'coupon_code'   => 'VIP30',
							'discount_text' => '30% off — exclusive VIP reward',
							'expiry_text'   => 'One use per account. Expires in 48 hours.',
							'bg_color'      => '#0d0d0d',
							'accent_color'  => '#c9a84c',
						),
					),

				),
			),

			// ── IMAGE ─────────────────────────────────────────────────────
			array(
				'category' => 'image',
				'label'    => __( 'Image', 'brevo-campaign-generator' ),
				'icon'     => 'image',
				'variants' => array(

					array(
						'id'              => 'image-full',
						'label'           => __( 'Full Width', 'brevo-campaign-generator' ),
						'description'     => __( 'Edge-to-edge image, no padding', 'brevo-campaign-generator' ),
						'type'            => 'image',
						'indicator_color' => '#e5e5e5',
						'settings'        => array(
							'image_url' => '',
							'alt_text'  => '',
							'link_url'  => '',
							'width'     => 100,
							'alignment' => 'center',
							'caption'   => '',
						),
					),

					array(
						'id'              => 'image-centered',
						'label'           => __( 'Centred 80%', 'brevo-campaign-generator' ),
						'description'     => __( 'Centred image at 80% width with caption area', 'brevo-campaign-generator' ),
						'type'            => 'image',
						'indicator_color' => '#f5f5f5',
						'settings'        => array(
							'image_url' => '',
							'alt_text'  => '',
							'link_url'  => '',
							'width'     => 80,
							'alignment' => 'center',
							'caption'   => 'Add your caption here',
						),
					),
				),
			),

			// ── DIVIDER ───────────────────────────────────────────────────
			array(
				'category' => 'divider',
				'label'    => __( 'Divider / Spacer', 'brevo-campaign-generator' ),
				'icon'     => 'horizontal_rule',
				'variants' => array(

					array(
						'id'              => 'divider-line',
						'label'           => __( 'Thin Line', 'brevo-campaign-generator' ),
						'description'     => __( '1px light grey divider line', 'brevo-campaign-generator' ),
						'type'            => 'divider',
						'indicator_color' => '#e5e5e5',
						'settings'        => array(
							'color'         => '#e5e5e5',
							'thickness'     => 1,
							'margin_top'    => 20,
							'margin_bottom' => 20,
						),
					),

					array(
						'id'              => 'divider-accent',
						'label'           => __( 'Accent Line', 'brevo-campaign-generator' ),
						'description'     => __( '2px accent colour divider', 'brevo-campaign-generator' ),
						'type'            => 'divider',
						'indicator_color' => '#e63529',
						'settings'        => array(
							'color'         => '#e63529',
							'thickness'     => 2,
							'margin_top'    => 16,
							'margin_bottom' => 16,
						),
					),

					array(
						'id'              => 'spacer-sm',
						'label'           => __( 'Small Spacer', 'brevo-campaign-generator' ),
						'description'     => __( '24px of vertical breathing room', 'brevo-campaign-generator' ),
						'type'            => 'spacer',
						'indicator_color' => '#f5f5f5',
						'settings'        => array(
							'height' => 24,
						),
					),

					array(
						'id'              => 'spacer-lg',
						'label'           => __( 'Large Spacer', 'brevo-campaign-generator' ),
						'description'     => __( '56px spacer for section separation', 'brevo-campaign-generator' ),
						'type'            => 'spacer',
						'indicator_color' => '#f5f5f5',
						'settings'        => array(
							'height' => 56,
						),
					),
					array(
						'id'              => 'divider-thick',
						'label'           => __( 'Thick Dark', 'brevo-campaign-generator' ),
						'description'     => __( '4px dark divider for strong section separation', 'brevo-campaign-generator' ),
						'type'            => 'divider',
						'indicator_color' => '#222222',
						'settings'        => array(
							'color'         => '#222222',
							'thickness'     => 4,
							'margin_top'    => 12,
							'margin_bottom' => 12,
						),
					),

					array(
						'id'              => 'divider-dashed',
						'label'           => __( 'Medium Grey', 'brevo-campaign-generator' ),
						'description'     => __( '2px mid-grey divider, balanced margins', 'brevo-campaign-generator' ),
						'type'            => 'divider',
						'indicator_color' => '#aaaaaa',
						'settings'        => array(
							'color'         => '#aaaaaa',
							'thickness'     => 2,
							'margin_top'    => 24,
							'margin_bottom' => 24,
						),
					),

					array(
						'id'              => 'spacer-xs',
						'label'           => __( 'Extra Small Spacer', 'brevo-campaign-generator' ),
						'description'     => __( '8px minimal spacer', 'brevo-campaign-generator' ),
						'type'            => 'spacer',
						'indicator_color' => '#f5f5f5',
						'settings'        => array(
							'height' => 8,
						),
					),

				),
			),

			// ── FOOTER ────────────────────────────────────────────────────
			array(
				'category' => 'footer',
				'label'    => __( 'Footer', 'brevo-campaign-generator' ),
				'icon'     => 'web_asset_off',
				'variants' => array(

					array(
						'id'              => 'footer-minimal',
						'label'           => __( 'Minimal', 'brevo-campaign-generator' ),
						'description'     => __( 'Light background, small text, unsubscribe link', 'brevo-campaign-generator' ),
						'type'            => 'footer',
						'indicator_color' => '#f5f5f5',
						'settings'        => array(
							'footer_text'      => 'You received this email because you subscribed to our newsletter. © ' . gmdate( 'Y' ) . ' Your Store.',
							'footer_links'     => '[{"label":"Unsubscribe","url":"{{unsubscribe_url}}"},{"label":"Privacy Policy","url":""}]',
							'text_color'       => '#999999',
							'bg_color'         => '#f5f5f5',
							'show_unsubscribe' => true,
						),
					),

					array(
						'id'              => 'footer-dark',
						'label'           => __( 'Dark', 'brevo-campaign-generator' ),
						'description'     => __( 'Dark background footer with light text', 'brevo-campaign-generator' ),
						'type'            => 'footer',
						'indicator_color' => '#0c0e1a',
						'settings'        => array(
							'footer_text'      => '© ' . gmdate( 'Y' ) . ' Your Store. All rights reserved.',
							'footer_links'     => '[{"label":"Unsubscribe","url":"{{unsubscribe_url}}"},{"label":"Privacy Policy","url":""}]',
							'text_color'       => '#8b92be',
							'bg_color'         => '#0c0e1a',
							'show_unsubscribe' => true,
						),
					),
				),
			),

		); // end build()
	}
}
