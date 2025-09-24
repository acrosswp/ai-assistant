<?php
namespace Ai_Assistant\Includes;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Simple AI Client using WordPress HTTP API
 *
 * A fallback implementation that uses WordPress's built-in HTTP API
 * when the full AI Client SDK is not available.
 *
 * @since 0.0.1
 * @package Ai_Assistant
 * @subpackage Ai_Assistant/includes
 */
class Simple_AI_Client {

	/**
	 * Provider configurations
	 *
	 * @var array
	 */
	private $provider_configs = array(
		'openai'    => array(
			'base_url' => 'https://api.openai.com/v1',
			'headers'  => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer {api_key}',
			),
			'endpoint' => '/chat/completions',
		),
		'anthropic' => array(
			'base_url' => 'https://api.anthropic.com/v1',
			'headers'  => array(
				'Content-Type'      => 'application/json',
				'x-api-key'         => '{api_key}',
				'anthropic-version' => '2023-06-01',
			),
			'endpoint' => '/messages',
		),
		'google'    => array(
			'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
			'headers'  => array(
				'Content-Type' => 'application/json',
			),
			'endpoint' => '/models/{model}:generateContent?key={api_key}',
		),
	);

	/**
	 * Generate AI response
	 *
	 * @param string $provider_id
	 * @param string $model_id
	 * @param string $api_key
	 * @param array  $messages
	 * @param string $system_instruction
	 * @return array|WP_Error
	 */
	public function generate_response( $provider_id, $model_id, $api_key, $messages, $system_instruction = '' ) {
		if ( ! isset( $this->provider_configs[ $provider_id ] ) ) {
			return new \WP_Error( 'invalid_provider', 'Invalid AI provider specified.' );
		}

		$config = $this->provider_configs[ $provider_id ];

		// Prepare request based on provider
		switch ( $provider_id ) {
			case 'openai':
				return $this->make_openai_request( $config, $model_id, $api_key, $messages, $system_instruction );

			case 'anthropic':
				return $this->make_anthropic_request( $config, $model_id, $api_key, $messages, $system_instruction );

			case 'google':
				return $this->make_google_request( $config, $model_id, $api_key, $messages, $system_instruction );

			default:
				return new \WP_Error( 'unsupported_provider', 'AI provider not yet supported in fallback mode.' );
		}
	}

	/**
	 * Make OpenAI API request
	 *
	 * @param array  $config
	 * @param string $model_id
	 * @param string $api_key
	 * @param array  $messages
	 * @param string $system_instruction
	 * @return array|WP_Error
	 */
	private function make_openai_request( $config, $model_id, $api_key, $messages, $system_instruction ) {
		$url = $config['base_url'] . $config['endpoint'];

		// Prepare messages for OpenAI format
		$formatted_messages = array();

		if ( ! empty( $system_instruction ) ) {
			$formatted_messages[] = array(
				'role'    => 'system',
				'content' => $system_instruction,
			);
		}

		foreach ( $messages as $message ) {
			$formatted_messages[] = array(
				'role'    => $message['role'],
				'content' => $message['content'],
			);
		}

		$body = array(
			'model'       => $model_id,
			'messages'    => $formatted_messages,
			'max_tokens'  => 1000,
			'temperature' => 0.7,
		);

		return $this->make_http_request(
			$url,
			$body,
			array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			)
		);
	}

	/**
	 * Make Anthropic API request
	 *
	 * @param array  $config
	 * @param string $model_id
	 * @param string $api_key
	 * @param array  $messages
	 * @param string $system_instruction
	 * @return array|WP_Error
	 */
	private function make_anthropic_request( $config, $model_id, $api_key, $messages, $system_instruction ) {
		$url = $config['base_url'] . $config['endpoint'];

		// Prepare messages for Anthropic format (exclude system messages)
		$formatted_messages = array();
		foreach ( $messages as $message ) {
			if ( $message['role'] !== 'system' ) {
				$formatted_messages[] = array(
					'role'    => $message['role'] === 'user' ? 'user' : 'assistant',
					'content' => $message['content'],
				);
			}
		}

		$body = array(
			'model'      => $model_id,
			'max_tokens' => 1000,
			'messages'   => $formatted_messages,
		);

		if ( ! empty( $system_instruction ) ) {
			$body['system'] = $system_instruction;
		}

		return $this->make_http_request(
			$url,
			$body,
			array(
				'x-api-key'         => $api_key,
				'anthropic-version' => '2023-06-01',
				'Content-Type'      => 'application/json',
			)
		);
	}

	/**
	 * Make Google Gemini API request
	 *
	 * @param array  $config
	 * @param string $model_id
	 * @param string $api_key
	 * @param array  $messages
	 * @param string $system_instruction
	 * @return array|WP_Error
	 */
	private function make_google_request( $config, $model_id, $api_key, $messages, $system_instruction ) {
		$endpoint = str_replace( '{model}', $model_id, $config['endpoint'] );
		$endpoint = str_replace( '{api_key}', $api_key, $endpoint );
		$url      = $config['base_url'] . $endpoint;

		// Prepare messages for Google format
		$contents = array();

		foreach ( $messages as $message ) {
			$role       = $message['role'] === 'user' ? 'user' : 'model';
			$contents[] = array(
				'role'  => $role,
				'parts' => array(
					array( 'text' => $message['content'] ),
				),
			);
		}

		$body = array(
			'contents'         => $contents,
			'generationConfig' => array(
				'maxOutputTokens' => 1000,
				'temperature'     => 0.7,
			),
		);

		if ( ! empty( $system_instruction ) ) {
			$body['systemInstruction'] = array(
				'parts' => array(
					array( 'text' => $system_instruction ),
				),
			);
		}

		return $this->make_http_request(
			$url,
			$body,
			array(
				'Content-Type' => 'application/json',
			)
		);
	}

	/**
	 * Make HTTP request using WordPress HTTP API
	 *
	 * @param string $url
	 * @param array  $body
	 * @param array  $headers
	 * @return array|WP_Error
	 */
	private function make_http_request( $url, $body, $headers ) {
		$response = wp_remote_post(
			$url,
			array(
				'headers' => $headers,
				'body'    => wp_json_encode( $body ),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code   = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $status_code !== 200 ) {
			$error_data    = json_decode( $response_body, true );
			$error_message = isset( $error_data['error']['message'] )
				? $error_data['error']['message']
				: 'API request failed with status ' . $status_code;

			return new \WP_Error( 'api_error', $error_message );
		}

		$decoded_response = json_decode( $response_body, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_Error( 'json_decode_error', 'Failed to decode API response' );
		}

		return $decoded_response;
	}

	/**
	 * Extract content from API response
	 *
	 * @param string $provider_id
	 * @param array  $response
	 * @return string
	 */
	public function extract_content( $provider_id, $response ) {
		switch ( $provider_id ) {
			case 'openai':
				return isset( $response['choices'][0]['message']['content'] )
					? $response['choices'][0]['message']['content']
					: '';

			case 'anthropic':
				return isset( $response['content'][0]['text'] )
					? $response['content'][0]['text']
					: '';

			case 'google':
				return isset( $response['candidates'][0]['content']['parts'][0]['text'] )
					? $response['candidates'][0]['content']['parts'][0]['text']
					: '';

			default:
				return '';
		}
	}
}
