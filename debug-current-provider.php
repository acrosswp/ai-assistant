<?php
/**
 * Debug script to check the current provider option value directly from the database.
 *
 * To use this script, visit it directly in your browser or run it from the command line:
 * php debug-current-provider.php
 */

// Load WordPress
require_once dirname( __DIR__, 3 ) . '/wp-load.php';

// Check if the user is logged in and has admin capabilities
if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) {
	die( 'You need to be an administrator to run this script.' );
}

echo '<h1>AI Assistant Provider Debug</h1>';

// Get the option directly
$option_value = get_option( 'ai_assistant_current_provider' );
echo '<h2>Current Provider Option Value:</h2>';
echo '<pre>';
var_dump( $option_value );
echo '</pre>';

// Get the option directly from the database
global $wpdb;
$db_value = $wpdb->get_var(
	$wpdb->prepare(
		"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
		'ai_assistant_current_provider'
	)
);

echo '<h2>Database Value:</h2>';
echo '<pre>';
var_dump( $db_value );
echo '</pre>';

// Check if the option exists
$option_exists = $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name = %s",
		'ai_assistant_current_provider'
	)
);

echo '<h2>Option Exists in Database:</h2>';
echo '<pre>';
var_dump( (bool) $option_exists );
echo '</pre>';

// Get provider credentials
$credentials = get_option( 'ai_assistant_provider_credentials' );
echo '<h2>Provider Credentials:</h2>';
echo '<pre>';
var_dump( $credentials );
echo '</pre>';

// List all available providers
require_once __DIR__ . '/Providers/Provider_Manager.php';
$provider_manager    = new \Ai_Assistant\Providers\Provider_Manager();
$available_providers = $provider_manager->get_available_provider_ids();

echo '<h2>Available Providers:</h2>';
echo '<pre>';
var_dump( $available_providers );
echo '</pre>';
