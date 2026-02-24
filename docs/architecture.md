# Architecture Overview

## Plugin Architecture

Brevo Campaign Generator is structured as a classic WordPress OOP plugin following the standard singleton/loader pattern. All classes carry the `BCG_` prefix and are autoloaded via Composer PSR-4.

---

## High-Level Flow

```
WordPress Admin
      │
      ▼
BCG_Admin (menus, enqueues)
      │
      ├── Campaign Wizard (Step 1)
      │       │
      │       ▼
      │   BCG_Product_Selector ──► WooCommerce Products DB
      │   BCG_Coupon ────────────► WooCommerce Coupons
      │
      ├── Campaign Editor (Step 2)
      │       │
      │       ▼
      │   BCG_AI_Manager
      │       ├── BCG_OpenAI ──────► OpenAI API (copy)
      │       └── BCG_Gemini ──────► Gemini API (images)
      │
      ├── Template Builder (page-section-builder.php)
      │       │
      │       ▼
      │   BCG_Section_Registry ────► Section type definitions + field schemas
      │   BCG_Section_Renderer ────► Email-safe HTML from section JSON
      │   BCG_Section_AI ──────────► Dispatches AI copy per section type
      │   BCG_Section_Presets ─────► Pre-built variant library (categorised palette)
      │   BCG_Section_Templates_Table ► Saved template CRUD (bcg_section_templates)
      │
      ├── AI Trainer (page-ai-trainer.php)
      │       │
      │       ▼
      │   wp_options (bcg_ai_trainer_company, bcg_ai_trainer_products)
      │   Injects store context into all AI generation prompts
      │
      ├── Template Editor (page-template-editor.php)
      │       │
      │       ▼
      │   BCG_Template ────────────► Token replacement, HTML rendering
      │   BCG_Template_Registry ───► Named template storage
      │
      ├── Brevo Integration
      │       │
      │       ▼
      │   BCG_Brevo ───────────────► Brevo API
      │
      ├── Credits & Billing
      │       │
      │       ▼
      │   BCG_Credits
      │   BCG_Stripe ──────────────► Stripe API
      │
      └── Stats Dashboard
              │
              ▼
          BCG_Brevo (stats methods) ► Brevo API (cached)
```

---

## Class Responsibilities

### Core

| Class | File | Responsibility |
|---|---|---|
| `BCG_Plugin` | `includes/class-bcg-plugin.php` | Bootstrap, registers all hooks, loads classes |
| `BCG_Activator` | `includes/class-bcg-activator.php` | Create DB tables, set default options, run `maybe_upgrade()` |
| `BCG_Deactivator` | `includes/class-bcg-deactivator.php` | Flush rewrite rules, clean transients |

### Admin

| Class | File | Responsibility |
|---|---|---|
| `BCG_Admin` | `includes/admin/class-bcg-admin.php` | Register menus, enqueue scripts/styles, register all AJAX handlers |
| `BCG_Settings` | `includes/admin/class-bcg-settings.php` | Settings page registration, save, API key resolution (constant vs option) |
| `BCG_Credits` | `includes/admin/class-bcg-credits.php` | Credit balance reads/writes, admin bar widget, Stripe AJAX handlers |
| `BCG_Stats` | `includes/admin/class-bcg-stats.php` | Brevo stats page controller |

### Campaign

| Class | File | Responsibility |
|---|---|---|
| `BCG_Campaign` | `includes/campaign/class-bcg-campaign.php` | CRUD for campaigns and campaign products; supports `builder_type`, `sections_json`, `section_template_id` fields |
| `BCG_Product_Selector` | `includes/campaign/class-bcg-product-selector.php` | WooCommerce product queries (best sellers, least sold, latest, manual) |
| `BCG_Coupon` | `includes/campaign/class-bcg-coupon.php` | WooCommerce coupon generation via `WC_Coupon` |
| `BCG_Template` | `includes/campaign/class-bcg-template.php` | Token replacement, HTML rendering for flat templates |
| `BCG_Template_Registry` | `includes/campaign/class-bcg-template-registry.php` | Named flat template storage and retrieval (10 built-in templates) |

### Template Builder (v1.5.0+)

