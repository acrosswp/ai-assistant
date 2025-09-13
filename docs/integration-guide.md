# PHP AI Client Integration Guide

This document provides an overview of how to integrate the WordPress PHP AI Client library into your WordPress plugin or theme, using the AI Client Wrapper pattern for improved maintainability and error handling.

## Architecture Overview

The AI Assistant plugin uses a three-layer architecture for AI functionality:

1. **WordPress PHP AI Client** - The core SDK from WordPress for communicating with AI providers
2. **AI Client Wrapper** - A custom abstraction layer that simplifies the SDK interface and handles errors
3. **Provider Manager** - A plugin-specific layer that manages provider configurations and preferences

This architecture provides several benefits:
- Isolates the plugin from breaking changes in the WordPress PHP AI Client API
- Centralizes error handling and logging
- Provides a simpler, more WordPress-friendly interface
- Makes testing and maintenance easier

## Integration Steps

### 1. Install Dependencies

Add the required dependencies to your `composer.json`:

```json
{
    "require": {
        "wordpress/php-ai-client": "^0.1.0",
        "guzzlehttp/guzzle": "^7.0"
    }
}
```

Then run:

```bash
composer install
```

### 2. Implement the AI Client Wrapper

Create a wrapper class that provides a simplified interface to the WordPress PHP AI Client:

```php
// includes/AI_Client/AI_Client_Wrapper.php
namespace YourPlugin\Includes\AI_Client;

use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Http\DTO\ApiKeyRequestAuthentication;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\AiClient\Providers\Models\DTO\ModelRequirements;
use Exception;

class AI_Client_Wrapper {
    // See the AI_Client_Wrapper.php in the AI Assistant plugin for a complete implementation
    // ...
}
```

### 3. Implement a Factory Class

Create a factory class to manage instances of your wrapper:

```php
// includes/AI_Client/AI_Client_Factory.php
namespace YourPlugin\Includes\AI_Client;

class AI_Client_Factory {
    private static $instance = null;

    public static function get_instance(array $provider_ids = []) {
        if (null === self::$instance) {
            self::$instance = new AI_Client_Wrapper($provider_ids);
        }
        return self::$instance;
    }

    // Additional factory methods...
}
```

### 4. Create a Provider Manager

Implement a class to manage provider credentials and settings:

```php
// includes/Providers/Provider_Manager.php
namespace YourPlugin\Providers;

use YourPlugin\Includes\AI_Client\AI_Client_Factory;

class Provider_Manager {
    // Provider management code...

    public function get_available_provider_ids(): array {
        return $this->ai_client->get_available_provider_ids();
    }

    // Additional provider management methods...
}
```

### 5. Initialize on Plugin Load

Initialize your provider manager and set up hooks:

```php
// main plugin file
function init_ai_functionality() {
    $provider_manager = new \YourPlugin\Providers\Provider_Manager();

    // Set up settings
    add_action('init', [$provider_manager, 'initialize_provider_credentials']);

    // Set up REST routes or other functionality
    // ...
}
add_action('plugins_loaded', 'init_ai_functionality');
```

## Error Handling

The wrapper should handle all exceptions from the WordPress PHP AI Client, log them appropriately, and return sensible fallback values:

```php
try {
    // Call to WordPress PHP AI Client
} catch (Exception $e) {
    error_log('YourPlugin: Error in AI Client: ' . $e->getMessage());
    return false; // Or other appropriate fallback
}
```

## Testing

Create test cases for your wrapper:

1. Test provider availability detection
2. Test with valid and invalid API keys
3. Test text generation and chat completion
4. Test error handling

## PHP Version Considerations

The WordPress PHP AI Client requires PHP 7.4 or higher. If you're using PHP 8+, you can use union types in your wrapper. For PHP 7.4 compatibility, use PHPDoc comments to document return types.

## Security Considerations

- Store API keys securely using the WordPress options API
- Use nonces for AJAX requests
- Sanitize all user inputs
- Validate API responses before processing them
