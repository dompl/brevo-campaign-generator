# Brevo Campaign Generator for WooCommerce

> Automatically generate and send Brevo email campaigns from your WooCommerce store using AI — no design or copywriting skills required.

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](CHANGELOG.md)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-6.3%2B-green.svg)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0%2B-blueviolet.svg)](https://woocommerce.com)
[![License](https://img.shields.io/badge/license-Proprietary-red.svg)](LICENSE)

---

## Overview

**Brevo Campaign Generator** connects your WooCommerce store to Brevo (formerly Sendinblue) and handles the entire email campaign workflow in one place:

- **Pick products** — best sellers, new arrivals, or manual selection, filtered by category
- **Generate copy** — headlines, descriptions, and subject lines via OpenAI GPT
- **Generate images** — AI product imagery via Google Gemini (Pro or Flash)
- **Build the email** — fully customisable HTML template with live preview
- **Add a coupon** — auto-generate WooCommerce discount codes
- **Push to Brevo** — create, schedule, or send campaigns without leaving WordPress
- **Review performance** — Brevo campaign stats right in your dashboard

---

## Requirements

| Requirement | Minimum Version |
|---|---|
| PHP | 8.1 |
| WordPress | 6.3 |
| WooCommerce | 8.0 |
| Brevo account | Any paid plan |
| OpenAI account | API access |
| Google Cloud account | Gemini API enabled |
| Stripe account | For credit top-ups |

---

## Installation

1. Download the latest release from the [Releases](../../releases) page
2. In WordPress admin, go to **Plugins → Add New → Upload Plugin**
3. Upload the `.zip` file and click **Install Now**
4. Activate the plugin
5. Navigate to **Brevo Campaigns → Settings** and enter your API keys

### From Source

```bash
git clone https://github.com/red-frog-studio/brevo-campaign-generator.git
cd brevo-campaign-generator
composer install
npm install
npm run build
```

Then zip the folder and install via WordPress, or symlink to your `wp-content/plugins/` directory for development.

---

## Configuration

### Step 1 — API Keys

Go to **Brevo Campaigns → Settings → API Keys** and enter:

| Key | Where to get it |
|---|---|
| OpenAI API Key | [platform.openai.com/api-keys](https://platform.openai.com/api-keys) |
| Google Gemini API Key | [aistudio.google.com/app/apikey](https://aistudio.google.com/app/apikey) |
| Brevo API Key | [app.brevo.com/settings/keys/api](https://app.brevo.com/settings/keys/api) |
| Stripe Publishable Key | [dashboard.stripe.com/apikeys](https://dashboard.stripe.com/apikeys) |
| Stripe Secret Key | [dashboard.stripe.com/apikeys](https://dashboard.stripe.com/apikeys) |

Use the **Test Connection** button next to each key to verify it works.

### Step 2 — AI Model Selection

Under **Settings → AI Models**, choose:

- **OpenAI model** — GPT-4o for best quality, GPT-4o Mini for lower cost
- **Gemini model** — Gemini 1.5 Pro for best images, Gemini 1.5 Flash for speed and economy

A pricing reference table shows estimated costs per generation to help you choose.

### Step 3 — Brevo Configuration

Under **Settings → Brevo**:
- Select your default mailing list
- Set sender name and email
- Add a campaign name prefix (e.g. `[WC]`)

### Step 4 — Top Up Credits

Credits are used for every AI generation. Go to **Brevo Campaigns → Credits & Billing** to top up via Stripe. Choose a credit pack, pay securely, and your balance is updated instantly.

| Pack | Credits | Price |
|---|---|---|
| Starter | 100 | £5 |
| Standard | 300 | £12 |
| Pro | 1,000 | £35 |

---

## Creating a Campaign

### Step 1 — Configure

1. Go to **Brevo Campaigns → New Campaign**
2. Enter a campaign title and optionally a subject line
3. Choose your product source: **Best Sellers**, **Least Sold**, or **Latest**
4. Optionally filter by WooCommerce category
5. Configure coupon settings (auto-generated discount code)
6. Set tone, language, and campaign theme
7. Click **Generate Campaign →**

The AI generates all copy and images. This takes 20–60 seconds depending on the number of products and selected models.

### Step 2 — Edit

Once generated, you see a full editing interface with a live email preview:

- **Edit any field** directly by typing
- **Click ↻ Regenerate** on any field to get a new AI version
- **Drag products** to reorder them
- **Add products** with the + button (AI content generated automatically)
- **Remove products** with the ✕ button

### Step 3 — Send

When you're happy:
- **Send Test Email** — receive a test at your admin email
- **Create in Brevo** — saves as a draft campaign in your Brevo account
- **Schedule** — pick a date and time, campaign sends automatically
- **Send Now** — sends immediately

---

## Template Editor

Go to **Brevo Campaigns → Template Editor** to customise the email design.

**Visual settings:**
- Logo upload, width, and positioning
- Navigation bar with custom links
- Background and content colours
- Button colours and border radius
- Font selection
- Header and footer text
- Footer links (including unsubscribe)

**Code editor:**
Switch to HTML mode to edit the raw template. Full CodeMirror editor with syntax highlighting.

**Live preview:**
See your changes reflected instantly in both desktop and mobile views.

Save as the default template (used for all new campaigns) or apply changes to a specific campaign only.

---

## Brevo Stats

Go to **Brevo Campaigns → Brevo Stats** to see performance data for all campaigns:

- Open rate, click rate, unsubscribes
- Per-campaign breakdown table
- Filterable by date range and status

Stats are fetched directly from the Brevo API and cached for 15 minutes.

---

## Credit Costs

| Task | Service | Credits |
|---|---|---|
| Generate all copy (full campaign) | OpenAI GPT-4o | 5 credits |
| Generate all copy (full campaign) | OpenAI GPT-4o Mini | 1 credit |
| Regenerate single field | OpenAI | 1–2 credits |
| Generate product image | Gemini 1.5 Pro | 10 credits |
| Generate product image | Gemini 1.5 Flash | 3 credits |
| Regenerate main campaign image | Gemini | 3–10 credits |

Credits are non-expiring. Unused credits remain on your account.

---

## Hooks & Filters

The plugin exposes hooks for developers who need to extend it.

### Filters

```php
// Modify the OpenAI prompt before sending
add_filter( 'bcg_openai_prompt', function( $prompt, $task, $context ) {
    return $prompt;
}, 10, 3 );

// Modify the Gemini image prompt
add_filter( 'bcg_gemini_image_prompt', function( $prompt, $product ) {
    return $prompt;
}, 10, 2 );

// Modify the campaign HTML before it's sent to Brevo
add_filter( 'bcg_campaign_html', function( $html, $campaign_id ) {
    return $html;
}, 10, 2 );

// Modify product query args for campaign product selection
add_filter( 'bcg_product_query_args', function( $args, $config ) {
    return $args;
}, 10, 2 );
```

### Actions

```php
// Fired after a campaign is created in Brevo
add_action( 'bcg_campaign_created', function( $campaign_id, $brevo_id ) {}, 10, 2 );

// Fired after a campaign is sent
add_action( 'bcg_campaign_sent', function( $campaign_id, $brevo_id ) {}, 10, 2 );

// Fired after credits are added to an account
add_action( 'bcg_credits_added', function( $user_id, $credits, $transaction_id ) {}, 10, 3 );
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a full version history.

---

## Support

This is a proprietary plugin developed by **Red Frog Studio** for agency use.

- **Issues:** Use [GitHub Issues](../../issues) with the appropriate template
- **Security:** Do not open public issues for security vulnerabilities — email directly

---

## Licence

Proprietary. All rights reserved. © Red Frog Studio.  
Not for redistribution or resale without written permission.
