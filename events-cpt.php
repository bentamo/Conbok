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
    if (!isset($_POST['conbook_create_event_nonce_field']) 
        || !wp_verify_nonce($_POST['conbook_create_event_nonce_field'], 'conbook_create_event_nonce')) {
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
                'name'  => sanitize_text_field($value),
                'price' => floatval($_POST['ticket_price_' . $i] ?? 0),
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
        update_post_meta($event_id, '_ticket_options', $tickets);

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

    wp_redirect(home_url('/'));
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
// 5. Admin Meta Box for Event Details
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
    wp_nonce_field('conbook_save_event_meta', 'conbook_event_meta_nonce');

    $start_date = get_post_meta($post->ID, '_start_date', true);
    $end_date   = get_post_meta($post->ID, '_end_date', true);
    $start_time = get_post_meta($post->ID, '_start_time', true);
    $end_time   = get_post_meta($post->ID, '_end_time', true);
    $location   = get_post_meta($post->ID, '_location', true);
    $tickets    = get_post_meta($post->ID, '_ticket_options', true) ?: [];

    ?>
    <p>
        <label>Start Date:</label><br>
        <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
    </p>
    <p>
        <label>End Date:</label><br>
        <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
    </p>
    <p>
        <label>Start Time:</label><br>
        <input type="time" name="start_time" value="<?php echo esc_attr($start_time); ?>">
    </p>
    <p>
        <label>End Time:</label><br>
        <input type="time" name="end_time" value="<?php echo esc_attr($end_time); ?>">
    </p>
    <p>
        <label>Location:</label><br>
        <input type="text" name="location" value="<?php echo esc_attr($location); ?>" style="width:100%;">
    </p>

    <h4>Tickets</h4>
    <?php if (!empty($tickets)) : ?>
        <?php foreach ($tickets as $i => $ticket) : ?>
            <p>
                <input type="text" name="ticket_name_<?php echo $i; ?>" value="<?php echo esc_attr($ticket['name']); ?>" placeholder="Ticket Name">
                <input type="number" step="0.01" name="ticket_price_<?php echo $i; ?>" value="<?php echo esc_attr($ticket['price']); ?>" placeholder="Price">
            </p>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No tickets yet.</p>
    <?php endif; ?>

    <p><strong>Created by:</strong>
        <?php 
        $author_id = $post->post_author;
        $user_info = get_userdata($author_id);
        echo esc_html($user_info ? $user_info->display_name : "User ID: $author_id");
        ?>
    </p>
    <?php
}

function conbook_save_event_meta($post_id) {
    if (!isset($_POST['conbook_event_meta_nonce']) ||
        !wp_verify_nonce($_POST['conbook_event_meta_nonce'], 'conbook_save_event_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, '_start_date', sanitize_text_field($_POST['start_date'] ?? ''));
    update_post_meta($post_id, '_end_date', sanitize_text_field($_POST['end_date'] ?? ''));
    update_post_meta($post_id, '_start_time', sanitize_text_field($_POST['start_time'] ?? ''));
    update_post_meta($post_id, '_end_time', sanitize_text_field($_POST['end_time'] ?? ''));
    update_post_meta($post_id, '_location', sanitize_text_field($_POST['location'] ?? ''));

    $tickets = [];
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'ticket_name_') === 0) {
            $i = str_replace('ticket_name_', '', $key);
            $tickets[] = [
                'name'  => sanitize_text_field($value),
                'price' => floatval($_POST['ticket_price_' . $i] ?? 0),
            ];
        }
    }
    update_post_meta($post_id, '_ticket_options', $tickets);
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
