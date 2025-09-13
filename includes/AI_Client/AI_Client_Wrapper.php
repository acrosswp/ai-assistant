<?php
/**
 * AI Client Wrapper
 *
 * Provides a simplified interface to the WordPress PHP AI Client library.
 *
 * @package Ai_Assistant
 * @subpackage Ai_Assistant/includes/AI_Client
 * @since 0.0.1
 */

namespace Ai_Assistant\Includes\AI_Client;

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use Exception;

/**
 * AI Client Wrapper Class
 *
 * This class provides a simpler, more stable interface to the WordPress PHP AI Client
 * allowing the plugin to interact with AI providers without tight coupling to the
 * underlying library implementation.
 *
 * @since 0.0.1
 */
class AI_Client_Wrapper {
	/**
	 * Default provider IDs supported by this wrapper.
	 *
	 * @var array<string>
	 */
	private array $provider_ids = array( 'anthropic', 'google', 'openai' );

	/**
	 * The AiClient registry instance.
	 *
	 * @var mixed
	 */
	private $registry;

	/**
	 * Cached mapping of provider model information.
	 *
	 * @var array<string, array>
	 */
	private array $provider_models = array();

	/**
	 * Constructor.
	 *
	 * @param array<string> $provider_ids Optional. List of provider IDs to support.
	 */
	public function __construct( array $provider_ids = array() ) {
		if ( ! empty( $provider_ids ) ) {
			$this->provider_ids = $provider_ids;
		}

		try {
			$this->registry = AiClient::defaultRegistry();

			// Add an admin notice if there are no configured providers
			add_action( 'admin_notices', array( $this, 'maybe_show_no_providers_notice' ) );
		} catch ( Exception $e ) {
			// Log error but don't break construction
			error_log( 'AI Assistant: Failed to initialize AiClient registry: ' . $e->getMessage() );
		}
	}

	/**
	 * Maybe show an admin notice if no providers are available.
	 *
	 * @return void
	 */
	public function maybe_show_no_providers_notice(): void {
		// Only show on the AI Assistant settings page
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'ai-assistant_page_ai-assistant' !== $screen->id ) {
			return;
		}

