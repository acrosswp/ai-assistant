# Provider Debug Logging

This document explains how to use the provider debug logging feature to troubleshoot provider-specific issues with the AI Assistant plugin.

## Overview

The provider debug logging feature logs all API requests and responses to the WordPress error log. This is useful for troubleshooting provider-specific issues, such as:

- Authentication problems
- Tool/function call errors
- Model compatibility issues
- Unexpected responses

## Enabling Debug Logging

There are two ways to enable provider debug logging:

### Method 1: Via the Settings Page

1. Go to **AI Assistant** in the WordPress admin menu
2. Scroll down to the **Debugging** section
3. Check the **Enable Provider Debugging** checkbox
4. Click **Save Changes**

### Method 2: Via wp-config.php

Add the following constant to your `wp-config.php` file:

```php
define('AI_ASSISTANT_DEBUG_PROVIDERS', true);
```

This method is recommended for development environments as it will persist even if plugin settings are reset.

## Viewing Debug Logs

The debug logs are written to the WordPress error log. The location of this log depends on your server configuration:

- If using the default WordPress error log: `wp-content/debug.log`
- If using a custom error log location defined in `wp-config.php`: Check your custom location
- If using a hosting provider: Check your hosting control panel for error log access

Each log entry is prefixed with `[AI Assistant Provider Debug]` for easy filtering.

## Log Format

Each API request and response is logged with the following information:

### Request Logs
- Provider ID (openai, anthropic, etc.)
- Complete request data (API keys and sensitive data are redacted)

### Response Logs
- Provider ID
- Complete response data or error message

## Troubleshooting Provider-Specific Issues

### OpenAI Issues

For OpenAI-specific issues, look for:
- Invalid function_call format
- Incorrect model parameters
- Token limit exceeded errors

### Anthropic Issues

For Anthropic-specific issues, look for:
- Incorrect tool format (different from OpenAI's format)
- Model compatibility issues
- Authentication errors

### Google Issues

For Google-specific issues, look for:
- API key format issues
- Region restrictions
- Rate limiting errors

## Security Considerations

The debug logger automatically redacts sensitive information such as API keys, but you should still be careful when sharing logs. Review logs before sharing to ensure no sensitive information is included.

## Performance Impact

Debug logging can have a performance impact, especially with large responses. It is recommended to disable debug logging in production environments.

## Need More Help?

If you need more help troubleshooting provider-specific issues, check the provider-specific documentation:

- [OpenAI API Documentation](https://platform.openai.com/docs/api-reference)
- [Anthropic API Documentation](https://docs.anthropic.com/claude/reference/getting-started-with-the-api)
- [Google AI API Documentation](https://ai.google.dev/docs)
