/**
 * Section Builder — Main JavaScript controller.
 *
 * Implements the three-panel Section Builder UI:
 *  - Palette: click to add section types
 *  - Canvas: drag-to-reorder sections, per-section controls
 *  - Settings panel: dynamic fields from registry schema
 *  - Live preview via AJAX → iframe
 *  - Save / load / delete named templates
 *  - "Generate All with AI" and per-section AI regeneration
 *
 * @package Brevo_Campaign_Generator
 * @since   1.5.0
 */
/* global bcg_section_builder, wp */
( function ( $ ) {
	'use strict';

	var BCGSectionBuilder = {

		// ── State ─────────────────────────────────────────────────────────

		sections:     [],     // Array of { id, type, settings } — source of truth.
		selectedId:   null,   // UUID of section currently open in settings panel.
		isDirty:      false,  // Unsaved changes flag.
		previewTimer: null,   // Debounce handle for live preview.
		currentTemplateId: 0, // ID of the loaded/saved template (0 = new).
		mediaFrame:   null,   // WP media frame instance.

		i18n:    bcg_section_builder.i18n          || {},
		types:   bcg_section_builder.section_types || {},
		presets: bcg_section_builder.presets       || [],

		// ── Initialisation ─────────────────────────────────────────────────

		init: function () {
			this.bindPalette();
			this.bindCanvas();
			this.bindToolbar();
			this.bindPreviewModal();
			this.bindLoadModal();
			this.renderPalette();
			this.renderCanvas();
		},

		// ── Palette ─────────────────────────────────────────────────────────

		/**
		 * Render pre-built section variant cards, grouped by category accordion.
		 */
		renderPalette: function () {
			var self    = this;
			var $list   = $( '#bcg-sb-palette' );
			var presets = self.presets;
			$list.empty();

			if ( ! presets || presets.length === 0 ) {
				$list.html( '<p class="bcg-sb-palette-empty">No sections available.</p>' );
				return;
			}

			$.each( presets, function ( i, cat ) {
				var $group = $( '<div>' ).addClass( 'bcg-sb-palette-group' );

				var $header = $( '<button>' )
					.attr( 'type', 'button' )
					.addClass( 'bcg-sb-palette-group-header' )
					.attr( 'aria-expanded', 'false' )
					.html(
						'<span class="material-icons-outlined bcg-sb-palette-cat-icon">' + self.escHtml( cat.icon || 'widgets' ) + '</span>' +
						'<span class="bcg-sb-palette-group-label">' + self.escHtml( cat.label ) + '</span>' +
						'<span class="bcg-sb-palette-group-chevron material-icons-outlined">expand_more</span>'
					);

				var $variants = $( '<div>' ).addClass( 'bcg-sb-palette-variants bcg-sb-palette-variants-collapsed' );

				$.each( cat.variants, function ( j, variant ) {
					var $card = $( '<button>' )
						.attr( 'type', 'button' )
						.addClass( 'bcg-sb-variant-card' )
						.attr( 'data-type', variant.type )
						.attr( 'data-variant-id', variant.id )
						.attr( 'title', variant.description || '' )
						.html(
							'<span class="bcg-sb-variant-icon material-icons-outlined">' + self.escHtml( ( bcg_section_builder.section_types[ variant.type ] && bcg_section_builder.section_types[ variant.type ].icon ) || 'widgets' ) + '</span>' +
							'<span class="bcg-sb-variant-label">' + self.escHtml( variant.label ) + '</span>'
						);
					$variants.append( $card );
				} );

				$group.append( $header ).append( $variants );
				$list.append( $group );
			} );
		},

		/**
		 * Bind click events on palette variant cards and category accordion toggles.
		 */
		bindPalette: function () {
			var self = this;

			// Variant card click — add section with preset settings.
			$( document ).on( 'click', '.bcg-sb-variant-card', function () {
				var type      = $( this ).data( 'type' );
				var variantId = $( this ).data( 'variant-id' );
				var presets   = self.presets;
				var settings  = null;

				// Find preset settings by variant ID.
				$.each( presets, function ( i, cat ) {
					$.each( cat.variants, function ( j, v ) {
						if ( v.id === variantId ) {
							settings = v.settings || {};
							return false;
						}
					} );
					if ( settings !== null ) { return false; }
				} );

				self.addSection( type, settings );
			} );

			// Category header click — exclusive accordion (one open at a time).
			$( document ).on( 'click', '.bcg-sb-palette-group-header', function () {
				var $btn      = $( this );
				var $variants = $btn.next( '.bcg-sb-palette-variants' );
				var expanded  = $btn.attr( 'aria-expanded' ) === 'true';

				// Close all other open groups.
				if ( ! expanded ) {
					$( '.bcg-sb-palette-group-header[aria-expanded="true"]' ).each( function () {
						var $other = $( this );
						$other.attr( 'aria-expanded', 'false' );
						$other.find( '.bcg-sb-palette-group-chevron' ).text( 'expand_more' );
						$other.next( '.bcg-sb-palette-variants' ).addClass( 'bcg-sb-palette-variants-collapsed' );
					} );
				}

				$btn.attr( 'aria-expanded', String( ! expanded ) );
				$btn.find( '.bcg-sb-palette-group-chevron' ).text( expanded ? 'expand_more' : 'expand_less' );
				$variants.toggleClass( 'bcg-sb-palette-variants-collapsed', expanded );
			} );
		},

		// ── Canvas ─────────────────────────────────────────────────────────

		/**
		 * Bind canvas-level interactions: sortable, section actions.
		 */
		bindCanvas: function () {
			var self = this;

			// jQuery UI Sortable — drag to reorder.
			$( '#bcg-sb-canvas' ).sortable( {
				items:       '.bcg-sb-section',
				handle:      '.bcg-sb-drag-handle',
				placeholder: 'bcg-sb-section-placeholder',
				tolerance:   'pointer',
				axis:        'y',
				update: function () {
					self.syncOrderFromDOM();
					self.markDirty();
					self.debouncePreview();
				}
			} );

			// Remove section.
			$( document ).on( 'click', '.bcg-sb-section-remove', function () {
				var id = $( this ).closest( '.bcg-sb-section' ).data( 'id' );
				self.removeSection( id );
			} );

			// Select section (open settings).
			$( document ).on( 'click', '.bcg-sb-section-edit', function () {
				var id = $( this ).closest( '.bcg-sb-section' ).data( 'id' );
				self.selectSection( id );
			} );

			// Move up.
			$( document ).on( 'click', '.bcg-sb-move-up', function () {
				var $card = $( this ).closest( '.bcg-sb-section' );
				var $prev = $card.prev( '.bcg-sb-section' );
				if ( $prev.length ) {
					$prev.before( $card );
					self.syncOrderFromDOM();
					self.markDirty();
					self.debouncePreview();
				}
			} );

			// Move down.
			$( document ).on( 'click', '.bcg-sb-move-down', function () {
				var $card = $( this ).closest( '.bcg-sb-section' );
				var $next = $card.next( '.bcg-sb-section' );
				if ( $next.length ) {
					$next.after( $card );
					self.syncOrderFromDOM();
					self.markDirty();
					self.debouncePreview();
				}
			} );

			// Per-section AI generate button.
			$( document ).on( 'click', '.bcg-sb-section-ai', function () {
				var id = $( this ).closest( '.bcg-sb-section' ).data( 'id' );
				self.generateSection( id );
			} );

			// "Add Section" footer button — scroll palette into view on mobile.
			$( '#bcg-sb-add-section-btn' ).on( 'click', function () {
				var $palette = $( '.bcg-sb-palette' );
				if ( $palette.length ) {
					$palette[ 0 ].scrollIntoView( { behavior: 'smooth', block: 'start' } );
				}
				$( '.bcg-sb-variant-card' ).first().focus();
			} );

			// Canvas card preview toggle.
			$( document ).on( 'click', '.bcg-sb-card-preview-toggle', function ( e ) {
				e.stopPropagation();
				var $btn     = $( this );
				var $card    = $btn.closest( '.bcg-sb-section' );
				var id       = $card.data( 'id' );
				var $preview = $card.find( '.bcg-sb-card-preview' );
				var $icon    = $btn.find( '.material-icons-outlined' );
				var isOpen   = $preview.is( ':visible' );
				if ( isOpen ) {
					$preview.slideUp( 200 );
					$icon.text( 'visibility' );
					$btn.attr( 'title', 'Show preview' );
				} else {
					$preview.slideDown( 200, function () {
						self.updateSectionPreview( id );
					} );
					$icon.text( 'visibility_off' );
					$btn.attr( 'title', 'Hide preview' );
				}
			} );
		},

		/**
		 * Render all section cards to the canvas from this.sections.
		 */
		renderCanvas: function () {
			var self    = this;
			var $canvas = $( '#bcg-sb-canvas' );
			var $empty  = $( '#bcg-sb-canvas-empty' );

			// Remove existing cards (keep empty placeholder).
			$canvas.find( '.bcg-sb-section' ).remove();

			if ( self.sections.length === 0 ) {
				$empty.show();
				return;
			}
			$empty.hide();

			$.each( self.sections, function ( i, section ) {
				var card = self.buildSectionCard( section );
				$canvas.append( card );
			} );

			// Highlight selected section.
			if ( self.selectedId ) {
				$canvas.find( '[data-id="' + self.selectedId + '"]' ).addClass( 'bcg-sb-section-active' );
			}
		},

		/**
		 * Build a single section card DOM element.
		 *
		 * @param  {Object} section Section object { id, type, settings }.
		 * @return {jQuery}
		 */
		buildSectionCard: function ( section ) {
			var self    = this;
			var typeDef = self.types[ section.type ] || {};
			var label   = typeDef.label || section.type;
			var icon    = typeDef.icon  || 'widgets';
			var hasAi   = !! typeDef.has_ai;

			var aiBtn = hasAi
				? '<button type="button" class="bcg-sb-section-ai bcg-btn-icon" title="' + self.escHtml( self.i18n.generating || 'Generate with AI' ) + '">' +
				  '<span class="material-icons-outlined">auto_awesome</span></button>'
				: '';

			var $card = $( '<div>' )
				.addClass( 'bcg-sb-section' )
				.attr( 'data-id', section.id )
				.attr( 'data-type', section.type );

			var headerHtml =
				'<div class="bcg-sb-section-header">' +
				'<span class="bcg-sb-drag-handle material-icons-outlined">drag_indicator</span>' +
				'<span class="bcg-sb-section-icon material-icons-outlined">' + self.escHtml( icon ) + '</span>' +
				'<span class="bcg-sb-section-label">' + self.escHtml( label ) + '</span>' +
				'<div class="bcg-sb-section-actions">' +
					aiBtn +
					'<button type="button" class="bcg-sb-move-up bcg-btn-icon" title="' + self.escAttr( self.i18n.move_up || 'Move up' ) + '"><span class="material-icons-outlined">keyboard_arrow_up</span></button>' +
					'<button type="button" class="bcg-sb-move-down bcg-btn-icon" title="' + self.escAttr( self.i18n.move_down || 'Move down' ) + '"><span class="material-icons-outlined">keyboard_arrow_down</span></button>' +
					'<button type="button" class="bcg-sb-section-edit bcg-btn-icon" title="' + self.escAttr( self.i18n.edit_settings || 'Edit settings' ) + '"><span class="material-icons-outlined">settings</span></button>' +
					'<button type="button" class="bcg-sb-card-preview-toggle bcg-btn-icon" title="Toggle preview"><span class="material-icons-outlined">visibility</span></button>' +
					'<button type="button" class="bcg-sb-section-remove bcg-btn-icon bcg-btn-danger-icon" title="' + self.escAttr( self.i18n.remove || 'Remove' ) + '"><span class="material-icons-outlined">delete</span></button>' +
				'</div>' +
				'</div>';

			var previewHtml =
				'<div class="bcg-sb-card-preview" style="display:none;">' +
				'<iframe class="bcg-sb-card-iframe" scrolling="no" frameborder="0"></iframe>' +
				'</div>';

			$card.html( headerHtml + previewHtml );

			return $card;
		},

		// ── Section CRUD ──────────────────────────────────────────────────

		/**
		 * Add a new section of the given type.
		 *
		 * @param {string}      type          Section type slug.
		 * @param {Object|null} presetSettings Optional preset settings to overlay on type defaults.
		 */
		addSection: function ( type, presetSettings ) {
			var typeDef = this.types[ type ];
			if ( ! typeDef ) { return; }

			// Start with type defaults; overlay with preset values if provided.
			var baseSettings = $.extend( true, {}, typeDef.defaults || {} );
			var settings = presetSettings
				? $.extend( true, baseSettings, presetSettings )
				: baseSettings;

			var section = {
				id:       this.generateUUID(),
				type:     type,
				settings: settings
			};

			this.sections.push( section );
			this.renderCanvas();
			this.selectSection( section.id );
			this.markDirty();
			this.debouncePreview();

			// Scroll new card into view.
			var $card = $( '[data-id="' + section.id + '"]' );
			if ( $card.length ) {
				$card[ 0 ].scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
			}
		},

		/**
		 * Remove a section by ID.
		 *
		 * @param {string} id Section UUID.
		 */
		removeSection: function ( id ) {
			if ( ! confirm( this.i18n.confirm_delete || 'Delete this section?' ) ) {
				return;
			}

			this.sections = this.sections.filter( function ( s ) { return s.id !== id; } );

			if ( this.selectedId === id ) {
				this.selectedId = null;
				this.renderSettingsPanel( null );
			}

			this.renderCanvas();
			this.markDirty();
			this.debouncePreview();
		},

		/**
		 * Select a section and open its settings in the right panel.
		 *
		 * @param {string} id Section UUID.
		 */
		selectSection: function ( id ) {
			this.selectedId = id;

			// Highlight the selected card.
			$( '.bcg-sb-section' ).removeClass( 'bcg-sb-section-active' );
			$( '[data-id="' + id + '"]' ).addClass( 'bcg-sb-section-active' );

			var section = this.getSectionById( id );
			this.renderSettingsPanel( section );
		},

		/**
		 * Update a specific setting key on a section and trigger preview.
		 *
		 * @param {string} id  Section UUID.
		 * @param {string} key Setting key.
		 * @param {*}      val New value.
		 */
		updateSetting: function ( id, key, val ) {
			var section = this.getSectionById( id );
			if ( ! section ) { return; }
			section.settings[ key ] = val;
			this.markDirty();
			this.debouncePreview();
		},

		/**
		 * Get a section object by its UUID.
		 *
		 * @param  {string} id
		 * @return {Object|null}
		 */
		getSectionById: function ( id ) {
			for ( var i = 0; i < this.sections.length; i++ ) {
				if ( this.sections[ i ].id === id ) {
					return this.sections[ i ];
				}
			}
			return null;
		},

		/**
		 * Re-sync this.sections order to match the current DOM order.
		 */
		syncOrderFromDOM: function () {
			var self     = this;
			var ordered  = [];
			$( '#bcg-sb-canvas .bcg-sb-section' ).each( function () {
				var id      = $( this ).data( 'id' );
				var section = self.getSectionById( id );
				if ( section ) {
					ordered.push( section );
				}
			} );
			self.sections = ordered;
		},

		// ── Settings Panel ────────────────────────────────────────────────

		/**
		 * Render the settings panel for the given section (or placeholder if null).
		 *
		 * @param {Object|null} section
		 */
		renderSettingsPanel: function ( section ) {
			var self  = this;
			var $body = $( '#bcg-sb-settings-body' );

			if ( ! section ) {
				$body.html(
					'<div class="bcg-sb-settings-placeholder">' +
					'<span class="material-icons-outlined">touch_app</span>' +
					'<p>' + ( self.i18n.click_to_edit || 'Click a section on the canvas to edit its settings.' ) + '</p>' +
					'</div>'
				);
				return;
			}

			var typeDef = self.types[ section.type ] || {};
			var fields  = typeDef.fields || [];
			var html    = '<div class="bcg-sb-fields">';

			html += '<div class="bcg-sb-section-type-label">' +
				'<span class="material-icons-outlined">' + self.escHtml( typeDef.icon || 'widgets' ) + '</span> ' +
				self.escHtml( typeDef.label || section.type ) +
				'</div>';

			if ( typeDef.has_ai ) {
				html += '<button type="button" class="bcg-sb-settings-ai-btn bcg-btn-ai bcg-btn-sm" data-id="' + self.escAttr( section.id ) + '">' +
					'<span class="material-icons-outlined">auto_awesome</span> ' +
					( self.i18n.generating || 'Generate with AI' ) +
					'</button>';
			}

			$.each( fields, function ( i, field ) {
				html += self.renderField( section.id, field, section.settings[ field.key ] );
			} );

			html += '</div>';

			$body.html( html );

			// Bind field change events for this panel.
			self.bindSettingsFields( section.id );

		},

		/**
		 * Render a single settings field.
		 *
		 * @param  {string} sectionId Section UUID.
		 * @param  {Object} field     Field schema object.
		 * @param  {*}      value     Current value.
		 * @return {string} HTML string.
		 */
		renderField: function ( sectionId, field, value ) {
			var self  = this;
			var key   = field.key;
			var label = field.label || key;
			var type  = field.type  || 'text';
			var id    = 'bcg-sb-field-' + sectionId.replace( /-/g, '' ) + '-' + key.replace( /[^a-z0-9]/gi, '' );

			if ( value === undefined || value === null ) {
				value = field.default !== undefined ? field.default : '';
			}

			var input = '';

			switch ( type ) {
				case 'text':
					input = '<input type="text" id="' + id + '" class="bcg-sb-field-input bcg-input" data-key="' + self.escAttr( key ) + '" value="' + self.escAttr( String( value ) ) + '" />';
					break;

				case 'textarea':
					input = '<textarea id="' + id + '" class="bcg-sb-field-input bcg-textarea" data-key="' + self.escAttr( key ) + '" rows="4">' + self.escHtml( String( value ) ) + '</textarea>';
					break;

				case 'range':
					var rangeMin  = field.min  !== undefined ? field.min  : 0;
					var rangeMax  = field.max  !== undefined ? field.max  : 100;
					var rangeStep = field.step !== undefined ? field.step : 1;
					var numVal    = parseFloat( value );
					if ( isNaN( numVal ) ) { numVal = rangeMin; }
					var rangePct  = rangeMax > rangeMin ? ( ( numVal - rangeMin ) / ( rangeMax - rangeMin ) ) * 100 : 0;
					input  = '<div class="bcg-sb-range-wrap">';
					input += '<input type="range" class="bcg-sb-range-input bcg-range" data-key="' + self.escAttr( field.key ) + '" min="' + rangeMin + '" max="' + rangeMax + '" step="' + rangeStep + '" value="' + self.escAttr( String( numVal ) ) + '" style="--range-progress:' + rangePct.toFixed(1) + '%">';
					input += '<span class="bcg-sb-range-value">' + self.escHtml( String( numVal ) ) + '</span>';
					input += '</div>';
					break;

				case 'number':
					input = '<input type="number" id="' + id + '" class="bcg-sb-field-input bcg-input" data-key="' + self.escAttr( key ) + '" value="' + self.escAttr( String( value ) ) + '" />';
					break;

				case 'color':
					input = '<div class="bcg-sb-color-row">' +
						'<input type="color" id="' + id + '-picker" class="bcg-sb-color-picker" data-key="' + self.escAttr( key ) + '" value="' + self.escAttr( String( value ) ) + '" />' +
						'<input type="text" id="' + id + '" class="bcg-sb-field-input bcg-input bcg-sb-color-text" data-key="' + self.escAttr( key ) + '" value="' + self.escAttr( String( value ) ) + '" maxlength="9" />' +
						'</div>';
					break;

				case 'toggle':
					var checked = value ? 'checked' : '';
					input = '<label class="bcg-sb-toggle">' +
						'<input type="checkbox" id="' + id + '" class="bcg-sb-field-input" data-key="' + self.escAttr( key ) + '" ' + checked + ' />' +
						'<span class="bcg-sb-toggle-slider"></span>' +
						'</label>';
					break;

				case 'select':
					var selLabel = String( value );
					$.each( field.options || [], function ( j, opt ) {
						if ( String( opt.value ) === String( value ) ) { selLabel = opt.label; return false; }
					} );
					input  = '<div class="bcg-select-wrapper bcg-sb-custom-select" data-key="' + self.escAttr( key ) + '">';
					input += '<button type="button" class="bcg-select-trigger" aria-haspopup="listbox" aria-expanded="false">';
					input += '<span class="bcg-select-value">' + self.escHtml( selLabel ) + '</span>';
					input += '<span class="material-icons-outlined" style="font-size:18px;flex-shrink:0;pointer-events:none;color:var(--bcg-text-muted);">expand_more</span>';
					input += '</button>';
					input += '<div class="bcg-select-menu bcg-dropdown-closed" role="listbox">';
					$.each( field.options || [], function ( j, opt ) {
						var isSel = String( opt.value ) === String( value );
						input += '<div class="bcg-select-option' + ( isSel ? ' is-selected' : '' ) + '" data-value="' + self.escAttr( String( opt.value ) ) + '" role="option" aria-selected="' + ( isSel ? 'true' : 'false' ) + '">' + self.escHtml( opt.label ) + '</div>';
					} );
					input += '</div></div>';
					break;

				case 'image':
					input = '<div class="bcg-sb-image-row">' +
						'<input type="text" id="' + id + '" class="bcg-sb-field-input bcg-input bcg-sb-image-url" data-key="' + self.escAttr( key ) + '" value="' + self.escAttr( String( value ) ) + '" placeholder="https://" />' +
						'<button type="button" class="bcg-btn-secondary bcg-btn-xs bcg-sb-image-btn" data-target="' + id + '">' +
						'<span class="material-icons-outlined">photo_library</span>' +
						'</button>' +
						'</div>';
					break;

								case 'product_select':
					input = self.renderProductSelectField( id, key, sectionId, value );
					break;

				case 'json':
					input = '<textarea id="' + id + '" class="bcg-sb-field-input bcg-textarea bcg-sb-json-field" data-key="' + self.escAttr( key ) + '" rows="4" spellcheck="false">' +
						self.escHtml( typeof value === 'string' ? value : JSON.stringify( value, null, 2 ) ) +
						'</textarea><span class="bcg-sb-json-hint">JSON format</span>';
					break;

				default:
					input = '<input type="text" id="' + id + '" class="bcg-sb-field-input bcg-input" data-key="' + self.escAttr( key ) + '" value="' + self.escAttr( String( value ) ) + '" />';
			}

			return '<div class="bcg-sb-field-row">' +
				'<label class="bcg-sb-field-label" for="' + id + '">' + self.escHtml( label ) + '</label>' +
				'<div class="bcg-sb-field-control">' + input + '</div>' +
				'</div>';
		},

		/**
		 * Bind change/input events for the currently rendered settings panel fields.
		 *
		 * @param {string} sectionId Section UUID.
		 */
		bindSettingsFields: function ( sectionId ) {
			var self  = this;
			var $body = $( '#bcg-sb-settings-body' );

			// Remove stale delegated handlers from previous section to prevent accumulation.
			$body.off( '.bcgFields' );

			// Text, textarea, number, json, image url, toggles.
			$body.on( 'input.bcgFields change.bcgFields', '.bcg-sb-field-input', function () {
				var $el  = $( this );
				var key  = $el.data( 'key' );
				var type = $el.attr( 'type' );
				var val;

				if ( type === 'checkbox' ) {
					val = $el.is( ':checked' );
				} else if ( type === 'number' ) {
					val = parseFloat( $el.val() ) || 0;
				} else {
					val = $el.val();
				}

				self.updateSetting( sectionId, key, val );
				self.debounceSectionPreview( sectionId );

				// Sync color text → picker.
				if ( $el.hasClass( 'bcg-sb-color-text' ) ) {
					var picker = $body.find( 'input[type="color"][data-key="' + key + '"]' );
					picker.val( val );
				}
			} );

			// Range slider — live value display + track fill.
			$body.on( 'input.bcgFields', '.bcg-sb-range-input', function () {
				var $input = $( this );
				var key    = $input.data( 'key' );
				var val    = parseFloat( $input.val() );
				var min    = parseFloat( $input.attr( 'min' ) || 0 );
				var max    = parseFloat( $input.attr( 'max' ) || 100 );
				var pct    = max > min ? ( ( val - min ) / ( max - min ) ) * 100 : 0;
				$input[0].style.setProperty( '--range-progress', pct.toFixed(1) + '%' );
				$input.closest( '.bcg-sb-range-wrap' ).find( '.bcg-sb-range-value' ).text( val );
				self.updateSetting( sectionId, key, val );
				self.debounceSectionPreview( sectionId );
			} );

			// Custom select — option chosen.
			$body.on( 'click.bcgFields', '.bcg-sb-custom-select .bcg-select-option', function ( e ) {
				e.stopPropagation();
				var $opt     = $( this );
				var $wrapper = $opt.closest( '.bcg-sb-custom-select' );
				var key      = $wrapper.data( 'key' );
				var val      = String( $opt.data( 'value' ) );
				$wrapper.find( '.bcg-select-value' ).text( $opt.text() );
				$wrapper.find( '.bcg-select-option' ).removeClass( 'is-selected' ).attr( 'aria-selected', 'false' );
				$opt.addClass( 'is-selected' ).attr( 'aria-selected', 'true' );
				$wrapper.find( '.bcg-select-menu' ).addClass( 'bcg-dropdown-closed' );
				$wrapper.find( '.bcg-select-trigger' ).attr( 'aria-expanded', 'false' );
				self.updateSetting( sectionId, key, val );
				self.debounceSectionPreview( sectionId );
			} );

			// Custom select — trigger toggle.
			$body.on( 'click.bcgFields', '.bcg-sb-custom-select .bcg-select-trigger', function ( e ) {
				e.stopPropagation();
				var $trigger = $( this );
				var $menu    = $trigger.next( '.bcg-select-menu' );
				var isOpen   = ! $menu.hasClass( 'bcg-dropdown-closed' );
				// Close all others inside settings body.
				$body.find( '.bcg-select-menu' ).addClass( 'bcg-dropdown-closed' );
				$body.find( '.bcg-select-trigger' ).attr( 'aria-expanded', 'false' );
				if ( ! isOpen ) {
					var rect = $trigger[0].getBoundingClientRect();
					$menu.css( {
						position:  'fixed',
						top:       ( rect.bottom + 2 ) + 'px',
						left:      rect.left + 'px',
						width:     rect.width + 'px',
						'z-index': 999999,
					} );
					$menu.removeClass( 'bcg-dropdown-closed' );
					$trigger.attr( 'aria-expanded', 'true' );
				}
			} );

			// Close dropdown when clicking outside.
			$( document ).off( 'click.bcgFieldsDoc' ).on( 'click.bcgFieldsDoc', function () {
				$body.find( '.bcg-select-menu' ).addClass( 'bcg-dropdown-closed' );
				$body.find( '.bcg-select-trigger' ).attr( 'aria-expanded', 'false' );
			} );

			// Color picker → sync text input.
			$body.on( 'input.bcgFields', '.bcg-sb-color-picker', function () {
				var $el  = $( this );
				var key  = $el.data( 'key' );
				var val  = $el.val();
				// Update the text sibling.
				$body.find( '.bcg-sb-color-text[data-key="' + key + '"]' ).val( val );
				self.updateSetting( sectionId, key, val );
				self.debounceSectionPreview( sectionId );
			} );

			// Media library button.
			$body.on( 'click.bcgFields', '.bcg-sb-image-btn', function () {
				var targetId = $( this ).data( 'target' );
				var $input   = $( '#' + targetId );
				var key      = $input.data( 'key' );

				if ( ! self.mediaFrame ) {
					self.mediaFrame = wp.media( {
						title:    self.i18n.select_image || 'Select Image',
						button:   { text: self.i18n.use_image || 'Use this image' },
						multiple: false,
						library:  { type: 'image' }
					} );
				}

				self.mediaFrame.off( 'select' ).on( 'select', function () {
					var attachment = self.mediaFrame.state().get( 'selection' ).first().toJSON();
					$input.val( attachment.url );
					self.updateSetting( sectionId, key, attachment.url );
					self.debounceSectionPreview( sectionId );
				} );

				self.mediaFrame.open();
			} );

			// Per-section AI button in settings panel.
			$body.on( 'click.bcgFields', '.bcg-sb-settings-ai-btn', function () {
				var id = $( this ).data( 'id' );
				self.generateSection( id );
			} );

			// Product select widgets — bind each one found in the panel.
			$body.find( '.bcg-sb-product-select' ).each( function () {
				self.bindProductSelectWidget( $( this ), sectionId );
			} );
		},


		// ── Product Select Widget ─────────────────────────────────────────

		/**
		 * Fire a debounced single-section preview update.
		 *
		 * @param {string} sectionId
		 */
		debounceSectionPreview: function ( sectionId ) {
			var self = this;
			if ( self._sectionPreviewTimer ) {
				clearTimeout( self._sectionPreviewTimer );
			}
			self._sectionPreviewTimer = setTimeout( function () {
				// updateSectionPreview checks internally if card preview is visible.
				self.updateSectionPreview( sectionId );
			}, 350 );
		},

		/**
		 * Render a section into its canvas-card inline preview iframe.
		 * Only fires if the card's preview area is currently visible.
		 *
		 * @param {string} sectionId
		 */
		updateSectionPreview: function ( sectionId ) {
			var self     = this;
			var section  = self.getSectionById( sectionId );
			var $card    = $( '.bcg-sb-section[data-id="' + sectionId + '"]' );
			var $preview = $card.find( '.bcg-sb-card-preview' );
			var $iframe  = $card.find( '.bcg-sb-card-iframe' );
			if ( ! section || ! $iframe.length || ! $preview.is( ':visible' ) ) { return; }

			$.ajax( {
				url:  bcg_section_builder.ajax_url,
				type: 'POST',
				data: {
					action:   'bcg_sb_preview',
					nonce:    bcg_section_builder.nonce,
					sections: JSON.stringify( [ section ] ),
				},
				success: function ( response ) {
					if ( response.success && response.data.html ) {
						var doc = $iframe[0].contentDocument || $iframe[0].contentWindow.document;
						doc.open();
						doc.write( response.data.html );
						doc.close();
						// Auto-size to content after load.
						setTimeout( function () {
							try {
								var h = doc.documentElement.scrollHeight || doc.body.scrollHeight;
								if ( h > 20 ) { $iframe.css( 'height', h + 'px' ); }
							} catch ( ex ) {}
						}, 300 );
					}
				},
			} );
		},

				success: function ( response ) {
					if ( response.success && response.data.html ) {
						var doc = $iframe[0].contentDocument || $iframe[0].contentWindow.document;
						doc.open();
						doc.write( response.data.html );
						doc.close();
						// Auto-size to content after load.
						setTimeout( function () {
							try {
								var h = doc.documentElement.scrollHeight || doc.body.scrollHeight;
								if ( h > 20 ) { $iframe.css( 'height', h + 'px' ); }
							} catch ( ex ) {}
						}, 200 );
					}
				},
			} );
		},

		/**
		 * Build the HTML for a product AJAX-select field.
		 *
		 * @param  {string} fieldId   Generated field ID prefix.
		 * @param  {string} key       Settings key (product_ids).
		 * @param  {string} sectionId Section UUID.
		 * @param  {string} value     Current comma-separated product IDs.
		 * @return {string} HTML string.
		 */
		renderProductSelectField: function ( fieldId, key, sectionId, value ) {
			var self    = this;
			var section = self.getSectionById( sectionId );
			var meta    = ( section && section.settings._product_meta ) ? section.settings._product_meta : {};
			var ids     = value ? String( value ).split( ',' ).map( function ( v ) { return parseInt( v, 10 ); } ).filter( Boolean ) : [];

			var tagsHtml = '';
			ids.forEach( function ( pid ) {
				var m = meta[ pid ] || {};
				tagsHtml += self.renderProductTag( pid, m.name || '#' + pid, m.image_url || '' );
			} );

			return '<div class="bcg-sb-product-select" data-key="' + self.escAttr( key ) + '" data-section-id="' + self.escAttr( sectionId ) + '">' +
				'<div class="bcg-sb-product-tags" id="bcg-sb-ptags-' + fieldId + '">' + tagsHtml + '</div>' +
				'<div class="bcg-product-search-wrapper">' +
					'<input type="text" class="bcg-sb-product-search-input bcg-input" id="bcg-sb-ps-' + fieldId + '" ' +
						'placeholder="Search products..." autocomplete="off" />' +
					'<div class="bcg-product-search-results" id="bcg-sb-ps-results-' + fieldId + '" style="display:none;"></div>' +
				'</div>' +
				'</div>';
		},

		/**
		 * Build HTML for a single selected-product tag chip.
		 *
		 * @param  {number} pid      Product ID.
		 * @param  {string} name     Product name.
		 * @param  {string} imageUrl Thumbnail URL.
		 * @return {string}
		 */
		renderProductTag: function ( pid, name, imageUrl ) {
			var imgHtml = imageUrl
				? '<img src="' + this.escAttr( imageUrl ) + '" alt="" class="bcg-manual-product-thumb" />' : '';
			return '<span class="bcg-manual-product-tag" data-product-id="' + parseInt( pid, 10 ) + '">' +
				imgHtml +
				'<span class="bcg-manual-product-name">' + this.escHtml( String( name ) ) + '</span>' +
				'<button type="button" class="bcg-manual-product-remove" title="Remove">&times;</button>' +
				'</span>';
		},

		/**
		 * Bind interaction for a product select widget inside the settings panel.
		 *
		 * @param {jQuery} $widget   The .bcg-sb-product-select element.
		 * @param {string} sectionId Section UUID.
		 */
		bindProductSelectWidget: function ( $widget, sectionId ) {
			var self    = this;
			var key     = $widget.data( 'key' );
			var $search = $widget.find( '.bcg-sb-product-search-input' );
			var $results = $widget.find( '.bcg-product-search-results' );
			var $tags    = $widget.find( '.bcg-sb-product-tags' );
			var timer    = null;

			function getIds() {
				var ids = [];
				$tags.find( '.bcg-manual-product-tag' ).each( function () {
					ids.push( parseInt( $( this ).data( 'product-id' ), 10 ) );
				} );
				return ids;
			}

			function sync() {
				self.updateSetting( sectionId, key, getIds().join( ',' ) );
				self.debounceSectionPreview( sectionId );
			}

			// Search on input.
			$search.on( 'input', function () {
				var q = $.trim( $( this ).val() );
				clearTimeout( timer );
				if ( q.length < 2 ) { $results.hide().empty(); return; }
				timer = setTimeout( function () {
					self.searchProductsForWidget( q, $results, getIds() );
				}, 350 );
			} );

			// Close on outside click.
			$( document ).on( 'click.bcgps-' + sectionId, function ( e ) {
				if ( ! $( e.target ).closest( $widget ).length ) { $results.hide(); }
			} );

			// Add product from results.
			$results.on( 'click', '.bcg-search-result-item', function () {
				var pid    = parseInt( $( this ).data( 'product-id' ), 10 );
				var name   = String( $( this ).data( 'product-name' ) );
				var imgUrl = String( $( this ).data( 'product-image' ) || '' );
				if ( getIds().indexOf( pid ) !== -1 ) { return; }
				$tags.append( self.renderProductTag( pid, name, imgUrl ) );
				// Store display meta so tags survive panel re-render.
				var section = self.getSectionById( sectionId );
				if ( section ) {
					if ( ! section.settings._product_meta ) { section.settings._product_meta = {}; }
					section.settings._product_meta[ pid ] = { name: name, image_url: imgUrl };
				}
				$search.val( '' );
				$results.hide().empty();
				sync();
			} );

			// Remove product tag.
			$tags.on( 'click', '.bcg-manual-product-remove', function () {
				$( this ).closest( '.bcg-manual-product-tag' ).remove();
				sync();
			} );
		},

		/**
		 * Fire AJAX product search and populate the results dropdown.
		 *
		 * @param {string} query       Keyword.
		 * @param {jQuery} $results    Dropdown container.
		 * @param {Array}  selectedIds Already-selected product IDs.
		 */
		searchProductsForWidget: function ( query, $results, selectedIds ) {
			var self = this;
			$.ajax( {
				url:  bcg_section_builder.ajax_url,
				type: 'POST',
				data: { action: 'bcg_search_products', nonce: bcg_section_builder.nonce, keyword: query },
				success: function ( res ) {
					if ( ! res.success || ! res.data.products ) { $results.hide().empty(); return; }
					var html = '';
					$.each( res.data.products, function ( i, p ) {
						var added = selectedIds.indexOf( p.id ) !== -1;
						html += '<div class="bcg-search-result-item' + ( added ? ' bcg-search-result-selected' : '' ) + '"' +
							' data-product-id="' + parseInt( p.id, 10 ) + '"' +
							' data-product-name="' + self.escAttr( p.name ) + '"' +
							' data-product-image="' + self.escAttr( p.image_url || '' ) + '">' +
							'<img src="' + self.escAttr( p.image_url || '' ) + '" alt="" class="bcg-search-result-img" />' +
							'<div class="bcg-search-result-info">' +
								'<span class="bcg-search-result-name">' + self.escHtml( p.name ) + '</span>' +
								'<span class="bcg-search-result-meta">' + p.price_html + '</span>' +
							'</div>' +
							( added ? '<span class="bcg-search-result-added">Added</span>' : '' ) +
							'</div>';
					} );
					if ( ! html ) { html = '<div class="bcg-search-no-results">No products found.</div>'; }
					$results.html( html ).show();
				},
				error: function () { $results.hide().empty(); }
			} );
		},

		// ── Toolbar ────────────────────────────────────────────────────────

		/**
		 * Bind toolbar button events.
		 */
		bindToolbar: function () {
			var self = this;

			$( '#bcg-sb-save-btn' ).on( 'click', function () {
				self.saveAsTemplate();
			} );

			$( '#bcg-sb-load-btn' ).on( 'click', function () {
				self.openLoadModal();
			} );

			$( '#bcg-sb-generate-btn' ).on( 'click', function () {
				self.generateAll();
			} );

			$( '#bcg-sb-preview-btn' ).on( 'click', function () {
				self.openPreviewModal();
			} );

			// Warn on navigation away with unsaved changes.
			$( window ).on( 'beforeunload', function () {
				if ( self.isDirty ) {
					return self.i18n.unsaved_changes || 'You have unsaved changes.';
				}
			} );
		},

		// ── Preview ───────────────────────────────────────────────────────

		/**
		 * Schedule a debounced preview update (300ms delay).
		 */
		debouncePreview: function () {
			var self = this;
			clearTimeout( self.previewTimer );
			self.previewTimer = setTimeout( function () {
				// Only auto-update if modal is open.
				if ( $( '#bcg-sb-preview-modal' ).is( ':visible' ) ) {
					self.updatePreview();
				}
			}, 300 );
		},

		/**
		 * Open the preview modal and render the current sections.
		 */
		openPreviewModal: function () {
			$( '#bcg-sb-preview-modal' ).show();
			this.updatePreview();
		},

		/**
		 * Fetch rendered HTML via AJAX and write to preview iframe.
		 */
		updatePreview: function () {
			var self    = this;
			var $iframe = $( '#bcg-sb-preview-iframe' );

			$.ajax( {
				url:  bcg_section_builder.ajax_url,
				type: 'POST',
				data: {
					action:   'bcg_sb_preview',
					nonce:    bcg_section_builder.nonce,
					sections: JSON.stringify( self.sections )
				},
				success: function ( res ) {
					if ( res.success && res.data.html ) {
						var doc = $iframe[ 0 ].contentDocument || $iframe[ 0 ].contentWindow.document;
						doc.open();
						doc.write( res.data.html );
						doc.close();
					}
				},
				error: function () {
					self.showStatus( self.i18n.preview_error || 'Preview error.', 'error' );
				}
			} );
		},

		// ── Preview Modal ─────────────────────────────────────────────────

		/**
		 * Bind preview modal controls.
		 */
		bindPreviewModal: function () {
			var self = this;

			$( '#bcg-sb-preview-close, #bcg-sb-preview-overlay' ).on( 'click', function () {
				$( '#bcg-sb-preview-modal' ).hide();
			} );

			// Desktop / Mobile toggle.
			$( document ).on( 'click', '.bcg-preview-toggle', function () {
				$( '.bcg-preview-toggle' ).removeClass( 'active' );
				$( this ).addClass( 'active' );

				var mode = $( this ).data( 'mode' );
				var $frame = $( '#bcg-sb-preview-iframe' );

				if ( mode === 'mobile' ) {
					$frame.css( { width: '375px', margin: '0 auto', display: 'block' } );
				} else {
					$frame.css( { width: '100%', margin: '', display: '' } );
				}
			} );
		},

		// ── Load Modal ────────────────────────────────────────────────────

		/**
		 * Bind load modal controls.
		 */
		bindLoadModal: function () {
			var self = this;

			$( '#bcg-sb-load-close, #bcg-sb-load-overlay' ).on( 'click', function () {
				$( '#bcg-sb-load-modal' ).hide();
			} );
		},

		/**
		 * Open the load modal and fetch saved templates.
		 */
		openLoadModal: function () {
			var self  = this;
			var $body = $( '#bcg-sb-load-body' );

			$( '#bcg-sb-load-modal' ).show();
			$body.html(
				'<p class="bcg-sb-loading-msg"><span class="material-icons-outlined bcg-spin">refresh</span> ' +
				( self.i18n.loading || 'Loading…' ) + '</p>'
			);

			$.ajax( {
				url:  bcg_section_builder.ajax_url,
				type: 'POST',
				data: { action: 'bcg_sb_get_templates', nonce: bcg_section_builder.nonce },
				success: function ( res ) {
					if ( ! res.success ) {
						$body.html( '<p class="bcg-error">' + self.escHtml( res.data.message || 'Error' ) + '</p>' );
						return;
					}

					var templates = res.data.templates || [];

					if ( templates.length === 0 ) {
						$body.html( '<p class="bcg-sb-no-templates">No saved templates yet.</p>' );
						return;
					}

					var html = '<table class="bcg-sb-templates-table"><thead><tr>' +
						'<th>Name</th><th>Updated</th><th>Actions</th></tr></thead><tbody>';

					$.each( templates, function ( i, t ) {
						html += '<tr data-id="' + parseInt( t.id, 10 ) + '">' +
							'<td>' + self.escHtml( t.name ) + '</td>' +
							'<td>' + self.escHtml( t.updated_at || '' ) + '</td>' +
							'<td>' +
							'<button type="button" class="bcg-btn-primary bcg-btn-xs bcg-sb-load-tmpl" data-id="' + parseInt( t.id, 10 ) + '">Load</button> ' +
							'<button type="button" class="bcg-btn-danger bcg-btn-xs bcg-sb-delete-tmpl" data-id="' + parseInt( t.id, 10 ) + '">Delete</button>' +
							'</td></tr>';
					} );

					html += '</tbody></table>';
					$body.html( html );

					// Load template.
					$body.on( 'click', '.bcg-sb-load-tmpl', function () {
						var id = parseInt( $( this ).data( 'id' ), 10 );
						self.loadTemplate( id );
					} );

					// Delete template.
					$body.on( 'click', '.bcg-sb-delete-tmpl', function () {
						var id = parseInt( $( this ).data( 'id' ), 10 );
						self.deleteTemplate( id );
					} );
				},
				error: function () {
					$body.html( '<p class="bcg-error">Failed to load templates.</p>' );
				}
			} );
		},

		// ── Template Save / Load / Delete ─────────────────────────────────

		/**
		 * Save the current canvas as a named template.
		 */
		saveAsTemplate: function () {
			var self  = this;
			var name  = $( '#bcg-sb-template-name' ).val().trim();

			if ( ! name ) {
				self.showStatus( self.i18n.name_required || 'Template name is required.', 'error' );
				$( '#bcg-sb-template-name' ).focus();
				return;
			}

			if ( self.sections.length === 0 ) {
				self.showStatus( self.i18n.no_sections || 'Add at least one section before saving.', 'error' );
				return;
			}

			var $btn = $( '#bcg-sb-save-btn' );
			$btn.prop( 'disabled', true ).text( self.i18n.saving || 'Saving…' );

			$.ajax( {
				url:  bcg_section_builder.ajax_url,
				type: 'POST',
				data: {
					action:      'bcg_sb_save_template',
					nonce:       bcg_section_builder.nonce,
					name:        name,
					description: '',
					id:          self.currentTemplateId,
					sections:    JSON.stringify( self.sections )
				},
				success: function ( res ) {
					$btn.prop( 'disabled', false ).html(
						'<span class="material-icons-outlined">save</span> Save Template'
					);

					if ( res.success ) {
						self.currentTemplateId = res.data.id;
						self.isDirty = false;
						$( '#bcg-sb-dirty-dot' ).hide();
						self.showStatus( self.i18n.saved || 'Template saved.', 'success' );
					} else {
						self.showStatus( res.data.message || self.i18n.save_error || 'Save failed.', 'error' );
					}
				},
				error: function () {
					$btn.prop( 'disabled', false ).html(
						'<span class="material-icons-outlined">save</span> Save Template'
					);
					self.showStatus( self.i18n.save_error || 'Save failed.', 'error' );
				}
			} );
		},

		/**
		 * Load a saved template by ID.
		 *
		 * @param {number} id Template ID.
		 */
		loadTemplate: function ( id ) {
			var self = this;

			if ( self.isDirty && ! confirm( self.i18n.confirm_load || 'Load template? Unsaved changes will be lost.' ) ) {
				return;
			}

			$.ajax( {
				url:  bcg_section_builder.ajax_url,
				type: 'POST',
				data: { action: 'bcg_sb_load_template', nonce: bcg_section_builder.nonce, id: id },
				success: function ( res ) {
					if ( res.success ) {
						self.sections          = res.data.sections || [];
						self.currentTemplateId = res.data.id;
						self.isDirty           = false;
						$( '#bcg-sb-dirty-dot' ).hide();
						$( '#bcg-sb-template-name' ).val( res.data.name || '' );
						self.selectedId = null;
						self.renderCanvas();
						self.renderSettingsPanel( null );
						$( '#bcg-sb-load-modal' ).hide();
						self.showStatus( 'Template loaded.', 'success' );
					} else {
						self.showStatus( res.data.message || 'Load failed.', 'error' );
					}
				},
				error: function () {
					self.showStatus( 'Load failed.', 'error' );
				}
			} );
		},

		/**
		 * Delete a saved template by ID.
		 *
		 * @param {number} id Template ID.
		 */
		deleteTemplate: function ( id ) {
			var self = this;

			if ( ! confirm( self.i18n.confirm_del_tmpl || 'Delete this template? This cannot be undone.' ) ) {
				return;
			}

			$.ajax( {
				url:  bcg_section_builder.ajax_url,
				type: 'POST',
				data: { action: 'bcg_sb_delete_template', nonce: bcg_section_builder.nonce, id: id },
				success: function ( res ) {
					if ( res.success ) {
						if ( self.currentTemplateId === id ) {
							self.currentTemplateId = 0;
						}
						self.openLoadModal(); // Refresh list.
					} else {
						self.showStatus( res.data.message || 'Delete failed.', 'error' );
					}
				},
				error: function () {
					self.showStatus( 'Delete failed.', 'error' );
				}
			} );
		},

		// ── AI Generation ─────────────────────────────────────────────────

		/**
		 * Generate AI content for all has_ai sections.
		 */
		generateAll: function () {
			var self    = this;
			var context = self.buildContext();
			var $btn    = $( '#bcg-sb-generate-btn' );

			$btn.prop( 'disabled', true );
			self.showStatus( self.i18n.generating || 'Generating AI content…', 'loading' );

			$.ajax( {
				url:     bcg_section_builder.ajax_url,
				type:    'POST',
				timeout: 120000,
				data: {
					action:   'bcg_sb_generate_all',
					nonce:    bcg_section_builder.nonce,
					sections: JSON.stringify( self.sections ),
					context:  JSON.stringify( context )
				},
				success: function ( res ) {
					$btn.prop( 'disabled', false );

					if ( res.success ) {
						self.sections = res.data.sections || self.sections;
						self.renderCanvas();

						// Re-render settings if a section is selected.
						if ( self.selectedId ) {
							var updated = self.getSectionById( self.selectedId );
							self.renderSettingsPanel( updated );
						}

						self.markDirty();
						self.debouncePreview();
						self.showStatus( 'AI content generated.', 'success' );
					} else {
						self.showStatus( res.data.message || self.i18n.generate_error || 'Generation failed.', 'error' );
					}
				},
				error: function () {
					$btn.prop( 'disabled', false );
					self.showStatus( self.i18n.generate_error || 'Generation failed.', 'error' );
				}
			} );
		},

		/**
		 * Generate AI content for a single section.
		 *
		 * @param {string} id Section UUID.
		 */
		generateSection: function ( id ) {
			var self    = this;
			var section = self.getSectionById( id );
			if ( ! section ) { return; }

			var context = self.buildContext();

			self.showStatus( self.i18n.generating || 'Generating…', 'loading' );

			$.ajax( {
				url:     bcg_section_builder.ajax_url,
				type:    'POST',
				timeout: 60000,
				data: {
					action:       'bcg_sb_generate_section',
					nonce:        bcg_section_builder.nonce,
					section_type: section.type,
					settings:     JSON.stringify( section.settings ),
					context:      JSON.stringify( context )
				},
				success: function ( res ) {
					if ( res.success ) {
						section.settings = res.data.settings || section.settings;

						if ( self.selectedId === id ) {
							self.renderSettingsPanel( section );
						}

						self.markDirty();
						self.debouncePreview();
						self.showStatus( 'AI content generated.', 'success' );
					} else {
						self.showStatus( res.data.message || self.i18n.generate_error || 'Generation failed.', 'error' );
					}
				},
				error: function () {
					self.showStatus( self.i18n.generate_error || 'Generation failed.', 'error' );
				}
			} );
		},

		/**
		 * Build the AI context object from toolbar inputs.
		 *
		 * @return {Object}
		 */
		buildContext: function () {
			return {
				theme:    $( '#bcg-sb-context-theme' ).val()    || '',
				tone:     $( '#bcg-sb-context-tone' ).val()     || 'Professional',
				language: $( '#bcg-sb-context-language' ).val() || 'English',
				products: []  // Products are resolved server-side from section settings.
			};
		},

		// ── Dirty State ───────────────────────────────────────────────────

		/**
		 * Mark the canvas as having unsaved changes.
		 */
		markDirty: function () {
			this.isDirty = true;
			$( '#bcg-sb-dirty-dot' ).show();
		},

		// ── Status Messages ───────────────────────────────────────────────

		/**
		 * Show a status message bar.
		 *
		 * @param {string} msg   Message text.
		 * @param {string} type  'success' | 'error' | 'loading'.
		 */
		showStatus: function ( msg, type ) {
			var $bar = $( '#bcg-sb-status' );
			$bar.removeClass( 'bcg-sb-status-success bcg-sb-status-error bcg-sb-status-loading' )
				.addClass( 'bcg-sb-status-' + ( type || 'success' ) )
				.text( msg )
				.show();

			if ( type !== 'loading' ) {
				clearTimeout( this._statusTimer );
				this._statusTimer = setTimeout( function () {
					$bar.fadeOut();
				}, 4000 );
			}
		},

		// ── Utilities ─────────────────────────────────────────────────────

		/**
		 * Generate a UUID v4.
		 *
		 * @return {string}
		 */
		generateUUID: function () {
			return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace( /[xy]/g, function ( c ) {
				var r = Math.random() * 16 | 0;
				var v = c === 'x' ? r : ( r & 0x3 | 0x8 );
				return v.toString( 16 );
			} );
		},

		/**
		 * Escape HTML special characters.
		 *
		 * @param  {string} str
		 * @return {string}
		 */
		escHtml: function ( str ) {
			return String( str )
				.replace( /&/g, '&amp;' )
				.replace( /</g, '&lt;' )
				.replace( />/g, '&gt;' )
				.replace( /"/g, '&quot;' )
				.replace( /'/g, '&#039;' );
		},

		/**
		 * Escape attribute value.
		 *
		 * @param  {string} str
		 * @return {string}
		 */
		escAttr: function ( str ) {
			return String( str ).replace( /"/g, '&quot;' );
		}
	};

	// Boot on DOM ready.
	$( function () {
		BCGSectionBuilder.init();
	} );

} )( jQuery );
