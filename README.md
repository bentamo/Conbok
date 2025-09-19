# Conbok WordPress Plugin

Conbok is a powerful WordPress plugin for event management, providing a comprehensive suite of tools for creating, managing, and tracking events. It includes features for user registration, QR code generation for ticketing, and detailed event dashboards.

This document provides a technical overview of the plugin's architecture, file structure, and development guidelines for developers.

## Features

-   **Event Management**: Create and manage events using a custom post type.
-   **User Registration**: Allow users to register for events through a dedicated form.
-   **Event Dashboards**: Provides detailed dashboards for event overview, guest lists, and registration tracking.
-   **QR Code Integration**: Generate and scan QR codes for event ticketing and check-in.
-   **User-Specific Pages**: Users can view their upcoming and past events and registrations.

## File Structure

The project is structured to separate concerns, with distinct directories for core logic, assets, and third-party libraries.

```
Conbok/
├── assets/                 # Frontend assets (CSS, JS)
├── includes/               # Core plugin classes and logic
├── lang/                   # Language and translation files
├── phpqrcode/              # Third-party QR code generation library
├── Gruntfile.js            # Grunt task configuration
├── composer.json           # PHP dependencies (for development)
├── package.json            # Node.js dependencies (for development)
├── create-event.php        # Logic for event creation form
├── event-dashboard.php     # Main dashboard for a single event
├── events-cpt.php          # Defines the 'Event' Custom Post Type
├── form-event-registration.php # Event registration form
├── qr-code-generator.php   # Generates QR codes for tickets
├── qr-scanner.php          # Page for scanning QR codes
├── wordpress-plugin-template.php # Main plugin entry file
└── uninstall.php           # Uninstallation script
```

### Key Files & Directories

-   `wordpress-plugin-template.php`: The main plugin file that WordPress uses to identify and load the plugin. It handles the initial setup and includes the core plugin class.

-   `includes/`: This directory contains the core PHP classes for the plugin.
    -   `class-wordpress-plugin-template.php`: The main plugin class, responsible for orchestrating the plugin's functionality.
    -   `lib/class-wordpress-plugin-template-admin-api.php`: Handles admin-side API requests.
    -   `lib/class-wordpress-plugin-template-post-type.php`: Manages the creation and behavior of custom post types.
    -   `lib/class-wordpress-plugin-template-taxonomy.php`: Manages custom taxonomies.
    -   `class-wordpress-plugin-template-settings.php`: Handles the plugin's settings page in the WordPress admin area.

-   `events-cpt.php`: Defines the `event` custom post type and its associated metadata.

-   `event-dashboard.php`: The entry point for the event-specific dashboard, which includes multiple tabs for managing an event.
    -   `event-dashboard-overview-tab.php`: Displays an overview of the event.
    -   `event-dashboard-guests-tab.php`: Lists all registered guests for the event.
    -   `event-dashboard-registrations-tab.php`: Shows a table of all registrations.

-   `qr-code-generator.php` & `qr-scanner.php`: These files, along with `qr-scanner.js` and the `phpqrcode/` library, provide the QR code generation and scanning functionality for event ticketing.

-   `assets/`: Contains the CSS and JavaScript files for both the admin and frontend.
    -   `admin.css` / `admin.js`: Styles and scripts for the WordPress admin interface.
    -   `frontend.css` / `frontend.js`: Styles and scripts for the public-facing pages.

-   `Gruntfile.js`, `package.json`, `composer.json`: These files are used for development and build processes. `Grunt` is used for task automation like compiling `.less` files and minifying assets.

## Getting Started for Developers

1.  **Clone the repository** into your WordPress `plugins` directory.
2.  **Install dependencies**:
    -   Run `npm install` to install the Node.js dependencies required for the build process (Grunt).
    -   Run `composer install` if there are any PHP dependencies.
3.  **Activate the plugin** in your WordPress admin dashboard.

## Build Process

The project uses Grunt for automating development tasks. The following commands are available:

-   `grunt less`: Compile `.less` files into CSS.
-   `grunt uglify`: Minify JavaScript files.
-   `grunt watch`: Watch for changes in `.less` and `.js` files and automatically run the corresponding tasks.

Refer to `Gruntfile.js` for a full list of available tasks.

## Custom Post Types

The plugin registers an `event` custom post type. The definition and associated hooks can be found in `events-cpt.php`.

## Dependencies

-   **PHP QR Code Library**: Located in the `phpqrcode/` directory. Used for generating QR codes.
-   **Grunt**: For task automation. Requires Node.js and npm.