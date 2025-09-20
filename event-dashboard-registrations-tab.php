<?php
/**
 * Event Dashboard Registrations Tab
 *
 * This file contains the shortcode to display all event registrations and an
 * associated AJAX handler for updating registration statuses. It is designed
 * for use on the event organizer's dashboard.
 */

/* ==============================================
 * SECTION 1: SHORTCODE FOR REGISTRATION TABLE DISPLAY
 * ============================================== */

/**
 * Displays a table of all registrations for a specific event.
 *
 * This shortcode retrieves registration data from the custom 'event_registrations'
 * table based on the event slug found in the URL. It also fetches associated
 * user and ticket information to present a comprehensive view of all attendees.
 * Each row includes a dropdown to change the registration's status and a link
 * to view the proof of payment.
 *
 * @usage [event-dashboard-registrations-tab]
 * @return string The HTML markup for the registrations table. Returns an empty
 * string if the event slug is missing or invalid.
 */
function conbook_event_dashboard_registrations_tab_shortcode() {
    global $wpdb;

    // Get the event slug from the URL
    /**
     * @var string|null The slug of the event, sanitized for security.
     */
    $slug = sanitize_text_field(get_query_var('event_slug', ''));
    if (!$slug) return '';

    // Get the event by slug
    /**
     * @var WP_Post|null The WordPress post object for the 'event' custom post type.
     */
    $event = get_page_by_path($slug, OBJECT, 'event');
    if (!$event) return '';

    /**
     * @var int The post ID of the event, used for all subsequent database queries.
     */
    $post_id = $event->ID;

    // Define custom table names
    /**
     * @var string The full name of the custom registrations table.
     */
    $table_reg = $wpdb->prefix . 'event_registrations';
    /**
     * @var string The full name of the custom tickets table.
     */
    $table_tickets = $wpdb->prefix . 'event_tickets';

    // Fetch all registrations for the specified event, ordered by creation date.
    /**
     * @var array An array of registration data objects from the database.
     */
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_reg WHERE event_id = %d ORDER BY created_at DESC",
        $post_id
    ));

    // Nonce for AJAX security
    /**
     * @var string A unique security token to prevent Cross-Site Request Forgery (CSRF).
     */
    $ajax_nonce = wp_create_nonce('update_registration_status_nonce');

    // Start output buffering to build the HTML
    ob_start(); ?>
    <div class="event-dashboard-registrations-tab">
        <table class="event-registrations-table" border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Ticket</th>
                    <th>Proof of Payment</th>
                    <th>Created At</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($results) : ?>
                    <?php foreach ($results as $row) :
                        // Get user info for the registrant
                        $user_info  = get_userdata($row->user_id);
                        $name       = $user_info ? $user_info->display_name : 'N/A';
                        $email      = $user_info ? $user_info->user_email : 'N/A';

                        // Fetch ticket details
                        $ticket_name  = 'N/A';
                        $ticket_price = '';
                        if ($row->ticket_id) {
                            $ticket = $wpdb->get_row($wpdb->prepare(
                                "SELECT name, price FROM $table_tickets WHERE id = %d",
                                $row->ticket_id
                            ));
                            if ($ticket) {
                                $ticket_name  = $ticket->name;
                                $ticket_price = number_format($ticket->price, 2);
                            }
                        }

                        // Get the proof of payment URL
                        $proof_url  = wp_get_attachment_url($row->proof_id);

                        // Set up status options for the dropdown
                        $status_options = ['pending', 'accepted', 'declined'];
                        $current_status = $row->status ? strtolower($row->status) : 'pending';
                        ?>
                        <tr>
                            <td><?php echo esc_html($name); ?></td>
                            <td><?php echo esc_html($email); ?></td>
                            <td>
                                <?php echo esc_html($ticket_name); ?>
                                <?php if ($ticket_price !== '') : ?>
                                    - Php <?php echo esc_html($ticket_price); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($proof_url) : ?>
                                    <a href="<?php echo esc_url($proof_url); ?>" target="_blank">View Proof</a>
                                <?php else : ?>
                                    No proof
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($row->created_at); ?></td>
                            <td>
                                <select class="registration-status-dropdown"
                                        data-registration-id="<?php echo esc_attr($row->id); ?>">
                                    <?php foreach ($status_options as $option) : ?>
                                        <option value="<?php echo esc_attr($option); ?>" <?php selected($current_status, $option); ?>>
                                            <?php echo ucfirst($option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">No registrations found for this event.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- /* ==============================================
     * SECTION 2: INLINE JAVASCRIPT FOR AJAX
     * ============================================== */ -->
    <script type="text/javascript">
        /**
         * Registration Status Update Script
         *
         * This script listens for 'change' events on the status dropdowns. When a
         * new status is selected, it sends an AJAX request to the server to update
         * the registration's status in the database.
         */
        jQuery(document).ready(function($) {
            $('.registration-status-dropdown').on('change', function() {
                var select = $(this);
                var registration_id = select.data('registration-id');
                var new_status = select.val();

                $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>',
                    type: 'POST',
                    data: {
                        action: 'update_registration_status',
                        registration_id: registration_id,
                        new_status: new_status,
                        security: '<?php echo $ajax_nonce; ?>'
                    },
                    success: function(response) {
                        alert(response.data);
                    },
                    error: function() {
                        alert('Error updating status.');
                    }
                });
            });
        });
    </script>
    <?php

    return ob_get_clean();
}
add_shortcode('event-dashboard-registrations-tab', 'conbook_event_dashboard_registrations_tab_shortcode');

