<?php
/**
 * Shortcode for Displaying a User's Upcoming Registrations.
 *
 * This file defines the `[user-upcoming-registrations]` shortcode, which queries and
 * displays a list of events for which the current logged-in user has registered.
 * The list includes events that are currently live or are scheduled for the future.
 *
 * The shortcode uses a secure database query (`$wpdb`) to join the custom
 * `event_registrations` table with the standard WordPress `posts` and `postmeta` tables.
 * This ensures that all necessary event details and registration status are
 * fetched efficiently. Each event card also includes a badge indicating the
 * registration status (e.g., 'PENDING', 'ACCEPTED') and a 'LIVE' badge if applicable.
 *
 * @package ConBook
 * @subpackage Shortcodes
 * @since 1.0.0
 */

/* ==============================================
 * SECTION 1: USER UPCOMING REGISTRATIONS SHORTCODE
 * ============================================== */

/**
 * Renders a list of a user's upcoming and live event registrations.
 *
 * This function retrieves all registrations for the current user for events that
 * have not yet ended. It constructs a secure SQL query using `$wpdb->prepare()`
 * to prevent SQL injection. The results are ordered by the event's start date
 * in ascending order.
 *
 * Each event is displayed as a card containing the event's title, start date, and
 * a thumbnail image. Badges are dynamically added to the card to show the
 * user's **registration status** (`PENDING`, `ACCEPTED`, or `DECLINED`) and a
 * **live status** (`LIVE`) if the event is currently in progress.
 *
 * @since 1.0.0
 *
 * @return string The HTML output for the grid of upcoming registrations or a message if none are found.
 */
function conbook_user_upcoming_registrations_shortcode() {
    // 1. SECURITY: Check if the user is logged in.
    if (!is_user_logged_in()) {
        return '<p>Please log in to see your upcoming registrations.</p>';
    }

    global $wpdb;
    $user_id = get_current_user_id();
    $now     = current_time('Y-m-d H:i:s');

    // 2. DATA: Define table names for the SQL query.
    $table_reg      = $wpdb->prefix . 'event_registrations';
    $table_events   = $wpdb->prefix . 'posts';
    $table_postmeta = $wpdb->prefix . 'postmeta';

    // 3. QUERY: Fetch registrations for events that have not yet ended.
    // Using a JOIN to get all required data in one query.
    // The query checks for events where the `_end_datetime` is in the future or is not set (`IS NULL`).
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT r.*, e.ID as event_id, e.post_title, e.post_name,
               pm.meta_value AS start_datetime,
               pm_end.meta_value AS end_datetime
        FROM {$table_reg} r
        INNER JOIN {$table_events} e ON r.event_id = e.ID
        LEFT JOIN {$table_postmeta} pm ON pm.post_id = e.ID AND pm.meta_key = '_start_datetime'
        LEFT JOIN {$table_postmeta} pm_end ON pm_end.post_id = e.ID AND pm_end.meta_key = '_end_datetime'
        WHERE r.user_id = %d
          AND e.post_type = 'event'
          AND e.post_status = 'publish'
          AND (pm_end.meta_value IS NULL OR pm_end.meta_value >= %s)
        GROUP BY r.id
        ORDER BY pm.meta_value ASC
    ", $user_id, $now), ARRAY_A);

    // 4. LOGIC: Check if any upcoming registrations were found.
    if (!$results) {
        return '<p>No upcoming registrations found.</p>';
    }

    // 5. OUTPUT: Start output buffering to capture the HTML.
    ob_start();
    ?>
    <div class="user-upcoming-events-wrapper">
        <div class="user-upcoming-events">
            <?php foreach ($results as $row):
                $event_id       = $row['event_id'];
                $event_slug     = $row['post_name'];
                $title          = $row['post_title'];
                $status         = ucfirst($row['status']);
                $start_datetime = $row['start_datetime'];
                $end_datetime   = $row['end_datetime'];

                // Sanitize and format data for display.
                $event_url = home_url('/event-page/?event_slug=' . $event_slug);
                $image_url = get_the_post_thumbnail_url($event_id, 'medium')
                           ?: 'https://via.placeholder.com/300x300?text=No+Image';

                // Determine if event is live.
                $is_live = ($start_datetime && $end_datetime && $start_datetime <= $now && $end_datetime >= $now);

                // Get CSS class for the status badge.
                $status_class = strtolower($row['status']);
            ?>
                <a href="<?php echo esc_url($event_url); ?>" class="event-card">
                    <?php if ($is_live): ?>
                        <div class="event-badge live">LIVE</div>
                    <?php endif; ?>

                    <div class="event-badge status-badge <?php echo esc_attr($status_class); ?>">
                        <?php echo esc_html(strtoupper($status)); ?>
                    </div>

                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>">

                    <div class="event-card-content">
                        <div class="event-date">
                            <?php echo $start_datetime ? date('m/d/y • g:i A', strtotime($start_datetime)) : 'TBA'; ?>
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

add_shortcode('user-upcoming-registrations', 'conbook_user_upcoming_registrations_shortcode');
?>