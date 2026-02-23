<?php
/**
 * Help & Documentation admin page.
 *
 * Two-column layout: sticky sidebar navigation + scrollable content sections.
 * Covers all user-facing functionality of the Brevo Campaign Generator plugin.
 *
 * @package Brevo_Campaign_Generator
 * @since   1.5.20
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php require BCG_PLUGIN_DIR . 'admin/views/partials/plugin-header.php'; ?>
<div class="bcg-wrap bcg-help-page">

	<!-- ── Page Header ────────────────────────────────────────────────── -->
	<div class="bcg-help-page-header">
		<div class="bcg-help-page-header-inner">
			<div class="bcg-help-page-title">
				<span class="material-icons-outlined">menu_book</span>
				<div>
					<h1><?php esc_html_e( 'Help & Documentation', 'brevo-campaign-generator' ); ?></h1>
					<p><?php esc_html_e( 'Everything you need to create great email campaigns.', 'brevo-campaign-generator' ); ?></p>
				</div>
			</div>
			<div class="bcg-help-search-wrap">
				<span class="material-icons-outlined">search</span>
				<input type="text" id="bcg-help-search" placeholder="<?php esc_attr_e( 'Search documentation…', 'brevo-campaign-generator' ); ?>" />
			</div>
		</div>
	</div>

	<!-- ── Two-Column Layout ──────────────────────────────────────────── -->
	<div class="bcg-help-layout">

		<!-- ── Sidebar Navigation ─────────────────────────────────────── -->
		<nav class="bcg-help-sidebar" id="bcg-help-sidebar">
			<div class="bcg-help-nav">
				<a href="#getting-started" class="bcg-help-nav-item is-active" data-section="getting-started">
					<span class="material-icons-outlined">rocket_launch</span>
					<?php esc_html_e( 'Getting Started', 'brevo-campaign-generator' ); ?>
				</a>
				<a href="#dashboard" class="bcg-help-nav-item" data-section="dashboard">
					<span class="material-icons-outlined">dashboard</span>
					<?php esc_html_e( 'Dashboard', 'brevo-campaign-generator' ); ?>
				</a>
				<a href="#creating-campaigns" class="bcg-help-nav-item" data-section="creating-campaigns">
					<span class="material-icons-outlined">add_circle_outline</span>
					<?php esc_html_e( 'Creating Campaigns', 'brevo-campaign-generator' ); ?>
				</a>
				<div class="bcg-help-nav-sub">
					<a href="#step1-configure" class="bcg-help-nav-item bcg-help-nav-child" data-section="step1-configure">
						<?php esc_html_e( 'Step 1: Configure', 'brevo-campaign-generator' ); ?>
					</a>
					<a href="#step2-edit" class="bcg-help-nav-item bcg-help-nav-child" data-section="step2-edit">
						<?php esc_html_e( 'Step 2: Edit & Send', 'brevo-campaign-generator' ); ?>
					</a>
				</div>
				<a href="#template-builder" class="bcg-help-nav-item" data-section="template-builder">
					<span class="material-icons-outlined">build</span>
					<?php esc_html_e( 'Template Builder', 'brevo-campaign-generator' ); ?>
				</a>
				<div class="bcg-help-nav-sub">
					<a href="#builder-palette" class="bcg-help-nav-item bcg-help-nav-child" data-section="builder-palette">
						<?php esc_html_e( 'Section Palette', 'brevo-campaign-generator' ); ?>
					</a>
					<a href="#builder-canvas" class="bcg-help-nav-item bcg-help-nav-child" data-section="builder-canvas">
						<?php esc_html_e( 'Canvas', 'brevo-campaign-generator' ); ?>
					</a>
					<a href="#builder-settings" class="bcg-help-nav-item bcg-help-nav-child" data-section="builder-settings">
						<?php esc_html_e( 'Settings Panel', 'brevo-campaign-generator' ); ?>
					</a>
					<a href="#section-types" class="bcg-help-nav-item bcg-help-nav-child" data-section="section-types">
						<?php esc_html_e( 'Section Types', 'brevo-campaign-generator' ); ?>
					</a>
				</div>
				<a href="#template-editor" class="bcg-help-nav-item" data-section="template-editor">
					<span class="material-icons-outlined">palette</span>
					<?php esc_html_e( 'Template Editor', 'brevo-campaign-generator' ); ?>
				</a>
				<a href="#ai-credits" class="bcg-help-nav-item" data-section="ai-credits">
					<span class="material-icons-outlined">auto_awesome</span>
					<?php esc_html_e( 'AI & Credits', 'brevo-campaign-generator' ); ?>
				</a>
				<a href="#brevo-stats" class="bcg-help-nav-item" data-section="brevo-stats">
					<span class="material-icons-outlined">bar_chart</span>
					<?php esc_html_e( 'Brevo Stats', 'brevo-campaign-generator' ); ?>
				</a>
			</div>

			<div class="bcg-help-sidebar-footer">
				<span class="material-icons-outlined">lightbulb</span>
				<div>
					<strong><?php esc_html_e( 'Need a feature?', 'brevo-campaign-generator' ); ?></strong>
					<span><?php esc_html_e( 'Use "Request a Section" in the Template Builder to send us ideas.', 'brevo-campaign-generator' ); ?></span>
				</div>
			</div>
		</nav><!-- /.bcg-help-sidebar -->

		<!-- ── Content Area ───────────────────────────────────────────── -->
		<div class="bcg-help-content" id="bcg-help-content">

			<!-- ══ 1. GETTING STARTED ══════════════════════════════════ -->
			<section class="bcg-help-section" id="getting-started">
				<div class="bcg-help-section-header">
					<span class="material-icons-outlined">rocket_launch</span>
					<div>
						<h2><?php esc_html_e( 'Getting Started', 'brevo-campaign-generator' ); ?></h2>
						<p><?php esc_html_e( 'A quick overview of what this plugin does and how to get up and running.', 'brevo-campaign-generator' ); ?></p>
					</div>
				</div>

				<div class="bcg-help-card">
					<h3><?php esc_html_e( 'What is Brevo Campaign Generator?', 'brevo-campaign-generator' ); ?></h3>
					<p><?php esc_html_e( 'Brevo Campaign Generator connects your WooCommerce store with Brevo (formerly Sendinblue) and uses AI to write your email campaigns for you. You choose the products, pick a tone and theme, and the AI generates compelling headlines, descriptions, and copy — then pushes it directly to Brevo so you can send it to your subscribers.', 'brevo-campaign-generator' ); ?></p>

					<div class="bcg-help-feature-grid">
						<div class="bcg-help-feature-item">
							<span class="material-icons-outlined bcg-help-feature-icon">inventory_2</span>
							<div>
								<strong><?php esc_html_e( 'WooCommerce Products', 'brevo-campaign-generator' ); ?></strong>
								<span><?php esc_html_e( 'Automatically pull your best sellers, latest, or hand-picked products.', 'brevo-campaign-generator' ); ?></span>
							</div>
						</div>
						<div class="bcg-help-feature-item">
							<span class="material-icons-outlined bcg-help-feature-icon">auto_awesome</span>
							<div>
								<strong><?php esc_html_e( 'AI Copywriting', 'brevo-campaign-generator' ); ?></strong>
								<span><?php esc_html_e( 'GPT-powered headlines, descriptions, subject lines, and more.', 'brevo-campaign-generator' ); ?></span>
							</div>
						</div>
						<div class="bcg-help-feature-item">
							<span class="material-icons-outlined bcg-help-feature-icon">build</span>
							<div>
								<strong><?php esc_html_e( 'Template Builder', 'brevo-campaign-generator' ); ?></strong>
								<span><?php esc_html_e( 'Build reusable email layouts from pre-built section blocks.', 'brevo-campaign-generator' ); ?></span>
							</div>
						</div>
						<div class="bcg-help-feature-item">
							<span class="material-icons-outlined bcg-help-feature-icon">send</span>
							<div>
								<strong><?php esc_html_e( 'One-Click Sending', 'brevo-campaign-generator' ); ?></strong>
								<span><?php esc_html_e( 'Push campaigns to Brevo and send or schedule with one click.', 'brevo-campaign-generator' ); ?></span>
							</div>
						</div>
						<div class="bcg-help-feature-item">
							<span class="material-icons-outlined bcg-help-feature-icon">local_offer</span>
							<div>
								<strong><?php esc_html_e( 'Auto Coupons', 'brevo-campaign-generator' ); ?></strong>
								<span><?php esc_html_e( 'Automatically generate WooCommerce discount coupons for your campaigns.', 'brevo-campaign-generator' ); ?></span>
							</div>
						</div>
						<div class="bcg-help-feature-item">
							<span class="material-icons-outlined bcg-help-feature-icon">bar_chart</span>
							<div>
								<strong><?php esc_html_e( 'Campaign Analytics', 'brevo-campaign-generator' ); ?></strong>
								<span><?php esc_html_e( 'View open rates, click rates, and more from the Stats page.', 'brevo-campaign-generator' ); ?></span>
							</div>
						</div>
					</div>
				</div>

				<div class="bcg-help-card">
					<h3><?php esc_html_e( 'Quick Start: Your First Campaign', 'brevo-campaign-generator' ); ?></h3>
					<div class="bcg-help-steps">
						<div class="bcg-help-step">
							<div class="bcg-help-step-number">1</div>
							<div class="bcg-help-step-body">
								<strong><?php esc_html_e( 'Go to New Campaign', 'brevo-campaign-generator' ); ?></strong>
								<p><?php esc_html_e( 'Click "New Campaign" in the left menu or the header. Fill in a title, subject line, and choose your mailing list.', 'brevo-campaign-generator' ); ?></p>
							</div>
						</div>
						<div class="bcg-help-step">
							<div class="bcg-help-step-number">2</div>
							<div class="bcg-help-step-body">
								<strong><?php esc_html_e( 'Select Products', 'brevo-campaign-generator' ); ?></strong>
								<p><?php esc_html_e( 'Choose how many products to feature, and whether to pull best sellers, latest products, or a manual selection.', 'brevo-campaign-generator' ); ?></p>
							</div>
						</div>
						<div class="bcg-help-step">
							<div class="bcg-help-step-number">3</div>
							<div class="bcg-help-step-body">
								<strong><?php esc_html_e( 'Choose AI Options', 'brevo-campaign-generator' ); ?></strong>
								<p><?php esc_html_e( 'Set the tone (Professional, Friendly, Urgent, etc.), enter an optional campaign theme like "Summer Sale", and pick your language.', 'brevo-campaign-generator' ); ?></p>
							</div>
						</div>
						<div class="bcg-help-step">
							<div class="bcg-help-step-number">4</div>
							<div class="bcg-help-step-body">
								<strong><?php esc_html_e( 'Generate Campaign', 'brevo-campaign-generator' ); ?></strong>
								<p><?php esc_html_e( 'Hit "Generate Campaign" and the AI writes all the copy. You land on the campaign editor where you can tweak anything.', 'brevo-campaign-generator' ); ?></p>
							</div>
						</div>
						<div class="bcg-help-step">
							<div class="bcg-help-step-number">5</div>
							<div class="bcg-help-step-body">
								<strong><?php esc_html_e( 'Send or Schedule', 'brevo-campaign-generator' ); ?></strong>
								<p><?php esc_html_e( 'Preview the email, send yourself a test, then push it to Brevo to send immediately or at a scheduled time.', 'brevo-campaign-generator' ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<div class="bcg-help-tip">
					<span class="material-icons-outlined">tips_and_updates</span>
					<div>
						<strong><?php esc_html_e( 'Tip:', 'brevo-campaign-generator' ); ?></strong>
						<?php esc_html_e( 'Before your first campaign, make sure your Brevo mailing list is set up and your API keys are configured in Settings. Contact your administrator if you need these set up.', 'brevo-campaign-generator' ); ?>
					</div>
				</div>
			</section>

			<!-- ══ 2. DASHBOARD ════════════════════════════════════════ -->
			<section class="bcg-help-section" id="dashboard">
				<div class="bcg-help-section-header">
					<span class="material-icons-outlined">dashboard</span>
					<div>
						<h2><?php esc_html_e( 'Dashboard', 'brevo-campaign-generator' ); ?></h2>
						<p><?php esc_html_e( 'Your campaign control centre — view, manage, and track all campaigns.', 'brevo-campaign-generator' ); ?></p>
					</div>
				</div>

				<div class="bcg-help-card">
					<h3><?php esc_html_e( 'Stats Cards', 'brevo-campaign-generator' ); ?></h3>
					<p><?php esc_html_e( 'At the top of the Dashboard you\'ll see four summary cards:', 'brevo-campaign-generator' ); ?></p>
					<div class="bcg-help-def-list">
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Total Campaigns', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'All campaigns ever created in this plugin.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Drafts', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Campaigns that have been created but not yet sent or pushed to Brevo.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Sent', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Campaigns that have been delivered to your subscribers.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Credits Balance', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Your remaining AI credits. Click "Top Up" to buy more.', 'brevo-campaign-generator' ); ?></span>
						</div>
					</div>
				</div>

				<div class="bcg-help-card">
					<h3><?php esc_html_e( 'Campaign Status Badges', 'brevo-campaign-generator' ); ?></h3>
					<p><?php esc_html_e( 'Every campaign in the table has a status badge showing where it is in its lifecycle:', 'brevo-campaign-generator' ); ?></p>
					<div class="bcg-help-badge-grid">
						<div class="bcg-help-badge-item">
							<span class="bcg-badge bcg-badge-draft"><?php esc_html_e( 'Draft', 'brevo-campaign-generator' ); ?></span>
							<span><?php esc_html_e( 'Created and saved but not yet pushed to Brevo. You can continue editing.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-badge-item">
							<span class="bcg-badge bcg-badge-ready"><?php esc_html_e( 'Ready', 'brevo-campaign-generator' ); ?></span>
							<span><?php esc_html_e( 'Campaign has been created in Brevo and is ready to send or schedule.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-badge-item">
							<span class="bcg-badge bcg-badge-scheduled"><?php esc_html_e( 'Scheduled', 'brevo-campaign-generator' ); ?></span>
							<span><?php esc_html_e( 'Campaign is queued to send at a future date and time.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-badge-item">
							<span class="bcg-badge bcg-badge-sent"><?php esc_html_e( 'Sent', 'brevo-campaign-generator' ); ?></span>
							<span><?php esc_html_e( 'Campaign has been delivered to subscribers. Stats are available in Brevo Stats.', 'brevo-campaign-generator' ); ?></span>
						</div>
					</div>
				</div>

				<div class="bcg-help-card">
					<h3><?php esc_html_e( 'Filtering & Searching', 'brevo-campaign-generator' ); ?></h3>
					<p><?php esc_html_e( 'Use the filter tabs (All / Draft / Ready / Scheduled / Sent) to narrow the campaign list by status. Use the search box to find campaigns by title or subject line.', 'brevo-campaign-generator' ); ?></p>
				</div>

				<div class="bcg-help-card">
					<h3><?php esc_html_e( 'Campaign Table Actions', 'brevo-campaign-generator' ); ?></h3>
					<p><?php esc_html_e( 'Hover over a campaign row to see available actions:', 'brevo-campaign-generator' ); ?></p>
					<div class="bcg-help-def-list">
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Edit', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Open the campaign editor to modify content and settings.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Duplicate', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Create a copy of the campaign as a new Draft.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Delete', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Permanently remove the campaign from the plugin (does not affect Brevo).', 'brevo-campaign-generator' ); ?></span>
						</div>
					</div>
				</div>
			</section>

			<!-- ══ 3. CREATING CAMPAIGNS ═══════════════════════════════ -->
			<section class="bcg-help-section" id="creating-campaigns">
				<div class="bcg-help-section-header">
					<span class="material-icons-outlined">add_circle_outline</span>
					<div>
						<h2><?php esc_html_e( 'Creating Campaigns', 'brevo-campaign-generator' ); ?></h2>
						<p><?php esc_html_e( 'Creating a campaign is a two-step process: configure your options, then edit the AI-generated content.', 'brevo-campaign-generator' ); ?></p>
					</div>
				</div>

				<!-- Step 1 -->
				<div class="bcg-help-card" id="step1-configure">
					<div class="bcg-help-card-label">
						<span class="bcg-help-step-pill">Step 1</span>
						<h3><?php esc_html_e( 'Campaign Configuration', 'brevo-campaign-generator' ); ?></h3>
					</div>

					<h4><?php esc_html_e( 'Campaign Basics', 'brevo-campaign-generator' ); ?></h4>
					<div class="bcg-help-def-list">
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Campaign Title', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Internal name for the campaign. Not visible to subscribers.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Subject Line', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'The email subject your subscribers see in their inbox. Use the ✨ button to have AI suggest one based on your products.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Preview Text', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'The short snippet shown next to the subject in most email clients. Complements the subject line.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Mailing List', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Which Brevo contact list to send to. Populated from your Brevo account.', 'brevo-campaign-generator' ); ?></span>
						</div>
					</div>

					<h4 class="bcg-mt-24"><?php esc_html_e( 'Product Selection', 'brevo-campaign-generator' ); ?></h4>
					<p><?php esc_html_e( 'Choose how the plugin selects products to feature in your campaign:', 'brevo-campaign-generator' ); ?></p>
					<div class="bcg-help-option-grid">
						<div class="bcg-help-option">
							<span class="material-icons-outlined">trending_up</span>
							<div>
								<strong><?php esc_html_e( 'Best Sellers', 'brevo-campaign-generator' ); ?></strong>
								<span><?php esc_html_e( 'Products sorted by sales count (highest first). Great for promotional campaigns.', 'brevo-campaign-generator' ); ?></span>
							</div>
						</div>
						<div class="bcg-help-option">
							<span class="material-icons-outlined">trending_down</span>
							<div>
								<strong><?php esc_html_e( 'Least Sold', 'brevo-campaign-generator' ); ?></strong>
								<span><?php esc_html_e( 'Products with fewer sales. Useful for clearing slow-moving stock.', 'brevo-campaign-generator' ); ?></span>
							</div>
						</div>
						<div class="bcg-help-option">
							<span class="material-icons-outlined">new_releases</span>
							<div>
								<strong><?php esc_html_e( 'Latest Products', 'brevo-campaign-generator' ); ?></strong>
								<span><?php esc_html_e( 'Most recently added products. Perfect for "New Arrivals" campaigns.', 'brevo-campaign-generator' ); ?></span>
							</div>
						</div>
						<div class="bcg-help-option">
							<span class="material-icons-outlined">touch_app</span>
							<div>
								<strong><?php esc_html_e( 'Manual Selection', 'brevo-campaign-generator' ); ?></strong>
								<span><?php esc_html_e( 'Search and hand-pick exactly which products to feature.', 'brevo-campaign-generator' ); ?></span>
							</div>
						</div>
					</div>
					<p class="bcg-text-secondary bcg-mt-12"><?php esc_html_e( 'You can also filter by product category to focus on a specific part of your range. Use "Preview Products" to see which products will be included before generating.', 'brevo-campaign-generator' ); ?></p>

					<h4 class="bcg-mt-24"><?php esc_html_e( 'Coupon', 'brevo-campaign-generator' ); ?></h4>
					<p><?php esc_html_e( 'Enable the coupon toggle to automatically create a WooCommerce discount code for this campaign. You can configure:', 'brevo-campaign-generator' ); ?></p>
					<ul class="bcg-help-list">
						<li><?php esc_html_e( 'Discount type: Percentage (%) or Fixed amount', 'brevo-campaign-generator' ); ?></li>
						<li><?php esc_html_e( 'Discount value (use ✨ to get an AI suggestion based on your products)', 'brevo-campaign-generator' ); ?></li>
						<li><?php esc_html_e( 'Expiry: number of days until the coupon expires', 'brevo-campaign-generator' ); ?></li>
						<li><?php esc_html_e( 'Optional prefix for the coupon code (e.g. SALE → SALE-A3K9P2)', 'brevo-campaign-generator' ); ?></li>
					</ul>

					<h4 class="bcg-mt-24"><?php esc_html_e( 'AI Generation Options', 'brevo-campaign-generator' ); ?></h4>
					<div class="bcg-help-def-list">
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Tone of Voice', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Professional, Friendly, Urgent, Playful, or Luxury — shapes how the AI writes the copy.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Campaign Theme', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Optional context for the AI — e.g. "Summer Sale", "Black Friday", "New Year". This gives the copy more focus.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Language', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'The AI will write all copy in the selected language. Supported: English, Polish, French, German, Spanish, Italian.', 'brevo-campaign-generator' ); ?></span>
						</div>
					</div>
				</div>

				<!-- Step 2 -->
				<div class="bcg-help-card" id="step2-edit">
					<div class="bcg-help-card-label">
						<span class="bcg-help-step-pill">Step 2</span>
						<h3><?php esc_html_e( 'Edit & Send', 'brevo-campaign-generator' ); ?></h3>
					</div>
					<p><?php esc_html_e( 'After generation, you land on the campaign editor. Every piece of content can be edited, and any field can be regenerated individually.', 'brevo-campaign-generator' ); ?></p>

					<h4><?php esc_html_e( 'Main Campaign Content', 'brevo-campaign-generator' ); ?></h4>
					<div class="bcg-help-def-list">
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Main Headline', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'The large hero headline at the top of the email. Edit freely or click ↻ Regenerate.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Main Description', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Supporting copy below the headline. Regenerate individually with ↻.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Main Image', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'The hero banner image. Regenerate with AI or upload your own using the media library.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Subject Line', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Editable here as well — regenerate if needed.', 'brevo-campaign-generator' ); ?></span>
						</div>
					</div>

					<h4 class="bcg-mt-24"><?php esc_html_e( 'Product Cards', 'brevo-campaign-generator' ); ?></h4>
					<p><?php esc_html_e( 'Each product appears as a card you can reorder by dragging. Within each card you can:', 'brevo-campaign-generator' ); ?></p>
					<ul class="bcg-help-list">
						<li><?php esc_html_e( 'Edit the AI-written headline and short description', 'brevo-campaign-generator' ); ?></li>
						<li><?php esc_html_e( 'Regenerate headline or description individually with ↻', 'brevo-campaign-generator' ); ?></li>
						<li><?php esc_html_e( 'Toggle "Show Buy Button" on or off', 'brevo-campaign-generator' ); ?></li>
						<li><?php esc_html_e( 'Switch between the product\'s own image or an AI-generated image', 'brevo-campaign-generator' ); ?></li>
						<li><?php esc_html_e( 'Remove the product from the campaign', 'brevo-campaign-generator' ); ?></li>
					</ul>
					<p class="bcg-text-secondary"><?php esc_html_e( 'Use "+ Add Another Product" at the bottom to add more products. AI content is generated immediately for any new product added.', 'brevo-campaign-generator' ); ?></p>

					<h4 class="bcg-mt-24"><?php esc_html_e( 'Coupon Block', 'brevo-campaign-generator' ); ?></h4>
					<p><?php esc_html_e( 'If you enabled a coupon, it appears as an editable block showing the code, discount text, and expiry. All fields are editable.', 'brevo-campaign-generator' ); ?></p>

					<h4 class="bcg-mt-24"><?php esc_html_e( 'Actions Bar', 'brevo-campaign-generator' ); ?></h4>
					<p><?php esc_html_e( 'The sticky bar at the bottom of the editor gives you these actions:', 'brevo-campaign-generator' ); ?></p>
					<div class="bcg-help-actions-grid">
						<div class="bcg-help-action-item">
							<span class="material-icons-outlined">save</span>
							<div>
								<strong><?php esc_html_e( 'Save Draft', 'brevo-campaign-generator' ); ?></strong>
								<span><?php esc_html_e( 'Save your current edits without sending.', 'brevo-campaign-generator' ); ?></span>
							</div>
						</div>
						<div class="bcg-help-action-item">
							<span class="material-icons-outlined">preview</span>
							<div>
								<strong><?php esc_html_e( 'Preview Email', 'brevo-campaign-generator' ); ?></strong>
								<span><?php esc_html_e( 'See a full render of the email in a modal (desktop &amp; mobile).', 'brevo-campaign-generator' ); ?></span>
							</div>
						</div>
						<div class="bcg-help-action-item">
							<span class="material-icons-outlined">science</span>
							<div>
								<strong><?php esc_html_e( 'Send Test Email', 'brevo-campaign-generator' ); ?></strong>
								<span><?php esc_html_e( 'Send the campaign to your admin email address to check how it looks in a real inbox.', 'brevo-campaign-generator' ); ?></span>
							</div>
						</div>
						<div class="bcg-help-action-item">
							<span class="material-icons-outlined">cloud_upload</span>
							<div>
								<strong><?php esc_html_e( 'Create in Brevo', 'brevo-campaign-generator' ); ?></strong>
								<span><?php esc_html_e( 'Push the campaign to your Brevo account as a draft campaign. Status becomes Ready.', 'brevo-campaign-generator' ); ?></span>
							</div>
						</div>
						<div class="bcg-help-action-item">
							<span class="material-icons-outlined">schedule</span>
							<div>
								<strong><?php esc_html_e( 'Schedule Campaign', 'brevo-campaign-generator' ); ?></strong>
								<span><?php esc_html_e( 'Pick a date and time to send. Brevo handles delivery automatically.', 'brevo-campaign-generator' ); ?></span>
							</div>
						</div>
						<div class="bcg-help-action-item">
							<span class="material-icons-outlined">send</span>
							<div>
								<strong><?php esc_html_e( 'Send Now', 'brevo-campaign-generator' ); ?></strong>
								<span><?php esc_html_e( 'Send the campaign to your mailing list immediately via Brevo.', 'brevo-campaign-generator' ); ?></span>
							</div>
						</div>
					</div>
				</div>
			</section>

			<!-- ══ 4. TEMPLATE BUILDER ═════════════════════════════════ -->
			<section class="bcg-help-section" id="template-builder">
				<div class="bcg-help-section-header">
					<span class="material-icons-outlined">build</span>
					<div>
						<h2><?php esc_html_e( 'Template Builder', 'brevo-campaign-generator' ); ?></h2>
						<p><?php esc_html_e( 'Build reusable, section-based email templates — no coding required.', 'brevo-campaign-generator' ); ?></p>
					</div>
				</div>

				<div class="bcg-help-card">
					<h3><?php esc_html_e( 'Overview', 'brevo-campaign-generator' ); ?></h3>
					<p><?php esc_html_e( 'The Template Builder lets you compose email layouts from pre-built section blocks. Arrange them in any order, customise every style setting, then save as a named template to reuse across campaigns.', 'brevo-campaign-generator' ); ?></p>
					<div class="bcg-help-layout-diagram">
						<div class="bcg-help-layout-panel">
							<div class="bcg-help-layout-panel-label"><?php esc_html_e( 'SECTIONS', 'brevo-campaign-generator' ); ?></div>
							<div class="bcg-help-layout-panel-icon"><span class="material-icons-outlined">widgets</span></div>
							<div class="bcg-help-layout-panel-desc"><?php esc_html_e( 'Click any variant to add it to the canvas', 'brevo-campaign-generator' ); ?></div>
						</div>
						<div class="bcg-help-layout-arrow"><span class="material-icons-outlined">arrow_forward</span></div>
						<div class="bcg-help-layout-panel bcg-help-layout-panel-center">
							<div class="bcg-help-layout-panel-label"><?php esc_html_e( 'CANVAS', 'brevo-campaign-generator' ); ?></div>
							<div class="bcg-help-layout-panel-icon"><span class="material-icons-outlined">dashboard</span></div>
							<div class="bcg-help-layout-panel-desc"><?php esc_html_e( 'Drag sections to reorder — click to select', 'brevo-campaign-generator' ); ?></div>
						</div>
						<div class="bcg-help-layout-arrow"><span class="material-icons-outlined">arrow_forward</span></div>
						<div class="bcg-help-layout-panel">
							<div class="bcg-help-layout-panel-label"><?php esc_html_e( 'SETTINGS', 'brevo-campaign-generator' ); ?></div>
							<div class="bcg-help-layout-panel-icon"><span class="material-icons-outlined">tune</span></div>
							<div class="bcg-help-layout-panel-desc"><?php esc_html_e( 'Edit colours, text, layout for the selected section', 'brevo-campaign-generator' ); ?></div>
						</div>
					</div>
				</div>

				<div class="bcg-help-card" id="builder-palette">
					<h3><?php esc_html_e( 'Section Palette (Left Panel)', 'brevo-campaign-generator' ); ?></h3>
					<p><?php esc_html_e( 'The palette lists all available section types grouped by category. Each group contains several pre-built variants with different layouts and styles.', 'brevo-campaign-generator' ); ?></p>
					<ul class="bcg-help-list">
						<li><?php esc_html_e( 'Scroll through the palette to browse all categories.', 'brevo-campaign-generator' ); ?></li>
						<li><?php esc_html_e( 'Click any variant card to instantly add that section to the bottom of the canvas.', 'brevo-campaign-generator' ); ?></li>
						<li><?php esc_html_e( 'The section is added with sensible default settings that you can customise in the Settings panel.', 'brevo-campaign-generator' ); ?></li>
					</ul>
					<div class="bcg-help-tip">
						<span class="material-icons-outlined">tips_and_updates</span>
						<div><?php esc_html_e( 'You can add the same section type multiple times — for example, two different Text Block sections with different content.', 'brevo-campaign-generator' ); ?></div>
					</div>
				</div>

				<div class="bcg-help-card" id="builder-canvas">
					<h3><?php esc_html_e( 'Canvas (Centre Panel)', 'brevo-campaign-generator' ); ?></h3>
					<p><?php esc_html_e( 'The canvas shows all the sections in your email template in order. Each section appears as a row with control buttons on the right.', 'brevo-campaign-generator' ); ?></p>
					<h4><?php esc_html_e( 'Reordering Sections', 'brevo-campaign-generator' ); ?></h4>
					<ul class="bcg-help-list">
						<li><?php esc_html_e( 'Drag the ≡ handle on the left side of a section row to drag-and-drop it to a new position.', 'brevo-campaign-generator' ); ?></li>
						<li><?php esc_html_e( 'Use the ↑ and ↓ arrow buttons to move a section up or down one position.', 'brevo-campaign-generator' ); ?></li>
					</ul>
					<h4 class="bcg-mt-16"><?php esc_html_e( 'Section Controls', 'brevo-campaign-generator' ); ?></h4>
					<div class="bcg-help-def-list">
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">auto_awesome</span> <?php esc_html_e( 'AI Generate', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Generate or regenerate AI content for this section. Only available for sections that have AI-writable text (Hero, Text Block, Banner, CTA, Products).', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">settings</span> <?php esc_html_e( 'Settings', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Click to open this section\'s settings in the right panel.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">visibility</span> <?php esc_html_e( 'Show / Hide', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Toggle the section\'s visibility in the preview and final output without deleting it.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><span class="material-icons-outlined" style="font-size:16px;vertical-align:middle;">delete</span> <?php esc_html_e( 'Delete', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Remove the section from the template permanently.', 'brevo-campaign-generator' ); ?></span>
						</div>
					</div>
					<div class="bcg-help-tip bcg-mt-16">
						<span class="material-icons-outlined">tips_and_updates</span>
						<div><?php esc_html_e( 'The orange dot in the Canvas header means you have unsaved changes. Remember to save your template before leaving the page.', 'brevo-campaign-generator' ); ?></div>
					</div>
				</div>

				<div class="bcg-help-card" id="builder-settings">
					<h3><?php esc_html_e( 'Settings Panel (Right Panel)', 'brevo-campaign-generator' ); ?></h3>
					<p><?php esc_html_e( 'When you click on a section in the canvas, its editable settings appear here. Settings vary by section type and typically include:', 'brevo-campaign-generator' ); ?></p>
					<ul class="bcg-help-list">
						<li><strong><?php esc_html_e( 'Text fields', 'brevo-campaign-generator' ); ?></strong> — <?php esc_html_e( 'headlines, body copy, button labels, URLs', 'brevo-campaign-generator' ); ?></li>
						<li><strong><?php esc_html_e( 'Colour pickers', 'brevo-campaign-generator' ); ?></strong> — <?php esc_html_e( 'background, text, button, accent colours', 'brevo-campaign-generator' ); ?></li>
						<li><strong><?php esc_html_e( 'Sliders', 'brevo-campaign-generator' ); ?></strong> — <?php esc_html_e( 'font size, padding, border radius, image width', 'brevo-campaign-generator' ); ?></li>
						<li><strong><?php esc_html_e( 'Dropdowns', 'brevo-campaign-generator' ); ?></strong> — <?php esc_html_e( 'alignment, columns, list style', 'brevo-campaign-generator' ); ?></li>
						<li><strong><?php esc_html_e( 'Image pickers', 'brevo-campaign-generator' ); ?></strong> — <?php esc_html_e( 'logo, hero image, product images (opens WordPress media library)', 'brevo-campaign-generator' ); ?></li>
						<li><strong><?php esc_html_e( 'Toggle switches', 'brevo-campaign-generator' ); ?></strong> — <?php esc_html_e( 'show/hide navigation, show/hide price, show/hide buy button', 'brevo-campaign-generator' ); ?></li>
					</ul>
					<p class="bcg-text-secondary"><?php esc_html_e( 'Changes in the Settings panel update the live preview automatically within a few seconds.', 'brevo-campaign-generator' ); ?></p>
				</div>

				<div class="bcg-help-card" id="section-types">
					<h3><?php esc_html_e( 'Section Types Reference', 'brevo-campaign-generator' ); ?></h3>
					<p><?php esc_html_e( 'All available section categories and their variants:', 'brevo-campaign-generator' ); ?></p>

					<div class="bcg-help-section-type-grid">

						<div class="bcg-help-section-type">
							<div class="bcg-help-section-type-header">
								<span>🔝</span>
								<strong><?php esc_html_e( 'Header', 'brevo-campaign-generator' ); ?></strong>
								<span class="bcg-help-section-type-badge"><?php esc_html_e( '2 variants', 'brevo-campaign-generator' ); ?></span>
							</div>
							<p><?php esc_html_e( 'Displays your store logo at the top of the email. Optionally includes navigation links.', 'brevo-campaign-generator' ); ?></p>
							<ul class="bcg-help-variants">
								<li><?php esc_html_e( 'Logo Only — clean, minimal logo display', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Logo + Navigation — logo with linked nav items', 'brevo-campaign-generator' ); ?></li>
							</ul>
						</div>

						<div class="bcg-help-section-type">
							<div class="bcg-help-section-type-header">
								<span>🖼️</span>
								<strong><?php esc_html_e( 'Hero / Banner', 'brevo-campaign-generator' ); ?></strong>
								<span class="bcg-help-section-type-badge bcg-ai-badge"><?php esc_html_e( 'AI', 'brevo-campaign-generator' ); ?></span>
								<span class="bcg-help-section-type-badge"><?php esc_html_e( '4 variants', 'brevo-campaign-generator' ); ?></span>
							</div>
							<p><?php esc_html_e( 'Full-width hero with background colour or image, headline, subtext, and optional CTA button. AI can generate the headline and subtext.', 'brevo-campaign-generator' ); ?></p>
							<ul class="bcg-help-variants">
								<li><?php esc_html_e( 'Standard — headline + subtext + button', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'No Button — headline + subtext only', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Compact — reduced padding for tighter layouts', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Tall & Spacious — generous padding for impact', 'brevo-campaign-generator' ); ?></li>
							</ul>
						</div>

						<div class="bcg-help-section-type">
							<div class="bcg-help-section-type-header">
								<span>📌</span>
								<strong><?php esc_html_e( 'Section Heading', 'brevo-campaign-generator' ); ?></strong>
								<span class="bcg-help-section-type-badge"><?php esc_html_e( '4 variants', 'brevo-campaign-generator' ); ?></span>
							</div>
							<p><?php esc_html_e( 'A title row to introduce a new content area. Useful for separating products from promotional text.', 'brevo-campaign-generator' ); ?></p>
							<ul class="bcg-help-variants">
								<li><?php esc_html_e( 'Centred + Accent Line', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Left-Aligned + Accent Line', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'No Accent Line — clean title only', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Centred, No Accent', 'brevo-campaign-generator' ); ?></li>
							</ul>
						</div>

						<div class="bcg-help-section-type">
							<div class="bcg-help-section-type-header">
								<span>📝</span>
								<strong><?php esc_html_e( 'Text Block', 'brevo-campaign-generator' ); ?></strong>
								<span class="bcg-help-section-type-badge bcg-ai-badge"><?php esc_html_e( 'AI', 'brevo-campaign-generator' ); ?></span>
								<span class="bcg-help-section-type-badge"><?php esc_html_e( '4 variants', 'brevo-campaign-generator' ); ?></span>
							</div>
							<p><?php esc_html_e( 'A block of editorial text — heading and/or body paragraph. AI writes both. Great for introductions or context.', 'brevo-campaign-generator' ); ?></p>
							<ul class="bcg-help-variants">
								<li><?php esc_html_e( 'With Heading + Body', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Body Only (Left-Aligned)', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Body Only (Centred)', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Large Heading Intro', 'brevo-campaign-generator' ); ?></li>
							</ul>
						</div>

						<div class="bcg-help-section-type">
							<div class="bcg-help-section-type-header">
								<span>📣</span>
								<strong><?php esc_html_e( 'Promotional Banner', 'brevo-campaign-generator' ); ?></strong>
								<span class="bcg-help-section-type-badge bcg-ai-badge"><?php esc_html_e( 'AI', 'brevo-campaign-generator' ); ?></span>
								<span class="bcg-help-section-type-badge"><?php esc_html_e( '3 variants', 'brevo-campaign-generator' ); ?></span>
							</div>
							<p><?php esc_html_e( 'A bold coloured banner with a short promotional message — ideal for announcing sales or highlights.', 'brevo-campaign-generator' ); ?></p>
							<ul class="bcg-help-variants">
								<li><?php esc_html_e( 'Centred (Heading + Subtext)', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Left-Aligned', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Heading Only (No Subtext)', 'brevo-campaign-generator' ); ?></li>
							</ul>
						</div>

						<div class="bcg-help-section-type">
							<div class="bcg-help-section-type-header">
								<span>🛍️</span>
								<strong><?php esc_html_e( 'Products', 'brevo-campaign-generator' ); ?></strong>
								<span class="bcg-help-section-type-badge bcg-ai-badge"><?php esc_html_e( 'AI', 'brevo-campaign-generator' ); ?></span>
								<span class="bcg-help-section-type-badge"><?php esc_html_e( '5 variants', 'brevo-campaign-generator' ); ?></span>
							</div>
							<p><?php esc_html_e( 'Displays WooCommerce products with images, name, price, and optional buy button. Choose 1–3 columns. AI writes per-product headlines and descriptions.', 'brevo-campaign-generator' ); ?></p>
							<ul class="bcg-help-variants">
								<li><?php esc_html_e( 'Single Column — one product per row', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( '2-Column Grid', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( '3-Column Grid', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Featured (Centred) — large single product spotlight', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Without Button — product display only', 'brevo-campaign-generator' ); ?></li>
							</ul>
						</div>

						<div class="bcg-help-section-type">
							<div class="bcg-help-section-type-header">
								<span>📋</span>
								<strong><?php esc_html_e( 'Feature List', 'brevo-campaign-generator' ); ?></strong>
								<span class="bcg-help-section-type-badge"><?php esc_html_e( '3 variants', 'brevo-campaign-generator' ); ?></span>
							</div>
							<p><?php esc_html_e( 'A structured list of features or benefits. Great for highlighting product USPs or offer details.', 'brevo-campaign-generator' ); ?></p>
							<ul class="bcg-help-variants">
								<li><?php esc_html_e( 'Bullet Points — classic dot list', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Checkmarks — ✓ icon for each item', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Numbered Steps — ordered sequence', 'brevo-campaign-generator' ); ?></li>
							</ul>
						</div>

						<div class="bcg-help-section-type">
							<div class="bcg-help-section-type-header">
								<span>🎯</span>
								<strong><?php esc_html_e( 'Call to Action', 'brevo-campaign-generator' ); ?></strong>
								<span class="bcg-help-section-type-badge bcg-ai-badge"><?php esc_html_e( 'AI', 'brevo-campaign-generator' ); ?></span>
								<span class="bcg-help-section-type-badge"><?php esc_html_e( '4 variants', 'brevo-campaign-generator' ); ?></span>
							</div>
							<p><?php esc_html_e( 'A focused CTA block with heading, subtext, and a prominent button. AI writes the copy to drive action.', 'brevo-campaign-generator' ); ?></p>
							<ul class="bcg-help-variants">
								<li><?php esc_html_e( 'Heading + Subtext + Button', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Heading + Button Only', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Compact — minimal padding', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Spacious — generous padding', 'brevo-campaign-generator' ); ?></li>
							</ul>
						</div>

						<div class="bcg-help-section-type">
							<div class="bcg-help-section-type-header">
								<span>🎟️</span>
								<strong><?php esc_html_e( 'Coupon Block', 'brevo-campaign-generator' ); ?></strong>
								<span class="bcg-help-section-type-badge"><?php esc_html_e( '2 variants', 'brevo-campaign-generator' ); ?></span>
							</div>
							<p><?php esc_html_e( 'Displays a discount coupon code with optional description and expiry text. Makes the offer unmissable.', 'brevo-campaign-generator' ); ?></p>
							<ul class="bcg-help-variants">
								<li><?php esc_html_e( 'Full Coupon — code + discount text + expiry', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Code Only — clean display of just the code', 'brevo-campaign-generator' ); ?></li>
							</ul>
						</div>

						<div class="bcg-help-section-type">
							<div class="bcg-help-section-type-header">
								<span>🖼️</span>
								<strong><?php esc_html_e( 'Image', 'brevo-campaign-generator' ); ?></strong>
								<span class="bcg-help-section-type-badge"><?php esc_html_e( '4 variants', 'brevo-campaign-generator' ); ?></span>
							</div>
							<p><?php esc_html_e( 'A standalone image block. Upload from the WordPress media library. Optionally link to a URL.', 'brevo-campaign-generator' ); ?></p>
							<ul class="bcg-help-variants">
								<li><?php esc_html_e( 'Full Width — edge-to-edge image', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Centred 80% — centred with slight inset', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Left 60% — left-aligned partial width', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Centred with Caption — image + caption text below', 'brevo-campaign-generator' ); ?></li>
							</ul>
						</div>

						<div class="bcg-help-section-type">
							<div class="bcg-help-section-type-header">
								<span>➖</span>
								<strong><?php esc_html_e( 'Divider / Spacer', 'brevo-campaign-generator' ); ?></strong>
								<span class="bcg-help-section-type-badge"><?php esc_html_e( '4 variants', 'brevo-campaign-generator' ); ?></span>
							</div>
							<p><?php esc_html_e( 'Add visual breathing room or horizontal rules between sections.', 'brevo-campaign-generator' ); ?></p>
							<ul class="bcg-help-variants">
								<li><?php esc_html_e( 'Thin Line — subtle 1px divider', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Accent Line — coloured divider', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Small Spacer — 24px of empty space', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Large Spacer — 48px of empty space', 'brevo-campaign-generator' ); ?></li>
							</ul>
						</div>

						<div class="bcg-help-section-type">
							<div class="bcg-help-section-type-header">
								<span>🔻</span>
								<strong><?php esc_html_e( 'Footer', 'brevo-campaign-generator' ); ?></strong>
								<span class="bcg-help-section-type-badge"><?php esc_html_e( '3 variants', 'brevo-campaign-generator' ); ?></span>
							</div>
							<p><?php esc_html_e( 'Email footer with legal text, links, and the required unsubscribe link. Always include a footer in every campaign.', 'brevo-campaign-generator' ); ?></p>
							<ul class="bcg-help-variants">
								<li><?php esc_html_e( 'Text Only — minimal text footer', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Text + Links — footer with clickable links', 'brevo-campaign-generator' ); ?></li>
								<li><?php esc_html_e( 'Compact + Dark — dark background footer', 'brevo-campaign-generator' ); ?></li>
							</ul>
						</div>

					</div><!-- /.bcg-help-section-type-grid -->
				</div>

				<div class="bcg-help-card">
					<h3><?php esc_html_e( 'Toolbar Actions', 'brevo-campaign-generator' ); ?></h3>
					<div class="bcg-help-def-list">
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Template Name', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Give your template a name before saving. Choose something descriptive like "Standard Newsletter" or "Sale Template".', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Load Template', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Open a previously saved template and load it into the canvas. Any current unsaved changes will be lost.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Generate All with AI', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'AI fills in the text fields for every section that supports it in one go. Uses credits. Set your campaign theme and tone first for best results.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Preview Email', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Opens a modal showing the rendered HTML email. Toggle between desktop and mobile views.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Save Template', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Saves the current canvas as a named template. Templates can be loaded when creating a new campaign.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Request a Section', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Missing a section type? Submit a request and we\'ll build it for you.', 'brevo-campaign-generator' ); ?></span>
						</div>
					</div>
				</div>
			</section>

			<!-- ══ 5. TEMPLATE EDITOR ══════════════════════════════════ -->
			<section class="bcg-help-section" id="template-editor">
				<div class="bcg-help-section-header">
					<span class="material-icons-outlined">palette</span>
					<div>
						<h2><?php esc_html_e( 'Template Editor', 'brevo-campaign-generator' ); ?></h2>
						<p><?php esc_html_e( 'A visual editor for customising the flat HTML email templates used in standard campaigns.', 'brevo-campaign-generator' ); ?></p>
					</div>
				</div>

				<div class="bcg-help-card">
					<h3><?php esc_html_e( 'Overview', 'brevo-campaign-generator' ); ?></h3>
					<p><?php esc_html_e( 'The Template Editor works with the 20 pre-built flat HTML templates used in standard campaigns (not the Section Builder). It has three panels:', 'brevo-campaign-generator' ); ?></p>
					<div class="bcg-help-three-col">
						<div class="bcg-help-col-item">
							<strong><?php esc_html_e( 'Visual Settings', 'brevo-campaign-generator' ); ?></strong>
							<span><?php esc_html_e( 'Tab-based controls for branding, colours, fonts, and layout.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-col-item">
							<strong><?php esc_html_e( 'HTML Editor', 'brevo-campaign-generator' ); ?></strong>
							<span><?php esc_html_e( 'Direct code editing for advanced customisation.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-col-item">
							<strong><?php esc_html_e( 'Live Preview', 'brevo-campaign-generator' ); ?></strong>
							<span><?php esc_html_e( 'Real-time preview with desktop/mobile toggle.', 'brevo-campaign-generator' ); ?></span>
						</div>
					</div>
				</div>

				<div class="bcg-help-card">
					<h3><?php esc_html_e( 'Visual Settings Tabs', 'brevo-campaign-generator' ); ?></h3>
					<div class="bcg-help-def-list">
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Branding', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Upload your logo, set logo width, and add a header tagline.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Layout', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Set the email max-width, background colour, and product column layout (1–3 columns).', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Colours', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Primary accent colour, button colour, text colour, link colour, and content background.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Typography', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Font family, heading font, button border radius, and button text colour.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Navigation', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Show or hide the nav bar, add nav links (label + URL).', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Footer', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Footer text, footer links (e.g. Privacy Policy, Unsubscribe). The unsubscribe link is required by Brevo.', 'brevo-campaign-generator' ); ?></span>
						</div>
					</div>
				</div>

				<div class="bcg-help-card">
					<h3><?php esc_html_e( 'Save Options', 'brevo-campaign-generator' ); ?></h3>
					<div class="bcg-help-def-list">
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Save as Default Template', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Applies these settings as the default for all future campaigns.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Save to This Campaign', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Saves the template only to the current campaign. Other campaigns are unaffected.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Reset to Default', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Reverts the template to the original plugin defaults.', 'brevo-campaign-generator' ); ?></span>
						</div>
					</div>
				</div>
			</section>

			<!-- ══ 6. AI & CREDITS ════════════════════════════════════ -->
			<section class="bcg-help-section" id="ai-credits">
				<div class="bcg-help-section-header">
					<span class="material-icons-outlined">auto_awesome</span>
					<div>
						<h2><?php esc_html_e( 'AI & Credits', 'brevo-campaign-generator' ); ?></h2>
						<p><?php esc_html_e( 'How AI generation works and how to manage your credits.', 'brevo-campaign-generator' ); ?></p>
					</div>
				</div>

				<div class="bcg-help-card">
					<h3><?php esc_html_e( 'What are Credits?', 'brevo-campaign-generator' ); ?></h3>
					<p><?php esc_html_e( 'Credits are the currency used to pay for AI generation. Each time you ask the AI to write content, a small number of credits is deducted from your balance.', 'brevo-campaign-generator' ); ?></p>
					<p><?php esc_html_e( 'Your current credit balance is always visible in the header bar at the top of every plugin page. When your balance reaches zero, AI generation is paused until you top up.', 'brevo-campaign-generator' ); ?></p>
				</div>

				<div class="bcg-help-card">
					<h3><?php esc_html_e( 'What Uses Credits?', 'brevo-campaign-generator' ); ?></h3>
					<div class="bcg-help-def-list">
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Campaign Generation', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Generating a full campaign (subject line, headline, description + all product copy) uses credits for each piece of content generated.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Regenerating Fields', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Every ↻ Regenerate button click costs credits for that specific field.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Template Builder AI Generation', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( '"Generate All with AI" and per-section AI buttons in the Template Builder both use credits.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Adding Products', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Adding a new product to an existing campaign immediately generates AI content for it, which costs credits.', 'brevo-campaign-generator' ); ?></span>
						</div>
					</div>
					<div class="bcg-help-tip bcg-mt-16">
						<span class="material-icons-outlined">info</span>
						<div><?php esc_html_e( 'If an AI call fails due to an API error, your credits are automatically refunded for that generation.', 'brevo-campaign-generator' ); ?></div>
					</div>
				</div>

				<div class="bcg-help-card">
					<h3><?php esc_html_e( 'Topping Up Credits', 'brevo-campaign-generator' ); ?></h3>
					<p><?php esc_html_e( 'Go to Credits & Billing in the menu. Choose a credit pack and complete the purchase. Credits are added to your balance instantly after payment.', 'brevo-campaign-generator' ); ?></p>
					<p><?php esc_html_e( 'Your full transaction history — top-ups and AI usage — is visible on the Credits & Billing page.', 'brevo-campaign-generator' ); ?></p>
				</div>

				<div class="bcg-help-card">
					<h3><?php esc_html_e( 'Tips for Better AI Results', 'brevo-campaign-generator' ); ?></h3>
					<ul class="bcg-help-list">
						<li><strong><?php esc_html_e( 'Set a campaign theme.', 'brevo-campaign-generator' ); ?></strong> <?php esc_html_e( 'Adding a theme like "Summer Sale" or "Back to School" gives the AI important context for writing focused, relevant copy.', 'brevo-campaign-generator' ); ?></li>
						<li><strong><?php esc_html_e( 'Choose the right tone.', 'brevo-campaign-generator' ); ?></strong> <?php esc_html_e( '"Urgent" works well for flash sales, "Luxury" for premium products, "Friendly" for community-oriented brands.', 'brevo-campaign-generator' ); ?></li>
						<li><strong><?php esc_html_e( 'Check your WooCommerce product data.', 'brevo-campaign-generator' ); ?></strong> <?php esc_html_e( 'The AI uses your product names and descriptions as context. Well-written product descriptions lead to better campaign copy.', 'brevo-campaign-generator' ); ?></li>
						<li><strong><?php esc_html_e( 'Edit after generating.', 'brevo-campaign-generator' ); ?></strong> <?php esc_html_e( 'AI is a starting point. Always review the generated copy and make tweaks to match your brand voice exactly.', 'brevo-campaign-generator' ); ?></li>
						<li><strong><?php esc_html_e( 'Regenerate selectively.', 'brevo-campaign-generator' ); ?></strong> <?php esc_html_e( 'If you like the headline but not the description, just regenerate the description. No need to redo everything.', 'brevo-campaign-generator' ); ?></li>
					</ul>
				</div>
			</section>

			<!-- ══ 7. BREVO STATS ══════════════════════════════════════ -->
			<section class="bcg-help-section" id="brevo-stats">
				<div class="bcg-help-section-header">
					<span class="material-icons-outlined">bar_chart</span>
					<div>
						<h2><?php esc_html_e( 'Brevo Stats', 'brevo-campaign-generator' ); ?></h2>
						<p><?php esc_html_e( 'Track the performance of your sent campaigns directly from the WordPress admin.', 'brevo-campaign-generator' ); ?></p>
					</div>
				</div>

				<div class="bcg-help-card">
					<h3><?php esc_html_e( 'Overview Stats Cards', 'brevo-campaign-generator' ); ?></h3>
					<p><?php esc_html_e( 'At the top of the Brevo Stats page, four cards summarise performance across all your campaigns:', 'brevo-campaign-generator' ); ?></p>
					<div class="bcg-help-def-list">
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Total Campaigns Sent', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Number of campaigns delivered to subscribers.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Average Open Rate', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'The percentage of recipients who opened the email. Industry average is typically 20–30%.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Average Click Rate', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'The percentage of recipients who clicked at least one link. A click rate of 2–5% is considered healthy.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Total Emails Sent', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Total number of individual email sends across all campaigns.', 'brevo-campaign-generator' ); ?></span>
						</div>
					</div>
				</div>

				<div class="bcg-help-card">
					<h3><?php esc_html_e( 'Campaign Table', 'brevo-campaign-generator' ); ?></h3>
					<p><?php esc_html_e( 'The table lists each campaign with the following columns:', 'brevo-campaign-generator' ); ?></p>
					<div class="bcg-help-def-list">
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Campaign', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Campaign name as it appears in Brevo.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Sent Date', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'When the campaign was delivered.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Recipients', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Number of contacts the email was sent to.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Opens / Open Rate', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Number and percentage of unique opens.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Clicks / Click Rate', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Number and percentage of unique clicks on any link.', 'brevo-campaign-generator' ); ?></span>
						</div>
						<div class="bcg-help-def-item">
							<span class="bcg-help-def-term"><?php esc_html_e( 'Unsubscribes', 'brevo-campaign-generator' ); ?></span>
							<span class="bcg-help-def-desc"><?php esc_html_e( 'Number of recipients who unsubscribed via this campaign.', 'brevo-campaign-generator' ); ?></span>
						</div>
					</div>
					<p class="bcg-text-secondary bcg-mt-12"><?php esc_html_e( 'Click any campaign row to expand full stats. Stats are cached for 15 minutes — refresh the page to load the latest data from Brevo.', 'brevo-campaign-generator' ); ?></p>
				</div>

				<div class="bcg-help-card">
					<h3><?php esc_html_e( 'Filtering', 'brevo-campaign-generator' ); ?></h3>
					<p><?php esc_html_e( 'Use the date range picker and status filter to narrow the campaign list. You can filter by status (All, Sent, Draft, Archived) and by custom date range to compare performance over time.', 'brevo-campaign-generator' ); ?></p>
				</div>

				<div class="bcg-help-tip">
					<span class="material-icons-outlined">tips_and_updates</span>
					<div>
						<strong><?php esc_html_e( 'Pro tip:', 'brevo-campaign-generator' ); ?></strong>
						<?php esc_html_e( 'A low open rate usually means the subject line needs work. Try using the AI subject line generator with a more specific campaign theme next time. A low click rate often means the CTA is unclear — make sure your button copy is action-oriented.', 'brevo-campaign-generator' ); ?>
					</div>
				</div>
			</section>

		</div><!-- /.bcg-help-content -->
	</div><!-- /.bcg-help-layout -->
</div><!-- /.bcg-wrap.bcg-help-page -->

<script>
( function () {
	'use strict';

	// ── Search ─────────────────────────────────────────────────────────
	var searchInput = document.getElementById( 'bcg-help-search' );
	if ( searchInput ) {
		searchInput.addEventListener( 'input', function () {
			var query = this.value.toLowerCase().trim();
			var sections = document.querySelectorAll( '.bcg-help-section' );
			sections.forEach( function ( section ) {
				if ( ! query ) {
					section.style.display = '';
					return;
				}
				var text = section.innerText.toLowerCase();
				section.style.display = text.includes( query ) ? '' : 'none';
			} );
		} );
	}

	// ── Active nav on scroll ───────────────────────────────────────────
	var navItems   = document.querySelectorAll( '.bcg-help-nav-item[data-section]' );
	var sectionsAll = document.querySelectorAll( '.bcg-help-section, .bcg-help-card[id]' );

	if ( 'IntersectionObserver' in window && navItems.length ) {
		var observer = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					var id = entry.target.id;
					navItems.forEach( function ( item ) {
						item.classList.toggle( 'is-active', item.getAttribute( 'data-section' ) === id );
					} );
				}
			} );
		}, { rootMargin: '-15% 0px -75% 0px', threshold: 0 } );

		sectionsAll.forEach( function ( el ) {
			if ( el.id ) { observer.observe( el ); }
		} );
	}

	// ── Smooth scroll on nav click ─────────────────────────────────────
	navItems.forEach( function ( item ) {
		item.addEventListener( 'click', function ( e ) {
			var href = this.getAttribute( 'href' );
			if ( href && href.startsWith( '#' ) ) {
				var target = document.querySelector( href );
				if ( target ) {
					e.preventDefault();
					target.scrollIntoView( { behavior: 'smooth', block: 'start' } );
					// Update active immediately.
					navItems.forEach( function ( n ) { n.classList.remove( 'is-active' ); } );
					item.classList.add( 'is-active' );
				}
			}
		} );
	} );
} )();
</script>
