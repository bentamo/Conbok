<?php
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
?>
