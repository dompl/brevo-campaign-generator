# API Integrations

This document describes all third-party APIs used by the plugin, how they are used, and what to know when configuring or debugging them.

---

## OpenAI (GPT)

**Used for:** All text generation — campaign headlines, descriptions, subject lines, coupon text, and all Template Builder section copy

**Endpoint:** `https://api.openai.com/v1/chat/completions`

**Authentication:** Bearer token (`Authorization: Bearer {api_key}`)

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

### Credit Cost

| Model | Credits per generation task |
|---|---|
| GPT-4o | 5 |
| GPT-4o Mini | 1 |
| GPT-4 Turbo | 5 |

### Configuring the Key

1. Get your key at [platform.openai.com/api-keys](https://platform.openai.com/api-keys)
2. Go to **Settings → API Keys** in the plugin, or define `BCG_OPENAI_API_KEY` in `wp-config.php`
3. Paste into the **OpenAI API Key** field
4. Click **Test Connection**

### Troubleshooting

| Error | Cause | Fix |
|---|---|---|
| `401 Unauthorized` | Invalid or expired key | Regenerate key at OpenAI |
| `429 Too Many Requests` | Rate limit hit | Wait and retry; check your OpenAI tier |
| `400 Bad Request` | Malformed prompt | Check plugin error log |
| `500 Internal Server Error` | OpenAI outage | Check [status.openai.com](https://status.openai.com) |

---

## Google Gemini

**Used for:** AI image generation for campaign header and product images (flat-template campaigns only)

**Endpoint:** `https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent`

**Authentication:** API key in query string (`?key={api_key}`)

### Supported Models

| Model | ID | Best For | Approx Cost |
|---|---|---|---|
| Gemini 1.5 Pro | `gemini-1.5-pro` | Highest quality images | ~$3.50/1M input tokens |
| Gemini 1.5 Flash | `gemini-1.5-flash` | Fast, cost-efficient | ~$0.075/1M input tokens |
| Gemini 2.0 Flash (Exp) | `gemini-2.0-flash-exp` | Latest experimental | Check Google AI pricing |

> Costs are approximate. See [ai.google.dev/pricing](https://ai.google.dev/pricing) for current rates.

> **Note:** Gemini image generation is not available in all countries. If you receive a regional restriction error, the plugin shows a human-readable message explaining the limitation.

### Image Prompt Structure

```
A professional {style} photograph of {product_name}.
{product_short_description}.
Clean neutral background, high detail, sharp focus,
suitable for an e-commerce email newsletter.
No text, labels, watermarks, or logos.
Aspect ratio: 600x400px horizontal banner.
```

**Available styles:**
- `photorealistic product` — clean studio shot
- `studio product photography` — professional white background
- `lifestyle product` — contextual, in-use setting
- `minimalist flat-lay` — overhead, clean composition
- `vivid illustrated` — bold, graphic style

### Credit Cost

| Model | Credits per image |
|---|---|
| Gemini 1.5 Pro | 10 |
| Gemini 1.5 Flash | 3 |
| Gemini 2.0 Flash Exp | 5 |

### Configuring the Key

1. Go to [aistudio.google.com/app/apikey](https://aistudio.google.com/app/apikey)
2. Create a new API key
3. Ensure the Generative Language API is enabled in your Google Cloud project
4. Paste the key into **Settings → API Keys → Google Gemini API Key**, or define `BCG_GEMINI_API_KEY` in `wp-config.php`
5. Click **Test Connection**

### Troubleshooting

| Error | Cause | Fix |
|---|---|---|
| `403 Forbidden` | Key invalid or API not enabled | Enable Generative Language API in Google Cloud |
| `429 Resource Exhausted` | Quota exceeded | Check quota in Google Cloud Console |
| Image not returned | Model selected does not support image output | Switch to a supported model |
| Regional restriction error | Gemini image gen not available in your country | Use WooCommerce product images instead |

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

## Stripe

**Used for:** Processing credit top-up payments

**Integration:** Stripe Elements (JS) + PaymentIntents API (server-side)

**Mode:** Uses test keys (`pk_test_`, `sk_test_`) during development; switch to live keys for production.

### Payment Flow

```
1. User selects credit pack → JS requests PaymentIntent
2. Plugin: POST /v1/payment_intents (server-side, secret key)
3. Stripe returns client_secret
4. Stripe.js confirms payment with card details
5. On success → AJAX call to bcg_stripe_confirm
6. Plugin verifies PaymentIntent status
7. Credits added, transaction logged
```

### Webhook (Recommended for Production)

Configure a Stripe webhook to `https://yoursite.com/wp-json/bcg/v1/stripe-webhook` for the event `payment_intent.succeeded`. This provides a fallback if the AJAX confirmation fails.

### Credit Packs (Default)

| Pack | Credits | Price (GBP) | Stripe Amount |
|---|---|---|---|
| Starter | 100 | £5.00 | 500 pence |
| Standard | 300 | £12.00 | 1200 pence |
| Pro | 1,000 | £35.00 | 3500 pence |

All amounts stored in pence (integer) for Stripe. Pack values editable in **Settings → Stripe**.

### Configuring Keys

1. Log into [dashboard.stripe.com](https://dashboard.stripe.com)
2. Go to **Developers → API Keys**
3. Copy both **Publishable key** and **Secret key**
4. Paste into **Settings → Stripe**, or define `BCG_STRIPE_PUB_KEY` and `BCG_STRIPE_SECRET_KEY` in `wp-config.php`
5. For production, use live keys; for testing, use test keys

### Test Cards

| Card Number | Result |
|---|---|
| `4242 4242 4242 4242` | Success |
| `4000 0000 0000 9995` | Decline |
| `4000 0025 0000 3155` | 3D Secure required |

Use any future expiry date and any 3-digit CVC.

### Troubleshooting

| Error | Cause | Fix |
|---|---|---|
| `No such payment_intent` | Stale or wrong intent ID | Refresh and retry |
| `Card declined` | Test card or actual decline | Use `4242...` test card |
| Credits not added after payment | AJAX confirmation failed | Check error log; add Stripe webhook as fallback |
| `Invalid API Key` | Wrong key pasted | Ensure no spaces; check test vs live mode |

---

## API Key Storage

API keys can be stored in two ways:

### Option A — Settings Page (default)

Keys are stored in `wp_options` with the prefix `bcg_`. They are displayed as masked password fields in the admin. The raw key is never output in HTML.

### Option B — PHP Constants in wp-config.php

Define constants before WordPress loads to use them instead of the database option:

```php
define( 'BCG_OPENAI_API_KEY',    'sk-...' );
define( 'BCG_GEMINI_API_KEY',    'AIza...' );
define( 'BCG_BREVO_API_KEY',     'xkeysib-...' );
define( 'BCG_STRIPE_PUB_KEY',    'pk_live_...' );
define( 'BCG_STRIPE_SECRET_KEY', 'sk_live_...' );
```

When a constant is defined, the corresponding settings field shows a "Key configured via wp-config.php" notice and the database option is ignored. All API consumers read the constant automatically.

This approach is recommended for production environments where database-stored secrets are undesirable.
