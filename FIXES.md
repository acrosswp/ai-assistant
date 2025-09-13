# AI Assistant Plugin Fixes

This document outlines the fixes that were implemented to resolve various issues in the AI Assistant WordPress plugin.

## Provider Selection & Tool Calls Fixes

### 1. Provider Selection Not Saving

**Issue**: The `ai_assistant_current_provider` setting was not being saved properly due to overly restrictive sanitization callback.
**Fix**: Updated the sanitize callback in `Provider_Manager.php` to accept valid providers even without API keys.

### 2. Empty Tool Calls Error

**Issue**: Both OpenAI ("Invalid 'messages[2].tool_calls': empty array") and Anthropic ("tools: List should have at least 1 item") providers were failing when empty tool_calls arrays were sent.
**Fix**: Added early return in `extract_function_call_abilities()` method to avoid sending empty tool arrays:
```php
// If the message has no function calls, early return to avoid empty tool_calls array
if ( ! $has_function_calls ) {
    return array( $function_calls, $invalid_function_call_names );
}
```

### 3. Debug Output in REST API Route

**Issue**: Debug output using `var_dump()` was causing "headers already sent" errors.
**Fix**: Removed debug output from `Chatbot_Messages_REST_Route.php`.

## New Features

### 1. Provider Debug Logging

**Feature**: Added comprehensive provider debugging to troubleshoot provider-specific issues.
**Implementation**:
- Created `Provider_Debug_Logger` class to log all API requests and responses
- Added settings option to enable/disable debugging
- Added support for enabling via `AI_ASSISTANT_DEBUG_PROVIDERS` constant in wp-config.php
- Created documentation in `/docs/provider-debug-logging.md`

## PHP Errors Fixed

### 1. Provider_Manager.php

**Issue**: Namespace resolution error with `AiClient` class.
**Fix**: Used fully qualified namespace reference `\WordPress\AiClient\AiClient` instead of `Ai_Assistant\Providers\AiClient`.

### 2. main.php

**Issue**: Severe file corruption with syntax errors and duplicate code fragments.
**Fix**: Completely rebuilt the file with proper syntax and structure, ensuring proper hooks for the Chatbot class.

## JavaScript Errors Fixed

### 1. Chatbot.php

**Issue**: Missing `wpApiSettings` variable in JavaScript.
**Fix**:
- Ensured jQuery is properly enqueued as a dependency
- Added proper `wp_localize_script()` calls to localize:
  - `wpApiSettings` with REST API URL and nonce
  - `ajaxurl` for admin-ajax compatibility

### 2. chatbot.js

**Issue**: JavaScript couldn't access the REST API due to missing `wpApiSettings`.
**Fix**: Script now properly uses the localized `wpApiSettings` from WordPress to make authenticated REST API calls.

## Integration Fixes

### 1. Chatbot Integration

**Issue**: Chatbot class wasn't properly hooked in the `main.php` file.
**Fix**: Added proper hooks in `define_public_hooks()` method:
```php
// Initialize chatbot scripts
$chatbot = new \Ai_Assistant\Public\Chatbot( $this->get_plugin_name(), $this->get_version() );
$this->loader->add_action( 'wp_enqueue_scripts', $chatbot, 'enqueue_scripts' );
$this->loader->add_action( 'admin_enqueue_scripts', $chatbot, 'enqueue_scripts' );
```

## Additional Notes

- The AI Client wrapper now properly references the WordPress AI Client library
- The plugin should now correctly authenticate and make API calls to the selected AI provider
- Both public-facing and admin interfaces should now work correctly
- Provider debug logging can help identify provider-specific issues

## Testing

After implementing these fixes, make sure to test:

1. Admin interface at `/wp-admin/admin.php?page=ai-assistant`
2. Chatbot functionality on both admin and public-facing pages
3. API provider configuration in settings
4. REST API authentication and requests
5. Enable debug logging and check the logs when encountering provider-specific issues

If any issues persist, check the WordPress debug log for more information.
