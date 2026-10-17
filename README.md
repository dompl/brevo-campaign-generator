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
- **Generate images** — AI product imagery via OpenAI `gpt-image-1`
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
| Red Frog AI portal | Sealed gateway key (`wp ats secret set gateway_key_email`) |

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
| Brevo API Key | [app.brevo.com/settings/keys/api](https://app.brevo.com/settings/keys/api) |

AI access needs no key here. The ATS AI Portal plugin holds one sealed gateway key per generator (`wp ats secret set gateway_key_email`); this plugin uses it automatically for text and images.

**Alternative — define the Brevo key in wp-config.php:**

You can define the Brevo key as a PHP constant instead of storing it in the database:

```php
define( 'BCG_BREVO_API_KEY', 'xkeysib-...' );
```

### Step 2 — AI Model Selection

Under **Settings → AI Models**, choose:

- **OpenAI model** — GPT-4o for best quality, GPT-4o Mini for lower cost

A pricing reference table shows the estimated cost in pounds per generation, charged to the shared AI Credits wallet, to help you choose.

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

### Step 5 — AI Credits

Every AI generation is charged in pounds to the shared ATS AI wallet, which all the site's AI tools draw from. Top up on **Marketing → AI Credits**; the balance shows in the plugin header and on the dashboard.

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

## AI Pricing

Every AI call (copy or image) is billed in pounds to the shared ATS AI wallet, at the rate shown in **Settings → AI Models**. There are no credits and nothing expires; see the Help page's "How AI is billed" card for a plain-English summary.

---

## Hooks & Filters

The plugin exposes hooks for developers who need to extend it.

### Filters

```php
// Modify the OpenAI prompt before sending
add_filter( 'bcg_openai_prompt', function( $prompt, $task, $context ) {
    return $prompt;
}, 10, 3 );

// Modify the product image prompt
add_filter( 'bcg_images_product_image_prompt', function( $prompt, $product, $style ) {
    return $prompt;
}, 10, 3 );

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
