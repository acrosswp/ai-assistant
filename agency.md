# AI Assistant Plugin - AI Agent Instructions

## Overview

This is a comprehensive WordPress AI Assistant plugin that follows modern PHP development standards, WordPress coding guidelines, and includes advanced tooling for professional plugin development. It's designed to provide AI-powered assistance features for WordPress websites.

## Project Structure & Architecture

### Core Framework

• **Framework**: Modern WordPress plugin boilerplate with PSR-4 autoloading
• **PHP Version**:
  ◦ 🔒 **MINIMUM REQUIRED**: PHP 7.4+ (enforced by Composer)
  ◦ 🚀 **RECOMMENDED**: PHP 8.0+ for optimal performance
  ◦ ⚠️ **CRITICAL**: Installation will FAIL on older PHP versions
• **WordPress Version**: Minimum 4.9.1+ (tested up to 6.2.2+)
• **Coding Standards**: WordPress Coding Standards (WPCS)
• **Build System**: @wordpress/scripts (Webpack-based)
• **Package Manager**: Composer + npm (with PHP version enforcement)
• **Namespace**: `Ai_Assistant\`

### Directory Structure

```
ai-assistant/
├── .github/                     # GitHub workflows and templates
│   ├── workflows/
│   │   ├── build-zip.yml       # Automated plugin zip creation
│   │   └── wordpress-plugin-deploy.yml  # WordPress.org deployment
│   └── copilot-instructions.md # AI agent instructions
├── .wordpress-org/             # WordPress.org assets
│   ├── banner-1544x500.jpg    # Plugin banner (large)
│   ├── banner-772x250.jpg     # Plugin banner (small)
│   ├── icon-128x128.jpg       # Plugin icon (small)
│   └── icon-256x256.jpg       # Plugin icon (large)
├── admin/                      # Admin area functionality
│   ├── Main.php               # Admin main class
│   └── partials/              # Admin templates
│       ├── menu.php           # Admin menu template
│       └── index.php          # Security file
├── build/                      # Compiled assets (auto-generated)
│   ├── css/                   # Compiled CSS files
│   ├── js/                    # Compiled JavaScript files
│   └── media/                 # Processed media files
├── includes/                   # Core plugin classes
│   ├── main.php               # Main plugin class
│   ├── loader.php             # Hook loader class
│   ├── activator.php          # Plugin activation logic
│   ├── deactivator.php        # Plugin deactivation logic
│   ├── i18n.php               # Internationalization
│   ├── Autoloader.php         # PSR-4 autoloader
│   ├── Abilities/             # AI Abilities system
│   ├── Agents/                # AI Agents system
│   ├── AI_Client/             # AI Client integration
│   ├── Providers/             # AI Provider management
│   └── REST_Routes/           # REST API endpoints
├── languages/                  # Translation files
│   └── ai-assistant.pot
├── public/                     # Public-facing functionality
│   ├── Main.php               # Public main class
│   ├── Chatbot.php            # AI Chatbot implementation
│   └── partials/              # Public templates
├── src/                        # Source assets (development)
│   ├── js/                    # JavaScript source files
│   ├── scss/                  # SCSS source files
│   └── media/                 # Media source files
├── vendor/                     # Composer dependencies
├── composer.json              # Composer configuration
├── package.json               # npm configuration
├── webpack.config.js          # Webpack build configuration
└── ai-assistant.php           # Main plugin file
```

## Development Workflow

### 🔒 MANDATORY: PHP Version Validation

**CRITICAL FIRST STEP**: Always verify PHP version before any development work:

```bash
# Check PHP version BEFORE starting development
php -v

# Expected output: PHP 7.4.0 or higher
# Example: PHP 8.0.30 (cli) (built: Aug  5 2023 10:50:05)
```

**ESSENTIAL VALIDATION**:
• ❌ **STOP**: If PHP < 7.4, upgrade before continuing
• ✅ **PROCEED**: PHP 7.4+ confirmed, development can begin
• 🚨 **WARNING**: Composer will prevent installation on incompatible versions

**PHP Version Benefits for Development**:
• ✅ **Modern Syntax**: Arrow functions, typed properties, null coalescing
• ✅ **Performance**: 20-30% performance improvement over PHP 7.3
• ✅ **Security**: Active security support and patches
• ✅ **Ecosystem**: Required by modern WordPress tools and packages

### Build System (@wordpress/scripts)

The plugin uses WordPress's official build tools for modern development:

#### Available npm Commands:

```bash
# Development build (with source maps)
npm run start

# Production build (optimized)
npm run build

# Check for JavaScript errors
npm run lint:js

# Fix JavaScript formatting
npm run lint:js:fix

