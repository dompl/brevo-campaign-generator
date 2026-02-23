# Changelog

All notable changes to **Brevo Campaign Generator for WooCommerce** will be documented in this file.

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html) and the [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) format.

---

## [Unreleased]

### Planned
- Multi-language template support
- A/B subject line testing via Brevo
- Campaign duplication
- Saved product sets / segment presets
- Webhook support for Brevo delivery events

---

## [1.5.32] — 2026-02-23

### Added
- **Section Builder: AI Prompt modal** — new "AI Prompt" button next to Generate with AI opens a popup where you describe the full email brief: visual feel, purpose, key offer, number of sections, tone. This prompt is injected into every generation call for this session alongside AI Trainer context
- **Section Builder: mandatory AI prompt** — clicking "Generate with AI" when no prompt has been entered opens the prompt modal automatically instead of generating with a blank brief; the prompt field is required to proceed

### Fixed
- **Section Builder: tone and language dropdowns not working** — root cause was jQuery delegated document click handlers interfering with each other; trigger is now bound directly on the static elements so `stopPropagation()` correctly prevents the close-all handler from firing

### Changed
- **AI generation now uses full context stack** — system prompt for every Section Builder generation call now includes: AI Trainer store context + AI Trainer product context + user-supplied AI prompt brief, all injected through `BCG_OpenAI::build_system_prompt()`

---

## [1.5.31] — 2026-02-23

### Added
- **Section Builder: Generate from empty canvas** — clicking "Generate All with AI" on an empty canvas now auto-builds a sensible default layout (Header → Hero → Products → Text → CTA → Footer) before running AI generation; previously nothing happened
- **Section Builder: custom tone/language dropdowns** — the toolbar Tone and Language selectors are now styled custom dropdowns (matching the rest of the plugin UI) instead of native browser selects

### Changed
- **Section Builder: Generate button repositioned** — the "Generate All with AI" button moved next to the Theme/Tone/Language context controls so its purpose is immediately clear

### Fixed
- **AI Trainer critical error** — page was calling a non-existent `bcg_plugin_header()` function; replaced with correct `require` for the plugin header partial
- **Campaign count + Generate button on one row** — "Number of Campaigns" dropdown and Generate button are now on the same line in the wizard actions area
- **Mailing list subscriber count** — suppressed "(0 subscribers)" label when Brevo bulk API returns no count data; lists now display name only when count is unavailable

---

## [1.5.30] — 2026-02-23

### Added
- **Schedule button on campaign list** — draft and ready campaigns now have a Schedule button in the dashboard action row; clicking opens a modal with date + time pickers; submits to the existing `bcg_schedule_campaign` AJAX handler and updates the row badge in place
- **AI Trainer in main nav** — AI Trainer moved from Settings tabs to its own submenu entry ("AI Trainer") directly under Brevo Campaigns; now a first-class page with full-width editing
- **Bulk campaign generation** — New Campaign wizard gains a "Number of Campaigns" dropdown (1–5); generates N sequential campaign drafts in one click with "Generating campaign X of N…" progress label
- **Server-side mailing list loading** — mailing lists now fetched and rendered server-side on page load (15-minute transient cache) instead of relying on JS AJAX; eliminates the "List ID: 2 (loading...)" placeholder issue; Refresh button still works as a fallback

### Changed
- **Campaign title optional** — the Campaign Title field is no longer required; if left blank, the campaign is auto-titled "Campaign — {date}" by the server

---

## [1.5.29] — 2026-02-23

### Added
- **2 new coupon section types** — `coupon_minimal` (clean borderless design, light background) and `coupon_ribbon` (dark background with a colour accent ribbon badge)
- **AI generation for all coupon sections** — all 6 coupon types (`coupon`, `coupon_banner`, `coupon_card`, `coupon_split`, `coupon_minimal`, `coupon_ribbon`) now have `has_ai: true`; the section AI dispatcher generates headline, offer text, and subtext for each
- **Expiry date picker** — all coupon sections now use `type: date` for the expiry field; rendered output formats as `d M Y`; old free-text `expiry_text` field replaced
- **Coupon Offer Text field** — coupon sections now have a dedicated `coupon_text` field (label: "Offer Text") replacing the legacy `discount_text` label
- **Products section headline** — products section gains a `section_headline` text field; if set it renders as an `<h2>` above the product grid; AI-generated on "Generate All"
- **Footer AI generation** — footer section `has_ai` now `true`; AI dispatcher returns settings unchanged (avoids overwriting compliance copy) but field toggles work as expected
- **AI Trainer settings tab** — new Settings tab with two textarea fields: "About Your Store" and "About Your Products"; content is injected into the OpenAI system prompt for all AI generation calls
- **Auto-save** — Section Builder auto-saves every 60 seconds when there are unsaved changes; shows a quiet "Auto-saved" indicator instead of a toast
- **Date picker field type** — `type: 'date'` rendered as `<input type="date">` in the section settings panel
- **Template first in New Campaign** — Email Template selection moved from step 5 to step 1 in the campaign wizard

### Changed
- **Plugin full-width** — removed `max-width: 1400px` from `.bcg-wrap` and `.bcg-plugin-header`; plugin now expands to the full available admin width
- **Campaign editor 50/50** — editor columns grid changed from `1fr 400px` to `1fr 1fr`
- **AI toggle UX** — when AI is enabled on a field, the input is hidden and a hint "AI will generate this field" is shown instead; input reappears when AI is toggled off
- **Section builder popup wider** — AI generation modal max-width increased from 500 px to 680 px
- **Action buttons no-wrap** — dashboard campaign action buttons are now always on one line (`flex-wrap: nowrap`)

