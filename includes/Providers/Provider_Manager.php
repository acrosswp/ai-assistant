<?php
/**
 * Class Ai_Assistant\Providers\Provider_Manager
 *
 * @since 0.0.1
 * @package ai-assistant
 */

namespace Ai_Assistant\Providers;

use Ai_Assistant\Includes\AI_Client\AI_Client_Factory;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Class for managing the different AI providers.
 *
 * @since 0.0.1
 */
class Provider_Manager {
	protected const OPTION_PROVIDER_CREDENTIALS = 'ai_assistant_provider_credentials';
	protected const OPTION_CURRENT_PROVIDER     = 'ai_assistant_current_provider';

	/**
	 * List of AI SDK provider IDs to consider.
	 *
	 * @since 0.0.1
	 * @var array<string>
	 */
	protected array $provider_ids;

	/**
	 * Cached list of available provider IDs.
	 *
	 * @since 0.0.1
	 * @var array<string>|null
	 */
	protected ?array $available_provider_ids = null;

	/**
	 * AI Client Wrapper instance.
	 *
	 * @since 0.0.1
	 * @var \Ai_Assistant\Includes\AI_Client\AI_Client_Wrapper
	 */
	protected $ai_client;

	/**
	 * Constructor.
	 *
	 * @since 0.0.1
	 * @param array<string> $provider_ids List of AI SDK provider IDs to consider.
	 */
	public function __construct( array $provider_ids = array( 'anthropic', 'google', 'openai' ) ) {
		$this->provider_ids = $provider_ids;
		$this->ai_client    = AI_Client_Factory::get_instance( $provider_ids );
	}

	/**
	 * Gets the available provider IDs.
	 *
	 * @since 0.0.1
	 *
	 * @return array<string> The available provider IDs.
	 */
	public function get_available_provider_ids(): array {
		if ( is_array( $this->available_provider_ids ) ) {
			return $this->available_provider_ids;
		}

		$this->available_provider_ids = $this->ai_client->get_available_provider_ids();
		return $this->available_provider_ids;
	}

	/**
	 * Gets the current provider ID.
	 *
	 * @since 0.0.1
	 *
	 * @return string The current provider ID.
	 */
	public function get_current_provider_id(): string {
		$provider_id = (string) get_option( self::OPTION_CURRENT_PROVIDER );
		error_log( 'AI Assistant: Getting current provider ID: ' . ( empty( $provider_id ) ? 'none' : $provider_id ) );

		if ( '' !== $provider_id && in_array( $provider_id, $this->provider_ids, true ) ) {
			return $provider_id;
		}

		$available_provider_ids = $this->get_available_provider_ids();
		if ( ! empty( $available_provider_ids ) ) {
			return $available_provider_ids[0];
		}

		// Default to the first provider if none are available
		if ( ! empty( $this->provider_ids ) ) {
			return $this->provider_ids[0];
		}

		return '';
	}

	/**
	 * Gets the AI client wrapper instance.
	 *
	 * @since 0.0.1
	 *
	 * @return \Ai_Assistant\Includes\AI_Client\AI_Client_Wrapper The AI client wrapper instance.
	 */
	public function get_ai_client() {
		return $this->ai_client;
	}

	/**
	 * Gets the preferred model ID for a provider.
	 *
	 * @since 0.0.1
	 *
	 * @param string $provider_id  The provider ID.
	 * @param array  $requirements Optional. The model requirements.
	 * @return string The preferred model ID.
	 */
	public function get_preferred_model_id( string $provider_id, array $requirements = array() ): string {
		// First check if the provider is available
		if ( ! $this->ai_client->is_provider_available( $provider_id ) ) {
			error_log( "AI Assistant: Provider {$provider_id} is not available or configured properly" );
			return '';
		}

		// If we have specific requirements, use the client wrapper to find a suitable model
		if ( ! empty( $requirements ) ) {
			$model_id = $this->ai_client->get_preferred_model_id( $provider_id, $requirements );
			if ( ! empty( $model_id ) ) {
				return $model_id;
			}
		}

		// Fallback to default models
		switch ( $provider_id ) {
			case 'anthropic':
				$model_id = 'claude-sonnet-4-20250514';
				break;
			case 'google':
				$model_id = 'gemini-2.5-flash';
				break;
			case 'openai':
				$model_id = 'gpt-4-turbo';
				break;
			default:
				$model_id = '';
		}

		/**
		 * Filters the preferred model ID for the given provider ID.
		 *
		 * The dynamic portion of the hook name refers to the provider ID.
		 *
		 * @since 0.0.1
		 *
		 * @param string $model_id The preferred model ID for the provider.
		 */
		return (string) apply_filters( "ai_assistant_preferred_{$provider_id}_model", $model_id );
	}

	/**
	 * Initializes the provider credentials configuration.
	 *
	 * This registers the option to store provider credentials and sets up authentication.
	 */
	public function initialize_provider_credentials(): void {
		register_setting(
			'ai_assistant_settings_group',
			self::OPTION_PROVIDER_CREDENTIALS,
			array(
				'type'              => 'object',
				'default'           => array(),
				'show_in_rest'      => false,
				'sanitize_callback' => function ( $credentials ) {
					if ( ! is_array( $credentials ) ) {
						return array();
					}
					foreach ( $credentials as $provider_id => $api_key ) {
						$credentials[ $provider_id ] = sanitize_text_field( $api_key );
					}
					return $credentials;
				},
			)
		);

		// Defer AiClient registry setup until it's actually needed
		// This prevents HTTP client discovery errors during plugin initialization
		add_action( 'wp_loaded', array( $this, 'setup_ai_client_registry' ), 20 );
	}

