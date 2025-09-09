<?php
// -------------------------------
// Shortcode: [user-upcoming-events]
// Shows all upcoming events for the logged-in user with square images
// -------------------------------
function conbook_user_upcoming_events_shortcode($atts) {
    // Ensure the user is logged in
    if (!is_user_logged_in()) {
        return '<p>Please log in to view your upcoming events.</p>';
    }

    $user_id = get_current_user_id();

    // Query upcoming events by this user
    $today = date('Y-m-d');
    $args = [
        'post_type'      => 'event',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'author'         => $user_id,
        'meta_key'       => '_start_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => [
            [
                'key'     => '_start_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            ],
        ],
    ];

    $events = new WP_Query($args);

    if (!$events->have_posts()) {
        return '<p>No upcoming events found.</p>';
    }

    // Build the HTML output
    $output = '<div class="user-upcoming-events" style="display:flex; flex-wrap:wrap; gap:20px;">';

    while ($events->have_posts()) {
        $events->the_post();
        $post_id = get_the_ID();
        $title = get_the_title();
        $start_date = get_post_meta($post_id, '_start_date', true);
        $formatted_start = date('F j, Y', strtotime($start_date));
        $image_url = get_the_post_thumbnail_url($post_id, 'medium') ?: 'https://via.placeholder.com/300x300?text=No+Image';

        $output .= '<div class="event-card" style="border:1px solid #ddd; border-radius:10px; overflow:hidden; width:300px;">';
        $output .= '<div style="width:100%; height:300px; overflow:hidden; display:flex; align-items:center; justify-content:center;">';
        $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($title) . '" style="width:100%; height:100%; object-fit:cover;">';
        $output .= '</div>';
        $output .= '<div class="event-card-content" style="padding:15px;"><center>';
        $output .= '<strong style="margin:0 0 10px;">' . esc_html($title) . '</strong>';
        $output .= '<p style="margin:0;">' . esc_html($formatted_start) . '</p>';
        $output .= '</center></div>'; // .event-card-content
        $output .= '</div>'; // .event-card
    }

    $output .= '</div>'; // .user-upcoming-events

    wp_reset_postdata();

    return $output;
}
add_shortcode('user-upcoming-events', 'conbook_user_upcoming_events_shortcode');
?>
