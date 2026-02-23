/**
 * Brevo Campaign Generator - Dashboard JS
 *
 * Handles campaign deletion, duplication, and filter interactions
 * on the dashboard page.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

/* global jQuery, bcgData */

(function ( $ ) {
	'use strict';

	var BCGDashboard = {

		/**
		 * Initialise the dashboard handlers.
		 */
		init: function () {
			this.bindEvents();
		},

		/**
		 * Bind DOM events.
		 */
		bindEvents: function () {
			$( document ).on( 'click', '.bcg-delete-campaign', this.onDeleteCampaign );
			$( document ).on( 'click', '.bcg-duplicate-campaign', this.onDuplicateCampaign );
			this.bindScheduleModal();
		},

		/**
		 * Bind the schedule campaign modal handlers.
		 *
		 * Opens the schedule modal when a .bcg-schedule-campaign button is
		 * clicked, handles close/cancel actions, backdrop clicks, and
		 * fires the bcg_schedule_campaign AJAX call on confirm.
		 *
		 * @return {void}
		 */
		bindScheduleModal: function () {
			// ── Schedule Campaign ──────────────────────────────────────────
			$( document ).on( 'click', '.bcg-schedule-campaign', function() {
				var $btn          = $( this );
				var campaignId    = $btn.data( 'campaign-id' );
				var campaignTitle = $btn.data( 'campaign-title' ) || 'Campaign #' + campaignId;

				// Set tomorrow as default date.
				var tomorrow = new Date();
				tomorrow.setDate( tomorrow.getDate() + 1 );
				var tomorrowStr = tomorrow.toISOString().split( 'T' )[0];

				$( '#bcg-schedule-campaign-id' ).val( campaignId );
				$( '#bcg-schedule-campaign-name' ).text( campaignTitle );
				$( '#bcg-schedule-date' ).val( tomorrowStr ).attr( 'min', tomorrowStr );
				$( '#bcg-schedule-error' ).hide();
				$( '#bcg-schedule-modal' ).fadeIn( 150 );
			} );

			$( '#bcg-schedule-modal-close, #bcg-schedule-modal-cancel' ).on( 'click', function() {
				$( '#bcg-schedule-modal' ).fadeOut( 150 );
			} );

			$( document ).on( 'click', '#bcg-schedule-modal', function( e ) {
				if ( $( e.target ).is( '#bcg-schedule-modal' ) ) {
					$( '#bcg-schedule-modal' ).fadeOut( 150 );
				}
			} );

			$( '#bcg-schedule-modal-confirm' ).on( 'click', function() {
				var campaignId = $( '#bcg-schedule-campaign-id' ).val();
				var date       = $( '#bcg-schedule-date' ).val();
				var time       = $( '#bcg-schedule-time' ).val() || '09:00';
				var $btn       = $( this );
				var $error     = $( '#bcg-schedule-error' );

				if ( ! date ) {
					$error.text( 'Please select a date.' ).show();
					return;
				}

				// Combine date + time → ISO 8601 string.
				var scheduledAt = date + 'T' + time + ':00';

				$btn.prop( 'disabled', true ).text( 'Scheduling\u2026' );
				$error.hide();

				$.ajax( {
					url:  bcgData.ajax_url,
					type: 'POST',
					data: {
						action:       'bcg_schedule_campaign',
						nonce:        bcgData.nonce,
						campaign_id:  campaignId,
						scheduled_at: scheduledAt
					},
					success: function( response ) {
						if ( response.success ) {
							$( '#bcg-schedule-modal' ).fadeOut( 150 );
							// Update the status badge on the row.
							var $row = $( '[data-campaign-id="' + campaignId + '"]' ).closest( 'tr' );
							$row.find( '.bcg-status-badge' ).removeClass().addClass( 'bcg-status-badge bcg-status-scheduled' ).text( 'Scheduled' );
							$row.find( '.bcg-schedule-campaign' ).remove();
						} else {
							var msg = ( response.data && response.data.message ) ? response.data.message : 'Scheduling failed.';
							$error.text( msg ).show();
						}
					},
					error: function() {
						$error.text( 'Request failed. Please try again.' ).show();
					},
					complete: function() {
						$btn.prop( 'disabled', false ).html( '<span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;margin-right:4px;">schedule</span>Schedule Campaign' );
					}
				} );
			} );
		},

		/**
		 * Handle campaign deletion.
		 *
		 * @param {Event} e Click event.
		 */
		onDeleteCampaign: function ( e ) {
			e.preventDefault();

			var $btn       = $( this );
			var campaignId = $btn.data( 'campaign-id' );
			var title      = $btn.data( 'campaign-title' ) || '';

			/* translators: %s: campaign title */
			var confirmMsg = bcgData.i18n && bcgData.i18n.confirm_delete
				? bcgData.i18n.confirm_delete
				: 'Are you sure you want to delete this campaign? This cannot be undone.';

			if ( title ) {
				confirmMsg = confirmMsg + '\n\n"' + title + '"';
			}

			if ( ! window.confirm( confirmMsg ) ) {
				return;
			}

			$btn.prop( 'disabled', true ).addClass( 'is-loading' );

			$.post( bcgData.ajax_url, {
				action:      'bcg_delete_campaign',
				nonce:       bcgData.nonce,
				campaign_id: campaignId
			} )
			.done( function ( response ) {
				if ( response.success ) {
					// Remove the table row with a fade.
					$btn.closest( 'tr' ).fadeOut( 300, function () {
						$( this ).remove();

						// If no more rows, show empty state.
						if ( $( '.bcg-campaigns-table tbody tr' ).length === 0 ) {
							window.location.reload();
						}
					} );
				} else {
					window.alert( response.data && response.data.message ? response.data.message : 'Delete failed.' );
					$btn.prop( 'disabled', false ).removeClass( 'is-loading' );
				}
			} )
			.fail( function () {
				window.alert( 'An error occurred. Please try again.' );
				$btn.prop( 'disabled', false ).removeClass( 'is-loading' );
			} );
		},

		/**
		 * Handle campaign duplication.
		 *
		 * @param {Event} e Click event.
		 */
		onDuplicateCampaign: function ( e ) {
			e.preventDefault();

			var $btn       = $( this );
			var campaignId = $btn.data( 'campaign-id' );

			$btn.prop( 'disabled', true ).addClass( 'is-loading' );

			$.post( bcgData.ajax_url, {
				action:      'bcg_duplicate_campaign',
				nonce:       bcgData.nonce,
				campaign_id: campaignId
			} )
			.done( function ( response ) {
				if ( response.success ) {
					// Redirect to the new campaign edit page.
					if ( response.data && response.data.edit_url ) {
						window.location.href = response.data.edit_url;
					} else {
						window.location.reload();
					}
				} else {
					window.alert( response.data && response.data.message ? response.data.message : 'Duplication failed.' );
					$btn.prop( 'disabled', false ).removeClass( 'is-loading' );
				}
			} )
			.fail( function () {
				window.alert( 'An error occurred. Please try again.' );
				$btn.prop( 'disabled', false ).removeClass( 'is-loading' );
			} );
		}
	};

	$( document ).ready( function () {
		BCGDashboard.init();
	} );

})( jQuery );
