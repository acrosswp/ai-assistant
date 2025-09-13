````markdown
# WordPress PHP AI Client Wrapper

## Overview

The WordPress PHP AI Client Wrapper is a layer of abstraction built on top of the WordPress PHP AI Client library. It provides a simplified, stable interface for interacting with AI providers like Anthropic, Google, and OpenAI without tightly coupling your plugin code to the underlying library implementation.

## Important Note on Namespace Usage

When referencing the WordPress AI Client library in your code, always use the fully qualified namespace to avoid fatal errors:

```php
// CORRECT: Use fully qualified namespace
$registry = \WordPress\AiClient\AiClient::defaultRegistry();

// INCORRECT: Will cause "Class not found" errors
$registry = AiClient::defaultRegistry();
```

This is especially important in the `Provider_Manager::setup_ai_client_registry()` method:

```php
public function setup_ai_client_registry(): void {
    try {
        // Always use the fully qualified namespace
        $registry = \WordPress\AiClient\AiClient::defaultRegistry();

        // Rest of your code...
    } catch ( \Exception $e ) {
        // Error handling...
    }
}
```

## Key Benefits

- **Simplified API**: Provides a clean, easy-to-use interface for common AI operations
- **Error Handling**: Built-in try/catch blocks with logging for robust error handling
- **Caching**: Optimized caching of provider information to improve performance
- **Abstraction**: Shields your code from changes in the underlying WordPress PHP AI Client
- **Standardization**: Consistent interface across different AI providers
- **Type Safety**: Proper type declarations for better IDE support and code quality
- **Versioning Protection**: Isolates your code from breaking changes in the WordPress PHP AI Client API

## Architecture

The wrapper consists of two main classes:

1. **`AI_Client_Wrapper`**: The core wrapper class that encapsulates the WordPress PHP AI Client functionality
2. **`AI_Client_Factory`**: A factory class for creating and managing instances of the wrapper

## Usage Examples

### Basic Setup

```php
// Get the default instance with standard providers (Anthropic, Google, OpenAI)
$ai_client = AI_Client_Factory::get_instance();

// Or create a custom instance with specific providers
$ai_client = AI_Client_Factory::create(['anthropic', 'openai']);

// Set up authentication for providers
$credentials = [
    'anthropic' => 'your-anthropic-api-key',
    'openai'    => 'your-openai-api-key',
];
AI_Client_Factory::setup_provider_authentication($credentials);
```

### Working with Providers

```php
// Get list of available providers
$available_providers = $ai_client->get_available_provider_ids();

// Check if a specific provider is available
if ($ai_client->is_provider_available('anthropic')) {
    // Use Anthropic provider
}

// Get the preferred model for a provider
$model_id = $ai_client->get_preferred_model_id('openai');
```

### Generating Text

```php
// Simple text generation
$result = $ai_client->generate_text(
    'openai',        // Provider ID
    'gpt-4-turbo',   // Model ID
    'Write a poem about WordPress plugins',  // Prompt
    [
        'temperature' => 0.7,
        'max_tokens'  => 500,
    ]
);

if ($result !== false) {
    echo $result;
}
```

### Chat Completions

```php
// Chat conversation
$messages = [
    [
        'role'    => 'system',
        'content' => 'You are a helpful assistant for WordPress users.',
    ],
    [
        'role'    => 'user',
        'content' => 'How do I create a custom post type?',
    ],
];

$result = $ai_client->chat_completion(
    'anthropic',
    'claude-sonnet-4-20250514',
    $messages,
    [
        'temperature' => 0.7,
        'max_tokens'  => 1000,
    ]
);

if ($result !== false) {
    echo $result['content'];

    // Handle function calls if present
    if (!empty($result['function_call'])) {
        $function_call = $result['function_call'];
        // Process function call...
    }
}
```

### Finding Compatible Models

```php
// Find models that support function calling
$models = $ai_client->find_models_for_requirements([
    'function_calling' => true,
]);

// Find models that support vision capabilities
$vision_models = $ai_client->find_models_for_requirements([
    'vision' => true,
]);

// Result structure:
// [
//   'openai' => [
//     'name' => 'OpenAI',
//     'models' => [
//       ['id' => 'gpt-4-turbo', 'name' => 'GPT-4 Turbo'],
//       ...
//     ]
//   ],
//   ...
// ]
```

## Error Handling

The wrapper automatically catches exceptions from the WordPress PHP AI Client and returns appropriate fallback values (false, empty arrays) to prevent your plugin from breaking. Errors are logged to the WordPress error log with the prefix "AI Assistant:" for easy identification.

## Integration with Provider Manager

The wrapper is designed to work seamlessly with the `Provider_Manager` class:

```php
class Provider_Manager {
    protected $ai_client;

    public function __construct(array $provider_ids = ['anthropic', 'google', 'openai']) {
        $this->ai_client = AI_Client_Factory::get_instance($provider_ids);
    }

    public function get_available_provider_ids(): array {
        return $this->ai_client->get_available_provider_ids();
    }

    // Other methods...
}
```

## Advanced Usage

For cases where the wrapper doesn't provide needed functionality, you can access the underlying registry:

```php
// Get the raw AiClient registry for advanced operations
$registry = $ai_client->get_registry();

// Now you can use the registry directly
// (but be aware this bypasses the wrapper's error handling)
```

## Troubleshooting

### Common Issues

1. **"No PSR-18 clients found"**: Make sure Guzzle is installed via Composer:
   ```
   composer require guzzlehttp/guzzle:^7.0
   ```

2. **Authentication Issues**: Ensure API keys are correct and properly formatted.

3. **Model Not Found**: Verify the model ID is correct for the chosen provider.

4. **PHP Version Compatibility**: The wrapper uses PHP 8.0+ features like union types (`string|bool`). If you need PHP 7.x compatibility:
   - Remove union type declarations (`string|bool` becomes just `string`)
   - Use phpdoc comments to document return types
   - Check WordPress PHP AI Client requirements (currently PHP 7.4+)

### Debugging

Enable WordPress debug logging to see detailed error messages from the wrapper:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Class Reference

### AI_Client_Wrapper

| Method | Description |
|--------|-------------|
| `__construct(array $provider_ids = [])` | Constructor |
| `get_available_provider_ids(): array` | Gets list of available providers |
| `is_provider_available(string $provider_id): bool` | Checks if a provider is configured |
| `set_provider_authentication(string $provider_id, string $api_key): bool` | Sets API key for a provider |
| `get_preferred_model_id(string $provider_id): string` | Gets recommended model ID |
| `find_models_for_requirements(array $requirements): array` | Finds models matching requirements |
| `generate_text(string $provider_id, string $model_id, string $prompt, array $options): string|bool` | Generates text |
| `chat_completion(string $provider_id, string $model_id, array $messages, array $options): array|bool` | Processes chat conversations |
| `get_registry()` | Gets raw AiClient registry |

### AI_Client_Factory

| Method | Description |
|--------|-------------|
| `get_instance(array $provider_ids = []): AI_Client_Wrapper` | Gets singleton instance |
| `create(array $provider_ids = []): AI_Client_Wrapper` | Creates new instance |
| `setup_provider_authentication(array $provider_credentials): bool` | Sets up API keys |
