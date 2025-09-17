<?php
// -------------------------------
// Shortcode: [user-upcoming-registrations]
// Displays all upcoming event registrations (LIVE + upcoming)
// -------------------------------
function conbook_user_upcoming_registrations_shortcode() {

    // Ensure the user is logged in
    if (!is_user_logged_in()) {
        return '<p>Please log in to see your upcoming registrations.</p>';
    }

    global $wpdb;
    $user_id = get_current_user_id();
    $now     = current_time('Y-m-d H:i:s');

    // Table names
    $table_reg      = $wpdb->prefix . 'event_registrations';
    $table_events   = $wpdb->prefix . 'posts';
    $table_postmeta = $wpdb->prefix . 'postmeta';

    // Fetch upcoming registrations (events that have not ended)
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

    if (!$results) {
        return '<p>No upcoming registrations found.</p>';
    }

    ob_start();
    ?>
    <div class="user-upcoming-events-wrapper">
        <div class="user-upcoming-events">
            <?php foreach ($results as $row):
                $event_id       = $row['event_id'];
                $event_slug     = $row['post_name'];
                $title          = $row['post_title'];
                $status         = ucfirst($row['status']); // Pending / Accepted / Declined
                $start_datetime = $row['start_datetime'];
                $end_datetime   = $row['end_datetime'];

                $event_url = home_url('/event-page/?event_slug=' . $event_slug);
                $image_url = get_the_post_thumbnail_url($event_id, 'medium') 
                             ?: 'https://via.placeholder.com/300x300?text=No+Image';

                // Determine if event is live (current time between start and end)
                $is_live = ($start_datetime && $end_datetime && $start_datetime <= $now && $end_datetime >= $now);

                // Registrant status CSS class for badges
                $status_class = strtolower($row['status']); // pending, accepted, declined
            ?>
                <a href="<?php echo esc_url($event_url); ?>" class="event-card" style="position:relative;">
                    
                    <!-- Event Status Badge (upper-left) -->
                    <?php if ($is_live): ?>
                        <div class="event-badge live" style="top:10px; left:10px; right:auto;">LIVE</div>
                    <?php endif; ?>

                    <!-- Registrant Status Badge (upper-right) -->
                    <div class="event-badge status-badge <?php echo esc_attr($status_class); ?>" style="top:10px; right:10px; left:auto;">
                        <?php echo esc_html(strtoupper($status)); ?>
                    </div>

                    <!-- Event Image -->
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($title); ?>">

                    <!-- Card Content -->
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

    return ob_get_clean();
}

add_shortcode('user-upcoming-registrations', 'conbook_user_upcoming_registrations_shortcode');
?>
