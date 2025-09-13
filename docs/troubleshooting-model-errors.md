# Troubleshooting AI Model Errors

This document provides guidance on how to resolve common errors related to AI models in the AI Assistant plugin.

## "No AI Model is Available" Error

If you're seeing the error message "Sorry, no AI model is available that supports your request" in the chatbot, this typically indicates an issue with AI provider configuration or model availability.

### Common Causes

1. **Missing API Keys**: No valid API keys have been configured for any provider.
2. **Function Calling Requirements**: If your request requires function calling capabilities but the configured model doesn't support it.
3. **Vision Capabilities**: If your request includes images but the model doesn't support vision capabilities.
4. **API Key Restrictions**: Your API key may have restrictions limiting access to specific models.
5. **Provider Configuration**: The provider might not be properly configured or authenticated.
6. **Service Outages**: The AI provider may be experiencing service disruptions.

### Resolution Steps

1. **Check API Keys and Provider Selection**:
   - Go to the AI Assistant settings page in your WordPress admin dashboard
   - Verify that you have entered a valid API key for at least one provider (OpenAI, Anthropic, or Google)
   - Make sure you've selected a provider in the dropdown menu
   - Save your settings after making any changes

2. **Verify API Key Validity**:
   - Check that your API key is current and has not expired
   - Verify that the API key has sufficient permissions/quota
   - Try regenerating your API key from the provider's website if necessary

3. **Check for Provider Service Outages**:
   - OpenAI: https://status.openai.com/
   - Anthropic: https://status.anthropic.com/
   - Google AI: https://status.cloud.google.com/

4. **Simplify Your Request**:
   - Try simpler queries that don't require special capabilities
   - Avoid complex instructions or multiple questions in one prompt
   - If using function calling features, try a basic question first to verify basic connectivity

5. **Network Connectivity**:
   - If your site is behind a firewall or proxy, ensure outbound connections to the AI APIs are allowed
   - Check your server's PHP configuration for outbound HTTP request limitations

6. **Enable Debug Logging**:
   - Add the following to your wp-config.php file:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```
   - Check the debug.log file in your wp-content directory for specific error messages

## Model Capability Requirements

Different features require different model capabilities:

| Feature | Required Capability | Available Models |
|---------|---------------------|------------------|
| Basic Text Generation | None | Most models |
| Function Calling | Function Calling | OpenAI: gpt-4-turbo, gpt-3.5-turbo <br> Anthropic: claude-sonnet, claude-3-opus <br> Google: gemini-1.5-pro |
| Image Analysis | Vision | OpenAI: gpt-4-vision <br> Anthropic: claude-3 models <br> Google: gemini-pro-vision |
| Embeddings | Embedding | OpenAI: text-embedding-ada <br> Various other dedicated embedding models |

## Fallback Behavior

The AI Client Wrapper now implements automatic fallback mechanisms:

1. First tries to find a model that supports all specified requirements
2. If no model is found, tries to find a model with reduced requirements (keeping function calling if needed)
3. Falls back to default recommended models per provider
4. If that fails, attempts to use a simpler fallback model from the same provider
5. As a last resort, tries to find any available model from any configured provider

## Advanced Troubleshooting

If you continue to experience issues after trying the steps above:

1. **Check PHP Requirements**:
   - PHP 8.0 or higher is recommended
   - PHP cURL extension must be enabled
   - PHP must have sufficient memory allocation (128MB+ recommended)

2. **Try a Different Provider**:
   - If one provider isn't working, try configuring a different one
   - Some providers may be more reliable in certain regions

3. **Check for Plugin Conflicts**:
   - Temporarily deactivate other plugins to see if there's a conflict
   - Some security plugins may block outgoing API connections

4. **Reset Chatbot History**:
   - Try clearing the chatbot history by clicking the "Reset" button in the chatbot interface
   - A corrupted conversation history can sometimes cause issues

5. **Server Environment**:
   - Some hosting environments may have restrictions that prevent proper functioning of AI API connections
   - Consider testing on a different hosting environment

## Getting Help

If you continue to experience issues after trying these solutions, please:

1. Gather the specific error messages from your debug log
2. Note which providers and models you've attempted to use
3. Document any recent changes to your WordPress site
4. Contact support with these details for faster resolution
