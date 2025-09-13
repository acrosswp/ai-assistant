# AI Assistant Troubleshooting Guide

## Provider Selection Not Saving

### Issue

The plugin was experiencing an issue where the selected AI provider (`ai_assistant_current_provider`) was not being saved to the database. This happened because:

1. The sanitize callback in `Provider_Manager::initialize_current_provider()` was too restrictive.
2. The sanitize callback was checking for the presence of API keys in the `ai_assistant_provider_credentials` option, but API keys were actually stored in individual options (`ai_assistant_anthropic_key`, etc.).
3. The provider IDs were being validated against `available_provider_ids` rather than just checking if they were valid provider IDs.

### Solution

The issue was fixed by:

1. Updating the `setup_ai_client_registry()` method to get credentials from the individual API key options.
2. Modifying the sanitize callback for `ai_assistant_current_provider` to accept any valid provider ID, regardless of whether it has an API key configured.
3. Enhancing the `get_current_provider_id()` method to be more resilient when no provider is available.

### Proper Configuration

For the plugin to work properly:

1. Make sure to set API keys for at least one provider in the plugin settings.
2. Select your preferred provider from the dropdown.
3. The plugin will save your selection even if you haven't added the API key yet, allowing you to set up the configuration in any order.

### Verifying Settings

You can verify that your settings are being saved correctly by using WP-CLI:

```bash
# Check the current provider setting
wp option get ai_assistant_current_provider

# Check available API keys
wp option get ai_assistant_anthropic_key
wp option get ai_assistant_google_key
wp option get ai_assistant_openai_key
```

## Empty Tool Calls Error

### Issue

The chatbot would sometimes encounter one of these errors, depending on which AI provider is being used:

```
# OpenAI error format
AI Assistant: Error in chatbot: Bad status code: 400. Invalid 'messages[2].tool_calls': empty array. Expected an array with minimum length 1, but got an empty array instead.

# Anthropic error format
AI Assistant: Error in chatbot: Bad status code: 400. tools: List should have at least 1 item after validation, not 0
```

This happened when the plugin tried to send an empty tool_calls array (OpenAI) or tools array (Anthropic) to the AI provider API.

### Solution

We fixed this by:

1. Removing debug code that was causing "headers already sent" errors
2. Updating the tool_calls handling to avoid sending empty arrays
3. Making the abilities/tools initialization safer
4. Adding an early check to skip tool_calls processing when no function calls are present

For more details, see the [Empty Tool Calls Fix](empty-tool-calls-fix.md) documentation.

### Provider-Specific Troubleshooting

#### Anthropic-Specific Issues
If you're using Anthropic (Claude) models and still seeing the "List should have at least 1 item after validation, not 0" error:

1. Make sure you're using the latest version of the PHP AI Client library
2. Check that your model selection is correct (claude-sonnet-4 is recommended)
3. Try temporarily disabling any abilities/tools in the Chatbot_Agent implementation
4. If issues persist, use `error_log()` statements to track what's being sent to the API

#### OpenAI-Specific Issues
If you're using OpenAI models and still seeing tool_calls errors:

1. Verify that your model supports function calling (gpt-4-turbo is recommended)
2. Check that the tool format matches OpenAI's requirements
3. Consider switching to a different provider temporarily to isolate the issue

## Debugging Provider Configuration

If you're having issues with provider configuration, the plugin now logs detailed information about the process. Check your server's error log for messages beginning with "AI Assistant:".

Common log messages include:
- "AI Assistant: Getting current provider ID: [provider]"
- "AI Assistant: Sanitizing provider ID: [provider]"
- "AI Assistant: Successfully configured provider [provider]"
- "AI Assistant: Provider [provider] not properly configured despite setting API key"

These logs can help diagnose issues with provider configuration and selection.
