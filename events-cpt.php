<?php
/**
 * Plugin Name: Events CPT + Frontend Form
 * Description: Custom Events post type with a frontend [create-event] form (image + tickets supported). This file contains the main plugin logic for event creation, editing, and management, including custom post type registration, form handling, and admin features.
 * Version: 1.0
 * Author: Rae
 *
 * @package ConBook
 * @subpackage Core
 */

/* ==============================================
 * SECTION 1: CUSTOM POST TYPE REGISTRATION
 * ============================================== */

/**
 * Registers the 'event' custom post type.
 *
 * This function defines a custom post type for "Events" and sets its properties,
 * including labels, public visibility, archive support, a calendar icon, and
 * supported features like 'title', 'editor', and 'thumbnail'. The rewrite slug is
 * set to 'events' for cleaner URLs.
 *
 * @since 1.0.0
 *
 * @return void
 */
function events_cpt_register() {
    $labels = [
        'name'          => _x('Events', 'post type general name', 'conbook'),
        'singular_name' => _x('Event', 'post type singular name', 'conbook'),
        'menu_name'     => _x('Events', 'admin menu', 'conbook'),
        'name_admin_bar' => _x('Event', 'add new on admin bar', 'conbook'),
        'add_new'       => _x('Add New', 'event', 'conbook'),
        'add_new_item'  => __('Add New Event', 'conbook'),
        'new_item'      => __('New Event', 'conbook'),
        'edit_item'     => __('Edit Event', 'conbook'),
        'view_item'     => __('View Event', 'conbook'),
        'all_items'     => __('All Events', 'conbook'),
        'search_items'  => __('Search Events', 'conbook'),
        'parent_item_colon' => __('Parent Events:', 'conbook'),
        'not_found'     => __('No events found.', 'conbook'),
        'not_found_in_trash' => __('No events found in Trash.', 'conbook'),
    ];

    $args = [
        'labels'      => $labels,
        'public'      => true,
        'has_archive' => true,
        'menu_icon'   => 'dashicons-calendar',
        'supports'    => ['title', 'editor', 'thumbnail'],
        'rewrite'     => ['slug' => 'events'],
    ];

    register_post_type('event', $args);
}
add_action('init', 'events_cpt_register');

/* ==============================================
 * SECTION 2: FRONTEND FORM HANDLING & SHORTCODE
 * ============================================== */

// Shortcode: [create-event]
// The shortcode itself is included from an external file for better organization.
require_once __DIR__ . '/create-event.php';

/**
 * Handles the frontend form submission for creating or editing an event.
 *
 * This function is hooked to both `admin_post_` and `admin_post_nopriv_` actions
 * to process the form data. It performs security checks, sanitizes all input,
 * and then either inserts a new post or updates an existing one. It also handles
 * the featured image upload and inserts/updates event tickets and payment methods
 * in their respective custom database tables.
 *
 * @since 1.0.0
 *
 * @return void
 */