# Check for CSS errors
npm run lint:css

# Generate translation files
npm run makepot
```

#### Asset Compilation:

• **SCSS → CSS**: Automatic compilation with autoprefixing
• **Modern JS → Compatible JS**: Babel transpilation
• **Asset Optimization**: Minification, source maps in development
• **Hot Reload**: Live reloading during development

### Composer Dependencies & Packages

#### 🔒 CRITICAL: PHP Version Requirement

**MANDATORY**: All packages require PHP 7.4+ enforced in `composer.json`:

```json
{
  "require": {
    "php": ">=7.4",
    "coenjacobs/mozart": "^0.7"
  }
}
```

**ESSENTIAL ENFORCEMENT**:
• ❌ **Installation Prevention**: Composer will REFUSE to install on PHP < 7.4
• ✅ **Version Safety**: Prevents runtime compatibility issues
• 🛡️ **Environment Protection**: Ensures consistent behavior across deployments
• 🚨 **CRITICAL**: This is NON-NEGOTIABLE for all AI Assistant projects

#### Core Development Tools:

• **coenjacobs/mozart** - PHP dependency scoping and namespacing to prevent plugin conflicts

### Mozart Package Scoping Integration

#### Purpose & Benefits

Mozart is a Composer plugin that helps prevent conflicts between WordPress plugins by:

• **Dependency Scoping**: Automatically prefixes third-party library namespaces
• **Conflict Prevention**: Prevents version conflicts when multiple plugins use the same libraries
• **Isolation**: Ensures each plugin uses its own isolated version of dependencies
• **Professional Development**: Essential for production plugins with external dependencies

#### Configuration in composer.json

```json
{
  "require": {
    "php": ">=7.4",
    "coenjacobs/mozart": "^0.7"
  },
  "extra": {
    "mozart": {
      "dep_namespace": "Ai_Assistant\\Dependencies\\",
      "dep_directory": "/vendor/",
      "classmap_directory": "/classes/",
      "classmap_prefix": "Ai_Assistant_",
      "packages": [],
      "excluded_packages": []
    }
  },
  "scripts": {
    "post-install-cmd": [
      "\"vendor/bin/mozart\" compose"
    ],
    "post-update-cmd": [
      "\"vendor/bin/mozart\" compose"
    ]
  }
}
```

#### Usage Workflow

```bash
# 1. Mozart is already installed in composer.json
# composer require coenjacobs/mozart:^0.7  # Already included

# 2. Configure Mozart in composer.json (see above)

# 3. Install your dependencies
composer require vendor/library-name

# 4. Run Mozart to scope dependencies
vendor/bin/mozart compose

# 5. Use scoped dependencies in code
use Ai_Assistant\Dependencies\Vendor\LibraryName\ClassName;
```

#### Integration Best Practices

• **Always scope external libraries**: Prevents conflicts with other plugins
• **Update mozart configuration**: When adding new dependencies
• **Version control scoped files**: Include generated files in Git
• **Test thoroughly**: Ensure scoped dependencies work correctly
• **Documentation**: Document scoped namespace usage for team members

### PSR-4 Autoloading Configuration

```json
{
  "autoload": {
    "psr-4": {
      "Ai_Assistant\\Includes\\": "includes/",
      "Ai_Assistant\\Admin\\": "admin/",
      "Ai_Assistant\\Public\\": "public/"
    }
  }
}
```

## Plugin Architecture Patterns

### Hook Management System

The plugin uses a centralized loader system for managing WordPress hooks:

```php
// Add action hook
$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );

// Add filter hook
$this->loader->add_filter( 'the_content', $plugin_public, 'filter_content' );
```

### Dependency Injection

The main class follows dependency injection patterns:

• Autoloader registration
• Composer dependency loading
• Service container pattern for major components

### Namespace Organization

• `Ai_Assistant\Includes\` - Core functionality
• `Ai_Assistant\Admin\` - Admin area features
• `Ai_Assistant\Public\` - Public-facing features

## AI-Specific Architecture

### AI Client Integration

The plugin includes a comprehensive AI client system:

• **AI_Client_Factory**: Factory for creating AI client instances
• **AI_Client_Wrapper**: Wrapper for standardized AI operations
• **Provider_Manager**: Manages different AI providers (OpenAI, Anthropic, etc.)

### Abilities System

Modular abilities system for AI functionality:

• **Abstract_Ability**: Base class for all AI abilities
• **Create_Post_Draft_Ability**: Creates draft posts
• **Generate_Post_Featured_Image_Ability**: Generates featured images
• **Search_Posts_Ability**: Searches WordPress posts
• **Publish_Post_Ability**: Publishes posts

### Agents System

AI agent framework for complex workflows:

• **Abstract_Agent**: Base class for AI agents
• **Chatbot_Agent**: Conversational AI agent
• **Agent_Step_Result**: Result objects for agent operations

## Security Best Practices

### Data Sanitization & Validation

```php
// Sanitize text input
$clean_text = sanitize_text_field( $_POST['user_input'] );

