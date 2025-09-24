<?php
namespace Ai_Assistant\Includes;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Chatbot Agent - Handles AI conversation logic
 *
 * @since 0.0.1
 * @package Ai_Assistant
 * @subpackage Ai_Assistant/includes
 */
class Chatbot_Agent {

	/**
	 * AI Client Manager instance
	 *
	 * @var AI_Client_Manager
	 */
	private $ai_client_manager;

	/**
	 * Constructor
	 *
	 * @param AI_Client_Manager $ai_client_manager
	 */
	public function __construct( $ai_client_manager ) {
		$this->ai_client_manager = $ai_client_manager;
	}

	/**
	 * Process a user message and return AI response
	 *
	 * @param string $user_message
	 * @param array  $conversation_history
	 * @return array|WP_Error
	 */
	public function process_message( $user_message, $conversation_history = array() ) {
		// Early bailout checks
		if ( ! $this->ai_client_manager->can_use_ai() ) {
			return new \WP_Error(
				'ai_disabled',
				__( 'AI features are currently disabled.', 'ai-assistant' )
			);
		}

		if ( empty( $user_message ) ) {
			return new \WP_Error(
				'empty_message',
				__( 'Message cannot be empty.', 'ai-assistant' )
			);
		}

		// Get AI client
		$ai_client = $this->ai_client_manager->get_ai_client();
		if ( ! $ai_client ) {
			return new \WP_Error(
				'ai_client_unavailable',
				__( 'AI client is not available. Please check your configuration.', 'ai-assistant' )
			);
		}

		try {
			// Build the conversation context
			$system_instruction = $this->get_system_instruction();
			$messages           = $this->build_message_history( $user_message, $conversation_history );

			// Handle different client types
			if ( $ai_client instanceof Simple_AI_Client ) {
				return $this->process_with_simple_client( $ai_client, $messages, $system_instruction );
			} else {
				return $this->process_with_full_client( $ai_client, $messages, $system_instruction );
			}
		} catch ( \Exception $e ) {
			error_log( 'AI Assistant: Error generating response: ' . $e->getMessage() );

			return new \WP_Error(
				'ai_error',
				sprintf(
					/* translators: %s: error message */
					__( 'An error occurred while processing your request: %s', 'ai-assistant' ),
					$e->getMessage()
				)
			);
		}
	}

