<?php
/**
 * Shortcode for Displaying a User's Past Events.
 *
 * This file defines the `[user-past-events]` shortcode, which queries and displays
 * a list of events created by the current logged-in user that have already
 * ended. It is designed to be used on a user dashboard page, providing a
 * clean and organized view of a user's event history.
 *
 * The shortcode performs a secure WordPress query (`WP_Query`) to fetch events
 * where the post author matches the current user's ID and the event's end
 * date is in the past. It then formats the event data into a visually
 * appealing grid of cards.
 *
 * @package ConBook
 * @subpackage Shortcodes
 * @since 1.0.0
 */

/* ==============================================
 * SECTION 1: USER PAST EVENTS SHORTCODE
 * ============================================== */

/**
 * Renders a list of a user's past events.
 *
 * This function queries for all 'event' custom post types created by the
 * current user where the event's end date is before the current time.
 * The results are ordered by the end date in descending order, showing the
 * most recently ended events first.
 *
 * Each event is displayed as an interactive card with its title, end date,
 * and a thumbnail image. A badge with "EXPIRED" is added to each card
 * to clearly indicate its status. The function utilizes WordPress's
 * `WP_Query` for efficient and secure database interaction.
 *
 * @since 1.0.0
 *
 * @param array $atts Shortcode attributes. Unused in this function but included for best practice.
 * @return string The HTML output for the grid of past events or a message if none are found.
 */
function conbook_user_past_events_shortcode($atts) {
    // 1. SECURITY: Check if the user is logged in.
    if (!is_user_logged_in()) {
        return '<p>Please log in to view your past events.</p>';
    }

    $user_id = get_current_user_id();

    // 2. QUERY: Set up the arguments for the WP_Query.
    $args = [
        'post_type'      => 'event',
        'posts_per_page' => -1, // Get all past events.
        'post_status'    => 'publish',
        'author'         => $user_id,
        'meta_query'     => [
            [
                'key'     => '_end_datetime',
                'value'   => current_time('mysql'),
                'compare' => '<', // Check for events that have already ended.
                'type'    => 'DATETIME',
            ],
        ],
        'orderby'        => 'meta_value',
        'order'          => 'DESC', // Newest ended events first.
        'meta_key'       => '_end_datetime',
        'meta_type'      => 'DATETIME',
    ];

    $events = new WP_Query($args);

    // 3. LOGIC: Check if any past events were found.
    if (!$events->have_posts()) {
        return '<p>No past events found.</p>';
    }

    // 4. OUTPUT: Start building the HTML output.
    $output = '<div class="user-past-events-wrapper">';
    $output .= '<div class="user-past-events">';

    while ($events->have_posts()) {
        $events->the_post();
        $post_id      = get_the_ID();
        $title        = get_the_title();
        $end_datetime = get_post_meta($post_id, '_end_datetime', true);

        // Format the end date for display, providing a fallback.
        $formatted_end = $end_datetime
            ? date('m/d/y • g:i A', strtotime($end_datetime))
            : 'Ended';

        // Get the event slug and build the permalink.
        $event_slug = get_post_field('post_name', $post_id);
        $event_link = esc_url(home_url('/event-page/?event_slug=' . $event_slug));

        // Get the post thumbnail URL or a placeholder image.
        $image_url = get_the_post_thumbnail_url($post_id, 'medium') ?: 'https://via.placeholder.com/300x300?text=No+Image';

        // Build the HTML for a single event card.
        $output .= '<a href="' . esc_url($event_link) . '" class="event-card">';
        $output .= '<div class="event-badge ended">ENDED</div>'; // Updated badge text to ENDED
        $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($title) . '">';
        $output .= '<div class="event-card-content">';
        $output .= '<div class="event-date">Ended on ' . esc_html($formatted_end) . '</div>';
        $output .= '<strong>' . esc_html($title) . '</strong>';
        $output .= '</div></a>';
    }

    $output .= '</div></div>';

    // 5. CLEANUP: Reset post data to its original state.
    wp_reset_postdata();

    return $output;
}
add_shortcode('user-past-events', 'conbook_user_past_events_shortcode');