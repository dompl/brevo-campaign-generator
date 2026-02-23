<?php
/**
 * What's New modal partial.
 *
 * Included on every BCG admin page via plugin-header.php.
 * The modal is shown automatically when a new version is detected (via JS localStorage check).
 * It can also be re-opened by clicking the version badge in the header.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.5.33
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="bcg-whats-new-modal" class="bcg-modal bcg-whats-new-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="bcg-whats-new-title">
	<div class="bcg-modal-overlay bcg-whats-new-overlay"></div>
	<div class="bcg-modal-content bcg-whats-new-content">

		<div class="bcg-modal-header bcg-whats-new-header">
			<span class="material-icons-outlined" style="color:var(--bcg-accent);font-size:22px;flex-shrink:0;">new_releases</span>
			<h3 id="bcg-whats-new-title">
				<?php esc_html_e( "What's New", 'brevo-campaign-generator' ); ?>
				<span class="bcg-whats-new-version">v<?php echo esc_html( BCG_VERSION ); ?></span>
			</h3>
			<button type="button" class="bcg-modal-close" id="bcg-whats-new-close" aria-label="<?php esc_attr_e( 'Close', 'brevo-campaign-generator' ); ?>">
				<span class="material-icons-outlined">close</span>
			</button>
		</div>

		<div class="bcg-whats-new-body">
			<p class="bcg-whats-new-intro">
				<?php esc_html_e( "Here's what improved in the latest update:", 'brevo-campaign-generator' ); ?>
			</p>
			<ul class="bcg-whats-new-list" id="bcg-whats-new-list">
				<!-- Items injected by JS from bcgData.whats_new.items -->
			</ul>
		</div>

		<div class="bcg-whats-new-footer">
			<button type="button" id="bcg-whats-new-dismiss" class="bcg-btn-primary">
				<span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">check</span>
				<?php esc_html_e( "Got it", 'brevo-campaign-generator' ); ?>
			</button>
		</div>

	</div><!-- /.bcg-modal-content -->
</div>
