<?php
/**
 * User Dashboard Shortcode.
 *
 * This file contains the shortcode to display a comprehensive user dashboard.
 * The dashboard is divided into two main sections: events created by the user
 * and events the user has registered for. Both sections feature a tabbed
 * interface to switch between "Upcoming" and "Past" events.
 *
 * The shortcode dynamically loads the content for each tab by calling other,
 * more specific shortcodes. It also includes styling and a simple JavaScript
 * function to manage the tab-switching functionality, enhancing the user experience.
 *
 * @package ConBook
 * @subpackage Shortcodes
 * @since 1.0.0
 */

/* ==============================================
 * SECTION 1: USER DASHBOARD SHORTCODE
 * ============================================== */

/**
 * Renders the main user dashboard with events and registrations.
 *
 * This shortcode acts as a container for displaying a user's activity. It checks
 * if the user is logged in, then renders a two-section dashboard. The first section
 * shows events the user has created, while the second shows events they have
 * registered for. The content for these sections is dynamically loaded using
 * nested shortcodes, ensuring a clean and modular structure.
 *
 * The function also includes inline CSS for styling the dashboard's layout,
 * a "Create Event" button, and a dynamic tab system. It uses `ob_start()`
 * to capture the HTML output, which is the standard method for shortcode
 * rendering in WordPress.
 *
 * @since 1.0.0
 *
 * @return string The HTML output for the user dashboard.
 */
