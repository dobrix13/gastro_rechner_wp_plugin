# Gastro-Rechner

![Gastro-Rechner Logo](https://img.shields.io/badge/Gastro--Rechner-v1.1.0-blue)
[![WordPress](https://img.shields.io/badge/WordPress-5.6%2B-green)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.2%2B-purple)](https://php.net/)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)

A personal plugin for restaurants and gastronomic businesses to calculate sales, tips, and cash handling. Streamline your cash management and team tip calculations with an elegant and intuitive interface.

## Features

- 🧮 **Sales Calculation**: Easily track and calculate daily sales
- 💰 **Cash Management**: Simplified cash handling with customizable float cash options
- 💵 **Team Tip Distribution**: Automatic tip calculations based on configurable percentage
- 🔐 **User Authentication**: Integrated login and logout functionality for secure access
- 👥 **User Permissions**: Role-based access control (administrators and authors)
- 📊 **Reporting**: Export data to CSV for accounting purposes
- 📝 **CRUD Operations**: Create, read, update, and delete entries with proper validation
- 🎨 **Theming Options**: Four theme options - light-cold, light-warm, dark-cold, and dark-warm
- 📱 **Responsive Design**: Works on desktop, tablet, and mobile devices
- 🔄 **Sortable Table**: Sort your data by various fields for easy analysis
- 👤 **User Integration**: Automatically uses the logged-in WordPress user's name
- 🔍 **Detailed View**: View detailed information for each submission
- 🧹 **Bulk Operations**: Clear all submissions with proper confirmation

## Installation

1. Download the plugin zip file
2. Go to WordPress admin > Plugins > Add New
3. Click on "Upload Plugin" and select the downloaded zip file
4. Activate the plugin through the 'Plugins' menu in WordPress
5. Go to "Gastro Rechner" in the admin menu to configure settings

## Usage

### Basic Usage

Add the shortcode to any page or post:

```
[gastro_rechner]
```

### Advanced Usage

Customize the appearance with shortcode attributes:

```
[gastro_rechner title="Daily Sales Calculator" theme="dark-cold" show_name="true"]
```

Available attributes:

- `title`: Custom title for the calculator (default: "Gastro-Rechner")
- `theme`: Choose between "light-cold", "light-warm", "dark-cold", or "dark-warm" theme (default: "light-cold")
- `show_name`: Show or hide the name field (default: "true")

### Admin Settings

Configure the plugin through the admin panel:

- Set the default flow cash amount
- Set the team tip percentage
- Enable/disable the flow cash toggle on the frontend
- Set the default color scheme for the calculator
- View and export all submissions

### User Experience

- **Login/Logout**: Users can log in and log out directly from the Gastro Rechner interface
- **Permissions**: Only administrators and authors can submit entries
- **Form Validation**: All inputs are validated to ensure accurate data
- **Responsive UI**: The interface adapts to different screen sizes
- **Data Management**: Users can view, edit, and delete their own submissions
- **Admin Controls**: Administrators can manage all submissions

## Development

### Prerequisites

- [Node.js](https://nodejs.org/) (v14 or higher)
- [npm](https://www.npmjs.com/) (v6 or higher)
- [WordPress](https://wordpress.org/) (v5.6 or higher)
- [PHP](https://php.net/) (v7.2 or higher)

### Setup for Development

1. Clone the repository:

   ```
   git clone https://github.com/dobrix13/gastro_rechner_wp_plugin.git
   ```

2. Navigate to the plugin directory:

   ```
   cd gastro-rechner
   ```

3. Install dependencies:

   ```
   npm install
   ```

4. Build the project:

   ```
   npm run build
   ```

5. For development with automatic rebuilding:
   ```
   npm run watch
   ```

## Technology Stack

- PHP for server-side functionality
- React for the frontend interface
- WordPress plugin architecture
- Font Awesome for icons
- CSS for styling with responsive design
- Webpack for building assets

## License

This project is licensed under the GPL v2 - see the [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html) file for details.

## Credits

Developed by dobrix

Last updated: May 6, 2025
