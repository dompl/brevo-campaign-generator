# CLAUDE.md — Brevo Campaign Generator for WooCommerce

> **Instructions for Claude Code:** This file defines the full specification for building the **Brevo Campaign Generator for WooCommerce** plugin. Read this file completely before writing any code. Follow the architecture, naming conventions, and implementation notes precisely.

---

## Project Overview

**Plugin Name:** Brevo Campaign Generator for WooCommerce  
**Slug:** `brevo-campaign-generator`  
**Version:** 1.0.0  
**Requires:** WordPress 6.3+, WooCommerce 8.0+, PHP 8.1+  
**License:** Proprietary — Red Frog Studio  

This plugin enables WooCommerce store owners to automatically generate, customise, and send Brevo (formerly Sendinblue) email campaigns directly from the WordPress admin. It uses OpenAI GPT for copywriting, Google Gemini for image generation, and Stripe for credit top-ups.

---

## Repository Setup (Do This First)

Before writing any plugin code, initialise the GitHub repository:

```bash
gh repo create red-frog-studio/brevo-campaign-generator --private --description "WooCommerce plugin to generate Brevo email campaigns using AI" --clone
cd brevo-campaign-generator
git init
git checkout -b main
```

Create the following structure before committing any code:

```
brevo-campaign-generator/
├── .github/
│   ├── ISSUE_TEMPLATE/
│   │   ├── bug_report.md
│   │   └── feature_request.md
│   └── PULL_REQUEST_TEMPLATE.md
├── docs/
│   ├── architecture.md
│   ├── api-integrations.md
│   ├── credits-and-pricing.md
│   └── template-editor.md
├── CLAUDE.md               ← this file
├── README.md
├── CHANGELOG.md
├── CONTRIBUTING.md
├── brevo-campaign-generator.php
├── uninstall.php
├── composer.json
├── package.json
└── ... (all plugin files)
```

Push an initial commit with README, CHANGELOG, and folder structure before proceeding with plugin code.

---

## Plugin Architecture

### Directory Structure

```
brevo-campaign-generator/
├── brevo-campaign-generator.php        # Main plugin bootstrap
├── uninstall.php                       # Cleanup on uninstall
├── composer.json
├── package.json
│
├── includes/
│   ├── class-bcg-plugin.php            # Main plugin class, hooks
│   ├── class-bcg-activator.php         # Activation: DB tables, defaults
│   ├── class-bcg-deactivator.php       # Deactivation hooks
│   │
│   ├── admin/
│   │   ├── class-bcg-admin.php         # Admin menu, enqueues
│   │   ├── class-bcg-settings.php      # Settings page
│   │   ├── class-bcg-credits.php       # Credits system + Stripe
│   │   └── class-bcg-stats.php         # Brevo stats dashboard
│   │
│   ├── campaign/
│   │   ├── class-bcg-campaign.php      # Campaign creation/management
│   │   ├── class-bcg-product-selector.php  # Product query logic
│   │   ├── class-bcg-coupon.php        # WooCommerce coupon generator
│   │   └── class-bcg-template.php      # HTML template engine
│   │
│   ├── ai/
│   │   ├── class-bcg-openai.php        # OpenAI GPT integration
│   │   ├── class-bcg-gemini.php        # Google Gemini integration
│   │   └── class-bcg-ai-manager.php    # AI task dispatcher + credit deduction
│   │
│   ├── integrations/
│   │   ├── class-bcg-brevo.php         # Brevo API client
│   │   └── class-bcg-stripe.php        # Stripe payment integration
│   │
│   └── db/
│       ├── class-bcg-campaigns-table.php
│       ├── class-bcg-credits-table.php
│       └── class-bcg-transactions-table.php
│
├── admin/
│   ├── views/
│   │   ├── page-dashboard.php          # Main dashboard
│   │   ├── page-new-campaign.php       # Campaign wizard (Step 1: configure)
│   │   ├── page-edit-campaign.php      # Campaign editor (Step 2: edit AI output)
│   │   ├── page-template-editor.php    # HTML template editor
│   │   ├── page-settings.php           # Plugin settings
│   │   ├── page-credits.php            # Credits & billing
│   │   ├── page-stats.php              # Brevo stats
│   │   └── partials/
│   │       ├── product-card.php
│   │       ├── credit-widget.php
│   │       └── template-preview.php
│   ├── css/
│   │   └── bcg-admin.css
│   └── js/
│       ├── bcg-campaign-builder.js     # Main campaign builder JS
│       ├── bcg-template-editor.js      # Template editor JS
│       ├── bcg-regenerate.js           # AI regeneration handlers
│       └── bcg-stripe.js              # Stripe.js integration
│
├── assets/
│   ├── css/
│   │   └── bcg-email.css               # Email template base CSS
│   └── images/
│       └── default-placeholder.png
│
└── templates/
    └── default-email-template.html     # Default email template
```

