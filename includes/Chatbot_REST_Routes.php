<?php
namespace Ai_Assistant\Includes;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Chatbot REST API Routes
 *
 * @since 0.0.1
 * @package Ai_Assistant
 * @subpackage Ai_Assistant/includes
 */
class Chatbot_REST_Routes {

	/**
	 * AI Client Manager instance
	 *
	 * @var AI_Client_Manager
	 */
	private $ai_client_manager;

	/**
	 * Chatbot Agent instance
	 *
	 * @var Chatbot_Agent
	 */
	private $chatbot_agent;

	/**
	 * REST namespace
	 *
	 * @var string
	 */
	private $namespace = 'ai-assistant/v1';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->ai_client_manager = AI_Client_Manager::instance();
		$this->chatbot_agent     = new Chatbot_Agent( $this->ai_client_manager );
	}

	/**
	 * Register REST routes
	 */
	public function register_routes() {
		// Early bailout if REST API is not available
		if ( ! function_exists( 'register_rest_route' ) ) {
			return;
		}

		// Messages endpoint
		register_rest_route(
			$this->namespace,
			'/messages',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_messages' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'send_message' ),
					'permission_callback' => array( $this, 'check_permissions' ),
					'args'                => array(
						'message' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_textarea_field',
							'validate_callback' => array( $this, 'validate_message' ),
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'reset_messages' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);
	}

	/**
	 * Check permissions for chatbot access
	 *
	 * @return bool|\WP_Error
	 */
	public function check_permissions() {
		// Early bailout if user is not logged in
		if ( ! is_user_logged_in() ) {
			return new \WP_Error(
				'rest_not_logged_in',
				__( 'You are not currently logged in.', 'ai-assistant' ),
				array( 'status' => 401 )
			);
		}

		// Check if user has permission to access chatbot
		if ( ! current_user_can( 'ai_assistant_access_chatbot' ) ) {
			return new \WP_Error(
				'rest_cannot_access_chatbot',
				__( 'Sorry, you are not allowed to access the chatbot.', 'ai-assistant' ),
				array( 'status' => 403 )
			);
		}

		// Check if AI features are enabled
		if ( ! $this->ai_client_manager->can_use_ai() ) {
			return new \WP_Error(
				'ai_features_disabled',
				__( 'AI features are currently disabled or not properly configured.', 'ai-assistant' ),
				array( 'status' => 503 )
			);
		}

		return true;
	}

	/**
	 * Validate message parameter
	 *
	 * @param string $value
	 * @param \WP_REST_Request $request
	 * @param string $param
	 * @return bool
	 */
	public function validate_message( $value, $request, $param ) {
		if ( empty( trim( $value ) ) ) {
			return false;
		}

		// Check message length
		$max_length = apply_filters( 'ai_assistant_max_message_length', 2000 );
		if ( strlen( $value ) > $max_length ) {
			return false;
		}

		return true;
	}

	/**
	 * Get messages history
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_messages( $request ) {
		$messages = $this->get_messages_history();
		return rest_ensure_response( $messages );
	}

	/**
	 * Send a message to the chatbot
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function send_message( $request ) {
		$user_message = $request->get_param( 'message' );
		$messages     = $this->get_messages_history();

		// Add user message to history
		$user_message_data = array(
			'role'      => 'user',
			'content'   => $user_message,
			'timestamp' => current_time( 'timestamp' ),
			'type'      => 'regular',
		);

		$messages[] = $user_message_data;

		// Get conversation history for context (last 10 messages)
		$conversation_history = array_slice( $messages, -10 );
		$conversation_context = array();

		foreach ( $conversation_history as $msg ) {
			if ( isset( $msg['role'] ) && isset( $msg['content'] ) ) {
				$conversation_context[] = array(
					'role'    => $msg['role'],
					'content' => $msg['content'],
				);
			}
		}

		// Process message with chatbot agent
		$response = $this->chatbot_agent->process_message( $user_message, $conversation_context );

		if ( is_wp_error( $response ) ) {
			// Create error response message
			$response_message = array(
				'role'      => 'assistant',
				'content'   => $response->get_error_message(),
				'timestamp' => current_time( 'timestamp' ),
				'type'      => 'error',
			);
		} else {
			// Create successful response message
			$response_message = array(
				'role'      => 'assistant',
				'content'   => $response['content'],
				'timestamp' => current_time( 'timestamp' ),
				'type'      => isset( $response['type'] ) ? $response['type'] : 'regular',
			);
		}

		// Add response to messages
		$messages[] = $response_message;

		// Save updated messages
		$this->save_messages_history( $messages );

		return rest_ensure_response( $response_message );
	}

	/**
	 * Reset messages history
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function reset_messages( $request ) {
		$old_messages = $this->get_messages_history();
		$this->clear_messages_history();

		return rest_ensure_response( $old_messages );
	}

	/**
	 * Get messages history for current user
	 *
	 * @return array
	 */
	private function get_messages_history() {
		$user_id  = get_current_user_id();
		$messages = get_user_meta( $user_id, 'ai_assistant_chatbot_messages', true );

		if ( ! is_array( $messages ) ) {
			$messages = array();
		}

		return $messages;
	}

	/**
	 * Save messages history for current user
	 *
	 * @param array $messages
	 */
	private function save_messages_history( $messages ) {
		$user_id = get_current_user_id();

		// Limit messages history (keep last 50 messages)
		$max_messages = apply_filters( 'ai_assistant_max_stored_messages', 50 );
		if ( count( $messages ) > $max_messages ) {
			$messages = array_slice( $messages, -$max_messages );
		}

		update_user_meta( $user_id, 'ai_assistant_chatbot_messages', $messages );
	}

	/**
	 * Clear messages history for current user
	 */
	private function clear_messages_history() {
		$user_id = get_current_user_id();
		delete_user_meta( $user_id, 'ai_assistant_chatbot_messages' );
	}
}
