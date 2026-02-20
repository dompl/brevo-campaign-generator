# ORCHESTRATION.md — Multi-Agent Build Plan

> **For the Orchestrator agent only.** This file defines how to split the plugin build across specialised sub-agents. Read CLAUDE.md first for the full plugin spec. Then execute the phases below in order, spawning sub-agents via Bash.

---

## How to Spawn a Sub-Agent

Use the Bash tool to call Claude Code CLI non-interactively:

```bash
claude --dangerously-skip-permissions -p "AGENT PROMPT HERE" --output-format stream-json
```

For longer tasks, write the prompt to a temp file first:

```bash
cat > /tmp/agent-prompt.txt << 'EOF'
Your full agent prompt here...
EOF
claude --dangerously-skip-permissions -p "$(cat /tmp/agent-prompt.txt)" --output-format stream-json
```

**Rules for the orchestrator:**
- Never write plugin code yourself — only coordinate
- Always wait for a phase to complete before starting the next phase
- After each agent completes, verify the files exist before proceeding
- If an agent fails, retry once with a more specific prompt
- Log each agent's completion to `build-log.md`

---

## Build Phases

### PHASE 1 — Foundation (Sequential — must complete before anything else)

Spawn these two agents **sequentially** (Agent 1B depends on Agent 1A).

---

#### Agent 1A — Bootstrap & Database

**Goal:** Plugin entry point, constants, autoloader, activator with DB tables.

**Prompt:**
```
You are a WordPress plugin developer. Read CLAUDE.md for full context.

Your task: Build the plugin foundation. Create these files with full working code:

1. brevo-campaign-generator.php
   - Full plugin header (see CLAUDE.md for exact header)
   - Define constants: BCG_VERSION, BCG_PLUGIN_DIR, BCG_PLUGIN_URL, BCG_PLUGIN_FILE
   - Check WooCommerce is active; if not, show admin notice and return
   - Require composer autoload
   - Instantiate BCG_Plugin on plugins_loaded hook

2. includes/class-bcg-plugin.php
   - Singleton pattern
   - load_dependencies() — require all class files
   - init_hooks() — register activation/deactivation hooks
   - run() — hook everything up

3. includes/class-bcg-activator.php
   - create_tables() using dbDelta for all 4 tables:
     bcg_campaigns, bcg_campaign_products, bcg_credits, bcg_transactions
   - Use exact schema from CLAUDE.md
   - set_default_options() for all bcg_ options with sensible defaults

4. includes/class-bcg-deactivator.php
   - Flush rewrite rules
   - Clear all bcg_* transients

5. uninstall.php
   - Drop all 4 bcg_ tables
   - Delete all bcg_ options

6. composer.json
   - PSR-4 autoload mapping BCG_ classes from includes/
   - PHP 8.1 requirement

Follow WordPress Coding Standards. Use $wpdb->prefix for table names.
All strings wrapped in __() with text domain 'brevo-campaign-generator'.
```

**Verify after:** `brevo-campaign-generator.php`, `includes/class-bcg-plugin.php`, `includes/class-bcg-activator.php` all exist and contain real code.

---

#### Agent 1B — Settings & Options Framework

**Depends on:** Agent 1A complete

**Goal:** Full settings page with all tabs and API key storage.

**Prompt:**
```
You are a WordPress plugin developer. Read CLAUDE.md for full context.

The plugin bootstrap and DB tables are already built. Your task: Build the settings system.

1. includes/admin/class-bcg-settings.php
   - Register settings page under BCG_Admin menu (slug: bcg-settings)
   - 5 tabs: API Keys | AI Models | Brevo | Stripe | Defaults
   - Register all settings using Settings API with sanitisation callbacks
   - API Keys tab: OpenAI, Gemini, Brevo, Stripe pub/secret (password fields, masked)
   - Each API key field has a Test Connection button (triggers bcg_test_api_key AJAX)
   - AI Models tab: OpenAI model select, Gemini model select
   - Static pricing reference table (see CLAUDE.md for values)
   - Credit cost fields (editable, one per AI task type)
   - Brevo tab: default list ID (populated via AJAX), sender name/email, campaign prefix
   - Stripe tab: currency, 3 credit packs (name, credits, price — all editable)
   - Defaults tab: default product count, coupon discount, coupon expiry, auto-generate coupon

2. admin/views/page-settings.php
   - Tab navigation UI
   - Render each tab's fields
   - Settings form with wp_nonce_field
   - Save button per tab

3. admin/css/bcg-admin.css
   - Base admin styles
   - Tab navigation styles
   - Masked API key field styles
   - Test Connection button + success/error state styles
   - Credit widget styles (for admin bar widget, .bcg-credit-widget)
   - General layout utilities used across all admin pages

Follow WordPress Coding Standards. Sanitise all inputs. Escape all outputs.
```

