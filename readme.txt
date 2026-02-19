=== WP Fetch ===
Contributors: devaloi
Tags: api, external-data, shortcode, caching, rest-api
Requires at least: 6.5
Tested up to: 6.7
Requires PHP: 8.2
Stable tag: 1.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Fetch and display external API data with transient caching, rate limiting, and shortcodes.

== Description ==

WP Fetch is a WordPress plugin that lets you configure multiple external API sources and display their data using shortcodes or the REST API. It includes:

* **Transient caching** with configurable TTL per source
* **Stale data fallback** when APIs are down
* **Rate limiting** for outbound requests
* **Shortcode** `[api_data source="name"]` for posts and pages
* **REST API endpoints** for programmatic and AJAX access
* **Admin settings page** with full CRUD for API sources
* **Dashboard widget** showing API health status
* **Encrypted auth storage** for API keys and tokens

== Installation ==

1. Upload the `wp-fetch` folder to `/wp-content/plugins/`
2. Run `composer install` inside the plugin directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to **Settings → WP Fetch** to configure your API sources

== Frequently Asked Questions ==

= How do I add an API source? =

Go to Settings → WP Fetch and fill in the source form with the API name, URL, authentication details, and cache settings.

= What happens when an API is down? =

WP Fetch serves stale cached data (up to 24 hours old) as a fallback. If no cached data exists, it displays the configurable fallback HTML.

= How does rate limiting work? =

Each source has a per-minute request limit (default: 30). When exceeded, the plugin serves cached data instead of making new requests.

= Can I customize the shortcode output? =

Yes. Create a template file in your theme at `your-theme/wp-fetch/{template-name}.php` and reference it with `[api_data source="name" template="template-name"]`.

== Changelog ==

= 1.0.0 =
* Initial release
* Multiple API source management
* Transient caching with stale fallback
* Rate limiting for outbound requests
* `[api_data]` shortcode
* WP REST API endpoints
* Admin settings page
* Dashboard status widget
