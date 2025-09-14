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
                'value'   => current_time('mysql'), // ✅ use local site time
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

    // Prepare inline CSS for the event grid/cards
    $output = '<style>
        .user-upcoming-events-wrapper {
            display: flex;
            justify-content: center;
            width: 100%;
        }
        .user-upcoming-events {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            max-width: 900px;
            width: 100%;
        }
        @media (max-width: 768px) {
            .user-upcoming-events {
                grid-template-columns: 1fr;
            }
        }
        .event-card {
            display: block;
            border: 1px solid #ddd;
            border-radius: 15px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            width: 100%;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        .event-card, .event-card * {
            text-decoration: none !important;
            color: inherit !important;
        }
        .event-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }
        .event-card-content {
            padding: 15px;
            text-align: center;
            background: #f2f4f7;
        }
        .event-date {
            font-weight: bold;
            color: #333;
            margin-bottom: 6px;
        }
        .event-card-content strong {
            display: block;
            font-size: 1.1em;
        }
    </style>';

    // Build HTML output
    $output .= '<div class="user-upcoming-events-wrapper">';
    $output .= '<div class="user-upcoming-events">';

    while ($events->have_posts()) {
        $events->the_post();
        $post_id = get_the_ID();
        $title   = get_the_title();

        // Get start datetime
        $start_datetime = get_post_meta($post_id, '_start_datetime', true);

        // Format start for display
        $formatted_start = $start_datetime 
            ? date('m/d/y • g:i A', strtotime($start_datetime))
            : 'TBA';

        // Event slug and link
        $event_slug = get_post_field('post_name', $post_id);
        $event_link = home_url('/event-page-organizer/?event-slug=' . $event_slug);

        // Thumbnail fallback
        $image_url = get_the_post_thumbnail_url($post_id, 'medium') 
            ?: 'https://via.placeholder.com/300x300?text=No+Image';

        // Output clickable card
        $output .= '<a href="' . esc_url($event_link) . '" class="event-card">';
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
