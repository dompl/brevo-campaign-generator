<?php
/**
 * Template preview partial.
 *
 * Renders the live preview iframe panel used in the template editor.
 * Displays the email template with current settings applied and provides
 * desktop/mobile preview toggle buttons.
 *
 * Expected variables in scope:
 *
 * @var string $preview_html  Rendered HTML content for the email preview.
 * @var int    $campaign_id   Optional campaign ID (0 for default template).
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$campaign_id = isset( $campaign_id ) ? absint( $campaign_id ) : 0;
?>
<div class="bcg-template-preview" id="bcg-template-preview">

	<!-- Preview toolbar -->
	<div class="bcg-template-preview__toolbar">
		<span class="bcg-template-preview__label">
			<?php esc_html_e( 'Preview', 'brevo-campaign-generator' ); ?>
		</span>
		<div class="bcg-template-preview__toggles">
			<button type="button"
					class="bcg-template-preview__toggle bcg-template-preview__toggle--active"
					data-preview-mode="desktop"
					aria-label="<?php esc_attr_e( 'Desktop preview', 'brevo-campaign-generator' ); ?>">
				<span class="dashicons dashicons-desktop"></span>
			</button>
			<button type="button"
					class="bcg-template-preview__toggle"
					data-preview-mode="mobile"
					aria-label="<?php esc_attr_e( 'Mobile preview', 'brevo-campaign-generator' ); ?>">
				<span class="dashicons dashicons-smartphone"></span>
			</button>
		</div>
	</div>

	<!-- Preview iframe container -->
	<div class="bcg-template-preview__frame-wrap" id="bcg-preview-frame-wrap">
		<iframe
			id="bcg-preview-iframe"
			class="bcg-template-preview__iframe bcg-template-preview__iframe--desktop"
			title="<?php esc_attr_e( 'Email template preview', 'brevo-campaign-generator' ); ?>"
			sandbox="allow-same-origin"
			data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>"
			srcdoc="<?php echo esc_attr( isset( $preview_html ) ? $preview_html : '' ); ?>"
		></iframe>
	</div>

</div>
