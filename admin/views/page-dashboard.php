<?php
/**
 * Dashboard view -- campaign list and quick stats.
 *
 * Displays summary stat cards (total campaigns, drafts, sent, AI wallet
 * balance), a filterable/searchable campaigns table with pagination
 * (20 per page), and a quick-action button for creating new campaigns.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Gather Data ──────────────────────────────────────────────────────

$campaign_handler = new BCG_Campaign();

// Read query parameters (nonce-exempt: read-only display filters).
// phpcs:disable WordPress.Security.NonceVerification.Recommended
$current_status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'all';
$current_search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$current_page   = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
$orderby        = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
$order          = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC';
// phpcs:enable WordPress.Security.NonceVerification.Recommended

// Validate status.
$valid_statuses = array( 'all', 'draft', 'ready', 'sent', 'scheduled' );
if ( ! in_array( $current_status, $valid_statuses, true ) ) {
	$current_status = 'all';
}

// Validate orderby.
$valid_orderbys = array( 'created_at', 'updated_at', 'title', 'status' );
if ( ! in_array( $orderby, $valid_orderbys, true ) ) {
	$orderby = 'created_at';
}

// Validate order direction.
$order = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';

$per_page = 20;

// Fetch campaigns.
$campaigns_data = $campaign_handler->get_all( array(
	'status'   => $current_status,
	'search'   => $current_search,
	'per_page' => $per_page,
	'page'     => $current_page,
	'orderby'  => $orderby,
	'order'    => $order,
) );

$campaigns   = $campaigns_data['items'];
$total       = $campaigns_data['total'];
$total_pages = $campaigns_data['total_pages'];

// Get status counts for filter tabs.
$status_counts = $campaign_handler->get_status_counts();

// Base URL for filter/pagination links.
$base_url = admin_url( 'admin.php?page=bcg-dashboard' );

// Status labels (reusable).
$status_labels = array(
	'all'       => __( 'All', 'brevo-campaign-generator' ),
	'draft'     => __( 'Draft', 'brevo-campaign-generator' ),
	'ready'     => __( 'Ready', 'brevo-campaign-generator' ),
	'scheduled' => __( 'Scheduled', 'brevo-campaign-generator' ),
	'sent'      => __( 'Sent', 'brevo-campaign-generator' ),
);

// Status badge CSS classes.
$status_badge_classes = array(
	'draft'     => 'bcg-status-draft',
	'ready'     => 'bcg-status-ready',
	'sent'      => 'bcg-status-sent',
	'scheduled' => 'bcg-status-scheduled',
);
?>

<?php require BCG_PLUGIN_DIR . 'admin/views/partials/plugin-header.php'; ?>
<div class="wrap bcg-wrap">

	<!-- ─── Page Header ────────────────────────────────────────────── -->

	<div class="bcg-dashboard-header bcg-flex bcg-items-center bcg-justify-between bcg-mb-20">
		<h1><?php esc_html_e( 'Brevo Campaigns', 'brevo-campaign-generator' ); ?></h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bcg-new-campaign' ) ); ?>" class="bcg-btn-primary">
			<span class="material-icons-outlined" aria-hidden="true">add</span>
			<?php esc_html_e( 'New Campaign', 'brevo-campaign-generator' ); ?>
		</a>
	</div>

	<!-- ─── Overview ───────────────────────────────────────────────── -->

	<?php $has_api_key = ! empty( get_option( 'bcg_brevo_api_key', '' ) ); ?>

	<section class="bcg-perf bcg-dash-overview bcg-mb-20">

		<div class="bcg-perf-summary">
			<div class="bcg-perf-cell">
				<span class="bcg-perf-label"><?php esc_html_e( 'Campaigns', 'brevo-campaign-generator' ); ?></span>
				<span class="bcg-perf-value"><?php echo esc_html( number_format( $status_counts['all'] ) ); ?></span>
				<span class="bcg-perf-note"><?php esc_html_e( 'Built in this plugin', 'brevo-campaign-generator' ); ?></span>
			</div>
			<div class="bcg-perf-cell">
				<span class="bcg-perf-label"><?php esc_html_e( 'Drafts', 'brevo-campaign-generator' ); ?></span>
				<span class="bcg-perf-value"><?php echo esc_html( number_format( $status_counts['draft'] ) ); ?></span>
				<span class="bcg-perf-note">
					<?php
					printf(
						/* translators: %s: number of campaigns ready to send */
						esc_html__( '%s ready to send', 'brevo-campaign-generator' ),
						esc_html( number_format( $status_counts['ready'] ?? 0 ) )
					);
					?>
				</span>
			</div>
			<div class="bcg-perf-cell">
				<span class="bcg-perf-label"><?php esc_html_e( 'Sent', 'brevo-campaign-generator' ); ?></span>
				<span class="bcg-perf-value"><?php echo esc_html( number_format( $status_counts['sent'] ) ); ?></span>
				<span class="bcg-perf-note">
					<?php
					printf(
						/* translators: %s: number of scheduled campaigns */
						esc_html__( '%s scheduled', 'brevo-campaign-generator' ),
						esc_html( number_format( $status_counts['scheduled'] ?? 0 ) )
					);
					?>
				</span>
			</div>
		</div>

		<?php if ( $has_api_key ) : ?>
			<div class="bcg-dash-latest" id="bcg-dash-latest">
				<div class="bcg-perf-chart-head">
					<h2 class="bcg-perf-title"><?php esc_html_e( 'Latest from Brevo', 'brevo-campaign-generator' ); ?></h2>
					<a class="bcg-dash-latest-link" href="<?php echo esc_url( admin_url( 'admin.php?page=bcg-stats' ) ); ?>">
						<?php esc_html_e( 'All campaign stats', 'brevo-campaign-generator' ); ?>
						<span class="material-icons-outlined" aria-hidden="true">arrow_forward</span>
					</a>
				</div>
				<table class="bcg-dash-latest-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Campaign', 'brevo-campaign-generator' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Sent on', 'brevo-campaign-generator' ); ?></th>
							<th scope="col" class="is-num"><?php esc_html_e( 'Sent to', 'brevo-campaign-generator' ); ?></th>
							<th scope="col" class="is-num"><?php esc_html_e( 'Open rate', 'brevo-campaign-generator' ); ?></th>
							<th scope="col" class="is-num"><?php esc_html_e( 'Click rate', 'brevo-campaign-generator' ); ?></th>
						</tr>
					</thead>
					<tbody id="bcg-dash-latest-body">
						<?php for ( $i = 0; $i < 5; $i++ ) : ?>
							<tr class="is-loading"><td colspan="5"><span class="bcg-skeleton bcg-skeleton-text">&nbsp;</span></td></tr>
						<?php endfor; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

	</section>

	<!-- ─── Status Filter Tabs ─────────────────────────────────────── -->

	<div class="bcg-dashboard-filters bcg-mb-16">
		<ul class="subsubsub">
			<?php
			$tab_links = array();
			foreach ( $status_labels as $status_key => $label ) {
				$count     = $status_counts[ $status_key ];
				$url       = add_query_arg( 'status', $status_key, $base_url );
				$is_active = ( $current_status === $status_key );
				$class     = $is_active ? 'current' : '';

				$tab_links[] = sprintf(
					'<li><a href="%s" class="%s">%s <span class="count">(%s)</span></a></li>',
					esc_url( $url ),
					esc_attr( $class ),
					esc_html( $label ),
					esc_html( number_format( $count ) )
				);
			}

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built with esc_url, esc_attr, esc_html above.
			echo implode( '', $tab_links );
			?>
		</ul>
	</div>

	<!-- ─── Search Box ─────────────────────────────────────────────── -->

	<form method="get" action="<?php echo esc_url( $base_url ); ?>" class="bcg-search-form bcg-mb-16">
		<input type="hidden" name="page" value="bcg-dashboard" />
		<?php if ( 'all' !== $current_status ) : ?>
			<input type="hidden" name="status" value="<?php echo esc_attr( $current_status ); ?>" />
		<?php endif; ?>
		<p class="search-box">
			<label class="screen-reader-text" for="bcg-campaign-search">
				<?php esc_html_e( 'Search campaigns', 'brevo-campaign-generator' ); ?>
			</label>
			<input
				type="search"
				id="bcg-campaign-search"
				name="s"
				value="<?php echo esc_attr( $current_search ); ?>"
				placeholder="<?php esc_attr_e( 'Search campaigns...', 'brevo-campaign-generator' ); ?>"
			/>
			<input
				type="submit"
				class="bcg-btn-secondary"
				value="<?php esc_attr_e( 'Search', 'brevo-campaign-generator' ); ?>"
			/>
			<?php if ( ! empty( $current_search ) ) : ?>
				<a
					href="<?php echo esc_url( add_query_arg( 'status', $current_status, $base_url ) ); ?>"
					class="bcg-btn-secondary"
				>
					<?php esc_html_e( 'Clear', 'brevo-campaign-generator' ); ?>
				</a>
			<?php endif; ?>
		</p>
	</form>

	<div style="clear: both;"></div>

	<?php if ( ! empty( $current_search ) ) : ?>
		<p class="bcg-text-muted bcg-mb-8">
			<?php
			printf(
				/* translators: 1: number of results, 2: search term */
				esc_html__( '%1$s result(s) for "%2$s"', 'brevo-campaign-generator' ),
				esc_html( number_format( $total ) ),
				esc_html( $current_search )
			);
			?>
		</p>
	<?php endif; ?>

	<!-- ─── Campaigns Table ────────────────────────────────────────── -->

	<?php if ( empty( $campaigns ) ) : ?>

		<!-- Empty State -->
		<div class="bcg-card bcg-empty-state">
			<div class="bcg-empty-state-icon">
				<span class="material-icons-outlined" style="font-size: 48px; color: var(--bcg-text-muted);" aria-hidden="true">mail_outline</span>
			</div>
			<?php if ( ! empty( $current_search ) ) : ?>
				<h2><?php esc_html_e( 'No campaigns found', 'brevo-campaign-generator' ); ?></h2>
				<p><?php esc_html_e( 'No campaigns match your search criteria. Try a different search term or clear the filter.', 'brevo-campaign-generator' ); ?></p>
			<?php elseif ( 'all' !== $current_status ) : ?>
				<h2>
					<?php
					printf(
						/* translators: %s: status name */
						esc_html__( 'No %s campaigns', 'brevo-campaign-generator' ),
						esc_html( strtolower( $status_labels[ $current_status ] ?? $current_status ) )
					);
					?>
				</h2>
				<p><?php esc_html_e( 'There are no campaigns with this status. Try viewing all campaigns or create a new one.', 'brevo-campaign-generator' ); ?></p>
				<a href="<?php echo esc_url( $base_url ); ?>" class="bcg-btn-secondary bcg-mt-8" style="display: inline-flex;">
					<?php esc_html_e( 'View All Campaigns', 'brevo-campaign-generator' ); ?>
				</a>
			<?php else : ?>
				<h2><?php esc_html_e( 'Create your first campaign', 'brevo-campaign-generator' ); ?></h2>
				<p><?php esc_html_e( 'You have not created any email campaigns yet. Get started by creating your first AI-powered campaign.', 'brevo-campaign-generator' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=bcg-new-campaign' ) ); ?>" class="bcg-btn-primary bcg-mt-8">
					<span class="material-icons-outlined" aria-hidden="true">add</span>
					<?php esc_html_e( 'Create Campaign', 'brevo-campaign-generator' ); ?>
				</a>
			<?php endif; ?>
		</div>

	<?php else : ?>

		<div class="bcg-table-card">
		<table class="wp-list-table widefat fixed striped bcg-campaigns-table">
			<thead>
				<tr>
					<?php
					// Sortable column: Title.
					$title_order = ( 'title' === $orderby && 'ASC' === $order ) ? 'desc' : 'asc';
					$title_class = ( 'title' === $orderby ) ? 'sorted ' . strtolower( $order ) : 'sortable desc';
					$title_url   = add_query_arg( array(
						'orderby' => 'title',
						'order'   => $title_order,
						'status'  => $current_status,
						's'       => $current_search,
					), $base_url );
					?>
					<th scope="col" class="bcg-col-title column-primary <?php echo esc_attr( $title_class ); ?>">
						<a href="<?php echo esc_url( $title_url ); ?>">
							<span><?php esc_html_e( 'Title', 'brevo-campaign-generator' ); ?></span>
							<span class="sorting-indicators">
								<span class="sorting-indicator asc" aria-hidden="true"></span>
								<span class="sorting-indicator desc" aria-hidden="true"></span>
							</span>
						</a>
					</th>
					<th scope="col" class="bcg-col-status">
						<?php esc_html_e( 'Status', 'brevo-campaign-generator' ); ?>
					</th>
					<th scope="col" class="bcg-col-products">
						<?php esc_html_e( 'Products', 'brevo-campaign-generator' ); ?>
					</th>
					<th scope="col" class="bcg-col-list">
						<?php esc_html_e( 'Mailing List', 'brevo-campaign-generator' ); ?>
					</th>
					<?php
					// Sortable column: Created.
					$created_order = ( 'created_at' === $orderby && 'DESC' === $order ) ? 'asc' : 'desc';
					$created_class = ( 'created_at' === $orderby ) ? 'sorted ' . strtolower( $order ) : 'sortable desc';
					$created_url   = add_query_arg( array(
						'orderby' => 'created_at',
						'order'   => $created_order,
						'status'  => $current_status,
						's'       => $current_search,
					), $base_url );
					?>
					<th scope="col" class="bcg-col-created <?php echo esc_attr( $created_class ); ?>">
						<a href="<?php echo esc_url( $created_url ); ?>">
							<span><?php esc_html_e( 'Created', 'brevo-campaign-generator' ); ?></span>
							<span class="sorting-indicators">
								<span class="sorting-indicator asc" aria-hidden="true"></span>
								<span class="sorting-indicator desc" aria-hidden="true"></span>
							</span>
						</a>
					</th>
					<th scope="col" class="bcg-col-actions">
						<?php esc_html_e( 'Actions', 'brevo-campaign-generator' ); ?>
					</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $campaigns as $campaign_row ) : ?>
					<?php
					$edit_url      = admin_url( 'admin.php?page=bcg-edit-campaign&campaign_id=' . absint( $campaign_row->id ) );
					$row_status    = sanitize_text_field( $campaign_row->status );
					$product_count = isset( $campaign_row->product_count ) ? absint( $campaign_row->product_count ) : 0;

					// Format created date.
					$created_display = '';
					$created_title   = '';
					if ( ! empty( $campaign_row->created_at ) ) {
						$timestamp       = strtotime( $campaign_row->created_at );
						$created_title   = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
						$created_display = sprintf(
							/* translators: %s: human-readable time difference */
							__( '%s ago', 'brevo-campaign-generator' ),
							human_time_diff( $timestamp, current_time( 'timestamp' ) )
						);
					}

					// Mailing list display.
					$list_display = ! empty( $campaign_row->mailing_list_id )
						? sprintf(
							/* translators: %s: mailing list ID */
							__( 'List #%s', 'brevo-campaign-generator' ),
							$campaign_row->mailing_list_id
						)
						: '';

					// Status badge.
					$badge_class = $status_badge_classes[ $row_status ] ?? 'bcg-status-draft';
					$badge_label = $status_labels[ $row_status ] ?? ucfirst( $row_status );
					?>
					<tr id="bcg-campaign-row-<?php echo absint( $campaign_row->id ); ?>">

						<!-- Title -->
						<td class="bcg-col-title column-primary" data-colname="<?php esc_attr_e( 'Title', 'brevo-campaign-generator' ); ?>">
							<strong>
								<a href="<?php echo esc_url( $edit_url ); ?>" class="row-title">
									<?php echo esc_html( $campaign_row->title ); ?>
								</a>
							</strong>
							<?php if ( ! empty( $campaign_row->subject ) && 0 !== strcasecmp( trim( $campaign_row->subject ), trim( $campaign_row->title ) ) ) : ?>
								<span class="bcg-row-subject" title="<?php echo esc_attr( $campaign_row->subject ); ?>">
									<?php echo esc_html( $campaign_row->subject ); ?>
								</span>
							<?php endif; ?>
							<button type="button" class="toggle-row">
								<span class="screen-reader-text"><?php esc_html_e( 'Show more details', 'brevo-campaign-generator' ); ?></span>
							</button>
						</td>

						<!-- Status -->
						<td class="bcg-col-status" data-colname="<?php esc_attr_e( 'Status', 'brevo-campaign-generator' ); ?>">
							<span class="bcg-status-badge <?php echo esc_attr( $badge_class ); ?>">
								<?php echo esc_html( $badge_label ); ?>
							</span>
						</td>

						<!-- Products -->
						<td class="bcg-col-products" data-colname="<?php esc_attr_e( 'Products', 'brevo-campaign-generator' ); ?>">
							<?php echo esc_html( $product_count ); ?>
						</td>

						<!-- Mailing List -->
						<td class="bcg-col-list" data-colname="<?php esc_attr_e( 'Mailing List', 'brevo-campaign-generator' ); ?>">
							<?php if ( $list_display ) : ?>
								<?php echo esc_html( $list_display ); ?>
							<?php else : ?>
								<span class="bcg-text-muted">&mdash;</span>
							<?php endif; ?>
						</td>

						<!-- Created -->
						<td class="bcg-col-created" data-colname="<?php esc_attr_e( 'Created', 'brevo-campaign-generator' ); ?>">
							<?php if ( $created_display ) : ?>
								<abbr title="<?php echo esc_attr( $created_title ); ?>">
									<?php echo esc_html( $created_display ); ?>
								</abbr>
							<?php else : ?>
								<span class="bcg-text-muted">&mdash;</span>
							<?php endif; ?>
						</td>

						<!-- Actions -->
						<td class="bcg-col-actions" data-colname="<?php esc_attr_e( 'Actions', 'brevo-campaign-generator' ); ?>">
							<div class="bcg-action-buttons">
								<a
									href="<?php echo esc_url( $edit_url ); ?>"
									class="bcg-btn-sm bcg-btn-secondary"
									title="<?php esc_attr_e( 'Edit', 'brevo-campaign-generator' ); ?>"
								>
									<?php esc_html_e( 'Edit', 'brevo-campaign-generator' ); ?>
								</a>

								<?php if ( ! empty( $campaign_row->template_html ) ) : ?>
									<a
										href="<?php echo esc_url( $edit_url . '&preview=1' ); ?>"
										class="bcg-btn-sm bcg-btn-secondary bcg-icon-btn"
										title="<?php esc_attr_e( 'Preview', 'brevo-campaign-generator' ); ?>"
										aria-label="<?php esc_attr_e( 'Preview', 'brevo-campaign-generator' ); ?>"
									>
										<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
									</a>
								<?php endif; ?>

								<?php if ( in_array( $campaign_row->status, array( 'draft', 'ready' ), true ) ) : ?>
									<button
										type="button"
										class="bcg-btn-sm bcg-btn-secondary bcg-icon-btn bcg-schedule-campaign"
										data-campaign-id="<?php echo absint( $campaign_row->id ); ?>"
										data-campaign-title="<?php echo esc_attr( $campaign_row->title ); ?>"
										title="<?php esc_attr_e( 'Schedule', 'brevo-campaign-generator' ); ?>"
										aria-label="<?php esc_attr_e( 'Schedule', 'brevo-campaign-generator' ); ?>"
									>
										<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
									</button>
								<?php endif; ?>

								<button
									type="button"
									class="bcg-btn-sm bcg-btn-secondary bcg-icon-btn bcg-duplicate-campaign"
									data-campaign-id="<?php echo absint( $campaign_row->id ); ?>"
									title="<?php esc_attr_e( 'Duplicate', 'brevo-campaign-generator' ); ?>"
									aria-label="<?php esc_attr_e( 'Duplicate', 'brevo-campaign-generator' ); ?>"
								>
									<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
								</button>

								<button
									type="button"
									class="bcg-btn-sm bcg-btn-secondary bcg-icon-btn bcg-icon-btn--danger bcg-delete-campaign"
									data-campaign-id="<?php echo absint( $campaign_row->id ); ?>"
									data-campaign-title="<?php echo esc_attr( $campaign_row->title ); ?>"
									title="<?php esc_attr_e( 'Delete', 'brevo-campaign-generator' ); ?>"
									aria-label="<?php esc_attr_e( 'Delete', 'brevo-campaign-generator' ); ?>"
								>
									<span class="dashicons dashicons-trash" aria-hidden="true"></span>
								</button>
							</div>
						</td>

					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		</div>

		<!-- ─── Pagination ──────────────────────────────────────────── -->

		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<span class="displaying-num">
						<?php
						printf(
							/* translators: %s: number of items */
							esc_html( _n( '%s item', '%s items', $total, 'brevo-campaign-generator' ) ),
							esc_html( number_format( $total ) )
						);
						?>
					</span>
					<span class="pagination-links">
						<?php
						// Build pagination link args.
						$pagination_base_args = array(
							'status'  => $current_status,
							'orderby' => $orderby,
							'order'   => strtolower( $order ),
						);
						if ( ! empty( $current_search ) ) {
							$pagination_base_args['s'] = $current_search;
						}
						?>

						<?php // First page. ?>
						<?php if ( $current_page > 1 ) : ?>
							<a class="first-page button" href="<?php echo esc_url( add_query_arg( array_merge( $pagination_base_args, array( 'paged' => 1 ) ), $base_url ) ); ?>">
								<span aria-hidden="true">&laquo;</span>
							</a>
						<?php else : ?>
							<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>
						<?php endif; ?>

						<?php // Previous page. ?>
						<?php if ( $current_page > 1 ) : ?>
							<a class="prev-page button" href="<?php echo esc_url( add_query_arg( array_merge( $pagination_base_args, array( 'paged' => $current_page - 1 ) ), $base_url ) ); ?>">
								<span aria-hidden="true">&lsaquo;</span>
							</a>
						<?php else : ?>
							<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>
						<?php endif; ?>

						<span class="paging-input">
							<span class="tablenav-paging-text">
								<?php
								printf(
									/* translators: 1: current page, 2: total pages */
									esc_html__( '%1$s of %2$s', 'brevo-campaign-generator' ),
									esc_html( number_format( $current_page ) ),
									esc_html( number_format( $total_pages ) )
								);
								?>
							</span>
						</span>

						<?php // Next page. ?>
						<?php if ( $current_page < $total_pages ) : ?>
							<a class="next-page button" href="<?php echo esc_url( add_query_arg( array_merge( $pagination_base_args, array( 'paged' => $current_page + 1 ) ), $base_url ) ); ?>">
								<span aria-hidden="true">&rsaquo;</span>
							</a>
						<?php else : ?>
							<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>
						<?php endif; ?>

						<?php // Last page. ?>
						<?php if ( $current_page < $total_pages ) : ?>
							<a class="last-page button" href="<?php echo esc_url( add_query_arg( array_merge( $pagination_base_args, array( 'paged' => $total_pages ) ), $base_url ) ); ?>">
								<span aria-hidden="true">&raquo;</span>
							</a>
						<?php else : ?>
							<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>
						<?php endif; ?>

					</span>
				</div>
			</div>
		<?php endif; ?>

	<?php endif; ?>

