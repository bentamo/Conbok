<?php
/**
 * Create/Edit Event Page Shortcode
 * Usage: [create-event]
 */
add_shortcode('create-event', function ($atts = []) {
    $uid = uniqid('event-');

    // Get the event slug from the URL
    $slug = sanitize_text_field(get_query_var('event_slug', ''));

    // If no slug is present, treat it as a "create new event" form
    $event   = $slug ? get_page_by_path($slug, OBJECT, 'event') : null;
    $post_id = $event ? $event->ID : 0;

    // Restrict editing to author or admin
    if ($event) {
        $current_user_id = get_current_user_id();
        if ($event->post_author != $current_user_id && !current_user_can('manage_options')) {
            return '<p>You are not allowed to edit this event.</p>';
        }
    }

    // Pre-fill values if editing
    $value_title       = $event ? esc_attr($event->post_title) : '';
    $value_description = $event ? esc_textarea($event->post_content) : '';
    $value_location    = $event ? esc_attr(get_post_meta($post_id, '_location', true)) : '';
    $value_start_date  = $event ? esc_attr(get_post_meta($post_id, '_start_date', true)) : '';
    $value_end_date    = $event ? esc_attr(get_post_meta($post_id, '_end_date', true)) : '';
    $value_start_time  = $event ? esc_attr(get_post_meta($post_id, '_start_time', true)) : '';
    $value_end_time    = $event ? esc_attr(get_post_meta($post_id, '_end_time', true)) : '';

    // Button text
    $button_text = $event ? 'Edit Event' : 'Create Event';

    // Tickets & Payments
    global $wpdb;
    $table_tickets  = $wpdb->prefix . 'event_tickets';
    $table_payments = $wpdb->prefix . 'event_payment_methods';

    $tickets  = $event ? $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_tickets WHERE event_id = %d", $event->ID), ARRAY_A) : [];
    $payments = $event ? $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_payments WHERE event_id = %d", $event->ID), ARRAY_A) : [];

    // Featured Image
    $thumbnail_id  = $event ? get_post_thumbnail_id($event->ID) : 0;
    $thumbnail_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : '';

    ob_start(); ?>

    <div class="event-form-container">
        <!-- Back Button -->
        <div class="back-button-container">
            <button type="button" class="back-btn">← Back to Personal Page</button>
        </div>

        <!-- Event Form -->
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" id="<?php echo esc_attr($uid); ?>-form">
            <input type="hidden" name="action" value="conbook_create_event">
            <?php wp_nonce_field('conbook_create_event_nonce', 'conbook_create_event_nonce_field'); ?>

            <?php if ($event): ?>
                <input type="hidden" name="event_id" value="<?php echo intval($event->ID); ?>">
            <?php endif; ?>

            <!-- Title -->
            <div class="title-container">
                <input type="text"
                    id="<?php echo esc_attr($uid); ?>-title"
                    name="event-title"
                    placeholder="Enter event title..."
                    value="<?php echo $value_title; ?>"
                    required />
            </div>

            <div class="event-form-wrapper">
                <!-- Image Upload -->
                <div class="image-upload card">
                    <div class="image-slot">
                        <span class="upload-text" <?php echo $thumbnail_url ? 'style="display:none;"' : ''; ?>>Click to upload</span>
                        <input type="file" id="<?php echo esc_attr($uid); ?>-image" name="event_image" accept="image/*" <?php echo $thumbnail_url ? '' : 'required'; ?> />
                        <img src="<?php echo esc_url($thumbnail_url); ?>" alt="Preview" class="preview-image" style="<?php echo $thumbnail_url ? 'display:block;' : 'display:none;'; ?>" />
                        <button type="button" class="remove-image-btn" style="<?php echo $thumbnail_url ? 'display:block;' : 'display:none;'; ?>">✖</button>
                    </div>
                </div>

                <!-- Fields -->
                <div id="<?php echo esc_attr($uid); ?>" class="event-form card">
                    <!-- Date -->
                    <div class="range-group">
                        <div class="range-field">
                            <label for="<?php echo esc_attr($uid); ?>-date-start">Start Date</label>
                            <input type="date" id="<?php echo esc_attr($uid); ?>-date-start" name="start-date" value="<?php echo $value_start_date; ?>" required />
                        </div>
                        <span class="range-sep">–</span>
                        <div class="range-field">
                            <label for="<?php echo esc_attr($uid); ?>-date-end">End Date</label>
                            <input type="date" id="<?php echo esc_attr($uid); ?>-date-end" name="end-date" value="<?php echo $value_end_date; ?>" required />
                        </div>
                    </div>

                    <!-- Time -->
                    <div class="range-group">
                        <div class="range-field">
                            <label for="<?php echo esc_attr($uid); ?>-time-start">Start Time</label>
                            <input type="time" id="<?php echo esc_attr($uid); ?>-time-start" name="start-time" value="<?php echo $value_start_time; ?>" required />
                        </div>
                        <span class="range-sep">–</span>
                        <div class="range-field">
                            <label for="<?php echo esc_attr($uid); ?>-time-end">End Time</label>
                            <input type="time" id="<?php echo esc_attr($uid); ?>-time-end" name="end-time" value="<?php echo $value_end_time; ?>" required />
                        </div>
                    </div>

                    <!-- Location -->
                    <div>
                        <label for="<?php echo esc_attr($uid); ?>-location">Location / Address</label>
                        <input type="text"
                            id="<?php echo esc_attr($uid); ?>-location"
                            name="location"
                            placeholder="Enter a location..."
                            value="<?php echo $value_location; ?>"
                            required />
                    </div>

                    <!-- Tickets -->
                    <div>
                        <label>Ticket Options <span style="color:red">*</span></label>
                        <div class="tickets-list">
                            <?php if ($tickets): ?>
                                <?php foreach ($tickets as $i => $ticket): ?>
                                    <div class="ticket-item">
                                        <input type="text" name="ticket_name_<?php echo $i+1; ?>" value="<?php echo esc_attr($ticket['name']); ?>" placeholder="Ticket Name" required />
                                        <input type="number" name="ticket_price_<?php echo $i+1; ?>" value="<?php echo esc_attr($ticket['price']); ?>" placeholder="Price" min="0" step="0.01" required />
                                        <textarea name="ticket_description_<?php echo $i+1; ?>" rows="1" placeholder="Ticket Description"><?php echo esc_textarea($ticket['description']); ?></textarea>
                                        <button type="button" class="remove-ticket">✖</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="add-ticket-btn">➕ Add New Type</button>
                        <p class="ticket-error" style="color:red; display:none;">At least one ticket is required.</p>
                    </div>

                    <!-- Payment Methods -->
                    <div>
                        <label>Payment Methods <span style="color:red">*</span></label>
                        <div class="payments-list">
                            <?php if ($payments): ?>
                                <?php foreach ($payments as $i => $p): ?>
                                    <div class="payment-item">
                                        <input type="text" name="payment_name_<?php echo $i+1; ?>" value="<?php echo esc_attr($p['name']); ?>" placeholder="Payment Method Name" required />
                                        <input type="text" name="payment_details_<?php echo $i+1; ?>" value="<?php echo esc_attr($p['details']); ?>" placeholder="Details (e.g. account number, link)" required />
                                        <button type="button" class="remove-payment">✖</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="add-payment-btn">➕ Add New Method</button>
                        <p class="payment-error" style="color:red; display:none;">At least one payment method is required.</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="<?php echo esc_attr($uid); ?>-description">Description</label>
                        <textarea id="<?php echo esc_attr($uid); ?>-description"
                            name="description"
                            placeholder="Enter event description..."
                            rows="4"
                            required><?php echo $value_description; ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="create-event-container">
                <button type="submit" class="create-event-btn">
                    <?php echo $button_text; ?>
                </button>
            </div>
        </form>
    </div>

    <style>
    /* General */
    .event-form-container { margin: 2rem 0; }
    .event-form-wrapper, .event-form-wrapper * { box-sizing: border-box; }
    .card { flex: 1; padding: 1rem; border: 1px solid #ddd; border-radius: .5rem; background: #fafafa; min-width: 0; }
    input, textarea, button { font-family: inherit; }

    /* Unified Button Styles for Back and Create Event */
    .back-btn,
    .create-event-btn {
        font-family: 'Inter', sans-serif;   /* Font */
        font-weight: 500;                    /* Medium weight */
        padding: 12px 20px;                   /* Top/Bottom and Left/Right padding */
        font-size: 16px;                     /* Text size */
        border-radius: 30px;                 /* Rounded pill shape */
        border: none;                        /* No border */
        cursor: pointer;                     /* Pointer on hover */
        background: linear-gradient(135deg,#ff4b2b,#7d3fff); /* Gradient background */
        color: #fff !important;              /* White text */
        text-decoration: none !important;    /* Remove underline */
        transition: all 0.3s ease;           /* Smooth hover transition */
        transform: translateY(0);            /* Initial position for hover effect */
        box-shadow: none;                     /* No shadow by default */
        position: relative;                  /* For potential pseudo-elements or effects */
    }

    /* Hover State */
    .back-btn:hover,
    .create-event-btn:hover {
        transform: translateY(-2px);         /* Lift effect */
        box-shadow: 0 4px 15px #F07BB1;      /* Glow shadow */
        color: #F07BB1 !important;           /* Text changes color to pinkish */
    }

    /* Back button container spacing */
    .back-button-container { margin-bottom: 1rem; }

    /* Title */
    .title-container { margin-bottom: 1.5rem; }
    .title-container input { width: 100%; padding: .75rem 1rem; border: 1px solid #ccc; border-radius: .5rem; font-weight: 600; }
    @media (max-width:600px){ .title-container input{ font-size:1.25rem; } }
    @media (min-width:601px){ .title-container input{ font-size:2rem; } }

    /* Layout */
    .event-form-wrapper { display: flex; gap: 1.5rem; align-items: flex-start; }
    .event-form { display: grid; gap: 1.25rem; }

    /* Image upload */
    .image-upload { flex: 0 0 auto; }
    .image-slot { border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; cursor: pointer; }
    .image-slot:hover { border-color: #888; }
    .image-slot input[type=file]{ position: absolute; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
    .image-slot img{ position: absolute; width: 100%; height: 100%; object-fit: cover; aspect-ratio: 1/1; display: none; }
    .upload-text{ color: #888; padding: .5rem; text-align: center; }
    .image-slot .remove-image-btn {
        display: none;
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        background: rgba(0,0,0,0.5);
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 1.5rem;
        height: 1.5rem;
        font-size: 1rem;
        line-height: 1.5rem;
        text-align: center;
        cursor: pointer;
        padding: 0;
        transition: background 0.2s;
    }
    .image-slot .remove-image-btn:hover { background: rgba(0,0,0,0.8); }

    /* Fields */
    .range-group { display:grid; grid-template-columns:1fr auto 1fr; gap:1rem; align-items:end; }
    .range-sep { font-weight:600; text-align:center; }
    .event-form input, .event-form textarea { width: 100%; padding: .5rem; border:1px solid #ccc; border-radius:.375rem; resize:none; }
    .tickets-list { display:grid; gap:.5rem; }
    .ticket-item { display:grid; grid-template-columns:2fr 1fr auto; gap:.5rem; align-items:center; }
    .ticket-item textarea {
        grid-column: 1 / span 3;
        width: 100%;
        padding: .5rem;
        border: 1px solid #ccc;
        border-radius: .375rem;
        resize: none;
    }
    .remove-ticket { grid-column: 1 / span 3; background:none; border:none; color:#c00; font-size:1.2rem; cursor:pointer; }
    .add-ticket-btn { margin-top:0.5rem; padding:.5rem .75rem; border:1px solid #ccc; background:#fff; color:#333; border-radius:.375rem; cursor:pointer; }
    .add-ticket-btn:hover { background:#f0f0f0; }

    /* Payments */
    .payments-list { display:grid; gap:.5rem; }
    .payment-item { display:grid; grid-template-columns:2fr 3fr auto; gap:.5rem; align-items:center; }
    .remove-payment { background:none; border:none; color:#c00; font-size:1.2rem; cursor:pointer; }
    .add-payment-btn { margin-top:0.5rem; padding:.5rem .75rem; border:1px solid #ccc; background:#fff; color:#333; border-radius:.375rem; cursor:pointer; }
    .add-payment-btn:hover { background:#f0f0f0; }

    /* Submit container */
    .create-event-container { margin-top:1.5rem; text-align:right; }

    @media (min-width:993px){
        .image-slot { height: 500px; width: 500px; }
    }
    @media (min-width:601px) and (max-width:992px){
        .event-form-wrapper { flex-direction: column; gap: 1.5rem; }
        .image-upload, .event-form { width: 100%; }
        .image-slot { width: 100%; aspect-ratio: 1/1; height: auto; }
    }
    @media (max-width:600px){
        .event-form-wrapper{ flex-direction:column; }
        .image-upload, .event-form { width: 100%; }
        .image-slot{ width:100%; aspect-ratio:1/1; height:auto; }
        .range-group{ grid-template-columns:1fr; }
        .range-sep{ display:none; }
        .ticket-item, .payment-item { grid-template-columns:1fr; }
    }
    </style>

    <script>
    (function(){
        const form = document.getElementById('<?php echo esc_js($uid); ?>-form');
        const root = document.getElementById('<?php echo esc_js($uid); ?>');

        /* ---------------- Tickets ---------------- */
        const ticketsList = root.querySelector('.tickets-list');
        const addTicketBtn = root.querySelector('.add-ticket-btn');
        const ticketError = root.querySelector('.ticket-error');
        let ticketIdx = ticketsList.querySelectorAll('.ticket-item').length;

        function addTicket() {
            ticketIdx++;
            const item = document.createElement('div');
            item.className = 'ticket-item';
            item.innerHTML = `
                <input type="text" name="ticket_name_${ticketIdx}" placeholder="Ticket Name" required />
                <input type="number" name="ticket_price_${ticketIdx}" placeholder="Price" min="0" step="0.01" required />
                <textarea name="ticket_description_${ticketIdx}" placeholder="Ticket Description" rows="1"></textarea>
                <button type="button" class="remove-ticket">✖</button>`;
            ticketsList.appendChild(item);
            item.querySelector('.remove-ticket').addEventListener('click', () => item.remove());
        }
        if (ticketIdx === 0) addTicket();
        addTicketBtn.addEventListener('click', addTicket);

        /* ---------------- Payments ---------------- */
        const paymentsList = root.querySelector('.payments-list');
        const addPaymentBtn = root.querySelector('.add-payment-btn');
        const paymentError = root.querySelector('.payment-error');
        let paymentIdx = paymentsList.querySelectorAll('.payment-item').length;

        function addPayment() {
            paymentIdx++;
            const item = document.createElement('div');
            item.className = 'payment-item';
            item.innerHTML = `
                <input type="text" name="payment_name_${paymentIdx}" placeholder="Payment Method Name" required />
                <input type="text" name="payment_details_${paymentIdx}" placeholder="Details (e.g. account number, link)" required />
                <button type="button" class="remove-payment">✖</button>`;
            paymentsList.appendChild(item);
            item.querySelector('.remove-payment').addEventListener('click', () => item.remove());
        }
        if (paymentIdx === 0) addPayment();
        addPaymentBtn.addEventListener('click', addPayment);

        /* ---------------- Form Validation ---------------- */
        form.addEventListener('submit', e => {
            let valid = true;

            if (!ticketsList.querySelector('.ticket-item')) {
                ticketError.style.display = 'block';
                valid = false;
            } else {
                ticketError.style.display = 'none';
            }

            if (!paymentsList.querySelector('.payment-item')) {
                paymentError.style.display = 'block';
                valid = false;
            } else {
                paymentError.style.display = 'none';
            }

            if (!valid) e.preventDefault();
        });

        /* ---------------- Image Upload ---------------- */
        const imgSlot = form.querySelector('.image-slot');
        if (imgSlot){
            const file = imgSlot.querySelector('input[type=file]');
            const preview = imgSlot.querySelector('img');
            const text = imgSlot.querySelector('.upload-text');
            const removeBtn = imgSlot.querySelector('.remove-image-btn');

            file.addEventListener('change', e => {
                const f = e.target.files[0];
                if (!f) return;
                const reader = new FileReader();
                reader.onload = e => {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    text.style.display = 'none';
                    removeBtn.style.display = 'block';
                };
                reader.readAsDataURL(f);
            });

            removeBtn.addEventListener('click', () => {
                file.value = '';
                preview.src = '';
                preview.style.display = 'none';
                text.style.display = 'block';
                removeBtn.style.display = 'none';
            });
        }

        /* ---------------- Back Button ---------------- */
        document.querySelector('.back-btn')?.addEventListener('click', () => history.back());
    })();
    </script>

    <?php
    return ob_get_clean();
});