/* ==============================================
 * SECTION 3: AJAX HANDLER FOR STATUS UPDATES
 * ============================================== */

/**
 * Handles the AJAX request to update a registration's status.
 *
 * This function processes the status change for an event registration. It verifies
 * the request with a nonce, updates the `event_registrations` table, and if the
 * status is changed to 'accepted', it automatically creates a corresponding entry
 * in the `event_guests` table for check-in purposes, provided a guest entry
 * does not already exist.
 *
 * @return void Sends a JSON response to the browser and terminates script execution.
 */
function conbook_update_registration_status() {
    global $wpdb;

    // Security check: verify the nonce to ensure the request is valid.
    check_ajax_referer('update_registration_status_nonce', 'security');

    /**
     * @var int The ID of the registration to update, sanitized to an integer.
     */
    $registration_id = isset($_POST['registration_id']) ? intval($_POST['registration_id']) : 0;
    /**
     * @var string The new status to be set, sanitized to a text field.
     */
    $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : 'Pending';

    /**
     * @var string The name of the custom registrations table.
     */
    $table_reg = $wpdb->prefix . 'event_registrations';
    /**
     * @var string The name of the custom guests table.
     */
    $table_guests = $wpdb->prefix . 'event_guests';

    if ($registration_id > 0) {
        // Update registration status in the database.
        $updated = $wpdb->update(
            $table_reg,
            ['status' => $new_status],
            ['id' => $registration_id],
            ['%s'],
            ['%d']
        );

        if ($updated !== false) {
            // Conditional logic: If the status is "Accepted", create a guest entry.
            if (strtolower($new_status) === 'accepted') {
                // Check if a guest entry already exists for this registration.
                $existing_guest = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_guests WHERE registration_id = %d",
                    $registration_id
                ));

                if (!$existing_guest) {
                    // Fetch user details from the registration table.
                    $registration = $wpdb->get_row($wpdb->prepare(
                        "SELECT user_id FROM $table_reg WHERE id = %d",
                        $registration_id
                    ));

                    if ($registration) {
                        // Fetch the full user object to get display name, email, and contact info.
                        $user_info = get_userdata($registration->user_id);
                        if ($user_info) {
                            $contact_number = get_user_meta($registration->user_id, 'contact-number-textbox', true);

                            // Insert the new guest entry.
                            $wpdb->insert(
                                $table_guests,
                                [
                                    'registration_id' => $registration_id,
                                    'name' => $user_info->display_name,
                                    'email' => $user_info->user_email,
                                    'contact_number' => $contact_number ? $contact_number : '',
                                    'token' => wp_generate_uuid4(), // Generate a unique token for the guest.
                                    'status' => 'Pending',
                                    'created_at' => current_time('mysql')
                                ],
                                ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
                            );
                        }
                    }
                }
            }

            wp_send_json_success('Status updated successfully.');
        } else {
            wp_send_json_error('No changes made.');
        }
    }

    // This part of the code is reached only if the registration ID is invalid.
    wp_send_json_error('Invalid registration ID.');
}
add_action('wp_ajax_update_registration_status', 'conbook_update_registration_status');