<!-- ── Schedule Campaign Modal ─────────────────────────────────── -->
<div id="bcg-schedule-modal" class="bcg-modal-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="bcg-schedule-modal-title">
	<div class="bcg-modal-box" style="max-width:420px;">
		<div class="bcg-modal-header">
			<h2 id="bcg-schedule-modal-title">
				<span class="material-icons-outlined">schedule</span>
				<?php esc_html_e( 'Schedule Campaign', 'brevo-campaign-generator' ); ?>
			</h2>
			<button type="button" class="bcg-modal-close" id="bcg-schedule-modal-close" aria-label="<?php esc_attr_e( 'Close', 'brevo-campaign-generator' ); ?>">
				<span class="material-icons-outlined">close</span>
			</button>
		</div>
		<div class="bcg-modal-body">
			<p id="bcg-schedule-campaign-name" style="font-weight:600;margin-bottom:16px;"></p>
			<div class="bcg-field-row">
				<label class="bcg-field-label" for="bcg-schedule-date"><?php esc_html_e( 'Date', 'brevo-campaign-generator' ); ?></label>
				<input type="date" id="bcg-schedule-date" class="bcg-input" style="color-scheme:dark;" />
			</div>
			<div class="bcg-field-row" style="margin-top:12px;">
				<label class="bcg-field-label" for="bcg-schedule-time"><?php esc_html_e( 'Time', 'brevo-campaign-generator' ); ?></label>
				<input type="time" id="bcg-schedule-time" class="bcg-input" value="09:00" style="color-scheme:dark;" />
			</div>
			<p id="bcg-schedule-error" class="bcg-notice bcg-notice-error" style="display:none;margin-top:12px;"></p>
		</div>
		<div class="bcg-modal-footer">
			<button type="button" class="bcg-btn bcg-btn-secondary" id="bcg-schedule-modal-cancel">
				<?php esc_html_e( 'Cancel', 'brevo-campaign-generator' ); ?>
			</button>
			<button type="button" class="bcg-btn bcg-btn-primary" id="bcg-schedule-modal-confirm">
				<span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;margin-right:4px;">schedule</span>
				<?php esc_html_e( 'Schedule Campaign', 'brevo-campaign-generator' ); ?>
			</button>
		</div>
		<input type="hidden" id="bcg-schedule-campaign-id" value="" />
	</div>
