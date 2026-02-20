<?php
/**
 * AI Manager — orchestrator for all AI generation tasks and credit handling.
 *
 * Sits between the AJAX handlers and the underlying AI service classes
 * (BCG_OpenAI for text, BCG_Gemini for images). Responsible for:
 * - Calculating credit costs before each operation
 * - Deducting credits and logging transactions
 * - Dispatching calls to the correct AI service
 * - Refunding credits on failure
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
 * Central dispatcher for AI generation tasks with integrated credit management.
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
	 * Gemini client instance.
	 *
	 * @var BCG_Gemini
	 */
	private BCG_Gemini $gemini;

	/**
	 * Credits handler instance.
	 *
	 * @var BCG_Credits
	 */
	private BCG_Credits $credits;

	/**
	 * The current WordPress user ID.
	 *
	 * @var int
	 */
	private int $user_id;

	/**
	 * Constructor.
	 *
	 * Initialises the AI service clients and credits handler.
	 *
	 * @since 1.0.0
	 *
	 * @param BCG_OpenAI|null  $openai  Optional. Override the OpenAI client.
	 * @param BCG_Gemini|null  $gemini  Optional. Override the Gemini client.
	 * @param BCG_Credits|null $credits Optional. Override the credits handler.
	 */
	public function __construct( ?BCG_OpenAI $openai = null, ?BCG_Gemini $gemini = null, ?BCG_Credits $credits = null ) {
		$this->openai  = $openai ?? new BCG_OpenAI();
		$this->gemini  = $gemini ?? new BCG_Gemini();
		$this->credits = $credits ?? new BCG_Credits();
		$this->user_id = get_current_user_id();
	}

	// ─── Full Campaign Generation ─────────────────────────────────────

	/**
	 * Generate all copy for a campaign in one batch.
	 *
	 * Generates: subject line, preview text, main headline, main description,
	 * product headlines and short descriptions for every product, and an
	 * optional coupon suggestion. Credits are deducted before each OpenAI
	 * call and refunded on individual failures.
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
	 *         @type int    $total_credits_used Total credits consumed.
	 *     }
	 *     WP_Error if insufficient credits for the entire batch.
	 */
	public function generate_campaign_copy( int $campaign_id, array $products, array $config ): array|\WP_Error {
		$theme           = sanitize_text_field( $config['theme'] ?? '' );
		$tone            = sanitize_text_field( $config['tone'] ?? 'Professional' );
		$language        = sanitize_text_field( $config['language'] ?? 'English' );
		$generate_coupon = ! empty( $config['generate_coupon'] );

		// Calculate total credits needed for the batch.
		// 4 campaign-level generations + 2 per product + 1 optional coupon.
		$cost_per_generation = $this->get_credit_cost( 'openai', 'text' );
		$generation_count    = 4 + ( count( $products ) * 2 );

		if ( $generate_coupon ) {
			++$generation_count;
		}

		$total_cost = $cost_per_generation * $generation_count;

		// Pre-check that the user has enough credits for the entire batch.
		if ( ! $this->check_credits( $total_cost ) ) {
			return new \WP_Error(
				'bcg_insufficient_credits',
				sprintf(
					/* translators: 1: credits required, 2: current balance */
					__( 'Insufficient credits. This campaign requires %1$s credits but your balance is %2$s. Please top up your credits.', 'brevo-campaign-generator' ),
					number_format( $total_cost, 0 ),
					number_format( $this->credits->get_balance( $this->user_id ), 0 )
				),
				array(
					'credits_required' => $total_cost,
					'credits_balance'  => $this->credits->get_balance( $this->user_id ),
				)
			);
		}

		$result              = array();
		$total_credits_used  = 0;
		$errors              = array();

		// --- Generate subject line ---
		$gen = $this->execute_openai_task(
			'generate_subject_line',
			function () use ( $products, $theme, $tone, $language ) {
				return $this->openai->generate_subject_line( $products, $theme, $tone, $language );
			}
		);

		if ( is_wp_error( $gen ) ) {
			$errors[] = 'subject_line: ' . $gen->get_error_message();
			$result['subject_line'] = '';
		} else {
			$result['subject_line']  = $gen['content'];
			$total_credits_used     += $gen['credits_used'];
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
			$errors[] = 'preview_text: ' . $gen->get_error_message();
			$result['preview_text'] = '';
		} else {
			$result['preview_text']  = $gen['content'];
			$total_credits_used     += $gen['credits_used'];
		}

		// --- Generate main headline ---
		$gen = $this->execute_openai_task(
			'generate_main_headline',
			function () use ( $products, $theme, $tone, $language ) {
				return $this->openai->generate_main_headline( $products, $theme, $tone, $language );
			}
		);

		if ( is_wp_error( $gen ) ) {
			$errors[] = 'main_headline: ' . $gen->get_error_message();
			$result['main_headline'] = '';
		} else {
			$result['main_headline']  = $gen['content'];
			$total_credits_used      += $gen['credits_used'];
		}

		// --- Generate main description ---
		$gen = $this->execute_openai_task(
			'generate_main_description',
			function () use ( $products, $theme, $tone, $language ) {
				return $this->openai->generate_main_description( $products, $theme, $tone, $language );
			}
		);

		if ( is_wp_error( $gen ) ) {
			$errors[] = 'main_description: ' . $gen->get_error_message();
			$result['main_description'] = '';
		} else {
			$result['main_description']  = $gen['content'];
			$total_credits_used         += $gen['credits_used'];
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
				$errors[] = sprintf( 'product_%d_headline: %s', $index, $gen->get_error_message() );
				$product_copy['ai_headline'] = '';
			} else {
				$product_copy['ai_headline']  = $gen['content'];
				$total_credits_used          += $gen['credits_used'];
			}

			// Product short description.
			$gen = $this->execute_openai_task(
				'generate_product_short_desc',
				function () use ( $product, $tone, $language ) {
					return $this->openai->generate_product_short_description( $product, $tone, $language );
				}
			);

			if ( is_wp_error( $gen ) ) {
				$errors[] = sprintf( 'product_%d_short_desc: %s', $index, $gen->get_error_message() );
				$product_copy['ai_short_desc'] = '';
			} else {
				$product_copy['ai_short_desc']  = $gen['content'];
				$total_credits_used            += $gen['credits_used'];
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
				$errors[] = 'coupon_suggestion: ' . $gen->get_error_message();
				$result['coupon_suggestion'] = null;
			} else {
				// Coupon returns an array, not a string.
				$result['coupon_suggestion']  = $gen['content'];
				$total_credits_used          += $gen['credits_used'];
			}
		} else {
			$result['coupon_suggestion'] = null;
		}

		$result['total_credits_used'] = $total_credits_used;

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
	 * per product. Credits are deducted per image before each call and
	 * refunded on individual failures.
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
	 *         @type string $main_image_url   URL of the generated main banner image.
	 *         @type array  $product_images   Associative array mapping product index to image URL.
	 *         @type int    $total_credits_used Total credits consumed.
	 *     }
	 *     WP_Error if insufficient credits.
	 */
	public function generate_campaign_images( int $campaign_id, array $products, array $config ): array|\WP_Error {
		$theme                    = sanitize_text_field( $config['theme'] ?? '' );
		$style                    = sanitize_text_field( $config['style'] ?? 'Photorealistic' );
		$generate_product_images  = ! empty( $config['generate_product_images'] );

		// Calculate total image credits needed.
		$cost_per_image = $this->get_credit_cost( 'gemini', 'image' );
		$image_count    = 1; // Main banner image.

		if ( $generate_product_images ) {
			$image_count += count( $products );
		}

		$total_cost = $cost_per_image * $image_count;

		// Pre-check credits for the whole batch.
		if ( ! $this->check_credits( $total_cost ) ) {
			return new \WP_Error(
				'bcg_insufficient_credits',
				sprintf(
					/* translators: 1: credits required, 2: current balance */
					__( 'Insufficient credits for image generation. This requires %1$s credits but your balance is %2$s. Please top up your credits.', 'brevo-campaign-generator' ),
					number_format( $total_cost, 0 ),
					number_format( $this->credits->get_balance( $this->user_id ), 0 )
				),
				array(
					'credits_required' => $total_cost,
					'credits_balance'  => $this->credits->get_balance( $this->user_id ),
				)
			);
		}

		$result             = array();
		$total_credits_used = 0;
		$errors             = array();

		// --- Generate main banner image ---
		$gen = $this->execute_gemini_task(
			'generate_main_image',
			function () use ( $products, $theme, $style, $campaign_id ) {
				return $this->gemini->generate_main_email_image( $products, $theme, $style, $campaign_id );
			}
		);

		if ( is_wp_error( $gen ) ) {
			$errors[] = 'main_image: ' . $gen->get_error_message();
			$result['main_image_url'] = '';
		} else {
			$result['main_image_url']  = $gen['content'];
			$total_credits_used       += $gen['credits_used'];
		}

		// --- Generate per-product images ---
		$product_images = array();

		if ( $generate_product_images ) {
			foreach ( $products as $index => $product ) {
				$gen = $this->execute_gemini_task(
					'generate_product_image',
					function () use ( $product, $style, $campaign_id ) {
						return $this->gemini->generate_product_image( $product, $style, $campaign_id );
					}
				);

				if ( is_wp_error( $gen ) ) {
					$errors[] = sprintf( 'product_%d_image: %s', $index, $gen->get_error_message() );
					$product_images[ $index ] = '';
				} else {
					$product_images[ $index ]  = $gen['content'];
					$total_credits_used       += $gen['credits_used'];
				}
			}
		}

		$result['product_images']     = $product_images;
		$result['total_credits_used'] = $total_credits_used;

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
	 * Deducts the correct credits for the field type and model, calls the
	 * appropriate AI service, and returns the new content. Refunds credits
	 * on failure.
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
	 *         @type mixed  $content      The regenerated content (string or array).
	 *         @type int    $credits_used Credits consumed.
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
	 * @return array|\WP_Error Result array with 'content' and 'credits_used', or WP_Error.
	 */
	private function regenerate_text_field( int $campaign_id, string $field, array $context ): array|\WP_Error {
		$products = $context['products'] ?? array();
		$product  = $context['product'] ?? array();
		$theme    = sanitize_text_field( $context['theme'] ?? '' );
		$tone     = sanitize_text_field( $context['tone'] ?? 'Professional' );
		$language = sanitize_text_field( $context['language'] ?? 'English' );
		$subject  = sanitize_text_field( $context['subject'] ?? '' );

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
	 * Regenerate an image-based field via Gemini.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $campaign_id The campaign ID.
	 * @param string $field       The field name ('main_image' or 'product_image').
	 * @param array  $context     Contextual data for generation.
	 * @return array|\WP_Error Result array with 'content' (URL) and 'credits_used', or WP_Error.
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
			'main_image'    => fn() => $this->gemini->generate_main_email_image( $products, $theme, $style, $campaign_id ),
			'product_image' => fn() => $this->gemini->generate_product_image( $product, $style, $campaign_id ),
			default         => null,
		};

		if ( null === $callable ) {
			return new \WP_Error(
				'bcg_invalid_image_field',
				__( 'Cannot determine the image generation task for this field.', 'brevo-campaign-generator' )
			);
		}

		return $this->execute_gemini_task( $task, $callable );
	}

	// ─── Task Execution with Credit Management ────────────────────────

	/**
	 * Execute an OpenAI text generation task with credit management.
	 *
	 * Deducts credits before the API call, executes the callable,
	 * records token usage, and refunds on failure.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $task     The task identifier for logging (e.g. 'generate_subject_line').
	 * @param callable $callable A closure that calls the appropriate OpenAI method.
	 *                           Must return a string or array on success, WP_Error on failure.
	 * @return array|\WP_Error Array with 'content' and 'credits_used' on success, WP_Error on failure.
	 */
	private function execute_openai_task( string $task, callable $callable ): array|\WP_Error {
		$credit_cost = $this->get_credit_cost( 'openai', 'text' );

		// Check credits.
		if ( ! $this->check_credits( $credit_cost ) ) {
			return new \WP_Error(
				'bcg_insufficient_credits',
				sprintf(
					/* translators: %s: credits required */
					__( 'Insufficient credits. This operation requires %s credits.', 'brevo-campaign-generator' ),
					number_format( $credit_cost, 0 )
				)
			);
		}

		// Determine the OpenAI service identifier for the ENUM column.
		$model   = $this->openai->get_model();
		$service = 'openai';

		// Deduct credits.
		$deduction = $this->deduct_credits( $credit_cost, $service, $task, null );

		if ( is_wp_error( $deduction ) ) {
			return $deduction;
		}

		// Execute the AI call.
		$result = $callable();

		// Handle failure — refund credits.
		if ( is_wp_error( $result ) ) {
			$this->refund_credits(
				$credit_cost,
				sprintf(
					/* translators: 1: task name, 2: error message */
					__( 'Refund for failed %1$s: %2$s', 'brevo-campaign-generator' ),
					$task,
					$result->get_error_message()
				)
			);

			$this->log_error(
				'openai_task_failed',
				sprintf( 'Task %s failed: %s', $task, $result->get_error_message() ),
				array( 'campaign_task' => $task )
			);

			return $result;
		}

		// Record the actual token usage (update the transaction log with token info).
		$tokens_used = $this->openai->get_last_tokens_used();

		/**
		 * Fires after a successful OpenAI generation task.
		 *
		 * @since 1.0.0
		 *
		 * @param string $task        The task identifier.
		 * @param mixed  $result      The generated content.
		 * @param int    $tokens_used Tokens consumed by the API call.
		 * @param int    $credit_cost Credits deducted.
		 */
		do_action( 'bcg_openai_task_completed', $task, $result, $tokens_used, $credit_cost );

		return array(
			'content'      => $result,
			'credits_used' => $credit_cost,
			'tokens_used'  => $tokens_used,
		);
	}

	/**
	 * Execute a Gemini image generation task with credit management.
	 *
	 * Deducts credits before the API call, executes the callable,
	 * and refunds on failure.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $task     The task identifier for logging (e.g. 'generate_main_image').
	 * @param callable $callable A closure that calls the appropriate Gemini method.
	 *                           Must return a URL string on success, WP_Error on failure.
	 * @return array|\WP_Error Array with 'content' (URL) and 'credits_used' on success, WP_Error on failure.
	 */
	private function execute_gemini_task( string $task, callable $callable ): array|\WP_Error {
		$credit_cost = $this->get_credit_cost( 'gemini', 'image' );

		// Check credits.
		if ( ! $this->check_credits( $credit_cost ) ) {
			return new \WP_Error(
				'bcg_insufficient_credits',
				sprintf(
					/* translators: %s: credits required */
					__( 'Insufficient credits. This image generation requires %s credits.', 'brevo-campaign-generator' ),
					number_format( $credit_cost, 0 )
				)
			);
		}

		// Determine the Gemini service identifier for the ENUM column.
		$service = $this->gemini->get_service_identifier();

		// Deduct credits.
		$deduction = $this->deduct_credits( $credit_cost, $service, $task, null );

		if ( is_wp_error( $deduction ) ) {
			return $deduction;
		}

		// Execute the AI call.
		$result = $callable();

		// Handle failure — refund credits.
		if ( is_wp_error( $result ) ) {
			$this->refund_credits(
				$credit_cost,
				sprintf(
					/* translators: 1: task name, 2: error message */
					__( 'Refund for failed %1$s: %2$s', 'brevo-campaign-generator' ),
					$task,
					$result->get_error_message()
				)
			);

			$this->log_error(
				'gemini_task_failed',
				sprintf( 'Task %s failed: %s', $task, $result->get_error_message() ),
				array( 'campaign_task' => $task )
			);

			return $result;
		}

		/**
		 * Fires after a successful Gemini generation task.
		 *
		 * @since 1.0.0
		 *
		 * @param string $task        The task identifier.
		 * @param string $result      The generated image URL.
		 * @param int    $credit_cost Credits deducted.
		 */
		do_action( 'bcg_gemini_task_completed', $task, $result, $credit_cost );

		return array(
			'content'      => $result,
			'credits_used' => $credit_cost,
		);
	}

	// ─── Credit Management ────────────────────────────────────────────

	/**
	 * Check whether the current user has sufficient credits.
	 *
	 * @since 1.0.0
	 *
	 * @param float $required The number of credits required.
	 * @return bool True if the user has enough credits, false otherwise.
	 */
	public function check_credits( float $required ): bool {
		if ( 'yes' === get_option( 'bcg_test_mode', 'no' ) ) {
			return true;
		}

		return $this->credits->has_sufficient_credits( $this->user_id, $required );
	}

	/**
	 * Deduct credits from the current user and log the transaction.
	 *
	 * @since 1.0.0
	 *
	 * @param float       $amount  The number of credits to deduct.
	 * @param string|null $service The AI service identifier ('openai', 'gemini-pro', 'gemini-flash').
	 * @param string|null $task    The task identifier for logging.
	 * @param int|null    $tokens  The number of tokens used (OpenAI only).
	 * @return float|\WP_Error The new balance on success, WP_Error on failure.
	 */
	public function deduct_credits( float $amount, ?string $service = null, ?string $task = null, ?int $tokens = null ): float|\WP_Error {
		if ( 'yes' === get_option( 'bcg_test_mode', 'no' ) ) {
			return 0.0;
		}

		return $this->credits->deduct_credits( $this->user_id, $amount, $service, $task, $tokens );
	}

	/**
	 * Refund credits to the current user and log the refund transaction.
	 *
	 * @since 1.0.0
	 *
	 * @param float  $amount      The number of credits to refund.
	 * @param string $description A human-readable reason for the refund.
	 * @return float|\WP_Error The new balance on success, WP_Error on failure.
	 */
	public function refund_credits( float $amount, string $description = '' ): float|\WP_Error {
		return $this->credits->refund_credits( $this->user_id, $amount, $description );
	}

	/**
	 * Get the credit cost for a given AI service and task type.
	 *
	 * Reads the credit costs from WordPress options. The cost depends on the
	 * currently configured model for each service.
	 *
	 * @since 1.0.0
	 *
	 * @param string $service The AI service: 'openai' or 'gemini'.
	 * @param string $task    The task type: 'text' or 'image'.
	 * @return int The credit cost per operation.
	 */
	public function get_credit_cost( string $service, string $task = 'text' ): int {
		if ( 'openai' === $service ) {
			return $this->openai->get_credit_cost();
		}

		if ( 'gemini' === $service ) {
			return $this->gemini->get_credit_cost();
		}

		// Fallback: look up from options by service and task.
		$option_key = sprintf( 'bcg_credit_cost_%s_%s', sanitize_key( $service ), sanitize_key( $task ) );
		return (int) get_option( $option_key, 1 );
	}

	/**
	 * Get the current user's credit balance.
	 *
	 * @since 1.0.0
	 *
	 * @return float The current credit balance.
	 */
	public function get_credit_balance(): float {
		return $this->credits->get_balance( $this->user_id );
	}

	/**
	 * Estimate the total credit cost for a full campaign generation.
	 *
	 * Useful for displaying an estimate to the user before they confirm
	 * the generation.
	 *
	 * @since 1.0.0
	 *
	 * @param int  $product_count          Number of products in the campaign.
	 * @param bool $generate_images        Whether AI images will be generated.
	 * @param bool $generate_coupon        Whether a coupon suggestion will be generated.
	 * @return array {
	 *     @type int $copy_credits   Credits needed for all text generation.
	 *     @type int $image_credits  Credits needed for all image generation.
	 *     @type int $total_credits  Total credits needed.
	 *     @type int $current_balance Current user's credit balance.
	 *     @type bool $can_afford    Whether the user can afford the generation.
	 * }
	 */
	public function estimate_campaign_cost( int $product_count, bool $generate_images = false, bool $generate_coupon = true ): array {
		$text_cost_per  = $this->get_credit_cost( 'openai', 'text' );
		$image_cost_per = $this->get_credit_cost( 'gemini', 'image' );

		// Text: 4 campaign fields + 2 per product + optional coupon.
		$text_count = 4 + ( $product_count * 2 );
		if ( $generate_coupon ) {
			++$text_count;
		}
		$copy_credits = $text_cost_per * $text_count;

		// Images: 1 main + 1 per product (if enabled).
		$image_count   = $generate_images ? ( 1 + $product_count ) : 0;
		$image_credits = $image_cost_per * $image_count;

		$total          = $copy_credits + $image_credits;
		$current_balance = $this->get_credit_balance();

		return array(
			'copy_credits'    => $copy_credits,
			'image_credits'   => $image_credits,
			'total_credits'   => $total,
			'current_balance' => $current_balance,
			'can_afford'      => $current_balance >= $total,
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
			'user_id'   => $this->user_id,
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
	 * Get the Gemini client instance.
	 *
	 * @since 1.0.0
	 *
	 * @return BCG_Gemini
	 */
	public function get_gemini(): BCG_Gemini {
		return $this->gemini;
	}

	/**
	 * Get the credits handler instance.
	 *
	 * @since 1.0.0
	 *
	 * @return BCG_Credits
	 */
	public function get_credits(): BCG_Credits {
		return $this->credits;
	}
}