| Class | File | Responsibility |
|---|---|---|
| `BCG_Section_Registry` | `includes/campaign/class-bcg-section-registry.php` | Defines all 20 section types: field schemas, defaults, AI flags, icons. Central source of truth for section type metadata. Exposed to JS via `get_all_for_js()`. |
| `BCG_Section_Renderer` | `includes/campaign/class-bcg-section-renderer.php` | Converts sections JSON array to email-client-safe HTML using table-based layout with fully inlined CSS. Includes responsive `@media` rules. Max width 600px. |
| `BCG_Section_AI` | `includes/campaign/class-bcg-section-ai.php` | Dispatches AI content generation per section type. Routes to the appropriate `BCG_OpenAI` method based on type slug. Injects AI Trainer context via `BCG_OpenAI::build_system_prompt()`. |
| `BCG_Section_Presets` | `includes/campaign/class-bcg-section-presets.php` | Library of pre-built structurally-distinct section variants grouped by category. Powers the categorised accordion palette in the builder UI. |
| `BCG_Section_Templates_Table` | `includes/db/class-bcg-section-templates-table.php` | CRUD for `{prefix}bcg_section_templates` table. Stores named, reusable full-email templates created in the Template Builder (title + sections_json). |

### Section Types (registered in `BCG_Section_Registry`)

The following section type slugs are registered. Types marked `has_ai: true` support AI content generation via `BCG_Section_AI`.

| Slug | Label | AI | Notes |
|---|---|---|---|
| `header` | Header | No | Logo, navigation links, background colour, text/link colour |
| `hero` | Hero / Banner | Yes | Headline, subtext, CTA button, background image/colour |
| `hero_split` | Hero Split | Yes | Two-column: image side + text panel; switchable left/right placement |
| `text` | Text Block | Yes | Heading, body textarea, font size, line height, alignment controls |
| `image` | Image | No | Full-width or constrained image with optional link and caption |
| `products` | Products | Yes | WooCommerce product picker, 1–3 columns, price/button toggles, square crop option |
| `banner` | Banner | Yes | Bold heading + subtext strip, no button |
| `cta` | Call to Action | Yes | Heading, subtext, and a prominent CTA button |
| `coupon` | Coupon — Classic | Yes | Bordered box with coupon code, headline, offer text, expiry date |
| `coupon_banner` | Coupon — Banner | Yes | Full-width dark strip with coupon code |
| `coupon_card` | Coupon — Card | Yes | Elevated card layout with accent border |
| `coupon_split` | Coupon — Split | Yes | Two-column layout: discount amount left, code right |
| `coupon_minimal` | Coupon — Minimal | Yes | Clean centred layout with dashed border code box |
| `coupon_ribbon` | Coupon — Ribbon | Yes | Dark background with ribbon accent strip |
| `divider` | Divider | No | Horizontal rule; line style: solid, dashed, dotted, or double; configurable thickness and colour |
| `spacer` | Spacer | No | Fixed-height transparent gap block |
| `heading` | Heading | No | Section heading with optional subtext and accent underline |
| `list` | List | Yes | Bullets, numbers, checkmarks, arrows, stars, dashes, hearts, diamonds, or plain; AI-generated heading and items |
| `social` | Social Media | No | SVG icons for 10 platforms; optional logo with position control; independent icon alignment |
| `footer` | Footer | Yes | Footer text, unsubscribe link, footer links array, optional social media icons |

### AI

| Class | File | Responsibility |
|---|---|---|
| `BCG_AI_Manager` | `includes/ai/class-bcg-ai-manager.php` | Dispatch AI tasks, deduct credits, handle errors/refunds, test mode guard |
| `BCG_OpenAI` | `includes/ai/class-bcg-openai.php` | OpenAI Chat Completions API client; all text generation methods |
| `BCG_Gemini` | `includes/ai/class-bcg-gemini.php` | Google Gemini API client; image generation |

### Integrations

| Class | File | Responsibility |
|---|---|---|
| `BCG_Brevo` | `includes/integrations/class-bcg-brevo.php` | Brevo REST API client |
| `BCG_Stripe` | `includes/integrations/class-bcg-stripe.php` | Stripe PaymentIntents + webhook handling |

---

## Admin Pages

