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

						// Rebuild the custom dropdown UI after AJAX repopulation.
						if ( typeof window.bcgRebuildCustomSelect === 'function' ) {
							window.bcgRebuildCustomSelect( $select );
						}
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

	// Initialise on DOM ready — only on the settings page where bcg_settings is localised.
	$( function() {
		if ( typeof bcg_settings !== 'undefined' ) {
			BCGSettings.init();
		}
	} );

} )( jQuery );

// ─── Global custom select initialisation ──────────────────────────────────────
// Runs on all BCG admin pages (outside the main IIFE so it is globally accessible).
/* global jQuery */
( function( $ ) {
	'use strict';

	/**
	 * Initialise the fancy custom select UI for all .bcg-select-styled elements.
	 *
	 * Safe to call multiple times — a data flag prevents double-building.
	 *
	 * @param {jQuery} $scope Optional jQuery scope (defaults to document).
	 */
	window.bcgInitCustomSelects = function( $scope ) {
		var $root = $scope || $( document );
		$root.find( 'select.bcg-select-styled' ).each( function() {
			var $select = $( this );

			// Skip if already built.
			if ( $select.data( 'bcg-custom-built' ) ) {
				return;
			}
			$select.data( 'bcg-custom-built', true );

			var selectedVal  = $select.val();
			var selectedText = $select.find( 'option:selected' ).text().trim();

			var $wrapper = $( '<div class="bcg-select-wrapper" style="display:inline-block;width:100%;position:relative;"></div>' );
			var $trigger = $(
				'<button type="button" class="bcg-select-trigger" aria-expanded="false" aria-haspopup="listbox">' +
				'<span class="bcg-select-value"></span>' +
				'<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>' +
				'</button>'
			);
			$trigger.find( '.bcg-select-value' ).text( selectedText );

			var $menu = $( '<div class="bcg-select-menu bcg-dropdown-closed" role="listbox"></div>' );
			$select.find( 'option' ).each( function() {
				var $opt    = $( this );
				var isChosen = ( $opt.val() === selectedVal );
				var $item   = $(
					'<button type="button" class="bcg-select-option' + ( isChosen ? ' is-selected' : '' ) + '" ' +
					'data-value="' + $opt.val() + '" role="option" aria-selected="' + String( isChosen ) + '">' +
					$opt.text().trim() + '</button>'
				);
				$menu.append( $item );
			} );

			$select.hide().wrap( $wrapper );
			$select.parent().append( $trigger ).append( $menu );
		} );
	};

	/**
	 * Destroy and rebuild the custom dropdown UI for a given native select.
	 *
	 * Used after AJAX repopulates select options (e.g. Brevo lists / senders).
	 *
	 * @param {jQuery} $select The native <select> element to rebuild for.
	 */
	window.bcgRebuildCustomSelect = function( $select ) {
		if ( ! $select.data( 'bcg-custom-built' ) ) {
			// Not yet built — just initialise normally.
			window.bcgInitCustomSelects( $select.closest( '.bcg-select-wrapper, :not(.bcg-select-wrapper)' ).parent() );
			return;
		}

		// Tear down.
		var $wrapper = $select.closest( '.bcg-select-wrapper' );
		$select.show().unwrap();
		$select.removeData( 'bcg-custom-built' );

		// Rebuild.
		window.bcgInitCustomSelects( $select.parent() );
	};

	// ── Event delegation: trigger button click ──────────────────────────────────
	$( document ).on( 'click', '.bcg-select-trigger', function( e ) {
		// Skip template editor selects — those are handled by bcg-template-editor.js.
		if ( $( this ).closest( '.bcg-template-settings-panel' ).length ) {
			return;
		}
		e.stopPropagation();
		var $trigger = $( this );
		var $menu    = $trigger.closest( '.bcg-select-wrapper' ).find( '.bcg-select-menu' );
		var isOpen   = ! $menu.hasClass( 'bcg-dropdown-closed' );

		// Close all open menus.
		$( '.bcg-select-menu' ).not( $menu ).addClass( 'bcg-dropdown-closed' );
		$( '.bcg-select-trigger' ).not( $trigger ).attr( 'aria-expanded', 'false' );

		if ( ! isOpen ) {
			var rect = $trigger[ 0 ].getBoundingClientRect();
			$menu.css( {
				position: 'fixed',
				top:      ( rect.bottom + 2 ) + 'px',
				left:     rect.left + 'px',
				width:    rect.width + 'px',
				'z-index': 99999
			} );
			$menu.removeClass( 'bcg-dropdown-closed' );
			$trigger.attr( 'aria-expanded', 'true' );
		} else {
			$menu.addClass( 'bcg-dropdown-closed' );
			$trigger.attr( 'aria-expanded', 'false' );
		}
	} );

	// ── Event delegation: option click ─────────────────────────────────────────
	$( document ).on( 'click', '.bcg-select-option', function( e ) {
		if ( $( this ).closest( '.bcg-template-settings-panel' ).length ) {
			return;
		}
		e.stopPropagation();
		var $opt     = $( this );
		var $wrapper = $opt.closest( '.bcg-select-wrapper' );
		var $trigger = $wrapper.find( '.bcg-select-trigger' );
		var $menu    = $wrapper.find( '.bcg-select-menu' );
		var value    = $opt.data( 'value' );
		var label    = $opt.text().trim();

		$wrapper.find( 'select.bcg-select-styled' ).val( value ).trigger( 'change' );
		$trigger.find( '.bcg-select-value' ).text( label );
		$menu.find( '.bcg-select-option' ).removeClass( 'is-selected' ).attr( 'aria-selected', 'false' );
		$opt.addClass( 'is-selected' ).attr( 'aria-selected', 'true' );
		$menu.addClass( 'bcg-dropdown-closed' );
		$trigger.attr( 'aria-expanded', 'false' );
	} );

	// ── Close on outside click ──────────────────────────────────────────────────
	$( document ).on( 'click', function( e ) {
		if ( ! $( e.target ).closest( '.bcg-select-wrapper' ).length ) {
			$( '.bcg-select-menu' ).addClass( 'bcg-dropdown-closed' );
			$( '.bcg-select-trigger' ).attr( 'aria-expanded', 'false' );
		}
	} );

	// ── Initialise on DOM ready ─────────────────────────────────────────────────
	$( function() {
		if ( $( 'body' ).hasClass( 'bcg-admin-page' ) || $( '.bcg-wrap' ).length ) {
			window.bcgInitCustomSelects( $( document ) );
		}
	} );

} )( jQuery );

