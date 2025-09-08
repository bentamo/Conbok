<?php
/**
 * ============================================================
 * Event Form Shortcode with Image Grid Upload + Buttons
 * Usage: [event-form]
 * ============================================================
 */

add_shortcode('event-form', function ($atts = []) {
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
        'time-step'          => '900',
        'time-default-start' => '',
        'time-default-end'   => '',

        // Location
        'location-label'     => 'Set Location / Address',
        'location-name'      => 'location',
        'location-required'  => 'false',
        'location-default'   => '',
        'location-placeholder' => 'Enter a location...',

        // Description
        'description-label' => 'Description',
        'description-name'  => 'description',
        'description-placeholder' => 'Enter event description...',

        // Wrapper
        'class'              => '',
    ], $atts, 'event-form');

    $uid = uniqid('event-');

    $date_required     = filter_var($a['date-required'], FILTER_VALIDATE_BOOLEAN);
    $time_required     = filter_var($a['time-required'], FILTER_VALIDATE_BOOLEAN);
    $location_required = filter_var($a['location-required'], FILTER_VALIDATE_BOOLEAN);

    ob_start(); ?>

    <!-- Back Button Container -->
    <div class="back-button-container">
        <button type="button" class="back-btn">
            ← Back to Personal Page
        </button>
    </div>

    <!-- Event Form -->
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
        <input type="hidden" name="action" value="conbook_create_event">
        <?php wp_nonce_field('conbook_create_event_nonce', 'conbook_create_event_nonce_field'); ?>

        <div class="event-form-wrapper">

            <!-- Image Grid Upload -->
            <div class="image-upload-grid">
                <div class="image-slot">
                    <span class="upload-text">Click to upload</span>
                    <input type="file" id="<?php echo esc_attr($uid); ?>-image" name="event_image" accept="image/*" />
                    <img src="" alt="Preview" class="preview-image" />
                </div>
            </div>

            <!-- Form Fields -->
            <div id="<?php echo esc_attr($uid); ?>" class="event-form <?php echo esc_attr($a['class']); ?>">

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

                <!-- Location -->
                <div class="location-group">
                    <div class="location-field">
                        <label for="<?php echo esc_attr($uid); ?>-location"><?php echo esc_html($a['location-label']); ?></label>
                        <input type="text"
                            id="<?php echo esc_attr($uid); ?>-location"
                            name="<?php echo esc_attr($a['location-name']); ?>"
                            value="<?php echo esc_attr($a['location-default']); ?>"
                            placeholder="<?php echo esc_attr($a['location-placeholder']); ?>"
                            <?php echo $location_required ? 'required' : ''; ?>
                        />
                    </div>
                </div>

                <!-- Ticket Options -->
                <div class="tickets-group">
                    <div class="tickets-field">
                        <label>Ticket Options</label>
                        <div class="tickets-list"></div>
                        <button type="button" class="add-ticket-btn">➕ Add New Type</button>
                    </div>
                </div>

                <!-- Description -->
                <div class="description-group">
                    <div class="description-field">
                        <label for="<?php echo esc_attr($uid); ?>-description"><?php echo esc_html($a['description-label']); ?></label>
                        <textarea id="<?php echo esc_attr($uid); ?>-description"
                            name="<?php echo esc_attr($a['description-name']); ?>"
                            placeholder="<?php echo esc_attr($a['description-placeholder']); ?>"
                            rows="4"></textarea>
                    </div>
                </div>

            </div>
        </div>

        <!-- Create Event Button Container -->
        <div class="create-event-container">
            <button type="submit" class="create-event-btn">
                Create Event
            </button>
        </div>
    </form>

    <script>
    (function(){
        var root = document.getElementById('<?php echo esc_js($uid); ?>');
        if (!root) return;

        // Date logic
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

        // Time logic
        var tStart = root.querySelector('#<?php echo esc_js($uid); ?>-time-start');
        var tEnd   = root.querySelector('#<?php echo esc_js($uid); ?>-time-end');
        function clampTime() {
            if (tStart.value && tEnd.value && tEnd.value < tStart.value) {
                tEnd.value = tStart.value;
            }
        }
        tStart.addEventListener('change', clampTime);
        tEnd.addEventListener('change', clampTime);

        // Ticket logic
        var ticketsList = root.querySelector('.tickets-list');
        var addTicketBtn = root.querySelector('.add-ticket-btn');
        var ticketIndex = 0;
        addTicketBtn.addEventListener('click', function() {
            ticketIndex++;
            var wrapper = document.createElement('div');
            wrapper.className = 'ticket-item';
            wrapper.innerHTML = `
                <input type="text" name="ticket_name_${ticketIndex}" placeholder="Ticket Name" required />
                <input type="number" name="ticket_price_${ticketIndex}" placeholder="Price" min="0" step="0.01" required />
                <button type="button" class="remove-ticket">✖</button>
            `;
            ticketsList.appendChild(wrapper);
            wrapper.querySelector('.remove-ticket').addEventListener('click', function(){ wrapper.remove(); });
        });

        // Image preview logic
        var imageSlot = root.parentElement.querySelector('.image-slot');
        var fileInput = imageSlot.querySelector('input[type="file"]');
        var preview = imageSlot.querySelector('.preview-image');
        var uploadText = imageSlot.querySelector('.upload-text');
        fileInput.addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                uploadText.style.display = 'none';
            }
            reader.readAsDataURL(file);
        });

        // Back button click
        var backBtn = document.querySelector('.back-btn');
        backBtn.addEventListener('click', function(){
            window.history.back();
        });
    })();
    </script>

    <style>
    /* Base */
    .back-button-container { text-align:left; margin-bottom:1rem; }
    .back-btn { padding:.5rem 1rem; background:#eee; border:1px solid #ccc; border-radius:.375rem; cursor:pointer; }
    .back-btn:hover { background:#ddd; }

    .event-form-wrapper { display:flex; gap:1.5rem; align-items:flex-start; flex-wrap:wrap; }
    .event-form { display:grid; gap:1.25rem; border:1px solid #ddd; border-radius:.5rem; padding:1rem; background:#fafafa; flex:1; min-width:260px; }

    /* Image Upload */
    .image-upload-grid { flex:0 0 auto; display:flex; justify-content:center; }
    .image-slot { width:500px; height:500px; border:2px dashed #ccc; display:flex; align-items:center; justify-content:center; cursor:pointer; position:relative; overflow:hidden; max-width:100%; }
    .image-slot:hover { border-color:#888; }
    .upload-text { text-align:center; color:#888; }
    .image-slot input[type="file"] { position:absolute; width:100%; height:100%; opacity:0; cursor:pointer; }
    .preview-image { position:absolute; width:100%; height:100%; object-fit:cover; display:none; }

    /* Fields */
    .range-group, .location-group, .tickets-group, .description-group { display:grid; gap:.5rem; align-items:end; grid-template-columns:1fr auto 1fr auto; column-gap:1rem; }
    .location-group, .description-group, .tickets-group { grid-template-columns:1fr; }
    .range-field, .location-field, .description-field, .tickets-field { display:grid; gap:.25rem; }
    .range-sep { padding:0 .25rem; font-weight:600; line-height:2.4; text-align:center; }
    .event-form input, .event-form textarea { width:100%; padding:.5rem; border:1px solid #ccc; border-radius:.375rem; font-family:inherit; }
    .tickets-list { display:grid; gap:.5rem; }
    .ticket-item { display:grid; grid-template-columns:2fr 1fr auto; gap:.5rem; align-items:center; }
    .ticket-item button.remove-ticket { background:none; border:none; color:#c00; font-size:1.2rem; cursor:pointer; }
    .add-ticket-btn { padding:.5rem .75rem; border:1px solid #ccc; background:#fff; border-radius:.375rem; cursor:pointer; }
    .add-ticket-btn:hover { background:#f0f0f0; }

    /* Button */
    .create-event-container { margin-top:1.5rem; text-align:right; }
    .create-event-btn { padding:.75rem 1.25rem; background:#0073aa; color:#fff; border:none; border-radius:.375rem; cursor:pointer; font-size:1rem; }
    .create-event-btn:hover { background:#005177; }

    /* Tablet (≥600px and <992px) */
    @media (max-width: 992px) {
      .event-form-wrapper { flex-direction:row; justify-content:center; }
      .image-upload-grid { flex:0 0 100%; display:flex; justify-content:center; }
      .image-slot { width:500px; height:500px; }
    }

    /* Mobile (<600px) */
    @media (max-width: 600px) {
      .event-form-wrapper { flex-direction:column; align-items:center; }
      .image-upload-grid, .event-form { width:100%; }
      .image-slot { width:100%; height:auto; aspect-ratio:1/1; }

      /* Make all form fields full width */
      .range-group,
      .time-range,
      .date-range,
      .location-group,
      .tickets-group,
      .description-group {
        grid-template-columns: 1fr;
      }
      .range-sep { display:none; }
      .ticket-item { grid-template-columns:1fr; }
    }
    </style>
    <?php
    return ob_get_clean();
});
