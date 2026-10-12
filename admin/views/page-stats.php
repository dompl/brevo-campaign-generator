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

	<!-- ============================================================ -->
	<!-- Performance summary + chart                                   -->
	<!-- ============================================================ -->
	<section class="bcg-perf" id="bcg-perf">

		<div class="bcg-perf-summary" id="bcg-stats-cards">
			<div class="bcg-perf-cell">
				<span class="bcg-perf-label"><?php esc_html_e( 'Emails sent', 'brevo-campaign-generator' ); ?></span>
				<span class="bcg-perf-value" id="bcg-perf-sent"><span class="bcg-skeleton bcg-skeleton-text">&nbsp;</span></span>
				<span class="bcg-perf-note" id="bcg-perf-sent-note">&nbsp;</span>
			</div>
			<div class="bcg-perf-cell">
				<span class="bcg-perf-label"><?php esc_html_e( 'Open rate', 'brevo-campaign-generator' ); ?></span>
				<span class="bcg-perf-value" id="bcg-perf-open"><span class="bcg-skeleton bcg-skeleton-text">&nbsp;</span></span>
				<span class="bcg-perf-note" id="bcg-perf-open-note">&nbsp;</span>
			</div>
			<div class="bcg-perf-cell">
				<span class="bcg-perf-label"><?php esc_html_e( 'Click rate', 'brevo-campaign-generator' ); ?></span>
				<span class="bcg-perf-value" id="bcg-perf-click"><span class="bcg-skeleton bcg-skeleton-text">&nbsp;</span></span>
				<span class="bcg-perf-note" id="bcg-perf-click-note">&nbsp;</span>
			</div>
			<div class="bcg-perf-cell">
				<span class="bcg-perf-label"><?php esc_html_e( 'Unsubscribe rate', 'brevo-campaign-generator' ); ?></span>
				<span class="bcg-perf-value" id="bcg-perf-unsub"><span class="bcg-skeleton bcg-skeleton-text">&nbsp;</span></span>
				<span class="bcg-perf-note" id="bcg-perf-unsub-note">&nbsp;</span>
			</div>
		</div>

		<div class="bcg-perf-chart-wrap">
			<div class="bcg-perf-chart-head">
				<h2 class="bcg-perf-title"><?php esc_html_e( 'Open and click rate by campaign', 'brevo-campaign-generator' ); ?></h2>
				<ul class="bcg-perf-legend">
					<li><i class="is-open"></i><?php esc_html_e( 'Opened', 'brevo-campaign-generator' ); ?></li>
					<li><i class="is-click"></i><?php esc_html_e( 'Clicked', 'brevo-campaign-generator' ); ?></li>
					<li><i class="is-avg"></i><?php esc_html_e( 'Average open rate', 'brevo-campaign-generator' ); ?></li>
				</ul>
			</div>
			<div class="bcg-perf-chart" id="bcg-perf-chart">
				<span class="bcg-skeleton bcg-perf-chart-skeleton">&nbsp;</span>
			</div>
			<div class="bcg-perf-tip" id="bcg-perf-tip" hidden></div>
		</div>

		<div class="bcg-stats-cache-notice" id="bcg-stats-cache-notice">
			<span class="bcg-cache-text">
				<?php if ( $cache_age ) : ?>
					<?php
					printf(
						/* translators: %s: time ago string */
						esc_html__( 'Figures from Brevo, updated %s', 'brevo-campaign-generator' ),
						esc_html( $cache_age )
					);
					?>
				<?php else : ?>
					<?php esc_html_e( 'Figures from Brevo', 'brevo-campaign-generator' ); ?>
				<?php endif; ?>
			</span>
			<button type="button" class="bcg-perf-refresh" id="bcg-refresh-stats" <?php echo $has_api_key ? '' : 'disabled'; ?>>
				<span class="material-icons-outlined" aria-hidden="true">refresh</span>
				<?php esc_html_e( 'Refresh', 'brevo-campaign-generator' ); ?>
			</button>
		</div>

	</section>

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
				<select id="bcg-filter-status" class="bcg-filter-input bcg-select-styled">
					<option value="all"><?php esc_html_e( 'All', 'brevo-campaign-generator' ); ?></option>
					<option value="sent"><?php esc_html_e( 'Sent', 'brevo-campaign-generator' ); ?></option>
					<option value="draft"><?php esc_html_e( 'Draft', 'brevo-campaign-generator' ); ?></option>
					<option value="queued"><?php esc_html_e( 'Queued', 'brevo-campaign-generator' ); ?></option>
					<option value="archive"><?php esc_html_e( 'Archived', 'brevo-campaign-generator' ); ?></option>
					<option value="suspended"><?php esc_html_e( 'Suspended', 'brevo-campaign-generator' ); ?></option>
				</select>
			</div>
			<div class="bcg-filter-group bcg-filter-actions">
				<button type="button" class="bcg-btn-secondary" id="bcg-apply-filters">
					<?php esc_html_e( 'Apply', 'brevo-campaign-generator' ); ?>
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
					<th class="bcg-col-recipients"><?php esc_html_e( 'Sent', 'brevo-campaign-generator' ); ?></th>
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
		limit:     100,
		total:     0,
		trend:     [],
		resizeTimer: null,

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
				var $btn    = $(this);
				var $row    = $btn.closest('tr');
				var $detail = $row.next('.bcg-stats-detail-row');
				var open    = $btn.attr('aria-expanded') !== 'true';

				$btn.attr('aria-expanded', open ? 'true' : 'false');
				$row.toggleClass('is-open', open);

				if ($detail.length) {
					$detail.toggle(open);
				} else if (open) {
					self.loadCampaignDetail($row.data('campaign-id'), $row);
				}
			});

			// Chart hover and resize.
			$('#bcg-perf-chart').on('mousemove', '.bcg-perf-hit', function (e) {
				self.showTip($(this).data('i'), e);
			}).on('mouseleave', function () {
				$('#bcg-perf-tip').prop('hidden', true);
				$('#bcg-perf-chart .bcg-perf-col.is-hover').removeClass('is-hover');
			});

			$(window).on('resize', function () {
				clearTimeout(self.resizeTimer);
				self.resizeTimer = setTimeout(function () { self.renderChart(); }, 150);
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
			// Render the summary strip and chart.
			this.trend = data.trend || [];
			this.renderSummary(data.aggregate || {});
			this.renderChart();

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
					'<?php echo esc_js( __( 'Figures from Brevo, updated just now', 'brevo-campaign-generator' ) ); ?>'
				);
			}

			// Update pagination.
			this.updatePagination();
		},

		buildCampaignRow: function (camp, index) {
			var isSent = camp.status === 'sent';
			var dash   = '<span class="bcg-muted">—</span>';
			var date   = isSent ? this.shortDate(camp.sent_date_raw) : '';

			return '<tr class="bcg-stats-campaign-row' + (isSent ? '' : ' is-unsent') + '" data-campaign-id="' + camp.id + '">' +
				'<td class="bcg-col-expand">' +
					'<button type="button" class="bcg-stats-row-toggle" aria-expanded="false" aria-label="<?php echo esc_js( __( 'Show details', 'brevo-campaign-generator' ) ); ?>">' +
						'<span class="material-icons-outlined" aria-hidden="true">expand_more</span>' +
					'</button>' +
				'</td>' +
				'<td class="bcg-col-name"><span class="bcg-camp-name">' + this.escHtml(camp.name) + '</span>' +
					(camp.subject && camp.subject.trim().toLowerCase() !== (camp.name || '').trim().toLowerCase() ? '<span class="bcg-camp-subject">' + this.escHtml(camp.subject) + '</span>' : '') +
				'</td>' +
				'<td class="bcg-col-date" title="' + this.escHtml(camp.sent_date || '') + '">' + (date ? this.escHtml(date) : dash) + '</td>' +
				'<td class="bcg-col-recipients">' + (isSent ? this.formatNumber(camp.recipients) : dash) + '</td>' +
				'<td class="bcg-col-opens">' + (isSent ? this.formatNumber(camp.opens) : dash) + '</td>' +
				'<td class="bcg-col-open-rate">' + (isSent ? this.rateCell(camp.open_rate_raw, 60) : dash) + '</td>' +
				'<td class="bcg-col-clicks">' + (isSent ? this.formatNumber(camp.clicks) : dash) + '</td>' +
				'<td class="bcg-col-click-rate">' + (isSent ? this.rateCell(camp.click_rate_raw, 15) : dash) + '</td>' +
				'<td class="bcg-col-unsubs">' + (isSent ? this.formatNumber(camp.unsubscribes) : dash) + '</td>' +
				'<td class="bcg-col-status">' + this.getStatusBadge(camp.status) + '</td>' +
			'</tr>';
		},

		rateCell: function (value, scaleMax) {
			var v     = parseFloat(value) || 0;
			var width = Math.min(100, (v / scaleMax) * 100);
			return '<span class="bcg-rate-bar" aria-hidden="true"><i style="width:' + width.toFixed(1) + '%"></i></span>' +
				'<span class="bcg-rate">' + v.toFixed(1) + '%</span>';
		},

		shortDate: function (raw) {
			if (!raw) {
				return '';
			}
			var d = new Date(raw);
			if (isNaN(d.getTime())) {
				return '';
			}
			return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
		},

		// ------------------------------------------------------------
		// Summary strip
		// ------------------------------------------------------------

		renderSummary: function (agg) {
			var trend     = this.trend;
			var delivered = agg.total_delivered || 0;
			var first     = trend.length ? new Date(trend[0].sent_date) : null;
			var recent    = this.rates(trend.slice(-5));

			$('#bcg-perf-sent').text(this.formatNumber(agg.total_emails_sent || 0));
			$('#bcg-perf-sent-note').text(
				this.formatNumber(agg.total_campaigns || 0) + ' <?php echo esc_js( __( 'campaigns', 'brevo-campaign-generator' ) ); ?>' +
				(first && !isNaN(first.getTime()) ? ' <?php echo esc_js( __( 'since', 'brevo-campaign-generator' ) ); ?> ' + first.toLocaleDateString('en-GB', { month: 'short', year: 'numeric' }) : '')
			);

			$('#bcg-perf-open').text((agg.avg_open_rate_raw || 0).toFixed(1) + '%');
			$('#bcg-perf-open-note').html(this.recentNote(recent.open, agg.avg_open_rate_raw || 0, trend.length));

			$('#bcg-perf-click').text((agg.avg_click_rate_raw || 0).toFixed(1) + '%');
			$('#bcg-perf-click-note').html(this.recentNote(recent.click, agg.avg_click_rate_raw || 0, trend.length));

			var unsubRate = delivered ? ((agg.total_unsubscribes || 0) / delivered) * 100 : 0;
			$('#bcg-perf-unsub').text(unsubRate.toFixed(2) + '%');
			$('#bcg-perf-unsub-note').text(this.formatNumber(agg.total_unsubscribes || 0) + ' <?php echo esc_js( __( 'people in total', 'brevo-campaign-generator' ) ); ?>');
		},

		rates: function (list) {
			var d = 0, o = 0, c = 0;
			for (var i = 0; i < list.length; i++) {
				d += list[i].delivered || 0;
				o += list[i].opens || 0;
				c += list[i].clicks || 0;
			}
			return { open: d ? (o / d) * 100 : 0, click: d ? (c / d) * 100 : 0 };
		},

		recentNote: function (recent, overall, count) {
			if (count < 6) {
				return '<?php echo esc_js( __( 'Average across all campaigns', 'brevo-campaign-generator' ) ); ?>';
			}
			var diff  = recent - overall;
			var cls   = Math.abs(diff) < 0.05 ? '' : (diff > 0 ? 'is-up' : 'is-down');
			var sign  = diff > 0 ? '+' : (diff < 0 ? '−' : '');
			return '<?php echo esc_js( __( 'Last 5 campaigns', 'brevo-campaign-generator' ) ); ?> ' + recent.toFixed(1) + '% ' +
				'<span class="bcg-delta ' + cls + '">' + sign + Math.abs(diff).toFixed(1) + ' pts</span>';
		},

		// ------------------------------------------------------------
		// Chart
		// ------------------------------------------------------------

		chartPoints: function () {
			var f    = this.getFilters();
			var out  = [];
			for (var i = 0; i < this.trend.length; i++) {
				var t   = this.trend[i];
				var day = (t.sent_date || '').substr(0, 10);
				if ((f.date_from && day < f.date_from) || (f.date_to && day > f.date_to) || !t.delivered) {
					continue;
				}
				out.push({
					t:     t,
					open:  (t.opens / t.delivered) * 100,
					click: (t.clicks / t.delivered) * 100,
					date:  new Date(t.sent_date)
				});
			}
			return out;
		},

		renderChart: function () {
			var $box   = $('#bcg-perf-chart');
			var pts    = this.chartPoints();
			this.points = pts;

			if (!pts.length) {
				$box.html('<p class="bcg-perf-chart-empty"><?php echo esc_js( __( 'No sent campaigns in this date range.', 'brevo-campaign-generator' ) ); ?></p>');
				return;
			}

			var W   = Math.max(320, $box.width());
			var H   = 260;
			var pad = { t: 12, r: 8, b: 28, l: 40 };
			var iw  = W - pad.l - pad.r;
			var ih  = H - pad.t - pad.b;

			var max = 0, sumO = 0, sumD = 0;
			for (var i = 0; i < pts.length; i++) {
				max = Math.max(max, pts[i].open);
				sumO += pts[i].t.opens;
				sumD += pts[i].t.delivered;
			}
			var top  = Math.max(20, Math.ceil(max / 20) * 20);
			var y    = function (v) { return pad.t + ih - (v / top) * ih; };
			var step = iw / pts.length;
			var bw   = Math.max(3, Math.min(28, step * 0.62));
			var avg  = sumD ? (sumO / sumD) * 100 : 0;
			var svg  = '';

			// Gridlines.
			for (var g = 0; g <= top; g += 20) {
				svg += '<line class="bcg-perf-grid" x1="' + pad.l + '" x2="' + (W - pad.r) + '" y1="' + y(g) + '" y2="' + y(g) + '"/>' +
					'<text class="bcg-perf-axis" x="' + (pad.l - 8) + '" y="' + (y(g) + 4) + '" text-anchor="end">' + g + '%</text>';
			}

			// Columns, hit areas and x labels.
			var lastLabel = -Infinity, lastYear = null, line = '', dots = '', hits = '';
			for (var j = 0; j < pts.length; j++) {
				var cx = pad.l + step * j + step / 2;
				var p  = pts[j];
				svg  += '<rect class="bcg-perf-col" data-i="' + j + '" x="' + (cx - bw / 2) + '" y="' + y(p.open) + '" width="' + bw + '" height="' + (y(0) - y(p.open)) + '" rx="2"/>';
				line += (j ? 'L' : 'M') + cx.toFixed(1) + ' ' + y(p.click).toFixed(1);
				dots += '<circle class="bcg-perf-dot" cx="' + cx + '" cy="' + y(p.click) + '" r="' + (step > 14 ? 3 : 2) + '"/>';
				hits += '<rect class="bcg-perf-hit" data-i="' + j + '" x="' + (pad.l + step * j) + '" y="' + pad.t + '" width="' + step + '" height="' + ih + '"/>';

				var newMonth = j === 0 || p.date.getMonth() !== pts[j - 1].date.getMonth() || p.date.getFullYear() !== pts[j - 1].date.getFullYear();
				if (newMonth && cx - lastLabel > 64) {
					var label = p.date.toLocaleDateString('en-GB', { month: 'short' });
					if (p.date.getFullYear() !== lastYear) {
						label += ' ' + String(p.date.getFullYear()).substr(2);
						lastYear = p.date.getFullYear();
					}
					svg += '<text class="bcg-perf-axis" x="' + cx + '" y="' + (H - 8) + '" text-anchor="middle">' + label + '</text>';
					lastLabel = cx;
				}
			}

			// Average open rate reference.
			svg += '<line class="bcg-perf-avg" x1="' + pad.l + '" x2="' + (W - pad.r) + '" y1="' + y(avg) + '" y2="' + y(avg) + '"/>';
			svg += '<path class="bcg-perf-line" d="' + line + '"/>' + dots + hits;

			$box.html('<svg width="' + W + '" height="' + H + '" viewBox="0 0 ' + W + ' ' + H + '" role="img" aria-label="<?php echo esc_js( __( 'Open and click rate for each campaign', 'brevo-campaign-generator' ) ); ?>">' + svg + '</svg>');
		},

		showTip: function (i, e) {
			var p = this.points && this.points[i];
			if (!p) {
				return;
			}
			var $tip  = $('#bcg-perf-tip');
			var $wrap = $tip.parent();
			var off   = $wrap.offset();

			$('#bcg-perf-chart .bcg-perf-col').removeClass('is-hover').filter('[data-i="' + i + '"]').addClass('is-hover');

			$tip.html(
				'<strong>' + this.escHtml(p.t.name) + '</strong>' +
				'<span class="bcg-perf-tip-date">' + this.escHtml(this.shortDate(p.t.sent_date)) + ' · ' + this.formatNumber(p.t.sent) + ' <?php echo esc_js( __( 'sent', 'brevo-campaign-generator' ) ); ?></span>' +
				'<span><i class="is-open"></i><?php echo esc_js( __( 'Opened', 'brevo-campaign-generator' ) ); ?> ' + this.formatNumber(p.t.opens) + ' <b>' + p.open.toFixed(1) + '%</b></span>' +
				'<span><i class="is-click"></i><?php echo esc_js( __( 'Clicked', 'brevo-campaign-generator' ) ); ?> ' + this.formatNumber(p.t.clicks) + ' <b>' + p.click.toFixed(1) + '%</b></span>'
			).prop('hidden', false);

			var x = e.pageX - off.left + 14;
			var maxX = $wrap.outerWidth() - $tip.outerWidth() - 8;
			if (x > maxX) {
				x = e.pageX - off.left - $tip.outerWidth() - 14;
			}
			$tip.css({ left: Math.max(8, x), top: Math.max(8, e.pageY - off.top - $tip.outerHeight() - 12) });
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
				'<span class="bcg-detail-label"><?php echo esc_js( __( 'Opened', 'brevo-campaign-generator' ) ); ?></span>' +
				'<span class="bcg-detail-value">' + this.formatNumber(gs.uniqueViews || 0) + '</span>' +
			'</div>';

			html += '<div class="bcg-stats-detail-item">' +
				'<span class="bcg-detail-label"><?php echo esc_js( __( 'Clicked', 'brevo-campaign-generator' ) ); ?></span>' +
				'<span class="bcg-detail-value">' + this.formatNumber(gs.uniqueClicks || 0) + '</span>' +
			'</div>';

			html += '<div class="bcg-stats-detail-item">' +
				'<span class="bcg-detail-label"><?php echo esc_js( __( 'Open rate', 'brevo-campaign-generator' ) ); ?></span>' +
				'<span class="bcg-detail-value">' + (gs.openRate || '0.00') + '%</span>' +
			'</div>';

			html += '<div class="bcg-stats-detail-item">' +
				'<span class="bcg-detail-label"><?php echo esc_js( __( 'Click rate', 'brevo-campaign-generator' ) ); ?></span>' +
				'<span class="bcg-detail-value">' + (gs.clickRate || '0.00') + '%</span>' +
			'</div>';

			html += '<div class="bcg-stats-detail-item">' +
				'<span class="bcg-detail-label"><?php echo esc_js( __( 'Soft bounces', 'brevo-campaign-generator' ) ); ?></span>' +
				'<span class="bcg-detail-value">' + this.formatNumber(gs.softBounces || 0) + '</span>' +
			'</div>';

			html += '<div class="bcg-stats-detail-item">' +
				'<span class="bcg-detail-label"><?php echo esc_js( __( 'Hard bounces', 'brevo-campaign-generator' ) ); ?></span>' +
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

			// Clear the summary strip.
			$('#bcg-perf .bcg-perf-value').text('—');
			$('#bcg-perf .bcg-perf-note').html('&nbsp;');
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