---

## Database Schema

Create these tables on activation using `$wpdb->prefix`:

### `bcg_campaigns`
```sql
CREATE TABLE {prefix}bcg_campaigns (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(255) NOT NULL,
    status          ENUM('draft','ready','sent','scheduled') DEFAULT 'draft',
    brevo_campaign_id BIGINT UNSIGNED NULL,
    subject         VARCHAR(255),
    preview_text    VARCHAR(255),
    main_image_url  TEXT,
    main_headline   TEXT,
    main_description TEXT,
    coupon_code     VARCHAR(100),
    coupon_discount DECIMAL(5,2),
    coupon_type     ENUM('percent','fixed_cart') DEFAULT 'percent',
    template_html   LONGTEXT,
    template_settings LONGTEXT,  -- JSON
    mailing_list_id VARCHAR(100),
    scheduled_at    DATETIME NULL,
    sent_at         DATETIME NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### `bcg_campaign_products`
```sql
CREATE TABLE {prefix}bcg_campaign_products (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id     BIGINT UNSIGNED NOT NULL,
    product_id      BIGINT UNSIGNED NOT NULL,
    sort_order      INT DEFAULT 0,
    ai_headline     TEXT,
    ai_short_desc   TEXT,
    custom_headline TEXT,
    custom_short_desc TEXT,
    generated_image_url TEXT,
    use_product_image TINYINT(1) DEFAULT 1,
    show_buy_button TINYINT(1) DEFAULT 1,
    INDEX idx_campaign (campaign_id)
);
```

### `bcg_credits`
```sql
CREATE TABLE {prefix}bcg_credits (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    balance         DECIMAL(10,4) DEFAULT 0.0000,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_user (user_id)
);
```

### `bcg_transactions`
```sql
CREATE TABLE {prefix}bcg_transactions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    type            ENUM('topup','usage','refund') NOT NULL,
    amount          DECIMAL(10,4) NOT NULL,
    balance_after   DECIMAL(10,4) NOT NULL,
    description     VARCHAR(255),
    stripe_payment_intent VARCHAR(255) NULL,
    ai_service      ENUM('openai','gemini-pro','gemini-flash') NULL,
    ai_task         VARCHAR(100) NULL,
    tokens_used     INT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_type (type)
);
```

---

## Admin Menu Structure

Register under a top-level menu: **Brevo Campaigns** with dashicon `dashicons-email-alt2`.

| Submenu | Slug | View |
|---|---|---|
| Dashboard | `bcg-dashboard` | Campaign list, quick stats |
| New Campaign | `bcg-new-campaign` | Campaign wizard |
| Template Editor | `bcg-template-editor` | HTML template builder |
| Brevo Stats | `bcg-stats` | Campaign analytics from Brevo |
| Credits & Billing | `bcg-credits` | Balance, top-up, history |
| Settings | `bcg-settings` | All API keys, defaults |

---

## Settings Page

Organise settings into tabs: **API Keys**, **AI Models**, **Brevo**, **Stripe**, **Defaults**.

### API Keys Tab
- OpenAI API Key (password field, masked)
- Google Gemini API Key (password field, masked)
- Brevo API Key (password field, masked)
- Stripe Publishable Key
- Stripe Secret Key
- Each field has a **Test Connection** button that fires an AJAX call to verify the key

### AI Models Tab

**OpenAI Model** (select):
- `gpt-4o` — GPT-4o (recommended, best quality)
- `gpt-4o-mini` — GPT-4o Mini (faster, lower cost)
- `gpt-4-turbo` — GPT-4 Turbo

**Gemini Model** (select):
- `gemini-1.5-pro` — Gemini 1.5 Pro (best quality, higher cost)
- `gemini-1.5-flash` — Gemini 1.5 Flash (fast, cost-efficient)
- `gemini-2.0-flash-exp` — Gemini 2.0 Flash (experimental)

Show a pricing reference table below the model selectors (static, hardcoded, clearly labelled "approximate costs — check provider for current pricing"):

| Service | Model | Task | Est. Cost |
|---|---|---|---|
| OpenAI | GPT-4o | Per campaign copy (approx 2K tokens) | ~$0.01–$0.05 |
| OpenAI | GPT-4o Mini | Per campaign copy | ~$0.001–$0.005 |
| Gemini | 1.5 Pro | Per image generation | ~$0.01–$0.04 |
| Gemini | 1.5 Flash | Per image generation | ~$0.001–$0.01 |

### Brevo Tab
- Default Mailing List (dropdown populated via Brevo API)
- Default Sender Name
- Default Sender Email
- Campaign Prefix (e.g. `[WC]` prepended to all campaign names)

### Stripe Tab
- Currency (default: GBP)
- Credit Pack options: define 3 top-up packs (credits + price)
  - Pack 1: 100 credits / £5
  - Pack 2: 300 credits / £12
  - Pack 3: 1000 credits / £35
  - All values editable by admin

### Defaults Tab
- Default number of products per campaign (1–10, default: 3)
- Default coupon discount (%)
- Default coupon expiry (days)
- Auto-generate coupon on new campaign (checkbox)

---

## Credits System

### Credit Values
Store credit costs as WordPress options (editable in Settings > AI Models):

```
bcg_credit_cost_openai_gpt4o           = 5      credits per generation
bcg_credit_cost_openai_gpt4o_mini      = 1      credit per generation
bcg_credit_cost_gemini_pro             = 10     credits per image
bcg_credit_cost_gemini_flash           = 3      credits per image
```

**1 credit = £0.05 by default** (this ratio is configurable in settings).

### Credit Widget
Show a persistent credit balance widget in the admin bar and on every plugin page:
```
💳 Credits: 142 | Top Up
```
Clicking "Top Up" opens a modal with the Stripe payment flow.

### Deducting Credits
Before every AI generation call:
1. Check user has sufficient credits
2. If not, show "Insufficient credits" notice with Top Up button
3. If yes, deduct credits and log to `bcg_transactions` table
4. On AI API error, refund the credits

### Stripe Top-Up Flow
1. User selects a credit pack
2. Click "Purchase" → Stripe.js creates a Payment Intent via AJAX (`bcg_stripe_create_intent`)
3. Stripe Elements card form appears inline
4. On success: webhook or AJAX confirms payment, credits added, transaction logged
5. Show success notice with updated balance

---

## Campaign Wizard — Step 1: Configure

File: `admin/views/page-new-campaign.php`

### Section 1: Campaign Basics
- Campaign Title (text input, required)
- Subject Line (text input, with **✨ Generate with AI** button)
- Preview Text (text input, with **✨ Generate** button)
- Mailing List (dropdown, populated from Brevo API)

### Section 2: Product Selection

**Number of products** (1–10, default from settings)

**Product source** (radio):
- Best sellers (sorted by sales count DESC)
- Least sold (sorted by sales count ASC)  
- Latest products (sorted by date DESC)
- Manual selection (show a searchable product picker)

**Filter by category** (multi-select checkbox tree, shows WooCommerce categories — optional)

**Preview selected products** — after choosing source + category, show a "Preview Products" button that loads a preview of which products will be included (AJAX).

### Section 3: Coupon
- Generate coupon automatically? (checkbox, checked by default)
- Discount type: Percentage / Fixed amount
- Discount value (number input, with **✨ Generate suggestion** button)
- Expiry: number of days from today (default: 7)
- Custom coupon code prefix (optional, e.g. `SALE`)

### Section 4: AI Generation Options
- Tone of voice (select): Professional / Friendly / Urgent / Playful / Luxury
- Campaign theme / occasion (text input, optional — e.g. "Black Friday", "Summer Sale")
- Language (select, default from WordPress locale): English / Polish / [other common languages]
- Generate product images with AI? (checkbox — if unchecked, uses WooCommerce product images)
- Image style (shown only if AI images enabled, select): Photorealistic / Studio Product / Lifestyle / Minimalist / Vivid Illustration

**[Generate Campaign →]** button — fires the full AI generation sequence.

---

## Campaign Wizard — Step 2: Edit Campaign

File: `admin/views/page-edit-campaign.php`

This is the main editing interface. Show a **live HTML preview** panel on the right (iframe, updates in real-time as user edits).

### Header Section
- Main headline (textarea, editable) + **↻ Regenerate** button
- Main image (shows generated/selected image) + **↻ Regenerate Image** button
  - Also option: **Use custom image** (media uploader)
- Main description (rich textarea, editable) + **↻ Regenerate** button
- Subject line (editable) + **↻ Regenerate** button

### Coupon Section
Shows only if coupon was generated:
- Coupon code (editable text input)
- Discount display text (editable, e.g. "Get 20% off your order!")
- Expiry date (date picker)
- **↻ Regenerate coupon text** button
- **Regenerate coupon code** button

### Products Repeater

Each product appears as a draggable card (use jQuery UI Sortable for reordering). Each card contains:

```
[Product Image or AI Generated Image]    [Product Title]
                                         [AI Headline — editable textarea]
                                         [↻ Regenerate Headline]
                                         [Short Description — editable textarea]
                                         [↻ Regenerate Description]
                                         [☑ Show Buy Button]
                                         [☑ Use product image | ◉ Use AI image]
                                         [↻ Regenerate Image]
                                         [✕ Remove product]
