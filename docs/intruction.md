````markdown
# AI Assistant Plugin - Complete Setup & Usage Guide

## Quick Start Checklist

- [ ] **Install Dependencies**: Run `composer install` to install required packages
- [ ] **Activate Plugin**: Enable the AI Assistant plugin in WordPress admin
- [ ] **Configure API Keys**: Add provider credentials in WP Admin → Ai Assistant
- [ ] **Test Chatbot**: Look for "💬 Need Help?" button on admin pages
- [ ] **Verify Functionality**: Send test messages and check responses

## Detailed Setup Instructions

### 1. Prerequisites

**Required Dependencies:**
- PHP 8.0 or higher
- WordPress 5.0 or higher
- Composer (for dependency management)

**Required PHP Packages:**
- `wordpress/php-ai-client ^0.1.0` - WordPress AI client SDK
- `guzzlehttp/guzzle ^7.0` - HTTP client for API requests

### 2. Installation Process

1. **Clone and Setup**
   ```bash
   git clone https://github.com/WPBoilerplate/ai-assistant.git
   cd ai-assistant
   ```

2. **Install Composer Dependencies**
   ```bash
   composer install
   ```
   This installs:
   - WordPress PHP AI Client
   - Guzzle HTTP Client (PSR-18 implementation)
   - Required HTTP dependencies

3. **Activate Plugin**
   - Upload plugin to `/wp-content/plugins/ai-assistant/`
   - Go to WP Admin → Plugins
   - Activate "Ai Assistant"

### 3. Configuration

1. **Access Settings**
   - Navigate to WP Admin → Ai Assistant
   - You'll see the settings page with provider configuration

2. **Add API Keys** (All fields are password-protected)
   - **Anthropic API Key**: Your Claude API key
   - **Google API Key**: Your Google AI/Gemini API key
   - **OpenAI API Key**: Your OpenAI API key
   - **Current Provider**: Select which provider to use

3. **Save Settings**
   - Click "Save Changes"
   - Settings are stored securely in WordPress options

## Troubleshooting Guide

### Issue 1: Fatal Error - "Too few arguments to function Loader::add_action()"

**Symptoms:**
```
PHP Fatal error: Too few arguments to function Loader::add_action()
```

**Solution:**
The custom WordPress plugin loader expects object method callbacks, not closures. Fixed by using WordPress native `add_action()` for anonymous functions:

```php
// ❌ Wrong - causes fatal error
$this->loader->add_action('hook', function() { ... });

// ✅ Correct - use WordPress native add_action for closures
add_action('hook', function() { ... });
```

### Issue 2: Fatal Error - "Class WordPress\AiClient\AiClient not found"

**Symptoms:**
```
PHP Fatal error: Class "WordPress\AiClient\AiClient" not found
```

**Solution:**
1. Add Composer autoloader to main plugin file:
```php
if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
}
```

2. Update `composer.json` to include AI client:
```json
{
    "require": {
        "php": ">=8.0",
        "wordpress/php-ai-client": "^0.1.0"
    }
}
```

3. Run `composer update`

### Issue 3: HTTP Discovery Error - "No PSR-18 clients found"

**Symptoms:**
```
Http\Discovery\Exception\DiscoveryFailedException: Could not find resource using any discovery strategy
No PSR-18 clients found. Make sure to install a package providing "psr/http-client-implementation"
```

**Solution:**
1. Add Guzzle HTTP client to composer.json:
```json
{
    "require": {
        "php": ">=8.0",
        "wordpress/php-ai-client": "^0.1.0",
        "guzzlehttp/guzzle": "^7.0"
    }
}
```

2. Allow php-http discovery plugin:
```json
{
    "config": {
        "allow-plugins": {
            "php-http/discovery": true
        }
    }
}
```

3. Run `composer update`

### Issue 4: AiClient Initialization Timing Problems

**Symptoms:**
HTTP client errors during WordPress `init` hook

**Solution:**
Defer AiClient initialization until after WordPress is fully loaded:

```php
public function initialize_provider_credentials(): void {
    // Register settings first
    register_setting(/* ... */);

    // Defer AiClient setup to prevent timing issues
    add_action( 'wp_loaded', array( $this, 'setup_ai_client_registry' ), 20 );
}

public function setup_ai_client_registry(): void {
    try {
        $registry = AiClient::defaultRegistry();
        // Setup provider authentication...
    } catch ( \Exception $e ) {
        error_log( 'AI Assistant: Failed to initialize AiClient registry: ' . $e->getMessage() );
    }
}
```

### Issue 5: WordPress AI Client Method Errors

