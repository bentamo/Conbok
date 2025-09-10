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
?>
