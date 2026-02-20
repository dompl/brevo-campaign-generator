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

[Unreleased]: https://github.com/dompl/brevo-campaign-generator/compare/v1.3.5...HEAD
[1.3.5]: https://github.com/dompl/brevo-campaign-generator/compare/v1.3.4...v1.3.5
[1.3.4]: https://github.com/dompl/brevo-campaign-generator/compare/v1.3.3...v1.3.4
[1.3.3]: https://github.com/dompl/brevo-campaign-generator/compare/v1.3.2...v1.3.3
[1.3.2]: https://github.com/dompl/brevo-campaign-generator/compare/v1.3.1...v1.3.2
[1.0.0]: https://github.com/dompl/brevo-campaign-generator/releases/tag/v1.0.0
