<?php
/**
 * Shortcode for Displaying a User's Past Registrations.
 *
 * This file defines the `[user-past-registrations]` shortcode, which queries and displays
 * a list of events for which the current logged-in user has registered and that
 * have already ended. This shortcode is designed for use on a user dashboard to
 * provide a complete history of the user's event attendance.
 *
 * The shortcode performs a secure database query (`$wpdb`) by joining the custom
 * `event_registrations` table with the standard WordPress `posts` and `postmeta` tables.
 * This approach efficiently retrieves all necessary event and registration data in a
 * single query, filtering for registrations of events that have passed.
 *
 * @package ConBook
 * @subpackage Shortcodes
 * @since 1.0.0
 */

/* ==============================================
 * SECTION 1: USER PAST REGISTRATIONS SHORTCODE
 * ============================================== */

/**
 * Renders a list of a user's past event registrations.
 *
 * This function retrieves a list of all registrations made by the current user
 * for events that have a `_end_datetime` in the past. It constructs a secure
 * SQL query using `$wpdb->prepare()` to prevent SQL injection attacks.
 *
 * The function then iterates through the results, generating HTML for each past
 * event registration. Each event is presented as a card with a title, end date,
 * and a "EXPIRED" badge. The output is buffered to ensure a single, clean return
 * string, which is the standard practice for WordPress shortcodes.
 *
 * @since 1.0.0
 *
 * @return string The HTML output for the grid of past registrations or a message if none are found.
 */
function conbook_user_past_registrations_shortcode() {
    // 1. SECURITY: Check if the user is logged in.
    if (!is_user_logged_in()) {
        return '<p>Please log in to see your past registrations.</p>';
    }

    global $wpdb;
    $user_id = get_current_user_id();
    $now     = current_time('Y-m-d H:i:s');

    // 2. DATA: Define table names for the SQL query.
    $table_reg      = $wpdb->prefix . 'event_registrations';
    $table_events   = $wpdb->prefix . 'posts';
    $table_postmeta = $wpdb->prefix . 'postmeta';

    // 3. QUERY: Fetch registrations where the event has ended.
    // Using a JOIN to efficiently get all required data in one query.
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT r.*, e.ID as event_id, e.post_title, e.post_name,
               pm_end.meta_value AS end_datetime
        FROM {$table_reg} r
        INNER JOIN {$table_events} e ON r.event_id = e.ID
        INNER JOIN {$table_postmeta} pm_end ON pm_end.post_id = e.ID
        WHERE r.user_id = %d
          AND e.post_type = 'event'
          AND e.post_status = 'publish'
          AND pm_end.meta_key = '_end_datetime'
          AND pm_end.meta_value <= %s
        ORDER BY pm_end.meta_value DESC
    ", $user_id, $now), ARRAY_A);

    // 4. LOGIC: Check if any past registrations were found.
    if (!$results) {
        return '<p>No past registrations found.</p>';
    }

    // 5. OUTPUT: Start output buffering to capture the HTML.
    ob_start();
    ?>
    <div class="user-past-events-wrapper">
        <div class="user-past-events">
            <?php foreach ($results as $row):
                $event_id     = $row['event_id'];
                $event_slug   = $row['post_name'];
                $title        = $row['post_title'];
                $end_datetime = $row['end_datetime'];

                // Sanitize and format data for display.
                $event_url = home_url('/event-page/?event_slug=' . $event_slug);
                $image_url = get_the_post_thumbnail_url($event_id, 'medium')
                           ?: 'https://via.placeholder.com/300x300?text=No+Image';

                // Format end datetime, providing a fallback.
                $formatted_end = $end_datetime ? date('m/d/y • g:i A', strtotime($end_datetime)) : '';
            ?>
                <a href="<?php echo esc_url($event_url); ?>" class="event-card">
                    <div class="event-badge ended">EXPIRED</div>

                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>">

                    <div class="event-card-content">
                        <div class="event-date">
                            <?php echo $formatted_end ? 'Ended on ' . esc_html($formatted_end) : 'Ended'; ?>
                        </div>
                        <strong><?php echo esc_html($title); ?></strong>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    // Return the captured HTML.
    return ob_get_clean();
}
add_shortcode('user-past-registrations', 'conbook_user_past_registrations_shortcode');
?>