### Fixed
- **Main image constrained** — campaign editor main image capped at `max-height: 300px` with `object-fit: cover` so large images no longer dominate the editor panel

---

## [1.5.28] — 2026-02-23

### Added
- **"Generate with AI" toggles in Section Builder** — every text/textarea field in the section settings panel now has an inline AI badge toggle. When enabled (default), the field is populated by AI on campaign generation; when disabled, the user's manually entered text is preserved. Excluded from toggling: button text, CTA text, and URL fields.
- **Coupon section variants** — three new coupon section types added to the palette:
  - **Coupon — Banner** — dark full-width horizontal bar with discount info on the left and the code on the right.
  - **Coupon — Card** — white card with a 4 px solid left border accent and a dashed code box.
  - **Coupon — Split** — two-column layout with a large discount number on the left and redemption details on the right.
  - The original **Coupon — Classic** type gains `headline`, `subtext`, `text_color`, `padding_top`, and `padding_bottom` fields.

### Fixed
- **Mailing list never loading** — removed N+1 Brevo API calls (one extra request per list for subscriber count). The initial bulk list response already returns `totalSubscribers`; those values are now used directly, eliminating the timeout that prevented the dropdown from populating.
- **Generation popup wrong colours** — the "Generating Campaign…" modal was rendered with a white background causing dark text to be invisible. Background now uses `var(--bcg-bg-surface)` (dark navy) matching the rest of the admin UI.

---

## [1.5.27] — 2026-02-23

### Fixed
- **Generation overlay not appearing** — added `position:fixed`, `z-index`, and backdrop CSS to `.bcg-generation-overlay` which was structurally present but visually invisible.
- **Section template ignored on generation** — JS `startGeneration()` now sends `template_slug` and `section_template_id` in the AJAX payload; previously these were never read from the hidden inputs, so the server always received empty values and fell back to the classic flat template.
- **Flat copy generation ran for section campaigns** — `handle_generate_campaign()` now branches on `section_template_id`: section campaigns skip `generate_campaign_copy()` and `generate_campaign_images()` (content already AI-populated per section in step 4b) and only save subject/preview text; flat campaigns retain the existing full generation flow.

### Added
- **Section editor in Edit Campaign page** — when editing a section-builder campaign, the edit page now shows one card per section with editable AI fields (hero: headline/subtext/button text; text: heading/body; banner: heading/subtext; CTA: heading/subtext/button text) instead of the flat headline/image/description fields.
- **Save Section Content** — saves all section field edits and re-renders the email HTML server-side via `BCG_Section_Renderer::render_sections()`, then refreshes the live preview iframe.
- **Per-section Regenerate** — `bcg_regen_campaign_section` AJAX handler regenerates AI content for a single section by UUID, re-renders the full sections HTML, and persists the update.

---

## [1.5.26] — 2026-02-23

### Fixed
- **Credits bug** — `BCG_AI_Manager::refund_credits()` now checks test mode before executing, matching the guard already present in `deduct_credits()`. Previously, when test mode was enabled, deductions were skipped (no-op) but refunds still ran, causing credits to increase on every failed AI generation call (e.g. Gemini geo-restriction).
- **PHP 8.1 compatibility** — replaced all `true|\WP_Error` return type declarations with `bool|\WP_Error` across `class-bcg-campaign.php`, `class-bcg-coupon.php`, `class-bcg-gemini.php`, `class-bcg-openai.php`, and `class-bcg-section-templates-table.php`. The standalone `true` type was introduced in PHP 8.2 and caused a fatal error on PHP 8.1 hosts.

---

## [1.5.25] — 2026-02-23

### Fixed
- **Product card image toggle** — replaced radio buttons with a compact two-button toggle group ([Product] [AI]); hidden radio inputs preserved for JS compatibility.
- **Remove product button** — styled as a red circle icon with no underline (class `bcg-remove-circle`).
- **Campaign editor layout** — full CSS added for `.bcg-editor-columns` (two-column grid), `.bcg-editor-preview` (sticky), `.bcg-preview-panel`, `.bcg-preview-header`, and `.bcg-actions-bar` (fixed bottom, box-shadow, gap from content).
- **AI image error message** — Gemini "Image generation is not available in your country" error now returns a human-readable message explaining it's a regional restriction with an alternative action.
- **Dashboard action buttons** — Duplicate and Delete buttons now use `bcg-btn-sm bcg-btn-secondary` matching the Edit/Preview buttons so all actions are the same height and style.
- **Mailing list** — AJAX success handler now handles `response.success = false` (e.g. API key not configured) by showing the error message in the dropdown instead of leaving it stuck on "Loading…".
- **Scroll to top on notice** — `showNotice()` now scrolls to the top of the page for success and error notices so the message is always visible.

---

## [1.5.24] — 2026-02-23

