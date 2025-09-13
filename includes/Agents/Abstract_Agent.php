<?php
/**
 * Class Ai_Assistant\Agents\Abstract_Agent
 *
 * @since 0.0.1
 * @package ai-assistant
 */

namespace Ai_Assistant\Agents;

use Ai_Assistant\Agents\Contracts\Agent;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Builders\PromptBuilder;
use WordPress\AiClient\Messages\DTO\Message;
use WordPress\AiClient\Messages\DTO\MessagePart;
use WordPress\AiClient\Messages\Enums\MessagePartChannelEnum;
use WordPress\AiClient\Messages\Enums\MessagePartTypeEnum;
use WordPress\AiClient\Messages\Enums\MessageRoleEnum;
use WordPress\AiClient\Tools\DTO\FunctionCall;
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;
use WordPress\AiClient\Tools\DTO\FunctionResponse;
use RuntimeException;
use WP_Ability;
use Exception;

/**
 * Base class for an agent.
 *
 * @since 0.0.1
 */
abstract class Abstract_Agent implements Agent {

	/**
	 * The allowed abilities for the agent, keyed by their sanitized name.
	 *
	 * @since 0.0.1
	 * @var array<WP_Ability>
	 */
	private array $abilities_map;

	/**
	 * The trajectory of messages exchanged with the agent.
	 *
	 * @since 0.0.1
	 * @var array<Message>
	 */
	private array $trajectory;

	/**
	 * The current step index.
	 *
	 * @since 0.0.1
	 * @var int
	 */
	private int $current_step_index = 0;

	/**
	 * Additional options for the agent.
	 *
	 * @since 0.0.1
	 * @var array<string, mixed>
	 */
	private array $options;

	/**
	 * Constructor.
	 *
	 * @since 0.0.1
	 *
	 * @param array<WP_Ability>    $abilities  The abilities available to the agent.
	 * @param array<Message>       $trajectory The initial trajectory of messages.
	 * @param array<string, mixed> $options    Additional options for the agent.
	 */
	public function __construct( array $abilities, array $trajectory, array $options = array() ) {
		$this->abilities_map = array();
		foreach ( $abilities as $ability ) {
			$this->abilities_map[ $this->sanitize_function_name( $ability->get_name() ) ] = $ability;
		}

		$this->trajectory = $trajectory;

		$this->options = wp_parse_args(
			$options,
			array(
				'max_step_retries' => 3,
			)
		);
	}

	/**
	 * Executes a single step of the agent's execution.
	 *
	 * @since 0.0.1
	 *
	 * @return Agent_Step_Result The result of the step execution.
	 *
	 * @throws RuntimeException If the invalid function calls message is not a user message.
	 */
	final public function step(): Agent_Step_Result {
		$success      = false;
		$retries      = 0;
		$new_messages = array();

		/*
		 * Call the LLM, either to respond to the user query or to trigger function calls.
		 * In case any invalid function calls are returned, retry until a valid response is received or the maximum
		 * number of retries is reached.
		 */
		do {
			++$retries;

			$prompt_builder = AiClient::prompt( $this->trajectory + $new_messages )
				->usingFunctionDeclarations( ...$this->get_function_declarations() );

			$result_message = $this->prompt_llm( $prompt_builder );

			list( $function_call_tools, $invalid_function_call_names ) = $this->extract_function_call_abilities( $result_message );

			$new_messages[] = $result_message;

			if ( count( $invalid_function_call_names ) > 0 ) {
				$invalid_function_calls_message = $this->get_invalid_function_calls_message( $invalid_function_call_names );
				if ( ! $invalid_function_calls_message->getRole()->isUser() ) {
					throw new RuntimeException(
						'Invalid function calls message must be a user message.'
					);
				}
				$new_messages[] = $invalid_function_calls_message;
			} else {
				$success = true;
			}
		} while ( ! $success && $retries < $this->options['max_step_retries'] );

		if ( ! $success ) {
			throw new RuntimeException(
				sprintf(
					'Agent failed to execute step after %d retries.',
					$this->options['max_step_retries']
				)
			);
		}

		// Execute any function calls found in the result message.
		foreach ( $function_call_tools as $function_call ) {
			$function_response_message = $this->execute_function_call( $function_call );
			if ( $function_response_message ) {
				$new_messages[] = $function_response_message;
			}
		}

		$finished = $this->is_finished( $new_messages );

		return $this->complete_step_and_get_result( $finished, $new_messages );
	}

	/**
	 * Prompts the LLM with the given prompt builder.
	 *
	 * @since 0.0.1
	 *
	 * @param PromptBuilder $prompt The prompt builder instance including the trajectory and function declarations.
	 * @return Message The result message from the LLM.
	 */
	abstract protected function prompt_llm( PromptBuilder $prompt ): Message;

	/**
	 * Checks whether the agent has finished its execution based on the new messages added to the agent's trajectory.
	 *
	 * @since 0.0.1
	 *
	 * @param array<Message> $new_messages The new messages appended to the agent's trajectory during the step.
	 * @return bool True if the agent has finished, false otherwise.
	 */
	abstract protected function is_finished( array $new_messages ): bool;

