<?php
/**
 * Template registry.
 *
 * Provides a central registry of all available email templates. Each template
 * has a slug, display name, description, HTML file path, and default settings
 * (colours, fonts, layout). The registry is used by the template editor to
 * present a chooser strip and by the template engine to load templates by slug.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Template_Registry
 *
 * Singleton registry of all bundled email templates.
 *
 * @since 1.1.0
 */
class BCG_Template_Registry {

	/**
	 * Singleton instance.
	 *
	 * @var BCG_Template_Registry|null
	 */
	private static ?BCG_Template_Registry $instance = null;

	/**
	 * Cached templates array.
	 *
	 * @var array|null
	 */
	private ?array $templates = null;

	/**
	 * Private constructor.
	 */
	private function __construct() {}

	/**
	 * Get the singleton instance.
	 *
	 * @since  1.1.0
	 * @return BCG_Template_Registry
	 */
	public static function get_instance(): BCG_Template_Registry {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get all registered templates.
	 *
	 * @since  1.1.0
	 * @return array Array of template definitions keyed by slug.
	 */
	public function get_templates(): array {
		if ( null !== $this->templates ) {
			return $this->templates;
		}

		$this->templates = $this->register_templates();

		return $this->templates;
	}

	/**
	 * Get a single template by slug.
	 *
	 * @since  1.1.0
	 *
	 * @param string $slug The template slug.
	 * @return array|null The template definition, or null if not found.
	 */
	public function get_template( string $slug ): ?array {
		$templates = $this->get_templates();

		return $templates[ $slug ] ?? null;
	}

	/**
	 * Get the HTML content for a template by slug.
	 *
	 * @since  1.1.0
	 *
	 * @param string $slug The template slug.
	 * @return string The template HTML, or empty string if not found.
	 */
	public function get_template_html( string $slug ): string {
		$template = $this->get_template( $slug );

		if ( ! $template || empty( $template['html_file'] ) ) {
			return '';
		}

		$file_path = $template['html_file'];

		if ( ! file_exists( $file_path ) ) {
			return '';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$contents = file_get_contents( $file_path );

		return false !== $contents ? $contents : '';
	}

	/**
	 * Get the default settings for a template by slug.
	 *
	 * @since  1.1.0
	 *
	 * @param string $slug The template slug.
	 * @return array The template settings, or empty array if not found.
	 */
	public function get_template_settings( string $slug ): array {
		$template = $this->get_template( $slug );

		return $template['settings'] ?? array();
	}

	/**
	 * Get all template slugs.
	 *
	 * @since  1.1.0
	 * @return array List of template slugs.
	 */
	public function get_slugs(): array {
		return array_keys( $this->get_templates() );
	}

	/**
	 * Register all bundled templates.
	 *
	 * @since  1.1.0
	 * @return array Array of template definitions keyed by slug.
	 */
	private function register_templates(): array {
		$templates_dir = BCG_PLUGIN_DIR . 'templates/';

		// Shared base settings — all templates use the same default colours.
		// Users customise colours via the settings panel.
		$base_settings = array(
			'logo_url'             => '',
			'logo_width'           => 180,
			'nav_links'            => array(
				array( 'label' => 'Shop', 'url' => '' ),
				array( 'label' => 'About', 'url' => '' ),
			),
			'show_nav'             => true,
			'primary_color'        => '#e84040',
			'background_color'     => '#f5f5f5',
			'content_background'   => '#ffffff',
			'text_color'           => '#333333',
			'link_color'           => '#e84040',
			'button_color'         => '#e84040',
			'button_text_color'    => '#ffffff',
			'button_border_radius' => 4,
			'font_family'          => 'Arial, sans-serif',
			'header_text'          => '',
			'footer_text'          => __( 'You received this email because you subscribed to our newsletter.', 'brevo-campaign-generator' ),
			'footer_links'         => array(
				array( 'label' => 'Privacy Policy', 'url' => '' ),
				array( 'label' => 'Unsubscribe', 'url' => '{{unsubscribe_url}}' ),
			),
			'max_width'            => 600,
			'show_coupon_block'    => true,
			'product_layout'       => 'stacked',
			'products_per_row'     => 1,
			'product_gap'          => 24,
			'product_button_size'  => 'medium',
			'section_order'        => null,
		);

		$templates = array();

		// 1. Classic — Standard card, contained hero, side-by-side products.
		$templates['classic'] = array(
			'slug'        => 'classic',
			'name'        => __( 'Classic', 'brevo-campaign-generator' ),
			'description' => __( 'Standard card layout with contained hero and side-by-side products.', 'brevo-campaign-generator' ),
			'html_file'   => $templates_dir . 'default-email-template.html',
			'settings'    => array_merge( $base_settings, array(
				'product_layout' => 'side-by-side',
			) ),
		);

		// 2. Full Width — Full-bleed hero, edge-to-edge sections, stacked products.
		$templates['full-width'] = array(
			'slug'        => 'full-width',
			'name'        => __( 'Full Width', 'brevo-campaign-generator' ),
			'description' => __( 'Full-bleed hero with edge-to-edge sections and stacked products.', 'brevo-campaign-generator' ),
			'html_file'   => $templates_dir . 'full-width-email-template.html',
			'settings'    => array_merge( $base_settings, array(
				'product_layout' => 'stacked',
			) ),
		);

		// 3. Reversed — Standard card, images on the right.
		$templates['reversed'] = array(
			'slug'        => 'reversed',
			'name'        => __( 'Reversed', 'brevo-campaign-generator' ),
			'description' => __( 'Products with text on the left and images on the right.', 'brevo-campaign-generator' ),
			'html_file'   => $templates_dir . 'reversed-email-template.html',
			'settings'    => array_merge( $base_settings, array(
				'product_layout' => 'reversed',
			) ),
		);

		// 4. Alternating — Zigzag layout, no hero, headline-first design.
		$templates['alternating'] = array(
			'slug'        => 'alternating',
			'name'        => __( 'Alternating', 'brevo-campaign-generator' ),
			'description' => __( 'Zigzag product layout alternating image left and right.', 'brevo-campaign-generator' ),
			'html_file'   => $templates_dir . 'alternating-email-template.html',
			'settings'    => array_merge( $base_settings, array(
				'product_layout' => 'alternating',
			) ),
		);

		// 5. Grid — 2-column product grid with images on top.
		$templates['grid'] = array(
			'slug'        => 'grid',
			'name'        => __( 'Grid', 'brevo-campaign-generator' ),
			'description' => __( 'Two-column product grid, ideal for showcasing multiple items.', 'brevo-campaign-generator' ),
			'html_file'   => $templates_dir . 'grid-email-template.html',
			'settings'    => array_merge( $base_settings, array(
				'product_layout'   => 'grid',
				'products_per_row' => 2,
			) ),
		);

		// 6. Compact — Narrow 480px, small thumbnails, minimal header.
		$templates['compact'] = array(
			'slug'        => 'compact',
			'name'        => __( 'Compact', 'brevo-campaign-generator' ),
			'description' => __( 'Narrow layout with small thumbnails and tight spacing.', 'brevo-campaign-generator' ),
			'html_file'   => $templates_dir . 'compact-email-template.html',
			'settings'    => array_merge( $base_settings, array(
				'product_layout' => 'compact',
				'max_width'      => 480,
			) ),
		);

		// 7. Cards — Each product in a bordered card with image on top.
		$templates['cards'] = array(
			'slug'        => 'cards',
			'name'        => __( 'Cards', 'brevo-campaign-generator' ),
			'description' => __( 'Each product in its own bordered card with rounded corners.', 'brevo-campaign-generator' ),
			'html_file'   => $templates_dir . 'cards-email-template.html',
			'settings'    => array_merge( $base_settings, array(
				'product_layout' => 'full-card',
			) ),
		);

		// 8. Feature — First product large, rest compact.
		$templates['feature'] = array(
			'slug'        => 'feature',
			'name'        => __( 'Feature', 'brevo-campaign-generator' ),
			'description' => __( 'First product displayed large, remaining products shown compact.', 'brevo-campaign-generator' ),
			'html_file'   => $templates_dir . 'feature-email-template.html',
			'settings'    => array_merge( $base_settings, array(
				'product_layout' => 'feature-first',
			) ),
		);

		// 9. Text Only — No images, just text with thin dividers.
		$templates['text-only'] = array(
			'slug'        => 'text-only',
			'name'        => __( 'Text Only', 'brevo-campaign-generator' ),
			'description' => __( 'Ultra-minimal text-only layout with no product images.', 'brevo-campaign-generator' ),
			'html_file'   => $templates_dir . 'text-only-email-template.html',
			'settings'    => array_merge( $base_settings, array(
				'product_layout' => 'text-only',
			) ),
		);

		// 10. Centered — All content center-aligned with rounded elements.
		$templates['centered'] = array(
			'slug'        => 'centered',
			'name'        => __( 'Centered', 'brevo-campaign-generator' ),
			'description' => __( 'Center-aligned throughout with generous rounded corners.', 'brevo-campaign-generator' ),
			'html_file'   => $templates_dir . 'centered-email-template.html',
			'settings'    => array_merge( $base_settings, array(
				'product_layout' => 'centered',
			) ),
		);

		/**
		 * Filter the registered templates.
		 *
		 * @since 1.1.0
		 *
		 * @param array $templates The registered templates.
		 */
		return apply_filters( 'bcg_registered_templates', $templates );
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 *
	 * @throws \Exception Always.
	 */
	public function __wakeup() {
		throw new \Exception(
			esc_html__( 'Cannot unserialize a singleton.', 'brevo-campaign-generator' )
		);
	}
}
