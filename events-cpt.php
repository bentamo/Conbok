<?php
/**
 * Plugin Name: ConBook Events
 * Description: MVP Events Management (Event CPT + Tickets, Registrations, Payments) with Event Meta Fields.
 * Version: 1.2
 * Author: Miko
 */
/**
 * ConBook Events Plugin Overview
 *
 * This plugin provides a basic MVP events management system in WordPress.
 * It allows you to manage Events (Custom Post Type), Tickets, Registrations, and Payments.
 *
 * Key Features and Additions:
 *
 * 1. Event Custom Post Type (CPT)
 *    - Registers an "Event" post type for creating and managing events.
 *    - Supports title, editor, thumbnail, and excerpt.
 *
 * 1a. Event Meta Boxes
 *    - Adds extra fields to the Event CPT in the admin:
 *        • Start Date
 *        • End Date
 *        • Start Time
 *        • End Time
 *        • Location
 *    - Saves these fields securely using WordPress post meta.
 *
 * 2. Custom Database Tables on Activation
 *    - Creates 5 custom tables for advanced event management:
 *        • event_attendees     → Stores attendee info
 *        • event_tickets       → Stores tickets linked to events
 *        • event_payments      → Stores payments and uploaded proof-of-payment files
 *        • event_registrations → Links attendees, tickets, and events with statuses
 *        • event_payment_options → Stores payment options for each event
 *
 * 3. Admin Menu + Subpages
 *    - Adds a main menu "ConBook Events" and submenus for:
 *        • Registrations
 *        • Tickets
 *        • Payments
 *
 * 4. Admin Page Placeholders
 *    - Simple placeholder pages for the above admin menus for future implementation.
 *
 * 5. Safe Deletion of Related Event Data
 *    - Hooks into 'before_delete_post' to remove all data linked to an Event when it's deleted:
 *        • Deletes all post meta for the event
 *        • Deletes registrations and associated payments, including uploaded files safely
 *        • Deletes tickets linked to the event
 *        • Deletes payment options linked to the event
 *    - Ensures no orphaned database records or leftover files remain.
 *
 * 6. Bulk Clean Test Events Admin Page
 *    - Adds an admin submenu "Bulk Clean" to quickly delete all test events.
 *    - Example uses search term 'test' in event titles.
 *    - Deletes all related data for matching events (tickets, registrations, payments, files, post meta).
 *
 * Notes:
 *    - The deletion logic is safe and smart: it will not throw errors if files are missing.
 *    - You can safely test events, delete them, and know the database remains clean.
 *    - Future improvements could include frontend forms for creating events and handling registrations.
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
 * 1a. Add Meta Boxes for Event CPT
 */
add_action( 'add_meta_boxes', 'conbook_add_event_meta_boxes' );

function conbook_add_event_meta_boxes() {
    add_meta_box(
        'conbook_event_details',
        'Event Details',
        'conbook_event_details_callback',
        'event',
        'normal',
        'high'
    );
}

function conbook_event_details_callback( $post ) {
    wp_nonce_field( basename( __FILE__ ), 'conbook_event_nonce' );

    $start_date = get_post_meta( $post->ID, '_start_date', true );
    $end_date   = get_post_meta( $post->ID, '_end_date', true );
    $start_time = get_post_meta( $post->ID, '_start_time', true );
    $end_time   = get_post_meta( $post->ID, '_end_time', true );
    $location   = get_post_meta( $post->ID, '_location', true );

    echo '<p><label>Start Date: <input type="date" name="start_date" value="' . esc_attr( $start_date ) . '"></label></p>';
    echo '<p><label>End Date: <input type="date" name="end_date" value="' . esc_attr( $end_date ) . '"></label></p>';
    echo '<p><label>Start Time: <input type="time" name="start_time" value="' . esc_attr( $start_time ) . '"></label></p>';
    echo '<p><label>End Time: <input type="time" name="end_time" value="' . esc_attr( $end_time ) . '"></label></p>';
    echo '<p><label>Location: <input type="text" name="location" value="' . esc_attr( $location ) . '" style="width:100%;"></label></p>';
}

/**
 * 1b. Save Meta Box Data
 */
add_action( 'save_post', 'conbook_save_event_meta', 10, 2 );

function conbook_save_event_meta( $post_id, $post ) {
    if ( ! isset( $_POST['conbook_event_nonce'] ) || ! wp_verify_nonce( $_POST['conbook_event_nonce'], basename( __FILE__ ) ) ) {
        return $post_id;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return $post_id;
    if ( $post->post_type != 'event' ) return $post_id;

    $fields = array( 'start_date', 'end_date', 'start_time', 'end_time', 'location' );

    foreach ( $fields as $field ) {
        if ( isset( $_POST[$field] ) ) {
            update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[$field] ) );
        }
    }
}

/**
 * 2. Create Custom Tables on Activation
 */
register_activation_hook( __FILE__, 'conbook_events_db_setup' );

