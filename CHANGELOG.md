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

[Unreleased]: https://github.com/dompl/brevo-campaign-generator/compare/v1.5.1...HEAD
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
