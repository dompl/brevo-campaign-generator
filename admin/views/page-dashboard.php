<?php
/**
 * Dashboard view -- campaign list and quick stats.
 *
 * Displays summary stat cards (total campaigns, drafts, sent, credit
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

// Credit balance.
$credit_balance = 0.0;
$user_id        = get_current_user_id();
if ( $user_id ) {
	global $wpdb;
	$credits_table  = $wpdb->prefix . 'bcg_credits';
	$credit_balance = (float) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT balance FROM {$credits_table} WHERE user_id = %d",
			$user_id
		)
	);
}

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

	<!-- ─── Stats Cards ────────────────────────────────────────────── -->

	<div class="bcg-stats-cards bcg-grid-4 bcg-mb-20">

		<div class="bcg-stat-card bcg-card">
			<div class="bcg-stat-card-inner">
				<div class="bcg-stat-icon">
					<span class="material-icons-outlined" aria-hidden="true">campaign</span>
				</div>
				<div class="bcg-stat-content">
					<span class="bcg-stat-value"><?php echo esc_html( number_format( $status_counts['all'] ) ); ?></span>
					<span class="bcg-stat-label"><?php esc_html_e( 'Total Campaigns', 'brevo-campaign-generator' ); ?></span>
				</div>
			</div>
		</div>

		<div class="bcg-stat-card bcg-card">
			<div class="bcg-stat-card-inner">
				<div class="bcg-stat-icon bcg-stat-icon-draft">
					<span class="material-icons-outlined" aria-hidden="true">drafts</span>
				</div>
				<div class="bcg-stat-content">
					<span class="bcg-stat-value"><?php echo esc_html( number_format( $status_counts['draft'] ) ); ?></span>
					<span class="bcg-stat-label"><?php esc_html_e( 'Drafts', 'brevo-campaign-generator' ); ?></span>
				</div>
			</div>
		</div>

		<div class="bcg-stat-card bcg-card">
			<div class="bcg-stat-card-inner">
				<div class="bcg-stat-icon bcg-stat-icon-sent">
					<span class="material-icons-outlined" aria-hidden="true">mark_email_read</span>
				</div>
				<div class="bcg-stat-content">
					<span class="bcg-stat-value"><?php echo esc_html( number_format( $status_counts['sent'] ) ); ?></span>
					<span class="bcg-stat-label"><?php esc_html_e( 'Sent', 'brevo-campaign-generator' ); ?></span>
				</div>
			</div>
		</div>

		<div class="bcg-stat-card bcg-card">
			<div class="bcg-stat-card-inner">
				<div class="bcg-stat-icon bcg-stat-icon-credits">
					<span class="material-icons-outlined" aria-hidden="true">toll</span>
				</div>
				<div class="bcg-stat-content">
					<span class="bcg-stat-value"><?php echo esc_html( number_format( $credit_balance, 0 ) ); ?></span>
					<span class="bcg-stat-label"><?php esc_html_e( 'Credits Remaining', 'brevo-campaign-generator' ); ?></span>
				</div>
			</div>
		</div>

	</div>

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
				<span class="material-icons-outlined" style="font-size: 48px; color: var(--bcg-text-muted, #565c7a);" aria-hidden="true">mail_outline</span>
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
					<th scope="col" class="bcg-col-title column-primary <?php echo esc_attr( $title_class ); ?>" style="width: 30%;">
						<a href="<?php echo esc_url( $title_url ); ?>">
							<span><?php esc_html_e( 'Title', 'brevo-campaign-generator' ); ?></span>
							<span class="sorting-indicators">
								<span class="sorting-indicator asc" aria-hidden="true"></span>
								<span class="sorting-indicator desc" aria-hidden="true"></span>
							</span>
						</a>
					</th>
					<th scope="col" class="bcg-col-status" style="width: 10%;">
						<?php esc_html_e( 'Status', 'brevo-campaign-generator' ); ?>
					</th>
					<th scope="col" class="bcg-col-products" style="width: 8%;">
						<?php esc_html_e( 'Products', 'brevo-campaign-generator' ); ?>
					</th>
					<th scope="col" class="bcg-col-list" style="width: 14%;">
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
					<th scope="col" class="bcg-col-created <?php echo esc_attr( $created_class ); ?>" style="width: 15%;">
						<a href="<?php echo esc_url( $created_url ); ?>">
							<span><?php esc_html_e( 'Created', 'brevo-campaign-generator' ); ?></span>
							<span class="sorting-indicators">
								<span class="sorting-indicator asc" aria-hidden="true"></span>
								<span class="sorting-indicator desc" aria-hidden="true"></span>
							</span>
						</a>
					</th>
					<th scope="col" class="bcg-col-actions" style="width: 23%;">
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
							<?php if ( ! empty( $campaign_row->subject ) ) : ?>
								<br />
								<span class="bcg-text-muted bcg-text-small bcg-truncate" style="display: inline-block; max-width: 300px;">
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
										class="bcg-btn-sm bcg-btn-secondary"
										title="<?php esc_attr_e( 'Preview', 'brevo-campaign-generator' ); ?>"
									>
										<?php esc_html_e( 'Preview', 'brevo-campaign-generator' ); ?>
									</a>
								<?php endif; ?>

								<button
									type="button"
									class="bcg-btn-sm bcg-btn-secondary bcg-duplicate-campaign"
									data-campaign-id="<?php echo absint( $campaign_row->id ); ?>"
									title="<?php esc_attr_e( 'Duplicate', 'brevo-campaign-generator' ); ?>"
								>
									<span class="dashicons dashicons-admin-page"></span>
									<?php esc_html_e( 'Duplicate', 'brevo-campaign-generator' ); ?>
								</button>

								<button
									type="button"
									class="bcg-btn-sm bcg-btn-secondary bcg-delete-campaign"
									data-campaign-id="<?php echo absint( $campaign_row->id ); ?>"
									data-campaign-title="<?php echo esc_attr( $campaign_row->title ); ?>"
									title="<?php esc_attr_e( 'Delete', 'brevo-campaign-generator' ); ?>"
									style="color:#d63638;border-color:rgba(214,54,56,0.3);"
								>
									<span class="dashicons dashicons-trash" style="color:#d63638;"></span>
									<?php esc_html_e( 'Delete', 'brevo-campaign-generator' ); ?>
								</button>
							</div>
						</td>

					</tr>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<th scope="col" class="bcg-col-title column-primary"><?php esc_html_e( 'Title', 'brevo-campaign-generator' ); ?></th>
					<th scope="col" class="bcg-col-status"><?php esc_html_e( 'Status', 'brevo-campaign-generator' ); ?></th>
					<th scope="col" class="bcg-col-products"><?php esc_html_e( 'Products', 'brevo-campaign-generator' ); ?></th>
					<th scope="col" class="bcg-col-list"><?php esc_html_e( 'Mailing List', 'brevo-campaign-generator' ); ?></th>
					<th scope="col" class="bcg-col-created"><?php esc_html_e( 'Created', 'brevo-campaign-generator' ); ?></th>
					<th scope="col" class="bcg-col-actions"><?php esc_html_e( 'Actions', 'brevo-campaign-generator' ); ?></th>
				</tr>
			</tfoot>
		</table>

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

</div>
