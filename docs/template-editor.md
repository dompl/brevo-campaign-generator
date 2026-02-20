# Template Editor

## Overview

The Template Editor allows you to customise the HTML email template used for all campaigns. Changes can be applied globally (as the default for all new campaigns) or to a specific campaign only.

Access the editor at **Brevo Campaigns → Template Editor**.

---

## Editor Layout

The editor is split into three panels:

```
┌─────────────────┬──────────────────────────┬───────────────────┐
│  Visual Settings│    HTML Editor           │   Live Preview    │
│  (left sidebar) │    (centre, optional)    │   (right panel)   │
│                 │                          │                   │
│  Branding       │  <html>                  │  [Desktop]        │
│  Layout         │    <head>...</head>      │  [Mobile]         │
│  Colours        │    <body>                │                   │
│  Typography     │      ...                 │  ┌─────────────┐  │
│  Navigation     │    </body>               │  │   LOGO      │  │
│  Footer         │  </html>                 │  │  NAV LINKS  │  │
│                 │                          │  │  HEADLINE   │  │
│  [Save Default] │  [Visual / Code toggle]  │  │  PRODUCTS   │  │
│  [Save Campaign]│                          │  │  FOOTER     │  │
└─────────────────┴──────────────────────────┴───────────────────┘
```

---

## Visual Settings

### Branding Tab

| Setting | Description |
|---|---|
| Logo | Upload or enter URL of your store logo |
| Logo width | Width in pixels (default: 180px) |
| Logo alt text | Accessibility text for the logo |
| Logo link | URL the logo links to (default: store homepage) |

### Layout Tab

| Setting | Description |
|---|---|
| Max email width | Content container width (default: 600px) |
| Product layout | Stacked (1 column) or Side-by-side (2 columns) |
| Products per row | 1 or 2 (only for side-by-side) |
| Show coupon block | Toggle coupon display on/off |

### Colours Tab

| Setting | Description |
|---|---|
| Page background | Outer background colour |
| Content background | Inner email body background |
| Primary colour | Accent / highlight colour |
| Text colour | Body text colour |
| Link colour | Hyperlink colour |
| Button background | CTA button fill colour |
| Button text | CTA button label colour |
| Button border radius | Rounded corners (px) |

### Typography Tab

| Setting | Description |
|---|---|
| Font family | Font stack (e.g. `Arial, sans-serif`) |
| Headline size | H1/H2 font size (px) |
| Body text size | Paragraph font size (px) |
| Line height | Body line height (e.g. 1.6) |

### Navigation Tab

| Setting | Description |
|---|---|
| Show navigation bar | Toggle on/off |
| Navigation links | Repeatable: Label + URL pairs |
| Nav background colour | Background of the nav bar |
| Nav text colour | Colour of nav link text |

### Footer Tab

| Setting | Description |
|---|---|
| Footer text | Small print, unsubscribe note |
| Footer links | Repeatable: Label + URL pairs |
| Footer background | Footer background colour |
| Footer text colour | Footer text colour |

---

## HTML Editor

Click **Switch to Code** to edit the raw HTML template directly.

The editor uses **CodeMirror** with HTML syntax highlighting, bracket matching, and auto-indentation.

**Token reference** — use these tokens anywhere in the HTML:

| Token | Replaced With |
|---|---|
| `{{campaign_headline}}` | AI-generated campaign headline |
| `{{campaign_description}}` | AI-generated main description |
| `{{campaign_image}}` | `<img>` tag for main campaign image |
| `{{coupon_code}}` | The coupon code string |
| `{{coupon_text}}` | AI-generated coupon CTA text |
| `{{coupon_expiry}}` | Human-readable expiry date |
| `{{products_block}}` | Rendered HTML block of all products |
| `{{store_name}}` | WooCommerce store name |
| `{{store_url}}` | WooCommerce store URL |
| `{{logo_url}}` | Logo image URL |
| `{{unsubscribe_url}}` | Brevo unsubscribe URL (auto-injected) |
| `{{current_year}}` | Current year (for footer copyright) |

**Conditional blocks:**

```html
{{#if show_coupon}}
  <!-- This block only renders if a coupon is set -->
  <div class="bcg-coupon-block">
    Use code <strong>{{coupon_code}}</strong> for {{coupon_text}}
  </div>
{{/if}}
```

---

## Live Preview

The preview panel updates in real time (300ms debounce) as you edit settings or HTML.

**Toggle views:**
- **Desktop** — full-width preview at 600px container
- **Mobile** — scaled preview at 375px

The preview renders using the current template settings and sample placeholder content (real campaign data is shown when editing a specific campaign).

---

## Saving Templates

| Action | What It Does |
|---|---|
| **Save as Default Template** | Saves template HTML + settings as the plugin default. All new campaigns start with this template. |
| **Save to This Campaign** | Saves template changes only to the currently open campaign (available when accessed from the campaign editor). |
| **Reset to Default** | Discards all changes and restores the original built-in template. A confirmation prompt appears first. |

---

## Template Email Structure

The default template generates inline-CSS email-safe HTML. The structure is:

```
[Outer wrapper — background colour]
  [Inner container — max 600px, centred]
    [Header]
      [Logo]
      [Navigation]
    [Hero]
      [Main campaign image]
      [Headline]
      [Description]
    [Coupon block — conditional]
      [Coupon code]
      [CTA text]
    [Products block — repeated per product]
      [Product image]
      [Product headline]
      [Product short description]
      [Buy button — conditional]
    [Footer]
      [Footer text]
      [Footer links]
      [Copyright]
```

All CSS is inlined at render time for maximum email client compatibility (Outlook, Gmail, Apple Mail, etc.).

---

## Tips for Best Results

- Keep the email max-width at **600px** — this is the industry standard for email clients
- Use web-safe fonts or include a Google Fonts embed if your ESP supports it (Brevo does)
- Test your template using **Send Test Email** in the campaign editor before sending live
- Use the **Mobile preview** to ensure readability on small screens
- Keep images under 600px wide; tall images may be clipped on mobile
- Avoid using `position: absolute/fixed` — not supported in most email clients
- Background images are not supported in Outlook — always provide a fallback background colour