function conbook_handle_create_event() {
    // SECURITY: Verify nonce for security.
    if (!isset($_POST['conbook_create_event_nonce_field']) || 
        !wp_verify_nonce($_POST['conbook_create_event_nonce_field'], 'conbook_create_event_nonce')) {
        wp_die('Security check failed');
    }

    // SECURITY: Ensure user is logged in to create or edit events.
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to create or edit an event');
    }

    global $wpdb;
    $table_tickets = $wpdb->prefix . 'event_tickets';
    $table_payments = $wpdb->prefix . 'event_payment_methods';

    // SANITIZATION: Sanitize all form input fields.
    $title       = sanitize_text_field($_POST['event-title']);
    $description = sanitize_textarea_field($_POST['description']);
    $location    = sanitize_text_field($_POST['location']);
    $start_date  = sanitize_text_field($_POST['start-date']);
    $end_date    = sanitize_text_field($_POST['end-date']);
    $start_time  = sanitize_text_field($_POST['start-time']);
    $end_time    = sanitize_text_field($_POST['end-time']);
    $event_id    = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

    // 1. Generate a unique slug for new events.
    function conbook_generate_random_string($length = 8) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $string;
    }

    if ($event_id) {
        // Editing existing event.
        $event = get_post($event_id);

        // SECURITY: Check if user has permission to edit this specific event.
        if (
            !$event ||
            $event->post_type !== 'event' ||
            (get_current_user_id() !== intval($event->post_author) && !current_user_can('manage_options'))
        ) {
            wp_die('You are not allowed to edit this event.');
        }

        $slug = $event->post_name; // keep original slug
    } else {
        // Creating new event: generate unique slug.
        $random_suffix = conbook_generate_random_string(8);
        $slug = 'evt-' . $random_suffix;
        while (get_page_by_path($slug, OBJECT, 'event')) {
            $random_suffix = conbook_generate_random_string(8);
            $slug = 'evt-' . $random_suffix;
        }
    }

    // 2. Insert or update the event post.
    if ($event_id) {
        // Update existing event.
        $update_result = wp_update_post([
            'ID'           => $event_id,
            'post_title'   => $title,
            'post_content' => $description,
            // 'post_name' is not needed here as we keep the existing slug.
        ]);
        if (is_wp_error($update_result)) {
            wp_die('Error updating event');
        }
    } else {
        // Create new event.
        $event_id = wp_insert_post([
            'post_type'    => 'event',
            'post_title'   => $title,
            'post_content' => $description,
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
            'post_name'    => $slug,
        ]);
        if (is_wp_error($event_id)) {
            wp_die('Error creating event');
        }
    }

    // 3. Update event meta fields.
    update_post_meta($event_id, '_location', $location);
    update_post_meta($event_id, '_start_date', $start_date);
    update_post_meta($event_id, '_end_date', $end_date);
    update_post_meta($event_id, '_start_time', $start_time);
    update_post_meta($event_id, '_end_time', $end_time);

    $start_datetime = date('Y-m-d H:i:s', strtotime("$start_date $start_time"));
    $end_datetime   = date('Y-m-d H:i:s', strtotime("$end_date $end_time"));
    update_post_meta($event_id, '_start_datetime', $start_datetime);
    update_post_meta($event_id, '_end_datetime', $end_datetime);

    // 4. Handle featured image upload.
    if (!empty($_FILES['event_image']['name'])) {
        // Ensure necessary files are loaded for media handling.
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $attachment_id = media_handle_upload('event_image', $event_id);
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($event_id, $attachment_id);
        }
    }

    // 5. Remove old tickets & payments before inserting new ones (for updates).
    if ($event_id) {
        $wpdb->delete($table_tickets, ['event_id' => $event_id]);
        $wpdb->delete($table_payments, ['event_id' => $event_id]);
    }

    // 6. Insert new tickets based on form submission.
    $ticket_index = 1;
    while (isset($_POST["ticket_name_$ticket_index"])) {
        $ticket_name  = sanitize_text_field($_POST["ticket_name_$ticket_index"]);
        $ticket_price = floatval($_POST["ticket_price_$ticket_index"]);
        $ticket_desc  = sanitize_textarea_field($_POST["ticket_description_$ticket_index"]);

        $wpdb->insert(
            $table_tickets,
            [
                'event_id'    => $event_id,
                'name'        => $ticket_name,
                'price'       => $ticket_price,
                'description' => $ticket_desc,
            ]
        );
        $ticket_index++;
    }

    // Insert new payment methods based on form submission.
    $payment_index = 1;
    while (isset($_POST["payment_name_$payment_index"])) {
        $payment_name    = sanitize_text_field($_POST["payment_name_$payment_index"]);
        $payment_details = sanitize_text_field($_POST["payment_details_$payment_index"]);

        $wpdb->insert(
            $table_payments,
            [
                'event_id' => $event_id,
                'name'     => $payment_name,
                'details'  => $payment_details,
            ]
        );
        $payment_index++;
    }

    // 7. Redirect the user to the event list page after submission.
    wp_safe_redirect(home_url('/conbook/view-events/'));
    exit;
}
add_action('admin_post_conbook_create_event', 'conbook_handle_create_event');
add_action('admin_post_nopriv_conbook_create_event', 'conbook_handle_create_event');

