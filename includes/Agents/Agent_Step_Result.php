<?php
/**
 * Class Ai_Assistant\Agents\Agent_Step_Result
 *
 * @since 0.0.1
 * @package ai-assistant
 */

namespace Ai_Assistant\Agents;

use WordPress\AiClient\Messages\DTO\Message;

/**
 * Result of a single agent step execution.
 *
 * @since 0.0.1
 */
class Agent_Step_Result {

	/**
	 * The step index.
	 *
	 * @since 0.0.1
	 * @var int
	 */
	private int $step_index;

	/**
	 * Whether the agent has finished execution.
	 *
	 * @since 0.0.1
	 * @var bool
	 */
	private bool $finished;

	/**
	 * The new messages generated during this step.
	 *
	 * @since 0.0.1
	 * @var array<Message>
	 */
	private array $new_messages;

	/**
	 * Constructor.
	 *
	 * @since 0.0.1
	 *
	 * @param int            $step_index    The step index.
	 * @param bool           $finished      Whether the agent has finished execution.
	 * @param array<Message> $new_messages  The new messages generated during this step.
	 */
	public function __construct( int $step_index, bool $finished, array $new_messages ) {
		$this->step_index   = $step_index;
		$this->finished     = $finished;
		$this->new_messages = $new_messages;
	}

	/**
	 * Gets the step index.
	 *
	 * @since 0.0.1
	 *
	 * @return int The step index.
	 */
	public function get_step_index(): int {
		return $this->step_index;
	}

	/**
	 * Checks whether the agent has finished execution.
	 *
	 * @since 0.0.1
	 *
	 * @return bool True if the agent has finished, false otherwise.
	 */
	public function finished(): bool {
		return $this->finished;
	}

	/**
	 * Gets the new messages generated during this step.
	 *
	 * @since 0.0.1
	 *
	 * @return array<Message> The new messages.
	 */
	public function get_new_messages(): array {
		return $this->new_messages;
	}

	/**
	 * Gets the last message from the new messages.
	 *
	 * @since 0.0.1
	 *
	 * @return Message|null The last message, or null if no messages.
	 */
	public function last_message(): ?Message {
		if ( empty( $this->new_messages ) ) {
			return null;
		}
		return end( $this->new_messages );
	}
}
