<?php
/**
 * ============================================================
 * 📅⏰ Date and Time Range Picker Shortcode
 * Usage: [date-and-time-picker]
 * ============================================================
 */

add_shortcode('date-and-time-picker', function ($atts = []) {
    $a = shortcode_atts([
        // Date settings
        'date-start-label'   => 'Start Date',
        'date-end-label'     => 'End Date',
        'date-name-start'    => 'start-date',
        'date-name-end'      => 'end-date',
        'date-required'      => 'false',
        'date-min'           => '',
        'date-max'           => '',
        'date-default-start' => '',
        'date-default-end'   => '',

        // Time settings
        'time-start-label'   => 'Start Time',
        'time-end-label'     => 'End Time',
        'time-name-start'    => 'start-time',
        'time-name-end'      => 'end-time',
        'time-required'      => 'false',
        'time-step'          => '900',   // 900 = 15 minutes
        'time-default-start' => '',
        'time-default-end'   => '',

        // Wrapper
        'class'              => '',
    ], $atts, 'date-and-time-picker');

    $uid = uniqid('dtp-');

    $date_required = filter_var($a['date-required'], FILTER_VALIDATE_BOOLEAN);
    $time_required = filter_var($a['time-required'], FILTER_VALIDATE_BOOLEAN);

    ob_start(); ?>
    <div id="<?php echo esc_attr($uid); ?>" class="datetime-wrap <?php echo esc_attr($a['class']); ?>">

        <!-- Date Range -->
        <div class="range-group date-range">
            <div class="range-field">
                <label for="<?php echo esc_attr($uid); ?>-date-start"><?php echo esc_html($a['date-start-label']); ?></label>
                <input type="date"
                    id="<?php echo esc_attr($uid); ?>-date-start"
                    name="<?php echo esc_attr($a['date-name-start']); ?>"
                    value="<?php echo esc_attr($a['date-default-start']); ?>"
                    <?php echo $a['date-min'] ? 'min="'.esc_attr($a['date-min']).'"' : ''; ?>
                    <?php echo $a['date-max'] ? 'max="'.esc_attr($a['date-max']).'"' : ''; ?>
                    <?php echo $date_required ? 'required' : ''; ?>
                />
            </div>
            <div class="range-sep">–</div>
            <div class="range-field">
                <label for="<?php echo esc_attr($uid); ?>-date-end"><?php echo esc_html($a['date-end-label']); ?></label>
                <input type="date"
                    id="<?php echo esc_attr($uid); ?>-date-end"
                    name="<?php echo esc_attr($a['date-name-end']); ?>"
                    value="<?php echo esc_attr($a['date-default-end']); ?>"
                    <?php echo $a['date-min'] ? 'min="'.esc_attr($a['date-min']).'"' : ''; ?>
                    <?php echo $a['date-max'] ? 'max="'.esc_attr($a['date-max']).'"' : ''; ?>
                    <?php echo $date_required ? 'required' : ''; ?>
                />
            </div>
        </div>

        <!-- Time Range -->
        <div class="range-group time-range">
            <div class="range-field">
                <label for="<?php echo esc_attr($uid); ?>-time-start"><?php echo esc_html($a['time-start-label']); ?></label>
                <input type="time"
                    id="<?php echo esc_attr($uid); ?>-time-start"
                    name="<?php echo esc_attr($a['time-name-start']); ?>"
                    value="<?php echo esc_attr($a['time-default-start']); ?>"
                    step="<?php echo intval($a['time-step']); ?>"
                    <?php echo $time_required ? 'required' : ''; ?>
                />
            </div>
            <div class="range-sep">–</div>
            <div class="range-field">
                <label for="<?php echo esc_attr($uid); ?>-time-end"><?php echo esc_html($a['time-end-label']); ?></label>
                <input type="time"
                    id="<?php echo esc_attr($uid); ?>-time-end"
                    name="<?php echo esc_attr($a['time-name-end']); ?>"
                    value="<?php echo esc_attr($a['time-default-end']); ?>"
                    step="<?php echo intval($a['time-step']); ?>"
                    <?php echo $time_required ? 'required' : ''; ?>
                />
            </div>
        </div>
    </div>

    <script>
    (function(){
        var root = document.getElementById('<?php echo esc_js($uid); ?>');
        if (!root) return;

        // Date fields
        var dStart = root.querySelector('#<?php echo esc_js($uid); ?>-date-start');
        var dEnd   = root.querySelector('#<?php echo esc_js($uid); ?>-date-end');
        function clampDate() {
            if (dStart.value) dEnd.min = dStart.value;
            if (dEnd.value) dStart.max = dEnd.value;
            if (dStart.value && dEnd.value && dEnd.value < dStart.value) {
                dEnd.value = dStart.value;
            }
        }
        dStart.addEventListener('change', clampDate);
        dEnd.addEventListener('change', clampDate);
        clampDate();

        // Time fields
        var tStart = root.querySelector('#<?php echo esc_js($uid); ?>-time-start');
        var tEnd   = root.querySelector('#<?php echo esc_js($uid); ?>-time-end');
        function clampTime() {
            if (tStart.value && tEnd.value && tEnd.value < tStart.value) {
                tEnd.value = tStart.value;
            }
        }
        tStart.addEventListener('change', clampTime);
        tEnd.addEventListener('change', clampTime);
    })();
    </script>

    <style>
    .datetime-wrap { display: grid; gap: 1rem; }
    .range-group { display: grid; grid-template-columns: 1fr auto 1fr; gap: .5rem; align-items: end; }
    .range-field { display: grid; gap: .25rem; }
    .range-sep { padding: 0 .25rem; font-weight: 600; line-height: 2.4; }
    .range-group input[type="date"],
    .range-group input[type="time"] { width: 100%; }
    </style>
    <?php
    return ob_get_clean();
});
