<?php
// -------------------------------
// Shortcode: [event-page]
// -------------------------------
function conbook_event_page_shortcode($atts) {
    global $wpdb;

    // Get the event slug from the URL
    $slug = sanitize_text_field(get_query_var('event_slug', ''));
    if (!$slug) return '';

    // Get the event by slug
    $event = get_page_by_path($slug, OBJECT, 'event');
    if (!$event) return '';

    $post_id = $event->ID;
    
    // Flex container for 2 side-by-side boxes
    $output  = '<div class="event-details" 
        style="display:flex; gap:20px; flex-wrap:wrap; align-items:center;">';

    // Back to Personal Page button (above left container)
    if ( is_user_logged_in() ) {
        $back_url = home_url('/view-events/');

        $output .= '<div class="event-back-button" style="margin-bottom:20px; width:100%;">';
        $output .= '<a 
            href="' . esc_url($back_url) . '" 
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
            ← Back to Personal Page
        </a>';
        $output .= '</div>';
    }

    // Left container (image) — always show
    $output .= '<div class="event-details-left" style="flex:1; max-width:50%; padding-right:20px;">';

    $thumbnail_id = get_post_thumbnail_id($post_id);
    $image_url    = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'full') : '';

    if ($image_url) {
        $output .= '<img src="' . esc_url($image_url) . '" 
            alt="' . esc_attr(get_the_title($post_id)) . '" 
            style="max-width:100%; height:auto; border-radius:20px;">';
    } else {
        $output .= '<div style="
            width:100%; 
            aspect-ratio:16/9; 
            background:#f0f0f0; 
            border-radius:20px; 
            display:flex; 
            align-items:center; 
            justify-content:center; 
            color:#999; 
            font-size:16px; 
            font-style:italic;
        ">
            No Image Available
        </div>';
    }

    $output .= '</div>'; // close left container

    // Right container (details)
    $output .= '<div class="event-details-right" 
        style="flex:1; padding-left:20px; padding-top:15px;">';

    // Subcontainer for Title
    $title = get_the_title($post_id);
    if ($title) {
        $output .= '<div class="event-title-box" style="margin-bottom:15px;">';
        $output .= '<h2 class="event-title" style="margin:0;">' . esc_html($title) . '</h2>';
        $output .= '</div>';
    }

    // Subcontainer for Date and Time (always show card)
    $start_date = get_post_meta($post_id, '_start_date', true);
    $end_date   = get_post_meta($post_id, '_end_date', true);
    $start_time = get_post_meta($post_id, '_start_time', true);
    $end_time   = get_post_meta($post_id, '_end_time', true);

    $output .= '<div class="event-datetime-card" style="margin-bottom:15px; padding:15px; border:1px solid #ddd; border-radius:30px; box-shadow:0 2px 6px rgba(0,0,0,0.1); background:#fff; display:flex; align-items:center; gap:15px;">';

    // Calendar icon
    $output .= '<div class="event-datetime-icon" style="flex-shrink:0; display:flex; align-items:center; justify-content:center;">';
    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="50" height="50" style="color:#444;">
        <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H3V6a2 2 0 0 1 2-2h1V3a1 1 0 0 1 1-1zM3 10h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V10zm5 3a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H8zm4 0a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12zm4 0a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H16z"/>
    </svg>';
    $output .= '</div>';

    // Text
    $output .= '<div class="event-datetime-text" style="flex:1;">';

    if ($start_date || $start_time) {
        if ($start_date) {
            $formatted_start_date = date('m/d/Y', strtotime($start_date));
            $formatted_end_date   = $end_date ? date('m/d/Y', strtotime($end_date)) : '';
            $output .= '<div class="event-date" style="margin-bottom:5px;"><strong>Date: </strong>' . esc_html($formatted_start_date);
            if ($formatted_end_date) {
                $output .= ' — ' . esc_html($formatted_end_date);
            }
            $output .= '</div>';
        }

        if ($start_time) {
            $formatted_start_time = date('h:i A', strtotime($start_time));
            $formatted_end_time   = $end_time ? date('h:i A', strtotime($end_time)) : '';
            $output .= '<div class="event-time"><strong>Time: </strong>' . esc_html($formatted_start_time);
            if ($formatted_end_time) {
                $output .= ' — ' . esc_html($formatted_end_time);
            }
            $output .= '</div>';
        }
    } else {
        $output .= '<p style="margin:5px 0 0;">No schedule available.</p>';
    }

    $output .= '</div>'; // close text
    $output .= '</div>'; // close card

    // Subcontainer for Location (always show card)
    $location = get_post_meta($post_id, '_location', true);

    $output .= '<div class="event-location-card" style="margin-bottom:15px; padding:15px; border:1px solid #ddd; border-radius:30px; box-shadow:0 2px 6px rgba(0,0,0,0.1); background:#fff; display:flex; align-items:center; gap:15px;">';

    // Location icon (pin)
    $output .= '<div class="event-location-icon" style="flex-shrink:0; display:flex; align-items:center; justify-content:center;">';
    $output .= '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" width="50" height="50" viewBox="0 0 24 24" style="color:#444;">
        <path d="M12 2C8.1 2 5 5.1 5 9c0 5.2 7 13 7 13s7-7.8 7-13c0-3.9-3.1-7-7-7zm0 9.5c-1.4 0-2.5-1.1-2.5-2.5S10.6 6.5 12 6.5s2.5 1.1 2.5 2.5S13.4 11.5 12 11.5z"/>
    </svg>';
    $output .= '</div>';

    // Location text
    $output .= '<div class="event-location-text" style="flex:1;">';
    if ($location) {
        $output .= '<strong>Location: </strong>' . esc_html($location);
    } else {
        $output .= '<p style="margin:0;">No location available.</p>';
    }
    $output .= '</div>';

    $output .= '</div>'; // close card

    // Subcontainer for Ticket Options (always show card)
    $tickets_table = $wpdb->prefix . 'event_tickets';
    $tickets = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $tickets_table WHERE event_id = %d", $post_id),
        ARRAY_A
    );

    $output .= '<div class="event-tickets-card" style="margin-bottom:15px; padding:15px; border:1px solid #ddd; border-radius:30px; box-shadow:0 2px 6px rgba(0,0,0,0.1); background:#fff; display:flex; align-items:center; gap:15px;">';

    // Ticket icon
    $output .= '<div class="event-ticket-icon" style="flex-shrink:0; display:flex; align-items:center; justify-content:center;">';
    $output .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="50" height="50" style="color:#444;">
        <path d="M22 10V6a2 2 0 0 0-2-2h-2V2h-2v2H8V2H6v2H4a2 2 0 0 0-2 2v4h2a2 2 0 1 1 0 4H2v4a2 2 0 0 0 2 2h2v2h2v-2h8v2h2v-2h2a2 2 0 0 0 2-2v-4h-2a2 2 0 1 1 0-4h2z"/>
    </svg>';
    $output .= '</div>';

    // Ticket text
    $output .= '<div class="event-ticket-text" style="flex:1;">';
    $output .= '<strong>Ticket Options:</strong>';

    if (!empty($tickets)) {
        $output .= '<ul style="list-style:none; margin:0; padding:0 0 0 10px;">';
        foreach ($tickets as $ticket) {
            $name  = esc_html($ticket['name'] ?? '');
            $price = isset($ticket['price']) ? number_format(floatval($ticket['price']), 2) : '0.00';
            $output .= '<li style="position:relative; padding-left:25px; margin-bottom:2px;">
                            <span style="position:absolute; left:0; top:6px; width:12px; height:12px; border-radius:50%; 
                            background:linear-gradient(135deg, rgb(255,75,43) 0%, rgb(125,63,255) 100%);"></span>
                            ' . $name . ' - Php ' . $price . '
                        </li>';
        }
        $output .= '</ul>';
    } else {
        $output .= '<p style="margin:5px 0 0;">No tickets available.</p>';
    }

    $output .= '</div>';
    $output .= '</div>'; // close card

    // Subcontainer for Payment Methods (always show card)
    $payments_table = $wpdb->prefix . 'event_payment_methods';
    $payments = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $payments_table WHERE event_id = %d", $post_id),
        ARRAY_A
    );

    $output .= '<div class="event-payments-card" style="margin-bottom:15px; padding:15px; border:1px solid #ddd; border-radius:30px; box-shadow:0 2px 6px rgba(0,0,0,0.1); background:#fff; display:flex; align-items:center; gap:15px;">';

    // Payment icon
    $output .= '<div class="event-payments-icon" style="flex-shrink:0; display:flex; align-items:center; justify-content:center;">';
    $output .= '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" width="50" height="50" viewBox="0 0 24 24" style="color:#444;">
        <path d="M20 4H4a2 2 0 0 0-2 2v2h20V6a2 2 0 0 0-2-2zm2 6H2v8a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-8zm-4 4a1 1 0 1 1 0 2h-4a1 1 0 1 1 0-2h4z"/>
    </svg>';
    $output .= '</div>';

    // Payment text
    $output .= '<div class="event-payments-text" style="flex:1;">';
    $output .= '<strong>Payment Methods:</strong>';

    if (!empty($payments)) {
        $output .= '<ul style="list-style:none; margin:0; padding:0 0 0 10px;">';
        foreach ($payments as $payment) {
            $name    = esc_html($payment['name'] ?? '');
            $details = esc_html($payment['details'] ?? '');
            $output .= '<li style="position:relative; padding-left:25px; margin-bottom:2px;">
                            <span style="position:absolute; left:0; top:6px; width:12px; height:12px; border-radius:50%; 
                            background:linear-gradient(135deg, rgb(255,75,43) 0%, rgb(125,63,255) 100%);"></span>
                            ' . $name . (!empty($details) ? ' - ' . $details : '') . '
                        </li>';
        }
        $output .= '</ul>';
    } else {
        $output .= '<p style="margin:5px 0 0;">No payment methods available.</p>';
    }

    $output .= '</div>';
    $output .= '</div>'; // close card

    // Subcontainer for Organizer (always show card)
    $author_id = $event->post_author;
    $user_info = get_userdata($author_id);

    if ($user_info) {
        $organizer_name  = $user_info->display_name;
        $organizer_email = $user_info->user_email;
    } else {
        $organizer_name  = 'Unknown Organizer';
        $organizer_email = '';
    }

    $output .= '<div class="event-organizer-card" style="margin-bottom:15px; padding:15px; border:1px solid #ddd; border-radius:30px; box-shadow:0 2px 6px rgba(0,0,0,0.1); background:#fff; display:flex; align-items:center; gap:15px;">';

    // Organizer icon
    $output .= '<div class="event-organizer-icon" style="flex-shrink:0; display:flex; align-items:center; justify-content:center;">';
    $output .= '<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" width="50" height="50" viewBox="0 0 24 24" style="color:#444;">
        <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
    </svg>';
    $output .= '</div>';

    // Organizer text
    $output .= '<div class="event-organizer-text" style="flex:1;">';
    $output .= '<strong>Organizer: </strong>' . esc_html($organizer_name);

    if ($organizer_email) {
        $output .= '<br><strong>Email: </strong><a href="mailto:' . esc_attr($organizer_email) . '">' . esc_html($organizer_email) . '</a>';
    }

    $output .= '</div>';
    $output .= '</div>'; // close card

    // ----- Join Event / Manage Event button -----
    $current_user_id = get_current_user_id(); // ID of logged-in user, 0 if not logged in
    $author_id = intval($event->post_author); // Ensure it’s an integer

    // Default button (Join Event)
    $button_text = 'Join Event';
    $button_url  = home_url('/event-registration/' . $slug);

    // If logged-in user is the author, change to Manage Event
    if ( $current_user_id > 0 && $current_user_id === $author_id ) {
        $button_text = 'Manage Event';
        $button_url  = home_url('/event-dashboard/' . $slug);
    }

    $output .= '<div style="margin-bottom:20px;">
        <a href="' . esc_url($button_url) . '" style="
            display:block;
            width:100%;
            padding:12px 0;
            border-radius:30px; 
            background:linear-gradient(135deg,rgb(255,75,43) 0%,rgb(125,63,255) 100%); 
            font-weight:600;
            color:#fff;
            text-decoration:none;
            text-align:center;
            font-family:Inter,sans-serif;"
        >' . esc_html($button_text) . '</a>
    </div>';

    $output .= '</div>'; // close right container
    $output .= '</div>'; // close event-details

    // Description card — spans full width
    $description = wpautop($event->post_content);

    $output .= '<div class="event-description-card" 
        style="margin-top:20px; padding:35px; border:1px solid #ddd; 
        border-radius:30px; box-shadow:0 2px 6px rgba(0,0,0,0.1); 
        background:#fff;">';

    // Bold Heading
    $output .= '<h3 style="margin-top:0; margin-bottom:25px; font-size:20px; font-weight:700; font-family:Inter, sans-serif; color:#333;">Description</h3>';

    // Content
    if ($description) {
        $output .= $description;
    } else {
        $output .= '<p style="margin:0;">No description available.</p>';
    }

    $output .= '</div>'; // close card

    // Only show the popup if the user is NOT logged in
    if ( ! is_user_logged_in() ) {

        $register_url = home_url('/register/');

        // Inline style for animation
        $output .= '<style>
            @keyframes slideInUp {
                0% { transform: translateY(100px); opacity: 0; }
                100% { transform: translateY(0); opacity: 1; }
            }
            .event-cta-popup-close {
                position:absolute;
                top:8px;
                right:10px;
                font-size:18px;
                font-weight:bold;
                color:#888;
                cursor:pointer;
                transition:color 0.2s;
            }
            .event-cta-popup-close:hover {
                color:#333;
            }
        </style>';

        // Floating CTA popup — bottom right with slide-in
        $output .= '<div class="event-cta-popup" 
            style="
                position:fixed;
                bottom:20px;
                right:20px;
                z-index:9999;
                background:#fff;
                border-radius:30px;
                box-shadow:0 4px 12px rgba(0,0,0,0.15);
                padding:25px;
                display:flex;
                flex-direction:column;
                align-items:center;
                max-width:250px;
                text-align:center;
                font-family:Inter,sans-serif;
                animation: slideInUp 0.6s ease-out;
            ">

            <!-- Close button -->
            <span class="event-cta-popup-close" onclick="this.parentElement.style.display=\'none\'">&times;</span>

            <h3 style="margin:0 0 12px 0; font-size:16px; font-weight:700; color:#333;">Want to create events like this?</h3>

            <a href="' . esc_url($register_url) . '" 
                style="
                    display:block; 
                    width:100%; 
                    padding:8px 0; 
                    border-radius:30px; 
                    background:linear-gradient(135deg, rgb(255,75,43) 0%, rgb(125,63,255) 100%); 
                    font-weight:600; 
                    color:#fff; 
                    text-decoration:none; 
                    transition:opacity 0.3s ease;
                    text-align:center;
                "
                onmouseover="this.style.opacity=\'0.85\'" 
                onmouseout="this.style.opacity=\'1\'"
            >Register</a>
        </div>';
    }

    return $output;
}
add_shortcode('event-page', 'conbook_event_page_shortcode');
?>
