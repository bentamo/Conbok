<?php
/**
 * Plugin Name: Event Tables
 * Description: Creates event-related tables (tickets, payment methods, registrations, and guests) on plugin activation to store all event and user data.
 * Version: 1.0
 * Author: Rae
 *
 * @package ConBook
 * @subpackage Database
 */

/* ==============================================
 * SECTION 1: DATABASE TABLE CREATION
 * ============================================== */

/**
 * Creates the necessary database tables for the event management system.
 *
 * This function is hooked to the `register_activation_hook` and is executed
 * only when the plugin is activated. It defines four custom tables:
 * `event_tickets`, `event_payment_methods`, `event_registrations`, and
 * `event_guests`. It uses WordPress's `dbDelta` function to safely create
 * or update the tables, ensuring that existing data is not lost on subsequent
 * activations or updates.
 *
 * @since 1.0.0
 *
 * @return void
 */
function event_tables_create() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset_collate = $wpdb->get_charset_collate();

    // Table names
    /**
     * @var string The full table name for event tickets.
     */
    $table_tickets = $wpdb->prefix . 'event_tickets';

    /**
     * @var string The full table name for event payment methods.
     */
    $table_payments = $wpdb->prefix . 'event_payment_methods';

    /**
     * @var string The full table name for user registrations.
     */
    $table_registrations = $wpdb->prefix . 'event_registrations';

    /**
     * @var string The full table name for event guests.
     */
    $table_guests = $wpdb->prefix . 'event_guests';

    /* ==============================================
     * SECTION 1.1: SQL DEFINITIONS
     * ============================================== */

    // Event Tickets table
    $sql_tickets = "CREATE TABLE $table_tickets (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        PRIMARY KEY (id),
        KEY idx_event_id (event_id)
    ) $charset_collate;";

    // Event Payment Methods table
    $sql_payments = "CREATE TABLE $table_payments (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id BIGINT(20) UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        details TEXT NULL,
        PRIMARY KEY (id),
        KEY idx_event_id (event_id)
    ) $charset_collate;";

    // Event Registrations table
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

    // Event Guests table
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
        UNIQUE KEY idx_token (token),      -- unique constraint on token
        KEY idx_registration_id (registration_id),
        KEY idx_status (status)
    ) $charset_collate;";

    /* ==============================================
     * SECTION 1.2: EXECUTION
     * ============================================== */

    // Run dbDelta to safely create/update the tables
    dbDelta($sql_tickets);
    dbDelta($sql_payments);
    dbDelta($sql_registrations);
    dbDelta($sql_guests);
}
register_activation_hook(__FILE__, 'event_tables_create');