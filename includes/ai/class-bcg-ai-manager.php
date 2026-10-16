<?php
/**
 * AI Manager — orchestrator for all AI generation tasks.
 *
 * Sits between the AJAX handlers and the underlying AI service classes
 * (BCG_OpenAI for text, BCG_Images for images). Responsible for:
 * - Dispatching calls to the correct AI service
 * - Recording what the Red Frog API portal charged for each call
 * - Stopping a batch as soon as the portal reports the wallet is empty
 * - Assembling composite results for full campaign generation
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_AI_Manager
 *
 * Central dispatcher for AI generation tasks.
 *
 * @since 1.0.0
 */
class BCG_AI_Manager {

	/**
	 * Maximum number of error log entries to retain.
	 *
	 * @var int
	 */
	private const MAX_ERROR_LOG_ENTRIES = 50;

	/**
	 * OpenAI client instance.
	 *
	 * @var BCG_OpenAI
	 */
	private BCG_OpenAI $openai;

	/**
	 * Images client instance.
	 *
	 * @var BCG_Images
	 */
	private BCG_Images $images;

	/**
	 * Constructor.
	 *
	 * Initialises the AI service clients.
	 *
	 * @since 1.0.0
	 *
	 * @param BCG_OpenAI|null $openai Optional. Override the OpenAI client.
	 * @param BCG_Images|null $images Optional. Override the Images client.
	 */
	public function __construct( ?BCG_OpenAI $openai = null, ?BCG_Images $images = null ) {
		$this->openai = $openai ?? new BCG_OpenAI();
		$this->images = $images ?? new BCG_Images();
	}

	// ─── Full Campaign Generation ─────────────────────────────────────

