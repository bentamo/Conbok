<?php
/**
 * Event Dashboard Shortcode
 *
 * This file contains the primary shortcode `[event-dashboard]` which displays a
 * customized dashboard interface for a specific event. The dashboard is
 * restricted to only the event's original author and features a tabbed
 * navigation system to switch between different views, such as Overview, Guests,
 * and Registrations.
 *
 * @package ConBook
 * @subpackage Shortcodes
 */

/* ==============================================
 * SECTION 1: MAIN DASHBOARD SHORTCODE
 * ============================================== */

/**
 * Generates the main event dashboard interface.
 *
 * The shortcode dynamically builds a tabbed dashboard that displays different
 * content based on the selected tab. It acts as a container for other shortcodes
 * (`[event-dashboard-overview-tab]`, etc.) and handles the frontend logic for
 * tab switching using simple JavaScript. Crucially, it includes an authorization
 * check to ensure only the event's author can access the dashboard.
 *
 * @since 1.0.0
 *
 * @param array $atts {
 * Optional. An array of shortcode attributes. This shortcode does not
 * currently use any attributes, but the parameter is included for
 * best practice and future compatibility.
 * }
 * @return string The complete HTML output for the event dashboard.
 * Returns an error message string if the user is not the event's author,
 * or an empty string if the event slug is invalid.
 */
function conbook_event_dashboard_shortcode($atts) {

    /* ==============================================
     * SECTION 1.1: ACCESS CONTROL AND DATA RETRIEVAL
     * ============================================== */

    // Get the event slug from the URL
    /**
     * @var string|null The slug of the event post, sanitized from the URL.
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
     * @var int The post ID of the event, used for authorization checks.
     */
    $post_id = $event->ID;

    // Get current user ID
    /**
     * @var int The ID of the currently logged-in user.
     */
    $current_user_id = get_current_user_id();

    // Get post author ID
    /**
     * @var int The ID of the event's author.
     */
    $author_id = (int) $event->post_author;

    // Restrict access: only the event's author can see the dashboard.
    if ($current_user_id !== $author_id) {
        return 'You are not allowed to view this dashboard.';
    }

    /* ==============================================
     * SECTION 1.2: HTML MARKUP AND INLINE STYLES
     * ============================================== */

    // Start output buffering to capture all HTML output
    ob_start();
    ?>
    <div class="event-dashboard" style="padding:20px;">
        <div class="dashboard-card" style="
            background-color: #fff; 
            padding: 20px; 
            border-radius: 30px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        ">
            <ul class="dashboard-tabs" style="
                list-style:none; 
                padding:0; 
                display:flex; 
                justify-content:center; 
                gap:15px; 
                cursor:pointer;
            ">
                <li class="tab active" data-tab="overview" style="padding:10px 15px; border-radius:20px; color:#0073aa; font-weight:bold;">Overview</li>
                <li class="tab" data-tab="guests" style="padding:10px 15px; border-radius:20px; color:#333;">Guests</li>
                <li class="tab" data-tab="registrations" style="padding:10px 15px; border-radius:20px; color:#333;">Registrations</li>
                <li class="tab" data-tab="insights" style="padding:10px 15px; border-radius:20px; color:#333;">Insights</li>
            </ul>

            <div class="dashboard-tab-content" style="padding-top:20px;">
                <div class="tab-pane" id="overview">
                    <?php echo do_shortcode('[event-dashboard-overview-tab]'); ?>
                </div>
                <div class="tab-pane" id="guests" style="display:none;">
                    <?php echo do_shortcode('[event-dashboard-guests-tab]'); ?>
                </div>
                <div class="tab-pane" id="registrations" style="display:none;">
                    <?php echo do_shortcode('[event-dashboard-registrations-tab]'); ?>
                </div>
                <div class="tab-pane" id="insights" style="display:none;">Insights content goes here.</div>
            </div>

            /* ==============================================
             * SECTION 1.3: INLINE JAVASCRIPT FOR TAB SWITCHING
             * ============================================== */
            <script>
            /**
             * Handles the interactive switching between dashboard tabs.
             *
             * This script adds a click event listener to each tab. When a tab is
             * clicked, it updates the visual appearance of the tabs (bolding the
             * active one and changing its color) and shows the corresponding
             * content pane while hiding all others.
             */
            document.addEventListener('DOMContentLoaded', function() {
                const tabs = document.querySelectorAll('.dashboard-tabs .tab');
                const panes = document.querySelectorAll('.tab-pane');

                tabs.forEach(tab => {
                    tab.addEventListener('click', function() {
                        // Deactivate all tabs and hide all content panes
                        tabs.forEach(t => {
                            t.classList.remove('active');
                            t.style.color = '#333';
                            t.style.fontWeight = 'normal';
                        });
                        panes.forEach(p => p.style.display = 'none');

                        // Activate the clicked tab and show its corresponding pane
                        this.classList.add('active');
                        this.style.color = '#0073aa';
                        this.style.fontWeight = 'bold';

                        const tabId = this.getAttribute('data-tab');
                        document.getElementById(tabId).style.display = 'block';
                    });
                });
            });
            </script>
        </div>
    </div>
    <?php
    // Return the final HTML output
    return ob_get_clean();
}
add_shortcode('event-dashboard', 'conbook_event_dashboard_shortcode');