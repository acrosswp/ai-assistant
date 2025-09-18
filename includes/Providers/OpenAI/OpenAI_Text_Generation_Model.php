<?php
/**
 * Custom OpenAI Text Generation Model that fixes empty tool_calls array issue
 *
 * @since 0.0.1
 * @package ai-assistant
 */

namespace Ai_Assistant\Providers\OpenAI;

use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\ProviderImplementations\OpenAi\OpenAiTextGenerationModel;

defined( 'ABSPATH' ) || exit;

/**
 * Custom OpenAI Text Generation Model that fixes the empty tool_calls array issue.
 *
 * The base SDK includes an empty 'tool_calls' array in messages even when there are no function calls.
 * OpenAI API requires this field to either be missing entirely or contain at least one tool call.
 * This custom implementation conditionally includes the tool_calls field only when there are actual tool calls.
 *
 * @since 0.0.1
 */
class OpenAI_Text_Generation_Model extends OpenAiTextGenerationModel {

	/**
	 * Override the prepareMessagesParam method to fix the empty tool_calls issue.
	 *
	 * @since 0.0.1
	 *
	 * @param list<Message> $messages The messages to prepare.
	 * @param string|null   $system_instruction An optional system instruction to prepend to the messages.
	 * @return list<array<string, mixed>> The prepared messages parameter.
	 */
	protected function prepareMessagesParam( array $messages, ?string $system_instruction = null ): array {
		$messages_param = array_map(
			function ( Message $message ): array {
				// Special case: Function response.
				$message_parts = $message->getParts();
				if ( count( $message_parts ) === 1 && $message_parts[0]->getType()->isFunctionResponse() ) {
					$function_response = $message_parts[0]->getFunctionResponse();
					if ( ! $function_response ) {
						throw new \RuntimeException(
							'The function response typed message part must contain a function response.'
						);
					}
					return array(
						'role'         => 'tool',
						'content'      => json_encode( $function_response->getResponse() ),
						'tool_call_id' => $function_response->getId(),
					);
				}

				// Build the base message
				$message_data = array(
					'role'    => $this->getMessageRoleString( $message->getRole() ),
					'content' => array_values(
						array_filter(
							array_map(
								array( $this, 'getMessagePartContentData' ),
								$message_parts
							)
						)
					),
				);

				// Only include tool_calls if there are actual tool calls
				$tool_calls = array_values(
					array_filter(
						array_map(
							array( $this, 'getMessagePartToolCallData' ),
							$message_parts
						)
					)
				);

				if ( ! empty( $tool_calls ) ) {
					$message_data['tool_calls'] = $tool_calls;
					error_log( '[AI Assistant Custom Model] Including tool_calls with ' . count( $tool_calls ) . ' calls' );
				} else {
					error_log( '[AI Assistant Custom Model] Skipping empty tool_calls array (fix applied)' );
				}

				return $message_data;
			},
			$messages
		);

		if ( $system_instruction ) {
			array_unshift(
				$messages_param,
				array(
					'role'    => 'system',
					'content' => array(
						array(
							'type' => 'text',
							'text' => $system_instruction,
						),
					),
				)
			);
		}

		return $messages_param;
	}
}
