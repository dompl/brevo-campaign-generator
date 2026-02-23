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
      ├── Section Builder (page-section-builder.php)
      │       │
      │       ▼
      │   BCG_Section_Registry ────► Section type definitions + field schemas
      │   BCG_Section_Renderer ────► Email-safe HTML from section JSON
      │   BCG_Section_AI ──────────► Dispatches AI copy per section type
      │   BCG_Section_Presets ─────► Pre-built variant library
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
| `BCG_Activator` | `includes/class-bcg-activator.php` | Create DB tables, set default options |
| `BCG_Deactivator` | `includes/class-bcg-deactivator.php` | Flush rewrite rules, clean transients |

### Admin

| Class | File | Responsibility |
|---|---|---|
| `BCG_Admin` | `includes/admin/class-bcg-admin.php` | Register menus, enqueue scripts/styles, register AJAX handlers |
| `BCG_Settings` | `includes/admin/class-bcg-settings.php` | Settings page registration and save |
| `BCG_Credits` | `includes/admin/class-bcg-credits.php` | Credit balance reads/writes, admin bar widget |
| `BCG_Stats` | `includes/admin/class-bcg-stats.php` | Brevo stats page controller |

### Campaign

| Class | File | Responsibility |
|---|---|---|
| `BCG_Campaign` | `includes/campaign/class-bcg-campaign.php` | CRUD for campaigns and campaign products |
| `BCG_Product_Selector` | `includes/campaign/class-bcg-product-selector.php` | WooCommerce product queries |
| `BCG_Coupon` | `includes/campaign/class-bcg-coupon.php` | WooCommerce coupon generation |
| `BCG_Template` | `includes/campaign/class-bcg-template.php` | Token replacement, HTML rendering |
| `BCG_Template_Registry` | `includes/campaign/class-bcg-template-registry.php` | Named template storage and retrieval |

### Section Builder (v1.5.0+)

| Class | File | Responsibility |
|---|---|---|
| `BCG_Section_Registry` | `includes/campaign/class-bcg-section-registry.php` | Defines all section types: field schemas, defaults, AI flags, icons. Central source of truth for section type metadata. Exposed to JS via `get_all_for_js()`. |
| `BCG_Section_Renderer` | `includes/campaign/class-bcg-section-renderer.php` | Converts sections JSON array to email-client-safe HTML using table-based layout with fully inlined CSS. Max width 600px. |
| `BCG_Section_AI` | `includes/campaign/class-bcg-section-ai.php` | Dispatches AI content generation per section type. Routes to the appropriate `BCG_OpenAI` method based on type slug (`hero`, `text`, `banner`, `cta`, `products`, `coupon` variants, `footer`). |
| `BCG_Section_Presets` | `includes/campaign/class-bcg-section-presets.php` | Library of pre-built structurally-distinct section variants. Variants differ in layout (column count, button presence, alignment, accent line) not only colour. Categorised palette rendered in the builder UI. |
| `BCG_Section_Templates_Table` | `includes/db/class-bcg-section-templates-table.php` | CRUD for `{prefix}bcg_section_templates` table. Stores named, reusable full-email templates created in the Section Builder (title + sections_json). |

### Section Types (registered in `BCG_Section_Registry`)

The following section type slugs are registered. Types marked `has_ai: true` support one-click AI content generation via `BCG_Section_AI`.

| Slug | Label | AI | Notes |
|---|---|---|---|
| `header` | Header | No | Logo, navigation links, background colour |
| `hero` | Hero / Banner | Yes | Headline, subtext, CTA button, background image/colour |
| `text` | Text Block | Yes | Heading, body textarea, font/alignment controls |
| `image` | Image | No | Full-width or constrained image with optional link/caption |
| `products` | Products | Yes | WooCommerce product picker, 1–3 columns, price/button toggles |
| `banner` | Banner | Yes | Bold heading + subtext strip, no button |
| `cta` | Call to Action | Yes | Heading, subtext, and a prominent CTA button |
| `coupon` | Coupon — Classic | Yes | Bordered box with coupon code, headline, offer text, expiry |
| `coupon_banner` | Coupon — Banner | Yes | Full-width dark/coloured strip with coupon code |
| `coupon_card` | Coupon — Card | Yes | Elevated card layout with accent border |
| `coupon_split` | Coupon — Split | Yes | Two-column layout: discount amount left, code right |
| `coupon_minimal` | Coupon — Minimal | Yes | Clean centred layout with dashed border code box |
| `coupon_ribbon` | Coupon — Ribbon | Yes | Dark background with ribbon accent strip |
| `divider` | Divider | No | Horizontal rule, configurable thickness and colour |
| `spacer` | Spacer | No | Fixed-height transparent gap block |
| `heading` | Heading | No | Section heading with optional subtext and accent underline |
| `list` | List | No | Bulleted, numbered, checkmark, or plain list |
| `footer` | Footer | Yes | Footer text, unsubscribe link, footer links array |

