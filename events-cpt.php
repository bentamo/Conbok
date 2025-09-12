<?php
/**
 * Plugin Name: Events CPT + Frontend Form
 * Description: Custom Events post type with a frontend [create-event] form (image + tickets supported).
 * Version: 1.0
 * Author: Rae
 */

// -------------------------------
// 1. Register Events CPT
// -------------------------------
function events_cpt_register() {
    $args = [
        'labels' => [
            'name'          => 'Events',
            'singular_name' => 'Event',
        ],
        'public'      => true,
        'has_archive' => true,
        'menu_icon'   => 'dashicons-calendar',
        'supports'    => ['title', 'editor', 'thumbnail'],
        'rewrite'     => ['slug' => 'events'],
    ];
    register_post_type('event', $args);
}
add_action('init', 'events_cpt_register');

// -------------------------------
// 2. Shortcode: [create-event]
// -------------------------------
require_once __DIR__ . '/create-event.php';

// -------------------------------
// 3. Handle Frontend Form Submission
// -------------------------------
function conbook_handle_create_event() {
    if (
        !isset($_POST['conbook_create_event_nonce_field']) ||
        !wp_verify_nonce($_POST['conbook_create_event_nonce_field'], 'conbook_create_event_nonce')
    ) {
        wp_die('Security check failed.');
    }

    // Collect form values
    $title       = sanitize_text_field($_POST['event-title'] ?? '');
    $start_date  = sanitize_text_field($_POST['start-date'] ?? '');
    $end_date    = sanitize_text_field($_POST['end-date'] ?? '');
    $start_time  = sanitize_text_field($_POST['start-time'] ?? '');
    $end_time    = sanitize_text_field($_POST['end-time'] ?? '');
    $location    = sanitize_text_field($_POST['location'] ?? '');
    $description = sanitize_textarea_field($_POST['description'] ?? '');

    // Tickets
    $tickets = [];
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'ticket_name_') === 0) {
            $i = str_replace('ticket_name_', '', $key);
            $tickets[] = [
                'name'        => sanitize_text_field($value),
                'price'       => floatval($_POST['ticket_price_' . $i] ?? 0),
                'description' => sanitize_textarea_field($_POST['ticket_description_' . $i] ?? ''),
            ];
        }
    }

    // Payment Methods
    $payments = [];
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'payment_name_') === 0) {
            $i = str_replace('payment_name_', '', $key);
            $payments[] = [
                'name'    => sanitize_text_field($value),
                'details' => sanitize_textarea_field($_POST['payment_details_' . $i] ?? ''),
            ];
        }
    }

    // Insert Event post with logged-in user as author
    $event_id = wp_insert_post([
        'post_type'    => 'event',
        'post_title'   => $title,
        'post_content' => $description,
        'post_status'  => 'publish',
        'post_author'  => get_current_user_id(),
    ]);

    if ($event_id) {
        // Save custom fields
        update_post_meta($event_id, '_start_date', $start_date);
        update_post_meta($event_id, '_end_date', $end_date);
        update_post_meta($event_id, '_start_time', $start_time);
        update_post_meta($event_id, '_end_time', $end_time);
        update_post_meta($event_id, '_location', $location);

        // Insert tickets into custom table
        global $wpdb;
        $table_tickets  = $wpdb->prefix . 'event_tickets';
        $table_payments = $wpdb->prefix . 'event_payment_methods';

        foreach ($tickets as $ticket) {
            $wpdb->insert($table_tickets, [
                'event_id'    => $event_id,
                'name'        => $ticket['name'],
                'description' => $ticket['description'],
                'price'       => $ticket['price'],
            ]);
        }

        // Insert payments into custom table
        foreach ($payments as $payment) {
            $wpdb->insert($table_payments, [
                'event_id' => $event_id,
                'name'     => $payment['name'],
                'details'  => $payment['details'],
            ]);
        }

        // Handle image upload
        if (!empty($_FILES['event_image']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $attachment_id = media_handle_upload('event_image', $event_id);
            if (!is_wp_error($attachment_id)) {
                set_post_thumbnail($event_id, $attachment_id);
            }
        }
    }

    wp_redirect(home_url('/view-events/'));
    exit;
}
add_action('admin_post_conbook_create_event', 'conbook_handle_create_event');
add_action('admin_post_nopriv_conbook_create_event', 'conbook_handle_create_event');

// -------------------------------
// 4. Delete Event Featured Image on Permanent Delete
// -------------------------------
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

// -------------------------------
// 5. Admin Meta Box for Event Details (Tickets + Registrants)
// -------------------------------
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

