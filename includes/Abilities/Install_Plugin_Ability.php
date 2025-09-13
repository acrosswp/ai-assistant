<?php
/**
 * Class Ai_Assistant\Abilities\Install_Plugin_Ability
 *
 * @since 0.0.1
 * @package ai-assistant
 */

namespace Ai_Assistant\Abilities;

use WP_Error;
use stdClass;

/**
 * Ability to install and activate a plugin from the WordPress.org repository.
 *
 * @since 0.0.1
 */
class Install_Plugin_Ability extends Abstract_Ability {

	public function __construct() {
		parent::__construct( 'install-plugin', array( 'label' => __( 'Install Plugin', 'ai-assistant' ) ) );
	}

	protected function description(): string {
		return __( 'Installs and activates a plugin from the WordPress.org repository by slug.', 'ai-assistant' );
	}

	protected function input_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'slug' => array(
					'type'        => 'string',
					'description' => __( 'The plugin slug (e.g., akismet).', 'ai-assistant' ),
				),
			),
			'required'   => array( 'slug' ),
		);
	}

	protected function output_schema(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'success' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the plugin was installed and activated successfully.', 'ai-assistant' ),
				),
				'error'   => array(
					'type'        => 'string',
					'description' => __( 'Error message if the installation failed.', 'ai-assistant' ),
				),
			),
		);
	}

	protected function execute_callback( $args ) {
		if ( ! isset( $args->slug ) || ! is_string( $args->slug ) ) {
			return new WP_Error( 'invalid_slug', __( 'A valid plugin slug is required.', 'ai-assistant' ) );
		}

		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		include_once ABSPATH . 'wp-admin/includes/file.php';

		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $args->slug,
				'fields' => array( 'sections' => false ),
			)
		);
		if ( is_wp_error( $api ) ) {
			return new WP_Error( 'plugin_api_error', $api->get_error_message() );
		}

		$upgrader = new \Plugin_Upgrader();
		$result   = $upgrader->install( $api->download_link );
		if ( is_wp_error( $result ) ) {
			error_log( 'Plugin install error: ' . $result->get_error_message() );
			return new WP_Error( 'plugin_install_error', $result->get_error_message() );
		}

		// Try to find the main plugin file if the default does not exist
		$plugin_dir = WP_PLUGIN_DIR . '/' . $args->slug;
		if ( is_dir( $plugin_dir ) ) {
			$plugin_files = glob( $plugin_dir . '/*.php' );
			foreach ( $plugin_files as $file ) {
				$plugin_data = get_plugin_data( $file, false, false );
				if ( ! empty( $plugin_data['Name'] ) && stripos( $plugin_data['Name'], $args->slug ) !== false ) {
					$plugin_file = basename( $plugin_dir ) . '/' . basename( $file );
					break;
				}
			}
		}

		if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
			activate_plugin( $plugin_file );
			$output          = new stdClass();
			$output->success = true;
			$output->error   = '';
			return $output;
		}

		$output          = new stdClass();
		$output->success = false;
		$output->error   = __( 'Plugin file not found after installation.', 'ai-assistant' );
		return $output;
	}

	protected function permission_callback( $args ) {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return new WP_Error( 'rest_cannot_install_plugin', __( 'Sorry, you are not allowed to install plugins.', 'ai-assistant' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}
}
