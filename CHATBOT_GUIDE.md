# AI Assistant Chatbot - Setup Guide

## Overview

This WordPress plugin provides an AI-powered chatbot that appears as a floating button in the admin area. It's built using the WordPress AI SDK and supports multiple AI providers (OpenAI, Google Gemini, Anthropic Claude).

## Features

- **Modern React-based UI**: Clean, responsive chatbot interface
- **Multiple AI Providers**: Support for OpenAI, Google Gemini, and Anthropic Claude
- **Early Bailout System**: Intelligent checks to ensure AI features are only loaded when needed
- **User Permission Management**: Granular control over who can access the chatbot
- **Conversation History**: Maintains chat history per user
- **Error Handling**: Graceful error handling with user-friendly messages
- **WordPress Integration**: Seamlessly integrates with WordPress admin

## Installation & Setup

### 1. Plugin Installation
The plugin should already be installed in your WordPress environment.

### 2. Configure API Keys

1. Go to **WordPress Admin > AI Assistant**
2. Enter your API credentials for the providers you want to use:
   - **OpenAI**: Get your API key from [OpenAI Platform](https://platform.openai.com/account/api-keys)
   - **Google Gemini**: Get your API key from [Google AI Studio](https://makersuite.google.com/app/apikey)
   - **Anthropic Claude**: Get your API key from [Anthropic Console](https://console.anthropic.com/)

3. Select your **Current Provider** from the dropdown
4. Click **Save Changes**

### 3. User Permissions

By default, the chatbot is available to:
- Administrators
- Editors

To modify permissions, you can use the `ai_assistant_access_chatbot` capability.

## Usage

### Accessing the Chatbot

1. Once configured, you'll see a blue **"Need Help?"** button in the bottom-right corner of admin pages
2. Click the button to open the chatbot window
3. Type your questions and get AI-powered responses
4. Use the reset button (🔄) to clear chat history
5. Use the close button (✕) to close the chatbot

### Chat Features

- **Conversation Context**: The AI remembers previous messages in your session
- **Error Recovery**: If something goes wrong, you'll see helpful error messages
- **Responsive Design**: Works on desktop and mobile devices
- **Keyboard Shortcuts**:
  - Press `Enter` to send a message
  - Press `Shift+Enter` for new lines
  - Press `Escape` to close the chatbot

## Development

### Architecture

The plugin follows WordPress best practices with:

- **PSR-4 Namespacing**: `Ai_Assistant\` namespace structure
- **Singleton Pattern**: Main class uses `Main::instance()`
- **Hook Loader System**: Centralized hook registration
- **AI Client Wrapper**: `AI_Client_Manager` class wraps the PHP AI Client SDK
- **Early Bailout Checks**: Multiple validation layers before loading AI features

### Key Components

1. **AI_Client_Manager**: Manages AI provider configurations and API access
2. **Chatbot_Agent**: Handles conversation logic and AI interactions
3. **Chatbot_REST_Routes**: Provides REST API endpoints for the frontend
4. **User_Capabilities**: Manages user permissions
5. **React Components**: Modern frontend built with WordPress components

### File Structure

```
includes/
├── AI_Client_Manager.php     # AI client wrapper
├── Chatbot_Agent.php         # Conversation logic
├── Chatbot_REST_Routes.php   # REST API endpoints
├── User_Capabilities.php     # Permission management
└── main.php                  # Main plugin orchestrator

src/js/components/
├── ChatbotApp.js            # Main chatbot component
├── ChatbotWindow.js         # Chat window UI
├── ChatMessage.js           # Individual message component
└── LoadingIndicator.js      # Loading animation

src/scss/
├── backend.scss             # Main stylesheet
└── chatbot.scss             # Chatbot-specific styles
```

## Customization

### Filters

- `ai_assistant_enable_ai_features`: Enable/disable AI features globally
- `ai_assistant_system_instruction`: Customize the AI's system prompt
- `ai_assistant_max_message_length`: Set maximum message length (default: 2000)
- `ai_assistant_max_stored_messages`: Set maximum stored messages per user (default: 50)

### Extending Functionality

The plugin is designed to be extensible. You can:

1. Add new AI providers by extending the `AI_Client_Manager`
2. Customize the chatbot UI by modifying the React components
3. Add new capabilities and permissions
4. Integrate with other WordPress plugins

## Troubleshooting

### Common Issues

1. **Chatbot button doesn't appear**
   - Check if you have valid API credentials
   - Ensure your user role has the `ai_assistant_access_chatbot` capability
   - Verify the provider is properly selected in settings

2. **"AI features are disabled" error**
   - Confirm API keys are entered correctly
   - Check that the selected provider matches your API key
   - Ensure the `ai_assistant_enable_ai_features` filter isn't returning false

3. **Build errors**
   - Run `npm install` to install dependencies
   - Run `npm run build` to compile assets
   - Check for any JavaScript errors in the browser console

### Support

For issues and feature requests, please check the plugin documentation or contact the development team.

## Security Notes

- API keys are stored in WordPress options (encrypted recommended for production)
- All API calls go through WordPress REST API with proper nonce verification
- User permissions are checked on every request
- Input is sanitized and validated before processing

## Performance

The plugin includes several optimizations:

- **Lazy Loading**: Chatbot assets only load when needed
- **Early Bailouts**: Multiple checks prevent unnecessary loading
- **Efficient Caching**: Conversation history is limited and efficiently stored
- **Minimal Dependencies**: Uses WordPress core components where possible
