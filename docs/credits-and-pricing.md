# Credits & Pricing

## Overview

The plugin uses a **credit system** to meter AI usage. Credits are purchased via Stripe and deducted for each AI generation task.

This approach gives you:
- Full control over AI costs
- Per-task transparency
- No ongoing subscription — pay only for what you use
- Full transaction history in the admin

---

## Credit Packs

Credits are purchased in pre-set packs via Stripe. Admins can adjust pack values in **Settings → Stripe**.

| Pack | Credits | Default Price | Cost per Credit |
|---|---|---|---|
| Starter | 100 | £5.00 | £0.050 |
| Standard | 300 | £12.00 | £0.040 |
| Pro | 1,000 | £35.00 | £0.035 |

Credits do not expire.

---

## Credit Costs per Task

### Text Generation (OpenAI)

| Model | Task | Credits |
|---|---|---|
| GPT-4o | Full campaign copy (headline + description + subject + preview + product headlines/descriptions) | 5 |
| GPT-4o | Single field regeneration | 2 |
| GPT-4o Mini | Full campaign copy | 1 |
| GPT-4o Mini | Single field regeneration | 1 |
| GPT-4 Turbo | Full campaign copy | 5 |
| GPT-4 Turbo | Single field regeneration | 2 |

### Image Generation (Gemini)

| Model | Task | Credits |
|---|---|---|
| Gemini 1.5 Pro | Main campaign header image | 10 |
| Gemini 1.5 Pro | Single product image | 10 |
| Gemini 1.5 Flash | Main campaign header image | 3 |
| Gemini 1.5 Flash | Single product image | 3 |

### Example: A full campaign with 3 products

Using **GPT-4o Mini** + **Gemini 1.5 Flash**:

| Task | Credits |
|---|---|
| All campaign copy | 1 |
| Main header image | 3 |
| 3× product images | 3 × 3 = 9 |
| **Total** | **13 credits** |

Cost from Starter pack: 13 × £0.05 = **£0.65 per campaign**

---

Using **GPT-4o** + **Gemini 1.5 Pro**:

| Task | Credits |
|---|---|
| All campaign copy | 5 |
| Main header image | 10 |
| 3× product images | 3 × 10 = 30 |
| **Total** | **45 credits** |

Cost from Pro pack: 45 × £0.035 = **£1.58 per campaign**

---

## Credit Deduction Logic

Credits are deducted **before** the AI call is made:

1. Check user's balance ≥ required credits
2. If insufficient → show notice, abort, do not call API
3. If sufficient → deduct credits, make API call
4. On success → task completes normally
5. On API error → **refund credits in full** + log error

This prevents credits being consumed when the API fails.

---

## Transaction Log

Every credit movement is logged in `bcg_transactions`:

| Field | Description |
|---|---|
| `type` | `topup`, `usage`, or `refund` |
| `amount` | Credits added or deducted |
| `balance_after` | Balance immediately after transaction |
| `description` | Human-readable description |
| `ai_service` | Which AI service was used |
| `ai_task` | What the task was |
| `tokens_used` | Actual tokens used (for OpenAI) |

The full log is visible in **Credits & Billing → Transaction History** with filtering by type and date.

---

## Balance Widget

A persistent credit balance widget appears in:
- The WordPress admin bar (top right)
- The top of every Brevo Campaigns page

```
💳 Credits: 142   [Top Up]
```

Clicking **Top Up** opens an inline modal with the Stripe payment flow.

---

## Configuring Credit Costs

Admins can adjust the credit cost per task in **Settings → AI Models**. This allows you to fine-tune the credit economy if you adjust pack prices.

Default credit-to-money ratio: **1 credit = £0.05** (£5 pack / 100 credits). Adjustable in settings.

---

## Frequently Asked Questions

**What happens if I run out of credits mid-generation?**  
The generation is cancelled before the API call. No credits are lost. A notice is shown with a link to top up.

**What if the AI API goes down during generation?**  
Credits are automatically refunded. An error is logged and a notice is shown in the admin.

**Do credits expire?**  
No. Credits remain on your account until used.

**Can I get a refund on unused credits?**  
This is a manual process — contact Red Frog Studio directly.

**Can I change the credit pack prices?**  
Yes. Go to **Settings → Stripe** to adjust pack names, credit amounts, and prices.
