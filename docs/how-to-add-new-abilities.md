# How to Add New Abilities to AI Assistant Plugin

This guide explains how to create and register new abilities for the AI Assistant plugin, allowing you to extend the chatbot's functionality with custom WordPress operations.

## Overview

An "ability" in the AI Assistant plugin is a specific action that the AI can perform, such as creating posts, managing plugins, or retrieving data. Each ability is defined as a PHP class that extends the `Abstract_Ability` base class.

## Step-by-Step Guide

### Step 1: Create the Ability Class

Create a new PHP file in the `includes/Abilities/` directory following the naming convention:
- Filename: `{Ability_Name}_Ability.php` (e.g., `Get_Active_Plugins_Ability.php`)
- Class name: `{Ability_Name}_Ability` (e.g., `Get_Active_Plugins_Ability`)

```php
<?php
/**
 * Class Ai_Assistant\Abilities\Your_New_Ability
 *
 * @since 0.0.1
 * @package ai-assistant
 */

namespace Ai_Assistant\Abilities;

use WP_Error;
use stdClass;

/**
 * Ability to perform your custom action.
 *
 * @since 0.0.1
 */
class Your_New_Ability extends Abstract_Ability {

    /**
     * Constructor.
     *
     * @since 0.0.1
     */
    public function __construct() {
        parent::__construct( 'your-ability-slug', array( 'label' => __( 'Your Ability Label', 'ai-assistant' ) ) );
    }

    // ... implement required methods (see below)
}
```

### Step 2: Implement Required Methods

Your ability class must implement these abstract methods:

#### 2.1 Description Method
```php
protected function description(): string {
    return __( 'Clear description of what this ability does for the AI to understand.', 'ai-assistant' );
}
```

#### 2.2 Input Schema Method
Define what parameters your ability accepts:

```php
protected function input_schema(): array {
    return array(
        'type'                 => 'object',
        'properties'           => array(
            'parameter_name' => array(
                'type'        => 'string', // or 'integer', 'boolean', 'array'
                'description' => __( 'Description of this parameter.', 'ai-assistant' ),
                'default'     => 'default_value', // optional
            ),
            'required_param' => array(
                'type'        => 'string',
                'description' => __( 'This parameter is required.', 'ai-assistant' ),
            ),
        ),
        'required'             => array( 'required_param' ), // list required parameters
        'additionalProperties' => false,
    );
}
```

#### 2.3 Output Schema Method
Define what your ability returns:

```php
protected function output_schema(): array {
    return array(
        'type'       => 'object',
        'properties' => array(
            'success' => array(
                'type'        => 'boolean',
                'description' => __( 'Whether the operation was successful.', 'ai-assistant' ),
            ),
            'message' => array(
                'type'        => 'string',
                'description' => __( 'Success or error message.', 'ai-assistant' ),
            ),
            'data' => array(
                'type'        => 'array',
                'description' => __( 'Result data if applicable.', 'ai-assistant' ),
            ),
        ),
    );
}
```

#### 2.4 Execute Callback Method
The main logic of your ability:

```php
protected function execute_callback( $args ) {
    // Validate inputs
    if ( ! isset( $args['required_param'] ) ) {
        return new WP_Error( 'missing_param', __( 'Required parameter is missing.', 'ai-assistant' ) );
    }

    // Perform your logic here
    $result = $this->do_your_custom_action( $args );

    // Return result (array format, not stdClass for consistency)
    return array(
        'success' => true,
        'message' => __( 'Operation completed successfully.', 'ai-assistant' ),
        'data'    => $result,
    );
}

private function do_your_custom_action( $args ) {
    // Your custom WordPress logic here
    // e.g., database queries, API calls, file operations, etc.
    return array(); // return your data
}
```

#### 2.5 Permission Callback Method
Check if the current user can execute this ability:

```php
protected function permission_callback( $args ) {
    if ( ! current_user_can( 'manage_options' ) ) { // or other capability
        return new WP_Error(
            'rest_cannot_perform_action',
            __( 'Sorry, you are not allowed to perform this action.', 'ai-assistant' ),
            array( 'status' => rest_authorization_required_code() )
        );
    }

    return true;
}
```

### Step 3: Register the Ability

Add your ability to the `Abilities_Registrar` class in `includes/Abilities/Abilities_Registrar.php`:

```php
\wp_register_ability(
    'ai-assistant/your-ability-slug',
    array(
        'label'         => __( 'Your Ability Label', 'ai-assistant' ),
        'ability_class' => Your_New_Ability::class,
    )
);
```

### Step 4: Add to REST Route (CRITICAL STEP)

