<?php
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

    // Format start and end dates as MM/DD/YYYY
    $formatted_start = date('m/d/Y', strtotime($start_date));
    $formatted_end   = $end_date ? date('m/d/Y', strtotime($end_date)) : '';

    $output = '<div class="event-date">';
    $output .= '<strong>Date: </strong>' . esc_html($formatted_start);

    if ($formatted_end) {
        $output .= ' — ' . esc_html($formatted_end);
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('event-date', 'conbook_event_date_shortcode');
?>
