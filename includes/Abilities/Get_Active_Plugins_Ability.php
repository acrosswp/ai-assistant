<?php
/**
 * Class Ai_Assistant\Abilities\Get_Active_Plugins_Ability
 *
 * @since 0.0.1
 * @package ai-assistant
 */

namespace Ai_Assistant\Abilities;

use WP_Error;
use stdClass;

/**
 * Ability to get the list of active plugins.
 *
 * @since 0.0.1
 */
class Get_Active_Plugins_Ability extends Abstract_Ability {

	/**
	 * Constructor.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		parent::__construct( 'get-active-plugins', array( 'label' => __( 'Get Active Plugins', 'ai-assistant' ) ) );
	}

	/**
	 * Returns the description of the ability.
	 *
	 * @since 0.0.1
	 *
	 * @return string The description of the ability.
	 */
	protected function description(): string {
		return __( 'Retrieves a list of all currently active plugins on the WordPress site. Call this with an empty object {} as input.', 'ai-assistant' );
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
			'type'                 => 'object',
			'properties'           => array(
				'include_inactive' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether to include inactive plugins in the list as well.', 'ai-assistant' ),
					'default'     => false,
				),
			),
			'required'             => array(),
			'additionalProperties' => false,
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
				'active_plugins' => array(
					'type'        => 'array',
					'description' => __( 'List of active plugins.', 'ai-assistant' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'name'        => array(
								'type'        => 'string',
								'description' => __( 'Plugin name.', 'ai-assistant' ),
							),
							'plugin_file' => array(
								'type'        => 'string',
								'description' => __( 'Plugin file path.', 'ai-assistant' ),
							),
							'version'     => array(
								'type'        => 'string',
								'description' => __( 'Plugin version.', 'ai-assistant' ),
							),
							'description' => array(
								'type'        => 'string',
								'description' => __( 'Plugin description.', 'ai-assistant' ),
							),
							'author'      => array(
								'type'        => 'string',
								'description' => __( 'Plugin author.', 'ai-assistant' ),
							),
							'status'      => array(
								'type'        => 'string',
								'description' => __( 'Plugin status (active/inactive).', 'ai-assistant' ),
							),
						),
					),
				),
				'total_count'    => array(
					'type'        => 'integer',
					'description' => __( 'Total number of plugins returned.', 'ai-assistant' ),
				),
			),
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
		if ( ! function_exists( 'get_plugins' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$include_inactive = false;
		if ( is_array( $args ) && isset( $args['include_inactive'] ) ) {
			$include_inactive = (bool) $args['include_inactive'];
		}

		// Get all plugins
		$all_plugins    = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );

		// For multisite, also get network active plugins
		if ( is_multisite() ) {
			$network_active = get_site_option( 'active_sitewide_plugins', array() );
			$network_active = array_keys( $network_active );
		} else {
			$network_active = array();
		}

		$plugin_list = array();

		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			$is_active = in_array( $plugin_file, $active_plugins, true ) || in_array( $plugin_file, $network_active, true );

			// Skip inactive plugins if not requested
			if ( ! $include_inactive && ! $is_active ) {
				continue;
			}

			$plugin_list[] = array(
				'name'        => $plugin_data['Name'],
				'plugin_file' => $plugin_file,
				'version'     => $plugin_data['Version'],
				'description' => wp_strip_all_tags( $plugin_data['Description'] ),
				'author'      => wp_strip_all_tags( $plugin_data['Author'] ),
				'status'      => $is_active ? 'active' : 'inactive',
			);
		}

		$result                 = new stdClass();
		$result->active_plugins = $plugin_list;
		$result->total_count    = count( $plugin_list );

		return $result;
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
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_cannot_view_plugins',
				__( 'Sorry, you are not allowed to view the plugin list.', 'ai-assistant' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}
}
