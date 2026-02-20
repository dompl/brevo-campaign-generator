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
      ├── Template Editor
      │       │
      │       ▼
      │   BCG_Template ────────────► Rendered HTML
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

## Database Design

See `CLAUDE.md` for full schema. Summary:

| Table | Purpose |
|---|---|
| `bcg_campaigns` | Campaign metadata, template HTML, Brevo campaign ID |
| `bcg_campaign_products` | Products per campaign with AI-generated and custom copy |
| `bcg_credits` | Per-user credit balance |
| `bcg_transactions` | Full audit trail of credit top-ups and usage |

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
   - On error → refunds credits, returns `WP_Error`
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