/* ==============================================
 * SECTION 3: ADMIN CUSTOMIZATIONS
 * ============================================== */

/**
 * Deletes the featured image of an event when the post is permanently deleted.
 *
 * This function is hooked to `before_delete_post`. It checks if the post type is
 * 'event' and, if so, retrieves and deletes the associated featured image to
 * prevent orphan media files. The second parameter of `wp_delete_attachment`
 * ensures the file is also removed from the server.
 *
 * @since 1.0.0
 *
 * @param int $post_id The ID of the post being deleted.
 * @return void
 */
function conbook_delete_event_image($post_id) {
    if (get_post_type($post_id) !== 'event') {
        return;
    }

    $thumbnail_id = get_post_thumbnail_id($post_id);
    if ($thumbnail_id) {
        wp_delete_attachment($thumbnail_id, true);
    }
}
add_action('before_delete_post', 'conbook_delete_event_image');

/**
 * Adds custom meta boxes to the 'event' post type editor screen.
 *
 * This function adds a meta box titled "Event Details" to the admin
 * interface for events. This box will display and allow editing of
 * general event info, tickets, payment methods, and registrants.
 * The meta box content is rendered by the `conbook_render_event_metabox` function.
 *
 * @since 1.0.0
 *
 * @return void
 */
function conbook_add_event_metaboxes() {
    add_meta_box(
        'conbook_event_details',
        'Event Details',
        'conbook_render_event_metabox',
        'event',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'conbook_add_event_metaboxes');

/**
 * Renders the content of the 'Event Details' meta box.
 *
 * This function retrieves and displays event metadata, tickets, payment methods,
 * and registrant data from the database. It uses HTML and PHP to create a form-like
 * interface for viewing and editing this information directly within the
 * WordPress admin panel. It also includes inline JavaScript to dynamically
 * add new rows for tickets and payment methods.
 *
 * @since 1.0.0
 *
 * @param WP_Post $post The current post object.
 * @return void
 */
function conbook_render_event_metabox($post) {
    global $wpdb;
    wp_nonce_field('conbook_save_event_meta', 'conbook_event_meta_nonce');

    // Load event meta.
    $start_date = get_post_meta($post->ID, '_start_date', true);
    $end_date   = get_post_meta($post->ID, '_end_date', true);
    $start_time = get_post_meta($post->ID, '_start_time', true);
    $end_time   = get_post_meta($post->ID, '_end_time', true);
    $location   = get_post_meta($post->ID, '_location', true);

    // Table names.
    $table_tickets       = $wpdb->prefix . 'event_tickets';
    $table_payments      = $wpdb->prefix . 'event_payment_methods';
    $table_registrations = $wpdb->prefix . 'event_registrations';

    // Load tickets associated with this event.
    $tickets = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table_tickets WHERE event_id = %d", $post->ID),
        ARRAY_A
    );

    // Load registrants for this event.
    $registrants = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table_registrations WHERE event_id = %d", $post->ID),
        ARRAY_A
    );

    // Load payment methods for this event.
    $payments = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table_payments WHERE event_id = %d", $post->ID),
        ARRAY_A
    );
    ?>
    <h3>General Info</h3>
    <p><label>Start Date:</label><br>
        <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>"></p>
    <p><label>End Date:</label><br>
        <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>"></p>
    <p><label>Start Time:</label><br>
        <input type="time" name="start_time" value="<?php echo esc_attr($start_time); ?>"></p>
    <p><label>End Time:</label><br>
        <input type="time" name="end_time" value="<?php echo esc_attr($end_time); ?>"></p>
    <p><label>Location:</label><br>
        <input type="text" name="location" style="width:100%;" value="<?php echo esc_attr($location); ?>"></p>

    <h3>Tickets</h3>
    <table class="widefat" id="tickets-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Price</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($tickets): ?>
            <?php foreach ($tickets as $ticket): ?>
                <tr>
                    <td><input type="text" name="ticket_name_<?php echo intval($ticket['id']); ?>" value="<?php echo esc_attr($ticket['name']); ?>"></td>
                    <td><input type="number" step="0.01" name="ticket_price_<?php echo intval($ticket['id']); ?>" value="<?php echo esc_attr($ticket['price']); ?>"></td>
                    <td><input type="text" name="ticket_desc_<?php echo intval($ticket['id']); ?>" value="<?php echo esc_attr($ticket['description']); ?>"></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="3">No tickets yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <p>
        <button type="button" class="button" id="add-ticket-row">+ Add Ticket</button>
    </p>

    <h3>Payment Methods</h3>
    <table class="widefat" id="payments-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($payments): ?>
            <?php foreach ($payments as $p): ?>
                <tr>
                    <td><input type="text" name="payment_name_<?php echo intval($p['id']); ?>" value="<?php echo esc_attr($p['name']); ?>"></td>
                    <td><input type="text" name="payment_details_<?php echo intval($p['id']); ?>" value="<?php echo esc_attr($p['details']); ?>"></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="2">No payment methods yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <p>
        <button type="button" class="button" id="add-payment-row">+ Add Payment Method</button>
    </p>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add Ticket Row
        document.getElementById('add-ticket-row').addEventListener('click', function() {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" name="new_ticket_name[]" placeholder="New Ticket Name"></td>
                <td><input type="number" step="0.01" name="new_ticket_price[]" placeholder="0.00"></td>
                <td><input type="text" name="new_ticket_desc[]" placeholder="Description"></td>
            `;
            document.querySelector('#tickets-table tbody').appendChild(row);
        });

        // Add Payment Row
        document.getElementById('add-payment-row').addEventListener('click', function() {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" name="new_payment_name[]" placeholder="Payment Method"></td>
                <td><input type="text" name="new_payment_details[]" placeholder="Details"></td>
            `;
            document.querySelector('#payments-table tbody').appendChild(row);
        });
    });
    </script>

    <h3>Registrants</h3>
    <table class="widefat">
        <thead>
            <tr>
                <th>User ID</th>
                <th>Ticket ID</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($registrants): ?>
            <?php foreach ($registrants as $r): ?>
                <tr>
                    <td><?php echo esc_html($r['user_id']); ?></td>
                    <td><?php echo esc_html($r['ticket_id']); ?></td>
                    <td>
                        <select name="reg_status_<?php echo intval($r['id']); ?>">
                            <option value="pending"  <?php selected($r['status'], 'pending'); ?>>Pending</option>
                            <option value="accepted" <?php selected($r['status'], 'accepted'); ?>>Accepted</option>
                            <option value="declined" <?php selected($r['status'], 'declined'); ?>>Declined</option>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="3">No registrants yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php
}