| Page | Menu Label | View File | URL Slug | Description |
|---|---|---|---|---|
| Dashboard | Dashboard | `admin/views/page-dashboard.php` | `bcg-dashboard` | Campaign list, quick stats, schedule modal |
| New Campaign | New Campaign | `admin/views/page-new-campaign.php` | `bcg-new-campaign` | 5-step campaign wizard |
| Edit Campaign | (hidden) | `admin/views/page-edit-campaign.php` | `bcg-edit-campaign` | Campaign editor Step 2; not shown in nav |
| Template Builder | Template Builder | `admin/views/page-section-builder.php` | `bcg-template-builder` | Visual drag-and-drop email builder |
| AI Trainer | AI Trainer | `admin/views/page-ai-trainer.php` | `bcg-ai-trainer` | Store and product context for AI prompts |
| Template Editor | Template Editor | `admin/views/page-template-editor.php` | `bcg-template-editor` | Legacy flat HTML template editor |
| Brevo Stats | Brevo Stats | `admin/views/page-stats.php` | `bcg-stats` | Campaign analytics from Brevo |
| Credits & Billing | Credits & Billing | `admin/views/page-credits.php` | `bcg-credits` | Balance, top-up, transaction history |
| Settings | Settings | `admin/views/page-settings.php` | `bcg-settings` | API keys, models, defaults — restricted to RFS admin |
| Help & Docs | Help & Docs | `admin/views/page-help.php` | `bcg-help` | Full in-admin documentation |

### Shared Partials

| Partial | File | Description |
|---|---|---|
| Plugin Header | `admin/views/partials/plugin-header.php` | Brand header with version badge, credit balance, nav links. Included on every page. Also includes the What's New modal partial. |
| What's New Modal | `admin/views/partials/whats-new-modal.php` | Auto-shows on version change via localStorage. Re-openable via `#bcg-version-badge` click. |
| Credit Widget | `admin/views/partials/credit-widget.php` | Inline credit balance display |
| Template Preview | `admin/views/partials/template-preview.php` | Rendered email preview frame |
| Product Card | `admin/views/partials/product-card.php` | Single product card for campaign editor |

---

## What's New Popup System

Introduced in v1.5.33. The popup shows automatically when the plugin is updated.

**Flow:**

1. `plugin-header.php` includes `partials/whats-new-modal.php` on every admin page
2. `bcgData.whats_new.items` is localised from PHP (array of `{ icon, text }` objects)
3. On DOM ready, `bcg-settings.js` checks `localStorage.getItem('bcg_dismissed_version')`
4. If stored version differs from `bcgData.version`, the modal is shown and populated
5. Closing via `#bcg-whats-new-close`, `#bcg-whats-new-dismiss`, or `.bcg-whats-new-overlay` sets `localStorage.setItem('bcg_dismissed_version', version)` and hides the modal
6. The version badge (`#bcg-version-badge`) in the plugin header always re-opens the modal regardless of dismissed state

**Version badge** (`.bcg-version-badge`) is rendered in `partials/plugin-header.php` alongside the plugin name. Styled as a pill with accent border; on hover fills with accent colour.

---

## Template Builder Architecture (v1.5.0+)

The Template Builder is a three-panel drag-and-drop email composition tool. The admin menu label and URL slug are "Template Builder" / `bcg-template-builder`; the internal view file is `page-section-builder.php`.

### Panels

- **Left — Palette:** Click-to-add section palette populated from `BCG_Section_Registry::get_all_for_js()` and `BCG_Section_Presets::get_all()`. Sections organised in a categorised accordion (one group open at a time). Each variant card shows the section type's Material Icon.
- **Centre — Canvas:** Sortable list of added sections. Each card shows the variant name (e.g. "Logo Only"), type icon, drag handle, and per-section controls (edit, duplicate, delete, AI generate, inline preview toggle).
- **Right — Settings:** Dynamically-rendered settings panel for the selected section. Field types: `text`, `textarea`, `color`, `range` (custom red slider), `toggle`, `select` (custom dropdown), `image`, `date`, `links` (label + URL repeater), `product_select` (AJAX product search widget).

### Toolbar

- Template name input
- Campaign context: Theme text field, Tone dropdown, Language dropdown
- Default Settings button (tune icon) — opens modal with global primary colour picker and font selector (12 options, persisted per site)
- AI Prompt button — opens `#bcg-sb-prompt-modal`
- Generate with AI button — runs full AI generation (layout design then copy fill)
- Load Template, Preview Email, Save Template, Request a Section buttons

