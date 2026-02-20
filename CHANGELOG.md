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

[Unreleased]: https://github.com/dompl/brevo-campaign-generator/compare/v1.3.2...HEAD
[1.3.2]: https://github.com/dompl/brevo-campaign-generator/compare/v1.3.1...v1.3.2
[1.0.0]: https://github.com/dompl/brevo-campaign-generator/releases/tag/v1.0.0
