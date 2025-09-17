<?php
function conbook_event_dashboard_registrations_tab_shortcode() {
    global $wpdb;

    // Get the event slug from the URL
    $slug = sanitize_text_field(get_query_var('event_slug', ''));
    if (!$slug) return '';

    // Get the event by slug
    $event = get_page_by_path($slug, OBJECT, 'event');
    if (!$event) return '';

    $post_id = $event->ID;

    $table_reg = $wpdb->prefix . 'event_registrations';
    $table_tickets = $wpdb->prefix . 'event_tickets';

    // Only fetch registrations for this specific event
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_reg WHERE event_id = %d ORDER BY created_at DESC",
        $post_id
    ));

    // Nonce for AJAX security
    $ajax_nonce = wp_create_nonce('update_registration_status_nonce');

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
                        $user_info  = get_userdata($row->user_id);
                        $name       = $user_info ? $user_info->display_name : 'N/A';
                        $email      = $user_info ? $user_info->user_email   : 'N/A';

                        // Ticket info
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

                        $proof_url  = wp_get_attachment_url($row->proof_id);

                        // Status options
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

    <script type="text/javascript">
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

// AJAX handler
function conbook_update_registration_status() {
    global $wpdb;

    check_ajax_referer('update_registration_status_nonce', 'security');

    $registration_id = isset($_POST['registration_id']) ? intval($_POST['registration_id']) : 0;
    $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : 'Pending';

    $table_reg = $wpdb->prefix . 'event_registrations';
    $table_guests = $wpdb->prefix . 'event_guests';

    if ($registration_id > 0) {
        // Update registration status
        $updated = $wpdb->update(
            $table_reg,
            ['status' => $new_status],
            ['id' => $registration_id],
            ['%s'],
            ['%d']
        );

        if ($updated !== false) {

            // If status is "Accepted", add a guest entry if it doesn't already exist
            if (strtolower($new_status) === 'accepted') {

                $existing_guest = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_guests WHERE registration_id = %d",
                    $registration_id
                ));

                if (!$existing_guest) {
                    // Get user info from registration
                    $registration = $wpdb->get_row($wpdb->prepare(
                        "SELECT user_id FROM $table_reg WHERE id = %d",
                        $registration_id
                    ));

                    if ($registration) {
                        $user_info = get_userdata($registration->user_id);
                        if ($user_info) {
                            // Get contact number from user meta
                            $contact_number = get_user_meta($registration->user_id, 'contact-number-textbox', true);

                            $wpdb->insert(
                                $table_guests,
                                [
                                    'registration_id' => $registration_id,
                                    'name' => $user_info->display_name,
                                    'email' => $user_info->user_email,
                                    'contact_number' => $contact_number ? $contact_number : '',
                                    'token' => wp_generate_uuid4(),
                                    'status' => 'Pending',
                                    'created_at' => current_time('mysql')
                                ],
                                ['%d', '%s', '%s', '%s', '%s', '%s']
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

    wp_send_json_error('Invalid registration ID.');
}
add_action('wp_ajax_update_registration_status', 'conbook_update_registration_status');
