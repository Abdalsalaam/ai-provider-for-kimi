# AI Provider for Kimi

A WordPress plugin that registers [Kimi (Moonshot AI)](https://platform.kimi.ai/) as a first-class provider for the [WordPress AI Client](https://github.com/WordPress/php-ai-client). Once active, any plugin or theme that uses the AI Client can call Kimi models the same way it calls OpenAI, Google, or Anthropic.

## Features

- Text generation with Kimi chat models
- Multi-turn chat history
- Function calling and tool use
- Structured output via JSON schema
- Reasoning support for the `kimi-k2` series
- Dynamic model discovery from `GET /v1/models`
- Default-model selection under **Settings → Kimi AI**

## Requirements

- PHP **7.4+**
- WordPress **7.0+** (AI Client bundled in core), or **6.9** with [`wordpress/php-ai-client`](https://github.com/WordPress/php-ai-client) installed
- A Kimi API key from [platform.kimi.ai](https://platform.kimi.ai/)

## Installation

### From the WordPress.org plugin directory

Search for *AI Provider for Kimi* in the plugin browser, install, and activate.

### From source

```bash
git clone https://github.com/Abdalsalaam/ai-provider-for-kimi.git wp-content/plugins/ai-provider-for-kimi
cd wp-content/plugins/ai-provider-for-kimi
composer install --no-dev
```

Then activate the plugin in WordPress.

## Configuration

1. Add your Kimi API key on the **WordPress AI connectors** screen (provided by the AI Client in core). The key is stored in the `connectors_ai_kimi_api_key` option.
2. Optionally visit **Settings → Kimi AI** to choose a default model.

## Architecture

The plugin extends the OpenAI-compatible base classes shipped with the PHP AI Client SDK, since Kimi exposes an OpenAI-compatible API at `https://api.moonshot.ai/v1`.

```
src/
├── Admin/
│   └── Settings.php                     # Settings → Kimi AI page (default model)
├── Metadata/
│   └── KimiModelMetadataDirectory.php   # extends AbstractOpenAiCompatibleModelMetadataDirectory
├── Models/
│   └── KimiTextGenerationModel.php      # extends AbstractOpenAiCompatibleTextGenerationModel
└── Provider/
    └── KimiProvider.php                 # extends AbstractApiProvider
```

The provider is registered on `init` (priority 5) via `AiClient::defaultRegistry()->registerProvider()`.

## Development

```bash
composer install
composer lint        # PHPCS + PHPStan
composer phpcs       # coding standards
composer phpcbf      # auto-fix coding standards
composer phpstan     # static analysis (level: max)
```

Coding standards:

- PHP 7.4 minimum, `declare(strict_types=1);` in every file
- PSR-12 (`phpcs.xml.dist`)
- PHPStan `level: max` (`phpstan.neon.dist`)
- DocBlocks with `@since`, `@param`, `@return` on every class, method, and function

## Privacy

When this plugin is active, prompts and content sent through the AI Client are transmitted to Moonshot AI's servers for processing. Review the [Kimi terms and privacy policy](https://platform.kimi.ai/) before enabling it on production.

## Contributing

Issues and pull requests are welcome at [github.com/Abdalsalaam/ai-provider-for-kimi](https://github.com/Abdalsalaam/ai-provider-for-kimi/issues). Please run `composer lint` before opening a PR.

## License

[GPL-2.0-or-later](LICENSE)