### AI

| Class | File | Responsibility |
|---|---|---|
| `BCG_AI_Manager` | `includes/ai/class-bcg-ai-manager.php` | Dispatch AI tasks, deduct credits, handle errors/refunds |
| `BCG_OpenAI` | `includes/ai/class-bcg-openai.php` | OpenAI Chat Completions API client |
| `BCG_Gemini` | `includes/ai/class-bcg-gemini.php` | Google Gemini API client |

### Integrations

| Class | File | Responsibility |
|---|---|---|
| `BCG_Brevo` | `includes/integrations/class-bcg-brevo.php` | Brevo REST API client |
| `BCG_Stripe` | `includes/integrations/class-bcg-stripe.php` | Stripe PaymentIntents + webhook handling |

---

## Admin Pages

| Page | View File | Description |
|---|---|---|
| Dashboard | `admin/views/page-dashboard.php` | Campaign list, quick stats |
| New Campaign | `admin/views/page-new-campaign.php` | Campaign wizard Step 1: configure |
| Edit Campaign | `admin/views/page-edit-campaign.php` | Campaign editor Step 2: edit AI output |
| Section Builder | `admin/views/page-section-builder.php` | Visual drag-and-drop email builder |
| AI Trainer | `admin/views/page-ai-trainer.php` | Store and product context for AI prompts |
| Template Editor | `admin/views/page-template-editor.php` | Legacy HTML template editor |
| Brevo Stats | `admin/views/page-stats.php` | Campaign analytics from Brevo |
| Credits & Billing | `admin/views/page-credits.php` | Balance, top-up, transaction history |
| Settings | `admin/views/page-settings.php` | API keys, models, defaults |
| Help | `admin/views/page-help.php` | Documentation and support |

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
5. Closing via `#bcg-whats-new-close`, `#bcg-whats-new-dismiss`, or `#bcg-whats-new-overlay` sets `localStorage.setItem('bcg_dismissed_version', version)` and hides the modal
6. The version badge (`#bcg-version-badge`) in the plugin header always re-opens the modal regardless of dismissed state

**Version badge** (`.bcg-version-badge`) is rendered in `partials/plugin-header.php` alongside the plugin name. Styled as a pill with accent border; on hover fills with accent colour.

---

## Section Builder Architecture (v1.5.0+)

The Section Builder is a three-panel drag-and-drop email composition tool.

### Panels

- **Left — Palette:** Click-to-add section palette populated from `BCG_Section_Registry::get_all_for_js()` and `BCG_Section_Presets::get_all()`. Sections organised in a categorised grid.
- **Centre — Canvas:** Sortable list of added sections. Each card shows label, type icon, drag handle, and per-section controls (edit, duplicate, delete, AI generate).
- **Right — Settings:** Dynamically-rendered settings panel for the selected section. Field types: `text`, `textarea`, `color`, `range`, `toggle`, `select`, `image`, `date`, `links`, `product_select`.

### Toolbar

- Template name input
- Campaign context: theme text field, Tone dropdown, Language dropdown
- AI Prompt button (opens `#bcg-sb-prompt-modal`)
- Generate with AI button (runs full AI generation pass on all `has_ai` sections)
- Load Template, Preview Email, Save Template, Request a Section buttons

### AI Prompt Modal (`#bcg-sb-prompt-modal`)

