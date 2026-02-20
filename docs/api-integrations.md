# API Integrations

This document describes all third-party APIs used by the plugin, how they're used, and what to know when configuring or debugging them.

---

## OpenAI (GPT)

**Used for:** All text generation — headlines, descriptions, subject lines, coupon text

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
    { "role": "system", "content": "System prompt..." },
    { "role": "user", "content": "Task prompt..." }
  ],
  "temperature": 0.75,
  "max_tokens": 500
}
```

### Token Budget Per Task

| Task | Max Tokens | Temperature |
|---|---|---|
| Subject line | 80 | 0.8 |
| Preview text | 80 | 0.7 |
| Main headline | 60 | 0.8 |
| Main description | 400 | 0.75 |
| Product headline | 80 | 0.8 |
| Product short description | 200 | 0.75 |
| Coupon text | 60 | 0.7 |
| Discount suggestion | 50 | 0.3 |

### Credit Cost

| Model | Credits per generation task |
|---|---|
| GPT-4o | 5 |
| GPT-4o Mini | 1 |
| GPT-4 Turbo | 5 |

### Configuring the Key

1. Get your key at [platform.openai.com/api-keys](https://platform.openai.com/api-keys)
2. Go to **Settings → API Keys** in the plugin
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

**Used for:** AI image generation for campaign header and product images

**Endpoint:** `https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent`

**Authentication:** API key in query string (`?key={api_key}`)

### Supported Models

| Model | ID | Best For | Approx Cost |
|---|---|---|---|
| Gemini 1.5 Pro | `gemini-1.5-pro` | Highest quality images | ~$3.50/1M input tokens |
| Gemini 1.5 Flash | `gemini-1.5-flash` | Fast, cost-efficient | ~$0.075/1M input tokens |
| Gemini 2.0 Flash (Exp) | `gemini-2.0-flash-exp` | Latest experimental | Check Google AI pricing |

> Costs are approximate. See [ai.google.dev/pricing](https://ai.google.dev/pricing) for current rates.

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
4. Paste the key into **Settings → API Keys → Google Gemini API Key**
5. Click **Test Connection**

### Troubleshooting

| Error | Cause | Fix |
|---|---|---|
| `403 Forbidden` | Key invalid or API not enabled | Enable Generative Language API in Google Cloud |
| `429 Resource Exhausted` | Quota exceeded | Check quota in Google Cloud Console |
| Image not returned | Model selected doesn't support image output | Switch to a supported model |

---

## Brevo

**Used for:** Creating campaigns, managing mailing lists, scheduling and sending, retrieving stats

**Base URL:** `https://api.brevo.com/v3/`

**Authentication:** Header `api-key: {api_key}`

### Endpoints Used

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/contacts/lists` | Fetch mailing lists |
| `POST` | `/emailCampaigns` | Create a new campaign |
| `PUT` | `/emailCampaigns/{id}` | Update a campaign |
| `POST` | `/emailCampaigns/{id}/sendNow` | Send immediately |
| `GET` | `/emailCampaigns` | List all campaigns |
| `GET` | `/emailCampaigns/{id}` | Get campaign details |
| `GET` | `/emailCampaigns/{id}/sendReport` | Get campaign stats |
| `POST` | `/emailCampaigns/{id}/sendTest` | Send test email |

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

### Configuring the Key

1. Log into [app.brevo.com](https://app.brevo.com)
2. Go to **Settings → API Keys**
3. Create a new API key with full campaign access
4. Paste into **Settings → API Keys → Brevo API Key**
5. Click **Test Connection** — this calls `GET /account` to verify

### Troubleshooting

| Error | Cause | Fix |
|---|---|---|
| `401 Unauthorized` | Invalid key | Regenerate in Brevo |
| `400 Bad Request` | Invalid payload | Check campaign data, ensure sender email is verified |
| `403 Forbidden` | Insufficient key permissions | Recreate key with campaign permissions |
| Lists not loading | API error or no lists exist | Create at least one list in Brevo first |

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
4. Paste into **Settings → Stripe**
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
