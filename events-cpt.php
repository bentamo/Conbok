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

    // Table 1: Events

    $table_event_details = $wpdb->prefix . 'event_details';
    $sql_event_details = "CREATE TABLE $table_event_details (
        event_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_name VARCHAR(255) NOT NULL,
        event_description TEXT NULL,
        event_date DATETIME NOT NULL,
        PRIMARY KEY (event_id)
    ) $charset_collate;";

    // Table 2: Attendees

    $table_event_attendees = $wpdb->prefix . 'event_attendees';
    $sql_event_attendees = "CREATE TABLE $table_event_attendees (
        attendee_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        first_name VARCHAR(100) NOT NULL,
        middle_name VARCHAR(100) NULL,
        last_name VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        contact_number VARCHAR(50) NULL,
        PRIMARY KEY (attendee_id),
        UNIQUE KEY uniq_email (email)
    ) $charset_collate;";

    // Table 3: Tickets
    
    $table_event_tickets = $wpdb->prefix . 'event_tickets';
    $sql_event_tickets = "CREATE TABLE $table_event_tickets (
        ticket_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) UNSIGNED NOT NULL,
        ticket_name VARCHAR(255) NOT NULL,
        ticket_description TEXT NULL,
        ticket_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        PRIMARY KEY (ticket_id),
        KEY idx_event_id (event_id)
    ) $charset_collate;";

    // Table 4: Payments

    $table_event_payments = $wpdb->prefix . 'event_payments';
    $sql_event_payments = "CREATE TABLE $table_event_payments (
        payment_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) UNSIGNED NOT NULL,
        attendee_id BIGINT(20) UNSIGNED NOT NULL,
        ticket_id BIGINT(20) UNSIGNED NOT NULL,
        proof_of_payment VARCHAR(255) NULL,  -- new column for storing file path / URL
        PRIMARY KEY (payment_id),
        KEY idx_event_id (event_id),
        KEY idx_attendee_id (attendee_id),
        KEY idx_ticket_id (ticket_id)
    ) $charset_collate;";

    // Table 5: Registrations

    $table_event_registrations = $wpdb->prefix . 'event_registrations';
    $sql_event_registrations = "CREATE TABLE $table_event_registrations (
        registration_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) UNSIGNED NOT NULL,
        attendee_id BIGINT(20) UNSIGNED NOT NULL,
        ticket_id BIGINT(20) UNSIGNED NOT NULL,
        payment_id BIGINT(20) UNSIGNED NULL,
        registration_status ENUM('Pending','Accepted','Rejected','Cancelled') DEFAULT 'Pending',
        checked_in TINYINT(1) DEFAULT 0,
        PRIMARY KEY (registration_id),
        KEY idx_event_id (event_id),
        KEY idx_attendee_id (attendee_id),
        KEY idx_ticket_id (ticket_id),
        KEY idx_payment_id (payment_id)
    ) $charset_collate;";

    // Table 6: Payment Options

    $table_event_payment_options = $wpdb->prefix . 'event_payment_options';
    $sql_event_payment_options = "CREATE TABLE $table_event_payment_options (
        payment_option_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) UNSIGNED NOT NULL,
        option_name VARCHAR(255) NOT NULL,        -- e.g., Bank Transfer, GCash, PayPal
        bank_name VARCHAR(255) NULL,              -- optional, only for bank transfers
        bank_account_name VARCHAR(255) NULL,      -- optional, only for bank transfers
        instructions TEXT NULL,                   -- payment instructions for this option
        PRIMARY KEY (payment_option_id),
        KEY idx_event_id (event_id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql_event_details );
    dbDelta( $sql_event_attendees );
    dbDelta( $sql_event_tickets );
    dbDelta( $sql_event_payments );
    dbDelta( $sql_event_registrations );
    dbDelta( $sql_event_payment_options );
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
