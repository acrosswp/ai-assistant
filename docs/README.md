# AI Assistant Documentation

This folder contains documentation for the AI Assistant plugin.

## Available Guides

- [Troubleshooting Guide](troubleshooting-guide.md) - Solutions for common issues
  - Provider selection not saving to database
  - Empty tool_calls array errors
  - Debugging provider configuration
  - Verifying settings

- [Empty Tool Calls Fix](empty-tool-calls-fix.md) - Detailed explanation of the fix for tool_calls errors

## Plugin Overview

The AI Assistant plugin provides integration with various AI providers (Anthropic, Google, OpenAI) to power WordPress features. It allows users to:

1. Configure API keys for different providers
2. Select a preferred provider
3. Use AI-powered features in WordPress

## Configuration

The plugin settings can be found in the WordPress admin under "AI Assistant".

For technical details on how the plugin works with the WordPress PHP AI Client, see the code in:
- `includes/Providers/Provider_Manager.php` - Manages provider configuration
- `includes/AI_Client/AI_Client_Wrapper.php` - Wraps the WordPress PHP AI Client
- `includes/Agents/Chatbot_Agent.php` - Handles AI chat functionality
- `includes/REST_Routes/Chatbot_Messages_REST_Route.php` - REST API for chatbot
