<?php
function join_event() {
    // Get the event slug from URL query
    $slug = isset($_GET['event-slug']) ? sanitize_text_field($_GET['event-slug']) : '';

    // Build the registration URL
    $url = 'http://localhost/conbook/event-registration/';
    if ($slug) {
        $url .= '?event-slug=' . urlencode($slug);
    }

    // Return the button HTML
    return '<a href="' . esc_url($url) . '" style="display:inline-block; padding:10px 20px; border-radius:30px; background:linear-gradient(135deg,rgb(255,75,43) 0%,rgb(125,63,255) 100%); font-family:Inter,sans-serif; font-weight:500; color:#fff; text-decoration:none; text-align:center;">Join Event</a>';
}

add_shortcode('btn-join-event', 'join_event');
