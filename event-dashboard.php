<?php
// -------------------------------
// Shortcode: [event-dashboard]
// Displays a custom event dashboard with centered clickable tabs (no background)
// Restricted so only the event's author can see it
// -------------------------------
function conbook_event_dashboard_shortcode($atts) {
    global $post;

    // Bail if not inside a post context
    if ( ! $post ) {
        return '';
    }

    // Get current user ID
    $current_user_id = get_current_user_id();

    // Get post author ID
    $author_id = (int) $post->post_author;

    // Restrict: only author can see the dashboard
    if ( $current_user_id !== $author_id ) {
        return 'You are not allowed to view this dashboard.';
    }

    // Start output buffering
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
                <div class="tab-pane" id="guests" style="display:none;">Guests content goes here.</div>
                <div class="tab-pane" id="registrations" style="display:none;">
                    <?php echo do_shortcode('[event-dashboard-registration-tab]'); ?>
                </div>
                <div class="tab-pane" id="insights" style="display:none;">Insights content goes here.</div>
            </div>

            <!-- Simple JS for Tab Switching -->
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const tabs = document.querySelectorAll('.dashboard-tabs .tab');
                const panes = document.querySelectorAll('.tab-pane');

                tabs.forEach(tab => {
                    tab.addEventListener('click', function() {
                        // Remove active state from all tabs
                        tabs.forEach(t => {
                            t.classList.remove('active');
                            t.style.color = '#333';
                            t.style.fontWeight = 'normal';
                        });
                        // Hide all panes
                        panes.forEach(p => p.style.display = 'none');

                        // Activate clicked tab
                        this.classList.add('active');
                        this.style.color = '#0073aa';
                        this.style.fontWeight = 'bold';

                        // Show corresponding pane
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