### Fixed
- **Field layout** — all `bcg-field-with-regen` groups now lay out as a flex row; input/textarea fills remaining space and the Regenerate button sits flush to its right. Affects Subject Line, Preview Text, Main Headline, Main Description, Coupon Code, Discount Display Text, and all product Headline / Description fields.
- **Device preview toggle** — desktop/mobile toggle in the campaign editor now correctly adds/removes class `bcg-preview-mobile` (was `bcg-preview-mobile-size`) so the existing CSS rule that constrains the iframe to 390 px actually fires.
- **Template mini-cards** — campaign editor template strip now has full CSS; mini-cards render with correct swatch, name, hover, and active states instead of plain square boxes.
- **Product card image** — constrained to 96 × 96 px with `object-fit: cover`; no longer fills its column with an oversized image.
- **Show Buy Button** — replaced plain checkbox with the inline custom toggle component to match the rest of the plugin UI.
- **Remove product button** — now icon-only; "Remove" text label hidden.
- **Date pickers** — coupon expiry and schedule modal date inputs changed to `type="date"` (native browser date picker); resolves the "does nothing on click" bug caused by jQuery UI datepicker initialising on hidden modal elements.
- **Schedule modal** — body and footer now have proper padding (20 px) via the new `.bcg-modal-body` / `.bcg-modal-footer` CSS rules.
- **Mailing list load error** — AJAX error handler now populates the list dropdown with a human-readable error message instead of leaving the user stuck on "Loading…".

---

## [1.5.23] — 2026-02-23

### Fixed
- **Responsive email sections** — generated email HTML now includes a `<style>` block with `@media (max-width: 620px)` rules. Section tables carry `class="bcg-s"` and go full-width on mobile; product cells carry `class="bcg-p"` and stack vertically; header nav carries `class="bcg-nav"` and hides on mobile; all images inside sections become fluid. The content wrapper uses `width:100%;max-width:600px` instead of a fixed pixel width.
- **Mobile preview** — reverted the `transform: scale()` approach; the preview iframe is now literally 375 px wide so the email's CSS media queries actually fire and the layout reflows correctly. Dark background + shadow retained for the mobile frame aesthetic.

---

## [1.5.22] — 2026-02-23

### Fixed
- **List section items** — replaced raw JSON textarea with a plain multiline textarea; each line is one list item. Renderer updated to parse by newline with legacy JSON fallback.
- **Footer links** — replaced raw JSON textarea with the standard label + URL repeater (same UX as nav links)
- **Mobile email preview** — instead of resizing the iframe to 375 px and causing horizontal scroll, the 600 px email is now scaled down via `transform: scale(0.625)` so content fits without overflowing. Mobile wrap gets a dark background and the iframe gets a subtle shadow.

---

## [1.5.21] — 2026-02-23

### Fixed
- **Request a Section modal** — replaced native `<select>` with the plugin's custom dropdown component; added proper 24px body padding; footer moved outside scroll area with raised background; intro styled as an accent-bordered info strip; labels tightened; status messages respect border-radius token; fully functional AJAX submit with reset on success

---

## [1.5.20] — 2026-02-23

### Added
- **Help & Documentation page** — new "Help & Docs" menu item under Brevo Campaigns. Two-column layout with sticky sidebar navigation and comprehensive documentation covering every user-facing feature: Dashboard, Creating Campaigns (Step 1 & 2), Template Builder (palette, canvas, settings, all 12 section types), Template Editor, AI & Credits, and Brevo Stats. Includes live search, smooth-scroll sidebar navigation with IntersectionObserver active-state tracking, and a fully styled design matching the existing admin UI.

---

## [1.5.19] — 2026-02-21

### Fixed
- Section Builder palette broken — sections could not be added to the canvas. Root cause: `BCG_Section_Presets::get_all_for_js()` returned a flat array of variants, but the JS `renderPalette()` expected a grouped array of category objects (each with `label`, `icon`, and `variants[]`). Fixed by returning the grouped format directly from `get_all()`.

---

## [1.5.18] — 2026-02-21

### Fixed
- **Palette section categories no longer accordion** — Category group headers are now plain non-interactive labels instead of collapsible buttons. All variant cards are always visible. The chevron icon and click-to-collapse/expand behaviour have been removed.

---

## [1.5.17] — 2026-02-21

### Security
- **Settings page restricted to `info@redfrogstudio.co.uk`** — The Settings submenu item is now only registered (and therefore visible) for the WordPress account with that exact email address. Any attempt to access the settings URL directly from another account is blocked with `wp_die()`.

---

## [1.5.16] — 2026-02-21

### Fixed
- **Palette accordion starts expanded** — All section category groups in the palette now open by default so variant cards are immediately visible and clickable. Groups can still be collapsed/expanded by clicking the category header.

### Added
- **Button Border Radius** — Hero, CTA, and Products sections now have a `Button Border Radius (px)` range slider (0–30 px, default 4). The hardcoded `4px` in the renderer is replaced with the field value.
- **Button Text Colour** — Products section now has a `Button Text Colour` colour picker (default `#ffffff`). Previously the button text colour was hardcoded white.

---

## [1.5.15] — 2026-02-21

### Added
- **Request a Section** — New "Request a Section" button in the Section Builder toolbar opens a modal form where users can choose a section type, describe what they need, and send the request directly to Red Frog Studio. The email includes the site URL, admin URL, requester name/email, plugin version, and date. Name and email are pre-filled from the logged-in WordPress user.

---

## [1.5.14] — 2026-02-21

### Added
- **Square Crop Images** — Products section now has a `Square Crop Images` toggle. When on, all product images are rendered at equal width × height with `object-fit:cover` so they tile uniformly regardless of the original image proportions.
- **Image Size (px)** — Companion range slider (80–320 px, default 200) to control the square size. Works independently of column count.

