<?php
namespace Ai_Assistant\Admin\Partials;

/**
 * Ai_Assistant_Main_Menu Main Menu Class.
 *
 * @since Ai_Assistant_Main_Menu 0.0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;


/**
 * Fired during plugin licences.
 *
 * This class defines all code necessary to run during the plugin's licences and update.
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
	 * Initialize the class and set its properties.
	 *
	 * @since    0.0.1
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		// Always register settings so they are available for the settings page.
		add_action( 'admin_init', array( $this, 'register_settings' ) );
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
	 * Register plugin settings and fields
	 */
	public function register_settings() {
		register_setting( 'ai_assistant_settings_group', 'ai_assistant_anthropic_key' );
		register_setting( 'ai_assistant_settings_group', 'ai_assistant_google_key' );
		register_setting( 'ai_assistant_settings_group', 'ai_assistant_openai_key' );
		register_setting( 'ai_assistant_settings_group', 'ai_assistant_current_provider' );

		add_settings_section(
			'ai_assistant_credentials_section',
			__( 'Credentials', 'ai-assistant' ),
			function () {
				echo '<p>Paste your API credentials for the different providers you would like to use here.</p>';
			},
			'ai-assistant-settings'
		);

		add_settings_field(
			'ai_assistant_anthropic_key',
			__( 'Anthropic', 'ai-assistant' ),
			function () {
				$value = esc_attr( get_option( 'ai_assistant_anthropic_key', '' ) );
				echo '<input type="password" name="ai_assistant_anthropic_key" value="' . $value . '" autocomplete="off" />';
			},
			'ai-assistant-settings',
			'ai_assistant_credentials_section'
		);

		add_settings_field(
			'ai_assistant_google_key',
			__( 'Google', 'ai-assistant' ),
			function () {
				$value = esc_attr( get_option( 'ai_assistant_google_key', '' ) );
				echo '<input type="password" name="ai_assistant_google_key" value="' . $value . '" autocomplete="off" />';
			},
			'ai-assistant-settings',
			'ai_assistant_credentials_section'
		);

		add_settings_field(
			'ai_assistant_openai_key',
			__( 'OpenAI', 'ai-assistant' ),
			function () {
				$value = esc_attr( get_option( 'ai_assistant_openai_key', '' ) );
				echo '<input type="password" name="ai_assistant_openai_key" value="' . $value . '" autocomplete="off" />';
			},
			'ai-assistant-settings',
			'ai_assistant_credentials_section'
		);

		add_settings_section(
			'ai_assistant_provider_section',
			__( 'Provider Preferences', 'ai-assistant' ),
			function () {
				echo '<p>Choose the provider you would like to use for the chatbot demo. Only providers with valid API credentials can be selected.</p>';
			},
			'ai-assistant-settings'
		);

		add_settings_field(
			'ai_assistant_current_provider',
			__( 'Current Provider', 'ai-assistant' ),
			array( $this, 'provider_dropdown_field' ),
			'ai-assistant-settings',
			'ai_assistant_provider_section'
		);
	}

	/**
	 * Render the provider dropdown, only allowing selection of providers with valid credentials
	 */
	public function provider_dropdown_field() {
		$providers = array(
			'anthropic' => __( 'Anthropic', 'ai-assistant' ),
			'google'    => __( 'Google', 'ai-assistant' ),
			'openai'    => __( 'OpenAI', 'ai-assistant' ),
		);
		$options   = array(
			'anthropic' => get_option( 'ai_assistant_anthropic_key', '' ),
			'google'    => get_option( 'ai_assistant_google_key', '' ),
			'openai'    => get_option( 'ai_assistant_openai_key', '' ),
		);
		$current   = get_option( 'ai_assistant_current_provider', 'anthropic' );
		echo '<select name="ai_assistant_current_provider">';
		foreach ( $providers as $key => $label ) {
			$disabled = empty( $options[ $key ] ) ? 'disabled' : '';
			$selected = ( $current === $key ) ? 'selected' : '';
			echo '<option value="' . esc_attr( $key ) . '" ' . $selected . ' ' . $disabled . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
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
