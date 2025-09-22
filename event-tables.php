<?php
/**
 * Creates the custom database tables for the plugin.
 *
 * This function is hooked to the `register_activation_hook` and runs when the plugin is
 * activated. It uses WordPress's `dbDelta` function to safely create or update the
 * necessary tables for event management, including `event_tickets`,
 * `event_payment_methods`, `event_registrations`, and `event_guests`. This
 * ensures the database schema is correctly set up without losing data on updates.
 *
 * The tables are prefixed with the WordPress database prefix to avoid conflicts.
 */

function event_tables_create() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset_collate = $wpdb->get_charset_collate();

    /**
     * Section: Table Name Definitions
     *
     * Defines the full table names by prepending the WordPress database prefix. This is a
     * best practice to ensure table names are unique and compatible with different WordPress
     * installations.
     */
    $table_tickets       = $wpdb->prefix . 'event_tickets';
    $table_payments      = $wpdb->prefix . 'event_payment_methods';
    $table_registrations = $wpdb->prefix . 'event_registrations';
    $table_guests        = $wpdb->prefix . 'event_guests';

    /**
     * Section: Table Name Definitions
     *
     * Defines the full table names by prepending the WordPress database prefix. This is a
     * best practice to ensure table names are unique and compatible with different WordPress
     * installations.
     */
    $sql_tickets = "CREATE TABLE $table_tickets (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        PRIMARY KEY (id),
        KEY idx_event_id (event_id)
    ) $charset_collate;";

    // Event Payment Methods
    $sql_payments = "CREATE TABLE $table_payments (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        details TEXT NULL,
        PRIMARY KEY (id),
        KEY idx_event_id (event_id)
    ) $charset_collate;";

    // Event Registrations
    $sql_registrations = "CREATE TABLE $table_registrations (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        event_id BIGINT(20) UNSIGNED NOT NULL,
        ticket_id BIGINT(20) UNSIGNED NOT NULL,
        payment_method_id BIGINT(20) UNSIGNED NOT NULL,
        proof_id BIGINT(20) UNSIGNED DEFAULT NULL,
        status ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_event_id (event_id),
        KEY idx_ticket_id (ticket_id),
        KEY idx_user_id (user_id),
        KEY idx_payment_method_id (payment_method_id),
        KEY idx_status (status)
    ) $charset_collate;";

    // Event Guests (linked to registrations)
    $sql_guests = "CREATE TABLE $table_guests (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        registration_id BIGINT(20) UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        contact_number VARCHAR(50) NULL,
        token CHAR(36) NOT NULL,  -- UUID v4 token
        status ENUM('Pending','Checked In','No Show','Cancelled') NOT NULL DEFAULT 'Pending',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY idx_token (token),       -- unique constraint on token
        KEY idx_registration_id (registration_id),
        KEY idx_status (status)
    ) $charset_collate;";

    /**
     * Section: Execute Table Creation
     *
     * Executes the `dbDelta` function for each table. This function intelligently compares the
     * desired schema with the current database structure and makes the necessary additions,
     * changes, or removals, without deleting existing data.
     */
    dbDelta($sql_tickets);
    dbDelta($sql_payments);
    dbDelta($sql_registrations);
    dbDelta($sql_guests);
}
register_activation_hook(__FILE__, 'event_tables_create');
