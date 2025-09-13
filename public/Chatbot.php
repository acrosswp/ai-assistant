<?php
namespace Ai_Assistant\Public;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Handles the chatbot functionality for the public-facing side of the site.
 *
 * @package    Ai_Assistant
 * @subpackage Ai_Assistant/public
 * @since      0.0.1
 */
class Chatbot {

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.0.1
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.0.1
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.0.1
	 * @param    string    $plugin_name    The name of the plugin.
	 * @param    string    $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the script for the public-facing side of the site.
	 *
	 * @since    0.0.1
	 */
	public function enqueue_scripts() {

		// Check if we're in admin area
		if ( ! is_admin() ) {
			return;
		}

		// Only show chatbot to administrators
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Debug logging - remove after testing
		error_log( 'AI Assistant Chatbot: Scripts are being enqueued for user with manage_options capability' );

		// Enqueue jQuery as a dependency
		wp_enqueue_script( 'jquery' );

		// Enqueue modular chatbot scripts with working approach

		// 1. Core script with inline wpApiSettings (ensures immediate availability)
		wp_enqueue_script(
			$this->plugin_name . '-chatbot-main',
			\AI_ASSISTANT_PLUGIN_URL . 'src/js/chatbot/chatbot-main.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		// Add the necessary wp-api settings for REST API calls
		$api_settings = array(
			'root'              => esc_url_raw( rest_url() ),
			'nonce'             => wp_create_nonce( 'wp_rest' ),
			'messages_endpoint' => rest_url( 'ai-assistant/v1/messages' ),
			'user_can_manage'   => current_user_can( 'manage_options' ),
		);

		wp_localize_script(
			$this->plugin_name . '-chatbot-main',
			'wpApiSettings',
			$api_settings
		);
	}
}
