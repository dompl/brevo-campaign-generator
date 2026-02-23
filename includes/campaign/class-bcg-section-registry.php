<?php
/**
 * Section Registry.
 *
 * Defines all available section types for the Section Builder, including
 * their field schemas, defaults, AI capabilities, and icons.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.5.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Section_Registry
 *
 * Central registry for all email section types available in the Section Builder.
 *
 * @since 1.5.0
 */
class BCG_Section_Registry {

	/**
	 * All registered section type definitions.
	 *
	 * @var array|null
	 */
	private static ?array $types = null;

	/**
	 * Get all section type definitions.
	 *
	 * @since  1.5.0
	 * @return array[]
	 */
	public static function get_all(): array {
		if ( null === self::$types ) {
			self::$types = self::build_types();
		}
		return self::$types;
	}

	/**
	 * Get a single section type definition by slug.
	 *
	 * @since  1.5.0
	 * @param  string $slug Section type slug.
	 * @return array|null
	 */
	public static function get( string $slug ): ?array {
		$all = self::get_all();
		return $all[ $slug ] ?? null;
	}

	/**
	 * Get default settings for a section type.
	 *
	 * @since  1.5.0
	 * @param  string $slug Section type slug.
	 * @return array
	 */
	public static function get_defaults( string $slug ): array {
		$type = self::get( $slug );
		if ( ! $type ) {
			return array();
		}
		$defaults = array();
		foreach ( $type['fields'] as $field ) {
			$defaults[ $field['key'] ] = $field['default'] ?? '';
		}
		return $defaults;
	}

	/**
	 * Return all section types serialised for JavaScript localisation.
	 *
	 * @since  1.5.0
	 * @return array
	 */
	public static function get_all_for_js(): array {
		$result = array();
		foreach ( self::get_all() as $slug => $type ) {
			$result[ $slug ] = array(
				'slug'   => $slug,
				'label'  => $type['label'],
				'icon'   => $type['icon'],
				'has_ai' => $type['has_ai'],
				'fields' => $type['fields'],
				'defaults' => self::get_defaults( $slug ),
			);
		}
		return $result;
	}

