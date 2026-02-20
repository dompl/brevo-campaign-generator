# Contributing to Brevo Campaign Generator for WooCommerce

This is a **private agency plugin** maintained by Red Frog Studio. Contributions are by invitation only. This document covers the internal development workflow.

---

## Development Setup

### Prerequisites

- PHP 8.1+
- Composer
- Node.js 18+ and npm
- WordPress 6.3+ local install (e.g. [LocalWP](https://localwp.com))
- WooCommerce 8.0+
- Git

### Getting Started

```bash
# Clone the repo
git clone https://github.com/red-frog-studio/brevo-campaign-generator.git
cd brevo-campaign-generator

# Install PHP dependencies
composer install

# Install JS dependencies and build assets
npm install
npm run dev
```

Symlink or copy the plugin folder into your local WordPress `wp-content/plugins/` directory, then activate from the WordPress admin.

### Environment Variables

Copy `.env.example` to `.env` and fill in your test API keys. Never commit real API keys.

```
OPENAI_API_KEY=sk-...
GEMINI_API_KEY=AIza...
BREVO_API_KEY=xkeysib-...
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
```

---

## Branch Strategy

| Branch | Purpose |
|---|---|
| `main` | Stable, production-ready |
| `develop` | Integration branch for features |
| `feature/feature-name` | Individual feature branches |
| `fix/issue-description` | Bug fix branches |
| `release/x.x.x` | Release preparation |

All work branches off `develop`. PRs merge back into `develop`. Releases merge `develop` → `main`.

### Naming Conventions

```
feature/gemini-image-retry
fix/stripe-webhook-duplicate-credits
release/1.1.0
```

---

## Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
type(scope): short description

[optional body]

[optional footer]
```

**Types:** `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

**Examples:**
```
feat(ai): add Gemini 2.0 Flash model support
fix(credits): correct credit deduction on API timeout refund
docs(readme): update installation steps
chore(deps): bump openai client to 2.1.0
```

---

## Code Standards

### PHP

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- All classes prefixed with `BCG_`
- All option names prefixed with `bcg_`
- All hooks prefixed with `bcg_`
- PHPDoc on all public methods
- No inline HTML — use view files in `admin/views/`

Run the linter before committing:
```bash
composer run lint
```

### JavaScript

- ES6+
- No jQuery for new code where avoidable (legacy WP admin JS excepted)
- All admin JS prefixed with `bcg` in function/variable names

Run the linter:
```bash
npm run lint
```

### CSS

- BEM-ish naming: `.bcg-block__element--modifier`
- All classes prefixed with `bcg-`
- No `!important` unless absolutely unavoidable (document why)

---

## Pull Request Process

1. Branch from `develop`
2. Write code + tests
3. Run linter: `composer run lint && npm run lint`
4. Open PR against `develop`
5. Fill in the PR template completely
6. Request review
7. Address feedback
8. Squash and merge

PRs must not decrease code quality, introduce new lint errors, or break existing behaviour.

---

## Versioning

This project uses [Semantic Versioning](https://semver.org):

- **MAJOR** — breaking changes (e.g. DB schema changes requiring migration)
- **MINOR** — new backwards-compatible features
- **PATCH** — backwards-compatible bug fixes

Update `CHANGELOG.md`, `README.md` version badge, and the plugin header version on every release.

---

## Reporting Issues

Use [GitHub Issues](../../issues) with the appropriate template:

- **Bug Report** — something is broken
- **Feature Request** — something you'd like added

For security vulnerabilities, do **not** open a public issue — contact Red Frog Studio directly.