// Sanitize email
$clean_email = sanitize_email( $_POST['email'] );

// Validate and sanitize URLs
$clean_url = esc_url_raw( $_POST['url'] );
```

### Nonce Verification

```php
// Generate nonce
wp_nonce_field( 'ai_assistant_action', 'ai_assistant_nonce' );

// Verify nonce
if ( ! wp_verify_nonce( $_POST['ai_assistant_nonce'], 'ai_assistant_action' ) ) {
    wp_die( 'Security check failed' );
}
```

### Capability Checks

```php
// Check user capabilities
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Insufficient permissions' );
}
```

## API Endpoints & REST API Integration

### Creating Custom Endpoints

The plugin supports easy REST API endpoint creation:

```php
// In your main class or dedicated API class
add_action( 'rest_api_init', array( $this, 'register_api_endpoints' ) );

public function register_api_endpoints() {
    register_rest_route( 'ai-assistant/v1', '/chatbot', array(
        'methods' => 'POST',
        'callback' => array( $this, 'chatbot_callback' ),
        'permission_callback' => array( $this, 'api_permissions' ),
    ) );
}
```

### Endpoint Structure Recommendations:

• **Namespace**: `ai-assistant/v1`
• **Authentication**: WordPress nonces or JWT tokens
• **Data Validation**: Use WordPress sanitization functions
• **Response Format**: JSON with standardized structure

## Performance Optimization

### Asset Loading Strategy

• **Conditional Loading**: Only load assets where needed
• **Minification**: Production builds are automatically minified
• **Caching**: Implement proper caching strategies
• **Lazy Loading**: Use WordPress lazy loading features

### Database Query Optimization

• Use WordPress query functions (`WP_Query`, `get_posts()`)
• Implement proper caching for expensive queries
• Use transients for temporary data storage

## Internationalization (i18n)

### Translation Setup

1. **Text Domain**: Use plugin slug as text domain
2. **Translation Functions**:

```php
__( 'Text to translate', 'ai-assistant' );
_e( 'Text to echo', 'ai-assistant' );
_n( 'Singular', 'Plural', $count, 'ai-assistant' );
```

3. **Generate POT File**:

```bash
npm run makepot
```

## Deployment & Distribution

### WordPress.org Repository

The plugin includes GitHub Actions for automated deployment:

#### Features:

• **Automated ZIP Creation**: Creates distributable plugin ZIP
• **SVN Deployment**: Pushes to WordPress.org repository
• **Version Management**: Handles version tagging
• **Asset Management**: Manages plugin assets (banners, icons)

### GitHub Actions Workflows

1. **build-zip.yml**: Creates plugin ZIP on releases
2. **wordpress-plugin-deploy.yml**: Deploys to WordPress.org

## Environment Configuration

### Development Environment

```bash
# Local development with WordPress
# Use Docker
docker-compose up -d

# Or use Local by Flywheel, XAMPP, etc.
```

### Environment Constants

```php
// wp-config.php additions for development
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );
```

## Common Development Patterns

### Plugin Activation/Deactivation

```php
// Activation hook
register_activation_hook( __FILE__, array( 'Activator', 'activate' ) );

// Deactivation hook
register_deactivation_hook( __FILE__, array( 'Deactivator', 'deactivate' ) );
```

### Admin Menu Integration

```php
add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

public function add_admin_menu() {
    add_menu_page(
        __( 'AI Assistant', 'ai-assistant' ),
        __( 'AI Assistant', 'ai-assistant' ),
        'manage_options',
        'ai-assistant',
        array( $this, 'admin_page_callback' )
    );
}
```

### Settings API Integration

```php
add_action( 'admin_init', array( $this, 'settings_init' ) );

public function settings_init() {
    register_setting( 'ai_assistant_settings', 'ai_assistant_options' );

    add_settings_section(
        'ai_assistant_section',
        __( 'AI Settings', 'ai-assistant' ),
        null,
        'ai_assistant_settings'
    );
}
```

## Maintenance & Updates

### Version Management

• Follow semantic versioning (x.y.z)
• Update version in main plugin file header
• Update version in package.json
• Create git tags for releases

### Backward Compatibility

• Maintain compatibility with supported WordPress versions
• Provide migration functions for database changes
• Deprecate features gradually with proper notices
• Update documentation to reflect compatibility changes
