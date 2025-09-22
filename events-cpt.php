<?php
/**
 * Plugin Name: Events CPT + Frontend Form
 * Description: Custom Events post type with a frontend [create-event] form (image + tickets supported).
 * Version: 1.0
 * Author: Rae
 */

/**
 * Registers the 'Event' custom post type.
 *
 * This function defines a new custom post type for events, specifying its labels,
 * public accessibility, archive support, menu icon, and supported features like
 * title, content, and featured images. It is hooked into the `init` action to
 * ensure it's loaded early in the WordPress lifecycle.
 */
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

/**
 * Handles the frontend event creation/editing form submission.
 *
 * This function is hooked to both `admin_post_conbook_create_event` and `admin_post_nopriv_conbook_create_event`,
 * allowing both logged-in and non-logged-in users (though restricted) to post to the form.
 * It performs security checks (nonce and user authentication), sanitizes all input fields,
 * and handles the creation or updating of an event post, its metadata, featured image,
 * and associated tickets and payment methods in the custom database tables.
 */
function conbook_handle_create_event() {
    if (!isset($_POST['conbook_create_event_nonce_field']) || 
        !wp_verify_nonce($_POST['conbook_create_event_nonce_field'], 'conbook_create_event_nonce')) {
        wp_die('Security check failed');
    }

    if (!is_user_logged_in()) {
        wp_die('You must be logged in to create or edit an event');
    }

    global $wpdb;
    $table_tickets  = $wpdb->prefix . 'event_tickets';
    $table_payments = $wpdb->prefix . 'event_payment_methods';

    $title       = sanitize_text_field($_POST['event-title']);
    $description = sanitize_textarea_field($_POST['description']);
    $location    = sanitize_text_field($_POST['location']);
    $start_date  = sanitize_text_field($_POST['start-date']);
    $end_date    = sanitize_text_field($_POST['end-date']);
    $start_time  = sanitize_text_field($_POST['start-time']);
    $end_time    = sanitize_text_field($_POST['end-time']);
    $event_id    = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

    /**
     * Generates a unique, random string for event slugs.
     *
     * This helper function creates a randomized string to ensure that event slugs are unique
     * and not easily guessable, especially for new events.
     *
     * @param int $length The desired length of the random string.
     * @return string The generated random string.
     */
    function conbook_generate_random_string($length = 8) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $string;
    }

    if ($event_id) {
        // Editing existing event
        $event = get_post($event_id);

        if (
            !$event ||
            $event->post_type !== 'event' ||
            (get_current_user_id() !== intval($event->post_author) && !current_user_can('manage_options'))
        ) {
            // Not the author OR not an admin -> block
            wp_die('You are not allowed to edit this event.');
        }

        $slug = $event->post_name; // keep original slug
    } else {
        // Creating new event: generate unique slug
        $random_suffix = conbook_generate_random_string(8);
        $slug = 'evt-' . $random_suffix;
        while (get_page_by_path($slug, OBJECT, 'event')) {
            $random_suffix = conbook_generate_random_string(8);
            $slug = 'evt-' . $random_suffix;
        }
    }

    /**
     * Section: Insert or Update Event Post
     *
     * This section handles the core post creation logic. If an `event_id` is present, it
     * updates an existing event, including a critical security check to verify the user's
     * permissions. If no `event_id` is provided, it creates a new event post with a
     * unique, randomly generated slug.
     */
    if ($event_id) {
        // Update existing event
        $update_result = wp_update_post([
            'ID'           => $event_id,
            'post_title'   => $title,
            'post_content' => $description,
            // slug is kept automatically
        ]);
        if (is_wp_error($update_result)) {
            wp_die('Error updating event');
        }
    } else {
        // Create new event
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

    /**
     * Section: Update Event Meta Fields
     *
     * Updates all custom metadata fields associated with the event post, such as
     * location, start/end dates, and times. It also creates combined datetime fields
     * for easier sorting and querying.
     */
    update_post_meta($event_id, '_location', $location);
    update_post_meta($event_id, '_start_date', $start_date);
    update_post_meta($event_id, '_end_date', $end_date);
    update_post_meta($event_id, '_start_time', $start_time);
    update_post_meta($event_id, '_end_time', $end_time);

    $start_datetime = date('Y-m-d H:i:s', strtotime("$start_date $start_time"));
    $end_datetime   = date('Y-m-d H:i:s', strtotime("$end_date $end_time"));
    update_post_meta($event_id, '_start_datetime', $start_datetime);
    update_post_meta($event_id, '_end_datetime', $end_datetime);

    /**
     * Section: Handle Featured Image
     *
     * Checks for a new image upload and, if one exists, handles the media upload process
     * and sets the uploaded image as the event's featured image. It includes necessary
     * WordPress core files for media handling.
     */
    if (!empty($_FILES['event_image']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $attachment_id = media_handle_upload('event_image', $event_id);
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($event_id, $attachment_id);
        }
    }

    /**
     * Section: Update Custom Database Tables (Tickets & Payments)
     *
     * Handles the logic for managing tickets and payment methods. For existing events, it
     * first deletes all old entries to prevent duplicates and data inconsistencies. It then
     * iterates through the submitted form data to insert new or updated ticket and payment
     * method information into the custom tables.
     */
    if ($event_id) {
        $wpdb->delete($table_tickets, ['event_id' => $event_id]);
        $wpdb->delete($table_payments, ['event_id' => $event_id]);
    }

    // -------------------------------
    // 6. Insert tickets
    // -------------------------------
    $ticket_index = 1;
    while (isset($_POST["ticket_name_$ticket_index"])) {
        $ticket_name  = sanitize_text_field($_POST["ticket_name_$ticket_index"]);
        $ticket_price = floatval($_POST["ticket_price_$ticket_index"]);
        $ticket_desc  = sanitize_textarea_field($_POST["ticket_description_$ticket_index"]);

        $wpdb->insert($table_tickets, [
            'event_id'    => $event_id,
            'name'        => $ticket_name,
            'price'       => $ticket_price,
            'description' => $ticket_desc,
        ]);
        $ticket_index++;
    }

    // Insert payment methods
    $payment_index = 1;
    while (isset($_POST["payment_name_$payment_index"])) {
        $payment_name    = sanitize_text_field($_POST["payment_name_$payment_index"]);
        $payment_details = sanitize_text_field($_POST["payment_details_$payment_index"]);

        $wpdb->insert($table_payments, [
            'event_id' => $event_id,
            'name'     => $payment_name,
            'details'  => $payment_details,
        ]);
        $payment_index++;
    }

    // -------------------------------
    // 7. Redirect to events list
    // -------------------------------
    wp_safe_redirect(home_url('/conbook/view-events/'));
    exit;
}
add_action('admin_post_conbook_create_event', 'conbook_handle_create_event');
add_action('admin_post_nopriv_conbook_create_event', 'conbook_handle_create_event');

