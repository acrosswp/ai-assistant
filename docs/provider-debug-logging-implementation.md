# Provider Debug Logging Implementation

This document describes the implementation of the Provider Debug Logging feature in the AI Assistant plugin.

## Overview

The Provider Debug Logging feature allows developers to log all API requests and responses between the AI Assistant plugin and the various AI providers (OpenAI, Anthropic, Google). This is essential for troubleshooting provider-specific issues, particularly related to function/tool calls.

## Implementation Details

### 1. Debug Logger Class

The core of the implementation is the `Provider_Debug_Logger` class located in:
```
/wp-content/plugins/ai-assistant/utils/class-provider-debug-logger.php
```

This class:
- Hooks into WordPress filters to intercept provider API requests and responses
- Sanitizes sensitive data like API keys
- Formats and logs the information to the WordPress error log

### 2. Integration Points

The debug logger is integrated at several key points:

#### Main Plugin Initialization
The logger is loaded during the chatbot initialization in `includes/main.php`:

```php
private function init_chatbot() {
    // Initialize provider debug logging
    if ( file_exists( $this->plugin_dir . 'utils/provider-debug-logging.php' ) ) {
        require_once $this->plugin_dir . 'utils/provider-debug-logging.php';
    }

    // ... rest of initialization
}
```

#### Provider Manager Integration
The `Provider_Manager` class has been enhanced to add hooks for debugging:

```php
protected function add_provider_debug_hooks(): void {
    // Add filters to intercept requests/responses for the PHP AI Client
    add_filter( 'ai_client_pre_provider_request', array( $this, 'debug_provider_request' ), 10, 3 );
    add_filter( 'ai_client_post_provider_response', array( $this, 'debug_provider_response' ), 10, 4 );
}
```

These hooks translate between the PHP AI Client's internal hooks and our plugin's hooks.

#### Settings Integration
A new debugging section has been added to the plugin settings in `admin/partials/menu.php`, allowing users to enable debug logging through the UI.

### 3. Empty Tool Calls Fix

In addition to the debug logging, we've made an important fix in the `Abstract_Agent` class to prevent empty tool_calls arrays from being sent to providers:

```php
// If the message has no function calls, early return to avoid empty tool_calls array
if ( ! $has_function_calls ) {
    return array( $function_calls, $invalid_function_call_names );
}
```

This fix helps prevent the common error: "Invalid 'messages[2].tool_calls': empty array" with OpenAI and "tools: List should have at least 1 item" with Anthropic.

### 4. User Documentation

Comprehensive user documentation has been added in:
```
/wp-content/plugins/ai-assistant/docs/provider-debug-logging.md
```

This documentation includes:
- How to enable debug logging (both via settings and wp-config.php)
- How to view and interpret the logs
- Provider-specific troubleshooting guidance

## Activation Methods

The debug logging can be activated in two ways:

1. **Via Settings UI**: Users can check the "Enable Provider Debugging" checkbox in the AI Assistant settings page.

2. **Via wp-config.php**: Developers can add this constant to wp-config.php:
   ```php
   define('AI_ASSISTANT_DEBUG_PROVIDERS', true);
   ```

## Log Format

The logs follow this format:

```
[AI Assistant Provider Debug] Provider Request (openai): {...request data...}
[AI Assistant Provider Debug] Provider Response (openai): {...response data...}
```

## Security Considerations

To ensure security:
- All API keys and sensitive authentication data are automatically redacted in the logs
- Debug logging can be disabled in production environments

## Next Steps

Consider future enhancements:
- Add provider-specific log filtering
- Implement a dedicated log viewer in the admin dashboard
- Add log rotation to prevent large log files
