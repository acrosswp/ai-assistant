<?php
/**
 * Class Ai_Assistant\REST_Routes\Chatbot_Messages_REST_Route
 *
 * @since 0.0.1
 * @package ai-assistant
 */

namespace Ai_Assistant\REST_Routes;

use Exception;
use Ai_Assistant\Agents\Chatbot_Agent;
use Ai_Assistant\Providers\Provider_Manager;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessagePartChannelEnum;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class for the chatbot messages REST API routes.
 *
 * @since 0.0.1
 */
class Chatbot_Messages_REST_Route {

	/**
	 * The provider manager instance.
	 *
	 * @since 0.0.1
	 * @var Provider_Manager
	 */
	private Provider_Manager $provider_manager;

	/**
	 * The REST route namespace.
	 *
	 * @since 0.0.1
	 * @var string
	 */
	protected string $rest_namespace;

	/**
	 * The REST route base.
	 *
	 * @since 0.0.1
	 * @var string
	 */
	protected string $rest_base;

	/**
	 * Constructor.
	 *
	 * @since 0.0.1
	 *
	 * @param Provider_Manager $provider_manager The provider manager instance.
	 * @param string           $rest_namespace   The REST route namespace.
	 * @param string           $rest_base        The REST route base.
	 */
	public function __construct( Provider_Manager $provider_manager, string $rest_namespace, string $rest_base ) {
		$this->provider_manager = $provider_manager;
		$this->rest_namespace   = $rest_namespace;
		$this->rest_base        = $rest_base;
	}

