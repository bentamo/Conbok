<?php
function conbook_user_upcoming_events_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<p>Please log in to view your upcoming events.</p>';
    }

    $user_id = get_current_user_id();

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

    $output = '<div class="user-upcoming-events-wrapper">';
    $output .= '<div class="user-upcoming-events">';

    while ($events->have_posts()) {
        $events->the_post();
        $post_id = get_the_ID();
        $title   = get_the_title();
        $start_datetime = get_post_meta($post_id, '_start_datetime', true);
        $formatted_start = $start_datetime 
            ? date('m/d/y • g:i A', strtotime($start_datetime))
            : 'TBA';
        $event_slug = get_post_field('post_name', $post_id);
        $event_link = home_url('/event-page/?event_slug=' . $event_slug);
        $image_url = get_the_post_thumbnail_url($post_id, 'medium') 
            ?: 'https://via.placeholder.com/300x300?text=No+Image';

        $output .= '<a href="' . esc_url($event_link) . '" class="event-card">';
        $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($title) . '">';
        $output .= '<div class="event-card-content">';
        $output .= '<div class="event-date">' . esc_html($formatted_start) . '</div>';
        $output .= '<strong>' . esc_html($title) . '</strong>';
        $output .= '</div></a>';
    }

    $output .= '</div></div>';
    wp_reset_postdata();
    return $output;
}
add_shortcode('user-upcoming-events', 'conbook_user_upcoming_events_shortcode');
