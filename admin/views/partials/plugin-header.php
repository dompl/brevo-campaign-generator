<?php
/**
 * Plugin brand header — displayed at the top of every BCG admin page.
 *
 * Shows the Red Frog Studio logo, plugin name, and the shared AI wallet balance.
 * Styled via bcg-admin.css; requires the bcg-admin-page body class.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$portal_balance = \RedFrogStudio\AtsAiPortal\Portal::has_key( 'email' ) ? \RedFrogStudio\AtsAiPortal\Portal::balance() : null;
$credits_url    = \RedFrogStudio\AtsAiPortal\Portal::credits_url();
$logo_url       = BCG_PLUGIN_URL . 'admin/images/rfs-logo.png';
?>
<div class="bcg-plugin-header">
	<div class="bcg-plugin-header-brand">
		<a href="https://redfrogstudio.co.uk" target="_blank" rel="noopener noreferrer" class="bcg-brand-logo-link">
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="Red Frog Studio" class="bcg-brand-logo" />
		</a>
		<div class="bcg-brand-divider"></div>
		<div class="bcg-brand-text">
			<span class="bcg-brand-plugin-name">AI Brevo</span>
			<span class="bcg-brand-tagline">for <strong><?php echo esc_html( get_bloginfo( 'name' ) ); ?></strong></span>
		</div>
		<button type="button" class="bcg-version-badge" id="bcg-version-badge" title="<?php esc_attr_e( "Click to see what's new", 'brevo-campaign-generator' ); ?>">
			v<?php echo esc_html( BCG_VERSION ); ?>
		</button>
	</div>
	<div class="bcg-plugin-header-actions">
		<a href="<?php echo esc_url( $credits_url ); ?>" class="bcg-header-credits">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
			<span class="bcg-header-credits-label"><?php echo null === $portal_balance ? esc_html__( 'AI not connected', 'brevo-campaign-generator' ) : '£' . esc_html( number_format( $portal_balance, 2 ) ) . ' ' . esc_html__( 'AI credit', 'brevo-campaign-generator' ); ?></span>
		</a>
		<?php foreach ( BCG_Admin::nav_links() as $nav_link ) : ?>
			<a href="<?php echo esc_url( $nav_link['url'] ); ?>" class="bcg-header-nav-link<?php echo $nav_link['active'] ? ' is-active' : ''; ?>"><?php echo esc_html( $nav_link['label'] ); ?></a>
		<?php endforeach; ?>
	</div>
</div>
<?php require_once BCG_PLUGIN_DIR . 'admin/views/partials/whats-new-modal.php'; ?>
