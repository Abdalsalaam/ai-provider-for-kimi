<?php
/**
 * Kimi provider admin settings class file.
 *
 * @since 1.0.0
 *
 * @package Halawa\KimiAiProvider
 */

declare(strict_types=1);

namespace Halawa\KimiAiProvider\Admin;

use Halawa\KimiAiProvider\Provider\KimiProvider;

/**
 * Class for the Kimi provider admin settings page.
 *
 * @since 1.0.0
 */
class Settings {

	/**
	 * The option name for storing the default model.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public const OPTION_DEFAULT_MODEL = 'ai_provider_kimi_default_model';

	/**
	 * The transient key for cached model list.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private const MODELS_TRANSIENT = 'ai_provider_kimi_models';

	/**
	 * Registers the admin settings page and hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register(): void {
		add_options_page(
			__( 'Kimi AI', 'ai-provider-for-kimi' ),
			__( 'Kimi AI', 'ai-provider-for-kimi' ),
			'manage_options',
			'ai-provider-for-kimi',
			array( $this, 'render_settings_page' )
		);

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'update_option_' . self::OPTION_DEFAULT_MODEL, array( $this, 'invalidate_models_cache' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Registers the settings with the WordPress Settings API.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'ai_provider_kimi_settings_group',
			self::OPTION_DEFAULT_MODEL,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
	}

	/**
	 * Renders the settings page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'ai-provider-for-kimi' ) );
		}

		$models        = self::fetch_models( self::get_api_key() );
		$default_model = get_option( self::OPTION_DEFAULT_MODEL, '' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'ai_provider_kimi_settings_group' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( self::OPTION_DEFAULT_MODEL ); ?>">
								<?php esc_html_e( 'Default Model', 'ai-provider-for-kimi' ); ?>
							</label>
						</th>
						<td>
							<?php if ( empty( $models ) ) : ?>
								<p class="description">
									<?php
									$api_key = self::get_api_key();
									if ( '' === $api_key ) {
										$message = __(
											'Please configure your API key in the WordPress AI connectors screen to load available models.',
											'ai-provider-for-kimi'
										);
									} else {
										$message = __(
											'Unable to fetch models. Please check your API key and try again.',
											'ai-provider-for-kimi'
										);
									}
									echo esc_html( $message );
									?>
								</p>
							<?php else : ?>
								<select
									name="<?php echo esc_attr( self::OPTION_DEFAULT_MODEL ); ?>"
									id="<?php echo esc_attr( self::OPTION_DEFAULT_MODEL ); ?>"
									class="regular-text"
								>
									<?php foreach ( $models as $model_id => $model_data ) : ?>
										<option
											value="<?php echo esc_attr( $model_id ); ?>"
											<?php selected( $default_model, $model_id ); ?>
										>
											<?php echo esc_html( $model_data['id'] ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">
									<?php
									$description = __(
										'Select the default model to use for text generation.',
										'ai-provider-for-kimi'
									);
									echo esc_html( $description );
									?>
								</p>
								<div id="kimi-model-details" class="kimi-model-details-card"></div>
								<?php
								wp_localize_script(
									'ai-provider-for-kimi-admin',
									'kimiModelData',
									$models
								);
								wp_localize_script(
									'ai-provider-for-kimi-admin',
									'kimiModelStrings',
									array(
										'contextLength' => __( 'Context length:', 'ai-provider-for-kimi' ),
										'capabilities'  => __( 'Capabilities:', 'ai-provider-for-kimi' ),
										'pricing'       => __( 'Pricing (per 1M tokens):', 'ai-provider-for-kimi' ),
										'in'            => __( 'In', 'ai-provider-for-kimi' ),
										'out'           => __( 'Out', 'ai-provider-for-kimi' ),
										'cache'         => __( 'Cache', 'ai-provider-for-kimi' ),
									)
								);
								?>
							<?php endif; ?>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save settings', 'ai-provider-for-kimi' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Gets the default model from the WordPress option.
	 *
	 * @since 1.0.0
	 *
	 * @return string The default model ID, or an empty string if not set.
	 */
	public static function get_default_model(): string {
		$model = get_option( self::OPTION_DEFAULT_MODEL, '' );
		return is_string( $model ) ? $model : '';
	}

	/**
	 * Gets the API key from the WordPress AI connector option.
	 *
	 * @since 1.0.0
	 *
	 * @return string The API key, or an empty string if not set.
	 */
	public static function get_api_key(): string {
		$connector_key = get_option( 'connectors_ai_kimi_api_key', '' );
		return is_string( $connector_key ) ? $connector_key : '';
	}