```

**[+ Add Another Product]** button at the bottom — opens the product picker, lets user select a product, then immediately generates AI content for it (deducts credits).

### Actions Bar (sticky bottom)
- **[← Back to Configuration]**
- **[Save Draft]**
- **[Preview Email]** (opens full HTML preview in modal)
- **[Send Test Email]** (sends to admin email)
- **[Create Campaign in Brevo →]** (creates draft campaign via Brevo API)
- **[Schedule Campaign]** (date/time picker + schedule via Brevo API)
- **[Send Now]** (sends immediately via Brevo API)

---

## AI Generation — Implementation Details

### OpenAI Integration (`class-bcg-openai.php`)

Use the Chat Completions API (`/v1/chat/completions`).

**Methods to implement:**

```php
generate_subject_line( $products, $theme, $tone, $language ) : string
generate_preview_text( $subject, $products ) : string
generate_main_headline( $products, $theme, $tone, $language ) : string
generate_main_description( $products, $theme, $tone, $language ) : string
generate_product_headline( $product, $tone, $language ) : string
generate_product_short_description( $product, $tone, $language ) : string
generate_coupon_discount_suggestion( $products, $theme ) : array  // returns ['value' => 20, 'type' => 'percent', 'text' => 'Get 20% off!']
```

**System prompt base:**
```
You are an expert email marketing copywriter for a WooCommerce e-commerce store.
Write compelling, conversion-focused copy. Be concise. Avoid clichés.
Always respond in {language}. Tone: {tone}.
```

**Temperature:** 0.75 for creative tasks, 0.3 for structured data.

### Gemini Integration (`class-bcg-gemini.php`)

Use the Gemini API for image generation: `https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent`

For image generation tasks, use the `gemini-1.5-pro` or `gemini-1.5-flash` model with image generation capability. Construct prompts that describe the desired product imagery.

**Methods:**
```php
generate_main_email_image( $products, $theme, $style ) : string  // returns image URL or base64
generate_product_image( $product, $style ) : string
```

**Prompt template for product images:**
```
A professional {style} photograph of {product_name}. 
{product_short_description}. 
Clean white/neutral background, high detail, suitable for an e-commerce email campaign.
No text or watermarks.
```

Save generated images to `wp-content/uploads/bcg/[campaign-id]/` and store the URL.

### Error Handling
- All API calls wrapped in try/catch
- Log errors to a custom WP option `bcg_error_log` (last 50 errors, FIFO)
- Show user-friendly error notices in admin with a "View details" expand
- On API timeout (>30s), abort and refund credits

---

## HTML Email Template Engine

### Default Template Structure

The template is stored in the DB per-campaign as `template_html` and as reusable defaults in `templates/default-email-template.html`.

Template uses a token system for dynamic content:

```
{{campaign_headline}}
{{campaign_description}}
{{campaign_image}}
{{coupon_code}}
{{coupon_text}}
{{products_block}}          ← replaced with rendered product grid
{{store_name}}
{{store_url}}
{{logo_url}}
{{unsubscribe_url}}
{{current_year}}
```

**Product block token** — each product renders as:
```html
<table class="bcg-product">
  <tr>
    <td class="bcg-product-image">
      <img src="{{product_image}}" alt="{{product_name}}">
    </td>
    <td class="bcg-product-content">
      <h3>{{product_headline}}</h3>
      <p>{{product_short_desc}}</p>
      {{#if show_buy_button}}
      <a href="{{product_url}}" class="bcg-btn">Buy Now</a>
      {{/if}}
    </td>
  </tr>
</table>
```

### Template Settings (JSON stored in `template_settings`)

```json
{
  "logo_url": "",
  "logo_width": 180,
  "nav_links": [
    { "label": "Shop", "url": "" },
    { "label": "About", "url": "" }
  ],
  "show_nav": true,
  "primary_color": "#e84040",
  "background_color": "#f5f5f5",
  "content_background": "#ffffff",
  "text_color": "#333333",
  "link_color": "#e84040",
  "button_color": "#e84040",
  "button_text_color": "#ffffff",
  "button_border_radius": 4,
  "font_family": "Arial, sans-serif",
  "header_text": "",
  "footer_text": "You received this email because you subscribed to our newsletter.",
  "footer_links": [
    { "label": "Privacy Policy", "url": "" },
    { "label": "Unsubscribe", "url": "{{unsubscribe_url}}" }
  ],
  "max_width": 600,
  "show_coupon_block": true,
  "product_layout": "stacked",
  "products_per_row": 1
}
```

### Template Editor Page (`page-template-editor.php`)

Split into three panels:

**Panel 1 — Visual Settings (left sidebar)**
Tabs: Branding | Layout | Colours | Typography | Navigation | Footer

Each tab shows relevant controls (colour pickers, text inputs, image uploaders).
All changes fire a live preview update via AJAX/postMessage.

**Panel 2 — HTML Editor (centre, optional)**
CodeMirror HTML editor showing the raw template HTML. Syntax highlighted. Changes reflect in preview.
Toggle button: "Switch to Visual / Switch to Code"

**Panel 3 — Live Preview (right)**
Rendered iframe showing the email with current settings applied.
Toggle buttons: Desktop preview / Mobile preview (scales iframe).

**Save options:**
- **Save as Default Template** — saves to plugin option, used for all new campaigns
- **Save to This Campaign** — saves only to current campaign
- **Reset to Default** — restores original template

---

## Brevo Integration (`class-bcg-brevo.php`)

Base URL: `https://api.brevo.com/v3/`

**Methods to implement:**

```php
// Lists
get_contact_lists() : array
get_list( $list_id ) : array

// Campaigns
create_campaign( $data ) : array
update_campaign( $campaign_id, $data ) : array
send_campaign_now( $campaign_id ) : bool
schedule_campaign( $campaign_id, $datetime ) : bool
get_campaigns( $status = 'all', $limit = 50 ) : array
get_campaign( $campaign_id ) : array
get_campaign_stats( $campaign_id ) : array

// Templates
create_template( $data ) : array

// Test
send_test_email( $campaign_id, $email ) : bool
```

**Campaign creation payload:**
```json
{
  "name": "[WC] Campaign Title",
  "subject": "Subject line here",
  "sender": { "name": "Store Name", "email": "store@example.com" },
  "type": "classic",
  "htmlContent": "...full HTML...",
  "recipients": { "listIds": [12] },
  "scheduledAt": "2025-03-01T10:00:00Z"
}
```

---

## Brevo Stats Dashboard (`page-stats.php`)

Fetch and display stats from Brevo API. Cache API responses for 15 minutes using WordPress transients.

**Top-level stats cards:**
- Total campaigns sent
- Average open rate
- Average click rate
- Total emails sent

**Campaigns table:**
| Campaign | Sent Date | Recipients | Opens | Open Rate | Clicks | Click Rate | Unsubscribes | Status |
|---|---|---|---|---|---|---|---|---|

Clicking a row expands to show full stats for that campaign including a link to edit in Brevo.

**Filters:** Date range picker, status filter.

---

## AJAX Endpoints

Register all via `wp_ajax_{action}`:

| Action | Handler | Description |
|---|---|---|
| `bcg_generate_campaign` | `handle_generate_campaign` | Full campaign generation |
| `bcg_regenerate_field` | `handle_regenerate_field` | Regenerate a single field |
| `bcg_regenerate_product` | `handle_regenerate_product` | Regenerate product AI content |
| `bcg_add_product` | `handle_add_product` | Add product to campaign |
| `bcg_preview_products` | `handle_preview_products` | Preview product selection |
| `bcg_save_campaign` | `handle_save_campaign` | Save campaign draft |
| `bcg_send_test` | `handle_send_test` | Send test email via Brevo |
| `bcg_create_brevo_campaign` | `handle_create_brevo_campaign` | Push to Brevo |
| `bcg_send_campaign` | `handle_send_campaign` | Send via Brevo |
| `bcg_schedule_campaign` | `handle_schedule_campaign` | Schedule via Brevo |
| `bcg_update_template` | `handle_update_template` | Save template settings |
| `bcg_preview_template` | `handle_preview_template` | Return rendered HTML |
| `bcg_stripe_create_intent` | `handle_stripe_create_intent` | Create Stripe PaymentIntent |
| `bcg_stripe_confirm` | `handle_stripe_confirm` | Confirm payment, add credits |
| `bcg_get_brevo_lists` | `handle_get_brevo_lists` | Fetch Brevo mailing lists |
| `bcg_test_api_key` | `handle_test_api_key` | Test any API connection |
| `bcg_generate_coupon` | `handle_generate_coupon` | Create WooCommerce coupon |

All AJAX handlers must:
1. Verify `nonce` (`wp_verify_nonce`)
2. Check `current_user_can('manage_woocommerce')`
3. Sanitise all inputs
4. Return JSON via `wp_send_json_success()` or `wp_send_json_error()`

---

## Coupon Generation (`class-bcg-coupon.php`)

```php
create_coupon( $campaign_id, $discount_value, $discount_type, $expiry_days, $prefix ) : string
```

Uses the `WC_Coupon` class. Auto-generates a unique code if none provided. Format: `{PREFIX}{RANDOM6}` e.g. `SALE-A3K9P2`.

Store the coupon ID against the campaign in `bcg_campaigns`.

---

## Security Requirements

- All AJAX nonces: use `bcg_nonce` with `wp_create_nonce('bcg_nonce')`
- API keys stored via `update_option()` with the key name prefixed `bcg_`
- Never output raw API keys in HTML — always mask in settings fields
- All DB queries use `$wpdb->prepare()`
- Sanitise: `sanitize_text_field()`, `wp_kses_post()` for HTML, `absint()` for IDs
- Escape: `esc_html()`, `esc_attr()`, `esc_url()` on all output
- Check `WC_DOING_AJAX` for AJAX requests

---

## JavaScript Architecture

### `bcg-campaign-builder.js`
- Manages the Step 2 campaign editor state
- All editable fields are bound to a JS state object
- On field change → debounced live preview update
- "Regenerate" buttons → AJAX call → update field → update preview
- Product cards use jQuery UI Sortable → on sort, update `sort_order` via AJAX

### `bcg-template-editor.js`
- Manages the template editor
- Settings panel controls → update a `templateSettings` JS object
- On any change → debounce 300ms → POST to `bcg_preview_template` → update iframe src

### `bcg-stripe.js`
- Initialise Stripe Elements
- Handle payment intent flow
- On success → call `bcg_stripe_confirm` AJAX → update credit balance widget

---

## Code Standards

- Follow WordPress Coding Standards (WPCS)
- All PHP classes use prefix `BCG_`
- All option names use prefix `bcg_`
- All hooks use prefix `bcg_`
- All CSS classes use prefix `bcg-`
- Comment all public methods with PHPDoc
- Use `WP_Error` for error returns
- Never use `die()` — use `wp_die()` where needed
- Text domain: `brevo-campaign-generator`
- All user-facing strings wrapped in `__()` or `_e()`

---

## Plugin Bootstrap (`brevo-campaign-generator.php`)

```php
<?php
/**
 * Plugin Name: Brevo Campaign Generator for WooCommerce
 * Plugin URI: https://github.com/red-frog-studio/brevo-campaign-generator
 * Description: Automatically generate and send Brevo email campaigns from WooCommerce using AI.
 * Version: 1.0.0
 * Author: Red Frog Studio
 * Author URI: https://redfrogstudio.co.uk
 * License: Proprietary
 * Text Domain: brevo-campaign-generator
 * Domain Path: /languages
 * Requires at least: 6.3
 * Requires PHP: 8.1
 * WC requires at least: 8.0
 */
```

Check for WooCommerce on activation — if not active, display admin notice and deactivate.

---

## Build Sequence

Build in this exact order to ensure dependencies are available:

1. Plugin bootstrap + constants
2. Database tables (activator)
3. Settings page + API key storage
4. Credits system (DB + widget)
5. Brevo API client
6. OpenAI client
7. Gemini client
8. Product selector
9. Coupon generator
10. Template engine
11. Campaign CRUD
12. Campaign wizard (Step 1)
13. Campaign editor (Step 2)
14. Template editor page
15. Brevo stats page
16. Stripe top-up flow
17. All AJAX handlers
18. Admin CSS + JS
19. Internationalisation

---

## Testing Checklist

Before first commit, verify:

- [ ] Plugin activates without errors on WP 6.3 + WooCommerce 8
- [ ] All DB tables created on activation, removed on uninstall
- [ ] Settings page saves and retrieves all API keys
- [ ] API key test buttons return success/failure correctly
- [ ] Product selector returns correct products for each sort mode
- [ ] AI generation calls complete and deduct credits
- [ ] Credit top-up flow completes via Stripe test mode
- [ ] Campaign saves to DB correctly
- [ ] Template preview renders correct HTML
- [ ] Brevo campaign creation succeeds via API
- [ ] Test email sends successfully
- [ ] Stats page loads and displays data
- [ ] All AJAX endpoints return correct JSON
- [ ] No PHP warnings or errors on any admin page

---

*End of CLAUDE.md*
