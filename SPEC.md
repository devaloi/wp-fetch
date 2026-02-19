# W03: wp-fetch — WordPress External API Plugin

**Catalog ID:** W03 | **Size:** S | **Language:** PHP / WordPress
**Repo name:** `wp-fetch`
**One-liner:** A WordPress plugin that fetches and displays external API data in WP admin and via shortcodes — with transient caching, rate limiting, error handling, and a settings page.

---

## Why This Stands Out

- **Full WordPress plugin lifecycle** — settings page, shortcodes, REST API, admin widgets, activation/deactivation hooks
- **Transient caching with configurable TTL** — shows understanding of WordPress performance patterns
- **Multiple API sources** — configure N external APIs from admin, each with its own URL, auth, cache TTL
- **Rate limiting for outbound requests** — prevent hammering external APIs, shows production awareness
- **Shortcode + REST API** — two consumption models: `[api_data source="weather"]` in posts, and `/wp-json/wp-fetch/v1/data/weather` for AJAX
- **Admin dashboard widget** — API health status at a glance, last fetch times, error counts
- **Error handling with fallback** — cached stale data shown when API is down, user-friendly error messages
- **WordPress coding standards** — PHPCS, proper sanitization, nonces, escaping, i18n-ready

---

## Architecture

```
wp-fetch/
├── wp-fetch.php                   # Plugin entry point, constants, bootstrap
├── includes/
│   ├── class-wp-fetch.php         # Main plugin class (singleton, hooks registration)
│   ├── class-api-manager.php      # Manage multiple API sources, fetch, cache
│   ├── class-api-source.php       # Single API source: URL, headers, auth, TTL
│   ├── class-cache.php            # Transient caching wrapper with TTL management
│   ├── class-rate-limiter.php     # Rate limiting for outbound API requests
│   ├── class-shortcode.php        # [api_data] shortcode handler
│   ├── class-rest-api.php         # WP REST API endpoints
│   ├── class-admin-settings.php   # Settings page (WP Settings API)
│   ├── class-admin-widget.php     # Dashboard widget showing API status
│   └── class-error-handler.php    # Error handling, fallback logic, logging
├── assets/
│   ├── css/
│   │   └── admin.css              # Admin settings + widget styles
│   └── js/
│       ├── admin.js               # Settings page interactions
│       └── widget.js              # AJAX refresh for dashboard widget
├── templates/
│   ├── settings-page.php          # Admin settings page template
│   ├── shortcode-output.php       # Default shortcode render template
│   └── widget-output.php          # Dashboard widget template
├── tests/
│   ├── bootstrap.php
│   ├── test-api-manager.php
│   ├── test-cache.php
│   ├── test-rate-limiter.php
│   ├── test-shortcode.php
│   └── test-rest-api.php
├── languages/
│   └── wp-fetch.pot               # Translation template
├── readme.txt                     # WordPress.org-style readme
├── composer.json
├── phpcs.xml
├── Makefile
├── .gitignore
├── LICENSE
└── README.md
```

---

## Features

### Admin Settings Page

- Add/edit/remove API sources
- Per-source settings: name, URL, HTTP method, headers (JSON), auth type (none, API key, Bearer token)
- Per-source cache TTL (default 5 minutes)
- Global rate limit (requests per minute)
- Test connection button (AJAX — hit the API and show response status)
- Sanitize and validate all inputs

### API Source Configuration

| Field | Type | Description |
|-------|------|-------------|
| Name | string | Unique identifier (slug) |
| URL | URL | API endpoint URL |
| Method | select | GET or POST |
| Headers | JSON | Custom headers (e.g., Accept, API key) |
| Auth Type | select | none, api_key, bearer |
| Auth Value | string | API key or token value (stored encrypted) |
| Cache TTL | integer | Cache duration in seconds (0 = no cache) |
| Transform | string | JSONPath-like expression to extract data |
| Fallback | text | HTML to display when API fails |

### Shortcode

```
[api_data source="weather"]
[api_data source="weather" template="custom-weather"]
[api_data source="weather" field="temperature"]
[api_data source="quotes" limit="5"]
```

### REST API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/wp-json/wp-fetch/v1/sources` | List configured API sources |
| GET | `/wp-json/wp-fetch/v1/data/{source}` | Fetch data from a source (cached) |
| POST | `/wp-json/wp-fetch/v1/refresh/{source}` | Force refresh (clear cache, re-fetch) |
| GET | `/wp-json/wp-fetch/v1/status` | Health status of all sources |

### Dashboard Widget

- Table of configured API sources
- Status indicator: green (ok), yellow (stale cache), red (error)
- Last successful fetch timestamp
- Error count in last 24h
- Quick refresh button per source

---

## Caching Strategy

```
Request for source data:
  1. Check transient cache → hit? Return cached data
  2. Check rate limiter → exceeded? Return stale cache or fallback
  3. Fetch from external API
  4. Success → store in transient, return data
  5. Failure → log error, return stale cache if available, else fallback HTML
```

### Rate Limiting

- Configurable: N requests per minute per source
- Uses transient to track request count per window
- When exceeded: skip fetch, serve stale cache, log warning
- Default: 30 requests per minute per source

---

## Tech Stack

| Component | Choice |
|-----------|--------|
| Platform | WordPress 6.5+ |
| Language | PHP 8.3 |
| Caching | WordPress Transients API |
| HTTP Client | `wp_remote_get()` / `wp_remote_post()` |
| Admin UI | WordPress Settings API |
| REST API | WP REST API (register_rest_route) |
| Testing | PHPUnit with WP test suite |
| Linting | PHPCS with WordPress standards |
| Autoloading | PSR-4 via Composer |
| Build | None (vanilla PHP, no transpilation) |

