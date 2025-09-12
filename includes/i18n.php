<?php
namespace Ai_Assistant\Includes;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Define the internationalization functionality
 *
 * @package    Ai_Assistant
 * @subpackage Ai_Assistant/includes
 */
class I18n {

	/**
	 * Actually load the plugin textdomain on `init`
	 */
	public function do_load_textdomain() {
		load_plugin_textdomain(
			'ai-assistant',
			false,
			plugin_basename( dirname( \AI_ASSISTANT_PLUGIN_FILE ) ) . '/languages/'
		);
	}
}
