<?php
/**
 * Credit balance widget partial.
 *
 * Renders the persistent credit balance widget displayed on every plugin page
 * and in the WordPress admin bar. Shows the current user's credit balance and
 * a "Top Up" button that opens the Stripe payment modal.
 *
 * Expected variables in scope:
 *
 * @var float  $balance   The current user's credit balance.
 * @var string $topup_url URL to the Credits & Billing admin page.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure balance is set — default to 0 if not provided.
$balance   = isset( $balance ) ? (float) $balance : 0.0;
$topup_url = isset( $topup_url ) ? $topup_url : admin_url( 'admin.php?page=bcg-credits' );
?>
<div class="bcg-credit-widget">
	<span class="bcg-credit-widget__icon" aria-hidden="true">&#128179;</span>
	<span class="bcg-credit-widget__label">
		<?php esc_html_e( 'Credits:', 'brevo-campaign-generator' ); ?>
	</span>
	<span class="bcg-credit-widget__balance" id="bcg-credit-balance">
		<?php echo esc_html( number_format( $balance, 0 ) ); ?>
	</span>
	<a href="<?php echo esc_url( $topup_url ); ?>"
	   class="bcg-credit-widget__topup"
	   id="bcg-topup-trigger">
		<?php esc_html_e( 'Top Up', 'brevo-campaign-generator' ); ?>
	</a>
</div>
