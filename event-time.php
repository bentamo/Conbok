<?php
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
?>
