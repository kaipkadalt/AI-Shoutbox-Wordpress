# AI-Shoutbox-Wordpress
A simple, real-time shoutbox widget and shortcode with OpenAI (GPT) integration, reCAPTCHA v3 protection, and customizable texts.

=== AI Shoutbox ===
Contributors: kaipkada, https://www.kaipkada.lt
Tags: chat, shoutbox, ai, openai, widget
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.4.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple, real-time shoutbox widget and shortcode with OpenAI (GPT) integration, reCAPTCHA v3 protection, and customizable texts.

== Description ==

AI Shoutbox provides a lightweight and fast chat box for your WordPress site. It uses a standard polling technique for real-time updates that works on any hosting environment. Key features:

* **Real-Time Chat:** Uses AJAX polling for message updates.
* **AI Integration:** Allow users to ask questions directly to OpenAI's GPT models.
* **reCAPTCHA v3 Protection:** Secure login form to prevent spam.
* **Session Persistence:** Remembers users for 1 hour.
* **Admin Settings Page:** Easily configure API keys and translate all public-facing texts from the WordPress admin.
* **Auto-Pruning:** Automatically clears chat history every 24 hours to keep the database clean.
* **Widget & Shortcode:** Display the shoutbox in any widget area or anywhere on your site using the `[ai_shoutbox]` shortcode.
* **Admin Tools:** Clear chat history manually from the settings page.

== Installation ==

1.  Upload the `ai-shoutbox` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to **Settings -> AI Shoutbox** and enter your Google reCAPTCHA v3 and OpenAI API keys. You can also translate the texts here.
4.  Go to `Appearance -> Widgets` and add the "AI Shoutbox" widget to a sidebar, or place the `[ai_shoutbox]` shortcode in any page or post.

== Changelog ==

= 1.4.2 =
* Fix WordPress Coding Standards warnings for database queries and output escaping.
* Fix `Invalid listener argument` JavaScript error on some browsers.
* Unify plugin version and readme.txt stable tag.
* Reduce plugin tags to the maximum of 5.

= 1.4.1 =
* Add `phpcs:ignore` comments for valid schema changes and direct DB queries to satisfy plugin checker.

= 1.4.0 =
* Revert to a stable `setInterval` polling method to prevent issues with firewalls (WAF).
* Add nonce security to all public-facing AJAX calls.
* Add comprehensive input validation and sanitization.

= 1.2.0 =
* Add setting to control AI response length (max_tokens).
* Move "Clear Chat" button to the main settings page.

= 1.1.0 =
* Added a dedicated settings page for API keys and text translations.
* Re-implemented shortcode `[ai_shoutbox]`.
* Refactored code into a more professional structure.
* Added a credit link.

= 1.0.0 =
* Initial release.