**Symptoms:**
```
PHP Fatal error: Call to undefined method WordPress\AiClient\Providers\ProviderRegistry::getProviderIds()
PHP Fatal error: Call to undefined method WordPress\AiClient\Providers\ProviderRegistry::getProviderModelIds()
```

**Solution:**
The WordPress AI Client v0.1.0 has a different API than expected. Fixed by:

1. **Adding constructor with provider IDs**:
```php
class Provider_Manager {
    protected array $provider_ids;

    public function __construct( array $provider_ids = array( 'anthropic', 'google', 'openai' ) ) {
        $this->provider_ids = $provider_ids;
    }
}
```

2. **Using available registry methods**:
```php
public function get_available_provider_ids(): array {
    $registry = AiClient::defaultRegistry();
    return array_values(
        array_filter(
            $this->provider_ids,
            static function ( string $provider_id ) use ( $registry ) {
                return $registry->hasProvider( $provider_id ) && $registry->isProviderConfigured( $provider_id );
            }
        )
    );
}
```

3. **Hardcoded model preferences**:
```php
public function get_preferred_model_id( string $provider_id ): string {
    switch ( $provider_id ) {
        case 'anthropic': return 'claude-sonnet-4-20250514';
        case 'google': return 'gemini-2.5-flash';
        case 'openai': return 'gpt-4-turbo';
        default: return '';
    }
}
```

### Issue 6: Translation Loading Warning

**Symptoms:**
```
Function _load_textdomain_just_in_time was called incorrectly. Translation loading for the 'ai-assistant' domain was triggered too early.
```

**Note:** This is a notice, not a fatal error. Can be resolved by ensuring text domain loading happens during or after the `init` action.

## Complete File Structure & Dependencies

### Final Working composer.json
```json
{
    "name": "wpboilerplate/ai-assistant",
    "type": "wordpress-plugin",
    "license": "GPL-2.0-or-later",
    "require": {
        "php": ">=8.0",
        "wordpress/php-ai-client": "^0.1.0",
        "guzzlehttp/guzzle": "^7.0"
    },
    "config": {
        "allow-plugins": {
            "php-http/discovery": true
        }
    },
    "autoload": {
        "psr-4": {
            "Ai_Assistant\\Includes\\": "includes/",
            "Ai_Assistant\\Admin\\": "admin/",
            "Ai_Assistant\\Public\\": "public/"
        }
    }
}
```

### Critical Code Changes Made

1. **ai-assistant.php** - Added Composer autoloader:
```php
// Load Composer autoloader
if ( file_exists( plugin_dir_path( __FILE__ ) . 'vendor/autoload.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
}
```

2. **includes/main.php** - Fixed hook registration:
```php
// ✅ Use WordPress native add_action for closures
add_action( 'rest_api_init', function () use ( $provider_manager ) {
    $chatbot_route = new \Ai_Assistant\REST_Routes\Chatbot_Messages_REST_Route(
        $provider_manager,
        'ai-assistant/v1',
        'messages'
    );
    $chatbot_route->register_route();
} );
```

3. **includes/Providers/Provider_Manager.php** - Deferred AiClient initialization:
```php
public function initialize_provider_credentials(): void {
    register_setting(/* settings registration */);

    // Defer AiClient registry setup until WordPress is fully loaded
    add_action( 'wp_loaded', array( $this, 'setup_ai_client_registry' ), 20 );
}

public function setup_ai_client_registry(): void {
    try {
        $registry = AiClient::defaultRegistry();
        // Setup authentication...
    } catch ( \Exception $e ) {
        error_log( 'AI Assistant: Failed to initialize AiClient registry: ' . $e->getMessage() );
    }
}
```

## Chatbot Implementation Features

### ✅ Completed Features

1. **Settings Page with WordPress Settings API**
   - API key fields for Anthropic, Google, and OpenAI (all password fields)
   - Provider selection dropdown (only shows providers with valid credentials)
   - Integrated with WordPress settings system

2. **REST API Routes**
   - GET `/wp-json/ai-assistant/v1/messages` - Retrieve chat history
   - POST `/wp-json/ai-assistant/v1/messages` - Send new message
   - DELETE `/wp-json/ai-assistant/v1/messages` - Reset chat history

3. **Agent System**
   - Abstract Agent base class with step execution
   - Chatbot Agent with WordPress-specific system instructions
   - Function call support for abilities/tools
   - Retry logic for failed function calls

4. **Provider Management**
   - Provider Manager class for handling AI providers
   - Automatic authentication setup with API keys
   - Dynamic provider/model selection

5. **Frontend Chatbot Interface**
   - jQuery-based chatbot widget
   - Responsive design with mobile support
   - Real-time messaging with loading indicators
   - Escape key to close, toggle button

