# Build wp-fetch — WordPress External API Plugin

You are building a **portfolio project** for a Senior AI Engineer's public GitHub. It must be impressive, clean, and production-grade. Read these docs before writing any code:

1. **`W03-wordpress-api-plugin.md`** — Complete project spec: architecture, phases, API manager, caching, rate limiting, shortcodes, REST API, admin UI, commit plan. This is your primary blueprint. Follow it phase by phase.
2. **`github-portfolio.md`** — Portfolio goals and Definition of Done (Level 1 + Level 2). Understand the quality bar.
3. **`github-portfolio-checklist.md`** — Pre-publish checklist. Every item must pass before you're done.

---

## Instructions

### Read first, build second
Read all three docs completely before writing a single line of code. Understand the API source management system, the transient caching with stale fallback, the rate limiter for outbound requests, the shortcode rendering, the WP REST API endpoints, and the admin settings page with dashboard widget.

### Follow the phases in order
The project spec has 4 phases. Do them in order:
1. **Plugin Scaffold & API Manager** — plugin setup with PSR-4 autoloading, API source model with CRUD, API manager with HTTP fetch and response parsing
2. **Caching & Rate Limiting** — transient caching with stale data fallback, rate limiter using transients, integrated fetch flow (cache → rate limit → fetch → fallback)
3. **Output** — shortcode handler with templates, WP REST API endpoints with permissions
4. **Admin UI & Polish** — settings page with source management, dashboard widget with status indicators, error handling, tests, README

### Commit frequently
Follow the commit plan in the spec. Use **conventional commits**. Each commit should be a logical unit.

### Quality non-negotiables
- **WordPress coding standards.** PHPCS with `WordPress` ruleset must pass. Proper sanitization (`sanitize_text_field`, `esc_url`), output escaping (`esc_html`, `esc_attr`, `wp_kses`), nonce verification on all form submissions.
- **Transient caching is mandatory.** Every external API call must check cache first. Stale cache must be available as fallback when the API is down. This is the most important production pattern.
- **Rate limiting protects external APIs.** Outbound requests must be counted and limited. When exceeded, serve stale cache gracefully — never error out to the user.
- **PSR-4 autoloading via Composer.** No manual `require` statements. `composer.json` with proper autoload configuration.
- **Settings API, not custom HTML forms.** Use `register_setting()`, `add_settings_section()`, `add_settings_field()` for the admin page.
- **REST API with proper permissions.** Data endpoints are public (for AJAX widgets). Admin endpoints require `manage_options` capability. Always validate permissions.
- **Shortcode is the public interface.** `[api_data source="weather"]` must work in any post or page. Template overrides from the theme directory must be supported.
- **Auth values encrypted in options.** API keys and tokens must not be stored as plain text. Use `wp_hash()` or similar.
- **PHPCS clean.** Run PHPCS with WordPress standards. Zero errors, zero warnings.

### What NOT to do
- Don't use `file_get_contents()` or `curl` directly. Use `wp_remote_get()` and `wp_remote_post()`.
- Don't store data in custom database tables. Use WordPress options and transients.
- Don't skip nonce verification on any form submission or AJAX request.
- Don't output unescaped data anywhere. Every variable in HTML must be escaped.
- Don't leave `// TODO` or `// FIXME` comments anywhere.
- Don't commit API keys, tokens, or real endpoint URLs in any file.

---

## GitHub Username

The GitHub username is **devaloi**. For any GitHub URLs, use `github.com/devaloi/wp-fetch`. For Composer package name, use `devaloi/wp-fetch`.

## Start

Read the three docs. Then begin Phase 1 from `W03-wordpress-api-plugin.md`.