	/**
	 * Generate all copy for a campaign in one batch.
	 *
	 * Generates: subject line, preview text, main headline, main description,
	 * product headlines and short descriptions for every product, and an
	 * optional coupon suggestion. Stops as soon as the portal reports the
	 * wallet is empty and returns that error.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $campaign_id The campaign ID.
	 * @param array $products    Array of product data arrays. Each element must
	 *                           contain 'name', 'price', 'short_description'.
	 * @param array $config      {
	 *     Campaign configuration.
	 *
	 *     @type string $theme          Campaign theme / occasion.
	 *     @type string $tone           Tone of voice.
	 *     @type string $language       Target language.
	 *     @type bool   $generate_coupon Whether to generate a coupon suggestion.
	 * }
	 * @return array|\WP_Error Associative array of generated content on success:
	 *     {
	 *         @type string $subject_line      Generated subject line.
	 *         @type string $preview_text      Generated preview text.
	 *         @type string $main_headline     Generated main headline.
	 *         @type string $main_description  Generated main description.
	 *         @type array  $products          Array of product data with 'ai_headline'
	 *                                         and 'ai_short_desc' keys added.
	 *         @type array  $coupon_suggestion Coupon suggestion array (if requested).
	 *         @type float  $total_charged_gbp Total pounds charged by the gateway.
	 *     }
	 *     WP_Error (code ats_portal_out_of_credit) as soon as any task hits it.
	 */
	public function generate_campaign_copy( int $campaign_id, array $products, array $config ): array|\WP_Error {
		$theme           = sanitize_text_field( $config['theme'] ?? '' );
		$tone            = sanitize_text_field( $config['tone'] ?? 'Professional' );
		$language        = sanitize_text_field( $config['language'] ?? 'English' );
		$generate_coupon = ! empty( $config['generate_coupon'] );

		$this->openai->set_reference( (string) $campaign_id );

		$result            = array();
		$total_charged_gbp = 0.0;
		$errors            = array();

		// --- Generate subject line ---
		$gen = $this->execute_openai_task(
			'generate_subject_line',
			function () use ( $products, $theme, $tone, $language ) {
				return $this->openai->generate_subject_line( $products, $theme, $tone, $language );
			}
		);

		if ( is_wp_error( $gen ) ) {
			if ( 'ats_portal_out_of_credit' === $gen->get_error_code() ) {
				return $gen;
			}
			$errors[] = 'subject_line: ' . $gen->get_error_message();
			$result['subject_line'] = '';
		} else {
			$result['subject_line']  = $gen['content'];
			$total_charged_gbp       += $gen['charged_gbp'];
		}

		// --- Generate preview text ---
		$subject_for_preview = ! empty( $result['subject_line'] ) ? $result['subject_line'] : '';
		$gen = $this->execute_openai_task(
			'generate_preview_text',
			function () use ( $subject_for_preview, $products ) {
				return $this->openai->generate_preview_text( $subject_for_preview, $products );
			}
		);

		if ( is_wp_error( $gen ) ) {
			if ( 'ats_portal_out_of_credit' === $gen->get_error_code() ) {
				return $gen;
			}
			$errors[] = 'preview_text: ' . $gen->get_error_message();
			$result['preview_text'] = '';
		} else {
			$result['preview_text']  = $gen['content'];
			$total_charged_gbp       += $gen['charged_gbp'];
		}

		// --- Generate main headline ---
		$gen = $this->execute_openai_task(
			'generate_main_headline',
			function () use ( $products, $theme, $tone, $language ) {
				return $this->openai->generate_main_headline( $products, $theme, $tone, $language );
			}
		);

		if ( is_wp_error( $gen ) ) {
			if ( 'ats_portal_out_of_credit' === $gen->get_error_code() ) {
				return $gen;
			}
			$errors[] = 'main_headline: ' . $gen->get_error_message();
			$result['main_headline'] = '';
		} else {
			$result['main_headline']  = $gen['content'];
			$total_charged_gbp        += $gen['charged_gbp'];
		}

		// --- Generate main description ---
		$gen = $this->execute_openai_task(
			'generate_main_description',
			function () use ( $products, $theme, $tone, $language ) {
				return $this->openai->generate_main_description( $products, $theme, $tone, $language );
			}
		);

		if ( is_wp_error( $gen ) ) {
			if ( 'ats_portal_out_of_credit' === $gen->get_error_code() ) {
				return $gen;
			}
			$errors[] = 'main_description: ' . $gen->get_error_message();
			$result['main_description'] = '';
		} else {
			$result['main_description']  = $gen['content'];
			$total_charged_gbp           += $gen['charged_gbp'];
		}

		// --- Generate per-product copy ---
		$enriched_products = array();

		foreach ( $products as $index => $product ) {
			$product_copy = $product;

			// Product headline.
			$gen = $this->execute_openai_task(
				'generate_product_headline',
				function () use ( $product, $tone, $language ) {
					return $this->openai->generate_product_headline( $product, $tone, $language );
				}
			);

			if ( is_wp_error( $gen ) ) {
				if ( 'ats_portal_out_of_credit' === $gen->get_error_code() ) {
					return $gen;
				}
				$errors[] = sprintf( 'product_%d_headline: %s', $index, $gen->get_error_message() );
				$product_copy['ai_headline'] = '';
			} else {
				$product_copy['ai_headline']  = $gen['content'];
				$total_charged_gbp            += $gen['charged_gbp'];
			}

			// Product short description.
			$gen = $this->execute_openai_task(
				'generate_product_short_desc',
				function () use ( $product, $tone, $language ) {
					return $this->openai->generate_product_short_description( $product, $tone, $language );
				}
			);

			if ( is_wp_error( $gen ) ) {
				if ( 'ats_portal_out_of_credit' === $gen->get_error_code() ) {
					return $gen;
				}
				$errors[] = sprintf( 'product_%d_short_desc: %s', $index, $gen->get_error_message() );
				$product_copy['ai_short_desc'] = '';
			} else {
				$product_copy['ai_short_desc']  = $gen['content'];
				$total_charged_gbp              += $gen['charged_gbp'];
			}

			$enriched_products[] = $product_copy;
		}

		$result['products'] = $enriched_products;

		// --- Generate coupon suggestion (optional) ---
		if ( $generate_coupon ) {
			$gen = $this->execute_openai_task(
				'generate_coupon_suggestion',
				function () use ( $products, $theme ) {
					return $this->openai->generate_coupon_discount_suggestion( $products, $theme );
				}
			);

			if ( is_wp_error( $gen ) ) {
				if ( 'ats_portal_out_of_credit' === $gen->get_error_code() ) {
					return $gen;
				}
				$errors[] = 'coupon_suggestion: ' . $gen->get_error_message();
				$result['coupon_suggestion'] = null;
			} else {
				// Coupon returns an array, not a string.
				$result['coupon_suggestion']  = $gen['content'];
				$total_charged_gbp            += $gen['charged_gbp'];
			}
		} else {
			$result['coupon_suggestion'] = null;
		}

		$result['total_charged_gbp'] = round( $total_charged_gbp, 4 );

		// Attach any partial errors for transparency.
		if ( ! empty( $errors ) ) {
			$result['partial_errors'] = $errors;
		}

		/**
		 * Fires after campaign copy has been generated.
		 *
		 * @since 1.0.0
		 *
		 * @param array $result      The generated content array.
		 * @param int   $campaign_id The campaign ID.
		 * @param array $config      The generation configuration.
		 */
		do_action( 'bcg_campaign_copy_generated', $result, $campaign_id, $config );

		return $result;
	}

