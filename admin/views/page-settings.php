<?php
/**
 * Settings page view.
 *
 * Renders the tabbed settings interface with form fields for each tab.
 * This file is loaded by BCG_Admin::render_settings_page().
 *
 * @package Brevo_Campaign_Generator
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings    = bcg()->get_settings();
$tabs        = $settings->get_tabs();
$current_tab = $settings->get_current_tab();

// Map tab slugs to settings page groups (used by settings_fields() and do_settings_sections()).
$tab_option_groups = array(
	'api-keys'  => 'bcg_settings_api_keys',
	'ai-models' => 'bcg_settings_ai_models',
	'brevo'     => 'bcg_settings_brevo',
	'stripe'    => 'bcg_settings_stripe',
	'defaults'  => 'bcg_settings_defaults',
);

$option_group = $tab_option_groups[ $current_tab ] ?? 'bcg_settings_api_keys';
?>
<?php require BCG_PLUGIN_DIR . 'admin/views/partials/plugin-header.php'; ?>
<div class="wrap bcg-wrap bcg-settings-wrap">

	<h1><?php esc_html_e( 'Brevo Campaign Generator Settings', 'brevo-campaign-generator' ); ?></h1>

	<?php settings_errors(); ?>

	<!-- Tab Navigation -->
	<nav class="nav-tab-wrapper bcg-nav-tab-wrapper">
		<?php foreach ( $tabs as $tab_slug => $tab_label ) : ?>
			<a
				href="<?php echo esc_url( admin_url( 'admin.php?page=bcg-settings&tab=' . $tab_slug ) ); ?>"
				class="nav-tab <?php echo $current_tab === $tab_slug ? 'nav-tab-active' : ''; ?>"
			>
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<!-- Settings Form -->
	<form method="post" action="options.php" class="bcg-settings-form">
		<?php
		settings_fields( $option_group );
		?>

		<div class="bcg-tab-content bcg-tab-<?php echo esc_attr( $current_tab ); ?>">
			<?php
			switch ( $current_tab ) {
				case 'api-keys':
					do_settings_sections( 'bcg_settings_api_keys' );
					break;

				case 'ai-models':
					do_settings_sections( 'bcg_settings_ai_models' );
					// Render the static pricing reference table.
					bcg_render_pricing_table( $settings );
					break;

				case 'brevo':
					do_settings_sections( 'bcg_settings_brevo' );
					break;

				case 'stripe':
					do_settings_sections( 'bcg_settings_stripe' );
					break;

				case 'defaults':
					do_settings_sections( 'bcg_settings_defaults' );
					break;

			}
			?>
		</div>

		<?php submit_button( __( 'Save Settings', 'brevo-campaign-generator' ) ); ?>
	</form>

</div>
<?php

/**
 * Render the static pricing reference table.
 *
 * Displayed below the AI Models tab to give administrators an approximate
 * cost overview for each AI provider and model.
 *
 * @since  1.0.0
 * @param  BCG_Settings $settings The settings instance.
 * @return void
 */
function bcg_render_pricing_table( BCG_Settings $settings ): void {
	$pricing = $settings->get_pricing_reference();
	?>
	<div class="bcg-pricing-reference">
		<h3><?php esc_html_e( 'Approximate Cost Reference', 'brevo-campaign-generator' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'These are approximate costs from each AI provider. Check the provider\'s pricing page for current rates.', 'brevo-campaign-generator' ); ?>
		</p>
		<table class="widefat bcg-pricing-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Service', 'brevo-campaign-generator' ); ?></th>
					<th><?php esc_html_e( 'Model', 'brevo-campaign-generator' ); ?></th>
					<th><?php esc_html_e( 'Task', 'brevo-campaign-generator' ); ?></th>
					<th><?php esc_html_e( 'Estimated Cost', 'brevo-campaign-generator' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $pricing as $index => $row ) : ?>
					<tr class="<?php echo 0 === $index % 2 ? 'alternate' : ''; ?>">
						<td><?php echo esc_html( $row['service'] ); ?></td>
						<td><code><?php echo esc_html( $row['model'] ); ?></code></td>
						<td><?php echo esc_html( $row['task'] ); ?></td>
						<td><strong><?php echo esc_html( $row['cost'] ); ?></strong></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}
