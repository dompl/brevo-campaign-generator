<?php
/**
 * Campaign image generation via OpenAI gpt-image-1 through the Red Frog API portal.
 *
 * Provides methods to generate product and campaign banner images using
 * OpenAI's gpt-image-1 model, routed through the Red Frog API portal
 * (`ats-ai-portal`) rather than a direct OpenAI key. Generated images are
 * saved to the WordPress uploads directory and their URLs are returned for
 * use in email campaigns.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Images
 *
 * Handles all communication with the Red Frog API portal for gpt-image-1
 * image generation tasks. Builds prompts from product data, calls the
 * portal, validates responses, and saves generated images to the local
 * filesystem.
 *
 * @since 1.0.0
 */
class BCG_Images {

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
	 * Image model. Only gpt-image-1 is used.
	 */
	private const MODEL = 'gpt-image-1';

	/**
	 * Hero banner size (landscape) and product tile size (square).
	 */
	private const SIZE_HERO    = '1536x1024';
	private const SIZE_PRODUCT = '1024x1024';

	/**
	 * Images take longer than text; allow two minutes.
	 */
	private const IMAGE_TIMEOUT = 120;

	/**
	 * Charge in pounds reported by the gateway for the last request.
	 *
	 * @var float|null
	 */
	private ?float $last_charged = null;

	public function __construct() {}

	/**
	 * Charge in pounds for the most recent request, or null if the gateway sent none.
	 */
	public function last_charged(): ?float {
		return $this->last_charged;
	}

	/**
	 * Model id used for every request.
	 */
	public function get_model(): string {
		return self::MODEL;
	}

	/**
	 * Shared "no key" error.
	 */
	private function no_key_error(): \WP_Error {
		return new \WP_Error(
			'ats_portal_no_key',
			__( 'AI is not connected. Run: wp ats secret set gateway_key_email', 'brevo-campaign-generator' )
		);
	}

