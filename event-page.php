<?php
// -------------------------------
// Shortcode: [event-page]
// -------------------------------
function conbook_event_page_shortcode($atts) {
    global $wpdb;

    // Add CSS inline once
    $css = '
    <style>
    /* Shared Glass Base */
    .glass-base {
      border-radius: 20px;
      border: 1px solid rgba(255, 255, 255, 0.15);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
    }
    /* Glass Card (5% opacity, 10px blur) */
    .glass-card {
      background: rgba(255, 255, 255, 0.05);
    }
    /* Glass Modal (8% opacity, 25px blur) */
    .glass-modal {
      background: rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(25px);
      -webkit-backdrop-filter: blur(25px);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }
    /* Animation for popup */
    @keyframes slideInUp {
      0% { transform: translateY(100px); opacity: 0; }
      100% { transform: translateY(0); opacity: 1; }
    }
    </style>
    ';

    // Get the event slug from the URL
    $slug = sanitize_text_field(get_query_var('event_slug', ''));
    if (!$slug) return '';

    // Get the event by slug
    $event = get_page_by_path($slug, OBJECT, 'event');
    if (!$event) return '';

    $post_id = $event->ID;
    // -------------------------------
// Check if event has ended
// -------------------------------
    $end_datetime   = get_post_meta($post_id, '_end_datetime', true);
    $now            = current_time('Y-m-d H:i:s');

    $is_past_event = false;
    if ($end_datetime && strtotime($end_datetime) < strtotime($now)) {
        $is_past_event = true;
    }

    
    // Start building output with CSS
    $output  = $css;
    $output .= '<div class="event-details" 
        style="display:flex; gap:20px; flex-wrap:wrap; align-items:center;">';

    // Back to Personal Page button
    if ( is_user_logged_in() ) {
        $back_url = home_url('/view-events/');

        $output .= '<div class="event-back-button" style="margin-bottom:20px; width:100%;">';
        $output .= '<a 
            href="' . esc_url($back_url) . '" 
            style="
                display:inline-block;
                padding:12px 20px;
                border-radius:30px;
                background:linear-gradient(135deg, rgb(255,75,43) 0%, rgb(125,63,255) 100%);
                font-family:Inter, sans-serif;
                font-weight:500;
                font-size:16px;
                color:#fff;
                text-decoration:none;
                text-align:center;
                box-shadow:0 2px 6px rgba(0,0,0,0.2);
                transition: all 0.3s ease;
                transform: translateY(0);
                position: relative;
            "
            onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 4px 15px #F07BB1\'; this.style.color=\'#F07BB1\';"
            onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 2px 6px rgba(0,0,0,0.2)\'; this.style.color=\'#fff\';"
        >
            ← Back to Personal Page
        </a>';
        $output .= '</div>';
    }

    // Left container (image)
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
    $output .= '<div class="event-details-right" style="flex:1; padding-left:20px; padding-top:15px;">';

    // Title
    $title = get_the_title($post_id);
    if ($title) {
        $output .= '<div class="event-title-box" style="margin-bottom:15px;">';
        $output .= '<h2 class="event-title" style="margin:0;">' . esc_html($title) . '</h2>';
        $output .= '</div>';
    }

    // Date & Time card
    $start_date = get_post_meta($post_id, '_start_date', true);
    $end_date   = get_post_meta($post_id, '_end_date', true);
    $start_time = get_post_meta($post_id, '_start_time', true);
    $end_time   = get_post_meta($post_id, '_end_time', true);

    $output .= '<div class="event-datetime-card glass-base glass-card" style="margin-bottom:15px; padding:15px; display:flex; align-items:center; gap:15px;">';
    $output .= '<div class="event-datetime-icon" style="flex-shrink:0; display:flex; align-items:center; justify-content:center;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="50" height="50" style="color:#444;">
            <path d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H3V6a2 2 0 0 1 2-2h1V3a1 1 0 0 1 1-1zM3 10h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V10zm5 3a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H8zm4 0a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H12zm4 0a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H16z"/>
        </svg>
    </div>';
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
    $output .= '</div></div>';

    // Location card
    $location = get_post_meta($post_id, '_location', true);
    $output .= '<div class="event-location-card glass-base glass-card" style="margin-bottom:15px; padding:15px; display:flex; align-items:center; gap:15px;">';
    $output .= '<div class="event-location-icon" style="flex-shrink:0; display:flex; align-items:center; justify-content:center;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" width="50" height="50" viewBox="0 0 24 24" style="color:#444;">
            <path d="M12 2C8.1 2 5 5.1 5 9c0 5.2 7 13 7 13s7-7.8 7-13c0-3.9-3.1-7-7-7zm0 9.5c-1.4 0-2.5-1.1-2.5-2.5S10.6 6.5 12 6.5s2.5 1.1 2.5 2.5S13.4 11.5 12 11.5z"/>
        </svg>
    </div>';
    $output .= '<div class="event-location-text" style="flex:1;">';
    if ($location) {
        $output .= '<strong>Location: </strong>' . esc_html($location);
    } else {
        $output .= '<p style="margin:0;">No location available.</p>';
    }
    $output .= '</div></div>';

    // Tickets card
    $tickets_table = $wpdb->prefix . 'event_tickets';
    $tickets = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $tickets_table WHERE event_id = %d", $post_id),
        ARRAY_A
    );
    $output .= '<div class="event-tickets-card glass-base glass-card" style="margin-bottom:15px; padding:15px; display:flex; align-items:center; gap:15px;">';
    $output .= '<div class="event-ticket-icon" style="flex-shrink:0; display:flex; align-items:center; justify-content:center;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="50" height="50" style="color:#444;">
            <path d="M22 10V6a2 2 0 0 0-2-2h-2V2h-2v2H8V2H6v2H4a2 2 0 0 0-2 2v4h2a2 2 0 1 1 0 4H2v4a2 2 0 0 0 2 2h2v2h2v-2h8v2h2v-2h2a2 2 0 0 0 2-2v-4h-2a2 2 0 1 1 0-4h2z"/>
        </svg>
    </div>';
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
    $output .= '</div></div>';

    // Payments card
    $payments_table = $wpdb->prefix . 'event_payment_methods';
    $payments = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $payments_table WHERE event_id = %d", $post_id),
        ARRAY_A
    );
    $output .= '<div class="event-payments-card glass-base glass-card" style="margin-bottom:15px; padding:15px; display:flex; align-items:center; gap:15px;">';
    $output .= '<div class="event-payments-icon" style="flex-shrink:0; display:flex; align-items:center; justify-content:center;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" width="50" height="50" viewBox="0 0 24 24" style="color:#444;">
            <path d="M20 4H4a2 2 0 0 0-2 2v2h20V6a2 2 0 0 0-2-2zm2 6H2v8a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-8zm-4 4a1 1 0 1 1 0 2h-4a1 1 0 1 1 0-2h4z"/>
        </svg>
    </div>';
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
    $output .= '</div></div>';

    // Organizer card
    $author_id = $event->post_author;
    $user_info = get_userdata($author_id);
    $organizer_name  = $user_info ? $user_info->display_name : 'Unknown Organizer';
    $organizer_email = $user_info ? $user_info->user_email : '';
    $output .= '<div class="event-organizer-card glass-base glass-card" style="margin-bottom:15px; padding:15px; display:flex; align-items:center; gap:15px;">';
    $output .= '<div class="event-organizer-icon" style="flex-shrink:0; display:flex; align-items:center; justify-content:center;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" width="50" height="50" viewBox="0 0 24 24" style="color:#444;">
            <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
        </svg>
    </div>';
    $output .= '<div class="event-organizer-text" style="flex:1;">';
    $output .= '<strong>Organizer: </strong>' . esc_html($organizer_name);
    if ($organizer_email) {
        $output .= '<br><strong>Email: </strong><a href="mailto:' . esc_attr($organizer_email) . '">' . esc_html($organizer_email) . '</a>';
    }
    $output .= '</div></div>';

    $current_user_id = get_current_user_id();
    $author_id = intval($event->post_author);

    if ($current_user_id === $author_id) {
        // Organizer always sees Manage Event
        $button_text  = 'Manage Event';
        $button_url   = home_url('/event-dashboard/' . $slug);
        $button_style = '';
    } else if ($is_past_event) {
        // Registrant sees past event
        $button_text  = 'Event Ended';
        $button_url   = '#';
        $button_style = 'pointer-events:none; cursor:default; opacity:0.6;';
    } else if ($current_user_id > 0) {
        // Registrant logic for upcoming events
        global $wpdb;
        $registrations_table = $wpdb->prefix . 'event_registrations';

        $registration = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $registrations_table WHERE event_id = %d AND user_id = %d",
                $post_id,
                $current_user_id
            ),
            ARRAY_A
        );

        if ($registration) {
            switch ($registration['status']) {
                case 'pending':
                case 'accepted':
                    // Minimal logic: add a description about QR code
                    $button_text = 'Cancel Registration';
                    $button_url  = home_url('/event-registration/cancel/' . $registration['id']);

                    // Add clickable link to show QR code popup (text centered)
                    $output .= '<p style="margin-bottom:10px; font-size:14px; color:#555; text-align:center;">
                                    <a href="#" id="view-qr-link" style="color:#FF4B2B; text-decoration:underline; cursor:pointer;">
                                        View your QR code for event entry
                                    </a>
                                </p>';

                    // Embed QR code in a hidden popup (bottom-right)
                    $output .= '<div id="qr-popup" class="glass-base glass-modal" style="
                        display:none; /* hidden by default */
                        position:fixed; bottom:20px; right:20px; z-index:9999;
                        padding:25px; flex-direction:column; align-items:center;
                        max-width:250px; text-align:center; font-family:Inter,sans-serif;
                        animation: slideInUp 0.6s ease-out;
                    ">
                        <span onclick="document.getElementById(\'qr-popup\').style.display=\'none\'" style="
                            position:absolute; top:8px; right:10px; font-size:18px; font-weight:bold; color:#888; cursor:pointer;">&times;</span>
                        ' . do_shortcode('[qrcode]') . '
                    </div>';

                    // Add inline JS to toggle the popup
                    $output .= '<script>
                        document.getElementById("view-qr-link").addEventListener("click", function(e){
                            e.preventDefault();
                            var popup = document.getElementById("qr-popup");
                            popup.style.display = "flex"; // show only on click
                        });
                    </script>';
                    break;
                case 'declined':
                    $button_text = 'Remove Event';
                    $button_url  = home_url('/event-registration/remove/' . $registration['id']);
                    break;
            }
        } else {
            $user_email = wp_get_current_user()->user_email;
            $guests_table = $wpdb->prefix . 'event_guests';
            $existing_email = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $guests_table WHERE event_id = %d AND email = %s",
                    $post_id,
                    $user_email
                )
            );

            if ($existing_email > 0) {
                $button_text = 'Already Registered';
                $button_url  = '#';
            } else {
                $button_text  = 'Join Event';
                $button_url   = home_url('/event-registration/' . $slug);
                $button_style = '';
            }
        }
    } else {
        // Not logged in
        $button_text = 'Join Event';
        $button_url  = home_url('/event-registration/' . $slug);
        $button_style = '';
    }


    $output .= '<div style="margin-bottom:20px;">
        <a href="' . esc_url($button_url) . '" style="
            display:block; width:100%; padding:12px 20px;
            border-radius:30px; 
            background:linear-gradient(135deg, rgb(255,75,43) 0%, rgb(125,63,255) 100%);
            font-family:Inter, sans-serif; font-weight:500; font-size:16px;
            color:#fff; text-decoration:none; text-align:center;
            box-shadow:0 2px 6px rgba(0,0,0,0.2); transition:all 0.3s ease;
            transform:translateY(0);
            position:relative;
            ' . $button_style . '
        "
        onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 4px 15px #F07BB1\'; this.style.color=\'#F07BB1\';"
        onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 2px 6px rgba(0,0,0,0.2)\'; this.style.color=\'#fff\';"
        >' . esc_html($button_text) . '</a>
    </div>';

    $output .= '</div>'; // close right container
    $output .= '</div>'; // close event-details
    
    // Description card
    $description = wpautop($event->post_content);
    $output .= '<div class="event-description-card glass-base glass-card" style="margin-top:20px; padding:35px;">';
    $output .= '<h3 style="margin-top:0; margin-bottom:25px; font-size:20px; font-weight:700; font-family:Inter, sans-serif; color:#333;">Description</h3>';
    $output .= $description ?: '<p style="margin:0;">No description available.</p>';
    $output .= '</div>';
    
    // Popup (Glass Modal Style)
    if ( ! is_user_logged_in() ) {
        $register_url = home_url('/register/');
        $output .= '<div class="event-cta-popup glass-base glass-modal" style="
            position:fixed; bottom:20px; right:20px; z-index:9999;
            padding:25px; display:flex; flex-direction:column; align-items:center;
            max-width:250px; text-align:center; font-family:Inter,sans-serif;
            animation: slideInUp 0.6s ease-out;">
            <span class="event-cta-popup-close" onclick="this.parentElement.style.display=\'none\'" style="
                position:absolute; top:8px; right:10px; font-size:18px; font-weight:bold; color:#888; cursor:pointer;">&times;</span>
            <h3 style="margin:0 0 12px 0; font-size:16px; font-weight:600; color:#333;">Want to create events like this?</h3>
            <a href="' . esc_url($register_url) . '" style="
                display:block; width:100%; padding:8px 0;
                border-radius:30px;
                background:linear-gradient(135deg, rgb(255,75,43) 0%, rgb(125,63,255) 100%);
                font-weight:500; color:#fff; text-decoration:none;
                text-align:center; font-family:Inter, sans-serif;
                transition:all 0.3s ease;
                transform:translateY(0);
                box-shadow:0 2px 6px rgba(0,0,0,0.2);
                position:relative;
            "
            onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 4px 15px #F07BB1\'; this.style.color=\'#F07BB1\';"
            onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 2px 6px rgba(0,0,0,0.2)\'; this.style.color=\'#fff\';"
            >Register</a>
        </div>';
    }

    return $output;
}
add_shortcode('event-page', 'conbook_event_page_shortcode');
?>
