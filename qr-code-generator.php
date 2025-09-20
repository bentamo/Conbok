<?php
/**
 * Plugin Name: QR Code Generator
 * Description: Minimal QR Code generator with secure token. Use shortcode [qrcode].
 * Version: 2.0
 * Author: You
 *
 * This plugin provides a shortcode `[qrcode]` to generate a QR code. The QR code
 * can either be based on a static text passed as a shortcode attribute or,
 * more dynamically, on a secure token for an event registration. It dynamically
 * looks up the user's "accepted" registration for a specific event based on
 * the URL slug and generates a QR code containing the unique guest token.
 * This is designed for ticket/entry verification systems.
 *
 * It uses the external PHP QR Code library, which must be included in the plugin directory.
 * The QR code is generated on-the-fly via a separate PHP file (`qr-image.php`)
 * to avoid storing the images on the server, which is an efficient approach.
 *
 * @package QR_Code_Generator
 * @since 1.0.0
 */

/* ==============================================
 * SECTION 1: DEPENDENCIES & SETUP
 * ============================================== */

// Include the QR Code library.
// It is crucial to have the `phpqrcode` folder with `qrlib.php` inside the plugin's root directory.
require_once plugin_dir_path(__FILE__) . 'phpqrcode/qrlib.php';

/* ==============================================
 * SECTION 2: SHORTCODE IMPLEMENTATION
 * ============================================== */

/**
 * Generates and displays a QR code.
 *
 * This shortcode retrieves a user's unique guest token for a specific event and
 * uses it to generate a QR code image.
 *
 * The shortcode's behavior depends on the context:
 * 1. If a 'text' attribute is provided (e.g., `[qrcode text="Hello"]`), it
 * generates a QR code for that text.
 * 2. If the 'text' attribute is empty, it attempts to find an accepted
 * registration for the current logged-in user and the event determined
 * from the URL's slug. It then uses the unique guest token associated with
 * that registration as the QR code data.
 *
 * @since 1.0.0
 *
 * @param array $atts Shortcode attributes.
 * @return string The HTML `<img>` tag for the QR code and a download link.
 */
function qr_code_generator_shortcode($atts) {
    global $wpdb;

    // Sanitize and normalize shortcode attributes.
    $atts = shortcode_atts(
        array(
            'text' => '', // Default empty, will be filled dynamically.
            'size' => 6,  // Default size, can be overridden.
        ),
        $atts,
        'qrcode'
    );

    $qr_text = $atts['text'];

    // If no static text is provided, attempt to generate the text from user data.
    if (empty($qr_text) && is_user_logged_in()) {
        $current_user = wp_get_current_user();

        // Retrieve event slug from URL, sanitizing for security.
        $slug = sanitize_text_field(get_query_var('event_slug', ''));

        if ($slug) {
            // Get the event post object by its slug.
            $event = get_page_by_path($slug, OBJECT, 'event');

            if ($event) {
                $event_id = $event->ID;
                $user_id  = $current_user->ID;

                // Define table names using the global `$wpdb` object for consistency.
                $table_registrations = $wpdb->prefix . "event_registrations";
                $table_guests        = $wpdb->prefix . "event_guests";

                // Query the database to find an 'accepted' registration for the current user and event.
                // Using prepared statements for security against SQL injection.
                $registration_id = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT id FROM {$table_registrations} WHERE user_id = %d AND event_id = %d AND status = 'accepted' LIMIT 1",
                        $user_id,
                        $event_id
                    )
                );

                // If an accepted registration is found, retrieve the guest token.
                if ($registration_id) {
                    $guest = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT token FROM {$table_guests} WHERE registration_id = %d LIMIT 1",
                            $registration_id
                        ),
                        ARRAY_A
                    );

                    // Set the QR code text to the unique guest token.
                    if ($guest && !empty($guest['token'])) {
                        $qr_text = $guest['token'];
                    }
                }
            }
        }
    }

    // Set a fallback text if no valid token was found or if the user is not registered.
    if (empty($qr_text)) {
        $qr_text = 'Not Registered';
    }

    // SECTION 2.1: QR Code Generation
    // -------------------------------
    // Build the URL for the QR code image, passing the sanitized text and size as parameters.
    // Use `urlencode()` to ensure the text is safe for a URL.
    $encoded_text = urlencode($qr_text);
    $size         = intval($atts['size']);
    $src          = plugins_url('qr-image.php', __FILE__) . "?text={$encoded_text}&size={$size}";

    // SECTION 2.2: HTML Output
    // -------------------------
    // Use output buffering to build the HTML string.
    ob_start();
    ?>
    <div class="qr-code-generator" style="text-align: center; margin: 20px 0;">
        <img src="<?php echo esc_url($src); ?>" alt="QR Code" style="border: 5px solid #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius: 10px; max-width: 100%; height: auto; display: block; margin: 0 auto;"/>
        <br>
        <a href="<?php echo esc_url($src); ?>" download="qrcode.png" style="display: inline-block; margin-top: 15px; padding: 10px 20px; font-family: sans-serif; font-size: 16px; font-weight: 600; text-decoration: none; color: #fff; background: linear-gradient(135deg, #ff4b2b, #7d3fff); border-radius: 30px; transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
            Download QR Code
        </a>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('qrcode', 'qr_code_generator_shortcode');