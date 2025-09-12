<?php
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
?>
