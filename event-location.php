<?php
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
?>
