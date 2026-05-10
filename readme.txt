=== AI Provider for Kimi ===
Contributors: abdalsalaam
Tags: ai, kimi, moonshot, ai-provider, chatbot
Requires at least: 6.9
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds Kimi (Moonshot AI) as a first-class provider for the WordPress AI Client.

== Description ==

AI Provider for Kimi registers Kimi (Moonshot AI) with the WordPress AI Client, so any plugin or theme that uses the AI Client can call Kimi models the same way it calls OpenAI, Google, or Anthropic.

**Features:**

* Text generation with Kimi chat models
* Multi-turn chat history
* Function calling and tool use
* Structured output via JSON schema
* Reasoning model support (kimi-k2 series)
* Dynamic model discovery from the Kimi API
* Settings screen for selecting a default Kimi model

Available models — including kimi-k2.6, kimi-k2.5, and the moonshot-v1 series — are fetched live from the Kimi API, so new releases appear without a plugin update.

**Requirements:**

* PHP 7.4 or higher
* A Kimi API key from [platform.kimi.ai](https://platform.kimi.ai/)
* WordPress 7.0+ (AI Client is bundled in core), or WordPress 6.9 with the [WordPress/php-ai-client](https://github.com/WordPress/php-ai-client) package installed

**Privacy & data sharing:**

When this plugin is used, prompts and content you send through the AI Client are transmitted to Moonshot AI's servers for processing. Review the [Kimi OpenPlatform Privacy Policy](https://platform.moonshot.ai/docs/agreement/userprivacy) before enabling this provider on a production site. See the **External services** section below for full details on what data is sent and when.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/ai-provider-for-kimi/`, or install through the WordPress plugin browser.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Add your Kimi API key on the WordPress AI connectors screen (provided by the AI Client in core).
4. Optionally visit **Settings → Kimi AI** to pick a default model.

== Frequently Asked Questions ==

= How do I get a Kimi API key? =

Sign up at [platform.kimi.ai](https://platform.kimi.ai/) and create an API key from your account dashboard.

= Does this plugin work without the WordPress AI Client? =

No. The plugin registers Kimi with the AI Client provider registry, so the AI Client must be available — either bundled in WordPress 7.0+ or installed as a separate package on 6.9.

= Which models are supported? =

Any chat-completion model exposed by the Kimi API, including kimi-k2.6, kimi-k2.5, moonshot-v1-8k, moonshot-v1-32k, and moonshot-v1-128k. The list is refreshed automatically.

= Does it support image generation? =

Not yet. This release covers text generation, chat, tool use, and structured output.

= Where is my API key stored? =

In the WordPress options table on your own site, under the AI Client's connector option (`connectors_ai_kimi_api_key`). It is never transmitted anywhere except to the Kimi API when fulfilling a request.

== External services ==

This plugin connects to the Kimi (Moonshot AI) API, an external service provided by Moonshot AI Ltd. It is required so the WordPress AI Client can route requests to Kimi models from your site.

The plugin contacts the following endpoints at `https://api.moonshot.ai/v1`:

* `GET /v1/models` — called from the **Settings → Kimi AI** screen and when the AI Client refreshes its model list. No user content is sent; only your configured Kimi API key is transmitted in the `Authorization` header so Moonshot can return the list of models available to your account.
* `POST /v1/chat/completions` — called whenever any plugin or theme on your site uses the WordPress AI Client to generate text with a Kimi model. The request includes your Kimi API key and the prompt/messages, system instructions, tool definitions, and any other parameters supplied by the calling code (for example, conversation history, JSON schema for structured output, or files attached to the prompt). No data is sent to Moonshot until such a request is made.

Your Kimi API key is stored in your site's WordPress options table and is only transmitted to `api.moonshot.ai` to authenticate the requests above.

This service is provided by Moonshot AI:

* Terms of Service: [https://platform.moonshot.ai/docs/agreement/modeluse](https://platform.moonshot.ai/docs/agreement/modeluse)
* Privacy Policy: [https://platform.moonshot.ai/docs/agreement/userprivacy](https://platform.moonshot.ai/docs/agreement/userprivacy)

== Changelog ==

= 1.0.0 =

* Initial release.
* Kimi text generation models with dynamic model discovery.
* Function calling and structured (JSON schema) output.
* Default-model selection under Settings → Kimi AI.

== Upgrade Notice ==

= 1.0.0 =
First public release.