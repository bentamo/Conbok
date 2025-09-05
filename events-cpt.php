<?php
/**
 * Plugin Name: ConBook Events
 * Description: MVP Events Management (Event CPT + Tickets, Registrations, Payments).
 * Version: 1.0
 * Author: Your Name
 */

/**
 * 1. Register Custom Post Type: Event
 */
add_action( 'init', 'conbook_register_event_cpt' );

function conbook_register_event_cpt() {
    $labels = array(
        'name' => 'Events',
        'singular_name' => 'Event',
        'menu_name' => 'Events',
        'add_new_item' => 'Add New Event',
        'edit_item' => 'Edit Event',
        'view_item' => 'View Event',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array( 'slug' => 'events' ),
        'menu_icon' => 'dashicons-calendar',
        'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
    );

    register_post_type( 'event', $args );
}

/**
 * 2. Create Custom Tables on Activation
 */
register_activation_hook( __FILE__, 'conbook_events_db_setup' );

function conbook_events_db_setup() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Table: Tickets
    $table_tickets = $wpdb->prefix . 'event_tickets';
    $sql_tickets = "CREATE TABLE $table_tickets (
        ticket_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) UNSIGNED NOT NULL,
        ticket_name VARCHAR(255) NOT NULL,
        ticket_description TEXT NULL,
        ticket_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        PRIMARY KEY  (ticket_id),
        KEY event_id (event_id)
    ) $charset_collate;";

    // Table: Registrations
    $table_regs = $wpdb->prefix . 'event_registrations';
    $sql_regs = "CREATE TABLE $table_regs (
        registration_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) UNSIGNED NOT NULL,
        ticket_id BIGINT(20) UNSIGNED NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        middle_name VARCHAR(100) NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        contact_number VARCHAR(50) NULL,
        payment_option VARCHAR(100) NULL,
        payment_proof VARCHAR(255) NULL,
        status ENUM('pending','accepted','rejected') DEFAULT 'pending',
        checked_in TINYINT(1) DEFAULT 0,
        registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (registration_id),
        KEY event_id (event_id),
        KEY ticket_id (ticket_id),
        KEY status (status)
    ) $charset_collate;";

    // Table: Payments
    $table_payments = $wpdb->prefix . 'event_payments';
    $sql_payments = "CREATE TABLE $table_payments (
        payment_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) UNSIGNED NOT NULL,
        bank_name VARCHAR(255) NOT NULL,
        bank_account_name VARCHAR(255) NOT NULL,
        instructions TEXT NULL,
        PRIMARY KEY  (payment_id),
        KEY event_id (event_id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql_tickets );
    dbDelta( $sql_regs );
    dbDelta( $sql_payments );
}

/**
 * 3. Admin Menu + Subpages
 */
add_action( 'admin_menu', 'conbook_events_admin_menu' );

function conbook_events_admin_menu() {
    add_menu_page(
        'ConBook Events',          // Page Title
        'ConBook Events',          // Menu Title
        'manage_options',          // Capability
        'conbook-events',          // Slug
        'conbook_events_dashboard',// Callback
        'dashicons-calendar-alt',  // Icon
        6                          // Position
    );

    add_submenu_page(
        'conbook-events',
        'Registrations',
        'Registrations',
        'manage_options',
        'conbook-registrations',
        'conbook_events_registrations_page'
    );

    add_submenu_page(
        'conbook-events',
        'Tickets',
        'Tickets',
        'manage_options',
        'conbook-tickets',
        'conbook_events_tickets_page'
    );

    add_submenu_page(
        'conbook-events',
        'Payments',
        'Payments',
        'manage_options',
        'conbook-payments',
        'conbook_events_payments_page'
    );
}

/**
 * 4. Admin Page Placeholders
 */
function conbook_events_dashboard() {
    echo '<div class="wrap"><h1>ConBook Events Dashboard</h1><p>Overview of your events will go here.</p></div>';
}

function conbook_events_registrations_page() {
    echo '<div class="wrap"><h1>Registrations</h1><p>List of event registrations will go here.</p></div>';
}

function conbook_events_tickets_page() {
    echo '<div class="wrap"><h1>Tickets</h1><p>Manage event tickets here.</p></div>';
}

function conbook_events_payments_page() {
    echo '<div class="wrap"><h1>Payments</h1><p>Set up payment options here.</p></div>';
}
