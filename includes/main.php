<?php
namespace Ai_Assistant\Includes;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/WPBoilerplate/ai-assistant
 * @since      0.0.1
 *
 * @package    Ai_Assistant
 * @subpackage Ai_Assistant/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      0.0.1
 * @package    Ai_Assistant
 * @subpackage Ai_Assistant/includes
 * @author     WPBoilerplate <contact@wpboilerplate.com>
 */
final class Main {

	/**
	 * The single instance of the class.
	 *
	 * @var Ai_Assistant
	 * @since 0.0.1
	 */
	protected static $_instance = null;

	/**
	 * The autoloader instance.
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      Autoloader    $autoloader    The plugin autoloader instance.
	 */
	protected $autoloader;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      Ai_Assistant_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The plugin dir path
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      string    $plugin_path    The string for plugin dir path
	 */
	protected $plugin_path;

	/**
	 * The current version of the plugin.
	 *
	 * @since    0.0.1
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Plugin directory path.
	 *
	 * @var string
	 */
	protected $plugin_dir;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    0.0.1
	 */
	public function __construct() {

		$this->plugin_name = 'ai-assistant';

		$this->define_constants();

		if ( defined( 'AI_ASSISTANT_VERSION' ) ) {
			$this->version = AI_ASSISTANT_VERSION;
		} else {
			$this->version = '0.0.1';
		}

		// Load the autoloader class manually before registering it
		$plugin_path = AI_ASSISTANT_PLUGIN_PATH;

		require_once $plugin_path . 'includes/Autoloader.php';

		$this->register_autoloader();

		$this->load_composer_dependencies();

		$this->load_dependencies();

		$this->set_locale();

		$this->load_hooks();
	}

	/**
	 * Main Ai_Assistant Instance.
	 *
	 * Ensures only one instance of WooCommerce is loaded or can be loaded.
	 *
	 * @since 0.0.1
	 * @static
	 * @see Ai_Assistant()
	 * @return Ai_Assistant - Main instance.
	 */
	public static function instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Define WCE Constants
	 */
	private function define_constants() {

		$this->define( 'AI_ASSISTANT_PLUGIN_BASENAME', plugin_basename( \AI_ASSISTANT_PLUGIN_FILE ) );
		$this->define( 'AI_ASSISTANT_PLUGIN_PATH', plugin_dir_path( \AI_ASSISTANT_PLUGIN_FILE ) );
		$this->define( 'AI_ASSISTANT_PLUGIN_URL', plugin_dir_url( \AI_ASSISTANT_PLUGIN_FILE ) );
		$this->define( 'AI_ASSISTANT_PLUGIN_NAME_SLUG', $this->plugin_name );
		$this->define( 'AI_ASSISTANT_PLUGIN_NAME', 'Ai Assistant' );

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugin_file = defined( 'AI_ASSISTANT_PLUGIN_FILE' )
			? \AI_ASSISTANT_PLUGIN_FILE
			: \AI_ASSISTANT_PLUGIN_FILE;
		$plugin_data = get_plugin_data( $plugin_file );
		$version     = $plugin_data['Version'];
		$this->define( 'AI_ASSISTANT_VERSION', $version );

		$this->define( 'AI_ASSISTANT_PLUGIN_URL', $version );

		$this->plugin_dir = AI_ASSISTANT_PLUGIN_PATH;
	}

