<?php
namespace Ai_Assistant\Admin\Settings;

use Ai_Assistant\Providers\Provider_Manager;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Manages settings for the AI Assistant plugin.
 *
 * @since      0.0.1
 * @package    Ai_Assistant\Admin\Settings
 */
class Settings_Manager {

	/**
	 * The provider manager instance.
	 *
	 * @var Provider_Manager
	 */
	private Provider_Manager $provider_manager;

	/**
	 * Constructor.
	 *
	 * @param Provider_Manager $provider_manager The provider manager instance.
	 */
	public function __construct( Provider_Manager $provider_manager ) {
		$this->provider_manager = $provider_manager;
		$this->provider_manager->initialize_provider_credentials();
		$this->provider_manager->initialize_current_provider();
	}

	/**
	 * Register plugin settings and fields
	 */
	public function register_settings() {
		register_setting( 'ai_assistant_settings_group', 'ai_assistant_anthropic_key' );
		register_setting( 'ai_assistant_settings_group', 'ai_assistant_google_key' );
		register_setting( 'ai_assistant_settings_group', 'ai_assistant_openai_key' );
		register_setting( 'ai_assistant_settings_group', 'ai_assistant_settings' );
		register_setting(
			'ai_assistant_settings_group',
			'ai_assistant_max_tokens',
			array(
				'type'              => 'integer',
				'default'           => 200,
				'sanitize_callback' => 'absint',
			)
		);
		register_setting(
			'ai_assistant_settings_group',
			'ai_assistant_selected_model',
			array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		// Add credentials section
		$this->add_credentials_section();

		// Add provider preferences section
		$this->add_provider_preferences_section();

		// Add debugging section
		$this->add_debugging_section();
	}

	/**
	 * Add credentials section and fields
	 */
	private function add_credentials_section() {
		add_settings_section(
			'ai_assistant_credentials_section',
			__( 'Credentials', 'ai-assistant' ),
			function () {
				echo '<p>Paste your API credentials for the different providers you would like to use here.</p>';
			},
			'ai-assistant-settings'
		);

		add_settings_field(
			'ai_assistant_anthropic_key',
			__( 'Anthropic', 'ai-assistant' ),
			function () {
				$value = esc_attr( get_option( 'ai_assistant_anthropic_key', '' ) );
				echo '<input type="password" name="ai_assistant_anthropic_key" value="' . $value . '" autocomplete="off" />';
			},
			'ai-assistant-settings',
			'ai_assistant_credentials_section'
		);

		add_settings_field(
			'ai_assistant_google_key',
			__( 'Google', 'ai-assistant' ),
			function () {
				$value = esc_attr( get_option( 'ai_assistant_google_key', '' ) );
				echo '<input type="password" name="ai_assistant_google_key" value="' . $value . '" autocomplete="off" />';
			},
			'ai-assistant-settings',
			'ai_assistant_credentials_section'
		);

		add_settings_field(
			'ai_assistant_openai_key',
			__( 'OpenAI', 'ai-assistant' ),
			function () {
				$value = esc_attr( get_option( 'ai_assistant_openai_key', '' ) );
				echo '<input type="password" name="ai_assistant_openai_key" value="' . $value . '" autocomplete="off" />';
			},
			'ai-assistant-settings',
			'ai_assistant_credentials_section'
		);
	}

	/**
	 * Add provider preferences section and fields
	 */
	private function add_provider_preferences_section() {
		add_settings_section(
			'ai_assistant_provider_section',
			__( 'Provider Preferences', 'ai-assistant' ),
			function () {
				echo '<p>Choose the provider you would like to use for the chatbot demo. Only providers with valid API credentials can be selected.</p>';
			},
			'ai-assistant-settings'
		);

		add_settings_field(
			'ai_assistant_current_provider',
			__( 'Current Provider', 'ai-assistant' ),
			array( $this, 'provider_dropdown_field' ),
			'ai-assistant-settings',
			'ai_assistant_provider_section'
		);

		add_settings_field(
			'ai_assistant_selected_model',
			__( 'Model', 'ai-assistant' ),
			array( $this, 'model_dropdown_field' ),
			'ai-assistant-settings',
			'ai_assistant_provider_section'
		);

		// Add field for function calling requirement
		add_settings_field(
			'ai_assistant_require_function_calling',
			__( 'Function Calling Support', 'ai-assistant' ),
			function () {
				$options = get_option( 'ai_assistant_settings', array() );
				$checked = isset( $options['require_function_calling'] ) && $options['require_function_calling'] ? 'checked' : '';
				echo '<input type="checkbox" name="ai_assistant_settings[require_function_calling]" value="1" ' . $checked . ' />';
				echo '<p class="description">' . esc_html__( 'Only show models that support function calling/tools (required for advanced features). Unchecking will show all models but may limit chatbot functionality.', 'ai-assistant' ) . '</p>';
			},
			'ai-assistant-settings',
			'ai_assistant_provider_section'
		);

		add_settings_field(
			'ai_assistant_max_tokens',
			__( 'Max Token Size', 'ai-assistant' ),
			function () {
				$value = esc_attr( get_option( 'ai_assistant_max_tokens', 200 ) );
				echo '<input type="number" min="1" max="4096" name="ai_assistant_max_tokens" value="' . $value . '" />';
				echo '<p class="description">' . esc_html__( 'Maximum number of tokens for LLM responses. Default is 200.', 'ai-assistant' ) . '</p>';
			},
			'ai-assistant-settings',
			'ai_assistant_provider_section'
		);
	}

	/**
	 * Add debugging section and fields
	 */
	private function add_debugging_section() {
		add_settings_section(
			'ai_assistant_debugging_section',
			__( 'Debugging', 'ai-assistant' ),
			function () {
				echo '<p>Debug settings to help troubleshoot provider-specific issues.</p>';
			},
			'ai-assistant-settings'
		);

		add_settings_field(
			'ai_assistant_debug_providers',
			__( 'Enable Provider Debugging', 'ai-assistant' ),
			function () {
				$options = get_option( 'ai_assistant_settings', array() );
				$checked = isset( $options['debug_providers'] ) && $options['debug_providers'] ? 'checked' : '';
				echo '<input type="checkbox" name="ai_assistant_settings[debug_providers]" value="1" ' . $checked . ' />';
				echo '<p class="description">' . esc_html__( 'Log all provider API requests and responses to the WordPress error log. Use this to troubleshoot provider-specific issues.', 'ai-assistant' ) . '</p>';
			},
			'ai-assistant-settings',
			'ai_assistant_debugging_section'
		);
	}

	/**
	 * Render the provider dropdown, only allowing selection of providers with valid credentials
	 */
	public function provider_dropdown_field() {
		$providers = array(
			'anthropic' => __( 'Anthropic', 'ai-assistant' ),
			'google'    => __( 'Google', 'ai-assistant' ),
			'openai'    => __( 'OpenAI', 'ai-assistant' ),
		);
		$options   = array(
			'anthropic' => get_option( 'ai_assistant_anthropic_key', '' ),
			'google'    => get_option( 'ai_assistant_google_key', '' ),
			'openai'    => get_option( 'ai_assistant_openai_key', '' ),
		);
		$current   = get_option( 'ai_assistant_current_provider', 'anthropic' );
		echo '<select name="ai_assistant_current_provider">';
		foreach ( $providers as $key => $label ) {
			$selected = ( $current === $key ) ? 'selected' : '';
			$has_key  = ! empty( $options[ $key ] );
			$style    = $has_key ? '' : ' style="color:#aaa;"';
			echo '<option value="' . esc_attr( $key ) . '" ' . $selected . $style . '>' . esc_html( $label ) . ( $has_key ? '' : ' (no key)' ) . '</option>';
		}
		echo '</select>';
		// Optionally, show a warning if the selected provider has no key
		if ( ! empty( $current ) && empty( $options[ $current ] ) ) {
			echo '<div style="color:#b00; margin-top:8px;">' . esc_html__( 'Warning: The selected provider does not have a valid API key. Please enter a key for this provider.', 'ai-assistant' ) . '</div>';
		}
	}

	/**
	 * Render the model dropdown for the selected provider.
	 */
	public function model_dropdown_field() {
		$current_provider         = get_option( 'ai_assistant_current_provider', 'anthropic' );
		$selected_model           = get_option( 'ai_assistant_selected_model', '' );
		$options                  = get_option( 'ai_assistant_settings', array() );
		$require_function_calling = isset( $options['require_function_calling'] ) && $options['require_function_calling'];

		// Define fallback models for each provider
		$fallback_models = array(
			'anthropic' => array(
				'claude-3-opus-20240229'   => array(
					'label'                     => 'Claude 3 Opus',
					'supports_function_calling' => true,
				),
				'claude-3-sonnet-20240229' => array(
					'label'                     => 'Claude 3 Sonnet',
					'supports_function_calling' => true,
				),
				'claude-3-haiku-20240307'  => array(
					'label'                     => 'Claude 3 Haiku',
					'supports_function_calling' => true,
				),
				'claude-2.1'               => array(
					'label'                     => 'Claude 2.1',
					'supports_function_calling' => false,
				),
				'claude-2.0'               => array(
					'label'                     => 'Claude 2.0',
					'supports_function_calling' => false,
				),
				'claude-instant-1.2'       => array(
					'label'                     => 'Claude Instant 1.2',
					'supports_function_calling' => false,
				),
			),
			'openai'    => array(
				'gpt-4o'        => array(
					'label'                     => 'GPT-4o',
					'supports_function_calling' => true,
				),
				'gpt-4-turbo'   => array(
					'label'                     => 'GPT-4 Turbo',
					'supports_function_calling' => true,
				),
				'gpt-4'         => array(
					'label'                     => 'GPT-4',
					'supports_function_calling' => true,
				),
				'gpt-3.5-turbo' => array(
					'label'                     => 'GPT-3.5 Turbo',
					'supports_function_calling' => true,
				),
			),
			'google'    => array(
				'gemini-1.5-pro-latest'   => array(
					'label'                     => 'Gemini 1.5 Pro',
					'supports_function_calling' => true,
				),
				'gemini-1.0-pro-latest'   => array(
					'label'                     => 'Gemini 1.0 Pro',
					'supports_function_calling' => true,
				),
				'gemini-1.0-ultra-latest' => array(
					'label'                     => 'Gemini 1.0 Ultra',
					'supports_function_calling' => true,
				),
			),
		);

		$models           = array();
		$provider_has_key = false;

		// Check if provider has API key
		$api_keys = array(
			'anthropic' => get_option( 'ai_assistant_anthropic_key', '' ),
			'google'    => get_option( 'ai_assistant_google_key', '' ),
			'openai'    => get_option( 'ai_assistant_openai_key', '' ),
		);

		if ( ! empty( $api_keys[ $current_provider ] ) ) {
			$provider_has_key = true;
		}

		// Only try to fetch models if provider has an API key
		if ( $provider_has_key ) {
			try {
				$api_service = new \Ai_Assistant\Admin\Services\Model_API_Service();

				// Fetch models based on provider
				if ( $current_provider === 'openai' ) {
					$models = $api_service->fetch_openai_models( $api_keys['openai'] );
				} elseif ( $current_provider === 'anthropic' ) {
					$models = $api_service->fetch_anthropic_models( $api_keys['anthropic'] );
				} elseif ( $current_provider === 'google' ) {
					$models = $api_service->fetch_google_models( $api_keys['google'] );
				}

				// If models not fetched from direct API call, try the AI Client
				if ( empty( $models ) ) {
					// ...existing client code...
				}

				// Debug
				error_log( 'AI Assistant: Models fetched for ' . $current_provider . ': ' . print_r( $models, true ) );
			} catch ( \Throwable $e ) {
				error_log( 'AI Assistant: Error fetching models: ' . $e->getMessage() );
				$models = array();
			}
		}

		// Use fallback models if no models were fetched
		if ( empty( $models ) && isset( $fallback_models[ $current_provider ] ) ) {
			$models = $fallback_models[ $current_provider ];
		}

		// Filter models based on function calling requirement
		$filtered_models = array();
		foreach ( $models as $model_id => $model_info ) {
			$label                     = is_array( $model_info ) ? $model_info['label'] : $model_info;
			$supports_function_calling = is_array( $model_info ) ?
				$model_info['supports_function_calling'] :
				$this->check_model_supports_function_calling( $current_provider, $model_id );

			// If requiring function calling, skip models that don't support it
			if ( $require_function_calling && ! $supports_function_calling ) {
				continue;
			}

			$filtered_models[ $model_id ] = $label . ( $supports_function_calling ? '' : ' (Basic Support)' );
		}

		// Build dropdown
		if ( empty( $filtered_models ) ) {
			echo '<select name="ai_assistant_selected_model" disabled><option value="">' . esc_html__( 'No compatible models available', 'ai-assistant' ) . '</option></select>';
			echo '<div style="color:#b00; margin-top:8px;">' . esc_html__( 'No models available that match your requirements. Try unchecking "Function Calling Support" to see more models.', 'ai-assistant' ) . '</div>';
			return;
		}

		echo '<select name="ai_assistant_selected_model">';
		echo '<option value="">' . esc_html__( 'Default (Auto-select)', 'ai-assistant' ) . '</option>';
		foreach ( $filtered_models as $model_id => $model_label ) {
			$selected = ( $selected_model === $model_id ) ? 'selected' : '';
			echo '<option value="' . esc_attr( $model_id ) . '" ' . $selected . '>' . esc_html( $model_label ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Check if a model supports function calling
	 *
	 * @param string $provider_id The provider ID
	 * @param string $model_id The model ID
	 * @return bool Whether the model supports function calling
	 */
	private function check_model_supports_function_calling( $provider_id, $model_id ) {
		// Default model compatibility table
		$function_calling_support = array(
			'openai'    => array(
				// GPT-4 models support function calling
				'gpt-4'         => true,
				'gpt-4-turbo'   => true,
				'gpt-4o'        => true,
				'gpt-3.5-turbo' => true,
				// Older models don't support function calling
				'text-davinci'  => false,
				'text-curie'    => false,
				'text-babbage'  => false,
				'text-ada'      => false,
				// DALL-E models don't support function calling
				'dall-e'        => false,
			),
			'anthropic' => array(
				// Claude 3 models support function calling
				'claude-3'        => true,
				'claude-3-opus'   => true,
				'claude-3-sonnet' => true,
				'claude-3-haiku'  => true,
				// Claude 2 models don't support function calling
				'claude-2'        => false,
				'claude-2.0'      => false,
				'claude-2.1'      => false,
				'claude-instant'  => false,
			),
			'google'    => array(
				// Gemini models support function calling
				'gemini-1.5' => true,
				'gemini-1.0' => true,
				'gemini-pro' => true,
			),
		);

		// Check for exact match
		if ( isset( $function_calling_support[ $provider_id ][ $model_id ] ) ) {
			return $function_calling_support[ $provider_id ][ $model_id ];
		}

		// Check for partial match (e.g., if model ID contains version)
		foreach ( $function_calling_support[ $provider_id ] ?? array() as $pattern => $supports ) {
			if ( stripos( $model_id, $pattern ) !== false ) {
				return $supports;
			}
		}

		// Default to false for unknown models to be safe
		return false;
	}
}