	/**
	 * Free connectivity check: GET /models through the gateway.
	 *
	 * @return bool|\WP_Error True on 200.
	 */
	public function test_connection(): bool|\WP_Error {
		if ( ! \RedFrogStudio\AtsAiPortal\Portal::has_key( 'email' ) ) {
			return $this->no_key_error();
		}

		$response = wp_remote_get(
			\RedFrogStudio\AtsAiPortal\Portal::openai_url( '/models' ),
			array(
				'headers' => \RedFrogStudio\AtsAiPortal\Portal::headers( 'email', 'ats:email:test' ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'ats_portal_unavailable', __( 'AI service unavailable, try again.', 'brevo-campaign-generator' ) );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		return 200 === $code ? true : \RedFrogStudio\AtsAiPortal\Portal::error( $code, wp_remote_retrieve_body( $response ) );
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
		if ( ! \RedFrogStudio\AtsAiPortal\Portal::has_key( 'email' ) ) {
			return $this->no_key_error();
		}

		if ( empty( $products ) ) {
			return new \WP_Error(
				'bcg_images_no_products',
				__( 'At least one product is required to generate a main email image.', 'brevo-campaign-generator' )
			);
		}

		$product_names = $this->extract_product_names( $products );
		$prompt        = $this->build_main_image_prompt( $product_names, $theme, $style );

		$image_data = $this->call_image_generation_api( $prompt, 'ats:email:hero-image', (string) $campaign_id, self::SIZE_HERO );

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
		if ( ! \RedFrogStudio\AtsAiPortal\Portal::has_key( 'email' ) ) {
			return $this->no_key_error();
		}

		if ( empty( $product['name'] ) ) {
			return new \WP_Error(
				'bcg_images_invalid_product',
				__( 'Product name is required to generate an image.', 'brevo-campaign-generator' )
			);
		}

		$prompt = $this->build_product_image_prompt( $product, $style );

		$image_data = $this->call_image_generation_api( $prompt, 'ats:email:product-image', (string) $campaign_id, self::SIZE_PRODUCT );

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
		 * Filter the prompt for main email banner image generation.
		 *
		 * @since 1.0.0
		 * @param string $prompt        The generated prompt.
		 * @param string $product_names Comma-separated product names.
		 * @param string $theme         Campaign theme.
		 * @param string $style         Image style.
		 */
		return apply_filters( 'bcg_images_main_image_prompt', $prompt, $product_names, $theme, $style );
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
		 * Filter the prompt for individual product image generation.
		 *
		 * @since 1.0.0
		 * @param string $prompt  The generated prompt.
		 * @param array  $product Product data.
		 * @param string $style   Image style.
		 */
		return apply_filters( 'bcg_images_product_image_prompt', $prompt, $product, $style );
	}

	/**
	 * POST /images/generations through the gateway. No retry: a second image costs real money.
	 *
	 * @param string $prompt    Full prompt.
	 * @param string $purpose   Gateway X-Purpose.
	 * @param string $reference Gateway X-Reference (campaign id or '').
	 * @param string $size      WxH accepted by gpt-image-1.
	 * @return array|\WP_Error ['data' => base64 png, 'mime_type' => 'image/png', 'charged_gbp' => float|null].
	 */
	private function call_image_generation_api( string $prompt, string $purpose, string $reference = '', string $size = self::SIZE_PRODUCT ): array|\WP_Error {
		$this->last_charged = null;

		$body = array(
			'model'   => self::MODEL,
			'prompt'  => $prompt,
			'size'    => $size,
			'quality' => 'medium',
			'n'       => 1,
		);

		$response = wp_remote_post(
			\RedFrogStudio\AtsAiPortal\Portal::openai_url( '/images/generations' ),
			array(
				'headers' => \RedFrogStudio\AtsAiPortal\Portal::headers( 'email', $purpose, $reference ),
				'body'    => wp_json_encode( $body ),
				'timeout' => self::IMAGE_TIMEOUT,
			)
		);

		$code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

		if ( is_wp_error( $response ) || $code >= 500 ) {
			$this->log_error( 'request_failed', is_wp_error( $response ) ? $response->get_error_message() : 'HTTP ' . $code );
			return new \WP_Error( 'ats_portal_unavailable', __( 'AI service unavailable, try again.', 'brevo-campaign-generator' ) );
		}

		$raw = wp_remote_retrieve_body( $response );

		if ( 200 !== $code ) {
			$error = \RedFrogStudio\AtsAiPortal\Portal::error( $code, $raw );
			$this->log_error( 'api_error', $error->get_error_message() );
			return $error;
		}

		$this->last_charged = \RedFrogStudio\AtsAiPortal\Portal::observe( $response );

		$data = json_decode( $raw, true );
		$b64  = $data['data'][0]['b64_json'] ?? '';

		if ( ! is_string( $b64 ) || '' === $b64 ) {
			$this->log_error( 'invalid_response', 'No b64_json in image response' );
			return new \WP_Error( 'bcg_images_no_image', __( 'The AI service returned no image. Try again.', 'brevo-campaign-generator' ) );
		}

		return array(
			'data'        => $b64,
			'mime_type'   => 'image/png',
			'charged_gbp' => $this->last_charged,
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
		 * Fires after a generated image has been successfully saved.
		 *
		 * @since 1.0.0
		 * @param string $file_url    Full URL to the saved image.
		 * @param string $file_path   Absolute filesystem path to the saved image.
		 * @param int    $campaign_id Campaign ID the image belongs to.
		 * @param string $mime_type   MIME type of the saved image.
		 */
		do_action( 'bcg_images_image_saved', $file_url, $file_path, $campaign_id, $actual_mime );

		return $file_url;
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
			'service' => 'openai-images',
			'context' => sanitize_text_field( $context ),
			'message' => sanitize_text_field( $message ),
			'model'   => self::MODEL,
		);

		// Enforce maximum log size (FIFO).
		if ( count( $log ) > self::MAX_ERROR_LOG_ENTRIES ) {
			$log = array_slice( $log, -self::MAX_ERROR_LOG_ENTRIES );
		}

		update_option( 'bcg_error_log', $log, false );
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
