<?php
// -------------------------------
// Shortcode: [event-registrants]
// -------------------------------
function conbook_event_registrants_shortcode() {
    global $wpdb;

    // Get the event slug from URL
    $slug = sanitize_text_field($_GET['event-slug'] ?? '');
    if (!$slug) return '<p>No event specified.</p>';

    // Get the event by slug
    $event = get_page_by_path($slug, OBJECT, 'event');
    if (!$event) return '<p>Event not found.</p>';

    $post_id = $event->ID;
    $table_registrations = $wpdb->prefix . 'event_registrations';
    $table_tickets       = $wpdb->prefix . 'event_tickets';

    // Get all registrants for this event
    $registrants = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT r.id, r.user_id, r.ticket_id, r.status, r.created_at,
                    t.name AS ticket_name, t.price AS ticket_price
             FROM $table_registrations r
             LEFT JOIN $table_tickets t ON r.ticket_id = t.id
             WHERE r.event_id = %d
             ORDER BY r.created_at DESC",
            $post_id
        ),
        ARRAY_A
    );

    if (!$registrants) {
        return '<p>No registrants found for this event.</p>';
    }

    ob_start();
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <th>User</th>
                <th>Ticket</th>
                <th>Price</th>
                <th>Status</th>
                <th>Registered At</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($registrants as $r): ?>
            <tr>
                <td>
                    <?php 
                    $user = get_userdata($r['user_id']);
                    echo $user ? esc_html($user->display_name) : 'User ID: ' . intval($r['user_id']);
                    ?>
                </td>
                <td><?php echo esc_html($r['ticket_name']); ?></td>
                <td><?php echo esc_html(number_format($r['ticket_price'], 2)); ?></td>
                <td><?php echo esc_html($r['status']); ?></td>
                <td><?php echo !empty($r['created_at']) ? esc_html($r['created_at']) : ''; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    return ob_get_clean();
}
add_shortcode('show-event-registrants', 'conbook_event_registrants_shortcode');
