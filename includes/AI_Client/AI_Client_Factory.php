<?php
/**
 * AI Client Factory
 *
 * Factory class for creating and managing AI Client Wrapper instances.
 *
 * @package Ai_Assistant
 * @subpackage Ai_Assistant/includes/AI_Client
 * @since 0.0.1
 */

namespace Ai_Assistant\Includes\AI_Client;

/**
 * AI Client Factory Class
 *
 * Provides a factory for creating and retrieving AI Client Wrapper instances.
 *
 * @since 0.0.1
 */
class AI_Client_Factory {
	/**
	 * Singleton instance of the AI Client Wrapper.
	 *
	 * @var AI_Client_Wrapper|null
	 */
	private static $instance = null;

	/**
	 * Gets the singleton instance of the AI Client Wrapper.
	 *
	 * @param array $provider_ids Optional. List of provider IDs to support.
	 * @return AI_Client_Wrapper The AI Client Wrapper instance.
	 */
	public static function get_instance( array $provider_ids = array() ) {
		if ( null === self::$instance ) {
			self::$instance = new AI_Client_Wrapper( $provider_ids );
		}
		return self::$instance;
	}

	/**
	 * Creates a new AI Client Wrapper instance.
	 *
	 * Use this method when you need a fresh instance with specific configurations.
	 *
	 * @param array $provider_ids Optional. List of provider IDs to support.
	 * @return AI_Client_Wrapper A new AI Client Wrapper instance.
	 */
	public static function create( array $provider_ids = array() ) {
		return new AI_Client_Wrapper( $provider_ids );
	}

	/**
	 * Sets up provider authentication for the singleton instance.
	 *
	 * @param array $provider_credentials Array of provider ID => API key pairs.
	 * @return bool Whether all authentications were set successfully.
	 */
	public static function setup_provider_authentication( array $provider_credentials ) {
		$client  = self::get_instance();
		$success = true;

		foreach ( $provider_credentials as $provider_id => $api_key ) {
			if ( ! empty( $api_key ) ) {
				$result = $client->set_provider_authentication( $provider_id, $api_key );
				if ( ! $result ) {
					$success = false;
				}
			}
		}

		return $success;
	}
}
