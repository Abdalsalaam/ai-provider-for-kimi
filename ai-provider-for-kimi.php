<?php
/**
 * Plugin Name: AI Provider for Kimi
 * Plugin URI: https://github.com/Abdalsalaam/ai-provider-for-kimi
 * Description: AI Provider for Kimi for the WordPress AI Client.
 * Requires at least: 6.9
 * Requires PHP: 7.4
 * Version: 1.0.0
 * Author: Abdalsalaam Halawa
 * Author URI: https://halawa.io
 * License: GPL-2.0-or-later
 * License URI: https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain: ai-provider-for-kimi
 *
 * @package Halawa\KimiAiProvider
 */

declare(strict_types=1);

namespace Halawa\KimiAiProvider;

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use Halawa\KimiAiProvider\Admin\Settings;
use Halawa\KimiAiProvider\Provider\KimiProvider;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Registers the AI Provider for Kimi with the AI Client.
 *
 * @since 1.0.0
 *
 * @return void
 */
function register_provider(): void {
	if ( ! class_exists( AiClient::class ) ) {
		return;
	}

	$registry = AiClient::defaultRegistry();

	if ( $registry->hasProvider( KimiProvider::class ) ) {
		return;
	}

	$registry->registerProvider( KimiProvider::class );

	$api_key = Settings::get_api_key();
	if ( '' !== $api_key ) {
		$registry->setProviderRequestAuthentication(
			KimiProvider::class,
			new ApiKeyRequestAuthentication( $api_key )
		);
	}
}

add_action( 'init', __NAMESPACE__ . '\\register_provider', 5 );

/**
 * Initializes the admin settings.
 *
 * @since 1.0.0
 *
 * @return void
 */
function register_admin_settings(): void {
	if ( ! is_admin() ) {
		return;
	}

	$settings = new Settings();
	$settings->register();
}

add_action( 'admin_menu', __NAMESPACE__ . '\\register_admin_settings' );
