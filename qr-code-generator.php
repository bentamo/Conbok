<?php
/**
 * Plugin Name: QR Code Generator
 * Description: Minimal QR Code generator with secure token. Use shortcode [qrcode].
 * Version: 2.0
 * Author: You
 */

require_once plugin_dir_path(__FILE__) . 'phpqrcode/qrlib.php';

function qr_code_generator_shortcode($atts) {
    global $wpdb;

    $atts = shortcode_atts(
        array(
            'text' => '',  // will be filled dynamically if empty
            'size' => 6,
        ),
        $atts,
        'qrcode'
    );

    // ✅ Only try to build text if empty + user logged in
    if (empty($atts['text']) && is_user_logged_in()) {
        $current_user = wp_get_current_user();

        // 1. Get event slug from query var
        $slug = sanitize_text_field(get_query_var('event_slug', ''));
        if ($slug) {
            // 2. Get the event (post_type = 'event')
            $event = get_page_by_path($slug, OBJECT, 'event');
            if ($event) {
                $event_id = $event->ID;

                // 3. Correct table names
                $table_registrations = $wpdb->prefix . "event_registrations"; // wp_event_registrations
                $table_guests        = $wpdb->prefix . "event_guests";        // wp_event_guests

                // 4. Check if user has an accepted registration
                $registration_id = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT id 
                         FROM $table_registrations 
                         WHERE user_id = %d 
                         AND event_id = %d 
                         AND status = 'accepted' 
                         LIMIT 1",
                        $current_user->ID,
                        $event_id
                    )
                );

                if ($registration_id) {
                    // 5. Get the first guest linked to registration
                    $guest = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT token 
                             FROM $table_guests 
                             WHERE registration_id = %d 
                             LIMIT 1",
                            $registration_id
                        ),
                        ARRAY_A
                    );

                    if ($guest && !empty($guest['token'])) {
                        // 6. Use the token as QR code text
                        $atts['text'] = $guest['token'];
                    }
                }
            }
        }
    }

    // ✅ Fallback
    if (empty($atts['text'])) {
        $atts['text'] = 'Not Registered';
    }

    // Build QR code URL
    $text = urlencode($atts['text']);
    $size = intval($atts['size']);
    $src  = plugins_url('qr-image.php', __FILE__) . "?text={$text}&size={$size}";

    // Output QR code with download link
    $html  = '<div class="qr-code-generator">';
    $html .= '<img src="' . esc_url($src) . '" alt="QR Code" />';
    $html .= '<br>';
    $html .= '<a href="' . esc_url($src) . '" download="qrcode.png">Download QR Code</a>';
    $html .= '</div>';

    return $html;
}
add_shortcode('qrcode', 'qr_code_generator_shortcode');
