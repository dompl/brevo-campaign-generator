<?php
/**
 * Brevo Stats page view.
 *
 * Displays campaign analytics fetched from the Brevo API including aggregate
 * stats cards, a filterable campaigns table with expandable detail rows, and
 * cache management controls.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$stats_handler = bcg()->get_stats();
$has_api_key   = ! empty( get_option( 'bcg_brevo_api_key', '' ) );
$cache_age     = $stats_handler ? $stats_handler->get_cache_age() : false;
$nonce         = wp_create_nonce( 'bcg_nonce' );
?>

<?php require BCG_PLUGIN_DIR . 'admin/views/partials/plugin-header.php'; ?>
<div class="wrap bcg-wrap bcg-stats-wrap">

	<h1><?php esc_html_e( 'Brevo Campaign Statistics', 'brevo-campaign-generator' ); ?></h1>

	<?php if ( ! $has_api_key ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php
				printf(
					/* translators: %s: settings page URL */
					wp_kses(
						__( 'Brevo API key is not configured. Please add your API key in the <a href="%s">Settings page</a>.', 'brevo-campaign-generator' ),
						array( 'a' => array( 'href' => array() ) )
					),
					esc_url( admin_url( 'admin.php?page=bcg-settings&tab=api-keys' ) )
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<!-- Cache Notice -->
	<div class="bcg-stats-cache-notice" id="bcg-stats-cache-notice">
		<span class="bcg-cache-text">
			<?php if ( $cache_age ) : ?>
				<?php
				printf(
					/* translators: %s: time ago string */
					esc_html__( 'Stats updated %s', 'brevo-campaign-generator' ),
					esc_html( $cache_age )
				);
				?>
			<?php else : ?>
				<?php esc_html_e( 'Stats not yet loaded', 'brevo-campaign-generator' ); ?>
			<?php endif; ?>
		</span>
		<button type="button" class="bcg-btn-secondary bcg-btn-sm" id="bcg-refresh-stats" <?php echo $has_api_key ? '' : 'disabled'; ?>>
			<?php esc_html_e( 'Refresh', 'brevo-campaign-generator' ); ?>
		</button>
	</div>

	<!-- ============================================================ -->
	<!-- Aggregate Stats Cards                                         -->
	<!-- ============================================================ -->
	<div class="bcg-stats-cards bcg-grid-4" id="bcg-stats-cards">

		<div class="bcg-stat-card" id="bcg-stat-total-campaigns">
			<div class="bcg-stat-card-inner">
				<div class="bcg-stat-icon"><span class="material-icons-outlined">campaign</span></div>
				<div class="bcg-stat-content">
					<span class="bcg-stat-value" id="bcg-stat-value-campaigns">
						<span class="bcg-skeleton bcg-skeleton-text">&nbsp;</span>
					</span>
					<span class="bcg-stat-label"><?php esc_html_e( 'Total Campaigns', 'brevo-campaign-generator' ); ?></span>
				</div>
			</div>
		</div>

		<div class="bcg-stat-card" id="bcg-stat-open-rate">
			<div class="bcg-stat-card-inner">
				<div class="bcg-stat-icon"><span class="material-icons-outlined">visibility</span></div>
				<div class="bcg-stat-content">
					<span class="bcg-stat-value" id="bcg-stat-value-open-rate">
						<span class="bcg-skeleton bcg-skeleton-text">&nbsp;</span>
					</span>
					<span class="bcg-stat-label"><?php esc_html_e( 'Avg Open Rate', 'brevo-campaign-generator' ); ?></span>
				</div>
			</div>
		</div>

		<div class="bcg-stat-card" id="bcg-stat-click-rate">
			<div class="bcg-stat-card-inner">
				<div class="bcg-stat-icon"><span class="material-icons-outlined">touch_app</span></div>
				<div class="bcg-stat-content">
					<span class="bcg-stat-value" id="bcg-stat-value-click-rate">
						<span class="bcg-skeleton bcg-skeleton-text">&nbsp;</span>
					</span>
					<span class="bcg-stat-label"><?php esc_html_e( 'Avg Click Rate', 'brevo-campaign-generator' ); ?></span>
				</div>
			</div>
		</div>

		<div class="bcg-stat-card" id="bcg-stat-total-sent">
			<div class="bcg-stat-card-inner">
				<div class="bcg-stat-icon"><span class="material-icons-outlined">send</span></div>
				<div class="bcg-stat-content">
					<span class="bcg-stat-value" id="bcg-stat-value-total-sent">
						<span class="bcg-skeleton bcg-skeleton-text">&nbsp;</span>
					</span>
					<span class="bcg-stat-label"><?php esc_html_e( 'Total Emails Sent', 'brevo-campaign-generator' ); ?></span>
				</div>
			</div>
		</div>

	</div>

	<!-- ============================================================ -->
	<!-- Filter Bar                                                    -->
	<!-- ============================================================ -->
	<div class="bcg-stats-filter-bar bcg-card" id="bcg-stats-filters">
		<div class="bcg-stats-filters-row">
			<div class="bcg-filter-group">
				<label for="bcg-filter-date-from"><?php esc_html_e( 'From', 'brevo-campaign-generator' ); ?></label>
				<input type="date" id="bcg-filter-date-from" class="bcg-filter-input" value="" />
			</div>
			<div class="bcg-filter-group">
				<label for="bcg-filter-date-to"><?php esc_html_e( 'To', 'brevo-campaign-generator' ); ?></label>
				<input type="date" id="bcg-filter-date-to" class="bcg-filter-input" value="" />
			</div>
			<div class="bcg-filter-group">
				<label for="bcg-filter-status"><?php esc_html_e( 'Status', 'brevo-campaign-generator' ); ?></label>
				<select id="bcg-filter-status" class="bcg-filter-input">
					<option value="all"><?php esc_html_e( 'All', 'brevo-campaign-generator' ); ?></option>
					<option value="sent"><?php esc_html_e( 'Sent', 'brevo-campaign-generator' ); ?></option>
					<option value="draft"><?php esc_html_e( 'Draft', 'brevo-campaign-generator' ); ?></option>
					<option value="queued"><?php esc_html_e( 'Queued', 'brevo-campaign-generator' ); ?></option>
					<option value="archive"><?php esc_html_e( 'Archived', 'brevo-campaign-generator' ); ?></option>
					<option value="suspended"><?php esc_html_e( 'Suspended', 'brevo-campaign-generator' ); ?></option>
				</select>
			</div>
			<div class="bcg-filter-group bcg-filter-actions">
				<button type="button" class="bcg-btn-primary" id="bcg-apply-filters">
					<?php esc_html_e( 'Apply Filters', 'brevo-campaign-generator' ); ?>
				</button>
				<button type="button" class="bcg-btn-secondary" id="bcg-clear-filters">
					<?php esc_html_e( 'Clear', 'brevo-campaign-generator' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- ============================================================ -->
	<!-- Campaigns Stats Table                                         -->
	<!-- ============================================================ -->
	<div class="bcg-card bcg-stats-table-card">

		<table class="widefat bcg-stats-table" id="bcg-stats-table">
			<thead>
				<tr>
					<th class="bcg-col-expand"></th>
					<th class="bcg-col-name"><?php esc_html_e( 'Campaign', 'brevo-campaign-generator' ); ?></th>
					<th class="bcg-col-date"><?php esc_html_e( 'Sent Date', 'brevo-campaign-generator' ); ?></th>
					<th class="bcg-col-recipients"><?php esc_html_e( 'Recipients', 'brevo-campaign-generator' ); ?></th>
					<th class="bcg-col-opens"><?php esc_html_e( 'Opens', 'brevo-campaign-generator' ); ?></th>
					<th class="bcg-col-open-rate"><?php esc_html_e( 'Open Rate', 'brevo-campaign-generator' ); ?></th>
					<th class="bcg-col-clicks"><?php esc_html_e( 'Clicks', 'brevo-campaign-generator' ); ?></th>
					<th class="bcg-col-click-rate"><?php esc_html_e( 'Click Rate', 'brevo-campaign-generator' ); ?></th>
					<th class="bcg-col-unsubs"><?php esc_html_e( 'Unsubs', 'brevo-campaign-generator' ); ?></th>
					<th class="bcg-col-status"><?php esc_html_e( 'Status', 'brevo-campaign-generator' ); ?></th>
				</tr>
			</thead>
			<tbody id="bcg-stats-table-body">
				<!-- Loading skeleton rows -->
				<?php for ( $i = 0; $i < 5; $i++ ) : ?>
					<tr class="bcg-skeleton-row">
						<td><span class="bcg-skeleton bcg-skeleton-icon">&nbsp;</span></td>
						<td><span class="bcg-skeleton bcg-skeleton-text-wide">&nbsp;</span></td>
						<td><span class="bcg-skeleton bcg-skeleton-text">&nbsp;</span></td>
						<td><span class="bcg-skeleton bcg-skeleton-text-short">&nbsp;</span></td>
						<td><span class="bcg-skeleton bcg-skeleton-text-short">&nbsp;</span></td>
						<td><span class="bcg-skeleton bcg-skeleton-text-short">&nbsp;</span></td>
						<td><span class="bcg-skeleton bcg-skeleton-text-short">&nbsp;</span></td>
						<td><span class="bcg-skeleton bcg-skeleton-text-short">&nbsp;</span></td>
						<td><span class="bcg-skeleton bcg-skeleton-text-short">&nbsp;</span></td>
						<td><span class="bcg-skeleton bcg-skeleton-badge">&nbsp;</span></td>
					</tr>
				<?php endfor; ?>
			</tbody>
		</table>

		<!-- Empty state (hidden by default) -->
		<div class="bcg-stats-empty-state" id="bcg-stats-empty" style="display:none;">
			<div class="bcg-empty-state">
				<div class="bcg-stat-icon" style="width:56px;height:56px;margin:0 auto 12px;"><span class="material-icons-outlined" style="font-size:28px!important;">bar_chart</span></div>
				<p><?php esc_html_e( 'No campaign statistics found. Send your first campaign to see stats here.', 'brevo-campaign-generator' ); ?></p>
			</div>
		</div>

		<!-- Error state (hidden by default) -->
		<div class="bcg-stats-error-state" id="bcg-stats-error" style="display:none;">
			<div class="bcg-notice bcg-notice-error">
				<p id="bcg-stats-error-message"></p>
				<button type="button" class="bcg-btn-secondary bcg-btn-sm" id="bcg-stats-retry">
					<?php esc_html_e( 'Retry', 'brevo-campaign-generator' ); ?>
				</button>
			</div>
		</div>

		<!-- Pagination -->
		<div class="bcg-stats-pagination" id="bcg-stats-pagination" style="display:none;">
			<div class="bcg-pagination">
				<button type="button" class="button bcg-pagination-btn" id="bcg-stats-prev" disabled>
					<?php esc_html_e( 'Previous', 'brevo-campaign-generator' ); ?>
				</button>
				<span class="bcg-pagination-info" id="bcg-stats-page-info"></span>
				<button type="button" class="button bcg-pagination-btn" id="bcg-stats-next" disabled>
					<?php esc_html_e( 'Next', 'brevo-campaign-generator' ); ?>
				</button>
			</div>
		</div>

	</div><!-- .bcg-stats-table-card -->

	<!-- Hidden data for JavaScript -->
	<input type="hidden" id="bcg-stats-nonce" value="<?php echo esc_attr( $nonce ); ?>" />
	<input type="hidden" id="bcg-stats-ajax-url" value="<?php echo esc_attr( admin_url( 'admin-ajax.php' ) ); ?>" />
	<input type="hidden" id="bcg-stats-has-api-key" value="<?php echo $has_api_key ? '1' : '0'; ?>" />

</div>

<script type="text/javascript">
/**
 * Brevo Campaign Generator - Stats Page JS
 *
 * Self-contained stats page controller. Handles data fetching, rendering
 * of stat cards and campaign table rows, filter application, expandable
 * detail rows, and pagination.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */
(function ($) {
	'use strict';

	var BCGStats = {

		ajaxUrl:   '',
		nonce:     '',
		hasApiKey: false,
		offset:    0,
		limit:     50,
		total:     0,

		init: function () {
			this.ajaxUrl   = $('#bcg-stats-ajax-url').val() || '';
			this.nonce     = $('#bcg-stats-nonce').val() || '';
			this.hasApiKey = $('#bcg-stats-has-api-key').val() === '1';

			this.bindEvents();

			if (this.hasApiKey) {
				this.loadStats();
			} else {
				this.showEmpty();
			}
		},

		bindEvents: function () {
			var self = this;

			$('#bcg-apply-filters').on('click', function () {
				self.offset = 0;
				self.loadStats();
			});

			$('#bcg-clear-filters').on('click', function () {
				$('#bcg-filter-date-from').val('');
				$('#bcg-filter-date-to').val('');
				$('#bcg-filter-status').val('all');
				self.offset = 0;
				self.loadStats();
			});

			$('#bcg-refresh-stats').on('click', function () {
				self.offset = 0;
				self.loadStats(true);
			});

			$('#bcg-stats-retry').on('click', function () {
				self.loadStats();
			});

			$('#bcg-stats-prev').on('click', function () {
				if (self.offset >= self.limit) {
					self.offset -= self.limit;
					self.loadStats();
				}
			});

			$('#bcg-stats-next').on('click', function () {
				if (self.offset + self.limit < self.total) {
					self.offset += self.limit;
					self.loadStats();
				}
			});

			// Expand/collapse row detail.
			$(document).on('click', '.bcg-stats-row-toggle', function (e) {
				e.preventDefault();
				var $row      = $(this).closest('tr');
				var $detail   = $row.next('.bcg-stats-detail-row');
				var campId    = $row.data('campaign-id');

				if ($detail.length && $detail.is(':visible')) {
					$detail.hide();
					$(this).find('.dashicons').removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
				} else if ($detail.length) {
					$detail.show();
					$(this).find('.dashicons').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
				} else {
					self.loadCampaignDetail(campId, $row);
					$(this).find('.dashicons').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
				}
			});
		},

		getFilters: function () {
			return {
				date_from: $('#bcg-filter-date-from').val() || '',
				date_to:   $('#bcg-filter-date-to').val() || '',
				status:    $('#bcg-filter-status').val() || 'all'
			};
		},

		loadStats: function (forceRefresh) {
			var self    = this;
			var action  = forceRefresh ? 'bcg_refresh_stats' : 'bcg_get_stats';
			var filters = this.getFilters();

			this.showLoading();

			$.ajax({
				url:      this.ajaxUrl,
				type:     'POST',
				dataType: 'json',
				data: {
					action:    action,
					nonce:     this.nonce,
					status:    filters.status,
					date_from: filters.date_from,
					date_to:   filters.date_to,
					limit:     this.limit,
					offset:    this.offset
				},
				success: function (response) {
					if (response.success && response.data) {
						self.renderStats(response.data);
					} else {
						self.showError(response.data ? response.data.message : 'An error occurred loading statistics.');
					}
				},
				error: function () {
					self.showError('Failed to connect to the server. Please try again.');
				}
			});
		},

		renderStats: function (data) {
			// Render aggregate cards.
			var agg = data.aggregate || {};
			$('#bcg-stat-value-campaigns').text(this.formatNumber(agg.total_campaigns || 0));
			$('#bcg-stat-value-open-rate').text(agg.avg_open_rate || '0.00%');
			$('#bcg-stat-value-click-rate').text(agg.avg_click_rate || '0.00%');
			$('#bcg-stat-value-total-sent').text(this.formatNumber(agg.total_emails_sent || 0));

			// Render campaigns table.
			var campaigns = data.campaigns || [];
			this.total    = data.total || 0;

			var $tbody = $('#bcg-stats-table-body');
			$tbody.empty();

			if (campaigns.length === 0) {
				this.showEmpty();
				return;
			}

			$('#bcg-stats-empty').hide();
			$('#bcg-stats-error').hide();
			$('#bcg-stats-table').show();

			for (var i = 0; i < campaigns.length; i++) {
				$tbody.append(this.buildCampaignRow(campaigns[i], i));
			}

			// Update cache notice.
			if (data.cached_at) {
				$('#bcg-stats-cache-notice .bcg-cache-text').text(
					'<?php echo esc_js( __( 'Stats updated just now', 'brevo-campaign-generator' ) ); ?>'
				);
			}

			// Update pagination.
			this.updatePagination();
		},

		buildCampaignRow: function (camp, index) {
			var altClass  = (index % 2 === 0) ? 'alternate' : '';
			var statusBadge = this.getStatusBadge(camp.status);

			return '<tr class="bcg-stats-campaign-row ' + altClass + '" data-campaign-id="' + camp.id + '">' +
				'<td class="bcg-col-expand">' +
					'<button type="button" class="button button-link bcg-stats-row-toggle" title="<?php echo esc_js( __( 'Expand details', 'brevo-campaign-generator' ) ); ?>">' +
						'<span class="dashicons dashicons-arrow-down-alt2"></span>' +
					'</button>' +
				'</td>' +
				'<td class="bcg-col-name">' + this.escHtml(camp.name) + '</td>' +
				'<td class="bcg-col-date">' + this.escHtml(camp.sent_date || '—') + '</td>' +
				'<td class="bcg-col-recipients">' + this.formatNumber(camp.recipients) + '</td>' +
				'<td class="bcg-col-opens">' + this.formatNumber(camp.opens) + '</td>' +
				'<td class="bcg-col-open-rate"><strong>' + this.escHtml(camp.open_rate) + '</strong></td>' +
				'<td class="bcg-col-clicks">' + this.formatNumber(camp.clicks) + '</td>' +
				'<td class="bcg-col-click-rate"><strong>' + this.escHtml(camp.click_rate) + '</strong></td>' +
				'<td class="bcg-col-unsubs">' + this.formatNumber(camp.unsubscribes) + '</td>' +
				'<td class="bcg-col-status">' + statusBadge + '</td>' +
			'</tr>';
		},

		loadCampaignDetail: function (campaignId, $parentRow) {
			var self = this;

			// Insert a loading detail row.
			var $detailRow = $(
				'<tr class="bcg-stats-detail-row" data-detail-for="' + campaignId + '">' +
					'<td colspan="10">' +
						'<div class="bcg-stats-detail-loading"><span class="bcg-spinner"></span> <?php echo esc_js( __( 'Loading details...', 'brevo-campaign-generator' ) ); ?></div>' +
					'</td>' +
				'</tr>'
			);
			$parentRow.after($detailRow);

			$.ajax({
				url:      this.ajaxUrl,
				type:     'POST',
				dataType: 'json',
				data: {
					action:      'bcg_get_campaign_detail',
					nonce:       this.nonce,
					campaign_id: campaignId
				},
				success: function (response) {
					if (response.success && response.data) {
						$detailRow.find('td').html(self.buildDetailContent(response.data, campaignId));
					} else {
						$detailRow.find('td').html(
							'<div class="bcg-notice bcg-notice-error"><p>' +
								self.escHtml(response.data ? response.data.message : 'Failed to load details.') +
							'</p></div>'
						);
					}
				},
				error: function () {
					$detailRow.find('td').html(
						'<div class="bcg-notice bcg-notice-error"><p><?php echo esc_js( __( 'Failed to load campaign details.', 'brevo-campaign-generator' ) ); ?></p></div>'
					);
				}
			});
		},

		buildDetailContent: function (data, campaignId) {
			var gs = data.globalStats || {};

			var html = '<div class="bcg-stats-detail-content">';
			html += '<h4>' + this.escHtml(data.name || '') + '</h4>';

			html += '<div class="bcg-stats-detail-grid">';

			html += '<div class="bcg-stats-detail-item">' +
				'<span class="bcg-detail-label"><?php echo esc_js( __( 'Sent', 'brevo-campaign-generator' ) ); ?></span>' +
				'<span class="bcg-detail-value">' + this.formatNumber(gs.sent || 0) + '</span>' +
			'</div>';

			html += '<div class="bcg-stats-detail-item">' +
				'<span class="bcg-detail-label"><?php echo esc_js( __( 'Delivered', 'brevo-campaign-generator' ) ); ?></span>' +
				'<span class="bcg-detail-value">' + this.formatNumber(gs.delivered || 0) + '</span>' +
			'</div>';

			html += '<div class="bcg-stats-detail-item">' +
				'<span class="bcg-detail-label"><?php echo esc_js( __( 'Unique Opens', 'brevo-campaign-generator' ) ); ?></span>' +
				'<span class="bcg-detail-value">' + this.formatNumber(gs.uniqueViews || 0) + '</span>' +
			'</div>';

			html += '<div class="bcg-stats-detail-item">' +
				'<span class="bcg-detail-label"><?php echo esc_js( __( 'Unique Clicks', 'brevo-campaign-generator' ) ); ?></span>' +
				'<span class="bcg-detail-value">' + this.formatNumber(gs.uniqueClicks || 0) + '</span>' +
			'</div>';

			html += '<div class="bcg-stats-detail-item">' +
				'<span class="bcg-detail-label"><?php echo esc_js( __( 'Open Rate', 'brevo-campaign-generator' ) ); ?></span>' +
				'<span class="bcg-detail-value">' + (gs.openRate || '0.00') + '%</span>' +
			'</div>';

			html += '<div class="bcg-stats-detail-item">' +
				'<span class="bcg-detail-label"><?php echo esc_js( __( 'Click Rate', 'brevo-campaign-generator' ) ); ?></span>' +
				'<span class="bcg-detail-value">' + (gs.clickRate || '0.00') + '%</span>' +
			'</div>';

			html += '<div class="bcg-stats-detail-item">' +
				'<span class="bcg-detail-label"><?php echo esc_js( __( 'Soft Bounces', 'brevo-campaign-generator' ) ); ?></span>' +
				'<span class="bcg-detail-value">' + this.formatNumber(gs.softBounces || 0) + '</span>' +
			'</div>';

			html += '<div class="bcg-stats-detail-item">' +
				'<span class="bcg-detail-label"><?php echo esc_js( __( 'Hard Bounces', 'brevo-campaign-generator' ) ); ?></span>' +
				'<span class="bcg-detail-value">' + this.formatNumber(gs.hardBounces || 0) + '</span>' +
			'</div>';

			html += '<div class="bcg-stats-detail-item">' +
				'<span class="bcg-detail-label"><?php echo esc_js( __( 'Unsubscribes', 'brevo-campaign-generator' ) ); ?></span>' +
				'<span class="bcg-detail-value">' + this.formatNumber(gs.unsubscriptions || 0) + '</span>' +
			'</div>';

			html += '<div class="bcg-stats-detail-item">' +
				'<span class="bcg-detail-label"><?php echo esc_js( __( 'Complaints', 'brevo-campaign-generator' ) ); ?></span>' +
				'<span class="bcg-detail-value">' + this.formatNumber(gs.complaints || 0) + '</span>' +
			'</div>';

			html += '</div>'; // .bcg-stats-detail-grid

			html += '<div class="bcg-stats-detail-actions">';
			html += '<a href="https://app.brevo.com/campaigns/edit/' + parseInt(campaignId, 10) + '" target="_blank" class="button button-small">';
			html += '<span class="dashicons dashicons-external"></span> <?php echo esc_js( __( 'View in Brevo', 'brevo-campaign-generator' ) ); ?>';
			html += '</a>';
			html += '</div>';

			html += '</div>'; // .bcg-stats-detail-content

			return html;
		},

		getStatusBadge: function (status) {
			var label = status || 'unknown';
			var cssClass = 'bcg-status-' + label.toLowerCase();

			var labels = {
				'sent':      '<?php echo esc_js( __( 'Sent', 'brevo-campaign-generator' ) ); ?>',
				'draft':     '<?php echo esc_js( __( 'Draft', 'brevo-campaign-generator' ) ); ?>',
				'queued':    '<?php echo esc_js( __( 'Queued', 'brevo-campaign-generator' ) ); ?>',
				'archive':   '<?php echo esc_js( __( 'Archived', 'brevo-campaign-generator' ) ); ?>',
				'suspended': '<?php echo esc_js( __( 'Suspended', 'brevo-campaign-generator' ) ); ?>'
			};

			var displayLabel = labels[label] || label;
			return '<span class="bcg-status-badge ' + cssClass + '">' + this.escHtml(displayLabel) + '</span>';
		},

		showLoading: function () {
			// Show skeleton rows.
			var $tbody = $('#bcg-stats-table-body');
			$tbody.empty();

			for (var i = 0; i < 5; i++) {
				$tbody.append(
					'<tr class="bcg-skeleton-row">' +
						'<td><span class="bcg-skeleton bcg-skeleton-icon">&nbsp;</span></td>' +
						'<td><span class="bcg-skeleton bcg-skeleton-text-wide">&nbsp;</span></td>' +
						'<td><span class="bcg-skeleton bcg-skeleton-text">&nbsp;</span></td>' +
						'<td><span class="bcg-skeleton bcg-skeleton-text-short">&nbsp;</span></td>' +
						'<td><span class="bcg-skeleton bcg-skeleton-text-short">&nbsp;</span></td>' +
						'<td><span class="bcg-skeleton bcg-skeleton-text-short">&nbsp;</span></td>' +
						'<td><span class="bcg-skeleton bcg-skeleton-text-short">&nbsp;</span></td>' +
						'<td><span class="bcg-skeleton bcg-skeleton-text-short">&nbsp;</span></td>' +
						'<td><span class="bcg-skeleton bcg-skeleton-text-short">&nbsp;</span></td>' +
						'<td><span class="bcg-skeleton bcg-skeleton-badge">&nbsp;</span></td>' +
					'</tr>'
				);
			}

			$('#bcg-stats-table').show();
			$('#bcg-stats-empty').hide();
			$('#bcg-stats-error').hide();
		},

		showEmpty: function () {
			$('#bcg-stats-table-body').empty();
			$('#bcg-stats-table').hide();
			$('#bcg-stats-pagination').hide();
			$('#bcg-stats-error').hide();
			$('#bcg-stats-empty').show();

			// Clear card values.
			$('#bcg-stat-value-campaigns').text('0');
			$('#bcg-stat-value-open-rate').text('0.00%');
			$('#bcg-stat-value-click-rate').text('0.00%');
			$('#bcg-stat-value-total-sent').text('0');
		},

		showError: function (message) {
			$('#bcg-stats-table-body').empty();
			$('#bcg-stats-table').hide();
			$('#bcg-stats-empty').hide();
			$('#bcg-stats-pagination').hide();
			$('#bcg-stats-error-message').text(message);
			$('#bcg-stats-error').show();
		},

		updatePagination: function () {
			var $pagination = $('#bcg-stats-pagination');

			if (this.total <= this.limit) {
				$pagination.hide();
				return;
			}

			$pagination.show();

			var currentPage = Math.floor(this.offset / this.limit) + 1;
			var totalPages  = Math.ceil(this.total / this.limit);

			$('#bcg-stats-page-info').text(
				currentPage + ' / ' + totalPages
			);

			$('#bcg-stats-prev').prop('disabled', this.offset <= 0);
			$('#bcg-stats-next').prop('disabled', (this.offset + this.limit) >= this.total);
		},

		formatNumber: function (num) {
			num = parseInt(num, 10) || 0;
			return num.toLocaleString();
		},

		escHtml: function (str) {
			if (!str) {
				return '';
			}
			var div = document.createElement('div');
			div.appendChild(document.createTextNode(str));
			return div.innerHTML;
		}
	};

	$(document).ready(function () {
		if ($('.bcg-stats-wrap').length) {
			BCGStats.init();
		}
	});

})(jQuery);
</script>
