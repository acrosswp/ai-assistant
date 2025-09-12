# Plugin Setup & Password Field Instructions

## Setup & Testing Steps

- [ ] Clone the Plugin from https://github.com/WPBoilerplate
- [ ] Push this on Github
- [ ] Activate the Plugin to test if this is working well
- [ ] Access the Menu in the admin dashboard area
- [ ] Fix the issue if there is any
- [ ] Push again on Github

## Making All Provider Fields Password Fields

1. Open the file where the settings fields are registered (e.g., `menu.php`).
2. Locate the code where each field is added using `add_settings_field`.
3. For each provider (Anthropic, Google, OpenAI), find the callback function that outputs the input field.
4. Change the input type from `type="text"` to `type="password"` for all three fields:
   - Example: `<input type="password" name="ai_assistant_google_key" ... />`
5. Save the file.
6. Refresh the settings page in the WordPress admin to confirm all three fields now hide the input as password fields.

---

Follow these steps for consistent setup and field security in the plugin.