---

### PHASE 2 — Integrations (Parallel — all 4 can run simultaneously)

Spawn all four agents at the same time.

---

#### Agent 2A — Brevo API Client

**Prompt:**
```
You are a WordPress plugin developer. Read CLAUDE.md for full context.

Your task: Build the complete Brevo API integration.

1. includes/integrations/class-bcg-brevo.php
   Implement ALL methods listed in CLAUDE.md:
   - get_contact_lists()
   - get_list( $list_id )
   - create_campaign( $data )
   - update_campaign( $campaign_id, $data )
   - send_campaign_now( $campaign_id )
   - schedule_campaign( $campaign_id, $datetime )
   - get_campaigns( $status, $limit )
   - get_campaign( $campaign_id )
   - get_campaign_stats( $campaign_id )
   - create_template( $data )
   - send_test_email( $campaign_id, $email )
   - test_connection() — calls GET /account, returns true/false

   Use wp_remote_request() for all HTTP calls.
   Get API key from get_option('bcg_brevo_api_key').
   Return WP_Error on failure. Log errors to bcg_error_log option.
   Cache get_contact_lists() with transient 'bcg_brevo_lists' (1 hour TTL).
   Cache get_campaigns() with transient 'bcg_brevo_campaigns' (15 min TTL).
   Cache get_campaign_stats() with transient 'bcg_stats_{id}' (15 min TTL).

Follow WordPress Coding Standards. PHPDoc on all methods.
```

---

#### Agent 2B — OpenAI Client

**Prompt:**
```
You are a WordPress plugin developer. Read CLAUDE.md for full context.

Your task: Build the OpenAI GPT integration.

1. includes/ai/class-bcg-openai.php
   Implement ALL generation methods from CLAUDE.md:
   - generate_subject_line( $products, $theme, $tone, $language )
   - generate_preview_text( $subject, $products )
   - generate_main_headline( $products, $theme, $tone, $language )
   - generate_main_description( $products, $theme, $tone, $language )
   - generate_product_headline( $product, $tone, $language )
   - generate_product_short_description( $product, $tone, $language )
   - generate_coupon_discount_suggestion( $products, $theme )

   Each method:
   - Builds system prompt + user prompt (see CLAUDE.md for base system prompt)
   - Calls /v1/chat/completions via wp_remote_post()
   - Uses model from get_option('bcg_openai_model', 'gpt-4o-mini')
   - Temperature 0.75 creative, 0.3 structured (see CLAUDE.md token budgets)
   - Returns string on success, WP_Error on failure
   - Stores token usage in $this->last_tokens_used for credit tracking

   System prompt template:
   "You are an expert email marketing copywriter for a WooCommerce e-commerce store.
   Write compelling, conversion-focused copy. Be concise. Avoid clichés.
   Respond only with the requested content — no explanations, no preamble.
   Always respond in {language}. Tone: {tone}."

Follow WordPress Coding Standards. PHPDoc on all methods.
```

---

#### Agent 2C — Gemini Client