---

## [1.5.13] — 2026-02-21

### Added
- **Heading Size control** — Text Block section now has a dedicated `Heading Size (px)` range slider (14–48 px, default 22) so the heading and body font sizes can be adjusted independently.
- **Line Height control** — Text Block section now has a `Line Height (%)` range slider (100–220, default 170 = 1.7). Controls body text line spacing directly in the settings panel.
- **Separate Padding Top / Bottom** — All sections that previously had a single `Padding (px)` field now have independent `Padding Top` and `Padding Bottom` sliders: Text Block, Banner, CTA, Heading, and List sections.
- **Layout-based section presets** — Complete overhaul of the Section Builder palette. All 36 variants now differ in structural layout (alignment, column count, presence of navigation/button/subtext, compact vs spacious, heading vs body-only), not just colour. Colour-only duplicates removed. 11 categories, 2–5 meaningful variants each.

### Fixed
- **Backward compatibility** — Renderer falls back to the old `padding` value for any existing saved sections that have not yet been updated, so existing templates continue to render correctly.

---

## [1.5.12] — 2026-02-21

### Added
- **Header Text/Link Colour** — Header section now has a `Text / Link Colour` field (colour picker). Controls the logo fallback text colour and all navigation link colours, allowing light text on dark backgrounds.
- **Navigation Links repeater** — The header `nav_links` field is now a user-friendly repeater UI (Label + URL inputs per row, + Add Link / × Remove buttons) instead of raw JSON. Changes serialize to JSON internally and trigger live preview refresh automatically.

### Fixed
- **Nav links field showed raw JSON** — Replaced the `json` type for `nav_links` with a new `links` field type that renders a proper row-based repeater in the settings panel.

---

## [1.5.11] — 2026-02-21

### Added
- **Canvas card inline preview** — each section card on the canvas now has a `visibility` eye button. Clicking it expands an inline preview below the card (scaled iframe, populated via AJAX). The preview auto-refreshes when settings change. Replaces the old settings-panel preview.
- **Sliders everywhere** — all remaining `number` fields in the Section Registry are now `range` sliders with appropriate min/max/step: `logo_width`, `headline_size`, `padding_top/bottom`, `font_size`, `padding`, `width`, `thickness`, `margin_top/bottom`, `height`, and all heading/list padding and font sizes.

### Fixed
- **Dropdown handler accumulation** — `bindSettingsFields` now uses namespaced jQuery events (`.bcgFields`) and calls `$body.off('.bcgFields')` before re-attaching. Switching between sections no longer stacks duplicate handlers, fixing the dropdown toggle that would open and immediately close.
- **Custom dropdown update** — text alignment and all other `select` fields now correctly save when an option is chosen (the sectionId closure was being shadowed by accumulated handlers).
- **Preview AJAX broken** — `updateSectionPreview` was using `self.ajaxUrl` and `self.nonce` which were `undefined` on the JS controller object. Fixed to use `bcg_section_builder.ajax_url` and `bcg_section_builder.nonce` (the WP-localised globals).
- **Product preview not refreshing** — adding/removing products in the product select widget now triggers `debounceSectionPreview`, so the canvas card preview updates live.

---

## [1.5.10] — 2026-02-21

### Added
- **Red Flowbite-style range slider** — `<input type="range">` fields now use a fully custom CSS slider: gradient track filled in red (`#e63529`) up to the current value via `--range-progress` CSS variable, 16px red thumb with grow-on-hover animation, cross-browser `-webkit` and `-moz` support. Value indicator displayed in red bold text.
- **Custom dropdowns for all select fields** — settings panel `select` fields now use the same `.bcg-select-wrapper / .bcg-select-trigger / .bcg-select-menu` custom dropdown used everywhere else in the plugin. Options position with `fixed` coords to escape `overflow:hidden` ancestors.
- **Live per-section preview** — selecting a section in the canvas now shows a scaled live preview iframe at the bottom of the settings panel. The preview auto-updates (350ms debounce) whenever any setting is changed (sliders, colours, text, dropdowns).

### Fixed
- **Product tags gap** — the CSS container rule was using the wrong class name (`.bcg-manual-product-tags` instead of `.bcg-sb-product-tags`), so the `flex + gap` layout never applied. Fixed — selected products now display with correct spacing.

---

## [1.5.9] — 2026-02-21

### Fixed
- **Settings panel crash** — `case 'range':` in `renderField` used undeclared `html` variable and wrong variable name `val` (should be `value`), throwing a ReferenceError that silently crashed the settings panel on every section click.

---

## [1.5.8] — 2026-02-21

### Added
- **Font size sliders** — range slider controls for font sizes across all text-bearing sections: Hero (subtext font size, button font size + padding), Banner (heading + subtext font size), CTA (heading + subtext + button font size + button padding H/V), Products (title, description, price, button font size + button padding H/V).
- **Text alignment** — new `text_align` select field added to Banner, Products, and List sections (left / centre / right).
- **Product gap control** — Products section now has a `product_gap` range slider (0–40 px) to control spacing between product cells.
- **Columns slider** — Products `columns` field changed from a dropdown to a range slider (1–3).
- **Price range fix** — variable products in the Products section now display "from £X" (minimum variation price) instead of "£min–£max".
- **Icons in palette cards** — colour swatches replaced with the section type's Material Icon for cleaner palette appearance.
- **26 new preset variants** — 5 headers (Split Navigation, Minimal White, Forest Green, Luxury Gold, Ocean Blue), 3 hero banners (Forest Green, Gold Luxury, Image Background), 2 headings (Minimal, Accent Background), 2 text blocks (Warm Tint, Accent Border), 5 product layouts (3-Column Grid, Minimal Light, Warm Cream, Featured Single, Forest Grid), 3 CTA (Minimal White, Warm Gold, Forest Green), 3 coupons (Clean White, Green Success, Premium Black), 3 dividers/spacers (Thick Dark, Medium Grey, Extra Small Spacer).

