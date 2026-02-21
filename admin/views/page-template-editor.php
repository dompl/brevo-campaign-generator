<?php
/**
 * Template Editor page view.
 *
 * Two-panel layout: settings/controls (left) and live preview (right).
 * The left panel includes visual settings tabs plus a Code tab for
 * direct HTML editing. All changes trigger a live preview update.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the template engine and registry for defaults.
$template_engine   = new BCG_Template();
$template_registry = BCG_Template_Registry::get_instance();
$all_templates     = $template_registry->get_templates();
$default_settings  = $template_engine->get_default_settings();
$default_html      = $template_engine->get_default_template();

// Check if editing a specific campaign.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading query parameter for display.
$campaign_id = isset( $_GET['campaign_id'] ) ? absint( $_GET['campaign_id'] ) : 0;

$current_html     = $default_html;
$current_settings = $default_settings;
$current_slug     = 'classic';

if ( $campaign_id ) {
	global $wpdb;

	$campaign = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT template_slug, template_html, template_settings FROM {$wpdb->prefix}bcg_campaigns WHERE id = %d",
			$campaign_id
		),
		ARRAY_A
	);

	if ( $campaign ) {
		if ( ! empty( $campaign['template_slug'] ) ) {
			$current_slug = $campaign['template_slug'];
		}

		if ( ! empty( $campaign['template_html'] ) ) {
			$current_html = $campaign['template_html'];
		}

		if ( ! empty( $campaign['template_settings'] ) ) {
			$decoded = json_decode( $campaign['template_settings'], true );
			if ( is_array( $decoded ) ) {
				$current_settings = wp_parse_args( $decoded, $default_settings );
			}
		}
	}
}

// Prepare nav links for repeater.
$nav_links = $current_settings['nav_links'] ?? array();
if ( ! is_array( $nav_links ) || empty( $nav_links ) ) {
	$nav_links = array( array( 'label' => '', 'url' => '' ) );
}

// Prepare footer links for repeater.
$footer_links = $current_settings['footer_links'] ?? array();
if ( ! is_array( $footer_links ) || empty( $footer_links ) ) {
	$footer_links = array( array( 'label' => '', 'url' => '' ) );
}

// Heading font options — includes Google Fonts used by bundled templates.
$heading_fonts = array(
	// ── Google Fonts (loaded by template HTML) ──────────────────────
	"'DM Serif Display', Georgia, 'Times New Roman', serif"  => '✦ DM Serif Display',
	"'Cormorant Garamond', Georgia, serif"                   => '✦ Cormorant Garamond',
	"'Libre Baskerville', Georgia, serif"                    => '✦ Libre Baskerville',
	"Merriweather, Georgia, serif"                           => '✦ Merriweather',
	"Cinzel, Georgia, 'Times New Roman', serif"              => '✦ Cinzel',
	"Oswald, Impact, 'Arial Black', sans-serif"              => '✦ Oswald',
	"'Bebas Neue', Impact, 'Arial Black', sans-serif"        => '✦ Bebas Neue',
	"Nunito, 'Helvetica Neue', Arial, sans-serif"            => '✦ Nunito',
	"'DM Sans', 'Helvetica Neue', Arial, sans-serif"         => '✦ DM Sans',
	// ── Web-safe fallbacks ──────────────────────────────────────────
	"Georgia, 'Times New Roman', serif"                      => 'Georgia',
	"'Times New Roman', Times, serif"                        => 'Times New Roman',
	'Arial, sans-serif'                                      => 'Arial',
	"'Helvetica Neue', Helvetica, sans-serif"                => 'Helvetica Neue',
	'Verdana, Geneva, sans-serif'                            => 'Verdana',
	"'Trebuchet MS', sans-serif"                             => 'Trebuchet MS',
);

// Body font options — web-safe fonts for maximum email client compatibility.
$font_families = array(
	'Arial, sans-serif'                               => 'Arial',
	"'Helvetica Neue', Helvetica, sans-serif"         => 'Helvetica Neue',
	'Georgia, serif'                                  => 'Georgia',
	"'Times New Roman', Times, serif"                 => 'Times New Roman',
	'Verdana, Geneva, sans-serif'                     => 'Verdana',
	'Tahoma, Geneva, sans-serif'                      => 'Tahoma',
	"'Trebuchet MS', sans-serif"                      => 'Trebuchet MS',
	"'Courier New', Courier, monospace"               => 'Courier New',
	"'Segoe UI', Tahoma, Geneva, Verdana, sans-serif" => 'Segoe UI',
	"'DM Sans', 'Helvetica Neue', Arial, sans-serif"  => '✦ DM Sans',
	"Nunito, 'Helvetica Neue', Arial, sans-serif"     => '✦ Nunito',
	"Merriweather, Georgia, serif"                    => '✦ Merriweather',
	"'Cormorant Garamond', Georgia, serif"            => '✦ Cormorant Garamond',
);

$nonce = wp_create_nonce( 'bcg_nonce' );
?>

<?php require BCG_PLUGIN_DIR . 'admin/views/partials/plugin-header.php'; ?>
<div class="wrap bcg-wrap bcg-template-editor-wrap">

	<div class="bcg-template-editor-header">
		<h1>
			<?php esc_html_e( 'Email Template Editor', 'brevo-campaign-generator' ); ?>
			<?php if ( $campaign_id ) : ?>
				<span class="bcg-text-muted bcg-text-small">
					<?php
					printf(
						/* translators: %d: campaign ID */
						esc_html__( '(Campaign #%d)', 'brevo-campaign-generator' ),
						$campaign_id
					);
					?>
				</span>
			<?php endif; ?>
		</h1>
		<div class="bcg-template-editor-actions">
			<button type="button" class="bcg-btn-secondary" id="bcg-reset-template">
				<?php esc_html_e( 'Reset to Default', 'brevo-campaign-generator' ); ?>
			</button>
			<?php if ( $campaign_id ) : ?>
				<button type="button" class="bcg-btn-secondary" id="bcg-save-to-campaign" data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>">
					<?php esc_html_e( 'Save to Campaign', 'brevo-campaign-generator' ); ?>
				</button>
			<?php endif; ?>
			<button type="button" class="bcg-btn-primary" id="bcg-save-default-template">
				<?php esc_html_e( 'Save as Default Template', 'brevo-campaign-generator' ); ?>
			</button>
		</div>
	</div>

	<!-- ============================================================ -->
	<!-- TEMPLATE CHOOSER STRIP                                       -->
	<!-- ============================================================ -->
	<div class="bcg-template-chooser" id="bcg-template-chooser">
		<div class="bcg-template-chooser-scroll">
			<?php
			// Layout diagram map: each layout gets a distinct mini-preview SVG.
			$layout_diagrams = array(
				'side-by-side'  => '<rect x="2" y="2" width="16" height="16" rx="1" fill="#ccc"/><rect x="20" y="2" width="24" height="4" rx="1" fill="#999"/><rect x="20" y="8" width="20" height="3" rx="1" fill="#bbb"/><rect x="20" y="13" width="14" height="5" rx="1" fill="%s"/>',
				'stacked'       => '<rect x="4" y="1" width="38" height="10" rx="1" fill="#ccc"/><rect x="4" y="13" width="30" height="3" rx="1" fill="#999"/><rect x="4" y="18" width="20" height="5" rx="1" fill="%s"/>',
				'reversed'      => '<rect x="2" y="2" width="24" height="4" rx="1" fill="#999"/><rect x="2" y="8" width="20" height="3" rx="1" fill="#bbb"/><rect x="2" y="13" width="14" height="5" rx="1" fill="%s"/><rect x="28" y="2" width="16" height="16" rx="1" fill="#ccc"/>',
				'alternating'   => '<rect x="2" y="1" width="12" height="8" rx="1" fill="#ccc"/><rect x="16" y="1" width="28" height="3" rx="1" fill="#999"/><rect x="16" y="5" width="22" height="2" rx="1" fill="#bbb"/><rect x="6" y="12" width="28" height="3" rx="1" fill="#999"/><rect x="6" y="16" width="22" height="2" rx="1" fill="#bbb"/><rect x="32" y="11" width="12" height="8" rx="1" fill="#ccc"/>',
				'grid'          => '<rect x="2" y="2" width="20" height="10" rx="1" fill="#ccc"/><rect x="24" y="2" width="20" height="10" rx="1" fill="#ccc"/><rect x="2" y="14" width="16" height="3" rx="1" fill="#999"/><rect x="24" y="14" width="16" height="3" rx="1" fill="#999"/><rect x="2" y="19" width="12" height="3" rx="1" fill="%s"/><rect x="24" y="19" width="12" height="3" rx="1" fill="%s"/>',
				'compact'       => '<rect x="2" y="2" width="8" height="8" rx="1" fill="#ccc"/><rect x="12" y="2" width="26" height="3" rx="1" fill="#999"/><rect x="12" y="6" width="20" height="2" rx="1" fill="#bbb"/><rect x="2" y="13" width="8" height="8" rx="1" fill="#ccc"/><rect x="12" y="13" width="26" height="3" rx="1" fill="#999"/><rect x="12" y="17" width="20" height="2" rx="1" fill="#bbb"/>',
				'full-card'     => '<rect x="4" y="1" width="38" height="1" rx="0" fill="#ddd"/><rect x="4" y="2" width="38" height="8" rx="0" fill="#ccc"/><rect x="4" y="10" width="38" height="1" rx="0" fill="#ddd"/><rect x="6" y="12" width="28" height="3" rx="1" fill="#999"/><rect x="6" y="16" width="20" height="2" rx="1" fill="#bbb"/><rect x="4" y="20" width="38" height="1" rx="0" fill="#ddd"/>',
				'text-only'     => '<rect x="4" y="2" width="30" height="4" rx="1" fill="#999"/><rect x="4" y="8" width="38" height="2" rx="1" fill="#bbb"/><line x1="4" y1="12" x2="42" y2="12" stroke="#ddd" stroke-width="1"/><rect x="4" y="14" width="28" height="4" rx="1" fill="#999"/><rect x="4" y="20" width="38" height="2" rx="1" fill="#bbb"/>',
				'centered'      => '<rect x="10" y="2" width="26" height="8" rx="2" fill="#ccc"/><rect x="8" y="12" width="30" height="3" rx="1" fill="#999"/><rect x="12" y="17" width="22" height="5" rx="2" fill="%s"/>',
				'feature-first' => '<rect x="2" y="1" width="42" height="10" rx="1" fill="#ccc"/><rect x="2" y="13" width="30" height="3" rx="1" fill="#999"/><rect x="2" y="18" width="6" height="6" rx="1" fill="#ccc"/><rect x="10" y="18" width="20" height="3" rx="1" fill="#bbb"/>',
			);

			foreach ( $all_templates as $tpl_slug => $tpl ) :
				$is_active       = ( $tpl_slug === $current_slug );
				$tpl_settings    = $tpl['settings'] ?? array();
				$primary         = esc_attr( $tpl_settings['primary_color'] ?? '#e84040' );
				$product_layout  = $tpl_settings['product_layout'] ?? 'stacked';
				$diagram_svg     = $layout_diagrams[ $product_layout ] ?? $layout_diagrams['stacked'];
				$diagram_svg     = sprintf( $diagram_svg, $primary, $primary );
			?>
				<button
					type="button"
					class="bcg-template-card<?php echo $is_active ? ' bcg-template-card-active' : ''; ?>"
					data-slug="<?php echo esc_attr( $tpl_slug ); ?>"
					title="<?php echo esc_attr( $tpl['description'] ?? '' ); ?>"
				>
					<div class="bcg-template-card-preview">
						<div class="bcg-template-card-swatch" style="background-color:#f5f5f5;">
							<div class="bcg-template-card-swatch-inner" style="background-color:#ffffff;border-top:3px solid <?php echo $primary; ?>;">
								<svg viewBox="0 0 46 24" xmlns="http://www.w3.org/2000/svg" style="width:100%;height:auto;display:block;">
									<?php echo $diagram_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG built from safe esc_attr values. ?>
								</svg>
							</div>
						</div>
					</div>
					<span class="bcg-template-card-name"><?php echo esc_html( $tpl['name'] ); ?></span>
					<?php if ( $is_active ) : ?>
						<span class="bcg-template-card-badge"><?php esc_html_e( 'Current', 'brevo-campaign-generator' ); ?></span>
					<?php endif; ?>
				</button>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="bcg-template-editor-layout">

		<!-- ============================================================ -->
		<!-- LEFT PANEL — Settings + Code Editor Tabs                      -->
		<!-- ============================================================ -->
		<div class="bcg-template-panel bcg-template-panel-controls" id="bcg-controls-panel">

			<!-- Settings section dropdown nav -->
			<div class="bcg-tab-nav-dropdown" id="bcg-tab-nav-dropdown">
				<button
					type="button"
					id="bcg-tab-nav-btn"
					class="bcg-tab-nav-trigger"
					aria-expanded="false"
					aria-haspopup="true"
					aria-controls="bcg-tab-nav-menu"
				>
					<span class="bcg-tab-nav-label" id="bcg-tab-nav-label"><?php esc_html_e( 'Branding', 'brevo-campaign-generator' ); ?></span>
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="bcg-tab-nav-chevron" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
				</button>
				<div id="bcg-tab-nav-menu" class="bcg-tab-nav-menu bcg-dropdown-closed" role="menu" aria-hidden="true">
					<button type="button" class="bcg-template-tab bcg-tab-nav-item active" data-tab="branding" role="menuitem">
						<span class="material-icons-outlined" aria-hidden="true">palette</span>
						<?php esc_html_e( 'Branding', 'brevo-campaign-generator' ); ?>
					</button>
					<button type="button" class="bcg-template-tab bcg-tab-nav-item" data-tab="layout" role="menuitem">
						<span class="material-icons-outlined" aria-hidden="true">dashboard</span>
						<?php esc_html_e( 'Layout', 'brevo-campaign-generator' ); ?>
					</button>
					<button type="button" class="bcg-template-tab bcg-tab-nav-item" data-tab="colours" role="menuitem">
						<span class="material-icons-outlined" aria-hidden="true">color_lens</span>
						<?php esc_html_e( 'Colours', 'brevo-campaign-generator' ); ?>
					</button>
					<button type="button" class="bcg-template-tab bcg-tab-nav-item" data-tab="button" role="menuitem">
						<span class="material-icons-outlined" aria-hidden="true">smart_button</span>
						<?php esc_html_e( 'Button', 'brevo-campaign-generator' ); ?>
					</button>
					<button type="button" class="bcg-template-tab bcg-tab-nav-item" data-tab="typography" role="menuitem">
						<span class="material-icons-outlined" aria-hidden="true">text_fields</span>
						<?php esc_html_e( 'Typography', 'brevo-campaign-generator' ); ?>
					</button>
					<button type="button" class="bcg-template-tab bcg-tab-nav-item" data-tab="navigation" role="menuitem">
						<span class="material-icons-outlined" aria-hidden="true">link</span>
						<?php esc_html_e( 'Navigation', 'brevo-campaign-generator' ); ?>
					</button>
					<button type="button" class="bcg-template-tab bcg-tab-nav-item" data-tab="footer" role="menuitem">
						<span class="material-icons-outlined" aria-hidden="true">crop_free</span>
						<?php esc_html_e( 'Footer', 'brevo-campaign-generator' ); ?>
					</button>
					<button type="button" class="bcg-template-tab bcg-tab-nav-item bcg-tab-nav-item-code" data-tab="code" role="menuitem">
						<span class="material-icons-outlined" aria-hidden="true">code</span>
						<?php esc_html_e( 'HTML', 'brevo-campaign-generator' ); ?>
					</button>
				</div>
			</div>

			<div class="bcg-template-settings-panels">

				<!-- Branding Tab -->
				<div class="bcg-template-settings-panel active" data-panel="branding">
					<h3><?php esc_html_e( 'Branding', 'brevo-campaign-generator' ); ?></h3>

					<div class="bcg-field-group">
						<label for="bcg-setting-logo_url"><?php esc_html_e( 'Logo URL', 'brevo-campaign-generator' ); ?></label>
						<div class="bcg-field-with-button">
							<input
								type="url"
								id="bcg-setting-logo_url"
								class="bcg-template-setting widefat"
								data-setting="logo_url"
								value="<?php echo esc_attr( $current_settings['logo_url'] ?? '' ); ?>"
								placeholder="<?php esc_attr_e( 'https://example.com/logo.png', 'brevo-campaign-generator' ); ?>"
							/>
							<button type="button" class="button bcg-upload-media" data-target="bcg-setting-logo_url">
								<?php esc_html_e( 'Upload', 'brevo-campaign-generator' ); ?>
							</button>
						</div>
					</div>

					<div class="bcg-field-group">
						<label for="bcg-setting-logo_width"><?php esc_html_e( 'Logo Width (px)', 'brevo-campaign-generator' ); ?></label>
						<input
							type="number"
							id="bcg-setting-logo_width"
							class="bcg-template-setting small-text"
							data-setting="logo_width"
							value="<?php echo esc_attr( absint( $current_settings['logo_width'] ?? 180 ) ); ?>"
							min="40"
							max="600"
							step="1"
						/>
					</div>

					<div class="bcg-field-group">
						<label for="bcg-setting-header_text"><?php esc_html_e( 'Header Text', 'brevo-campaign-generator' ); ?></label>
						<input
							type="text"
							id="bcg-setting-header_text"
							class="bcg-template-setting widefat"
							data-setting="header_text"
							value="<?php echo esc_attr( $current_settings['header_text'] ?? '' ); ?>"
							placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
						/>
						<p class="description"><?php esc_html_e( 'Displayed when no logo is set.', 'brevo-campaign-generator' ); ?></p>
					</div>
				</div>

				<!-- Layout Tab -->
				<div class="bcg-template-settings-panel" data-panel="layout">
					<h3><?php esc_html_e( 'Layout', 'brevo-campaign-generator' ); ?></h3>

					<div class="bcg-field-group">
						<label for="bcg-setting-max_width"><?php esc_html_e( 'Max Width (px)', 'brevo-campaign-generator' ); ?></label>
						<input
							type="number"
							id="bcg-setting-max_width"
							class="bcg-template-setting small-text"
							data-setting="max_width"
							value="<?php echo esc_attr( absint( $current_settings['max_width'] ?? 600 ) ); ?>"
							min="400"
							max="800"
							step="10"
						/>
					</div>

					<div class="bcg-field-group">
						<label for="bcg-setting-product_layout"><?php esc_html_e( 'Product Layout', 'brevo-campaign-generator' ); ?></label>
						<select id="bcg-setting-product_layout" class="bcg-template-setting widefat" data-setting="product_layout">
							<option value="stacked" <?php selected( $current_settings['product_layout'] ?? 'stacked', 'stacked' ); ?>>
								<?php esc_html_e( 'Stacked (image on top)', 'brevo-campaign-generator' ); ?>
							</option>
							<option value="side-by-side" <?php selected( $current_settings['product_layout'] ?? 'stacked', 'side-by-side' ); ?>>
								<?php esc_html_e( 'Side by Side (image left)', 'brevo-campaign-generator' ); ?>
							</option>
							<option value="reversed" <?php selected( $current_settings['product_layout'] ?? 'stacked', 'reversed' ); ?>>
								<?php esc_html_e( 'Reversed (image right)', 'brevo-campaign-generator' ); ?>
							</option>
							<option value="alternating" <?php selected( $current_settings['product_layout'] ?? 'stacked', 'alternating' ); ?>>
								<?php esc_html_e( 'Alternating (zigzag)', 'brevo-campaign-generator' ); ?>
							</option>
							<option value="grid" <?php selected( $current_settings['product_layout'] ?? 'stacked', 'grid' ); ?>>
								<?php esc_html_e( 'Grid', 'brevo-campaign-generator' ); ?>
							</option>
							<option value="compact" <?php selected( $current_settings['product_layout'] ?? 'stacked', 'compact' ); ?>>
								<?php esc_html_e( 'Compact (small thumbnails)', 'brevo-campaign-generator' ); ?>
							</option>
							<option value="full-card" <?php selected( $current_settings['product_layout'] ?? 'stacked', 'full-card' ); ?>>
								<?php esc_html_e( 'Cards (bordered)', 'brevo-campaign-generator' ); ?>
							</option>
							<option value="text-only" <?php selected( $current_settings['product_layout'] ?? 'stacked', 'text-only' ); ?>>
								<?php esc_html_e( 'Text Only (no images)', 'brevo-campaign-generator' ); ?>
							</option>
							<option value="centered" <?php selected( $current_settings['product_layout'] ?? 'stacked', 'centered' ); ?>>
								<?php esc_html_e( 'Centered', 'brevo-campaign-generator' ); ?>
							</option>
							<option value="feature-first" <?php selected( $current_settings['product_layout'] ?? 'stacked', 'feature-first' ); ?>>
								<?php esc_html_e( 'Feature (first product large)', 'brevo-campaign-generator' ); ?>
							</option>
						</select>
					</div>

					<div class="bcg-field-group">
						<label for="bcg-setting-products_per_row"><?php esc_html_e( 'Products Per Row', 'brevo-campaign-generator' ); ?></label>
						<select id="bcg-setting-products_per_row" class="bcg-template-setting widefat" data-setting="products_per_row">
							<option value="1" <?php selected( $current_settings['products_per_row'] ?? 1, 1 ); ?>>1</option>
							<option value="2" <?php selected( $current_settings['products_per_row'] ?? 1, 2 ); ?>>2</option>
							<option value="3" <?php selected( $current_settings['products_per_row'] ?? 1, 3 ); ?>>3</option>
						</select>
					</div>

					<div class="bcg-field-group">
						<label for="bcg-setting-product_gap"><?php esc_html_e( 'Product Spacing (px)', 'brevo-campaign-generator' ); ?></label>
						<div class="bcg-range-field">
							<input
								type="range"
								id="bcg-setting-product_gap"
								class="bcg-template-setting"
								data-setting="product_gap"
								value="<?php echo esc_attr( absint( $current_settings['product_gap'] ?? 24 ) ); ?>"
								min="0"
								max="48"
								step="4"
							/>
							<span class="bcg-range-value"><?php echo esc_html( absint( $current_settings['product_gap'] ?? 24 ) ); ?>px</span>
						</div>
					</div>

					<div class="bcg-field-group">
						<label>
							<input
								type="checkbox"
								id="bcg-setting-show_coupon_block"
								class="bcg-template-setting"
								data-setting="show_coupon_block"
								<?php checked( ! empty( $current_settings['show_coupon_block'] ) ); ?>
							/>
							<?php esc_html_e( 'Show Coupon Block', 'brevo-campaign-generator' ); ?>
						</label>
					</div>
				</div>

				<!-- Colours Tab -->
				<div class="bcg-template-settings-panel" data-panel="colours">
					<h3><?php esc_html_e( 'Colours', 'brevo-campaign-generator' ); ?></h3>

					<div class="bcg-field-group">
						<label for="bcg-setting-background_color"><?php esc_html_e( 'Page Background', 'brevo-campaign-generator' ); ?></label>
						<input
							type="color"
							id="bcg-setting-background_color"
							class="bcg-template-setting bcg-color-input"
							data-setting="background_color"
							value="<?php echo esc_attr( $current_settings['background_color'] ?? '#f5f5f5' ); ?>"
						/>
						<span class="bcg-color-value"><?php echo esc_html( $current_settings['background_color'] ?? '#f5f5f5' ); ?></span>
					</div>

					<div class="bcg-field-group">
						<label for="bcg-setting-content_background"><?php esc_html_e( 'Content Background', 'brevo-campaign-generator' ); ?></label>
						<input
							type="color"
							id="bcg-setting-content_background"
							class="bcg-template-setting bcg-color-input"
							data-setting="content_background"
							value="<?php echo esc_attr( $current_settings['content_background'] ?? '#ffffff' ); ?>"
						/>
						<span class="bcg-color-value"><?php echo esc_html( $current_settings['content_background'] ?? '#ffffff' ); ?></span>
					</div>

					<div class="bcg-field-group">
						<label for="bcg-setting-primary_color"><?php esc_html_e( 'Primary Colour', 'brevo-campaign-generator' ); ?></label>
						<input
							type="color"
							id="bcg-setting-primary_color"
							class="bcg-template-setting bcg-color-input"
							data-setting="primary_color"
							value="<?php echo esc_attr( $current_settings['primary_color'] ?? '#e84040' ); ?>"
						/>
						<span class="bcg-color-value"><?php echo esc_html( $current_settings['primary_color'] ?? '#e84040' ); ?></span>
					</div>

					<div class="bcg-field-group">
						<label for="bcg-setting-text_color"><?php esc_html_e( 'Text Colour', 'brevo-campaign-generator' ); ?></label>
						<input
							type="color"
							id="bcg-setting-text_color"
							class="bcg-template-setting bcg-color-input"
							data-setting="text_color"
							value="<?php echo esc_attr( $current_settings['text_color'] ?? '#333333' ); ?>"
						/>
						<span class="bcg-color-value"><?php echo esc_html( $current_settings['text_color'] ?? '#333333' ); ?></span>
					</div>

					<div class="bcg-field-group">
						<label for="bcg-setting-link_color"><?php esc_html_e( 'Link Colour', 'brevo-campaign-generator' ); ?></label>
						<input
							type="color"
							id="bcg-setting-link_color"
							class="bcg-template-setting bcg-color-input"
							data-setting="link_color"
							value="<?php echo esc_attr( $current_settings['link_color'] ?? '#e84040' ); ?>"
						/>
						<span class="bcg-color-value"><?php echo esc_html( $current_settings['link_color'] ?? '#e84040' ); ?></span>
					</div>
				</div>

				<!-- Button Tab -->
				<div class="bcg-template-settings-panel" data-panel="button">
					<h3><?php esc_html_e( 'Button', 'brevo-campaign-generator' ); ?></h3>

					<div class="bcg-field-group">
						<label for="bcg-setting-button_color"><?php esc_html_e( 'Button Background', 'brevo-campaign-generator' ); ?></label>
						<input
							type="color"
							id="bcg-setting-button_color"
							class="bcg-template-setting bcg-color-input"
							data-setting="button_color"
							value="<?php echo esc_attr( $current_settings['button_color'] ?? '#e84040' ); ?>"
						/>
						<span class="bcg-color-value"><?php echo esc_html( $current_settings['button_color'] ?? '#e84040' ); ?></span>
					</div>

					<div class="bcg-field-group">
						<label for="bcg-setting-button_text_color"><?php esc_html_e( 'Button Text Colour', 'brevo-campaign-generator' ); ?></label>
						<input
							type="color"
							id="bcg-setting-button_text_color"
							class="bcg-template-setting bcg-color-input"
							data-setting="button_text_color"
							value="<?php echo esc_attr( $current_settings['button_text_color'] ?? '#ffffff' ); ?>"
						/>
						<span class="bcg-color-value"><?php echo esc_html( $current_settings['button_text_color'] ?? '#ffffff' ); ?></span>
					</div>

					<div class="bcg-field-group">
						<label for="bcg-setting-button_border_radius"><?php esc_html_e( 'Border Radius (px)', 'brevo-campaign-generator' ); ?></label>
						<input
							type="number"
							id="bcg-setting-button_border_radius"
							class="bcg-template-setting small-text"
							data-setting="button_border_radius"
							value="<?php echo esc_attr( absint( $current_settings['button_border_radius'] ?? 4 ) ); ?>"
							min="0"
							max="50"
							step="1"
						/>
					</div>

					<div class="bcg-field-group">
						<label for="bcg-setting-product_button_size"><?php esc_html_e( 'Product Button Size', 'brevo-campaign-generator' ); ?></label>
						<select id="bcg-setting-product_button_size" class="bcg-template-setting widefat" data-setting="product_button_size">
							<option value="small" <?php selected( $current_settings['product_button_size'] ?? 'medium', 'small' ); ?>>
								<?php esc_html_e( 'Small', 'brevo-campaign-generator' ); ?>
							</option>
							<option value="medium" <?php selected( $current_settings['product_button_size'] ?? 'medium', 'medium' ); ?>>
								<?php esc_html_e( 'Medium (Default)', 'brevo-campaign-generator' ); ?>
							</option>
							<option value="large" <?php selected( $current_settings['product_button_size'] ?? 'medium', 'large' ); ?>>
								<?php esc_html_e( 'Large', 'brevo-campaign-generator' ); ?>
							</option>
						</select>
					</div>
				</div>

				<!-- Typography Tab -->
				<div class="bcg-template-settings-panel" data-panel="typography">
					<h3><?php esc_html_e( 'Typography', 'brevo-campaign-generator' ); ?></h3>

					<div class="bcg-field-group">
						<label for="bcg-setting-heading_font_family"><?php esc_html_e( 'Heading Font', 'brevo-campaign-generator' ); ?></label>
						<select id="bcg-setting-heading_font_family" class="bcg-template-setting widefat" data-setting="heading_font_family">
							<?php foreach ( $heading_fonts as $font_value => $font_label ) : ?>
								<option value="<?php echo esc_attr( $font_value ); ?>" <?php selected( $current_settings['heading_font_family'] ?? "Georgia, 'Times New Roman', serif", $font_value ); ?>>
									<?php echo esc_html( $font_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description bcg-mt-4">
							<?php esc_html_e( 'Used for the main campaign headline. ✦ marks fonts loaded by the template.', 'brevo-campaign-generator' ); ?>
						</p>
					</div>

					<div class="bcg-field-group">
						<label for="bcg-setting-font_family"><?php esc_html_e( 'Body Font', 'brevo-campaign-generator' ); ?></label>
						<select id="bcg-setting-font_family" class="bcg-template-setting widefat" data-setting="font_family">
							<?php foreach ( $font_families as $font_value => $font_label ) : ?>
								<option value="<?php echo esc_attr( $font_value ); ?>" <?php selected( $current_settings['font_family'] ?? 'Arial, sans-serif', $font_value ); ?>>
									<?php echo esc_html( $font_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description bcg-mt-4">
							<?php esc_html_e( 'Used for descriptions, nav links, and body text.', 'brevo-campaign-generator' ); ?>
						</p>
					</div>
				</div>

				<!-- Navigation Tab -->
				<div class="bcg-template-settings-panel" data-panel="navigation">
					<h3><?php esc_html_e( 'Navigation', 'brevo-campaign-generator' ); ?></h3>

					<div class="bcg-field-group">
						<label>
							<input
								type="checkbox"
								id="bcg-setting-show_nav"
								class="bcg-template-setting"
								data-setting="show_nav"
								<?php checked( ! empty( $current_settings['show_nav'] ) ); ?>
							/>
							<?php esc_html_e( 'Show Navigation Bar', 'brevo-campaign-generator' ); ?>
						</label>
					</div>

					<div class="bcg-field-group" id="bcg-nav-links-section">
						<label><?php esc_html_e( 'Navigation Links', 'brevo-campaign-generator' ); ?></label>
						<div id="bcg-nav-links-repeater" class="bcg-repeater">
							<?php foreach ( $nav_links as $index => $link ) : ?>
								<div class="bcg-repeater-row" data-index="<?php echo esc_attr( $index ); ?>">
									<input
										type="text"
										class="bcg-nav-link-label"
										value="<?php echo esc_attr( $link['label'] ?? '' ); ?>"
										placeholder="<?php esc_attr_e( 'Label', 'brevo-campaign-generator' ); ?>"
									/>
									<input
										type="url"
										class="bcg-nav-link-url"
										value="<?php echo esc_attr( $link['url'] ?? '' ); ?>"
										placeholder="<?php esc_attr_e( 'https://...', 'brevo-campaign-generator' ); ?>"
									/>
									<button type="button" class="button bcg-repeater-remove" title="<?php esc_attr_e( 'Remove', 'brevo-campaign-generator' ); ?>">&times;</button>
								</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="button bcg-repeater-add" data-repeater="nav-links">
							<?php esc_html_e( '+ Add Link', 'brevo-campaign-generator' ); ?>
						</button>
					</div>
				</div>

				<!-- Footer Tab -->
				<div class="bcg-template-settings-panel" data-panel="footer">
					<h3><?php esc_html_e( 'Footer', 'brevo-campaign-generator' ); ?></h3>

					<div class="bcg-field-group">
						<label for="bcg-setting-footer_text"><?php esc_html_e( 'Footer Text', 'brevo-campaign-generator' ); ?></label>
						<textarea
							id="bcg-setting-footer_text"
							class="bcg-template-setting widefat"
							data-setting="footer_text"
							rows="3"
						><?php echo esc_textarea( $current_settings['footer_text'] ?? '' ); ?></textarea>
					</div>

					<div class="bcg-field-group">
						<label><?php esc_html_e( 'Footer Links', 'brevo-campaign-generator' ); ?></label>
						<div id="bcg-footer-links-repeater" class="bcg-repeater">
							<?php foreach ( $footer_links as $index => $link ) : ?>
								<div class="bcg-repeater-row" data-index="<?php echo esc_attr( $index ); ?>">
									<input
										type="text"
										class="bcg-footer-link-label"
										value="<?php echo esc_attr( $link['label'] ?? '' ); ?>"
										placeholder="<?php esc_attr_e( 'Label', 'brevo-campaign-generator' ); ?>"
									/>
									<input
										type="text"
										class="bcg-footer-link-url"
										value="<?php echo esc_attr( $link['url'] ?? '' ); ?>"
										placeholder="<?php esc_attr_e( 'https://... or {{unsubscribe_url}}', 'brevo-campaign-generator' ); ?>"
									/>
									<button type="button" class="button bcg-repeater-remove" title="<?php esc_attr_e( 'Remove', 'brevo-campaign-generator' ); ?>">&times;</button>
								</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="button bcg-repeater-add" data-repeater="footer-links">
							<?php esc_html_e( '+ Add Link', 'brevo-campaign-generator' ); ?>
						</button>
					</div>
				</div>

				<!-- Code Editor Tab -->
				<div class="bcg-template-settings-panel bcg-template-code-panel" data-panel="code">
					<div class="bcg-code-panel-header">
						<h3><?php esc_html_e( 'HTML Code', 'brevo-campaign-generator' ); ?></h3>
						<button type="button" class="button bcg-token-reference-toggle" id="bcg-toggle-tokens">
							<?php esc_html_e( 'Token Reference', 'brevo-campaign-generator' ); ?>
						</button>
					</div>

					<!-- Token Reference Cheat Sheet -->
					<div class="bcg-token-reference" id="bcg-token-reference" style="display:none;">
						<h4><?php esc_html_e( 'Available Template Tokens', 'brevo-campaign-generator' ); ?></h4>
						<div class="bcg-token-list">
							<div class="bcg-token-group">
								<h5><?php esc_html_e( 'Campaign Data', 'brevo-campaign-generator' ); ?></h5>
								<code>{{campaign_headline}}</code>
								<code>{{campaign_description}}</code>
								<code>{{campaign_image}}</code>
								<code>{{subject}}</code>
								<code>{{preview_text}}</code>
								<code>{{coupon_code}}</code>
								<code>{{coupon_text}}</code>
								<code>{{products_block}}</code>
							</div>
							<div class="bcg-token-group">
								<h5><?php esc_html_e( 'Store / Global', 'brevo-campaign-generator' ); ?></h5>
								<code>{{store_name}}</code>
								<code>{{store_url}}</code>
								<code>{{logo_url}}</code>
								<code>{{unsubscribe_url}}</code>
								<code>{{current_year}}</code>
							</div>
							<div class="bcg-token-group">
								<h5><?php esc_html_e( 'Settings Tokens', 'brevo-campaign-generator' ); ?></h5>
								<code>{{setting_primary_color}}</code>
								<code>{{setting_background_color}}</code>
								<code>{{setting_content_background}}</code>
								<code>{{setting_text_color}}</code>
								<code>{{setting_link_color}}</code>
								<code>{{setting_button_color}}</code>
								<code>{{setting_button_text_color}}</code>
								<code>{{setting_button_border_radius}}</code>
								<code>{{setting_font_family}}</code>
								<code>{{setting_max_width}}</code>
								<code>{{setting_logo_url}}</code>
								<code>{{setting_logo_width}}</code>
								<code>{{setting_header_text}}</code>
								<code>{{setting_footer_text}}</code>
							</div>
							<div class="bcg-token-group">
								<h5><?php esc_html_e( 'Conditionals', 'brevo-campaign-generator' ); ?></h5>
								<code>{{#if show_nav}}...{{/if}}</code>
								<code>{{#if logo_url}}...{{/if}}</code>
								<code>{{#if show_coupon_block}}...{{/if}}</code>
								<code>{{#if campaign_image}}...{{/if}}</code>
								<code>{{navigation_links}}</code>
								<code>{{footer_links}}</code>
							</div>
						</div>
					</div>

					<div class="bcg-editor-code-wrapper" id="bcg-code-editor-wrapper">
						<textarea id="bcg-template-html" name="template_html"><?php echo esc_textarea( $current_html ); ?></textarea>
					</div>
				</div>

			</div><!-- .bcg-template-settings-panels -->

		</div><!-- .bcg-template-panel-controls -->

		<!-- ============================================================ -->
		<!-- RIGHT PANEL — Live Preview                                    -->
		<!-- ============================================================ -->
		<div class="bcg-template-panel bcg-template-panel-preview" id="bcg-preview-panel">

			<div class="bcg-preview-toolbar">
				<span class="bcg-preview-label"><?php esc_html_e( 'Live Preview', 'brevo-campaign-generator' ); ?></span>
				<div class="bcg-preview-device-toggle">
					<button type="button" class="bcg-preview-device active" data-device="desktop" title="<?php esc_attr_e( 'Desktop Preview', 'brevo-campaign-generator' ); ?>">
						<span class="material-icons-outlined" style="font-size:14px;">desktop_windows</span>
						<?php esc_html_e( 'Desktop', 'brevo-campaign-generator' ); ?>
					</button>
					<button type="button" class="bcg-preview-device" data-device="mobile" title="<?php esc_attr_e( 'Mobile Preview', 'brevo-campaign-generator' ); ?>">
						<span class="material-icons-outlined" style="font-size:14px;">smartphone</span>
						<?php esc_html_e( 'Mobile', 'brevo-campaign-generator' ); ?>
					</button>
				</div>
				<span class="bcg-preview-status" id="bcg-preview-status"></span>
			</div>

			<div class="bcg-preview-frame-wrapper" id="bcg-preview-wrapper">
				<div class="bcg-preview-overlay-container">
					<iframe
						id="bcg-preview-iframe"
						class="bcg-preview-iframe bcg-preview-desktop"
						sandbox="allow-same-origin"
						title="<?php esc_attr_e( 'Email Template Preview', 'brevo-campaign-generator' ); ?>"
					></iframe>
					<div id="bcg-section-overlays"></div>
				</div>
			</div>

		</div><!-- .bcg-template-panel-preview -->

	</div><!-- .bcg-template-editor-layout -->

	<!-- Hidden data for JavaScript -->
	<input type="hidden" id="bcg-template-nonce" value="<?php echo esc_attr( $nonce ); ?>" />
	<input type="hidden" id="bcg-template-ajax-url" value="<?php echo esc_attr( admin_url( 'admin-ajax.php' ) ); ?>" />
	<input type="hidden" id="bcg-template-campaign-id" value="<?php echo esc_attr( $campaign_id ); ?>" />
	<input type="hidden" id="bcg-template-settings-json" value="<?php echo esc_attr( wp_json_encode( $current_settings ) ); ?>" />
	<input type="hidden" id="bcg-template-current-slug" value="<?php echo esc_attr( $current_slug ); ?>" />

</div>