6. **Security & Permissions**
   - Admin-only access (`manage_options` capability)
   - Nonce verification for REST API calls
   - Input sanitization and XSS protection

### 🏗️ Architecture

```
includes/
├── Agents/
│   ├── Contracts/Agent.php          # Agent interface
│   ├── Abstract_Agent.php           # Base agent functionality
│   ├── Agent_Step_Result.php        # Step execution result
│   └── Chatbot_Agent.php           # WordPress chatbot agent
├── Providers/
│   └── Provider_Manager.php        # AI provider management
├── REST_Routes/
│   └── Chatbot_Messages_REST_Route.php  # API endpoints
└── Abilities/
    └── Abilities_Registrar.php     # Register chatbot abilities
```

### 🎯 Usage Instructions

1. **Install Dependencies**
   ```bash
   composer install
   ```

2. **Configure API Keys**
   - Go to WP Admin → Ai Assistant
   - Enter API keys for desired providers (Anthropic, Google, OpenAI)
   - Select current provider from dropdown
   - Save changes

3. **Use the Chatbot**
   - Look for "💬 Need Help?" button in bottom-right corner of admin pages
   - Click to open chatbot interface
   - Type questions about WordPress, the site, or general assistance
   - Use Escape key to close or click the X button

### 🔧 API Integration

The chatbot uses the WordPress PHP AI Client SDK for provider communication:
- Supports multiple AI providers (Anthropic, Google, OpenAI)
- Function calling for WordPress abilities
- Automatic model selection per provider
- Error handling and retry logic

### 🎨 Frontend Features

- **Responsive Design**: Works on desktop and mobile
- **Real-time Chat**: Instant messaging with typing indicators
- **Persistent History**: Chat history saved per user
- **Keyboard Shortcuts**: Escape to close, Enter to send
- **Accessibility**: ARIA labels and screen reader support

## Testing the Plugin

### Verification Steps

1. **Check Plugin Activation**
   ```bash
   # Check for fatal errors in debug log
   tail -f wp-content/debug.log
   ```

2. **Access Settings Page**
   - Go to WP Admin → Ai Assistant
   - Verify all three provider fields show as password inputs
   - Verify provider dropdown appears

3. **Test Chatbot Interface**
   - Look for "💬 Need Help?" button on admin pages
   - Click to open chatbot
   - Send test message: "Hello, can you help me with WordPress?"
   - Verify response is generated

4. **API Endpoint Testing**
   ```bash
   # Test GET endpoint
   curl -X GET "http://localhost:8882/wp-json/ai-assistant/v1/messages" \
        -H "X-WP-Nonce: YOUR_NONCE"

   # Test POST endpoint
   curl -X POST "http://localhost:8882/wp-json/ai-assistant/v1/messages" \
        -H "Content-Type: application/json" \
        -H "X-WP-Nonce: YOUR_NONCE" \
        -d '{"message": "Hello"}'
   ```

### Expected Results

- ✅ No fatal errors in debug.log
- ✅ Settings page loads with password fields
- ✅ Chatbot widget appears and responds
- ✅ API endpoints return proper JSON responses
- ✅ Provider authentication works with valid API keys

## Making All Provider Fields Password Fields ✅ COMPLETED

All provider credential fields (Anthropic, Google, OpenAI) are now password fields as implemented in `admin/partials/menu.php`:

```php
// All three fields use type="password"
<input type="password" name="ai_assistant_anthropic_key" ... />
<input type="password" name="ai_assistant_google_key" ... />
<input type="password" name="ai_assistant_openai_key" ... />
```

## Final Plugin Status: ✅ FULLY FUNCTIONAL

### What's Working:
- ✅ Plugin activates without fatal errors
- ✅ Composer dependencies properly installed (WordPress AI Client + Guzzle)
- ✅ Settings page with secure password fields
- ✅ REST API endpoints for chatbot communication
- ✅ Frontend chatbot interface with responsive design
- ✅ Agent system with WordPress-specific capabilities
- ✅ Provider management with automatic authentication
- ✅ Error handling and graceful degradation

### Fixed Issues:
- ✅ Loader::add_action() fatal error - Fixed hook registration
- ✅ AiClient class not found - Added Composer autoloader
- ✅ PSR-18 HTTP client missing - Added Guzzle dependency
- ✅ AiClient initialization timing - Deferred to wp_loaded hook
- ✅ WordPress AI Client API mismatch - Updated Provider_Manager methods
- ✅ All provider fields are password type - Security implemented---

🎉 **The AI Assistant plugin is now ready for production use!** Configure your API keys and start chatting with your WordPress AI assistant.