### Fixed
- **Product tag gap** — selected product tags in the section settings widget now have `gap: 6px` between them and smaller line-height.
- **Product search input gap** — added top margin above the product search input bar.

---

## [1.5.7] — 2026-02-21

### Added
- **"My Templates" in campaign wizard** — Section 5 (Email Template) now shows a "My Templates" panel below the built-in template grid. Templates saved in the Template Builder are loaded via AJAX and displayed as clickable cards. Selecting one sets `builder_type = sections` and stores the `section_template_id`; clicking again or selecting a flat template deselects it.
- **Section template generation flow** — when a "My Template" is selected and the user clicks Generate Campaign, the handler: (1) loads the saved section template's JSON from the database; (2) injects the campaign's coupon code, discount text, and expiry into any Coupon-type sections automatically; (3) fills selected campaign products into any Products sections that have no IDs set; (4) runs `BCG_Section_AI::generate_all()` to populate AI-capable sections (Hero, Text, Banner, CTA, Products) using the chosen tone, theme, and language; (5) renders sections to email-safe HTML via `BCG_Section_Renderer`; (6) persists `builder_type`, `sections_json`, `section_template_id`, and rendered `template_html` to the campaign.
- **`bcg_get_section_templates` AJAX endpoint** — returns saved Template Builder templates for the campaign wizard My Templates panel.
- **`builder_type`, `sections_json`, `section_template_id`** added to `BCG_Campaign::UPDATABLE_CAMPAIGN_FIELDS` and `create_draft()` so these fields are properly persisted and sanitised.

---

## [1.5.6] — 2026-02-21

### Fixed
- **Load Template and Preview modals not rendering as popups** — `.bcg-modal` had no CSS definition; the modal divs rendered as in-flow elements at the bottom of the page rather than as fixed overlays. Added full modal base CSS: `.bcg-modal` (fixed inset, flex centre), `.bcg-modal-overlay` (semi-transparent backdrop with blur), `.bcg-modal-content` (raised surface, shadow, border-radius), `.bcg-modal-header`, `.bcg-modal-close`. Both the Load Template and Preview Email modals now open as proper centred pop-ups.

### Changed
- **Palette accordion — closed by default** — all category groups now start collapsed (`aria-expanded="false"`, `expand_more` chevron, `.bcg-sb-palette-variants-collapsed` added on render). Click a category header to expand its variants.
- **Palette accordion — exclusive (one open at a time)** — opening any category group automatically closes the previously open group, keeping the palette compact.

---

## [1.5.5] — 2026-02-21

### Changed
- **Renamed "Section Builder" → "Template Builder"** — the admin menu item, page title, help tab headings, and URL slug (`bcg-section-builder` → `bcg-template-builder`) are all updated to "Template Builder". Internal file names and script handles are unchanged.
- **Palette panel now scrolls independently** — `.bcg-sb-palette` changed from `overflow: hidden` to `overflow-x: hidden; overflow-y: auto` with `max-height: calc(100vh - 120px)` and `position: sticky; top: 32px`. Accordion category groups remain expanded by default; the panel scrolls within the viewport so all categories are reachable without scrolling the entire page.

---

## [1.5.4] — 2026-02-21

### Fixed
- **`bcg_section_templates` table missing on update** — the table was only created during plugin activation. Sites that updated by replacing plugin files without re-activating never had the table, causing a fatal DB error in the Section Builder. `maybe_upgrade()` now runs a `dbDelta` for the table when the stored version is older than 1.5.3, and `BCG_Plugin::run()` triggers `maybe_upgrade()` on every page load whenever the stored `bcg_version` option is behind the current `BCG_VERSION` constant.

### Changed
- **Products field — AJAX product picker** — the `product_ids` field in the Section Builder Products section settings panel is now a full AJAX product search widget (same UI pattern as the campaign builder) instead of a plain text input. Searching for a product by name shows a dropdown of results with thumbnail and price; selected products appear as removable tag chips. Product display metadata (name, image URL) is stored in `_product_meta` alongside the comma-separated `product_ids` value so tags survive panel re-renders.

---

## [1.5.3] — 2026-02-21

### Fixed
- **Section Builder palette empty** — `presets` was never initialised in the `BCGSectionBuilder` JS object. `bcg_section_builder.presets` was passed from PHP but not assigned to `this.presets`, so `renderPalette()` always received `undefined` and showed "No sections available". Added `presets: bcg_section_builder.presets || []` to the object state definition.
- **PHP template tags in JS** — four `<?php esc_attr_e(…) ?>` calls were embedded in `bcg-section-builder.js` inside JS string literals. Because `bcg-section-builder.js` is a static `.js` file (not parsed by PHP), these tags rendered literally as tooltip text. Replaced with i18n lookups (`self.i18n.move_up`, `self.i18n.move_down`, `self.i18n.edit_settings`, `self.i18n.remove`) backed by English fallback strings; corresponding keys added to the `bcg_section_builder` localisation array.

