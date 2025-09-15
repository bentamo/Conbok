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

    // Query all past events (based on end datetime)
    $args = [
        'post_type'      => 'event',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'author'         => $user_id,
        'meta_query'     => [
            [
                'key'     => '_end_datetime',
                'value'   => current_time('mysql'),
                'compare' => '<',
                'type'    => 'DATETIME',
            ],
        ],
        'orderby'  => 'meta_value',
        'order'    => 'DESC', // most recently expired first
        'meta_key' => '_end_datetime',
        'meta_type'=> 'DATETIME',
    ];

    $events = new WP_Query($args);

    if (!$events->have_posts()) {
        return '<p>No past events found.</p>';
    }

    // Prepare inline CSS for the event grid/cards
    $output = '<style>
        /* Grid wrapper */
        .user-past-events-wrapper {
            display: flex;
            justify-content: center;
            width: 100%;
        }

        .user-past-events {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 400px));
            gap: 20px;
            justify-content: center;
            max-width: 850px;
            width: 100%;
        }

        @media (max-width: 768px) {
            .user-past-events {
                grid-template-columns: 1fr;
            }
        }

        /* Glassmorphic Event Card */
        .event-card {
            display: block;
            border-radius: 15px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            width: 100%;
            text-decoration: none;
            color: inherit;
            transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
            position: relative;
        }

        .event-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            background: rgba(255, 255, 255, 0.15);
        }

        .event-card, .event-card * {
            text-decoration: none !important;
            color: inherit !important;
        }

        /* Image styling with slight zoom effect */
        .event-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s ease, filter 0.3s ease;
            filter: brightness(0.95);
        }

        .event-card:hover img {
            transform: scale(1.03);
            filter: brightness(1);
        }

        /* Card content overlay */
        .event-card-content {
            padding: 15px;
            text-align: center;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(5px);
            border-bottom-left-radius: 15px;
            border-bottom-right-radius: 15px;
        }

        .event-date {
            font-weight: normal;
            color: #333;
            margin-bottom: 6px;
        }

        .event-card-content strong {
            display: block;
            font-size: 2em;
        }

        /* Optional: Past badge on top-left */
        .event-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(5px);
            color: #fff;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: 600;
            z-index: 2;
        }
    </style>';

    // Build HTML output
    $output .= '<div class="user-past-events-wrapper">';
    $output .= '<div class="user-past-events">';

    while ($events->have_posts()) {
        $events->the_post();
        $post_id = get_the_ID();
        $title   = get_the_title();

        // Get end datetime
        $end_datetime = get_post_meta($post_id, '_end_datetime', true);

        // Format end for display
        $formatted_end = $end_datetime
            ? date('m/d/y • g:i A', strtotime($end_datetime))
            : 'Ended';

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
        $output .= '<div class="event-date">Ended: ' . esc_html($formatted_end) . '</div>';
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
