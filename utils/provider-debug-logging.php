<?php
/**
 * Provider Debug Logging
 *
 * This file initializes the Provider Debug Logger.
 *
 * @package AI_Assistant
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include the Provider Debug Logger class.
require_once __DIR__ . '/class-provider-debug-logger.php';

// Initialize the logger.
AI_Assistant\Utils\Provider_Debug_Logger::init();