Shown when the user clicks the AI Prompt toolbar button. Allows free-form description of the desired email. Combined with AI Trainer context (store description, product notes) before sending to OpenAI. Footer uses `.bcg-whats-new-footer` CSS class for consistent styling. Contains Cancel and Generate with AI buttons; no "Save Prompt" button (saving is implicit on generate).

### Data Flow — Full AI Generation

1. User clicks "Generate with AI" (or "Save & Generate" from AI Prompt modal)
2. JS collects: prompt text, tone, language, campaign theme, current sections array
3. AJAX POST to `bcg_sb_generate_all`
4. PHP iterates sections; for each with `has_ai: true`, calls `BCG_Section_AI::generate()`
5. `BCG_Section_AI` dispatches to the appropriate `BCG_OpenAI` method
6. Credits deducted per generation call
7. Updated sections JSON returned to JS
8. Canvas re-renders all section settings panels with AI-generated values

### Template Persistence

Templates are stored in `{prefix}bcg_section_templates` via `BCG_Section_Templates_Table`:

| Column | Type | Description |
|---|---|---|
| `id` | BIGINT | Auto-increment primary key |
| `title` | VARCHAR(255) | Template display name |
| `sections_json` | LONGTEXT | JSON array of section objects |
| `created_at` | DATETIME | Creation timestamp |
| `updated_at` | DATETIME | Last update timestamp |

### Preview

The Preview modal renders the current sections array via `BCG_Section_Renderer::render()` into an iframe. Supports desktop (full width) and mobile (375px scaled) preview modes.

---

## AI Trainer Page

Introduced in v1.5.x. A standalone menu page (`page-ai-trainer.php`) where the store admin provides:

- **Company / Store Description** — background, values, tone, target audience
- **Product Notes** — key products, ranges, USPs, commonly asked-about items

Saved to `wp_options` as `bcg_ai_trainer_company` and `bcg_ai_trainer_products`. This context is prepended to all AI generation prompts (campaign copy and section AI generation) to produce on-brand results without the user having to re-describe the store in every prompt.

---

## Database Design

See `CLAUDE.md` for full schema. Summary:

| Table | Purpose |
|---|---|
| `bcg_campaigns` | Campaign metadata, template HTML, Brevo campaign ID |
| `bcg_campaign_products` | Products per campaign with AI-generated and custom copy |
| `bcg_credits` | Per-user credit balance |
| `bcg_transactions` | Full audit trail of credit top-ups and usage |
| `bcg_section_templates` | Named email templates from the Section Builder (sections JSON) |

All table names use `$wpdb->prefix` (typically `wp_bcg_*`).

---

## Request Lifecycle — AI Generation

1. User clicks **Generate Campaign →** in Step 1
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
11. JS redirects to Step 2 (Edit Campaign page)

---

## Template Token System

Templates use `{{token}}` double-brace syntax, replaced by `BCG_Template::render()`.

Product blocks use a sub-template (`{{products_block}}`), which is itself rendered iteratively from `bcg_campaign_products` rows.

Conditional blocks use `{{#if condition}}...{{/if}}` syntax, processed before token replacement.

Template settings (colours, fonts, etc.) are stored as JSON in `bcg_campaigns.template_settings` and injected as inline CSS during rendering.

The Section Builder uses a separate rendering pipeline (`BCG_Section_Renderer`) which produces fully inline-CSS email HTML from a sections JSON array, bypassing the token system entirely.

---

## Caching Strategy

| Data | Cache | TTL |
|---|---|---|
| Brevo mailing lists | WP Transient `bcg_brevo_lists` | 1 hour |
| Brevo campaign stats | WP Transient `bcg_stats_{id}` | 15 minutes |
| Brevo campaigns list | WP Transient `bcg_brevo_campaigns` | 15 minutes |
| Generated images (URLs) | Stored in DB permanently | — |

---

## Security Architecture

- All AJAX endpoints: nonce verification + capability check (`manage_woocommerce`)
- API keys: stored in `wp_options`, never exposed in HTML (masked input fields)
- All DB writes: `$wpdb->prepare()` with parameterised queries
- Image uploads: stored in `uploads/bcg/`, validated MIME type
- Stripe webhooks: signature verification via `Stripe\Webhook::constructEvent()`
- No `eval()`, no raw SQL, no `$_GET`/`$_POST` used without sanitisation
