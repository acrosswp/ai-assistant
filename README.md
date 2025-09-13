````markdown
# AI Assistant Plugin

This WordPress plugin provides AI capabilities to your website through integration with various AI providers including Anthropic, Google, and OpenAI.

## Features

- Chatbot functionality for admin and public-facing pages
- Multiple AI provider support (Anthropic, Google, OpenAI)
- WordPress Settings API integration for provider configuration
- REST API endpoints for message processing
- Provider debug logging for troubleshooting

## Recent Fixes

For details about recent bugfixes, please see [FIXES.md](FIXES.md).

## Documentation

- [Provider Debug Logging](docs/provider-debug-logging.md) - Learn how to use the debug logging feature to troubleshoot provider-specific issues
- [Troubleshooting Guide](docs/troubleshooting-guide.md) - Common issues and solutions
- [Empty Tool Calls Fix](docs/empty-tool-calls-fix.md) - Details about the fix for empty tool_calls errors

## Development Setup

### @wordpress/scripts

https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/

### Adding multipal input file

Now are using the https://github.com/x3p0-dev/x3p0-ideas/tree/block-example exmaple to setup out plugins

1.  Run `npm install` command and it will generate folder and files
2.  Now run `npm run build` command and it will generate plugin build
3.  Now run `npm run start` command and it will generate plugin on every file update

### Create blocks

1. Once everything install goto `src/` folder and run `npx @wordpress/create-block ai-assistant-block --no-plugin`

2. Now run `composer require wpboilerplate/wpb-register-blocks`

3. Now add
```
/**
 * Check if class exists or not
 */
if ( class_exists( 'WPBoilerplate_Register_Blocks' ) ) {
	new WPBoilerplate_Register_Blocks( $this->plugin_dir );
}
```
inside the `load_composer_dependencies` method at the end

4. Now run `composer update`

5. Once that is installed run `npm run build`

### Update your code via Github

1. run `composer require wpboilerplate/wpb-updater-checker-github`

2. Now run `composer update`

3. Now add
```
/**
 * Check if class exists or not
 */
/**
 * For Plugin Update via Github
 */
if ( class_exists( 'WPBoilerplate_Updater_Checker_Github' ) ) {

	$package = array(
		'repo' 		        => 'https://github.com/WPBoilerplate/ai-assistant',
		'file_path' 		=> AI_ASSISTANT_PLUGIN_FILE,
		'plugin_name_slug'	=> AI_ASSISTANT_PLUGIN_NAME_SLUG,
		'release_branch' 	=> 'main'
	);

	new WPBoilerplate_Updater_Checker_Github( $package );
}
```
inside the `load_composer_dependencies` method at the end


# Composer

### Adding dependency for Custom Plugins

1. Adding BuddyBoss Platform and Platform Pro dependency
   `composer require wpboilerplate/wpb-buddypress-or-buddyboss-dependency`
   and then add the below code in function load_dependencies after vendor autoload file included `require_once( AI_ASSISTANT_PLUGIN_PATH . 'vendor/autoload.php' );`

```
/**
 * Add the dependency for the call
 */
    if ( class_exists( 'WPBoilerplate_BuddyPress_BuddyBoss_Platform_Dependency' ) ) {
        new WPBoilerplate_BuddyPress_BuddyBoss_Platform_Dependency( $this->get_plugin_name(), AI_ASSISTANT_FILES );
    }
```

2. Adding BuddyBoss Platform dependency
   `composer require wpboilerplate/wpb-buddyboss-dependency`

3. Adding WooCommerce dependency
   `composer require wpboilerplate/wpb-woocommerce-dependency`

4. Adding ACF Pro dependency
   `composer require wpboilerplate/acrossswp-acf-pro-dependency`

5. Adding View Analytics dependency
   `composer require wpboilerplate/wpb-view-analytics-dependency`


# Credits

1. https://github.com/xwp/wp-foo-bar

2. https://github.com/acrosswp/

3. https://github.com/10up/action-wordpress-plugin-build-zip

4. https://github.com/10up/action-wordpress-plugin-deploy

5. https://docs.google.com/document/d/1GMKxjxdFqwCg3ESC337eNvA6FmaokW9Zlkjm-mhSroU/edit?tab=t.0#heading=h.d22cu7925a4z
