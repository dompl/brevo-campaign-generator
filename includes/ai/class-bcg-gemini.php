<?php
/**
 * Google Gemini AI integration for image generation.
 *
 * Provides methods to generate product and campaign banner images using the
 * Google Gemini API. Generated images are saved to the WordPress uploads
 * directory and their URLs are returned for use in email campaigns.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Gemini
 *
 * Handles all communication with the Google Gemini API for image generation
 * tasks. Builds prompts from product data, calls the API, validates responses,
 * and saves generated images to the local filesystem.
 *
 * @since 1.0.0
 */
class BCG_Gemini {

	/**
	 * Base URL for the Gemini API (without trailing slash).
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const API_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models';

	/**
	 * Maximum time in seconds to wait for an API response.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private const REQUEST_TIMEOUT = 30;

	/**
	 * Maximum number of error log entries to retain.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private const MAX_ERROR_LOG_ENTRIES = 50;

	/**
	 * Allowed MIME types for generated images.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	private const ALLOWED_MIME_TYPES = array(
		'image/png'  => 'png',
		'image/jpeg' => 'jpg',
		'image/webp' => 'webp',
	);

	/**
	 * The Gemini API key.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $api_key;

	/**
	 * The Gemini model to use for generation.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private string $model;

	/**
	 * Constructor.
	 *
	 * Loads the API key and selected model from WordPress options.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->api_key = (string) get_option( 'bcg_gemini_api_key', '' );
		$model         = (string) get_option( 'bcg_gemini_model', 'gemini-2.5-flash' );

		// Migrate deprecated model names to current equivalents.
		$deprecated_map = array(
			'gemini-1.5-flash'              => 'gemini-2.5-flash',
			'gemini-1.5-pro'                => 'gemini-2.5-pro',
			'gemini-2.0-flash-exp'          => 'gemini-2.5-flash',
			'gemini-2.0-flash'              => 'gemini-2.5-flash',
			'gemini-2.5-flash-preview-05-20' => 'gemini-2.5-flash',
			'gemini-2.5-pro-preview-05-06'  => 'gemini-2.5-pro',
		);

		if ( isset( $deprecated_map[ $model ] ) ) {
			$model = $deprecated_map[ $model ];
			update_option( 'bcg_gemini_model', $model );
		}

		$this->model = $model;
	}

	/**
	 * Test the connection to the Gemini API.
	 *
	 * Sends a minimal text-only request to verify that the API key is valid
	 * and the selected model is accessible.
	 *
	 * @since  1.0.0
	 * @return true|\WP_Error True on success, WP_Error on failure.
	 */
	public function test_connection(): bool|\WP_Error {
		if ( empty( $this->api_key ) ) {
			return new \WP_Error(
				'bcg_gemini_no_key',
				__( 'Google Gemini API key is not configured.', 'brevo-campaign-generator' )
			);
		}

		$url = $this->build_endpoint_url();

		$body = array(
			'contents' => array(
				array(
					'parts' => array(
						array(
							'text' => 'Respond with exactly: OK',
						),
					),
				),
			),
			'generationConfig' => array(
				'maxOutputTokens' => 10,
			),
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->log_error( 'test_connection', $response->get_error_message() );
			return new \WP_Error(
				'bcg_gemini_connection_failed',
				sprintf(
					/* translators: %s: error message from the API request */
					__( 'Failed to connect to Gemini API: %s', 'brevo-campaign-generator' ),
					$response->get_error_message()
				)
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( 200 !== $code ) {
			$error_message = $this->extract_api_error( $body, $code );
			$this->log_error( 'test_connection', $error_message );
			return new \WP_Error(
				'bcg_gemini_api_error',
				$error_message
			);
		}

		return true;
	}

	/**
	 * Generate a main email banner image for a campaign.
	 *
	 * Creates a composite image representing multiple products, suitable for
	 * use as a newsletter header/banner. The image is saved to the uploads
	 * directory under the campaign's folder.
	 *
	 * @since  1.0.0
	 * @param  array  $products    Array of WooCommerce product data arrays. Each
	 *                              element should contain 'name' and 'short_description' keys.
	 * @param  string $theme       Campaign theme or occasion (e.g. "Black Friday", "Summer Sale").
	 * @param  string $style       Image style: Photorealistic, Studio Product, Lifestyle,
	 *                              Minimalist, or Vivid Illustration.
	 * @param  int    $campaign_id Optional. Campaign ID for organising saved images.
	 *                              Defaults to 0 (saved in a general folder).
	 * @return string|\WP_Error    Full URL to the saved image on success, WP_Error on failure.
	 */
	public function generate_main_email_image( array $products, string $theme, string $style, int $campaign_id = 0 ): string|\WP_Error {
		if ( empty( $this->api_key ) ) {
			return new \WP_Error(
				'bcg_gemini_no_key',
				__( 'Google Gemini API key is not configured.', 'brevo-campaign-generator' )
			);
		}

		if ( empty( $products ) ) {
			return new \WP_Error(
				'bcg_gemini_no_products',
				__( 'At least one product is required to generate a main email image.', 'brevo-campaign-generator' )
			);
		}

		$product_names = $this->extract_product_names( $products );
		$prompt        = $this->build_main_image_prompt( $product_names, $theme, $style );

		$image_data = $this->call_image_generation_api( $prompt );

		if ( is_wp_error( $image_data ) ) {
			return $image_data;
		}

		$filename = $this->generate_filename( 'main-banner', $campaign_id );

		return $this->save_image( $image_data['data'], $image_data['mime_type'], $filename, $campaign_id );
	}

	/**
	 * Generate an AI image for a single product.
	 *
	 * Creates a product-focused image suitable for use in an email campaign
	 * product card. The image is saved to the uploads directory.
	 *
	 * @since  1.0.0
	 * @param  array  $product     WooCommerce product data array containing 'name'
	 *                              and 'short_description' keys. May also contain 'id'.
	 * @param  string $style       Image style: Photorealistic, Studio Product, Lifestyle,
	 *                              Minimalist, or Vivid Illustration.
	 * @param  int    $campaign_id Optional. Campaign ID for organising saved images.
	 *                              Defaults to 0 (saved in a general folder).
	 * @return string|\WP_Error    Full URL to the saved image on success, WP_Error on failure.
	 */
	public function generate_product_image( array $product, string $style, int $campaign_id = 0 ): string|\WP_Error {
		if ( empty( $this->api_key ) ) {
			return new \WP_Error(
				'bcg_gemini_no_key',
				__( 'Google Gemini API key is not configured.', 'brevo-campaign-generator' )
			);
		}

		if ( empty( $product['name'] ) ) {
			return new \WP_Error(
				'bcg_gemini_invalid_product',
				__( 'Product name is required to generate an image.', 'brevo-campaign-generator' )
			);
		}

		$prompt = $this->build_product_image_prompt( $product, $style );

		$image_data = $this->call_image_generation_api( $prompt );

		if ( is_wp_error( $image_data ) ) {
			return $image_data;
		}

		$product_slug = sanitize_title( $product['name'] );
		$product_id   = ! empty( $product['id'] ) ? absint( $product['id'] ) : 0;
		$filename     = $this->generate_filename( 'product-' . $product_id . '-' . $product_slug, $campaign_id );

		return $this->save_image( $image_data['data'], $image_data['mime_type'], $filename, $campaign_id );
	}

	/**
	 * Build the prompt for a main campaign banner image.
	 *
	 * @since  1.0.0
	 * @param  string $product_names Comma-separated list of product names.
	 * @param  string $theme         Campaign theme or occasion.
	 * @param  string $style         Desired image style.
	 * @return string The constructed prompt.
	 */
	private function build_main_image_prompt( string $product_names, string $theme, string $style ): string {
		$theme_text = ! empty( $theme )
			? sprintf( 'Campaign theme: %s.', sanitize_text_field( $theme ) )
			: 'General e-commerce promotional campaign.';

		$prompt = sprintf(
			'A professional %s e-commerce email banner image representing: %s. %s '
			. 'Clean composition, vibrant colours, suitable for a newsletter header. '
			. 'No text, labels, watermarks, or logos. Aspect ratio: horizontal 600x300 pixels.',
			sanitize_text_field( $style ),
			sanitize_text_field( $product_names ),
			$theme_text
		);

		/**
		 * Filter the Gemini prompt for main email banner image generation.
		 *
		 * @since 1.0.0
		 * @param string $prompt        The generated prompt.
		 * @param string $product_names Comma-separated product names.
		 * @param string $theme         Campaign theme.
		 * @param string $style         Image style.
		 */
		return apply_filters( 'bcg_gemini_main_image_prompt', $prompt, $product_names, $theme, $style );
	}

	/**
	 * Build the prompt for a single product image.
	 *
	 * @since  1.0.0
	 * @param  array  $product Product data array with 'name' and 'short_description'.
	 * @param  string $style   Desired image style.
	 * @return string The constructed prompt.
	 */
	private function build_product_image_prompt( array $product, string $style ): string {
		$product_name = sanitize_text_field( $product['name'] );
		$description  = ! empty( $product['short_description'] )
			? sanitize_text_field( wp_strip_all_tags( $product['short_description'] ) )
			: '';

		$prompt = sprintf(
			'A professional %s photograph of %s. %s '
			. 'Clean neutral background, high detail, sharp focus, suitable for an e-commerce email. '
			. 'No text, labels, watermarks, or logos. Aspect ratio: horizontal.',
			sanitize_text_field( $style ),
			$product_name,
			$description
		);

		/**
		 * Filter the Gemini prompt for individual product image generation.
		 *
		 * @since 1.0.0
		 * @param string $prompt  The generated prompt.
		 * @param array  $product Product data.
		 * @param string $style   Image style.
		 */
		return apply_filters( 'bcg_gemini_product_image_prompt', $prompt, $product, $style );
	}

	/**
	 * Call the Gemini API to generate an image.
	 *
	 * Sends the prompt to the Gemini generateContent endpoint with image
	 * generation configuration. Extracts and validates the base64 image
	 * data from the response.
	 *
	 * @since  1.0.0
	 * @param  string $prompt The image generation prompt.
	 * @return array|\WP_Error Array with 'data' (base64 string) and 'mime_type' keys on
	 *                          success, WP_Error on failure.
	 */
	private function call_image_generation_api( string $prompt ): array|\WP_Error {
		$url = $this->build_endpoint_url();

		$request_body = array(
			'contents'         => array(
				array(
					'parts' => array(
						array(
							'text' => $prompt,
						),
					),
				),
			),
			'generationConfig' => array(
				'responseModalities' => array( 'TEXT', 'IMAGE' ),
			),
		);

		/**
		 * Filter the request body sent to the Gemini API.
		 *
		 * @since 1.0.0
		 * @param array  $request_body The API request body.
		 * @param string $prompt       The image generation prompt.
		 */
		$request_body = apply_filters( 'bcg_gemini_request_body', $request_body, $prompt );

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => self::REQUEST_TIMEOUT,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $request_body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			$error_msg = $response->get_error_message();

			// Check for timeout specifically.
			if ( str_contains( strtolower( $error_msg ), 'timeout' ) || str_contains( strtolower( $error_msg ), 'timed out' ) ) {
				$this->log_error( 'image_generation', __( 'API request timed out after 30 seconds.', 'brevo-campaign-generator' ) );
				return new \WP_Error(
					'bcg_gemini_timeout',
					__( 'The Gemini API request timed out. Please try again.', 'brevo-campaign-generator' )
				);
			}

			$this->log_error( 'image_generation', $error_msg );
			return new \WP_Error(
				'bcg_gemini_request_failed',
				sprintf(
					/* translators: %s: HTTP error message */
					__( 'Failed to connect to Gemini API: %s', 'brevo-campaign-generator' ),
					$error_msg
				)
			);
		}

		$http_code     = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( 200 !== $http_code ) {
			$error_message = $this->extract_api_error( $response_body, $http_code );
			$this->log_error( 'image_generation', $error_message );
			return new \WP_Error(
				'bcg_gemini_api_error',
				$error_message
			);
		}

		return $this->extract_image_from_response( $response_body );
	}

	/**
	 * Extract base64 image data from the Gemini API response.
	 *
	 * Parses the JSON response and looks for inline image data within the
	 * candidates array. Validates that the MIME type is an allowed image format.
	 *
	 * @since  1.0.0
	 * @param  string $response_body Raw JSON response body from the API.
	 * @return array|\WP_Error Array with 'data' and 'mime_type' on success, WP_Error on failure.
	 */
	private function extract_image_from_response( string $response_body ): array|\WP_Error {
		$decoded = json_decode( $response_body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->log_error( 'image_extraction', __( 'Invalid JSON response from Gemini API.', 'brevo-campaign-generator' ) );
			return new \WP_Error(
				'bcg_gemini_invalid_json',
				__( 'The Gemini API returned an invalid response. Please try again.', 'brevo-campaign-generator' )
			);
		}

		// Navigate the response structure to find image data.
		// Expected structure: candidates[0].content.parts[N].inlineData.data
		$candidates = $decoded['candidates'] ?? array();

		if ( empty( $candidates ) ) {
			$this->log_error( 'image_extraction', __( 'No candidates returned in Gemini API response.', 'brevo-campaign-generator' ) );
			return new \WP_Error(
				'bcg_gemini_no_candidates',
				__( 'The Gemini API did not return any image candidates. Please try again with a different prompt.', 'brevo-campaign-generator' )
			);
		}

		$parts = $candidates[0]['content']['parts'] ?? array();

		if ( empty( $parts ) ) {
			$this->log_error( 'image_extraction', __( 'No parts found in Gemini API response candidate.', 'brevo-campaign-generator' ) );
			return new \WP_Error(
				'bcg_gemini_no_parts',
				__( 'The Gemini API response did not contain any content. Please try again.', 'brevo-campaign-generator' )
			);
		}

		// Search through parts for inline image data.
		foreach ( $parts as $part ) {
			if ( ! empty( $part['inlineData']['data'] ) && ! empty( $part['inlineData']['mimeType'] ) ) {
				$mime_type  = $part['inlineData']['mimeType'];
				$image_data = $part['inlineData']['data'];

				// Validate MIME type.
				if ( ! array_key_exists( $mime_type, self::ALLOWED_MIME_TYPES ) ) {
					$this->log_error(
						'image_extraction',
						sprintf(
							/* translators: %s: MIME type */
							__( 'Unsupported image MIME type received: %s', 'brevo-campaign-generator' ),
							$mime_type
						)
					);
					return new \WP_Error(
						'bcg_gemini_unsupported_mime',
						sprintf(
							/* translators: %s: MIME type */
							__( 'The Gemini API returned an unsupported image format: %s', 'brevo-campaign-generator' ),
							esc_html( $mime_type )
						)
					);
				}

				// Validate that the data is valid base64.
				$decoded_data = base64_decode( $image_data, true );

				if ( false === $decoded_data || empty( $decoded_data ) ) {
					$this->log_error( 'image_extraction', __( 'Invalid base64 image data received from Gemini API.', 'brevo-campaign-generator' ) );
					return new \WP_Error(
						'bcg_gemini_invalid_base64',
						__( 'The image data received from Gemini API was corrupted. Please try again.', 'brevo-campaign-generator' )
					);
				}

				return array(
					'data'      => $image_data,
					'mime_type' => $mime_type,
				);
			}
		}

		// No inline image data found — the model may have only returned text.
		$this->log_error( 'image_extraction', __( 'No inline image data found in Gemini API response. The model may not support image generation.', 'brevo-campaign-generator' ) );
		return new \WP_Error(
			'bcg_gemini_no_image_data',
			__( 'The Gemini API did not return an image. The selected model may not support image generation, or the prompt may need adjustment.', 'brevo-campaign-generator' )
		);
	}

	/**
	 * Save base64-encoded image data to the WordPress uploads directory.
	 *
	 * Creates the campaign-specific subdirectory if it does not exist, decodes
	 * the base64 data, performs a secondary MIME type validation on the actual
	 * file content, and writes the file to disk.
	 *
	 * @since  1.0.0
	 * @param  string $base64_data Base64-encoded image data.
	 * @param  string $mime_type   MIME type of the image (e.g. 'image/png').
	 * @param  string $filename    Desired filename (without extension).
	 * @param  int    $campaign_id Campaign ID for directory organisation.
	 * @return string|\WP_Error    Full URL to the saved image on success, WP_Error on failure.
	 */
	private function save_image( string $base64_data, string $mime_type, string $filename, int $campaign_id ): string|\WP_Error {
		$upload_dir = wp_upload_dir();

		if ( ! empty( $upload_dir['error'] ) ) {
			$this->log_error( 'save_image', $upload_dir['error'] );
			return new \WP_Error(
				'bcg_upload_dir_error',
				sprintf(
					/* translators: %s: upload directory error message */
					__( 'WordPress uploads directory error: %s', 'brevo-campaign-generator' ),
					$upload_dir['error']
				)
			);
		}

		// Build the campaign-specific directory path.
		$campaign_folder = $campaign_id > 0 ? (string) $campaign_id : 'general';
		$target_dir      = trailingslashit( $upload_dir['basedir'] ) . 'bcg/' . $campaign_folder;
		$target_url_base = trailingslashit( $upload_dir['baseurl'] ) . 'bcg/' . $campaign_folder;

		// Create directory if it does not exist.
		if ( ! file_exists( $target_dir ) ) {
			$dir_created = wp_mkdir_p( $target_dir );
			if ( ! $dir_created ) {
				$this->log_error( 'save_image', sprintf( 'Failed to create directory: %s', $target_dir ) );
				return new \WP_Error(
					'bcg_mkdir_failed',
					__( 'Failed to create the image upload directory. Please check file permissions.', 'brevo-campaign-generator' )
				);
			}
		}

		// Add an index.php to prevent directory listing.
		$index_file = trailingslashit( $target_dir ) . 'index.php';
		if ( ! file_exists( $index_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $index_file, '<?php // Silence is golden.' );
		}

		// Decode the base64 image data.
		$decoded_data = base64_decode( $base64_data, true );

		if ( false === $decoded_data || empty( $decoded_data ) ) {
			$this->log_error( 'save_image', __( 'Failed to decode base64 image data.', 'brevo-campaign-generator' ) );
			return new \WP_Error(
				'bcg_base64_decode_failed',
				__( 'Failed to decode image data. The image may be corrupted.', 'brevo-campaign-generator' )
			);
		}

		// Validate the actual binary content matches the declared MIME type.
		$finfo = new \finfo( FILEINFO_MIME_TYPE );
		$actual_mime = $finfo->buffer( $decoded_data );

		if ( ! array_key_exists( $actual_mime, self::ALLOWED_MIME_TYPES ) ) {
			$this->log_error(
				'save_image',
				sprintf(
					'MIME type mismatch: declared %s, actual %s',
					$mime_type,
					$actual_mime
				)
			);
			return new \WP_Error(
				'bcg_mime_validation_failed',
				sprintf(
					/* translators: %s: actual MIME type detected */
					__( 'Image content validation failed. Detected type: %s', 'brevo-campaign-generator' ),
					esc_html( $actual_mime )
				)
			);
		}

		// Determine file extension from the validated MIME type.
		$extension = self::ALLOWED_MIME_TYPES[ $actual_mime ];
		$full_filename = sanitize_file_name( $filename . '.' . $extension );

		// Ensure unique filename to prevent overwrites.
		$file_path = trailingslashit( $target_dir ) . $full_filename;
		$file_url  = trailingslashit( $target_url_base ) . $full_filename;

		if ( file_exists( $file_path ) ) {
			$unique_suffix = substr( wp_generate_password( 8, false ), 0, 8 );
			$full_filename = sanitize_file_name( $filename . '-' . $unique_suffix . '.' . $extension );
			$file_path     = trailingslashit( $target_dir ) . $full_filename;
			$file_url      = trailingslashit( $target_url_base ) . $full_filename;
		}

		// Write the image file.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$bytes_written = file_put_contents( $file_path, $decoded_data );

		if ( false === $bytes_written || 0 === $bytes_written ) {
			$this->log_error( 'save_image', sprintf( 'Failed to write image file: %s', $file_path ) );
			return new \WP_Error(
				'bcg_file_write_failed',
				__( 'Failed to save the generated image to disk. Please check file permissions.', 'brevo-campaign-generator' )
			);
		}

		// Set correct file permissions.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
		chmod( $file_path, 0644 );

		/**
		 * Fires after a Gemini-generated image has been successfully saved.
		 *
		 * @since 1.0.0
		 * @param string $file_url    Full URL to the saved image.
		 * @param string $file_path   Absolute filesystem path to the saved image.
		 * @param int    $campaign_id Campaign ID the image belongs to.
		 * @param string $mime_type   MIME type of the saved image.
		 */
		do_action( 'bcg_gemini_image_saved', $file_url, $file_path, $campaign_id, $actual_mime );

		return $file_url;
	}

	/**
	 * Build the full API endpoint URL including the model and API key.
	 *
	 * @since  1.0.0
	 * @return string The complete endpoint URL.
	 */
	private function build_endpoint_url(): string {
		return sprintf(
			'%s/%s:generateContent?key=%s',
			self::API_BASE_URL,
			rawurlencode( $this->model ),
			rawurlencode( $this->api_key )
		);
	}

	/**
	 * Extract a human-readable error message from an API error response.
	 *
	 * @since  1.0.0
	 * @param  string $response_body Raw response body (JSON).
	 * @param  int    $http_code     HTTP status code.
	 * @return string Formatted error message.
	 */
	private function extract_api_error( string $response_body, int $http_code ): string {
		$decoded = json_decode( $response_body, true );

		if ( json_last_error() === JSON_ERROR_NONE && ! empty( $decoded['error']['message'] ) ) {
			return sprintf(
				/* translators: 1: HTTP status code, 2: error message from the API */
				__( 'Gemini API error (%1$d): %2$s', 'brevo-campaign-generator' ),
				$http_code,
				sanitize_text_field( $decoded['error']['message'] )
			);
		}

		return sprintf(
			/* translators: %d: HTTP status code */
			__( 'Gemini API returned an unexpected error (HTTP %d).', 'brevo-campaign-generator' ),
			$http_code
		);
	}

	/**
	 * Extract product names from a products array into a comma-separated string.
	 *
	 * @since  1.0.0
	 * @param  array $products Array of product data arrays.
	 * @return string Comma-separated product names.
	 */
	private function extract_product_names( array $products ): string {
		$names = array();

		foreach ( $products as $product ) {
			if ( ! empty( $product['name'] ) ) {
				$names[] = sanitize_text_field( $product['name'] );
			}
		}

		return implode( ', ', $names );
	}

	/**
	 * Generate a unique filename for a saved image.
	 *
	 * @since  1.0.0
	 * @param  string $prefix      Descriptive prefix for the filename.
	 * @param  int    $campaign_id Campaign ID to include in the filename.
	 * @return string Sanitised filename without extension.
	 */
	private function generate_filename( string $prefix, int $campaign_id ): string {
		$timestamp = gmdate( 'Ymd-His' );
		$hash      = substr( wp_generate_password( 6, false ), 0, 6 );

		$parts = array( 'bcg' );

		if ( $campaign_id > 0 ) {
			$parts[] = 'c' . $campaign_id;
		}

		$parts[] = sanitize_file_name( $prefix );
		$parts[] = $timestamp;
		$parts[] = $hash;

		return implode( '-', $parts );
	}

	/**
	 * Log an error to the plugin's error log option.
	 *
	 * Maintains a FIFO queue of the last 50 errors stored in the WordPress
	 * options table under the key `bcg_error_log`.
	 *
	 * @since  1.0.0
	 * @param  string $context Short label identifying where the error occurred.
	 * @param  string $message The error message to log.
	 * @return void
	 */
	private function log_error( string $context, string $message ): void {
		$log = get_option( 'bcg_error_log', array() );

		if ( ! is_array( $log ) ) {
			$log = array();
		}

		$log[] = array(
			'time'    => current_time( 'mysql', true ),
			'service' => 'gemini',
			'context' => sanitize_text_field( $context ),
			'message' => sanitize_text_field( $message ),
			'model'   => $this->model,
		);

		// Enforce maximum log size (FIFO).
		if ( count( $log ) > self::MAX_ERROR_LOG_ENTRIES ) {
			$log = array_slice( $log, -self::MAX_ERROR_LOG_ENTRIES );
		}

		update_option( 'bcg_error_log', $log, false );
	}

	/**
	 * Get the currently configured Gemini model.
	 *
	 * @since  1.0.0
	 * @return string The model identifier string.
	 */
	public function get_model(): string {
		return $this->model;
	}

	/**
	 * Get the credit cost for the currently configured model.
	 *
	 * Returns the per-image credit cost based on the selected Gemini model.
	 * Falls back to the flash tier cost if the model is not recognised.
	 *
	 * @since  1.0.0
	 * @return int Credit cost per image generation.
	 */
	public function get_credit_cost(): int {
		if ( str_contains( $this->model, 'pro' ) ) {
			return (int) get_option( 'bcg_credit_cost_gemini_pro', 10 );
		}

		return (int) get_option( 'bcg_credit_cost_gemini_flash', 3 );
	}

	/**
	 * Determine the AI service identifier for transaction logging.
	 *
	 * Maps the current model to one of the ENUM values accepted by the
	 * bcg_transactions table: 'gemini-pro' or 'gemini-flash'.
	 *
	 * @since  1.0.0
	 * @return string The service identifier for logging.
	 */
	public function get_service_identifier(): string {
		if ( str_contains( $this->model, 'pro' ) ) {
			return 'gemini-pro';
		}

		return 'gemini-flash';
	}

	/**
	 * Delete all generated images for a specific campaign.
	 *
	 * Removes the campaign's image directory and all files within it.
	 * This is intended for use during campaign deletion or cleanup.
	 *
	 * @since  1.0.0
	 * @param  int $campaign_id The campaign ID whose images should be deleted.
	 * @return bool True if the directory was removed or did not exist, false on failure.
	 */
	public function delete_campaign_images( int $campaign_id ): bool {
		if ( $campaign_id <= 0 ) {
			return false;
		}

		$upload_dir = wp_upload_dir();
		$target_dir = trailingslashit( $upload_dir['basedir'] ) . 'bcg/' . $campaign_id;

		if ( ! is_dir( $target_dir ) ) {
			return true;
		}

		$files = glob( trailingslashit( $target_dir ) . '*' );

		if ( is_array( $files ) ) {
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
					unlink( $file );
				}
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		return rmdir( $target_dir );
	}
}
