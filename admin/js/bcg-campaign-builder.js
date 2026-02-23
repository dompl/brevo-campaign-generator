/**
 * Campaign Builder — Step 1 Wizard JS.
 *
 * Manages the New Campaign wizard interactions: product preview, AI field
 * generation (subject, preview text, coupon suggestion), mailing list
 * loading, manual product picker, category tree, image style toggle,
 * form validation, and the full generation pipeline with progress steps.
 *
 * Depends on jQuery and the bcg_campaign_builder localised object.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

/* global jQuery, bcg_campaign_builder */

( function( $ ) {
	'use strict';

	/**
	 * Main campaign builder controller.
	 *
	 * @type {Object}
	 */
	var BCG_CampaignBuilder = {

		/**
		 * Manually selected product IDs for the manual picker.
		 *
		 * @type {Array}
		 */
		manualProductIds: [],

		/**
		 * Search debounce timer reference.
		 *
		 * @type {number|null}
		 */
		searchTimer: null,

		/**
		 * Whether a generation is currently in progress.
		 *
		 * @type {boolean}
		 */
		isGenerating: false,

		/**
		 * Active XHR requests that can be aborted.
		 *
		 * @type {Object}
		 */
		activeRequests: {},

		/**
		 * Product IDs currently shown in the preview grid.
		 * Used to exclude them when replacing a single product.
		 *
		 * @type {Array}
		 */
		previewProductIds: [],

		/**
		 * Initialise the builder. Binds all event listeners.
		 *
		 * @return {void}
		 */
		init: function() {
			this.bindProductSource();
			this.bindManualPicker();
			this.bindCategoryTree();
			this.bindPreviewProducts();
			this.bindCouponToggle();
			this.bindDiscountTypeToggle();
			this.bindImageToggle();
			this.bindMailingListRefresh();
			this.bindAIGenerateButtons();
			this.bindCouponSuggestion();
			this.bindTemplatePicker();
			this.bindSectionTemplatePicker();
			this.bindFormSubmit();
			this.bindOverlayClose();

			// Set initial coupon visibility.
			this.toggleCouponDetails();

			// Load mailing lists on page load.
			this.loadMailingLists();

			// Load section templates on page load.
			this.loadSectionTemplates();
		},

		// ─── Product Source Radio Toggle ────────────────────────────

		/**
		 * Show/hide the manual product picker based on the selected
		 * product source radio.
		 *
		 * @return {void}
		 */
		bindProductSource: function() {
			var self = this;

			$( '#bcg-product-source-group' ).on( 'change', 'input[name="product_source"]', function() {
				var value = $( this ).val();

				if ( 'manual' === value ) {
					$( '#bcg-manual-picker' ).slideDown( 200 );
				} else {
					$( '#bcg-manual-picker' ).slideUp( 200 );
				}

				// Hide the product preview when changing source.
				$( '#bcg-product-preview-area' ).hide();
			} );
		},

		// ─── Manual Product Picker ─────────────────────────────────

		/**
		 * Bind search input and selection logic for the manual
		 * product picker.
		 *
		 * @return {void}
		 */
		bindManualPicker: function() {
			var self = this;

			// Search input with debounce.
			$( '#bcg-product-search' ).on( 'input', function() {
				var query = $.trim( $( this ).val() );

				if ( self.searchTimer ) {
					clearTimeout( self.searchTimer );
				}

				if ( query.length < 2 ) {
					$( '#bcg-product-search-results' ).hide().empty();
					return;
				}

				self.searchTimer = setTimeout( function() {
					self.searchProducts( query );
				}, 350 );
			} );

			// Close search results when clicking outside.
			$( document ).on( 'click', function( e ) {
				if ( ! $( e.target ).closest( '.bcg-product-search-wrapper' ).length ) {
					$( '#bcg-product-search-results' ).hide();
				}
			} );

			// Select a product from search results.
			$( '#bcg-product-search-results' ).on( 'click', '.bcg-search-result-item', function( e ) {
				e.preventDefault();

				var productId   = parseInt( $( this ).data( 'product-id' ), 10 );
				var productName = $( this ).data( 'product-name' );
				var productImg  = $( this ).data( 'product-image' );

				if ( ! productId || self.manualProductIds.indexOf( productId ) !== -1 ) {
					return;
				}

				self.addManualProduct( productId, productName, productImg );
				$( '#bcg-product-search' ).val( '' );
				$( '#bcg-product-search-results' ).hide().empty();
			} );

			// Remove a manually selected product.
			$( '#bcg-manual-selected-products' ).on( 'click', '.bcg-manual-product-remove', function( e ) {
				e.preventDefault();

				var productId = parseInt( $( this ).closest( '.bcg-manual-product-tag' ).data( 'product-id' ), 10 );
				self.removeManualProduct( productId );
			} );
		},

		/**
		 * Search for products via AJAX.
		 *
		 * @param {string} query The search keyword.
		 * @return {void}
		 */
		searchProducts: function( query ) {
			var self = this;

			if ( self.activeRequests.search ) {
				self.activeRequests.search.abort();
			}

			self.activeRequests.search = $.ajax( {
				url:  bcg_campaign_builder.ajax_url,
				type: 'POST',
				data: {
					action:  'bcg_search_products',
					nonce:   bcg_campaign_builder.nonce,
					keyword: query
				},
				success: function( response ) {
					if ( response.success && response.data.products ) {
						self.renderSearchResults( response.data.products );
					} else {
						$( '#bcg-product-search-results' ).hide().empty();
					}
				},
				error: function( jqXHR, textStatus ) {
					if ( 'abort' !== textStatus ) {
						$( '#bcg-product-search-results' ).hide().empty();
					}
				},
				complete: function() {
					delete self.activeRequests.search;
				}
			} );
		},

		/**
		 * Render search results in the dropdown.
		 *
		 * @param {Array} products Array of product data objects.
		 * @return {void}
		 */
		renderSearchResults: function( products ) {
			var self     = this;
			var $results = $( '#bcg-product-search-results' );
			var html     = '';

			if ( ! products.length ) {
				html = '<div class="bcg-search-no-results">' +
					bcg_campaign_builder.i18n.no_products_found +
					'</div>';
				$results.html( html ).show();
				return;
			}

			$.each( products, function( i, product ) {
				var isSelected = self.manualProductIds.indexOf( product.id ) !== -1;
				var selectedClass = isSelected ? ' bcg-search-result-selected' : '';

				html += '<div class="bcg-search-result-item' + selectedClass + '"' +
					' data-product-id="' + parseInt( product.id, 10 ) + '"' +
					' data-product-name="' + self.escapeAttr( product.name ) + '"' +
					' data-product-image="' + self.escapeAttr( product.image_url ) + '">';
				html += '<img src="' + self.escapeAttr( product.image_url ) + '" alt="" class="bcg-search-result-img" />';
				html += '<div class="bcg-search-result-info">';
				html += '<span class="bcg-search-result-name">' + self.escapeHtml( product.name ) + '</span>';
				html += '<span class="bcg-search-result-meta">' + product.price_html + '</span>';
				if ( product.category ) {
					html += '<span class="bcg-search-result-cat">' + self.escapeHtml( product.category ) + '</span>';
				}
				html += '</div>';
				if ( isSelected ) {
					html += '<span class="bcg-search-result-added">' +
						bcg_campaign_builder.i18n.already_added +
						'</span>';
				}
				html += '</div>';
			} );

			$results.html( html ).show();
		},

		/**
		 * Add a product to the manual selection.
		 *
		 * @param {number} productId   The product ID.
		 * @param {string} productName The product name.
		 * @param {string} productImg  The product image URL.
		 * @return {void}
		 */
		addManualProduct: function( productId, productName, productImg ) {
			this.manualProductIds.push( productId );
			this.updateManualProductIds();

			var html = '<span class="bcg-manual-product-tag" data-product-id="' + productId + '">';
			html += '<img src="' + this.escapeAttr( productImg ) + '" alt="" class="bcg-manual-product-thumb" />';
			html += '<span class="bcg-manual-product-name">' + this.escapeHtml( productName ) + '</span>';
			html += '<button type="button" class="bcg-manual-product-remove" title="' +
				bcg_campaign_builder.i18n.remove + '">&times;</button>';
			html += '</span>';

			$( '#bcg-manual-selected-products' ).append( html );
		},

		/**
		 * Remove a product from the manual selection.
		 *
		 * @param {number} productId The product ID to remove.
		 * @return {void}
		 */
		removeManualProduct: function( productId ) {
			this.manualProductIds = this.manualProductIds.filter( function( id ) {
				return id !== productId;
			} );
			this.updateManualProductIds();

			$( '.bcg-manual-product-tag[data-product-id="' + productId + '"]' ).remove();
		},

		/**
		 * Sync the hidden input value with the manual product IDs.
		 *
		 * @return {void}
		 */
		updateManualProductIds: function() {
			$( '#bcg-manual-product-ids' ).val( this.manualProductIds.join( ',' ) );
		},

		// ─── Category Tree ─────────────────────────────────────────

		/**
		 * Bind Select All / Deselect All behaviour and parent-child
		 * cascading for the category checkbox tree.
		 *
		 * @return {void}
		 */
		bindCategoryTree: function() {
			// Select All.
			$( '#bcg-category-select-all' ).on( 'click', function( e ) {
				e.preventDefault();
				$( '#bcg-category-tree .bcg-category-checkbox' ).prop( 'checked', true );
			} );

			// Deselect All.
			$( '#bcg-category-deselect-all' ).on( 'click', function( e ) {
				e.preventDefault();
				$( '#bcg-category-tree .bcg-category-checkbox' ).prop( 'checked', false );
			} );

			// When checking a parent, check all its children.
			$( '#bcg-category-tree' ).on( 'change', '.bcg-category-checkbox', function() {
				var isChecked = $( this ).prop( 'checked' );
				var $children = $( this ).closest( '.bcg-category-item' ).find( '.bcg-category-children .bcg-category-checkbox' );

				if ( $children.length ) {
					$children.prop( 'checked', isChecked );
				}

				// If unchecking a child, uncheck parent if all siblings are unchecked.
				if ( ! isChecked ) {
					var $parentList = $( this ).closest( '.bcg-category-children' );
					if ( $parentList.length ) {
						var $parentCheckbox = $parentList.closest( '.bcg-category-item' ).find( '> .bcg-category-label > .bcg-category-checkbox' );
						var anyChecked = $parentList.find( '.bcg-category-checkbox:checked' ).length > 0;
						if ( ! anyChecked ) {
							$parentCheckbox.prop( 'checked', false );
						}
					}
				}
			} );
		},

		// ─── Preview Products ──────────────────────────────────────

		/**
		 * Bind the Preview Products button to load a product preview
		 * via AJAX.
		 *
		 * @return {void}
		 */
		bindPreviewProducts: function() {
			var self = this;

			$( '#bcg-preview-products-btn' ).on( 'click', function( e ) {
				e.preventDefault();
				self.loadProductPreview();
			} );

			// Replace-product button (event-delegated — cards are rendered dynamically).
			$( '#bcg-product-preview-grid' ).on( 'click', '.bcg-replace-product-btn', function( e ) {
				e.preventDefault();
				e.stopPropagation();
				var $btn = $( this );
				if ( $btn.hasClass( 'is-replacing' ) ) {
					return;
				}
				var productId = parseInt( $btn.closest( '.bcg-preview-product-card' ).data( 'product-id' ), 10 );
				self.replacePreviewProduct( $btn, productId );
			} );
		},

		/**
		 * Fetch product preview data from the server and render
		 * preview cards.
		 *
		 * @return {void}
		 */
		loadProductPreview: function() {
			var self     = this;
			var $btn     = $( '#bcg-preview-products-btn' );
			var $spinner = $( '#bcg-preview-spinner' );

			var data = {
				action:         'bcg_preview_products',
				nonce:          bcg_campaign_builder.nonce,
				product_count:  $( '#bcg-product-count' ).val(),
				product_source: $( 'input[name="product_source"]:checked' ).val(),
				category_ids:   self.getSelectedCategories(),
				manual_ids:     self.manualProductIds
			};

			$btn.prop( 'disabled', true );
			$spinner.show();

			if ( self.activeRequests.preview ) {
				self.activeRequests.preview.abort();
			}

			self.activeRequests.preview = $.ajax( {
				url:  bcg_campaign_builder.ajax_url,
				type: 'POST',
				data: data,
				success: function( response ) {
					if ( response.success && response.data.products ) {
						self.renderProductPreview( response.data.products );
					} else {
						var msg = ( response.data && response.data.message )
							? response.data.message
							: bcg_campaign_builder.i18n.preview_error;
						self.showNotice( msg, 'error' );
					}
				},
				error: function( jqXHR, textStatus ) {
					if ( 'abort' !== textStatus ) {
						self.showNotice( bcg_campaign_builder.i18n.preview_error, 'error' );
					}
				},
				complete: function() {
					$btn.prop( 'disabled', false );
					$spinner.hide();
					delete self.activeRequests.preview;
				}
			} );
		},

		/**
		 * Build the HTML for a single product preview card.
		 *
		 * @param {Object} product Product data object.
		 * @return {string} HTML string for the card.
		 */
		buildProductCardHtml: function( product ) {
			var self = this;
			var html = '';

			html += '<div class="bcg-preview-product-card" data-product-id="' + parseInt( product.id, 10 ) + '">';
			html += '<div class="bcg-preview-product-image">';
			html += '<img src="' + self.escapeAttr( product.image_url ) + '" alt="' + self.escapeAttr( product.name ) + '" />';
			html += '<button type="button" class="bcg-replace-product-btn" title="Replace with a different product">';
			html += '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46A7.93 7.93 0 0 0 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74A7.93 7.93 0 0 0 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/></svg>';
			html += '</button>';
			html += '</div>';
			html += '<div class="bcg-preview-product-info">';
			html += '<h4 class="bcg-preview-product-name">' + self.escapeHtml( product.name ) + '</h4>';
			html += '<div class="bcg-preview-product-price">' + product.price_html + '</div>';
			if ( product.category ) {
				html += '<span class="bcg-preview-product-cat">' + self.escapeHtml( product.category ) + '</span>';
			}
			if ( product.total_sales > 0 ) {
				html += '<span class="bcg-preview-product-sales">' +
					product.total_sales + ' ' + bcg_campaign_builder.i18n.sales +
					'</span>';
			}
			html += '<span class="bcg-preview-product-stock bcg-stock-' + self.escapeAttr( product.stock_status ) + '">' +
				self.escapeHtml( product.stock_status ) +
				'</span>';
			html += '</div>';
			html += '</div>';

			return html;
		},

		/**
		 * Render product preview cards in the preview area.
		 *
		 * @param {Array} products Array of product preview objects.
		 * @return {void}
		 */
		renderProductPreview: function( products ) {
			var self  = this;
			var $area = $( '#bcg-product-preview-area' );
			var $grid = $( '#bcg-product-preview-grid' );
			var html  = '';

			// Reset tracked IDs.
			self.previewProductIds = [];

			if ( ! products.length ) {
				html = '<div class="bcg-empty-state"><p>' +
					bcg_campaign_builder.i18n.no_products_found +
					'</p></div>';
				$grid.html( html );
				$( '#bcg-preview-count' ).text( '' );
				$area.show();
				return;
			}

			$( '#bcg-preview-count' ).text( '(' + products.length + ')' );

			$.each( products, function( i, product ) {
				self.previewProductIds.push( parseInt( product.id, 10 ) );
				html += self.buildProductCardHtml( product );
			} );

			$grid.html( html );
			$area.slideDown( 200 );
		},

		/**
		 * Replace a single product card with a different product from the same source.
		 *
		 * Sends an AJAX request for 1 product, excluding all currently displayed
		 * product IDs. On success, swaps the card in-place.
		 *
		 * @param {jQuery} $btn      The replace button element.
		 * @param {number} productId The product ID being replaced.
		 * @return {void}
		 */
		replacePreviewProduct: function( $btn, productId ) {
			var self  = this;
			var $card = $btn.closest( '.bcg-preview-product-card' );

			$btn.addClass( 'is-replacing' );

			var data = {
				action:         'bcg_preview_products',
				nonce:          bcg_campaign_builder.nonce,
				product_count:  1,
				product_source: $( 'input[name="product_source"]:checked' ).val(),
				category_ids:   self.getSelectedCategories(),
				manual_ids:     [],
				exclude_ids:    self.previewProductIds
			};

			$.ajax( {
				url:  bcg_campaign_builder.ajax_url,
				type: 'POST',
				data: data,
				success: function( response ) {
					if ( response.success && response.data.products && response.data.products.length ) {
						var newProduct = response.data.products[ 0 ];
						var newId      = parseInt( newProduct.id, 10 );

						// Swap the tracked ID.
						var idx = self.previewProductIds.indexOf( productId );
						if ( idx !== -1 ) {
							self.previewProductIds[ idx ] = newId;
						}

						// Replace the card DOM.
						var $newCard = $( self.buildProductCardHtml( newProduct ) );
						$newCard.css( 'opacity', 0 );
						$card.replaceWith( $newCard );
						$newCard.animate( { opacity: 1 }, 250 );
					}
					// If no replacement found, silently restore the button.
				},
				error: function() {
					// Network error — just restore the button state.
				},
				complete: function() {
					// Button is gone if replace succeeded, but safe to run anyway.
					$btn.removeClass( 'is-replacing' );
				}
			} );
		},

		/**
		 * Get the selected category term IDs from the checkbox tree.
		 *
		 * @return {Array} Array of term ID integers.
		 */
		getSelectedCategories: function() {
			var ids = [];

			$( '#bcg-category-tree .bcg-category-checkbox:checked' ).each( function() {
				ids.push( parseInt( $( this ).val(), 10 ) );
			} );

			return ids;
		},

		// ─── Coupon Toggle ─────────────────────────────────────────

		/**
		 * Bind the coupon generation checkbox to show/hide coupon
		 * configuration fields.
		 *
		 * @return {void}
		 */
		bindCouponToggle: function() {
			var self = this;

			$( '#bcg-generate-coupon' ).on( 'change', function() {
				self.toggleCouponDetails();
			} );
		},

		/**
		 * Show or hide the coupon details section based on the
		 * checkbox state.
		 *
		 * @return {void}
		 */
		toggleCouponDetails: function() {
			if ( $( '#bcg-generate-coupon' ).prop( 'checked' ) ) {
				$( '#bcg-coupon-details' ).slideDown( 200 );
			} else {
				$( '#bcg-coupon-details' ).slideUp( 200 );
			}
		},

		// ─── Discount Type Toggle ──────────────────────────────────

		/**
		 * Update the discount suffix label when switching between
		 * percentage and fixed discount types.
		 *
		 * @return {void}
		 */
		bindDiscountTypeToggle: function() {
			$( 'input[name="coupon_type"]' ).on( 'change', function() {
				var type = $( this ).val();

				if ( 'percent' === type ) {
					$( '#bcg-discount-suffix' ).text( '%' );
					$( '#bcg-discount-value' ).attr( 'max', 100 );
				} else {
					$( '#bcg-discount-suffix' ).text( bcg_campaign_builder.currency_symbol );
					$( '#bcg-discount-value' ).attr( 'max', 9999 );
				}
			} );
		},

		// ─── AI Image Style Toggle ─────────────────────────────────

		/**
		 * Show/hide the image style select based on whether the AI
		 * images checkbox is checked.
		 *
		 * @return {void}
		 */
		bindImageToggle: function() {
			$( '#bcg-generate-images' ).on( 'change', function() {
				if ( $( this ).prop( 'checked' ) ) {
					$( '#bcg-image-style-row' ).slideDown( 200 );
				} else {
					$( '#bcg-image-style-row' ).slideUp( 200 );
				}
			} );
		},

		// ─── Mailing List ──────────────────────────────────────────

		/**
		 * Bind the Refresh button for mailing lists.
		 *
		 * @return {void}
		 */
		bindMailingListRefresh: function() {
			var self = this;

			$( '#bcg-refresh-lists' ).on( 'click', function( e ) {
				e.preventDefault();
				self.loadMailingLists();
			} );
		},

		/**
		 * Load mailing lists from Brevo via AJAX.
		 *
		 * @return {void}
		 */
		loadMailingLists: function() {
			var $select = $( '#bcg-mailing-list' );
			var $btn    = $( '#bcg-refresh-lists' );
			var current = $select.data( 'current' ) || '';

			$btn.prop( 'disabled', true ).addClass( 'is-loading' );

			$.ajax( {
				url:  bcg_campaign_builder.ajax_url,
				type: 'POST',
				data: {
					action: 'bcg_get_brevo_lists',
					nonce:  bcg_campaign_builder.nonce
				},
				success: function( response ) {
					if ( response.success && response.data && response.data.lists ) {
						var html = '<option value="">' +
							bcg_campaign_builder.i18n.select_list +
							'</option>';

						$.each( response.data.lists, function( i, list ) {
							var selected = String( list.id ) === String( current ) ? ' selected' : '';
							html += '<option value="' + parseInt( list.id, 10 ) + '"' + selected + '>';
							html += list.name + ' (' + list.totalSubscribers + ' ' +
								bcg_campaign_builder.i18n.subscribers + ')';
							html += '</option>';
						} );

						$select.html( html );
					} else if ( ! response.success && response.data && response.data.message ) {
						$select.html( '<option value="">' + response.data.message + '</option>' );
					}
				},
				error: function() {
					$select.html( '<option value=>' + ( bcg_campaign_builder.i18n.error_loading_lists || 'Error loading lists — click Refresh to retry' ) + '</option>' );
				},
				complete: function() {
					$btn.prop( 'disabled', false ).removeClass( 'is-loading' );
				}
			} );
		},

		// ─── AI Generate Buttons (Subject / Preview Text) ──────────

		/**
		 * Bind the AI generate buttons for subject line and preview
		 * text fields.
		 *
		 * @return {void}
		 */
		bindAIGenerateButtons: function() {
			var self = this;

			$( '.bcg-ai-generate-btn[data-action="bcg_regenerate_field"]' ).on( 'click', function( e ) {
				e.preventDefault();

				var $btn   = $( this );
				var field  = $btn.data( 'field' );

				if ( $btn.hasClass( 'is-loading' ) ) {
					return;
				}

				self.generateField( $btn, field );
			} );
		},

		/**
		 * Call the AJAX endpoint to generate a single text field
		 * (subject line or preview text).
		 *
		 * @param {jQuery}  $btn  The button element.
		 * @param {string}  field The field identifier.
		 * @return {void}
		 */
		generateField: function( $btn, field ) {
			var self          = this;
			var originalText  = $btn.text();

			$btn.addClass( 'is-loading' ).prop( 'disabled', true );

			var data = {
				action:    'bcg_regenerate_field',
				nonce:     bcg_campaign_builder.nonce,
				field:     field,
				title:     $( '#bcg-campaign-title' ).val(),
				theme:     $( '#bcg-theme' ).val(),
				tone:      $( '#bcg-tone' ).val(),
				language:  $( '#bcg-language' ).val(),
				subject:   $( '#bcg-subject-line' ).val()
			};

			$.ajax( {
				url:  bcg_campaign_builder.ajax_url,
				type: 'POST',
				data: data,
				success: function( response ) {
					var value = response.data && ( response.data.content || response.data.value );
					if ( response.success && value ) {
						var targetMap = {
							'subject_line': '#bcg-subject-line',
							'preview_text': '#bcg-preview-text'
						};

						if ( targetMap[ field ] ) {
							$( targetMap[ field ] ).val( value ).trigger( 'change' );
						}
					} else {
						var msg = ( response.data && response.data.message )
							? response.data.message
							: bcg_campaign_builder.i18n.generation_error;
						self.showNotice( msg, 'error' );
					}
				},
				error: function() {
					self.showNotice( bcg_campaign_builder.i18n.generation_error, 'error' );
				},
				complete: function() {
					$btn.removeClass( 'is-loading' ).prop( 'disabled', false );
				}
			} );
		},

		// ─── Coupon Suggestion ─────────────────────────────────────

		/**
		 * Bind the Generate Suggestion button for coupon discount.
		 *
		 * @return {void}
		 */
		bindCouponSuggestion: function() {
			var self = this;

			$( '#bcg-suggest-discount-btn' ).on( 'click', function( e ) {
				e.preventDefault();

				var $btn = $( this );

				if ( $btn.hasClass( 'is-loading' ) ) {
					return;
				}

				self.generateCouponSuggestion( $btn );
			} );
		},

		/**
		 * Call the AJAX endpoint for a coupon discount suggestion.
		 *
		 * @param {jQuery} $btn The button element.
		 * @return {void}
		 */
		generateCouponSuggestion: function( $btn ) {
			var self = this;

			$btn.addClass( 'is-loading' ).prop( 'disabled', true );

			var data = {
				action:   'bcg_regenerate_field',
				nonce:    bcg_campaign_builder.nonce,
				field:    'coupon_suggestion',
				title:    $( '#bcg-campaign-title' ).val(),
				theme:    $( '#bcg-theme' ).val(),
				tone:     $( '#bcg-tone' ).val(),
				language: $( '#bcg-language' ).val()
			};

			$.ajax( {
				url:  bcg_campaign_builder.ajax_url,
				type: 'POST',
				data: data,
				success: function( response ) {
					if ( response.success && response.data ) {
						if ( response.data.value ) {
							$( '#bcg-discount-value' ).val( response.data.value );
						}
						if ( response.data.type ) {
							$( 'input[name="coupon_type"][value="' + response.data.type + '"]' )
								.prop( 'checked', true )
								.trigger( 'change' );
						}
					} else {
						var msg = ( response.data && response.data.message )
							? response.data.message
							: bcg_campaign_builder.i18n.generation_error;
						self.showNotice( msg, 'error' );
					}
				},
				error: function() {
					self.showNotice( bcg_campaign_builder.i18n.generation_error, 'error' );
				},
				complete: function() {
					$btn.removeClass( 'is-loading' ).prop( 'disabled', false );
				}
			} );
		},

		// ─── Template Picker ──────────────────────────────────────

		/**
		 * Bind the template picker card selection.
		 *
		 * @return {void}
		 */
		bindTemplatePicker: function() {
			$( '#bcg-template-picker' ).on( 'click', '.bcg-template-card', function() {
				var $card = $( this );
				var slug  = $card.data( 'slug' );

				// Update active state.
				$( '#bcg-template-picker .bcg-template-card' ).removeClass( 'bcg-template-card-active' );
				$card.addClass( 'bcg-template-card-active' );

				// Update the hidden input.
				$( '#bcg-template-slug' ).val( slug );

				// Deselect any section template card.
				$( '.bcg-section-template-card' ).removeClass( 'bcg-section-template-card-active' );
				$( '#bcg-section-template-id' ).val( 0 );
			} );
		},

		// ── My Templates (section builder templates) ───────────────

		/**
		 * Load user-built section templates and render them in the My Templates grid.
		 *
		 * @return {void}
		 */
		loadSectionTemplates: function() {
			var $grid    = $( '#bcg-my-templates-grid' );
			var $loading = $( '#bcg-my-templates-loading' );

			if ( ! $grid.length ) { return; }

			$.ajax( {
				url:  bcg_campaign_builder.ajax_url,
				type: 'POST',
				data: { action: 'bcg_get_section_templates', nonce: bcg_campaign_builder.nonce },
				success: function ( res ) {
					$loading.remove();
					if ( ! res.success || ! res.data.templates || res.data.templates.length === 0 ) {
						$grid.append(
							'<p class="bcg-my-templates-empty">' +
							'<span class="material-icons-outlined">add_circle_outline</span>' +
							'No templates yet. <a href="' + bcg_campaign_builder.template_builder_url + '">Build one in the Template Builder</a>.' +
							'</p>'
						);
						return;
					}
					$.each( res.data.templates, function ( i, tpl ) {
						var $card = $( '<button>' )
							.attr( 'type', 'button' )
							.addClass( 'bcg-section-template-card' )
							.attr( 'data-id', parseInt( tpl.id, 10 ) )
							.attr( 'data-name', tpl.name )
							.html(
								'<div class="bcg-stc-icon"><span class="material-icons-outlined">dashboard_customize</span></div>' +
								'<span class="bcg-stc-name">' + $( '<span>' ).text( tpl.name ).html() + '</span>' +
								( tpl.description ? '<span class="bcg-stc-desc">' + $( '<span>' ).text( tpl.description ).html() + '</span>' : '' )
							);
						$grid.append( $card );
					} );
				},
				error: function () {
					$loading.remove();
					$grid.append( '<p class="bcg-my-templates-empty bcg-error">Could not load templates.</p>' );
				}
			} );
		},

		/**
		 * Bind section template card click — select it, deselect flat templates.
		 *
		 * @return {void}
		 */
		bindSectionTemplatePicker: function() {
			$( document ).on( 'click', '.bcg-section-template-card', function () {
				var $card      = $( this );
				var id         = parseInt( $card.data( 'id' ), 10 );
				var isSelected = $card.hasClass( 'bcg-section-template-card-active' );

				if ( isSelected ) {
					// Deselect — revert to first flat template.
					$card.removeClass( 'bcg-section-template-card-active' );
					$( '#bcg-section-template-id' ).val( 0 );
					var $firstFlat = $( '.bcg-template-card' ).first();
					$firstFlat.addClass( 'bcg-template-card-active' );
					$( '#bcg-template-slug' ).val( $firstFlat.data( 'slug' ) || 'classic' );
				} else {
					// Deselect all flat templates.
					$( '.bcg-template-card' ).removeClass( 'bcg-template-card-active' );
					$( '#bcg-template-slug' ).val( 'sections' );
					// Deselect other section template cards.
					$( '.bcg-section-template-card' ).removeClass( 'bcg-section-template-card-active' );
					// Select this card.
					$card.addClass( 'bcg-section-template-card-active' );
					$( '#bcg-section-template-id' ).val( id );
				}
			} );
		},

		// ─── Form Validation & Submit ──────────────────────────────

		/**
		 * Bind the form submission handler.
		 *
		 * @return {void}
		 */
		bindFormSubmit: function() {
			var self = this;

			$( '#bcg-campaign-wizard' ).on( 'submit', function( e ) {
				e.preventDefault();

				if ( self.isGenerating ) {
					return;
				}

				if ( ! self.validateForm() ) {
					return;
				}

				self.startGeneration();
			} );
		},

		/**
		 * Validate the campaign wizard form before submission.
		 *
		 * @return {boolean} True if valid, false otherwise.
		 */
		validateForm: function() {
			var errors = [];

			// Clear previous errors.
			$( '.bcg-field-error' ).removeClass( 'bcg-field-error' );
			this.clearNotices();

			// Campaign title is required.
			var title = $.trim( $( '#bcg-campaign-title' ).val() );
			if ( ! title ) {
				errors.push( bcg_campaign_builder.i18n.title_required );
				$( '#bcg-campaign-title' ).addClass( 'bcg-field-error' ).focus();
			}

			// Manual source requires at least one product.
			var source = $( 'input[name="product_source"]:checked' ).val();
			if ( 'manual' === source && this.manualProductIds.length < 1 ) {
				errors.push( bcg_campaign_builder.i18n.manual_products_required );
			}

			// Coupon validation if enabled.
			if ( $( '#bcg-generate-coupon' ).prop( 'checked' ) ) {
				var discount = parseFloat( $( '#bcg-discount-value' ).val() );
				if ( isNaN( discount ) || discount <= 0 ) {
					errors.push( bcg_campaign_builder.i18n.discount_required );
					$( '#bcg-discount-value' ).addClass( 'bcg-field-error' );
				}
			}

			if ( errors.length ) {
				this.showNotice( errors.join( '<br>' ), 'error' );
				return false;
			}

			return true;
		},

		// ─── Generation Pipeline ───────────────────────────────────

		/**
		 * Start the full campaign generation pipeline.
		 *
		 * Shows the progress overlay and fires the AJAX request.
		 * Server-side handles the full pipeline (products, copy,
		 * images, finalise). Client-side animates the progress steps
		 * with estimated timings.
		 *
		 * @return {void}
		 */
		startGeneration: function() {
			var self = this;

			self.isGenerating = true;

			var $overlay = $( '#bcg-generation-overlay' );
			var $btn     = $( '#bcg-generate-campaign-btn' );

			// Reset all steps to pending state.
			$( '.bcg-generation-step' ).each( function() {
				$( this )
					.removeClass( 'bcg-step-active bcg-step-complete bcg-step-error' )
					.find( '.bcg-step-spinner' ).hide();
				$( this ).find( '.bcg-step-check' ).hide();
				$( this ).find( '.bcg-step-number' ).show();
			} );

			$( '#bcg-generation-error' ).hide();
			$( '#bcg-generation-actions' ).hide();

			$btn.prop( 'disabled', true );
			$overlay.fadeIn( 200 );

			// Animate through steps with estimated timings.
			self.activateStep( 'products' );

			// Collect all form data.
			var formData = {
				action:              'bcg_generate_campaign',
				nonce:               bcg_campaign_builder.nonce,
				campaign_title:      $( '#bcg-campaign-title' ).val(),
				subject_line:        $( '#bcg-subject-line' ).val(),
				preview_text:        $( '#bcg-preview-text' ).val(),
				mailing_list_id:     $( '#bcg-mailing-list' ).val(),
				product_count:       $( '#bcg-product-count' ).val(),
				product_source:      $( 'input[name="product_source"]:checked' ).val(),
				category_ids:        self.getSelectedCategories(),
				manual_product_ids:  self.manualProductIds,
				generate_coupon:     $( '#bcg-generate-coupon' ).prop( 'checked' ) ? '1' : '0',
				coupon_type:         $( 'input[name="coupon_type"]:checked' ).val(),
				coupon_discount:     $( '#bcg-discount-value' ).val(),
				coupon_expiry_days:  $( '#bcg-coupon-expiry' ).val(),
				coupon_prefix:       $( '#bcg-coupon-prefix' ).val(),
				tone:                $( '#bcg-tone' ).val(),
				theme:               $( '#bcg-theme' ).val(),
				language:            $( '#bcg-language' ).val(),
				generate_images:     $( '#bcg-generate-images' ).prop( 'checked' ) ? '1' : '0',
				image_style:         $( '#bcg-image-style' ).val(),
				template_slug:       $( '#bcg-template-slug' ).val() || 'classic',
				section_template_id: parseInt( $( '#bcg-section-template-id' ).val() || 0, 10 )
			};

			// Calculate expected duration for step animations.
			var hasImages      = formData.generate_images === '1';
			var stepDelay1     = 2000;   // Products fetching.
			var stepDelay2     = 8000;   // Copy generation.
			var stepDelay3     = hasImages ? 15000 : 1000; // Image generation.

			// Simulate step progress (actual completion handled by AJAX response).
			setTimeout( function() {
				if ( self.isGenerating ) {
					self.completeStep( 'products' );
					self.activateStep( 'copy' );
				}
			}, stepDelay1 );

			setTimeout( function() {
				if ( self.isGenerating ) {
					self.completeStep( 'copy' );
					self.activateStep( 'images' );
				}
			}, stepDelay1 + stepDelay2 );

			setTimeout( function() {
				if ( self.isGenerating ) {
					self.completeStep( 'images' );
					self.activateStep( 'finalise' );
				}
			}, stepDelay1 + stepDelay2 + stepDelay3 );

			// Fire the AJAX request.
			$.ajax( {
				url:     bcg_campaign_builder.ajax_url,
				type:    'POST',
				data:    formData,
				timeout: 120000, // 2-minute timeout.
				success: function( response ) {
					if ( response.success && response.data.campaign_id ) {
						// Complete all steps.
						self.completeStep( 'products' );
						self.completeStep( 'copy' );
						self.completeStep( 'images' );
						self.completeStep( 'finalise' );

						// Redirect to the edit page after a brief delay.
						setTimeout( function() {
							window.location.href = response.data.redirect_url ||
								bcg_campaign_builder.edit_url + '&campaign_id=' + response.data.campaign_id;
						}, 800 );
					} else {
						self.handleGenerationError( response );
					}
				},
				error: function( jqXHR, textStatus ) {
					var msg = bcg_campaign_builder.i18n.generation_failed;

					if ( 'timeout' === textStatus ) {
						msg = bcg_campaign_builder.i18n.generation_timeout;
					}

					self.handleGenerationError( { data: { message: msg } } );
				}
			} );
		},

		/**
		 * Activate a generation step (show spinner).
		 *
		 * @param {string} stepId The step identifier.
		 * @return {void}
		 */
		activateStep: function( stepId ) {
			var $step = $( '#bcg-step-' + stepId );

			$step.addClass( 'bcg-step-active' );
			$step.find( '.bcg-step-number' ).hide();
			$step.find( '.bcg-step-spinner' ).show();
			$step.find( '.bcg-step-check' ).hide();
		},

		/**
		 * Mark a generation step as complete (show checkmark).
		 *
		 * @param {string} stepId The step identifier.
		 * @return {void}
		 */
		completeStep: function( stepId ) {
			var $step = $( '#bcg-step-' + stepId );

			$step.removeClass( 'bcg-step-active' ).addClass( 'bcg-step-complete' );
			$step.find( '.bcg-step-number' ).hide();
			$step.find( '.bcg-step-spinner' ).hide();
			$step.find( '.bcg-step-check' ).show();
		},

		/**
		 * Handle a generation error: mark the current active step as
		 * errored, show the error message, and display the Close
		 * button.
		 *
		 * @param {Object} response The AJAX response or error data.
		 * @return {void}
		 */
		handleGenerationError: function( response ) {
			var self = this;
			var msg  = bcg_campaign_builder.i18n.generation_failed;

			if ( response.data && response.data.message ) {
				msg = response.data.message;
			}

			// Mark active step as error.
			$( '.bcg-generation-step.bcg-step-active' ).each( function() {
				$( this ).removeClass( 'bcg-step-active' ).addClass( 'bcg-step-error' );
				$( this ).find( '.bcg-step-spinner' ).hide();
				$( this ).find( '.bcg-step-number' ).show();
			} );

			$( '#bcg-generation-error-message' ).html( self.escapeHtml( msg ) );
			$( '#bcg-generation-error' ).show();
			$( '#bcg-generation-actions' ).show();

			self.isGenerating = false;
			$( '#bcg-generate-campaign-btn' ).prop( 'disabled', false );
		},

		// ─── Overlay Close ─────────────────────────────────────────

		/**
		 * Bind the overlay close button.
		 *
		 * @return {void}
		 */
		bindOverlayClose: function() {
			var self = this;

			$( '#bcg-generation-cancel' ).on( 'click', function( e ) {
				e.preventDefault();

				$( '#bcg-generation-overlay' ).fadeOut( 200 );
				self.isGenerating = false;
				$( '#bcg-generate-campaign-btn' ).prop( 'disabled', false );
			} );
		},

		// ─── Notices ───────────────────────────────────────────────

		/**
		 * Show a notice at the top of the wizard form.
		 *
		 * @param {string} message The notice message (HTML allowed).
		 * @param {string} type    The notice type: 'error', 'success',
		 *                         'warning', 'info'.
		 * @return {void}
		 */
		showNotice: function( message, type ) {
			type = type || 'error';

			var html = '<div class="bcg-notice bcg-notice-' + this.escapeAttr( type ) + ' bcg-wizard-notice">' +
				'<p>' + message + '</p>' +
				'<button type="button" class="bcg-notice-dismiss">&times;</button>' +
				'</div>';

			$( '#bcg-wizard-notices' ).html( html );

			// Scroll to notices.
			$( 'html, body' ).animate( {
				scrollTop: $( '#bcg-wizard-notices' ).offset().top - 50
			}, 300 );

			// Auto-dismiss after 10 seconds.
			setTimeout( function() {
				$( '.bcg-wizard-notice' ).fadeOut( 300, function() {
					$( this ).remove();
				} );
			}, 10000 );
		},

		/**
		 * Clear all wizard notices.
		 *
		 * @return {void}
		 */
		clearNotices: function() {
			$( '#bcg-wizard-notices' ).empty();
		},

		// ─── Utility Methods ───────────────────────────────────────

		/**
		 * Escape a string for safe use in HTML attributes.
		 *
		 * @param {string} str The string to escape.
		 * @return {string} The escaped string.
		 */
		escapeAttr: function( str ) {
			if ( ! str ) {
				return '';
			}
			return String( str )
				.replace( /&/g, '&amp;' )
				.replace( /"/g, '&quot;' )
				.replace( /'/g, '&#39;' )
				.replace( /</g, '&lt;' )
				.replace( />/g, '&gt;' );
		},

		/**
		 * Escape a string for safe use in HTML content.
		 *
		 * @param {string} str The string to escape.
		 * @return {string} The escaped string.
		 */
		escapeHtml: function( str ) {
			if ( ! str ) {
				return '';
			}
			return String( str )
				.replace( /&/g, '&amp;' )
				.replace( /</g, '&lt;' )
				.replace( />/g, '&gt;' )
				.replace( /"/g, '&quot;' )
				.replace( /'/g, '&#39;' );
		}
	};

	// ─── Dismiss notices via delegation ────────────────────────────

	$( document ).on( 'click', '.bcg-notice-dismiss', function( e ) {
		e.preventDefault();
		$( this ).closest( '.bcg-notice' ).fadeOut( 200, function() {
			$( this ).remove();
		} );
	} );

	// ─── Init on document ready ────────────────────────────────────

	$( document ).ready( function() {
		if ( $( '#bcg-campaign-wizard' ).length ) {
			BCG_CampaignBuilder.init();
		}
	} );

} )( jQuery );
