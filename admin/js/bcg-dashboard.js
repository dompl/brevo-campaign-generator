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
