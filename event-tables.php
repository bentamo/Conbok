<?php
/**
 * Plugin Name: Event Tables
 * Description: Creates event-related tables (tickets, payment methods, registrations) on activation.
 * Version: 1.0
 * Author: Rae
 */

function event_tables_create() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset_collate = $wpdb->get_charset_collate();

    // Table names
    $table_tickets       = $wpdb->prefix . 'event_tickets';
    $table_payments      = $wpdb->prefix . 'event_payment_methods';
    $table_registrations = $wpdb->prefix . 'event_registrations';

    // Event Tickets
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
        event_id BIGINT(20) UNSIGNED NOT NULL,
        ticket_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        payment_method_id BIGINT(20) UNSIGNED NOT NULL,
        proof_id BIGINT(20) UNSIGNED DEFAULT NULL,
        status ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_event_id (event_id),
        KEY idx_ticket_id (ticket_id),
        KEY idx_user_id (user_id),
        KEY idx_payment_method_id (payment_method_id),
        KEY idx_status (status)
    ) $charset_collate;";

    // Run dbDelta (safe create/update)
    dbDelta($sql_tickets);
    dbDelta($sql_payments);
    dbDelta($sql_registrations);
}
register_activation_hook(__FILE__, 'event_tables_create');