**Prompt:**
```
You are a WordPress plugin developer. Read CLAUDE.md for full context.

Your task: Build the Google Gemini image generation integration.

1. includes/ai/class-bcg-gemini.php
   Implement:
   - generate_main_email_image( $products, $theme, $style )
   - generate_product_image( $product, $style )
   - test_connection()

   Each image generation method:
   - Builds image prompt using templates from CLAUDE.md
   - Calls Gemini API via wp_remote_post()
   - Model: get_option('bcg_gemini_model', 'gemini-1.5-flash')
   - Endpoint: https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent
   - API key as query param ?key={key}
   - Extracts base64 image data from response
   - Saves image to wp-content/uploads/bcg/{campaign_id}/ using wp_upload_dir()
   - Validates MIME type before saving
   - Returns full URL to saved image on success, WP_Error on failure

   Image prompt templates (from CLAUDE.md):
   Product: "A professional {style} photograph of {product_name}. {product_short_description}.
   Clean neutral background, high detail, sharp focus, suitable for an e-commerce email.
   No text, labels, watermarks, or logos. Aspect ratio: horizontal."

   Main image: "A professional {style} e-commerce email banner image representing:
   {product_names}. Campaign theme: {theme}. Clean composition, vibrant colours,
   suitable for a newsletter header. No text or watermarks."

Follow WordPress Coding Standards. PHPDoc on all methods.
```

---

#### Agent 2D — Stripe Integration

**Prompt:**
```
You are a WordPress plugin developer. Read CLAUDE.md for full context.

Your task: Build the Stripe payment integration for credit top-ups.

1. includes/integrations/class-bcg-stripe.php
   Implement:
   - create_payment_intent( $pack_key ) — creates Stripe PaymentIntent, returns client_secret
   - confirm_payment( $payment_intent_id ) — verifies payment succeeded
   - get_credit_packs() — returns configured packs from options
   - handle_webhook() — verifies signature, processes payment_intent.succeeded

   Use wp_remote_post() for Stripe API calls (no SDK required).
   Stripe API base: https://api.stripe.com/v1/
   Auth: Basic auth with secret key as username, empty password.

2. admin/js/bcg-stripe.js
   - Initialise Stripe.js with publishable key (localised via wp_localize_script)
   - Render Stripe Elements card field into #bcg-card-element
   - On form submit: stripe.confirmCardPayment(clientSecret)
   - On success: call bcg_stripe_confirm AJAX endpoint
   - On success: update credit balance in DOM without page reload
   - Show loading state during payment processing
   - Show clear error messages on failure

3. admin/views/page-credits.php
   - Current credit balance (large, prominent display)
   - Transaction history table (paginated, 20 per page)
     Columns: Date | Type | Description | Credits | Balance After
     Filter by: All | Top-ups | Usage | Refunds
   - Top-up section: 3 credit pack cards with name, credits, price, Purchase button
   - Purchase button opens inline Stripe Elements payment form
   - Post-payment success message with updated balance

Follow WordPress Coding Standards. Sanitise all inputs. Escape all outputs.
```

---

### PHASE 3 — Core Logic (Parallel — can run simultaneously)

---

#### Agent 3A — Campaign & Product Logic

**Prompt:**
```
You are a WordPress plugin developer. Read CLAUDE.md for full context.

The integrations (Brevo, OpenAI, Gemini, Stripe classes) are already built.
Your task: Build the campaign and product management logic.

1. includes/campaign/class-bcg-campaign.php
   - create_draft( $data ) — inserts to bcg_campaigns, returns campaign ID
   - update( $campaign_id, $data ) — updates campaign fields
   - get( $campaign_id ) — returns campaign row + associated products
   - get_all( $args ) — returns paginated list for dashboard
   - delete( $campaign_id ) — deletes campaign + products
   - add_product( $campaign_id, $product_id, $ai_data ) — inserts to bcg_campaign_products
   - update_product( $product_row_id, $data )
   - remove_product( $product_row_id )
   - reorder_products( $campaign_id, $ordered_ids )
   - save_template( $campaign_id, $html, $settings_json )

2. includes/campaign/class-bcg-product-selector.php
   - get_products( $config ) — main query method
     $config: [ count, source (bestsellers|leastsold|latest|manual),
                category_ids[], manual_ids[] ]
   - Sources:
     bestsellers: WC_Product_Query with orderby=total_sales desc
     leastsold: orderby=total_sales asc
     latest: orderby=date desc
     manual: post__in with manual_ids
   - Returns array of WC_Product objects
   - preview_products( $config ) — same but returns lightweight array for AJAX preview

3. includes/campaign/class-bcg-coupon.php
   - create_coupon( $campaign_id, $discount_value, $discount_type, $expiry_days, $prefix )
   - Uses WC_Coupon, generates unique code: {PREFIX}-{RANDOM6}
   - Sets: discount type, amount, expiry date, usage limit (1 per customer)
   - Returns coupon code string
   - delete_campaign_coupon( $campaign_id ) — removes coupon when campaign deleted

4. includes/db/ — all 3 table classes with:
   - get_table_name()
   - get_schema() — returns CREATE TABLE SQL for dbDelta
   - Used by BCG_Activator

All DB queries via $wpdb->prepare(). Follow WordPress Coding Standards.
```

