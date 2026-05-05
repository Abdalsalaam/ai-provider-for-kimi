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
									<?php foreach ( $models as $model_id => $model_name ) : ?>
										<option
											value="<?php echo esc_attr( $model_id ); ?>"
											<?php selected( $default_model, $model_id ); ?>
										>
											<?php echo esc_html( $model_name ); ?>
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
							<?php endif; ?>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Settings', 'ai-provider-for-kimi' ) ); ?>
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
		\Halawa\KimiAiProvider\Provider\KimiProvider::modelMetadataDirectory()->invalidateCaches();
	}

	/**
	 * Fetches the list of available models from the Kimi API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $api_key The API key to use for the request.
	 * @return array<string, string> Associative array of model IDs to model names.
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
						$models[ $model_id ] = $model_id;
					}

					if ( ! empty( $models ) ) {
						set_transient( self::MODELS_TRANSIENT, $models, HOUR_IN_SECONDS );
						return $models;
					}
				}
			}
		}

		// Fallback to the known Kimi model list when the API is unreachable or no key is set.
		return array(
			'kimi-k2.6'                       => 'kimi-k2.6',
			'kimi-k2.5'                       => 'kimi-k2.5',
			'moonshot-v1-128k'                => 'moonshot-v1-128k',
			'moonshot-v1-32k'                 => 'moonshot-v1-32k',
			'moonshot-v1-8k'                  => 'moonshot-v1-8k',
			'moonshot-v1-128k-vision-preview' => 'moonshot-v1-128k-vision-preview',
			'moonshot-v1-32k-vision-preview'  => 'moonshot-v1-32k-vision-preview',
			'moonshot-v1-8k-vision-preview'   => 'moonshot-v1-8k-vision-preview',
		);
	}
}
