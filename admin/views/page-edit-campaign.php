<?php
/**
 * Campaign editor page — Step 2.
 *
 * Two-column layout: left panel contains all editable campaign fields (header,
 * coupon, product repeater), right panel hosts a live-updating iframe preview
 * of the rendered email. A sticky actions bar at the bottom provides save,
 * preview, test, Brevo push, scheduling, and send controls.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Validate the campaign ID from the URL.
$campaign_id = isset( $_GET['campaign_id'] ) ? absint( $_GET['campaign_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

if ( ! $campaign_id ) {
	echo '<div class="wrap"><div class="notice notice-error"><p>';
	esc_html_e( 'Invalid campaign ID. Please return to the dashboard and select a campaign.', 'brevo-campaign-generator' );
	echo '</p></div></div>';
	return;
}

// Load the campaign with products.
$campaign_handler = new BCG_Campaign();
$campaign         = $campaign_handler->get( $campaign_id );

if ( is_wp_error( $campaign ) ) {
	echo '<div class="wrap"><div class="notice notice-error"><p>';
	echo esc_html( $campaign->get_error_message() );
	echo '</p></div></div>';
	return;
}

// Determine coupon-related flags.
$has_coupon       = ! empty( $campaign->coupon_code );
$coupon_code      = $campaign->coupon_code ?? '';
$coupon_discount  = $campaign->coupon_discount ?? 0;
$coupon_type      = $campaign->coupon_type ?? 'percent';

// Build a coupon display text.
$coupon_text = '';
if ( $has_coupon && $coupon_discount > 0 ) {
	if ( 'percent' === $coupon_type ) {
		$coupon_text = sprintf(
			/* translators: 1: coupon code, 2: discount percentage */
			__( 'Use code %1$s for %2$s%% off your order!', 'brevo-campaign-generator' ),
			$coupon_code,
			number_format( (float) $coupon_discount, 0 )
		);
	} else {
		$coupon_text = sprintf(
			/* translators: 1: coupon code, 2: formatted amount */
			__( 'Use code %1$s for %2$s off your order!', 'brevo-campaign-generator' ),
			$coupon_code,
			function_exists( 'wc_price' ) ? wc_price( (float) $coupon_discount ) : number_format( (float) $coupon_discount, 2 )
		);
	}
}

// Status label.
$status_labels = array(
	'draft'     => __( 'Draft', 'brevo-campaign-generator' ),
	'ready'     => __( 'Ready', 'brevo-campaign-generator' ),
	'sent'      => __( 'Sent', 'brevo-campaign-generator' ),
	'scheduled' => __( 'Scheduled', 'brevo-campaign-generator' ),
);
$status_label = $status_labels[ $campaign->status ] ?? ucfirst( $campaign->status );

// Dashboard URL.
$dashboard_url = admin_url( 'admin.php?page=bcg-dashboard' );

