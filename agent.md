# Dependency Scoping with Mozart

## What is Mozart?

[Mozart](https://github.com/coenjacobs/mozart) is a Composer plugin that automatically prefixes (scopes) PHP dependencies in your WordPress plugin. This prevents conflicts when multiple plugins use the same libraries.

## How does it work?

- Mozart rewrites namespaces and class names for your dependencies, placing them under your own namespace (e.g., `Ai_Assistant\Dependencies\`).
- This ensures your plugin's dependencies do not conflict with those of other plugins or WordPress core.

## How is it configured?

In your `composer.json`:

- Add Mozart as a dependency:
  ```json
  "coenjacobs/mozart": "^0.7"
  ```
- Configure Mozart in the `extra` section:
  ```json
  "extra": {
    "mozart": {
      "dep_namespace": "Ai_Assistant\\Dependencies\\",
      "dep_directory": "/vendor/",
      "classmap_directory": "/classes/",
      "classmap_prefix": "Ai_Assistant_",
      "packages": [],
      "excluded_packages": []
    }
  }
  ```
- Add post-install and post-update scripts:
  ```json
  "scripts": {
    "post-install-cmd": [
      "\"vendor/bin/mozart\" compose"
    ],
    "post-update-cmd": [
      "\"vendor/bin/mozart\" compose"
    ]
  }
  ```

## How do I use it?

- After running `composer install` or `composer update`, Mozart will automatically prefix your dependencies.
- You can also run it manually:
  ```bash
  vendor/bin/mozart compose
  ```
- Use your dependencies under the new namespace, e.g.:
  ```php
  use Ai_Assistant\Dependencies\GuzzleHttp\Client;
  ```

## Why is this important?

- Prevents fatal errors and class redeclaration issues in WordPress environments with many plugins.
- Ensures your plugin is production-ready and compatible with other plugins using the same libraries.

## References
- [Mozart GitHub](https://github.com/coenjacobs/mozart)
- [WordPress Plugin Boilerplate - Dependency Management](https://github.com/WPBoilerplate/wordpress-plugin-boilerplate)
