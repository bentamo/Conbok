<?php
/**
 * Plugin Name: ConBook Events
 * Description: MVP Events Management (Event CPT + Tickets, Registrations, Payments) with Event Meta Fields.
 * Version: 1.4
 * Author: Miko
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

    $start_date   = get_post_meta( $post->ID, '_start_date', true );
    $end_date     = get_post_meta( $post->ID, '_end_date', true );
    $start_time   = get_post_meta( $post->ID, '_start_time', true );
    $end_time     = get_post_meta( $post->ID, '_end_time', true );
    $location     = get_post_meta( $post->ID, '_location', true );
    $description  = get_post_meta( $post->ID, '_description', true );
    $ticket_opts  = get_post_meta( $post->ID, '_ticket_options', true );
    $ticket_opts  = is_array( $ticket_opts ) ? $ticket_opts : [];
    $event_pic    = get_post_meta( $post->ID, '_event_picture', true );

    echo '<p><label>Start Date: <input type="date" name="start_date" value="' . esc_attr( $start_date ) . '"></label></p>';
    echo '<p><label>End Date: <input type="date" name="end_date" value="' . esc_attr( $end_date ) . '"></label></p>';
    echo '<p><label>Start Time: <input type="time" name="start_time" value="' . esc_attr( $start_time ) . '"></label></p>';
    echo '<p><label>End Time: <input type="time" name="end_time" value="' . esc_attr( $end_time ) . '"></label></p>';
    echo '<p><label>Location: <input type="text" name="location" value="' . esc_attr( $location ) . '" style="width:100%;"></label></p>';

    echo '<p><label>Description:<br>';
    echo '<textarea name="description" rows="4" style="width:100%;">' . esc_textarea( $description ) . '</textarea></label></p>';

    echo '<p><strong>Ticket Options:</strong></p>';
    echo '<div id="ticket-options-wrapper">';
    if ( ! empty( $ticket_opts ) ) {
        foreach ( $ticket_opts as $index => $ticket ) {
            $name  = esc_attr( $ticket['name'] ?? '' );
            $price = esc_attr( $ticket['price'] ?? '' );
            echo '<div class="ticket-option">';
            echo '<input type="text" name="ticket_options['.$index.'][name]" placeholder="Ticket Name" value="'.$name.'" /> ';
            echo '<input type="number" step="0.01" name="ticket_options['.$index.'][price]" placeholder="Price" value="'.$price.'" /> ';
            echo '<button type="button" class="remove-ticket button">Remove</button>';
            echo '</div>';
        }
    }
    echo '</div>';
    echo '<p><button type="button" id="add-ticket" class="button">Add Ticket</button></p>';

    echo '<p><strong>Event Picture:</strong></p>';
    if ( $event_pic ) {
        echo '<div id="event-picture-preview"><img src="' . esc_url( wp_get_attachment_url( $event_pic ) ) . '" style="max-width:200px;display:block;margin-bottom:10px;">';
        echo '<button type="button" class="button remove-picture">Remove Picture</button></div>';
    }
    echo '<input type="file" name="event_picture" id="event_picture">';
    echo '<input type="hidden" name="event_picture_id" id="event_picture_id" value="' . esc_attr( $event_pic ) . '">';
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let wrapper = document.getElementById('ticket-options-wrapper');
        let addBtn = document.getElementById('add-ticket');
        addBtn.addEventListener('click', function(e) {
            e.preventDefault();
            let index = wrapper.querySelectorAll('.ticket-option').length;
            let div = document.createElement('div');
            div.classList.add('ticket-option');
            div.innerHTML = '<input type="text" name="ticket_options['+index+'][name]" placeholder="Ticket Name" /> ' +
                            '<input type="number" step="0.01" name="ticket_options['+index+'][price]" placeholder="Price" /> ' +
                            '<button type="button" class="remove-ticket button">Remove</button>';
            wrapper.appendChild(div);
        });
        wrapper.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-ticket')) {
                e.preventDefault();
                e.target.parentNode.remove();
            }
        });
        document.addEventListener('click', function(e){
            if(e.target.classList.contains('remove-picture')){
                e.preventDefault();
                document.getElementById('event_picture_id').value = '';
                document.getElementById('event-picture-preview').remove();
            }
        });
    });
    </script>
    <?php
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

    $fields = array( 'start_date', 'end_date', 'start_time', 'end_time', 'location', 'description' );
    foreach ( $fields as $field ) {
        if ( isset( $_POST[$field] ) ) {
            update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[$field] ) );
        }
    }

    if ( isset( $_POST['ticket_options'] ) && is_array( $_POST['ticket_options'] ) ) {
        $clean_tickets = [];
        foreach ( $_POST['ticket_options'] as $ticket ) {
            if ( !empty($ticket['name']) ) {
                $clean_tickets[] = [
                    'name'  => sanitize_text_field( $ticket['name'] ),
                    'price' => floatval( $ticket['price'] )
                ];
            }
        }
        update_post_meta( $post_id, '_ticket_options', $clean_tickets );
    } else {
        delete_post_meta( $post_id, '_ticket_options' );
    }

    if ( !empty($_FILES['event_picture']['name']) ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        $attachment_id = media_handle_upload( 'event_picture', $post_id );
        if ( ! is_wp_error( $attachment_id ) ) {
            update_post_meta( $post_id, '_event_picture', $attachment_id );
        }
    } else {
        if ( isset($_POST['event_picture_id']) && $_POST['event_picture_id'] === '' ) {
            delete_post_meta( $post_id, '_event_picture' );
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

    $table_event_payments = $wpdb->prefix . 'event_payments';
    $sql_event_payments = "CREATE TABLE $table_event_payments (
        payment_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        registration_id BIGINT(20) UNSIGNED NOT NULL,
        proof_of_payment VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (payment_id),
        KEY idx_registration_id (registration_id)
    ) $charset_collate;";

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
 * 5. Safe Deletion of Related Event Data
 */
