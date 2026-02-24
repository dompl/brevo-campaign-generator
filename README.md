# Brevo Campaign Generator for WooCommerce

> Automatically generate and send Brevo email campaigns from your WooCommerce store using AI — no design or copywriting skills required.

[![Version](https://img.shields.io/badge/version-1.5.43-blue.svg)](CHANGELOG.md)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-6.3%2B-green.svg)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0%2B-blueviolet.svg)](https://woocommerce.com)
[![License](https://img.shields.io/badge/license-Proprietary-red.svg)](LICENSE)

---

## Overview

**Brevo Campaign Generator** connects your WooCommerce store to Brevo (formerly Sendinblue) and handles the entire email campaign workflow in one place:

- **Template Builder** — compose stunning emails from 19 reusable section types using drag-and-drop; AI fills in copy automatically
- **Pick products** — best sellers, new arrivals, least sold, or manual selection, filtered by category
- **Generate copy** — headlines, descriptions, and subject lines via OpenAI GPT
- **Generate images** — AI product imagery via Google Gemini (Pro or Flash)
- **Build the email** — flat HTML templates with live preview, or the recommended Template Builder approach
- **Add a coupon** — auto-generate WooCommerce discount codes with multiple display styles
- **Push to Brevo** — create, schedule, or send campaigns without leaving WordPress
- **Review performance** — Brevo campaign stats right in your dashboard
- **Train the AI** — store a description of your brand and products so AI copy stays on-brand every time

---

## Requirements

| Requirement | Minimum Version |
|---|---|
| PHP | 8.1 |
| WordPress | 6.3 |
| WooCommerce | 8.0 |
| Brevo account | Any paid plan |
| OpenAI account | API access |
| Google Cloud account | Gemini API enabled (for AI images) |
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
git clone https://github.com/dompl/brevo-campaign-generator.git
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

**Alternative — define keys in wp-config.php:**

You can define any API key as a PHP constant in `wp-config.php` instead of storing it in the database. When a constant is defined, the settings field shows a confirmation notice and the constant is used automatically:

```php
define( 'BCG_OPENAI_API_KEY',     'sk-...' );
define( 'BCG_GEMINI_API_KEY',     'AIza...' );
define( 'BCG_BREVO_API_KEY',      'xkeysib-...' );
define( 'BCG_STRIPE_PUB_KEY',     'pk_live_...' );
define( 'BCG_STRIPE_SECRET_KEY',  'sk_live_...' );
```

### Step 2 — AI Model Selection

Under **Settings → AI Models**, choose:

- **OpenAI model** — GPT-4o for best quality, GPT-4o Mini for lower cost
- **Gemini model** — Gemini 1.5 Pro for best images, Gemini 1.5 Flash for speed and economy

A pricing reference table shows estimated costs per generation to help you choose.

### Step 3 — Brevo Configuration

Under **Settings → Brevo**:
- Select your default mailing list (populated from Brevo API)
- Set sender name and email (chosen from your verified Brevo senders)
- Add a campaign name prefix (e.g. `[WC]`)

### Step 4 — Train the AI (Recommended)

Go to **Brevo Campaigns → AI Trainer** and fill in:

- **About Your Store** — background, values, tone, target audience
- **About Your Products** — key ranges, USPs, anything the AI should know

This context is injected into every AI generation call so copy stays on-brand without you having to re-describe your store each time.

### Step 5 — Top Up Credits

Credits are used for every AI generation. Go to **Brevo Campaigns → Credits & Billing** to top up via Stripe. Choose a credit pack, pay securely, and your balance is updated instantly.

| Pack | Credits | Price |
|---|---|---|
| Starter | 100 | £5 |
| Standard | 300 | £12 |
| Pro | 1,000 | £35 |

---

## Creating a Campaign

### Option A — Template Builder (Recommended)

The Template Builder is the primary way to create beautiful, flexible email campaigns.

1. Go to **Brevo Campaigns → Template Builder**
2. Browse the **Palette** (left panel) — section types are grouped by category; click any variant card to add it to the canvas
3. Drag sections to reorder them; click any canvas card to edit its settings in the right panel
4. Use the **AI Prompt** button to describe your email brief; then click **Generate with AI** to fill all AI-capable sections
5. Click **Save Template** to save the template for reuse
6. When creating a new campaign, select your saved template from **My Templates** in Step 1

**Generate with AI flow:**

1. Click the **AI Prompt** button — type a description of the email, or use the microphone button for voice input
2. Saved prompts are stored locally (up to 10) and selectable from a dropdown
3. Click **Save & Generate with AI** — the AI first designs the layout (which section types to use), then fills in copy for all AI-capable sections
4. AI uses your AI Trainer store context automatically — no need to re-describe your brand

### Option B — Campaign Wizard

For a guided, product-focused flow:

1. Go to **Brevo Campaigns → New Campaign**
2. Work through the 5-step wizard:
   - **Step 1 — Email Template**: choose a saved Template Builder template or a flat HTML template
   - **Step 2 — Campaign Basics**: title, subject line, mailing list
   - **Step 3 — Products**: source (best sellers / least sold / latest / manual), category filter, number of products
   - **Step 4 — Coupon**: auto-generate a WooCommerce coupon with discount type and expiry
   - **Step 5 — AI & Generate**: tone, language, campaign theme; click **Generate Campaign**

You can generate up to 5 campaigns at once using the **Number of Campaigns** selector.

### Editing a Campaign

After generation, the Edit Campaign page shows a live email preview alongside editable fields:

- **Edit any field** directly by typing
- **Click Regenerate** on any field to get a new AI version
- **Drag products** to reorder them
- **Add or remove products** with the + and remove buttons
- **Switch templates** using the template strip above the preview

### Sending a Campaign

When you are happy with the campaign:
- **Send Test Email** — receive a test at your admin email
- **Create in Brevo** — saves as a draft campaign in your Brevo account
- **Schedule** — pick a date and time, campaign sends automatically via Brevo
- **Send Now** — sends immediately via Brevo

You can also schedule campaigns directly from the Dashboard without opening the editor.

---

## Template Builder

Go to **Brevo Campaigns → Template Builder** to compose emails from reusable sections.

### Section Types

| Section | AI | Description |
|---|---|---|
| Header | No | Logo, navigation links, background colour |
| Hero / Banner | Yes | Headline, subtext, CTA button, background image or colour |
| Hero Split | Yes | Two-column: image on one side, headline + text + button on the other |
| Text Block | Yes | Heading, body text, font and alignment controls |
| Image | No | Full-width or constrained image with optional link and caption |
| Products | Yes | WooCommerce product picker, 1–3 columns, price and button toggles |
| Banner | Yes | Bold heading and subtext strip, no button |
| Call to Action | Yes | Heading, subtext, and a prominent CTA button |
| Coupon — Classic | Yes | Bordered box with coupon code, headline, offer text, expiry |
| Coupon — Banner | Yes | Full-width dark strip with coupon code on the right |
| Coupon — Card | Yes | Elevated card with accent border and dashed code box |
| Coupon — Split | Yes | Two-column: discount amount left, code right |
| Coupon — Minimal | Yes | Clean centred layout with dashed border code box |
| Coupon — Ribbon | Yes | Dark background with ribbon accent strip |
| Divider | No | Horizontal rule — solid, dashed, dotted, or double; configurable thickness |
| Spacer | No | Fixed-height transparent gap block |
| Heading | No | Section heading with optional subtext and accent underline |
| List | Yes | Bulleted, numbered, checkmark, arrows, stars, dashes, hearts, diamonds, or plain list |
| Social Media | No | Configurable social platform icon links with optional logo |
| Footer | Yes | Footer text, unsubscribe link, footer links, optional social icons |

### AI Generation

- **Full generation**: click **AI Prompt** to describe your email, then **Save & Generate with AI** — the AI designs the section layout and fills copy in one pass
- **Per-section generation**: each canvas card has an AI regenerate button for individual sections
- Voice input is available in the AI Prompt modal (Web Speech API; hidden on unsupported browsers)
- Saved prompts persist in localStorage (up to 10, deduplicated)

### Toolbar Controls

- Template name input
- Campaign context: Theme text field, Tone dropdown (Professional / Friendly / Urgent / Playful / Luxury), Language dropdown
- Default Settings modal (tune icon): global primary colour picker and font selector
- AI Prompt button, Generate with AI button
- Load Template, Preview Email, Save Template, Request a Section buttons

---

## Legacy Template Editor

Go to **Brevo Campaigns → Template Editor** to customise the flat HTML email template used by classic campaigns.

**Visual settings:**
- Logo upload, width, and positioning
- Navigation bar with custom links
- Background and content colours
- Button colours and border radius
- Font selection (heading and body independently)
- Header and footer text
- Footer links (including unsubscribe)

**Code editor:**
Switch to HTML mode to edit the raw template. Full CodeMirror editor with syntax highlighting.

**Live preview:**
See changes reflected instantly in both desktop (600px) and mobile (375px) views.

Save as the default template for all new campaigns, or apply changes to a specific campaign only.

---

## AI Trainer

Go to **Brevo Campaigns → AI Trainer** to teach the AI about your store.

- **About Your Store** — company background, brand voice, target audience
- **About Your Products** — key product ranges, USPs, anything the AI should reference

This context is prepended to every AI generation call (campaign copy and Template Builder sections) automatically. You never need to re-enter it.

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
| Regenerate single field | OpenAI GPT-4o | 2 credits |
| Regenerate single field | OpenAI GPT-4o Mini | 1 credit |
| Template Builder — per AI section generation | OpenAI (selected model) | Per model cost |
| Generate product image | Gemini 1.5 Pro | 10 credits |
| Generate product image | Gemini 1.5 Flash | 3 credits |
| Generate main campaign image | Gemini 1.5 Pro | 10 credits |
| Generate main campaign image | Gemini 1.5 Flash | 3 credits |

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
- **Request a section type:** Use the **Request a Section** button in the Template Builder toolbar
- **Security:** Do not open public issues for security vulnerabilities — email directly

---

## Licence

Proprietary. All rights reserved. © Red Frog Studio.
Not for redistribution or resale without written permission.