### Added
- **Section Builder User Guide** (contextual help) — four help tabs now appear in the WordPress screen Help menu (top-right) on the Section Builder page: *Overview*, *Section Types*, *AI Generation*, and *Saving & Loading*. Each tab explains the relevant workflow in plain language, including a complete section-type reference table. Implemented via `add_section_builder_help_tabs()` hooked to `load-{page_hook}`.

---

## [1.5.2] — 2026-02-21

### Fixed
- **Duplicate dropdowns in Template Editor** — `bcg-settings.js` (loaded globally) and `bcg-template-editor.js` both ran their own custom-select initializer with different guard keys, causing every `<select>` on the template editor to be wrapped twice (two visible dropdowns). Template editor now delegates to `window.bcgInitCustomSelects` from the global script, which is guarded to run only once per element.
- **Navigation toggle unresponsive after first click** — the toggle checkbox was `width:0;height:0`, so only clicking the adjacent label text toggled it. Changed to `width:100%;height:100%;z-index:1` so the invisible checkbox covers the full pill area; the visual thumb now has `pointer-events:none`.
- **Section Builder header misaligned** — `plugin-header.php` was included inside `.bcg-wrap` instead of outside it (like all other pages), causing the brand bar to appear inset rather than flush with the page edge.

---

## [1.5.1] — 2026-02-21

### Added
- **Pre-built section variant library** (`BCG_Section_Presets`) — ~30 curated section variants across 13 categories (Header, Hero, Heading, Text, Banner, Products, List, CTA, Coupon, Image, Divider, Spacer, Footer), each shipping with fully-configured settings so blocks look great the moment they land on the canvas
- **2 new section types**: `heading` (standalone headline with accent line, configurable alignment and size) and `list` (bullet, numbered, checkmark, or plain list with AI-editable items) — both added to the registry, renderer, and presets library
- **Categorised accordion palette** — Section Builder left panel now shows variants grouped by category with expand/collapse accordion headers; clicking any variant card adds that styled section to the canvas (replacing the previous flat list of abstract type buttons)
- **Colour swatch per variant** — each palette card shows a small colour chip (`indicator_color`) giving an instant visual cue of the variant's background/style

### Changed
- `addSection()` now accepts an optional `presetSettings` argument; preset values are merged over type defaults so existing template load/generate flows are unaffected

---

## [1.5.0] — 2026-02-21

### Added
- **Section Builder** — new admin page (`Brevo Campaigns → Section Builder`) with a three-panel layout (palette, canvas, settings) for composing email templates from 11 reusable section types
- **11 section types**: Header, Hero/Banner, Text Block, Image, Products, Banner, Call to Action, Coupon, Divider, Spacer, Footer — each with a fully defined field schema and email-safe renderer
- **Drag-to-reorder canvas** — jQuery UI Sortable for section cards; move-up/move-down buttons for keyboard accessibility
- **Live settings panel** — clicking any canvas card opens its fields (text, textarea, number, colour picker, toggle, select, image picker, JSON) in the right panel with real-time preview updates (300ms debounce)
- **Named section templates** — save/load/delete named templates persisted in the new `bcg_section_templates` database table
- **AI generation** — "Generate All with AI" fills all `has_ai` sections in one click; per-section AI regeneration button; uses existing OpenAI integration
- **3 new OpenAI methods**: `generate_text_block()`, `generate_banner_text()`, `generate_cta_text()` for Section Builder AI generation
- **Email-safe HTML renderer** (`BCG_Section_Renderer`) — table-based layout with fully inlined CSS; no external stylesheets or class names in output for maximum email client compatibility
- **7 new AJAX endpoints**: `bcg_sb_preview`, `bcg_sb_save_template`, `bcg_sb_get_templates`, `bcg_sb_load_template`, `bcg_sb_delete_template`, `bcg_sb_generate_all`, `bcg_sb_generate_section`
- **DB migration** — `bcg_section_templates` table created on activation; `builder_type`, `sections_json`, `section_template_id` columns added to `bcg_campaigns` via `maybe_upgrade()`
- Section Builder styles integrated into existing `bcg-admin.css` design system

---

## [1.4.0] — 2026-02-21

### Added
- **Complete admin UI redesign** — dark premium SaaS aesthetic across all plugin pages; custom CSS design system with variables, toggle switches, radio cards, and refined typography
- **10 new email templates** — Classic, Feature, Dark, Cards, Compact, Text-Only, Grid, Centered, Full-Width, Alternating; each with distinct heading fonts loaded via Google Fonts
- **Template picker on New Campaign wizard** — responsive grid of clickable template cards with visual previews; chosen template is saved with the campaign
- **Custom dropdown component** — all native `<select>` elements across every admin page (Settings, New Campaign, Edit Campaign, Stats) replaced with a bespoke keyboard-accessible custom dropdown, loaded globally on all BCG pages
- **Category toggle switches** — Filter by Category on the New Campaign wizard uses compact toggle switches instead of plain checkboxes
- **Replace-product button** — each product card in the Preview Products grid shows a circular refresh icon on hover; clicking it fetches a different product from the same source/category (excluding all already-shown products) and swaps the card in-place with a fade animation
- **Template editor overhaul** — header settings panel, colour grid, section overlays with drag-to-reorder, sticky preview panel, desktop/mobile device toggle, Google Fonts integration