		$available_providers = $this->get_available_provider_ids();
		if ( empty( $available_providers ) ) {
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'AI Assistant: No AI providers are configured', 'ai-assistant' ); ?></strong>
				</p>
				<p>
					<?php esc_html_e( 'Please add an API key for at least one provider (OpenAI, Anthropic, or Google) to use the AI Assistant features.', 'ai-assistant' ); ?>
				</p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-assistant#provider-settings' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Configure Providers', 'ai-assistant' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ai-assistant&tab=troubleshooting' ) ); ?>" class="button">
						<?php esc_html_e( 'Troubleshooting', 'ai-assistant' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Gets the available provider IDs.
	 *
	 * @return array<string> List of available configured provider IDs.
	 */
	public function get_available_provider_ids(): array {
		if ( ! $this->registry ) {
			return array();
		}

		try {
			return array_values(
				array_filter(
					$this->provider_ids,
					function ( string $provider_id ) {
						return $this->registry->hasProvider( $provider_id ) &&
								$this->registry->isProviderConfigured( $provider_id );
					}
				)
			);
		} catch ( Exception $e ) {
			error_log( 'AI Assistant: Error getting available providers: ' . $e->getMessage() );
			return array();
		}
	}

	/**
	 * Checks if a provider is available and configured.
	 *
	 * @param string $provider_id The provider ID to check.
	 * @return bool Whether the provider is available.
	 */
	public function is_provider_available( string $provider_id ): bool {
		if ( ! $this->registry ) {
			return false;
		}

		try {
			return $this->registry->hasProvider( $provider_id ) &&
					$this->registry->isProviderConfigured( $provider_id );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Sets the authentication for a provider.
	 *
	 * @param string $provider_id The provider ID.
	 * @param string $api_key The API key for the provider.
	 * @return bool Whether the authentication was set successfully.
	 */
	public function set_provider_authentication( string $provider_id, string $api_key ): bool {
		if ( ! $this->registry || empty( $api_key ) ) {
			return false;
		}

		try {
			$this->registry->setProviderRequestAuthentication(
				$provider_id,
				new ApiKeyRequestAuthentication( $api_key )
			);
			return true;
		} catch ( Exception $e ) {
			error_log( 'AI Assistant: Error setting provider authentication: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Gets the preferred model ID for a provider.
	 *
	 * @param string $provider_id The provider ID.
	 * @param array  $requirements Optional. The model requirements.
	 * @return string The preferred model ID.
	 */
	public function get_preferred_model_id( string $provider_id, array $requirements = array() ): string {
		// Default model recommendations
		$default_models = array(
			'anthropic' => 'claude-sonnet-4-20250514',
			'google'    => 'gemini-2.5-flash',
			'openai'    => 'gpt-4-turbo',
		);

		// Fallback models for simpler requirements
		$fallback_models = array(
			'anthropic' => 'claude-instant-1.2',
			'google'    => 'gemini-1.0-pro',
			'openai'    => 'gpt-3.5-turbo',
		);

		// If we have specific requirements, try to find a model that matches
		if ( ! empty( $requirements ) && $this->registry && $this->is_provider_available( $provider_id ) ) {
			try {
				// First try with the full requirements
				$model_id = $this->find_provider_model_for_requirements( $provider_id, $requirements );
				if ( ! empty( $model_id ) ) {
					return $model_id;
				}

				// If no model found, try with simplified requirements (remove specific capabilities)
				$simplified_requirements = array();
				if ( isset( $requirements['function_calling'] ) && $requirements['function_calling'] ) {
					// Keep function calling as it's important
					$simplified_requirements['function_calling'] = true;
				}

				$model_id = $this->find_provider_model_for_requirements( $provider_id, $simplified_requirements );
				if ( ! empty( $model_id ) ) {
					return $model_id;
				}
			} catch ( Exception $e ) {
				error_log( 'AI Assistant: Error finding model for requirements: ' . $e->getMessage() );
				// Continue to use default models
			}
		}

		// If we still don't have a model, return the default or fallback
		if ( isset( $default_models[ $provider_id ] ) ) {
			return $default_models[ $provider_id ];
		}

		if ( isset( $fallback_models[ $provider_id ] ) ) {
			return $fallback_models[ $provider_id ];
		}

		return '';
	}

	/**
	 * Finds a model for a provider that matches the given requirements.
	 *
	 * @param string $provider_id The provider ID.
	 * @param array  $requirements The model requirements.
	 * @return string The model ID or empty string if none found.
	 */
	private function find_provider_model_for_requirements( string $provider_id, array $requirements = array() ): string {
		if ( ! $this->registry ) {
			return '';
		}

		try {
			// Create model requirements based on specified capabilities
			$required_capabilities = array();

			if ( isset( $requirements['function_calling'] ) && $requirements['function_calling'] ) {
				// Add function calling capability if supported
				// Note: WordPress\AiClient\Providers\Models\Enums\CapabilityEnum doesn't
				// have a specific function_calling capability, we might need to infer
				// from other capabilities or add as a custom option
			}

			if ( isset( $requirements['vision'] ) && $requirements['vision'] ) {
				// Add vision capability if supported
			}

			if ( isset( $requirements['embedding'] ) && $requirements['embedding'] ) {
				// Use embedding generation if supported
				$required_capabilities[] = \WordPress\AiClient\Providers\Models\Enums\CapabilityEnum::embeddingGeneration();
			}

			// Create ModelRequirements from the capabilities
			$model_requirements = new ModelRequirements( $required_capabilities, array() );

			// Find models for this provider that match requirements
			$models_metadata = $this->registry->findProviderModelsMetadataForSupport( $provider_id, $model_requirements );

			// Return the first model if any found
			if ( ! empty( $models_metadata ) && isset( $models_metadata[0] ) ) {
				return $models_metadata[0]->getId();
			}
		} catch ( Exception $e ) {
			error_log( 'AI Assistant: Error finding model for provider: ' . $e->getMessage() );
		}

		return '';
	}

	/**
	 * Finds models that support the given requirements.
	 *
	 * @param array $requirements The model requirements.
	 * @return array List of models that match the requirements.
	 */
	public function find_models_for_requirements( array $requirements = array() ): array {
		if ( ! $this->registry ) {
			return array();
		}

		try {
			// Create model requirements based on specified capabilities
			$required_capabilities = array();

			if ( isset( $requirements['function_calling'] ) && $requirements['function_calling'] ) {
				// Add function calling capability if supported
				// Note: WordPress\AiClient\Providers\Models\Enums\CapabilityEnum doesn't
				// have a specific function_calling capability, we might need to infer
				// from other capabilities or add as a custom option
			}

			if ( isset( $requirements['vision'] ) && $requirements['vision'] ) {
				// Add vision capability if supported
			}

			if ( isset( $requirements['embedding'] ) && $requirements['embedding'] ) {
				// Use embedding generation if supported
				$required_capabilities[] = \WordPress\AiClient\Providers\Models\Enums\CapabilityEnum::embeddingGeneration();
			}

			// Create ModelRequirements from the capabilities
			$model_requirements = new ModelRequirements( $required_capabilities, array() );

			$models_metadata = $this->registry->findModelsMetadataForSupport( $model_requirements );

			$result = array();
			foreach ( $models_metadata as $provider_models ) {
				$provider_id            = $provider_models->getProviderMetadata()->getId();
				$result[ $provider_id ] = array(
					'name'   => $provider_models->getProviderMetadata()->getName(),
					'models' => array(),
				);

				foreach ( $provider_models->getModelMetadataList() as $model_metadata ) {
					$result[ $provider_id ]['models'][] = array(
						'id'   => $model_metadata->getId(),
						'name' => $model_metadata->getName(),
					);
				}
			}

			return $result;
		} catch ( Exception $e ) {
			error_log( 'AI Assistant: Error finding models: ' . $e->getMessage() );
			return array();
		}
	}

	/**
	 * Executes a completion request to generate text.
	 *
	 * @param string $provider_id The provider ID.
	 * @param string $model_id The model ID.
	 * @param string $prompt The prompt text.
	 * @param array $options Optional. Additional options for the completion.
	 * @return string|bool The generated text or false on failure.
	 */
	public function generate_text( string $provider_id, string $model_id, string $prompt, array $options = array() ): string|bool {
		if ( ! $this->registry ) {
			return false;
		}

		try {
			$model_config = new ModelConfig();

			// Apply options if provided
			if ( ! empty( $options ) ) {
				if ( isset( $options['temperature'] ) ) {
					$model_config->setTemperature( (float) $options['temperature'] );
				}

				if ( isset( $options['max_tokens'] ) ) {
					$model_config->setMaxTokens( (int) $options['max_tokens'] );
				}
			}

			$model = $this->registry->getProviderModel( $provider_id, $model_id, $model_config );

			// Get generate_text operation
			if ( method_exists( $model, 'generate_text' ) ) {
				$result = $model->generate_text( $prompt );
				return $result->getText();
			}

			return false;
		} catch ( Exception $e ) {
			error_log( 'AI Assistant: Error generating text: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Executes a chat completion request.
	 *
	 * @param string $provider_id The provider ID.
	 * @param string $model_id The model ID.
	 * @param array $messages The chat messages array.
	 * @param array $options Optional. Additional options for the chat completion.
	 * @return array|bool The response messages or false on failure.
	 * @throws Exception If no model is available or other critical errors occur.
	 */
	public function chat_completion( string $provider_id, string $model_id, array $messages, array $options = array() ): array|bool {
		if ( ! $this->registry ) {
			throw new Exception( 'AI registry not initialized' );
		}

		// Check if provider is available
		if ( ! $this->is_provider_available( $provider_id ) ) {
			error_log( "AI Assistant: Provider {$provider_id} is not available or not configured" );

			// Try fallback providers if enabled
			if ( isset( $options['try_fallback_providers'] ) && $options['try_fallback_providers'] ) {
				$available_providers = $this->get_available_provider_ids();
				if ( ! empty( $available_providers ) ) {
					$fallback_provider = $available_providers[0];
					error_log( "AI Assistant: Attempting fallback to provider: {$fallback_provider}" );

					// Get a suitable model for this provider
					$fallback_model = $this->get_preferred_model_id( $fallback_provider, $options['requirements'] ?? array() );
					if ( ! empty( $fallback_model ) ) {
						return $this->chat_completion(
							$fallback_provider,
							$fallback_model,
							$messages,
							array_diff_key( $options, array( 'try_fallback_providers' => true ) )
						);
					}
				}
			}

			throw new Exception( "Provider {$provider_id} is not available or not configured" );
		}

		// Check model ID
		if ( empty( $model_id ) ) {
			error_log( "AI Assistant: No model ID specified for provider {$provider_id}" );

			// Try to find a suitable model
			$requirements   = $options['requirements'] ?? array();
			$fallback_model = $this->get_preferred_model_id( $provider_id, $requirements );

			if ( empty( $fallback_model ) ) {
				throw new Exception( "No suitable model found for provider {$provider_id}" );
			}

			$model_id = $fallback_model;
			error_log( "AI Assistant: Using model {$model_id} as fallback" );
		}

		try {
			$model_config = new ModelConfig();

			// Apply options if provided
			if ( ! empty( $options ) ) {
				if ( isset( $options['temperature'] ) ) {
					$model_config->setTemperature( (float) $options['temperature'] );
				}

				if ( isset( $options['max_tokens'] ) ) {
					$model_config->setMaxTokens( (int) $options['max_tokens'] );
				}

				if ( isset( $options['functions'] ) ) {
					// Set function declarations for AI model
					// Create FunctionDeclaration objects from the provided functions
					$function_declarations = array();
					foreach ( $options['functions'] as $function ) {
						if ( isset( $function['name'] ) && isset( $function['description'] ) ) {
							$function_declarations[] = new \WordPress\AiClient\Tools\DTO\FunctionDeclaration(
								$function['name'],
								$function['description'],
								$function['parameters'] ?? array()
							);
						}
					}
					$model_config->setFunctionDeclarations( $function_declarations );
				}
			}

			$model = $this->registry->getProviderModel( $provider_id, $model_id, $model_config );

			// Format messages for the client
			$formatted_messages = array();
			foreach ( $messages as $message ) {
				if ( isset( $message['role'] ) && isset( $message['content'] ) ) {
					$formatted_messages[] = array(
						'role'    => $message['role'],
						'content' => $message['content'],
					);
				}
			}

			// Get chat_completion operation
			if ( method_exists( $model, 'chat_completion' ) ) {
				$result = $model->chat_completion( $formatted_messages );
				return array(
					'content'       => $result->getMessage()['content'] ?? '',
					'function_call' => $result->getFunctionCall() ?? null,
				);
			}

			throw new Exception( "Model {$model_id} does not support chat completion" );
		} catch ( Exception $e ) {
			error_log( 'AI Assistant: Error in chat completion: ' . $e->getMessage() );

			// Try with a fallback model if this was a "no models found" type of error
			if ( strpos( $e->getMessage(), 'models' ) !== false && isset( $options['fallback_model_id'] ) ) {
				try {
					error_log( 'AI Assistant: Attempting fallback to model: ' . $options['fallback_model_id'] );
					return $this->chat_completion(
						$provider_id,
						$options['fallback_model_id'],
						$messages,
						array_diff_key( $options, array( 'fallback_model_id' => true ) )
					);
				} catch ( Exception $fallback_e ) {
					error_log( 'AI Assistant: Fallback model also failed: ' . $fallback_e->getMessage() );
				}
			}

			// Throw the exception to be handled by the caller
			throw $e;
		}
	}

	/**
	 * Gets the raw AiClient registry instance.
	 *
	 * For advanced usage where the wrapper doesn't provide needed functionality.
	 * Use with caution as this exposes the underlying implementation.
	 *
	 * @return mixed The AiClient registry instance.
	 */
	public function get_registry() {
		return $this->registry;
	}
}
