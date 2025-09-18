<?php
/**
 * Class Ai_Assistant\Abilities\Publish_Post_Ability
 *
 * @since 0.0.1
 * @package ai-assistant
 */

namespace Ai_Assistant\Abilities;

use WP_Error;

/**
 * Ability to publish an existing WordPress post.
 *
 * @since 0.0.1
 */
class Publish_Post_Ability extends Abstract_Ability {

	/**
	 * Constructor.
	 *
	 * @since 0.0.1
	 *
	 * @param string              $name       The name of the ability.
	 * @param array<string,mixed> $properties The properties of the ability.
	 */
	public function __construct( string $name, array $properties = array() ) {
		parent::__construct( $name, $properties );
	}

	/**
	 * Returns the description of the ability.
	 *
	 * @since 0.0.1
	 *
	 * @return string The description of the ability.
	 */
	protected function description(): string {
		return 'Publishes an existing WordPress post.';
	}

	/**
	 * Returns the input schema of the ability.
	 *
	 * @since 0.0.1
	 *
	 * @return array<string, mixed> The input schema of the ability.
	 */
	protected function input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'The ID of the post to publish.',
				),
			),
			'required'   => array( 'post_id' ),
		);
	}

	/**
	 * Returns the output schema of the ability.
	 *
	 * @since 0.0.1
	 *
	 * @return array<string, mixed> The output schema of the ability.
	 */
	protected function output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'post_id'       => array(
					'type'        => 'integer',
					'description' => 'The ID of the published post.',
				),
				'post_edit_url' => array(
					'type'        => 'string',
					'description' => 'The URL to edit the post in the WordPress admin.',
				),
				'post_url'      => array(
					'type'        => 'string',
					'description' => 'The public URL of the post.',
				),
				'message'       => array(
					'type'        => 'string',
					'description' => 'A success message.',
				),
			),
			'required'   => array( 'post_id', 'post_edit_url', 'post_url', 'message' ),
		);
	}

	/**
	 * Executes the ability with the given input arguments.
	 *
	 * @since 0.0.1
	 *
	 * @param mixed $args The input arguments to the ability.
	 * @return mixed|WP_Error The result of the ability execution, or a WP_Error on failure.
	 */
	protected function execute_callback( $args ) {
		$post = get_post( $args['post_id'] );

		if ( ! $post ) {
			return new WP_Error(
				'post_not_found',
				'Post with ID ' . $args['post_id'] . ' not found.'
			);
		}

		if ( 'publish' === $post->post_status ) {
			return array(
				'post_id'       => $post->ID,
				'post_edit_url' => get_edit_post_link( $post->ID, 'raw' ),
				'post_url'      => get_permalink( $post ),
				'message'       => 'Post is already published.',
			);
		}

		$updated_post_id = wp_update_post(
			array(
				'ID'          => $args['post_id'],
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $updated_post_id ) ) {
			return $updated_post_id;
		}

		return array(
			'post_id'       => $updated_post_id,
			'post_edit_url' => get_edit_post_link( $updated_post_id, 'raw' ),
			'post_url'      => get_permalink( $updated_post_id ),
			'message'       => 'Post published successfully.',
		);
	}

	/**
	 * Checks whether the current user has permission to execute the ability with the given input arguments.
	 *
	 * @since 0.0.1
	 *
	 * @param mixed $args The input arguments to the ability.
	 * @return bool|WP_Error True if the user has permission, false or WP_Error otherwise.
	 */
	protected function permission_callback( $args ) {
		if ( ! current_user_can( 'publish_post', $args['post_id'] ) ) {
			return new WP_Error(
				'insufficient_capabilities',
				'You do not have permission to publish this post.'
			);
		}
		return true;
	}
}
