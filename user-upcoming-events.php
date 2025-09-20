<?php
/**
 * Shortcode for Displaying a User's Upcoming Events.
 *
 * This file defines the `[user-upcoming-events]` shortcode, which queries and displays
 * a list of events created by the current logged-in user that are either upcoming
 * or currently in progress. It is designed to be used on a user dashboard page,
 * providing a clear overview of a user's schedule of created events.
 *
 * The shortcode performs a secure WordPress query (`WP_Query`) to fetch events
 * where the post author matches the current user's ID and the event's end date
 * is in the future. It then formats the event data into a visually
 * appealing grid of cards.
 *
 * @package ConBook
 * @subpackage Shortcodes
 * @since 1.0.0
 */

/* ==============================================
 * SECTION 1: USER UPCOMING EVENTS SHORTCODE
 * ============================================== */

/**
 * Renders a list of a user's upcoming events.
 *
 * This function queries for all 'event' custom post types created by the
 * current user where the event's end date is on or after the current time.
 * The results are ordered by the start date in ascending order, showing the
 * next events chronologically.
 *
 * Each event is displayed as an interactive card with its title, start date,
 * and a thumbnail image. A "LIVE" badge is added to events that are
 * currently ongoing. The function utilizes WordPress's `WP_Query` for
 * efficient and secure database interaction.
 *
 * @since 1.0.0
 *
 * @param array $atts Shortcode attributes. Unused in this function but included for best practice.
 * @return string The HTML output for the grid of upcoming events or a message if none are found.
 */
function conbook_user_upcoming_events_shortcode($atts) {
    // 1. SECURITY: Check if the user is logged in.
    if (!is_user_logged_in()) {
        return '<p>Please log in to view your upcoming events.</p>';
    }

    $user_id = get_current_user_id();

    // 2. QUERY: Set up the arguments for the WP_Query.
    $args = [
        'post_type'      => 'event',
        'posts_per_page' => -1, // Get all upcoming events.
        'post_status'    => 'publish',
        'author'         => $user_id,
        'meta_query'     => [
            [
                'key'     => '_end_datetime',
                'value'   => current_time('mysql'),
                'compare' => '>=', // Check for events that haven't ended yet.
                'type'    => 'DATETIME',
            ],
        ],
        'orderby'        => 'meta_value',
        'order'          => 'ASC', // Chronological order.
        'meta_key'       => '_start_datetime',
        'meta_type'      => 'DATETIME'
    ];

    $events = new WP_Query($args);

    // 3. LOGIC: Check if any upcoming events were found.
    if (!$events->have_posts()) {
        return '<p>No upcoming events found.</p>';
    }

    // 4. OUTPUT: Use output buffering to build the HTML string.
    ob_start();
    ?>
    <div class="user-upcoming-events-wrapper">
        <div class="user-upcoming-events">
            <?php while ($events->have_posts()): $events->the_post();
                $post_id        = get_the_ID();
                $title          = get_the_title();

                // Get start and end datetimes.
                $start_datetime = get_post_meta($post_id, '_start_datetime', true);
                $end_datetime   = get_post_meta($post_id, '_end_datetime', true);
                $now            = current_time('mysql');

                // Format start date for display.
                $formatted_start = $start_datetime
                    ? date('m/d/y • g:i A', strtotime($start_datetime))
                    : 'TBA';

                // Get event slug and link.
                $event_slug = get_post_field('post_name', $post_id);
                $event_link = home_url('/event-page/?event_slug=' . $event_slug);

                // Get thumbnail URL with fallback.
                $image_url = get_the_post_thumbnail_url($post_id, 'medium')
                           ?: 'https://via.placeholder.com/300x300?text=No+Image';

                // Detect ongoing event.
                $is_ongoing = ($start_datetime && $end_datetime &&
                                $start_datetime <= $now && $end_datetime >= $now);
            ?>
                <a href="<?php echo esc_url($event_link); ?>" class="event-card">
                    <?php if ($is_ongoing): ?>
                        <div class="event-badge live">LIVE</div>
                    <?php endif; ?>

                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>">

                    <div class="event-card-content">
                        <div class="event-date">
                            <?php echo esc_html($formatted_start); ?>
                        </div>
                        <strong><?php echo esc_html($title); ?></strong>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
    <?php
    // 5. CLEANUP: Reset post data to its original state.
    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('user-upcoming-events', 'conbook_user_upcoming_events_shortcode');
?>