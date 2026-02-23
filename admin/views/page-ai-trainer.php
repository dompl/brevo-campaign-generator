<?php
/**
 * AI Trainer page view.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.5.29
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle save.
if ( isset( $_POST['bcg_ai_trainer_save'] ) ) {
	check_admin_referer( 'bcg_ai_trainer_save', 'bcg_ai_trainer_nonce' );

	if ( current_user_can( 'manage_woocommerce' ) ) {
		$company  = isset( $_POST['bcg_ai_trainer_company'] ) ? wp_kses_post( wp_unslash( $_POST['bcg_ai_trainer_company'] ) ) : '';
		$products = isset( $_POST['bcg_ai_trainer_products'] ) ? wp_kses_post( wp_unslash( $_POST['bcg_ai_trainer_products'] ) ) : '';

		update_option( 'bcg_ai_trainer_company', $company );
		update_option( 'bcg_ai_trainer_products', $products );

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'AI Trainer context saved successfully.', 'brevo-campaign-generator' ) . '</p></div>';
	}
}

$company  = get_option( 'bcg_ai_trainer_company', '' );
$products = get_option( 'bcg_ai_trainer_products', '' );
?>
<?php bcg_plugin_header(); ?>

<div class="bcg-wrap">

	<div class="bcg-page-header">
		<h1><?php esc_html_e( 'AI Trainer', 'brevo-campaign-generator' ); ?></h1>
		<p class="bcg-page-subtitle">
			<?php esc_html_e( 'Teach the AI about your business. This context is automatically injected into every AI generation call to make copy more relevant and on-brand.', 'brevo-campaign-generator' ); ?>
		</p>
	</div>

	<form method="post" action="">
		<?php wp_nonce_field( 'bcg_ai_trainer_save', 'bcg_ai_trainer_nonce' ); ?>
		<input type="hidden" name="bcg_ai_trainer_save" value="1" />

		<div class="bcg-card">
			<div class="bcg-card-header">
				<h2>
					<span class="material-icons-outlined" style="vertical-align:middle;margin-right:6px;font-size:20px;">school</span>
					<?php esc_html_e( 'About Your Store', 'brevo-campaign-generator' ); ?>
				</h2>
			</div>
			<div class="bcg-card-body">
				<p class="description bcg-mb-12">
					<?php esc_html_e( 'Describe your store: what you sell, your brand voice, target audience, unique selling points, and anything else the AI should know about your business.', 'brevo-campaign-generator' ); ?>
				</p>
				<textarea
					name="bcg_ai_trainer_company"
					id="bcg_ai_trainer_company"
					rows="8"
					class="large-text bcg-full-width"
					placeholder="<?php esc_attr_e( 'e.g. We are a UK-based diamond tools supplier specialising in cutting, grinding and drilling equipment for trade professionals. Our brand tone is expert, trustworthy and no-nonsense. We focus on quality and value for money.', 'brevo-campaign-generator' ); ?>"
				><?php echo esc_textarea( $company ); ?></textarea>
			</div>
		</div>

		<div class="bcg-card" style="margin-top:20px;">
			<div class="bcg-card-header">
				<h2>
					<span class="material-icons-outlined" style="vertical-align:middle;margin-right:6px;font-size:20px;">inventory_2</span>
					<?php esc_html_e( 'About Your Products', 'brevo-campaign-generator' ); ?>
				</h2>
			</div>
			<div class="bcg-card-body">
				<p class="description bcg-mb-12">
					<?php esc_html_e( 'Describe your product range, any current promotions, seasonal themes, or product-specific context the AI should reference when writing copy.', 'brevo-campaign-generator' ); ?>
				</p>
				<textarea
					name="bcg_ai_trainer_products"
					id="bcg_ai_trainer_products"
					rows="8"
					class="large-text bcg-full-width"
					placeholder="<?php esc_attr_e( 'e.g. Our best-selling product lines include diamond core drill bits, angle grinder discs, and wall chasing blades. We are currently running a clearance on our summer stock. All products come with a 12-month warranty.', 'brevo-campaign-generator' ); ?>"
				><?php echo esc_textarea( $products ); ?></textarea>
			</div>
		</div>

		<div style="margin-top:20px;">
			<button type="submit" class="bcg-btn bcg-btn-primary">
				<span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;margin-right:4px;">save</span>
				<?php esc_html_e( 'Save AI Trainer Context', 'brevo-campaign-generator' ); ?>
			</button>
		</div>

	</form>

</div>
