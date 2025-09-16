# WordPress AI Assistant: Comprehensive Documentation

> **Important:**
>
> **Do not make any changes directly in the `vendor/` folder.**
>
> The `vendor/` directory is managed by Composer and all dependencies (including the WordPress PHP AI Client) must only be updated or changed by running Composer commands (e.g., `composer update` or `composer require`).
>
> Any manual edits to files in `vendor/` will be lost the next time Composer installs or updates dependencies. Always contribute fixes upstream or use Composer to manage dependency versions.

## Table of Contents

1. [Introduction](#introduction)
2. [Plugin Structure](#plugin-structure)
3. [Agent System Architecture](#agent-system-architecture)
4. [Core Components](#core-components)
   - [Agent Interface](#agent-interface)
   - [Abstract Agent](#abstract-agent)
   - [Agent Step Result](#agent-step-result)
   - [Chatbot Agent](#chatbot-agent)
5. [AI Client Integration](#ai-client-integration)
   - [AI Client Wrapper](#ai-client-wrapper)
   - [AI Client Factory](#ai-client-factory)
6. [Abilities System](#abilities-system)
   - [Abstract Ability](#abstract-ability)
   - [Available Abilities](#available-abilities)
   - [Abilities Registration](#abilities-registration)
   - [Adding New Abilities](#adding-new-abilities)
7. [REST API Integration](#rest-api-integration)
   - [Chatbot Messages REST Route](#chatbot-messages-rest-route)
   - [Authentication and Permissions](#authentication-and-permissions)
8. [Frontend Implementation](#frontend-implementation)
   - [Chatbot JavaScript](#chatbot-javascript)
   - [CSS Styling](#css-styling)
9. [Build System](#build-system)
   - [NPM Scripts](#npm-scripts)
   - [Webpack Configuration](#webpack-configuration)
   - [Composer Dependencies](#composer-dependencies)
10. [Settings and Administration](#settings-and-administration)
    - [Settings Manager](#settings-manager)
    - [Provider Management](#provider-management)
    - [Model Selection](#model-selection)
11. [WordPress Integration](#wordpress-integration)
    - [Plugin Hooks](#plugin-hooks)
    - [WordPress Coding Standards](#wordpress-coding-standards)
12. [Troubleshooting](#troubleshooting)
13. [Best Practices](#best-practices)

## Introduction

The WordPress AI Assistant plugin provides an agent-based architecture that integrates powerful AI models with WordPress functionality. It allows AI models to understand user requests, make decisions, and execute WordPress tasks through a conversational interface. The plugin supports multiple AI providers (OpenAI, Anthropic, Google) and offers a framework for extending capabilities through an abilities system.

## Plugin Structure

The plugin follows a well-organized directory structure:

```
ai-assistant/
├── admin/                    # Admin-specific code
│   ├── settings/             # Settings management
│   ├── services/             # Admin services
│   └── partials/             # Admin UI templates
├── build/                    # Compiled assets
│   ├── css/                  # Compiled CSS
│   ├── js/                   # Compiled JavaScript
│   └── media/                # Compiled media assets
├── docs/                     # Documentation files
├── includes/                 # Core plugin functionality
│   ├── Abilities/            # WordPress abilities
│   ├── Agents/               # AI agent system
│   │   └── Contracts/        # Agent interfaces
│   ├── AI_Client/            # AI client wrapper
│   ├── Providers/            # AI provider management
│   └── REST_Routes/          # REST API endpoints
├── languages/                # Internationalization
├── public/                   # Public-facing code
│   └── partials/             # Public UI templates
├── src/                      # Source assets
│   ├── js/                   # JavaScript source
│   ├── scss/                 # SCSS source
│   └── media/                # Media source files
├── vendor/                   # Composer dependencies
├── ai-assistant.php          # Plugin bootstrap file
├── composer.json             # Composer configuration
├── package.json              # NPM configuration
└── webpack.config.js         # Webpack configuration
```

## Agent System Architecture

The agent system follows a layered architecture:

```
User Request → REST API → Chatbot_Agent → AI Model → Function Calls → WordPress Abilities → Response
```

The workflow is as follows:

1. A user sends a request through the REST API
2. The system instantiates a `Chatbot_Agent` with the necessary abilities
3. The agent sends the conversation to the AI model with function declarations
4. The AI model responds with function calls or direct responses
5. The agent executes any function calls using WordPress abilities
6. Results are returned to the user in a conversational format

## Core Components

### Agent Interface

The `Agent` interface (`includes/Agents/Contracts/Agent.php`) defines the contract for all agent implementations:

```php
interface Agent {
    public function step(): Agent_Step_Result;
}
```

This single method represents one cycle of agent reasoning, including sending the conversation to the AI model, processing responses, executing function calls, and managing the conversation history.

### Abstract Agent

The `Abstract_Agent` class (`includes/Agents/Abstract_Agent.php`) implements the core agent functionality:

```php
abstract class Abstract_Agent implements Agent {
    private array $abilities_map;
    private array $trajectory;
    private int $current_step_index = 0;
    private array $options;

    public function __construct(array $abilities, array $trajectory, array $options = array()) {
        // Initialize abilities, trajectory, and options
    }

    final public function step(): Agent_Step_Result {
        // Core agent execution logic
    }

    abstract protected function prompt_llm(PromptBuilder $prompt): Message;
    abstract protected function is_finished(array $new_messages): bool;

    // Additional helper methods
}
```

Key methods include:
- `step()`: Executes one step of agent reasoning
- `prompt_llm()`: Abstract method for AI model communication
- `is_finished()`: Abstract method to determine task completion
- `extract_function_call_abilities()`: Processes function calls from AI responses
- `execute_function_call()`: Executes WordPress abilities based on function calls
- `get_function_declarations()`: Generates function declarations for the AI model

### Agent Step Result

The `Agent_Step_Result` class (`includes/Agents/Agent_Step_Result.php`) encapsulates the result of a single agent step:

```php
class Agent_Step_Result {
    private int $step_index;
    private bool $finished;
    private array $new_messages;

    public function __construct(int $step_index, bool $finished, array $new_messages) {
        // Initialize properties
    }

    // Getter methods
    public function get_step_index(): int
    public function finished(): bool
    public function get_new_messages(): array
    public function last_message(): ?Message
}
```

### Chatbot Agent

The `Chatbot_Agent` class (`includes/Agents/Chatbot_Agent.php`) extends `Abstract_Agent` to provide a complete chatbot implementation:

```php
class Chatbot_Agent extends Abstract_Agent {
    private Provider_Manager $provider_manager;

    public function __construct(Provider_Manager $provider_manager, array $abilities, array $trajectory, array $options = array()) {
        parent::__construct($abilities, $trajectory, $options);
        $this->provider_manager = $provider_manager;
    }

    public function check_model_supports_function_calling($provider_id, $model_id) {
        // Check if a model supports function calling
    }

    protected function prompt_llm(PromptBuilder $prompt): Message {
        // Send messages to AI model with error handling
    }

    protected function is_finished(array $new_messages): bool {
        // Determine if agent has completed its task
    }

    protected function get_system_instruction(): string {
        // Generate context-aware system instructions
    }
}
```

The `Chatbot_Agent` handles:
- Provider and model selection
- Function calling compatibility checks
- Error handling and retries
- System instructions with WordPress context
- Model fallbacks when errors occur

## AI Client Integration

### AI Client Wrapper

The `AI_Client_Wrapper` class (`includes/AI_Client/AI_Client_Wrapper.php`) provides a simplified interface to the WordPress PHP AI Client:

```php
class AI_Client_Wrapper {
    private array $provider_ids = array('anthropic', 'google', 'openai');
    private $registry;
    private array $provider_models = array();

    public function __construct(array $provider_ids = array()) {
        // Initialize the wrapper
    }

    // Provider management
    public function get_available_provider_ids(): array
    public function is_provider_available(string $provider_id): bool
    public function set_provider_authentication(string $provider_id, string $api_key): bool

    // Model management
    public function get_preferred_model_id(string $provider_id, array $requirements = array()): string
    public function find_models_for_requirements(array $requirements = array()): array

    // AI operations
    public function generate_text(string $provider_id, string $model_id, string $prompt, array $options = array()): string|bool
    public function chat_completion(string $provider_id, string $model_id, array $messages, array $options = array()): array|bool

    // Advanced usage
    public function get_registry()
}
```

The wrapper handles:
- Provider authentication and availability
- Model selection based on requirements
- Chat completions with function calling
- Error handling and fallbacks
- Caching of provider information

### AI Client Factory

The `AI_Client_Factory` class (`includes/AI_Client/AI_Client_Factory.php`) provides a factory for creating and managing `AI_Client_Wrapper` instances:

```php
class AI_Client_Factory {
    private static $instance = null;

    public static function get_instance(array $provider_ids = array())
    public static function create(array $provider_ids = array())
    public static function setup_provider_authentication(array $provider_credentials)
}
```

## Abilities System

### Abstract Ability

The `Abstract_Ability` class (`includes/Abilities/Abstract_Ability.php`) extends WordPress's `WP_Ability` to provide a consistent interface for AI-executable actions:

```php
abstract class Abstract_Ability extends WP_Ability {
    public function __construct(string $name, array $properties = array()) {
        // Initialize the ability
    }

    public function get_function_declaration(): FunctionDeclaration {
        // Generate function declaration for AI models
    }

    // Abstract methods
    abstract protected function description(): string;
    abstract protected function input_schema(): array;
    abstract protected function output_schema(): array;
    abstract protected function execute_callback($args);
    abstract protected function permission_callback($args);
}
```

### Available Abilities

The plugin includes several built-in abilities:

1. **Create_Post_Draft_Ability**: Creates a draft post with title, content, excerpt, and featured image
2. **Publish_Post_Ability**: Publishes an existing draft post
3. **Get_Post_Ability**: Retrieves information about a specific post
4. **Search_Posts_Ability**: Searches for posts based on criteria
5. **Generate_Post_Featured_Image_Ability**: Generates a featured image for a post
6. **Set_Permalink_Structure_Ability**: Sets the permalink structure for the site
7. **Install_Plugin_Ability**: Installs a plugin from the WordPress.org repository
8. **Activate_Plugin_Ability**: Activates an installed plugin
9. **Get_Active_Plugins_Ability**: Lists all active plugins on the site


### Abilities Registration

The `Abilities_Registrar` class (`includes/Abilities/Abilities_Registrar.php`) registers all abilities with unique slugs. **As of the latest update, all abilities are registered using the `wp-ai-sdk-chatbot-demo/` prefix to ensure global uniqueness and compatibility with the registry pattern.**

```php
class Abilities_Registrar {
    public function register_abilities(): void {
        \wp_register_ability(
            'wp-ai-sdk-chatbot-demo/get-post',
            array(
                'label'         => __('Get Post', 'ai-assistant'),
                'ability_class' => Get_Post_Ability::class,
            )
        );
        // ...register all other abilities with the 'wp-ai-sdk-chatbot-demo/' prefix
    }
}
```

**Important:** When retrieving abilities in your code, always use the full registry slug, e.g.:

```php
$ability = wp_get_ability('wp-ai-sdk-chatbot-demo/get-post');
```

This ensures you get the shared, globally configured instance from the registry, not a new object.

### Adding New Abilities

To add a new ability:

1. Create a class extending `Abstract_Ability`
2. Implement required methods (description, input_schema, output_schema, execute_callback, permission_callback)
3. Register the ability in `Abilities_Registrar` **using the `wp-ai-sdk-chatbot-demo/your-ability-slug` format**
4. Add it to the abilities array in `Chatbot_Messages_REST_Route` using `wp_get_ability('wp-ai-sdk-chatbot-demo/your-ability-slug')`

## REST API Integration

### Chatbot Messages REST Route

The `Chatbot_Messages_REST_Route` class provides the core REST API endpoints for chatbot interactions:

```php
class Chatbot_Messages_REST_Route {
    private Provider_Manager $provider_manager;
    protected string $rest_namespace;
    protected string $rest_base;

    public function __construct(Provider_Manager $provider_manager, string $rest_namespace, string $rest_base) {
        // Initialize the route
    }

    public function register_route(): void {
        // Register REST endpoints
    }

    // Endpoint handlers
    public function get_messages(): WP_REST_Response
    public function send_message(WP_REST_Request $request): WP_REST_Response
    public function reset_messages(): WP_REST_Response

    // Helper methods
    protected function get_messages_history(): array
    protected function prepare_message_instances(array $messages): array

    // OpenAI Thread Management (New in v1.1)
    protected function get_or_create_openai_thread(string $session_id): array
    protected function create_openai_thread_message(string $thread_id, string $content, string $role): array
    protected function get_openai_thread_messages(string $thread_id): array
}
```

### OpenAI Threads and Messages Integration

Starting with version 1.1, the plugin includes full support for OpenAI's Threads and Messages API, providing persistent conversation tracking and improved context management.

#### Key Features

1. **Automatic Thread Management**: Each chat session automatically creates and manages an OpenAI thread
2. **Message Persistence**: All messages are stored both locally and in OpenAI threads
3. **Context Continuity**: Conversations maintain context across multiple interactions
4. **Database Tracking**: Thread and message IDs are stored in the chat history for reference

#### Implementation Details

The plugin extends the existing chat history table with two new columns:
- `thread_id`: Stores the OpenAI thread ID for conversation tracking
- `message_id`: Stores the OpenAI message ID for individual message reference

```sql
ALTER TABLE wp_ai_assistant_chat_history
ADD COLUMN thread_id VARCHAR(255) NULL,
ADD COLUMN message_id VARCHAR(255) NULL;
```

#### OpenAI Thread Workflow

When a user sends a message with OpenAI as the provider:

1. **Thread Creation/Retrieval**: The system checks if a thread exists for the session ID, creates one if needed
2. **Message Creation**: User messages are added to the OpenAI thread
3. **AI Response**: The AI processes the thread and generates responses
4. **Database Storage**: Both thread ID and message ID are stored in the chat history

#### API Methods

##### get_or_create_openai_thread()
```php
protected function get_or_create_openai_thread(string $session_id): array {
    // Check for existing thread in chat history
    // If not found, create new OpenAI thread
    // Return array with success status and thread_id
}
```

##### create_openai_thread_message()
```php
protected function create_openai_thread_message(string $thread_id, string $content, string $role): array {
    // Create message in OpenAI thread
    // Handle API errors gracefully
    // Return array with success status and message_id
}
```

##### get_openai_thread_messages()
```php
protected function get_openai_thread_messages(string $thread_id): array {
    // Retrieve all messages from OpenAI thread
    // Format messages for display
    // Handle pagination if needed
}
```

#### Error Handling

The OpenAI integration includes comprehensive error handling:

- **Thread Creation Errors**: Falls back to standard conversation flow
- **Message Creation Errors**: Continues with local message storage
- **API Rate Limits**: Implements retry logic with exponential backoff
- **Authentication Errors**: Provides clear error messages

#### Configuration

OpenAI thread support is automatically enabled when:
1. OpenAI is selected as the provider
2. A valid OpenAI API key is configured
3. The model supports the threads API

No additional configuration is required - the feature works transparently with existing chatbot functionality.
```

The class provides endpoints for:
- Getting the conversation history (`GET /ai-assistant/v1/messages`)
- Sending a new message (`POST /ai-assistant/v1/messages`)
- Resetting the conversation (`DELETE /ai-assistant/v1/messages`)

### Authentication and Permissions

The REST API endpoints are secured with WordPress nonce validation and capability checks:

```php
public function check_permissions() {
    if (!current_user_can('manage_options')) {
        return new WP_Error(
            'rest_cannot_access_chatbot',
            esc_html__('Sorry, you are not allowed to access the chatbot.', 'ai-assistant'),
            is_user_logged_in() ? 403 : 401
        );
    }
    return true;
}
```

## Frontend Implementation

### Chatbot JavaScript

The frontend chatbot implementation (`src/js/chatbot.js`) uses jQuery to create an interactive chat interface:

```javascript
const AiAssistantChatbot = {
    init: function() {
        // Initialize the chatbot
    },

    createChatbotInterface: function() {
        // Create the chat UI
    },

    addStyles: function() {
        // Add CSS styles
    },

    bindEvents: function() {
        // Bind event handlers
    },

    // Chat functionality
    toggleChatbot: function() {},
    closeChatbot: function() {},
    sendMessage: function() {},
    addMessage: function(content, type) {},
    loadMessageHistory: function() {},
    callChatbotAPI: function(message) {},
    clearChatHistory: function() {},

    // Helper methods
    showLoading: function() {},
    hideLoading: function() {},
    scrollToBottom: function() {},
    escapeHtml: function(text) {}
};
```

Key features include:
- Floating chat interface with toggle button
- Real-time message display
- Loading indicators
- Message history management
- REST API integration
- Error handling
- Mobile responsiveness

### CSS Styling

The chatbot includes embedded CSS for styling the interface:

```css
.ai-assistant-toggle {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #0073aa;
    color: white;
    /* Additional styling */
}

.ai-assistant-chatbot {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 350px;
    height: 500px;
    /* Additional styling */
}

/* Message styling, animations, responsive design */
```

## Build System

### NPM Scripts

The plugin uses npm scripts (`package.json`) for build processes:

```json
"scripts": {
    "build": "wp-scripts build",
    "format": "wp-scripts format",
    "lint:css": "wp-scripts lint-style",
    "lint:js": "wp-scripts lint-js",
    "packages-update": "wp-scripts packages-update",
    "plugin-zip": "wp-scripts plugin-zip",
    "start": "wp-scripts start"
}
```

### Webpack Configuration

The webpack configuration (`webpack.config.js`) extends WordPress's default configuration:

```javascript
module.exports = {
    ...defaultConfig,
    ...{
        entry: {
            ...getWebpackEntryPoints(),
            ...blockStylesheets(),
            'js/frontend': path.resolve(process.cwd(), 'src/js', 'frontend.js'),
            'js/backend': path.resolve(process.cwd(), 'src/js', 'backend.js'),
            'css/frontend': path.resolve(process.cwd(), 'src/scss', 'frontend.scss'),
            'css/backend': path.resolve(process.cwd(), 'src/scss', 'backend.scss')
        },
        plugins: [
            ...defaultConfig.plugins,
            new RemoveEmptyScriptsPlugin({
                stage: RemoveEmptyScriptsPlugin.STAGE_AFTER_PROCESS_PLUGINS
            }),
            new CopyPlugin({
                patterns: [
                    {
                        from: './src/media',
                        to: './media'
                    }
                ]
            })
        ]
    }
};
```

### Composer Dependencies

The plugin uses Composer (`composer.json`) to manage PHP dependencies:

```json
"require": {
    "php": ">=8.0",
    "wordpress/php-ai-client": "^0.1.0",
    "guzzlehttp/guzzle": "^7.0"
},
"autoload": {
    "psr-4": {
        "Ai_Assistant\\Includes\\": "includes/",
        "Ai_Assistant\\Admin\\": "admin/",
        "Ai_Assistant\\Public\\": "public/"
    }
}
```

The core dependency is `wordpress/php-ai-client`, which provides the underlying AI client functionality.

## Settings and Administration

### Settings Manager

The `Settings_Manager` class (`admin/settings/Settings_Manager.php`) manages the plugin settings:

```php
class Settings_Manager {
    private Provider_Manager $provider_manager;

    public function __construct(Provider_Manager $provider_manager) {
        // Initialize settings manager
    }

    public function register_settings() {
        // Register settings and fields
    }

    // Settings sections
    private function add_credentials_section() {}
    private function add_provider_preferences_section() {}
    private function add_debugging_section() {}

    // Field rendering
    public function provider_dropdown_field() {}
    public function model_dropdown_field() {}
}
```

The settings include:
- API credentials for OpenAI, Anthropic, and Google
- Provider selection
- Model selection with function calling compatibility checks
- Maximum token limit
- Debug options

### Provider Management

The `Provider_Manager` class (`includes/Providers/Provider_Manager.php`) manages AI provider configuration:

```php
class Provider_Manager {
    protected const OPTION_PROVIDER_CREDENTIALS = 'ai_assistant_provider_credentials';
    protected const OPTION_CURRENT_PROVIDER = 'ai_assistant_current_provider';

    protected array $provider_ids;
    protected ?array $available_provider_ids = null;
    protected $ai_client;

    public function __construct(array $provider_ids = array('anthropic', 'google', 'openai')) {
        // Initialize provider manager
    }

    // Provider management
    public function get_available_provider_ids(): array
    public function get_current_provider_id(): string
    public function get_ai_client()
    public function get_preferred_model_id(string $provider_id, array $requirements = array()): string

    // Configuration
    public function initialize_provider_credentials(): void
    public function setup_ai_client_registry(): void
    public function initialize_current_provider(): void

    // Debugging
    protected function add_provider_debug_hooks(): void
    public function debug_provider_request($request_data, $provider_id, $args)
    public function debug_provider_response($response, $request_data, $provider_id, $args)
}
```

### Model Selection

The settings include a model dropdown that:
- Shows available models for the selected provider
- Filters models based on function calling requirement
- Shows compatibility information
- Provides fallback models when API data isn't available
- Handles errors gracefully

## WordPress Integration

### Plugin Hooks

The plugin uses WordPress hooks for integration:

```php
// Main plugin class
register_activation_hook(__FILE__, 'Ai_Assistant\ai_assistant_activate');
register_deactivation_hook(__FILE__, 'Ai_Assistant\ai_assistant_deactivate');

// REST API registration
add_action('rest_api_init', function() use ($provider_manager) {
    $chatbot_route = new \Ai_Assistant\REST_Routes\Chatbot_Messages_REST_Route(
        $provider_manager,
        'ai-assistant/v1',
        'messages'
    );
    $chatbot_route->register_route();
});

// Abilities registration
add_action('abilities_api_init', function() {
    $registrar = new \Ai_Assistant\Abilities\Abilities_Registrar();
    $registrar->register_abilities();
});

// Script enqueuing
add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
```

### WordPress Coding Standards

The plugin follows WordPress coding standards:

1. **Namespaces**: `Ai_Assistant\` as the root namespace with proper sub-namespaces
2. **File Structure**: One class per file with proper naming conventions
3. **Docblocks**: Complete PHPDoc comments for classes and methods
4. **Internationalization**: Text strings wrapped in `__()` with 'ai-assistant' text domain
5. **Capability Checks**: `current_user_can()` checks before operations
6. **Sanitization**: Input sanitization and validation
7. **Error Handling**: Proper use of `WP_Error`
8. **REST API**: Following WordPress REST API standards
9. **Options API**: Using WordPress options for settings storage

## Troubleshooting

Common issues and solutions:

1. **Empty Tool Calls Error**: The plugin includes retry logic to handle cases where AI models return empty tool_calls arrays:

```php
// Check for empty tool_calls array
$parts = $message->getParts();
foreach ($parts as $part) {
    if (method_exists($part, 'getToolCalls') &&
        is_array($part->getToolCalls()) &&
        empty($part->getToolCalls())) {
        // If empty tool_calls, throw exception to retry
        throw new \Exception('Empty tool_calls detected, retrying...');
    }
}
```

2. **Model Compatibility**: The plugin checks if models support function calling:

```php
function check_model_supports_function_calling($provider_id, $model_id) {
    // Default model compatibility table
    $function_calling_support = array(
        'openai' => array(
            'gpt-4' => true,
            // Additional models
        ),
        'anthropic' => array(
            'claude-3' => true,
            // Additional models
        ),
        'google' => array(
            'gemini-1.5' => true,
            // Additional models
        ),
    );

    // Check for exact match or partial match
    // Default to false for unknown models
}
```

3. **Provider Authentication**: The plugin validates API keys and provides clear error messages:

```php
if (empty($provider_id)) {
    error_log('AI Assistant: No provider is currently selected');
    throw new \InvalidArgumentException(
        'No AI provider is configured. Please set up a provider in the settings.'
    );
}
```

## Best Practices


When working with the agent system:

1. **Always use the registry for abilities**: Retrieve all abilities via `wp_get_ability('wp-ai-sdk-chatbot-demo/your-ability-slug')` to ensure shared configuration and compatibility.
2. **Error Handling**: Always handle exceptions from agent steps, as they may occur due to API limits, model errors, or invalid function calls.
3. **Function Declarations**: Provide clear, detailed function declarations with proper input schemas to help the AI model understand how to use abilities.
4. **System Instructions**: Craft clear system instructions to guide the AI model's behavior.
5. **Message Management**: Be mindful of message history size to avoid token limits.
6. **Model Selection**: Choose appropriate models that support function calling when abilities are needed.
7. **Permission Checks**: Always implement thorough permission checks in abilities to maintain WordPress security.
8. **Testing**: Test agents with a variety of inputs and edge cases to ensure robust behavior.

By following these guidelines, you can create effective, reliable agents that enhance WordPress with AI capabilities.

## Advanced Troubleshooting, Debugging, and Configuration

### Provider Debug Logging
- Enable provider debug logging via the plugin settings (Debugging section) or by adding `define('AI_ASSISTANT_DEBUG_PROVIDERS', true);` to `wp-config.php`.
- Logs all API requests and responses to the WordPress error log, with sensitive data redacted.
- Useful for diagnosing authentication, tool/function call, and model compatibility issues.
- Disable debug logging in production for performance.

### Empty Tool Calls Fix

**Problem**: The OpenAI API returns a 400 error with message "Invalid 'messages[X].tool_calls': empty array" when receiving empty `tool_calls` arrays in messages.

**Root Cause**: The WordPress PHP AI Client library's `AbstractOpenAiCompatibleTextGenerationModel` class was always including a `tool_calls` field in message data, even when the array was empty after filtering.

**Solution**: Modified the `prepareMessagesParam` method in the AI Client library to only include `tool_calls` when there are actual tool calls present:

```php
// Build the message data
$message_data = array(
    'role'    => $this->getMessageRoleString( $message->getRole() ),
    'content' => array_values(
        array_filter(
            array_map(
                array( $this, 'getMessagePartContentData' ),
                $messageParts
            )
        )
    ),
);

// Only add tool_calls if there are actual tool calls (avoid empty arrays)
$tool_calls = array_values(
    array_filter(
        array_map(
            array( $this, 'getMessagePartToolCallData' ),
            $messageParts
        )
    )
);

if ( ! empty( $tool_calls ) ) {
    $message_data['tool_calls'] = $tool_calls;
}

return $message_data;
```

**Files Modified**:
- `vendor/wordpress/php-ai-client/src/Providers/OpenAiCompatibleImplementation/AbstractOpenAiCompatibleTextGenerationModel.php` - Fixed empty tool_calls array issue
- `includes/Agents/Abstract_Agent.php` - Contains additional safeguards
- `includes/REST_Routes/Chatbot_Messages_REST_Route.php` - Enhanced error handling for this specific case

**Error Logs Before Fix**:
```
Request failed with status 400: {
  "error": {
    "message": "Invalid 'messages[2].tool_calls': empty array. Expected non-empty array if provided.",
    "type": "invalid_request_error",
    "param": "messages[2].tool_calls",
    "code": "invalid_request_error"
  }
}
```

**Expected Result**: The chatbot now works without the empty tool_calls error, even when no function calls are needed for the user's request.

### No Models Found Error Handling
- Implements multi-level fallback if no model matches requirements: tries all requirements, then reduced, then defaults, then alternative providers.
- Improved admin notices and error messages guide users to fix configuration issues.

### Provider Selection and Settings
- Provider selection is saved even if no API key is set, allowing flexible setup order.
- Use WP-CLI to verify settings: `wp option get ai_assistant_current_provider` and API key options.
- The sanitize callback for provider selection is permissive to allow setup before API keys are entered.

### Chatbot Permissions and Customization
- By default, only users with `manage_options` can access the chatbot.
- Permissions can be customized via the `ai_assistant_chatbot_user_can_access` filter.
- Chatbot styling can be overridden with custom CSS.

### Troubleshooting and Debugging
- Enable `WP_DEBUG` and `WP_DEBUG_LOG` for detailed error messages.
- Check for common issues: missing API keys, model not found, HTTP client errors, plugin conflicts, and server restrictions.
- Use the troubleshooting guide for step-by-step solutions.

### Security Considerations
- All provider credential fields are password fields in the settings UI.
- API keys are stored securely using the WordPress options API.
- Debug logs redact sensitive data.
