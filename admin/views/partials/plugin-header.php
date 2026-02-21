<?php
/**
 * Plugin brand header — displayed at the top of every BCG admin page.
 *
 * Shows the Red Frog Studio logo, plugin name, and current credit balance.
 * Styled via bcg-admin.css; requires the bcg-admin-page body class.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$credit_balance = ( function_exists( 'bcg' ) && bcg()->get_admin() ) ? bcg()->get_admin()->get_current_user_credit_balance() : 0;
$credits_url    = admin_url( 'admin.php?page=bcg-credits' );
$logo_url       = BCG_PLUGIN_URL . 'admin/images/rfs-logo.png';
?>
<div class="bcg-plugin-header">
	<div class="bcg-plugin-header-brand">
		<a href="https://redfrogstudio.co.uk" target="_blank" rel="noopener noreferrer" class="bcg-brand-logo-link">
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="Red Frog Studio" class="bcg-brand-logo" />
		</a>
		<div class="bcg-brand-divider"></div>
		<div class="bcg-brand-text">
			<span class="bcg-brand-plugin-name">Brevo Campaign Generator</span>
			<span class="bcg-brand-tagline">for <strong><?php echo esc_html( get_bloginfo( 'name' ) ); ?></strong></span>
		</div>
	</div>
	<div class="bcg-plugin-header-actions">
		<a href="<?php echo esc_url( $credits_url ); ?>" class="bcg-header-credits">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
			<span class="bcg-header-credits-label"><?php echo number_format( (float) $credit_balance, 0 ); ?> credits</span>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bcg-dashboard' ) ); ?>" class="bcg-header-nav-link <?php echo ( isset( $_GET['page'] ) && $_GET['page'] === 'bcg-dashboard' ) ? 'is-active' : ''; // phpcs:ignore ?>">Dashboard</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bcg-new-campaign' ) ); ?>" class="bcg-header-nav-link <?php echo ( isset( $_GET['page'] ) && $_GET['page'] === 'bcg-new-campaign' ) ? 'is-active' : ''; // phpcs:ignore ?>">New Campaign</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bcg-settings' ) ); ?>" class="bcg-header-nav-link <?php echo ( isset( $_GET['page'] ) && $_GET['page'] === 'bcg-settings' ) ? 'is-active' : ''; // phpcs:ignore ?>">Settings</a>
	</div>
</div>