**This is the step often forgotten!** Add your ability class to the abilities array in `includes/REST_Routes/Chatbot_Messages_REST_Route.php`:

```php
// Include our abilities
$abilities = array(
    new \Ai_Assistant\Abilities\Get_Post_Ability(),
    new \Ai_Assistant\Abilities\Create_Post_Draft_Ability(),
    new \Ai_Assistant\Abilities\Publish_Post_Ability(),
    new \Ai_Assistant\Abilities\Search_Posts_Ability(),
    new \Ai_Assistant\Abilities\Generate_Post_Featured_Image_Ability(),
    new \Ai_Assistant\Abilities\Set_Permalink_Structure_Ability(),
    new \Ai_Assistant\Abilities\Install_Plugin_Ability(),
    new \Ai_Assistant\Abilities\Activate_Plugin_Ability(),
    new \Ai_Assistant\Abilities\Get_Active_Plugins_Ability(),
    new \Ai_Assistant\Abilities\Your_New_Ability(), // ADD THIS LINE
);
```

## Important Guidelines

### Parameter Handling
- Use **array format** for parameters: `$args['parameter']` (not `$args->parameter`)
- Always validate required parameters
- Provide clear error messages

### Return Values
- Return **arrays**, not `stdClass` objects for consistency
- Use `WP_Error` for error conditions
- Include meaningful success/error messages

### Security
- Always implement proper permission checks
- Sanitize and validate all inputs
- Use WordPress capabilities (`current_user_can()`)

### Schema Design
- Be specific about parameter types and descriptions
- Mark required parameters in the `required` array
- Include `additionalProperties: false` for strict validation

## Example: Complete Ability

Here's a complete example of a simple ability that gets the site's basic information:

```php
<?php
namespace Ai_Assistant\Abilities;

use WP_Error;

class Get_Site_Info_Ability extends Abstract_Ability {

    public function __construct() {
        parent::__construct( 'get-site-info', array( 'label' => __( 'Get Site Info', 'ai-assistant' ) ) );
    }

    protected function description(): string {
        return __( 'Retrieves basic information about the WordPress site including title, description, and URL.', 'ai-assistant' );
    }

    protected function input_schema(): array {
        return array(
            'type'                 => 'object',
            'properties'           => array(),
            'required'             => array(),
            'additionalProperties' => false,
        );
    }

    protected function output_schema(): array {
        return array(
            'type'       => 'object',
            'properties' => array(
                'site_title' => array(
                    'type'        => 'string',
                    'description' => __( 'The site title.', 'ai-assistant' ),
                ),
                'site_description' => array(
                    'type'        => 'string',
                    'description' => __( 'The site tagline/description.', 'ai-assistant' ),
                ),
                'site_url' => array(
                    'type'        => 'string',
                    'description' => __( 'The site URL.', 'ai-assistant' ),
                ),
            ),
        );
    }

    protected function execute_callback( $args ) {
        return array(
            'site_title'       => get_bloginfo( 'name' ),
            'site_description' => get_bloginfo( 'description' ),
            'site_url'         => get_site_url(),
        );
    }

    protected function permission_callback( $args ) {
        if ( ! current_user_can( 'read' ) ) {
            return new WP_Error(
                'rest_cannot_view_site_info',
                __( 'Sorry, you are not allowed to view site information.', 'ai-assistant' ),
                array( 'status' => rest_authorization_required_code() )
            );
        }
        return true;
    }
}
```

## Testing Your Ability

1. **Clear any caches** after adding the ability
2. **Test with the AI chatbot** using natural language:
   - "Get the site information"
   - "Show me site details"
   - "What's the site title?"

## Common Issues

1. **Forgot to add to REST route** - The most common issue! Always remember Step 4.
2. **Parameter format mismatch** - Use `$args['param']` not `$args->param`
3. **Schema validation errors** - Ensure your schema matches your actual parameters
4. **Permission errors** - Check that capabilities are appropriate for your use case

## Best Practices

1. **Descriptive names** - Use clear, descriptive ability names and descriptions
2. **Error handling** - Always handle edge cases and provide meaningful errors
3. **Documentation** - Document your parameters and return values clearly
4. **Testing** - Test with various inputs and edge cases
5. **Security** - Follow WordPress security best practices

## Debugging

If your ability isn't working:

1. Check the WordPress debug log (`wp-content/debug.log`)
2. Verify the ability is registered in `Abilities_Registrar`
3. Confirm it's added to the REST route abilities array
4. Test the schema with simple inputs first
5. Check user permissions

This documentation should help you create robust, functional abilities for the AI Assistant plugin!
