/**
 * Brevo Campaign Generator — Template Editor JS
 *
 * Manages the two-panel template editor: controls panel (left) with
 * settings tabs and code editor tab, plus live preview iframe (right).
 * All settings changes trigger a debounced AJAX call to
 * bcg_preview_template which renders the template server-side.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

/* global jQuery, wp, bcg_template_editor */
(function ($) {
	'use strict';

	/**
	 * Template Editor controller.
	 */
	var BCGTemplateEditor = {

		/**
		 * Template settings state object.
		 */
		templateSettings: {},

		/**
		 * CodeMirror editor instance.
		 */
		codeMirror: null,

		/**
		 * Debounce timer for preview updates.
		 */
		previewTimer: null,

		/**
		 * AJAX URL.
		 */
		ajaxUrl: '',

		/**
		 * Security nonce.
		 */
		nonce: '',

		/**
		 * Campaign ID (0 = editing default template).
		 */
		campaignId: 0,

		/**
		 * Whether the editor has unsaved changes.
		 */
		isDirty: false,

		/**
		 * Whether CodeMirror has been initialised.
		 */
		codeMirrorReady: false,

		/**
		 * Current template slug.
		 */
		currentSlug: 'classic',

		/**
		 * Initialise the template editor.
		 */
		init: function () {
			this.ajaxUrl     = $('#bcg-template-ajax-url').val() || '';
			this.nonce       = $('#bcg-template-nonce').val() || '';
			this.campaignId  = parseInt($('#bcg-template-campaign-id').val(), 10) || 0;
			this.currentSlug = $('#bcg-template-current-slug').val() || 'classic';

			// Parse initial settings from hidden field.
			var settingsJson = $('#bcg-template-settings-json').val();
			try {
				this.templateSettings = JSON.parse(settingsJson) || {};
			} catch (e) {
				this.templateSettings = {};
			}

			this.bindEvents();
			this.updatePreview();
		},

		/**
		 * Initialise CodeMirror on the template HTML textarea.
		 * Called lazily when the Code tab is first activated.
		 */
		initCodeMirror: function () {
			if (this.codeMirrorReady) {
				// Already initialised — just refresh.
				if (this.codeMirror) {
					this.codeMirror.refresh();
				}
				return;
			}

			var textarea = document.getElementById('bcg-template-html');
			if (!textarea) {
				return;
			}

			// Check if CodeMirror is available (loaded by WP).
			if (typeof wp !== 'undefined' && wp.codeEditor && wp.codeEditor.initialize) {
				var editorSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
				editorSettings.codemirror = $.extend({}, editorSettings.codemirror, {
					mode:          'htmlmixed',
					lineNumbers:   true,
					lineWrapping:  true,
					indentUnit:    4,
					tabSize:       4,
					indentWithTabs: true,
					autoCloseTags: true,
					matchBrackets: true,
					matchTags:     { bothTags: true },
					foldGutter:    true,
					gutters:       ['CodeMirror-linenumbers', 'CodeMirror-foldgutter']
				});

				var editorInstance = wp.codeEditor.initialize(textarea, editorSettings);
				this.codeMirror = editorInstance.codemirror;
			} else if (typeof CodeMirror !== 'undefined') {
				// Fallback to raw CodeMirror.
				this.codeMirror = CodeMirror.fromTextArea(textarea, {
					mode:          'htmlmixed',
					lineNumbers:   true,
					lineWrapping:  true,
					indentUnit:    4,
					tabSize:       4,
					autoCloseTags: true,
					matchBrackets: true
				});
			}

			if (this.codeMirror) {
				var self = this;
				this.codeMirror.on('change', function () {
					self.isDirty = true;
					self.debouncePreview();
				});

				// Set size to fill container.
				this.codeMirror.setSize(null, '100%');
			}

			this.codeMirrorReady = true;
		},

		/**
		 * Bind all event handlers.
		 */
		bindEvents: function () {
			var self = this;

			// Template chooser card click.
			$(document).on('click', '.bcg-template-card', function (e) {
				e.preventDefault();
				var slug = $(this).data('slug');
				if (slug && slug !== self.currentSlug) {
					self.switchTemplate(slug);
				}
			});

			// Tab nav dropdown: toggle open/close on trigger click.
			$(document).on('click', '#bcg-tab-nav-btn', function (e) {
				e.stopPropagation();
				var $menu  = $('#bcg-tab-nav-menu');
				var isOpen = !$menu.hasClass('bcg-dropdown-closed');
				if (isOpen) {
					$menu.addClass('bcg-dropdown-closed');
					$(this).attr('aria-expanded', 'false');
					$menu.attr('aria-hidden', 'true');
				} else {
					$menu.removeClass('bcg-dropdown-closed');
					$(this).attr('aria-expanded', 'true');
					$menu.attr('aria-hidden', 'false');
				}
			});

			// Close tab nav dropdown when clicking outside.
			$(document).on('click', function (e) {
				if (!$(e.target).closest('#bcg-tab-nav-dropdown').length) {
					$('#bcg-tab-nav-menu').addClass('bcg-dropdown-closed');
					$('#bcg-tab-nav-btn').attr('aria-expanded', 'false');
				}
			});

			// Settings tab navigation (includes code tab).
			$(document).on('click', '.bcg-template-tab', function (e) {
				e.preventDefault();
				var tab = $(this).data('tab');
				self.switchSettingsTab(tab);

				// Lazy-init CodeMirror when code tab first opens.
				if (tab === 'code') {
					setTimeout(function () {
						self.initCodeMirror();
					}, 50);
				}
			});

			// Settings field changes.
			$(document).on('change input', '.bcg-template-setting', function () {
				self.updateSettingFromInput($(this));
				self.isDirty = true;
				self.debouncePreview();
			});

			// Colour input value display.
			$(document).on('input', '.bcg-color-input', function () {
				$(this).siblings('.bcg-color-value').text($(this).val());
			});

			// Range input value display.
			$(document).on('input', '.bcg-range-field input[type="range"]', function () {
				$(this).siblings('.bcg-range-value').text($(this).val() + 'px');
			});

			// Token reference toggle.
			$(document).on('click', '#bcg-toggle-tokens', function (e) {
				e.preventDefault();
				$('#bcg-token-reference').slideToggle(200);
			});

			// Preview device toggle.
			$(document).on('click', '.bcg-preview-device', function (e) {
				e.preventDefault();
				self.switchPreviewDevice($(this).data('device'));
			});

			// Repeater: add row.
			$(document).on('click', '.bcg-repeater-add', function (e) {
				e.preventDefault();
				self.addRepeaterRow($(this).data('repeater'));
			});

			// Repeater: remove row.
			$(document).on('click', '.bcg-repeater-remove', function (e) {
				e.preventDefault();
				$(this).closest('.bcg-repeater-row').remove();
				self.collectRepeaterData();
				self.isDirty = true;
				self.debouncePreview();
			});

			// Repeater: field changes.
			$(document).on('change input', '.bcg-nav-link-label, .bcg-nav-link-url, .bcg-footer-link-label, .bcg-footer-link-url', function () {
				self.collectRepeaterData();
				self.isDirty = true;
				self.debouncePreview();
			});

			// Save as Default Template.
			$(document).on('click', '#bcg-save-default-template', function (e) {
				e.preventDefault();
				self.saveTemplate('default');
			});

			// Save to Campaign.
			$(document).on('click', '#bcg-save-to-campaign', function (e) {
				e.preventDefault();
				self.saveTemplate('campaign');
			});

			// Reset to Default.
			$(document).on('click', '#bcg-reset-template', function (e) {
				e.preventDefault();
				self.resetTemplate();
			});

			// Media uploader.
			$(document).on('click', '.bcg-upload-media', function (e) {
				e.preventDefault();
				self.openMediaUploader($(this).data('target'));
			});

			// Section overlay actions.
			$(document).on('click', '.bcg-overlay-move-up', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var sectionId = $(this).closest('.bcg-section-overlay').data('section-id');
				self.handleSectionMove(sectionId, 'up');
			});

			$(document).on('click', '.bcg-overlay-move-down', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var sectionId = $(this).closest('.bcg-section-overlay').data('section-id');
				self.handleSectionMove(sectionId, 'down');
			});

			$(document).on('click', '.bcg-overlay-duplicate', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var sectionId = $(this).closest('.bcg-section-overlay').data('section-id');
				self.handleSectionDuplicate(sectionId);
			});

			$(document).on('click', '.bcg-overlay-delete', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var sectionId = $(this).closest('.bcg-section-overlay').data('section-id');
				self.handleSectionDelete(sectionId);
			});

			// Warn about unsaved changes.
			$(window).on('beforeunload', function () {
				if (self.isDirty) {
					return true;
				}
			});
		},

		/**
		 * Switch the active settings tab.
		 *
		 * @param {string} tabName Tab identifier.
		 */
		switchSettingsTab: function (tabName) {
			$('.bcg-template-tab').removeClass('active');
			$('.bcg-template-tab[data-tab="' + tabName + '"]').addClass('active');
			$('.bcg-template-settings-panel').removeClass('active');
			$('.bcg-template-settings-panel[data-panel="' + tabName + '"]').addClass('active');

			// Update dropdown trigger label and close the menu.
			var label = $('.bcg-template-tab[data-tab="' + tabName + '"]').clone().children('span.material-icons-outlined').remove().end().text().trim();
			$('#bcg-tab-nav-label').text(label);
			$('#bcg-tab-nav-menu').addClass('bcg-dropdown-closed');
			$('#bcg-tab-nav-btn').attr('aria-expanded', 'false');
			$('#bcg-tab-nav-menu').attr('aria-hidden', 'true');
		},

		/**
		 * Switch preview between desktop and mobile widths.
		 *
		 * @param {string} device Either 'desktop' or 'mobile'.
		 */
		switchPreviewDevice: function (device) {
			var self = this;

			$('.bcg-preview-device').removeClass('active');
			$('.bcg-preview-device[data-device="' + device + '"]').addClass('active');

			var iframe = $('#bcg-preview-iframe');
			iframe.removeClass('bcg-preview-desktop bcg-preview-mobile');
			iframe.addClass('bcg-preview-' + device);

			// Update overlay container width to match device mode.
			var $container = $('.bcg-preview-overlay-container');
			if (device === 'mobile') {
				$container.css('max-width', '375px');
			} else {
				$container.css('max-width', '660px');
			}

			// Rebuild overlays after layout settles (transition is 0.3s).
			setTimeout(function () {
				self.autoSizeIframe();
				self.buildSectionOverlays();
			}, 350);
		},

		/**
		 * Update a setting value from an input element.
		 *
		 * @param {jQuery} $input The input element.
		 */
		updateSettingFromInput: function ($input) {
			var settingKey = $input.data('setting');
			if (!settingKey) {
				return;
			}

			var value;
			if ($input.is(':checkbox')) {
				value = $input.is(':checked');
			} else {
				value = $input.val();
			}

			// Parse numeric values.
			if ($input.attr('type') === 'number' || $input.attr('type') === 'range') {
				value = parseInt(value, 10) || 0;
			}

			this.templateSettings[settingKey] = value;
		},

		/**
		 * Collect nav and footer link data from repeater rows.
		 */
		collectRepeaterData: function () {
			// Navigation links.
			var navLinks = [];
			$('#bcg-nav-links-repeater .bcg-repeater-row').each(function () {
				var label = $(this).find('.bcg-nav-link-label').val() || '';
				var url   = $(this).find('.bcg-nav-link-url').val() || '';
				if (label || url) {
					navLinks.push({ label: label, url: url });
				}
			});
			this.templateSettings.nav_links = navLinks;

			// Footer links.
			var footerLinks = [];
			$('#bcg-footer-links-repeater .bcg-repeater-row').each(function () {
				var label = $(this).find('.bcg-footer-link-label').val() || '';
				var url   = $(this).find('.bcg-footer-link-url').val() || '';
				if (label || url) {
					footerLinks.push({ label: label, url: url });
				}
			});
			this.templateSettings.footer_links = footerLinks;
		},

		/**
		 * Add a new repeater row.
		 *
		 * @param {string} repeater Either 'nav-links' or 'footer-links'.
		 */
		addRepeaterRow: function (repeater) {
			var container, labelClass, urlClass, placeholder;

			if (repeater === 'nav-links') {
				container   = $('#bcg-nav-links-repeater');
				labelClass  = 'bcg-nav-link-label';
				urlClass    = 'bcg-nav-link-url';
				placeholder = 'https://...';
			} else {
				container   = $('#bcg-footer-links-repeater');
				labelClass  = 'bcg-footer-link-label';
				urlClass    = 'bcg-footer-link-url';
				placeholder = 'https://... or {{unsubscribe_url}}';
			}

			var index = container.find('.bcg-repeater-row').length;
			var row   = $(
				'<div class="bcg-repeater-row" data-index="' + index + '">' +
					'<input type="text" class="' + labelClass + '" value="" placeholder="Label" />' +
					'<input type="' + (repeater === 'nav-links' ? 'url' : 'text') + '" class="' + urlClass + '" value="" placeholder="' + placeholder + '" />' +
					'<button type="button" class="button bcg-repeater-remove" title="Remove">&times;</button>' +
				'</div>'
			);

			container.append(row);
		},

		/**
		 * Switch to a different template by slug.
		 *
		 * @param {string} slug The template slug to load.
		 */
		switchTemplate: function (slug) {
			var self = this;

			// Confirm if there are unsaved changes.
			if (this.isDirty) {
				if (!confirm(bcg_template_editor.i18n.confirm_switch || 'Switching templates will replace your current HTML and settings. Any unsaved changes will be lost. Continue?')) {
					return;
				}
			}

			// Update card active state.
			$('.bcg-template-card').removeClass('bcg-template-card-active');
			$('.bcg-template-card-badge').remove();
			$('.bcg-template-card[data-slug="' + slug + '"]')
				.addClass('bcg-template-card-active')
				.append('<span class="bcg-template-card-badge">' + (bcg_template_editor.i18n.current || 'Current') + '</span>');

			$('#bcg-preview-status').text(bcg_template_editor.i18n.loading_template || 'Loading template...');

			$.ajax({
				url:      this.ajaxUrl,
				type:     'POST',
				dataType: 'json',
				data: {
					action:        'bcg_load_template',
					nonce:         this.nonce,
					template_slug: slug
				},
				success: function (response) {
					if (response.success && response.data) {
						// Update current slug.
						self.currentSlug = slug;
						$('#bcg-template-current-slug').val(slug);

						// Update CodeMirror / textarea with new HTML.
						if (response.data.html) {
							if (self.codeMirror) {
								self.codeMirror.setValue(response.data.html);
							} else {
								$('#bcg-template-html').val(response.data.html);
							}
						}

						// Update settings.
						if (response.data.settings) {
							self.templateSettings = response.data.settings;
							// Reset section order when switching templates.
							self.templateSettings.section_order = null;
							self.populateSettingsFields();
						}

						self.isDirty = false;
						self.updatePreview();
						$('#bcg-preview-status').text('');
						self.showNotice('success', bcg_template_editor.i18n.template_loaded || 'Template loaded successfully.');
					} else {
						$('#bcg-preview-status').text('');
						self.showNotice('error', (response.data && response.data.message) || (bcg_template_editor.i18n.template_error || 'Failed to load template.'));
					}
				},
				error: function () {
					$('#bcg-preview-status').text('');
					self.showNotice('error', bcg_template_editor.i18n.template_error || 'Failed to load template.');
				}
			});
		},

		/**
		 * Debounce the preview update (300ms).
		 */
		debouncePreview: function () {
			var self = this;

			if (this.previewTimer) {
				clearTimeout(this.previewTimer);
			}

			this.previewTimer = setTimeout(function () {
				self.updatePreview();
			}, 300);
		},

		/**
		 * Send AJAX request to render template preview and update the iframe.
		 */
		updatePreview: function () {
			var self = this;

			// Collect current state.
			this.collectRepeaterData();

			var templateHtml = '';
			if (this.codeMirror) {
				templateHtml = this.codeMirror.getValue();
			} else {
				templateHtml = $('#bcg-template-html').val();
			}

			$('#bcg-preview-status').text(bcg_template_editor.i18n.updating || 'Updating...');

			$.ajax({
				url:      this.ajaxUrl,
				type:     'POST',
				dataType: 'json',
				data: {
					action:            'bcg_preview_template',
					nonce:             this.nonce,
					template_html:     templateHtml,
					template_settings: JSON.stringify(this.templateSettings)
				},
				success: function (response) {
					if (response.success && response.data && response.data.html) {
						self.updateIframeContent(response.data.html);
						$('#bcg-preview-status').text('');
					} else {
						$('#bcg-preview-status')
							.text(bcg_template_editor.i18n.preview_error || 'Preview error')
							.addClass('bcg-text-error');

						setTimeout(function () {
							$('#bcg-preview-status').text('').removeClass('bcg-text-error');
						}, 3000);
					}
				},
				error: function () {
					// On AJAX error, render client-side with basic token replacement.
					self.updateIframeContent(templateHtml);
					$('#bcg-preview-status').text('');
				}
			});
		},

		/**
		 * Write HTML content into the preview iframe.
		 *
		 * @param {string} html The full HTML document.
		 */
		updateIframeContent: function (html) {
			var self   = this;
			var iframe = document.getElementById('bcg-preview-iframe');
			if (!iframe) {
				return;
			}

			// Use srcdoc for modern browsers.
			iframe.srcdoc = html;

			// Rebuild overlays after iframe loads and auto-size iframe to content.
			iframe.onload = function () {
				setTimeout(function () {
					self.autoSizeIframe();
					self.buildSectionOverlays();
				}, 100);
			};
		},

		/**
		 * Auto-size the iframe height to match its content.
		 *
		 * This eliminates internal scrolling so the overlays
		 * align correctly with visible content.
		 */
		autoSizeIframe: function () {
			var iframe = document.getElementById('bcg-preview-iframe');
			if (!iframe) {
				return;
			}

			var iframeDoc;
			try {
				iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
			} catch (e) {
				return;
			}
			if (!iframeDoc || !iframeDoc.body) {
				return;
			}

			// Temporarily collapse to 1px so that body { height: 100% } in email
			// templates does not inflate scrollHeight beyond actual content height.
			iframe.style.height = '1px';

			var contentHeight = Math.max(
				iframeDoc.body.scrollHeight,
				iframeDoc.documentElement ? iframeDoc.documentElement.scrollHeight : 0
			);

			if (contentHeight > 0) {
				iframe.style.height = contentHeight + 'px';
			}
		},

		/**
		 * Build section overlays on top of the preview iframe.
		 *
		 * Reads tr[data-bcg-section] elements from the iframe content and creates
		 * floating overlay divs with action buttons (move up/down, duplicate, delete).
		 */
		buildSectionOverlays: function () {
			var self       = this;
			var $container = $('#bcg-section-overlays');
			$container.empty();

			var iframe = document.getElementById('bcg-preview-iframe');
			if (!iframe) {
				return;
			}

			var iframeDoc;
			try {
				iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
			} catch (e) {
				return;
			}
			if (!iframeDoc || !iframeDoc.body) {
				return;
			}

			// Find all elements with the data-bcg-section attribute (<tr> or <table>).
			var sectionRows = iframeDoc.querySelectorAll('[data-bcg-section]');
			if (!sectionRows.length) {
				return;
			}

			// Sync overlay container height to iframe scrollable content.
			var iframeBody = iframeDoc.body;
			var docHeight  = Math.max(
				iframeBody.scrollHeight,
				iframeDoc.documentElement ? iframeDoc.documentElement.scrollHeight : 0
			);
			$container.css('height', docHeight + 'px');

			// Build an ordered list of section IDs from iframe DOM.
			var sectionIds = [];
			sectionRows.forEach(function (el) {
				sectionIds.push(el.getAttribute('data-bcg-section'));
			});

			// Store DOM section order locally for overlay positioning and as the
			// source of truth when the user first explicitly reorders a section.
			// Do NOT write into templateSettings.section_order here — that property
			// should only be set when the user deliberately moves/duplicates/deletes
			// a section, so that reorder_sections() is never called on a plain
			// font/colour change where no reordering was requested.
			this.sectionOrder = sectionIds.slice();

			var sectionNames = (bcg_template_editor && bcg_template_editor.section_names) || {};

			sectionRows.forEach(function (trEl, index) {
				var sectionId = trEl.getAttribute('data-bcg-section');
				var type      = sectionId.replace(/-\d+$/, '').replace(/-dup\d+$/, '');
				var label     = sectionNames[type] || type.charAt(0).toUpperCase() + type.slice(1);

				// Use offsetTop for the position (document-relative, not viewport-relative).
				var top = trEl.offsetTop;

				// Walk up offset parents to get the absolute position in the document.
				var offsetParent = trEl.offsetParent;
				while (offsetParent) {
					top += offsetParent.offsetTop;
					offsetParent = offsetParent.offsetParent;
				}

				// Determine height: distance to next section row, or to end of document.
				var height;
				if (index + 1 < sectionRows.length) {
					var nextEl  = sectionRows[index + 1];
					var nextTop = nextEl.offsetTop;
					var nextParent = nextEl.offsetParent;
					while (nextParent) {
						nextTop += nextParent.offsetTop;
						nextParent = nextParent.offsetParent;
					}
					height = nextTop - top;
				} else {
					// Last section — extends to end of document.
					height = docHeight - top;
				}

				if (height < 20) {
					height = 20;
				}

				var $overlay = $(
					'<div class="bcg-section-overlay" data-section-id="' + self.escAttr(sectionId) + '" ' +
					'style="top:' + top + 'px;height:' + height + 'px;">' +
						'<span class="bcg-section-overlay-label">' + $('<span>').text(label).html() + '</span>' +
						'<div class="bcg-section-overlay-actions">' +
							'<button type="button" class="bcg-overlay-btn bcg-overlay-move-up" title="Move Up">&uarr;</button>' +
							'<button type="button" class="bcg-overlay-btn bcg-overlay-move-down" title="Move Down">&darr;</button>' +
							'<button type="button" class="bcg-overlay-btn bcg-overlay-delete" title="Delete">&times;</button>' +
						'</div>' +
					'</div>'
				);

				$container.append($overlay);
			});
		},

		/**
		 * Handle moving a section up or down.
		 *
		 * @param {string} sectionId The section ID.
		 * @param {string} direction Either 'up' or 'down'.
		 */
		handleSectionMove: function (sectionId, direction) {
			// On the first explicit reorder, bootstrap section_order from the
			// current DOM order captured by buildSectionOverlays().
			var order = this.templateSettings.section_order;
			if (!order || !Array.isArray(order)) {
				if (!this.sectionOrder || !this.sectionOrder.length) {
					return;
				}
				order = this.sectionOrder.slice();
			}

			var index = order.indexOf(sectionId);
			if (index === -1) {
				return;
			}

			var swapIndex = direction === 'up' ? index - 1 : index + 1;
			if (swapIndex < 0 || swapIndex >= order.length) {
				return;
			}

			// Swap.
			var temp = order[swapIndex];
			order[swapIndex] = order[index];
			order[index] = temp;

			this.templateSettings.section_order = order;
			this.isDirty = true;
			this.debouncePreview();
		},

		/**
		 * Handle duplicating a section.
		 *
		 * @param {string} sectionId The section ID to duplicate.
		 */
		handleSectionDuplicate: function (sectionId) {
			var order = this.templateSettings.section_order;
			if (!order || !Array.isArray(order)) {
				if (!this.sectionOrder || !this.sectionOrder.length) {
					return;
				}
				order = this.sectionOrder.slice();
			}

			var index = order.indexOf(sectionId);
			if (index === -1) {
				return;
			}

			// Create a unique duplicate ID.
			var dupCount = 1;
			var baseSectionId = sectionId.replace(/-dup\d+$/, '');
			var newId;
			do {
				newId = baseSectionId + '-dup' + dupCount;
				dupCount++;
			} while (order.indexOf(newId) !== -1);

			// Insert copy after the original.
			order.splice(index + 1, 0, newId);

			this.templateSettings.section_order = order;
			this.isDirty = true;
			this.debouncePreview();
		},

		/**
		 * Handle deleting a section.
		 *
		 * @param {string} sectionId The section ID to delete.
		 */
		handleSectionDelete: function (sectionId) {
			var order = this.templateSettings.section_order;
			if (!order || !Array.isArray(order)) {
				if (!this.sectionOrder || !this.sectionOrder.length) {
					return;
				}
				order = this.sectionOrder.slice();
			}

			if (!confirm(bcg_template_editor.i18n.confirm_delete_section || 'Delete this section?')) {
				return;
			}

			var index = order.indexOf(sectionId);
			if (index === -1) {
				return;
			}

			order.splice(index, 1);
			this.templateSettings.section_order = order;
			this.isDirty = true;
			this.debouncePreview();
		},

		/**
		 * Save the template and/or settings.
		 *
		 * @param {string} target Either 'default' or 'campaign'.
		 */
		saveTemplate: function (target) {
			var self = this;

			this.collectRepeaterData();

			var templateHtml = '';
			if (this.codeMirror) {
				templateHtml = this.codeMirror.getValue();
			} else {
				templateHtml = $('#bcg-template-html').val();
			}

			var $button;
			if (target === 'default') {
				$button = $('#bcg-save-default-template');
			} else {
				$button = $('#bcg-save-to-campaign');
			}

			var originalText = $button.text();
			$button.prop('disabled', true).text(bcg_template_editor.i18n.saving || 'Saving...');

			$.ajax({
				url:      this.ajaxUrl,
				type:     'POST',
				dataType: 'json',
				data: {
					action:            'bcg_update_template',
					nonce:             this.nonce,
					target:            target,
					campaign_id:       this.campaignId,
					template_html:     templateHtml,
					template_settings: JSON.stringify(this.templateSettings)
				},
				success: function (response) {
					if (response.success) {
						self.isDirty = false;
						self.showNotice('success', response.data.message || (bcg_template_editor.i18n.saved || 'Template saved.'));
					} else {
						self.showNotice('error', response.data.message || (bcg_template_editor.i18n.save_error || 'Failed to save template.'));
					}
				},
				error: function () {
					self.showNotice('error', bcg_template_editor.i18n.save_error || 'Failed to save template.');
				},
				complete: function () {
					$button.prop('disabled', false).text(originalText);
				}
			});
		},

		/**
		 * Reset the template to the bundled default.
		 */
		resetTemplate: function () {
			if (!confirm(bcg_template_editor.i18n.confirm_reset || 'Are you sure you want to reset the template to the default? This cannot be undone.')) {
				return;
			}

			var self    = this;
			var $button = $('#bcg-reset-template');
			var originalText = $button.text();

			$button.prop('disabled', true).text(bcg_template_editor.i18n.resetting || 'Resetting...');

			$.ajax({
				url:      this.ajaxUrl,
				type:     'POST',
				dataType: 'json',
				data: {
					action: 'bcg_reset_template',
					nonce:  this.nonce
				},
				success: function (response) {
					if (response.success && response.data) {
						// Update CodeMirror with the default HTML.
						if (response.data.html && self.codeMirror) {
							self.codeMirror.setValue(response.data.html);
						} else if (response.data.html) {
							$('#bcg-template-html').val(response.data.html);
						}

						// Update settings.
						if (response.data.settings) {
							self.templateSettings = response.data.settings;
							self.populateSettingsFields();
						}

						self.isDirty = false;
						self.updatePreview();
						self.showNotice('success', response.data.message || (bcg_template_editor.i18n.reset_success || 'Template reset to default.'));
					} else {
						self.showNotice('error', response.data.message || (bcg_template_editor.i18n.reset_error || 'Failed to reset template.'));
					}
				},
				error: function () {
					self.showNotice('error', bcg_template_editor.i18n.reset_error || 'Failed to reset template.');
				},
				complete: function () {
					$button.prop('disabled', false).text(originalText);
				}
			});
		},

		/**
		 * Populate all settings fields from the templateSettings object.
		 * Used after resetting or loading a different template.
		 */
		populateSettingsFields: function () {
			var settings = this.templateSettings;

			// Simple scalar fields.
			$('.bcg-template-setting').each(function () {
				var $input     = $(this);
				var settingKey = $input.data('setting');

				if (!settingKey || typeof settings[settingKey] === 'undefined') {
					return;
				}

				var value = settings[settingKey];

				if ($input.is(':checkbox')) {
					$input.prop('checked', !!value);
				} else {
					$input.val(value);
				}

				// Update colour value display.
				if ($input.hasClass('bcg-color-input')) {
					$input.siblings('.bcg-color-value').text(value);
				}

				// Update range value display.
				if ($input.attr('type') === 'range') {
					$input.siblings('.bcg-range-value').text(value + 'px');
				}
			});

			// Nav links repeater.
			var navLinks = settings.nav_links || [];
			var $navRepeater = $('#bcg-nav-links-repeater');
			$navRepeater.empty();
			if (navLinks.length === 0) {
				navLinks = [{ label: '', url: '' }];
			}
			for (var i = 0; i < navLinks.length; i++) {
				$navRepeater.append(
					'<div class="bcg-repeater-row" data-index="' + i + '">' +
						'<input type="text" class="bcg-nav-link-label" value="' + this.escAttr(navLinks[i].label || '') + '" placeholder="Label" />' +
						'<input type="url" class="bcg-nav-link-url" value="' + this.escAttr(navLinks[i].url || '') + '" placeholder="https://..." />' +
						'<button type="button" class="button bcg-repeater-remove" title="Remove">&times;</button>' +
					'</div>'
				);
			}

			// Footer links repeater.
			var footerLinks = settings.footer_links || [];
			var $footerRepeater = $('#bcg-footer-links-repeater');
			$footerRepeater.empty();
			if (footerLinks.length === 0) {
				footerLinks = [{ label: '', url: '' }];
			}
			for (var j = 0; j < footerLinks.length; j++) {
				$footerRepeater.append(
					'<div class="bcg-repeater-row" data-index="' + j + '">' +
						'<input type="text" class="bcg-footer-link-label" value="' + this.escAttr(footerLinks[j].label || '') + '" placeholder="Label" />' +
						'<input type="text" class="bcg-footer-link-url" value="' + this.escAttr(footerLinks[j].url || '') + '" placeholder="https://... or {{unsubscribe_url}}" />' +
						'<button type="button" class="button bcg-repeater-remove" title="Remove">&times;</button>' +
					'</div>'
				);
			}
		},

		/**
		 * Open the WordPress media uploader.
		 *
		 * @param {string} targetId The ID of the input to receive the URL.
		 */
		openMediaUploader: function (targetId) {
			var self = this;

			if (typeof wp === 'undefined' || !wp.media) {
				alert('WordPress media library is not available.');
				return;
			}

			var frame = wp.media({
				title:    bcg_template_editor.i18n.select_image || 'Select Image',
				multiple: false,
				library:  { type: 'image' },
				button:   { text: bcg_template_editor.i18n.use_image || 'Use this image' }
			});

			frame.on('select', function () {
				var attachment = frame.state().get('selection').first().toJSON();
				var $target    = $('#' + targetId);

				$target.val(attachment.url).trigger('change');
				self.isDirty = true;
			});

			frame.open();
		},

		/**
		 * Show an admin notice.
		 *
		 * @param {string} type    Notice type: 'success', 'error', 'info'.
		 * @param {string} message The message text.
		 */
		showNotice: function (type, message) {
			var $notice = $(
				'<div class="notice notice-' + type + ' is-dismissible bcg-template-notice">' +
					'<p>' + $('<span>').text(message).html() + '</p>' +
					'<button type="button" class="notice-dismiss">' +
						'<span class="screen-reader-text">Dismiss</span>' +
					'</button>' +
				'</div>'
			);

			// Remove any existing template notices.
			$('.bcg-template-notice').remove();

			$('.bcg-template-editor-header').after($notice);

			// Auto-dismiss.
			setTimeout(function () {
				$notice.fadeOut(300, function () {
					$(this).remove();
				});
			}, 5000);

			// Manual dismiss.
			$notice.find('.notice-dismiss').on('click', function () {
				$notice.fadeOut(200, function () {
					$(this).remove();
				});
			});
		},

		/**
		 * Escape a string for safe use in an HTML attribute.
		 *
		 * @param {string} str The raw string.
		 * @return {string} Escaped string.
		 */
		escAttr: function (str) {
			var div = document.createElement('div');
			div.appendChild(document.createTextNode(str));
			return div.innerHTML.replace(/"/g, '&quot;');
		}
	};

	// Initialise on document ready.
	$(document).ready(function () {
		if ($('.bcg-template-editor-wrap').length) {
			BCGTemplateEditor.init();
		}
	});

})(jQuery);
