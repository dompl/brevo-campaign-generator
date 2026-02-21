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
					array( 'key' => 'logo_width',  'label' => __( 'Logo Width (px)', 'brevo-campaign-generator' ), 'type' => 'number', 'default' => 180 ),
					array( 'key' => 'bg_color',    'label' => __( 'Background Colour', 'brevo-campaign-generator' ), 'type' => 'color',  'default' => '#ffffff' ),
					array( 'key' => 'show_nav',    'label' => __( 'Show Navigation', 'brevo-campaign-generator' ),  'type' => 'toggle', 'default' => false ),
					array( 'key' => 'nav_links',   'label' => __( 'Nav Links (JSON)', 'brevo-campaign-generator' ), 'type' => 'json',   'default' => '[]' ),
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
					array( 'key' => 'headline_size',  'label' => __( 'Headline Size (px)', 'brevo-campaign-generator' ),  'type' => 'number', 'default' => 36 ),
					array( 'key' => 'headline_color', 'label' => __( 'Headline Colour', 'brevo-campaign-generator' ),     'type' => 'color',  'default' => '#ffffff' ),
					array( 'key' => 'subtext',        'label' => __( 'Subtext', 'brevo-campaign-generator' ),             'type' => 'textarea', 'default' => 'Discover our latest collection' ),
					array( 'key' => 'subtext_color',  'label' => __( 'Subtext Colour', 'brevo-campaign-generator' ),      'type' => 'color',  'default' => '#cccccc' ),
					array( 'key' => 'cta_text',       'label' => __( 'Button Text', 'brevo-campaign-generator' ),         'type' => 'text',   'default' => 'Shop Now' ),
					array( 'key' => 'cta_url',        'label' => __( 'Button URL', 'brevo-campaign-generator' ),          'type' => 'text',   'default' => '' ),
					array( 'key' => 'cta_bg_color',   'label' => __( 'Button Background', 'brevo-campaign-generator' ),   'type' => 'color',  'default' => '#e63529' ),
					array( 'key' => 'cta_text_color', 'label' => __( 'Button Text Colour', 'brevo-campaign-generator' ),  'type' => 'color',  'default' => '#ffffff' ),
					array( 'key' => 'padding_top',    'label' => __( 'Padding Top (px)', 'brevo-campaign-generator' ),    'type' => 'number', 'default' => 48 ),
					array( 'key' => 'padding_bottom', 'label' => __( 'Padding Bottom (px)', 'brevo-campaign-generator' ), 'type' => 'number', 'default' => 48 ),
				),
			),

			// ── Text Block ───────────────────────────────────────────────
			'text' => array(
				'label'  => __( 'Text Block', 'brevo-campaign-generator' ),
				'icon'   => 'article',
				'has_ai' => true,
				'fields' => array(
					array( 'key' => 'heading',    'label' => __( 'Heading', 'brevo-campaign-generator' ),         'type' => 'text',     'default' => '' ),
					array( 'key' => 'body',       'label' => __( 'Body Text', 'brevo-campaign-generator' ),       'type' => 'textarea', 'default' => 'Add your text content here.' ),
					array( 'key' => 'text_color', 'label' => __( 'Text Colour', 'brevo-campaign-generator' ),     'type' => 'color',    'default' => '#333333' ),
					array( 'key' => 'bg_color',   'label' => __( 'Background Colour', 'brevo-campaign-generator' ), 'type' => 'color',  'default' => '#ffffff' ),
					array( 'key' => 'font_size',  'label' => __( 'Font Size (px)', 'brevo-campaign-generator' ),   'type' => 'number',  'default' => 15 ),
					array( 'key' => 'padding',    'label' => __( 'Padding (px)', 'brevo-campaign-generator' ),     'type' => 'number',  'default' => 30 ),
					array( 'key' => 'alignment',  'label' => __( 'Text Alignment', 'brevo-campaign-generator' ),   'type' => 'select',  'default' => 'left',
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
					array( 'key' => 'width',      'label' => __( 'Width (%)', 'brevo-campaign-generator' ),   'type' => 'number', 'default' => 100 ),
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
					array( 'key' => 'product_ids',   'label' => __( 'Products', 'brevo-campaign-generator' ), 'type' => 'product_select', 'default' => '' ),
					array( 'key' => 'columns',       'label' => __( 'Columns', 'brevo-campaign-generator' ),                       'type' => 'select', 'default' => '1',
						'options' => array(
							array( 'value' => '1', 'label' => '1' ),
							array( 'value' => '2', 'label' => '2' ),
							array( 'value' => '3', 'label' => '3' ),
						),
					),
					array( 'key' => 'show_price',    'label' => __( 'Show Price', 'brevo-campaign-generator' ),       'type' => 'toggle', 'default' => true ),
					array( 'key' => 'show_button',   'label' => __( 'Show Button', 'brevo-campaign-generator' ),      'type' => 'toggle', 'default' => true ),
					array( 'key' => 'button_text',   'label' => __( 'Button Text', 'brevo-campaign-generator' ),      'type' => 'text',   'default' => 'Buy Now' ),
					array( 'key' => 'button_color',  'label' => __( 'Button Colour', 'brevo-campaign-generator' ),    'type' => 'color',  'default' => '#e63529' ),
					array( 'key' => 'bg_color',      'label' => __( 'Background Colour', 'brevo-campaign-generator' ), 'type' => 'color', 'default' => '#ffffff' ),
				),
			),

			// ── Banner ────────────────────────────────────────────────────
			'banner' => array(
				'label'  => __( 'Banner', 'brevo-campaign-generator' ),
				'icon'   => 'campaign',
				'has_ai' => true,
				'fields' => array(
					array( 'key' => 'bg_color',    'label' => __( 'Background Colour', 'brevo-campaign-generator' ), 'type' => 'color',    'default' => '#e63529' ),
					array( 'key' => 'text_color',  'label' => __( 'Text Colour', 'brevo-campaign-generator' ),       'type' => 'color',    'default' => '#ffffff' ),
					array( 'key' => 'heading',     'label' => __( 'Heading', 'brevo-campaign-generator' ),           'type' => 'text',     'default' => 'Special Offer!' ),
					array( 'key' => 'subtext',     'label' => __( 'Subtext', 'brevo-campaign-generator' ),           'type' => 'textarea', 'default' => 'Don\'t miss out on this limited time deal.' ),
					array( 'key' => 'padding',     'label' => __( 'Padding (px)', 'brevo-campaign-generator' ),      'type' => 'number',   'default' => 30 ),
				),
			),

			// ── Call to Action ────────────────────────────────────────────
			'cta' => array(
				'label'  => __( 'Call to Action', 'brevo-campaign-generator' ),
				'icon'   => 'ads_click',
				'has_ai' => true,
				'fields' => array(
					array( 'key' => 'heading',          'label' => __( 'Heading', 'brevo-campaign-generator' ),            'type' => 'text',     'default' => 'Ready to shop?' ),
					array( 'key' => 'subtext',          'label' => __( 'Subtext', 'brevo-campaign-generator' ),            'type' => 'textarea', 'default' => 'Click below to explore our collection.' ),
					array( 'key' => 'button_text',      'label' => __( 'Button Text', 'brevo-campaign-generator' ),        'type' => 'text',     'default' => 'Shop Now' ),
					array( 'key' => 'button_url',       'label' => __( 'Button URL', 'brevo-campaign-generator' ),         'type' => 'text',     'default' => '' ),
					array( 'key' => 'button_bg',        'label' => __( 'Button Background', 'brevo-campaign-generator' ),  'type' => 'color',    'default' => '#e63529' ),
					array( 'key' => 'button_text_color','label' => __( 'Button Text Colour', 'brevo-campaign-generator' ), 'type' => 'color',    'default' => '#ffffff' ),
					array( 'key' => 'bg_color',         'label' => __( 'Background Colour', 'brevo-campaign-generator' ),  'type' => 'color',    'default' => '#f5f5f5' ),
					array( 'key' => 'text_color',       'label' => __( 'Text Colour', 'brevo-campaign-generator' ),        'type' => 'color',    'default' => '#333333' ),
					array( 'key' => 'padding',          'label' => __( 'Padding (px)', 'brevo-campaign-generator' ),       'type' => 'number',   'default' => 40 ),
				),
			),

			// ── Coupon ────────────────────────────────────────────────────
			'coupon' => array(
				'label'  => __( 'Coupon', 'brevo-campaign-generator' ),
				'icon'   => 'local_offer',
				'has_ai' => false,
				'fields' => array(
					array( 'key' => 'coupon_code',    'label' => __( 'Coupon Code', 'brevo-campaign-generator' ),    'type' => 'text',  'default' => 'SAVE10' ),
					array( 'key' => 'discount_text',  'label' => __( 'Discount Text', 'brevo-campaign-generator' ),  'type' => 'text',  'default' => 'Get 10% off your order!' ),
					array( 'key' => 'expiry_text',    'label' => __( 'Expiry Text', 'brevo-campaign-generator' ),    'type' => 'text',  'default' => 'Expires in 7 days' ),
					array( 'key' => 'bg_color',       'label' => __( 'Background Colour', 'brevo-campaign-generator' ), 'type' => 'color', 'default' => '#fff8e6' ),
					array( 'key' => 'accent_color',   'label' => __( 'Accent Colour', 'brevo-campaign-generator' ),  'type' => 'color', 'default' => '#e63529' ),
				),
			),

			// ── Divider ───────────────────────────────────────────────────
			'divider' => array(
				'label'  => __( 'Divider', 'brevo-campaign-generator' ),
				'icon'   => 'horizontal_rule',
				'has_ai' => false,
				'fields' => array(
					array( 'key' => 'color',         'label' => __( 'Colour', 'brevo-campaign-generator' ),            'type' => 'color',  'default' => '#e5e5e5' ),
					array( 'key' => 'thickness',     'label' => __( 'Thickness (px)', 'brevo-campaign-generator' ),    'type' => 'number', 'default' => 1 ),
					array( 'key' => 'margin_top',    'label' => __( 'Margin Top (px)', 'brevo-campaign-generator' ),   'type' => 'number', 'default' => 20 ),
					array( 'key' => 'margin_bottom', 'label' => __( 'Margin Bottom (px)', 'brevo-campaign-generator' ),'type' => 'number', 'default' => 20 ),
				),
			),

			// ── Spacer ────────────────────────────────────────────────────
			'spacer' => array(
				'label'  => __( 'Spacer', 'brevo-campaign-generator' ),
				'icon'   => 'space_bar',
				'has_ai' => false,
				'fields' => array(
					array( 'key' => 'height', 'label' => __( 'Height (px)', 'brevo-campaign-generator' ), 'type' => 'number', 'default' => 30 ),
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
					array( 'key' => 'font_size',    'label' => __( 'Font Size (px)', 'brevo-campaign-generator' ),     'type' => 'number',   'default' => 28 ),
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
					array( 'key' => 'padding',      'label' => __( 'Padding (px)', 'brevo-campaign-generator' ),      'type' => 'number',   'default' => 30 ),
				),
			),

			// ── List ──────────────────────────────────────────────────────
			'list' => array(
				'label'  => __( 'List', 'brevo-campaign-generator' ),
				'icon'   => 'format_list_bulleted',
				'has_ai' => false,
				'fields' => array(
					array( 'key' => 'heading',      'label' => __( 'Heading', 'brevo-campaign-generator' ),           'type' => 'text',   'default' => '' ),
					array( 'key' => 'items',        'label' => __( 'List Items (JSON)', 'brevo-campaign-generator' ), 'type' => 'json',   'default' => '[{"text":"First item"},{"text":"Second item"},{"text":"Third item"}]' ),
					array( 'key' => 'list_style',   'label' => __( 'List Style', 'brevo-campaign-generator' ),        'type' => 'select', 'default' => 'bullets',
						'options' => array(
							array( 'value' => 'bullets',  'label' => __( 'Bullets', 'brevo-campaign-generator' ) ),
							array( 'value' => 'numbers',  'label' => __( 'Numbers', 'brevo-campaign-generator' ) ),
							array( 'value' => 'checks',   'label' => __( 'Checkmarks', 'brevo-campaign-generator' ) ),
							array( 'value' => 'none',     'label' => __( 'None', 'brevo-campaign-generator' ) ),
						),
					),
					array( 'key' => 'text_color',   'label' => __( 'Text Colour', 'brevo-campaign-generator' ),       'type' => 'color',  'default' => '#333333' ),
					array( 'key' => 'bg_color',     'label' => __( 'Background Colour', 'brevo-campaign-generator' ), 'type' => 'color',  'default' => '#ffffff' ),
					array( 'key' => 'accent_color', 'label' => __( 'Accent / Icon Colour', 'brevo-campaign-generator' ), 'type' => 'color', 'default' => '#e63529' ),
					array( 'key' => 'font_size',    'label' => __( 'Font Size (px)', 'brevo-campaign-generator' ),    'type' => 'number', 'default' => 15 ),
					array( 'key' => 'padding',      'label' => __( 'Padding (px)', 'brevo-campaign-generator' ),      'type' => 'number', 'default' => 30 ),
				),
			),

			// ── Footer ────────────────────────────────────────────────────
			'footer' => array(
				'label'  => __( 'Footer', 'brevo-campaign-generator' ),
				'icon'   => 'web_asset_off',
				'has_ai' => false,
				'fields' => array(
					array( 'key' => 'footer_text',        'label' => __( 'Footer Text', 'brevo-campaign-generator' ),       'type' => 'textarea', 'default' => 'You received this email because you subscribed to our newsletter.' ),
					array( 'key' => 'footer_links',       'label' => __( 'Footer Links (JSON)', 'brevo-campaign-generator' ),'type' => 'json',     'default' => '[{"label":"Unsubscribe","url":"{{unsubscribe_url}}"}]' ),
					array( 'key' => 'text_color',         'label' => __( 'Text Colour', 'brevo-campaign-generator' ),        'type' => 'color',    'default' => '#999999' ),
					array( 'key' => 'bg_color',           'label' => __( 'Background Colour', 'brevo-campaign-generator' ),  'type' => 'color',    'default' => '#f5f5f5' ),
					array( 'key' => 'show_unsubscribe',   'label' => __( 'Show Unsubscribe', 'brevo-campaign-generator' ),   'type' => 'toggle',   'default' => true ),
				),
			),
		);
	}
}