// ── What's New popup ──────────────────────────────────────────────────────
$( function () {
	if ( typeof bcgData === 'undefined' || ! bcgData.whats_new ) { return; }

	var version    = bcgData.version || '';
	var whatsNew   = bcgData.whats_new;
	var $modal     = $( '#bcg-whats-new-modal' );
	var $list      = $( '#bcg-whats-new-list' );
	var STORAGE_KEY = 'bcg_dismissed_version';

	// Build list items from bcgData.whats_new.items.
	function populateList() {
		$list.empty();
		if ( whatsNew.items && whatsNew.items.length ) {
			$.each( whatsNew.items, function ( i, item ) {
				var icon = item.icon || 'check_circle';
				var text = item.text || '';
				$list.append(
					'<li class="bcg-whats-new-item">' +
					'<span class="material-icons-outlined bcg-whats-new-item-icon">' + icon + '</span>' +
					'<span class="bcg-whats-new-item-text">' + $( '<span>' ).text( text ).html() + '</span>' +
					'</li>'
				);
			} );
		}
	}

	function dismissModal() {
		try { localStorage.setItem( STORAGE_KEY, version ); } catch ( e ) {}
		$modal.hide();
	}

	function showModal() {
		populateList();
		$modal.show();
	}

	// Show automatically if this version hasn't been dismissed yet.
	var dismissed;
	try { dismissed = localStorage.getItem( STORAGE_KEY ); } catch ( e ) { dismissed = null; }
	if ( dismissed !== version && $modal.length ) {
		showModal();
	}

	// Dismiss handlers.
	$( document ).on( 'click', '#bcg-whats-new-close, #bcg-whats-new-dismiss, #bcg-whats-new-overlay', function () {
		dismissModal();
	} );

	// Version badge: always re-open.
	$( document ).on( 'click', '#bcg-version-badge', function () {
		showModal();
	} );
} );