	/**
	 * Define constant if not already set
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Register the plugin's autoloader.
	 *
	 * This autoloader will automatically load classes from the plugin's namespace
	 * when they are instantiated.
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	private function register_autoloader() {
		// Get the plugin path
		$plugin_path = AI_ASSISTANT_PLUGIN_PATH;

		// Create autoloader instance
		$this->autoloader = new Autoloader( 'Ai_Assistant', $plugin_path );

		// Register the autoloader
		spl_autoload_register( array( $this->autoloader, 'autoload' ) );
	}

	/**
	 * Register all the hook once all the active plugins are loaded
	 *
	 * Uses the plugins_loaded to load all the hooks and filters
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	public function load_hooks() {

		/**
		 * Check if plugin can be loaded safely or not
		 *
		 * @since    0.0.1
		 */
		if ( apply_filters( 'ai-assistant-load', true ) ) {
			$this->define_admin_hooks();
			$this->define_public_hooks();
		}
	}

	/**
	 * Load composer dependencies if available
	 */
	private function load_composer_dependencies() {
		$plugin_path = \AI_ASSISTANT_PLUGIN_PATH;

		// Check if composer autoloader exists and the vendor directory is properly set up
		if ( file_exists( $plugin_path . 'vendor/autoload.php' ) ) {
			// Check if critical dependency directories exist
			$required_dirs = array(
				'guzzlehttp/guzzle',
				'php-http/guzzle7-adapter',
			);

			$all_dependencies_exist = true;
			foreach ( $required_dirs as $dir ) {
				if ( ! is_dir( $plugin_path . 'vendor/' . $dir ) ) {
					$all_dependencies_exist = false;
					break;
				}
			}

			if ( ! $all_dependencies_exist ) {
				error_log( 'AI Assistant: HTTP client dependencies are missing. Using fallback client only.' );
				return;
			}

			require_once $plugin_path . 'vendor/autoload.php';
		}
	}   /**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Ai_Assistant\Admin\Loader. Orchestrates the hooks of the plugin.
	 * - Ai_Assistant\Admin\I18n. Defines internationalization functionality.
	 * - Ai_Assistant\Admin\Main. Defines all hooks for the admin area.
	 * - Ai_Assistant_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	private function load_dependencies() {

		$this->loader = Loader::instance();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Ai_Assistant_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	private function set_locale() {
		$i18n = new I18n();

		// Now attach it to `init`, not `plugins_loaded`
		$this->loader->add_action( 'init', $i18n, 'do_load_textdomain' );
	}


	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new \Ai_Assistant\Admin\Main( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		/**
		 * Add the Plugin Main Menu
		 */
		$main_menu = new \Ai_Assistant\Admin\Partials\Menu( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_menu', $main_menu, 'main_menu' );
		$this->loader->add_action( 'plugin_action_links', $main_menu, 'plugin_action_links', 1000, 2 );
		// Register settings section and fields
		$this->loader->add_action( 'admin_init', '\Ai_Assistant\Admin\Partials\Menu', 'register_settings' );

		/**
		 * Initialize User Capabilities
		 */
		User_Capabilities::init();

		/**
		 * Initialize REST API Routes
		 */
		$rest_routes = new Chatbot_REST_Routes();
		$this->loader->add_action( 'rest_api_init', $rest_routes, 'register_routes' );

		/**
		 * Enqueue chatbot assets
		 */
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_chatbot_assets' );
		$this->loader->add_action( 'admin_footer', $this, 'render_chatbot_container' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    0.0.1
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new \Ai_Assistant\Public\Main( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    0.0.1
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     0.0.1
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     0.0.1
	 * @return    Ai_Assistant_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * The reference to the autoloader instance.
	 *
	 * @since     0.0.1
	 * @return    Autoloader    The plugin autoloader instance.
	 */
	public function get_autoloader() {
		return $this->autoloader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     0.0.1
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Enqueue chatbot assets
	 *
	 * @since     0.0.1
	 */
	public function enqueue_chatbot_assets() {
		// Early bailout if AI features are not available
		$ai_client_manager = AI_Client_Manager::instance();
		if ( ! $ai_client_manager->can_use_ai() ) {
			return;
		}

		// Enqueue chatbot assets only on admin pages
		if ( ! is_admin() ) {
			return;
		}

		// Get current screen
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		// Load chatbot on all admin pages
		wp_localize_script(
			$this->plugin_name,
			'aiAssistantChatbot',
			array(
				'enabled'     => true,
				'apiUrl'      => rest_url( 'ai-assistant/v1/' ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'currentUser' => wp_get_current_user(),
			)
		);
	}

	/**
	 * Render chatbot container
	 *
	 * @since     0.0.1
	 */
	public function render_chatbot_container() {
		// Early bailout if AI features are not available
		$ai_client_manager = AI_Client_Manager::instance();
		if ( ! $ai_client_manager->can_use_ai() ) {
			return;
		}

		// Only render on admin pages
		if ( ! is_admin() ) {
			return;
		}

		echo '<div id="ai-assistant-chatbot-root"></div>';
	}
}