// Nonce for all AJAX operations.
$nonce = wp_create_nonce( 'bcg_nonce' );
?>
<div class="wrap bcg-wrap bcg-editor-wrap">
<?php require BCG_PLUGIN_DIR . 'admin/views/partials/plugin-header.php'; ?>

	<!-- Page header -->
	<div class="bcg-editor-page-header bcg-flex bcg-items-center bcg-justify-between bcg-mb-16">
		<div class="bcg-flex bcg-items-center bcg-gap-12">
			<h1 class="bcg-mt-0 bcg-mb-0">
				<?php
				printf(
					/* translators: %s: campaign title */
					esc_html__( 'Edit Campaign: %s', 'brevo-campaign-generator' ),
					esc_html( $campaign->title )
				);
				?>
			</h1>
			<span class="bcg-status-badge bcg-status-<?php echo esc_attr( $campaign->status ); ?>">
				<?php echo esc_html( $status_label ); ?>
			</span>
		</div>
		<a href="<?php echo esc_url( $dashboard_url ); ?>" class="button">
			<?php esc_html_e( 'Back to Dashboard', 'brevo-campaign-generator' ); ?>
		</a>
	</div>

	<!-- Admin notices area -->
	<div id="bcg-editor-notices"></div>

	<!-- Two-column editor layout -->
	<div class="bcg-editor-columns">

		<!-- ============================================================
			 LEFT COLUMN — Editor Fields
			 ============================================================ -->
		<div class="bcg-editor-main">

			<!-- ── HEADER SECTION ───────────────────────────────── -->
			<div class="bcg-card bcg-editor-section" id="bcg-section-header">
				<div class="bcg-card-header">
					<h3><?php esc_html_e( 'Campaign Header', 'brevo-campaign-generator' ); ?></h3>
				</div>
				<div class="bcg-card-body">

					<!-- Subject Line -->
					<div class="bcg-field-group bcg-mb-16">
						<label class="bcg-field-label" for="bcg-subject">
							<?php esc_html_e( 'Subject Line', 'brevo-campaign-generator' ); ?>
						</label>
						<div class="bcg-field-with-regen">
							<input type="text"
								id="bcg-subject"
								class="large-text bcg-campaign-field"
								data-field="subject"
								value="<?php echo esc_attr( $campaign->subject ?? '' ); ?>"
								placeholder="<?php esc_attr_e( 'Enter email subject line...', 'brevo-campaign-generator' ); ?>"
							/>
							<button type="button"
								class="button bcg-regen-field bcg-regen-btn"
								data-field="subject_line"
								title="<?php esc_attr_e( 'Regenerate subject line', 'brevo-campaign-generator' ); ?>">
								<?php esc_html_e( 'Regenerate', 'brevo-campaign-generator' ); ?>
							</button>
						</div>
					</div>

					<!-- Preview Text -->
					<div class="bcg-field-group bcg-mb-16">
						<label class="bcg-field-label" for="bcg-preview-text">
							<?php esc_html_e( 'Preview Text', 'brevo-campaign-generator' ); ?>
						</label>
						<div class="bcg-field-with-regen">
							<input type="text"
								id="bcg-preview-text"
								class="large-text bcg-campaign-field"
								data-field="preview_text"
								value="<?php echo esc_attr( $campaign->preview_text ?? '' ); ?>"
								placeholder="<?php esc_attr_e( 'Email preview text shown in inbox...', 'brevo-campaign-generator' ); ?>"
							/>
							<button type="button"
								class="button bcg-regen-field bcg-regen-btn"
								data-field="preview_text"
								title="<?php esc_attr_e( 'Regenerate preview text', 'brevo-campaign-generator' ); ?>">
								<?php esc_html_e( 'Regenerate', 'brevo-campaign-generator' ); ?>
							</button>
						</div>
					</div>

					<!-- Main Headline -->
					<div class="bcg-field-group bcg-mb-16">
						<label class="bcg-field-label" for="bcg-main-headline">
							<?php esc_html_e( 'Main Headline', 'brevo-campaign-generator' ); ?>
						</label>
						<div class="bcg-field-with-regen">
							<textarea
								id="bcg-main-headline"
								class="large-text bcg-campaign-field"
								data-field="main_headline"
								rows="2"
								placeholder="<?php esc_attr_e( 'Campaign main headline...', 'brevo-campaign-generator' ); ?>"
							><?php echo esc_textarea( $campaign->main_headline ?? '' ); ?></textarea>
							<button type="button"
								class="button bcg-regen-field bcg-regen-btn"
								data-field="main_headline"
								title="<?php esc_attr_e( 'Regenerate headline', 'brevo-campaign-generator' ); ?>">
								<?php esc_html_e( 'Regenerate', 'brevo-campaign-generator' ); ?>
							</button>
						</div>
					</div>

					<!-- Main Image -->
					<div class="bcg-field-group bcg-mb-16">
						<label class="bcg-field-label">
							<?php esc_html_e( 'Main Image', 'brevo-campaign-generator' ); ?>
						</label>
						<div class="bcg-main-image-wrapper">
							<div class="bcg-main-image-preview">
								<?php
								$main_image = ! empty( $campaign->main_image_url )
									? $campaign->main_image_url
									: BCG_PLUGIN_URL . 'assets/images/default-placeholder.png';
								?>
								<img
									id="bcg-main-image-img"
									src="<?php echo esc_url( $main_image ); ?>"
									alt="<?php esc_attr_e( 'Main campaign image', 'brevo-campaign-generator' ); ?>"
									class="bcg-main-image-display"
								/>
							</div>
							<div class="bcg-main-image-actions bcg-flex bcg-gap-8 bcg-mt-8">
								<button type="button"
									class="button bcg-regen-field bcg-regen-btn"
									data-field="main_image"
									title="<?php esc_attr_e( 'Regenerate main image with AI', 'brevo-campaign-generator' ); ?>">
									<?php esc_html_e( 'Regenerate Image', 'brevo-campaign-generator' ); ?>
								</button>
								<button type="button"
									class="button bcg-upload-custom-image"
									id="bcg-upload-main-image"
									title="<?php esc_attr_e( 'Use a custom image from the media library', 'brevo-campaign-generator' ); ?>">
									<?php esc_html_e( 'Use Custom Image', 'brevo-campaign-generator' ); ?>
								</button>
							</div>
							<input type="hidden"
								id="bcg-main-image-url"
								class="bcg-campaign-field"
								data-field="main_image_url"
								value="<?php echo esc_url( $campaign->main_image_url ?? '' ); ?>"
							/>
						</div>
					</div>

					<!-- Main Description -->
					<div class="bcg-field-group">
						<label class="bcg-field-label" for="bcg-main-description">
							<?php esc_html_e( 'Main Description', 'brevo-campaign-generator' ); ?>
						</label>
						<div class="bcg-field-with-regen">
							<textarea
								id="bcg-main-description"
								class="large-text bcg-campaign-field"
								data-field="main_description"
								rows="4"
								placeholder="<?php esc_attr_e( 'Campaign description text...', 'brevo-campaign-generator' ); ?>"
							><?php echo esc_textarea( $campaign->main_description ?? '' ); ?></textarea>
							<button type="button"
								class="button bcg-regen-field bcg-regen-btn"
								data-field="main_description"
								title="<?php esc_attr_e( 'Regenerate description', 'brevo-campaign-generator' ); ?>">
								<?php esc_html_e( 'Regenerate', 'brevo-campaign-generator' ); ?>
							</button>
						</div>
					</div>

				</div><!-- .bcg-card-body -->
			</div><!-- #bcg-section-header -->


			<!-- ── COUPON SECTION ────────────────────────────────── -->
			<div class="bcg-card bcg-editor-section" id="bcg-section-coupon"
				style="<?php echo $has_coupon ? '' : 'display:none;'; ?>">
				<div class="bcg-card-header">
					<h3><?php esc_html_e( 'Coupon', 'brevo-campaign-generator' ); ?></h3>
				</div>
				<div class="bcg-card-body">

					<!-- Coupon Code -->
					<div class="bcg-field-group bcg-mb-16">
						<label class="bcg-field-label" for="bcg-coupon-code">
							<?php esc_html_e( 'Coupon Code', 'brevo-campaign-generator' ); ?>
						</label>
						<div class="bcg-field-with-regen">
							<input type="text"
								id="bcg-coupon-code"
								class="regular-text bcg-campaign-field bcg-coupon-field"
								data-field="coupon_code"
								value="<?php echo esc_attr( $coupon_code ); ?>"
								placeholder="<?php esc_attr_e( 'e.g. SALE-A3K9P2', 'brevo-campaign-generator' ); ?>"
							/>
							<button type="button"
								class="button bcg-regenerate-coupon-code bcg-regen-btn"
								title="<?php esc_attr_e( 'Generate a new coupon code', 'brevo-campaign-generator' ); ?>">
								<?php esc_html_e( 'Regenerate Code', 'brevo-campaign-generator' ); ?>
							</button>
						</div>
					</div>

					<!-- Discount Display Text -->
					<div class="bcg-field-group bcg-mb-16">
						<label class="bcg-field-label" for="bcg-coupon-text">
							<?php esc_html_e( 'Discount Display Text', 'brevo-campaign-generator' ); ?>
						</label>
						<div class="bcg-field-with-regen">
							<input type="text"
								id="bcg-coupon-text"
								class="large-text bcg-coupon-text-field"
								value="<?php echo esc_attr( $coupon_text ); ?>"
								placeholder="<?php esc_attr_e( 'e.g. Get 20% off your order!', 'brevo-campaign-generator' ); ?>"
							/>
							<button type="button"
								class="button bcg-regen-field bcg-regen-btn"
								data-field="coupon_suggestion"
								title="<?php esc_attr_e( 'Regenerate coupon text', 'brevo-campaign-generator' ); ?>">
								<?php esc_html_e( 'Regenerate', 'brevo-campaign-generator' ); ?>
							</button>
						</div>
					</div>

					<!-- Discount Amount + Type -->
					<div class="bcg-flex bcg-gap-16 bcg-mb-16">
						<div class="bcg-field-group" style="flex:1;">
							<label class="bcg-field-label" for="bcg-coupon-discount">
								<?php esc_html_e( 'Discount Value', 'brevo-campaign-generator' ); ?>
							</label>
							<input type="number"
								id="bcg-coupon-discount"
								class="small-text bcg-campaign-field bcg-coupon-field"
								data-field="coupon_discount"
								value="<?php echo esc_attr( $coupon_discount ); ?>"
								min="0"
								step="0.01"
							/>
						</div>
						<div class="bcg-field-group" style="flex:1;">
							<label class="bcg-field-label" for="bcg-coupon-type">
								<?php esc_html_e( 'Discount Type', 'brevo-campaign-generator' ); ?>
							</label>
							<select id="bcg-coupon-type"
								class="bcg-campaign-field bcg-coupon-field"
								data-field="coupon_type">
								<option value="percent" <?php selected( $coupon_type, 'percent' ); ?>>
									<?php esc_html_e( 'Percentage (%)', 'brevo-campaign-generator' ); ?>
								</option>
								<option value="fixed_cart" <?php selected( $coupon_type, 'fixed_cart' ); ?>>
									<?php esc_html_e( 'Fixed amount', 'brevo-campaign-generator' ); ?>
								</option>
							</select>
						</div>
					</div>

					<!-- Expiry Date -->
					<div class="bcg-field-group">
						<label class="bcg-field-label" for="bcg-coupon-expiry">
							<?php esc_html_e( 'Coupon Expiry Date', 'brevo-campaign-generator' ); ?>
						</label>
						<input type="text"
							id="bcg-coupon-expiry"
							class="regular-text bcg-datepicker"
							placeholder="<?php esc_attr_e( 'Select expiry date...', 'brevo-campaign-generator' ); ?>"
							autocomplete="off"
						/>
					</div>

				</div><!-- .bcg-card-body -->
			</div><!-- #bcg-section-coupon -->


			<!-- ── PRODUCTS SECTION ──────────────────────────────── -->
			<div class="bcg-card bcg-editor-section" id="bcg-section-products">
				<div class="bcg-card-header">
					<h3>
						<?php esc_html_e( 'Products', 'brevo-campaign-generator' ); ?>
						<span class="bcg-product-count bcg-text-muted">(<?php echo count( $campaign->products ); ?>)</span>
					</h3>
				</div>
				<div class="bcg-card-body">

					<!-- Sortable products container -->
					<div id="bcg-products-sortable" class="bcg-products-list">
						<?php
						if ( ! empty( $campaign->products ) ) {
							foreach ( $campaign->products as $product ) {
								include BCG_PLUGIN_DIR . 'admin/views/partials/product-card.php';
							}
						} else {
							?>
							<div class="bcg-empty-state" id="bcg-no-products">
								<p class="bcg-text-muted">
									<?php esc_html_e( 'No products in this campaign yet. Use the button below to add products.', 'brevo-campaign-generator' ); ?>
								</p>
							</div>
							<?php
						}
						?>
					</div>

					<!-- Add product button -->
					<div class="bcg-mt-16 bcg-text-center">
						<button type="button" class="button button-secondary" id="bcg-add-product-btn">
							<?php esc_html_e( 'Add Another Product', 'brevo-campaign-generator' ); ?>
						</button>
					</div>

				</div><!-- .bcg-card-body -->
			</div><!-- #bcg-section-products -->

		</div><!-- .bcg-editor-main -->


		<!-- ============================================================
			 RIGHT COLUMN — Live Preview
			 ============================================================ -->
		<div class="bcg-editor-preview">

			<!-- Template Picker Strip -->
			<?php
			$template_registry = BCG_Template_Registry::get_instance();
			$all_templates     = $template_registry->get_templates();
			$current_slug      = $campaign->template_slug ?? 'classic';

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
			?>
			<div class="bcg-editor-template-strip" id="bcg-editor-template-strip">
				<div class="bcg-template-strip-header bcg-flex bcg-items-center bcg-justify-between bcg-mb-8">
					<span class="bcg-template-strip-label"><?php esc_html_e( 'Template:', 'brevo-campaign-generator' ); ?></span>
				</div>
				<div class="bcg-template-strip-cards">
					<?php foreach ( $all_templates as $tpl_slug => $tpl ) :
						$is_active       = ( $tpl_slug === $current_slug );
						$tpl_settings    = $tpl['settings'] ?? array();
						$primary         = esc_attr( $tpl_settings['primary_color'] ?? '#e84040' );
						$product_layout  = $tpl_settings['product_layout'] ?? 'stacked';
						$diagram_svg     = $layout_diagrams[ $product_layout ] ?? $layout_diagrams['stacked'];
						$diagram_svg     = sprintf( $diagram_svg, $primary, $primary );
					?>
						<button
							type="button"
							class="bcg-template-mini-card<?php echo $is_active ? ' bcg-template-card-active' : ''; ?>"
							data-slug="<?php echo esc_attr( $tpl_slug ); ?>"
							title="<?php echo esc_attr( $tpl['name'] . ( ! empty( $tpl['description'] ) ? ' — ' . $tpl['description'] : '' ) ); ?>"
						>
							<div class="bcg-template-mini-swatch" style="background-color:#f5f5f5;">
								<div class="bcg-template-mini-swatch-inner" style="background-color:#ffffff;border-top:2px solid <?php echo $primary; ?>;">
									<svg viewBox="0 0 46 24" xmlns="http://www.w3.org/2000/svg" style="width:100%;height:auto;display:block;">
										<?php echo $diagram_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</svg>
								</div>
							</div>
							<span class="bcg-template-mini-name"><?php echo esc_html( $tpl['name'] ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="bcg-preview-panel">
				<div class="bcg-preview-header bcg-flex bcg-items-center bcg-justify-between">
					<h3 class="bcg-mt-0 bcg-mb-0"><?php esc_html_e( 'Live Preview', 'brevo-campaign-generator' ); ?></h3>
					<div class="bcg-preview-device-toggle bcg-flex bcg-gap-8">
						<button type="button"
							class="button bcg-preview-device-btn bcg-preview-desktop active"
							data-device="desktop"
							title="<?php esc_attr_e( 'Desktop preview', 'brevo-campaign-generator' ); ?>">
							<span class="dashicons dashicons-desktop"></span>
						</button>
						<button type="button"
							class="button bcg-preview-device-btn bcg-preview-mobile"
							data-device="mobile"
							title="<?php esc_attr_e( 'Mobile preview', 'brevo-campaign-generator' ); ?>">
							<span class="dashicons dashicons-smartphone"></span>
						</button>
					</div>
				</div>
				<div class="bcg-preview-iframe-wrapper" id="bcg-preview-wrapper">
					<div class="bcg-preview-loading" id="bcg-preview-loading" style="display:none;">
						<span class="bcg-spinner"></span>
						<span><?php esc_html_e( 'Updating preview...', 'brevo-campaign-generator' ); ?></span>
					</div>
					<iframe
						id="bcg-preview-iframe"
						class="bcg-preview-iframe bcg-preview-desktop-size"
						title="<?php esc_attr_e( 'Email preview', 'brevo-campaign-generator' ); ?>"
						sandbox="allow-same-origin"
					></iframe>
				</div>
			</div>
		</div><!-- .bcg-editor-preview -->

	</div><!-- .bcg-editor-columns -->


	<!-- ── STICKY ACTIONS BAR ─────────────────────────────────── -->
	<div class="bcg-actions-bar" id="bcg-actions-bar">
		<div class="bcg-actions-bar-inner bcg-flex bcg-items-center bcg-justify-between">

			<div class="bcg-actions-left bcg-flex bcg-gap-8">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=bcg-new-campaign&campaign_id=' . absint( $campaign_id ) ) ); ?>"
					class="button">
					<?php esc_html_e( 'Back to Configuration', 'brevo-campaign-generator' ); ?>
				</a>

				<button type="button" class="button button-primary" id="bcg-save-draft">
					<?php esc_html_e( 'Save Draft', 'brevo-campaign-generator' ); ?>
				</button>
			</div>

			<div class="bcg-actions-right bcg-flex bcg-gap-8">
				<button type="button" class="button" id="bcg-preview-email" title="<?php esc_attr_e( 'Open full email preview in a new window', 'brevo-campaign-generator' ); ?>">
					<?php esc_html_e( 'Preview Email', 'brevo-campaign-generator' ); ?>
				</button>

				<button type="button" class="button" id="bcg-send-test" title="<?php esc_attr_e( 'Send a test email to the admin email address', 'brevo-campaign-generator' ); ?>">
					<?php esc_html_e( 'Send Test Email', 'brevo-campaign-generator' ); ?>
				</button>

				<button type="button" class="button" id="bcg-create-brevo">
					<?php esc_html_e( 'Create in Brevo', 'brevo-campaign-generator' ); ?>
				</button>

				<button type="button" class="button" id="bcg-schedule-campaign">
					<?php esc_html_e( 'Schedule', 'brevo-campaign-generator' ); ?>
				</button>

				<button type="button" class="button bcg-btn-primary" id="bcg-send-now">
					<?php esc_html_e( 'Send Now', 'brevo-campaign-generator' ); ?>
				</button>
			</div>

		</div>
	</div><!-- .bcg-actions-bar -->


	<!-- ── ADD PRODUCT MODAL ──────────────────────────────────── -->
	<div id="bcg-add-product-modal" class="bcg-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="bcg-modal-title-product">
		<div class="bcg-modal-overlay"></div>
		<div class="bcg-modal-content">
			<div class="bcg-modal-header bcg-flex bcg-items-center bcg-justify-between">
				<h3 id="bcg-modal-title-product" class="bcg-mt-0 bcg-mb-0"><?php esc_html_e( 'Add Product', 'brevo-campaign-generator' ); ?></h3>
				<button type="button" class="bcg-modal-close" aria-label="<?php esc_attr_e( 'Close', 'brevo-campaign-generator' ); ?>">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
			<div class="bcg-modal-body">
				<div class="bcg-field-group bcg-mb-16">
					<label class="bcg-field-label" for="bcg-product-search">
						<?php esc_html_e( 'Search products', 'brevo-campaign-generator' ); ?>
					</label>
					<input type="text"
						id="bcg-product-search"
						class="large-text"
						placeholder="<?php esc_attr_e( 'Type product name...', 'brevo-campaign-generator' ); ?>"
						autocomplete="off"
					/>
				</div>
				<div id="bcg-product-search-results" class="bcg-product-search-results">
					<p class="bcg-text-muted bcg-text-center">
						<?php esc_html_e( 'Start typing to search for products.', 'brevo-campaign-generator' ); ?>
					</p>
				</div>
			</div>
		</div>
	</div>


	<!-- ── SCHEDULE MODAL ─────────────────────────────────────── -->
	<div id="bcg-schedule-modal" class="bcg-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="bcg-modal-title-schedule">
		<div class="bcg-modal-overlay"></div>
		<div class="bcg-modal-content bcg-modal-small">
			<div class="bcg-modal-header bcg-flex bcg-items-center bcg-justify-between">
				<h3 id="bcg-modal-title-schedule" class="bcg-mt-0 bcg-mb-0"><?php esc_html_e( 'Schedule Campaign', 'brevo-campaign-generator' ); ?></h3>
				<button type="button" class="bcg-modal-close" aria-label="<?php esc_attr_e( 'Close', 'brevo-campaign-generator' ); ?>">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
			<div class="bcg-modal-body">
				<div class="bcg-field-group bcg-mb-16">
					<label class="bcg-field-label" for="bcg-schedule-date">
						<?php esc_html_e( 'Date', 'brevo-campaign-generator' ); ?>
					</label>
					<input type="text"
						id="bcg-schedule-date"
						class="regular-text bcg-datepicker"
						placeholder="<?php esc_attr_e( 'Select date...', 'brevo-campaign-generator' ); ?>"
						autocomplete="off"
					/>
				</div>
				<div class="bcg-field-group bcg-mb-16">
					<label class="bcg-field-label" for="bcg-schedule-time">
						<?php esc_html_e( 'Time', 'brevo-campaign-generator' ); ?>
					</label>
					<input type="time"
						id="bcg-schedule-time"
						class="regular-text"
						value="10:00"
					/>
				</div>
			</div>
			<div class="bcg-modal-footer bcg-flex bcg-justify-between">
				<button type="button" class="button bcg-modal-close">
					<?php esc_html_e( 'Cancel', 'brevo-campaign-generator' ); ?>
				</button>
				<button type="button" class="button button-primary" id="bcg-confirm-schedule">
					<?php esc_html_e( 'Schedule Campaign', 'brevo-campaign-generator' ); ?>
				</button>
			</div>
		</div>
	</div>


	<!-- ── SEND CONFIRMATION MODAL ─────────────────────────────── -->
	<div id="bcg-send-modal" class="bcg-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="bcg-modal-title-send">
		<div class="bcg-modal-overlay"></div>
		<div class="bcg-modal-content bcg-modal-small">
			<div class="bcg-modal-header bcg-flex bcg-items-center bcg-justify-between">
				<h3 id="bcg-modal-title-send" class="bcg-mt-0 bcg-mb-0"><?php esc_html_e( 'Send Campaign Now', 'brevo-campaign-generator' ); ?></h3>
				<button type="button" class="bcg-modal-close" aria-label="<?php esc_attr_e( 'Close', 'brevo-campaign-generator' ); ?>">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
			<div class="bcg-modal-body">
				<p>
					<?php esc_html_e( 'Are you sure you want to send this campaign immediately? This action cannot be undone.', 'brevo-campaign-generator' ); ?>
				</p>
				<p class="bcg-text-muted">
					<?php
					printf(
						/* translators: %s: campaign title */
						esc_html__( 'Campaign: %s', 'brevo-campaign-generator' ),
						'<strong>' . esc_html( $campaign->title ) . '</strong>'
					);
					?>
				</p>
			</div>
			<div class="bcg-modal-footer bcg-flex bcg-justify-between">
				<button type="button" class="button bcg-modal-close">
					<?php esc_html_e( 'Cancel', 'brevo-campaign-generator' ); ?>
				</button>
				<button type="button" class="button bcg-btn-primary" id="bcg-confirm-send">
					<?php esc_html_e( 'Send Now', 'brevo-campaign-generator' ); ?>
				</button>
			</div>
		</div>
	</div>


	<!-- ── SEND TEST EMAIL MODAL ────────────────────────────────── -->
	<div id="bcg-test-email-modal" class="bcg-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="bcg-modal-title-test-email">
		<div class="bcg-modal-overlay"></div>
		<div class="bcg-modal-content bcg-modal-small">
			<div class="bcg-modal-header bcg-flex bcg-items-center bcg-justify-between">
				<h3 id="bcg-modal-title-test-email" class="bcg-mt-0 bcg-mb-0"><?php esc_html_e( 'Send Test Email', 'brevo-campaign-generator' ); ?></h3>
				<button type="button" class="bcg-modal-close" aria-label="<?php esc_attr_e( 'Close', 'brevo-campaign-generator' ); ?>">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
			<div class="bcg-modal-body">
				<div class="bcg-field-group bcg-mb-16">
					<label class="bcg-field-label" for="bcg-test-email-address">
						<?php esc_html_e( 'Email Address', 'brevo-campaign-generator' ); ?>
					</label>
					<input type="email"
						id="bcg-test-email-address"
						class="large-text"
						value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>"
						placeholder="<?php esc_attr_e( 'Enter email address...', 'brevo-campaign-generator' ); ?>"
					/>
					<p class="description">
						<?php esc_html_e( 'The test email will be sent to this address.', 'brevo-campaign-generator' ); ?>
					</p>
				</div>
			</div>
			<div class="bcg-modal-footer bcg-flex bcg-justify-between">
				<button type="button" class="button bcg-modal-close">
					<?php esc_html_e( 'Cancel', 'brevo-campaign-generator' ); ?>
				</button>
				<button type="button" class="button button-primary" id="bcg-confirm-send-test">
					<?php esc_html_e( 'Send Test Email', 'brevo-campaign-generator' ); ?>
				</button>
			</div>
		</div>
	</div>


	<!-- ── PREVIEW EMAIL MODAL ──────────────────────────────────── -->
	<div id="bcg-preview-modal" class="bcg-modal bcg-modal-fullscreen" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="bcg-modal-title-preview">
		<div class="bcg-modal-overlay"></div>
		<div class="bcg-modal-content bcg-modal-full">
			<div class="bcg-modal-header bcg-flex bcg-items-center bcg-justify-between">
				<h3 id="bcg-modal-title-preview" class="bcg-mt-0 bcg-mb-0"><?php esc_html_e( 'Email Preview', 'brevo-campaign-generator' ); ?></h3>
				<button type="button" class="bcg-modal-close" aria-label="<?php esc_attr_e( 'Close', 'brevo-campaign-generator' ); ?>">
					<span class="dashicons dashicons-no-alt"></span>
				</button>
			</div>
			<div class="bcg-modal-body bcg-p-0">
				<iframe id="bcg-fullscreen-preview-iframe" class="bcg-fullscreen-preview-iframe"
					title="<?php esc_attr_e( 'Full email preview', 'brevo-campaign-generator' ); ?>"
					sandbox="allow-same-origin"
				></iframe>
			</div>
		</div>
	</div>

</div><!-- .bcg-editor-wrap -->
