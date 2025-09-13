<?php
/**
 * Class Ai_Assistant\Abilities\Activate_Plugin_Ability
 *
 * @since 0.0.1
 * @package ai-assistant
 */

namespace Ai_Assistant\Abilities;

use WP_Error;
use stdClass;

/**
 * Ability to activate or deactivate an installed plugin by folder/slug.
 *
 * @since 0.0.1
 */
class Activate_Plugin_Ability extends Abstract_Ability {

	public function __construct() {
		parent::__construct( 'activate-plugin', array( 'label' => __( 'Activate Plugin', 'ai-assistant' ) ) );
	}

	protected function description(): string {
		return __( 'Activates or deactivates an installed plugin by folder name or slug.', 'ai-assistant' );
	}

	protected function input_schema(): array {
				return array(
					'type'       => 'object',
					'properties' => array(
						'plugin_name' => array(
							'type'        => 'string',
							'description' => __( 'The plugin name or folder/slug (e.g., WooCommerce, woocommerce, Akismet Anti-Spam, akismet).', 'ai-assistant' ),
						),
						'activate'    => array(
							'type'        => 'boolean',
							'description' => __( 'Whether to activate (true) or deactivate (false) the plugin.', 'ai-assistant' ),
							'default'     => true,
						),
					),
					'required'   => array( 'plugin_name' ),
				);
	}

	protected function output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'success'     => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the plugin was activated/deactivated successfully.', 'ai-assistant' ),
				),
				'message'     => array(
					'type'        => 'string',
					'description' => __( 'Success or error message.', 'ai-assistant' ),
				),
				'plugin_name' => array(
					'type'        => 'string',
					'description' => __( 'The name of the plugin that was activated/deactivated.', 'ai-assistant' ),
				),
				'plugin_file' => array(
					'type'        => 'string',
					'description' => __( 'The plugin file path that was used.', 'ai-assistant' ),
				),
			),
		);
	}

	protected function execute_callback( $args ) {

		// Only plugin_name is required
		$plugin_input = isset( $args->plugin_name ) && is_string( $args->plugin_name ) ? sanitize_text_field( $args->plugin_name ) : '';
		$activate     = isset( $args->activate ) ? (bool) $args->activate : true;

		if ( empty( $plugin_input ) ) {
			return new WP_Error( 'invalid_plugin_input', __( 'A valid plugin name or folder/slug is required.', 'ai-assistant' ) );
		}

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		// Get all installed plugins
		$all_plugins          = get_plugins();
		$plugin_file          = '';
		$resolved_plugin_name = '';

				// Try to match by folder/slug (exact)
		foreach ( $all_plugins as $file => $data ) {
			$folder = dirname( $file );
			if ( $plugin_input === $folder || $plugin_input === basename( $file, '.php' ) ) {
				$plugin_file          = $file;
				$resolved_plugin_name = isset( $data['Name'] ) ? $data['Name'] : $plugin_input;
				break;
			}
		}

				// Try to match by plugin name (case-insensitive, exact)
		if ( empty( $plugin_file ) ) {
			foreach ( $all_plugins as $file => $data ) {
				if ( isset( $data['Name'] ) && 0 === strcasecmp( $data['Name'], $plugin_input ) ) {
					$plugin_file          = $file;
					$resolved_plugin_name = $data['Name'];
					break;
				}
			}
		}

				// Try partial match by plugin name (case-insensitive)
		if ( empty( $plugin_file ) ) {
			foreach ( $all_plugins as $file => $data ) {
				if ( isset( $data['Name'] ) && false !== stripos( $data['Name'], $plugin_input ) ) {
					$plugin_file          = $file;
					$resolved_plugin_name = $data['Name'];
					break;
				}
			}
		}

				// Try partial match by folder/slug
		if ( empty( $plugin_file ) ) {
			foreach ( $all_plugins as $file => $data ) {
				$folder = dirname( $file );
				if ( false !== stripos( $folder, $plugin_input ) ) {
					$plugin_file          = $file;
					$resolved_plugin_name = isset( $data['Name'] ) ? $data['Name'] : $plugin_input;
					break;
				}
			}
		}

		if ( empty( $plugin_file ) ) {
			return new WP_Error(
				'plugin_not_found',
				sprintf(
					/* translators: %s: plugin name or folder */
					__( 'Plugin with name or folder "%s" not found or not installed.', 'ai-assistant' ),
					$plugin_input
				)
			);
		}

				$output              = new stdClass();
				$output->plugin_name = $resolved_plugin_name;
				$output->plugin_file = $plugin_file;

		if ( $activate ) {
			// Activate the plugin
			if ( is_plugin_active( $plugin_file ) ) {
				$output->success = true;
				$output->message = sprintf(
					/* translators: %s: plugin name */
					__( 'Plugin "%s" is already active.', 'ai-assistant' ),
					$resolved_plugin_name
				);
				return $output;
			}

			$result = activate_plugin( $plugin_file );
			if ( is_wp_error( $result ) ) {
				$output->success = false;
				$output->message = sprintf(
					/* translators: %1$s: plugin name, %2$s: error message */
					__( 'Failed to activate plugin "%1$s": %2$s', 'ai-assistant' ),
					$resolved_plugin_name,
					$result->get_error_message()
				);
				return $output;
			}

			$output->success = true;
			$output->message = sprintf(
				/* translators: %s: plugin name */
				__( 'Plugin "%s" has been activated successfully.', 'ai-assistant' ),
				$resolved_plugin_name
			);

		} else {
			// Deactivate the plugin
			if ( ! is_plugin_active( $plugin_file ) ) {
				$output->success = true;
				$output->message = sprintf(
					/* translators: %s: plugin name */
					__( 'Plugin "%s" is already inactive.', 'ai-assistant' ),
					$resolved_plugin_name
				);
				return $output;
			}

			deactivate_plugins( $plugin_file );

			$output->success = true;
			$output->message = sprintf(
				/* translators: %s: plugin name */
				__( 'Plugin "%s" has been deactivated successfully.', 'ai-assistant' ),
				$resolved_plugin_name
			);
		}

		return $output;
	}

		// (find_plugin_file_by_name_or_slug is now obsolete and removed)

	protected function permission_callback( $args ) {
		$activate = isset( $args->activate ) ? (bool) $args->activate : true;

		if ( $activate && ! current_user_can( 'activate_plugins' ) ) {
			return new WP_Error(
				'rest_cannot_activate_plugin',
				__( 'Sorry, you are not allowed to activate plugins.', 'ai-assistant' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! $activate && ! current_user_can( 'deactivate_plugins' ) ) {
			return new WP_Error(
				'rest_cannot_deactivate_plugin',
				__( 'Sorry, you are not allowed to deactivate plugins.', 'ai-assistant' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}
}
