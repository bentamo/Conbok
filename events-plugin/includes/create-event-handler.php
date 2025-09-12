<?php
function ep_handle_create_event() {
    if (!isset($_POST['ep_nonce']) || !wp_verify_nonce($_POST['ep_nonce'], 'ep_create_event')) {
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
add_action('admin_post_ep_create_event', 'ep_handle_create_event');
add_action('admin_post_nopriv_ep_create_event', 'ep_handle_create_event');