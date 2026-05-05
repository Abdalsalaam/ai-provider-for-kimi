<?php
/**
 * Kimi text generation model class file.
 *
 * @since 1.0.0
 *
 * @package Halawa\KimiAiProvider
 */

declare(strict_types=1);

namespace Halawa\KimiAiProvider\Models;

use WordPress\AiClient\Common\Exception\InvalidArgumentException;
use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Providers\Http\DTO\Request;
use WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum;
use WordPress\AiClient\Providers\OpenAiCompatibleImplementation\AbstractOpenAiCompatibleTextGenerationModel;
use Halawa\KimiAiProvider\Provider\KimiProvider;

/**
 * Class for a Kimi text generation model.
 *
 * @since 1.0.0
 */
class KimiTextGenerationModel extends AbstractOpenAiCompatibleTextGenerationModel {

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
			$data,
			$this->getRequestOptions()
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 1.0.0
	 *
	 * @param array $prompt The prompt data.
	 */
	protected function prepareGenerateTextParams( array $prompt ): array {
		$params = parent::prepareGenerateTextParams( $prompt );

		$model_id = $this->metadata()->getId();

		// Kimi reasoning models enforce fixed values for several parameters.
		if ( self::isReasoningModel( $model_id ) ) {
			unset(
				$params['temperature'],
				$params['top_p'],
				$params['presence_penalty'],
				$params['frequency_penalty']
			);
			$params['n'] = 1;

			// Disable thinking mode to avoid excessive latency.
			$params['thinking'] = array(
				'type' => 'disabled',
			);

			return $params;
		}

		// For non-reasoning models, clamp temperature to the valid [0, 1] range.
		if ( isset( $params['temperature'] ) && is_numeric( $params['temperature'] ) ) {
			$params['temperature'] = max( 0.0, min( 1.0, (float) $params['temperature'] ) );
		}

		return $params;
	}

	/**
	 * {@inheritDoc}
	 *
	 * Overrides the parent to wrap the schema with a required "name" key,
	 * which the Kimi API enforces for structured output requests.
	 *
	 * @since 1.0.0
	 *
	 * @param ?array $output_schema The output schema.
	 */
	protected function prepareResponseFormatParam( ?array $output_schema ): array {
		if ( is_array( $output_schema ) ) {
			return array(
				'type'        => 'json_schema',
				'json_schema' => array(
					'name'   => 'kimi_schema',
					'schema' => $output_schema,
				),
			);
		}

		return array(
			'type' => 'json_object',
		);
	}

	/**
	 * Checks whether the given model ID is a Kimi reasoning model with fixed parameters.
	 *
	 * @since 1.0.0
	 *
	 * @param string $model_id The model identifier.
	 * @return bool True if the model is a reasoning model.
	 */
	private static function isReasoningModel( string $model_id ): bool {
		return str_starts_with( $model_id, 'kimi-k2' );
	}

	/**
	 * Returns the Kimi API specific content data for a message part.
	 *
	 * Overrides the parent to add support for video input, which Kimi supports
	 * but the standard OpenAI-compatible base class does not handle.
	 *
	 * @since 1.0.0
	 *
	 * @param MessagePart $part The message part to get the data for.
	 * @return ?array<string, mixed> The data for the message content part, or null if not applicable.
	 * @throws RuntimeException If the file or URL is missing.
	 */
	protected function getMessagePartContentData( MessagePart $part ): ?array {
		$type = $part->getType();
		if ( $type->isFile() ) {
			$file = $part->getFile();
			if ( ! $file ) {
				// This should be impossible due to class internals, but still needs to be checked.
				throw new RuntimeException( 'The file typed message part must contain a file.' );
			}

			if ( $file->isVideo() ) {
				if ( $file->isRemote() ) {
					$file_url = $file->getUrl();
					if ( ! $file_url ) {
						// This should be impossible due to class internals, but still needs to be checked.
						throw new RuntimeException( 'The remote file must contain a URL.' );
					}
					return array(
						'type'      => 'video_url',
						'video_url' => array(
							'url' => $file_url,
						),
					);
				}
				// Inline video file.
				return array(
					'type'      => 'video_url',
					'video_url' => array(
						'url' => $file->getDataUri(),
					),
				);
			}
		}

		// Delegate all other types to the parent implementation.
		return parent::getMessagePartContentData( $part );
	}
}
