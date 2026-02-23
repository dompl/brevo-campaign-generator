<?php
/**
 * OpenAI GPT integration.
 *
 * Handles all text generation tasks for the Brevo Campaign Generator plugin
 * using the OpenAI Chat Completions API. Provides methods for generating
 * subject lines, headlines, descriptions, preview text, and coupon
 * suggestions from WooCommerce product data.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_OpenAI
 *
 * OpenAI GPT client for email marketing copy generation.
 *
 * @since 1.0.0
 */
class BCG_OpenAI {

	/**
	 * OpenAI Chat Completions API endpoint.
	 *
	 * @var string
	 */
	const API_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

	/**
	 * Request timeout in seconds.
	 *
	 * @var int
	 */
	const REQUEST_TIMEOUT = 30;

	/**
	 * Maximum number of error log entries to retain.
	 *
	 * @var int
	 */
	const MAX_ERROR_LOG_ENTRIES = 50;

	/**
	 * Temperature for creative tasks (headlines, descriptions, subject lines).
	 *
	 * @var float
	 */
	const TEMPERATURE_CREATIVE = 0.75;

	/**
	 * Temperature for structured data tasks (coupon suggestions, JSON output).
	 *
	 * @var float
	 */
	const TEMPERATURE_STRUCTURED = 0.3;

	/**
	 * The OpenAI API key.
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * The OpenAI model to use (e.g. gpt-4o, gpt-4o-mini, gpt-4-turbo).
	 *
	 * @var string
	 */
	private string $model;

	/**
	 * Number of tokens used in the last API call.
	 *
	 * Exposed publicly so the AI Manager can read it for credit tracking
	 * after each generation call.
	 *
	 * @var int
	 */
	public int $last_tokens_used = 0;

	/**
	 * Optional campaign-level AI prompt set by the Section Builder.
	 * Injected into the system prompt for all generation calls on this instance.
	 *
	 * @var string
	 */
	private string $campaign_prompt = '';

	/**
	 * Constructor.
	 *
	 * Retrieves the API key and model from WordPress options.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_key Optional. Override the stored API key.
	 * @param string $model   Optional. Override the stored model.
	 */
	public function __construct( string $api_key = '', string $model = '' ) {
		$this->api_key = ! empty( $api_key ) ? $api_key : bcg_get_api_key( 'bcg_openai_api_key' );
		$this->model   = ! empty( $model ) ? $model : get_option( 'bcg_openai_model', 'gpt-4o-mini' );
	}

	/**
	 * Set a campaign-level AI prompt to inject into all generation calls.
	 *
	 * @since 1.5.32
	 *
	 * @param string $prompt The free-form campaign prompt from the Section Builder.
	 * @return void
	 */
	public function set_campaign_prompt( string $prompt ): void {
		$this->campaign_prompt = sanitize_textarea_field( $prompt );
	}

	// ─── Public Generation Methods ─────────────────────────────────────

	/**
	 * Generate an email campaign subject line.
	 *
	 * Creates a compelling, concise subject line based on the products being
	 * promoted, the campaign theme, tone, and target language.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $products Array of WooCommerce product data arrays.
	 *                         Each element should contain 'name', 'price',
	 *                         'short_description', and optionally 'category'.
	 * @param string $theme    Campaign theme or occasion (e.g. "Black Friday").
	 * @param string $tone     Tone of voice: Professional, Friendly, Urgent, Playful, or Luxury.
	 * @param string $language Target language (e.g. "English", "Polish").
	 * @return string|\WP_Error The generated subject line on success, WP_Error on failure.
	 */
	public function generate_subject_line( array $products, string $theme, string $tone, string $language ): string|\WP_Error {
		$product_summary = $this->build_product_summary( $products );

		$user_prompt = sprintf(
			"Write a single email subject line for an e-commerce promotional campaign.\n\n" .
			"Products being promoted:\n%s\n\n" .
			"%s" .
			"Requirements:\n" .
			"- Maximum 60 characters\n" .
			"- Create urgency or curiosity\n" .
			"- Mention a key product or benefit if possible\n" .
			"- Do not use ALL CAPS\n" .
			"- Return ONLY the subject line text, nothing else",
			$product_summary,
			! empty( $theme ) ? "Campaign theme/occasion: {$theme}\n\n" : ''
		);

		return $this->make_completion_request(
			$this->build_system_prompt( $tone, $language ),
			$user_prompt,
			self::TEMPERATURE_CREATIVE,
			150
		);
	}

	/**
	 * Generate email preview text (preheader).
	 *
	 * Creates a short preview text that complements the subject line and
	 * encourages the recipient to open the email.
	 *
	 * @since 1.0.0
	 *
	 * @param string $subject  The email subject line to complement.
	 * @param array  $products Array of WooCommerce product data arrays.
	 * @return string|\WP_Error The generated preview text on success, WP_Error on failure.
	 */
	public function generate_preview_text( string $subject, array $products ): string|\WP_Error {
		$product_names = $this->extract_product_names( $products );

		$user_prompt = sprintf(
			"Write a single email preview text (preheader) that complements the following subject line.\n\n" .
			"Subject line: \"%s\"\n" .
			"Products in the email: %s\n\n" .
			"Requirements:\n" .
			"- Maximum 100 characters\n" .
			"- Should add information not already in the subject line\n" .
			"- Encourage the reader to open the email\n" .
			"- Return ONLY the preview text, nothing else",
			$subject,
			$product_names
		);

		return $this->make_completion_request(
			$this->build_system_prompt( 'Friendly', $this->get_site_language() ),
			$user_prompt,
			self::TEMPERATURE_CREATIVE,
			150
		);
	}