---

#### Agent 3B — AI Manager & Template Engine

**Prompt:**
```
You are a WordPress plugin developer. Read CLAUDE.md for full context.

The OpenAI and Gemini classes are already built.
Your task: Build the AI manager (orchestrator for AI tasks + credits) and template engine.

1. includes/ai/class-bcg-ai-manager.php
   Dispatcher that sits between AJAX handlers and AI classes.

   - generate_campaign_copy( $campaign_id, $products, $config )
     Generates: subject, preview text, main headline, main description,
     all product headlines, all product short descriptions, coupon suggestion
     Deducts credits before calling. Refunds on failure.
     Returns array of all generated content or WP_Error.

   - generate_campaign_images( $campaign_id, $products, $config )
     Generates: main campaign image + one image per product (if AI images enabled)
     Deducts credits per image before each call. Refunds on individual failures.
     Returns array of image URLs or WP_Error.

   - regenerate_field( $campaign_id, $field, $context )
     Regenerates a single field (subject/headline/description/product_headline/etc.)
     Deducts correct credits for model + field type.
     Returns new string or WP_Error.

   - check_credits( $required ) — returns true/false
   - deduct_credits( $amount, $service, $task, $tokens ) — logs transaction
   - refund_credits( $amount, $description ) — logs refund transaction
   - get_credit_cost( $service, $task ) — looks up from options

2. includes/campaign/class-bcg-template.php
   - render( $campaign_id ) — full render, returns final HTML string
   - render_preview( $template_html, $settings_json, $sample_data ) — for live preview
   - apply_settings( $html, $settings ) — inlines CSS from template settings JSON
   - replace_tokens( $html, $data ) — replaces all {{token}} placeholders
   - process_conditionals( $html, $data ) — handles {{#if condition}}...{{/if}} blocks
   - render_products_block( $products ) — generates HTML for product repeater
   - get_default_template() — returns HTML from templates/default-email-template.html
   - save_default_template( $html, $settings )

   Also create: templates/default-email-template.html
   Full, email-client-safe HTML template (tables-based layout, inline-CSS ready)
   With all {{tokens}} in place. Max width 600px. Sections: header with logo + nav,
   hero image + headline + description, coupon block (conditional), products repeater,
   footer with links + copyright. Clean, professional design.

Follow WordPress Coding Standards. PHPDoc on all methods.
```

---

### PHASE 4 — Admin UI (Parallel)

---

#### Agent 4A — Admin Bootstrap & Dashboard

**Prompt:**
```
You are a WordPress plugin developer. Read CLAUDE.md for full context.

All backend classes are built. Your task: Build the admin shell and dashboard.

1. includes/admin/class-bcg-admin.php
   - add_menu_pages() — register all menu items (see CLAUDE.md menu structure)
   - enqueue_scripts( $hook ) — enqueue CSS/JS only on BCG pages
     Localise bcgData object with: ajax_url, nonce, credit_balance, stripe_pub_key,
     all model options
   - register_ajax_handlers() — register ALL wp_ajax_ handlers from CLAUDE.md
   - render_credit_widget() — add to admin bar (wp_admin_bar_menu hook)
     Shows: "💳 Credits: {N}" with link to credits page

2. admin/views/page-dashboard.php
   - Stats cards row: Total campaigns | Drafts | Sent | Credits remaining
   - Campaigns table:
     Columns: Title | Status (badge) | Products | Mailing List | Created | Actions
     Actions: Edit | Preview | Duplicate | Delete
     Empty state with "Create your first campaign" CTA
   - Quick action button: [+ New Campaign]
   - Pagination (20 per page)

Follow WordPress Coding Standards. All output escaped. Nonces on all forms/AJAX.
```

