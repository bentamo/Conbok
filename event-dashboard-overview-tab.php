<?php
// Shortcode: [event-dashboard-overview-tab]
function conbook_event_dashboard_overview_tab_shortcode($atts) {
    global $wpdb;

    // Get the event slug from the URL
    $slug = sanitize_text_field(get_query_var('event_slug', ''));
    if (!$slug) return '';

    // Get the event by slug
    $event = get_page_by_path($slug, OBJECT, 'event');
    if (!$event) return '';

    $post_id = $event->ID;

    // -------------------------------
    // Cancel Event logic
    // -------------------------------
    if (isset($_GET['cancel_event']) && $_GET['cancel_event'] == 1) {
        // Delete featured image if exists
        $thumb_id = get_post_thumbnail_id($post_id);
        if ($thumb_id) {
            wp_delete_attachment($thumb_id, true);
        }

        // Delete proof(s) of payment stored as meta (if any)
        $proof_ids = get_post_meta($post_id, '_proof_of_payment_ids', true);
        if (!empty($proof_ids)) {
            if (!is_array($proof_ids)) {
                $proof_ids = [$proof_ids];
            }
            foreach ($proof_ids as $pid) {
                wp_delete_attachment(intval($pid), true);
            }
            delete_post_meta($post_id, '_proof_of_payment_ids');
        }

        // Delete registrations for this event
        $registrations_table = $wpdb->prefix . 'event_registrations';
        $registrations = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $registrations_table WHERE event_id = %d", $post_id),
            ARRAY_A
        );

        if ($registrations) {
            foreach ($registrations as $reg) {
                if (!empty($reg['proof_id'])) {
                    wp_delete_attachment(intval($reg['proof_id']), true);
                }
            }
            $wpdb->delete($registrations_table, ['event_id' => $post_id]);
        }

        // Delete the event itself
        wp_delete_post($post_id, true);

        // Redirect back to events list
        wp_safe_redirect(home_url('/view-events/'));
        exit;
    }

    // Start output
    $output = '<div class="event-dashboard-overview" style="padding-left:20px; padding-right:20px;">';

    // Event Title
    $title = get_the_title($post_id);
    if ($title) {
        $output .= '<h2 class="event-dashboard-title" style="margin-bottom:15px; font-size:24px;">' . esc_html($title) . '</h2>';
    }

    // Date and Time Card
    $start_date = get_post_meta($post_id, '_start_date', true);
    $end_date   = get_post_meta($post_id, '_end_date', true);
    $start_time = get_post_meta($post_id, '_start_time', true);
    $end_time   = get_post_meta($post_id, '_end_time', true);

    $output .= '<div class="event-datetime-card" style="display:flex; align-items:center; gap:10px; padding:15px; border:1px solid #ddd; border-radius:20px; margin-bottom:15px; background:#fff;">';

    // Calendar Icon
    $output .= '<div class="event-datetime-icon" style="flex-shrink:0; display:flex; align-items:center; justify-content:center;">';
    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="40" height="40" style="color:#444;">
        <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H3V6a2 2 0 0 1 2-2h1V3a1 1 0 0 1 1-1zM3 10h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V10zm5 3a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H8zm4 0a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12zm4 0a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H16z"/>
    </svg>';
    $output .= '</div>';

    // Date & Time Text
    $output .= '<div class="event-datetime-text" style="flex:1;">';
    if ($start_date || $start_time) {
        if ($start_date) {
            $formatted_start_date = date('m/d/Y', strtotime($start_date));
            $formatted_end_date   = $end_date ? date('m/d/Y', strtotime($end_date)) : '';
            $output .= '<div><strong>Date: </strong>' . esc_html($formatted_start_date);
            if ($formatted_end_date) {
                $output .= ' — ' . esc_html($formatted_end_date);
            }
            $output .= '</div>';
        }
        if ($start_time) {
            $formatted_start_time = date('h:i A', strtotime($start_time));
            $formatted_end_time   = $end_time ? date('h:i A', strtotime($end_time)) : '';
            $output .= '<div><strong>Time: </strong>' . esc_html($formatted_start_time);
            if ($formatted_end_time) {
                $output .= ' — ' . esc_html($formatted_end_time);
            }
            $output .= '</div>';
        }
    } else {
        $output .= '<p style="margin:0;">No schedule available.</p>';
    }
    $output .= '</div>'; // close datetime text

    $output .= '</div>'; // close datetime card

    // Location Card
    $location = get_post_meta($post_id, '_location', true);

    $output .= '<div class="event-location-card" style="display:flex; align-items:center; gap:10px; padding:15px; border:1px solid #ddd; border-radius:20px; margin-bottom:15px; background:#fff;">';

    // Location Icon
    $output .= '<div class="event-location-icon" style="flex-shrink:0; display:flex; align-items:center; justify-content:center;">';
    $output .= '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" width="40" height="40" viewBox="0 0 24 24" style="color:#444;">
        <path d="M12 2C8.1 2 5 5.1 5 9c0 5.2 7 13 7 13s7-7.8 7-13c0-3.9-3.1-7-7-7zm0 9.5c-1.4 0-2.5-1.1-2.5-2.5S10.6 6.5 12 6.5s2.5 1.1 2.5 2.5S13.4 11.5 12 11.5z"/>
    </svg>';
    $output .= '</div>';

    // Location Text
    $output .= '<div class="event-location-text" style="flex:1;">';
    if ($location) {
        $output .= '<strong>Location: </strong>' . esc_html($location);
    } else {
        $output .= '<p style="margin:0;">No location available.</p>';
    }
    $output .= '</div>'; // close location text

    $output .= '</div>'; // close location card

    // Ticket Options Card
    $tickets_table = $wpdb->prefix . 'event_tickets';
    $tickets = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $tickets_table WHERE event_id = %d", $post_id),
        ARRAY_A
    );

    $output .= '<div class="event-tickets-card" style="display:flex; align-items:center; gap:10px; padding:15px; border:1px solid #ddd; border-radius:20px; margin-bottom:15px; background:#fff;">';

    // Ticket Icon
    $output .= '<div class="event-ticket-icon" style="flex-shrink:0; display:flex; align-items:center; justify-content:center;">';
    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="40" height="40" style="color:#444;">
        <path d="M22 10V6a2 2 0 0 0-2-2h-2V2h-2v2H8V2H6v2H4a2 2 0 0 0-2 2v4h2a2 2 0 1 1 0 4H2v4a2 2 0 0 0 2 2h2v2h2v-2h8v2h2v-2h2a2 2 0 0 0 2-2v-4h-2a2 2 0 1 1 0-4h2z"/>
    </svg>';
    $output .= '</div>';

    // Ticket Text with gradient bullets
    $output .= '<div class="event-ticket-text" style="flex:1;">';
    $output .= '<strong>Ticket Options:</strong>';
    if (!empty($tickets)) {
        $output .= '<ul style="margin:5px 0 0 15px; padding:0; list-style:none;">';
        foreach ($tickets as $ticket) {
            $name  = esc_html($ticket['name'] ?? '');
            $price = isset($ticket['price']) ? number_format(floatval($ticket['price']), 2) : '0.00';
            $output .= '<li style="position:relative; padding-left:25px; margin-bottom:5px;">
                            <span style="
                                position:absolute; 
                                left:0; 
                                top:6px; 
                                width:12px; 
                                height:12px; 
                                border-radius:50%; 
                                background:linear-gradient(135deg, rgb(255,75,43) 0%, rgb(125,63,255) 100%);
                            "></span>
                            ' . $name . ' - Php ' . $price . '
                        </li>';
        }
        $output .= '</ul>';
    } else {
        $output .= '<p style="margin:0;">No tickets available.</p>';
    }
    $output .= '</div>'; // close ticket text

    $output .= '</div>'; // close tickets card

    // Payment Methods Card
    $payments_table = $wpdb->prefix . 'event_payment_methods';
    $payments = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $payments_table WHERE event_id = %d", $post_id),
        ARRAY_A
    );

    $output .= '<div class="event-payments-card" style="display:flex; align-items:center; gap:10px; padding:15px; border:1px solid #ddd; border-radius:20px; margin-bottom:15px; background:#fff;">';

    // Payment Icon (same style)
    $output .= '<div class="event-payment-icon" style="flex-shrink:0; display:flex; align-items:center; justify-content:center;">';
    $output .= '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" width="40" height="40" viewBox="0 0 24 24" style="color:#444;">
        <path d="M20 4H4a2 2 0 0 0-2 2v2h20V6a2 2 0 0 0-2-2zm2 6H2v8a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-8zm-4 4a1 1 0 1 1 0 2h-4a1 1 0 1 1 0-2h4z"/>
    </svg>';
    $output .= '</div>';

    // Payment Text with gradient bullets
    $output .= '<div class="event-payment-text" style="flex:1;">';
    $output .= '<strong>Payment Methods:</strong>';
    if (!empty($payments)) {
        $output .= '<ul style="margin:5px 0 0 15px; padding:0; list-style:none;">';
        foreach ($payments as $payment) {
            $name    = esc_html($payment['name'] ?? '');
            $details = esc_html($payment['details'] ?? '');
            $output .= '<li style="position:relative; padding-left:25px; margin-bottom:5px;">
                            <span style="
                                position:absolute; 
                                left:0; 
                                top:6px; 
                                width:12px; 
                                height:12px; 
                                border-radius:50%; 
                                background:linear-gradient(135deg, rgb(255,75,43) 0%, rgb(125,63,255) 100%);
                            "></span>
                            ' . $name . (!empty($details) ? ' - ' . $details : '') . '
                        </li>';
        }
        $output .= '</ul>';
    } else {
        $output .= '<p style="margin:0;">No payment methods available.</p>';
    }
    $output .= '</div>'; // close payment text

    $output .= '</div>'; // close payments card

    // Organizer Card
    $author_id = $event->post_author;
    $user_info = get_userdata($author_id);

    $organizer_name  = $user_info ? $user_info->display_name : 'Unknown Organizer';
    $organizer_email = $user_info ? $user_info->user_email : '';

    $output .= '<div class="event-organizer-card" style="display:flex; align-items:center; gap:10px; padding:15px; border:1px solid #ddd; border-radius:20px; margin-bottom:15px; background:#fff;">';

        // Organizer Icon (same style)
        $output .= '<div class="event-organizer-icon" style="flex-shrink:0; display:flex; align-items:center; justify-content:center;">';
        $output .= '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" width="40" height="40" viewBox="0 0 24 24" style="color:#444;">
            <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
        </svg>';
        $output .= '</div>';

        // Organizer Text
        $output .= '<div class="event-organizer-text" style="flex:1;">';
        $output .= '<strong>Organizer: </strong>' . esc_html($organizer_name);
        if ($organizer_email) {
            $output .= '<br><strong>Email: </strong><a href="mailto:' . esc_attr($organizer_email) . '">' . esc_html($organizer_email) . '</a>';
        }
        $output .= '</div>'; // close organizer text

    $output .= '</div>'; // close organizer card
    
    // Empty Container with two subcontainers side by side, transparent background
    $output .= '<div class="event-dashboard-empty" style="display:flex; gap:20px; flex-wrap:wrap; margin-bottom:15px; padding:15px; border-radius:20px; background:transparent;">';

        // Left Subcontainer with Edit Details button, left-aligned
        $output .= '<div class="empty-left" style="flex:1; min-width:150px; padding:10px; border-radius:10px; background:transparent; display:flex; justify-content:flex-start; align-items:center;">
            <a href="' . esc_url(home_url('/create-event/' . $slug)) . '" 
                style="
                    display:inline-block; 
                    padding:12px 25px; 
                    border-radius:30px; 
                    background:linear-gradient(135deg, rgb(125,63,255) 0%, rgb(255,75,43) 100%); 
                    font-family:Inter, sans-serif; 
                    font-weight:600; 
                    font-size:16px; 
                    color:#fff; 
                    text-decoration:none; 
                    text-align:center; 
                    box-shadow:0 2px 6px rgba(0,0,0,0.2); 
                    transition:opacity 0.3s ease;
                "
                onmouseover="this.style.opacity=\'0.85\'" 
                onmouseout="this.style.opacity=\'1\'"
            >
                Edit Details
            </a>
        </div>';

        // Right Subcontainer with Share Event button, right-aligned
        $output .= '<div class="empty-right" style="flex:1; min-width:150px; padding:10px; border-radius:10px; background:transparent; display:flex; justify-content:flex-end; align-items:center;">
            <a href="#" 
                class="share-event-btn"
                data-link="' . esc_url(home_url('/event-page/' . $slug)) . '"
                style="
                    display:inline-block; 
                    padding:12px 25px; 
                    border-radius:30px; 
                    background:linear-gradient(135deg, rgb(125,63,255) 0%, rgb(255,75,43) 100%); 
                    font-family:Inter, sans-serif; 
                    font-weight:600; 
                    font-size:16px; 
                    color:#fff; 
                    text-decoration:none; 
                    text-align:center; 
                    box-shadow:0 2px 6px rgba(0,0,0,0.2); 
                    transition:opacity 0.3s ease;
                "
                onclick="event.preventDefault(); 
                        navigator.clipboard.writeText(this.getAttribute(\'data-link\')).then(() => { 
                            alert(\'Event link copied to clipboard!\'); 
                        });"
                onmouseover="this.style.opacity=\'0.85\'" 
                onmouseout="this.style.opacity=\'1\'"
            >
                Share Event
            </a>
        </div>';

    $output .= '</div>'; // close transparent container

    // -------------------------------
    // Cancel Event Container
    // -------------------------------
    $output .= '<div class="event-cancel-card" style="padding:15px; border:1px solid #ddd; border-radius:20px; margin-bottom:15px; background:#fff; text-align:center;">';

        // Description above button
        $output .= '<div style="font-size:14px; color:#666; margin-bottom:10px;">
            Canceling this event will notify all registered attendees and remove it from the public calendar. This action cannot be undone.
        </div>';

        // Cancel Button
        $output .= '<a href="' . esc_url(add_query_arg('cancel_event', '1')) . '" 
            style="
                display:inline-block; 
                padding:12px 25px; 
                border-radius:30px; 
                background:linear-gradient(135deg, rgb(125,63,255) 0%, rgb(255,75,43) 100%); 
                font-family:Inter, sans-serif; 
                font-weight:600; 
                font-size:16px; 
                color:#fff; 
                text-decoration:none; 
                text-align:center; 
                box-shadow:0 2px 6px rgba(0,0,0,0.2); 
                transition:opacity 0.3s ease;
            "
            onmouseover="this.style.opacity=\'0.85\'" 
            onmouseout="this.style.opacity=\'1\'"
            onclick="return confirm(\'Are you sure you want to cancel this event? This cannot be undone.\');"
        >
            Cancel Event
        </a>';

    $output .= '</div>'; // close cancel card

    $output .= '</div>'; // close overview container

    return $output;
}
add_shortcode('event-dashboard-overview-tab', 'conbook_event_dashboard_overview_tab_shortcode');
?>