/**
 * Saves the custom meta box data when an event post is saved or updated.
 *
 * This function is hooked to the `save_post_event` action. It performs a nonce
 * check and user capability check for security. It then sanitizes all input
 * from the meta box form and uses the `update_post_meta` and `$wpdb` methods
 * to save changes to the event's custom fields, tickets, payment methods,
 * and registrant statuses.
 *
 * @since 1.0.0
 *
 * @param int $post_id The ID of the post being saved.
 * @return void
 */
function conbook_save_event_meta($post_id) {
    global $wpdb;
    // SECURITY: Verify nonce and user permissions.
    if (!isset($_POST['conbook_event_meta_nonce']) ||
        !wp_verify_nonce($_POST['conbook_event_meta_nonce'], 'conbook_save_event_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Table names.
    $table_tickets       = $wpdb->prefix . 'event_tickets';
    $table_payments      = $wpdb->prefix . 'event_payment_methods';
    $table_registrations = $wpdb->prefix . 'event_registrations';

    // Save general info.
    update_post_meta($post_id, '_start_date', sanitize_text_field($_POST['start_date'] ?? ''));
    update_post_meta($post_id, '_end_date', sanitize_text_field($_POST['end_date'] ?? ''));
    update_post_meta($post_id, '_start_time', sanitize_text_field($_POST['start_time'] ?? ''));
    update_post_meta($post_id, '_end_time', sanitize_text_field($_POST['end_time'] ?? ''));
    update_post_meta($post_id, '_location', sanitize_text_field($_POST['location'] ?? ''));

    // Combine into full datetime fields (24-hour format).
    $start_date     = sanitize_text_field($_POST['start_date'] ?? '');
    $end_date       = sanitize_text_field($_POST['end_date'] ?? '');
    $start_time     = sanitize_text_field($_POST['start_time'] ?? '');
    $end_time       = sanitize_text_field($_POST['end_time'] ?? '');
    $start_datetime = date('Y-m-d H:i:s', strtotime("$start_date $start_time"));
    $end_datetime   = date('Y-m-d H:i:s', strtotime("$end_date $end_time"));
    update_post_meta($post_id, '_start_datetime', $start_datetime);
    update_post_meta($post_id, '_end_datetime', $end_datetime);

    // Update existing tickets.
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'ticket_name_') === 0) {
            $id = intval(str_replace('ticket_name_', '', $key));
            $name = sanitize_text_field($_POST['ticket_name_' . $id] ?? '');
            $price = floatval($_POST['ticket_price_' . $id] ?? 0);
            $desc = sanitize_text_field($_POST['ticket_desc_' . $id] ?? '');
            $wpdb->update(
                $table_tickets,
                ['name' => $name, 'price' => $price, 'description' => $desc],
                ['id' => $id]
            );
        }
    }

    // Insert new tickets.
    if (!empty($_POST['new_ticket_name'])) {
        foreach ($_POST['new_ticket_name'] as $i => $name) {
            $name = sanitize_text_field($name);
            if ($name) {
                $price = floatval($_POST['new_ticket_price'][$i] ?? 0);
                $desc  = sanitize_text_field($_POST['new_ticket_desc'][$i] ?? '');
                $wpdb->insert(
                    $table_tickets,
                    [
                        'event_id'    => $post_id,
                        'name'        => $name,
                        'price'       => $price,
                        'description' => $desc
                    ]
                );
            }
        }
    }

    // Update existing payments.
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'payment_name_') === 0) {
            $id = intval(str_replace('payment_name_', '', $key));
            $name = sanitize_text_field($_POST['payment_name_' . $id] ?? '');
            $details = sanitize_textarea_field($_POST['payment_details_' . $id] ?? '');
            $wpdb->update(
                $table_payments,
                ['name' => $name, 'details' => $details],
                ['id' => $id]
            );
        }
    }

    // Insert new payments.
    if (!empty($_POST['new_payment_name'])) {
        foreach ($_POST['new_payment_name'] as $i => $name) {
            $name = sanitize_text_field($name);
            if ($name) {
                $details = sanitize_textarea_field($_POST['new_payment_details'][$i] ?? '');
                $wpdb->insert(
                    $table_payments,
                    [
                        'event_id' => $post_id,
                        'name'     => $name,
                        'details'  => $details
                    ]
                );
            }
        }
    }

    // Update registrant statuses.
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'reg_status_') === 0) {
            $id = intval(str_replace('reg_status_', '', $key));
            $status = sanitize_text_field($value);
            $wpdb->update(
                $table_registrations,
                ['status' => $status],
                ['id' => $id]
            );
        }
    }
}
add_action('save_post_event', 'conbook_save_event_meta');

