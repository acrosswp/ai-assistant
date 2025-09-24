<?php
namespace Ai_Assistant\Includes;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * AI Client Manager - Wrapper around the PHP AI Client SDK
 *
 * Provides a centralized way to manage AI client instances with early bailout checks
 * and easy configuration management.
 *
 * @since 0.0.1
 * @package Ai_Assistant
 * @subpackage Ai_Assistant/includes
 */
class AI_Client_Manager {

	/**
	 * Single instance of this class
	 *
	 * @var AI_Client_Manager|null
	 */
	private static $instance = null;

	/**
	 * Available providers with their configuration
	 *
	 * @var array
	 */
	private $providers = array(
		'anthropic' => array(
			'name'          => 'Anthropic',
			'api_key_name'  => 'ANTHROPIC_API_KEY',
			'default_model' => 'claude-3-5-sonnet-20241022',
		),
		'google'    => array(
			'name'          => 'Google',
			'api_key_name'  => 'GOOGLE_GEMINI_API_KEY',
			'default_model' => 'gemini-1.5-flash',
		),
		'openai'    => array(
			'name'          => 'OpenAI',
			'api_key_name'  => 'OPENAI_API_KEY',
			'default_model' => 'gpt-4o-mini',
		),
	);

	/**
	 * Current plugin settings
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Get single instance
	 *
	 * @return AI_Client_Manager
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->settings = get_option( 'ai_assistant_settings', array() );
		$this->setup_api_keys();
	}

	/**
	 * Early bailout check - Can we use AI features?
	 *
	 * @return bool
	 */
	public function can_use_ai() {
		// Check if AI features are globally disabled
		if ( ! apply_filters( 'ai_assistant_enable_ai_features', true ) ) {
			return false;
		}

		// Check if user has permission
		if ( ! current_user_can( 'ai_assistant_access_chatbot' ) ) {
			return false;
		}

		// Check if we have a valid provider configured
		$current_provider = $this->get_current_provider_id();
		if ( empty( $current_provider ) || 'none' === $current_provider ) {
			return false;
		}

		// Check if provider has valid API key
		if ( ! $this->is_provider_configured( $current_provider ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Setup API keys in the AI Client
	 */
	private function setup_api_keys() {
		foreach ( $this->providers as $provider_id => $provider_config ) {
			$api_key = $this->get_provider_api_key( $provider_id );
			if ( ! empty( $api_key ) ) {
				// Set environment variable for the AI Client
				$_ENV[ $provider_config['api_key_name'] ] = $api_key;
				putenv( $provider_config['api_key_name'] . '=' . $api_key );
			}
		}
	}

	/**
	 * Get current provider ID
	 *
	 * @return string
	 */
	public function get_current_provider_id() {
		return isset( $this->settings['current_provider'] ) ? $this->settings['current_provider'] : 'none';
	}

	/**
	 * Get current model ID for a provider
	 *
	 * @param string $provider_id
	 * @return string
	 */
	public function get_preferred_model_id( $provider_id ) {
		if ( ! isset( $this->providers[ $provider_id ] ) ) {
			return '';
		}

		// For now, return default model. Can be extended to support user preferences
		return $this->providers[ $provider_id ]['default_model'];
	}

	/**
	 * Check if provider is properly configured
	 *
	 * @param string $provider_id
	 * @return bool
	 */
	public function is_provider_configured( $provider_id ) {
		$api_key = $this->get_provider_api_key( $provider_id );
		return ! empty( $api_key );
	}

	/**
	 * Get API key for a provider
	 *
	 * @param string $provider_id
	 * @return string
	 */
	public function get_provider_api_key( $provider_id ) {
		if ( ! isset( $this->providers[ $provider_id ] ) ) {
			return '';
		}

		return isset( $this->settings[ $provider_id ] ) ? $this->settings[ $provider_id ] : '';
	}

	/**
	 * Get provider metadata
	 *
	 * @param string $provider_id
	 * @return array
	 */
	public function get_provider_metadata( $provider_id ) {
		if ( ! isset( $this->providers[ $provider_id ] ) ) {
			return array(
				'name' => 'Unknown',
			);
		}

		return array(
			'name' => $this->providers[ $provider_id ]['name'],
		);
	}

	/**
	 * Get model metadata
	 *
	 * @param string $provider_id
	 * @param string $model_id
	 * @return array
	 */
	public function get_model_metadata( $provider_id, $model_id ) {
		return array(
			'name' => $model_id,
		);
	}

	/**
	 * Get available providers (only configured ones)
	 *
	 * @return array
	 */
	public function get_available_providers() {
		$available = array();
		foreach ( $this->providers as $provider_id => $provider_config ) {
			if ( $this->is_provider_configured( $provider_id ) ) {
				$available[ $provider_id ] = $provider_config;
			}
		}
		return $available;
	}

	/**
	 * Get AI Client registry instance
	 *
	 * @return mixed|Simple_AI_Client|null
	 */
	public function get_ai_client() {
		if ( ! $this->can_use_ai() ) {
			return null;
		}

		// Try to use the full AI Client SDK first
		if ( class_exists( '\WordPress\AiClient\AiClient' ) ) {
			try {
				return \WordPress\AiClient\AiClient::defaultRegistry();
			} catch ( \Exception $e ) {
				error_log( 'AI Assistant: Failed to initialize AI client - ' . $e->getMessage() );
				// Fall through to use fallback client
			}
		}

		// Use fallback client
		return new Simple_AI_Client();
	}

	/**
	 * Get provider model instance
	 *
	 * @param string|null $provider_id
	 * @param string|null $model_id
	 * @return mixed|null
	 */
	public function get_provider_model( $provider_id = null, $model_id = null ) {
		$client = $this->get_ai_client();
		if ( ! $client ) {
			return null;
		}

		$provider_id = $provider_id ? $provider_id : $this->get_current_provider_id();
		$model_id    = $model_id ? $model_id : $this->get_preferred_model_id( $provider_id );

		if ( empty( $provider_id ) || empty( $model_id ) ) {
			return null;
		}

		try {
			if ( method_exists( $client, 'getProviderModel' ) ) {
				return $client->getProviderModel( $provider_id, $model_id );
			}
		} catch ( \Exception $e ) {
			error_log( 'AI Assistant: Failed to get provider model: ' . $e->getMessage() );
		}

		return null;
	}

	/**
	 * Refresh settings cache
	 */
	public function refresh_settings() {
		$this->settings = get_option( 'ai_assistant_settings', array() );
		$this->setup_api_keys();
	}
}
