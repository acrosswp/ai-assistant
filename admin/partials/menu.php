<?php
namespace Ai_Assistant\Admin\Partials;

use Ai_Assistant\Admin\Settings\Settings_Manager;
use Ai_Assistant\Providers\Provider_Manager;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main Menu Class for the AI Assistant plugin.
 *
 * @since      0.0.1
 * @package    Ai_Assistant\Admin\Partials\Menu
 * @subpackage Ai_Assistant\Admin\Partials
 */
class Menu {

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
	 * Settings manager instance.
	 *
	 * @since    0.0.1
	 * @var      Settings_Manager
	 */
	private $settings_manager;

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

		// Initialize settings manager
		$provider_manager       = new Provider_Manager();
		$this->settings_manager = new Settings_Manager( $provider_manager );

		// Register settings
		add_action( 'admin_init', array( $this->settings_manager, 'register_settings' ) );
	}

	/**
	 * Adds the plugin license page to the admin menu.
	 *
	 * @return void
	 */
	public function main_menu() {
		add_menu_page(
			__( 'Ai Assistant', 'ai-assistant' ),
			__( 'Ai Assistant', 'ai-assistant' ),
			'manage_options',
			'ai-assistant',
			array( $this, 'about' )
		);
	}

	/**
	 * Settings page for the plugin using WordPress Settings API
	 */
	public function about() {
		// Settings are now always registered in the constructor.
		?>
		<div class="wrap" style="max-width: 700px;">
			<h1 style="font-size:2.2em; margin-bottom: 0.5em;">AI SDK Chatbot Demo Settings</h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'ai_assistant_settings_group' );
				do_settings_sections( 'ai-assistant-settings' );
				submit_button( __( 'Save Changes', 'ai-assistant' ) );
				?>
			</form>
		</div>
		<style>
			.form-table th {
				width: 180px;
				text-align: left;
				font-size: 1.1em;
			}
			.form-table input[type="text"],
			.form-table input[type="password"] {
				width: 100%;
				max-width: 400px;
				font-size: 1em;
			}
			.form-table select {
				min-width: 180px;
				font-size: 1em;
			}
			.form-table td {
				vertical-align: middle;
			}
			.form-table .description {
				color: #666;
				font-size: 0.95em;
			}
		</style>
		<?php
	}

	/**
	 * Add Settings link to plugins area.
	 *
	 * @since    0.0.1
	 *
	 * @param array  $links Links array in which we would prepend our link.
	 * @param string $file  Current plugin basename.
	 * @return array Processed links.
	 */
	public function plugin_action_links( $links, $file ) {
		// Return normal links if not BuddyPress.
		if ( \AI_ASSISTANT_PLUGIN_BASENAME !== $file ) {
			return $links;
		}

		// Add a few links to the existing links array.
		return array_merge(
			$links,
			array(
				'about' => sprintf( '<a href="%sadmin.php?page=%s">%s</a>', admin_url(), 'ai-assistant', esc_html__( 'About', 'ai-assistant' ) ),
			)
		);
	}
}