add_action( 'before_delete_post', 'conbook_delete_event_related_data' );

function conbook_delete_event_related_data( $post_id ) {
    $post_type = get_post_type( $post_id );
    if ( $post_type !== 'event' ) return;

    global $wpdb;
    $wpdb->delete( $wpdb->postmeta, [ 'post_id' => $post_id ] );

    $registrations = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT registration_id FROM {$wpdb->prefix}event_registrations WHERE event_id = %d",
            $post_id
        )
    );

    foreach ( $registrations as $reg ) {
        $registration_id = $reg->registration_id;
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
        $wpdb->delete( "{$wpdb->prefix}event_payments", [ 'registration_id' => $registration_id ], [ '%d' ] );
        $wpdb->delete( "{$wpdb->prefix}event_registrations", [ 'registration_id' => $registration_id ], [ '%d' ] );
    }

    $wpdb->delete( "{$wpdb->prefix}event_tickets", [ 'event_id' => $post_id ], [ '%d' ] );
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
        $events = get_posts([
            'post_type' => 'event',
            'posts_per_page' => -1,
            's' => 'test'
        ]);
        foreach ( $events as $event ) {
            conbook_delete_event_related_data( $event->ID );
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

/**
 * 7. Frontend Form Handler (from Code #1)
 */
add_action('admin_post_conbook_create_event', 'conbook_handle_event_form');
add_action('admin_post_nopriv_conbook_create_event', 'conbook_handle_event_form');

function conbook_handle_event_form() {
    if (!isset($_POST['conbook_create_event_nonce_field']) || 
        !wp_verify_nonce($_POST['conbook_create_event_nonce_field'], 'conbook_create_event_nonce')) {
        wp_die('Security check failed');
    }
    global $wpdb;

    $event_id = wp_insert_post([
        'post_title'   => sanitize_text_field($_POST['location'] ?? 'Untitled Event'),
        'post_type'    => 'event',
        'post_status'  => 'publish',
        'post_content' => sanitize_textarea_field($_POST['description'] ?? ''),
    ]);
    if (is_wp_error($event_id)) {
        wp_die('Failed to create event.');
    }

    $fields = [
        'start_date'  => $_POST['start-date'] ?? '',
        'end_date'    => $_POST['end-date'] ?? '',
        'start_time'  => $_POST['start-time'] ?? '',
        'end_time'    => $_POST['end-time'] ?? '',
        'location'    => $_POST['location'] ?? '',
        'description' => $_POST['description'] ?? '',
    ];
    foreach ($fields as $key => $val) {
        update_post_meta($event_id, '_' . $key, sanitize_text_field($val));
    }

    if (!empty($_FILES['event_image']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_id = media_handle_upload('event_image', $event_id);
        if (!is_wp_error($attachment_id)) {
            update_post_meta($event_id, '_event_picture', $attachment_id);
        }
    }

    $ticket_index = 0;
    $ticket_opts = [];
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'ticket_name_') === 0) {
            $ticket_name  = sanitize_text_field($value);
            $ticket_price = floatval($_POST['ticket_price_' . $ticket_index] ?? 0);
            if (!empty($ticket_name)) {
                $wpdb->insert(
                    $wpdb->prefix . 'event_tickets',
                    [
                        'event_id'         => $event_id,
                        'ticket_name'      => $ticket_name,
                        'ticket_price'     => $ticket_price,
                        'ticket_description' => '',
                    ],
                    ['%d', '%s', '%f', '%s']
                );
                $ticket_opts[] = ['name' => $ticket_name, 'price' => $ticket_price];
            }
            $ticket_index++;
        }
    }
    if (!empty($ticket_opts)) {
        update_post_meta($event_id, '_ticket_options', $ticket_opts);
    }

    // Redirect to Event Dashboard (to be changed)
    wp_redirect(home_url('/thank-you/?event_created=1'));
    exit;
}