	/**
	 * Invalidates the SDK model metadata cache when the default model changes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function invalidate_models_cache(): void {
		KimiProvider::modelMetadataDirectory()->invalidateCaches();
		delete_transient( self::MODELS_TRANSIENT );
	}

	/**
	 * Enqueues admin assets for the settings page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix The current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( 'settings_page_ai-provider-for-kimi' !== $hook_suffix ) {
			return;
		}

		$plugin_url = plugin_dir_url( dirname( __DIR__, 2 ) . '/ai-provider-for-kimi.php' );

		wp_enqueue_style(
			'ai-provider-for-kimi-admin',
			$plugin_url . 'assets/css/admin-settings.css',
			array(),
			'1.0.0'
		);

		wp_enqueue_script(
			'ai-provider-for-kimi-admin',
			$plugin_url . 'assets/js/admin-settings.js',
			array(),
			'1.0.0',
			true
		);
	}

	/**
	 * Fetches the list of available models from the Kimi API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_key The API key to use for the request.
	 * @return array<string, array<string, mixed>> Associative array of model IDs to model metadata.
	 */
	private static function fetch_models( string $api_key ): array {
		$cached = get_transient( self::MODELS_TRANSIENT );
		if ( is_array( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}

		if ( '' !== $api_key ) {
			$response = wp_remote_get(
				'https://api.moonshot.ai/v1/models',
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $api_key,
					),
					'timeout' => 10,
				)
			);

			if ( ! is_wp_error( $response ) ) {
				$body = wp_remote_retrieve_body( $response );
				$data = json_decode( $body, true );

				if ( is_array( $data ) && isset( $data['data'] ) && is_array( $data['data'] ) ) {
					$models = array();
					foreach ( $data['data'] as $model ) {
						if ( ! is_array( $model ) || ! isset( $model['id'] ) || ! is_string( $model['id'] ) ) {
							continue;
						}
						$model_id            = $model['id'];
						$models[ $model_id ] = array(
							'id'                 => $model_id,
							'context_length'     => isset( $model['context_length'] ) && is_numeric( $model['context_length'] ) ? (int) $model['context_length'] : 0,
							'supports_image_in'  => ! empty( $model['supports_image_in'] ),
							'supports_video_in'  => ! empty( $model['supports_video_in'] ),
							'supports_reasoning' => ! empty( $model['supports_reasoning'] ),
							'pricing'            => self::get_model_pricing( $model_id ),
							'description'        => self::get_model_description( $model_id ),
						);
					}

					if ( ! empty( $models ) ) {
						set_transient( self::MODELS_TRANSIENT, $models, HOUR_IN_SECONDS );
						return $models;
					}
				}
			}
		}

