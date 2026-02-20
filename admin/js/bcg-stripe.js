/**
 * Brevo Campaign Generator - Stripe Payment Integration
 *
 * Handles the Stripe.js card element mounting, payment processing,
 * credit balance updates, and all UI feedback for the credits page.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

/* global Stripe, jQuery, bcg_stripe */
(function ($) {
	'use strict';

	/**
	 * BCGStripe — manages the entire Stripe payment flow.
	 */
	var BCGStripe = {

		/**
		 * Stripe.js instance.
		 */
		stripe: null,

		/**
		 * Stripe Elements instance.
		 */
		elements: null,

		/**
		 * The mounted Card Element.
		 */
		cardElement: null,

		/**
		 * The client secret for the current PaymentIntent.
		 */
		clientSecret: null,

		/**
		 * The currently selected pack key (0, 1, or 2).
		 */
		selectedPack: null,

		/**
		 * Whether a payment is currently being processed.
		 */
		isProcessing: false,

		/**
		 * Initialise the Stripe payment UI.
		 */
		init: function () {
			// Ensure Stripe.js and our localised data are available.
			if (typeof Stripe === 'undefined' || typeof bcg_stripe === 'undefined') {
				return;
			}

			if (!bcg_stripe.publishable_key) {
				this.showConfigError();
				return;
			}

			// Initialise Stripe.js.
			this.stripe = Stripe(bcg_stripe.publishable_key);
			this.elements = this.stripe.elements();

			this.bindEvents();
			this.initTransactionFilters();
		},

		/**
		 * Bind all event handlers.
		 */
		bindEvents: function () {
			var self = this;

			// Pack selection buttons.
			$(document).on('click', '.bcg-pack-purchase-btn', function (e) {
				e.preventDefault();
				if (self.isProcessing) {
					return;
				}
				var packKey = parseInt($(this).data('pack-key'), 10);
				self.selectPack(packKey, $(this));
			});

			// Payment form submit.
			$(document).on('submit', '#bcg-payment-form', function (e) {
				e.preventDefault();
				if (self.isProcessing) {
					return;
				}
				self.processPayment();
			});

			// Cancel payment.
			$(document).on('click', '#bcg-cancel-payment', function (e) {
				e.preventDefault();
				if (self.isProcessing) {
					return;
				}
				self.hidePaymentForm();
			});
		},

		/**
		 * Show an error when Stripe is not configured.
		 */
		showConfigError: function () {
			$('#bcg-stripe-payment-section').html(
				'<div class="bcg-notice bcg-notice-warning">' +
				'<p>' + bcg_stripe.i18n.stripe_not_configured + '</p>' +
				'</div>'
			);
		},

		/**
		 * Handle pack selection — creates a PaymentIntent and shows the card form.
		 *
		 * @param {number} packKey The pack index (0, 1, or 2).
		 * @param {jQuery} $button The clicked button element.
		 */
		selectPack: function (packKey, $button) {
			var self = this;
			this.selectedPack = packKey;

			// Disable all purchase buttons and show loading.
			$('.bcg-pack-purchase-btn').prop('disabled', true);
			$button.addClass('is-loading').text(bcg_stripe.i18n.preparing);

			// Clear any previous messages.
			this.clearMessages();

			// Create a PaymentIntent via AJAX.
			$.ajax({
				url: bcg_stripe.ajax_url,
				type: 'POST',
				data: {
					action: 'bcg_stripe_create_intent',
					nonce: bcg_stripe.nonce,
					pack_key: packKey
				},
				success: function (response) {
					$('.bcg-pack-purchase-btn').prop('disabled', false).removeClass('is-loading');
					$button.text(bcg_stripe.i18n.purchase);

					if (response.success && response.data.client_secret) {
						self.clientSecret = response.data.client_secret;
						self.showPaymentForm(response.data.pack);
					} else {
						var msg = (response.data && response.data.message) ?
							response.data.message : bcg_stripe.i18n.generic_error;
						self.showError(msg);
					}
				},
				error: function () {
					$('.bcg-pack-purchase-btn').prop('disabled', false).removeClass('is-loading');
					$button.text(bcg_stripe.i18n.purchase);
					self.showError(bcg_stripe.i18n.generic_error);
				}
			});
		},

		/**
		 * Show the Stripe payment form with the card element.
		 *
		 * @param {Object} pack The selected credit pack details.
		 */
		showPaymentForm: function (pack) {
			var $form = $('#bcg-payment-form');
			var $section = $('#bcg-stripe-payment-section');

			// Show the form section.
			$section.slideDown(200);

			// Update the summary.
			$('#bcg-payment-summary-credits').text(pack.credits);
			$('#bcg-payment-summary-price').text(
				bcg_stripe.currency_symbol + parseFloat(pack.price).toFixed(2)
			);

			// Mount the card element if not already done.
			if (!this.cardElement) {
				this.mountCardElement();
			} else {
				this.cardElement.clear();
			}

			// Scroll to the form.
			$('html, body').animate({
				scrollTop: $section.offset().top - 60
			}, 300);

			$form.show();
			$('#bcg-submit-payment').prop('disabled', false).text(bcg_stripe.i18n.pay_now);
		},

		/**
		 * Hide the payment form and reset state.
		 */
		hidePaymentForm: function () {
			$('#bcg-stripe-payment-section').slideUp(200);
			$('#bcg-payment-form').hide();
			this.clientSecret = null;
			this.selectedPack = null;
			this.clearMessages();
		},

		/**
		 * Mount the Stripe Card Element into the DOM.
		 */
		mountCardElement: function () {
			var style = {
				base: {
					color: '#1d2327',
					fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif',
					fontSmoothing: 'antialiased',
					fontSize: '15px',
					lineHeight: '1.5',
					'::placeholder': {
						color: '#a7aaad'
					}
				},
				invalid: {
					color: '#d63638',
					iconColor: '#d63638'
				}
			};

			this.cardElement = this.elements.create('card', {
				style: style,
				hidePostalCode: false
			});

			this.cardElement.mount('#bcg-card-element');

			// Listen for card validation errors.
			var self = this;
			this.cardElement.on('change', function (event) {
				var $errors = $('#bcg-card-errors');
				if (event.error) {
					$errors.text(event.error.message).show();
				} else {
					$errors.text('').hide();
				}
			});
		},

		/**
		 * Process the payment using stripe.confirmCardPayment().
		 */
		processPayment: function () {
			var self = this;

			if (!this.clientSecret) {
				this.showError(bcg_stripe.i18n.generic_error);
				return;
			}

			this.isProcessing = true;
			this.setLoadingState(true);

			this.stripe.confirmCardPayment(this.clientSecret, {
				payment_method: {
					card: this.cardElement
				}
			}).then(function (result) {
				if (result.error) {
					self.isProcessing = false;
					self.setLoadingState(false);
					self.showError(result.error.message);
				} else if (result.paymentIntent && result.paymentIntent.status === 'succeeded') {
					// Payment succeeded — confirm and add credits.
					self.confirmPaymentAndAddCredits(result.paymentIntent.id);
				} else {
					self.isProcessing = false;
					self.setLoadingState(false);
					self.showError(bcg_stripe.i18n.payment_failed);
				}
			});
		},

		/**
		 * Call the AJAX endpoint to confirm payment and add credits.
		 *
		 * @param {string} paymentIntentId The Stripe PaymentIntent ID.
		 */
		confirmPaymentAndAddCredits: function (paymentIntentId) {
			var self = this;

			$('#bcg-submit-payment').text(bcg_stripe.i18n.adding_credits);

			$.ajax({
				url: bcg_stripe.ajax_url,
				type: 'POST',
				data: {
					action: 'bcg_stripe_confirm',
					nonce: bcg_stripe.nonce,
					payment_intent_id: paymentIntentId
				},
				success: function (response) {
					self.isProcessing = false;
					self.setLoadingState(false);

					if (response.success) {
						self.onPaymentSuccess(response.data);
					} else {
						var msg = (response.data && response.data.message) ?
							response.data.message : bcg_stripe.i18n.generic_error;
						self.showError(msg);
					}
				},
				error: function () {
					self.isProcessing = false;
					self.setLoadingState(false);
					self.showError(bcg_stripe.i18n.generic_error);
				}
			});
		},

		/**
		 * Handle a successful payment — update the UI.
		 *
		 * @param {Object} data Response data from the server.
		 */
		onPaymentSuccess: function (data) {
			// Hide the payment form.
			this.hidePaymentForm();

			// Update the credit balance display.
			this.updateBalanceDisplay(data.new_balance);

			// Show the success message.
			this.showSuccess(data.message);

			// Refresh the transaction history if it exists on the page.
			this.refreshTransactionHistory();
		},

		/**
		 * Update all credit balance displays on the page.
		 *
		 * @param {number} newBalance The new credit balance.
		 */
		updateBalanceDisplay: function (newBalance) {
			var formatted = Math.floor(newBalance).toLocaleString();

			// Update the main balance display.
			$('.bcg-balance-amount').text(formatted);

			// Update the admin bar widget.
			$('#wp-admin-bar-bcg-credits .bcg-credit-widget strong').text(formatted);

			// Add a brief highlight animation.
			$('.bcg-balance-amount').addClass('bcg-balance-updated');
			setTimeout(function () {
				$('.bcg-balance-amount').removeClass('bcg-balance-updated');
			}, 2000);
		},

		/**
		 * Refresh the transaction history table via page reload of just that section.
		 */
		refreshTransactionHistory: function () {
			var $table = $('#bcg-transactions-table');
			if (!$table.length) {
				return;
			}

			// Reload the page to get the updated transaction history.
			// We use a short delay so the user sees the success message first.
			setTimeout(function () {
				window.location.reload();
			}, 1500);
		},

		/**
		 * Initialise the transaction history filter tabs.
		 */
		initTransactionFilters: function () {
			$(document).on('click', '.bcg-transaction-filter', function (e) {
				e.preventDefault();

				var $link = $(this);
				var type = $link.data('type');
				var url = new URL(window.location.href);

				url.searchParams.set('tx_type', type);
				url.searchParams.delete('tx_page');

				window.location.href = url.toString();
			});
		},

		/**
		 * Set the loading/processing state on the payment form.
		 *
		 * @param {boolean} loading Whether the form should be in loading state.
		 */
		setLoadingState: function (loading) {
			var $submit = $('#bcg-submit-payment');
			var $cancel = $('#bcg-cancel-payment');
			var $spinner = $('#bcg-payment-spinner');

			if (loading) {
				$submit.prop('disabled', true).text(bcg_stripe.i18n.processing);
				$cancel.prop('disabled', true);
				$spinner.show();
				$('.bcg-pack-purchase-btn').prop('disabled', true);
			} else {
				$submit.prop('disabled', false).text(bcg_stripe.i18n.pay_now);
				$cancel.prop('disabled', false);
				$spinner.hide();
				$('.bcg-pack-purchase-btn').prop('disabled', false);
			}
		},

		/**
		 * Display an error message to the user.
		 *
		 * @param {string} message The error message.
		 */
		showError: function (message) {
			this.clearMessages();
			var $container = $('#bcg-payment-messages');
			$container.html(
				'<div class="bcg-notice bcg-notice-error">' +
				'<p><strong>' + bcg_stripe.i18n.error_prefix + '</strong> ' +
				this.escapeHtml(message) + '</p>' +
				'</div>'
			).show();

			// Scroll to message.
			$('html, body').animate({
				scrollTop: $container.offset().top - 60
			}, 300);
		},

		/**
		 * Display a success message to the user.
		 *
		 * @param {string} message The success message.
		 */
		showSuccess: function (message) {
			this.clearMessages();
			var $container = $('#bcg-payment-messages');
			$container.html(
				'<div class="bcg-notice bcg-notice-success">' +
				'<p>' + this.escapeHtml(message) + '</p>' +
				'</div>'
			).show();

			// Scroll to message.
			$('html, body').animate({
				scrollTop: $container.offset().top - 60
			}, 300);
		},

		/**
		 * Clear all payment messages.
		 */
		clearMessages: function () {
			$('#bcg-payment-messages').empty().hide();
			$('#bcg-card-errors').text('').hide();
		},

		/**
		 * Escape HTML entities in a string to prevent XSS.
		 *
		 * @param {string} str The raw string.
		 * @return {string} The escaped string.
		 */
		escapeHtml: function (str) {
			var div = document.createElement('div');
			div.appendChild(document.createTextNode(str));
			return div.innerHTML;
		}
	};

	// Initialise when the DOM is ready.
	$(document).ready(function () {
		BCGStripe.init();
	});

})(jQuery);
