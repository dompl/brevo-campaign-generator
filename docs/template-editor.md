# Template Editor & Template Builder

## Two Email Authoring Approaches

The plugin offers two ways to create email designs:

1. **Template Builder** (v1.5.0+, recommended) — drag-and-drop composition from 20 reusable section types; AI fills copy; templates are saved and reused across campaigns
2. **Template Editor** (original) — visual settings and a raw HTML editor for a single flat email template; still fully functional for simple campaigns

---

## Template Builder

Access at **Brevo Campaigns → Template Builder**.

The Template Builder is the recommended approach for building campaigns. It produces email-client-safe HTML from a sections JSON array via `BCG_Section_Renderer`, with responsive mobile styles included.

### Layout

```
┌──────────────────┬────────────────────────────────┬──────────────────────┐
│  Palette         │  Canvas                        │  Settings            │
│  (left panel)    │  (centre panel)                │  (right panel)       │
│                  │                                │                      │
│  ▼ Headers       │  [Header]  ☰ 👁 ✏ ⧉ ⊗ ✨     │  Logo URL [...]      │
│    Logo Only     │                                │  Logo Width  ━━━━●  │
│    Logo + Nav    │  [Hero / Banner]  ☰ 👁 ✏ ⧉ ⊗ ✨│  Background  ●      │
│    ...           │                                │  Show Nav  ⬤         │
│  ▼ Hero Banners  │  [Products]  ☰ 👁 ✏ ⧉ ⊗ ✨    │  Nav Links  [+]     │
│    Dark          │                                │                      │
│    Minimal       │  [Footer]  ☰ 👁 ✏ ⧉ ⊗ ✨      │  [Inline preview]   │
│    ...           │                                │                      │
└──────────────────┴────────────────────────────────┴──────────────────────┘
```

### Palette

The left panel shows all available section variants grouped by category in an accordion. Only one category group is open at a time. Click any variant card to add that section to the canvas. Each card shows the section type's Material Icon.

Categories include: Headers, Hero Banners, Heading, Text Blocks, Banners, Products, Lists, Call to Action, Coupon Blocks, Image, Dividers & Spacers, Social Media, Footers, and Standard Templates (saved templates from My Templates).

### Canvas

The centre panel shows the list of sections in the current template. Each canvas card shows:

- **Variant label** (e.g. "Logo Only", "Logo + Navigation") — set when the variant was added from the palette
- **Section type icon**
- **Drag handle** — drag to reorder; move-up/move-down buttons for keyboard accessibility
- **Eye button** — expands an inline scaled preview below the card
- **Edit button** — opens the settings panel for this section
- **Duplicate button**
- **Delete button**
- **AI button** — regenerates AI content for this section only (visible on sections with `has_ai: true`)

### Settings Panel

Clicking a canvas card opens its field controls in the right panel. Field types:

| Type | Rendered As | Notes |
|---|---|---|
| `text` | Text input | |
| `textarea` | Textarea | Each line is one item for list sections |
| `color` | Colour picker | |
| `range` | Custom red slider | Gradient track filled to current value; displays value in red bold text |
| `toggle` | Pill toggle switch | |
| `select` | Custom dropdown | Matches plugin's custom dropdown design |
| `image` | URL input with uploader | |
| `date` | Native date picker | |
| `links` | Label + URL repeater | Add/remove rows; serialises to JSON internally |
| `product_select` | AJAX product search widget | Search by name; selected products shown as removable tag chips |

An inline scaled preview at the bottom of the settings panel auto-refreshes (350ms debounce) on any setting change.

Fields marked for AI generation show an AI badge toggle. When enabled (default), the field is populated by AI on generation. When disabled, manually entered text is preserved.

### Toolbar

| Control | Description |
|---|---|
| Template name | Name for save/load |
| Theme | Campaign theme/occasion text (e.g. "Black Friday") |
| Tone | Professional / Friendly / Urgent / Playful / Luxury |
| Language | Language for AI copy generation |
| Default Settings (tune icon) | Opens modal to set global primary colour and font for the template |
| AI Prompt | Opens the AI Prompt modal |
| Generate with AI | Triggers full AI generation (layout design + copy fill) |
| Load Template | Opens saved templates list |
| Preview Email | Opens full email preview modal (desktop / mobile) |
| Save Template | Saves current state as a named template |
| Request a Section | Opens a form to request a new section type from Red Frog Studio |

### AI Generation

**Full generation:**

1. Click **AI Prompt** — a modal opens with a free-form description textarea
2. Voice input is available via the microphone button (Web Speech API, continuous mode)
3. Previously saved prompts appear in a dropdown (up to 10, stored in localStorage)
4. Click **Save & Generate with AI** — the AI first designs the layout (which section types to use and in what order, based on the prompt), then fills copy into all AI-capable sections
5. AI uses your AI Trainer context automatically

**Per-section generation:**

Click the AI button on any individual canvas card to regenerate copy for that section only.

**Empty canvas:**

