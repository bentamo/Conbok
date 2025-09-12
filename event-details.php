<?php
// -------------------------------
// Shortcode: [event-title]
// Uses GET parameter 'event-slug'
// -------------------------------
function conbook_event_title_shortcode($atts) {
    // Get the event slug from URL ?event-slug=
    $slug = sanitize_text_field($_GET['event-slug'] ?? '');
    if (!$slug) return '';

    // Get the event by slug
    $event = get_page_by_path($slug, OBJECT, 'event');
    if (!$event) return '';

    $post_id = $event->ID;

    $title = get_the_title($post_id);
    if (!$title) return '';

    $output = '<div class="event-title"><h3>';
    $output .= esc_html($title);
    $output .= '</h3></div>';

    return $output;
}
add_shortcode('event-title', 'conbook_event_title_shortcode');

// -------------------------------
// Shortcode: [event-date]
// Uses GET parameter 'event-slug'
// -------------------------------
function conbook_event_date_shortcode($atts) {

    // Get the event slug from URL ?event-slug=
    $slug = sanitize_text_field($_GET['event-slug'] ?? '');
    if (!$slug) return '';

    // Get the event by slug
    $event = get_page_by_path($slug, OBJECT, 'event');
    if (!$event) return '';

    $post_id = $event->ID;

    $start_date = get_post_meta($post_id, '_start_date', true);
    $end_date   = get_post_meta($post_id, '_end_date', true);

    if (!$start_date) return '';

    // Format start and end dates
    $formatted_start = date('F j, Y', strtotime($start_date));
    $formatted_end   = $end_date ? date('F j, Y', strtotime($end_date)) : '';

    $output = '<div class="event-date">';
    $output .= '<strong>Date: </strong>' . esc_html($formatted_start);

    if ($formatted_end) {
        $output .= ' — ' . esc_html($formatted_end);
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('event-date', 'conbook_event_date_shortcode');

// -------------------------------
// Shortcode: [event-time]
// Uses GET parameter 'event-slug'
// -------------------------------
function conbook_event_time_shortcode($atts) {

    // Get the event slug from URL ?event-slug=
    $slug = sanitize_text_field($_GET['event-slug'] ?? '');
    if (!$slug) return '';

    // Get the event by slug
    $event = get_page_by_path($slug, OBJECT, 'event');
    if (!$event) return '';

    $post_id = $event->ID;

    $start_time = get_post_meta($post_id, '_start_time', true);
    $end_time   = get_post_meta($post_id, '_end_time', true);

    if (!$start_time) return '';

    // Format start and end times
    $formatted_start = date('h:i A', strtotime($start_time));
    $formatted_end   = $end_time ? date('h:i A', strtotime($end_time)) : '';

    $output = '<div class="event-time">';
    $output .= '<strong>Time: </strong>' . esc_html($formatted_start);

    if ($formatted_end) {
        $output .= ' — ' . esc_html($formatted_end);
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('event-time', 'conbook_event_time_shortcode');

// -------------------------------
// Shortcode: [event-location]
// Uses GET parameter 'event-slug'
// -------------------------------
function conbook_event_location_shortcode($atts) {
    // Get the event slug from URL ?event-slug=
    $slug = sanitize_text_field($_GET['event-slug'] ?? '');
    if (!$slug) return '';

    // Get the event by slug
    $event = get_page_by_path($slug, OBJECT, 'event');
    if (!$event) return '';

    $post_id = $event->ID;

    $location = get_post_meta($post_id, '_location', true);
    if (!$location) return '';

    $output = '<div class="event-location">';
    $output .= '<strong>Location: </strong>' . esc_html($location);
    $output .= '</div>';

    return $output;
}
add_shortcode('event-location', 'conbook_event_location_shortcode');

// -------------------------------
// Shortcode: [event-ticket-options]
// Uses GET parameter 'event-slug'
// -------------------------------
function conbook_event_ticket_options_shortcode($atts) {
    global $wpdb;

    // Get the event slug from URL ?event-slug=
    $slug = sanitize_text_field($_GET['event-slug'] ?? '');
    if (!$slug) return '';

    // Get the event by slug
    $event = get_page_by_path($slug, OBJECT, 'event');
    if (!$event) return '';

    $post_id = $event->ID;

    // Load tickets (from DB)
    $tickets_table = $wpdb->prefix . 'event_tickets';
    $tickets = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $tickets_table WHERE event_id = %d", $post_id),
        ARRAY_A
    );

    if (empty($tickets)) {
        return '<div class="event-tickets">No tickets available.</div>';
    }

    $output = '<div class="event-tickets"><strong>Ticket Options:</strong><ul>';
    foreach ($tickets as $ticket) {
        $name  = esc_html($ticket['name'] ?? '');
        $price = isset($ticket['price']) ? number_format(floatval($ticket['price']), 2) : '0.00';
        $output .= "<li>{$name} - Php {$price}</li>";
    }
    $output .= '</ul></div>';

    return $output;
}
add_shortcode('event-ticket-options', 'conbook_event_ticket_options_shortcode');

// -------------------------------
// Shortcode: [event-organizer]
// Uses GET parameter 'event-slug'
// -------------------------------
function conbook_event_organizer_shortcode($atts) {
    // Get the event slug from URL ?event-slug=
    $slug = sanitize_text_field($_GET['event-slug'] ?? '');
    if (!$slug) return '';

    // Get the event by slug
    $event = get_page_by_path($slug, OBJECT, 'event');
    if (!$event) return '';

    $author_id = $event->post_author;
    $user_info = get_userdata($author_id);
    $organizer = $user_info ? $user_info->display_name : 'Unknown Organizer';

    $output = '<div class="event-organizer">';
    $output .= '<strong>Organizer: </strong>' . esc_html($organizer);
    $output .= '</div>';

    return $output;
}
add_shortcode('event-organizer', 'conbook_event_organizer_shortcode');

// -------------------------------
// Shortcode: [event-image]
// Uses GET parameter 'event-slug'
// -------------------------------
function conbook_event_image_shortcode($atts) {
    // Get the event slug from URL ?event-slug=
    $slug = sanitize_text_field($_GET['event-slug'] ?? '');
    if (!$slug) return '';

    // Get the event by slug
    $event = get_page_by_path($slug, OBJECT, 'event');
    if (!$event) return '';

    $post_id = $event->ID;

    // Get the featured image URL
    $thumbnail_id = get_post_thumbnail_id($post_id);
    if (!$thumbnail_id) return '';

    $image_url = wp_get_attachment_image_url($thumbnail_id, 'full');
    if (!$image_url) return '';

    $output = '<div class="event-image">';
    $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr(get_the_title($post_id)) . '" style="max-width:100%;height:auto;">';
    $output .= '</div>';

    return $output;
}
add_shortcode('event-image', 'conbook_event_image_shortcode');

// -------------------------------
// Shortcode: [event-description]
// Uses GET parameter 'event-slug'
// -------------------------------
function conbook_event_description_shortcode($atts) {
    // Get the event slug from URL ?event-slug=
    $slug = sanitize_text_field($_GET['event-slug'] ?? '');
    if (!$slug) return '';

    // Get the event by slug
    $event = get_page_by_path($slug, OBJECT, 'event');
    if (!$event) return '';

    $post_id = $event->ID;

    // Get the post content
    $content = apply_filters('the_content', $event->post_content);
    if (!$content) return '';

    $output = '<div class="event-description"><center>';
    $output .= '<h3>Description</h3>'. $content;
    $output .= '</center></div>';

    return $output;
}
add_shortcode('event-description', 'conbook_event_description_shortcode');
?>