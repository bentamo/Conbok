<?php
// -------------------------------
// Shortcode: [user-past-events]
// Shows all past events for the logged-in user in 2 centered columns
// -------------------------------
function conbook_user_past_events_shortcode($atts) {
    // Ensure the user is logged in
    if (!is_user_logged_in()) {
        return '<p>Please log in to view your past events.</p>';
    }

    $user_id = get_current_user_id();

    // Query past events by this user
    $today = date('Y-m-d');
    $args = [
        'post_type'      => 'event',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'author'         => $user_id,
        'meta_key'       => '_start_date',
        'orderby'        => 'meta_value',
        'order'          => 'DESC',
        'meta_query'     => [
            [
                'key'     => '_start_date',
                'value'   => $today,
                'compare' => '<',
                'type'    => 'DATE',
            ],
        ],
    ];

    $events = new WP_Query($args);

    if (!$events->have_posts()) {
        return '<p>No past events found.</p>';
    }

    // Inline style for grid layout (same as upcoming events)
    $output = '<style>
        .user-past-events-wrapper {
            display: flex;
            justify-content: center; /* center the grid */
            width: 100%;
        }
        .user-past-events {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            max-width: 900px;
            width: 100%;
        }
        @media (max-width: 768px) {
            .user-past-events {
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
        .event-card,
        .event-card * {
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

    // Build the HTML output
    $output .= '<div class="user-past-events-wrapper">';
    $output .= '<div class="user-past-events">';

    while ($events->have_posts()) {
        $events->the_post();
        $post_id = get_the_ID();
        $title = get_the_title();

        // Get event start date & time
        $start_date = get_post_meta($post_id, '_start_date', true); 
        $start_time = get_post_meta($post_id, '_start_time', true); 
        $datetime_str = $start_date . ' ' . ($start_time ?: '00:00:00');
        $formatted_start = date('M j, Y • g:i A', strtotime($datetime_str));

        // Event slug
        $event_slug = get_post_field('post_name', $post_id);

        // Build event link
        $event_link = home_url('/event-page-organizer/?event-slug=' . $event_slug);

        // Thumbnail fallback
        $image_url = get_the_post_thumbnail_url($post_id, 'medium') 
            ?: 'https://via.placeholder.com/300x300?text=No+Image';

        // Card output (clickable)
        $output .= '<a href="' . esc_url($event_link) . '" class="event-card">';
        $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($title) . '">';
        $output .= '<div class="event-card-content">';
        $output .= '<div class="event-date">' . esc_html($formatted_start) . '</div>';
        $output .= '<strong>' . esc_html($title) . '</strong>';
        $output .= '</div>'; // .event-card-content
        $output .= '</a>';   // close clickable card
    }

    $output .= '</div>'; // .user-past-events
    $output .= '</div>'; // .user-past-events-wrapper

    wp_reset_postdata();

    return $output;
}
add_shortcode('user-past-events', 'conbook_user_past_events_shortcode');
?>
