<?php
// -------------------------------
// Shortcode: [user-dashboard]
// Displays user dashboard with upcoming/past events and registrations
// -------------------------------
add_shortcode('user-dashboard', function() {

    if (!is_user_logged_in()) {
        return '<p>Please log in to view your dashboard.</p>';
    }

    ob_start();
    ?>
    <div class="dashboard-container">

        <!-- My Events Section -->
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

        <!-- My Registrations Section -->
        <section class="dashboard-section" style="margin-top:60px;">
            <h2 style="font-size:28px; font-weight:bold; background: linear-gradient(135deg,#ff4b2b,#7d3fff); -webkit-background-clip: text; color: transparent; margin-bottom:20px;">My Registrations</h2>

            <div class="tabs">
                <button class="tab-btn active" data-tab="upcoming-reg">Upcoming</button>
                <button class="tab-btn" data-tab="past-reg">Past</button>
                <span class="tab-indicator"></span>
            </div>

            <div class="tab-content">
                <div class="tab-panel active" id="upcoming-reg">
                    <?php echo do_shortcode(''); ?>
                </div>
                <div class="tab-panel" id="past-reg">
                    <?php echo do_shortcode(''); ?>
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

        /* Event Cards */
        .user-upcoming-events-wrapper, .user-past-events-wrapper { display:flex; justify-content:center; width:100%; }
        .user-upcoming-events, .user-past-events {
            display:grid;
            grid-template-columns: repeat(2, minmax(0, 400px));
            gap: 20px;
            justify-content: center;
            max-width: 850px;
            width: 100%;
        }
        @media (max-width: 768px) { .user-upcoming-events, .user-past-events { grid-template-columns: 1fr; } }

        .event-card {
            display: block;
            border-radius: 15px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            width: 100%;
            text-decoration: none;
            color: inherit;
            transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
            position: relative;
        }
        .event-card:hover { transform: translateY(-6px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); background: rgba(255,255,255,0.15); }
        .event-card img { width: 100%; height: 250px; object-fit: cover; transition: transform 0.3s ease, filter 0.3s ease; filter: brightness(0.95); }
        .event-card:hover img { transform: scale(1.03); filter: brightness(1); }
        .event-card-content { padding: 15px; text-align: center; background: rgba(255,255,255,0.25); backdrop-filter: blur(5px); border-bottom-left-radius: 15px; border-bottom-right-radius: 15px; }
        .event-date { font-weight: bold; color:#333; margin-bottom:6px; }
        .event-card-content strong { display:block; font-size:1.1em; }
        .event-badge { position:absolute; top:10px; left:10px; background:rgba(255,255,255,0.2); backdrop-filter:blur(5px); color:#fff; padding:3px 8px; border-radius:12px; font-size:0.75em; font-weight:600; z-index:2; }
    </style>

    <script>
        // Tab initialization function (can be reused after AJAX refresh)
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

        initTabs(); // Initialize tabs on page load

        // AJAX auto-refresh for events
        function refreshEvents() {
            fetch("<?php echo admin_url('admin-ajax.php'); ?>?action=refresh_user_events")
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        document.querySelector('#upcoming').innerHTML = data.data.upcoming;
                        document.querySelector('#past').innerHTML = data.data.past;
                        initTabs(); // Re-initialize tab JS after refresh
                    }
                })
                .catch(err => console.error(err));
        }

        // Refresh every 30 seconds
        setInterval(refreshEvents, 30000);

        // Refresh immediately on page load
        refreshEvents();
    </script>
    <?php

    return ob_get_clean();
});

// -------------------------------
// AJAX handler for auto-refreshing events
// -------------------------------
add_action('wp_ajax_refresh_user_events', function() {
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
    }

    // Generate updated HTML for both upcoming and past events
    $upcoming = do_shortcode('[user-upcoming-events]');
    $past     = do_shortcode('[user-past-events]');

    wp_send_json_success([
        'upcoming' => $upcoming,
        'past'     => $past
    ]);
});
?>