	// ─── Image Generation ─────────────────────────────────────────────

	/**
	 * Generate all images for a campaign.
	 *
	 * Generates the main campaign banner image and optionally one AI image
	 * per product. Stops as soon as the portal reports the wallet is empty
	 * and returns that error.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $campaign_id The campaign ID.
	 * @param array $products    Array of product data arrays.
	 * @param array $config      {
	 *     Image generation configuration.
	 *
	 *     @type string $theme           Campaign theme / occasion.
	 *     @type string $style           Image style (Photorealistic, Studio Product, etc.).
	 *     @type bool   $generate_product_images Whether to generate per-product images.
	 * }
	 * @return array|\WP_Error Associative array on success:
	 *     {
	 *         @type string $main_image_url    URL of the generated main banner image.
	 *         @type array  $product_images    Associative array mapping product index to image URL.
	 *         @type float  $total_charged_gbp Total pounds charged by the gateway.
	 *     }
	 *     WP_Error (code ats_portal_out_of_credit) as soon as any task hits it.
	 */
	public function generate_campaign_images( int $campaign_id, array $products, array $config ): array|\WP_Error {
		$theme                   = sanitize_text_field( $config['theme'] ?? '' );
		$style                   = sanitize_text_field( $config['style'] ?? 'Photorealistic' );
		$generate_product_images = ! empty( $config['generate_product_images'] );

		$result            = array();
		$total_charged_gbp = 0.0;
		$errors            = array();

		// --- Generate main banner image ---
		$gen = $this->execute_image_task(
			'generate_main_image',
			function () use ( $products, $theme, $style, $campaign_id ) {
				return $this->images->generate_main_email_image( $products, $theme, $style, $campaign_id );
			}
		);

		if ( is_wp_error( $gen ) ) {
			if ( 'ats_portal_out_of_credit' === $gen->get_error_code() ) {
				return $gen;
			}
			$errors[] = 'main_image: ' . $gen->get_error_message();
			$result['main_image_url'] = '';
		} else {
			$result['main_image_url']  = $gen['content'];
			$total_charged_gbp         += $gen['charged_gbp'];
		}

		// --- Generate per-product images ---
		$product_images = array();

		if ( $generate_product_images ) {
			foreach ( $products as $index => $product ) {
				$gen = $this->execute_image_task(
					'generate_product_image',
					function () use ( $product, $style, $campaign_id ) {
						return $this->images->generate_product_image( $product, $style, $campaign_id );
					}
				);

				if ( is_wp_error( $gen ) ) {
					if ( 'ats_portal_out_of_credit' === $gen->get_error_code() ) {
						return $gen;
					}
					$errors[] = sprintf( 'product_%d_image: %s', $index, $gen->get_error_message() );
					$product_images[ $index ] = '';
				} else {
					$product_images[ $index ]  = $gen['content'];
					$total_charged_gbp         += $gen['charged_gbp'];
				}
			}
		}

		$result['product_images']    = $product_images;
		$result['total_charged_gbp'] = round( $total_charged_gbp, 4 );

		if ( ! empty( $errors ) ) {
			$result['partial_errors'] = $errors;
		}

		/**
		 * Fires after campaign images have been generated.
		 *
		 * @since 1.0.0
		 *
		 * @param array $result      The generated images array.
		 * @param int   $campaign_id The campaign ID.
		 * @param array $config      The generation configuration.
		 */
		do_action( 'bcg_campaign_images_generated', $result, $campaign_id, $config );

		return $result;
	}

