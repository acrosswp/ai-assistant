<?php
namespace Ai_Assistant\Includes;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/WPBoilerplate/ai-assistant
 * @since      0.0.1
 *
 * @package    Ai_Assistant
 * @subpackage Ai_Assistant/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      0.0.1
 * @package    Ai_Assistant
 * @subpackage Ai_Assistant/includes
 * @author     WPBoilerplate <contact@wpboilerplate.com>
 */
class Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    0.0.1
	 */
	public static function activate() {
		self::create_chat_history_table();
	}

	/**
	 * Creates the custom chat history table if it does not exist.
	 */
	public static function create_chat_history_table() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'ai_assistant_chat_history';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			session_id VARCHAR(128) NOT NULL,
			text_sent LONGTEXT,
			tokens_sent INT,
			abilities_data LONGTEXT,
			tokens_received INT,
			response_text LONGTEXT,
			llm_provider VARCHAR(128),
			model VARCHAR(128),
			request_data LONGTEXT,
			response_data LONGTEXT,
			request_headers LONGTEXT,
			response_headers LONGTEXT,
			request_time DATETIME,
			response_time DATETIME,
			latency_ms INT,
			status_code INT,
			error_message LONGTEXT,
			ip_address VARCHAR(64),
			user_agent TEXT,
			referer TEXT,
			plugin_version VARCHAR(32),
			wp_version VARCHAR(32),
			site_url VARCHAR(255),
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY session_id (session_id),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