	/**
	 * Sets up the AiClient registry with provider credentials.
	 * Called after WordPress is fully loaded to avoid HTTP client discovery issues.
	 */
	public function setup_ai_client_registry(): void {
		// Get credentials from individual options
		$current_credentials = array(
			'anthropic' => get_option( 'ai_assistant_anthropic_key', '' ),
			'google'    => get_option( 'ai_assistant_google_key', '' ),
			'openai'    => get_option( 'ai_assistant_openai_key', '' ),
		);

		// Check if we have any valid credentials
		$has_valid_credentials = false;
		foreach ( $current_credentials as $provider_id => $api_key ) {
			if ( ! empty( $api_key ) ) {
				$has_valid_credentials = true;
				break;
			}
		}

		if ( ! $has_valid_credentials ) {
			error_log( 'AI Assistant: No valid API keys found in provider credentials' );
			return;
		}

		try {
			$registry = \WordPress\AiClient\AiClient::defaultRegistry();

			// Add hooks for request/response debugging before configuring providers
			$this->add_provider_debug_hooks();

			foreach ( $current_credentials as $provider_id => $api_key ) {
				if ( empty( $api_key ) ) {
					continue;
				}

				try {
					$registry->setProviderRequestAuthentication(
						$provider_id,
						new ApiKeyRequestAuthentication( $api_key )
					);

					// Verify provider is configured correctly
					if ( $registry->isProviderConfigured( $provider_id ) ) {
						error_log( "AI Assistant: Successfully configured provider {$provider_id}" );
					} else {
						error_log( "AI Assistant: Provider {$provider_id} not properly configured despite setting API key" );
					}
				} catch ( \Exception $provider_e ) {
					error_log( "AI Assistant: Failed to configure provider {$provider_id}: " . $provider_e->getMessage() );
				}
			}

			// Check if we have any configured providers
			$available_providers = array_filter(
				$this->provider_ids,
				function ( $provider_id ) use ( $registry ) {
					return $registry->hasProvider( $provider_id ) && $registry->isProviderConfigured( $provider_id );
				}
			);

			if ( empty( $available_providers ) ) {
				error_log( 'AI Assistant: No providers were successfully configured' );
			} else {
				error_log( 'AI Assistant: Successfully configured providers: ' . implode( ', ', $available_providers ) );

				// Update the cached available providers
				$this->available_provider_ids = array_values( $available_providers );
			}
		} catch ( \Exception $e ) {
			// Log error but don't break the plugin
			error_log( 'AI Assistant: Failed to initialize AiClient registry: ' . $e->getMessage() );
		}
	}

	/**
	 * Add hooks for provider request/response debugging
	 */
	protected function add_provider_debug_hooks(): void {
		// Add filters to intercept requests/responses for the PHP AI Client
		add_filter( 'ai_client_pre_provider_request', array( $this, 'debug_provider_request' ), 10, 3 );
		add_filter( 'ai_client_post_provider_response', array( $this, 'debug_provider_response' ), 10, 4 );
	}

	/**
	 * Debug hook for provider requests
	 *
	 * @param array  $request_data The request data being sent to the provider.
	 * @param string $provider_id The provider ID.
	 * @param array  $args Additional request arguments.
	 * @return array The unmodified request data
	 */
	public function debug_provider_request( $request_data, $provider_id, $args ) {
		// Pass to the generic pre-provider-request hook that our debug logger listens to
		return apply_filters( 'ai_assistant_pre_provider_request', $request_data, $provider_id, $args );
	}

	/**
	 * Debug hook for provider responses
	 *
	 * @param mixed  $response The response from the provider.
	 * @param array  $request_data The original request data.
	 * @param string $provider_id The provider ID.
	 * @param array  $args Additional request arguments.
	 * @return mixed The unmodified response
	 */
	public function debug_provider_response( $response, $request_data, $provider_id, $args ) {
		// Pass to the generic post-provider-response hook that our debug logger listens to
		return apply_filters( 'ai_assistant_post_provider_response', $response, $request_data, $provider_id, $args );
	}

	/**
	 * Initializes the current provider configuration.
	 *
	 * This registers the option to store the current provider.
	 */
	public function initialize_current_provider(): void {
		register_setting(
			'ai_assistant_settings_group',
			self::OPTION_CURRENT_PROVIDER,
			array(
				'type'              => 'string',
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => function ( $provider_id ) {
					error_log( 'AI Assistant: Sanitizing provider ID: ' . $provider_id );

					// Allow any valid provider ID, regardless of whether it's configured
					if ( in_array( $provider_id, $this->provider_ids, true ) ) {
						error_log( 'AI Assistant: Provider ID ' . $provider_id . ' is valid, saving it' );
						return $provider_id;
					}

					error_log( 'AI Assistant: Provider ID ' . $provider_id . ' is not valid' );
					return '';
				},
			)
		);
	}
}
