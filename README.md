# WP Fetch

[![CI](https://github.com/devaloi/wp-fetch/actions/workflows/ci.yml/badge.svg)](https://github.com/devaloi/wp-fetch/actions/workflows/ci.yml)

A WordPress plugin that fetches and displays external API data in the admin dashboard and via shortcodes — with transient caching, rate limiting, error handling, and a settings page.

## Features

- **Multiple API Sources** — Configure N external APIs from the admin, each with its own URL, auth, and cache TTL
- **Transient Caching** — Configurable per-source TTL with stale-data fallback when APIs are down
- **Rate Limiting** — Prevent hammering external APIs with configurable per-minute limits
- **Shortcode** — Display API data anywhere with `[api_data source="weather"]`
- **REST API** — Four endpoints for AJAX consumption and programmatic access
- **Dashboard Widget** — API health status at a glance with refresh buttons
- **Settings Page** — Full CRUD for API sources with test connection button
- **Encrypted Auth Storage** — API keys and tokens stored encrypted in the database

## Requirements

- WordPress 6.5+
- PHP 8.2+
- Composer (for autoloading)

## Installation

```bash
git clone https://github.com/devaloi/wp-fetch.git
cd wp-fetch
composer install
```

Copy or symlink the `wp-fetch` directory into your WordPress `wp-content/plugins/` directory, then activate from the WordPress admin.

## Configuration

### Adding API Sources

1. Go to **Settings → WP Fetch** in the WordPress admin
2. Fill in the source form:
   - **Name** — Unique slug (e.g., `weather`, `quotes`)
   - **URL** — Full API endpoint URL
   - **Method** — GET or POST
   - **Headers** — JSON object of custom headers
   - **Auth Type** — None, API Key, or Bearer Token
   - **Auth Value** — Your API key or token (stored encrypted)
   - **Cache TTL** — Cache duration in seconds (0 disables caching)
   - **Transform** — Dot-notation path to extract nested data (e.g., `data.results`)
   - **Fallback** — HTML to display when the API is unavailable
3. Click **Save Source**
4. Use the **Test** button to verify connectivity

### Global Settings

- **Rate Limit** — Maximum outbound requests per minute per source (default: 30)

## Usage

### Shortcode

Display API data in any post or page:

```
[api_data source="weather"]
[api_data source="weather" field="temperature"]
[api_data source="weather" template="custom-weather"]
[api_data source="quotes" limit="5"]
```

| Attribute  | Description                                      |
|------------|--------------------------------------------------|
| `source`   | Required. Source slug configured in settings      |
| `field`    | Dot-notation path to extract a specific field     |
| `template` | Custom template name (looked up in theme)         |
| `limit`    | Limit array results to N items                    |

### REST API Endpoints

| Method | Endpoint                            | Auth     | Description                    |
|--------|-------------------------------------|----------|--------------------------------|
| GET    | `/wp-json/wp-fetch/v1/sources`      | Admin    | List all configured sources    |
| GET    | `/wp-json/wp-fetch/v1/data/{source}`| Public   | Fetch data from a source       |
| POST   | `/wp-json/wp-fetch/v1/refresh/{source}` | Admin | Force refresh a source       |
| GET    | `/wp-json/wp-fetch/v1/status`       | Admin    | Health status of all sources   |

### Dashboard Widget

The **WP Fetch — API Status** widget appears on the WordPress dashboard showing:
- Status indicator per source (green/yellow/red)
- Error count in the last 24 hours
- Quick refresh button per source

## Template Customization

Override the default shortcode output by creating a template in your theme:

```
your-theme/wp-fetch/{template-name}.php
```

Available variables in templates:
- `$data` — The fetched data (array or scalar)
- `$source_name` — The source slug
- `$cached` — Whether the data came from cache (bool)

## Caching Strategy

```
1. Check transient cache → hit? Return cached data
2. Check rate limiter → exceeded? Return stale cache or fallback
3. Fetch from external API
4. Success → store in transient, return data
5. Failure → log error, return stale cache if available, else fallback HTML
```

Stale data is kept in a separate long-TTL transient (24 hours) so it can be served as a fallback when the primary cache expires and the API is unreachable.

## Hooks & Filters

The plugin uses standard WordPress patterns. Key integration points:

- **Activation** — Creates default options (`wp_fetch_sources`, `wp_fetch_rate_limit`)
- **Deactivation** — Flushes the object cache
- **Uninstall** — Removes all options and transients

## Running Tests

```bash
composer install
./vendor/bin/phpunit
```

## Tech Stack

| Component   | Technology                   |
|-------------|------------------------------|
| Platform    | WordPress 6.5+               |
| Language    | PHP 8.2+                     |
| Caching     | WordPress Transients API     |
| HTTP Client | `wp_remote_get()` / `wp_remote_post()` |
| Admin UI    | WordPress Settings API       |
| REST API    | WP REST API                  |
| Testing     | PHPUnit 10                   |
| Autoloading | Composer classmap            |

## License

MIT — see [LICENSE](LICENSE) for details.