/**
 * Deletes the featured image when an event post is permanently deleted.
 *
 * This function prevents orphaned media files in the WordPress library by
 * automatically deleting the featured image when its associated event post
 * is permanently removed from the database. It is hooked to `before_delete_post`.
 *
 * @param int $post_id The ID of the post being deleted.
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
 * Adds a custom meta box to the 'event' post type in the admin area.
 *
 * This meta box provides a centralized location for managing event-specific details
 * such as dates, times, location, tickets, payments, and a list of registrants.
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
 * Renders the content of the custom event details meta box.
 *
 * This function generates the HTML for the meta box, displaying the event's metadata
 * in form fields and presenting existing tickets, payments, and registrant lists in
 * tables. It uses dynamic JavaScript to allow for adding new rows on the fly.
 *
 * @param WP_Post $post The current post object.
 */
function conbook_render_event_metabox($post) {
    global $wpdb;
    wp_nonce_field('conbook_save_event_meta', 'conbook_event_meta_nonce');

    // Load event meta
    $start_date = get_post_meta($post->ID, '_start_date', true);
    $end_date   = get_post_meta($post->ID, '_end_date', true);
    $start_time = get_post_meta($post->ID, '_start_time', true);
    $end_time   = get_post_meta($post->ID, '_end_time', true);
    $location   = get_post_meta($post->ID, '_location', true);

    // Table names
    $table_tickets       = $wpdb->prefix . 'event_tickets';
    $table_payments      = $wpdb->prefix . 'event_payment_methods';
    $table_registrations = $wpdb->prefix . 'event_registrations';

    // Load tickets
    $tickets = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table_tickets WHERE event_id = %d", $post->ID),
        ARRAY_A
    );

    // Load registrants
    $registrants = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table_registrations WHERE event_id = %d", $post->ID),
        ARRAY_A
    );

    // Load payment methods
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
                    <td><input type="text" name="payment_name_<?php echo $p['id']; ?>" value="<?php echo esc_attr($p['name']); ?>"></td>
                    <td><input type="text" name="payment_details_<?php echo $p['id']; ?>" value="<?php echo esc_attr($p['details']); ?>"></td>
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