---

#### Agent 4B — Campaign Wizard (Step 1)

**Prompt:**
```
You are a WordPress plugin developer. Read CLAUDE.md for full context.

Your task: Build the campaign configuration wizard (Step 1).

1. admin/views/page-new-campaign.php
   Full wizard UI with 4 sections as described in CLAUDE.md:

   Section 1 — Campaign Basics:
   Title (required), Subject Line (text + ✨ Generate button), 
   Preview Text (text + ✨ Generate button), Mailing List (select, loaded via AJAX)

   Section 2 — Product Selection:
   Number of products (1-10 spinner), Product source (radio: Best Sellers / Least Sold /
   Latest / Manual), Category filter (multi-select checkbox tree from WC categories),
   [Preview Products] button → AJAX loads product preview cards below

   Section 3 — Coupon:
   Generate coupon checkbox (checked by default), Discount type (radio),
   Discount value (number + ✨ Generate suggestion button), Expiry days,
   Custom prefix input

   Section 4 — AI Options:
   Tone (select: Professional/Friendly/Urgent/Playful/Luxury),
   Theme/occasion (text input), Language (select),
   Generate AI images checkbox, Image style (conditional select)

   [Generate Campaign →] button — posts to bcg_generate_campaign AJAX
   Shows a progress indicator while generating (steps: Fetching products →
   Generating copy → Generating images → Finalising)
   On complete: redirect to edit campaign page

2. admin/js/bcg-campaign-builder.js
   - Product preview AJAX (bcg_preview_products)
   - Subject line / preview text generation AJAX
   - Coupon suggestion AJAX
   - Show/hide image style select based on AI images checkbox
   - Category tree multi-select behaviour
   - Generation progress steps animation
   - Form validation before submit
   - Error handling and notices

Follow WordPress Coding Standards. All output escaped. wp_nonce_field on form.
```

---

#### Agent 4C — Campaign Editor (Step 2)

**Prompt:**
```
You are a WordPress plugin developer. Read CLAUDE.md for full context.

Your task: Build the campaign editor (Step 2 — the main editing interface).

1. admin/views/page-edit-campaign.php
   Two-column layout: Editor (left, ~60%) + Live Preview iframe (right, ~40%)

   Header section:
   - Main headline textarea + ↻ Regenerate button
   - Main image display + ↻ Regenerate Image + Use Custom Image (media uploader)
   - Main description (wp_editor / rich textarea) + ↻ Regenerate
   - Subject line + ↻ Regenerate

   Coupon section (shown if campaign has coupon):
   - Coupon code (editable) + Regenerate code button
   - Discount text (editable) + ↻ Regenerate
   - Expiry date picker

   Products repeater:
   - jQuery UI Sortable container
   - Each product card (partial: admin/views/partials/product-card.php):
     Product image thumbnail, product name header,
     AI Headline textarea + ↻ Regenerate,
     Short Description textarea + ↻ Regenerate,
     Show Buy Button checkbox, Image source radio (product/AI),
     ↻ Regenerate Image button (shown if AI selected),
     ✕ Remove button
   - [+ Add Another Product] button → product search modal → generates AI content

   Sticky actions bar (bottom):
   Back | Save Draft | Preview Email | Send Test | Create in Brevo | Schedule | Send Now

2. admin/views/partials/product-card.php
   Reusable product card partial (used in editor repeater)

3. admin/js/bcg-regenerate.js
   - Binds all ↻ Regenerate buttons
   - Posts to bcg_regenerate_field or bcg_regenerate_product
   - Shows loading spinner on button during request
   - Updates the field value on success
   - Triggers live preview refresh
   - Shows error notice on failure (with credits refunded message if applicable)
   - Add product modal: search box → AJAX product search → select → generate + insert card
   - Product sort: jQuery UI Sortable init + save order on stop event
   - Save Draft: posts to bcg_save_campaign AJAX
   - Create in Brevo: bcg_create_brevo_campaign AJAX → shows Brevo campaign URL on success
   - Schedule: date/time picker modal → bcg_schedule_campaign AJAX
   - Send Now: confirmation modal → bcg_send_campaign AJAX

4. Live preview:
   Iframe src updates via bcg_preview_template AJAX whenever any field changes (300ms debounce)

Follow WordPress Coding Standards. All output escaped. PHPDoc on all methods.
```

