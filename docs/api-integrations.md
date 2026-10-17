# API Integrations

This document describes all third-party APIs used by the plugin, how they are used, and what to know when configuring or debugging them.

---

## OpenAI (GPT), via the Red Frog AI Portal

**Used for:** All text generation — campaign headlines, descriptions, subject lines, coupon text, and all Template Builder section copy

**Endpoint:** the portal's OpenAI route, `Portal::openai_url( '/chat/completions' )`, which proxies to OpenAI and meters usage. The plugin never calls `api.openai.com` directly.

**Authentication:** `Portal::headers( 'email', $purpose, $reference )` sets the Authorization header from the sealed `gateway_key_email` key; the plugin holds no OpenAI key of its own.

### Supported Models

| Model | ID | Best For | Approx Cost |
|---|---|---|---|
| GPT-4o | `gpt-4o` | Best quality, nuanced copy | ~$5/1M input tokens |
| GPT-4o Mini | `gpt-4o-mini` | Fast, cost-efficient | ~$0.15/1M input tokens |
| GPT-4 Turbo | `gpt-4-turbo` | Strong quality, large context | ~$10/1M input tokens |

> Costs are approximate and subject to change. See [openai.com/pricing](https://openai.com/pricing) for current rates.

### Request Format

```json
{
  "model": "gpt-4o",
  "messages": [
    { "role": "system", "content": "System prompt with AI Trainer context..." },
    { "role": "user", "content": "Task prompt..." }
  ],
  "temperature": 0.75,
  "max_tokens": 500
}
```

### System Prompt Context Stack

Every generation call uses a layered system prompt built by `BCG_OpenAI::build_system_prompt()`:

1. Base copywriter role definition with tone and language
2. Store currency from WooCommerce (so AI uses the correct currency symbol)
3. AI Trainer — company/store description (`bcg_ai_trainer_company`)
4. AI Trainer — product notes (`bcg_ai_trainer_products`)
5. User-supplied AI Prompt text (Template Builder sessions)

### Methods

#### Campaign Copy (Flat Template)

| Method | Max Tokens | Temperature | Description |
|---|---|---|---|
| `generate_subject_line()` | 80 | 0.8 | Email subject line |
| `generate_preview_text()` | 80 | 0.7 | Inbox preview text |
| `generate_main_headline()` | 60 | 0.8 | Campaign hero headline |
| `generate_main_description()` | 400 | 0.75 | Main campaign body text |
| `generate_product_headline()` | 80 | 0.8 | Per-product headline |
| `generate_product_short_description()` | 200 | 0.75 | Per-product short copy |
| `generate_coupon_discount_suggestion()` | 50 | 0.3 | Discount value + type + text |

#### Template Builder Section Copy

| Method | Description |
|---|---|
| `generate_text_block()` | Returns `heading` and `body` for Text Block sections |
| `generate_banner_text()` | Returns `heading` and `subtext` for Banner sections |
| `generate_cta_text()` | Returns `heading`, `subtext`, and `cta_text` for CTA sections |
| `generate_list_items()` | Returns `heading` and `items` (newline-separated) for List sections |
| `generate_section_layout()` | Returns an ordered array of section type slugs representing the AI-designed email layout |

#### Hero / Hero Split / Coupon Sections

Hero, Hero Split, and all Coupon section types are handled by the `BCG_Section_AI` dispatcher, which routes to the appropriate method based on the section type slug, using the same underlying `BCG_OpenAI` client.

### Billing

Every call is billed in pounds to the shared ATS AI wallet, not to a credit count. See **Settings → AI Models** for the current rate per generation, and top up on **Marketing → AI Credits** if the balance runs low.

### Configuring the Key

No key is needed in this plugin. The ATS AI Portal plugin holds one sealed gateway key per generator (`wp ats secret set gateway_key_email`), and every request to the portal is authenticated with it automatically.

### Troubleshooting

| Error | Cause | Fix |
|---|---|---|
| `401 Unauthorized` | Invalid or expired key | Regenerate key at OpenAI |
| `429 Too Many Requests` | Rate limit hit | Wait and retry; check your OpenAI tier |
| `400 Bad Request` | Malformed prompt | Check plugin error log |
| `500 Internal Server Error` | OpenAI outage | Check [status.openai.com](https://status.openai.com) |

---

## OpenAI Images, via the Red Frog AI Portal

**Used for:** AI image generation for the campaign hero banner and individual product images

**Model:** `gpt-image-1`, quality `medium`. Hero banners generate at 1536x1024; product images generate at 1024x1024.

**Endpoint:** `Portal::openai_url( '/images/generations' )`, proxied and metered the same way as the text calls above.

**Authentication:** the same sealed `gateway_key_email` key, via `Portal::headers()`. No key is stored in this plugin.

There is no automatic retry on image generation. A failed attempt is reported as an error rather than retried, because a second attempt is a second charge.

### Available Styles

| Value | Label |
|---|---|
| `photorealistic` | Photorealistic |
| `studio_product` | Studio Product |
| `lifestyle` | Lifestyle |
| `minimalist` | Minimalist |
| `vivid_illustration` | Vivid Illustration |

The chosen style is woven into the prompt built by `BCG_Images::build_main_image_prompt()` (hero banner) and `build_product_image_prompt()` (per-product), both filterable via `bcg_images_main_image_prompt` and `bcg_images_product_image_prompt`.

### Billing

Every image is billed in pounds to the shared ATS AI wallet, at the rate shown in **Settings → AI Models**. Top up on **Marketing → AI Credits**.

### Troubleshooting

| Error | Cause | Fix |
|---|---|---|
| "No AI credit" / `ats_portal_out_of_credit` | Wallet balance is too low | Top up on Marketing → AI Credits |
| "AI is not available" / `ats_portal_no_key` | Gateway key not sealed for this generator | Ask an admin to run `wp ats secret set gateway_key_email` |
| `ats_portal_rate_limited` | Too many requests in a short time | Wait a moment and try again |
| `ats_portal_unavailable` | Portal or OpenAI outage | Check again shortly; no charge is made on failure |

---

## Brevo

**Used for:** Creating campaigns, managing mailing lists, scheduling and sending, retrieving stats, listing verified senders

**Base URL:** `https://api.brevo.com/v3/`

**Authentication:** Header `api-key: {api_key}`

### Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/contacts/lists` | Fetch mailing lists |
| `GET` | `/senders` | Fetch verified senders |
| `GET` | `/account` | Verify API key (test connection) |
| `POST` | `/emailCampaigns` | Create a new campaign |
| `PUT` | `/emailCampaigns/{id}` | Update a campaign |
| `POST` | `/emailCampaigns/{id}/sendNow` | Send immediately |
| `GET` | `/emailCampaigns` | List all campaigns |
| `GET` | `/emailCampaigns/{id}` | Get campaign details |
| `GET` | `/emailCampaigns/{id}/sendReport` | Get campaign stats |
| `POST` | `/emailCampaigns/{id}/sendTest` | Send test email |

### Sender Resolution

Before every send operation, `ensure_brevo_campaign()` calls `GET /senders` and resolves the sender by:

1. Email match against the stored sender email
2. Stored sender ID match
3. First verified sender as fallback

The resolved sender (with the correct Brevo sender `id`) is passed in the API payload and persisted to the `bcg_brevo_sender` option. Either `{id}` alone or `{name, email}` is passed — never both — as required by the Brevo API.

### Campaign Payload

```json
{
  "name": "[WC] Summer Sale",
  "subject": "Up to 30% off this weekend only",
  "sender": {
    "name": "My Store",
    "email": "hello@mystore.com"
  },
  "type": "classic",
  "htmlContent": "<!DOCTYPE html>...",
  "recipients": {
    "listIds": [5]
  },
  "scheduledAt": "2025-03-01T10:00:00+00:00"
}
```

### Stats Response Fields Used

| Field | Displayed As |
|---|---|
| `statistics.globalStats.uniqueViews` | Opens |
| `statistics.globalStats.clickers` | Clicks |
| `statistics.globalStats.unsubscriptions` | Unsubscribes |
| `statistics.globalStats.recipients` | Recipients |
| `openRate` | Open Rate % |
| `clickRate` | Click Rate % |

### Mailing List Loading

Mailing lists are fetched server-side on page load using a 1-hour transient (`bcg_brevo_lists`) and rendered into the New Campaign dropdown without a separate AJAX call. A Refresh button is available as a fallback. The same transient is used for the Settings → Brevo dropdown.

### Configuring the Key

1. Log into [app.brevo.com](https://app.brevo.com)
2. Go to **Settings → API Keys**
3. Create a new API key with full campaign access
4. Paste into **Settings → API Keys → Brevo API Key**, or define `BCG_BREVO_API_KEY` in `wp-config.php`
5. Click **Test Connection** — this calls `GET /account` to verify

### Troubleshooting

| Error | Cause | Fix |
|---|---|---|
| `401 Unauthorized` | Invalid key | Regenerate in Brevo |
| `400 Bad Request` | Invalid payload | Check campaign data, ensure sender email is verified |
| `403 Forbidden` | Insufficient key permissions | Recreate key with campaign permissions |
| Lists not loading | API error or no lists exist | Create at least one list in Brevo first |
| "Sender is invalid / inactive" | Sender not verified in Brevo | Go to Settings → Brevo and re-select a verified sender |

---

## API Key Storage

API keys can be stored in two ways:

### Option A — Settings Page (default)

Keys are stored in `wp_options` with the prefix `bcg_`. They are displayed as masked password fields in the admin. The raw key is never output in HTML.

### Option B — PHP Constants in wp-config.php

Define the constant before WordPress loads to use it instead of the database option:

```php
define( 'BCG_BREVO_API_KEY', 'xkeysib-...' );
```

When the constant is defined, the Brevo API Key field shows a "Key configured via wp-config.php" notice and the database option is ignored.

This approach is recommended for production environments where database-stored secrets are undesirable.

OpenAI text and image generation have no key in this plugin at all. They are authenticated by the Red Frog AI Portal's sealed gateway key (`wp ats secret set gateway_key_email`), managed outside this plugin.
