# Dobrix Rechner Plugin

## Overview

The **Dobrix Rechner Plugin** is a powerful and user-friendly WordPress plugin designed for gastro professionals to manage and calculate sales data efficiently. It provides a seamless way to record, display, and manage sales submissions, including detailed calculations for exchange cash, team tips, and cash-out amounts. The plugin integrates with WordPress's custom post types and AJAX functionality to ensure a dynamic and responsive user experience.

---

## Key Features

### Front-End Functionality

- **Dynamic Sales Form**: A user-friendly form to input sales data, including:
  - Total Sales (€)
  - Sales in Cash (€)
  - Exchange Cash (toggle switch for Yes/No)
- **Dynamic Table Updates**: Displays all sales submissions in a table format with the following columns:
  - Waiter Name
  - Total Sales (€)
  - Sales in Cash (€)
  - Exchange Amount (€)
  - Team Tip (€)
  - Team Tip Percentage (%)
  - Cash Out (€)
  - Date/Time
- **Actions for Each Submission**:
  - **Edit**: Edit the submission details.
  - **Delete**: Remove the submission via AJAX.
  - **Show**: View detailed submission data in a popup.
- **Submission Confirmation**: Displays a styled card with submission details after a successful form submission.
- **Clear Table Button**: Clears all records dynamically via AJAX.

### Back-End Functionality

- **Custom Post Type**: Submissions are stored as a custom post type (`dobrix_sales`) for easy management.
- **Admin Settings Page**:
  - Configure default exchange cash amount (€).
  - Set team tip percentage (%).
  - Reset settings to default values.
  - Delete all sales submissions.
- **Daily Statistics**: Shortcode to display daily sales statistics, including total sales, cash-out amounts, and more.

### Technical Features

- **AJAX Integration**: Ensures dynamic updates for form submissions, table updates, and record deletions without page reloads.
- **Custom Post Type**: Utilizes WordPress's custom post type functionality for structured data storage.
- **Shortcodes**:
  - `[dobrix_rechner_form]`: Displays the sales form and submissions table.
  - `[dobrix_rechner_statistics]`: Displays daily sales statistics.
  - `[dobrix_rechner_reset]`: Adds a button to reset the database.
- **Responsive Design**: Fully responsive and mobile-friendly.

- **Localization Ready**: ... Still working on it ...
  Supports translations for multiple languages.

---

## Technical Details

### File Structure

```
gastro-rechner/
├── gastro-rechner.php  (Main plugin file)
├── package.json        (For React dependencies)
├── webpack.config.js   (For building React)
├── build/              (React build directory)
├── src/                (React source directory)
    ├── index.js       (Main React UI component)
```

### Dependencies

- **WordPress Core**: Utilizes WordPress's custom post types, shortcodes, and AJAX API.
- **Node.js**: Used for building JavaScript assets with Webpack.
- **@wordpress/scripts**: Simplifies block editor integration.

### Enqueued Scripts and Styles

- **Frontend**:
  - `dobrix-rechner-ajax.js`: Handles AJAX requests for form submissions and table updates.
  - `custom.css`: Styles for the plugin's front-end elements.
- **Block Editor**:
  - `dobrix-rechner-block.min.js`: JavaScript for the custom block.
  - `dobrix-rechner-block-editor.css`: Styles for the block editor.

---

## Installation

1. **Upload the Plugin**:
   - Upload the `dobrix-rechner` folder to the `/wp-content/plugins/` directory.
2. **Activate the Plugin**:
   - Go to the WordPress admin dashboard and activate the plugin under the Plugins menu.
3. **Configure Settings**:
   - Navigate to `Settings > Dobrix Rechner` to configure exchange cash and team tip percentage.
4. **Add Shortcodes**:
   - Use `[dobrix_rechner_form]` to display the sales form and table.
   - Use `[dobrix_rechner_statistics]` to display daily statistics.

---

## Usage

### Adding the Sales Form

1. Create or edit a page in WordPress.
2. Add the `[dobrix_rechner_form]` shortcode to the page content.
3. Publish or update the page.

### Viewing Daily Statistics

1. Create or edit a page in WordPress.
2. Add the `[dobrix_rechner_statistics]` shortcode to the page content.
3. Publish or update the page.

### Resetting the Database

1. Create or edit a page in WordPress.
2. Add the `[dobrix_rechner_reset]` shortcode to the page content.
3. Publish or update the page.
4. Click the reset button to clear all sales submissions.

---

## Development

### Building Assets

1. Install Node.js dependencies:
   ```bash
   npm install
   ```
2. Build the assets:
   ```bash
   npm run build
   ```
3. Start the development server:
   ```bash
   npm run start
   ```

### Debugging

- Enable WordPress debugging in `wp-config.php`:
  ```php
  define('WP_DEBUG', true);
  define('WP_DEBUG_LOG', true);
  define('WP_DEBUG_DISPLAY', false);
  ```
- Check the debug log at `wp-content/debug.log`.

---

## Support

For support or feature requests, please contact the plugin author at:

---

## License

This plugin is licensed under the [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html).