### Fixed
- **Preview Products spinner not visible** — `.bcg-spinner` CSS class was missing; now defined alongside `.bcg-btn-spinner`
- **Preview Products ignoring changed settings** — JS was sending `count`/`source` keys but PHP read `product_count`/`product_source`; parameter names now match
- **Product images too large in preview/search** — added `object-fit: cover` containers with fixed dimensions and `!important` overrides for both the search-results dropdown (40×40 px) and the preview grid (110 px height)
- **Product preview images now use thumbnail size** — `format_product_preview()` requests WordPress `thumbnail` instead of `medium` for admin UI; email rendering continues to use `medium` at render time

---

## [1.3.8] — 2026-02-20

### Fixed
- **"Only one of Sender ID or Sender Email can be passed"** — v1.3.7 was passing both `id` and `name`+`email` in the sender payload. The Brevo API requires exactly one: either `{id}` alone or `{name, email}` alone. When a sender ID is resolved, the payload now contains only `{id}`; otherwise `{name, email}`.

---

## [1.3.7] — 2026-02-20

### Fixed
- **"Sender is invalid / inactive" — complete resolution** — the v1.3.6 fix only auto-fetched a verified sender when the stored email was completely empty or malformed. The real-world failure was that a syntactically valid but non-verified email was already stored, so the auto-fetch never triggered. `ensure_brevo_campaign()` now **always** calls `GET /senders` before every send operation and resolves the sender by: (1) email match, (2) stored ID match, (3) first verified sender as fallback. The resolved sender (with correct Brevo sender `id`) is then persisted to the option and passed in the API payload, guaranteeing Brevo can identify the sender regardless of what was previously stored.

---

## [1.3.6] — 2026-02-20

### Fixed
- **"Sender is invalid / inactive" on Send Test, Create in Brevo, Send Now** — the Brevo API requires the sender to match a verified sender in the account. Three issues combined to cause this: (1) the sender `id` was not stored in the `bcg_brevo_sender` option when a verified sender was selected in Settings, meaning Brevo had to match by email alone and could fail on any formatting discrepancy; (2) the sender `id` was not included in the campaign creation/update API payload even when present; (3) if no sender was configured at all (e.g. fresh install or upgrade from pre-v1.3.2), the code fell back to legacy free-text options or WordPress admin email which are not verified Brevo senders. Fixed by: storing `id` alongside `name`/`email` in the option JSON, including `id` in the Brevo API sender payload, and auto-fetching the first verified Brevo sender at send time if nothing is configured (saves automatically to the option for future use).

---

## [1.3.5] — 2026-02-20

### Fixed
- **Template editor — content disappears on font change** — `buildSectionOverlays()` was auto-populating `templateSettings.section_order` from the DOM on every preview render. This caused `reorder_sections()` to be called on subsequent updates (e.g. changing the heading font), which could drop structural HTML sections and break the email layout. Section order in `templateSettings` is now only written when the user explicitly moves, duplicates, or deletes a section; overlay positioning uses a local `this.sectionOrder` instead.
- **Template editor — massive gap below email preview** — `autoSizeIframe()` measured `body.scrollHeight` while the iframe still had its previous height, causing `body { height: 100% }` in email templates to inflate the measured height far beyond actual content. Iframe is now temporarily collapsed to 1 px before measuring so `scrollHeight` reflects true content height.
- **Template settings JSON corruption** — `sanitize_text_field()` was applied to the raw JSON settings string in three AJAX handlers (preview template, update template, save campaign). This function strips HTML tags and collapses whitespace, both of which can corrupt JSON values. Replaced with `wp_unslash()` only; individual values are already escaped downstream by `esc_attr()`, `esc_url()`, etc. in `apply_settings()`.

---

## [1.3.4] — 2026-02-20

### Added
- **Heading font control** — Typography tab now has separate "Heading Font" and "Body Font" selectors; heading font applies to the main campaign headline independently of the body text
- **Per-template heading fonts** — each template ships with its own distinct default heading font (e.g. DM Serif Display for Classic, Bebas Neue for Feature, Cinzel for Luxury Centered)
- `{{setting_heading_font_family}}` token added to all 10 email templates, replacing hardcoded headline font-family values

### Fixed
- **Cards template** — conditional Handlebars expression `{{#if ...}}` inside an inline CSS `style` attribute (padding-bottom) was rendered as literal code; replaced with a static value
- **Text-only template** — `{{campaign_image}}` was output as raw text content instead of an `<img>` src attribute
- **Text-only template** — unconditional `{{store_name}}` paragraph always rendered regardless of logo visibility; removed

---

## [1.3.3] — 2026-02-20

### Changed
- **Complete email template redesign** — all 10 templates rebuilt with modern aesthetics, Google Fonts, and distinct visual identities:
  - **Default** — "Refined Premium": DM Serif Display headline, solid-fill pill coupon, bold top accent bar
  - **Feature** — "Hero Spotlight": Bebas Neue headline in a full-width primary colour band above hero, dramatic visual hierarchy
  - **Reversed** — "Midnight Luxury": dark mode (deep charcoal), Cormorant Garamond, gold/primary accents, dark coupon card
  - **Cards** — "Elevated Cards": DM Sans, each section as a separate white card floating on grey, pill coupon
  - **Full-width** — "Bold & Vivid": Oswald, full-bleed colour header and headline band, squared-off squared coupon block
  - **Alternating** — "Editorial Magazine": Libre Baskerville + Open Sans, top/bottom accent bars, italic coupon code
  - **Grid** — "Modern Commerce Grid": Nunito, primary accent stripe under header, pill coupon, cool grey outer
  - **Centered** — "Luxury Centered": Cinzel + Lato, rectangular framed card, decorative accent line, zero border-radius elegance
  - **Compact** — "Smart Newsletter": Merriweather, left-aligned pull-quote headline, horizontal inline coupon
  - **Text-only** — "Literary Elegance": Cormorant Garamond throughout, typographic coupon, parchment background