	// ─── Single Field Regeneration ────────────────────────────────────

	/**
	 * Regenerate a single field for a campaign.
	 *
	 * Calls the appropriate AI service for the field type and returns the
	 * new content and the amount charged by the portal.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $campaign_id The campaign ID.
	 * @param string $field       The field to regenerate. One of: 'subject_line',
	 *                            'preview_text', 'main_headline', 'main_description',
	 *                            'product_headline', 'product_short_desc',
	 *                            'coupon_suggestion', 'main_image', 'product_image'.
	 * @param array  $context     {
	 *     Contextual data required for regeneration.
	 *
	 *     @type array  $products    Array of product data (required for most fields).
	 *     @type array  $product     Single product data (for product-level fields).
	 *     @type string $theme       Campaign theme.
	 *     @type string $tone        Tone of voice.
	 *     @type string $language    Target language.
	 *     @type string $subject     Subject line (for preview_text regeneration).
	 *     @type string $style       Image style (for image fields).
	 * }
	 * @return array|\WP_Error Associative array on success:
	 *     {
	 *         @type mixed  $content     The regenerated content (string or array).
	 *         @type float  $charged_gbp Amount charged by the portal, in GBP.
	 *     }
	 *     WP_Error on failure.
	 */
	public function regenerate_field( int $campaign_id, string $field, array $context ): array|\WP_Error {
		$field = sanitize_text_field( $field );

		// Determine whether this is a text or image task.
		$image_fields = array( 'main_image', 'product_image' );
		$is_image     = in_array( $field, $image_fields, true );

		// Validate the field name.
		$valid_fields = array(
			'subject_line',
			'preview_text',
			'main_headline',
			'main_description',
			'product_headline',
			'product_short_desc',
			'coupon_suggestion',
			'main_image',
			'product_image',
		);

		if ( ! in_array( $field, $valid_fields, true ) ) {
			return new \WP_Error(
				'bcg_invalid_field',
				sprintf(
					/* translators: %s: field name */
					__( 'Invalid field name for regeneration: %s', 'brevo-campaign-generator' ),
					esc_html( $field )
				)
			);
		}

		// Route to the correct handler.
		if ( $is_image ) {
			return $this->regenerate_image_field( $campaign_id, $field, $context );
		}

		return $this->regenerate_text_field( $campaign_id, $field, $context );
	}

