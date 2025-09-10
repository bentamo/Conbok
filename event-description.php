<?php
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