/**
 * Saves the data submitted from the custom event meta box.
 *
 * This function is hooked to `save_post_event` and handles sanitization and storage
 * of all fields from the meta box, including general event info and the dynamic
 * tables for tickets, payments, and registrant statuses. It performs essential
 * security checks to ensure data integrity.
 *
 * @param int $post_id The ID of the post being saved.
 */
function conbook_save_event_meta($post_id) {
    global $wpdb;
    if (!isset($_POST['conbook_event_meta_nonce']) ||
        !wp_verify_nonce($_POST['conbook_event_meta_nonce'], 'conbook_save_event_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Table names
    $table_tickets       = $wpdb->prefix . 'event_tickets';
    $table_payments      = $wpdb->prefix . 'event_payment_methods';
    $table_registrations = $wpdb->prefix . 'event_registrations';

    // Save general info
    update_post_meta($post_id, '_start_date', sanitize_text_field($_POST['start_date'] ?? ''));
    update_post_meta($post_id, '_end_date', sanitize_text_field($_POST['end_date'] ?? ''));
    update_post_meta($post_id, '_start_time', sanitize_text_field($_POST['start_time'] ?? ''));
    update_post_meta($post_id, '_end_time', sanitize_text_field($_POST['end_time'] ?? ''));
    update_post_meta($post_id, '_location', sanitize_text_field($_POST['location'] ?? ''));

    // Combine into full datetime fields (24-hour format)
    $start_date  = sanitize_text_field($_POST['start_date'] ?? '');
    $end_date    = sanitize_text_field($_POST['end_date'] ?? '');
    $start_time  = sanitize_text_field($_POST['start_time'] ?? '');
    $end_time    = sanitize_text_field($_POST['end_time'] ?? '');
    $start_datetime = date('Y-m-d H:i:s', strtotime("$start_date $start_time"));
    $end_datetime   = date('Y-m-d H:i:s', strtotime("$end_date $end_time"));
    update_post_meta($post_id, '_start_datetime', $start_datetime);
    update_post_meta($post_id, '_end_datetime', $end_datetime);


    // Update existing tickets
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'ticket_name_') === 0) {
            $id = intval(str_replace('ticket_name_', '', $key));
            $name = sanitize_text_field($_POST['ticket_name_' . $id] ?? '');
            $price = floatval($_POST['ticket_price_' . $id] ?? 0);
            $desc = sanitize_text_field($_POST['ticket_desc_' . $id] ?? '');
            $wpdb->update($table_tickets,
                ['name' => $name, 'price' => $price, 'description' => $desc],
                ['id' => $id]
            );
        }
    }

    // Insert new tickets
    if (!empty($_POST['new_ticket_name'])) {
        foreach ($_POST['new_ticket_name'] as $i => $name) {
            $name = sanitize_text_field($name);
            if ($name) {
                $price = floatval($_POST['new_ticket_price'][$i] ?? 0);
                $desc  = sanitize_text_field($_POST['new_ticket_desc'][$i] ?? '');
                $wpdb->insert($table_tickets, [
                    'event_id'    => $post_id,
                    'name'        => $name,
                    'price'       => $price,
                    'description' => $desc
                ]);
            }
        }
    }

    // Update existing payments
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'payment_name_') === 0) {
            $id = intval(str_replace('payment_name_', '', $key));
            $name = sanitize_text_field($_POST['payment_name_' . $id] ?? '');
            $details = sanitize_textarea_field($_POST['payment_details_' . $id] ?? '');
            $wpdb->update($table_payments,
                ['name' => $name, 'details' => $details],
                ['id' => $id]
            );
        }
    }

    // Insert new payments
    if (!empty($_POST['new_payment_name'])) {
        foreach ($_POST['new_payment_name'] as $i => $name) {
            $name = sanitize_text_field($name);
            if ($name) {
                $details = sanitize_textarea_field($_POST['new_payment_details'][$i] ?? '');
                $wpdb->insert($table_payments, [
                    'event_id' => $post_id,
                    'name'     => $name,
                    'details'  => $details
                ]);
            }
        }
    }

    // Update registrant statuses
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'reg_status_') === 0) {
            $id = intval(str_replace('reg_status_', '', $key));
            $status = sanitize_text_field($value);
            $wpdb->update($table_registrations,
                ['status' => $status],
                ['id' => $id]
            );
        }
    }
}
add_action('save_post_event', 'conbook_save_event_meta');

