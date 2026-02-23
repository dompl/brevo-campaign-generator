<?php
/**
 * Section AI dispatcher.
 *
 * Dispatches AI generation requests to the appropriate OpenAI method for
 * each section type in the Section Builder. Handles all has_ai section
 * types: hero, text, banner, cta, products.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.5.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BCG_Section_AI
 *
 * AI content generation dispatcher for Section Builder section types.
 *
 * @since 1.5.0
 */
class BCG_Section_AI {

	/**
	 * Generate AI content for a single section.
	 *
	 * Dispatches to the appropriate generation method based on section type
	 * and merges the generated values into the section's settings array.
	 *
	 * @since  1.5.0
	 *
	 * @param string $type     Section type slug (hero, text, banner, cta, products).
	 * @param array  $settings Current section settings.
	 * @param array  $context  Campaign context: products[], theme, tone, language, currency_symbol.
	 * @return array|\WP_Error Updated settings array on success, WP_Error on failure.
	 */
	public static function generate( string $type, array $settings, array $context ): array|\WP_Error {
		$openai = new BCG_OpenAI();

		// Inject campaign-level AI prompt if provided.
		if ( ! empty( $context['prompt'] ) ) {
			$openai->set_campaign_prompt( $context['prompt'] );
		}

		$tone     = sanitize_text_field( $context['tone'] ?? 'Professional' );
		$language = sanitize_text_field( $context['language'] ?? 'English' );

		switch ( $type ) {
			case 'hero':
			case 'hero_split':
				return self::generate_hero( $openai, $settings, $context, $tone, $language );

			case 'text':
				return self::generate_text( $openai, $settings, $context, $tone, $language );

			case 'banner':
				return self::generate_banner( $openai, $settings, $context, $tone, $language );

			case 'cta':
				return self::generate_cta( $openai, $settings, $context, $tone, $language );

			case 'products':
				return self::generate_products_section( $openai, $settings, $context, $tone, $language );

			case 'coupon':
			case 'coupon_banner':
			case 'coupon_card':
			case 'coupon_split':
			case 'coupon_minimal':
			case 'coupon_ribbon':
				return self::generate_coupon_text( $openai, $settings, $context, $tone, $language );

			case 'footer':
				return self::generate_footer_text( $openai, $settings, $context, $tone, $language );

			default:
				// Section type has no AI generation — return settings unchanged.
				return $settings;
		}
	}

	/**
	 * Generate AI content for all has_ai sections in a sections array.
	 *
	 * Iterates over each section and calls generate() for those with has_ai = true
	 * in the registry. Returns the full sections array with AI fields merged in.
	 *
	 * @since  1.5.0
	 *
	 * @param  array $sections Array of section objects (each with 'id', 'type', 'settings').
	 * @param  array $context  Campaign context array.
	 * @return array Updated sections array. Individual WP_Errors are stored in settings['_ai_error'].
	 */
	public static function generate_all( array $sections, array $context ): array {
		foreach ( $sections as &$section ) {
			$type     = $section['type'] ?? '';
			$type_def = BCG_Section_Registry::get( $type );

			if ( ! $type_def || empty( $type_def['has_ai'] ) ) {
				continue;
			}

			$result = self::generate( $type, $section['settings'] ?? array(), $context );

			if ( is_wp_error( $result ) ) {
				// Store the error message in settings so JS can surface it.
				$section['settings']['_ai_error'] = $result->get_error_message();
			} else {
				$section['settings'] = $result;
			}
		}
		unset( $section );

		return $sections;
	}

	// ── Private Generation Methods ───────────────────────────────────

	/**
	 * Generate hero section AI content.
	 *
	 * @since  1.5.0
	 * @param  BCG_OpenAI $openai   OpenAI instance.
	 * @param  array      $settings Current settings.
	 * @param  array      $context  Campaign context.
	 * @param  string     $tone     Tone of voice.
	 * @param  string     $language Target language.
	 * @return array|\WP_Error
	 */
	private static function generate_hero( BCG_OpenAI $openai, array $settings, array $context, string $tone, string $language ): array|\WP_Error {
		$products = $context['products'] ?? array();
		$theme    = $context['theme'] ?? '';

		if ( $settings['_ai_headline'] ?? true ) {
			$headline = $openai->generate_main_headline( $products, $theme, $tone, $language );
			if ( is_wp_error( $headline ) ) {
				return $headline;
			}
			$settings['headline'] = $headline;
		}

		if ( $settings['_ai_subtext'] ?? true ) {
			$subtext = $openai->generate_main_description( $products, $theme, $tone, $language );
			if ( is_wp_error( $subtext ) ) {
				return $subtext;
			}
			$settings['subtext'] = $subtext;
		}

		return $settings;
	}

