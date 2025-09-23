<?php
/**
 * Main Shortcode Handler for the Event Guest Dashboard Tab.
 *
 * Registers the `event-dashboard-guests-tab` shortcode. This function is responsible for
 * querying and displaying a list of all registered guests for a specific event. It fetches
 * guest information from the database and renders an HTML table. The table includes fields
 * for the guest's name, email, contact number, and a dropdown to update their status
 * (e.g., Checked In, No Show). It also enqueues an inline script to handle AJAX updates.
 *
 * Usage: `[event-dashboard-guests-tab]`
 *
 * @return string The complete HTML output of the guests table.
 */
function conbook_event_dashboard_guests_tab_shortcode() {
    global $wpdb;

    /**
     * Section: Event Data Retrieval
     *
     * Fetches the event slug from the URL query variable. It then uses the slug to
     * retrieve the corresponding event post object. The function returns early if
     * no valid slug or event is found, preventing errors.
     */
    $slug = sanitize_text_field(get_query_var('event_slug', ''));
    if (!$slug) return '';

    // Get the event by slug
    $event = get_page_by_path($slug, OBJECT, 'event');
    if (!$event) return '';

    $post_id = $event->ID;

    /**
     * Section: Database Query
     *
     * This section joins the `event_guests` and `event_registrations` tables to fetch
     * a comprehensive list of all guests associated with the current event. The results
     * are ordered by creation date to show the most recent registrations first.
     */
    $table_guests = $wpdb->prefix . 'event_guests';
    $table_reg = $wpdb->prefix . 'event_registrations';

    // Get all guests linked to registrations for this event
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT g.* 
         FROM $table_guests g
         INNER JOIN $table_reg r ON g.registration_id = r.id
         WHERE r.event_id = %d
         ORDER BY g.created_at DESC",
        $post_id
    ));

    /**
     * Section: Security Nonce
     *
     * Creates a unique security nonce for AJAX requests. This is a crucial security measure
     * to protect against CSRF (Cross-Site Request Forgery) attacks.
     */
    $ajax_nonce = wp_create_nonce('update_guest_status_nonce');

    /**
     * Section: HTML Output Generation
     *
     * Starts an output buffer to capture the generated HTML. This section renders a table
     * that displays the guest list. It iterates through the database results and populates
     * each row with guest data, including a dynamic dropdown for status updates.
     */
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
                        $status_options = ['Pending','Checked In','No Show','Cancelled'];
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

    <!-- Section: JavaScript for AJAX Updates

        This inline script uses jQuery to listen for changes on the guest status dropdown.
        When a change occurs, it sends an AJAX request to the server with the guest's ID
        and the new status. This allows for real-time updates without a page reload. -->
    <script type="text/javascript">
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

    return ob_get_clean();
}
add_shortcode('event-dashboard-guests-tab', 'conbook_event_dashboard_guests_tab_shortcode');


/**
 * Section: JavaScript for AJAX Updates
 *
 * This inline script uses jQuery to listen for changes on the guest status dropdown.
 * When a change occurs, it sends an AJAX request to the server with the guest's ID
 * and the new status. This allows for real-time updates without a page reload.
 */
function conbook_update_guest_status() {
    global $wpdb;

    check_ajax_referer('update_guest_status_nonce', 'security');

    $guest_id = isset($_POST['guest_id']) ? intval($_POST['guest_id']) : 0;
    $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : 'Pending';

    $table_guests = $wpdb->prefix . 'event_guests';

    if ($guest_id > 0) {
        $updated = $wpdb->update(
            $table_guests,
            ['status' => $new_status],
            ['id' => $guest_id],
            ['%s'],
            ['%d']
        );

        if ($updated !== false) {
            wp_send_json_success('Guest status updated successfully.');
        } else {
            wp_send_json_error('No changes made.');
        }
    }

    wp_send_json_error('Invalid guest ID.');
}
add_action('wp_ajax_update_guest_status', 'conbook_update_guest_status');
