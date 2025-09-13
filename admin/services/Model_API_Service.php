<?php
namespace Ai_Assistant\Admin\Services;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Service class for API calls to fetch models from different providers.
 *
 * @since      0.0.1
 * @package    Ai_Assistant\Admin\Services
 */
class Model_API_Service {

	/**
	 * Fetch available models directly from OpenAI API
	 *
	 * @param string $api_key The OpenAI API key
	 * @return array The available models
	 */
	public function fetch_openai_models( $api_key ) {
		$models = array();

		$response = wp_remote_get(
			'https://api.openai.com/v1/models',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'OpenAI API Error: ' . $response->get_error_message() );
			return $models;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			error_log( 'OpenAI API Error: ' . wp_remote_retrieve_body( $response ) );
			return $models;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || ! isset( $body['data'] ) || ! is_array( $body['data'] ) ) {
			return $models;
		}

		// Filter for chat models and newer models
		$chat_models = array();
		foreach ( $body['data'] as $model ) {
			// Only include GPT models and explicitly include specific non-GPT models we want
			if (
				isset( $model['id'] ) &&
				(
					strpos( $model['id'], 'gpt' ) !== false ||
					strpos( $model['id'], 'text-embedding' ) !== false ||
					$model['id'] === 'dall-e-3' ||
					$model['id'] === 'dall-e-2'
				)
			) {
				$chat_models[ $model['id'] ] = $model['id'];
			}
		}

		// Sort models to put newer versions first
		uksort(
			$chat_models,
			function ( $a, $b ) {
				// Put GPT-4 models before GPT-3.5 models
				if ( strpos( $a, 'gpt-4' ) !== false && strpos( $b, 'gpt-3.5' ) !== false ) {
					return -1;
				}
				if ( strpos( $a, 'gpt-3.5' ) !== false && strpos( $b, 'gpt-4' ) !== false ) {
					return 1;
				}

				// Put models with "turbo" after other models of the same version
				if ( strpos( $a, 'turbo' ) !== false && strpos( $b, 'turbo' ) === false ) {
					return -1;
				}
				if ( strpos( $a, 'turbo' ) === false && strpos( $b, 'turbo' ) !== false ) {
					return 1;
				}

				return strcmp( $a, $b );
			}
		);

		return $chat_models;
	}

	/**
	 * Fetch available models directly from Anthropic API
	 *
	 * @param string $api_key The Anthropic API key
	 * @return array The available models
	 */
	public function fetch_anthropic_models( $api_key ) {
		$models = array();

		// Current Anthropic API version
		$anthropic_version = '2023-06-01';

		$response = wp_remote_get(
			'https://api.anthropic.com/v1/models',
			array(
				'headers' => array(
					'x-api-key'         => $api_key,
					'anthropic-version' => $anthropic_version,
					'Content-Type'      => 'application/json',
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'Anthropic API Error: ' . $response->get_error_message() );
			return $models;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			error_log( 'Anthropic API Error: ' . wp_remote_retrieve_body( $response ) );
			return $models;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || ! isset( $body['data'] ) || ! is_array( $body['data'] ) ) {
			return $models;
		}

		// Process models
		$chat_models = array();
		foreach ( $body['data'] as $model ) {
			if ( isset( $model['id'] ) ) {
				$display_name                = isset( $model['display_name'] ) ? $model['display_name'] : $model['id'];
				$chat_models[ $model['id'] ] = $display_name;
			}
		}

		// Sort models to put newer Claude models first
		uksort(
			$chat_models,
			function ( $a, $b ) {
				// Put Claude 3 models before Claude 2 models
				if ( strpos( $a, 'claude-3' ) !== false && strpos( $b, 'claude-3' ) === false ) {
					return -1;
				}
				if ( strpos( $a, 'claude-3' ) === false && strpos( $b, 'claude-3' ) !== false ) {
					return 1;
				}

				// Within Claude 3, order by capability (Opus > Sonnet > Haiku)
				if ( strpos( $a, 'claude-3' ) !== false && strpos( $b, 'claude-3' ) !== false ) {
					$capability_order = array(
						'opus'   => 1,
						'sonnet' => 2,
						'haiku'  => 3,
					);
					foreach ( $capability_order as $capability => $order ) {
						$a_has = strpos( $a, $capability ) !== false;
						$b_has = strpos( $b, $capability ) !== false;

						if ( $a_has && ! $b_has ) {
							return -1;
						}
						if ( ! $a_has && $b_has ) {
							return 1;
						}
					}
				}

				return strcmp( $a, $b );
			}
		);

		return $chat_models;
	}

	/**
	 * Fetch available models directly from Google's Generative Language API
	 *
	 * @param string $api_key The Google API key
	 * @return array The available models
	 */
	public function fetch_google_models( $api_key ) {
		$models = array();

		$response = wp_remote_get(
			'https://generativelanguage.googleapis.com/v1beta/models?pageSize=200',
			array(
				'headers' => array(
					'x-goog-api-key' => $api_key,
					'Content-Type'   => 'application/json',
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'Google API Error: ' . $response->get_error_message() );
			return $models;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			error_log( 'Google API Error: ' . wp_remote_retrieve_body( $response ) );
			return $models;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || ! isset( $body['models'] ) || ! is_array( $body['models'] ) ) {
			return $models;
		}

		// Process models
		$gemini_models = array();
		foreach ( $body['models'] as $model ) {
			if ( isset( $model['name'] ) ) {
				// Extract the model ID from the full name (formats like "models/gemini-pro")
				$model_parts = explode( '/', $model['name'] );
				$model_id    = end( $model_parts );

				// Only include Gemini models and filter out versions we don't want
				if ( strpos( $model_id, 'gemini' ) !== false ) {
					// For display name, use displayName if available, otherwise the model ID
					$display_name = isset( $model['displayName'] ) ? $model['displayName'] : $model_id;

					// Add model version or date if available
					if ( isset( $model['version'] ) ) {
						$display_name .= ' (' . $model['version'] . ')';
					}

					$gemini_models[ $model_id ] = $display_name;
				}
			}
		}

		// Sort models to put newer Gemini models first
		uksort(
			$gemini_models,
			function ( $a, $b ) {
				// Put Gemini 1.5 models before Gemini 1.0 models
				if ( strpos( $a, '1.5' ) !== false && strpos( $b, '1.5' ) === false ) {
					return -1;
				}
				if ( strpos( $a, '1.5' ) === false && strpos( $b, '1.5' ) !== false ) {
					return 1;
				}

				// Put Pro models before non-pro models
				$a_pro = strpos( $a, 'pro' ) !== false;
				$b_pro = strpos( $b, 'pro' ) !== false;
				if ( $a_pro && ! $b_pro ) {
					return -1;
				}
				if ( ! $a_pro && $b_pro ) {
					return 1;
				}

				return strcmp( $a, $b );
			}
		);

		return $gemini_models;
	}
}