</div>

</div>

<?php if ( $has_api_key ) : ?>
<script>
( function ( $ ) {
	'use strict';

	var $body = $( '#bcg-dash-latest-body' );

	function esc( value ) {
		return $( '<div>' ).text( value == null ? '' : String( value ) ).html();
	}

	function shortDate( raw ) {
		var d = raw ? new Date( raw ) : null;
		return d && ! isNaN( d ) ? d.toLocaleDateString( 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' } ) : '';
	}

	function rateCell( value, scale, cls ) {
		var v = parseFloat( value ) || 0;
		var w = Math.max( 0, Math.min( 100, ( v / scale ) * 100 ) );
		return '<td class="is-num ' + cls + '"><span class="bcg-rate-bar"><i style="width:' + w.toFixed( 1 ) + '%"></i></span>' +
			'<span class="bcg-rate">' + v.toFixed( 1 ) + '%</span></td>';
	}

	function message( text ) {
		$body.html( '<tr class="is-empty"><td colspan="5">' + esc( text ) + '</td></tr>' );
	}

	$.post( ajaxurl, {
		action: 'bcg_dashboard_latest',
		nonce: <?php echo wp_json_encode( wp_create_nonce( 'bcg_nonce' ) ); ?>
	} ).done( function ( response ) {
		if ( ! response || ! response.success ) {
			message( ( response && response.data && response.data.message ) || <?php echo wp_json_encode( __( 'Could not load campaigns from Brevo.', 'brevo-campaign-generator' ) ); ?> );
			return;
		}

		// Local statuses were just corrected from Brevo, so reload once to show them.
		if ( response.data.synced > 0 ) {
			window.location.reload();
			return;
		}

		var rows = response.data.campaigns || [];

		if ( ! rows.length ) {
			message( <?php echo wp_json_encode( __( 'No campaigns have been sent from Brevo yet.', 'brevo-campaign-generator' ) ); ?> );
			return;
		}

		$body.html( rows.map( function ( c ) {
			return '<tr>' +
				'<td class="bcg-dash-latest-name"><span title="' + esc( c.subject ) + '">' + esc( c.name ) + '</span></td>' +
				'<td title="' + esc( c.sent_date ) + '">' + esc( shortDate( c.sent_date_raw ) ) + '</td>' +
				'<td class="is-num">' + ( parseInt( c.recipients, 10 ) || 0 ).toLocaleString( 'en-GB' ) + '</td>' +
				rateCell( c.open_rate_raw, 60, 'bcg-col-open-rate' ) +
				rateCell( c.click_rate_raw, 15, 'bcg-col-click-rate' ) +
				'</tr>';
		} ).join( '' ) );
	} ).fail( function () {
		message( <?php echo wp_json_encode( __( 'Could not load campaigns from Brevo.', 'brevo-campaign-generator' ) ); ?> );
	} );
}( jQuery ) );
</script>
<?php endif; ?>
