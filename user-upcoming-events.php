<?php
// -------------------------------
// Shortcode: [user-upcoming-events]
// Shows all upcoming events for the logged-in user in 2 centered columns
// -------------------------------
function conbook_user_upcoming_events_shortcode($atts) {
    // Ensure the user is logged in
    if (!is_user_logged_in()) {
        return '<p>Please log in to view your upcoming events.</p>';
    }

    $user_id = get_current_user_id();

    // Query all future and ongoing events (based on end datetime)
    $args = [
        'post_type'      => 'event',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'author'         => $user_id,
        'meta_query'     => [
            [
                'key'     => '_end_datetime',
                'value'   => current_time('mysql'),
                'compare' => '>=',
                'type'    => 'DATETIME',
            ],
        ],
        'orderby'  => 'meta_value',
        'order'    => 'ASC',
        'meta_key' => '_start_datetime',
        'meta_type'=> 'DATETIME'
    ];

    $events = new WP_Query($args);

    if (!$events->have_posts()) {
        return '<p>No upcoming events found.</p>';
    }

    // Wrapper HTML
    $output  = '<div class="user-upcoming-events-wrapper">';
    $output .= '<div class="user-upcoming-events">';

    while ($events->have_posts()) {
        $events->the_post();
        $post_id = get_the_ID();
        $title   = get_the_title();

        // Get start + end datetimes
        $start_datetime = get_post_meta($post_id, '_start_datetime', true);
        $end_datetime   = get_post_meta($post_id, '_end_datetime', true);
        $now            = current_time('mysql');

        // Format start for display
        $formatted_start = $start_datetime 
            ? date('m/d/y • g:i A', strtotime($start_datetime))
            : 'TBA';

        // Event slug and link
        $event_slug = get_post_field('post_name', $post_id);
        $event_link = home_url('/event-page/?event_slug=' . $event_slug);

        // Thumbnail fallback
        $image_url = get_the_post_thumbnail_url($post_id, 'medium') 
            ?: 'https://via.placeholder.com/300x300?text=No+Image';

        // Detect ongoing event
        $is_ongoing = ($start_datetime && $end_datetime && 
                      $start_datetime <= $now && $end_datetime >= $now);

        // Output clickable card
        $output .= '<a href="' . esc_url($event_link) . '" class="event-card">';

        // If ongoing, show glass badge
        if ($is_ongoing) {
            $output .= '<div class="event-badge live">LIVE</div>';
        }

        $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($title) . '">';
        $output .= '<div class="event-card-content">';
        $output .= '<div class="event-date">' . esc_html($formatted_start) . '</div>';
        $output .= '<strong>' . esc_html($title) . '</strong>';
        $output .= '</div>'; // .event-card-content
        $output .= '</a>';   // close clickable card
    }

    $output .= '</div>'; // .user-upcoming-events
    $output .= '</div>'; // .user-upcoming-events-wrapper

    wp_reset_postdata();

    return $output;
}
add_shortcode('user-upcoming-events', 'conbook_user_upcoming_events_shortcode');
?>
