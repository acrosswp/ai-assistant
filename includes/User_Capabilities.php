<?php
namespace Ai_Assistant\Includes;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * User Capabilities Manager
 *
 * @since 0.0.1
 * @package Ai_Assistant
 * @subpackage Ai_Assistant/includes
 */
class User_Capabilities {

	/**
	 * Initialize capabilities
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_capabilities' ) );
	}

	/**
	 * Add custom capabilities
	 */
	public static function add_capabilities() {
		// Early bailout if not admin or during AJAX requests
		if ( ! is_admin() || wp_doing_ajax() ) {
			return;
		}

		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->add_cap( 'ai_assistant_access_chatbot' );
		}

		// Also add to editor role
		$role = get_role( 'editor' );
		if ( $role ) {
			$role->add_cap( 'ai_assistant_access_chatbot' );
		}
	}

	/**
	 * Remove capabilities (for deactivation)
	 */
	public static function remove_capabilities() {
		$roles = array( 'administrator', 'editor' );

		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$role->remove_cap( 'ai_assistant_access_chatbot' );
			}
		}
	}
}
