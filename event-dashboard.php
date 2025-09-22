<?php
/**
 * Shortcode Handler for the Main Event Dashboard.
 *
 * Registers the `event-dashboard` shortcode. This is the main controller for the event
 * management interface. It's responsible for displaying a tabbed dashboard that
 * includes an overview, guest list, registrations, and insights. The function first
 * verifies that the current user is the author of the event, restricting access to
 * unauthorized users.
 *
 * The shortcode renders the HTML structure for the tabs and content panes, and then
 * dynamically loads content into each tab by calling other specialized shortcodes
 * (e.g., `[event-dashboard-overview-tab]`). An inline JavaScript snippet handles the
 * front-end logic for switching between the different tabs.
 *
 * Usage: `[event-dashboard]`
 *
 * @param array $atts Shortcode attributes (not currently used).
 * @return string The complete HTML output for the event dashboard.
 */
function conbook_event_dashboard_shortcode($atts) {

    /**
     * Section: Event Data Retrieval and Access Control
     *
     * Fetches the event slug from the URL and retrieves the event post object. It then
     * performs a crucial security check to ensure that only the event's author can view
     * the dashboard, returning an error message for all other users.
     */
    $slug = sanitize_text_field(get_query_var('event_slug', ''));
    if (!$slug) return '';

    // Get the event by slug
    $event = get_page_by_path($slug, OBJECT, 'event');
    if (!$event) return '';

    $post_id = $event->ID;

    // Get current user ID
    $current_user_id = get_current_user_id();

    // Get post author ID
    $author_id = (int) $event->post_author;

    // Restrict: only author can see the dashboard
    if ($current_user_id !== $author_id) {
        return 'You are not allowed to view this dashboard.';
    }

    /**
     * Section: HTML Output Generation
     *
     * Starts an output buffer to capture all subsequent HTML. This section defines the
     * main structure of the dashboard, including a container card, the tab navigation
     * (`<ul class="dashboard-tabs">`), and the content areas for each tab (`<div class="tab-pane">`).
     * It uses `do_shortcode()` to embed the content from the specific tab shortcodes.
     */
    ob_start();
    ?>
    <div class="event-dashboard" style="padding:20px;">
        <div class="dashboard-card" style="
            background-color: #fff; 
            padding: 20px; 
            border-radius: 30px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        ">
            <!-- Dashboard Tabs -->
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

            <!-- Tab Content Area -->
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

            <!-- Section: JavaScript for Tab Switching
            
             A simple inline JavaScript snippet that handles the client-side interactivity of
             the tabs. When a user clicks a tab, it toggles the "active" class and displays
             the corresponding content pane while hiding all others. -->
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const tabs = document.querySelectorAll('.dashboard-tabs .tab');
                const panes = document.querySelectorAll('.tab-pane');

                tabs.forEach(tab => {
                    tab.addEventListener('click', function() {
                        tabs.forEach(t => {
                            t.classList.remove('active');
                            t.style.color = '#333';
                            t.style.fontWeight = 'normal';
                        });
                        panes.forEach(p => p.style.display = 'none');

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
    return ob_get_clean();
}
add_shortcode('event-dashboard', 'conbook_event_dashboard_shortcode');