/**
 * Adds a custom 'Created By' column to the Events post list in the admin.
 *
 * This filter hooks into `manage_event_posts_columns` to add a new column for
 * the event's author, making it easier for administrators to see who created each event.
 *
 * @param array $columns The existing columns.
 * @return array The modified array of columns.
 */
function conbook_add_event_author_column($columns) {
    $columns['event_author'] = 'Created By';
    return $columns;
}
add_filter('manage_event_posts_columns', 'conbook_add_event_author_column');

/**
 * Renders the content for the custom 'Created By' column.
 *
 * This action hooks into `manage_event_posts_custom_column` to display the
 * event author's name in the newly created column.
 *
 * @param string $column The name of the column.
 * @param int $post_id The ID of the current post.
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
 * Adds a 'Registrants' count column to the Events post list in the admin.
 *
 * This provides a quick overview of how many users have registered for each event
 * directly from the main post list.
 *
 * @param array $columns The existing columns.
 * @return array The modified array of columns.
 */
function conbook_add_event_registrants_column($columns) {
    $columns['event_registrants'] = 'Registrants';
    return $columns;
}
add_filter('manage_event_posts_columns', 'conbook_add_event_registrants_column');

/**
 * Renders the content for the custom 'Registrants' column.
 *
 * This function retrieves and displays the total number of registrants for each event,
 * with a link to a filtered view if there are any registrations.
 *
 * @param string $column The name of the column.
 * @param int $post_id The ID of the current post.
 */
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

/**
 * Section: Custom URL Rewrite Rules
 *
 * This section defines a series of rewrite rules to create clean, "pretty" URLs
 * for various event-related pages like the public event page, event dashboard,
 * event creation form, and registration pages. It maps user-friendly slugs to
 * the correct internal WordPress pages and query variables.
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
 * Allows the 'event_slug' query variable to be used by WordPress.
 *
 * This filter adds `event_slug` to the list of public query variables, which is
 * essential for our rewrite rules to function and for WordPress to correctly
 * identify the event being requested from the URL.
 *
 * @param array $vars The existing array of query variables.
 * @return array The modified array with `event_slug` added.
 */
function conbook_event_query_vars($vars) {
    $vars[] = 'event_slug';
    return $vars;
}
add_filter('query_vars', 'conbook_event_query_vars');