function conbook_events_db_setup() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Attendees
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

    // Tickets
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

    // Payments (proof of payment)
    $table_event_payments = $wpdb->prefix . 'event_payments';
    $sql_event_payments = "CREATE TABLE $table_event_payments (
        payment_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        registration_id BIGINT(20) UNSIGNED NOT NULL,
        proof_of_payment VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (payment_id),
        KEY idx_registration_id (registration_id)
    ) $charset_collate;";

    // Registrations
    $table_event_registrations = $wpdb->prefix . 'event_registrations';
    $sql_event_registrations = "CREATE TABLE $table_event_registrations (
        registration_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) UNSIGNED NOT NULL,
        attendee_id BIGINT(20) UNSIGNED NOT NULL,
        ticket_id BIGINT(20) UNSIGNED NOT NULL,
        registration_status ENUM('Pending','Accepted','Rejected','Cancelled') DEFAULT 'Pending',
        checked_in TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (registration_id),
        KEY idx_event_id (event_id),
        KEY idx_attendee_id (attendee_id),
        KEY idx_ticket_id (ticket_id)
    ) $charset_collate;";

    // Payment Options
    $table_event_payment_options = $wpdb->prefix . 'event_payment_options';
    $sql_event_payment_options = "CREATE TABLE $table_event_payment_options (
        payment_option_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) UNSIGNED NOT NULL,
        option_name VARCHAR(255) NOT NULL,
        bank_name VARCHAR(255) NULL,
        bank_account_name VARCHAR(255) NULL,
        instructions TEXT NULL,
        PRIMARY KEY (payment_option_id),
        KEY idx_event_id (event_id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
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
        'ConBook Events',
        'ConBook Events',
        'manage_options',
        'conbook-events',
        'conbook_events_dashboard',
        'dashicons-calendar-alt',
        6
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

/**
 * 5. Safe Deletion of Related Event Data (including post meta)
 */
add_action( 'before_delete_post', 'conbook_delete_event_related_data' );

function conbook_delete_event_related_data( $post_id ) {
    $post_type = get_post_type( $post_id );
    if ( $post_type !== 'event' ) return;

    global $wpdb;

    // --- 1. Delete all post meta for this event ---
    $wpdb->delete( $wpdb->postmeta, [ 'post_id' => $post_id ] );

    // --- 2. Delete registrations and payments ---
    $registrations = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT registration_id FROM {$wpdb->prefix}event_registrations WHERE event_id = %d",
            $post_id
        )
    );

    foreach ( $registrations as $reg ) {
        $registration_id = $reg->registration_id;

        // Delete proof-of-payment files safely
        $payments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT proof_of_payment FROM {$wpdb->prefix}event_payments WHERE registration_id = %d",
                $registration_id
            )
        );

        foreach ( $payments as $payment ) {
            $file_path = ABSPATH . $payment->proof_of_payment;
            if ( !empty($payment->proof_of_payment) && file_exists( $file_path ) ) {
                unlink( $file_path );
            }
        }

        // Delete payment records
        $wpdb->delete( "{$wpdb->prefix}event_payments", [ 'registration_id' => $registration_id ], [ '%d' ] );

        // Delete registration record
        $wpdb->delete( "{$wpdb->prefix}event_registrations", [ 'registration_id' => $registration_id ], [ '%d' ] );
    }

    // --- 3. Delete tickets linked to this event ---
    $wpdb->delete( "{$wpdb->prefix}event_tickets", [ 'event_id' => $post_id ], [ '%d' ] );

    // --- 4. Delete payment options linked to this event ---
    $wpdb->delete( "{$wpdb->prefix}event_payment_options", [ 'event_id' => $post_id ], [ '%d' ] );
}

/**
 * 6. Bulk Clean Test Events Admin Page
 */
add_action( 'admin_menu', 'conbook_bulk_clean_admin_menu' );

function conbook_bulk_clean_admin_menu() {
    add_submenu_page(
        'conbook-events',
        'Bulk Clean Events',
        'Bulk Clean',
        'manage_options',
        'conbook-bulk-clean',
        'conbook_bulk_clean_page'
    );
}

function conbook_bulk_clean_page() {
    if ( isset($_POST['conbook_bulk_delete']) && check_admin_referer('conbook_bulk_delete_nonce') ) {
        global $wpdb;

        // Example: Only delete events with 'test' in the title
        $events = get_posts([
            'post_type' => 'event',
            'posts_per_page' => -1,
            's' => 'test' // Adjust the search term if needed
        ]);

        foreach ( $events as $event ) {
            // Use the same deletion function we created
            conbook_delete_event_related_data( $event->ID );
            
            // Finally delete the event post
            wp_delete_post( $event->ID, true );
        }

        echo '<div class="notice notice-success"><p>All test events and related data have been deleted.</p></div>';
    }

    echo '<div class="wrap">';
    echo '<h1>Bulk Clean Test Events</h1>';
    echo '<form method="post">';
    wp_nonce_field('conbook_bulk_delete_nonce');
    echo '<p>Click the button below to delete all test events and all their associated data (tickets, registrations, payments, files, post meta).</p>';
    echo '<p><input type="submit" name="conbook_bulk_delete" class="button button-primary" value="Delete All Test Events"></p>';
    echo '</form>';
    echo '</div>';
}
