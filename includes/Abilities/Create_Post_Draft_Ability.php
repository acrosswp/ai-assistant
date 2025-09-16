<?php
/**
 * Class Ai_Assistant\Abilities\Create_Post_Draft_Ability
 *
 * @since 0.0.1
 * @package ai-assistant
 */

namespace Ai_Assistant\Abilities;

use stdClass;
use WP_Error;
use WP_Post;

/**
 * Class for the Create Post Draft ability.
 *
 * @since 0.0.1
 */
class Create_Post_Draft_Ability extends Abstract_Ability {

	/**
	 * Constructor.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		parent::__construct( 'create-post-draft', array( 'label' => __( 'Create Post Draft', 'ai-assistant' ) ) );
	}

	/**
	 * Returns the description of the ability.
	 *
	 * @since 0.0.1
	 *
	 * @return string The description of the ability.
	 */
	protected function description(): string {
		return __( 'Creates a new post draft with title, content, excerpt and featured image ID.', 'ai-assistant' );
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
				'title'             => array(
					'type'        => 'string',
					'description' => __( 'The title of the post.', 'ai-assistant' ),
				),
				'content'           => array(
					'type'        => 'string',
					'description' => __( 'The content of the post, in HTML format.', 'ai-assistant' ),
				),
				'excerpt'           => array(
					'type'        => 'string',
					'description' => __( 'The excerpt of the post, in HTML format.', 'ai-assistant' ),
				),
				'featured_image_id' => array(
					'type'        => 'integer',
					'description' => __( 'The ID of the featured image to use for the post.', 'ai-assistant' ),
				),
			),
			'required'   => array( 'title', 'content' ),
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
				'id'        => array(
					'type'        => 'integer',
					'description' => __( 'The ID of the post.', 'ai-assistant' ),
				),
				'title'     => array(
					'type'        => 'string',
					'description' => __( 'The title of the post.', 'ai-assistant' ),
				),
				'edit_link' => array(
					'type'        => 'string',
					'description' => __( 'The URL to edit the post.', 'ai-assistant' ),
				),
			),
		);
	}

	/**
	 * Executes the ability with the given input arguments.
	 *
	 * @since 0.0.1
	 *
	 * @param mixed $args The input arguments to the ability (array or object).
	 * @return stdClass|WP_Error The result of the ability execution, or a WP_Error on failure.
	 */
	protected function execute_callback( $args ) {
		// Accept both array and object input for compatibility
		if ( is_object( $args ) ) {
			$args = (array) $args;
		}

		// Validate required fields
		if ( empty( $args['title'] ) || empty( $args['content'] ) ) {
			return new WP_Error( 'missing_required_fields', __( 'Title and content are required.', 'ai-assistant' ) );
		}

		// Sanitize input
		$title   = sanitize_text_field( $args['title'] );
		$content = wp_kses_post( $args['content'] );

		$postarr = array(
			'post_type'    => 'post',
			'post_status'  => 'draft',
			'post_title'   => $title,
			'post_content' => $content,
		);

		if ( ! empty( $args['excerpt'] ) ) {
			$postarr['post_excerpt'] = sanitize_text_field( $args['excerpt'] );
		}

		$post_id = wp_insert_post( $postarr, true );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		if ( ! empty( $args['featured_image_id'] ) && is_numeric( $args['featured_image_id'] ) ) {
			set_post_thumbnail( $post_id, intval( $args['featured_image_id'] ) );
		}

		$post = get_post( $post_id );
		if ( ! $post || ! $post instanceof WP_Post ) {
			return new WP_Error(
				'post_not_found',
				__( 'Post not found.', 'ai-assistant' )
			);
		}

		$result            = new stdClass();
		$result->id        = $post->ID;
		$result->title     = $post->post_title;
		$result->edit_link = get_edit_post_link( $post->ID, 'raw' );

		return $result;
	}

	/**
	 * Checks whether the current user has permission to execute the ability with the given input arguments.
	 *
	 * @since 0.0.1
	 *
	 * @param stdClass $args The input arguments to the ability.
	 * @return bool|WP_Error True if the user has permission, false or WP_Error otherwise.
	 */
	protected function permission_callback( $args ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'rest_cannot_create_post',
				__( 'Sorry, you are not allowed to create posts.', 'ai-assistant' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}
}