	/**
	 * Completes the step and returns the result.
	 *
	 * @since 0.0.1
	 *
	 * @param bool           $finished     Whether the agent has finished execution.
	 * @param array<Message> $new_messages The new messages generated during this step.
	 * @return Agent_Step_Result The result of the step execution.
	 */
	private function complete_step_and_get_result( bool $finished, array $new_messages ): Agent_Step_Result {
		// Append the new messages to the trajectory, so that they are available for the next step.
		foreach ( $new_messages as $new_message ) {
			$this->trajectory[] = $new_message;
		}

		return new Agent_Step_Result(
			$this->current_step_index++,
			$finished,
			$new_messages
		);
	}

	/**
	 * Finds an ability by its name.
	 *
	 * @since 0.0.1
	 *
	 * @param string $name The name of the ability to find.
	 * @return WP_Ability|null The ability if found, null otherwise.
	 */
	private function find_ability_by_name( string $name ): ?WP_Ability {
		return $this->abilities_map[ $name ] ?? null;
	}

	/**
	 * Gets the function declarations for the abilities available to the agent.
	 *
	 * @since 0.0.1
	 *
	 * @return array<FunctionDeclaration> The function declarations.
	 */
	public function get_function_declarations(): array {
		$function_declarations = array();
		foreach ( $this->abilities_map as $ability ) {
			$function_declarations[] = $ability->get_function_declaration();
		}
		return $function_declarations;
	}

	/**
	 * Extracts function call abilities from a message.
	 *
	 * @since 0.0.1
	 *
	 * @param Message $message The message to extract function calls from.
	 * @return array{0: array<FunctionCall>, 1: array<string>} Array containing function calls and invalid function call names.
	 */
	private function extract_function_call_abilities( Message $message ): array {
		$function_calls              = array();
		$invalid_function_call_names = array();

		// Check if the message has tool_calls
		$has_function_calls = false;
		foreach ( $message->getParts() as $part ) {
			if ( $part->getType()->isFunctionCall() ) {
				$has_function_calls = true;
				break;
			}
		}

		// If the message has no function calls, early return to avoid empty tool_calls array
		if ( ! $has_function_calls ) {
			return array( $function_calls, $invalid_function_call_names );
		}

		foreach ( $message->getParts() as $part ) {
			if ( ! $part->getType()->isFunctionCall() ) {
				continue;
			}

			$function_call = $part->getFunctionCall();
			if ( ! $function_call ) {
				continue;
			}

			$sanitized_name = $this->sanitize_function_name( $function_call->getName() );
			if ( $this->find_ability_by_name( $sanitized_name ) ) {
				$function_calls[] = $function_call;
			} else {
				$invalid_function_call_names[] = $function_call->getName();
			}
		}

		return array( $function_calls, $invalid_function_call_names );
	}

	/**
	 * Executes a function call and returns the response message.
	 *
	 * @since 0.0.1
	 *
	 * @param FunctionCall $function_call The function call to execute.
	 * @return Message|null The function response message, or null if execution failed.
	 */
	private function execute_function_call( FunctionCall $function_call ): ?Message {
		$sanitized_name = $this->sanitize_function_name( $function_call->getName() );
		$ability        = $this->find_ability_by_name( $sanitized_name );

		if ( ! $ability ) {
			return null;
		}

		try {
			$response = $ability->execute( $function_call->getArgs() );

			// Workaround for SDK compatibility
			// Create a message directly rather than using FunctionResponse object
			$response_data = json_encode(
				array(
					'name'     => $function_call->getName(),
					'response' => $response,
				)
			);

			return new Message(
				MessageRoleEnum::user(),
				array(
					new MessagePart(
						MessagePartChannelEnum::content(),
						MessagePartTypeEnum::text(),
						$response_data
					),
				)
			);
		} catch ( Exception $e ) {
			// Log error and continue
			error_log( 'Function call execution failed: ' . $e->getMessage() );
			return null;
		}
	}

	/**
	 * Gets an invalid function calls message.
	 *
	 * @since 0.0.1
	 *
	 * @param array<string> $invalid_function_call_names The invalid function call names.
	 * @return Message The invalid function calls message.
	 */
	private function get_invalid_function_calls_message( array $invalid_function_call_names ): Message {
		$error_text = sprintf(
			'The following function calls are not available: %s. Please use only the functions that are available to you.',
			implode( ', ', $invalid_function_call_names )
		);

		// Create the message part based on constructor signature
		try {
			$message_part = new MessagePart(
				MessagePartChannelEnum::content(),
				MessagePartTypeEnum::text(),
				$error_text,
				null,
				null,
				null
			);
		} catch ( Exception $e ) {
			// Try alternate constructor signature
			$message_part = new MessagePart(
				MessagePartChannelEnum::content(),
				MessagePartTypeEnum::text(),
				$error_text
			);
		}

		return new Message(
			MessageRoleEnum::user(),
			array( $message_part )
		);
	}

	/**
	 * Sanitizes a function name to be used as a key.
	 *
	 * @since 0.0.1
	 *
	 * @param string $name The function name to sanitize.
	 * @return string The sanitized function name.
	 */
	private function sanitize_function_name( string $name ): string {
		return str_replace( array( '-', '/' ), '_', $name );
	}
}
