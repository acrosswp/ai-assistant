<?php
/**
 * Class Ai_Assistant\Abilities\Abilities_Registrar
 *
 * @since 0.0.1
 * @package ai-assistant
 */

namespace Ai_Assistant\Abilities;

/**
 * Registers all abilities for the plugin.
 *
 * @since 0.0.1
 */
class Abilities_Registrar {

	/**
	 * Registers all abilities.
	 *
	 * @since 0.0.1
	 */
	public function register_abilities(): void {

		\wp_register_ability(
			'ai-assistant/get-post',
			array(
				'label'         => __( 'Get Post', 'ai-assistant' ),
				'ability_class' => Get_Post_Ability::class,
			)
		);

		\wp_register_ability(
			'ai-assistant/create-post-draft',
			array(
				'label'         => __( 'Create Post Draft', 'ai-assistant' ),
				'ability_class' => Create_Post_Draft_Ability::class,
			)
		);

		\wp_register_ability(
			'ai-assistant/generate-post-featured-image',
			array(
				'label'         => __( 'Generate Post Featured Image', 'ai-assistant' ),
				'ability_class' => Generate_Post_Featured_Image_Ability::class,
			)
		);

		\wp_register_ability(
			'ai-assistant/publish-post',
			array(
				'label'         => __( 'Publish Post', 'ai-assistant' ),
				'ability_class' => Publish_Post_Ability::class,
			)
		);

		\wp_register_ability(
			'ai-assistant/search-posts',
			array(
				'label'         => __( 'Search Posts', 'ai-assistant' ),
				'ability_class' => Search_Posts_Ability::class,
			)
		);

		\wp_register_ability(
			'ai-assistant/set-permalink-structure',
			array(
				'label'         => __( 'Set Permalink Structure', 'ai-assistant' ),
				'ability_class' => Set_Permalink_Structure_Ability::class,
			)
		);
	}
}