	/**
	 * Regenerate a text-based field via OpenAI.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $campaign_id The campaign ID.
	 * @param string $field       The field name.
	 * @param array  $context     Contextual data for generation.
	 * @return array|\WP_Error Result array with 'content' and 'charged_gbp', or WP_Error.
	 */
	private function regenerate_text_field( int $campaign_id, string $field, array $context ): array|\WP_Error {
		$products = $context['products'] ?? array();
		$product  = $context['product'] ?? array();
		$theme    = sanitize_text_field( $context['theme'] ?? '' );
		$tone     = sanitize_text_field( $context['tone'] ?? 'Professional' );
		$language = sanitize_text_field( $context['language'] ?? 'English' );
		$subject  = sanitize_text_field( $context['subject'] ?? '' );

		$this->openai->set_reference( (string) $campaign_id );

		// Map field name to OpenAI method and task identifier.
		$task_map = array(
			'subject_line'       => 'generate_subject_line',
			'preview_text'       => 'generate_preview_text',
			'main_headline'      => 'generate_main_headline',
			'main_description'   => 'generate_main_description',
			'product_headline'   => 'generate_product_headline',
			'product_short_desc' => 'generate_product_short_desc',
			'coupon_suggestion'  => 'generate_coupon_suggestion',
		);

		$task = $task_map[ $field ] ?? '';

		if ( empty( $task ) ) {
			return new \WP_Error(
				'bcg_invalid_text_field',
				__( 'Cannot determine the generation task for this field.', 'brevo-campaign-generator' )
			);
		}

		// Build the callable based on field type.
		$callable = match ( $field ) {
			'subject_line'       => fn() => $this->openai->generate_subject_line( $products, $theme, $tone, $language ),
			'preview_text'       => fn() => $this->openai->generate_preview_text( $subject, $products ),
			'main_headline'      => fn() => $this->openai->generate_main_headline( $products, $theme, $tone, $language ),
			'main_description'   => fn() => $this->openai->generate_main_description( $products, $theme, $tone, $language ),
			'product_headline'   => fn() => $this->openai->generate_product_headline( $product, $tone, $language ),
			'product_short_desc' => fn() => $this->openai->generate_product_short_description( $product, $tone, $language ),
			'coupon_suggestion'  => fn() => $this->openai->generate_coupon_discount_suggestion( $products, $theme ),
		};

		return $this->execute_openai_task( $task, $callable );
	}

	/**
	 * Regenerate an image-based field via BCG_Images.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $campaign_id The campaign ID.
	 * @param string $field       The field name ('main_image' or 'product_image').
	 * @param array  $context     Contextual data for generation.
	 * @return array|\WP_Error Result array with 'content' (URL) and 'charged_gbp', or WP_Error.
	 */
	private function regenerate_image_field( int $campaign_id, string $field, array $context ): array|\WP_Error {
		$products = $context['products'] ?? array();
		$product  = $context['product'] ?? array();
		$theme    = sanitize_text_field( $context['theme'] ?? '' );
		$style    = sanitize_text_field( $context['style'] ?? 'Photorealistic' );

		$task_map = array(
			'main_image'    => 'generate_main_image',
			'product_image' => 'generate_product_image',
		);

		$task = $task_map[ $field ] ?? '';

		$callable = match ( $field ) {
			'main_image'    => fn() => $this->images->generate_main_email_image( $products, $theme, $style, $campaign_id ),
			'product_image' => fn() => $this->images->generate_product_image( $product, $style, $campaign_id ),
			default         => null,
		};

		if ( null === $callable ) {
			return new \WP_Error(
				'bcg_invalid_image_field',
				__( 'Cannot determine the image generation task for this field.', 'brevo-campaign-generator' )
			);
		}

		return $this->execute_image_task( $task, $callable );
	}

	// ─── Task Execution ─────────────────────────────────────────────────

