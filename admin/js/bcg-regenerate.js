/**
 * Campaign Editor — Step 2 JS
 *
 * Manages the campaign editor interface including:
 * - Regeneration of individual campaign and product fields via AJAX
 * - Product card sorting (jQuery UI Sortable)
 * - Live preview iframe updates (debounced)
 * - Add Product modal with search
 * - Save Draft
 * - Create in Brevo / Schedule / Send Now
 * - Send Test Email
 * - Media uploader for custom images
 * - Device toggle for preview (desktop / mobile)
 *
 * Depends on: jQuery, jQuery UI Sortable, jQuery UI Datepicker, wp-util.
 * Localised data available via: bcg_editor (ajax_url, nonce, campaign_id, i18n).
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

/* global jQuery, bcg_editor, wp */
;(function( $, editor ) {
	'use strict';

	if ( typeof editor === 'undefined' ) {
		return;
	}

	/* =====================================================================
	   STATE
	   ===================================================================== */

	/**
	 * Debounce timer for preview updates.
	 *
	 * @type {number|null}
	 */
	var previewTimer = null;

	/**
	 * Debounce delay in milliseconds for live preview updates.
	 *
	 * @type {number}
	 */
	var PREVIEW_DEBOUNCE = 300;

	/**
	 * Debounce timer for product search.
	 *
	 * @type {number|null}
	 */
	var searchTimer = null;

	/**
	 * Search debounce delay in milliseconds.
	 *
	 * @type {number}
	 */
	var SEARCH_DEBOUNCE = 400;

	/* =====================================================================
	   INITIALISATION
	   ===================================================================== */

	$( document ).ready( function() {
		initSortable();
		initDatepickers();
		bindRegenButtons();
		bindFieldChanges();
		bindActionButtons();
		bindProductModal();
		bindDeviceToggle();
		bindMediaUploader();
		bindImageSourceRadios();
		bindRemoveProduct();
		bindModalClose();

		// Initial preview load.
		refreshPreview();
	});

	/* =====================================================================
	   SORTABLE — Product Reordering
	   ===================================================================== */

	/**
	 * Initialise jQuery UI Sortable on the products container.
	 *
	 * @return {void}
	 */
	function initSortable() {
		$( '#bcg-products-sortable' ).sortable({
			handle: '.bcg-drag-handle',
			items: '> .bcg-product-card',
			placeholder: 'bcg-sortable-placeholder',
			tolerance: 'pointer',
			cursor: 'grabbing',
			opacity: 0.7,
			stop: function() {
				saveProductOrder();
			}
		});
	}

	/**
	 * Save the current product sort order via AJAX.
	 *
	 * @return {void}
	 */
	function saveProductOrder() {
		var orderedIds = [];
		$( '#bcg-products-sortable .bcg-product-card' ).each( function() {
			orderedIds.push( $( this ).data( 'product-row-id' ) );
		});

		if ( orderedIds.length === 0 ) {
			return;
		}

		$.post( editor.ajax_url, {
			action:      'bcg_save_campaign',
			_ajax_nonce: editor.nonce,
			campaign_id: editor.campaign_id,
			product_order: orderedIds
		}).done( function( response ) {
			if ( response.success ) {
				showNotice( 'success', editor.i18n.order_saved );
			} else {
				showNotice( 'error', response.data || editor.i18n.save_error );
			}
		}).fail( function() {
			showNotice( 'error', editor.i18n.save_error );
		});
	}

	/* =====================================================================
	   DATEPICKERS
	   ===================================================================== */

	/**
	 * Initialise jQuery UI Datepicker on .bcg-datepicker fields.
	 *
	 * @return {void}
	 */
	function initDatepickers() {
		$( '.bcg-datepicker' ).datepicker({
			dateFormat: 'yy-mm-dd',
			minDate: 0,
			changeMonth: true,
			changeYear: true
		});
	}

	/* =====================================================================
	   REGENERATE BUTTONS — Campaign Fields
	   ===================================================================== */

	/**
	 * Bind click handlers to all regeneration buttons.
	 *
	 * @return {void}
	 */
	function bindRegenButtons() {
		// Campaign-level field regeneration.
		$( document ).on( 'click', '.bcg-regen-field', function( e ) {
			e.preventDefault();
			var $btn = $( this );
			var field = $btn.data( 'field' );

			if ( ! field || $btn.hasClass( 'is-loading' ) ) {
				return;
			}

			regenerateCampaignField( $btn, field );
		});

		// Product-level field regeneration.
		$( document ).on( 'click', '.bcg-regen-product-field', function( e ) {
			e.preventDefault();
			var $btn = $( this );
			var productRowId = $btn.data( 'product-row-id' );
			var field = $btn.data( 'field' );

			if ( ! field || ! productRowId || $btn.hasClass( 'is-loading' ) ) {
				return;
			}

			regenerateProductField( $btn, productRowId, field );
		});

		// Product image regeneration.
		$( document ).on( 'click', '.bcg-regen-product-image', function( e ) {
			e.preventDefault();
			var $btn = $( this );
			var productRowId = $btn.data( 'product-row-id' );

			if ( ! productRowId || $btn.hasClass( 'is-loading' ) ) {
				return;
			}

			regenerateProductField( $btn, productRowId, 'product_image' );
		});

		// Coupon code regeneration.
		$( document ).on( 'click', '.bcg-regenerate-coupon-code', function( e ) {
			e.preventDefault();
			var $btn = $( this );

			if ( $btn.hasClass( 'is-loading' ) ) {
				return;
			}

			regenerateCouponCode( $btn );
		});
	}

	/**
	 * Regenerate a campaign-level field via AJAX.
	 *
	 * @param {jQuery} $btn  The button element.
	 * @param {string} field The field name to regenerate.
	 * @return {void}
	 */
	function regenerateCampaignField( $btn, field ) {
		setButtonLoading( $btn, true );

		$.post( editor.ajax_url, {
			action:      'bcg_regenerate_field',
			_ajax_nonce: editor.nonce,
			campaign_id: editor.campaign_id,
			field:       field
		}).done( function( response ) {
			if ( response.success && response.data ) {
				var content = response.data.content || '';

				// Map the field to the correct input element.
				switch ( field ) {
					case 'subject_line':
						$( '#bcg-subject' ).val( content ).trigger( 'change' );
						break;
					case 'preview_text':
						$( '#bcg-preview-text' ).val( content ).trigger( 'change' );
						break;
					case 'main_headline':
						$( '#bcg-main-headline' ).val( content ).trigger( 'change' );
						break;
					case 'main_description':
						$( '#bcg-main-description' ).val( content ).trigger( 'change' );
						break;
					case 'main_image':
						if ( content ) {
							$( '#bcg-main-image-img' ).attr( 'src', content );
							$( '#bcg-main-image-url' ).val( content ).trigger( 'change' );
						}
						break;
					case 'coupon_suggestion':
						if ( typeof content === 'object' && content !== null ) {
							if ( content.value ) {
								$( '#bcg-coupon-discount' ).val( content.value );
							}
							if ( content.type ) {
								$( '#bcg-coupon-type' ).val( content.type );
							}
							if ( content.text ) {
								$( '#bcg-coupon-text' ).val( content.text );
							}
						}
						break;
				}

				refreshPreview();
			} else {
				showNotice( 'error', ( response.data && response.data.message ) || editor.i18n.regen_error );
			}
		}).fail( function() {
			showNotice( 'error', editor.i18n.regen_error );
		}).always( function() {
			setButtonLoading( $btn, false );
		});
	}

	/**
	 * Regenerate a product-level field via AJAX.
	 *
	 * @param {jQuery} $btn          The button element.
	 * @param {number} productRowId  The campaign product row ID.
	 * @param {string} field         The field name to regenerate.
	 * @return {void}
	 */
	function regenerateProductField( $btn, productRowId, field ) {
		setButtonLoading( $btn, true );

		$.post( editor.ajax_url, {
			action:         'bcg_regenerate_product',
			_ajax_nonce:    editor.nonce,
			campaign_id:    editor.campaign_id,
			product_row_id: productRowId,
			field:          field
		}).done( function( response ) {
			if ( response.success && response.data ) {
				var content = response.data.content || '';
				var $card = $( '.bcg-product-card[data-product-row-id="' + productRowId + '"]' );

				switch ( field ) {
					case 'product_headline':
						$card.find( '.bcg-product-headline' ).val( content ).trigger( 'change' );
						break;
					case 'product_short_desc':
						$card.find( '.bcg-product-shortdesc' ).val( content ).trigger( 'change' );
						break;
					case 'product_image':
						if ( content ) {
							var $img = $card.find( '.bcg-product-card-img' );
							$img.attr( 'src', content );
							$img.data( 'ai-image', content );
							$img.attr( 'data-ai-image', content );

							// Switch to AI image source.
							$card.find( '.bcg-image-source-radio[value="ai"]' ).prop( 'checked', true ).trigger( 'change' );
						}
						break;
				}

				refreshPreview();
			} else {
				showNotice( 'error', ( response.data && response.data.message ) || editor.i18n.regen_error );
			}
		}).fail( function() {
			showNotice( 'error', editor.i18n.regen_error );
		}).always( function() {
			setButtonLoading( $btn, false );
		});
	}

	/**
	 * Regenerate the coupon code via AJAX.
	 *
	 * @param {jQuery} $btn The button element.
	 * @return {void}
	 */
	function regenerateCouponCode( $btn ) {
		setButtonLoading( $btn, true );

		$.post( editor.ajax_url, {
			action:      'bcg_generate_coupon',
			_ajax_nonce: editor.nonce,
			campaign_id: editor.campaign_id
		}).done( function( response ) {
			if ( response.success && response.data && response.data.coupon_code ) {
				$( '#bcg-coupon-code' ).val( response.data.coupon_code ).trigger( 'change' );
				refreshPreview();
			} else {
				showNotice( 'error', ( response.data && response.data.message ) || editor.i18n.regen_error );
			}
		}).fail( function() {
			showNotice( 'error', editor.i18n.regen_error );
		}).always( function() {
			setButtonLoading( $btn, false );
		});
	}

	/* =====================================================================
	   FIELD CHANGES — Live Preview Trigger
	   ===================================================================== */

	/**
	 * Bind change/input events on editable fields to trigger a debounced
	 * live preview update.
	 *
	 * @return {void}
	 */
	function bindFieldChanges() {
		$( document ).on( 'input change', '.bcg-campaign-field, .bcg-coupon-field, .bcg-coupon-text-field, .bcg-product-headline, .bcg-product-shortdesc, .bcg-product-show-buy-btn', function() {
			debouncedPreview();
		});
	}

	/* =====================================================================
	   IMAGE SOURCE RADIOS
	   ===================================================================== */

	/**
	 * Bind change events on image source radio buttons.
	 * Switches the displayed image between product image and AI image,
	 * and shows/hides the Regenerate Image button.
	 *
	 * @return {void}
	 */
	function bindImageSourceRadios() {
		$( document ).on( 'change', '.bcg-image-source-radio', function() {
			var $radio = $( this );
			var source = $radio.val();
			var productRowId = $radio.data( 'product-row-id' );
			var $card = $( '.bcg-product-card[data-product-row-id="' + productRowId + '"]' );
			var $img = $card.find( '.bcg-product-card-img' );
			var $regenBtn = $card.find( '.bcg-regen-product-image' );

			if ( source === 'product' ) {
				$img.attr( 'src', $img.data( 'wc-image' ) || $img.attr( 'src' ) );
				$regenBtn.hide();
			} else {
				var aiImage = $img.data( 'ai-image' );
				if ( aiImage ) {
					$img.attr( 'src', aiImage );
				}
				$regenBtn.show();
			}

			// Update the product row via save.
			debouncedPreview();
		});
	}

	/* =====================================================================
	   REMOVE PRODUCT
	   ===================================================================== */

	/**
	 * Bind click handler for product removal.
	 *
	 * @return {void}
	 */
	function bindRemoveProduct() {
		$( document ).on( 'click', '.bcg-remove-product', function( e ) {
			e.preventDefault();

			if ( ! confirm( editor.i18n.confirm_remove ) ) {
				return;
			}

			var $btn = $( this );
			var productRowId = $btn.data( 'product-row-id' );
			var $card = $btn.closest( '.bcg-product-card' );

			$card.css( 'opacity', 0.5 );

			$.post( editor.ajax_url, {
				action:         'bcg_save_campaign',
				_ajax_nonce:    editor.nonce,
				campaign_id:    editor.campaign_id,
				remove_product: productRowId
			}).done( function( response ) {
				if ( response.success ) {
					$card.slideUp( 200, function() {
						$card.remove();
						updateProductCount();
						refreshPreview();
					});
				} else {
					$card.css( 'opacity', 1 );
					showNotice( 'error', response.data || editor.i18n.save_error );
				}
			}).fail( function() {
				$card.css( 'opacity', 1 );
				showNotice( 'error', editor.i18n.save_error );
			});
		});
	}

	/* =====================================================================
	   ACTION BUTTONS — Save, Preview, Send Test, Brevo, Schedule, Send
	   ===================================================================== */

	/**
	 * Bind all action bar button handlers.
	 *
	 * @return {void}
	 */
	function bindActionButtons() {
		// Save Draft.
		$( '#bcg-save-draft' ).on( 'click', handleSaveDraft );

		// Preview Email (fullscreen modal).
		$( '#bcg-preview-email' ).on( 'click', handlePreviewEmail );

		// Send Test Email (opens modal).
		$( '#bcg-send-test' ).on( 'click', function() {
			openModal( '#bcg-test-email-modal' );
		});

		// Confirm Send Test Email.
		$( '#bcg-confirm-send-test' ).on( 'click', handleSendTest );

		// Create in Brevo.
		$( '#bcg-create-brevo' ).on( 'click', handleCreateBrevo );

		// Schedule Campaign (opens modal).
		$( '#bcg-schedule-campaign' ).on( 'click', function() {
			openModal( '#bcg-schedule-modal' );
		});

		// Confirm Schedule.
		$( '#bcg-confirm-schedule' ).on( 'click', handleSchedule );

		// Send Now (opens confirmation modal).
		$( '#bcg-send-now' ).on( 'click', function() {
			openModal( '#bcg-send-modal' );
		});

		// Confirm Send.
		$( '#bcg-confirm-send' ).on( 'click', handleSendNow );
	}

	/**
	 * Gather all campaign field values from the editor into a data object.
	 *
	 * @return {Object} Campaign data suitable for AJAX submission.
	 */
	function gatherCampaignData() {
		var data = {
			action:           'bcg_save_campaign',
			_ajax_nonce:      editor.nonce,
			campaign_id:      editor.campaign_id,
			subject:          $( '#bcg-subject' ).val() || '',
			preview_text:     $( '#bcg-preview-text' ).val() || '',
			main_headline:    $( '#bcg-main-headline' ).val() || '',
			main_description: $( '#bcg-main-description' ).val() || '',
			main_image_url:   $( '#bcg-main-image-url' ).val() || '',
			coupon_code:      $( '#bcg-coupon-code' ).val() || '',
			coupon_discount:  $( '#bcg-coupon-discount' ).val() || '0',
			coupon_type:      $( '#bcg-coupon-type' ).val() || 'percent',
			products:         []
		};

		// Gather per-product data.
		$( '#bcg-products-sortable .bcg-product-card' ).each( function( index ) {
			var $card = $( this );
			var productRowId = $card.data( 'product-row-id' );
			var imageSource = $card.find( '.bcg-image-source-radio:checked' ).val() || 'product';

			data.products.push({
				row_id:           productRowId,
				sort_order:       index,
				custom_headline:  $card.find( '.bcg-product-headline' ).val() || '',
				custom_short_desc: $card.find( '.bcg-product-shortdesc' ).val() || '',
				use_product_image: imageSource === 'product' ? 1 : 0,
				show_buy_button:  $card.find( '.bcg-product-show-buy-btn' ).is( ':checked' ) ? 1 : 0
			});
		});

		return data;
	}

	/**
	 * Handle Save Draft.
	 *
	 * @param {Event} e Click event.
	 * @return {void}
	 */
	function handleSaveDraft( e ) {
		e.preventDefault();

		var $btn = $( '#bcg-save-draft' );
		if ( $btn.hasClass( 'is-loading' ) ) {
			return;
		}

		setButtonLoading( $btn, true );
		var data = gatherCampaignData();

		$.post( editor.ajax_url, data ).done( function( response ) {
			if ( response.success ) {
				showNotice( 'success', editor.i18n.saved );
			} else {
				showNotice( 'error', response.data || editor.i18n.save_error );
			}
		}).fail( function() {
			showNotice( 'error', editor.i18n.save_error );
		}).always( function() {
			setButtonLoading( $btn, false );
		});
	}

	/**
	 * Handle Preview Email in fullscreen modal.
	 *
	 * @param {Event} e Click event.
	 * @return {void}
	 */
	function handlePreviewEmail( e ) {
		e.preventDefault();

		openModal( '#bcg-preview-modal' );

		// Save the current state first, then request the rendered preview.
		var saveData = gatherCampaignData();

		$.post( editor.ajax_url, saveData ).done( function() {
			$.post( editor.ajax_url, {
				action:      'bcg_preview_template',
				_ajax_nonce: editor.nonce,
				campaign_id: editor.campaign_id
			}).done( function( response ) {
				if ( response.success && response.data && response.data.html ) {
					writeToIframe( '#bcg-fullscreen-preview-iframe', response.data.html );
				}
			});
		});
	}

	/**
	 * Handle Send Test Email (from modal confirmation).
	 *
	 * @param {Event} e Click event.
	 * @return {void}
	 */
	function handleSendTest( e ) {
		e.preventDefault();

		var $btn = $( '#bcg-confirm-send-test' );
		if ( $btn.hasClass( 'is-loading' ) ) {
			return;
		}

		var testEmail = $( '#bcg-test-email-address' ).val().trim();
		if ( ! testEmail ) {
			showNotice( 'error', 'Please enter an email address.' );
			return;
		}

		setButtonLoading( $btn, true );

		// Save first, then send.
		var saveData = gatherCampaignData();
		$.post( editor.ajax_url, saveData ).done( function() {
			// Now send the test email.
			$.post( editor.ajax_url, {
				action:      'bcg_send_test',
				_ajax_nonce: editor.nonce,
				campaign_id: editor.campaign_id,
				email:       testEmail
			}).done( function( response ) {
				if ( response.success ) {
					showNotice( 'success', ( response.data && response.data.message ) || editor.i18n.test_sent );
					closeAllModals();
				} else {
					showNotice( 'error', ( response.data && response.data.message ) || editor.i18n.save_error );
				}
			}).fail( function() {
				showNotice( 'error', editor.i18n.save_error );
			}).always( function() {
				setButtonLoading( $btn, false );
			});
		}).fail( function() {
			showNotice( 'error', editor.i18n.save_error );
			setButtonLoading( $btn, false );
		});
	}

	/**
	 * Handle Create in Brevo.
	 *
	 * @param {Event} e Click event.
	 * @return {void}
	 */
	function handleCreateBrevo( e ) {
		e.preventDefault();

		var $btn = $( '#bcg-create-brevo' );
		if ( $btn.hasClass( 'is-loading' ) ) {
			return;
		}

		setButtonLoading( $btn, true );

		// Save first, then push to Brevo.
		var saveData = gatherCampaignData();
		$.post( editor.ajax_url, saveData ).done( function() {
			$.post( editor.ajax_url, {
				action:      'bcg_create_brevo_campaign',
				_ajax_nonce: editor.nonce,
				campaign_id: editor.campaign_id
			}).done( function( response ) {
				if ( response.success ) {
					var msg = editor.i18n.brevo_created;
					if ( response.data && response.data.brevo_url ) {
						msg += ' <a href="' + response.data.brevo_url + '" target="_blank" rel="noopener noreferrer">' +
							'View in Brevo &rarr;</a>';
					}
					showNotice( 'success', msg, true );
				} else {
					showNotice( 'error', ( response.data && response.data.message ) || editor.i18n.save_error );
				}
			}).fail( function() {
				showNotice( 'error', editor.i18n.save_error );
			}).always( function() {
				setButtonLoading( $btn, false );
			});
		}).fail( function() {
			showNotice( 'error', editor.i18n.save_error );
			setButtonLoading( $btn, false );
		});
	}

	/**
	 * Handle Schedule Campaign.
	 *
	 * @return {void}
	 */
	function handleSchedule() {
		var $btn = $( '#bcg-confirm-schedule' );
		if ( $btn.hasClass( 'is-loading' ) ) {
			return;
		}

		var date = $( '#bcg-schedule-date' ).val();
		var time = $( '#bcg-schedule-time' ).val();

		if ( ! date || ! time ) {
			showNotice( 'error', editor.i18n.select_datetime );
			return;
		}

		var datetime = date + 'T' + time + ':00';

		setButtonLoading( $btn, true );

		// Save first, then schedule.
		var saveData = gatherCampaignData();
		$.post( editor.ajax_url, saveData ).done( function() {
			$.post( editor.ajax_url, {
				action:       'bcg_schedule_campaign',
				_ajax_nonce:  editor.nonce,
				campaign_id:  editor.campaign_id,
				scheduled_at: datetime
			}).done( function( response ) {
				if ( response.success ) {
					showNotice( 'success', editor.i18n.scheduled );
					closeAllModals();
					updateStatusBadge( 'scheduled' );
				} else {
					showNotice( 'error', ( response.data && response.data.message ) || editor.i18n.save_error );
				}
			}).fail( function() {
				showNotice( 'error', editor.i18n.save_error );
			}).always( function() {
				setButtonLoading( $btn, false );
			});
		}).fail( function() {
			showNotice( 'error', editor.i18n.save_error );
			setButtonLoading( $btn, false );
		});
	}

	/**
	 * Handle Send Now.
	 *
	 * @return {void}
	 */
	function handleSendNow() {
		var $btn = $( '#bcg-confirm-send' );
		if ( $btn.hasClass( 'is-loading' ) ) {
			return;
		}

		setButtonLoading( $btn, true );

		// Save first, then send.
		var saveData = gatherCampaignData();
		$.post( editor.ajax_url, saveData ).done( function() {
			$.post( editor.ajax_url, {
				action:      'bcg_send_campaign',
				_ajax_nonce: editor.nonce,
				campaign_id: editor.campaign_id
			}).done( function( response ) {
				if ( response.success ) {
					showNotice( 'success', editor.i18n.campaign_sent );
					closeAllModals();
					updateStatusBadge( 'sent' );
				} else {
					showNotice( 'error', ( response.data && response.data.message ) || editor.i18n.save_error );
				}
			}).fail( function() {
				showNotice( 'error', editor.i18n.save_error );
			}).always( function() {
				setButtonLoading( $btn, false );
			});
		}).fail( function() {
			showNotice( 'error', editor.i18n.save_error );
			setButtonLoading( $btn, false );
		});
	}

	/* =====================================================================
	   LIVE PREVIEW — Iframe Updates
	   ===================================================================== */

	/**
	 * Debounced wrapper for refreshPreview.
	 *
	 * @return {void}
	 */
	function debouncedPreview() {
		clearTimeout( previewTimer );
		previewTimer = setTimeout( refreshPreview, PREVIEW_DEBOUNCE );
	}

	/**
	 * Refresh the live preview iframe by saving the current campaign data
	 * first, then requesting a rendered preview from the server.
	 *
	 * @return {void}
	 */
	function refreshPreview() {
		var $loading = $( '#bcg-preview-loading' );
		$loading.show();

		// Save the current state first so the preview reflects edits.
		var saveData = gatherCampaignData();

		$.post( editor.ajax_url, saveData ).done( function() {
			// Now request the rendered preview using the saved campaign.
			$.post( editor.ajax_url, {
				action:      'bcg_preview_template',
				_ajax_nonce: editor.nonce,
				campaign_id: editor.campaign_id
			}).done( function( response ) {
				if ( response.success && response.data && response.data.html ) {
					writeToIframe( '#bcg-preview-iframe', response.data.html );
				}
			}).always( function() {
				$loading.hide();
			});
		}).fail( function() {
			$loading.hide();
		});
	}

	/**
	 * Write HTML content to an iframe by selector.
	 *
	 * @param {string} selector The iframe jQuery selector.
	 * @param {string} html     The HTML content.
	 * @return {void}
	 */
	function writeToIframe( selector, html ) {
		var $iframe = $( selector );
		if ( ! $iframe.length ) {
			return;
		}

		var iframeDoc = $iframe[0].contentWindow || $iframe[0].contentDocument;
		if ( iframeDoc.document ) {
			iframeDoc = iframeDoc.document;
		}

		iframeDoc.open();
		iframeDoc.write( html );
		iframeDoc.close();
	}

	/* =====================================================================
	   DEVICE TOGGLE — Desktop / Mobile Preview
	   ===================================================================== */

	/**
	 * Bind click handlers for preview device toggle buttons.
	 *
	 * @return {void}
	 */
	function bindDeviceToggle() {
		$( '.bcg-preview-device-btn' ).on( 'click', function() {
			var $btn    = $( this );
			var device  = $btn.data( 'device' );
			var $iframe = $( '#bcg-preview-iframe' );

			$( '.bcg-preview-device-btn' ).removeClass( 'active' );
			$btn.addClass( 'active' );

			if ( device === 'mobile' ) {
				$iframe.addClass( 'bcg-preview-mobile' );
			} else {
				$iframe.removeClass( 'bcg-preview-mobile' );
			}
		} );
	}

	/* =====================================================================
	   MEDIA UPLOADER — Custom Image
	   ===================================================================== */

	/**
	 * Bind the media uploader for the main campaign image.
	 *
	 * @return {void}
	 */
	function bindMediaUploader() {
		$( '#bcg-upload-main-image' ).on( 'click', function( e ) {
			e.preventDefault();

			var mediaFrame = wp.media({
				title: 'Select Campaign Image',
				button: { text: 'Use This Image' },
				multiple: false,
				library: { type: 'image' }
			});

			mediaFrame.on( 'select', function() {
				var attachment = mediaFrame.state().get( 'selection' ).first().toJSON();

				if ( attachment && attachment.url ) {
					$( '#bcg-main-image-img' ).attr( 'src', attachment.url );
					$( '#bcg-main-image-url' ).val( attachment.url ).trigger( 'change' );
					refreshPreview();
				}
			});

			mediaFrame.open();
		});
	}

	/* =====================================================================
	   ADD PRODUCT MODAL
	   ===================================================================== */

	/**
	 * Bind the Add Product modal: open button, search input, result clicks.
	 *
	 * @return {void}
	 */
	function bindProductModal() {
		// Open modal.
		$( '#bcg-add-product-btn' ).on( 'click', function() {
			$( '#bcg-product-search' ).val( '' );
			$( '#bcg-product-search-results' ).html(
				'<p class="bcg-text-muted bcg-text-center">' +
				'Start typing to search for products.' +
				'</p>'
			);
			openModal( '#bcg-add-product-modal' );
			setTimeout( function() {
				$( '#bcg-product-search' ).trigger( 'focus' );
			}, 100 );
		});

		// Search input (debounced).
		$( '#bcg-product-search' ).on( 'input', function() {
			var keyword = $( this ).val().trim();

			clearTimeout( searchTimer );

			if ( keyword.length < 2 ) {
				$( '#bcg-product-search-results' ).html(
					'<p class="bcg-text-muted bcg-text-center">' +
					'Type at least 2 characters to search.' +
					'</p>'
				);
				return;
			}

			$( '#bcg-product-search-results' ).html(
				'<p class="bcg-text-muted bcg-text-center">' +
				'<span class="bcg-spinner bcg-spinner-small"></span> ' +
				editor.i18n.searching +
				'</p>'
			);

			searchTimer = setTimeout( function() {
				searchProducts( keyword );
			}, SEARCH_DEBOUNCE );
		});

		// Click a search result to add the product.
		$( document ).on( 'click', '.bcg-product-search-item', function( e ) {
			e.preventDefault();

			var $item = $( this );
			if ( $item.hasClass( 'is-loading' ) ) {
				return;
			}

			var productId = $item.data( 'product-id' );
			addProductToCampaign( $item, productId );
		});
	}

	/**
	 * Search for WooCommerce products via AJAX.
	 *
	 * @param {string} keyword The search keyword.
	 * @return {void}
	 */
	function searchProducts( keyword ) {
		$.post( editor.ajax_url, {
			action:      'bcg_preview_products',
			_ajax_nonce: editor.nonce,
			search:      keyword,
			source:      'manual'
		}).done( function( response ) {
			if ( response.success && response.data && response.data.length > 0 ) {
				renderSearchResults( response.data );
			} else {
				$( '#bcg-product-search-results' ).html(
					'<p class="bcg-text-muted bcg-text-center">' +
					editor.i18n.no_results +
					'</p>'
				);
			}
		}).fail( function() {
			$( '#bcg-product-search-results' ).html(
				'<p class="bcg-text-error bcg-text-center">Search failed. Please try again.</p>'
			);
		});
	}

	/**
	 * Render product search results as clickable items.
	 *
	 * @param {Array} products Array of product data objects.
	 * @return {void}
	 */
	function renderSearchResults( products ) {
		var html = '';

		for ( var i = 0; i < products.length; i++ ) {
			var p = products[i];
			var imgUrl = p.image_url || '';
			var priceHtml = p.price_html || '';

			html += '<div class="bcg-product-search-item bcg-flex bcg-items-center bcg-gap-12" ' +
				'data-product-id="' + parseInt( p.id, 10 ) + '">';

			if ( imgUrl ) {
				html += '<img src="' + escHtml( imgUrl ) + '" alt="" class="bcg-search-item-img" />';
			}

			html += '<div class="bcg-search-item-info">';
			html += '<strong class="bcg-search-item-name">' + escHtml( p.name || '' ) + '</strong>';
			if ( priceHtml ) {
				html += '<span class="bcg-search-item-price bcg-text-muted"> &mdash; ' + priceHtml + '</span>';
			}
			if ( p.category ) {
				html += '<br/><span class="bcg-text-small bcg-text-muted">' + escHtml( p.category ) + '</span>';
			}
			html += '</div>';
			html += '<span class="bcg-search-item-add dashicons dashicons-plus-alt2"></span>';
			html += '</div>';
		}

		$( '#bcg-product-search-results' ).html( html );
	}

	/**
	 * Add a product to the campaign via AJAX, then insert the product card.
	 *
	 * @param {jQuery} $item     The search result element.
	 * @param {number} productId The WooCommerce product ID.
	 * @return {void}
	 */
	function addProductToCampaign( $item, productId ) {
		$item.addClass( 'is-loading' );
		$item.find( '.bcg-search-item-add' )
			.removeClass( 'dashicons-plus-alt2' )
			.addClass( 'bcg-spinner bcg-spinner-small' );

		$.post( editor.ajax_url, {
			action:      'bcg_add_product',
			_ajax_nonce: editor.nonce,
			campaign_id: editor.campaign_id,
			product_id:  productId
		}).done( function( response ) {
			if ( response.success && response.data && response.data.card_html ) {
				// Remove the empty state notice if present.
				$( '#bcg-no-products' ).remove();

				// Append the new product card.
				$( '#bcg-products-sortable' ).append( response.data.card_html );

				// Re-initialise sortable.
				$( '#bcg-products-sortable' ).sortable( 'refresh' );

				updateProductCount();
				refreshPreview();
				showNotice( 'success', editor.i18n.product_added );

				// Mark the item as added.
				$item.addClass( 'bcg-item-added' );
				$item.find( '.bcg-spinner' )
					.removeClass( 'bcg-spinner bcg-spinner-small' )
					.addClass( 'dashicons-yes-alt' );
			} else {
				showNotice( 'error', ( response.data && response.data.message ) || editor.i18n.save_error );
				$item.removeClass( 'is-loading' );
				$item.find( '.bcg-spinner' )
					.removeClass( 'bcg-spinner bcg-spinner-small' )
					.addClass( 'dashicons-plus-alt2' );
			}
		}).fail( function() {
			showNotice( 'error', editor.i18n.save_error );
			$item.removeClass( 'is-loading' );
			$item.find( '.bcg-spinner' )
				.removeClass( 'bcg-spinner bcg-spinner-small' )
				.addClass( 'dashicons-plus-alt2' );
		});
	}

	/* =====================================================================
	   MODALS
	   ===================================================================== */

	/**
	 * Open a modal by selector.
	 *
	 * @param {string} selector jQuery selector for the modal.
	 * @return {void}
	 */
	function openModal( selector ) {
		$( selector ).fadeIn( 150 );
		$( 'body' ).addClass( 'bcg-modal-open' );
	}

	/**
	 * Close all open modals.
	 *
	 * @return {void}
	 */
	function closeAllModals() {
		$( '.bcg-modal' ).fadeOut( 150 );
		$( 'body' ).removeClass( 'bcg-modal-open' );
	}

	/**
	 * Bind modal close events: close button, overlay click, Escape key.
	 *
	 * @return {void}
	 */
	function bindModalClose() {
		// Close button.
		$( document ).on( 'click', '.bcg-modal-close', function() {
			closeAllModals();
		});

		// Overlay click.
		$( document ).on( 'click', '.bcg-modal-overlay', function() {
			closeAllModals();
		});

		// Escape key.
		$( document ).on( 'keydown', function( e ) {
			if ( e.key === 'Escape' || e.keyCode === 27 ) {
				if ( $( '.bcg-modal:visible' ).length ) {
					closeAllModals();
				}
			}
		});
	}

	/* =====================================================================
	   UTILITY FUNCTIONS
	   ===================================================================== */

	/**
	 * Show a transient admin notice in the editor notices area.
	 *
	 * @param {string}  type      Notice type: 'success', 'error', 'warning', 'info'.
	 * @param {string}  message   The notice message (may contain HTML if allowHtml is true).
	 * @param {boolean} allowHtml Whether to treat message as HTML. Default false.
	 * @return {void}
	 */
	function showNotice( type, message, allowHtml ) {
		var $container = $( '#bcg-editor-notices' );
		var noticeClass = 'bcg-notice bcg-notice-' + type;
		var $notice;

		if ( allowHtml ) {
			$notice = $( '<div class="' + noticeClass + '"></div>' ).html( message );
		} else {
			$notice = $( '<div class="' + noticeClass + '"></div>' ).text( message );
		}

		$container.prepend( $notice );

		// Auto-dismiss after 5 seconds.
		setTimeout( function() {
			$notice.fadeOut( 300, function() {
				$notice.remove();
			});
		}, 5000 );
	}

	/**
	 * Set or remove the loading state on a button.
	 *
	 * @param {jQuery}  $btn    The button element.
	 * @param {boolean} loading Whether to set loading state.
	 * @return {void}
	 */
	function setButtonLoading( $btn, loading ) {
		if ( loading ) {
			$btn.addClass( 'is-loading' ).prop( 'disabled', true );
			if ( ! $btn.find( '.bcg-btn-spinner' ).length ) {
				$btn.prepend( '<span class="bcg-btn-spinner"></span>' );
			}
		} else {
			$btn.removeClass( 'is-loading' ).prop( 'disabled', false );
			$btn.find( '.bcg-btn-spinner' ).remove();
		}
	}

	/**
	 * Update the product count display.
	 *
	 * @return {void}
	 */
	function updateProductCount() {
		var count = $( '#bcg-products-sortable .bcg-product-card' ).length;
		$( '.bcg-product-count' ).text( '(' + count + ')' );
	}

	/**
	 * Update the status badge text and class.
	 *
	 * @param {string} status The new status.
	 * @return {void}
	 */
	function updateStatusBadge( status ) {
		var labels = {
			draft: 'Draft',
			ready: 'Ready',
			sent: 'Sent',
			scheduled: 'Scheduled'
		};

		var $badge = $( '.bcg-status-badge' );
		$badge.removeClass( 'bcg-status-draft bcg-status-ready bcg-status-sent bcg-status-scheduled' );
		$badge.addClass( 'bcg-status-' + status );
		$badge.text( labels[ status ] || status );
	}

	/**
	 * Simple HTML entity escaping for use in building HTML strings.
	 *
	 * @param {string} str The raw string.
	 * @return {string} The escaped string.
	 */
	/**
	 * Handle template strip card click on the editor page.
	 *
	 * Saves the new template_slug and refreshes the preview.
	 */
	$( '#bcg-editor-template-strip' ).on( 'click', '.bcg-template-mini-card', function() {
		var $card   = $( this );
		var newSlug = $card.data( 'slug' );

		if ( $card.hasClass( 'bcg-template-card-active' ) ) {
			return;
		}

		// Update active state.
		$( '#bcg-editor-template-strip .bcg-template-mini-card' ).removeClass( 'bcg-template-card-active' );
		$card.addClass( 'bcg-template-card-active' );

		// Save with new template_slug (triggers template HTML/settings update on server).
		var data = gatherCampaignData();
		data.template_slug = newSlug;

		$.post( editor.ajax_url, data ).done( function( response ) {
			if ( response.success ) {
				refreshPreview();
				showNotice( 'success', editor.i18n.saved );
			} else {
				showNotice( 'error', response.data || editor.i18n.save_error );
			}
		}).fail( function() {
			showNotice( 'error', editor.i18n.save_error );
		});
	} );

	function escHtml( str ) {
		if ( typeof str !== 'string' ) {
			return '';
		}
		return str
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}

})( jQuery, typeof bcg_editor !== 'undefined' ? bcg_editor : undefined );
