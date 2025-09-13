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
				'plugin_folder' => array(
					'type'        => 'string',
					'description' => __( 'The plugin folder name or slug (e.g., woocommerce, akismet).', 'ai-assistant' ),
				),
				'activate'      => array(
					'type'        => 'boolean',
					'description' => __( 'Whether to activate (true) or deactivate (false) the plugin.', 'ai-assistant' ),
					'default'     => true,
				),
			),
			'required'   => array( 'plugin_folder' ),
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
		if ( ! isset( $args->plugin_folder ) || ! is_string( $args->plugin_folder ) ) {
			return new WP_Error( 'invalid_plugin_folder', __( 'A valid plugin folder name is required.', 'ai-assistant' ) );
		}

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		$plugin_folder = sanitize_text_field( $args->plugin_folder );
		$activate      = isset( $args->activate ) ? (bool) $args->activate : true;

		// Find the plugin file
		$plugin_file = $this->find_plugin_file( $plugin_folder );

		if ( ! $plugin_file ) {
			return new WP_Error(
				'plugin_not_found',
				sprintf(
					/* translators: %s: plugin folder name */
					__( 'Plugin with folder name "%s" not found or not installed.', 'ai-assistant' ),
					$plugin_folder
				)
			);
		}

		// Get plugin data for name
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false, false );
		$plugin_name = ! empty( $plugin_data['Name'] ) ? $plugin_data['Name'] : $plugin_folder;

		$output              = new stdClass();
		$output->plugin_name = $plugin_name;
		$output->plugin_file = $plugin_file;

		if ( $activate ) {
			// Activate the plugin
			if ( is_plugin_active( $plugin_file ) ) {
				$output->success = true;
				$output->message = sprintf(
					/* translators: %s: plugin name */
					__( 'Plugin "%s" is already active.', 'ai-assistant' ),
					$plugin_name
				);
				return $output;
			}

			$result = activate_plugin( $plugin_file );
			if ( is_wp_error( $result ) ) {
				$output->success = false;
				$output->message = sprintf(
					/* translators: %1$s: plugin name, %2$s: error message */
					__( 'Failed to activate plugin "%1$s": %2$s', 'ai-assistant' ),
					$plugin_name,
					$result->get_error_message()
				);
				return $output;
			}

			$output->success = true;
			$output->message = sprintf(
				/* translators: %s: plugin name */
				__( 'Plugin "%s" has been activated successfully.', 'ai-assistant' ),
				$plugin_name
			);

		} else {
			// Deactivate the plugin
			if ( ! is_plugin_active( $plugin_file ) ) {
				$output->success = true;
				$output->message = sprintf(
					/* translators: %s: plugin name */
					__( 'Plugin "%s" is already inactive.', 'ai-assistant' ),
					$plugin_name
				);
				return $output;
			}

			deactivate_plugins( $plugin_file );

			$output->success = true;
			$output->message = sprintf(
				/* translators: %s: plugin name */
				__( 'Plugin "%s" has been deactivated successfully.', 'ai-assistant' ),
				$plugin_name
			);
		}

		return $output;
	}

	/**
	 * Find the main plugin file based on folder name.
	 *
	 * @param string $plugin_folder The plugin folder name.
	 * @return string|false The plugin file path or false if not found.
	 */
	private function find_plugin_file( $plugin_folder ) {
		$plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_folder;

		// Check if the folder exists
		if ( ! is_dir( $plugin_dir ) ) {
			return false;
		}

		// First, try the most common pattern: folder/folder.php
		$main_file = $plugin_folder . '/' . $plugin_folder . '.php';
		if ( file_exists( WP_PLUGIN_DIR . '/' . $main_file ) ) {
			return $main_file;
		}

		// Get all installed plugins and search for ones in this folder
		$all_plugins = get_plugins();
		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			$file_folder = dirname( $plugin_file );
			if ( $file_folder === $plugin_folder ) {
				return $plugin_file;
			}
		}

		// If still not found, scan the directory for PHP files with plugin headers
		$php_files = glob( $plugin_dir . '/*.php' );
		foreach ( $php_files as $file ) {
			$plugin_data = get_plugin_data( $file, false, false );
			if ( ! empty( $plugin_data['Name'] ) ) {
				return $plugin_folder . '/' . basename( $file );
			}
		}

		return false;
	}

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
