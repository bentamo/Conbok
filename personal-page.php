<?php
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
                <div class="tab-panel" id="upcoming">
                    <?php echo do_shortcode('[user-upcoming-events]'); ?>
                </div>
                <div class="tab-panel" id="past" style="display:none;">
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
                <div class="tab-panel" id="upcoming-reg">
                    <?php echo do_shortcode(''); ?>
                </div>
                <div class="tab-panel" id="past-reg" style="display:none;">
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

        /* -----------------------------
           Create Event Button Styling
        ------------------------------ */
        .create-event-btn {
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            padding: 8px 16px;
            font-size: 14px;
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
            text-decoration: none !important;
            color: #F07BB1 !important;
        }

        /* -----------------------------
           Tab Buttons Styling
        ------------------------------ */
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
            transform: translateY(0);
            border: none;
            text-decoration: none !important;
        }

        /* Only float effect on hover */
        .tab-btn:hover {
            transform: translateY(-2px);
            box-shadow: none;
            background: transparent;
            color: inherit !important;
            text-decoration: none !important;
        }

        .tab-btn.active {
            color: #000;
            font-weight: bold;
        }

        /* Remove all outlines, shadows, and background for tab buttons */
        .tab-btn,
        .tab-btn:focus,
        .tab-btn:active {
            outline: none !important;
            box-shadow: none !important;
            background: transparent !important;
            color: inherit !important;
        }

        /* Tab indicator line */
        .tab-indicator {
            position: absolute;
            bottom: -6px;
            height: 2px;
            background: linear-gradient(135deg,#ff4b2b,#7d3fff);
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        .tab-content { }
        .tab-panel { }
    </style>

    <script>
        document.querySelectorAll('.tabs').forEach(tabContainer => {
            const buttons = tabContainer.querySelectorAll('.tab-btn');
            const indicator = tabContainer.querySelector('.tab-indicator');
            const panels = tabContainer.nextElementSibling.querySelectorAll('.tab-panel') || tabContainer.querySelectorAll('.tab-panel');

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
                btn.addEventListener('click', () => {
                    buttons.forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');

                    const tab = btn.dataset.tab;
                    panels.forEach(panel => panel.style.display = (panel.id === tab) ? 'block' : 'none');

                    updateIndicator();
                });
            });
        });
    </script>
    <?php

    return ob_get_clean();
});