	/**
	 * Generate text block section AI content.
	 *
	 * @since  1.5.0
	 * @param  BCG_OpenAI $openai   OpenAI instance.
	 * @param  array      $settings Current settings.
	 * @param  array      $context  Campaign context.
	 * @param  string     $tone     Tone of voice.
	 * @param  string     $language Target language.
	 * @return array|\WP_Error
	 */
	private static function generate_text( BCG_OpenAI $openai, array $settings, array $context, string $tone, string $language ): array|\WP_Error {
		$gen_heading = $settings['_ai_heading'] ?? true;
		$gen_body    = $settings['_ai_body'] ?? true;

		if ( ! $gen_heading && ! $gen_body ) {
			return $settings;
		}

		$result = $openai->generate_text_block( $context, $tone, $language );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( $gen_heading && ! empty( $result['heading'] ) ) {
			$settings['heading'] = $result['heading'];
		}
		if ( $gen_body && ! empty( $result['body'] ) ) {
			$settings['body'] = $result['body'];
		}

		return $settings;
	}

	/**
	 * Generate banner section AI content.
	 *
	 * @since  1.5.0
	 * @param  BCG_OpenAI $openai   OpenAI instance.
	 * @param  array      $settings Current settings.
	 * @param  array      $context  Campaign context.
	 * @param  string     $tone     Tone of voice.
	 * @param  string     $language Target language.
	 * @return array|\WP_Error
	 */
	private static function generate_banner( BCG_OpenAI $openai, array $settings, array $context, string $tone, string $language ): array|\WP_Error {
		$gen_heading = $settings['_ai_heading'] ?? true;
		$gen_subtext = $settings['_ai_subtext'] ?? true;

		if ( ! $gen_heading && ! $gen_subtext ) {
			return $settings;
		}

		$result = $openai->generate_banner_text( $context, $tone, $language );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( $gen_heading && ! empty( $result['heading'] ) ) {
			$settings['heading'] = $result['heading'];
		}
		if ( $gen_subtext && ! empty( $result['subtext'] ) ) {
			$settings['subtext'] = $result['subtext'];
		}

		return $settings;
	}

	/**
	 * Generate CTA section AI content.
	 *
	 * @since  1.5.0
	 * @param  BCG_OpenAI $openai   OpenAI instance.
	 * @param  array      $settings Current settings.
	 * @param  array      $context  Campaign context.
	 * @param  string     $tone     Tone of voice.
	 * @param  string     $language Target language.
	 * @return array|\WP_Error
	 */
	private static function generate_cta( BCG_OpenAI $openai, array $settings, array $context, string $tone, string $language ): array|\WP_Error {
		$gen_heading = $settings['_ai_heading'] ?? true;
		$gen_subtext = $settings['_ai_subtext'] ?? true;

		if ( ! $gen_heading && ! $gen_subtext ) {
			return $settings;
		}

		$result = $openai->generate_cta_text( $context, $tone, $language );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( $gen_heading && ! empty( $result['heading'] ) ) {
			$settings['heading'] = $result['heading'];
		}
		if ( $gen_subtext && ! empty( $result['subtext'] ) ) {
			$settings['subtext'] = $result['subtext'];
		}
		if ( ! empty( $result['button_text'] ) ) {
			$settings['button_text'] = $result['button_text'];
		}

		return $settings;
	}

	/**
	 * Generate products section AI content.
	 *
	 * Generates AI headline and short description for each product ID listed
	 * in the section's product_ids setting. The generated text is stored in
	 * ai_overrides sub-array keyed by product ID. Also generates a section_headline
	 * if the _ai_section_headline flag is not false.
	 *
	 * @since  1.5.0
	 * @param  BCG_OpenAI $openai   OpenAI instance.
	 * @param  array      $settings Current settings.
	 * @param  array      $context  Campaign context.
	 * @param  string     $tone     Tone of voice.
	 * @param  string     $language Target language.
	 * @return array|\WP_Error
	 */
	private static function generate_products_section( BCG_OpenAI $openai, array $settings, array $context, string $tone, string $language ): array|\WP_Error {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return $settings;
		}

