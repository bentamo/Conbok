<?php
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
?>
