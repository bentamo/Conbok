<?php
// -------------------------------
// Shortcode: [event-dashboard-registration-tab]
// Displays only the Registrants list for the event dashboard
// -------------------------------
function conbook_event_dashboard_registration_tab_shortcode($atts) {
    ob_start();
    global $wpdb;

    // Accept either event_id or slug (attribute), otherwise fall back to URL query var
    $atts = shortcode_atts([
        'event_id' => 0,
        'slug'     => '',
    ], $atts, 'event-dashboard-registration-tab');

    $event_id = intval($atts['event_id']);

    if (!$event_id) {
        $slug = sanitize_text_field($atts['slug'] ?: get_query_var('event_slug', ''));
        if ($slug) {
            $event = get_page_by_path($slug, OBJECT, 'event');
            if ($event) {
                $event_id = intval($event->ID);
            }
        }
    }

    if (! $event_id) {
        echo '<p>No event selected or event not found.</p>';
        return ob_get_clean();
    }

    // --- Registrants ---
    $registrations_table = $wpdb->prefix . 'event_registrations';
    $registrants = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $registrations_table WHERE event_id = %d ORDER BY id DESC", $event_id),
        ARRAY_A
    );

    echo '<div class="registrants-card" style="padding:15px;border:1px solid #ddd;border-radius:20px;background:#fff;">';
    echo '<h4 style="margin:0 0 10px 0;font-size:16px;">Registrants</h4>';

    echo '<table style="width:100%;border-collapse:collapse;">';
    echo '<thead>';
    echo '<tr style="background:#f9f9f9;text-align:left;">';
    echo '<th style="padding:8px;border-bottom:1px solid #ddd;">Name</th>';
    echo '<th style="padding:8px;border-bottom:1px solid #ddd;">Email</th>';
    echo '<th style="padding:8px;border-bottom:1px solid #ddd;">Status</th>';
    echo '<th style="padding:8px;border-bottom:1px solid #ddd;">Action</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    if (! empty($registrants)) {
        foreach ($registrants as $r) {
            $name = esc_html(trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')));
            if ($name === '') {
                $name = 'Unnamed Registrant';
            }

            $email = esc_html($r['email'] ?? 'N/A');
            $status = esc_html($r['status'] ?? 'Pending');

            echo '<tr>';
            echo '<td style="padding:8px;border-bottom:1px solid #eee;">' . $name . '</td>';
            echo '<td style="padding:8px;border-bottom:1px solid #eee;">' . $email . '</td>';
            echo '<td style="padding:8px;border-bottom:1px solid #eee;">' . $status . '</td>';
            echo '<td style="padding:8px;border-bottom:1px solid #eee;"><a href="#" style="color:#0073aa;text-decoration:none;">View</a></td>';
            echo '</tr>';
        }
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';

    return ob_get_clean();
}
add_shortcode('event-dashboard-registration-tab', 'conbook_event_dashboard_registration_tab_shortcode');
?>
