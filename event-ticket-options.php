<?php
// -------------------------------
// Shortcode: [event-ticket-options]
// Uses GET parameter 'event-slug'
// -------------------------------
function conbook_event_ticket_options_shortcode($atts) {
    // Get the event slug from URL ?event-slug=
    $slug = sanitize_text_field($_GET['event-slug'] ?? '');
    if (!$slug) return '';

    // Get the event by slug
    $event = get_page_by_path($slug, OBJECT, 'event');
    if (!$event) return '';

    $post_id = $event->ID;

    $tickets = get_post_meta($post_id, '_ticket_options', true);
    if (empty($tickets) || !is_array($tickets)) return '<div class="event-tickets">No tickets available.</div>';

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
?>
