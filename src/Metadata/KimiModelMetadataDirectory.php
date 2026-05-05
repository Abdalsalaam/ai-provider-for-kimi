<?php
/**
 * Kimi model metadata directory class file.
 *
 * @since 1.0.0
 *
 * @package Halawa\KimiAiProvider
 */

declare(strict_types=1);

namespace Halawa\KimiAiProvider\Metadata;

use WordPress\AiClient\Messages\Enums\ModalityEnum;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\DTO\Response;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\Http\Exception\ResponseException;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\DTO\SupportedOption;
use WordPress\AiClient\Providers\Models\Enums\CapabilityEnum;
use WordPress\AiClient\Providers\Models\Enums\OptionEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleModelMetadataDirectory;
use Halawa\KimiAiProvider\Admin\Settings;
use Halawa\KimiAiProvider\Provider\KimiProvider;

/**
 * Class for the Kimi model metadata directory.
 *
 * @since 1.0.0
 *
 * @phpstan-type ModelsResponseData array{
 *     object: string,
 *     data: list<array{
 *         id: string,
 *         object: string,
 *         created: int,
 *         owned_by: string
 *     }>
 * }
 */
class KimiModelMetadataDirectory extends AbstractOpenAiCompatibleModelMetadataDirectory {

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param HttpMethodEnum $method  The HTTP method.
	 * @param string         $path    The request path.
	 * @param array          $headers The request headers.
	 * @param mixed          $data    The request data.
	 */
	protected function createRequest( HttpMethodEnum $method, string $path, array $headers = array(), $data = null ): Request {
		return new Request(
			$method,
			KimiProvider::url( $path ),
			$headers,
			$data
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param Response $response The API response.
	 * @return array The list of model metadata.
	 * @throws ResponseException If the response data is missing.
	 */
	protected function parseResponseToModelMetadataList( Response $response ): array {
		/**
		 * Response data from the API.
		 *
		 * @var ModelsResponseData $response_data
		 */
		$response_data = $response->getData();
		if ( ! isset( $response_data['data'] ) || ! is_array( $response_data['data'] ) ) {
			throw ResponseException::fromMissingData( 'Kimi', 'data' );
		}

		$models_data = (array) $response_data['data'];

		$models = array_values(
			array_map(
				static function ( array $model_data ): ModelMetadata {
					$model_id   = $model_data['id'];
					$model_name = $model_id;

					$capabilities = array(
						CapabilityEnum::textGeneration(),
						CapabilityEnum::chatHistory(),
					);

					$options = self::getBaseOptions();

					// Add image input support for multimodal models.
					if ( self::isMultimodalModel( $model_id ) ) {
						$options[] = new SupportedOption(
							OptionEnum::inputModalities(),
							array(
								array( ModalityEnum::text() ),
								array( ModalityEnum::text(), ModalityEnum::image() ),
							)
						);
					} else {
						$options[] = new SupportedOption(
							OptionEnum::inputModalities(),
							array( array( ModalityEnum::text() ) )
						);
					}

					$options[] = new SupportedOption(
						OptionEnum::outputModalities(),
						array( array( ModalityEnum::text() ) )
					);

					return new ModelMetadata(
						$model_id,
						$model_name,
						$capabilities,
						$options
					);
				},
				$models_data
			)
		);

		usort( $models, array( $this, 'modelSortCallback' ) );

		$default_model = Settings::get_default_model();
		if ( '' !== $default_model ) {
			foreach ( $models as $index => $model ) {
				if ( $model->getId() === $default_model ) {
					$default = array_splice( $models, $index, 1 );
					array_unshift( $models, $default[0] );
					break;
				}
			}
		}

		return $models;
	}

	/**
	 * Returns the base set of supported options for all Kimi models.
	 *
	 * @since 1.0.0
	 *
	 * @return list<SupportedOption> The base supported options.
	 */
	private static function getBaseOptions(): array {
		return array(
			new SupportedOption( OptionEnum::systemInstruction() ),
			new SupportedOption( OptionEnum::candidateCount() ),
			new SupportedOption( OptionEnum::maxTokens() ),
			new SupportedOption( OptionEnum::temperature() ),
			new SupportedOption( OptionEnum::topP() ),
			new SupportedOption( OptionEnum::stopSequences() ),
			new SupportedOption( OptionEnum::presencePenalty() ),
			new SupportedOption( OptionEnum::frequencyPenalty() ),
			new SupportedOption( OptionEnum::logprobs() ),
			new SupportedOption( OptionEnum::topLogprobs() ),
			new SupportedOption( OptionEnum::outputMimeType(), array( 'text/plain', 'application/json' ) ),
			new SupportedOption( OptionEnum::outputSchema() ),
			new SupportedOption( OptionEnum::functionDeclarations() ),
			new SupportedOption( OptionEnum::customOptions() ),
		);
	}

	/**
	 * Checks whether the given model ID supports multimodal input.
	 *
	 * @since 1.0.0
	 *
	 * @param string $model_id The model identifier.
	 * @return bool True if the model supports multimodal input.
	 */
	private static function isMultimodalModel( string $model_id ): bool {
		return str_starts_with( $model_id, 'kimi-k2.6' )
			|| str_starts_with( $model_id, 'kimi-k2.5' )
			|| str_contains( $model_id, 'vision' );
	}

	/**
	 * Callback function for sorting models by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param ModelMetadata $a First model.
	 * @param ModelMetadata $b Second model.
	 * @return int Comparison result.
	 */
	protected function modelSortCallback( ModelMetadata $a, ModelMetadata $b ): int {
		$a_id = $a->getId();
		$b_id = $b->getId();

		// Prefer k2.6 models over other models.
		if ( str_starts_with( $a_id, 'kimi-k2.6' ) && ! str_starts_with( $b_id, 'kimi-k2.6' ) ) {
			return -1;
		}
		if ( str_starts_with( $b_id, 'kimi-k2.6' ) && ! str_starts_with( $a_id, 'kimi-k2.6' ) ) {
			return 1;
		}

		// Prefer k2.5 models over other models.
		if ( str_starts_with( $a_id, 'kimi-k2.5' ) && ! str_starts_with( $b_id, 'kimi-k2.5' ) ) {
			return -1;
		}
		if ( str_starts_with( $b_id, 'kimi-k2.5' ) && ! str_starts_with( $a_id, 'kimi-k2.5' ) ) {
			return 1;
		}

		// Prefer k2 models over moonshot-v1 models.
		if ( str_starts_with( $a_id, 'kimi-k2' ) && ! str_starts_with( $b_id, 'kimi-k2' ) ) {
			return -1;
		}
		if ( str_starts_with( $b_id, 'kimi-k2' ) && ! str_starts_with( $a_id, 'kimi-k2' ) ) {
			return 1;
		}

		// Prefer kimi models over moonshot models.
		if ( str_starts_with( $a_id, 'kimi-' ) && ! str_starts_with( $b_id, 'kimi-' ) ) {
			return -1;
		}
		if ( str_starts_with( $b_id, 'kimi-' ) && ! str_starts_with( $a_id, 'kimi-' ) ) {
			return 1;
		}

		// Prefer non-preview models over preview models.
		if ( str_contains( $a_id, '-preview' ) && ! str_contains( $b_id, '-preview' ) ) {
			return 1;
		}
		if ( str_contains( $b_id, '-preview' ) && ! str_contains( $a_id, '-preview' ) ) {
			return -1;
		}

		// Fallback: Sort alphabetically.
		return strcmp( $a_id, $b_id );
	}
}