---

#### Agent 4D — Template Editor & Stats

**Prompt:**
```
You are a WordPress plugin developer. Read CLAUDE.md for full context.

Your task: Build the template editor page and the Brevo stats dashboard.

1. admin/views/page-template-editor.php
   Three-panel layout (see CLAUDE.md template editor spec):

   Left panel — Settings tabs:
   Branding (logo, width, alt, link),
   Layout (max-width, product layout, products per row, show coupon),
   Colours (6 colour pickers: page bg, content bg, primary, text, link, button),
   Button (button bg, button text colour, border radius),
   Typography (font family, headline size, body size, line height),
   Navigation (show nav toggle, repeatable nav links),
   Footer (footer text textarea, repeatable footer links)

   Centre panel — HTML editor:
   CodeMirror instance (HTML mode, syntax highlighting)
   Visual/Code toggle button
   Token reference cheat sheet (collapsible)

   Right panel — Live preview:
   iframe (updates via bcg_preview_template AJAX, 300ms debounce)
   Desktop/Mobile toggle (changes iframe width)

   Save bar: Save as Default | Save to Campaign | Reset to Default

2. admin/js/bcg-template-editor.js
   - Initialise CodeMirror on textarea
   - All settings panel inputs → update templateSettings JS object
   - Any change → debounce 300ms → POST to bcg_preview_template → update iframe srcdoc
   - Visual/Code toggle: sync CodeMirror content with visual settings and vice versa
   - Colour picker inputs (use native <input type="color"> or wp-color-picker)
   - Repeatable nav/footer link rows (add/remove)
   - Save as Default → bcg_update_template AJAX (scope: default)
   - Save to Campaign → bcg_update_template AJAX (scope: campaign, campaign_id)
   - Reset → confirm modal → reload page

3. admin/views/page-stats.php
   - 4 stat cards: Total campaigns | Avg open rate | Avg click rate | Total sent
   - Filter bar: date range picker + status select + [Apply] button
   - Campaigns stats table:
     Campaign name | Sent date | Recipients | Opens | Open Rate | Clicks | Click Rate | Unsubs | Status
     Clicking a row expands inline to show full breakdown + link to Brevo
   - Loading skeleton shown while fetching from Brevo API
   - Cache notice: "Stats updated X minutes ago [Refresh]"

4. includes/admin/class-bcg-stats.php
   - get_stats_data( $filters ) — fetches from BCG_Brevo, applies filters, returns formatted array
   - format_rate( $value ) — formats decimal as percentage string
   - Transient-aware caching wrapper

Follow WordPress Coding Standards. All output escaped. Nonces on all AJAX.
```

---

### PHASE 5 — AJAX Handlers (Sequential — after all UI and logic is complete)

#### Agent 5 — All AJAX Handlers

**Prompt:**
```
You are a WordPress plugin developer. Read CLAUDE.md for full context.

All classes and views are built. Your task: implement ALL AJAX handler methods
inside includes/admin/class-bcg-admin.php (or a dedicated class-bcg-ajax.php
if cleaner — your choice).

Implement every handler from the AJAX table in CLAUDE.md:

bcg_generate_campaign — orchestrates full generation:
  1. Validate inputs (products config, campaign config)
  2. BCG_Campaign::create_draft()
  3. BCG_Product_Selector::get_products()
  4. BCG_Coupon::create_coupon() if requested
  5. BCG_AI_Manager::generate_campaign_copy()
  6. BCG_AI_Manager::generate_campaign_images() if enabled
  7. BCG_Campaign::update() with all generated content
  8. Return campaign ID + redirect URL

bcg_regenerate_field — single field regeneration via BCG_AI_Manager::regenerate_field()
bcg_regenerate_product — regenerate all AI content for one product
bcg_add_product — add product to campaign + generate AI content
bcg_preview_products — return lightweight product list HTML
bcg_save_campaign — save all edited fields to DB
bcg_send_test — BCG_Brevo::send_test_email()
bcg_create_brevo_campaign — BCG_Template::render() + BCG_Brevo::create_campaign()
bcg_send_campaign — BCG_Brevo::send_campaign_now()
bcg_schedule_campaign — BCG_Brevo::schedule_campaign()
bcg_update_template — BCG_Template::save_default_template() or campaign-specific save
bcg_preview_template — BCG_Template::render_preview(), return rendered HTML
bcg_stripe_create_intent — BCG_Stripe::create_payment_intent()
bcg_stripe_confirm — BCG_Stripe::confirm_payment(), add credits, log transaction
bcg_get_brevo_lists — BCG_Brevo::get_contact_lists()
bcg_test_api_key — test the specified API (openai/gemini/brevo/stripe)
bcg_generate_coupon — BCG_Coupon::create_coupon()

Every handler MUST:
1. wp_verify_nonce( $_POST['nonce'], 'bcg_nonce' ) — die on fail
2. current_user_can('manage_woocommerce') — die on fail
3. Sanitise all $_POST inputs
4. Return wp_send_json_success($data) or wp_send_json_error($message)

Follow WordPress Coding Standards strictly.
```