- Replaced dashed-border coupon boxes with filled pill designs across all templates
- All templates now include Google Fonts with robust email client fallbacks and MSO VML button compatibility

---

## [1.3.2] — 2026-02-20

### Added
- Brevo verified senders dropdown — replaces free-text sender name/email fields with a dropdown populated from Brevo's verified senders API
- Template picker on New Campaign wizard (Step 1) — visual grid of all available templates
- Template picker strip on Edit Campaign page (Step 2) — compact template chooser above preview, applies template on click
- `brevo_url` returned in Create in Brevo response — "View in Brevo" link now works
- Store currency (from WooCommerce) added to all AI system prompts — AI-generated copy now uses correct currency symbol instead of defaulting to $
- Currency symbol prepended to product prices in AI prompt data
- Inline spinner (`.bcg-btn-spinner`) for AJAX button loading states

### Changed
- Sender settings stored as single JSON option `bcg_brevo_sender` (backward-compatible with legacy `bcg_brevo_sender_name`/`bcg_brevo_sender_email`)
- Campaign generation now uses selected template's HTML and settings from the template registry
- Save campaign handler applies new template HTML/settings when `template_slug` changes
- `setButtonLoading()` now prepends a CSS spinner instead of spinning dashicons

### Removed
- Dashicons from all action buttons (Generate, Regenerate, Save, Preview, Send, Schedule, etc.)
- `.bcg-spin-icon` CSS class (replaced by `.bcg-btn-spinner`)

### Fixed
- "Sender is invalid / inactive" error when sending test emails or creating Brevo campaigns
- "Nothing happens" when clicking Create in Brevo (sender validation was blocking)
- AI-generated email copy using wrong currency ($) instead of store currency

---

## [1.0.0] — Unreleased

### Added
- Initial plugin release
- Campaign wizard: product selection with Best Sellers, Least Sold, Latest, and Manual modes
- Category filtering for product selection
- OpenAI GPT-4o and GPT-4o Mini integration for campaign copy generation
- Google Gemini 1.5 Pro and Flash integration for AI image generation
- Per-field regeneration with ↻ Regenerate buttons
- Live HTML email preview in campaign editor
- Draggable/sortable product repeater with add/remove support
- WooCommerce coupon auto-generation with AI discount suggestions
- Fully customisable HTML email template (visual editor + raw HTML + live preview)
- Brevo API integration: create, schedule, and send campaigns
- Brevo campaign stats dashboard
- Stripe-powered credit top-up with three selectable packs
- Credit balance widget in admin bar
- Per-generation credit deduction with transaction log
- API key management with connection testing
- AI model selection with pricing reference table
- Settings tabs: API Keys, AI Models, Brevo, Stripe, Defaults
- GitHub repository initialisation with wiki, issue templates, and README

---

[Unreleased]: https://github.com/dompl/brevo-campaign-generator/compare/v1.5.7...HEAD
[1.5.7]: https://github.com/dompl/brevo-campaign-generator/compare/v1.5.6...v1.5.7
[1.5.6]: https://github.com/dompl/brevo-campaign-generator/compare/v1.5.5...v1.5.6
[1.5.5]: https://github.com/dompl/brevo-campaign-generator/compare/v1.5.4...v1.5.5
[1.5.4]: https://github.com/dompl/brevo-campaign-generator/compare/v1.5.3...v1.5.4
[1.5.3]: https://github.com/dompl/brevo-campaign-generator/compare/v1.5.2...v1.5.3
[1.5.2]: https://github.com/dompl/brevo-campaign-generator/compare/v1.5.1...v1.5.2
[1.5.1]: https://github.com/dompl/brevo-campaign-generator/compare/v1.5.0...v1.5.1
[1.5.0]: https://github.com/dompl/brevo-campaign-generator/compare/v1.4.0...v1.5.0
[1.4.0]: https://github.com/dompl/brevo-campaign-generator/compare/v1.3.8...v1.4.0
[1.3.8]: https://github.com/dompl/brevo-campaign-generator/compare/v1.3.7...v1.3.8
[1.3.7]: https://github.com/dompl/brevo-campaign-generator/compare/v1.3.6...v1.3.7
[1.3.6]: https://github.com/dompl/brevo-campaign-generator/compare/v1.3.5...v1.3.6
[1.3.5]: https://github.com/dompl/brevo-campaign-generator/compare/v1.3.4...v1.3.5
[1.3.4]: https://github.com/dompl/brevo-campaign-generator/compare/v1.3.3...v1.3.4
[1.3.3]: https://github.com/dompl/brevo-campaign-generator/compare/v1.3.2...v1.3.3
[1.3.2]: https://github.com/dompl/brevo-campaign-generator/compare/v1.3.1...v1.3.2
[1.0.0]: https://github.com/dompl/brevo-campaign-generator/releases/tag/v1.0.0
