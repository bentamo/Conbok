<?php
/**
 * Shortcode for Event Registration Form.
 *
 * This file contains the shortcode to display a frontend form that allows users
 * to register for a specific event, select a ticket and payment method, and
 * upload a proof of payment. It also handles the form submission logic,
 * including validation, data sanitization, and database insertion.
 *
 * @package ConBook
 * @subpackage Shortcodes
 */

/* ==============================================
 * SECTION 1: EVENT REGISTRATION SHORTCODE
 * ============================================== */

/**
 * Renders the event registration form and processes its submission.
 *
 * This function serves two main purposes:
 * 1. It displays a dynamic form to the user, populated with details of a specific
 * event identified by its slug in the URL (e.g., `event-registration/my-event-slug`).
 * The form includes fields for the user's details, ticket selection, payment
 * method selection, and a file upload for proof of payment.
 * 2. It handles the form submission via a POST request. It performs crucial security
 * checks, sanitizes all incoming data, handles the secure upload of the
 * payment proof, prevents duplicate registrations, and inserts the
 * registration details into the `event_registrations` custom database table.
 *
 * @since 1.0.0
 *
 * @return string The HTML output of the form or a success/error message with a redirect script.
 */
function event_registration() {
    global $wpdb;

    // SECTION 1.1: Data Initialization and Retrieval
    // ---------------------------------------------
    $event_title     = 'Event';
    $post_id         = 0;
    $ticket_options  = [];
    $payment_options = [];

    // SECURITY: Sanitize and validate event slug from URL.
    $slug = sanitize_text_field(get_query_var('event_slug', ''));
    if (empty($slug)) {
        return '<p>Event not found. Please check the URL.</p>';
    }

    // Retrieve the event post object by its slug.
    $event = get_page_by_path($slug, OBJECT, 'event');
    if (!$event) {
        return '<p>Event not found. Please check the URL.</p>';
    }

    $post_id     = $event->ID;
    $event_title = $event->post_title;

    // Retrieve tickets from event_tickets table using prepared statements for security.
    $tickets = $wpdb->get_results(
        $wpdb->prepare("SELECT id, name, price FROM {$wpdb->prefix}event_tickets WHERE event_id = %d", $post_id),
        ARRAY_A
    );
    if (!empty($tickets)) {
        foreach ($tickets as $t) {
            $ticket_options[$t['id']] = esc_html($t['name']) . ' - Php ' . number_format(floatval($t['price']), 2);
        }
    }

    // Retrieve payment methods from event_payment_methods table.
    $payments = $wpdb->get_results(
        $wpdb->prepare("SELECT id, name, details FROM {$wpdb->prefix}event_payment_methods WHERE event_id = %d", $post_id),
        ARRAY_A
    );
    if (!empty($payments)) {
        foreach ($payments as $p) {
            $payment_options[$p['id']] = esc_html($p['name']) . ' - ' . esc_html($p['details']);
        }
    }

    // Fallback if no tickets or payments are configured.
    if (empty($ticket_options)) {
        $ticket_options = [0 => 'No tickets available'];
    }
    if (empty($payment_options)) {
        $payment_options = [0 => 'No payment methods available'];
    }

    // Get current logged-in user information for pre-filling the form.
    $current_user = wp_get_current_user();
    $first_name   = $current_user->first_name ?? '';
    $last_name    = $current_user->last_name ?? '';
    $email        = $current_user->user_email ?? '';
    $contact      = get_user_meta($current_user->ID, 'contact-number-textbox', true) ?? '';

    // SECTION 1.2: Form Submission Handling
    // ------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // SECURITY: Verify nonce to prevent CSRF attacks.
        if (!isset($_POST['event_registration_nonce']) || !wp_verify_nonce($_POST['event_registration_nonce'], 'event_registration')) {
            wp_die('Security check failed. Please try again.');
        }

        // SANITIZATION: Sanitize all user inputs before use.
        $ticket_id         = intval($_POST['ticket_id']);
        $payment_method_id = intval($_POST['payment_method_id']);
        $proof_id          = 0;

        // Handle file upload securely.
        if (!empty($_FILES['proof_of_payment']['name'])) {
            // Load required WordPress media handling files.
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';

            // Process the file upload.
            $upload = wp_handle_upload($_FILES['proof_of_payment'], ['test_form' => false]);

            if ($upload && !isset($upload['error'])) {
                // Create the attachment post and generate metadata.
                $filetype   = wp_check_filetype($upload['file']);
                $attachment = [
                    'post_mime_type' => $filetype['type'],
                    'post_title'     => sanitize_file_name($_FILES['proof_of_payment']['name']),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                ];
                $proof_id = wp_insert_attachment($attachment, $upload['file'], $post_id);
                wp_generate_attachment_metadata($proof_id, $upload['file']);
            }
        }

        // Prevent duplicate registration for the current user.
        $current_user_id = get_current_user_id();

        // Check if user already registered for this event.
        $existing_registration = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}event_registrations WHERE event_id = %d AND user_id = %d",
                $post_id,
                $current_user_id
            )
        );

        if ($existing_registration > 0) {
            // Inform the user and redirect to the event page.
            $redirect_url = esc_url(home_url('/event-page/' . $slug));
            return '<script>
                        alert("⚠️ You have already registered for this event!");
                        window.location.href = "' . $redirect_url . '";
                    </script>';
        }

        // Insert registration into custom table.
        $table = $wpdb->prefix . 'event_registrations';
        $wpdb->insert(
            $table,
            [
                'user_id'           => $current_user_id,
                'event_id'          => $post_id,
                'ticket_id'         => $ticket_id,
                'payment_method_id' => $payment_method_id,
                'proof_id'          => $proof_id,
                'status'            => 'pending',
                'created_at'        => current_time('mysql')
            ],
            [
                '%d', // user_id
                '%d', // event_id
                '%d', // ticket_id
                '%d', // payment_method_id
                '%d', // proof_id
                '%s', // status
                '%s'  // created_at
            ]
        );

        // Success message with alert + auto-redirect.
        $redirect_url = esc_url(home_url('/event-page/' . $slug . '/'));
        return '<script>
                    alert("✅ Registration submitted for ' . esc_js($event_title) . '");
                    window.location.href = "' . $redirect_url . '";
                </script>';
    }

    // SECTION 1.3: HTML Form Output
    // ----------------------------
    ob_start(); ?>
    <style>
        /* Glassmorphic form container */
        .form-box {
            width:50%;
            margin:30px auto;
            font-family:Inter, sans-serif;
            line-height:1.65;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 25px rgba(0,0,0,0.1);
            padding: 25px 30px;
        }

        .form-box h3 { 
            font-weight:800; 
            font-size:1.6rem; 
            margin-bottom:20px; 
            line-height:1.2; 
            text-align:center;
            color: #000;
            background: linear-gradient(135deg, #FF4B2B, #7D3FFF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .form-box label { 
            font-weight:600; 
            font-size:0.95rem; 
            margin-bottom:6px; 
            display:block; 
            color: #000;
        }

        .form-box select,
        .form-box input[type="text"],
        .form-box input[type="email"],
        .form-box input[type="file"] {
            width:100%; 
            min-height:48px; 
            padding:0 12px; 
            margin-bottom:18px;
            border:1px solid rgba(255,255,255,0.3); 
            border-radius:8px; 
            font-size:1rem; 
            font-weight:400; 
            line-height:1.5; 
            box-sizing:border-box;
            background: rgba(255,255,255,0.4);
            color: #000;
        }

        .form-box input[readonly] {
            background-color: rgba(255,255,255,0.4);
            border-color: rgba(255,255,255,0.2);
            color: #000;
            cursor: not-allowed;
        }

        .form-box .file-upload {
            display:block; 
            width:100%; 
            min-height:48px; 
            padding:14px; 
            margin-bottom:18px;
            border:2px dashed #F07bb1; 
            border-radius:8px; 
            text-align:center; 
            cursor:pointer; 
            color:#000;
            font-weight:500; 
            font-size:0.95rem; 
            line-height:1.4; 
            display:flex; 
            align-items:center; 
            justify-content:center;
            transition:all 0.25s ease;
            background: rgba(255,255,255,0.05);
        }

        .form-box .file-upload:hover { 
            border-color:#7d3fff; 
            background: rgba(255,255,255,0.1); 
        }

        .form-box input[type="file"] {
            display:none;
        }

        .form-box input[type="submit"] {
            width:100%; 
            min-height:48px; 
            border:none; 
            border-radius:40px;
            background:linear-gradient(135deg,#ff4b2b,#7d3fff);
            color:#fff; 
            padding:0 16px; 
            font-weight:600; 
            font-size:1rem; 
            cursor:pointer; 
            transition: all 0.3s ease; 
            transform: translateY(0); 
            box-shadow: 0 4px 10px rgba(125,63,255,0.4);
        }

        .form-box input[type="submit"]:hover {
            transform: translateY(-2px); 
            box-shadow: 0 4px 15px #F07bb1;
            color: #F07bb1 !important;
        }
    </style>

    <div class="form-box">
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('event_registration', 'event_registration_nonce'); ?>
            <h3>Register for <?php echo esc_html($event_title); ?></h3>

            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" required value="<?php echo esc_attr($first_name); ?>" readonly>

            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" required value="<?php echo esc_attr($last_name); ?>" readonly>

            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required value="<?php echo esc_attr($email); ?>" readonly>

            <label for="contact_number">Contact Number</label>
            <input type="text" id="contact_number" name="contact_number" required value="<?php echo esc_attr($contact); ?>" readonly>

            <label for="ticket_id">Ticket Type</label>
            <select id="ticket_id" name="ticket_id" required>
                <?php foreach ($ticket_options as $id => $opt): ?>
                    <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($opt); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="payment_method_id">Payment Method</label>
            <select id="payment_method_id" name="payment_method_id" required>
                <?php foreach ($payment_options as $id => $name): ?>
                    <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($name); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="proof_of_payment">Proof of Payment</label>
            <label class="file-upload" for="proof_of_payment">📤 Click or drag file here</label>
            <input type="file" id="proof_of_payment" name="proof_of_payment" accept="image/*" required>

            <input type="submit" value="Submit">
        </form>
    </div>

    <script>
        const fileInput = document.getElementById('proof_of_payment');
        const uploadLabel = document.querySelector('.file-upload');
        fileInput.addEventListener('change', function(){
            uploadLabel.textContent = this.files.length ? this.files[0].name : '📤 Click or drag file here';
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('form-event-registration', 'event_registration');