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
		 * Settings page for AI SDK Chatbot Demo
		 */
	public function about() {
		// Early bailout for permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'ai-assistant' ) );
		}

		echo '<div class="wrap">';
			echo '<h1>' . esc_html__( 'AI SDK Chatbot Demo Settings', 'ai-assistant' ) . '</h1>';

			echo '<form method="post" action="options.php">';
				settings_fields( 'ai_assistant_settings_group' );
				do_settings_sections( 'ai_assistant_settings' );
				submit_button( esc_html__( 'Save Changes', 'ai-assistant' ) );
			echo '</form>';
		echo '</div>';
	}
	/**
	 * Register settings, sections, and fields for the settings page
	 */
	public static function register_settings() {
		// Early bailout for non-admin
		if ( ! is_admin() ) {
			return;
		}

		register_setting(
			'ai_assistant_settings_group',
			'ai_assistant_settings',
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
			)
		);

		add_settings_section(
			'ai_assistant_credentials_section',
			__( 'Credentials', 'ai-assistant' ),
			function () {
				echo '<p>' . esc_html__( 'Paste your API credentials for the different providers you would like to use here.', 'ai-assistant' ) . '</p>';
			},
			'ai_assistant_settings'
		);

		add_settings_field(
			'ai_assistant_anthropic',
			__( 'Anthropic', 'ai-assistant' ),
			array( __CLASS__, 'render_text_field' ),
			'ai_assistant_settings',
			'ai_assistant_credentials_section',
			array( 'id' => 'anthropic' )
		);
		add_settings_field(
			'ai_assistant_google',
			__( 'Google', 'ai-assistant' ),
			array( __CLASS__, 'render_text_field' ),
			'ai_assistant_settings',
			'ai_assistant_credentials_section',
			array( 'id' => 'google' )
		);
		add_settings_field(
			'ai_assistant_openai',
			__( 'OpenAI', 'ai-assistant' ),
			array( __CLASS__, 'render_text_field' ),
			'ai_assistant_settings',
			'ai_assistant_credentials_section',
			array( 'id' => 'openai' )
		);

		add_settings_section(
			'ai_assistant_provider_section',
			__( 'Provider Preferences', 'ai-assistant' ),
			function () {
				echo '<p>' . esc_html__( 'Choose the provider you would like to use for the chatbot demo. Only providers with valid API credentials can be selected.', 'ai-assistant' ) . '</p>';
			},
			'ai_assistant_settings'
		);

		add_settings_field(
			'ai_assistant_current_provider',
			__( 'Current Provider', 'ai-assistant' ),
			array( __CLASS__, 'render_provider_dropdown' ),
			'ai_assistant_settings',
			'ai_assistant_provider_section'
		);
	}

	/**
	 * Sanitize settings
	 */
	public static function sanitize_settings( $input ) {
		$output                     = array();
		$output['anthropic']        = isset( $input['anthropic'] ) ? sanitize_text_field( $input['anthropic'] ) : '';
		$output['google']           = isset( $input['google'] ) ? sanitize_text_field( $input['google'] ) : '';
		$output['openai']           = isset( $input['openai'] ) ? sanitize_text_field( $input['openai'] ) : '';
		$output['current_provider'] = isset( $input['current_provider'] ) ? sanitize_text_field( $input['current_provider'] ) : 'none';
		return $output;
	}

	/**
	 * Render text field for credentials
	 */
	public static function render_text_field( $args ) {
		$options = get_option( 'ai_assistant_settings' );
		$id      = $args['id'];
		$value   = isset( $options[ $id ] ) ? esc_attr( $options[ $id ] ) : '';
		printf(
			'<input type="text" id="ai_assistant_%s" name="ai_assistant_settings[%s]" value="%s" class="regular-text" />',
			esc_attr( $id ),
			esc_attr( $id ),
			$value
		);
	}

	/**
	 * Render provider dropdown
	 */
	public static function render_provider_dropdown() {
		$options   = get_option( 'ai_assistant_settings' );
		$current   = isset( $options['current_provider'] ) ? $options['current_provider'] : 'none';
		$providers = array(
			'none'      => __( 'None', 'ai-assistant' ),
			'anthropic' => __( 'Anthropic', 'ai-assistant' ),
			'google'    => __( 'Google', 'ai-assistant' ),
			'openai'    => __( 'OpenAI', 'ai-assistant' ),
		);
		echo '<select id="ai_assistant_current_provider" name="ai_assistant_settings[current_provider]">';
		foreach ( $providers as $key => $label ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( $current, $key, false ), esc_html( $label ) );
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
