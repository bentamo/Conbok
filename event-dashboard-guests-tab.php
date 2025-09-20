<?php
/**
 * Shortcode and AJAX Handler for Event Guest Management
 *
 * This file contains two main components:
 * 1. `conbook_event_dashboard_guests_tab_shortcode`: A shortcode that displays a list of
 * guests for a specific event in a table format, allowing for status updates.
 * 2. `conbook_update_guest_status`: An AJAX handler that processes the status
 * updates for individual guests.
 */

/* ==============================================
 * SECTION 1: SHORTCODE FOR GUEST TABLE DISPLAY
 * ============================================== */

/**
 * Generates the HTML for the event dashboard guests tab.
 *
 * This function retrieves all guests associated with a specific event and displays
 * them in a table. Each guest row includes a dropdown to change their status
 * (e.g., Pending, Checked In). The function uses an event slug from the URL
 * to identify the correct event.
 *
 * @usage [event-dashboard-guests-tab]
 * @return string The HTML markup for the guest table. Returns an empty string if no
 * event slug is found or the event doesn't exist.
 */
function conbook_event_dashboard_guests_tab_shortcode() {
    global $wpdb;

    // Get the event slug from the URL
    /**
     * @var string|null The slug of the event from the URL query var 'event_slug'.
     * The value is sanitized to ensure security.
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
     * @var int The post ID of the event.
     */
    $post_id = $event->ID;

    /**
     * @var string The name of the custom database table for guests.
     */
    $table_guests = $wpdb->prefix . 'event_guests';
    /**
     * @var string The name of the custom database table for registrations.
     */
    $table_reg = $wpdb->prefix . 'event_registrations';

    // Get all guests linked to registrations for this event
    /**
     * @var array An array of guest data objects from the database. A JOIN operation
     * is used to link guests to the event via the registration table.
     */
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT g.* FROM $table_guests g
         INNER JOIN $table_reg r ON g.registration_id = r.id
         WHERE r.event_id = %d
         ORDER BY g.created_at DESC",
        $post_id
    ));

    // Nonce for AJAX
    /**
     * @var string A unique security nonce created for the AJAX request. This
     * is crucial for preventing CSRF attacks.
     */
    $ajax_nonce = wp_create_nonce('update_guest_status_nonce');

    // Start output buffering to capture the HTML
    ob_start(); ?>
    <div class="event-dashboard-guests-tab">
        <table class="event-guests-table" border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Contact Number</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($results) : ?>
                    <?php foreach ($results as $row) :
                        /**
                         * @var array An array of available status options for a guest.
                         */
                        $status_options = ['Pending','Checked In','No Show','Cancelled'];
                        /**
                         * @var string The current status of the guest, defaulting to 'Pending' if not set.
                         */
                        $current_status = $row->status ? $row->status : 'Pending';
                        ?>
                        <tr>
                            <td><?php echo esc_html($row->name); ?></td>
                            <td><?php echo esc_html($row->email); ?></td>
                            <td><?php echo esc_html($row->contact_number); ?></td>
                            <td>
                                <select class="guest-status-dropdown" data-guest-id="<?php echo esc_attr($row->id); ?>">
                                    <?php foreach ($status_options as $option) : ?>
                                        <option value="<?php echo esc_attr($option); ?>" <?php selected($current_status, $option); ?>>
                                            <?php echo esc_html($option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4" style="text-align:center;">No guests found for this event.</td>
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
         * Guest Status Update Script
         *
         * This script listens for changes on the guest status dropdown. When a user
         * selects a new status, it sends an asynchronous AJAX request to the server
         * to update the guest's status in the database.
         */
        jQuery(document).ready(function($) {
            $('.guest-status-dropdown').on('change', function() {
                var select = $(this);
                var guest_id = select.data('guest-id');
                var new_status = select.val();

                $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>',
                    type: 'POST',
                    data: {
                        action: 'update_guest_status',
                        guest_id: guest_id,
                        new_status: new_status,
                        security: '<?php echo $ajax_nonce; ?>'
                    },
                    success: function(response) {
                        alert(response.data);
                    },
                    error: function() {
                        alert('Error updating guest status.');
                    }
                });
            });
        });
    </script>
    <?php

    // Return the captured HTML
    return ob_get_clean();
}
// Hook the function to the shortcode tag
add_shortcode('event-dashboard-guests-tab', 'conbook_event_dashboard_guests_tab_shortcode');

/* ==============================================
 * SECTION 3: AJAX HANDLER
 * ============================================== */

/**
 * Handles the AJAX request to update a guest's status.
 *
 * This function is called via the `wp_ajax` hook. It performs a security check
 * using a nonce, validates the input data (guest ID and new status), and then
 * updates the `status` column in the `event_guests` custom table.
 *
 * @return void Sends a JSON response with success or error message and then exits.
 */
function conbook_update_guest_status() {
    global $wpdb;

    // Check for a valid nonce to prevent CSRF attacks.
    check_ajax_referer('update_guest_status_nonce', 'security');

    /**
     * @var int The ID of the guest to be updated. The value is sanitized to an integer.
     */
    $guest_id = isset($_POST['guest_id']) ? intval($_POST['guest_id']) : 0;
    /**
     * @var string The new status to be set for the guest. Sanitized to a text field.
     */
    $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : 'Pending';

    /**
     * @var string The name of the custom database table.
     */
    $table_guests = $wpdb->prefix . 'event_guests';

    // Proceed with the database update only if the guest ID is valid.
    if ($guest_id > 0) {
        /**
         * @var int|false The number of rows updated on success, or `false` on failure.
         */
        $updated = $wpdb->update(
            $table_guests,
            ['status' => $new_status], // Data to update
            ['id' => $guest_id],       // WHERE clause
            ['%s'],                    // Data format: string
            ['%d']                     // WHERE format: integer
        );

        if ($updated !== false) {
            wp_send_json_success('Guest status updated successfully.');
        } else {
            wp_send_json_error('No changes made.');
        }
    }

    // Send a JSON error response if the provided guest ID was invalid.
    wp_send_json_error('Invalid guest ID.');
}
// Hook the function to the `wp_ajax` action for logged-in users only.
add_action('wp_ajax_update_guest_status', 'conbook_update_guest_status');