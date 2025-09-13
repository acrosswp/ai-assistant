<?php
/**
 * Interface Ai_Assistant\Agents\Contracts\Agent
 *
 * @since 0.0.1
 * @package ai-assistant
 */

namespace Ai_Assistant\Agents\Contracts;

use Ai_Assistant\Agents\Agent_Step_Result;

/**
 * Interface for an agent.
 *
 * @since 0.0.1
 */
interface Agent {

	/**
	 * Executes a single step of the agent's execution.
	 *
	 * @since 0.0.1
	 *
	 * @return Agent_Step_Result The result of the step execution.
	 */
	public function step(): Agent_Step_Result;
}