		return self::get_fallback_models();
	}

	/**
	 * Returns the fallback model list when the API is unreachable.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string, array<string, mixed>> Fallback model metadata.
	 */
	private static function get_fallback_models(): array {
		return array(
			'kimi-k2.6'                       => array(
				'id'                 => 'kimi-k2.6',
				'context_length'     => 256000,
				'supports_image_in'  => true,
				'supports_video_in'  => true,
				'supports_reasoning' => true,
				'pricing'            => null,
				'description'        => __( 'Kimi K2.6: Advanced multimodal reasoning model with extended context.', 'ai-provider-for-kimi' ),
			),
			'kimi-k2.5'                       => array(
				'id'                 => 'kimi-k2.5',
				'context_length'     => 256000,
				'supports_image_in'  => true,
				'supports_video_in'  => true,
				'supports_reasoning' => true,
				'pricing'            => array(
					'input'     => 0.60,
					'output'    => 3.00,
					'cache_hit' => 0.10,
				),
				'description'        => __( 'Kimi K2.5: 256K context, supports images, video, and reasoning.', 'ai-provider-for-kimi' ),
			),
			'moonshot-v1-128k'                => array(
				'id'                 => 'moonshot-v1-128k',
				'context_length'     => 128000,
				'supports_image_in'  => false,
				'supports_video_in'  => false,
				'supports_reasoning' => false,
				'pricing'            => array(
					'input'     => 2.00,
					'output'    => 5.00,
					'cache_hit' => null,
				),
				'description'        => __( 'Moonshot V1 128K: General-purpose model with 128K context.', 'ai-provider-for-kimi' ),
			),
			'moonshot-v1-32k'                 => array(
				'id'                 => 'moonshot-v1-32k',
				'context_length'     => 32000,
				'supports_image_in'  => false,
				'supports_video_in'  => false,
				'supports_reasoning' => false,
				'pricing'            => array(
					'input'     => 1.00,
					'output'    => 3.00,
					'cache_hit' => null,
				),
				'description'        => __( 'Moonshot V1 32K: General-purpose model with 32K context.', 'ai-provider-for-kimi' ),
			),
			'moonshot-v1-8k'                  => array(
				'id'                 => 'moonshot-v1-8k',
				'context_length'     => 8000,
				'supports_image_in'  => false,
				'supports_video_in'  => false,
				'supports_reasoning' => false,
				'pricing'            => array(
					'input'     => 0.20,
					'output'    => 2.00,
					'cache_hit' => null,
				),
				'description'        => __( 'Moonshot V1 8K: General-purpose model with 8K context.', 'ai-provider-for-kimi' ),
			),
			'moonshot-v1-128k-vision-preview' => array(
				'id'                 => 'moonshot-v1-128k-vision-preview',
				'context_length'     => 128000,
				'supports_image_in'  => true,
				'supports_video_in'  => false,
				'supports_reasoning' => false,
				'pricing'            => array(
					'input'     => 2.00,
					'output'    => 5.00,
					'cache_hit' => null,
				),
				'description'        => __( 'Moonshot V1 128K Vision: Vision-enabled variant with 128K context.', 'ai-provider-for-kimi' ),
			),
			'moonshot-v1-32k-vision-preview'  => array(
				'id'                 => 'moonshot-v1-32k-vision-preview',
				'context_length'     => 32000,
				'supports_image_in'  => true,
				'supports_video_in'  => false,
				'supports_reasoning' => false,
				'pricing'            => array(
					'input'     => 1.00,
					'output'    => 3.00,
					'cache_hit' => null,
				),
				'description'        => __( 'Moonshot V1 32K Vision: Vision-enabled variant with 32K context.', 'ai-provider-for-kimi' ),
			),
			'moonshot-v1-8k-vision-preview'   => array(
				'id'                 => 'moonshot-v1-8k-vision-preview',
				'context_length'     => 8000,
				'supports_image_in'  => true,
				'supports_video_in'  => false,
				'supports_reasoning' => false,
				'pricing'            => array(
					'input'     => 0.20,
					'output'    => 2.00,
					'cache_hit' => null,
				),
				'description'        => __( 'Moonshot V1 8K Vision: Vision-enabled variant with 8K context.', 'ai-provider-for-kimi' ),
			),
		);
	}

	/**
	 * Gets pricing metadata for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param string $model_id The model identifier.
	 * @return array<string, float|null>|null Pricing data or null if unknown.
	 */
	private static function get_model_pricing( string $model_id ): ?array {
		$base_id = str_replace( '-vision-preview', '', $model_id );

		$map = array(
			'kimi-k2.5'        => array(
				'input'     => 0.60,
				'output'    => 3.00,
				'cache_hit' => 0.10,
			),
			'moonshot-v1-8k'   => array(
				'input'     => 0.20,
				'output'    => 2.00,
				'cache_hit' => null,
			),
			'moonshot-v1-32k'  => array(
				'input'     => 1.00,
				'output'    => 3.00,
				'cache_hit' => null,
			),
			'moonshot-v1-128k' => array(
				'input'     => 2.00,
				'output'    => 5.00,
				'cache_hit' => null,
			),
		);

		return $map[ $base_id ] ?? null;
	}

	/**
	 * Generates a human-readable description for a model.
	 *
	 * @since 1.0.0
	 *
	 * @param string $model_id The model identifier.
	 * @return string The model description.
	 */
	private static function get_model_description( string $model_id ): string {
		if ( str_starts_with( $model_id, 'kimi-k2.6' ) ) {
			return __( 'Kimi K2.6: Advanced multimodal reasoning model with extended context.', 'ai-provider-for-kimi' );
		}

		if ( str_starts_with( $model_id, 'kimi-k2.5' ) ) {
			return __( 'Kimi K2.5: 256K context, supports images, video, and reasoning.', 'ai-provider-for-kimi' );
		}

		if ( str_starts_with( $model_id, 'moonshot-v1-128k-vision' ) ) {
			return __( 'Moonshot V1 128K Vision: Vision-enabled variant with 128K context.', 'ai-provider-for-kimi' );
		}

		if ( str_starts_with( $model_id, 'moonshot-v1-128k' ) ) {
			return __( 'Moonshot V1 128K: General-purpose model with 128K context.', 'ai-provider-for-kimi' );
		}

		if ( str_starts_with( $model_id, 'moonshot-v1-32k-vision' ) ) {
			return __( 'Moonshot V1 32K Vision: Vision-enabled variant with 32K context.', 'ai-provider-for-kimi' );
		}

		if ( str_starts_with( $model_id, 'moonshot-v1-32k' ) ) {
			return __( 'Moonshot V1 32K: General-purpose model with 32K context.', 'ai-provider-for-kimi' );
		}

		if ( str_starts_with( $model_id, 'moonshot-v1-8k-vision' ) ) {
			return __( 'Moonshot V1 8K Vision: Vision-enabled variant with 8K context.', 'ai-provider-for-kimi' );
		}

		if ( str_starts_with( $model_id, 'moonshot-v1-8k' ) ) {
			return __( 'Moonshot V1 8K: General-purpose model with 8K context.', 'ai-provider-for-kimi' );
		}

		return __( 'Moonshot AI model.', 'ai-provider-for-kimi' );
	}
}
