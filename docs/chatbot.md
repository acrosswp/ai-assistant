# AI Assistant Chatbot

This document explains the chatbot functionality in the AI Assistant plugin, including how it's implemented and how to customize it.

## Overview

The AI Assistant chatbot provides an interactive chat interface for both admin and public-facing pages. It uses the WordPress REST API to communicate with AI providers through the plugin's wrapper architecture.

## Architecture Components

### Chatbot PHP Class

The `Chatbot.php` class in the `public` directory handles:

- Script enqueuing for the chatbot JavaScript
- Script localization for REST API authentication
- User permission checks

Key methods:
- `enqueue_scripts()`: Loads the JavaScript and adds the necessary localized variables

### JavaScript Implementation

The `chatbot.js` file in the `src/js` directory contains:
- Chat interface UI
- User interaction handling
- REST API communication
- Message formatting and display

### REST API Routes

The `Chatbot_Messages_REST_Route` class registers a REST API endpoint that:
- Accepts incoming messages from the chatbot
- Authenticates requests using WordPress nonce validation
- Processes messages through the selected AI provider
- Returns AI-generated responses

## Permissions and Security

By default, the chatbot is only shown to logged-in users with the `manage_options` capability (administrators). This is controlled in the `Chatbot::enqueue_scripts()` method:

```php
// Only load chatbot for logged-in users with appropriate permissions
if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
    return;
}
```

The REST API endpoint is also secured with WordPress nonce verification to prevent unauthorized access.

## Customization

### Changing Permissions

To modify who can access the chatbot, use the WordPress filter system to hook into the permission check:

```php
add_filter( 'ai_assistant_chatbot_user_can_access', function( $can_access, $user_id ) {
    // Allow editors and above to access the chatbot
    if ( user_can( $user_id, 'edit_posts' ) ) {
        return true;
    }
    return $can_access;
}, 10, 2 );
```

### Styling the Chatbot

The chatbot's appearance is controlled by CSS injected by the JavaScript. To customize the appearance:

1. Create a custom CSS file in your theme
2. Use specific selectors to override the default styles:
   ```css
   .ai-assistant-chatbot {
       /* Your custom styles */
   }

   .ai-assistant-toggle {
       /* Your custom toggle button styles */
   }
   ```

### Adding Custom Abilities

The chatbot can be extended with custom abilities:

1. Create a new class in the `Abilities` directory
2. Extend the `Abstract_Ability` class
3. Register your ability in the `Abilities_Registrar`

Example:
```php
class My_Custom_Ability extends Abstract_Ability {
    public function get_name(): string {
        return 'my_custom_ability';
    }

    public function execute( array $args ): array {
        // Implement your custom logic
        return [
            'success' => true,
            'data' => $args['processed_data'],
        ];
    }
}
```

## Troubleshooting

Common issues and solutions:

### JavaScript Errors

If you see "wpApiSettings is undefined" or similar errors:
- Make sure the Chatbot.php class is properly hooked in main.php
- Check that wp_localize_script is correctly adding the wpApiSettings variable
- Verify that the REST API is enabled and functioning

### REST API Issues

If the chatbot can't communicate with the API:
- Check browser console for CORS or authentication errors
- Verify the REST API is not blocked by security plugins
- Ensure the nonce is being correctly passed in the header

### AI Provider Connection

If the chatbot sends messages but doesn't get responses:
- Verify your API credentials are correct
- Check if the selected provider and model are available
- Look for error messages in the WordPress debug log
