<?php
/**
 * Plugin Name: WordPress Plugin Template
 * Version: 1.0.0
 * Plugin URI: http://www.hughlashbrooke.com/
 * Description: This is your starter template for your next WordPress plugin.
 * Author: Hugh Lashbrooke
 * Author URI: http://www.hughlashbrooke.com/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: wordpress-plugin-template
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Hugh Lashbrooke
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load plugin class files.
require_once 'includes/class-wordpress-plugin-template.php';
require_once 'includes/class-wordpress-plugin-template-settings.php';


// Load plugin libraries.
require_once 'includes/lib/class-wordpress-plugin-template-admin-api.php';
require_once 'includes/lib/class-wordpress-plugin-template-post-type.php';
require_once 'includes/lib/class-wordpress-plugin-template-taxonomy.php';
require_once 'registration_form.php';
require_once 'landing_page.php';
require_once 'my_events.php';
require_once 'create-event.php';
require_once 'events-cpt.php';
require_once 'qr-scanner.php';
require_once 'qr-generator.php';

// Event Landing Page
require_once 'event-title.php';
require_once 'event-date.php';
require_once 'event-time.php';
require_once 'event-location.php';
require_once 'event-ticket-options.php';
require_once 'event-image.php';
require_once 'event-description.php';
require_once 'event-organizer.php';
require_once 'user-upcoming-events.php';
require_once 'user-past-events.php';

// Buttons
require_once 'btn-back-to-view-events.php';
require_once 'btn-join-event.php';

// Forms
require_once 'form-event-registration.php';

/**
 * Returns the main instance of WordPress_Plugin_Template to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object WordPress_Plugin_Template
 */
function wordpress_plugin_template() {
	$instance = WordPress_Plugin_Template::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = WordPress_Plugin_Template_Settings::instance( $instance );
	}

	return $instance;
}

wordpress_plugin_template();
