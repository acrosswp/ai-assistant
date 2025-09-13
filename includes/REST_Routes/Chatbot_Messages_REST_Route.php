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

		// Generate a session id (could be improved to persist per user session)
		$session_id = isset( $_COOKIE['ai_assistant_session_id'] ) ? sanitize_text_field( $_COOKIE['ai_assistant_session_id'] ) : wp_generate_uuid4();

		// Count tokens before sending to provider
		$input_token_count = $this->count_tokens_in_messages( $message_instances );
		error_log( 'AI Assistant: Input tokens: ' . $input_token_count );

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

			// Check if the selected model supports function calling
			$selected_model       = get_option( 'ai_assistant_selected_model', '' );
			$current_provider     = $this->provider_manager->get_current_provider_id();
			$has_function_calling = true;

			if ( ! empty( $selected_model ) ) {
				// Create a temporary agent just to check model compatibility
				$temp_agent           = new Chatbot_Agent( $this->provider_manager, array(), array() );
				$has_function_calling = $temp_agent->check_model_supports_function_calling( $current_provider, $selected_model );
			}

			// If model doesn't support function calling, use an empty abilities array
			if ( ! $has_function_calling ) {
				error_log( "AI Assistant: Selected model $selected_model doesn't support function calling. Using basic mode." );
				$abilities = array();
			}

			$agent = new Chatbot_Agent( $this->provider_manager, $abilities, $message_instances );

			// Wrap the agent execution in a try/catch block to handle specific errors
			try {
				do {
					$agent_result = $agent->step();
				} while ( ! $agent_result->finished() );

				$result_message = array_merge(
					array( 'type' => 'regular' ),
					$agent_result->last_message()->toArray()
				);

				// Count tokens in response
				$output_token_count = $this->count_tokens_in_message( $agent_result->last_message() );

			} catch ( \InvalidArgumentException $inner_e ) {
				// Handle model compatibility issues
				if ( stripos( $inner_e->getMessage(), 'function calling' ) !== false ||
					stripos( $inner_e->getMessage(), 'does not support' ) !== false ) {

					// Create a simplified agent without abilities for fallback
					$fallback_agent = new Chatbot_Agent( $this->provider_manager, array(), $message_instances );

					// Log the fallback
					error_log( 'AI Assistant: Falling back to model without abilities due to compatibility issue: ' . $inner_e->getMessage() );

					// Add a note about limited functionality to the user
					$fallback_note = "\n\n(Note: Some advanced features are currently unavailable with the selected AI model.)";

					do {
						$agent_result = $fallback_agent->step();
					} while ( ! $agent_result->finished() );

					$last_message       = $agent_result->last_message();
					$last_message_array = $last_message->toArray();

					// Add the note to the message content
					foreach ( $last_message_array['parts'] as &$part ) {
						if ( isset( $part['text'] ) ) {
							$part['text'] .= $fallback_note;
						}
					}

					$result_message = array_merge(
						array( 'type' => 'regular' ),
						$last_message_array
					);

					// Count tokens in response
					$output_token_count = $this->count_tokens_in_message( $last_message );
				} else {
					// If it's another type of argument error, rethrow it
					throw $inner_e;
				}
			}

			// Count tokens in response
			$output_token_count = $this->count_tokens_in_message( $agent_result->last_message() );
			error_log( 'AI Assistant: Output tokens: ' . $output_token_count );

			// Log total usage
			$this->log_token_usage( $input_token_count, $output_token_count );

			$request_time     = current_time( 'mysql', 1 );
			$request_headers  = maybe_serialize( getallheaders() );
			$ip_address       = $_SERVER['REMOTE_ADDR'] ?? '';
			$user_agent       = $_SERVER['HTTP_USER_AGENT'] ?? '';
			$referer          = $_SERVER['HTTP_REFERER'] ?? '';
			$plugin_version   = defined( 'AI_ASSISTANT_VERSION' ) ? AI_ASSISTANT_VERSION : '';
			$wp_version       = get_bloginfo( 'version' );
			$site_url         = site_url();
			$status_code      = null;
			$error_message    = null;
			$response_headers = null;
			$response_time    = null;
			$latency_ms       = null;

			$response_time = current_time( 'mysql', 1 );
			$latency_ms    = round( ( strtotime( $response_time ) - strtotime( $request_time ) ) * 1000 );
			$status_code   = 200;
			// If you have access to LLM response headers, set $response_headers here

			$this->insert_chat_history(
				array(
					'user_id'          => get_current_user_id(),
					'session_id'       => $session_id,
					'text_sent'        => $this->extract_text_from_message( $new_message ),
					'tokens_sent'      => $input_token_count,
					'abilities_data'   => maybe_serialize( $abilities ),
					'tokens_received'  => $output_token_count,
					'response_text'    => $this->extract_text_from_message( $result_message ),
					'llm_provider'     => $this->provider_manager->get_current_provider_id(),
					'model'            => method_exists( $agent, 'get_model' ) ? $agent->get_model() : '',
					'request_data'     => maybe_serialize( $new_message ),
					'response_data'    => maybe_serialize( $result_message ),
					'request_headers'  => $request_headers,
					'response_headers' => $response_headers,
					'request_time'     => $request_time,
					'response_time'    => $response_time,
					'latency_ms'       => $latency_ms,
					'status_code'      => $status_code,
					'error_message'    => $error_message,
					'ip_address'       => $ip_address,
					'user_agent'       => $user_agent,
					'referer'          => $referer,
					'plugin_version'   => $plugin_version,
					'wp_version'       => $wp_version,
					'site_url'         => $site_url,
				)
			);

		} catch ( \InvalidArgumentException $e ) {
			// Special handling for no model found
			error_log( 'AI Assistant: Invalid argument in chatbot: ' . $e->getMessage() );

			$error_message = '';
			if ( stripos( $e->getMessage(), 'function calling' ) !== false ) {
				$error_message = esc_html__( 'The selected model does not support function calling features. Please choose a compatible model in the settings, or try a simpler question that doesn\'t require advanced features.', 'ai-assistant' );
			} else {
				$error_message = esc_html__( 'Sorry, no AI model is available that supports your request. Please check your provider settings or try a simpler question.', 'ai-assistant' );
			}

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
			$error_message  = $e->getMessage();
			$status_code    = 400;
			$response_time  = current_time( 'mysql', 1 );
			$latency_ms     = round( ( strtotime( $response_time ) - strtotime( $request_time ) ) * 1000 );
			$this->insert_chat_history(
				array(
					'user_id'          => get_current_user_id(),
					'session_id'       => $session_id,
					'text_sent'        => $this->extract_text_from_message( $new_message ),
					'tokens_sent'      => $input_token_count,
					'abilities_data'   => maybe_serialize( $abilities ?? array() ),
					'tokens_received'  => 0,
					'response_text'    => $error_message,
					'llm_provider'     => $this->provider_manager->get_current_provider_id(),
					'model'            => '',
					'request_data'     => maybe_serialize( $new_message ),
					'response_data'    => '',
					'request_headers'  => $request_headers,
					'response_headers' => $response_headers,
					'request_time'     => $request_time,
					'response_time'    => $response_time,
					'latency_ms'       => $latency_ms,
					'status_code'      => $status_code,
					'error_message'    => $error_message,
					'ip_address'       => $ip_address,
					'user_agent'       => $user_agent,
					'referer'          => $referer,
					'plugin_version'   => $plugin_version,
					'wp_version'       => $wp_version,
					'site_url'         => $site_url,
				)
			);
		} catch ( Exception $e ) {
			error_log( 'AI Assistant: Error in chatbot: ' . $e->getMessage() );

			// Check for empty tool_calls error
			$error_message = $e->getMessage();
			if ( strpos( $error_message, 'Invalid \'messages' ) !== false &&
				strpos( $error_message, 'tool_calls' ) !== false &&
				strpos( $error_message, 'empty array' ) !== false ) {

				$error_message = esc_html__( 'The AI model attempted to use a tool but encountered an error. Please try rephrasing your request or ask a different question.', 'ai-assistant' );
			} else {
				$error_message = sprintf(
					/* translators: %s: original error message */
					esc_html__( 'An error occurred while processing the request: %s', 'ai-assistant' ),
					esc_html( $e->getMessage() )
				);
			}

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
			$error_message  = $e->getMessage();
			$status_code    = 500;
			$response_time  = current_time( 'mysql', 1 );
			$latency_ms     = round( ( strtotime( $response_time ) - strtotime( $request_time ) ) * 1000 );
			$this->insert_chat_history(
				array(
					'user_id'          => get_current_user_id(),
					'session_id'       => $session_id,
					'text_sent'        => $this->extract_text_from_message( $new_message ),
					'tokens_sent'      => $input_token_count,
					'abilities_data'   => maybe_serialize( $abilities ?? array() ),
					'tokens_received'  => 0,
					'response_text'    => $error_message,
					'llm_provider'     => $this->provider_manager->get_current_provider_id(),
					'model'            => '',
					'request_data'     => maybe_serialize( $new_message ),
					'response_data'    => '',
					'request_headers'  => $request_headers,
					'response_headers' => $response_headers,
					'request_time'     => $request_time,
					'response_time'    => $response_time,
					'latency_ms'       => $latency_ms,
					'status_code'      => $status_code,
					'error_message'    => $error_message,
					'ip_address'       => $ip_address,
					'user_agent'       => $user_agent,
					'referer'          => $referer,
					'plugin_version'   => $plugin_version,
					'wp_version'       => $wp_version,
					'site_url'         => $site_url,
				)
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

	/**
	 * Counts tokens in an array of Message instances.
	 *
	 * @since 0.0.1
	 *
	 * @param array<Message> $messages Array of Message instances.
	 * @return int Estimated token count.
	 */
	private function count_tokens_in_messages( array $messages ): int {
		$total_tokens = 0;
		foreach ( $messages as $message ) {
			$total_tokens += $this->count_tokens_in_message( $message );
		}
		return $total_tokens;
	}

	/**
	 * Counts tokens in a single Message instance.
	 *
	 * @since 0.0.1
	 *
	 * @param Message $message Message instance.
	 * @return int Estimated token count.
	 */
	private function count_tokens_in_message( Message $message ): int {
		$text_content = '';
		$parts        = $message->getParts();

		foreach ( $parts as $part ) {
			if ( $part->getType()->value === MessagePartTypeEnum::text()->value ) {
				$text_content .= $part->getText() . ' ';
			}
		}

		// Simple estimation: ~4 characters per token for English text
		// For more accuracy, use tiktoken library or OpenAI's tokenizer API
		return (int) ceil( strlen( $text_content ) / 4 );
	}

	/**
	 * Logs token usage for monitoring and billing purposes.
	 *
	 * @since 0.0.1
	 *
	 * @param int $input_tokens Number of input tokens.
	 * @param int $output_tokens Number of output tokens.
	 */
	private function log_token_usage( int $input_tokens, int $output_tokens ): void {
		$user_id      = get_current_user_id();
		$total_tokens = $input_tokens + $output_tokens;

		// Update user's token usage stats
		$current_usage = (int) get_user_meta( $user_id, 'ai_assistant_token_usage', true );
		update_user_meta( $user_id, 'ai_assistant_token_usage', $current_usage + $total_tokens );

		// Log for admin monitoring
		error_log(
			sprintf(
				'AI Assistant Token Usage - User: %d, Input: %d, Output: %d, Total: %d',
				$user_id,
				$input_tokens,
				$output_tokens,
				$total_tokens
			)
		);
	}

	/**
	 * Inserts a chat record into the custom chat history table.
	 */
	private function insert_chat_history( array $args ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ai_assistant_chat_history';

		$wpdb->insert(
			$table_name,
			array(
				'user_id'          => $args['user_id'],
				'session_id'       => $args['session_id'],
				'text_sent'        => $args['text_sent'],
				'tokens_sent'      => $args['tokens_sent'],
				'abilities_data'   => $args['abilities_data'],
				'tokens_received'  => $args['tokens_received'],
				'response_text'    => $args['response_text'],
				'llm_provider'     => $args['llm_provider'],
				'model'            => $args['model'],
				'request_data'     => $args['request_data'],
				'response_data'    => $args['response_data'],
				'request_headers'  => $args['request_headers'],
				'response_headers' => $args['response_headers'],
				'request_time'     => $args['request_time'],
				'response_time'    => $args['response_time'],
				'latency_ms'       => $args['latency_ms'],
				'status_code'      => $args['status_code'],
				'error_message'    => $args['error_message'],
				'ip_address'       => $args['ip_address'],
				'user_agent'       => $args['user_agent'],
				'referer'          => $args['referer'],
				'plugin_version'   => $args['plugin_version'],
				'wp_version'       => $args['wp_version'],
				'site_url'         => $args['site_url'],
			),
			array(
				'%d',
				'%s',
				'%s',
				'%d',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);
	}

	/**
	 * Extracts concatenated text from a message array.
	 */
	private function extract_text_from_message( $message ) {
		if ( is_array( $message ) && isset( $message['parts'] ) && is_array( $message['parts'] ) ) {
			$text = '';
			foreach ( $message['parts'] as $part ) {
				if ( isset( $part['text'] ) ) {
					$text .= $part['text'] . ' ';
				}
			}
			return trim( $text );
		}
		return '';
	}
}

// Register table creation on plugin activation
register_activation_hook( __FILE__, array( '\Ai_Assistant\REST_Routes\Chatbot_Messages_REST_Route', 'create_chat_history_table' ) );