	/**
	 * Build the full type definitions array.
	 *
	 * @since  1.5.0
	 * @return array[]
	 */
	private static function build_types(): array {
		return array(

			// ── Header ───────────────────────────────────────────────────
			'header' => array(
				'label'  => __( 'Header', 'brevo-campaign-generator' ),
				'icon'   => 'web_asset',
				'has_ai' => false,
				'fields' => array(
					array( 'key' => 'logo_url',    'label' => __( 'Logo URL', 'brevo-campaign-generator' ),        'type' => 'image',  'default' => '' ),
					array( 'key' => 'logo_width',  'label' => __( 'Logo Width (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 180, 'min' => 50, 'max' => 400, 'step' => 5 ),
					array( 'key' => 'bg_color',    'label' => __( 'Background Colour', 'brevo-campaign-generator' ), 'type' => 'color',  'default' => '#ffffff' ),
					array( 'key' => 'show_nav',    'label' => __( 'Show Navigation', 'brevo-campaign-generator' ),  'type' => 'toggle', 'default' => false ),
					array( 'key' => 'text_color', 'label' => __( 'Text / Link Colour', 'brevo-campaign-generator' ), 'type' => 'color',  'default' => '#333333' ),
					array( 'key' => 'nav_links',  'label' => __( 'Navigation Links', 'brevo-campaign-generator' ),  'type' => 'links',  'default' => '[]' ),
				),
			),

			// ── Hero / Banner ─────────────────────────────────────────────
			'hero' => array(
				'label'  => __( 'Hero / Banner', 'brevo-campaign-generator' ),
				'icon'   => 'panorama',
				'has_ai' => true,
				'fields' => array(
					array( 'key' => 'bg_color',       'label' => __( 'Background Colour', 'brevo-campaign-generator' ),  'type' => 'color',  'default' => '#1a1a2e' ),
					array( 'key' => 'image_url',      'label' => __( 'Background Image URL', 'brevo-campaign-generator' ), 'type' => 'image',  'default' => '' ),
					array( 'key' => 'headline',       'label' => __( 'Headline', 'brevo-campaign-generator' ),            'type' => 'text',   'default' => 'Your Campaign Headline' ),
					array( 'key' => 'headline_size',  'label' => __( 'Headline Size (px)', 'brevo-campaign-generator' ),  'type' => 'range', 'default' => 36, 'min' => 16, 'max' => 60, 'step' => 1 ),
					array( 'key' => 'headline_color', 'label' => __( 'Headline Colour', 'brevo-campaign-generator' ),     'type' => 'color',  'default' => '#ffffff' ),
					array( 'key' => 'subtext',        'label' => __( 'Subtext', 'brevo-campaign-generator' ),             'type' => 'textarea', 'default' => 'Discover our latest collection' ),
					array( 'key' => 'subtext_color',  'label' => __( 'Subtext Colour', 'brevo-campaign-generator' ),      'type' => 'color',  'default' => '#cccccc' ),
					array( 'key' => 'subtext_font_size','label' => __( 'Subtext Font Size (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 16, 'min' => 10, 'max' => 28, 'step' => 1 ),
					array( 'key' => 'cta_text',       'label' => __( 'Button Text', 'brevo-campaign-generator' ),         'type' => 'text',   'default' => 'Shop Now' ),
					array( 'key' => 'cta_url',        'label' => __( 'Button URL', 'brevo-campaign-generator' ),          'type' => 'text',   'default' => '' ),
					array( 'key' => 'cta_bg_color',   'label' => __( 'Button Background', 'brevo-campaign-generator' ),   'type' => 'color',  'default' => '#e63529' ),
					array( 'key' => 'cta_text_color', 'label' => __( 'Button Text Colour', 'brevo-campaign-generator' ),  'type' => 'color',  'default' => '#ffffff' ),
					array( 'key' => 'cta_font_size',  'label' => __( 'Button Font Size (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 16, 'min' => 10, 'max' => 28, 'step' => 1 ),
					array( 'key' => 'cta_padding_h',  'label' => __( 'Button Padding H (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 32, 'min' => 8, 'max' => 60, 'step' => 1 ),
					array( 'key' => 'cta_padding_v',  'label' => __( 'Button Padding V (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 14, 'min' => 4, 'max' => 28, 'step' => 1 ),
					array( 'key' => 'cta_border_radius', 'label' => __( 'Button Border Radius (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 4, 'min' => 0, 'max' => 30, 'step' => 1 ),
					array( 'key' => 'padding_top',    'label' => __( 'Padding Top (px)', 'brevo-campaign-generator' ),    'type' => 'range', 'default' => 48, 'min' => 0, 'max' => 120, 'step' => 4 ),
					array( 'key' => 'padding_bottom', 'label' => __( 'Padding Bottom (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 48, 'min' => 0, 'max' => 120, 'step' => 4 ),
				),
			),

			// ── Hero Split ────────────────────────────────────────────────
			'hero_split' => array(
				'label'  => __( 'Hero Split', 'brevo-campaign-generator' ),
				'icon'   => 'view_column',
				'has_ai' => true,
				'fields' => array(
					array( 'key' => 'image_url',       'label' => __( 'Image URL', 'brevo-campaign-generator' ),             'type' => 'image',  'default' => '' ),
					array( 'key' => 'image_side',      'label' => __( 'Image Side', 'brevo-campaign-generator' ),            'type' => 'select', 'default' => 'right',
						'options' => array(
							array( 'value' => 'right', 'label' => __( 'Right', 'brevo-campaign-generator' ) ),
							array( 'value' => 'left',  'label' => __( 'Left', 'brevo-campaign-generator' ) ),
						),
					),
					array( 'key' => 'text_bg_color',   'label' => __( 'Text Panel Background', 'brevo-campaign-generator' ), 'type' => 'color',  'default' => '#1a1a2e' ),
					array( 'key' => 'headline',        'label' => __( 'Headline', 'brevo-campaign-generator' ),              'type' => 'text',   'default' => 'Your Campaign Headline' ),
					array( 'key' => 'headline_size',   'label' => __( 'Headline Size (px)', 'brevo-campaign-generator' ),    'type' => 'range',  'default' => 32, 'min' => 16, 'max' => 60, 'step' => 1 ),
					array( 'key' => 'headline_color',  'label' => __( 'Headline Colour', 'brevo-campaign-generator' ),       'type' => 'color',  'default' => '#ffffff' ),
					array( 'key' => 'subtext',         'label' => __( 'Subtext', 'brevo-campaign-generator' ),               'type' => 'textarea', 'default' => 'Discover our latest collection' ),
					array( 'key' => 'subtext_color',   'label' => __( 'Subtext Colour', 'brevo-campaign-generator' ),        'type' => 'color',  'default' => '#cccccc' ),
					array( 'key' => 'subtext_font_size', 'label' => __( 'Subtext Font Size (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 15, 'min' => 10, 'max' => 28, 'step' => 1 ),
					array( 'key' => 'cta_text',        'label' => __( 'Button Text', 'brevo-campaign-generator' ),           'type' => 'text',   'default' => 'Shop Now' ),
					array( 'key' => 'cta_url',         'label' => __( 'Button URL', 'brevo-campaign-generator' ),            'type' => 'text',   'default' => '' ),
					array( 'key' => 'cta_bg_color',    'label' => __( 'Button Background', 'brevo-campaign-generator' ),    'type' => 'color',  'default' => '#e63529' ),
					array( 'key' => 'cta_text_color',  'label' => __( 'Button Text Colour', 'brevo-campaign-generator' ),   'type' => 'color',  'default' => '#ffffff' ),
					array( 'key' => 'cta_border_radius', 'label' => __( 'Button Border Radius (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 4, 'min' => 0, 'max' => 30, 'step' => 1 ),
					array( 'key' => 'text_padding', 'label' => __( 'Padding Top & Bottom (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 48, 'min' => 16, 'max' => 120, 'step' => 4 ),
				),
			),

			// ── Text Block ───────────────────────────────────────────────
			'text' => array(
				'label'  => __( 'Text Block', 'brevo-campaign-generator' ),
				'icon'   => 'article',
				'has_ai' => true,
				'fields' => array(
					array( 'key' => 'heading',        'label' => __( 'Heading', 'brevo-campaign-generator' ),               'type' => 'text',     'default' => '' ),
					array( 'key' => 'heading_size',   'label' => __( 'Heading Size (px)', 'brevo-campaign-generator' ),     'type' => 'range',    'default' => 22, 'min' => 14, 'max' => 48, 'step' => 1 ),
					array( 'key' => 'body',           'label' => __( 'Body Text', 'brevo-campaign-generator' ),             'type' => 'textarea', 'default' => 'Add your text content here.' ),
					array( 'key' => 'text_color',     'label' => __( 'Text Colour', 'brevo-campaign-generator' ),           'type' => 'color',    'default' => '#333333' ),
					array( 'key' => 'bg_color',       'label' => __( 'Background Colour', 'brevo-campaign-generator' ),     'type' => 'color',    'default' => '#ffffff' ),
					array( 'key' => 'font_size',      'label' => __( 'Font Size (px)', 'brevo-campaign-generator' ),        'type' => 'range',    'default' => 15, 'min' => 10, 'max' => 28, 'step' => 1 ),
					array( 'key' => 'line_height',    'label' => __( 'Line Height (%)', 'brevo-campaign-generator' ),       'type' => 'range',    'default' => 170, 'min' => 100, 'max' => 220, 'step' => 5 ),
					array( 'key' => 'padding_top',    'label' => __( 'Padding Top (px)', 'brevo-campaign-generator' ),      'type' => 'range',    'default' => 30, 'min' => 0, 'max' => 80, 'step' => 2 ),
					array( 'key' => 'padding_bottom', 'label' => __( 'Padding Bottom (px)', 'brevo-campaign-generator' ),   'type' => 'range',    'default' => 30, 'min' => 0, 'max' => 80, 'step' => 2 ),
					array( 'key' => 'alignment',      'label' => __( 'Text Alignment', 'brevo-campaign-generator' ),        'type' => 'select',   'default' => 'left',
						'options' => array(
							array( 'value' => 'left',   'label' => __( 'Left', 'brevo-campaign-generator' ) ),
							array( 'value' => 'center', 'label' => __( 'Centre', 'brevo-campaign-generator' ) ),
							array( 'value' => 'right',  'label' => __( 'Right', 'brevo-campaign-generator' ) ),
						),
					),
				),
			),

			// ── Image ─────────────────────────────────────────────────────
			'image' => array(
				'label'  => __( 'Image', 'brevo-campaign-generator' ),
				'icon'   => 'image',
				'has_ai' => false,
				'fields' => array(
					array( 'key' => 'image_url',  'label' => __( 'Image URL', 'brevo-campaign-generator' ),   'type' => 'image',  'default' => '' ),
					array( 'key' => 'alt_text',   'label' => __( 'Alt Text', 'brevo-campaign-generator' ),    'type' => 'text',   'default' => '' ),
					array( 'key' => 'link_url',   'label' => __( 'Link URL', 'brevo-campaign-generator' ),    'type' => 'text',   'default' => '' ),
					array( 'key' => 'width',      'label' => __( 'Width (%)', 'brevo-campaign-generator' ),   'type' => 'range',  'default' => 100, 'min' => 20, 'max' => 100, 'step' => 5 ),
					array( 'key' => 'alignment',  'label' => __( 'Alignment', 'brevo-campaign-generator' ),   'type' => 'select', 'default' => 'center',
						'options' => array(
							array( 'value' => 'left',   'label' => __( 'Left', 'brevo-campaign-generator' ) ),
							array( 'value' => 'center', 'label' => __( 'Centre', 'brevo-campaign-generator' ) ),
							array( 'value' => 'right',  'label' => __( 'Right', 'brevo-campaign-generator' ) ),
						),
					),
					array( 'key' => 'caption',    'label' => __( 'Caption', 'brevo-campaign-generator' ),     'type' => 'text',   'default' => '' ),
				),
			),

			// ── Products ─────────────────────────────────────────────────
			'products' => array(
				'label'  => __( 'Products', 'brevo-campaign-generator' ),
				'icon'   => 'shopping_cart',
				'has_ai' => true,
				'fields' => array(
					array( 'key' => 'section_headline', 'label' => __( 'Section Headline', 'brevo-campaign-generator' ), 'type' => 'text', 'default' => '' ),
					array( 'key' => 'product_ids',      'label' => __( 'Products', 'brevo-campaign-generator' ),             'type' => 'product_select', 'default' => '' ),
					array( 'key' => 'columns',          'label' => __( 'Columns', 'brevo-campaign-generator' ),              'type' => 'range',          'default' => 1,       'min' => 1, 'max' => 3, 'step' => 1 ),
					array( 'key' => 'product_gap',      'label' => __( 'Product Gap (px)', 'brevo-campaign-generator' ),     'type' => 'range',          'default' => 15,      'min' => 0, 'max' => 40, 'step' => 1 ),
					array( 'key' => 'text_align',       'label' => __( 'Text Alignment', 'brevo-campaign-generator' ),       'type' => 'select',         'default' => 'left',
						'options' => array(
							array( 'value' => 'left',   'label' => __( 'Left', 'brevo-campaign-generator' ) ),
							array( 'value' => 'center', 'label' => __( 'Centre', 'brevo-campaign-generator' ) ),
							array( 'value' => 'right',  'label' => __( 'Right', 'brevo-campaign-generator' ) ),
						),
					),
					array( 'key' => 'title_font_size',  'label' => __( 'Title Font Size (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 16, 'min' => 10, 'max' => 32, 'step' => 1 ),
					array( 'key' => 'desc_font_size',   'label' => __( 'Desc Font Size (px)', 'brevo-campaign-generator' ),  'type' => 'range', 'default' => 14, 'min' => 10, 'max' => 24, 'step' => 1 ),
					array( 'key' => 'price_font_size',  'label' => __( 'Price Font Size (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 16, 'min' => 10, 'max' => 24, 'step' => 1 ),
					array( 'key' => 'show_price',       'label' => __( 'Show Price', 'brevo-campaign-generator' ),           'type' => 'toggle', 'default' => true ),
					array( 'key' => 'show_button',      'label' => __( 'Show Button', 'brevo-campaign-generator' ),          'type' => 'toggle', 'default' => true ),
					array( 'key' => 'button_text',      'label' => __( 'Button Text', 'brevo-campaign-generator' ),          'type' => 'text',   'default' => 'Buy Now' ),
					array( 'key' => 'button_color',     'label' => __( 'Button Colour', 'brevo-campaign-generator' ),        'type' => 'color',  'default' => '#e63529' ),
					array( 'key' => 'button_text_color', 'label' => __( 'Button Text Colour', 'brevo-campaign-generator' ),  'type' => 'color',  'default' => '#ffffff' ),
					array( 'key' => 'button_font_size', 'label' => __( 'Button Font Size (px)', 'brevo-campaign-generator' ),'type' => 'range', 'default' => 14, 'min' => 10, 'max' => 22, 'step' => 1 ),
					array( 'key' => 'button_padding_h', 'label' => __( 'Button Padding H (px)', 'brevo-campaign-generator' ),'type' => 'range', 'default' => 20, 'min' => 8,  'max' => 48, 'step' => 1 ),
					array( 'key' => 'button_padding_v', 'label' => __( 'Button Padding V (px)', 'brevo-campaign-generator' ),'type' => 'range', 'default' => 10, 'min' => 4,  'max' => 24, 'step' => 1 ),
					array( 'key' => 'button_border_radius', 'label' => __( 'Button Border Radius (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 4, 'min' => 0, 'max' => 30, 'step' => 1 ),
					array( 'key' => 'square_images',    'label' => __( 'Square Crop Images', 'brevo-campaign-generator' ),  'type' => 'toggle', 'default' => false ),
					array( 'key' => 'image_size',       'label' => __( 'Image Size (px)', 'brevo-campaign-generator' ),     'type' => 'range',  'default' => 200, 'min' => 80, 'max' => 320, 'step' => 8 ),
					array( 'key' => 'bg_color',         'label' => __( 'Background Colour', 'brevo-campaign-generator' ),    'type' => 'color',  'default' => '#ffffff' ),
				),
			),

			// ── Banner ────────────────────────────────────────────────────
			'banner' => array(
				'label'  => __( 'Banner', 'brevo-campaign-generator' ),
				'icon'   => 'campaign',
				'has_ai' => true,
				'fields' => array(
					array( 'key' => 'bg_color',          'label' => __( 'Background Colour', 'brevo-campaign-generator' ),    'type' => 'color',    'default' => '#e63529' ),
					array( 'key' => 'text_color',        'label' => __( 'Text Colour', 'brevo-campaign-generator' ),          'type' => 'color',    'default' => '#ffffff' ),
					array( 'key' => 'heading',           'label' => __( 'Heading', 'brevo-campaign-generator' ),              'type' => 'text',     'default' => 'Special Offer!' ),
					array( 'key' => 'heading_font_size', 'label' => __( 'Heading Font Size (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 26, 'min' => 14, 'max' => 48, 'step' => 1 ),
					array( 'key' => 'subtext',           'label' => __( 'Subtext', 'brevo-campaign-generator' ),              'type' => 'textarea', 'default' => 'Don\'t miss out on this limited time deal.' ),
					array( 'key' => 'subtext_font_size', 'label' => __( 'Subtext Font Size (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 15, 'min' => 10, 'max' => 24, 'step' => 1 ),
					array( 'key' => 'text_align',        'label' => __( 'Text Alignment', 'brevo-campaign-generator' ),       'type' => 'select',   'default' => 'center',
						'options' => array(
							array( 'value' => 'left',   'label' => __( 'Left', 'brevo-campaign-generator' ) ),
							array( 'value' => 'center', 'label' => __( 'Centre', 'brevo-campaign-generator' ) ),
							array( 'value' => 'right',  'label' => __( 'Right', 'brevo-campaign-generator' ) ),
						),
					),
					array( 'key' => 'padding_top',    'label' => __( 'Padding Top (px)', 'brevo-campaign-generator' ),      'type' => 'range',    'default' => 30, 'min' => 0, 'max' => 80, 'step' => 2 ),
					array( 'key' => 'padding_bottom', 'label' => __( 'Padding Bottom (px)', 'brevo-campaign-generator' ),   'type' => 'range',    'default' => 30, 'min' => 0, 'max' => 80, 'step' => 2 ),
				),
			),

			// ── Call to Action ────────────────────────────────────────────
			'cta' => array(
				'label'  => __( 'Call to Action', 'brevo-campaign-generator' ),
				'icon'   => 'ads_click',
				'has_ai' => true,
				'fields' => array(
					array( 'key' => 'heading',            'label' => __( 'Heading', 'brevo-campaign-generator' ),              'type' => 'text',     'default' => 'Ready to shop?' ),
					array( 'key' => 'heading_font_size',  'label' => __( 'Heading Font Size (px)', 'brevo-campaign-generator' ), 'type' => 'range',  'default' => 26, 'min' => 14, 'max' => 48, 'step' => 1 ),
					array( 'key' => 'subtext',            'label' => __( 'Subtext', 'brevo-campaign-generator' ),              'type' => 'textarea', 'default' => 'Click below to explore our collection.' ),
					array( 'key' => 'subtext_font_size',  'label' => __( 'Subtext Font Size (px)', 'brevo-campaign-generator' ), 'type' => 'range',  'default' => 15, 'min' => 10, 'max' => 24, 'step' => 1 ),
					array( 'key' => 'button_text',        'label' => __( 'Button Text', 'brevo-campaign-generator' ),          'type' => 'text',     'default' => 'Shop Now' ),
					array( 'key' => 'button_url',         'label' => __( 'Button URL', 'brevo-campaign-generator' ),           'type' => 'text',     'default' => '' ),
					array( 'key' => 'button_bg',          'label' => __( 'Button Background', 'brevo-campaign-generator' ),    'type' => 'color',    'default' => '#e63529' ),
					array( 'key' => 'button_text_color',  'label' => __( 'Button Text Colour', 'brevo-campaign-generator' ),   'type' => 'color',    'default' => '#ffffff' ),
					array( 'key' => 'button_font_size',   'label' => __( 'Button Font Size (px)', 'brevo-campaign-generator' ), 'type' => 'range',  'default' => 17, 'min' => 10, 'max' => 28, 'step' => 1 ),
					array( 'key' => 'button_padding_h',   'label' => __( 'Button Padding H (px)', 'brevo-campaign-generator' ), 'type' => 'range',  'default' => 40, 'min' => 12, 'max' => 80, 'step' => 1 ),
					array( 'key' => 'button_padding_v',   'label' => __( 'Button Padding V (px)', 'brevo-campaign-generator' ), 'type' => 'range',  'default' => 16, 'min' => 6,  'max' => 32, 'step' => 1 ),
					array( 'key' => 'button_border_radius', 'label' => __( 'Button Border Radius (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 4, 'min' => 0, 'max' => 30, 'step' => 1 ),
					array( 'key' => 'bg_color',           'label' => __( 'Background Colour', 'brevo-campaign-generator' ),    'type' => 'color',    'default' => '#f5f5f5' ),
					array( 'key' => 'text_color',         'label' => __( 'Text Colour', 'brevo-campaign-generator' ),          'type' => 'color',    'default' => '#333333' ),
					array( 'key' => 'padding_top',    'label' => __( 'Padding Top (px)', 'brevo-campaign-generator' ),      'type' => 'range',    'default' => 40, 'min' => 0, 'max' => 80, 'step' => 2 ),
					array( 'key' => 'padding_bottom', 'label' => __( 'Padding Bottom (px)', 'brevo-campaign-generator' ),   'type' => 'range',    'default' => 40, 'min' => 0, 'max' => 80, 'step' => 2 ),
				),
			),

			// ── Coupon — Classic ─────────────────────────────────────────────
			'coupon' => array(
				'label'  => __( 'Coupon — Classic', 'brevo-campaign-generator' ),
				'icon'   => 'local_offer',
				'has_ai' => true,
				'fields' => array(
					array( 'key' => 'headline',      'label' => __( 'Headline', 'brevo-campaign-generator' ),      'type' => 'text',  'default' => 'Exclusive Offer Just For You' ),
					array( 'key' => 'coupon_text',   'label' => __( 'Offer Text', 'brevo-campaign-generator' ),    'type' => 'text',  'default' => 'Get 10% off your order!' ),
					array( 'key' => 'subtext',       'label' => __( 'Subtext', 'brevo-campaign-generator' ),       'type' => 'text',  'default' => 'Use at checkout' ),
					array( 'key' => 'coupon_code',   'label' => __( 'Coupon Code', 'brevo-campaign-generator' ),   'type' => 'text',  'default' => 'SAVE10' ),
					array( 'key' => 'expiry_date',   'label' => __( 'Expiry Date', 'brevo-campaign-generator' ),   'type' => 'date',  'default' => '' ),
					array( 'key' => 'bg_color',      'label' => __( 'Background Colour', 'brevo-campaign-generator' ), 'type' => 'color', 'default' => '#fff8e6' ),
					array( 'key' => 'accent_color',  'label' => __( 'Accent Colour', 'brevo-campaign-generator' ),  'type' => 'color', 'default' => '#e63529' ),
					array( 'key' => 'text_color',    'label' => __( 'Text Colour', 'brevo-campaign-generator' ),    'type' => 'color', 'default' => '#333333' ),
					array( 'key' => 'padding_top',   'label' => __( 'Padding Top (px)', 'brevo-campaign-generator' ),    'type' => 'range', 'default' => 30, 'min' => 0, 'max' => 80, 'step' => 2 ),
					array( 'key' => 'padding_bottom','label' => __( 'Padding Bottom (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 30, 'min' => 0, 'max' => 80, 'step' => 2 ),
				),
			),

			// ── Coupon — Banner ─────────────────────────────────────────────
			'coupon_banner' => array(
				'label'  => __( 'Coupon — Banner', 'brevo-campaign-generator' ),
				'icon'   => 'redeem',
				'has_ai' => true,
				'fields' => array(
					array( 'key' => 'headline',      'label' => __( 'Headline', 'brevo-campaign-generator' ),      'type' => 'text',  'default' => 'Limited Time Offer' ),
					array( 'key' => 'coupon_text',   'label' => __( 'Offer Text', 'brevo-campaign-generator' ),    'type' => 'text',  'default' => 'Save 20% Today' ),
					array( 'key' => 'subtext',       'label' => __( 'Subtext', 'brevo-campaign-generator' ),       'type' => 'text',  'default' => 'Apply at checkout' ),
					array( 'key' => 'coupon_code',   'label' => __( 'Coupon Code', 'brevo-campaign-generator' ),   'type' => 'text',  'default' => 'SAVE20' ),
					array( 'key' => 'expiry_date',   'label' => __( 'Expiry Date', 'brevo-campaign-generator' ),   'type' => 'date',  'default' => '' ),
					array( 'key' => 'bg_color',      'label' => __( 'Background Colour', 'brevo-campaign-generator' ), 'type' => 'color', 'default' => '#1a1a2e' ),
					array( 'key' => 'accent_color',  'label' => __( 'Accent Colour', 'brevo-campaign-generator' ),  'type' => 'color', 'default' => '#e63529' ),
					array( 'key' => 'text_color',    'label' => __( 'Text Colour', 'brevo-campaign-generator' ),    'type' => 'color', 'default' => '#ffffff' ),
					array( 'key' => 'padding_top',   'label' => __( 'Padding Top (px)', 'brevo-campaign-generator' ),    'type' => 'range', 'default' => 28, 'min' => 0, 'max' => 80, 'step' => 2 ),
					array( 'key' => 'padding_bottom','label' => __( 'Padding Bottom (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 28, 'min' => 0, 'max' => 80, 'step' => 2 ),
				),
			),

			// ── Coupon — Card ─────────────────────────────────────────────────
			'coupon_card' => array(
				'label'  => __( 'Coupon — Card', 'brevo-campaign-generator' ),
				'icon'   => 'card_giftcard',
				'has_ai' => true,
				'fields' => array(
					array( 'key' => 'headline',      'label' => __( 'Headline', 'brevo-campaign-generator' ),      'type' => 'text',  'default' => 'Your Special Discount' ),
					array( 'key' => 'coupon_text',   'label' => __( 'Offer Text', 'brevo-campaign-generator' ),    'type' => 'text',  'default' => '15% OFF' ),
					array( 'key' => 'subtext',       'label' => __( 'Subtext', 'brevo-campaign-generator' ),       'type' => 'text',  'default' => 'Enter code at checkout' ),
					array( 'key' => 'coupon_code',   'label' => __( 'Coupon Code', 'brevo-campaign-generator' ),   'type' => 'text',  'default' => 'GIFT15' ),
					array( 'key' => 'expiry_date',   'label' => __( 'Expiry Date', 'brevo-campaign-generator' ),   'type' => 'date',  'default' => '' ),
					array( 'key' => 'bg_color',      'label' => __( 'Background Colour', 'brevo-campaign-generator' ), 'type' => 'color', 'default' => '#ffffff' ),
					array( 'key' => 'card_bg',       'label' => __( 'Card Background', 'brevo-campaign-generator' ),   'type' => 'color', 'default' => '#f8f9ff' ),
					array( 'key' => 'accent_color',  'label' => __( 'Accent / Border Colour', 'brevo-campaign-generator' ), 'type' => 'color', 'default' => '#e63529' ),
					array( 'key' => 'text_color',    'label' => __( 'Text Colour', 'brevo-campaign-generator' ),    'type' => 'color', 'default' => '#222222' ),
					array( 'key' => 'padding_top',   'label' => __( 'Padding Top (px)', 'brevo-campaign-generator' ),    'type' => 'range', 'default' => 24, 'min' => 0, 'max' => 80, 'step' => 2 ),
					array( 'key' => 'padding_bottom','label' => __( 'Padding Bottom (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 24, 'min' => 0, 'max' => 80, 'step' => 2 ),
				),
			),

			// ── Coupon — Split ────────────────────────────────────────────────
			'coupon_split' => array(
				'label'  => __( 'Coupon — Split', 'brevo-campaign-generator' ),
				'icon'   => 'view_column',
				'has_ai' => true,
				'fields' => array(
					array( 'key' => 'headline',      'label' => __( 'Headline', 'brevo-campaign-generator' ),       'type' => 'text',  'default' => 'Exclusive Member Offer' ),
					array( 'key' => 'coupon_text',   'label' => __( 'Offer Text', 'brevo-campaign-generator' ),     'type' => 'text',  'default' => '25% OFF' ),
					array( 'key' => 'subtext',       'label' => __( 'Subtext', 'brevo-campaign-generator' ),        'type' => 'text',  'default' => 'Use code at checkout' ),
					array( 'key' => 'coupon_code',   'label' => __( 'Coupon Code', 'brevo-campaign-generator' ),    'type' => 'text',  'default' => 'VIP25' ),
					array( 'key' => 'expiry_date',   'label' => __( 'Expiry Date', 'brevo-campaign-generator' ),    'type' => 'date',  'default' => '' ),
					array( 'key' => 'discount_text', 'label' => __( 'Discount Amount', 'brevo-campaign-generator' ), 'type' => 'text',  'default' => '25%' ),
					array( 'key' => 'discount_label','label' => __( 'Discount Label', 'brevo-campaign-generator' ),  'type' => 'text',  'default' => 'OFF' ),
					array( 'key' => 'left_bg',       'label' => __( 'Left Panel Background', 'brevo-campaign-generator' ), 'type' => 'color', 'default' => '#e63529' ),
					array( 'key' => 'right_bg',      'label' => __( 'Right Panel Background', 'brevo-campaign-generator' ), 'type' => 'color', 'default' => '#ffffff' ),
					array( 'key' => 'left_text_color','label' => __( 'Left Text Colour', 'brevo-campaign-generator' ),  'type' => 'color', 'default' => '#ffffff' ),
					array( 'key' => 'right_text_color','label' => __( 'Right Text Colour', 'brevo-campaign-generator' ), 'type' => 'color', 'default' => '#222222' ),
					array( 'key' => 'accent_color',  'label' => __( 'Code Accent Colour', 'brevo-campaign-generator' ), 'type' => 'color', 'default' => '#e63529' ),
					array( 'key' => 'padding_top',   'label' => __( 'Padding Top (px)', 'brevo-campaign-generator' ),    'type' => 'range', 'default' => 0, 'min' => 0, 'max' => 40, 'step' => 2 ),
					array( 'key' => 'padding_bottom','label' => __( 'Padding Bottom (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 0, 'min' => 0, 'max' => 40, 'step' => 2 ),
				),
			),

			// ── Coupon — Minimal ─────────────────────────────────────────────────
			'coupon_minimal' => array(
				'label'  => __( 'Coupon — Minimal', 'brevo-campaign-generator' ),
				'icon'   => 'confirmation_number',
				'has_ai' => true,
				'fields' => array(
					array( 'key' => 'headline',      'label' => __( 'Headline', 'brevo-campaign-generator' ),      'type' => 'text',  'default' => 'Special Offer' ),
					array( 'key' => 'coupon_text',   'label' => __( 'Offer Text', 'brevo-campaign-generator' ),    'type' => 'text',  'default' => 'Get 20% off your order' ),
					array( 'key' => 'subtext',       'label' => __( 'Subtext', 'brevo-campaign-generator' ),       'type' => 'text',  'default' => 'Use at checkout. Limited time only.' ),
					array( 'key' => 'coupon_code',   'label' => __( 'Coupon Code', 'brevo-campaign-generator' ),   'type' => 'text',  'default' => 'SAVE20' ),
					array( 'key' => 'expiry_date',   'label' => __( 'Expiry Date', 'brevo-campaign-generator' ),   'type' => 'date',  'default' => '' ),
					array( 'key' => 'bg_color',      'label' => __( 'Background Colour', 'brevo-campaign-generator' ), 'type' => 'color', 'default' => '#f9f9f9' ),
					array( 'key' => 'text_color',    'label' => __( 'Text Colour', 'brevo-campaign-generator' ),    'type' => 'color', 'default' => '#222222' ),
					array( 'key' => 'accent_color',  'label' => __( 'Accent Colour', 'brevo-campaign-generator' ),  'type' => 'color', 'default' => '#e63529' ),
					array( 'key' => 'padding_top',   'label' => __( 'Padding Top (px)', 'brevo-campaign-generator' ),    'type' => 'range', 'default' => 40, 'min' => 0, 'max' => 80, 'step' => 4 ),
					array( 'key' => 'padding_bottom','label' => __( 'Padding Bottom (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 40, 'min' => 0, 'max' => 80, 'step' => 4 ),
				),
			),

			// ── Coupon — Ribbon ──────────────────────────────────────────────────
			'coupon_ribbon' => array(
				'label'  => __( 'Coupon — Ribbon', 'brevo-campaign-generator' ),
				'icon'   => 'local_offer',
				'has_ai' => true,
				'fields' => array(
					array( 'key' => 'headline',      'label' => __( 'Headline', 'brevo-campaign-generator' ),      'type' => 'text',  'default' => 'Exclusive Deal' ),
					array( 'key' => 'coupon_text',   'label' => __( 'Offer Text', 'brevo-campaign-generator' ),    'type' => 'text',  'default' => 'Save big on your next purchase' ),
					array( 'key' => 'subtext',       'label' => __( 'Subtext', 'brevo-campaign-generator' ),       'type' => 'text',  'default' => 'Enter code at checkout to redeem.' ),
					array( 'key' => 'coupon_code',   'label' => __( 'Coupon Code', 'brevo-campaign-generator' ),   'type' => 'text',  'default' => 'EXCLUSIVE' ),
					array( 'key' => 'expiry_date',   'label' => __( 'Expiry Date', 'brevo-campaign-generator' ),   'type' => 'date',  'default' => '' ),
					array( 'key' => 'bg_color',      'label' => __( 'Background Colour', 'brevo-campaign-generator' ), 'type' => 'color', 'default' => '#1a1a2e' ),
					array( 'key' => 'text_color',    'label' => __( 'Text Colour', 'brevo-campaign-generator' ),    'type' => 'color', 'default' => '#ffffff' ),
					array( 'key' => 'accent_color',  'label' => __( 'Accent Colour', 'brevo-campaign-generator' ),  'type' => 'color', 'default' => '#e63529' ),
					array( 'key' => 'ribbon_color',  'label' => __( 'Ribbon Colour', 'brevo-campaign-generator' ),  'type' => 'color', 'default' => '#f5c518' ),
					array( 'key' => 'padding_top',   'label' => __( 'Padding Top (px)', 'brevo-campaign-generator' ),    'type' => 'range', 'default' => 48, 'min' => 0, 'max' => 80, 'step' => 4 ),
					array( 'key' => 'padding_bottom','label' => __( 'Padding Bottom (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 48, 'min' => 0, 'max' => 80, 'step' => 4 ),
				),
			),

						// ── Divider ───────────────────────────────────────────────────
			'divider' => array(
				'label'  => __( 'Divider', 'brevo-campaign-generator' ),
				'icon'   => 'horizontal_rule',
				'has_ai' => false,
				'fields' => array(
					array( 'key' => 'color',         'label' => __( 'Colour', 'brevo-campaign-generator' ),            'type' => 'color',  'default' => '#e5e5e5' ),
					array( 'key' => 'thickness',     'label' => __( 'Thickness (px)', 'brevo-campaign-generator' ),    'type' => 'range',  'default' => 1, 'min' => 1, 'max' => 8, 'step' => 1 ),
					array( 'key' => 'margin_top',    'label' => __( 'Margin Top (px)', 'brevo-campaign-generator' ),   'type' => 'range',  'default' => 20, 'min' => 0, 'max' => 60, 'step' => 2 ),
					array( 'key' => 'margin_bottom', 'label' => __( 'Margin Bottom (px)', 'brevo-campaign-generator' ),'type' => 'range',  'default' => 20, 'min' => 0, 'max' => 60, 'step' => 2 ),
				),
			),

			// ── Spacer ────────────────────────────────────────────────────
			'spacer' => array(
				'label'  => __( 'Spacer', 'brevo-campaign-generator' ),
				'icon'   => 'space_bar',
				'has_ai' => false,
				'fields' => array(
					array( 'key' => 'height', 'label' => __( 'Height (px)', 'brevo-campaign-generator' ), 'type' => 'range',  'default' => 30, 'min' => 8, 'max' => 120, 'step' => 4 ),
				),
			),

			// ── Heading ───────────────────────────────────────────────────
			'heading' => array(
				'label'  => __( 'Heading', 'brevo-campaign-generator' ),
				'icon'   => 'title',
				'has_ai' => false,
				'fields' => array(
					array( 'key' => 'text',         'label' => __( 'Heading Text', 'brevo-campaign-generator' ),       'type' => 'text',     'default' => 'Section Heading' ),
					array( 'key' => 'subtext',      'label' => __( 'Subtext', 'brevo-campaign-generator' ),            'type' => 'text',     'default' => '' ),
					array( 'key' => 'font_size',    'label' => __( 'Font Size (px)', 'brevo-campaign-generator' ),     'type' => 'range',    'default' => 28, 'min' => 16, 'max' => 48, 'step' => 1 ),
					array( 'key' => 'text_color',   'label' => __( 'Text Colour', 'brevo-campaign-generator' ),        'type' => 'color',    'default' => '#111111' ),
					array( 'key' => 'bg_color',     'label' => __( 'Background Colour', 'brevo-campaign-generator' ),  'type' => 'color',    'default' => '#ffffff' ),
					array( 'key' => 'alignment',    'label' => __( 'Alignment', 'brevo-campaign-generator' ),          'type' => 'select',   'default' => 'center',
						'options' => array(
							array( 'value' => 'left',   'label' => __( 'Left', 'brevo-campaign-generator' ) ),
							array( 'value' => 'center', 'label' => __( 'Centre', 'brevo-campaign-generator' ) ),
							array( 'value' => 'right',  'label' => __( 'Right', 'brevo-campaign-generator' ) ),
						),
					),
					array( 'key' => 'accent_color', 'label' => __( 'Accent Colour', 'brevo-campaign-generator' ),     'type' => 'color',    'default' => '#e63529' ),
					array( 'key' => 'show_accent',  'label' => __( 'Show Accent Line', 'brevo-campaign-generator' ),  'type' => 'toggle',   'default' => true ),
					array( 'key' => 'padding_top',    'label' => __( 'Padding Top (px)', 'brevo-campaign-generator' ),     'type' => 'range',    'default' => 30, 'min' => 0, 'max' => 60, 'step' => 2 ),
					array( 'key' => 'padding_bottom', 'label' => __( 'Padding Bottom (px)', 'brevo-campaign-generator' ),  'type' => 'range',    'default' => 30, 'min' => 0, 'max' => 60, 'step' => 2 ),
				),
			),

			// ── List ──────────────────────────────────────────────────────
			'list' => array(
				'label'  => __( 'List', 'brevo-campaign-generator' ),
				'icon'   => 'format_list_bulleted',
				'has_ai' => true,
				'fields' => array(
					array( 'key' => 'heading',      'label' => __( 'Heading', 'brevo-campaign-generator' ),           'type' => 'text',   'default' => '' ),
					array( 'key' => 'items',        'label' => __( 'List Items (one per line)', 'brevo-campaign-generator' ), 'type' => 'textarea', 'default' => "First item\nSecond item\nThird item" ),
					array( 'key' => 'list_style',   'label' => __( 'List Style', 'brevo-campaign-generator' ),        'type' => 'select', 'default' => 'bullets',
						'options' => array(
							array( 'value' => 'bullets',  'label' => __( 'Bullets', 'brevo-campaign-generator' ) ),
							array( 'value' => 'numbers',  'label' => __( 'Numbers', 'brevo-campaign-generator' ) ),
							array( 'value' => 'checks',   'label' => __( 'Checkmarks', 'brevo-campaign-generator' ) ),
							array( 'value' => 'none',     'label' => __( 'None', 'brevo-campaign-generator' ) ),
						array( 'value' => 'arrows',   'label' => __( 'Arrows (→)', 'brevo-campaign-generator' ) ),
						array( 'value' => 'stars',    'label' => __( 'Stars (★)', 'brevo-campaign-generator' ) ),
						array( 'value' => 'dashes',   'label' => __( 'Dashes (–)', 'brevo-campaign-generator' ) ),
						array( 'value' => 'heart',    'label' => __( 'Hearts (♥)', 'brevo-campaign-generator' ) ),
						array( 'value' => 'diamond',  'label' => __( 'Diamonds (◆)', 'brevo-campaign-generator' ) ),
						),
					),
					array( 'key' => 'text_color',   'label' => __( 'Text Colour', 'brevo-campaign-generator' ),       'type' => 'color',  'default' => '#333333' ),
					array( 'key' => 'bg_color',     'label' => __( 'Background Colour', 'brevo-campaign-generator' ), 'type' => 'color',  'default' => '#ffffff' ),
					array( 'key' => 'accent_color', 'label' => __( 'Accent / Icon Colour', 'brevo-campaign-generator' ), 'type' => 'color', 'default' => '#e63529' ),
					array( 'key' => 'font_size',    'label' => __( 'Font Size (px)', 'brevo-campaign-generator' ),    'type' => 'range',  'default' => 15, 'min' => 10, 'max' => 24, 'step' => 1 ),
					array( 'key' => 'text_align',   'label' => __( 'Text Alignment', 'brevo-campaign-generator' ),    'type' => 'select', 'default' => 'left',
						'options' => array(
							array( 'value' => 'left',   'label' => __( 'Left', 'brevo-campaign-generator' ) ),
							array( 'value' => 'center', 'label' => __( 'Centre', 'brevo-campaign-generator' ) ),
							array( 'value' => 'right',  'label' => __( 'Right', 'brevo-campaign-generator' ) ),
						),
					),
					array( 'key' => 'padding_top',    'label' => __( 'Padding Top (px)', 'brevo-campaign-generator' ),     'type' => 'range',  'default' => 30, 'min' => 0, 'max' => 60, 'step' => 2 ),
					array( 'key' => 'padding_bottom', 'label' => __( 'Padding Bottom (px)', 'brevo-campaign-generator' ),  'type' => 'range',  'default' => 30, 'min' => 0, 'max' => 60, 'step' => 2 ),
					array( 'key' => 'item_gap', 'label' => __( 'Gap Between Items (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 8, 'min' => 0, 'max' => 32, 'step' => 2 ),
				),
			),

			// ── Social Media ──────────────────────────────────────────
			'social' => array(
				'label'  => __( 'Social Media', 'brevo-campaign-generator' ),
				'icon'   => 'share',
				'has_ai' => false,
				'fields' => array(
					array( 'key' => 'heading',    'label' => __( 'Heading', 'brevo-campaign-generator' ),             'type' => 'text',   'default' => 'Follow Us' ),
					array( 'key' => 'social_links', 'label' => __( 'Social Links', 'brevo-campaign-generator' ),     'type' => 'links',  'default' => '[{"label":"Facebook","url":""},{"label":"Instagram","url":""},{"label":"Twitter","url":""},{"label":"TikTok","url":""}]' ),
					array( 'key' => 'bg_color',   'label' => __( 'Background Colour', 'brevo-campaign-generator' ),  'type' => 'color',  'default' => '#ffffff' ),
					array( 'key' => 'text_color', 'label' => __( 'Text Colour', 'brevo-campaign-generator' ),        'type' => 'color',  'default' => '#333333' ),
					array( 'key' => 'icon_bg',    'label' => __( 'Icon Background', 'brevo-campaign-generator' ),    'type' => 'color',  'default' => '#e63529' ),
					array( 'key' => 'icon_color', 'label' => __( 'Icon Text Colour', 'brevo-campaign-generator' ),   'type' => 'color',  'default' => '#ffffff' ),
					array( 'key' => 'padding_top',    'label' => __( 'Padding Top (px)', 'brevo-campaign-generator' ),    'type' => 'range', 'default' => 24, 'min' => 0, 'max' => 60, 'step' => 2 ),
					array( 'key' => 'padding_bottom', 'label' => __( 'Padding Bottom (px)', 'brevo-campaign-generator' ), 'type' => 'range', 'default' => 24, 'min' => 0, 'max' => 60, 'step' => 2 ),
				),
			),

			// ── Footer ────────────────────────────────────────────────────
			'footer' => array(
				'label'  => __( 'Footer', 'brevo-campaign-generator' ),
				'icon'   => 'web_asset_off',
				'has_ai' => true,
				'fields' => array(
					array( 'key' => 'footer_text',        'label' => __( 'Footer Text', 'brevo-campaign-generator' ),       'type' => 'textarea', 'default' => 'You received this email because you subscribed to our newsletter.' ),
					array( 'key' => 'footer_links',       'label' => __( 'Footer Links', 'brevo-campaign-generator' ),        'type' => 'links',    'default' => '[{"label":"Unsubscribe","url":"{{unsubscribe_url}}"}]' ),
					array( 'key' => 'text_color',         'label' => __( 'Text Colour', 'brevo-campaign-generator' ),        'type' => 'color',    'default' => '#999999' ),
					array( 'key' => 'bg_color',           'label' => __( 'Background Colour', 'brevo-campaign-generator' ),  'type' => 'color',    'default' => '#f5f5f5' ),
					array( 'key' => 'show_unsubscribe',   'label' => __( 'Show Unsubscribe', 'brevo-campaign-generator' ),   'type' => 'toggle',   'default' => true ),
					array( 'key' => 'show_social',    'label' => __( 'Show Social Media Icons', 'brevo-campaign-generator' ), 'type' => 'toggle', 'default' => false ),
					array( 'key' => 'social_links',   'label' => __( 'Social Media Links', 'brevo-campaign-generator' ),      'type' => 'links',  'default' => '[{"label":"Facebook","url":""},{"label":"Instagram","url":""},{"label":"Twitter","url":""}]' ),
				),
			),
		);
	}
}