function conbook_render_event_metabox($post) {
    global $wpdb;
    wp_nonce_field('conbook_save_event_meta', 'conbook_event_meta_nonce');

    // Load event meta
    $start_date = get_post_meta($post->ID, '_start_date', true);
    $end_date   = get_post_meta($post->ID, '_end_date', true);
    $start_time = get_post_meta($post->ID, '_start_time', true);
    $end_time   = get_post_meta($post->ID, '_end_time', true);
    $location   = get_post_meta($post->ID, '_location', true);

    // Load tickets (from DB)
    $tickets_table = $wpdb->prefix . 'event_tickets';
    $tickets = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $tickets_table WHERE event_id = %d", $post->ID),
        ARRAY_A
    );

    // Load registrants
    $registrations_table = $wpdb->prefix . 'event_registrations';
    $registrants = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $registrations_table WHERE event_id = %d", $post->ID),
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
                    <td><input type="text" name="ticket_name_<?php echo $ticket['id']; ?>" value="<?php echo esc_attr($ticket['name']); ?>"></td>
                    <td><input type="number" step="0.01" name="ticket_price_<?php echo $ticket['id']; ?>" value="<?php echo esc_attr($ticket['price']); ?>"></td>
                    <td><input type="text" name="ticket_desc_<?php echo $ticket['id']; ?>" value="<?php echo esc_attr($ticket['description']); ?>"></td>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const addBtn = document.getElementById('add-ticket-row');
        const tableBody = document.querySelector('#tickets-table tbody');

        addBtn.addEventListener('click', function() {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" name="new_ticket_name[]" placeholder="New Ticket Name"></td>
                <td><input type="number" step="0.01" name="new_ticket_price[]" placeholder="0.00"></td>
                <td><input type="text" name="new_ticket_desc[]" placeholder="Description"></td>
            `;
            tableBody.appendChild(row);
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
                        <select name="reg_status_<?php echo $r['id']; ?>">
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

function conbook_save_event_meta($post_id) {
    global $wpdb;
    if (!isset($_POST['conbook_event_meta_nonce']) ||
        !wp_verify_nonce($_POST['conbook_event_meta_nonce'], 'conbook_save_event_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Save general info
    update_post_meta($post_id, '_start_date', sanitize_text_field($_POST['start_date'] ?? ''));
    update_post_meta($post_id, '_end_date', sanitize_text_field($_POST['end_date'] ?? ''));
    update_post_meta($post_id, '_start_time', sanitize_text_field($_POST['start_time'] ?? ''));
    update_post_meta($post_id, '_end_time', sanitize_text_field($_POST['end_time'] ?? ''));
    update_post_meta($post_id, '_location', sanitize_text_field($_POST['location'] ?? ''));

    // Update existing tickets
    $tickets_table = $wpdb->prefix . 'event_tickets';
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'ticket_name_') === 0) {
            $id = intval(str_replace('ticket_name_', '', $key));
            $name = sanitize_text_field($_POST['ticket_name_' . $id] ?? '');
            $price = floatval($_POST['ticket_price_' . $id] ?? 0);
            $desc = sanitize_text_field($_POST['ticket_desc_' . $id] ?? '');
            $wpdb->update($tickets_table,
                ['name' => $name, 'price' => $price, 'description' => $desc],
                ['id' => $id]
            );
        }
    }

    // Insert new tickets
    if (!empty($_POST['new_ticket_name'])) {
        foreach ($_POST['new_ticket_name'] as $i => $name) {
            $name = sanitize_text_field($name);
            if ($name) { // only insert if name provided
                $price = floatval($_POST['new_ticket_price'][$i] ?? 0);
                $desc  = sanitize_text_field($_POST['new_ticket_desc'][$i] ?? '');
                $wpdb->insert($tickets_table, [
                    'event_id'    => $post_id,
                    'name'        => $name,
                    'price'       => $price,
                    'description' => $desc
                ]);
            }
        }
    }

    // Update registrant statuses
    $registrations_table = $wpdb->prefix . 'event_registrations';
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'reg_status_') === 0) {
            $id = intval(str_replace('reg_status_', '', $key));
            $status = sanitize_text_field($value);
            $wpdb->update($registrations_table,
                ['status' => $status],
                ['id' => $id]
            );
        }
    }
}
add_action('save_post_event', 'conbook_save_event_meta');

// -------------------------------
// 6. Add Event Author Column in Admin List
// -------------------------------
function conbook_add_event_author_column($columns) {
    $columns['event_author'] = 'Created By';
    return $columns;
}
add_filter('manage_event_posts_columns', 'conbook_add_event_author_column');

function conbook_render_event_author_column($column, $post_id) {
    if ($column === 'event_author') {
        $author_id = get_post_field('post_author', $post_id);
        $user_info = get_userdata($author_id);
        echo esc_html($user_info ? $user_info->display_name : 'User ID: ' . $author_id);
    }
}
add_action('manage_event_posts_custom_column', 'conbook_render_event_author_column', 10, 2);

// -------------------------------
// 7. Add Registrants Column in Admin List
// -------------------------------
function conbook_add_event_registrants_column($columns) {
    $columns['event_registrants'] = 'Registrants';
    return $columns;
}
add_filter('manage_event_posts_columns', 'conbook_add_event_registrants_column');

function conbook_render_event_registrants_column($column, $post_id) {
    if ($column === 'event_registrants') {
        global $wpdb;
        $table_registrations = $wpdb->prefix . 'event_registrations';

        // Count how many people registered for this event
        $count = $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $table_registrations WHERE event_id = %d", $post_id)
        );

        if ($count > 0) {
            // Link to filterable Registrations (you can later build a full admin page if needed)
            echo '<strong>' . intval($count) . '</strong> registered';
        } else {
            echo '0';
        }
    }
}
add_action('manage_event_posts_custom_column', 'conbook_render_event_registrants_column', 10, 2);
