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
class Class_Chatbot {

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
	 * Register the chatbot JavaScript for the public-facing side of the site.
	 *
	 * @since    0.0.1
	 */
	public function enqueue_scripts() {
		// Only load chatbot for logged-in users with appropriate permissions
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-chatbot',
			plugin_dir_url( dirname( __DIR__ ) ) . 'src/js/chatbot.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		// Add the necessary wp-api settings for REST API calls
		wp_localize_script(
			$this->plugin_name . '-chatbot',
			'wpApiSettings',
			array(
				'root'  => esc_url_raw( rest_url() ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			)
		);

		// Add ajaxurl for compatibility with the chatbot script
		wp_localize_script(
			$this->plugin_name . '-chatbot',
			'ajaxurl',
			admin_url( 'admin-ajax.php' )
		);
	}
}