		$ids_raw = $settings['product_ids'] ?? '';
		if ( is_string( $ids_raw ) ) {
			$product_ids = array_filter( array_map( 'absint', explode( ',', $ids_raw ) ) );
		} else {
			$product_ids = array_filter( array_map( 'absint', (array) $ids_raw ) );
		}

		if ( empty( $product_ids ) ) {
			return $settings;
		}

		$overrides = $settings['ai_overrides'] ?? array();

		foreach ( $product_ids as $pid ) {
			$product = wc_get_product( $pid );
			if ( ! $product ) {
				continue;
			}

			$product_data = array(
				'name'              => $product->get_name(),
				'price'             => $product->get_price(),
				'short_description' => wp_strip_all_tags( $product->get_short_description() ),
				'category'          => implode( ', ', wp_get_post_terms( $pid, 'product_cat', array( 'fields' => 'names' ) ) ),
			);

			$headline = $openai->generate_product_headline( $product_data, $tone, $language );
			$desc     = $openai->generate_product_short_description( $product_data, $tone, $language );

			$overrides[ $pid ] = array(
				'headline' => is_wp_error( $headline ) ? '' : $headline,
				'desc'     => is_wp_error( $desc ) ? '' : $desc,
			);
		}

		$settings['ai_overrides'] = $overrides;

		// Generate section headline if enabled.
		if ( ( $settings['_ai_section_headline'] ?? true ) && ! empty( $context['products'] ) ) {
			$headline = $openai->generate_product_headline( $context['products'][0], $tone, $language );
			if ( ! is_wp_error( $headline ) ) {
				$settings['section_headline'] = $headline;
			}
		}

		return $settings;
	}

	/**
	 * Generate coupon section AI text (headline, coupon_text, subtext).
	 *
	 * Used for all coupon variants: coupon, coupon_banner, coupon_card,
	 * coupon_split, coupon_minimal, coupon_ribbon.
	 *
	 * @since  1.6.0
	 * @param  BCG_OpenAI $openai   OpenAI instance.
	 * @param  array      $settings Current settings.
	 * @param  array      $context  Campaign context.
	 * @param  string     $tone     Tone of voice.
	 * @param  string     $language Target language.
	 * @return array|\WP_Error
	 */
	private static function generate_coupon_text( BCG_OpenAI $openai, array $settings, array $context, string $tone, string $language ): array|\WP_Error {
		$products = $context['products'] ?? array();
		$theme    = $context['theme'] ?? '';

		if ( $settings['_ai_headline'] ?? true ) {
			$result = $openai->generate_main_headline( $products, $theme, $tone, $language );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			$settings['headline'] = $result;
		}

		if ( $settings['_ai_coupon_text'] ?? true ) {
			$result = $openai->generate_main_description( $products, $theme, $tone, $language );
			if ( ! is_wp_error( $result ) ) {
				// Keep it short — first sentence only.
				$text = preg_replace( '/\..*$/s', '.', $result );
				$settings['coupon_text'] = trim( $text ) ?: $result;
			}
		}

		if ( $settings['_ai_subtext'] ?? true ) {
			$result = $openai->generate_preview_text( $theme . ' ' . ( $settings['headline'] ?? '' ), $products );
			if ( ! is_wp_error( $result ) ) {
				$settings['subtext'] = $result;
			}
		}

		return $settings;
	}

	/**
	 * Generate footer section AI text.
	 *
	 * Footer text (legal/unsubscribe) is not AI-generated as it typically
	 * contains required legal language. This method is a no-op placeholder
	 * that returns settings unchanged.
	 *
	 * @since  1.6.0
	 * @param  BCG_OpenAI $openai   OpenAI instance.
	 * @param  array      $settings Current settings.
	 * @param  array      $context  Campaign context.
	 * @param  string     $tone     Tone of voice.
	 * @param  string     $language Target language.
	 * @return array
	 */
	private static function generate_footer_text( BCG_OpenAI $openai, array $settings, array $context, string $tone, string $language ): array {
		// Footer usually contains legal/unsubscribe text — skip AI generation to
		// avoid overwriting required compliance copy.
		return $settings;
	}
}
