<?php
namespace Ai_Assistant\Admin;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;


/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/WPBoilerplate/ai-assistant
 * @since      0.0.1
 *
 * @package    Ai_Assistant
 * @subpackage Ai_Assistant/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Ai_Assistant
 * @subpackage Ai_Assistant/admin
 * @author     WPBoilerplate <contact@wpboilerplate.com>
 */
class Main {

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
	 * The js_asset_file of the backend
	 *
	 * @since    0.0.1
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $js_asset_file;

	/**
	 * The css_asset_file of the backend
	 *
	 * @since    0.0.1
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $css_asset_file;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.0.1
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		$this->js_asset_file  = include \AI_ASSISTANT_PLUGIN_PATH . 'build/js/backend.asset.php';
		$this->css_asset_file = include \AI_ASSISTANT_PLUGIN_PATH . 'build/css/backend.asset.php';
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    0.0.1
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ai_Assistant_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ai_Assistant_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, \AI_ASSISTANT_PLUGIN_URL . 'build/css/backend.css', $this->css_asset_file['dependencies'], $this->css_asset_file['version'], 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    0.0.1
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Ai_Assistant_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Ai_Assistant_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, \AI_ASSISTANT_PLUGIN_URL . 'build/js/backend.js', $this->js_asset_file['dependencies'], $this->js_asset_file['version'], false );

		// Enqueue chatbot script for admin users
		if ( current_user_can( 'manage_options' ) ) {
			wp_enqueue_script(
				$this->plugin_name . '-chatbot',
				\AI_ASSISTANT_PLUGIN_URL . 'src/js/chatbot.js',
				array( 'jquery' ),
				$this->version,
				true
			);

			// Localize script for REST API
			wp_localize_script(
				$this->plugin_name . '-chatbot',
				'aiAssistantChatbot',
				array(
					'apiUrl' => rest_url( 'ai-assistant/v1/' ),
					'nonce'  => wp_create_nonce( 'wp_rest' ),
				)
			);
		}
	}
}