Clicking "Generate with AI" on an empty canvas auto-builds a default layout (Header → Hero → Products → Text → CTA → Footer) before running AI generation.

### Saving and Loading Templates

- **Save Template** — saves the current template with its name to the `bcg_section_templates` database table
- **Load Template** — shows a list of saved templates; click any to load it
- **Auto-save** — every 60 seconds when there are unsaved changes; shows a quiet "Auto-saved" indicator
- **My Templates in campaign wizard** — saved templates appear in Step 1 of the New Campaign wizard; selecting one uses it for the campaign's email

### Using a Template in a Campaign

1. Save your template in the Template Builder
2. Go to **Brevo Campaigns → New Campaign**
3. In Step 1 (Email Template), click your template in the **My Templates** section
4. Complete the wizard and click Generate Campaign
5. The handler loads the sections JSON, injects the campaign's products and coupon data, runs AI generation on all AI-capable sections, and renders the email HTML

---

## Legacy Template Editor

Access at **Brevo Campaigns → Template Editor**.

The Template Editor customises the flat HTML template used by classic (non-section-builder) campaigns. It works alongside the Template Builder — both approaches are fully supported.

### Editor Layout

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

### Visual Settings

#### Branding Tab

| Setting | Description |
|---|---|
| Logo | Upload or enter URL of your store logo |
| Logo width | Width in pixels (default: 180px) |
| Logo alignment | Left / Centre / Right |
| Logo alt text | Accessibility text for the logo |
| Logo link | URL the logo links to (default: store homepage) |

#### Layout Tab

| Setting | Description |
|---|---|
| Max email width | Content container width (default: 600px) |
| Product layout | Stacked (1 column) or Side-by-side (2 columns) |
| Products per row | 1 or 2 (only for side-by-side) |
| Show coupon block | Toggle coupon display on/off |
| Show navigation bar | Toggle nav on/off |

#### Colours Tab

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

#### Typography Tab

| Setting | Description |
|---|---|
| Heading font | Font for campaign headline (e.g. `DM Serif Display`) |
| Body font | Font stack for body text (e.g. `Arial, sans-serif`) |
| Headline size | H1/H2 font size (px) |
| Body text size | Paragraph font size (px) |
| Line height | Body line height (e.g. 1.6) |

#### Navigation Tab

| Setting | Description |
|---|---|
| Navigation links | Repeatable: Label + URL pairs |
| Nav background colour | Background of the nav bar |
| Nav text colour | Colour of nav link text |

#### Footer Tab

| Setting | Description |
|---|---|
| Footer text | Small print, unsubscribe note |
| Footer links | Repeatable: Label + URL pairs |
| Footer background | Footer background colour |
| Footer text colour | Footer text colour |

---

### HTML Editor

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
| `{{setting_logo_alignment}}` | Logo alignment value (`left`, `center`, `right`) |
| `{{setting_heading_font_family}}` | Heading font family string |

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

### Live Preview

The preview panel updates in real time (300ms debounce) as you edit settings or HTML.

**Toggle views:**
- **Desktop** — full-width preview at 600px container
- **Mobile** — 375px wide iframe (media queries fire correctly)

The preview renders using the current template settings and sample placeholder content (real campaign data is shown when editing a specific campaign).

---

### Saving Templates

| Action | What It Does |
|---|---|
| **Save as Default Template** | Saves template HTML + settings as the plugin default. All new classic campaigns start with this template. |
| **Save to This Campaign** | Saves template changes only to the currently open campaign (available when accessed from the campaign editor). |
| **Reset to Default** | Discards all changes and restores the original built-in template. A confirmation prompt appears first. |

---

### Built-in Flat Templates

10 flat templates are included, each with a distinct visual identity and default heading font:

| Template | Style | Default Heading Font |
|---|---|---|
| Default | Refined Premium | DM Serif Display |
| Feature | Hero Spotlight | Bebas Neue |
| Reversed | Midnight Luxury | Cormorant Garamond |
| Cards | Elevated Cards | DM Sans |
| Full-Width | Bold & Vivid | Oswald |
| Alternating | Editorial Magazine | Libre Baskerville |
| Grid | Modern Commerce Grid | Nunito |
| Centered | Luxury Centered | Cinzel |
| Compact | Smart Newsletter | Merriweather |
| Text-Only | Literary Elegance | Cormorant Garamond |

All templates include Google Fonts with email client fallbacks and MSO VML button compatibility for Outlook.

---

### Template Email Structure (Flat)

The default flat template generates inline-CSS email-safe HTML. The structure is:

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
- Use the Template Builder for complex, multi-section emails; use the Template Editor for simple product newsletters
- Test your template using **Send Test Email** in the campaign editor before sending live
- Use the **Mobile preview** to ensure readability on small screens
- Keep images under 600px wide; tall images may be clipped on mobile
- Avoid using `position: absolute/fixed` — not supported in most email clients
- Background images are not supported in Outlook — always provide a fallback background colour
- The Template Builder renderer inlines all CSS automatically — you do not need to do this manually