### AI Prompt Modal (`#bcg-sb-prompt-modal`)

Shown when the user clicks the AI Prompt toolbar button. Features:
- Free-form description textarea with voice input (Web Speech API; continuous mode; pulsing ring animation while recording)
- Saved prompts dropdown (populated from localStorage, max 10, deduplicated)
- "Save & Generate with AI" button — saves prompt and triggers generation
- "Cancel" button

### Data Flow — Full AI Generation

1. User clicks "Generate with AI" (or "Save & Generate" from AI Prompt modal)
2. JS collects: prompt text, tone, language, campaign theme, current sections array
3. AJAX POST to `bcg_sb_build_layout` — OpenAI designs the layout (ordered list of section type slugs based on the prompt)
4. PHP builds section objects from registry defaults for each slug
5. AJAX POST to `bcg_sb_generate_all` — PHP iterates sections; for each with `has_ai: true`, calls `BCG_Section_AI::generate()`
6. `BCG_Section_AI` dispatches to the appropriate `BCG_OpenAI` method
7. Full context stack injected: AI Trainer store context + AI Trainer product context + user prompt
8. Credits deducted per generation call
9. Updated sections JSON returned to JS
10. Canvas re-renders all section settings panels with AI-generated values

### Template Persistence

Templates are stored in `{prefix}bcg_section_templates` via `BCG_Section_Templates_Table`:

| Column | Type | Description |
|---|---|---|
| `id` | BIGINT | Auto-increment primary key |
| `title` | VARCHAR(255) | Template display name |
| `sections_json` | LONGTEXT | JSON array of section objects |
| `created_at` | DATETIME | Creation timestamp |
| `updated_at` | DATETIME | Last update timestamp |

Auto-save runs every 60 seconds when there are unsaved changes; shows a quiet "Auto-saved" indicator.

### Preview

The Preview modal renders the current sections array via `BCG_Section_Renderer::render()` into an iframe. Supports desktop (full width, 600px) and mobile (literal 375px iframe so media queries fire correctly) preview modes.

---

## AI Trainer Page

A standalone menu page (`page-ai-trainer.php`) where the store admin provides:

- **Company / Store Description** — background, values, tone, target audience
- **Product Notes** — key products, ranges, USPs, commonly asked-about items

Saved to `wp_options` as `bcg_ai_trainer_company` and `bcg_ai_trainer_products`. This context is prepended to all AI generation prompts (campaign copy and Template Builder section AI generation) via `BCG_OpenAI::build_system_prompt()` to produce on-brand results without the user having to re-describe the store in every prompt.

---

## Campaign Wizard — 5-Step Flow

Introduced in v1.5.34. The New Campaign page is split into five steps with a step indicator and Next/Previous navigation:

1. **Email Template** — choose a saved Template Builder template (shown above standard flat templates as "My Templates") or a flat HTML template
2. **Campaign Basics** — title (optional; auto-titled "Campaign — {date}" if blank), subject line, mailing list (server-side loaded with 15-minute cache), sender
3. **Products** — source (best sellers / least sold / latest / manual), category filter, number of products, number of campaigns (1–5 bulk generation)
4. **Coupon** — auto-generate toggle, discount type, value, expiry, code prefix
5. **AI & Generate** — tone, language, campaign theme; click Generate Campaign

---

## Database Design

| Table | Purpose |
|---|---|
| `bcg_campaigns` | Campaign metadata, template HTML, Brevo campaign ID, `builder_type`, `sections_json`, `section_template_id` |
| `bcg_campaign_products` | Products per campaign with AI-generated and custom copy |
| `bcg_credits` | Per-user credit balance |
| `bcg_transactions` | Full audit trail of credit top-ups and usage |
| `bcg_section_templates` | Named email templates from the Template Builder (sections JSON) |

All table names use `$wpdb->prefix` (typically `wp_bcg_*`).

The `maybe_upgrade()` method runs on every page load when the stored `bcg_version` option is behind the current `BCG_VERSION` constant, ensuring the `bcg_section_templates` table and new columns are created on plugin file updates (not just activation).

---

## AJAX Endpoints