	/**
	 * Registers the REST route with its endpoints.
	 *
	 * @since 0.0.1
	 */
	public function register_route(): void {
		register_rest_route(
			$this->rest_namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_messages' ),
					'permission_callback' => array( $this, 'check_permissions' ),
					'args'                => array(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'send_message' ),
					'permission_callback' => array( $this, 'check_permissions' ),
					'args'                => rest_get_endpoint_args_for_schema(
						$this->get_message_schema(),
						WP_REST_Server::CREATABLE
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'reset_messages' ),
					'permission_callback' => array( $this, 'check_permissions' ),
					'args'                => array(),
				),
				'schema' => array( $this, 'get_message_schema' ),
			)
		);
	}

	/**
	 * Checks the required permissions for the routes.
	 *
	 * @since 0.0.1
	 *
	 * @return bool|WP_Error Whether the user has the required permissions or a WP_Error object if not.
	 */
	public function check_permissions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_cannot_access_chatbot',
				esc_html__( 'Sorry, you are not allowed to access the chatbot.', 'ai-assistant' ),
				is_user_logged_in() ? 403 : 401
			);
		}
		return true;
	}

	/**
	 * Handles the given request to get messages and returns a response.
	 *
	 * @since 0.0.1
	 *
	 * @return WP_REST_Response WordPress REST response object.
	 */
	public function get_messages(): WP_REST_Response {
		$messages = $this->get_messages_history();

		return rest_ensure_response( $messages );
	}

	/**
	 * Handles the given request to send a message and returns a response.
	 *
	 * @since 0.0.1
	 *
	 * @param WP_REST_Request $request WordPress REST request object, including parameters.
	 * @return WP_REST_Response WordPress REST response object.
	 */
	public function send_message( WP_REST_Request $request ): WP_REST_Response {

		$messages = $this->get_messages_history();

		$message_schema = $this->get_message_schema();

		$new_message = array();
		foreach ( $message_schema['properties'] as $prop => $schema ) {
			if ( isset( $request[ $prop ] ) ) {
				$new_message[ $prop ] = $request[ $prop ];
			} elseif ( isset( $schema['default'] ) ) {
				$new_message[ $prop ] = $schema['default'];
			}
		}

		$messages[] = $new_message;

		// Only convert to message instances right before passing to the agent
		// to avoid any issues with tool_calls and function_call structures
		$message_instances = $this->prepare_message_instances( $messages );

		// Validate that we have a configured provider before proceeding
		$provider_id = $this->provider_manager->get_current_provider_id();

		if ( empty( $provider_id ) ) {
			$error_message = esc_html__( 'No AI provider is configured. Please configure a provider in the settings.', 'ai-assistant' );

			$result_message = array(
				'type'             => 'error',
				Message::KEY_ROLE  => MessageRoleEnum::model()->value,
				Message::KEY_PARTS => array(
					array(
						MessagePart::KEY_CHANNEL => MessagePartChannelEnum::content()->value,
						MessagePart::KEY_TYPE    => MessagePartTypeEnum::text()->value,
						MessagePart::KEY_TEXT    => $error_message,
					),
				),
			);

			$messages[] = $result_message;
			update_user_meta( get_current_user_id(), 'wp_ai_assistant_chatbot_messages', $messages );
			return rest_ensure_response( $result_message );
		}

		try {
			// Include our abilities
			$abilities = array(
				new \Ai_Assistant\Abilities\Get_Post_Ability(),
				new \Ai_Assistant\Abilities\Create_Post_Draft_Ability(),
				new \Ai_Assistant\Abilities\Publish_Post_Ability(),
				new \Ai_Assistant\Abilities\Search_Posts_Ability(),
				new \Ai_Assistant\Abilities\Generate_Post_Featured_Image_Ability(),
				new \Ai_Assistant\Abilities\Set_Permalink_Structure_Ability(),
				new \Ai_Assistant\Abilities\Install_Plugin_Ability(),
				new \Ai_Assistant\Abilities\Activate_Plugin_Ability(),
				new \Ai_Assistant\Abilities\Get_Active_Plugins_Ability(),
			);

			$agent = new Chatbot_Agent( $this->provider_manager, $abilities, $message_instances );
			do {
				$agent_result = $agent->step();
			} while ( ! $agent_result->finished() );

			$result_message = array_merge(
				array( 'type' => 'regular' ),
				$agent_result->last_message()->toArray()
			);
		} catch ( \InvalidArgumentException $e ) {
			// Special handling for no model found
			error_log( 'AI Assistant: Invalid argument in chatbot: ' . $e->getMessage() );

			$error_message  = esc_html__( 'Sorry, no AI model is available that supports your request. Please check your provider settings or try a simpler question.', 'ai-assistant' );
			$result_message = array(
				'type'             => 'error',
				Message::KEY_ROLE  => MessageRoleEnum::model()->value,
				Message::KEY_PARTS => array(
					array(
						MessagePart::KEY_CHANNEL => MessagePartChannelEnum::content()->value,
						MessagePart::KEY_TYPE    => MessagePartTypeEnum::text()->value,
						MessagePart::KEY_TEXT    => $error_message,
					),
				),
			);
		} catch ( Exception $e ) {
			error_log( 'AI Assistant: Error in chatbot: ' . $e->getMessage() );

			$error_message = sprintf(
				/* translators: %s: original error message */
				esc_html__( 'An error occurred while processing the request: %s', 'ai-assistant' ),
				esc_html( $e->getMessage() )
			);

			$result_message = array(
				'type'             => 'error',
				Message::KEY_ROLE  => MessageRoleEnum::model()->value,
				Message::KEY_PARTS => array(
					array(
						MessagePart::KEY_CHANNEL => MessagePartChannelEnum::content()->value,
						MessagePart::KEY_TYPE    => MessagePartTypeEnum::text()->value,
						MessagePart::KEY_TEXT    => $error_message,
					),
				),
			);
		}

		$messages[] = $result_message;

		update_user_meta( get_current_user_id(), 'wp_ai_assistant_chatbot_messages', $messages );

		return rest_ensure_response( $result_message );
	}

	/**
	 * Handles the request to reset the messages history.
	 *
	 * @since 0.0.1
	 *
	 * @return WP_REST_Response WordPress REST response object.
	 */
	public function reset_messages(): WP_REST_Response {
		delete_user_meta( get_current_user_id(), 'wp_ai_assistant_chatbot_messages' );

		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Gets the message schema for the REST API.
	 *
	 * @since 0.0.1
	 *
	 * @return array<string, mixed> The message schema.
	 */
	public function get_message_schema(): array {
			// Manually define the schema since Message::getSchema() does not exist
			$properties = array(
				'role'  => array(
					'description' => __( 'Role of the message sender.', 'ai-assistant' ),
					'type'        => 'string',
					'enum'        => array( 'user', 'assistant', 'system', 'model' ),
					'default'     => 'user',
				),
				'parts' => array(
					'description' => __( 'Message parts.', 'ai-assistant' ),
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'channel' => array(
								'type'        => 'string',
								'description' => __( 'Channel of the message part.', 'ai-assistant' ),
							),
							'type'    => array(
								'type'        => 'string',
								'description' => __( 'Type of the message part.', 'ai-assistant' ),
							),
							'text'    => array(
								'type'        => 'string',
								'description' => __( 'Text content of the message part.', 'ai-assistant' ),
							),
						),
						'required'   => array( 'channel', 'type', 'text' ),
					),
				),
			);

			// Additional property for UI error/regular type
			$properties['type'] = array(
				'description' => __( 'Type of the message.', 'ai-assistant' ),
				'type'        => 'string',
				'enum'        => array( 'regular', 'error' ),
				'default'     => 'regular',
			);

			return array(
				'$schema'              => 'http://json-schema.org/draft-04/schema#',
				'title'                => 'chatbot_message',
				'type'                 => 'object',
				'properties'           => $properties,
				'additionalProperties' => false,
			);
	}

	/**
	 * Gets the list of messages history for the current user.
	 *
	 * @since 0.0.1
	 *
	 * @return array<array<string, mixed>> The list of messages, conforming to the Message schema plus an additional
	 *                                    'type' property.
	 */
	protected function get_messages_history(): array {
		// Using get_user_meta directly with the correct key prefix
		$messages = get_user_meta( get_current_user_id(), 'wp_ai_assistant_chatbot_messages', true );
		if ( ! is_array( $messages ) ) {
			$messages = array();
		}

		return $messages;
	}

	/**
	 * Prepares the given list of messages history to be returned as Message instances.
	 *
	 * @since 0.0.1
	 *
	 * @param array<array<string, mixed>> $messages The list of messages to prepare.
	 * @return array<Message> The list of prepared Message instances.
	 */
	protected function prepare_message_instances( array $messages ): array {
		return array_map(
			static function ( $message ) {
				// Remove the 'type' property as it's not part of the Message schema
				unset( $message['type'] );
				return Message::fromArray( $message );
			},
			$messages
		);
	}
}
