# BCG Admin Design System — Style Guide

> **Purpose:** This document defines the complete visual design system for WordPress admin plugins built by Red Frog Studio (Dom Kapelewski). Copy this system into any new plugin to achieve a consistent, branded look across all RFS tools.
>
> **Last updated:** v1.4.0 · February 2026

---

## Quick Start — Applying This System to a New Plugin

1. Copy `admin/css/bcg-admin.css` into your plugin (rename the prefix from `bcg` to your plugin's prefix throughout)
2. Register the `bcg-admin-page` body class on your admin pages (see PHP Implementation below)
3. Enqueue Google Fonts + Material Icons (see Asset Enqueuing below)
4. Wrap all page output in `<div class="wrap bcg-wrap">` (or your plugin's prefix)
5. Use the plugin header partial before the wrap div (see Plugin Header below)

---

## 1. Aesthetic Direction

**Tone:** Dark premium SaaS — minimal, refined, authoritative
**Inspiration:** Linear.app, Vercel Dashboard, Raycast
**Audience:** WooCommerce store admins — technically capable but non-developer

**Key principles:**
- Deep dark backgrounds with subtle blue-tinted surfaces
- Single sharp red accent (Red Frog Studio brand)
- Clean, uncluttered layouts — every element earns its place
- Generous whitespace, clear hierarchy
- Micro-interactions on hover/focus but never distracting

---

## 2. Fonts

### Google Fonts CDN
```
https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=JetBrains+Mono:wght@400;500;600&display=swap
```

| Role | Font | When to Use |
|------|------|-------------|
| Display / Headings | **Syne** 600–800 | Page titles, stat values, section headings |
| Body / UI | **DM Sans** 400–600 | Paragraphs, labels, button text, form fields |
| Code / Monospace | **JetBrains Mono** | API keys, code blocks, stored key display |

### CSS Variables
```css
--bcg-font-display: 'Syne', 'Helvetica Neue', sans-serif;
--bcg-font-body:    'DM Sans', 'Helvetica Neue', sans-serif;
--bcg-font-mono:    'JetBrains Mono', 'Courier New', monospace;
```

### Type Scale
| Element | Font | Size | Weight | Color |
|---------|------|------|--------|-------|
| Page `h1` | Syne | 22px | 700 | `--bcg-text-primary` |
| Section `h2` | Syne | 16px | 700 | `--bcg-text-primary` |
| Subsection `h3` | Syne | 14px | 600 | `--bcg-text-primary` |
| Body text | DM Sans | 13px | 400 | `--bcg-text-secondary` |
| Label / caption | DM Sans | 11px | 600 | `--bcg-text-muted` |
| Section label | DM Sans | 10px | 700 | `--bcg-text-muted` (uppercase, 0.12em tracking) |
| Stat value | Syne | 28px | 800 | `--bcg-text-primary` |
| Stat label | DM Sans | 10px | 700 | `--bcg-text-muted` (uppercase) |
| Monospace | JetBrains Mono | 11px | 400 | `--bcg-text-secondary` |

---

## 3. Colour Palette

All colours defined as CSS custom properties on `body.bcg-admin-page`.

### Backgrounds
```css
--bcg-bg-page:    #0c0e1a   /* deepest background — page base */
--bcg-bg-surface: #111526   /* cards, panels, plugin header */
--bcg-bg-raised:  #181d35   /* elevated panels, dropdowns */
--bcg-bg-hover:   #1e2440   /* hover state for interactive elements */
--bcg-bg-input:   #0e1120   /* form inputs */
```

### Borders
```css
--bcg-border:        rgba(255,255,255,0.07)    /* standard */
--bcg-border-focus:  rgba(230,53,41,0.55)      /* focused inputs */
--bcg-border-subtle: rgba(255,255,255,0.03)    /* very subtle dividers */
```

### Brand Accent (Red Frog Studio)
```css
--bcg-accent:      #e63529   /* primary red */
--bcg-accent-hover:#cc2b20   /* darker on hover */
--bcg-accent-dim:  rgba(230,53,41,0.12)  /* subtle tinted bg */
--bcg-accent-glow: rgba(230,53,41,0.28)  /* glow/shadow effects */
```

### Text
```css
--bcg-text-primary:   #eef0ff   /* main content text — near-white with blue tint */
--bcg-text-secondary: #8b92be   /* supporting text, descriptions */
--bcg-text-muted:     #4f567a   /* labels, placeholders, inactive elements */
```

### Status Colours
```css
--bcg-success:      #22c55e    --bcg-success-dim:  rgba(34,197,94,0.1)
--bcg-warning:      #f59e0b    --bcg-warning-dim:  rgba(245,158,11,0.1)
--bcg-error:        #ef4444    --bcg-error-dim:    rgba(239,68,68,0.1)
--bcg-info:         #3b82f6    --bcg-info-dim:     rgba(59,130,246,0.1)
```

---

## 4. Spacing

Spacing uses a 4px base unit. Reference both the CSS variable and the utility class.

| Token | Value | Utility Class |
|-------|-------|---------------|
| `--bcg-space-1` | 4px | `.bcg-mb-4` / `.bcg-mt-4` |
| `--bcg-space-2` | 8px | `.bcg-mb-8` / `.bcg-mt-8` |
| `--bcg-space-3` | 12px | `.bcg-mb-12` / `.bcg-mt-12` |
| `--bcg-space-4` | 16px | `.bcg-mb-16` / `.bcg-mt-16` |
| `--bcg-space-5` | 20px | `.bcg-mb-20` / `.bcg-mt-20` |
| `--bcg-space-6` | 24px | `.bcg-mb-24` / `.bcg-mt-24` |
| `--bcg-space-8` | 32px | `.bcg-mb-32` / `.bcg-mt-32` |

---

## 5. Border Radius

```css
--bcg-radius-sm:   6px    /* buttons, inputs, small chips */
--bcg-radius:     10px    /* cards, panels */
--bcg-radius-lg:  16px    /* large cards, modal */
--bcg-radius-xl:  24px    /* hero elements */
--bcg-radius-pill:999px   /* pills, badges */
```

---

## 6. Shadows

```css
--bcg-shadow-sm:     0 1px 3px rgba(0,0,0,0.5)          /* subtle lift */
--bcg-shadow:        0 4px 16px rgba(0,0,0,0.55)         /* standard card shadow */
--bcg-shadow-lg:     0 12px 40px rgba(0,0,0,0.65)        /* modals, floating elements */
--bcg-shadow-accent: 0 4px 20px rgba(230,53,41,0.3)      /* primary buttons */
```

---

## 7. Icons — Google Material Icons Outlined

### CDN (enqueue in PHP)
```
https://fonts.googleapis.com/icon?family=Material+Icons+Outlined
```

### Usage
```html
<span class="material-icons-outlined" aria-hidden="true">icon_name</span>
```

### Default Size
All Material Icons inside `.bcg-wrap` render at **18px** by default.
Buttons render icons at **16px**.
Stat card icons render at **22px**.

### Recommended Icons by Context

| Context | Icon Name | Notes |
|---------|-----------|-------|
| Total Campaigns | `campaign` | |
| Drafts | `drafts` | Not `draft` |
| Sent | `mark_email_read` | |
| Credits | `toll` | |
| New / Add | `add` | |
| Settings | `settings` | |
| Dashboard | `dashboard` | |
| Template | `design_services` | |
| Stats | `bar_chart` | |
| Billing | `credit_card` | |
| Regenerate | `refresh` | |
| Edit | `edit` | |
| Delete | `delete` | |
| Preview | `visibility` | |
| Email | `mail_outline` | |
| Send | `send` | |
| Schedule | `schedule` | |
| Save | `save` | |
| Back | `arrow_back` | |
| Check / Success | `check_circle` | |
| Warning | `warning` | |
| Error | `error` | |
| Info | `info` | |
| Search | `search` | |
| AI / Generate | `auto_awesome` | |
| Image | `image` | |
| Coupon | `local_offer` | |
| Products | `inventory_2` | |
| Key / API | `key` | |

### Never use Dashicons — always use Material Icons Outlined.

---

## 8. Layout System

### Page Wrapper
```html
<div class="wrap bcg-wrap">
  <!-- page content -->
</div>
```

### Grid Utilities
```html
<!-- 4-column grid (dashboard stats) -->
<div class="bcg-grid-4"> ... </div>

<!-- 3-column grid -->
<div class="bcg-grid-3"> ... </div>

<!-- 2-column grid -->
<div class="bcg-grid-2"> ... </div>

<!-- Responsive: all grids collapse to 1 column at ≤ 782px (WP admin breakpoint) -->
```

### Flex Utilities
```html
<div class="bcg-flex bcg-items-center bcg-justify-between"> ... </div>
<div class="bcg-flex bcg-items-center bcg-gap-12"> ... </div>
```
Available: `.bcg-flex`, `.bcg-items-center`, `.bcg-items-start`, `.bcg-justify-between`, `.bcg-justify-end`, `.bcg-flex-1`, `.bcg-gap-4`, `.bcg-gap-8`, `.bcg-gap-12`, `.bcg-gap-16`

---

## 9. Component Library

### 9.1 Plugin Header

Place this **before** the `.wrap.bcg-wrap` div so it's flush with the top of the content area.

```php
<?php require BCG_PLUGIN_DIR . 'admin/views/partials/plugin-header.php'; ?>
<div class="wrap bcg-wrap">
  ...
</div>
```

The header partial includes:
- Red Frog Studio logo (links to redfrogstudio.co.uk)
- Plugin name + "by Dom Kapelewski"
- Credit balance pill (links to Credits & Billing page)
- Navigation: Dashboard · New Campaign · Settings

**Critical:** The page body must have class `bcg-admin-page` AND `#wpbody-content` must have `padding-top: 0 !important` to avoid a gap below the WP toolbar. This is handled automatically by the CSS when the body class is set.

---

### 9.2 Cards

**Standard Card**
```html
<div class="bcg-card">
  <h2 class="bcg-card-header">Section Title</h2>
  <!-- content -->
</div>
```

**Raised Panel** (for nested content within a card)
```html
<div class="bcg-panel">
  <!-- nested content -->
</div>
```

**Section Label** (divider with text)
```html
<div class="bcg-section-label">Section Name</div>
```

---

### 9.3 Stat Cards (Dashboard)

```html
<div class="bcg-grid-4 bcg-mb-20">

  <!-- Default (accent red icon) -->
  <div class="bcg-stat-card bcg-card">
    <div class="bcg-stat-card-inner">
      <div class="bcg-stat-icon">
        <span class="material-icons-outlined" aria-hidden="true">campaign</span>
      </div>
      <div class="bcg-stat-content">
        <span class="bcg-stat-value">142</span>
        <span class="bcg-stat-label">Total Campaigns</span>
      </div>
    </div>
  </div>

  <!-- Warning (amber icon) -->
  <div class="bcg-stat-card bcg-card">
    <div class="bcg-stat-card-inner">
      <div class="bcg-stat-icon bcg-stat-icon-draft">
        <span class="material-icons-outlined" aria-hidden="true">drafts</span>
      </div>
      <div class="bcg-stat-content">
        <span class="bcg-stat-value">3</span>
        <span class="bcg-stat-label">Drafts</span>
      </div>
    </div>
  </div>

  <!-- Success (green icon) -->
  <div class="bcg-stat-card bcg-card">
    <div class="bcg-stat-card-inner">
      <div class="bcg-stat-icon bcg-stat-icon-sent">
        <span class="material-icons-outlined" aria-hidden="true">mark_email_read</span>
      </div>
      <div class="bcg-stat-content">
        <span class="bcg-stat-value">87</span>
        <span class="bcg-stat-label">Sent</span>
      </div>
    </div>
  </div>

  <!-- Info (blue icon) -->
  <div class="bcg-stat-card bcg-card">
    <div class="bcg-stat-card-inner">
      <div class="bcg-stat-icon bcg-stat-icon-credits">
        <span class="material-icons-outlined" aria-hidden="true">toll</span>
      </div>
      <div class="bcg-stat-content">
        <span class="bcg-stat-value">250</span>
        <span class="bcg-stat-label">Credits Remaining</span>
      </div>
    </div>
  </div>

</div>
```

**Icon colour modifiers:**
- (none) → accent red (`--bcg-accent`)
- `.bcg-stat-icon-draft` → warning amber (`--bcg-warning`)
- `.bcg-stat-icon-sent` → success green (`--bcg-success`)
- `.bcg-stat-icon-credits` → info blue (`--bcg-info`)

---

### 9.4 Buttons

| Class | Appearance | Use Case |
|-------|-----------|----------|
| `.bcg-btn-primary` | Solid accent red | Main CTA (New Campaign, Save, Create) |
| `.bcg-btn-secondary` | Dark raised bg, white border | Secondary actions (Cancel, Back) |
| `.bcg-btn-ghost` | Transparent, border | Tertiary actions |
| `.bcg-btn-danger` | Red-tinted | Destructive actions (Delete, Remove) |
| `.bcg-regen-btn` | Minimal ghost | Regenerate AI content |

**Size modifiers:** `.bcg-btn-sm` · `.bcg-btn-lg`

```html
<!-- Primary with icon -->
<a href="..." class="bcg-btn-primary">
  <span class="material-icons-outlined" aria-hidden="true">add</span>
  New Campaign
</a>

<!-- Secondary -->
<button type="button" class="bcg-btn-secondary">Cancel</button>

<!-- Danger -->
<button type="button" class="bcg-btn-danger">
  <span class="material-icons-outlined" aria-hidden="true">delete</span>
  Delete
</button>

<!-- Regenerate (small, text only) -->
<button type="button" class="bcg-regen-btn">
  <span class="material-icons-outlined" aria-hidden="true">refresh</span>
  Regenerate
</button>
```

**Loading state** — insert a spinner span at the start of the button:
```html
<button class="bcg-btn-primary is-loading" disabled>
  <span class="bcg-btn-spinner"></span>
  Saving...
</button>
```

---

### 9.5 Form Elements

**Basic form row**
```html
<div class="bcg-form-row">
  <label class="bcg-form-label" for="field-id">Field Label</label>
  <div class="bcg-form-field">
    <input type="text" id="field-id" class="bcg-input" placeholder="Enter value">
  </div>
  <p class="bcg-form-help">Helper text describing the field.</p>
</div>
```

**Input with action button (API key + Test Connection)**
```html
<div class="bcg-form-field bcg-input-with-action">
  <input type="password" class="bcg-input" value="">
  <button type="button" class="bcg-btn-secondary bcg-test-connection-btn">
    Test Connection
  </button>
</div>
```

**Select / Dropdown**
```html
<select class="bcg-select">
  <option value="gpt-4o">GPT-4o (Recommended)</option>
  <option value="gpt-4o-mini">GPT-4o Mini</option>
</select>
```

**Textarea**
```html
<textarea class="bcg-textarea" rows="4"></textarea>
```

**Checkbox group**
```html
<label class="bcg-checkbox-label">
  <input type="checkbox" class="bcg-checkbox" checked>
  Enable this feature
</label>
```

**Form table** (WP settings API compatible)
```html
<table class="form-table bcg-form-table">
  <tr>
    <th scope="row">
      <label class="bcg-form-label" for="field">Field Name</label>
    </th>
    <td>
      <input type="text" id="field" class="bcg-input" name="option_name">
      <p class="bcg-form-help">Description text.</p>
    </td>
  </tr>
</table>
```

---

### 9.6 Tab Navigation

```html
<nav class="bcg-tabs">
  <a href="?page=plugin&tab=general" class="bcg-tab active">General</a>
  <a href="?page=plugin&tab=api" class="bcg-tab">API Keys</a>
  <a href="?page=plugin&tab=advanced" class="bcg-tab">Advanced</a>
</nav>
```

Active tab gets the class `active` (or `current` for WP compatibility).

---

### 9.7 Status Badges

```html
<span class="bcg-badge bcg-badge-success">Active</span>
<span class="bcg-badge bcg-badge-warning">Pending</span>
<span class="bcg-badge bcg-badge-error">Failed</span>
<span class="bcg-badge bcg-badge-info">Scheduled</span>
<span class="bcg-badge bcg-badge-draft">Draft</span>
```

**Campaign status classes:**
- `.bcg-status-draft` → amber "Draft"
- `.bcg-status-ready` → blue "Ready"
- `.bcg-status-sent` → green "Sent"
- `.bcg-status-scheduled` → purple "Scheduled"

---

### 9.8 Notices / Alerts

```html
<!-- Success -->
<div class="bcg-notice bcg-notice-success">
  <span class="material-icons-outlined" aria-hidden="true">check_circle</span>
  Campaign saved successfully.
</div>

<!-- Error -->
<div class="bcg-notice bcg-notice-error">
  <span class="material-icons-outlined" aria-hidden="true">error</span>
  Failed to connect to Brevo API.
</div>

<!-- Warning -->
<div class="bcg-notice bcg-notice-warning">
  <span class="material-icons-outlined" aria-hidden="true">warning</span>
  Your credit balance is low.
</div>

<!-- Info -->
<div class="bcg-notice bcg-notice-info">
  <span class="material-icons-outlined" aria-hidden="true">info</span>
  This feature requires a Brevo API key.
</div>
```

---

### 9.9 Empty State

Used when a list or table has no data:

```html
<div class="bcg-empty-state">
  <span class="material-icons-outlined" aria-hidden="true">mail_outline</span>
  <h2>No items found</h2>
  <p>You haven't created anything yet. Get started below.</p>
  <a href="..." class="bcg-btn-primary">
    <span class="material-icons-outlined" aria-hidden="true">add</span>
    Create First Item
  </a>
</div>
```

---

### 9.10 Tables

```html
<table class="bcg-table wp-list-table widefat">
  <thead>
    <tr>
      <th>Column A</th>
      <th>Column B</th>
      <th>Status</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Value A</td>
      <td>Value B</td>
      <td><span class="bcg-status-badge bcg-status-sent">Sent</span></td>
      <td>
        <a href="#" class="bcg-btn-ghost bcg-btn-sm">Edit</a>
        <button class="bcg-btn-danger bcg-btn-sm">Delete</button>
      </td>
    </tr>
  </tbody>
</table>
```

---

### 9.11 Modals

```html
<!-- Trigger -->
<button class="bcg-btn-secondary" data-modal="modal-id">Open Modal</button>

<!-- Modal structure -->
<div id="modal-id" class="bcg-modal" role="dialog" aria-modal="true">
  <div class="bcg-modal-backdrop"></div>
  <div class="bcg-modal-dialog">
    <div class="bcg-modal-header">
      <h2 class="bcg-modal-title">Modal Title</h2>
      <button class="bcg-modal-close" aria-label="Close">
        <span class="material-icons-outlined">close</span>
      </button>
    </div>
    <div class="bcg-modal-body">
      <!-- content -->
    </div>
    <div class="bcg-modal-footer">
      <button class="bcg-btn-secondary" data-modal-close>Cancel</button>
      <button class="bcg-btn-primary">Confirm</button>
    </div>
  </div>
</div>
```

---

### 9.12 Loading / Skeleton

**Inline spinner (standalone)**
```html
<span class="bcg-spinner" aria-label="Loading..."></span>
```

**Full-page loading overlay**
```html
<div class="bcg-loading-overlay">
  <div class="bcg-loading-content">
    <div class="bcg-loading-spinner"></div>
    <p class="bcg-loading-message">Generating with AI...</p>
  </div>
</div>
```

**Skeleton text placeholder**
```html
<div class="bcg-skeleton" style="width:60%; height:14px;"></div>
<div class="bcg-skeleton" style="width:40%; height:14px; margin-top:8px;"></div>
```

---

## 10. PHP Implementation

### Step 1 — Add body class filter

In your main admin class:

```php
/**
 * Add custom body class to all plugin admin pages.
 */
public function add_plugin_body_class( string $classes ): string {
    $screen = get_current_screen();
    if ( $screen && str_contains( $screen->id, 'your-plugin-slug' ) ) {
        $classes .= ' bcg-admin-page';
    }
    return $classes;
}

// Register in constructor or init method:
add_filter( 'admin_body_class', array( $this, 'add_plugin_body_class' ) );
```

### Step 2 — Enqueue assets

```php
/**
 * Enqueue admin CSS and JS on plugin pages.
 */
public function enqueue_admin_assets( string $hook ): void {
    // Only load on plugin pages.
    if ( ! str_contains( $hook, 'your-plugin-slug' ) ) {
        return;
    }

    // Google Fonts (Syne · DM Sans · JetBrains Mono)
    wp_enqueue_style(
        'your-plugin-google-fonts',
        'https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=JetBrains+Mono:wght@400;500;600&display=swap',
        array(),
        null
    );

    // Material Icons Outlined
    wp_enqueue_style(
        'your-plugin-material-icons',
        'https://fonts.googleapis.com/icon?family=Material+Icons+Outlined',
        array(),
        null
    );

    // Plugin admin CSS
    wp_enqueue_style(
        'your-plugin-admin',
        YOUR_PLUGIN_URL . 'admin/css/admin.css',
        array(),
        YOUR_PLUGIN_VERSION
    );
}

add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
```

### Step 3 — Page template structure

```php
<?php
// Each admin view PHP file:
// 1. Plugin header BEFORE the wrap div
require YOUR_PLUGIN_DIR . 'admin/views/partials/plugin-header.php';

// 2. Main wrap
?>
<div class="wrap bcg-wrap">

    <!-- Page heading row -->
    <div class="bcg-flex bcg-items-center bcg-justify-between bcg-mb-20">
        <h1><?php esc_html_e( 'Page Title', 'your-plugin-slug' ); ?></h1>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=your-plugin-new' ) ); ?>" class="bcg-btn-primary">
            <span class="material-icons-outlined" aria-hidden="true">add</span>
            <?php esc_html_e( 'New Item', 'your-plugin-slug' ); ?>
        </a>
    </div>

    <!-- Stats -->
    <div class="bcg-grid-4 bcg-mb-20">
        <!-- stat cards here -->
    </div>

    <!-- Main content -->
    <div class="bcg-card bcg-mb-16">
        <h2 class="bcg-card-header"><?php esc_html_e( 'Section', 'your-plugin-slug' ); ?></h2>
        <!-- content -->
    </div>

</div><!-- .bcg-wrap -->
```

### Step 4 — Plugin header partial

Create `admin/views/partials/plugin-header.php`:

```php
<?php
/**
 * Plugin header — branding bar, credit pill, navigation.
 * Rendered OUTSIDE .bcg-wrap so it sits flush at the top of #wpbody-content.
 */
defined( 'ABSPATH' ) || exit;

// Get credit balance (adapt to your plugin's credit system).
$credit_balance = 0;
?>
<header class="bcg-plugin-header">
    <div class="bcg-plugin-header-brand">
        <a href="https://redfrogstudio.co.uk" class="bcg-brand-logo-link" target="_blank" rel="noopener">
            <img
                src="<?php echo esc_url( YOUR_PLUGIN_URL . 'admin/images/rfs-logo.png' ); ?>"
                alt="Red Frog Studio"
                class="bcg-brand-logo"
                width="56" height="33"
            >
        </a>
        <div class="bcg-brand-info">
            <span class="bcg-brand-name"><?php esc_html_e( 'Your Plugin Name', 'your-plugin-slug' ); ?></span>
            <span class="bcg-brand-tagline">
                <?php esc_html_e( 'for WooCommerce — by', 'your-plugin-slug' ); ?>
                <strong><?php esc_html_e( 'Dom Kapelewski', 'your-plugin-slug' ); ?></strong>
            </span>
        </div>
    </div>

    <div class="bcg-plugin-header-actions">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=your-plugin-credits' ) ); ?>"
           class="bcg-header-credit-pill">
            <img src="<?php echo esc_url( YOUR_PLUGIN_URL . 'admin/images/rfs-logo.png' ); ?>" alt="" width="16" height="10">
            <span><?php echo esc_html( number_format( $credit_balance ) ); ?> credits</span>
        </a>
        <nav class="bcg-plugin-header-nav" aria-label="Plugin navigation">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=your-plugin' ) ); ?>" class="bcg-header-nav-link">Dashboard</a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=your-plugin-settings' ) ); ?>" class="bcg-header-nav-link">Settings</a>
        </nav>
    </div>
</header>
```

---

## 11. CSS Class Naming Convention

All classes use your plugin's prefix (`bcg-` in BCG, replace with your own):

```
{prefix}-{component}              .bcg-card, .bcg-btn-primary
{prefix}-{component}-{modifier}   .bcg-btn-secondary, .bcg-stat-icon-draft
{prefix}-{utility}                .bcg-mb-16, .bcg-flex, .bcg-grid-4
{prefix}-{state}                  .bcg-status-sent, .bcg-notice-error
```

CSS scoping: all design system rules are scoped to `body.{prefix}-admin-page` to prevent leaking into other WP admin pages.

---

## 12. WordPress Admin Overrides — Important Notes

The design system overrides several WP admin defaults. Key patterns:

1. **Button overrides** use `!important` (NOT `all: unset`) to avoid breaking Dashicons font rendering in standard WP buttons used in the same page.

2. **`padding-top: 0 !important`** on `#wpbody-content` is required to eliminate the gap between the WP toolbar and the plugin header. This is set via:
   ```css
   body.bcg-admin-page #wpbody-content { padding-top: 0 !important; }
   ```

3. **Font inheritance** — WP admin sets a base 13px font-size. All BCG typography uses explicit `font-size` with `!important` where WP admin specificity would otherwise win.

4. **`body.bcg-admin-page` scoping** — The body class filter `admin_body_class` adds this class. All CSS rules must be scoped to this selector to avoid visual regressions on other admin pages.

5. **Form table compatibility** — BCG styles `.form-table` used by the WP Settings API while preserving its accessibility structure.

---

## 13. Accessibility Checklist

- All icons are decorative — use `aria-hidden="true"` on Material Icon spans
- Buttons that are icon-only (close, toggle) must have `aria-label`
- Modals must use `role="dialog"` and `aria-modal="true"`
- Active tab must have `aria-current="page"` or `class="active"`
- Form inputs must have `<label>` elements linked via `for`/`id`
- Colour contrast: text-primary (#eef0ff) on bg-surface (#111526) = ≥ 9:1 ✓
- Colour contrast: accent (#e63529) on dark backgrounds = ≥ 4.5:1 ✓

---

## 14. File Checklist for New Plugin

When applying this design system to a new plugin, copy and adapt these files:

```
admin/
  css/
    admin.css              ← copy bcg-admin.css, replace bcg- prefix
  images/
    rfs-logo.png           ← copy from BCG plugin
  views/
    partials/
      plugin-header.php    ← adapt header partial
    page-dashboard.php     ← adapt for your plugin's dashboard
    page-settings.php      ← adapt for your settings
```

PHP:
- Add `admin_body_class` filter → `bcg-admin-page` → `your-plugin-admin-page`
- Add `admin_enqueue_scripts` hook → load fonts, icons, CSS, JS
- Replace all `bcg_` prefixes in CSS classes with your plugin's prefix

---

*This document is maintained by Red Frog Studio. Update it whenever new components are added to the BCG plugin design system.*