	/**
	 * Process message with Simple AI Client (fallback)
	 *
	 * @param Simple_AI_Client $client
	 * @param array            $messages
	 * @param string           $system_instruction
	 * @return array|WP_Error
	 */
	private function process_with_simple_client( $client, $messages, $system_instruction ) {
		$provider_id = $this->ai_client_manager->get_current_provider_id();
		$model_id    = $this->ai_client_manager->get_preferred_model_id( $provider_id );
		$api_key     = $this->ai_client_manager->get_provider_api_key( $provider_id );

		if ( empty( $api_key ) ) {
			return new \WP_Error(
				'api_key_missing',
				__( 'API key not configured for the selected provider.', 'ai-assistant' )
			);
		}

		$response = $client->generate_response(
			$provider_id,
			$model_id,
			$api_key,
			$messages,
			$system_instruction
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$ai_content = $client->extract_content( $provider_id, $response );

		if ( empty( $ai_content ) ) {
			return new \WP_Error(
				'empty_response',
				__( 'Received empty response from AI service.', 'ai-assistant' )
			);
		}

		return array(
			'role'    => 'assistant',
			'content' => $ai_content,
			'type'    => 'regular',
		);
	}

	/**
	 * Process message with full AI Client SDK
	 *
	 * @param mixed  $ai_client
	 * @param array  $messages
	 * @param string $system_instruction
	 * @return array|WP_Error
	 */
	private function process_with_full_client( $ai_client, $messages, $system_instruction ) {
		// Get provider model
		$provider_model = $this->ai_client_manager->get_provider_model();
		if ( ! $provider_model ) {
			return new \WP_Error(
				'provider_model_unavailable',
				__( 'AI provider model is not available.', 'ai-assistant' )
			);
		}

		// Create prompt builder
		if ( class_exists( '\WordPress\AiClient\Builders\PromptBuilder' ) ) {
			$prompt_builder = new \WordPress\AiClient\Builders\PromptBuilder();

			// Add system instruction if available
			if ( method_exists( $prompt_builder, 'usingSystemInstruction' ) ) {
				$prompt_builder->usingSystemInstruction( $system_instruction );
			}

			// Add messages
			foreach ( $messages as $message ) {
				if ( method_exists( $prompt_builder, 'withMessage' ) ) {
					$prompt_builder->withMessage( $message );
				}
			}

			// Use the provider model
			if ( method_exists( $prompt_builder, 'usingModel' ) ) {
				$prompt_builder->usingModel( $provider_model );
			}

			// Generate response
			if ( method_exists( $prompt_builder, 'generateTextResult' ) ) {
				$result = $prompt_builder->generateTextResult();

				if ( method_exists( $result, 'toMessage' ) ) {
					$response_message = $result->toMessage();
					return $this->format_response( $response_message );
				}
			}
		}

		return new \WP_Error(
			'ai_generation_failed',
			__( 'Failed to generate AI response. Please try again.', 'ai-assistant' )
		);
	}

	/**
	 * Get system instruction for the chatbot
	 *
	 * @return string
	 */
	private function get_system_instruction() {
		$instruction = "You are a helpful WordPress assistant chatbot. You are here to help users with their WordPress questions and provide information about WordPress-related topics.

## Guidelines:
- Be helpful, accurate, and concise
- Focus on WordPress-related topics
- If you don't know something, say so honestly
- Provide practical, actionable advice when possible
- Be friendly and professional

## WordPress Context:
- The user is working with a WordPress site
- You can provide information about plugins, themes, customization, and best practices
- Always prioritize security and performance in your recommendations

How can I help you with your WordPress site today?";

		return apply_filters( 'ai_assistant_system_instruction', $instruction );
	}

	/**
	 * Build message history for the AI client
	 *
	 * @param string $user_message
	 * @param array  $conversation_history
	 * @return array
	 */
	private function build_message_history( $user_message, $conversation_history ) {
		$messages = array();

		// Add conversation history
		foreach ( $conversation_history as $message ) {
			if ( isset( $message['role'] ) && isset( $message['content'] ) ) {
				$messages[] = $this->create_message( $message['role'], $message['content'] );
			}
		}

		// Add current user message
		$messages[] = $this->create_message( 'user', $user_message );

		return $messages;
	}

	/**
	 * Create a message object
	 *
	 * @param string $role
	 * @param string $content
	 * @return array
	 */
	private function create_message( $role, $content ) {
		return array(
			'role'    => $role,
			'content' => $content,
		);
	}

	/**
	 * Format the AI response
	 *
	 * @param mixed $response_message
	 * @return array
	 */
	private function format_response( $response_message ) {
		$response = array(
			'role'    => 'assistant',
			'content' => '',
			'type'    => 'regular',
		);

		// Try to extract content from the response message
		if ( is_object( $response_message ) ) {
			if ( method_exists( $response_message, 'getContent' ) ) {
				$response['content'] = $response_message->getContent();
			} elseif ( method_exists( $response_message, 'toArray' ) ) {
				$message_array = $response_message->toArray();
				if ( isset( $message_array['content'] ) ) {
					$response['content'] = $message_array['content'];
				} elseif ( isset( $message_array['parts'] ) && is_array( $message_array['parts'] ) ) {
					// Extract text from parts
					$content_parts = array();
					foreach ( $message_array['parts'] as $part ) {
						if ( isset( $part['text'] ) ) {
							$content_parts[] = $part['text'];
						}
					}
					$response['content'] = implode( "\n", $content_parts );
				}
			}
		} elseif ( is_string( $response_message ) ) {
			$response['content'] = $response_message;
		}

		// Fallback if no content found
		if ( empty( $response['content'] ) ) {
			$response['content'] = __( 'I apologize, but I was unable to generate a proper response. Please try again.', 'ai-assistant' );
			$response['type']    = 'error';
		}

		return $response;
	}
}