/* ==============================================
 * SECTION 4: ADMIN COLUMN CUSTOMIZATION
 * ============================================== */

/**
 * Adds a new 'Created By' column to the admin list view for events.
 *
 * This function is hooked to `manage_event_posts_columns` and adds a custom
 * column with the header "Created By" to the list of events in the admin
 * dashboard.
 *
 * @since 1.0.0
 *
 * @param array $columns An associative array of column headers.
 * @return array The filtered array of columns.
 */
function conbook_add_event_author_column($columns) {
    $columns['event_author'] = 'Created By';
    return $columns;
}
add_filter('manage_event_posts_columns', 'conbook_add_event_author_column');

/**
 * Renders the content for the 'Created By' custom column.
 *
 * This function is hooked to `manage_event_posts_custom_column`. It checks
 * for the custom column `event_author` and retrieves the display name of
 * the event's author to display in that column.
 *
 * @since 1.0.0
 *
 * @param string $column The name of the column.
 * @param int    $post_id The ID of the current post.
 * @return void
 */
function conbook_render_event_author_column($column, $post_id) {
    if ($column === 'event_author') {
        $author_id = get_post_field('post_author', $post_id);
        $user_info = get_userdata($author_id);
        echo esc_html($user_info ? $user_info->display_name : 'User ID: ' . $author_id);
    }
}
add_action('manage_event_posts_custom_column', 'conbook_render_event_author_column', 10, 2);

