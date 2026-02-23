<?php
/**
 * Product card partial for the campaign editor repeater.
 *
 * Renders a single draggable product card with image, editable AI headline,
 * editable short description, regeneration buttons, image source controls,
 * buy button toggle, and remove button.
 *
 * Expected variables in scope:
 *
 * @var object $product       Campaign product row object from BCG_Campaign::get().
 *                            Properties: id, campaign_id, product_id, sort_order,
 *                            ai_headline, ai_short_desc, custom_headline,
 *                            custom_short_desc, generated_image_url,
 *                            use_product_image, show_buy_button,
 *                            wc_product_name, wc_price_html, wc_permalink,
 *                            wc_image_url, wc_stock_status.
 * @var int    $campaign_id   The parent campaign ID.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Determine display values.
$product_row_id     = absint( $product->id );
$product_id         = absint( $product->product_id );
$product_name       = ! empty( $product->wc_product_name )
	? $product->wc_product_name
	: __( 'Product not found', 'brevo-campaign-generator' );
$product_price_html = ! empty( $product->wc_price_html ) ? $product->wc_price_html : '';
$product_permalink  = ! empty( $product->wc_permalink ) ? $product->wc_permalink : '#';
$wc_image_url       = ! empty( $product->wc_image_url ) ? $product->wc_image_url : '';
$ai_image_url       = ! empty( $product->generated_image_url ) ? $product->generated_image_url : '';
$use_product_image  = (int) ( $product->use_product_image ?? 1 );
$show_buy_button    = (int) ( $product->show_buy_button ?? 1 );

// Headline: custom overrides AI.
$headline = '';
if ( ! empty( $product->custom_headline ) ) {
	$headline = $product->custom_headline;
} elseif ( ! empty( $product->ai_headline ) ) {
	$headline = $product->ai_headline;
}

// Short description: custom overrides AI.
$short_desc = '';
if ( ! empty( $product->custom_short_desc ) ) {
	$short_desc = $product->custom_short_desc;
} elseif ( ! empty( $product->ai_short_desc ) ) {
	$short_desc = $product->ai_short_desc;
}

// Determine the display image.
$display_image = $use_product_image ? $wc_image_url : $ai_image_url;
if ( empty( $display_image ) ) {
	$display_image = BCG_PLUGIN_URL . 'assets/images/default-placeholder.png';
}
?>
<div class="bcg-product-card bcg-card"
	data-product-row-id="<?php echo esc_attr( $product_row_id ); ?>"
	data-product-id="<?php echo esc_attr( $product_id ); ?>"
	data-sort-order="<?php echo esc_attr( absint( $product->sort_order ?? 0 ) ); ?>">

	<div class="bcg-product-card-header bcg-flex bcg-items-center bcg-justify-between">
		<div class="bcg-product-card-drag bcg-flex bcg-items-center bcg-gap-8">
			<span class="bcg-drag-handle dashicons dashicons-menu" title="<?php esc_attr_e( 'Drag to reorder', 'brevo-campaign-generator' ); ?>"></span>
			<h4 class="bcg-product-card-title">
				<?php echo esc_html( $product_name ); ?>
				<?php if ( ! empty( $product_price_html ) ) : ?>
					<span class="bcg-product-card-price bcg-text-muted">&mdash; <?php echo wp_kses_post( $product_price_html ); ?></span>
				<?php endif; ?>
			</h4>
		</div>
		<button type="button"
			class="bcg-remove-product bcg-remove-circle"
			data-product-row-id="<?php echo esc_attr( $product_row_id ); ?>"
			title="<?php esc_attr_e( 'Remove product', 'brevo-campaign-generator' ); ?>">
			<span class="dashicons dashicons-no-alt"></span>
		</button>
	</div>

	<div class="bcg-product-card-body bcg-flex bcg-gap-16">

		<!-- Product image column -->
		<div class="bcg-product-card-image-col">
			<div class="bcg-product-card-image-wrapper">
				<img
					src="<?php echo esc_url( $display_image ); ?>"
					alt="<?php echo esc_attr( $product_name ); ?>"
					class="bcg-product-card-img"
					data-wc-image="<?php echo esc_url( $wc_image_url ); ?>"
					data-ai-image="<?php echo esc_url( $ai_image_url ); ?>"
				/>
			</div>

			<!-- Image source button toggle -->
			<div class="bcg-image-source-btn-group bcg-mt-8">
				<button type="button"
					class="bcg-img-src-btn<?php echo $use_product_image ? ' is-active' : ''; ?>"
					data-source="product"
					data-product-row-id="<?php echo esc_attr( $product_row_id ); ?>">
					<?php esc_html_e( 'Product', 'brevo-campaign-generator' ); ?>
				</button>
				<button type="button"
					class="bcg-img-src-btn<?php echo ! $use_product_image ? ' is-active' : ''; ?>"
					data-source="ai"
					data-product-row-id="<?php echo esc_attr( $product_row_id ); ?>">
					<?php esc_html_e( 'AI', 'brevo-campaign-generator' ); ?>
				</button>
			</div>
			<!-- Hidden radios kept for JS compatibility -->
			<fieldset class="bcg-product-image-source" style="display:none;">
				<input type="radio"
					name="bcg_image_source_<?php echo esc_attr( $product_row_id ); ?>"
					value="product"
					class="bcg-image-source-radio"
					data-product-row-id="<?php echo esc_attr( $product_row_id ); ?>"
					<?php checked( $use_product_image, 1 ); ?> />
				<input type="radio"
					name="bcg_image_source_<?php echo esc_attr( $product_row_id ); ?>"
					value="ai"
					class="bcg-image-source-radio"
					data-product-row-id="<?php echo esc_attr( $product_row_id ); ?>"
					<?php checked( $use_product_image, 0 ); ?> />
			</fieldset>

			<div class="bcg-product-image-actions bcg-mt-8">
				<button type="button"
					class="button bcg-regen-product-image bcg-regen-btn"
					data-product-row-id="<?php echo esc_attr( $product_row_id ); ?>"
					data-field="product_image"
					style="<?php echo $use_product_image ? 'display:none;' : ''; ?>">
					<?php esc_html_e( 'Regenerate Image', 'brevo-campaign-generator' ); ?>
				</button>
			</div>
		</div>

		<!-- Product content column -->
		<div class="bcg-product-card-content-col">

			<!-- AI Headline -->
			<div class="bcg-field-group bcg-mb-8">
				<label class="bcg-field-label" for="bcg_headline_<?php echo esc_attr( $product_row_id ); ?>">
					<?php esc_html_e( 'Headline', 'brevo-campaign-generator' ); ?>
				</label>
				<div class="bcg-field-with-regen">
					<textarea
						id="bcg_headline_<?php echo esc_attr( $product_row_id ); ?>"
						class="bcg-product-headline large-text"
						rows="2"
						data-product-row-id="<?php echo esc_attr( $product_row_id ); ?>"
						data-field="custom_headline"
					><?php echo esc_textarea( $headline ); ?></textarea>
					<button type="button"
						class="button bcg-regen-product-field bcg-regen-btn"
						data-product-row-id="<?php echo esc_attr( $product_row_id ); ?>"
						data-field="product_headline"
						title="<?php esc_attr_e( 'Regenerate headline', 'brevo-campaign-generator' ); ?>">
						<?php esc_html_e( 'Regenerate', 'brevo-campaign-generator' ); ?>
					</button>
				</div>
			</div>

			<!-- Short Description -->
			<div class="bcg-field-group bcg-mb-8">
				<label class="bcg-field-label" for="bcg_shortdesc_<?php echo esc_attr( $product_row_id ); ?>">
					<?php esc_html_e( 'Short Description', 'brevo-campaign-generator' ); ?>
				</label>
				<div class="bcg-field-with-regen">
					<textarea
						id="bcg_shortdesc_<?php echo esc_attr( $product_row_id ); ?>"
						class="bcg-product-shortdesc large-text"
						rows="3"
						data-product-row-id="<?php echo esc_attr( $product_row_id ); ?>"
						data-field="custom_short_desc"
					><?php echo esc_textarea( $short_desc ); ?></textarea>
					<button type="button"
						class="button bcg-regen-product-field bcg-regen-btn"
						data-product-row-id="<?php echo esc_attr( $product_row_id ); ?>"
						data-field="product_short_desc"
						title="<?php esc_attr_e( 'Regenerate description', 'brevo-campaign-generator' ); ?>">
						<?php esc_html_e( 'Regenerate', 'brevo-campaign-generator' ); ?>
					</button>
				</div>
			</div>

			<!-- Show Buy Button -->
			<div class="bcg-field-group">
				<label class="bcg-inline-toggle">
					<span class="bcg-inline-toggle-switch">
						<input type="checkbox"
							class="bcg-product-show-buy-btn"
							data-product-row-id="<?php echo esc_attr( $product_row_id ); ?>"
							<?php checked( $show_buy_button, 1 ); ?> />
						<span class="bcg-inline-toggle-thumb"></span>
					</span>
					<span class="bcg-inline-toggle-label"><?php esc_html_e( 'Show Buy Button', 'brevo-campaign-generator' ); ?></span>
				</label>
			</div>

		</div><!-- .bcg-product-card-content-col -->
	</div><!-- .bcg-product-card-body -->
</div>