---

## Phased Build Plan

### Phase 1: Plugin Scaffold & API Manager

**1.1 — Plugin setup**
- Plugin header, constants (version, paths), activation/deactivation hooks
- Main class with singleton pattern, hook registration
- PSR-4 autoloading via Composer
- phpcs.xml with WordPress coding standards

**1.2 — API source model**
- `API_Source` class: name, URL, method, headers, auth, TTL, transform, fallback
- Stored as serialized array in `wp_options`
- CRUD: add, update, remove, get, list all sources
- Sanitization on save, validation on load

**1.3 — API manager**
- `API_Manager` class: orchestrates fetching from sources
- `fetch(source_name)` — get data from a configured source
- HTTP request via `wp_remote_get()` / `wp_remote_post()`
- Set headers, auth token, timeout (configurable)
- Parse response: check status code, decode JSON
- Transform: extract nested data using dot-notation path (e.g., `data.results`)
- Tests: fetch mock API, parse response, transform data

### Phase 2: Caching & Rate Limiting

**2.1 — Transient cache**
- `Cache` class wrapping WordPress Transients API
- `get(key)` — return cached data or null
- `set(key, data, ttl)` — store with expiration
- `delete(key)` — invalidate
- `get_stale(key)` — return expired data if available (separate long-TTL transient)
- Stale cache: on fetch failure, return last known good data
- Tests: set/get, expiry, stale data fallback

**2.2 — Rate limiter**
- `Rate_Limiter` class using transients for request counting
- Sliding window: count requests in current minute per source
- `allow(source_name)` → bool (under limit)
- `record(source_name)` — increment counter
- When exceeded: log, skip fetch, return stale cache
- Tests: under limit allows, over limit blocks, window resets

**2.3 — Integrated fetch flow**
- `API_Manager::fetch()` integrates: cache check → rate limit → HTTP fetch → cache store → fallback
- Error handler: log errors to custom option (last N errors per source)
- Tests: cache hit skips HTTP, rate limit serves stale, API error serves fallback

### Phase 3: Output — Shortcode & REST API

**3.1 — Shortcode**
- Register `[api_data]` shortcode
- Attributes: source (required), template, field, limit
- Fetch data via API_Manager
- Render: default template or custom template from theme directory
- Template variables: `$data`, `$source`, `$cached` (bool)
- Fallback HTML when source not found or data unavailable
- Tests: shortcode renders, missing source shows fallback, field extraction works

**3.2 — REST API endpoints**
- Register routes under `wp-fetch/v1` namespace
- `GET /sources` — list configured sources (admin only)
- `GET /data/{source}` — fetch and return data (public, cached)
- `POST /refresh/{source}` — clear cache and re-fetch (admin only)
- `GET /status` — health status of all sources (admin only)
- Permission callbacks: public for data, `manage_options` for admin routes
- Tests: endpoints return correct data, permissions enforced

### Phase 4: Admin UI & Polish

**4.1 — Settings page**
- Register under Settings menu: "WP Fetch"
- List configured API sources in a table
- Add/edit source form: all fields from API Source Configuration table
- Delete source with confirmation
- Test connection button (AJAX → `wp_remote_get` → show status)
- Save with nonce verification, sanitization
- Enqueue admin CSS/JS only on settings page

**4.2 — Dashboard widget**
- Register dashboard widget via `wp_add_dashboard_widget()`
- Show table: source name, status indicator, last fetch, error count
- AJAX refresh button per source
- Auto-refresh on page load
- Enqueue widget JS/CSS only on dashboard

**4.3 — Error handling polish**
- Error log stored in options (last 50 errors per source)
- Error display in admin: dismissible notices for persistent failures
- Fallback content: configurable per source, graceful degradation

**4.4 — Tests**
- API Manager: fetch, cache integration, rate limit, error fallback
- Cache: set, get, stale, delete
- Rate Limiter: allow, block, window reset
- Shortcode: render, missing source, field extraction
- REST API: endpoints, permissions, data format
- Settings: sanitization, validation

**4.5 — README**
- Install and activate instructions
- Configuration: adding API sources
- Shortcode usage with examples
- REST API endpoints reference
- Caching and rate limiting explanation
- Template customization guide
- Hook/filter reference for developers

**4.6 — WordPress.org readme.txt**
- Standard format: description, installation, FAQ, changelog
- Screenshots descriptions

**4.7 — Final checks**
- PHPCS clean (WordPress coding standards)
- All text is i18n-ready (`__()`, `esc_html__()`)
- No direct database queries (use WP APIs)
- No hardcoded URLs, paths, or credentials
- Auth values stored encrypted in options
- Activate/deactivate/uninstall lifecycle clean (clean up options on uninstall)

---

## Commit Plan

1. `feat: scaffold plugin with main class and PSR-4 autoloading`
2. `feat: add API source model with CRUD operations`
3. `feat: add API manager with HTTP fetch and response parsing`
4. `feat: add transient caching with stale data fallback`
5. `feat: add rate limiter for outbound API requests`
6. `feat: add integrated fetch flow with cache, rate limit, fallback`
7. `feat: add shortcode for displaying API data`
8. `feat: add WP REST API endpoints`
9. `feat: add admin settings page with source management`
10. `feat: add dashboard widget with API status`
11. `test: add unit tests for API manager, cache, rate limiter`
12. `refactor: sanitization, escaping, i18n`
13. `docs: add README and WordPress readme.txt`
14. `chore: PHPCS cleanup and final polish`
