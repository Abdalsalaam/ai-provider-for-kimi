<?php
/**
 * Kimi AI Provider class file.
 *
 * @since 1.0.0
 *
 * @package Halawa\KimiAiProvider
 */

declare(strict_types=1);

namespace Halawa\KimiAiProvider\Provider;

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Http\DTO\RequestOptions;
use WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use Halawa\KimiAiProvider\Metadata\KimiModelMetadataDirectory;
use Halawa\KimiAiProvider\Models\KimiTextGenerationModel;

/**
 * Class for the Kimi provider.
 *
 * @since 1.0.0
 */
class KimiProvider extends AbstractApiProvider {

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	protected static function baseUrl(): string {
		return 'https://api.moonshot.ai/v1';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param ModelMetadata    $model_metadata  The model metadata.
	 * @param ProviderMetadata $provider_metadata The provider metadata.
	 */
	protected static function createModel(
		ModelMetadata $model_metadata,
		ProviderMetadata $provider_metadata
	): ModelInterface {
		$model = new KimiTextGenerationModel( $model_metadata, $provider_metadata );

		$request_options = new RequestOptions();
		$request_options->setTimeout( 120.0 );
		$model->setRequestOptions( $request_options );

		return $model;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	protected static function createProviderMetadata(): ProviderMetadata {
		$provider_metadata_args = array(
			'kimi',
			'Kimi',
			ProviderTypeEnum::cloud(),
			'https://platform.kimi.ai',
			RequestAuthenticationMethod::apiKey(),
		);

		// Provider description support was added in 1.2.0.
		if ( version_compare( AiClient::VERSION, '1.2.0', '>=' ) ) {
			// For WordPress, we should translate the description.
			if ( function_exists( '__' ) ) {
				$provider_metadata_args[] = __( 'Text generation with Moonshot AI models.', 'ai-provider-for-kimi' );
			} else {
				$provider_metadata_args[] = 'Text generation with Moonshot AI models.';
			}
		}

		// Provider logoPath support was added in 1.3.0.
		if ( version_compare( AiClient::VERSION, '1.3.0', '>=' ) ) {
			$provider_metadata_args[] = dirname( __DIR__, 2 ) . '/assets/images/kimi.svg';
		}

		return new ProviderMetadata( ...$provider_metadata_args );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	protected static function createProviderAvailability(): ProviderAvailabilityInterface {
		// Check valid API access by attempting to list models.
		return new ListModelsApiBasedProviderAvailability(
			static::modelMetadataDirectory()
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 */
	protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface {
		return new KimiModelMetadataDirectory();
	}
}
