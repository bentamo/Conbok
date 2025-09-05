<?php

/**
 * Event Registration Form Shortcode
 *
 * This shortcode displays an event registration form and processes
 * the form submission, including a file upload for proof of payment.
 *
 * Usage: [event_registration]
 *
 * @return string The HTML for the form or a success/error message.
 */
function event_registration_shortcode() {
    // Check if the form has been submitted
    if (isset($_POST['submit_registration'])) {
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $payment_method = sanitize_text_field($_POST['payment_method']);

        // Handle file upload
        $proof_of_payment = '';
        if (isset($_FILES['proof_of_payment']) && !empty($_FILES['proof_of_payment']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            $upload_overrides = array('test_form' => false);
            $proof_of_payment_file = wp_handle_upload($_FILES['proof_of_payment'], $upload_overrides);

            if (isset($proof_of_payment_file['url'])) {
                $proof_of_payment = $proof_of_payment_file['url'];
            }
        }

        // Validate form data
        if (!empty($first_name) && !empty($last_name) && is_email($email) && !empty($payment_method) && !empty($proof_of_payment)) {
            // All data is valid. Now you can save it or send a notification.
            // Example: Sending an email notification
            $to = 'youremail@example.com';
            $subject = 'New Event Registration from ' . $first_name . ' ' . $last_name;
            $message = "A new event registration has been submitted.\n\n";
            $message .= "First Name: " . $first_name . "\n";
            $message .= "Last Name: " . $last_name . "\n";
            $message .= "Email: " . $email . "\n";
            $message .= "Payment Method: " . $payment_method . "\n";
            $message .= "Proof of Payment: " . $proof_of_payment . "\n";
            $headers = 'From: noreply@' . parse_url(site_url(), PHP_URL_HOST) . "\r\n";
            
            wp_mail($to, $subject, $message, $headers);
            
            // Success message
            return '<div class="registration-success">Thank you! Your registration has been submitted.</div>';
        } else {
            // Error message
            return '<div class="registration-error">Please fill out all required fields and try again.</div>';
        }
    }

    // Display the form
    ob_start(); // Start output buffering
    ?>
    <form action="" method="post" enctype="multipart/form-data">
        <p>
            <label for="first_name">First Name:</label>
            <input type="text" name="first_name" id="first_name" required>
        </p><br>
        <p>
            <label for="last_name">Last Name:</label>
            <input type="text" name="last_name" id="last_name" required>
        </p><br>
        <p>
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required>
        </p><br>
        <p>
            <label for="payment_method">Payment Method:</label>
            <select name="payment_method" id="payment_method" required>
                <option value="">-- Select a Payment Method --</option>
                <option value="paypal">PayPal</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="credit_card">Credit Card</option>
            </select>
        </p><br>
        <p>
            <label for="proof_of_payment">Proof of Payment:</label>
            <input type="file" name="proof_of_payment" id="proof_of_payment" required>
        </p><br>
        <p>
            <input type="submit" name="submit_registration" value="Register">
        </p>
    </form>
    <?php
    $form = ob_get_clean(); // Get the buffered content
    return $form;
}
add_shortcode('event_registration', 'event_registration_shortcode');