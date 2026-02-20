/**
 * Brevo Campaign Generator - Settings Page JavaScript
 *
 * Handles:
 * - Test Connection AJAX calls for each API key
 * - Brevo mailing list loading/refreshing
 * - Credit packs per-credit calculation
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

/* global jQuery, bcg_settings */
( function( $ ) {
	'use strict';

	/**
	 * Settings module.
	 */
	var BCGSettings = {

		/**
		 * Initialise event handlers.
		 */
		init: function() {
			this.bindTestConnection();
			this.bindBrevoListRefresh();
			this.bindCreditPackCalculation();
			this.autoLoadBrevoLists();
		},

		/**
		 * Bind click handlers for all Test Connection buttons.
		 */
		bindTestConnection: function() {
			$( document ).on( 'click', '.bcg-test-connection', function( e ) {
				e.preventDefault();

				var $button  = $( this );
				var service  = $button.data( 'service' );
				var $result  = $( '.bcg-test-result[data-service="' + service + '"]' );

				// Prevent double-clicks.
				if ( $button.hasClass( 'is-loading' ) ) {
					return;
				}

				// Reset and show loading state.
				$button.addClass( 'is-loading' );
				$result
					.removeClass( 'bcg-test-success bcg-test-error' )
					.addClass( 'bcg-test-loading' )
					.text( bcg_settings.i18n.testing );

				$.ajax( {
					url:  bcg_settings.ajax_url,
					type: 'POST',
					data: {
						action:  'bcg_test_api_key',
						nonce:   bcg_settings.nonce,
						service: service
					},
					success: function( response ) {
						$button.removeClass( 'is-loading' );
						$result.removeClass( 'bcg-test-loading' );

						if ( response.success ) {
							$result
								.addClass( 'bcg-test-success' )
								.text( response.data.message );
						} else {
							$result
								.addClass( 'bcg-test-error' )
								.text( response.data.message || bcg_settings.i18n.error );
						}
					},
					error: function( xhr, status, error ) {
						$button.removeClass( 'is-loading' );
						$result
							.removeClass( 'bcg-test-loading' )
							.addClass( 'bcg-test-error' )
							.text( bcg_settings.i18n.error + ' (' + error + ')' );
					}
				} );
			} );
		},

		/**
		 * Bind click handler for the Brevo list refresh button.
		 */
		bindBrevoListRefresh: function() {
			$( document ).on( 'click', '.bcg-refresh-brevo-lists', function( e ) {
				e.preventDefault();

				var $button = $( this );

				if ( $button.hasClass( 'is-loading' ) || $button.prop( 'disabled' ) ) {
					return;
				}

				BCGSettings.loadBrevoLists( $button );
			} );
		},

		/**
		 * Auto-load Brevo lists when the Brevo tab is displayed.
		 */
		autoLoadBrevoLists: function() {
			var $select = $( '.bcg-brevo-list-select' );
			var $button = $( '.bcg-refresh-brevo-lists' );

			// Only auto-load if we are on the Brevo tab and the button is enabled.
			if ( $select.length && ! $button.prop( 'disabled' ) ) {
				BCGSettings.loadBrevoLists( $button );
			}
		},

		/**
		 * Load Brevo mailing lists via AJAX.
		 *
		 * @param {jQuery} $button The refresh button element.
		 */
		loadBrevoLists: function( $button ) {
			var $select  = $( '.bcg-brevo-list-select' );
			var current  = $select.data( 'current' ) || '';

			$button.addClass( 'is-loading' ).text( bcg_settings.i18n.testing );

			$.ajax( {
				url:  bcg_settings.ajax_url,
				type: 'POST',
				data: {
					action: 'bcg_get_brevo_lists',
					nonce:  bcg_settings.nonce
				},
				success: function( response ) {
					$button.removeClass( 'is-loading' ).text(
						/* Reset button text. */
						$button.data( 'original-text' ) || 'Refresh Lists'
					);

					if ( response.success && response.data.lists ) {
						// Clear existing options.
						$select.empty();
						$select.append(
							$( '<option>' ).val( '' ).text( '-- Select a list --' )
						);

						// Populate with fetched lists.
						$.each( response.data.lists, function( i, list ) {
							var label = list.name + ' (' + list.totalSubscribers + ' subscribers)';
							var $option = $( '<option>' )
								.val( list.id )
								.text( label );

							// Re-select the current value.
							if ( String( list.id ) === String( current ) ) {
								$option.prop( 'selected', true );
							}

							$select.append( $option );
						} );
					} else {
						var msg = ( response.data && response.data.message ) ? response.data.message : 'Failed to load lists.';
						BCGSettings.showInlineNotice( $select.closest( '.bcg-brevo-list-wrapper' ), msg, 'error' );
					}
				},
				error: function() {
					$button.removeClass( 'is-loading' ).text( 'Refresh Lists' );
					BCGSettings.showInlineNotice(
						$select.closest( '.bcg-brevo-list-wrapper' ),
						'Network error while loading lists.',
						'error'
					);
				}
			} );
		},

		/**
		 * Bind input handlers on credit pack fields to recalculate per-credit cost.
		 */
		bindCreditPackCalculation: function() {
			$( document ).on( 'input', '.bcg-credit-packs-table input[type="number"]', function() {
				var $row     = $( this ).closest( 'tr' );
				var credits  = parseInt( $row.find( 'input[name*="[credits]"]' ).val(), 10 ) || 0;
				var price    = parseFloat( $row.find( 'input[name*="[price]"]' ).val() ) || 0;
				var perCredit = credits > 0 ? ( price / credits ).toFixed( 4 ) : '0.0000';

				// Get the currency symbol from the first cell that has it.
				var symbol = $row.find( '.bcg-currency-symbol' ).first().text() || '';

				$row.find( '.bcg-per-credit' ).text( symbol + perCredit );
			} );
		},

		/**
		 * Show a temporary inline notice.
		 *
		 * @param {jQuery} $container Parent element.
		 * @param {string} message    The message text.
		 * @param {string} type       Notice type: 'error', 'success', 'info'.
		 */
		showInlineNotice: function( $container, message, type ) {
			// Remove any existing inline notice.
			$container.find( '.bcg-inline-notice' ).remove();

			var cssClass = 'bcg-inline-notice bcg-notice-' + ( type || 'info' );
			var $notice  = $( '<p>' )
				.addClass( cssClass )
				.text( message )
				.css( {
					'flex-basis': '100%',
					'margin-top': '8px',
					'font-size':  '13px'
				} );

			$container.append( $notice );

			// Auto-remove after 8 seconds.
			setTimeout( function() {
				$notice.fadeOut( 300, function() {
					$notice.remove();
				} );
			}, 8000 );
		}
	};

	// Initialise on DOM ready.
	$( function() {
		BCGSettings.init();
	} );

} )( jQuery );