All AJAX handlers must: verify nonce (`bcg_nonce`), check `manage_woocommerce` capability, sanitise inputs, and return JSON via `wp_send_json_success()` / `wp_send_json_error()`.

### Campaign

| Action | Handler | Description |
|---|---|---|
| `bcg_generate_campaign` | `handle_generate_campaign` | Full campaign generation (copy + images or section AI) |
| `bcg_regenerate_field` | `handle_regenerate_field` | Regenerate a single flat-template field |
| `bcg_regenerate_product` | `handle_regenerate_product` | Regenerate product AI content |
| `bcg_add_product` | `handle_add_product` | Add product to campaign |
| `bcg_preview_products` | `handle_preview_products` | Preview product selection |
| `bcg_search_products` | `handle_search_products` | AJAX product search (name autocomplete) |
| `bcg_save_campaign` | `handle_save_campaign` | Save campaign draft |
| `bcg_delete_campaign` | `handle_delete_campaign` | Delete a campaign |
| `bcg_duplicate_campaign` | `handle_duplicate_campaign` | Duplicate a campaign |
| `bcg_regen_campaign_section` | `handle_regen_campaign_section` | Regenerate a single section in a section-builder campaign |

### Brevo / Sending

| Action | Handler | Description |
|---|---|---|
| `bcg_send_test` | `handle_send_test` | Send test email via Brevo |
| `bcg_create_brevo_campaign` | `handle_create_brevo_campaign` | Push campaign to Brevo |
| `bcg_send_campaign` | `handle_send_campaign` | Send via Brevo |
| `bcg_schedule_campaign` | `handle_schedule_campaign` | Schedule via Brevo |
| `bcg_get_brevo_lists` | `handle_get_brevo_lists` | Fetch Brevo mailing lists |
| `bcg_get_brevo_senders` | `handle_get_brevo_senders` | Fetch verified Brevo senders |

### Template Editor

| Action | Handler | Description |
|---|---|---|
| `bcg_update_template` | `handle_update_template` | Save flat template settings |
| `bcg_preview_template` | `handle_preview_template` | Return rendered flat template HTML |
| `bcg_reset_template` | `handle_reset_template` | Reset flat template to default |
| `bcg_load_template` | `handle_load_template` | Load a named flat template |
| `bcg_generate_coupon` | `handle_generate_coupon` | Create WooCommerce coupon |

### Template Builder

| Action | Handler | Description |
|---|---|---|
| `bcg_sb_preview` | `handle_sb_preview` | Return rendered section HTML for preview |
| `bcg_sb_save_template` | `handle_sb_save_template` | Save a named Template Builder template |
| `bcg_sb_get_templates` | `handle_sb_get_templates` | List saved templates |
| `bcg_get_section_templates` | `handle_get_section_templates` | Return templates for campaign wizard My Templates panel |
| `bcg_sb_load_template` | `handle_sb_load_template` | Load a named template into builder |
| `bcg_sb_delete_template` | `handle_sb_delete_template` | Delete a saved template |
| `bcg_sb_generate_all` | `handle_sb_generate_all` | AI fill all `has_ai` sections |
| `bcg_sb_generate_section` | `handle_sb_generate_section` | AI generate a single section |
| `bcg_sb_build_layout` | `handle_sb_build_layout` | AI design the section layout |
| `bcg_sb_save_global_defaults` | `handle_sb_save_global_defaults` | Persist default font and primary colour |
| `bcg_request_section` | `handle_request_section` | Send a "Request a Section" email to Red Frog Studio |

### Settings & Credits

| Action | Handler | Description |
|---|---|---|
| `bcg_test_api_key` | `handle_test_api_key` | Test any API connection |
| `bcg_stripe_create_intent` | `handle_stripe_create_intent` | Create Stripe PaymentIntent |
| `bcg_stripe_confirm` | `handle_stripe_confirm` | Confirm payment, add credits |
| `bcg_get_credit_balance` | `handle_get_credit_balance` | Refresh credit balance widget |

---

## Request Lifecycle — AI Generation

### Classic (Flat Template) Campaign

