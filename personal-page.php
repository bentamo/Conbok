<?php
/**
 * SECTION: CORE SHORTCODE LOGIC
 * Description: Registers the primary [user-dashboard] shortcode
 * to display a logged-in user's personalized dashboard.
 *
 * This section includes the main function that handles the
 * shortcode's output, including user authentication checks and
 * the primary HTML structure.
 */

/**
 * Renders the full user dashboard view.
 *
 * This shortcode checks if a user is logged in. If not, it displays a
 * login prompt. For logged-in users, it generates the HTML for the
 * dashboard, including separate sections for 'My Events' and
 * 'My Registrations', each with their own tabbed content.
 *
 * It uses nested shortcodes to dynamically load event and registration
 * data, separating content from the main layout logic.
 *
 * @return string The HTML output for the user dashboard.
 */
add_shortcode('user-dashboard', function() {

    /**
     * SUB-SECTION: User Authentication
     * Description: Verifies if a user is currently logged in before
     * rendering the dashboard content.
     */
    if (!is_user_logged_in()) {
        return '<p>Please log in to view your dashboard.</p>';
    }

    ob_start();
    ?>
    <div class="dashboard-container">

        <!-- 
         SUB-SECTION: My Events
 
         Description: Renders the events section of the user dashboard.
         This section features a 'Create Event' button and a tabbed
         interface for viewing upcoming and past events owned by the user.
         The content is populated by the [user-upcoming-events] and
         [user-past-events] shortcodes. 
        -->
        <section class="dashboard-section">
            <div class="dashboard-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="font-size:28px; font-weight:bold; background: linear-gradient(135deg,#ff4b2b,#7d3fff); -webkit-background-clip: text; color: transparent;">My Events</h2>
                <a href="<?php echo site_url('/create-event'); ?>" class="create-event-btn">Create Event +</a>
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

        <!-- 
         SUB-SECTION: My Registrations

         Description: Renders the registrations section of the user dashboard.
         This section provides a tabbed view for events the user has
         registered for, separated into upcoming and past events.
         The content is populated by the [user-upcoming-registrations] and
         [user-past-registrations] shortcodes.
        -->
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

    <!-- 
     SECTION: STYLESHEET
     Description: Contains all CSS rules for styling the user dashboard.
     The styles are encapsulated within the shortcode to ensure they are
     loaded only when the shortcode is used.
    
     This section is divided into logical sub-sections for better organization.
    -->
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

    <!-- 
     SECTION: JAVASCRIPT
     Description: Contains all JavaScript for handling interactive elements.
     This script manages the tabbed interface functionality.
    -->
    <script>
        /**
         * Initializes the tab functionality for all '.tabs' containers on the page.
         *
         * This function handles:
         * 1. Setting the initial position of the tab indicator.
         * 2. Updating the indicator on window resize to maintain responsiveness.
         * 3. Attaching click event listeners to each tab button to switch
         * between tab panels and update the indicator's position.
         */
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
        initTabs();
    </script>
    <?php
    return ob_get_clean();
});