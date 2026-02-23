<?php
/**
 * Section Presets — Structurally-distinct variant library.
 *
 * Variants within the same category differ in LAYOUT STRUCTURE (column count,
 * presence of navigation / button / subtext / accent line, alignment, spacing),
 * not merely colour.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.5.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Section_Presets
 *
 * Provides a registry of pre-built section variants for the Section Builder.
 */
class BCG_Section_Presets {

	/**
	 * Return all preset categories and their variants.
	 *
	 * @return array[]
	 */
	public static function get_all(): array {
		return [

			// ------------------------------------------------------------------ HEADER
			[
				'category' => 'header',
				'label'    => 'Header',
				'icon'     => 'web_asset',
				'variants' => [
					[
						'id'              => 'header-logo-only',
						'label'           => 'Logo Only',
						'description'     => 'Logo on the left — no navigation links.',
						'type'            => 'header',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'logo_width'  => 180,
							'show_nav'    => false,
							'nav_links'   => '[]',
							'text_color'  => '#333333',
						],
					],
					[
						'id'              => 'header-logo-nav',
						'label'           => 'Logo + Navigation',
						'description'     => 'Logo left, three nav links right.',
						'type'            => 'header',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'logo_width'  => 160,
							'show_nav'    => true,
							'nav_links'   => '[{"label":"Shop","url":""},{"label":"Sale","url":""},{"label":"About","url":""}]',
							'text_color'  => '#333333',
						],
					],
					[
						'id'              => 'header-dark',
						'label'           => 'Dark Background',
						'description'     => 'Dark navy header with white logo/text.',
						'type'            => 'header',
						'indicator_color' => '#1a1a2e',
						'settings'        => [
							'bg_color'    => '#1a1a2e',
							'text_color'  => '#ffffff',
							'logo_width'  => 180,
							'show_nav'    => false,
						],
					],
					[
						'id'              => 'header-centered',
						'label'           => 'Logo Centred',
						'description'     => 'Logo centred, no navigation — clean newsletter style.',
						'type'            => 'header',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'bg_color'    => '#ffffff',
							'text_color'  => '#333333',
							'logo_width'  => 160,
							'show_nav'    => false,
							'logo_align'  => 'center',
						],
					],
					[
						'id'              => 'header-minimal-border',
						'label'           => 'Minimal + Border',
						'description'     => 'White header with a bottom border accent line.',
						'type'            => 'header',
						'indicator_color' => '#fafafa',
						'settings'        => [
							'bg_color'    => '#fafafa',
							'text_color'  => '#111111',
							'logo_width'  => 150,
							'show_nav'    => false,
						],
					],
				],
			],

			// ------------------------------------------------------------------ HERO
			[
				'category' => 'hero',
				'label'    => 'Hero Banner',
				'icon'     => 'panorama',
				'variants' => [
					[
						'id'              => 'hero-standard',
						'label'           => 'Standard (Headline + Subtext + Button)',
						'description'     => 'Full hero with CTA button and balanced padding.',
						'type'            => 'hero',
						'indicator_color' => '#1a1a2e',
						'settings'        => [
							'bg_color'        => '#1a1a2e',
							'headline_color'  => '#ffffff',
							'subtext_color'   => '#cccccc',
							'cta_bg_color'    => '#e63529',
							'cta_text_color'  => '#ffffff',
							'cta_text'        => 'Shop Now',
							'padding_top'     => 48,
							'padding_bottom'  => 48,
							'headline_size'   => 36,
						],
					],
					[
						'id'              => 'hero-no-button',
						'label'           => 'No Button',
						'description'     => 'Headline and subtext only — no call-to-action button.',
						'type'            => 'hero',
						'indicator_color' => '#1a1a2e',
						'settings'        => [
							'bg_color'        => '#1a1a2e',
							'headline_color'  => '#ffffff',
							'subtext_color'   => '#cccccc',
							'cta_bg_color'    => '#e63529',
							'cta_text_color'  => '#ffffff',
							'cta_text'        => '',
							'padding_top'     => 48,
							'padding_bottom'  => 48,
							'headline_size'   => 36,
						],
					],
					[
						'id'              => 'hero-compact',
						'label'           => 'Compact',
						'description'     => 'Reduced padding and smaller headline for a tighter layout.',
						'type'            => 'hero',
						'indicator_color' => '#1a1a2e',
						'settings'        => [
							'bg_color'        => '#1a1a2e',
							'headline_color'  => '#ffffff',
							'subtext_color'   => '#cccccc',
							'cta_bg_color'    => '#e63529',
							'cta_text_color'  => '#ffffff',
							'cta_text'        => 'Shop Now',
							'padding_top'     => 24,
							'padding_bottom'  => 24,
							'headline_size'   => 28,
						],
					],
					[
						'id'              => 'hero-spacious',
						'label'           => 'Tall & Spacious',
						'description'     => 'Extra tall with large headline — commands attention.',
						'type'            => 'hero',
						'indicator_color' => '#1a1a2e',
						'settings'        => [
							'bg_color'        => '#1a1a2e',
							'headline_color'  => '#ffffff',
							'subtext_color'   => '#cccccc',
							'cta_bg_color'    => '#e63529',
							'cta_text_color'  => '#ffffff',
							'cta_text'        => 'Explore Now',
							'padding_top'     => 80,
							'padding_bottom'  => 80,
							'headline_size'   => 44,
						],
					],
				],
			],

			// ------------------------------------------------------------------ HERO SPLIT
			[
				'category' => 'hero_split',
				'label'    => 'Hero Split',
				'icon'     => 'view_column',
				'variants' => [
					[
						'id'              => 'hero-split-img-right',
						'label'           => 'Image Right — Dark',
						'description'     => 'Text panel left, image right. Dark background, white text.',
						'type'            => 'hero_split',
						'indicator_color' => '#1a1a2e',
						'settings'        => [
							'image_side'      => 'right',
							'text_bg_color'   => '#1a1a2e',
							'headline_color'  => '#ffffff',
							'subtext_color'   => '#cccccc',
							'cta_bg_color'    => '#e63529',
							'cta_text_color'  => '#ffffff',
							'cta_border_radius' => 4,
							'headline'        => 'Discover Something New',
							'subtext'         => 'Explore our latest arrivals',
							'cta_text'        => 'Shop Now',
							'text_padding'    => 48,
						],
					],
				],
			],

			// ------------------------------------------------------------------ HEADING
			[
				'category' => 'heading',
				'label'    => 'Section Heading',
				'icon'     => 'campaign',
				'variants' => [
					[
						'id'              => 'heading-centered-accent',
						'label'           => 'Centred + Accent Line',
						'description'     => 'Centred text with decorative accent underline and tagline subtext.',
						'type'            => 'heading',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'text_color'     => '#111111',
							'bg_color'       => '#ffffff',
							'accent_color'   => '#e63529',
							'alignment'      => 'center',
							'show_accent'    => true,
							'subtext'        => 'A brief tagline or description',
							'font_size'      => 28,
							'padding_top'    => 28,
							'padding_bottom' => 28,
						],
					],
					[
						'id'              => 'heading-left-accent',
						'label'           => 'Left-Aligned + Accent Line',
						'description'     => 'Left-aligned heading with accent underline, no subtext.',
						'type'            => 'heading',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'text_color'     => '#111111',
							'bg_color'       => '#ffffff',
							'accent_color'   => '#e63529',
							'alignment'      => 'left',
							'show_accent'    => true,
							'subtext'        => '',
							'font_size'      => 24,
							'padding_top'    => 28,
							'padding_bottom' => 28,
						],
					],
					[
						'id'              => 'heading-no-accent',
						'label'           => 'No Accent Line',
						'description'     => 'Plain left-aligned heading with no decorative line.',
						'type'            => 'heading',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'text_color'     => '#111111',
							'bg_color'       => '#ffffff',
							'accent_color'   => '#e63529',
							'alignment'      => 'left',
							'show_accent'    => false,
							'subtext'        => '',
							'font_size'      => 22,
							'padding_top'    => 28,
							'padding_bottom' => 28,
						],
					],
					[
						'id'              => 'heading-with-subtext',
						'label'           => 'Centred, No Accent',
						'description'     => 'Centred heading and subtext, clean — no accent line.',
						'type'            => 'heading',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'text_color'     => '#111111',
							'bg_color'       => '#ffffff',
							'accent_color'   => '#e63529',
							'alignment'      => 'center',
							'show_accent'    => false,
							'subtext'        => 'Curated picks just for you',
							'font_size'      => 26,
							'padding_top'    => 28,
							'padding_bottom' => 28,
						],
					],
				],
			],

			// ------------------------------------------------------------------ TEXT BLOCK
			[
				'category' => 'text',
				'label'    => 'Text Block',
				'icon'     => 'article',
				'variants' => [
					[
						'id'              => 'text-heading-body',
						'label'           => 'With Heading + Body',
						'description'     => 'A bold heading above a paragraph of body text.',
						'type'            => 'text',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'text_color'     => '#333333',
							'bg_color'       => '#ffffff',
							'heading'        => 'Your Heading Here',
							'heading_size'   => 22,
							'body'           => 'Add your main message here. This section is great for introductions, announcements, or any copy that needs a clear heading to guide the reader.',
							'alignment'      => 'left',
							'font_size'      => 15,
							'line_height'    => 170,
							'padding_top'    => 30,
							'padding_bottom' => 30,
						],
					],
					[
						'id'              => 'text-body-left',
						'label'           => 'Body Only (Left-Aligned)',
						'description'     => 'Paragraph text only — no heading, left-aligned.',
						'type'            => 'text',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'text_color'     => '#333333',
							'bg_color'       => '#ffffff',
							'heading'        => '',
							'heading_size'   => 22,
							'body'           => 'Write your message here. Keep it concise and relevant to your readers.',
							'alignment'      => 'left',
							'font_size'      => 15,
							'line_height'    => 170,
							'padding_top'    => 30,
							'padding_bottom' => 30,
						],
					],
					[
						'id'              => 'text-body-centered',
						'label'           => 'Body Only (Centred)',
						'description'     => 'Centred paragraph text only — ideal for short, impactful copy.',
						'type'            => 'text',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'text_color'     => '#333333',
							'bg_color'       => '#ffffff',
							'heading'        => '',
							'heading_size'   => 22,
							'body'           => 'A short, centred message works great for promotional intros or closing remarks.',
							'alignment'      => 'center',
							'font_size'      => 15,
							'line_height'    => 170,
							'padding_top'    => 36,
							'padding_bottom' => 36,
						],
					],
					[
						'id'              => 'text-large-intro',
						'label'           => 'Large Heading Intro',
						'description'     => 'Oversized heading (32px) with body text — use as a bold section opener.',
						'type'            => 'text',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'text_color'     => '#333333',
							'bg_color'       => '#ffffff',
							'heading'        => 'Introducing Something Special',
							'heading_size'   => 32,
							'body'           => 'We\'ve put together something we think you\'ll love. Read on to find out more about what\'s new this season.',
							'alignment'      => 'left',
							'font_size'      => 15,
							'line_height'    => 170,
							'padding_top'    => 36,
							'padding_bottom' => 24,
						],
					],
				],
			],

			// ------------------------------------------------------------------ BANNER
			[
				'category' => 'banner',
				'label'    => 'Promotional Banner',
				'icon'     => 'campaign',
				'variants' => [
					[
						'id'              => 'banner-centered',
						'label'           => 'Centred (Heading + Subtext)',
						'description'     => 'Centred heading and subtext — balanced announcement layout.',
						'type'            => 'banner',
						'indicator_color' => '#e63529',
						'settings'        => [
							'bg_color'           => '#e63529',
							'text_color'         => '#ffffff',
							'text_align'         => 'center',
							'heading'            => 'Limited Time Offer',
							'subtext'            => 'Don\'t miss out — this deal ends soon.',
							'heading_font_size'  => 24,
							'subtext_font_size'  => 15,
							'padding_top'        => 28,
							'padding_bottom'     => 28,
						],
					],
					[
						'id'              => 'banner-left',
						'label'           => 'Left-Aligned',
						'description'     => 'Left-aligned heading and subtext — natural reading flow.',
						'type'            => 'banner',
						'indicator_color' => '#e63529',
						'settings'        => [
							'bg_color'           => '#e63529',
							'text_color'         => '#ffffff',
							'text_align'         => 'left',
							'heading'            => 'Free Shipping on Orders Over £50',
							'subtext'            => 'Use code FREESHIP at checkout.',
							'heading_font_size'  => 22,
							'subtext_font_size'  => 14,
							'padding_top'        => 24,
							'padding_bottom'     => 24,
						],
					],
					[
						'id'              => 'banner-heading-only',
						'label'           => 'Heading Only (No Subtext)',
						'description'     => 'Single bold headline — maximum impact, no supporting text.',
						'type'            => 'banner',
						'indicator_color' => '#e63529',
						'settings'        => [
							'bg_color'           => '#e63529',
							'text_color'         => '#ffffff',
							'text_align'         => 'center',
							'heading'            => '🔥 Today Only — 20% Off Everything',
							'subtext'            => '',
							'heading_font_size'  => 26,
							'subtext_font_size'  => 15,
							'padding_top'        => 24,
							'padding_bottom'     => 24,
						],
					],
				],
			],

			// ------------------------------------------------------------------ PRODUCTS
			[
				'category' => 'products',
				'label'    => 'Products',
				'icon'     => 'shopping_cart',
				'variants' => [
					[
						'id'              => 'products-single',
						'label'           => 'Single Column',
						'description'     => 'One product per row, left-aligned with Buy Now button.',
						'type'            => 'products',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'product_ids'  => '',
							'columns'      => 1,
							'text_align'   => 'left',
							'show_button'  => true,
							'button_text'  => 'Buy Now',
							'show_price'   => true,
							'button_color' => '#e63529',
							'bg_color'     => '#ffffff',
						],
					],
					[
						'id'              => 'products-two-col',
						'label'           => '2-Column Grid',
						'description'     => 'Two products per row, left-aligned grid layout.',
						'type'            => 'products',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'product_ids'  => '',
							'columns'      => 2,
							'text_align'   => 'left',
							'show_button'  => true,
							'button_text'  => 'Shop Now',
							'show_price'   => true,
							'button_color' => '#e63529',
							'bg_color'     => '#ffffff',
						],
					],
					[
						'id'              => 'products-three-col',
						'label'           => '3-Column Grid',
						'description'     => 'Three products per row, centred text, compact display.',
						'type'            => 'products',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'product_ids'  => '',
							'columns'      => 3,
							'text_align'   => 'center',
							'show_button'  => true,
							'button_text'  => 'Buy Now',
							'show_price'   => true,
							'button_color' => '#e63529',
							'bg_color'     => '#ffffff',
						],
					],
					[
						'id'              => 'products-featured',
						'label'           => 'Featured (Centred)',
						'description'     => 'Single centred product — spotlight a hero item.',
						'type'            => 'products',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'product_ids'  => '',
							'columns'      => 1,
							'text_align'   => 'center',
							'show_button'  => true,
							'button_text'  => 'Get Yours',
							'show_price'   => true,
							'button_color' => '#e63529',
							'bg_color'     => '#ffffff',
						],
					],
					[
						'id'              => 'products-no-button',
						'label'           => 'Without Button',
						'description'     => 'Two-column grid showing price but no buy button.',
						'type'            => 'products',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'product_ids'  => '',
							'columns'      => 2,
							'text_align'   => 'left',
							'show_button'  => false,
							'button_text'  => '',
							'show_price'   => true,
							'button_color' => '#e63529',
							'bg_color'     => '#ffffff',
						],
					],
				],
			],

			// ------------------------------------------------------------------ LIST
			[
				'category' => 'list',
				'label'    => 'Feature List',
				'icon'     => 'inventory_2',
				'variants' => [
					[
						'id'              => 'list-bullets',
						'label'           => 'Bullet Points',
						'description'     => 'Classic bullet-point list — great for benefits or features.',
						'type'            => 'list',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'text_color'     => '#333333',
							'bg_color'       => '#ffffff',
							'accent_color'   => '#e63529',
							'list_style'     => 'bullets',
							'heading'        => 'Why Shop With Us',
							'items'          => '[{"text":"Free UK delivery on orders over £40"},{"text":"Hassle-free 30-day returns"},{"text":"Friendly customer support 7 days a week"},{"text":"Exclusive discounts for subscribers"}]',
							'font_size'      => 15,
							'padding_top'    => 30,
							'padding_bottom' => 30,
						],
					],
					[
						'id'              => 'list-checks',
						'label'           => 'Checkmarks',
						'description'     => 'Green checkmark list — ideal for product inclusions or guarantees.',
						'type'            => 'list',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'text_color'     => '#333333',
							'bg_color'       => '#ffffff',
							'accent_color'   => '#22c55e',
							'list_style'     => 'checks',
							'heading'        => 'What\'s Included',
							'items'          => '[{"text":"Premium quality materials"},{"text":"Full 12-month warranty"},{"text":"Easy online returns"},{"text":"Secure checkout & data protection"}]',
							'font_size'      => 15,
							'padding_top'    => 30,
							'padding_bottom' => 30,
						],
					],
					[
						'id'              => 'list-numbered',
						'label'           => 'Numbered Steps',
						'description'     => 'Numbered step list — perfect for how-it-works sections.',
						'type'            => 'list',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'text_color'     => '#333333',
							'bg_color'       => '#ffffff',
							'accent_color'   => '#e63529',
							'list_style'     => 'numbers',
							'heading'        => 'How It Works',
							'items'          => '[{"text":"Browse our curated selection of products"},{"text":"Add your favourites to your basket"},{"text":"Check out securely in seconds"},{"text":"Sit back while we handle the rest"}]',
							'font_size'      => 15,
							'padding_top'    => 30,
							'padding_bottom' => 30,
						],
					],
				],
			],

			// ------------------------------------------------------------------ CTA
			[
				'category' => 'cta',
				'label'    => 'Call to Action',
				'icon'     => 'ads_click',
				'variants' => [
					[
						'id'              => 'cta-full',
						'label'           => 'Heading + Subtext + Button',
						'description'     => 'Full CTA block with heading, supporting text, and button.',
						'type'            => 'cta',
						'indicator_color' => '#f5f5f5',
						'settings'        => [
							'bg_color'          => '#f5f5f5',
							'text_color'        => '#333333',
							'button_bg'         => '#e63529',
							'button_text_color' => '#ffffff',
							'heading'           => 'Ready to explore our collection?',
							'subtext'           => 'Shop now and discover the perfect product for you.',
							'button_text'       => 'Shop Now',
							'padding_top'       => 48,
							'padding_bottom'    => 48,
						],
					],
					[
						'id'              => 'cta-no-subtext',
						'label'           => 'Heading + Button Only',
						'description'     => 'Heading and button only — no supporting subtext.',
						'type'            => 'cta',
						'indicator_color' => '#f5f5f5',
						'settings'        => [
							'bg_color'          => '#f5f5f5',
							'text_color'        => '#333333',
							'button_bg'         => '#e63529',
							'button_text_color' => '#ffffff',
							'heading'           => 'Explore the Collection',
							'subtext'           => '',
							'button_text'       => 'Browse All Products',
							'padding_top'       => 40,
							'padding_bottom'    => 40,
						],
					],
					[
						'id'              => 'cta-compact',
						'label'           => 'Compact',
						'description'     => 'Small-font heading, brief subtext, reduced padding — fits anywhere.',
						'type'            => 'cta',
						'indicator_color' => '#f5f5f5',
						'settings'        => [
							'bg_color'          => '#f5f5f5',
							'text_color'        => '#333333',
							'button_bg'         => '#e63529',
							'button_text_color' => '#ffffff',
							'heading'           => 'Shop Now',
							'subtext'           => 'Fast delivery. Free returns.',
							'button_text'       => 'View Products',
							'heading_font_size' => 20,
							'padding_top'       => 24,
							'padding_bottom'    => 24,
						],
					],
					[
						'id'              => 'cta-spacious',
						'label'           => 'Spacious',
						'description'     => 'Large heading (30px) with generous padding — high-impact close.',
						'type'            => 'cta',
						'indicator_color' => '#f5f5f5',
						'settings'        => [
							'bg_color'          => '#f5f5f5',
							'text_color'        => '#333333',
							'button_bg'         => '#e63529',
							'button_text_color' => '#ffffff',
							'heading'           => 'Something Special Is Waiting',
							'subtext'           => 'Take a look at what we\'ve put together just for you.',
							'button_text'       => 'See the Range',
							'heading_font_size' => 30,
							'padding_top'       => 64,
							'padding_bottom'    => 64,
						],
					],
					[
						'id'              => 'cta-dark',
						'label'           => 'Dark Background',
						'description'     => 'Dark navy background with white text and red button.',
						'type'            => 'cta',
						'indicator_color' => '#1a1a2e',
						'settings'        => [
							'heading'            => 'Ready to Shop?',
							'heading_font_size'  => 26,
							'subtext'            => 'Browse our full collection today.',
							'subtext_font_size'  => 15,
							'button_text'        => 'Shop Now',
							'button_url'         => '',
							'button_bg'          => '#e63529',
							'button_text_color'  => '#ffffff',
							'button_font_size'   => 16,
							'button_padding_h'   => 40,
							'button_padding_v'   => 16,
							'button_border_radius' => 4,
							'bg_color'           => '#1a1a2e',
							'text_color'         => '#ffffff',
							'padding_top'        => 40,
							'padding_bottom'     => 40,
						],
					],
					[
						'id'              => 'cta-pill-button',
						'label'           => 'Pill Button',
						'description'     => 'Light background with a fully rounded pill-style button.',
						'type'            => 'cta',
						'indicator_color' => '#f0f4ff',
						'settings'        => [
							'heading'            => 'Don\'t Miss Out',
							'heading_font_size'  => 24,
							'subtext'            => 'Explore everything we have to offer.',
							'subtext_font_size'  => 15,
							'button_text'        => 'View Collection',
							'button_url'         => '',
							'button_bg'          => '#e63529',
							'button_text_color'  => '#ffffff',
							'button_font_size'   => 15,
							'button_padding_h'   => 44,
							'button_padding_v'   => 16,
							'button_border_radius' => 50,
							'bg_color'           => '#f0f4ff',
							'text_color'         => '#1a1a2e',
							'padding_top'        => 44,
							'padding_bottom'     => 44,
						],
					],
					[
						'id'              => 'cta-minimal-link',
						'label'           => 'Minimal Text Link',
						'description'     => 'No background colour — just centred text and a subtle link button.',
						'type'            => 'cta',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'heading'            => 'Explore the Full Range',
							'heading_font_size'  => 22,
							'subtext'            => '',
							'subtext_font_size'  => 14,
							'button_text'        => 'See All Products →',
							'button_url'         => '',
							'button_bg'          => 'transparent',
							'button_text_color'  => '#e63529',
							'button_font_size'   => 15,
							'button_padding_h'   => 20,
							'button_padding_v'   => 10,
							'button_border_radius' => 0,
							'bg_color'           => '#ffffff',
							'text_color'         => '#333333',
							'padding_top'        => 32,
							'padding_bottom'     => 32,
						],
					],
				],
			],

			// ------------------------------------------------------------------ COUPON
			[
				'category' => 'coupon',
				'label'    => 'Coupon Block',
				'icon'     => 'confirmation_number',
				'variants' => [
					[
						'id'              => 'coupon-full',
						'label'           => 'Classic',
						'description'     => 'Coupon code with headline, offer text and optional expiry.',
						'type'            => 'coupon',
						'indicator_color' => '#fff8e6',
						'settings'        => [
							'headline'     => 'Exclusive Offer Just For You',
							'coupon_text'  => 'Get 10% off your entire order!',
							'subtext'      => 'Use at checkout',
							'coupon_code'  => 'SAVE10',
							'bg_color'     => '#fff8e6',
							'accent_color' => '#e63529',
							'text_color'   => '#333333',
						],
					],
					[
						'id'              => 'coupon-banner',
						'label'           => 'Banner',
						'description'     => 'Bold full-width dark banner — coupon code centred.',
						'type'            => 'coupon_banner',
						'indicator_color' => '#1a1a2e',
						'settings'        => [
							'headline'     => 'Limited Time Offer',
							'coupon_text'  => 'Save 20% Today',
							'subtext'      => 'Apply at checkout',
							'coupon_code'  => 'SAVE20',
							'bg_color'     => '#1a1a2e',
							'accent_color' => '#e63529',
							'text_color'   => '#ffffff',
						],
					],
					[
						'id'              => 'coupon-card',
						'label'           => 'Card',
						'description'     => 'Clean card with inner accent border — premium gifting feel.',
						'type'            => 'coupon_card',
						'indicator_color' => '#f8f9ff',
						'settings'        => [
							'headline'     => 'Your Special Discount',
							'coupon_text'  => '15% OFF',
							'subtext'      => 'Enter code at checkout',
							'coupon_code'  => 'GIFT15',
							'bg_color'     => '#ffffff',
							'card_bg'      => '#f8f9ff',
							'accent_color' => '#e63529',
							'text_color'   => '#222222',
						],
					],
					[
						'id'              => 'coupon-split',
						'label'           => 'Split Panel',
						'description'     => 'Discount amount on the left, copyable code on the right.',
						'type'            => 'coupon_split',
						'indicator_color' => '#e63529',
						'settings'        => [
							'headline'         => 'Exclusive Member Offer',
							'coupon_text'      => 'Use code at checkout',
							'subtext'          => 'One use per customer',
							'coupon_code'      => 'VIP25',
							'discount_text'    => '25%',
							'discount_label'   => 'OFF',
							'left_bg'          => '#e63529',
							'right_bg'         => '#ffffff',
							'left_text_color'  => '#ffffff',
							'right_text_color' => '#222222',
							'accent_color'     => '#e63529',
						],
					],
					[
						'id'              => 'coupon-minimal',
						'label'           => 'Minimal',
						'description'     => 'Clean borderless layout — code highlighted in accent colour.',
						'type'            => 'coupon_minimal',
						'indicator_color' => '#f9f9f9',
						'settings'        => [
							'headline'     => 'Special Offer',
							'coupon_text'  => 'Get 20% off your order',
							'subtext'      => 'Use at checkout. Limited time only.',
							'coupon_code'  => 'SAVE20',
							'bg_color'     => '#f9f9f9',
							'text_color'   => '#222222',
							'accent_color' => '#e63529',
						],
					],
					[
						'id'              => 'coupon-ribbon',
						'label'           => 'Ribbon',
						'description'     => 'Dark background with a bold yellow ribbon accent bar.',
						'type'            => 'coupon_ribbon',
						'indicator_color' => '#1a1a2e',
						'settings'        => [
							'headline'     => 'Exclusive Deal',
							'coupon_text'  => 'Save big on your next purchase',
							'subtext'      => 'Enter code at checkout to redeem.',
							'coupon_code'  => 'EXCLUSIVE',
							'bg_color'     => '#1a1a2e',
							'text_color'   => '#ffffff',
							'accent_color' => '#e63529',
							'ribbon_color' => '#f5c518',
						],
					],
					[
						'id'              => 'coupon-code-only',
						'label'           => 'Code Only',
						'description'     => 'Just the coupon code on a white background — ultra minimal.',
						'type'            => 'coupon',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'headline'     => '',
							'coupon_text'  => '',
							'subtext'      => '',
							'coupon_code'  => 'WELCOME15',
							'bg_color'     => '#ffffff',
							'accent_color' => '#333333',
							'text_color'   => '#333333',
						],
					],
				],
			],

			// ------------------------------------------------------------------ IMAGE
			[
				'category' => 'image',
				'label'    => 'Image',
				'icon'     => 'image',
				'variants' => [
					[
						'id'              => 'image-full',
						'label'           => 'Full Width',
						'description'     => 'Image spans the full content width, centred, no caption.',
						'type'            => 'image',
						'indicator_color' => '#f0f0f0',
						'settings'        => [
							'image_url'  => '',
							'alt_text'   => '',
							'link_url'   => '',
							'width'      => 100,
							'alignment'  => 'center',
							'caption'    => '',
						],
					],
					[
						'id'              => 'image-centered-80',
						'label'           => 'Centred 80%',
						'description'     => 'Image at 80% width, centred — slight side margins.',
						'type'            => 'image',
						'indicator_color' => '#f0f0f0',
						'settings'        => [
							'image_url'  => '',
							'alt_text'   => '',
							'link_url'   => '',
							'width'      => 80,
							'alignment'  => 'center',
							'caption'    => '',
						],
					],
					[
						'id'              => 'image-left-60',
						'label'           => 'Left 60%',
						'description'     => 'Image at 60% width, left-aligned — pairs with text.',
						'type'            => 'image',
						'indicator_color' => '#f0f0f0',
						'settings'        => [
							'image_url'  => '',
							'alt_text'   => '',
							'link_url'   => '',
							'width'      => 60,
							'alignment'  => 'left',
							'caption'    => '',
						],
					],
					[
						'id'              => 'image-captioned',
						'label'           => 'Centred with Caption',
						'description'     => 'Image at 80% width with a caption line below.',
						'type'            => 'image',
						'indicator_color' => '#f0f0f0',
						'settings'        => [
							'image_url'  => '',
							'alt_text'   => '',
							'link_url'   => '',
							'width'      => 80,
							'alignment'  => 'center',
							'caption'    => 'Add your caption here',
						],
					],
				],
			],

			// ------------------------------------------------------------------ DIVIDER / SPACER
			[
				'category' => 'divider',
				'label'    => 'Divider / Spacer',
				'icon'     => 'horizontal_rule',
				'variants' => [
					[
						'id'              => 'divider-thin',
						'label'           => 'Thin Line',
						'description'     => '1px light grey horizontal rule with standard margins.',
						'type'            => 'divider',
						'indicator_color' => '#e5e5e5',
						'settings'        => [
							'type'          => 'divider',
							'color'         => '#e5e5e5',
							'thickness'     => 1,
							'margin_top'    => 20,
							'margin_bottom' => 20,
						],
					],
					[
						'id'              => 'divider-accent',
						'label'           => 'Accent Line',
						'description'     => '2px brand-coloured rule — draws the eye between sections.',
						'type'            => 'divider',
						'indicator_color' => '#e63529',
						'settings'        => [
							'type'          => 'divider',
							'color'         => '#e63529',
							'thickness'     => 2,
							'margin_top'    => 16,
							'margin_bottom' => 16,
						],
					],
					[
						'id'              => 'spacer-small',
						'label'           => 'Small Spacer',
						'description'     => 'Invisible 24px vertical gap — subtle breathing room.',
						'type'            => 'divider',
						'indicator_color' => '#f9f9f9',
						'settings'        => [
							'type'   => 'spacer',
							'height' => 24,
						],
					],
					[
						'id'              => 'spacer-large',
						'label'           => 'Large Spacer',
						'description'     => 'Invisible 56px vertical gap — creates clear section separation.',
						'type'            => 'divider',
						'indicator_color' => '#f9f9f9',
						'settings'        => [
							'type'   => 'spacer',
							'height' => 56,
						],
					],
				],
			],

			// ------------------------------------------------------------------ SOCIAL MEDIA
			[
				'category' => 'social',
				'label'    => 'Social Media',
				'icon'     => 'share',
				'variants' => [
					[
						'id'              => 'social-light',
						'label'           => 'Light Background',
						'description'     => 'White background with coloured circular icon links.',
						'type'            => 'social',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'heading'      => 'Follow Us',
							'social_links' => '[{"label":"Facebook","url":""},{"label":"Instagram","url":""},{"label":"Twitter","url":""},{"label":"TikTok","url":""}]',
							'bg_color'     => '#ffffff',
							'text_color'   => '#333333',
							'icon_bg'      => '#e63529',
							'icon_color'   => '#ffffff',
							'padding_top'  => 24,
							'padding_bottom' => 24,
						],
					],
					[
						'id'              => 'social-dark',
						'label'           => 'Dark Background',
						'description'     => 'Dark navy background — pairs with the dark footer.',
						'type'            => 'social',
						'indicator_color' => '#1a1a2e',
						'settings'        => [
							'heading'      => 'Find Us Online',
							'social_links' => '[{"label":"Facebook","url":""},{"label":"Instagram","url":""},{"label":"Twitter","url":""},{"label":"YouTube","url":""}]',
							'bg_color'     => '#1a1a2e',
							'text_color'   => '#cccccc',
							'icon_bg'      => '#e63529',
							'icon_color'   => '#ffffff',
							'padding_top'  => 24,
							'padding_bottom' => 24,
						],
					],
				],
			],

			// ------------------------------------------------------------------ FOOTER
			[
				'category' => 'footer',
				'label'    => 'Footer',
				'icon'     => 'space_bar',
				'variants' => [
					[
						'id'              => 'footer-text-only',
						'label'           => 'Text Only',
						'description'     => 'Plain footer text — no links, no unsubscribe line.',
						'type'            => 'footer',
						'indicator_color' => '#f5f5f5',
						'settings'        => [
							'footer_text'      => 'You received this email because you subscribed to our newsletter.',
							'show_unsubscribe' => false,
							'footer_links'     => '[]',
							'text_color'       => '#999999',
							'bg_color'         => '#f5f5f5',
						],
					],
					[
						'id'              => 'footer-with-links',
						'label'           => 'Text + Links',
						'description'     => 'Footer text with Unsubscribe and Privacy Policy links.',
						'type'            => 'footer',
						'indicator_color' => '#f5f5f5',
						'settings'        => [
							'footer_text'      => 'You received this email because you subscribed to our newsletter.',
							'show_unsubscribe' => true,
							'footer_links'     => '[{"label":"Unsubscribe","url":"{{unsubscribe_url}}"},{"label":"Privacy Policy","url":""}]',
							'text_color'       => '#999999',
							'bg_color'         => '#f5f5f5',
						],
					],
					[
						'id'              => 'footer-compact',
						'label'           => 'Compact + Dark',
						'description'     => 'Dark background footer with copyright text and unsubscribe link.',
						'type'            => 'footer',
						'indicator_color' => '#0c0e1a',
						'settings'        => [
							'footer_text'      => '© YEAR Your Store. All rights reserved.',
							'show_unsubscribe' => true,
							'footer_links'     => '[{"label":"Unsubscribe","url":"{{unsubscribe_url}}"}]',
							'text_color'       => '#8b92be',
							'bg_color'         => '#0c0e1a',
						],
					],
					[
						'id'              => 'footer-social',
						'label'           => 'Social Icons',
						'description'     => 'Footer text with social media icon links below.',
						'type'            => 'footer',
						'indicator_color' => '#f5f5f5',
						'settings'        => [
							'footer_text'      => 'Follow us and stay in the loop.',
							'show_unsubscribe' => true,
							'footer_links'     => '[{"label":"Unsubscribe","url":"{{unsubscribe_url}}"}]',
							'text_color'       => '#999999',
							'bg_color'         => '#f5f5f5',
							'show_social'      => true,
							'social_links'     => '[{"label":"Facebook","url":""},{"label":"Instagram","url":""},{"label":"Twitter","url":""}]',
						],
					],
					[
						'id'              => 'footer-minimal-white',
						'label'           => 'Minimal White',
						'description'     => 'Clean white footer with just an unsubscribe link.',
						'type'            => 'footer',
						'indicator_color' => '#ffffff',
						'settings'        => [
							'footer_text'      => 'You received this email because you opted in.',
							'show_unsubscribe' => true,
							'footer_links'     => '[{"label":"Unsubscribe","url":"{{unsubscribe_url}}"}]',
							'text_color'       => '#aaaaaa',
							'bg_color'         => '#ffffff',
							'show_social'      => false,
							'social_links'     => '[]',
						],
					],
					[
						'id'              => 'footer-brand',
						'label'           => 'Brand Colour',
						'description'     => 'Footer using the accent red as background — stands out.',
						'type'            => 'footer',
						'indicator_color' => '#e63529',
						'settings'        => [
							'footer_text'      => 'You received this email because you subscribed.',
							'show_unsubscribe' => true,
							'footer_links'     => '[{"label":"Unsubscribe","url":"{{unsubscribe_url}}"}]',
							'text_color'       => 'rgba(255,255,255,0.8)',
							'bg_color'         => '#e63529',
							'show_social'      => false,
							'social_links'     => '[]',
						],
					],
				],
			],

		];
	}

	/**
	 * Return a single variant by its ID, searching all categories.
	 *
	 * @param string $variant_id The variant ID to look up.
	 * @return array|null The variant array, or null if not found.
	 */
	public static function get_variant( string $variant_id ): ?array {
		foreach ( self::get_all() as $category ) {
			foreach ( $category['variants'] as $variant ) {
				if ( $variant['id'] === $variant_id ) {
					return $variant;
				}
			}
		}

		return null;
	}

	/**
	 * Return all presets formatted for JavaScript consumption.
	 *
	 * The returned structure is a grouped array of category objects, each with:
	 *   - label  (string)  Category display name
	 *   - icon   (string)  Category emoji / Material Icon name
	 *   - variants (array) Array of variant objects for that category
	 *
	 * @return array[]
	 */
	public static function get_all_for_js(): array {
		return array_values( self::get_all() );
	}

	/**
	 * Build a section array from a preset variant ID.
	 *
	 * Merges the preset settings with any overrides supplied by the caller,
	 * then wraps them in the standard section envelope used by the Section Builder.
	 *
	 * @param string $variant_id The preset variant ID.
	 * @param array  $overrides  Optional settings to override the preset defaults.
	 * @return array|null A ready-to-use section array, or null if the variant was not found.
	 */
	public static function build( string $variant_id, array $overrides = [] ): ?array {
		$variant = self::get_variant( $variant_id );

		if ( null === $variant ) {
			return null;
		}

		$settings = array_merge( $variant['settings'], $overrides );

		return [
			'id'       => uniqid( 'section_', true ),
			'type'     => $variant['type'],
			'settings' => $settings,
		];
	}
}
