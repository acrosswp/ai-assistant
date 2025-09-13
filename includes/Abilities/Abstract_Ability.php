<?php
/**
 * Class Ai_Assistant\Abilities\Abstract_Ability
 *
 * @since 0.0.1
 * @package ai-assistant
 */

namespace Ai_Assistant\Abilities;

use InvalidArgumentException;
use WP_Ability;
use WP_Error;
use WordPress\AiClient\Tools\DTO\FunctionDeclaration;

/**
 * Base class for a WordPress ability.
 *
 * @since 0.0.1
 */
abstract class Abstract_Ability extends WP_Ability {

	/**
	 * Constructor.
	 *
	 * @since 0.0.1
	 *
	 * @param string              $name       The name of the ability.
	 * @param array<string,mixed> $properties The properties of the ability. Must include `label`.
	 *
	 * @throws InvalidArgumentException Thrown if the label property is missing or invalid.
	 */
	public function __construct( string $name, array $properties = array() ) {
		if ( ! isset( $properties['label'] ) || ! is_string( $properties['label'] ) ) {
			throw new InvalidArgumentException( 'The "label" property is required and must be a string.' );
		}

		parent::__construct(
			$name,
			array(
				'label'               => $properties['label'],
				'description'         => $this->description(),
				'input_schema'        => $this->input_schema(),
				'output_schema'       => $this->output_schema(),
				'execute_callback'    => array( $this, 'execute_callback' ),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);
	}

	/**
	 * Gets the function declaration for the ability.
	 *
	 * @since 0.0.1
	 *
	 * @return FunctionDeclaration The function declaration.
	 */
	public function get_function_declaration(): FunctionDeclaration {
		return new FunctionDeclaration(
			$this->get_name(),
			$this->description(),
			array(
				'type'       => 'object',
				'properties' => $this->input_schema()['properties'] ?? array(),
				'required'   => $this->input_schema()['required'] ?? array(),
			)
		);
	}

	/**
	 * Returns the description of the ability.
	 *
	 * @since 0.0.1
	 *
	 * @return string The description of the ability.
	 */
	abstract protected function description(): string;

	/**
	 * Returns the input schema of the ability.
	 *
	 * @since 0.0.1
	 *
	 * @return array<string, mixed> The input schema of the ability.
	 */
	abstract protected function input_schema(): array;

	/**
	 * Returns the output schema of the ability.
	 *
	 * @since 0.0.1
	 *
	 * @return array<string, mixed> The output schema of the ability.
	 */
	abstract protected function output_schema(): array;

	/**
	 * Executes the ability with the given input arguments.
	 *
	 * @since 0.0.1
	 *
	 * @param mixed $args The input arguments to the ability.
	 * @return mixed|WP_Error The result of the ability execution, or a WP_Error on failure.
	 */
	abstract protected function execute_callback( $args );

	/**
	 * Checks whether the current user has permission to execute the ability with the given input arguments.
	 *
	 * @since 0.0.1
	 *
	 * @param mixed $args The input arguments to the ability.
	 * @return bool|WP_Error True if the user has permission, false or WP_Error otherwise.
	 */
	abstract protected function permission_callback( $args );
}