/**
 * Adds a new 'Registrants' column to the admin list view for events.
 *
 * This function adds a custom column with the header "Registrants" to
 * the list of events, providing a quick count of registrations.
 *
 * @since 1.0.0
 *
 * @param array $columns An associative array of column headers.
 * @return array The filtered array of columns.
 */
function conbook_add_event_registrants_column($columns) {
    $columns['event_registrants'] = 'Registrants';
    return $columns;
}
add_filter('manage_event_posts_columns', 'conbook_add_event_registrants_column');

/**
 * Renders the content for the 'Registrants' custom column.
 *
 * This function queries the `event_registrations` table to count the number of
 * registrations for a specific event and displays the count in the `event_registrants`
 * column.
 *
 * @since 1.0.0
 *
 * @param string $column The name of the column.
 * @param int    $post_id The ID of the current post.
 * @return void
 */
function conbook_render_event_registrants_column($column, $post_id) {
    if ($column === 'event_registrants') {
        global $wpdb;
        $table_registrations = $wpdb->prefix . 'event_registrations';

        // Count how many people registered for this event.
        $count = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $table_registrations WHERE event_id = %d", $post_id)
        );

        if ($count > 0) {
            // Link to filterable Registrations (you can later build a full admin page if needed).
            echo '<strong>' . intval($count) . '</strong> registered';
        } else {
            echo '0';
        }
    }
}
add_action('manage_event_posts_custom_column', 'conbook_render_event_registrants_column', 10, 2);

/* ==============================================
 * SECTION 5: REWRITE RULES & QUERY VARS
 * ============================================== */

/**
 * Adds custom rewrite rules to enable pretty URLs for specific event-related pages.
 *
 * This section defines rewrite rules to allow for URL structures like
 * `/event-page/event-slug/`, `/event-dashboard/event-slug/`, and so on.
 * It maps these user-friendly URLs to the correct WordPress pages and passes
 * the `event_slug` as a query variable.
 *
 * @since 1.0.0
 *
 * @return void
 */
add_action('init', function() {
    add_rewrite_rule(
        '^event-page/([^/]+)/?$',
        'index.php?pagename=event-page&event_slug=$matches[1]',
        'top'
    );
});

add_action('init', function() {
    add_rewrite_rule(
        '^event-dashboard/([^/]+)/?$',
        'index.php?pagename=event-dashboard&event_slug=$matches[1]',
        'top'
    );
});

add_action('init', function() {
    add_rewrite_rule(
        '^create-event/([^/]+)/?$',
        'index.php?pagename=create-event&event_slug=$matches[1]',
        'top'
    );
});

add_action('init', function() {
    add_rewrite_rule(
        '^event-registration/([^/]+)/?$',
        'index.php?pagename=event-registration&event_slug=$matches[1]',
        'top'
    );
});

/**
 * Adds the 'event_slug' query variable to the list of recognized query variables.
 *
 * This function ensures that WordPress recognizes `event_slug` as a valid
 * query variable, allowing it to be used in template files via `get_query_var()`.
 *
 * @since 1.0.0
 *
 * @param array $vars The array of query variables.
 * @return array The filtered array of query variables.
 */
function conbook_event_query_vars($vars) {
    $vars[] = 'event_slug';
    return $vars;
}
add_filter('query_vars', 'conbook_event_query_vars');