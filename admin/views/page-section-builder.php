<?php
/**
 * Template Builder admin page.
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

				<!-- Tone — custom dropdown -->
				<div class="bcg-select-wrapper bcg-sb-toolbar-select" id="bcg-sb-context-tone" data-value="Professional">
					<button type="button" class="bcg-select-trigger" aria-haspopup="listbox" aria-expanded="false">
						<span class="bcg-select-value"><?php esc_html_e( 'Professional', 'brevo-campaign-generator' ); ?></span>
						<span class="material-icons-outlined" style="font-size:16px;flex-shrink:0;pointer-events:none;color:var(--bcg-text-muted);">expand_more</span>
					</button>
					<div class="bcg-select-menu bcg-dropdown-closed" role="listbox">
						<div class="bcg-select-option is-selected" data-value="Professional"><?php esc_html_e( 'Professional', 'brevo-campaign-generator' ); ?></div>
						<div class="bcg-select-option" data-value="Friendly"><?php esc_html_e( 'Friendly', 'brevo-campaign-generator' ); ?></div>
						<div class="bcg-select-option" data-value="Urgent"><?php esc_html_e( 'Urgent', 'brevo-campaign-generator' ); ?></div>
						<div class="bcg-select-option" data-value="Playful"><?php esc_html_e( 'Playful', 'brevo-campaign-generator' ); ?></div>
						<div class="bcg-select-option" data-value="Luxury"><?php esc_html_e( 'Luxury', 'brevo-campaign-generator' ); ?></div>
					</div>
				</div>

				<!-- Language — custom dropdown -->
				<div class="bcg-select-wrapper bcg-sb-toolbar-select" id="bcg-sb-context-language" data-value="English">
					<button type="button" class="bcg-select-trigger" aria-haspopup="listbox" aria-expanded="false">
						<span class="bcg-select-value"><?php esc_html_e( 'English', 'brevo-campaign-generator' ); ?></span>
						<span class="material-icons-outlined" style="font-size:16px;flex-shrink:0;pointer-events:none;color:var(--bcg-text-muted);">expand_more</span>
					</button>
					<div class="bcg-select-menu bcg-dropdown-closed" role="listbox">
						<div class="bcg-select-option is-selected" data-value="English"><?php esc_html_e( 'English', 'brevo-campaign-generator' ); ?></div>
						<div class="bcg-select-option" data-value="Polish"><?php esc_html_e( 'Polish', 'brevo-campaign-generator' ); ?></div>
						<div class="bcg-select-option" data-value="French"><?php esc_html_e( 'French', 'brevo-campaign-generator' ); ?></div>
						<div class="bcg-select-option" data-value="German"><?php esc_html_e( 'German', 'brevo-campaign-generator' ); ?></div>
						<div class="bcg-select-option" data-value="Spanish"><?php esc_html_e( 'Spanish', 'brevo-campaign-generator' ); ?></div>
						<div class="bcg-select-option" data-value="Italian"><?php esc_html_e( 'Italian', 'brevo-campaign-generator' ); ?></div>
					</div>
				</div>

				<button type="button" id="bcg-sb-prompt-btn" class="bcg-btn-secondary bcg-btn-sm">
					<span class="material-icons-outlined">edit_note</span>
					<?php esc_html_e( 'AI Prompt', 'brevo-campaign-generator' ); ?>
				</button>

				<button type="button" id="bcg-sb-generate-btn" class="bcg-btn-ai bcg-btn-sm">
					<span class="material-icons-outlined">auto_awesome</span>
					<?php esc_html_e( 'Generate with AI', 'brevo-campaign-generator' ); ?>
				</button>
			</div>
		</div>

		<div class="bcg-sb-toolbar-right">
			<div class="bcg-sb-load-wrap">
				<button type="button" id="bcg-sb-load-btn" class="bcg-btn-secondary bcg-btn-sm">
					<span class="material-icons-outlined">folder_open</span>
					<?php esc_html_e( 'Load Template', 'brevo-campaign-generator' ); ?>
				</button>
			</div>

			<button type="button" id="bcg-sb-preview-btn" class="bcg-btn-secondary bcg-btn-sm">
				<span class="material-icons-outlined">preview</span>
				<?php esc_html_e( 'Preview Email', 'brevo-campaign-generator' ); ?>
			</button>

			<button type="button" id="bcg-sb-save-btn" class="bcg-btn-primary bcg-btn-sm">
				<span class="material-icons-outlined">save</span>
				<?php esc_html_e( 'Save Template', 'brevo-campaign-generator' ); ?>
			</button>
			<span id="bcg-sb-autosave-indicator" style="display:none;font-size:11px;color:var(--bcg-text-muted);margin-left:8px;"></span>

			<button type="button" id="bcg-sb-request-btn" class="bcg-btn-secondary bcg-btn-sm">
				<span class="material-icons-outlined">lightbulb</span>
				<?php esc_html_e( 'Request a Section', 'brevo-campaign-generator' ); ?>
			</button>

			<button type="button" id="bcg-sb-defaults-btn" class="bcg-btn-secondary bcg-btn-sm">
				<span class="material-icons-outlined">tune</span>
				<?php esc_html_e( 'Default Settings', 'brevo-campaign-generator' ); ?>
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

	<!-- ── Default Settings Modal ───────────────────────────────────────── -->
	<div id="bcg-sb-defaults-modal" class="bcg-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="bcg-sb-defaults-title">
		<div class="bcg-modal-overlay" id="bcg-sb-defaults-overlay"></div>
		<div class="bcg-modal-content bcg-sb-defaults-content">

			<div class="bcg-modal-header">
				<span class="material-icons-outlined" style="color:var(--bcg-accent);font-size:20px;flex-shrink:0;">tune</span>
				<h3 id="bcg-sb-defaults-title"><?php esc_html_e( 'Default Settings', 'brevo-campaign-generator' ); ?></h3>
				<button type="button" class="bcg-modal-close" id="bcg-sb-defaults-close" aria-label="<?php esc_attr_e( 'Close', 'brevo-campaign-generator' ); ?>">
					<span class="material-icons-outlined">close</span>
				</button>
			</div>

			<div class="bcg-sb-defaults-modal-body">

				<div class="bcg-form-row">
					<label class="bcg-form-label">
						<?php esc_html_e( 'Default Font', 'brevo-campaign-generator' ); ?>
					</label>
					<p class="bcg-form-hint"><?php esc_html_e( 'Applied to all text sections in the email.', 'brevo-campaign-generator' ); ?></p>
					<div class="bcg-select-wrapper bcg-sb-custom-select" id="bcg-sb-font-wrapper">
						<button type="button" class="bcg-select-trigger" aria-haspopup="listbox" aria-expanded="false">
							<span class="bcg-select-value" id="bcg-sb-font-label">Georgia</span>
							<span class="material-icons-outlined" style="font-size:18px;flex-shrink:0;pointer-events:none;color:var(--bcg-text-muted);">expand_more</span>
						</button>
						<div class="bcg-select-menu bcg-dropdown-closed" role="listbox">
							<div class="bcg-select-option is-selected" data-value="Georgia, serif">Georgia</div>
							<div class="bcg-select-option" data-value="'Times New Roman', Times, serif">Times New Roman</div>
							<div class="bcg-select-option" data-value="'Playfair Display', Georgia, serif">Playfair Display</div>
							<div class="bcg-select-option" data-value="Arial, Helvetica, sans-serif">Arial</div>
							<div class="bcg-select-option" data-value="Verdana, Geneva, Tahoma, sans-serif">Verdana</div>
							<div class="bcg-select-option" data-value="'Trebuchet MS', Tahoma, Geneva, sans-serif">Trebuchet MS</div>
							<div class="bcg-select-option" data-value="'Roboto', Arial, sans-serif">Roboto</div>
							<div class="bcg-select-option" data-value="'Open Sans', Arial, sans-serif">Open Sans</div>
							<div class="bcg-select-option" data-value="'Lato', Arial, sans-serif">Lato</div>
							<div class="bcg-select-option" data-value="'Montserrat', Arial, sans-serif">Montserrat</div>
							<div class="bcg-select-option" data-value="'Source Sans Pro', Arial, sans-serif">Source Sans Pro</div>
							<div class="bcg-select-option" data-value="Courier New, Courier, monospace">Courier New</div>
						</div>
					</div>
				</div>

				<div class="bcg-form-row">
					<label class="bcg-form-label" for="bcg-sb-primary-color">
						<?php esc_html_e( 'Primary Colour', 'brevo-campaign-generator' ); ?>
					</label>
					<p class="bcg-form-hint"><?php esc_html_e( 'Replaces the accent/button colour across all sections when you click Apply.', 'brevo-campaign-generator' ); ?></p>
					<div style="display:flex;align-items:center;gap:10px;margin-top:6px;">
						<input type="color" id="bcg-sb-primary-color" class="bcg-sb-primary-color-input" value="#e63529" />
						<button type="button" id="bcg-sb-apply-primary-color" class="bcg-btn bcg-btn-secondary bcg-btn-sm">
							<span class="material-icons-outlined">format_paint</span>
							<?php esc_html_e( 'Apply to All Sections', 'brevo-campaign-generator' ); ?>
						</button>
					</div>
				</div>

			</div><!-- /.bcg-sb-defaults-modal-body -->

			<div class="bcg-sb-defaults-modal-footer">
				<button type="button" id="bcg-sb-defaults-done" class="bcg-btn-primary">
					<?php esc_html_e( 'Done', 'brevo-campaign-generator' ); ?>
				</button>
			</div>

		</div><!-- /.bcg-modal-content -->
	</div>

	<!-- ── Request a Section Modal ────────────────────────── -->
	<div id="bcg-sb-request-modal" class="bcg-modal" style="display:none;">
		<div class="bcg-modal-overlay" id="bcg-sb-request-overlay"></div>
		<div class="bcg-modal-content bcg-sb-request-content">

			<div class="bcg-modal-header">
				<span class="material-icons-outlined" style="color:var(--bcg-accent);font-size:20px;flex-shrink:0;">lightbulb</span>
				<h3><?php esc_html_e( 'Request a New Section Type', 'brevo-campaign-generator' ); ?></h3>
				<button type="button" class="bcg-modal-close" id="bcg-sb-request-close">
					<span class="material-icons-outlined">close</span>
				</button>
			</div>

			<div class="bcg-sb-request-body">
				<p class="bcg-sb-request-intro">
					<?php esc_html_e( "Tell us what kind of section you need and we'll build it for you. We'll get back to you once it's ready.", 'brevo-campaign-generator' ); ?>
				</p>

				<div class="bcg-form-row">
					<label><?php esc_html_e( 'Section Type', 'brevo-campaign-generator' ); ?> <span class="bcg-required">*</span></label>
					<div class="bcg-select-wrapper bcg-req-custom-select" id="bcg-req-type-wrapper">
						<button type="button" class="bcg-select-trigger" id="bcg-req-type-trigger" aria-haspopup="listbox" aria-expanded="false">
							<span class="bcg-select-value bcg-req-placeholder"><?php esc_html_e( '— Choose a type —', 'brevo-campaign-generator' ); ?></span>
							<span class="material-icons-outlined" style="font-size:18px;flex-shrink:0;pointer-events:none;color:var(--bcg-text-muted);">expand_more</span>
						</button>
						<div class="bcg-select-menu bcg-dropdown-closed" role="listbox">
							<div class="bcg-select-option" data-value="Header" role="option"><?php esc_html_e( 'Header', 'brevo-campaign-generator' ); ?></div>
							<div class="bcg-select-option" data-value="Hero / Banner" role="option"><?php esc_html_e( 'Hero / Banner', 'brevo-campaign-generator' ); ?></div>
							<div class="bcg-select-option" data-value="Text Block" role="option"><?php esc_html_e( 'Text Block', 'brevo-campaign-generator' ); ?></div>
							<div class="bcg-select-option" data-value="Image" role="option"><?php esc_html_e( 'Image', 'brevo-campaign-generator' ); ?></div>
							<div class="bcg-select-option" data-value="Products" role="option"><?php esc_html_e( 'Products', 'brevo-campaign-generator' ); ?></div>
							<div class="bcg-select-option" data-value="Banner" role="option"><?php esc_html_e( 'Banner', 'brevo-campaign-generator' ); ?></div>
							<div class="bcg-select-option" data-value="Call to Action" role="option"><?php esc_html_e( 'Call to Action', 'brevo-campaign-generator' ); ?></div>
							<div class="bcg-select-option" data-value="Coupon" role="option"><?php esc_html_e( 'Coupon', 'brevo-campaign-generator' ); ?></div>
							<div class="bcg-select-option" data-value="Footer" role="option"><?php esc_html_e( 'Footer', 'brevo-campaign-generator' ); ?></div>
							<div class="bcg-select-option" data-value="Other" role="option"><?php esc_html_e( 'Other / Custom', 'brevo-campaign-generator' ); ?></div>
						</div>
					</div>
					<input type="hidden" id="bcg-req-type" value="" />
				</div>

				<div class="bcg-form-row">
					<label for="bcg-req-description">
						<?php esc_html_e( 'Detailed Description', 'brevo-campaign-generator' ); ?>
						<span class="bcg-required">*</span>
					</label>
					<textarea id="bcg-req-description" class="bcg-textarea" rows="5"
						placeholder="<?php esc_attr_e( 'Describe the layout, content, and any specific requirements…', 'brevo-campaign-generator' ); ?>"></textarea>
				</div>

				<div class="bcg-form-row bcg-form-row-2col">
					<div>
						<label for="bcg-req-name"><?php esc_html_e( 'Your Name', 'brevo-campaign-generator' ); ?></label>
						<input type="text" id="bcg-req-name" class="bcg-input" />
					</div>
					<div>
						<label for="bcg-req-email"><?php esc_html_e( 'Your Email', 'brevo-campaign-generator' ); ?></label>
						<input type="email" id="bcg-req-email" class="bcg-input" />
					</div>
				</div>

				<div id="bcg-req-status" class="bcg-sb-request-status" style="display:none;"></div>
			</div><!-- /.bcg-sb-request-body -->

			<div class="bcg-sb-request-footer">
				<button type="button" id="bcg-sb-request-cancel" class="bcg-btn-secondary">
					<?php esc_html_e( 'Cancel', 'brevo-campaign-generator' ); ?>
				</button>
				<button type="button" id="bcg-sb-request-submit" class="bcg-btn-primary">
					<span class="material-icons-outlined">send</span>
					<?php esc_html_e( 'Send Request', 'brevo-campaign-generator' ); ?>
				</button>
			</div>

		</div><!-- /.bcg-modal-content -->
	</div>

	<!-- ── AI Prompt Modal ───────────────────────────────────────────── -->
	<div id="bcg-sb-prompt-modal" class="bcg-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="bcg-sb-prompt-title">
		<div class="bcg-modal-overlay" id="bcg-sb-prompt-overlay"></div>
		<div class="bcg-modal-content bcg-sb-prompt-content">

			<div class="bcg-modal-header">
				<span class="material-icons-outlined" style="color:var(--bcg-accent);font-size:20px;flex-shrink:0;">edit_note</span>
				<h3 id="bcg-sb-prompt-title"><?php esc_html_e( 'AI Prompt', 'brevo-campaign-generator' ); ?></h3>
				<button type="button" class="bcg-modal-close" id="bcg-sb-prompt-close" aria-label="<?php esc_attr_e( 'Close', 'brevo-campaign-generator' ); ?>">
					<span class="material-icons-outlined">close</span>
				</button>
			</div>

			<div class="bcg-sb-prompt-body">

				<div class="bcg-sb-prompt-hint-box">
					<p class="bcg-sb-prompt-hint-title"><?php esc_html_e( 'What to include in your prompt:', 'brevo-campaign-generator' ); ?></p>
					<ul class="bcg-sb-prompt-hints">
						<li><?php esc_html_e( 'Purpose or occasion — e.g. Black Friday, product launch, seasonal sale', 'brevo-campaign-generator' ); ?></li>
						<li><?php esc_html_e( 'Visual feel — e.g. dark and bold, clean and minimal, bright and energetic', 'brevo-campaign-generator' ); ?></li>
						<li><?php esc_html_e( 'Key message or offer — e.g. 25% off drill bits, free shipping this week', 'brevo-campaign-generator' ); ?></li>
						<li><?php esc_html_e( 'Layout — e.g. hero banner, 3 products, a coupon, and a footer', 'brevo-campaign-generator' ); ?></li>
						<li><?php esc_html_e( 'Tone — e.g. authoritative and expert, friendly and casual', 'brevo-campaign-generator' ); ?></li>
					</ul>
				</div>

				<div class="bcg-sb-textarea-wrap">
					<textarea
						id="bcg-sb-ai-prompt"
						class="bcg-textarea bcg-sb-prompt-textarea"
						rows="7"
						placeholder="<?php esc_attr_e( 'e.g. Create a bold summer sale email for our diamond tools range. Dark background with bright orange accents. Feature 3 best-selling products with short punchy descriptions. Include a 20% discount coupon. Tone: confident and direct — speaking to trade professionals who value quality.', 'brevo-campaign-generator' ); ?>"
					></textarea>

					<div class="bcg-sb-textarea-toolbar">
						<div class="bcg-sb-saved-prompts-wrap" id="bcg-sb-saved-prompts-wrap" style="display:none;">
							<div class="bcg-custom-select" id="bcg-saved-prompts-wrapper">
								<button type="button" class="bcg-select-trigger" id="bcg-saved-prompts-trigger" aria-haspopup="listbox" aria-expanded="false">
									<span class="bcg-select-value"><?php esc_html_e( '— Load a saved prompt —', 'brevo-campaign-generator' ); ?></span>
									<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="6 9 12 15 18 9"></polyline></svg>
								</button>
								<ul class="bcg-select-menu bcg-dropdown-closed" role="listbox" id="bcg-saved-prompts-menu">
									<!-- Options populated by JS via refreshSavedPromptsDropdown() -->
								</ul>
							</div>
						</div>
						<button type="button" id="bcg-sb-voice-btn" class="bcg-sb-voice-btn" title="<?php esc_attr_e( 'Speak your prompt', 'brevo-campaign-generator' ); ?>">
							<span class="material-icons-outlined">mic</span>
						</button>
					</div>
				</div>

				<div class="bcg-sb-prompt-info-note">
					<span class="material-icons-outlined">info</span>
					<span><?php esc_html_e( 'Combined with your AI Trainer store and product context for fully on-brand results.', 'brevo-campaign-generator' ); ?></span>
				</div>

				<div id="bcg-sb-prompt-status" style="display:none;margin-top:12px;"></div>

			</div><!-- /.bcg-sb-prompt-body -->

			<div class="bcg-modal-footer">
				<button type="button" id="bcg-sb-prompt-cancel" class="bcg-btn-secondary bcg-prompt-footer-cancel">
					<?php esc_html_e( 'Cancel', 'brevo-campaign-generator' ); ?>
				</button>
				<button type="button" id="bcg-sb-prompt-save" class="bcg-btn-secondary">
					<span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">bookmark_add</span>
					<?php esc_html_e( 'Save Prompt', 'brevo-campaign-generator' ); ?>
				</button>
				<button type="button" id="bcg-sb-prompt-generate" class="bcg-btn-ai">
					<span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">auto_awesome</span>
					<?php esc_html_e( 'Save & Generate with AI', 'brevo-campaign-generator' ); ?>
				</button>
			</div>

		</div><!-- /.bcg-modal-content -->
	</div>

</div><!-- /.bcg-wrap.bcg-section-builder-page -->