---

### PHASE 6 — Final Polish (Sequential)

#### Agent 6 — Integration, Testing & Cleanup

**Prompt:**
```
You are a WordPress plugin developer. Read CLAUDE.md for full context.

All plugin code is now written. Your task: final integration and polish.

1. Review every PHP file for:
   - Missing require/include statements in class-bcg-plugin.php
   - Any hardcoded strings not wrapped in __()
   - Any output not escaped
   - Any DB queries not using $wpdb->prepare()
   - Missing PHPDoc on public methods

2. Create admin/css/bcg-admin.css with complete styles:
   - Admin bar credit widget
   - Settings page tabs
   - Campaign dashboard table
   - Stats cards and table
   - Product card in repeater (with drag handle)
   - Action buttons bar (sticky bottom)
   - Progress indicator for generation
   - Modal overlays (product picker, preview, schedule)
   - Regenerate button loading state
   - Success/error notice styles
   - Credits page pack cards
   - Template editor three-panel layout
   - Responsive adjustments for 1200px+ screens
   All classes prefixed with bcg-. No !important unless documented.

3. Create assets/css/bcg-email.css
   Base styles for the email template (these get inlined).

4. Create package.json with:
   - lint script using eslint
   - build script (minify JS/CSS if needed)

5. Verify the scaffold matches the directory structure in CLAUDE.md exactly.
   Create any missing empty files.

6. Update build-log.md with a summary of what was built.

7. Do a final read of the main plugin file and ensure activation hook,
   deactivation hook, and plugin class instantiation are all wired correctly.

Report any issues found and fixed.
```

---

## Build Log

Create `build-log.md` in the plugin root and append a line after each agent completes:

```markdown
# Build Log

- [x] Phase 1A — Bootstrap & Database — COMPLETE
- [x] Phase 1B — Settings & Options — COMPLETE
- [x] Phase 2A — Brevo Client — COMPLETE
- [x] Phase 2B — OpenAI Client — COMPLETE
- [x] Phase 2C — Gemini Client — COMPLETE
- [x] Phase 2D — Stripe Integration — COMPLETE
- [x] Phase 3A — Campaign & Product Logic — COMPLETE
- [x] Phase 3B — AI Manager & Template Engine — COMPLETE
- [x] Phase 4A — Admin Bootstrap & Dashboard — COMPLETE
- [x] Phase 4B — Campaign Wizard (Step 1) — COMPLETE
- [x] Phase 4C — Campaign Editor (Step 2) — COMPLETE
- [x] Phase 4D — Template Editor & Stats — COMPLETE
- [x] Phase 5 — AJAX Handlers — COMPLETE
- [x] Phase 6 — Integration & Polish — COMPLETE
```

---

## Orchestrator Checklist

Before calling the build complete:

- [ ] All 14 agents have completed
- [ ] `build-log.md` shows all phases complete
- [ ] Plugin activates without PHP errors
- [ ] All 4 DB tables created on activation
- [ ] Settings page loads with all 5 tabs
- [ ] No files listed in CLAUDE.md directory structure are empty
- [ ] `composer.json` is valid JSON with correct PSR-4 autoload paths