	/**
	 * Generate the main campaign headline.
	 *
	 * Creates a prominent headline for the top of the email campaign,
	 * designed to capture attention immediately.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $products Array of WooCommerce product data arrays.
	 * @param string $theme    Campaign theme or occasion.
	 * @param string $tone     Tone of voice.
	 * @param string $language Target language.
	 * @return string|\WP_Error The generated headline on success, WP_Error on failure.
	 */
	public function generate_main_headline( array $products, string $theme, string $tone, string $language ): string|\WP_Error {
		$product_summary = $this->build_product_summary( $products );

		$user_prompt = sprintf(
			"Write a single main headline for the hero section of a promotional email campaign.\n\n" .
			"Products being promoted:\n%s\n\n" .
			"%s" .
			"Requirements:\n" .
			"- Maximum 80 characters\n" .
			"- Bold and attention-grabbing\n" .
			"- Convey the core value proposition or offer\n" .
			"- Suitable as an H1 heading in an email\n" .
			"- Return ONLY the headline text, nothing else",
			$product_summary,
			! empty( $theme ) ? "Campaign theme/occasion: {$theme}\n\n" : ''
		);

		return $this->make_completion_request(
			$this->build_system_prompt( $tone, $language ),
			$user_prompt,
			self::TEMPERATURE_CREATIVE,
			150
		);
	}

	/**
	 * Generate the main campaign description.
	 *
	 * Creates a short, persuasive introductory paragraph for the email
	 * campaign that sits below the main headline.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $products Array of WooCommerce product data arrays.
	 * @param string $theme    Campaign theme or occasion.
	 * @param string $tone     Tone of voice.
	 * @param string $language Target language.
	 * @return string|\WP_Error The generated description on success, WP_Error on failure.
	 */
	public function generate_main_description( array $products, string $theme, string $tone, string $language ): string|\WP_Error {
		$product_summary = $this->build_product_summary( $products );

		$user_prompt = sprintf(
			"Write a short introductory paragraph for a promotional email campaign.\n\n" .
			"Products being promoted:\n%s\n\n" .
			"%s" .
			"Requirements:\n" .
			"- 2 to 4 sentences maximum\n" .
			"- Highlight the value of the featured products\n" .
			"- Include a subtle call-to-action\n" .
			"- Do not repeat product names verbatim — reference them naturally\n" .
			"- Do not use placeholder text like [Store Name]\n" .
			"- Return ONLY the paragraph text, nothing else",
			$product_summary,
			! empty( $theme ) ? "Campaign theme/occasion: {$theme}\n\n" : ''
		);

		return $this->make_completion_request(
			$this->build_system_prompt( $tone, $language ),
			$user_prompt,
			self::TEMPERATURE_CREATIVE,
			400
		);
	}

	/**
	 * Generate a headline for a single product.
	 *
	 * Creates a short, compelling headline specific to one product,
	 * suitable for use in a product card within the email.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $product Product data array with 'name', 'price',
	 *                        'short_description', 'category' keys.
	 * @param string $tone    Tone of voice.
	 * @param string $language Target language.
	 * @return string|\WP_Error The generated headline on success, WP_Error on failure.
	 */
	public function generate_product_headline( array $product, string $tone, string $language ): string|\WP_Error {
		$product_info = $this->format_single_product( $product );

		$user_prompt = sprintf(
			"Write a single short marketing headline for this product to use in a promotional email.\n\n" .
			"Product details:\n%s\n\n" .
			"Requirements:\n" .
			"- Maximum 60 characters\n" .
			"- Focus on the key benefit or unique selling point\n" .
			"- Do not just repeat the product name\n" .
			"- Make it compelling and action-oriented\n" .
			"- Return ONLY the headline text, nothing else",
			$product_info
		);

		return $this->make_completion_request(
			$this->build_system_prompt( $tone, $language ),
			$user_prompt,
			self::TEMPERATURE_CREATIVE,
			100
		);
	}

	/**
	 * Generate a short description for a single product.
	 *
	 * Creates a brief, persuasive description for one product suitable
	 * for use in a product card within the email campaign.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $product Product data array with 'name', 'price',
	 *                        'short_description', 'category' keys.
	 * @param string $tone    Tone of voice.
	 * @param string $language Target language.
	 * @return string|\WP_Error The generated description on success, WP_Error on failure.
	 */
	public function generate_product_short_description( array $product, string $tone, string $language ): string|\WP_Error {
		$product_info = $this->format_single_product( $product );

		$user_prompt = sprintf(
			"Write a short marketing description for this product to use in a promotional email.\n\n" .
			"Product details:\n%s\n\n" .
			"Requirements:\n" .
			"- 1 to 2 sentences maximum\n" .
			"- Highlight the main benefit or feature\n" .
			"- Create desire to learn more or purchase\n" .
			"- Do not include the price in the description\n" .
			"- Do not use placeholder text\n" .
			"- Return ONLY the description text, nothing else",
			$product_info
		);

		return $this->make_completion_request(
			$this->build_system_prompt( $tone, $language ),
			$user_prompt,
			self::TEMPERATURE_CREATIVE,
			200
		);
	}

