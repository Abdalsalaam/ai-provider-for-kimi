=== AI Provider for Kimi ===
Contributors: abdalsalaam
Tags: ai, kimi, moonshot, connector
Requires at least: 7.0
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Kimi AI provider for the PHP AI Client SDK.

== Description ==

This plugin provides Kimi AI (Moonshot AI) integration for the PHP AI Client SDK. It enables WordPress sites to use Kimi models for text generation, chat, and other AI capabilities.

**Features:**

* Text generation with Kimi models
* Chat history support
* Function calling support
* Automatic provider registration
* Secure API key storage with encryption

Available models are dynamically discovered from the Kimi API, including kimi-k2.6, kimi-k2.5, and moonshot-v1 series.

**Requirements:**

* PHP 7.4 or higher
* For WordPress 6.9, the [wordpress/php-ai-client](https://github.com/WordPress/php-ai-client) package must be installed
* For WordPress 7.0 and above, no additional changes are required
* Kimi API key

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/ai-provider-for-kimi/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your Kimi API key via Settings -> Kimi AI

== Frequently Asked Questions ==

= How do I get a Kimi API key? =

Visit the [Kimi Platform](https://platform.kimi.ai/) to create an API key.

= Does this plugin work without the PHP AI Client? =

No, this plugin requires the PHP AI Client plugin to be installed and activated. It provides the Kimi-specific implementation that the PHP AI Client uses.

== Changelog ==

= 1.0.0 =

* Initial release
* Support for Kimi text generation models
* Function calling support
* Secure API key storage