1. User clicks **Generate Campaign** in Step 5
2. JS POSTs to `wp_ajax_bcg_generate_campaign`
3. `BCG_Admin::handle_generate_campaign()` is called
4. Nonce verified, capabilities checked, inputs sanitised
5. `BCG_Campaign::create_draft()` creates a campaign DB record
6. `BCG_Product_Selector::get_products()` fetches WooCommerce products
7. `BCG_AI_Manager::generate_campaign_copy()` is called
   - Checks credit balance
   - Deducts credits upfront
   - Calls `BCG_OpenAI` for all copy tasks
   - On error: refunds credits, returns `WP_Error`
8. `BCG_AI_Manager::generate_campaign_images()` is called (if enabled)
   - Deducts credits per image
   - Calls `BCG_Gemini` for each product + main image
   - Saves images to `uploads/bcg/{campaign_id}/`
9. `BCG_Campaign::update()` saves all generated content
10. JSON response with campaign ID returned to JS
11. JS redirects to Edit Campaign page

### Section Template Campaign

1. User selects a "My Template" in Step 1, completes wizard, clicks Generate
2. Handler branches on `section_template_id` being set
3. Saved sections JSON loaded from `bcg_section_templates`
4. Coupon code, discount text, and expiry injected into any Coupon sections
5. Selected products injected into Products sections without pre-set IDs
6. `BCG_Section_AI::generate_all()` fills copy into all `has_ai` sections
7. `BCG_Section_Renderer::render()` produces email-safe HTML
8. `builder_type`, `sections_json`, `section_template_id`, and `template_html` persisted to campaign

---

## Template Token System

Flat templates use `{{token}}` double-brace syntax, replaced by `BCG_Template::render()`.

Product blocks use a sub-template (`{{products_block}}`), rendered iteratively from `bcg_campaign_products` rows.

Conditional blocks use `{{#if condition}}...{{/if}}` syntax, processed before token replacement.

Template settings (colours, fonts, etc.) are stored as JSON in `bcg_campaigns.template_settings` and injected as inline CSS during rendering.

The Template Builder uses a separate rendering pipeline (`BCG_Section_Renderer`) which produces fully inline-CSS email HTML from a sections JSON array, bypassing the token system entirely. Responsive `@media (max-width: 620px)` rules are included in a `<style>` block for email clients that support them.

---

## Caching Strategy

| Data | Cache | TTL |
|---|---|---|
| Brevo mailing lists | WP Transient `bcg_brevo_lists` | 1 hour |
| Brevo campaign stats | WP Transient `bcg_stats_{id}` | 15 minutes |
| Brevo campaigns list | WP Transient `bcg_brevo_campaigns` | 15 minutes |
| Generated images (URLs) | Stored in DB permanently | — |

Mailing lists are now also fetched and rendered server-side on the New Campaign page load using the transient, eliminating the need for an initial AJAX call.

---

## Security Architecture

- All AJAX endpoints: nonce verification (`bcg_nonce`) + capability check (`manage_woocommerce`)
- Settings page restricted to the Red Frog Studio admin email via `is_rfs_admin()` check
- API keys: stored in `wp_options` or read from PHP constants; never exposed in HTML (masked input fields)
- All DB writes: `$wpdb->prepare()` with parameterised queries
- Image uploads: stored in `uploads/bcg/`, validated MIME type
- Stripe webhooks: signature verification via `Stripe\Webhook::constructEvent()`
- No `eval()`, no raw SQL, no `$_GET`/`$_POST` used without sanitisation
- Template settings JSON uses `wp_unslash()` only (not `sanitize_text_field()` which would corrupt JSON)

---

## JavaScript Files

| File | Purpose |
|---|---|
| `admin/js/bcg-campaign-builder.js` | Campaign editor Step 2: state management, live preview, regenerate buttons, product card sorting |
| `admin/js/bcg-section-builder.js` | Template Builder: palette, canvas, settings panel, AI generation, template save/load |
| `admin/js/bcg-template-editor.js` | Flat template editor: visual settings, CodeMirror HTML editor, live preview |
| `admin/js/bcg-settings.js` | Global: custom dropdown init, What's New modal, settings page tabs, API key test |
| `admin/js/bcg-regenerate.js` | AI regeneration handlers (shared between wizard and editor) |
| `admin/js/bcg-dashboard.js` | Dashboard: schedule modal, campaign actions |
| `admin/js/bcg-stripe.js` | Stripe Elements: PaymentIntent flow, credit balance update |