add_shortcode('user-dashboard', function() {
    // Check if the user is logged in; if not, return a login prompt.
    if (!is_user_logged_in()) {
        return '<p>Please log in to view your dashboard.</p>';
    }

    // Start output buffering to capture the HTML content.
    ob_start();
    ?>
    <div class="dashboard-container">

        <section class="dashboard-section">
            <div class="dashboard-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="font-size:28px; font-weight:bold; background: linear-gradient(135deg,#ff4b2b,#7d3fff); -webkit-background-clip: text; color: transparent;">My Events</h2>
                <a href="<?php echo esc_url(site_url('/create-event')); ?>" class="create-event-btn">Create Event +</a>
            </div>

            <div class="tabs">
                <button class="tab-btn active" data-tab="upcoming">Upcoming</button>
                <button class="tab-btn" data-tab="past">Past</button>
                <span class="tab-indicator"></span>
            </div>

            <div class="tab-content">
                <div class="tab-panel active" id="upcoming">
                    <?php echo do_shortcode('[user-upcoming-events]'); ?>
                </div>
                <div class="tab-panel" id="past">
                    <?php echo do_shortcode('[user-past-events]'); ?>
                </div>
            </div>
        </section>

        <section class="dashboard-section" style="margin-top:60px;">
            <h2 style="font-size:28px; font-weight:bold; background: linear-gradient(135deg,#ff4b2b,#7d3fff); -webkit-background-clip: text; color: transparent; margin-bottom:20px;">My Registrations</h2>

            <div class="tabs">
                <button class="tab-btn active" data-tab="upcoming-reg">Upcoming</button>
                <button class="tab-btn" data-tab="past-reg">Past</button>
                <span class="tab-indicator"></span>
            </div>

            <div class="tab-content">
                <div class="tab-panel active" id="upcoming-reg">
                    <?php echo do_shortcode('[user-upcoming-registrations]'); ?>
                </div>
                <div class="tab-panel" id="past-reg">
                    <?php echo do_shortcode('[user-past-registrations]'); ?>
                </div>
            </div>
        </section>

    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@500;600&display=swap');

        .dashboard-container {
            max-width:1200px;
            margin:auto;
            padding:20px;
            font-family: 'Inter', sans-serif;
        }

        /* Create Event Button */
        .create-event-btn {
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            padding: 12px 25px;
            font-size: 16px;
            border-radius: 30px;
            border: none;
            cursor: pointer;
            background: linear-gradient(135deg,#ff4b2b,#7d3fff);
            color: #fff !important;
            text-decoration: none !important;
            transition: all 0.3s ease;
            transform: translateY(0);
            box-shadow: none;
            position: relative;
        }
        .create-event-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px #F07BB1;
            color: #F07BB1 !important;
        }

        /* Tabs */
        .tabs {
            position: relative;
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }
        .tab-btn {
            background: transparent;
            color: #333;
            padding: 10px 20px;
            font-weight: 500;
            transition: transform 0.3s ease;
            cursor: pointer;
            border: none;
        }
        .tab-btn:hover {
            transform: translateY(-2px);
            background: transparent;
            color: inherit !important;
        }
        .tab-btn.active {
            color: #000;
            font-weight: bold;
        }
        .tab-btn, .tab-btn:focus, .tab-btn:active {
            outline: none !important;
            box-shadow: none !important;
            background: transparent !important;
            color: inherit !important;
        }
        .tab-indicator {
            position: absolute;
            bottom: -6px;
            height: 2px;
            background: linear-gradient(135deg,#ff4b2b,#7d3fff);
            border-radius: 2px;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .tab-content { position: relative; }
        .tab-panel {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.35s ease;
        }
        .tab-panel.active {
            opacity: 1;
            visibility: visible;
            position: relative;
        }

        /* Event Grid Wrappers */
        .user-upcoming-events-wrapper,
        .user-past-events-wrapper {
            display:flex;
            justify-content:center;
            width:100%;
        }
        .user-upcoming-events,
        .user-past-events {
            display:grid;
            grid-template-columns: repeat(2, minmax(0, 400px));
            gap: 20px;
            justify-content: center;
            max-width: 850px;
            width: 100%;
        }
        @media (max-width: 768px) {
            .user-upcoming-events,
            .user-past-events { grid-template-columns: 1fr; }
        }

        /* Glassmorphic Event Card (Standard Glass Card Rules) */
        .event-card {
            display: block;
            border-radius: 15px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.05); /* 5% opacity */
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            width: 100%;
            text-decoration: none;
            color: inherit;
            transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
            position: relative;
        }
        .event-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            background: rgba(255, 255, 255, 0.1);
        }
        .event-card, .event-card * {
            text-decoration: none !important;
            color: inherit !important;
        }

        /* Event Card Image */
        .event-card img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s ease, filter 0.3s ease;
            filter: brightness(0.95);
        }
        .event-card:hover img {
            transform: scale(1.03);
            filter: brightness(1);
        }

        /* Event Card Content */
        .event-card-content {
            padding: 15px;
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border-bottom-left-radius: 15px;
            border-bottom-right-radius: 15px;
        }
        .event-date {
            font-weight: normal;
            color: #333;
            margin-bottom: 6px;
        }
        .event-card-content strong {
            display:block;
            font-size:1.2em;
        }

        /* Base badge (for Live, etc.) */
        .event-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            z-index: 2;
            letter-spacing: 0.5px;
        }

        /* Live badge (gradient) */
        .event-badge.live {
            background: linear-gradient(135deg, #FF4B2B, #7D3FFF);
            color: #fff !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        /* Ended badge (subtle glass) */
        .event-badge.ended {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            color: #000 !important;
            font-weight: 500;
            box-shadow: inset 0 1px 3px rgba(255,255,255,0.2),
                        0 2px 6px rgba(0,0,0,0.2);
        }

        /* Registrant status badges (upper-right) with soft colors and black text */
        .event-badge.status-badge {
            color: #000 !important; /* black text */
        }

        .event-badge.status-badge.pending {
            background: rgba(255, 230, 128, 0.95); /* softer yellow */
        }

        .event-badge.status-badge.accepted {
            background: rgba(144, 238, 144, 0.95); /* softer green */
        }

        .event-badge.status-badge.declined {
            background: rgba(255, 128, 128, 0.95); /* softer red */
        }
        
    </style>

    <script>
        // Tab initialization
        function initTabs() {
            document.querySelectorAll('.tabs').forEach(tabContainer => {
                const buttons = tabContainer.querySelectorAll('.tab-btn');
                const indicator = tabContainer.querySelector('.tab-indicator');
                const panels = tabContainer.nextElementSibling.querySelectorAll('.tab-panel');

                function updateIndicator() {
                    const activeBtn = tabContainer.querySelector('.tab-btn.active');
                    if(activeBtn && indicator) {
                        indicator.style.width = activeBtn.offsetWidth + 'px';
                        indicator.style.left = activeBtn.offsetLeft + 'px';
                    }
                }

                updateIndicator();
                window.addEventListener('resize', updateIndicator);

                buttons.forEach(btn => {
                    btn.onclick = () => {
                        buttons.forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                        const tab = btn.dataset.tab;
                        panels.forEach(panel => {
                            if(panel.id === tab){
                                panel.classList.add('active');
                            } else {
                                panel.classList.remove('active');
                            }
                        });
                        updateIndicator();
                    };
                });
            });
        }
        document.addEventListener('DOMContentLoaded', initTabs);
    </script>
    <?php
    // Return the captured HTML.
    return ob_get_clean();
});