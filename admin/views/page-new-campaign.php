<?php
/**
 * New Campaign wizard view (Step 1: Configure).
 *
 * Renders the four-section campaign configuration form: Campaign Basics,
 * Product Selection, Coupon, and AI Generation Options. Submitting the
 * form triggers an AJAX-driven generation pipeline that creates draft
 * content and redirects to the campaign editor (Step 2).
 *
 * Loaded by BCG_Admin::render_new_campaign_page().
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Retrieve defaults from settings.
$default_products       = (int) get_option( 'bcg_default_products_per_campaign', 3 );
$default_discount       = (float) get_option( 'bcg_default_coupon_discount', 10 );
$default_expiry         = (int) get_option( 'bcg_default_coupon_expiry_days', 7 );
$default_auto_coupon    = get_option( 'bcg_default_auto_generate_coupon', 'yes' );
$default_mailing_list   = get_option( 'bcg_brevo_default_list_id', '' );

// Product categories for the filter tree.
$product_selector = new BCG_Product_Selector();
$categories       = $product_selector->get_categories();

// Build a lookup table for category hierarchy.
$category_lookup = array();

foreach ( $categories as $cat ) {
	$category_lookup[ $cat['term_id'] ] = $cat;
}

// WordPress locale for default language.
$wp_locale      = get_locale();
$default_lang   = 'en';
$locale_map     = array(
	'en_' => 'en',
	'pl_' => 'pl',
	'de_' => 'de',
	'fr_' => 'fr',
	'es_' => 'es',
	'it_' => 'it',
	'pt_' => 'pt',
	'nl_' => 'nl',
	'sv_' => 'sv',
	'da_' => 'da',
	'no_' => 'no',
	'cs_' => 'cs',
	'ro_' => 'ro',
);

foreach ( $locale_map as $prefix => $lang_code ) {
	if ( str_starts_with( $wp_locale, $prefix ) ) {
		$default_lang = $lang_code;
		break;
	}
}
?>
<?php require BCG_PLUGIN_DIR . 'admin/views/partials/plugin-header.php'; ?>
<div class="wrap bcg-wrap bcg-new-campaign-wrap">

	<h1><?php esc_html_e( 'New Campaign', 'brevo-campaign-generator' ); ?></h1>

	<?php settings_errors( 'bcg_campaign' ); ?>

	<!-- Notices container for JS-driven messages -->
	<div id="bcg-wizard-notices"></div>

	<form id="bcg-campaign-wizard" class="bcg-campaign-wizard" method="post" novalidate>
		<?php wp_nonce_field( 'bcg_nonce', 'bcg_nonce' ); ?>

		<!-- ============================================================
		     Section 1: Campaign Basics
		     ============================================================ -->
		<div class="bcg-card bcg-wizard-section" id="bcg-section-basics">
			<div class="bcg-card-header">
				<h2><?php esc_html_e( '1. Campaign Basics', 'brevo-campaign-generator' ); ?></h2>
			</div>
			<div class="bcg-card-body">

				<!-- Campaign Title -->
				<div class="bcg-field-row">
					<label for="bcg-campaign-title" class="bcg-field-label">
						<?php esc_html_e( 'Campaign Title', 'brevo-campaign-generator' ); ?>
						<span class="bcg-required">*</span>
					</label>
					<input
						type="text"
						id="bcg-campaign-title"
						name="campaign_title"
						class="regular-text bcg-full-width"
						required
						placeholder="<?php esc_attr_e( 'e.g. Summer Sale 2026', 'brevo-campaign-generator' ); ?>"
					/>
				</div>

				<!-- Subject Line -->
				<div class="bcg-field-row">
					<label for="bcg-subject-line" class="bcg-field-label">
						<?php esc_html_e( 'Subject Line', 'brevo-campaign-generator' ); ?>
					</label>
					<div class="bcg-field-with-action">
						<input
							type="text"
							id="bcg-subject-line"
							name="subject_line"
							class="regular-text bcg-full-width"
							placeholder="<?php esc_attr_e( 'Enter or generate with AI...', 'brevo-campaign-generator' ); ?>"
						/>
						<button
							type="button"
							class="bcg-btn-secondary bcg-ai-generate-btn"
							data-field="subject_line"
							data-action="bcg_regenerate_field"
						>
							<?php esc_html_e( 'Generate with AI', 'brevo-campaign-generator' ); ?>
						</button>
					</div>
				</div>

				<!-- Preview Text -->
				<div class="bcg-field-row">
					<label for="bcg-preview-text" class="bcg-field-label">
						<?php esc_html_e( 'Preview Text', 'brevo-campaign-generator' ); ?>
					</label>
					<div class="bcg-field-with-action">
						<input
							type="text"
							id="bcg-preview-text"
							name="preview_text"
							class="regular-text bcg-full-width"
							placeholder="<?php esc_attr_e( 'Short text shown after subject in inbox...', 'brevo-campaign-generator' ); ?>"
						/>
						<button
							type="button"
							class="bcg-btn-secondary bcg-ai-generate-btn"
							data-field="preview_text"
							data-action="bcg_regenerate_field"
						>
							<?php esc_html_e( 'Generate', 'brevo-campaign-generator' ); ?>
						</button>
					</div>
				</div>

				<!-- Mailing List -->
				<div class="bcg-field-row">
					<label for="bcg-mailing-list" class="bcg-field-label">
						<?php esc_html_e( 'Mailing List', 'brevo-campaign-generator' ); ?>
					</label>
					<div class="bcg-field-with-action">
						<select
							id="bcg-mailing-list"
							name="mailing_list_id"
							class="bcg-brevo-list-select"
							data-current="<?php echo esc_attr( $default_mailing_list ); ?>"
						>
							<option value="">
								<?php esc_html_e( '-- Select a mailing list --', 'brevo-campaign-generator' ); ?>
							</option>
							<?php if ( $default_mailing_list ) : ?>
								<option value="<?php echo esc_attr( $default_mailing_list ); ?>" selected>
									<?php
									printf(
										/* translators: %s: mailing list ID */
										esc_html__( 'List ID: %s (loading...)', 'brevo-campaign-generator' ),
										esc_html( $default_mailing_list )
									);
									?>
								</option>
							<?php endif; ?>
						</select>
						<button
							type="button"
							class="bcg-btn-secondary bcg-refresh-lists-btn"
							id="bcg-refresh-lists"
						>
							<?php esc_html_e( 'Refresh', 'brevo-campaign-generator' ); ?>
						</button>
					</div>
				</div>

			</div>
		</div>

		<!-- ============================================================
		     Section 2: Product Selection
		     ============================================================ -->
		<div class="bcg-card bcg-wizard-section" id="bcg-section-products">
			<div class="bcg-card-header">
				<h2><?php esc_html_e( '2. Product Selection', 'brevo-campaign-generator' ); ?></h2>
			</div>
			<div class="bcg-card-body">

				<!-- Number of products -->
				<div class="bcg-field-row">
					<label for="bcg-product-count" class="bcg-field-label">
						<?php esc_html_e( 'Number of Products', 'brevo-campaign-generator' ); ?>
					</label>
					<input
						type="number"
						id="bcg-product-count"
						name="product_count"
						class="small-text"
						min="1"
						max="10"
						step="1"
						value="<?php echo esc_attr( $default_products ); ?>"
					/>
					<span class="bcg-field-suffix"><?php esc_html_e( 'products (1-10)', 'brevo-campaign-generator' ); ?></span>
				</div>

				<!-- Product Source -->
				<div class="bcg-field-row">
					<label class="bcg-field-label">
						<?php esc_html_e( 'Product Source', 'brevo-campaign-generator' ); ?>
					</label>
					<div class="bcg-radio-cards" id="bcg-product-source-group" role="radiogroup">
						<label class="bcg-radio-card">
							<input
								type="radio"
								name="product_source"
								value="bestsellers"
								checked
							/>
							<span class="bcg-radio-card-dot"></span>
							<span class="bcg-radio-card-body">
								<span class="bcg-radio-card-title"><?php esc_html_e( 'Best Sellers', 'brevo-campaign-generator' ); ?></span>
								<span class="bcg-radio-card-desc"><?php esc_html_e( 'Sorted by sales count (highest first)', 'brevo-campaign-generator' ); ?></span>
							</span>
						</label>
						<label class="bcg-radio-card">
							<input
								type="radio"
								name="product_source"
								value="leastsold"
							/>
							<span class="bcg-radio-card-dot"></span>
							<span class="bcg-radio-card-body">
								<span class="bcg-radio-card-title"><?php esc_html_e( 'Least Sold', 'brevo-campaign-generator' ); ?></span>
								<span class="bcg-radio-card-desc"><?php esc_html_e( 'Sorted by sales count (lowest first)', 'brevo-campaign-generator' ); ?></span>
							</span>
						</label>
						<label class="bcg-radio-card">
							<input
								type="radio"
								name="product_source"
								value="latest"
							/>
							<span class="bcg-radio-card-dot"></span>
							<span class="bcg-radio-card-body">
								<span class="bcg-radio-card-title"><?php esc_html_e( 'Latest Products', 'brevo-campaign-generator' ); ?></span>
								<span class="bcg-radio-card-desc"><?php esc_html_e( 'Sorted by date (newest first)', 'brevo-campaign-generator' ); ?></span>
							</span>
						</label>
						<label class="bcg-radio-card">
							<input
								type="radio"
								name="product_source"
								value="manual"
							/>
							<span class="bcg-radio-card-dot"></span>
							<span class="bcg-radio-card-body">
								<span class="bcg-radio-card-title"><?php esc_html_e( 'Manual Selection', 'brevo-campaign-generator' ); ?></span>
								<span class="bcg-radio-card-desc"><?php esc_html_e( 'Search and pick products individually', 'brevo-campaign-generator' ); ?></span>
							</span>
						</label>
					</div>
				</div>

				<!-- Manual Product Picker (hidden by default) -->
				<div class="bcg-field-row bcg-manual-picker-row" id="bcg-manual-picker" style="display: none;">
					<label for="bcg-product-search" class="bcg-field-label">
						<?php esc_html_e( 'Search Products', 'brevo-campaign-generator' ); ?>
					</label>
					<div class="bcg-product-search-wrapper">
						<input
							type="text"
							id="bcg-product-search"
							class="regular-text bcg-full-width"
							placeholder="<?php esc_attr_e( 'Type to search products...', 'brevo-campaign-generator' ); ?>"
							autocomplete="off"
						/>
						<div id="bcg-product-search-results" class="bcg-product-search-results" style="display: none;"></div>
					</div>
					<div id="bcg-manual-selected-products" class="bcg-manual-selected-products"></div>
					<input type="hidden" id="bcg-manual-product-ids" name="manual_product_ids" value="" />
				</div>

				<!-- Category Filter -->
				<?php if ( ! empty( $categories ) ) : ?>
					<div class="bcg-field-row">
						<label class="bcg-field-label">
							<?php esc_html_e( 'Filter by Category', 'brevo-campaign-generator' ); ?>
							<span class="bcg-text-muted bcg-text-small"><?php esc_html_e( '(optional)', 'brevo-campaign-generator' ); ?></span>
						</label>
						<div class="bcg-category-tree-wrapper" id="bcg-category-tree">
							<div class="bcg-category-tree-actions">
								<button type="button" class="bcg-btn-secondary bcg-btn-sm" id="bcg-category-select-all">
									<?php esc_html_e( 'Select All', 'brevo-campaign-generator' ); ?>
								</button>
								<button type="button" class="bcg-btn-secondary bcg-btn-sm" id="bcg-category-deselect-all">
									<?php esc_html_e( 'Deselect All', 'brevo-campaign-generator' ); ?>
								</button>
							</div>
							<div class="bcg-category-tree-list">
								<?php bcg_render_category_tree( $categories, $category_lookup, 0 ); ?>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<!-- Preview Products Button -->
				<div class="bcg-field-row bcg-preview-products-row">
					<button
						type="button"
						class="bcg-btn-secondary"
						id="bcg-preview-products-btn"
					>
						<?php esc_html_e( 'Preview Products', 'brevo-campaign-generator' ); ?>
					</button>
					<span class="bcg-spinner bcg-spinner-small" id="bcg-preview-spinner" style="display: none;"></span>
				</div>

				<!-- Product Preview Area -->
				<div id="bcg-product-preview-area" class="bcg-product-preview-area" style="display: none;">
					<h3 class="bcg-preview-heading">
						<?php esc_html_e( 'Selected Products Preview', 'brevo-campaign-generator' ); ?>
						<span id="bcg-preview-count" class="bcg-text-muted"></span>
					</h3>
					<div id="bcg-product-preview-grid" class="bcg-product-preview-grid"></div>
				</div>

			</div>
		</div>

		<!-- ============================================================
		     Section 3: Coupon
		     ============================================================ -->
		<div class="bcg-card bcg-wizard-section" id="bcg-section-coupon">
			<div class="bcg-card-header">
				<h2><?php esc_html_e( '3. Coupon', 'brevo-campaign-generator' ); ?></h2>
			</div>
			<div class="bcg-card-body">

				<!-- Generate coupon toggle -->
				<div class="bcg-field-row">
					<label class="bcg-toggle" for="bcg-generate-coupon">
						<span class="bcg-toggle-switch">
							<input
								type="checkbox"
								id="bcg-generate-coupon"
								name="generate_coupon"
								value="1"
								<?php checked( $default_auto_coupon, 'yes' ); ?>
							/>
							<span class="bcg-toggle-thumb"></span>
						</span>
						<span class="bcg-toggle-content">
							<span class="bcg-toggle-title"><?php esc_html_e( 'Generate coupon automatically', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-toggle-description"><?php esc_html_e( 'Creates a unique WooCommerce coupon code for this campaign', 'brevo-campaign-generator' ); ?></span>
						</span>
					</label>
				</div>

				<!-- Coupon details (shown/hidden based on checkbox) -->
				<div id="bcg-coupon-details" class="bcg-coupon-details">

					<!-- Discount Type -->
					<div class="bcg-field-row">
						<label class="bcg-field-label">
							<?php esc_html_e( 'Discount Type', 'brevo-campaign-generator' ); ?>
						</label>
						<div class="bcg-radio-cards bcg-radio-cards-inline" role="radiogroup">
							<label class="bcg-radio-card">
								<input type="radio" name="coupon_type" value="percent" checked />
								<span class="bcg-radio-card-dot"></span>
								<span class="bcg-radio-card-body">
									<span class="bcg-radio-card-title"><?php esc_html_e( 'Percentage', 'brevo-campaign-generator' ); ?></span>
									<span class="bcg-radio-card-desc"><?php esc_html_e( 'e.g. 20% off', 'brevo-campaign-generator' ); ?></span>
								</span>
							</label>
							<label class="bcg-radio-card">
								<input type="radio" name="coupon_type" value="fixed_cart" />
								<span class="bcg-radio-card-dot"></span>
								<span class="bcg-radio-card-body">
									<span class="bcg-radio-card-title"><?php esc_html_e( 'Fixed Amount', 'brevo-campaign-generator' ); ?></span>
									<span class="bcg-radio-card-desc"><?php esc_html_e( 'e.g. £5 off cart', 'brevo-campaign-generator' ); ?></span>
								</span>
							</label>
						</div>
					</div>

					<!-- Discount Value -->
					<div class="bcg-field-row">
						<label for="bcg-discount-value" class="bcg-field-label">
							<?php esc_html_e( 'Discount Value', 'brevo-campaign-generator' ); ?>
						</label>
						<div class="bcg-field-with-action">
							<input
								type="number"
								id="bcg-discount-value"
								name="coupon_discount"
								class="small-text"
								min="1"
								max="100"
								step="1"
								value="<?php echo esc_attr( $default_discount ); ?>"
							/>
							<span class="bcg-field-suffix" id="bcg-discount-suffix">%</span>
							<button
								type="button"
								class="bcg-btn-secondary bcg-ai-generate-btn"
								id="bcg-suggest-discount-btn"
								data-action="bcg_generate_coupon"
							>
								<?php esc_html_e( 'Generate Suggestion', 'brevo-campaign-generator' ); ?>
							</button>
						</div>
					</div>

					<!-- Expiry Days -->
					<div class="bcg-field-row">
						<label for="bcg-coupon-expiry" class="bcg-field-label">
							<?php esc_html_e( 'Expiry', 'brevo-campaign-generator' ); ?>
						</label>
						<input
							type="number"
							id="bcg-coupon-expiry"
							name="coupon_expiry_days"
							class="small-text"
							min="1"
							max="365"
							step="1"
							value="<?php echo esc_attr( $default_expiry ); ?>"
						/>
						<span class="bcg-field-suffix"><?php esc_html_e( 'days from today', 'brevo-campaign-generator' ); ?></span>
					</div>

					<!-- Custom Coupon Code Prefix -->
					<div class="bcg-field-row">
						<label for="bcg-coupon-prefix" class="bcg-field-label">
							<?php esc_html_e( 'Custom Code Prefix', 'brevo-campaign-generator' ); ?>
							<span class="bcg-text-muted bcg-text-small"><?php esc_html_e( '(optional)', 'brevo-campaign-generator' ); ?></span>
						</label>
						<input
							type="text"
							id="bcg-coupon-prefix"
							name="coupon_prefix"
							class="regular-text"
							maxlength="20"
							placeholder="<?php esc_attr_e( 'e.g. SALE', 'brevo-campaign-generator' ); ?>"
						/>
						<p class="description">
							<?php esc_html_e( 'A prefix for the auto-generated coupon code, e.g. SALE-A3K9P2.', 'brevo-campaign-generator' ); ?>
						</p>
					</div>

				</div>

			</div>
		</div>

		<!-- ============================================================
		     Section 4: AI Generation Options
		     ============================================================ -->
		<div class="bcg-card bcg-wizard-section" id="bcg-section-ai">
			<div class="bcg-card-header">
				<h2><?php esc_html_e( '4. AI Generation Options', 'brevo-campaign-generator' ); ?></h2>
			</div>
			<div class="bcg-card-body">

				<!-- Tone of Voice -->
				<div class="bcg-field-row">
					<label for="bcg-tone" class="bcg-field-label">
						<?php esc_html_e( 'Tone of Voice', 'brevo-campaign-generator' ); ?>
					</label>
					<select id="bcg-tone" name="tone" class="bcg-select-medium">
						<option value="professional"><?php esc_html_e( 'Professional', 'brevo-campaign-generator' ); ?></option>
						<option value="friendly"><?php esc_html_e( 'Friendly', 'brevo-campaign-generator' ); ?></option>
						<option value="urgent"><?php esc_html_e( 'Urgent', 'brevo-campaign-generator' ); ?></option>
						<option value="playful"><?php esc_html_e( 'Playful', 'brevo-campaign-generator' ); ?></option>
						<option value="luxury"><?php esc_html_e( 'Luxury', 'brevo-campaign-generator' ); ?></option>
					</select>
				</div>

				<!-- Campaign Theme / Occasion -->
				<div class="bcg-field-row">
					<label for="bcg-theme" class="bcg-field-label">
						<?php esc_html_e( 'Campaign Theme / Occasion', 'brevo-campaign-generator' ); ?>
						<span class="bcg-text-muted bcg-text-small"><?php esc_html_e( '(optional)', 'brevo-campaign-generator' ); ?></span>
					</label>
					<input
						type="text"
						id="bcg-theme"
						name="theme"
						class="regular-text bcg-full-width"
						placeholder="<?php esc_attr_e( 'e.g. Black Friday, Summer Sale, Christmas Gifts...', 'brevo-campaign-generator' ); ?>"
					/>
				</div>

				<!-- Language -->
				<div class="bcg-field-row">
					<label for="bcg-language" class="bcg-field-label">
						<?php esc_html_e( 'Language', 'brevo-campaign-generator' ); ?>
					</label>
					<select id="bcg-language" name="language" class="bcg-select-medium">
						<option value="en" <?php selected( $default_lang, 'en' ); ?>><?php esc_html_e( 'English', 'brevo-campaign-generator' ); ?></option>
						<option value="pl" <?php selected( $default_lang, 'pl' ); ?>><?php esc_html_e( 'Polish', 'brevo-campaign-generator' ); ?></option>
						<option value="de" <?php selected( $default_lang, 'de' ); ?>><?php esc_html_e( 'German', 'brevo-campaign-generator' ); ?></option>
						<option value="fr" <?php selected( $default_lang, 'fr' ); ?>><?php esc_html_e( 'French', 'brevo-campaign-generator' ); ?></option>
						<option value="es" <?php selected( $default_lang, 'es' ); ?>><?php esc_html_e( 'Spanish', 'brevo-campaign-generator' ); ?></option>
						<option value="it" <?php selected( $default_lang, 'it' ); ?>><?php esc_html_e( 'Italian', 'brevo-campaign-generator' ); ?></option>
						<option value="pt" <?php selected( $default_lang, 'pt' ); ?>><?php esc_html_e( 'Portuguese', 'brevo-campaign-generator' ); ?></option>
						<option value="nl" <?php selected( $default_lang, 'nl' ); ?>><?php esc_html_e( 'Dutch', 'brevo-campaign-generator' ); ?></option>
						<option value="sv" <?php selected( $default_lang, 'sv' ); ?>><?php esc_html_e( 'Swedish', 'brevo-campaign-generator' ); ?></option>
						<option value="da" <?php selected( $default_lang, 'da' ); ?>><?php esc_html_e( 'Danish', 'brevo-campaign-generator' ); ?></option>
						<option value="no" <?php selected( $default_lang, 'no' ); ?>><?php esc_html_e( 'Norwegian', 'brevo-campaign-generator' ); ?></option>
						<option value="cs" <?php selected( $default_lang, 'cs' ); ?>><?php esc_html_e( 'Czech', 'brevo-campaign-generator' ); ?></option>
						<option value="ro" <?php selected( $default_lang, 'ro' ); ?>><?php esc_html_e( 'Romanian', 'brevo-campaign-generator' ); ?></option>
					</select>
				</div>

				<!-- Generate AI Images toggle -->
				<div class="bcg-field-row">
					<label class="bcg-toggle" for="bcg-generate-images">
						<span class="bcg-toggle-switch">
							<input
								type="checkbox"
								id="bcg-generate-images"
								name="generate_images"
								value="1"
							/>
							<span class="bcg-toggle-thumb"></span>
						</span>
						<span class="bcg-toggle-content">
							<span class="bcg-toggle-title"><?php esc_html_e( 'Generate product images with AI', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-toggle-description"><?php esc_html_e( 'Uses Gemini to create campaign imagery. If unchecked, WooCommerce product images are used instead.', 'brevo-campaign-generator' ); ?></span>
						</span>
					</label>
				</div>

				<!-- Image Style (shown only when AI images enabled) -->
				<div class="bcg-field-row" id="bcg-image-style-row" style="display: none;">
					<label for="bcg-image-style" class="bcg-field-label">
						<?php esc_html_e( 'Image Style', 'brevo-campaign-generator' ); ?>
					</label>
					<select id="bcg-image-style" name="image_style" class="bcg-select-medium">
						<option value="photorealistic"><?php esc_html_e( 'Photorealistic', 'brevo-campaign-generator' ); ?></option>
						<option value="studio_product"><?php esc_html_e( 'Studio Product', 'brevo-campaign-generator' ); ?></option>
						<option value="lifestyle"><?php esc_html_e( 'Lifestyle', 'brevo-campaign-generator' ); ?></option>
						<option value="minimalist"><?php esc_html_e( 'Minimalist', 'brevo-campaign-generator' ); ?></option>
						<option value="vivid_illustration"><?php esc_html_e( 'Vivid Illustration', 'brevo-campaign-generator' ); ?></option>
					</select>
				</div>

			</div>
		</div>

		<!-- ============================================================
		     Section 5: Email Template
		     ============================================================ -->
		<div class="bcg-card bcg-wizard-section" id="bcg-section-template">
			<div class="bcg-card-header">
				<h2><?php esc_html_e( '5. Email Template', 'brevo-campaign-generator' ); ?></h2>
			</div>
			<div class="bcg-card-body">
				<p class="description bcg-mb-12">
					<?php esc_html_e( 'Choose the email template layout for your campaign. You can customise colours and styles later in the template editor.', 'brevo-campaign-generator' ); ?>
				</p>
				<?php
				$template_registry = BCG_Template_Registry::get_instance();
				$all_templates     = $template_registry->get_templates();

				$layout_diagrams = array(
					'side-by-side'  => '<rect x="2" y="2" width="16" height="16" rx="1" fill="#ccc"/><rect x="20" y="2" width="24" height="4" rx="1" fill="#999"/><rect x="20" y="8" width="20" height="3" rx="1" fill="#bbb"/><rect x="20" y="13" width="14" height="5" rx="1" fill="%s"/>',
					'stacked'       => '<rect x="4" y="1" width="38" height="10" rx="1" fill="#ccc"/><rect x="4" y="13" width="30" height="3" rx="1" fill="#999"/><rect x="4" y="18" width="20" height="5" rx="1" fill="%s"/>',
					'reversed'      => '<rect x="2" y="2" width="24" height="4" rx="1" fill="#999"/><rect x="2" y="8" width="20" height="3" rx="1" fill="#bbb"/><rect x="2" y="13" width="14" height="5" rx="1" fill="%s"/><rect x="28" y="2" width="16" height="16" rx="1" fill="#ccc"/>',
					'alternating'   => '<rect x="2" y="1" width="12" height="8" rx="1" fill="#ccc"/><rect x="16" y="1" width="28" height="3" rx="1" fill="#999"/><rect x="16" y="5" width="22" height="2" rx="1" fill="#bbb"/><rect x="6" y="12" width="28" height="3" rx="1" fill="#999"/><rect x="6" y="16" width="22" height="2" rx="1" fill="#bbb"/><rect x="32" y="11" width="12" height="8" rx="1" fill="#ccc"/>',
					'grid'          => '<rect x="2" y="2" width="20" height="10" rx="1" fill="#ccc"/><rect x="24" y="2" width="20" height="10" rx="1" fill="#ccc"/><rect x="2" y="14" width="16" height="3" rx="1" fill="#999"/><rect x="24" y="14" width="16" height="3" rx="1" fill="#999"/><rect x="2" y="19" width="12" height="3" rx="1" fill="%s"/><rect x="24" y="19" width="12" height="3" rx="1" fill="%s"/>',
					'compact'       => '<rect x="2" y="2" width="8" height="8" rx="1" fill="#ccc"/><rect x="12" y="2" width="26" height="3" rx="1" fill="#999"/><rect x="12" y="6" width="20" height="2" rx="1" fill="#bbb"/><rect x="2" y="13" width="8" height="8" rx="1" fill="#ccc"/><rect x="12" y="13" width="26" height="3" rx="1" fill="#999"/><rect x="12" y="17" width="20" height="2" rx="1" fill="#bbb"/>',
					'full-card'     => '<rect x="4" y="1" width="38" height="1" rx="0" fill="#ddd"/><rect x="4" y="2" width="38" height="8" rx="0" fill="#ccc"/><rect x="4" y="10" width="38" height="1" rx="0" fill="#ddd"/><rect x="6" y="12" width="28" height="3" rx="1" fill="#999"/><rect x="6" y="16" width="20" height="2" rx="1" fill="#bbb"/><rect x="4" y="20" width="38" height="1" rx="0" fill="#ddd"/>',
					'text-only'     => '<rect x="4" y="2" width="30" height="4" rx="1" fill="#999"/><rect x="4" y="8" width="38" height="2" rx="1" fill="#bbb"/><line x1="4" y1="12" x2="42" y2="12" stroke="#ddd" stroke-width="1"/><rect x="4" y="14" width="28" height="4" rx="1" fill="#999"/><rect x="4" y="20" width="38" height="2" rx="1" fill="#bbb"/>',
					'centered'      => '<rect x="10" y="2" width="26" height="8" rx="2" fill="#ccc"/><rect x="8" y="12" width="30" height="3" rx="1" fill="#999"/><rect x="12" y="17" width="22" height="5" rx="2" fill="%s"/>',
					'feature-first' => '<rect x="2" y="1" width="42" height="10" rx="1" fill="#ccc"/><rect x="2" y="13" width="30" height="3" rx="1" fill="#999"/><rect x="2" y="18" width="6" height="6" rx="1" fill="#ccc"/><rect x="10" y="18" width="20" height="3" rx="1" fill="#bbb"/>',
				);
				?>
				<div class="bcg-template-picker" id="bcg-template-picker">
					<?php foreach ( $all_templates as $tpl_slug => $tpl ) :
						$is_default      = ( 'classic' === $tpl_slug );
						$tpl_settings    = $tpl['settings'] ?? array();
						$primary         = esc_attr( $tpl_settings['primary_color'] ?? '#e84040' );
						$product_layout  = $tpl_settings['product_layout'] ?? 'stacked';
						$diagram_svg     = $layout_diagrams[ $product_layout ] ?? $layout_diagrams['stacked'];
						$diagram_svg     = sprintf( $diagram_svg, $primary, $primary );
					?>
						<button
							type="button"
							class="bcg-template-card<?php echo $is_default ? ' bcg-template-card-active' : ''; ?>"
							data-slug="<?php echo esc_attr( $tpl_slug ); ?>"
							title="<?php echo esc_attr( $tpl['description'] ?? '' ); ?>"
						>
							<div class="bcg-template-card-preview">
								<div class="bcg-template-card-swatch" style="background-color:#f5f5f5;">
									<div class="bcg-template-card-swatch-inner" style="background-color:#ffffff;border-top:3px solid <?php echo $primary; ?>;">
										<svg viewBox="0 0 46 24" xmlns="http://www.w3.org/2000/svg" style="width:100%;height:auto;display:block;">
											<?php echo $diagram_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</svg>
									</div>
								</div>
							</div>
							<span class="bcg-template-card-name"><?php echo esc_html( $tpl['name'] ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>
				<input type="hidden" id="bcg-template-slug" name="template_slug" value="classic" />
			</div>
		</div>

		<!-- ============================================================
		     Generation Progress Overlay
		     ============================================================ -->
		<div id="bcg-generation-overlay" class="bcg-generation-overlay" style="display: none;">
			<div class="bcg-generation-modal">
				<h2><?php esc_html_e( 'Generating Campaign...', 'brevo-campaign-generator' ); ?></h2>
				<p class="bcg-generation-subtitle">
					<?php esc_html_e( 'Please wait while we create your campaign content with AI.', 'brevo-campaign-generator' ); ?>
				</p>
				<div class="bcg-generation-steps">
					<div class="bcg-generation-step" id="bcg-step-products" data-step="products">
						<span class="bcg-step-indicator">
							<span class="bcg-step-number">1</span>
							<span class="bcg-spinner bcg-spinner-small bcg-step-spinner" style="display: none;"></span>
							<span class="dashicons dashicons-yes-alt bcg-step-check" style="display: none;"></span>
						</span>
						<span class="bcg-step-label"><?php esc_html_e( 'Fetching products', 'brevo-campaign-generator' ); ?></span>
					</div>
					<div class="bcg-generation-step" id="bcg-step-copy" data-step="copy">
						<span class="bcg-step-indicator">
							<span class="bcg-step-number">2</span>
							<span class="bcg-spinner bcg-spinner-small bcg-step-spinner" style="display: none;"></span>
							<span class="dashicons dashicons-yes-alt bcg-step-check" style="display: none;"></span>
						</span>
						<span class="bcg-step-label"><?php esc_html_e( 'Generating copy', 'brevo-campaign-generator' ); ?></span>
					</div>
					<div class="bcg-generation-step" id="bcg-step-images" data-step="images">
						<span class="bcg-step-indicator">
							<span class="bcg-step-number">3</span>
							<span class="bcg-spinner bcg-spinner-small bcg-step-spinner" style="display: none;"></span>
							<span class="dashicons dashicons-yes-alt bcg-step-check" style="display: none;"></span>
						</span>
						<span class="bcg-step-label"><?php esc_html_e( 'Generating images', 'brevo-campaign-generator' ); ?></span>
					</div>
					<div class="bcg-generation-step" id="bcg-step-finalise" data-step="finalise">
						<span class="bcg-step-indicator">
							<span class="bcg-step-number">4</span>
							<span class="bcg-spinner bcg-spinner-small bcg-step-spinner" style="display: none;"></span>
							<span class="dashicons dashicons-yes-alt bcg-step-check" style="display: none;"></span>
						</span>
						<span class="bcg-step-label"><?php esc_html_e( 'Finalising', 'brevo-campaign-generator' ); ?></span>
					</div>
				</div>
				<div id="bcg-generation-error" class="bcg-notice bcg-notice-error" style="display: none;">
					<p id="bcg-generation-error-message"></p>
				</div>
				<div class="bcg-generation-actions" id="bcg-generation-actions" style="display: none;">
					<button type="button" class="bcg-btn-secondary" id="bcg-generation-cancel">
						<?php esc_html_e( 'Close', 'brevo-campaign-generator' ); ?>
					</button>
				</div>
			</div>
		</div>

		<!-- ============================================================
		     Submit Button
		     ============================================================ -->
		<div class="bcg-wizard-actions">
			<button
				type="submit"
				class="bcg-btn-primary bcg-btn-large"
				id="bcg-generate-campaign-btn"
			>
				<?php esc_html_e( 'Generate Campaign', 'brevo-campaign-generator' ); ?>
			</button>
		</div>

	</form>

</div>
<?php

/**
 * Recursively render a category tree as nested checkboxes.
 *
 * Outputs unordered list items with checkboxes for each product category.
 * Child categories are nested inside a sub-list beneath their parent.
 *
 * @since 1.0.0
 *
 * @param array $all_categories  Flat array of all category data arrays.
 * @param array $lookup          Lookup table mapping term_id to category data.
 * @param int   $parent_id       The parent term ID to render children for.
 * @param int   $depth           Current nesting depth (for indentation control).
 * @return void
 */
function bcg_render_category_tree( array $all_categories, array $lookup, int $parent_id, int $depth = 0 ): void {
	$children = array_filter( $all_categories, function ( $cat ) use ( $parent_id ) {
		return (int) $cat['parent'] === $parent_id;
	} );

	if ( empty( $children ) ) {
		return;
	}

	$class = 0 === $depth ? 'bcg-category-list' : 'bcg-category-children';

	echo '<ul class="' . esc_attr( $class ) . '">';

	foreach ( $children as $cat ) {
		$term_id      = (int) $cat['term_id'];
		$has_children = false;

		foreach ( $all_categories as $check ) {
			if ( (int) $check['parent'] === $term_id ) {
				$has_children = true;
				break;
			}
		}

		echo '<li class="bcg-category-item">';
		echo '<label class="bcg-category-label">';
		echo '<input type="checkbox" name="category_ids[]" value="' . esc_attr( $term_id ) . '" class="bcg-category-checkbox" />';
		echo ' ' . esc_html( $cat['name'] );
		echo ' <span class="bcg-category-count">(' . esc_html( $cat['count'] ) . ')</span>';
		echo '</label>';

		if ( $has_children ) {
			bcg_render_category_tree( $all_categories, $lookup, $term_id, $depth + 1 );
		}

		echo '</li>';
	}

	echo '</ul>';
}
