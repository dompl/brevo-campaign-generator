<?php
/**
 * Credits & Billing admin page.
 *
 * Displays the current credit balance, credit pack purchase options with
 * inline Stripe payment, and a paginated transaction history table with
 * type filtering.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current user data.
$current_user_id = get_current_user_id();
$credits_handler = new BCG_Credits();
$credits_handler->ensure_user_record( $current_user_id );
$current_balance = $credits_handler->get_balance( $current_user_id );

// Get credit packs.
$stripe_handler = new BCG_Stripe();
$credit_packs   = $stripe_handler->get_credit_packs();

// Currency settings.
$currency_code   = get_option( 'bcg_stripe_currency', 'GBP' );
$settings        = new BCG_Settings();
$currency_symbol = $settings->get_currency_symbol( $currency_code );

// Transaction history — pagination and filtering.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading display parameters only.
$tx_type = isset( $_GET['tx_type'] ) ? sanitize_text_field( wp_unslash( $_GET['tx_type'] ) ) : 'all';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$tx_page = isset( $_GET['tx_page'] ) ? absint( $_GET['tx_page'] ) : 1;

$valid_tx_types = array( 'all', 'topup', 'usage', 'refund' );
if ( ! in_array( $tx_type, $valid_tx_types, true ) ) {
	$tx_type = 'all';
}

$transactions_data = $credits_handler->get_transactions(
	$current_user_id,
	array(
		'type'     => $tx_type,
		'per_page' => 20,
		'page'     => $tx_page,
	)
);

$transactions = $transactions_data['items'];
$total_pages  = $transactions_data['total_pages'];
$total_items  = $transactions_data['total'];

// Check if Stripe is configured.
$stripe_configured = ! empty( get_option( 'bcg_stripe_publishable_key', '' ) ) && ! empty( get_option( 'bcg_stripe_secret_key', '' ) );
?>

<div class="wrap bcg-wrap">
	<h1><?php esc_html_e( 'Credits & Billing', 'brevo-campaign-generator' ); ?></h1>

	<!-- Payment Messages Container -->
	<div id="bcg-payment-messages" style="display: none;"></div>

	<!-- ══════════════════════════════════════════════════════════════════
	     Credit Balance Card
	     ══════════════════════════════════════════════════════════════════ -->
	<div class="bcg-card bcg-balance-card">
		<div class="bcg-balance-display">
			<div class="bcg-balance-label">
				<?php esc_html_e( 'Your Credit Balance', 'brevo-campaign-generator' ); ?>
			</div>
			<div class="bcg-balance-value">
				<span class="bcg-balance-amount"><?php echo esc_html( number_format( $current_balance, 0 ) ); ?></span>
				<span class="bcg-balance-unit"><?php esc_html_e( 'credits', 'brevo-campaign-generator' ); ?></span>
			</div>
			<div class="bcg-balance-monetary bcg-text-muted">
				<?php
				$credit_value = (float) get_option( 'bcg_credit_value', '0.05' );
				$monetary     = $current_balance * $credit_value;
				printf(
					/* translators: 1: currency symbol, 2: monetary value */
					esc_html__( 'Approximate value: %1$s%2$s', 'brevo-campaign-generator' ),
					esc_html( $currency_symbol ),
					esc_html( number_format( $monetary, 2 ) )
				);
				?>
			</div>
		</div>
	</div>

	<!-- ══════════════════════════════════════════════════════════════════
	     Credit Packs — Top Up
	     ══════════════════════════════════════════════════════════════════ -->
	<div class="bcg-card">
		<div class="bcg-card-header">
			<h2><?php esc_html_e( 'Top Up Credits', 'brevo-campaign-generator' ); ?></h2>
		</div>
		<div class="bcg-card-body">

			<?php if ( ! $stripe_configured ) : ?>
				<div class="bcg-notice bcg-notice-warning">
					<p>
						<?php
						printf(
							/* translators: %s: URL to settings page */
							wp_kses(
								__( 'Stripe is not configured. Please add your Stripe API keys in the <a href="%s">Settings page</a> to enable credit purchases.', 'brevo-campaign-generator' ),
								array( 'a' => array( 'href' => array() ) )
							),
							esc_url( admin_url( 'admin.php?page=bcg-settings&tab=api-keys' ) )
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<div class="bcg-grid-3 bcg-credit-packs-grid">
				<?php foreach ( $credit_packs as $pack_key => $pack ) : ?>
					<?php
					$per_credit = $pack['credits'] > 0 ? $pack['price'] / $pack['credits'] : 0;
					$is_best    = 2 === $pack_key; // Last pack is usually best value.
					?>
					<div class="bcg-pack-card<?php echo $is_best ? ' bcg-pack-card-featured' : ''; ?>">
						<?php if ( $is_best ) : ?>
							<div class="bcg-pack-badge">
								<?php esc_html_e( 'Best Value', 'brevo-campaign-generator' ); ?>
							</div>
						<?php endif; ?>
						<div class="bcg-pack-credits">
							<?php echo esc_html( number_format( $pack['credits'] ) ); ?>
						</div>
						<div class="bcg-pack-credits-label">
							<?php esc_html_e( 'credits', 'brevo-campaign-generator' ); ?>
						</div>
						<div class="bcg-pack-price">
							<?php
							echo esc_html( $currency_symbol . number_format( $pack['price'], 2 ) );
							?>
						</div>
						<div class="bcg-pack-per-credit bcg-text-muted bcg-text-small">
							<?php
							printf(
								/* translators: 1: currency symbol, 2: cost per credit */
								esc_html__( '%1$s%2$s per credit', 'brevo-campaign-generator' ),
								esc_html( $currency_symbol ),
								esc_html( number_format( $per_credit, 4 ) )
							);
							?>
						</div>
						<button
							type="button"
							class="bcg-pack-purchase-btn<?php echo $is_best ? ' bcg-btn-primary' : ' bcg-btn-secondary'; ?>"
							data-pack-key="<?php echo esc_attr( $pack_key ); ?>"
							<?php echo $stripe_configured ? '' : 'disabled'; ?>
						>
							<?php esc_html_e( 'Purchase', 'brevo-campaign-generator' ); ?>
						</button>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- ── Stripe Payment Form (hidden until pack selected) ── -->
			<div id="bcg-stripe-payment-section" style="display: none;">
				<div class="bcg-card bcg-payment-card bcg-mt-20">
					<div class="bcg-card-header">
						<h3><?php esc_html_e( 'Complete Your Purchase', 'brevo-campaign-generator' ); ?></h3>
					</div>
					<div class="bcg-card-body">

						<div class="bcg-payment-summary bcg-mb-16">
							<p>
								<?php esc_html_e( 'You are purchasing:', 'brevo-campaign-generator' ); ?>
								<strong>
									<span id="bcg-payment-summary-credits">0</span>
									<?php esc_html_e( 'credits', 'brevo-campaign-generator' ); ?>
								</strong>
								<?php esc_html_e( 'for', 'brevo-campaign-generator' ); ?>
								<strong><span id="bcg-payment-summary-price"></span></strong>
							</p>
						</div>

						<form id="bcg-payment-form" style="display: none;">
							<div class="bcg-form-row bcg-mb-16">
								<label for="bcg-card-element" class="bcg-form-label">
									<?php esc_html_e( 'Card Details', 'brevo-campaign-generator' ); ?>
								</label>
								<div id="bcg-card-element" class="bcg-card-element-wrapper">
									<!-- Stripe Card Element will be mounted here -->
								</div>
								<div id="bcg-card-errors" class="bcg-card-errors" role="alert" style="display: none;"></div>
							</div>

							<div class="bcg-payment-actions bcg-flex bcg-items-center bcg-gap-12">
								<button type="submit" id="bcg-submit-payment" class="bcg-btn-primary">
									<?php esc_html_e( 'Pay Now', 'brevo-campaign-generator' ); ?>
								</button>
								<button type="button" id="bcg-cancel-payment" class="bcg-btn-secondary">
									<?php esc_html_e( 'Cancel', 'brevo-campaign-generator' ); ?>
								</button>
								<span id="bcg-payment-spinner" class="bcg-spinner bcg-spinner-small" style="display: none;"></span>
							</div>
						</form>

						<div class="bcg-stripe-badge bcg-mt-16 bcg-text-muted bcg-text-small">
							<span class="dashicons dashicons-lock"></span>
							<?php esc_html_e( 'Payments are processed securely by Stripe. Your card details never touch our servers.', 'brevo-campaign-generator' ); ?>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>

	<!-- ══════════════════════════════════════════════════════════════════
	     Transaction History
	     ══════════════════════════════════════════════════════════════════ -->
	<div class="bcg-card">
		<div class="bcg-card-header">
			<h2><?php esc_html_e( 'Transaction History', 'brevo-campaign-generator' ); ?></h2>
			<span class="bcg-text-muted bcg-text-small">
				<?php
				printf(
					/* translators: %d: total number of transactions */
					esc_html( _n( '%d transaction', '%d transactions', $total_items, 'brevo-campaign-generator' ) ),
					$total_items
				);
				?>
			</span>
		</div>
		<div class="bcg-card-body">

			<!-- Transaction Type Filters -->
			<div class="bcg-transaction-filters bcg-mb-16">
				<?php
				$filter_links = array(
					'all'    => __( 'All', 'brevo-campaign-generator' ),
					'topup'  => __( 'Top-ups', 'brevo-campaign-generator' ),
					'usage'  => __( 'Usage', 'brevo-campaign-generator' ),
					'refund' => __( 'Refunds', 'brevo-campaign-generator' ),
				);

				$base_url = admin_url( 'admin.php?page=bcg-credits' );

				foreach ( $filter_links as $filter_type => $filter_label ) :
					$is_active = ( $tx_type === $filter_type );
					$filter_url = add_query_arg( 'tx_type', $filter_type, $base_url );
					?>
					<a
						href="<?php echo esc_url( $filter_url ); ?>"
						class="bcg-transaction-filter-link<?php echo $is_active ? ' bcg-filter-active' : ''; ?>"
					>
						<?php echo esc_html( $filter_label ); ?>
					</a>
				<?php endforeach; ?>
			</div>

			<?php if ( empty( $transactions ) ) : ?>
				<div class="bcg-empty-state">
					<p class="bcg-text-muted">
						<?php
						if ( 'all' === $tx_type ) {
							esc_html_e( 'No transactions yet. Purchase some credits to get started.', 'brevo-campaign-generator' );
						} else {
							printf(
								/* translators: %s: transaction type */
								esc_html__( 'No %s transactions found.', 'brevo-campaign-generator' ),
								esc_html( $filter_links[ $tx_type ] )
							);
						}
						?>
					</p>
				</div>
			<?php else : ?>
				<table id="bcg-transactions-table" class="widefat bcg-transactions-table">
					<thead>
						<tr>
							<th class="bcg-col-date"><?php esc_html_e( 'Date', 'brevo-campaign-generator' ); ?></th>
							<th class="bcg-col-type"><?php esc_html_e( 'Type', 'brevo-campaign-generator' ); ?></th>
							<th class="bcg-col-description"><?php esc_html_e( 'Description', 'brevo-campaign-generator' ); ?></th>
							<th class="bcg-col-credits"><?php esc_html_e( 'Credits', 'brevo-campaign-generator' ); ?></th>
							<th class="bcg-col-balance"><?php esc_html_e( 'Balance After', 'brevo-campaign-generator' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $transactions as $index => $tx ) : ?>
							<?php
							$row_class  = ( $index % 2 === 0 ) ? '' : 'alternate';
							$amount     = (float) $tx->amount;
							$is_credit  = $amount > 0;
							$type_label = '';
							$type_class = '';

							switch ( $tx->type ) {
								case 'topup':
									$type_label = __( 'Top-up', 'brevo-campaign-generator' );
									$type_class = 'bcg-tx-topup';
									break;
								case 'usage':
									$type_label = __( 'Usage', 'brevo-campaign-generator' );
									$type_class = 'bcg-tx-usage';
									break;
								case 'refund':
									$type_label = __( 'Refund', 'brevo-campaign-generator' );
									$type_class = 'bcg-tx-refund';
									break;
							}
							?>
							<tr class="<?php echo esc_attr( $row_class ); ?>">
								<td class="bcg-col-date">
									<?php
									$date = mysql2date(
										get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
										$tx->created_at
									);
									echo esc_html( $date );
									?>
								</td>
								<td class="bcg-col-type">
									<span class="bcg-tx-badge <?php echo esc_attr( $type_class ); ?>">
										<?php echo esc_html( $type_label ); ?>
									</span>
								</td>
								<td class="bcg-col-description">
									<?php echo esc_html( $tx->description ); ?>
								</td>
								<td class="bcg-col-credits <?php echo $is_credit ? 'bcg-text-success' : 'bcg-text-error'; ?>">
									<?php
									if ( $is_credit ) {
										echo '+' . esc_html( number_format( abs( $amount ), 0 ) );
									} else {
										echo '-' . esc_html( number_format( abs( $amount ), 0 ) );
									}
									?>
								</td>
								<td class="bcg-col-balance">
									<?php echo esc_html( number_format( (float) $tx->balance_after, 0 ) ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<!-- Pagination -->
				<?php if ( $total_pages > 1 ) : ?>
					<div class="bcg-pagination bcg-mt-16">
						<?php
						$pagination_base = add_query_arg(
							array(
								'page'    => 'bcg-credits',
								'tx_type' => $tx_type,
							),
							admin_url( 'admin.php' )
						);

						// Previous page.
						if ( $tx_page > 1 ) :
							$prev_url = add_query_arg( 'tx_page', $tx_page - 1, $pagination_base );
							?>
							<a href="<?php echo esc_url( $prev_url ); ?>" class="bcg-pagination-btn bcg-btn-secondary">
								&larr; <?php esc_html_e( 'Previous', 'brevo-campaign-generator' ); ?>
							</a>
						<?php endif; ?>

						<span class="bcg-pagination-info bcg-text-muted">
							<?php
							printf(
								/* translators: 1: current page, 2: total pages */
								esc_html__( 'Page %1$d of %2$d', 'brevo-campaign-generator' ),
								$tx_page,
								$total_pages
							);
							?>
						</span>

						<?php
						// Next page.
						if ( $tx_page < $total_pages ) :
							$next_url = add_query_arg( 'tx_page', $tx_page + 1, $pagination_base );
							?>
							<a href="<?php echo esc_url( $next_url ); ?>" class="bcg-pagination-btn bcg-btn-secondary">
								<?php esc_html_e( 'Next', 'brevo-campaign-generator' ); ?> &rarr;
							</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>

		</div>
	</div>

</div>
