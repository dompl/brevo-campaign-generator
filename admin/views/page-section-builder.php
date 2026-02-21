<?php
/**
 * Section Builder admin page.
 *
 * Three-panel layout: Palette | Canvas | Settings Panel
 * Toolbar: Template name, Load, Save, Generate All, Preview
 *
 * @package Brevo_Campaign_Generator
 * @since   1.5.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php require BCG_PLUGIN_DIR . 'admin/views/partials/plugin-header.php'; ?>
<div class="bcg-wrap bcg-section-builder-page">

	<!-- ── Toolbar ───────────────────────────────────────────────────── -->
	<div class="bcg-sb-toolbar">
		<div class="bcg-sb-toolbar-left">
			<div class="bcg-sb-name-wrap">
				<input type="text"
					id="bcg-sb-template-name"
					class="bcg-sb-name-input"
					placeholder="<?php esc_attr_e( 'Template Name…', 'brevo-campaign-generator' ); ?>"
					maxlength="255"
				/>
			</div>

			<div class="bcg-sb-toolbar-context">
				<input type="text"
					id="bcg-sb-context-theme"
					class="bcg-input-sm"
					placeholder="<?php esc_attr_e( 'Campaign theme (e.g. Summer Sale)', 'brevo-campaign-generator' ); ?>"
				/>
				<select id="bcg-sb-context-tone" class="bcg-select-sm">
					<option value="Professional"><?php esc_html_e( 'Professional', 'brevo-campaign-generator' ); ?></option>
					<option value="Friendly"><?php esc_html_e( 'Friendly', 'brevo-campaign-generator' ); ?></option>
					<option value="Urgent"><?php esc_html_e( 'Urgent', 'brevo-campaign-generator' ); ?></option>
					<option value="Playful"><?php esc_html_e( 'Playful', 'brevo-campaign-generator' ); ?></option>
					<option value="Luxury"><?php esc_html_e( 'Luxury', 'brevo-campaign-generator' ); ?></option>
				</select>
				<select id="bcg-sb-context-language" class="bcg-select-sm">
					<option value="English"><?php esc_html_e( 'English', 'brevo-campaign-generator' ); ?></option>
					<option value="Polish"><?php esc_html_e( 'Polish', 'brevo-campaign-generator' ); ?></option>
					<option value="French"><?php esc_html_e( 'French', 'brevo-campaign-generator' ); ?></option>
					<option value="German"><?php esc_html_e( 'German', 'brevo-campaign-generator' ); ?></option>
					<option value="Spanish"><?php esc_html_e( 'Spanish', 'brevo-campaign-generator' ); ?></option>
					<option value="Italian"><?php esc_html_e( 'Italian', 'brevo-campaign-generator' ); ?></option>
				</select>
			</div>
		</div>

		<div class="bcg-sb-toolbar-right">
			<div class="bcg-sb-load-wrap">
				<button type="button" id="bcg-sb-load-btn" class="bcg-btn-secondary bcg-btn-sm">
					<span class="material-icons-outlined">folder_open</span>
					<?php esc_html_e( 'Load Template', 'brevo-campaign-generator' ); ?>
				</button>
			</div>

			<button type="button" id="bcg-sb-generate-btn" class="bcg-btn-ai bcg-btn-sm">
				<span class="material-icons-outlined">auto_awesome</span>
				<?php esc_html_e( 'Generate All with AI', 'brevo-campaign-generator' ); ?>
			</button>

			<button type="button" id="bcg-sb-preview-btn" class="bcg-btn-secondary bcg-btn-sm">
				<span class="material-icons-outlined">preview</span>
				<?php esc_html_e( 'Preview Email', 'brevo-campaign-generator' ); ?>
			</button>

			<button type="button" id="bcg-sb-save-btn" class="bcg-btn-primary bcg-btn-sm">
				<span class="material-icons-outlined">save</span>
				<?php esc_html_e( 'Save Template', 'brevo-campaign-generator' ); ?>
			</button>
		</div>
	</div><!-- /.bcg-sb-toolbar -->

	<!-- ── Status bar ────────────────────────────────────────────────── -->
	<div id="bcg-sb-status" class="bcg-sb-status" style="display:none;"></div>

	<!-- ── Three-Panel Layout ─────────────────────────────────────────── -->
	<div class="bcg-sb-layout">

		<!-- Left Panel: Palette -->
		<div class="bcg-sb-palette">
			<div class="bcg-sb-panel-header">
				<span class="material-icons-outlined">widgets</span>
				<?php esc_html_e( 'Sections', 'brevo-campaign-generator' ); ?>
			</div>
			<div class="bcg-sb-palette-list" id="bcg-sb-palette">
				<!-- Populated by JS from bcg_section_builder.section_types -->
			</div>
		</div><!-- /.bcg-sb-palette -->

		<!-- Centre Panel: Canvas -->
		<div class="bcg-sb-canvas-wrap">
			<div class="bcg-sb-panel-header">
				<span class="material-icons-outlined">dashboard</span>
				<?php esc_html_e( 'Canvas', 'brevo-campaign-generator' ); ?>
				<span class="bcg-sb-dirty-dot" id="bcg-sb-dirty-dot" title="<?php esc_attr_e( 'Unsaved changes', 'brevo-campaign-generator' ); ?>"></span>
			</div>

			<div class="bcg-sb-canvas" id="bcg-sb-canvas">
				<div class="bcg-sb-canvas-empty" id="bcg-sb-canvas-empty">
					<span class="material-icons-outlined">add_circle_outline</span>
					<p><?php esc_html_e( 'Click a section in the palette to add it here, then drag to reorder.', 'brevo-campaign-generator' ); ?></p>
				</div>
			</div>

			<div class="bcg-sb-canvas-add">
				<button type="button" class="bcg-sb-add-section-btn" id="bcg-sb-add-section-btn">
					<span class="material-icons-outlined">add</span>
					<?php esc_html_e( 'Add Section', 'brevo-campaign-generator' ); ?>
				</button>
			</div>
		</div><!-- /.bcg-sb-canvas-wrap -->

		<!-- Right Panel: Settings + Preview -->
		<div class="bcg-sb-settings-panel" id="bcg-sb-settings-panel">
			<div class="bcg-sb-panel-header">
				<span class="material-icons-outlined">tune</span>
				<?php esc_html_e( 'Settings', 'brevo-campaign-generator' ); ?>
			</div>

			<div class="bcg-sb-settings-body" id="bcg-sb-settings-body">
				<div class="bcg-sb-settings-placeholder">
					<span class="material-icons-outlined">touch_app</span>
					<p><?php esc_html_e( 'Click a section on the canvas to edit its settings.', 'brevo-campaign-generator' ); ?></p>
				</div>
			</div>
		</div><!-- /.bcg-sb-settings-panel -->

	</div><!-- /.bcg-sb-layout -->

	<!-- ── Preview Modal ─────────────────────────────────────────────── -->
	<div id="bcg-sb-preview-modal" class="bcg-modal" style="display:none;">
		<div class="bcg-modal-overlay" id="bcg-sb-preview-overlay"></div>
		<div class="bcg-modal-content bcg-sb-preview-content">
			<div class="bcg-modal-header">
				<h3><?php esc_html_e( 'Email Preview', 'brevo-campaign-generator' ); ?></h3>
				<div class="bcg-sb-preview-controls">
					<button type="button" class="bcg-preview-toggle active" data-mode="desktop">
						<span class="material-icons-outlined">desktop_windows</span>
					</button>
					<button type="button" class="bcg-preview-toggle" data-mode="mobile">
						<span class="material-icons-outlined">smartphone</span>
					</button>
				</div>
				<button type="button" class="bcg-modal-close" id="bcg-sb-preview-close">
					<span class="material-icons-outlined">close</span>
				</button>
			</div>
			<div class="bcg-sb-preview-frame-wrap" id="bcg-sb-preview-frame-wrap">
				<iframe id="bcg-sb-preview-iframe" class="bcg-sb-preview-iframe"></iframe>
			</div>
		</div>
	</div>

	<!-- ── Load Template Modal ───────────────────────────────────────── -->
	<div id="bcg-sb-load-modal" class="bcg-modal" style="display:none;">
		<div class="bcg-modal-overlay" id="bcg-sb-load-overlay"></div>
		<div class="bcg-modal-content bcg-sb-load-content">
			<div class="bcg-modal-header">
				<h3><?php esc_html_e( 'Load Saved Template', 'brevo-campaign-generator' ); ?></h3>
				<button type="button" class="bcg-modal-close" id="bcg-sb-load-close">
					<span class="material-icons-outlined">close</span>
				</button>
			</div>
			<div class="bcg-sb-load-body" id="bcg-sb-load-body">
				<p class="bcg-sb-loading-msg">
					<span class="material-icons-outlined bcg-spin">refresh</span>
					<?php esc_html_e( 'Loading templates…', 'brevo-campaign-generator' ); ?>
				</p>
			</div>
		</div>
	</div>

</div><!-- /.bcg-wrap.bcg-section-builder-page -->
