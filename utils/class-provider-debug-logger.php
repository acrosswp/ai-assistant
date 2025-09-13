<?php
/**
 * AI Assistant Provider Debug Logging Utility
 *
 * This script helps debug provider-specific issues by logging all API requests
 * and responses for each provider. It can be enabled via the debug setting in
 * the plugin options or by adding a constant to wp-config.php.
 *
 * @package AI_Assistant
 */

namespace AI_Assistant\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provider Debug Logger class
 */
class Provider_Debug_Logger {

	/**
	 * Initialize the logger
	 */
	public static function init() {
		// Check if debug logging is enabled.
		if ( ! self::is_debug_logging_enabled() ) {
			return;
		}

		// Add filters to log provider API requests and responses.
		add_filter( 'ai_assistant_pre_provider_request', array( __CLASS__, 'log_provider_request' ), 10, 3 );
		add_filter( 'ai_assistant_post_provider_response', array( __CLASS__, 'log_provider_response' ), 10, 4 );

		// Log initialization.
		self::log( 'Provider debug logging initialized' );
	}

	/**
	 * Check if debug logging is enabled
	 *
	 * @return bool True if debug logging is enabled
	 */
	public static function is_debug_logging_enabled() {
		// Check for constant definition in wp-config.php.
		if ( defined( 'AI_ASSISTANT_DEBUG_PROVIDERS' ) && constant( 'AI_ASSISTANT_DEBUG_PROVIDERS' ) ) {
			return true;
		}

		// Check for plugin option.
		$options = get_option( 'ai_assistant_settings', array() );
		return isset( $options['debug_providers'] ) && $options['debug_providers'];
	}

	/**
	 * Log provider API request
	 *
	 * @param array  $request_data The request data being sent to the provider.
	 * @param string $provider_id The provider ID (openai, anthropic, etc).
	 * @param array  $args Additional request arguments.
	 * @return array The unmodified request data
	 */
	public static function log_provider_request( $request_data, $provider_id, $args ) {
		// Create a safe version of the request data for logging (remove API keys).
		$safe_data = self::sanitize_sensitive_data( $request_data );

		// Log the request.
		self::log(
			sprintf(
				'Provider Request (%s): %s',
				$provider_id,
				wp_json_encode( $safe_data, JSON_PRETTY_PRINT )
			)
		);

		// Return unmodified data.
		return $request_data;
	}

	/**
	 * Log provider API response
	 *
	 * @param mixed  $response The response from the provider.
	 * @param array  $request_data The original request data.
	 * @param string $provider_id The provider ID.
	 * @param array  $args Additional request arguments.
	 * @return mixed The unmodified response
	 */
	public static function log_provider_response( $response, $request_data, $provider_id, $args ) {
		// Check if the response is an error.
		$is_error = is_wp_error( $response );

		// Log the response.
		if ( $is_error ) {
			self::log(
				sprintf(
					'Provider Error (%s): %s',
					$provider_id,
					$response->get_error_message()
				)
			);
		} else {
			// Create a safe version of the response for logging.
			$safe_response = self::sanitize_sensitive_data( $response );

			self::log(
				sprintf(
					'Provider Response (%s): %s',
					$provider_id,
					wp_json_encode( $safe_response, JSON_PRETTY_PRINT )
				)
			);
		}

		// Return unmodified response.
		return $response;
	}

	/**
	 * Sanitize sensitive data for logging
	 *
	 * @param mixed $data The data to sanitize.
	 * @return mixed The sanitized data
	 */
	private static function sanitize_sensitive_data( $data ) {
		// Create a copy of the data.
		$safe_data = $data;

		// If it's an array, recursively sanitize it.
		if ( is_array( $safe_data ) ) {
			// Check for and remove API keys.
			foreach ( $safe_data as $key => $value ) {
				if ( in_array( $key, array( 'api_key', 'authorization', 'key', 'secret', 'token' ), true ) ) {
					$safe_data[ $key ] = '[REDACTED]';
				} elseif ( is_array( $value ) || is_object( $value ) ) {
					$safe_data[ $key ] = self::sanitize_sensitive_data( $value );
				}
			}
		} elseif ( is_object( $safe_data ) ) {
			// If it's an object, convert to array, sanitize, and convert back.
			$arr       = (array) $safe_data;
			$arr       = self::sanitize_sensitive_data( $arr );
			$safe_data = (object) $arr;
		}

		return $safe_data;
	}

	/**
	 * Log a message to the debug log
	 *
	 * @param string $message The message to log.
	 */
	public static function log( $message ) {
		if ( ! is_string( $message ) ) {
			$message = wp_json_encode( $message );
		}

		error_log( sprintf( '[AI Assistant Provider Debug] %s', $message ) );
	}
}