	/**
	 * Execute an OpenAI text generation task and record the portal charge.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $task     The task identifier for logging (e.g. 'generate_subject_line').
	 * @param callable $callable A closure that calls the appropriate OpenAI method.
	 *                           Must return a string or array on success, WP_Error on failure.
	 * @return array|\WP_Error Array with 'content', 'charged_gbp' and 'tokens_used' on success, WP_Error on failure.
	 */
	private function execute_openai_task( string $task, callable $callable ): array|\WP_Error {
		// Execute the AI call.
		$result = $callable();

		// Handle failure.
		if ( is_wp_error( $result ) ) {
			$this->log_error(
				'openai_task_failed',
				sprintf( 'Task %s failed: %s', $task, $result->get_error_message() ),
				array( 'campaign_task' => $task, 'code' => $result->get_error_code() )
			);

			return $result;
		}

		$tokens_used = $this->openai->get_last_tokens_used();
		$charged_gbp = (float) ( $this->openai->last_charged() ?? 0.0 );

		/**
		 * Fires after a successful OpenAI generation task.
		 *
		 * @since 1.6.0
		 *
		 * @param string $task        The task identifier.
		 * @param mixed  $result      The generated content.
		 * @param int    $tokens_used Tokens consumed by the API call.
		 * @param float  $charged_gbp Amount charged by the portal, in GBP.
		 */
		do_action( 'bcg_openai_task_completed', $task, $result, $tokens_used, $charged_gbp );

		return array(
			'content'     => $result,
			'charged_gbp' => $charged_gbp,
			'tokens_used' => $tokens_used,
		);
	}

	/**
	 * Execute an image generation task and record the portal charge.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $task     The task identifier for logging (e.g. 'generate_main_image').
	 * @param callable $callable A closure that calls the appropriate BCG_Images method.
	 *                           Must return a URL string on success, WP_Error on failure.
	 * @return array|\WP_Error Array with 'content' (URL) and 'charged_gbp' on success, WP_Error on failure.
	 */
	private function execute_image_task( string $task, callable $callable ): array|\WP_Error {
		// Execute the AI call.
		$result = $callable();

		// Handle failure.
		if ( is_wp_error( $result ) ) {
			$this->log_error(
				'image_task_failed',
				sprintf( 'Task %s failed: %s', $task, $result->get_error_message() ),
				array( 'campaign_task' => $task, 'code' => $result->get_error_code() )
			);

			return $result;
		}

		$charged_gbp = (float) ( $this->images->last_charged() ?? 0.0 );

		/**
		 * Fires after a successful image generation task.
		 *
		 * @since 1.6.0
		 *
		 * @param string $task        The task identifier.
		 * @param string $result      The generated image URL.
		 * @param float  $charged_gbp Amount charged by the portal, in GBP.
		 */
		do_action( 'bcg_image_task_completed', $task, $result, $charged_gbp );

		return array(
			'content'     => $result,
			'charged_gbp' => $charged_gbp,
		);
	}

	// ─── Error Logging ────────────────────────────────────────────────

	/**
	 * Log an error to the plugin's error log.
	 *
	 * Stores the last N errors (FIFO) in the bcg_error_log WordPress option.
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

		$log[] = array(
			'timestamp' => current_time( 'mysql' ),
			'source'    => 'ai-manager',
			'code'      => sanitize_text_field( $code ),
			'message'   => sanitize_text_field( $message ),
			'context'   => $context,
		);

		if ( count( $log ) > self::MAX_ERROR_LOG_ENTRIES ) {
			$log = array_slice( $log, -self::MAX_ERROR_LOG_ENTRIES );
		}

		update_option( 'bcg_error_log', $log, false );
	}

	// ─── Accessors ────────────────────────────────────────────────────

	/**
	 * Get the OpenAI client instance.
	 *
	 * @since 1.0.0
	 *
	 * @return BCG_OpenAI
	 */
	public function get_openai(): BCG_OpenAI {
		return $this->openai;
	}

	/**
	 * Get the image generation client instance.
	 *
	 * @since 1.0.0
	 *
	 * @return BCG_Images
	 */
	public function get_images(): BCG_Images {
		return $this->images;
	}

	/**
	 * Get the current AI portal balance.
	 *
	 * @since 1.6.0
	 *
	 * @return float|null The balance in GBP, or null if it could not be read.
	 */
	public function get_balance_gbp(): ?float {
		return \RedFrogStudio\AtsAiPortal\Portal::balance();
	}
}