	/**
	 * Generate a coupon discount suggestion.
	 *
	 * Analyses the products and campaign theme to suggest an appropriate
	 * discount value, type, and promotional display text.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $products Array of WooCommerce product data arrays.
	 * @param string $theme    Campaign theme or occasion.
	 * @return array|\WP_Error Associative array with keys 'value' (int), 'type'
	 *                         ('percent'|'fixed_cart'), and 'text' (string) on
	 *                         success, WP_Error on failure.
	 */
	public function generate_coupon_discount_suggestion( array $products, string $theme ): array|\WP_Error {
		$product_summary = $this->build_product_summary( $products );

		$avg_price = $this->calculate_average_price( $products );

		$user_prompt = sprintf(
			"Suggest a discount coupon for an e-commerce email campaign.\n\n" .
			"Products being promoted:\n%s\n\n" .
			"Average product price: %s\n" .
			"%s" .
			"Return your response as a valid JSON object with exactly these keys:\n" .
			"- \"value\": the discount amount as an integer (e.g. 20)\n" .
			"- \"type\": either \"percent\" or \"fixed_cart\"\n" .
			"- \"text\": a short promotional text for the coupon (e.g. \"Get 20%% off your order!\")\n\n" .
			"Guidelines:\n" .
			"- Percentage discounts should be between 5 and 50\n" .
			"- Fixed discounts should be reasonable relative to the average price\n" .
			"- The promotional text should be exciting and under 60 characters\n" .
			"- Return ONLY the JSON object, no markdown code fences, no explanation",
			$product_summary,
			$avg_price,
			! empty( $theme ) ? "Campaign theme/occasion: {$theme}\n\n" : ''
		);

		$currency        = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'GBP';
		$currency_symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '£';

		$system_prompt = "You are an expert email marketing copywriter for a WooCommerce e-commerce store. " .
			"Write compelling, conversion-focused copy. Be concise. Avoid cliches. " .
			"Respond only with the requested content - no explanations, no preamble. " .
			"Always respond in English. " .
			"The store currency is {$currency} ({$currency_symbol}). Always use this currency symbol when mentioning prices.";

		$result = $this->make_completion_request(
			$system_prompt,
			$user_prompt,
			self::TEMPERATURE_STRUCTURED,
			200
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $this->parse_coupon_response( $result );
	}

	// ─── API Communication ─────────────────────────────────────────────

	/**
	 * Send a Chat Completion request to the OpenAI API.
	 *
	 * Constructs the request payload, sends it via wp_remote_post(),
	 * handles the response, logs any errors, and records token usage.
	 *
	 * @since 1.0.0
	 *
	 * @param string $system_prompt The system-level instruction.
	 * @param string $user_prompt   The user-level prompt.
	 * @param float  $temperature   Sampling temperature (0.0 to 2.0).
	 * @param int    $max_tokens    Maximum tokens in the response.
	 * @return string|\WP_Error The generated text content on success, WP_Error on failure.
	 */
	private function make_completion_request(
		string $system_prompt,
		string $user_prompt,
		float $temperature = self::TEMPERATURE_CREATIVE,
		int $max_tokens = 500
	): string|\WP_Error {
		// Reset token counter for this call.
		$this->last_tokens_used = 0;

		// Validate prerequisites.
		if ( empty( $this->api_key ) ) {
			return new \WP_Error(
				'bcg_openai_no_key',
				__( 'OpenAI API key is not configured. Please add your API key in Settings.', 'brevo-campaign-generator' )
			);
		}

		$body = array(
			'model'       => $this->model,
			'messages'    => array(
				array(
					'role'    => 'system',
					'content' => $system_prompt,
				),
				array(
					'role'    => 'user',
					'content' => $user_prompt,
				),
			),
			'temperature' => $temperature,
			'max_tokens'  => $max_tokens,
		);

		/**
		 * Filter the OpenAI request body before sending.
		 *
		 * Allows other components to modify the request payload, for example
		 * to add additional parameters or override defaults.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $body         The request body array.
		 * @param string $system_prompt The system prompt being used.
		 * @param string $user_prompt   The user prompt being sent.
		 */
		$body = apply_filters( 'bcg_openai_request_body', $body, $system_prompt, $user_prompt );

		$response = wp_remote_post(
			self::API_ENDPOINT,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => self::REQUEST_TIMEOUT,
			)
		);

		// Handle transport-level errors (connection timeout, DNS failure, etc.).
		if ( is_wp_error( $response ) ) {
			$this->log_error(
				'request_failed',
				$response->get_error_message(),
				array( 'model' => $this->model )
			);

			return new \WP_Error(
				'bcg_openai_request_failed',
				sprintf(
					/* translators: %s: error message from wp_remote_post */
					__( 'OpenAI API request failed: %s', 'brevo-campaign-generator' ),
					$response->get_error_message()
				)
			);
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$raw_body  = wp_remote_retrieve_body( $response );
		$data      = json_decode( $raw_body, true );

		// Handle HTTP error responses.
		if ( 200 !== $http_code ) {
			$error_message = $this->extract_api_error_message( $data, $http_code );

			$this->log_error(
				'api_error',
				$error_message,
				array(
					'http_code' => $http_code,
					'model'     => $this->model,
					'response'  => $this->truncate_string( $raw_body, 500 ),
				)
			);

			return new \WP_Error(
				'bcg_openai_api_error',
				$error_message
			);
		}

		// Validate response structure.
		if (
			! is_array( $data ) ||
			! isset( $data['choices'][0]['message']['content'] )
		) {
			$this->log_error(
				'invalid_response',
				__( 'OpenAI returned an unexpected response format.', 'brevo-campaign-generator' ),
				array(
					'model'    => $this->model,
					'response' => $this->truncate_string( $raw_body, 500 ),
				)
			);

			return new \WP_Error(
				'bcg_openai_invalid_response',
				__( 'OpenAI returned an unexpected response format. Please try again.', 'brevo-campaign-generator' )
			);
		}

		// Record token usage for credit tracking.
		if ( isset( $data['usage']['total_tokens'] ) ) {
			$this->last_tokens_used = (int) $data['usage']['total_tokens'];
		}

		$content = trim( $data['choices'][0]['message']['content'] );

		// Strip potential surrounding quotes that GPT sometimes adds.
		$content = $this->clean_generated_text( $content );

		/**
		 * Filter the generated OpenAI content before returning.
		 *
		 * @since 1.0.0
		 *
		 * @param string $content      The generated content.
		 * @param string $user_prompt  The prompt that was sent.
		 * @param string $model        The model that was used.
		 * @param int    $tokens_used  Total tokens used.
		 */
		$content = apply_filters( 'bcg_openai_generated_content', $content, $user_prompt, $this->model, $this->last_tokens_used );

		return $content;
	}

	// ─── Prompt Building ───────────────────────────────────────────────

	/**
	 * Build the system prompt with tone and language.
	 *
	 * Constructs the base system-level instruction that guides the AI's
	 * behaviour for all copywriting tasks.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tone     Tone of voice.
	 * @param string $language Target language.
	 * @return string The formatted system prompt.
	 */
	private function build_system_prompt( string $tone, string $language ): string {
		$tone     = sanitize_text_field( $tone );
		$language = sanitize_text_field( $language );

		// Use sensible defaults if empty.
		if ( empty( $tone ) ) {
			$tone = 'Professional';
		}
		if ( empty( $language ) ) {
			$language = $this->get_site_language();
		}

		// Include WooCommerce store currency so AI uses the correct symbol.
		$currency        = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'GBP';
		$currency_symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '£';

		$prompt = sprintf(
			'You are an expert email marketing copywriter for a WooCommerce e-commerce store. ' .
			'Write compelling, conversion-focused copy. Be concise. Avoid cliches. ' .
			'Respond only with the requested content - no explanations, no preamble. ' .
			'Always respond in %s. Tone: %s. ' .
			'The store currency is %s (%s). Always use this currency symbol when mentioning prices.',
			$language,
			$tone,
			$currency,
			$currency_symbol
		);

		// Append AI trainer context if available.
		$company_context  = get_option( 'bcg_ai_trainer_company', '' );
		$products_context = get_option( 'bcg_ai_trainer_products', '' );

		if ( ! empty( $company_context ) ) {
			$prompt .= "\n\nStore context: " . $company_context;
		}
		if ( ! empty( $products_context ) ) {
			$prompt .= "\n\nProduct context: " . $products_context;
		}
		if ( ! empty( $this->campaign_prompt ) ) {
			$prompt .= "\n\nCampaign brief from the user: " . $this->campaign_prompt;
		}

		return $prompt;
	}

	/**
	 * Build a summary of multiple products for use in prompts.
	 *
	 * Formats each product's name, price, description, and category into
	 * a numbered list that the AI can reason about.
	 *
	 * @since 1.0.0
	 *
	 * @param array $products Array of product data arrays.
	 * @return string Formatted product summary.
	 */
	private function build_product_summary( array $products ): string {
		if ( empty( $products ) ) {
			return __( '(No products provided)', 'brevo-campaign-generator' );
		}

		$currency_symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '£';
		$lines           = array();

		foreach ( $products as $index => $product ) {
			$number = $index + 1;
			$name   = $product['name'] ?? __( 'Unknown Product', 'brevo-campaign-generator' );
			$price  = $product['price'] ?? '';
			$desc   = $product['short_description'] ?? '';
			$cat    = $product['category'] ?? '';

			$line = sprintf( '%d. %s', $number, $name );

			if ( ! empty( $price ) ) {
				$line .= sprintf( ' (%s%s)', $currency_symbol, $price );
			}

			if ( ! empty( $cat ) ) {
				$line .= sprintf( ' [%s]', $cat );
			}

			if ( ! empty( $desc ) ) {
				// Strip HTML tags and limit description length for the prompt.
				$clean_desc = wp_strip_all_tags( $desc );
				$clean_desc = $this->truncate_string( $clean_desc, 200 );
				$line      .= "\n   " . $clean_desc;
			}

			$lines[] = $line;
		}

		return implode( "\n", $lines );
	}

	/**
	 * Format a single product's data for use in prompts.
	 *
	 * @since 1.0.0
	 *
	 * @param array $product Product data array.
	 * @return string Formatted product information.
	 */
	private function format_single_product( array $product ): string {
		$currency_symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '£';
		$parts           = array();

		$name = $product['name'] ?? __( 'Unknown Product', 'brevo-campaign-generator' );
		$parts[] = sprintf( 'Name: %s', $name );

		if ( ! empty( $product['price'] ) ) {
			$parts[] = sprintf( 'Price: %s%s', $currency_symbol, $product['price'] );
		}

		if ( ! empty( $product['category'] ) ) {
			$parts[] = sprintf( 'Category: %s', $product['category'] );
		}

		if ( ! empty( $product['short_description'] ) ) {
			$clean_desc = wp_strip_all_tags( $product['short_description'] );
			$clean_desc = $this->truncate_string( $clean_desc, 300 );
			$parts[]    = sprintf( 'Description: %s', $clean_desc );
		}

		if ( ! empty( $product['regular_price'] ) && ! empty( $product['sale_price'] ) ) {
			$parts[] = sprintf(
				'Regular price: %s%s, Sale price: %s%s',
				$currency_symbol,
				$product['regular_price'],
				$currency_symbol,
				$product['sale_price']
			);
		}

		return implode( "\n", $parts );
	}

	/**
	 * Extract product names as a comma-separated string.
	 *
	 * @since 1.0.0
	 *
	 * @param array $products Array of product data arrays.
	 * @return string Comma-separated product names.
	 */
	private function extract_product_names( array $products ): string {
		$names = array();

		foreach ( $products as $product ) {
			if ( ! empty( $product['name'] ) ) {
				$names[] = $product['name'];
			}
		}

		if ( empty( $names ) ) {
			return __( '(No products)', 'brevo-campaign-generator' );
		}

		return implode( ', ', $names );
	}

	/**
	 * Calculate the average price from product data.
	 *
	 * @since 1.0.0
	 *
	 * @param array $products Array of product data arrays.
	 * @return string Formatted average price or 'N/A' if no prices available.
	 */
	private function calculate_average_price( array $products ): string {
		$prices = array();

		foreach ( $products as $product ) {
			if ( ! empty( $product['price'] ) ) {
				// Extract numeric value from formatted price string.
				$numeric = preg_replace( '/[^0-9.]/', '', $product['price'] );
				if ( is_numeric( $numeric ) && (float) $numeric > 0 ) {
					$prices[] = (float) $numeric;
				}
			}
		}

		if ( empty( $prices ) ) {
			return 'N/A';
		}

		$average = array_sum( $prices ) / count( $prices );

		return number_format( $average, 2 );
	}

	// ─── Response Processing ───────────────────────────────────────────

	/**
	 * Clean generated text by removing unwanted artefacts.
	 *
	 * Strips surrounding quotes, markdown formatting, and extraneous
	 * whitespace that the model sometimes adds.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text The raw generated text.
	 * @return string Cleaned text.
	 */
	private function clean_generated_text( string $text ): string {
		// Remove surrounding double quotes.
		if ( strlen( $text ) >= 2 && '"' === $text[0] && '"' === $text[ strlen( $text ) - 1 ] ) {
			$text = substr( $text, 1, -1 );
		}

		// Remove surrounding single quotes.
		if ( strlen( $text ) >= 2 && "'" === $text[0] && "'" === $text[ strlen( $text ) - 1 ] ) {
			$text = substr( $text, 1, -1 );
		}

		// Remove markdown bold markers.
		$text = str_replace( array( '**', '__' ), '', $text );

		// Remove leading/trailing whitespace and newlines.
		$text = trim( $text );

		return $text;
	}

	/**
	 * Parse the coupon discount suggestion response from JSON.
	 *
	 * Validates the expected keys and types, applying sensible defaults
	 * for any missing or invalid values.
	 *
	 * @since 1.0.0
	 *
	 * @param string $response_text The raw JSON response from OpenAI.
	 * @return array|\WP_Error Parsed coupon data or WP_Error.
	 */
	private function parse_coupon_response( string $response_text ): array|\WP_Error {
		// Strip markdown code fences if the model wrapped its response.
		$cleaned = preg_replace( '/^```(?:json)?\s*/', '', $response_text );
		$cleaned = preg_replace( '/\s*```$/', '', $cleaned );
		$cleaned = trim( $cleaned );

		$data = json_decode( $cleaned, true );

		if ( ! is_array( $data ) ) {
			$this->log_error(
				'coupon_parse_error',
				__( 'Failed to parse coupon suggestion as JSON.', 'brevo-campaign-generator' ),
				array( 'raw_response' => $this->truncate_string( $response_text, 500 ) )
			);

			return new \WP_Error(
				'bcg_openai_parse_error',
				__( 'Failed to parse the AI coupon suggestion. Please try again.', 'brevo-campaign-generator' )
			);
		}

		// Validate and sanitise the value.
		$value = isset( $data['value'] ) ? absint( $data['value'] ) : 10;
		if ( $value < 1 ) {
			$value = 10;
		}

		// Validate the type.
		$type = isset( $data['type'] ) ? sanitize_text_field( $data['type'] ) : 'percent';
		if ( ! in_array( $type, array( 'percent', 'fixed_cart' ), true ) ) {
			$type = 'percent';
		}

		// Clamp percentage values.
		if ( 'percent' === $type && $value > 100 ) {
			$value = 50;
		}

		// Validate the text.
		$text = isset( $data['text'] ) ? sanitize_text_field( $data['text'] ) : '';
		if ( empty( $text ) ) {
			$text = sprintf(
				/* translators: %d: discount value */
				__( 'Get %d%% off your order!', 'brevo-campaign-generator' ),
				$value
			);
		}

		return array(
			'value' => $value,
			'type'  => $type,
			'text'  => $text,
		);
	}

	// ─── Error Handling & Logging ──────────────────────────────────────

	/**
	 * Extract a human-readable error message from an OpenAI API error response.
	 *
	 * @since 1.0.0
	 *
	 * @param array|null $data      The decoded response body.
	 * @param int        $http_code The HTTP status code.
	 * @return string The error message.
	 */
	private function extract_api_error_message( ?array $data, int $http_code ): string {
		if ( is_array( $data ) && isset( $data['error']['message'] ) ) {
			$api_message = sanitize_text_field( $data['error']['message'] );

			return sprintf(
				/* translators: 1: HTTP status code, 2: API error message */
				__( 'OpenAI API error (HTTP %1$d): %2$s', 'brevo-campaign-generator' ),
				$http_code,
				$api_message
			);
		}

		// Map common HTTP codes to helpful messages.
		return match ( $http_code ) {
			401     => __( 'OpenAI API error: Invalid API key. Please check your API key in Settings.', 'brevo-campaign-generator' ),
			429     => __( 'OpenAI API error: Rate limit exceeded. Please wait a moment and try again.', 'brevo-campaign-generator' ),
			500,
			502,
			503     => __( 'OpenAI API error: The service is temporarily unavailable. Please try again shortly.', 'brevo-campaign-generator' ),
			default => sprintf(
				/* translators: %d: HTTP status code */
				__( 'OpenAI API returned an unexpected error (HTTP %d).', 'brevo-campaign-generator' ),
				$http_code
			),
		};
	}

	/**
	 * Log an error to the plugin's error log.
	 *
	 * Stores the last N errors (FIFO) in the bcg_error_log WordPress option
	 * for debugging in the admin interface.
	 *
	 * @since 1.0.0
	 *
	 * @param string $code    A short error code identifier.
	 * @param string $message Human-readable error description.
	 * @param array  $context Optional additional data for debugging.
	 * @return void
	 */
	private function log_error( string $code, string $message, array $context = array() ): void {
		$log = get_option( 'bcg_error_log', array() );

		if ( ! is_array( $log ) ) {
			$log = array();
		}

		$entry = array(
			'timestamp' => current_time( 'mysql' ),
			'source'    => 'openai',
			'code'      => sanitize_text_field( $code ),
			'message'   => sanitize_text_field( $message ),
			'context'   => $context,
		);

		$log[] = $entry;

		// Keep only the last N entries (FIFO).
		if ( count( $log ) > self::MAX_ERROR_LOG_ENTRIES ) {
			$log = array_slice( $log, -self::MAX_ERROR_LOG_ENTRIES );
		}

		update_option( 'bcg_error_log', $log, false );
	}

	// ─── Utility Methods ───────────────────────────────────────────────

	/**
	 * Truncate a string to a maximum length.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string     The string to truncate.
	 * @param int    $max_length Maximum character length.
	 * @return string The truncated string with ellipsis if shortened.
	 */
	private function truncate_string( string $string, int $max_length ): string {
		if ( mb_strlen( $string ) <= $max_length ) {
			return $string;
		}

		return mb_substr( $string, 0, $max_length - 3 ) . '...';
	}

	/**
	 * Get the site's primary language as a human-readable name.
	 *
	 * Falls back to "English" if the locale cannot be determined.
	 *
	 * @since 1.0.0
	 *
	 * @return string Language name (e.g. "English", "Polish").
	 */
	private function get_site_language(): string {
		$locale = get_locale();

		$language_map = array(
			'en_US' => 'English',
			'en_GB' => 'English',
			'en_AU' => 'English',
			'en_CA' => 'English',
			'pl_PL' => 'Polish',
			'de_DE' => 'German',
			'fr_FR' => 'French',
			'es_ES' => 'Spanish',
			'it_IT' => 'Italian',
			'pt_BR' => 'Portuguese',
			'pt_PT' => 'Portuguese',
			'nl_NL' => 'Dutch',
			'sv_SE' => 'Swedish',
			'da_DK' => 'Danish',
			'nb_NO' => 'Norwegian',
			'fi'    => 'Finnish',
			'cs_CZ' => 'Czech',
			'ro_RO' => 'Romanian',
			'hu_HU' => 'Hungarian',
			'ja'    => 'Japanese',
			'zh_CN' => 'Chinese',
			'ko_KR' => 'Korean',
			'ar'    => 'Arabic',
			'tr_TR' => 'Turkish',
		);

		// Try exact match first.
		if ( isset( $language_map[ $locale ] ) ) {
			return $language_map[ $locale ];
		}

		// Try matching just the language prefix (e.g. 'en' from 'en_US').
		$prefix = substr( $locale, 0, 2 );
		foreach ( $language_map as $key => $name ) {
			if ( str_starts_with( $key, $prefix ) ) {
				return $name;
			}
		}

		return 'English';
	}

	/**
	 * Get the model identifier currently in use.
	 *
	 * @since 1.0.0
	 *
	 * @return string The model ID (e.g. "gpt-4o", "gpt-4o-mini").
	 */
	public function get_model(): string {
		return $this->model;
	}

	/**
	 * Get the number of tokens used in the last API call.
	 *
	 * @since 1.0.0
	 *
	 * @return int Token count.
	 */
	public function get_last_tokens_used(): int {
		return $this->last_tokens_used;
	}

	/**
	 * Check whether a valid API key is configured.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if an API key is present.
	 */
	public function has_api_key(): bool {
		return ! empty( $this->api_key );
	}

	/**
	 * Get the credit cost for the current model.
	 *
	 * Reads the per-generation credit cost from WordPress options based on
	 * the model currently selected.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of credits per generation.
	 */
	public function get_credit_cost(): int {
		return match ( $this->model ) {
			'gpt-4o'      => (int) get_option( 'bcg_credit_cost_openai_gpt4o', 5 ),
			'gpt-4o-mini' => (int) get_option( 'bcg_credit_cost_openai_gpt4o_mini', 1 ),
			'gpt-4-turbo' => (int) get_option( 'bcg_credit_cost_openai_gpt4o', 5 ),
			default       => (int) get_option( 'bcg_credit_cost_openai_gpt4o_mini', 1 ),
		};
	}

	/**
	 * Test the API connection with a minimal request.
	 *
	 * Sends a very small completion request to verify the API key and model
	 * are working correctly. This is more thorough than the Settings page
	 * test which only calls the models endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @return true|\WP_Error True on success, WP_Error on failure.
	 */
	// ─── Section Builder Generation Methods ───────────────────────────

	/**
	 * Generate heading and body text for a text block section.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $context  Campaign context: products[], theme, currency_symbol.
	 * @param string $tone     Tone of voice.
	 * @param string $language Target language.
	 * @return array|\WP_Error Array with 'heading' and 'body' keys, or WP_Error.
	 */
	public function generate_text_block( array $context, string $tone, string $language ): array|\WP_Error {
		$product_summary = $this->build_product_summary( $context['products'] ?? array() );
		$theme           = sanitize_text_field( $context['theme'] ?? '' );

		$user_prompt = sprintf(
			"Write a text block for a promotional email campaign.\n\n" .
			"Products being promoted:\n%s\n\n" .
			"%s" .
			"Return ONLY a valid JSON object with exactly these keys:\n" .
			"- \"heading\": a short, punchy heading (max 60 characters, or empty string if none needed)\n" .
			"- \"body\": 2-3 sentences of engaging email copy\n\n" .
			"Return ONLY the JSON object, no markdown code fences, no explanation.",
			$product_summary,
			! empty( $theme ) ? "Campaign theme: {$theme}\n\n" : ''
		);

		$result = $this->make_completion_request(
			$this->build_system_prompt( $tone, $language ),
			$user_prompt,
			self::TEMPERATURE_CREATIVE,
			300
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$parsed = json_decode( $result, true );
		if ( ! is_array( $parsed ) || ! array_key_exists( 'body', $parsed ) ) {
			return array( 'heading' => '', 'body' => $result );
		}

		return array(
			'heading' => sanitize_text_field( $parsed['heading'] ?? '' ),
			'body'    => sanitize_textarea_field( $parsed['body'] ?? '' ),
		);
	}

	/**
	 * Generate heading and subtext for a banner section.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $context  Campaign context: products[], theme, currency_symbol.
	 * @param string $tone     Tone of voice.
	 * @param string $language Target language.
	 * @return array|\WP_Error Array with 'heading' and 'subtext' keys, or WP_Error.
	 */
	public function generate_banner_text( array $context, string $tone, string $language ): array|\WP_Error {
		$product_summary = $this->build_product_summary( $context['products'] ?? array() );
		$theme           = sanitize_text_field( $context['theme'] ?? '' );

		$user_prompt = sprintf(
			"Write a bold promotional banner for a marketing email.\n\n" .
			"Products being promoted:\n%s\n\n" .
			"%s" .
			"Return ONLY a valid JSON object with exactly these keys:\n" .
			"- \"heading\": a bold, impactful heading (max 50 characters)\n" .
			"- \"subtext\": a short supporting sentence (max 100 characters)\n\n" .
			"Return ONLY the JSON object, no markdown code fences, no explanation.",
			$product_summary,
			! empty( $theme ) ? "Campaign theme: {$theme}\n\n" : ''
		);

		$result = $this->make_completion_request(
			$this->build_system_prompt( $tone, $language ),
			$user_prompt,
			self::TEMPERATURE_CREATIVE,
			200
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$parsed = json_decode( $result, true );
		if ( ! is_array( $parsed ) || ! array_key_exists( 'heading', $parsed ) ) {
			return array( 'heading' => $result, 'subtext' => '' );
		}

		return array(
			'heading' => sanitize_text_field( $parsed['heading'] ?? '' ),
			'subtext' => sanitize_text_field( $parsed['subtext'] ?? '' ),
		);
	}

	/**
	 * Generate heading, subtext, and button text for a CTA section.
	 *
	 * @since 1.5.0
	 *
	 * @param array  $context  Campaign context: products[], theme, currency_symbol.
	 * @param string $tone     Tone of voice.
	 * @param string $language Target language.
	 * @return array|\WP_Error Array with 'heading', 'subtext', 'button_text' keys, or WP_Error.
	 */
	public function generate_cta_text( array $context, string $tone, string $language ): array|\WP_Error {
		$product_summary = $this->build_product_summary( $context['products'] ?? array() );
		$theme           = sanitize_text_field( $context['theme'] ?? '' );

		$user_prompt = sprintf(
			"Write a compelling call-to-action section for a promotional email.\n\n" .
			"Products being promoted:\n%s\n\n" .
			"%s" .
			"Return ONLY a valid JSON object with exactly these keys:\n" .
			"- \"heading\": an engaging CTA heading (max 60 characters)\n" .
			"- \"subtext\": supporting text to encourage clicks (max 120 characters)\n" .
			"- \"button_text\": the button label (max 25 characters, e.g. 'Shop Now', 'Claim Offer')\n\n" .
			"Return ONLY the JSON object, no markdown code fences, no explanation.",
			$product_summary,
			! empty( $theme ) ? "Campaign theme: {$theme}\n\n" : ''
		);

		$result = $this->make_completion_request(
			$this->build_system_prompt( $tone, $language ),
			$user_prompt,
			self::TEMPERATURE_CREATIVE,
			250
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$parsed = json_decode( $result, true );
		if ( ! is_array( $parsed ) || ! array_key_exists( 'heading', $parsed ) ) {
			return array( 'heading' => $result, 'subtext' => '', 'button_text' => 'Shop Now' );
		}

		return array(
			'heading'     => sanitize_text_field( $parsed['heading'] ?? '' ),
			'subtext'     => sanitize_text_field( $parsed['subtext'] ?? '' ),
			'button_text' => sanitize_text_field( $parsed['button_text'] ?? 'Shop Now' ),
		);
	}

	// ─── Layout Generation ─────────────────────────────────────────────────

	/**
	 * Ask GPT to suggest an email section layout based on a campaign brief.
	 *
	 * Returns an ordered array of section type slugs (e.g. ['header','hero','products','footer'])
	 * that best match the user's campaign brief and context.
	 *
	 * @since  1.5.38
	 *
	 * @param string $prompt  The user's campaign brief.
	 * @param array  $context Campaign context — tone, language, etc.
	 * @return array|\WP_Error Ordered array of section-type slugs, or WP_Error on failure.
	 */
	public function generate_section_layout( string $prompt, array $context ): array|\WP_Error {
		$tone     = sanitize_text_field( $context['tone'] ?? 'Professional' );
		$language = sanitize_text_field( $context['language'] ?? 'English' );

		$system_prompt = $this->build_system_prompt( $tone, $language );

		$user_prompt = sprintf(
			"Based on this email campaign brief:\n\"\"%s\"\n\n" .
			"Return a JSON array of email section types for this campaign layout.\n" .
			"Available types: header, hero, text, products, banner, cta, coupon, divider, spacer, footer\n\n" .
			"Rules:\n" .
			"- First element must be \"header\", last must be \"footer\"\n" .
			"- Use 4–8 sections total\n" .
			"- Match the brief (e.g. Black Friday → hero + products + coupon + cta)\n" .
			"- You may repeat types (e.g. two text blocks)\n" .
			"- Return ONLY valid JSON — no explanation, no markdown fences\n\n" .
			"Example: [\"header\",\"hero\",\"products\",\"coupon\",\"cta\",\"footer\"]",
			$prompt
		);

		$result = $this->make_completion_request(
			$system_prompt,
			$user_prompt,
			self::TEMPERATURE_STRUCTURED,
			150
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Extract JSON array from response (may be wrapped in markdown).
		preg_match( '/\\[[\\s\\S]*?\\]/', $result, $matches );
		$json  = $matches[0] ?? '[]';
		$types = json_decode( $json, true );

		if ( ! is_array( $types ) ) {
			return new \WP_Error( 'layout_parse', __( 'Could not parse layout from AI response.', 'brevo-campaign-generator' ) );
		}

		// Whitelist valid section types.
		$valid = array( 'header', 'hero', 'text', 'products', 'banner', 'cta', 'coupon', 'divider', 'spacer', 'footer' );
		$types = array_values( array_filter( $types, static fn( string $t ) => in_array( $t, $valid, true ) ) );

		// Guarantee header first and footer last.
		if ( empty( $types ) || $types[0] !== 'header' ) {
			array_unshift( $types, 'header' );
		}
		if ( end( $types ) !== 'footer' ) {
			$types[] = 'footer';
		}

		return $types;
	}

	// ─── Test Connection ─────────────────────────────────────────────

	public function test_connection(): bool|\WP_Error {
		if ( empty( $this->api_key ) ) {
			return new \WP_Error(
				'bcg_openai_no_key',
				__( 'OpenAI API key is not configured.', 'brevo-campaign-generator' )
			);
		}

		$result = $this->make_completion_request(
			'You are a helpful assistant.',
			'Reply with exactly the word: OK',
			0.0,
			5
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}
}
