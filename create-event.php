<?php
/**
 * Create Event Page Shortcode
 * Usage: [create-event]
 */
add_shortcode('create-event', function ($atts = []) {
    $uid = uniqid('event-');

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

            <!-- Title -->
            <div class="title-container">
                <input type="text"
                    id="<?php echo esc_attr($uid); ?>-title"
                    name="event-title"
                    placeholder="Enter event title..."
                    required />
            </div>

            <div class="event-form-wrapper">
                <!-- Image Upload -->
                <div class="image-upload card">
                    <div class="image-slot">
                        <span class="upload-text">Click to upload</span>
                        <input type="file" id="<?php echo esc_attr($uid); ?>-image" name="event_image" accept="image/*" required />
                        <img src="" alt="Preview" class="preview-image" />
                        <button type="button" class="remove-image-btn">✖</button>
                    </div>
                </div>

                <!-- Fields -->
                <div id="<?php echo esc_attr($uid); ?>" class="event-form card">
                    <!-- Date -->
                    <div class="range-group">
                        <div class="range-field">
                            <label for="<?php echo esc_attr($uid); ?>-date-start">Start Date</label>
                            <input type="date" id="<?php echo esc_attr($uid); ?>-date-start" name="start-date" required />
                        </div>
                        <span class="range-sep">–</span>
                        <div class="range-field">
                            <label for="<?php echo esc_attr($uid); ?>-date-end">End Date</label>
                            <input type="date" id="<?php echo esc_attr($uid); ?>-date-end" name="end-date" required />
                        </div>
                    </div>

                    <!-- Time -->
                    <div class="range-group">
                        <div class="range-field">
                            <label for="<?php echo esc_attr($uid); ?>-time-start">Start Time</label>
                            <input type="time" id="<?php echo esc_attr($uid); ?>-time-start" name="start-time" required />
                        </div>
                        <span class="range-sep">–</span>
                        <div class="range-field">
                            <label for="<?php echo esc_attr($uid); ?>-time-end">End Time</label>
                            <input type="time" id="<?php echo esc_attr($uid); ?>-time-end" name="end-time" required />
                        </div>
                    </div>

                    <!-- Location -->
                    <div>
                        <label for="<?php echo esc_attr($uid); ?>-location">Location / Address</label>
                        <input type="text"
                            id="<?php echo esc_attr($uid); ?>-location"
                            name="location"
                            placeholder="Enter a location..."
                            required />
                    </div>

                    <!-- Tickets -->
                    <div>
                        <label>Ticket Options <span style="color:red">*</span></label>
                        <div class="tickets-list"></div>
                        <button type="button" class="add-ticket-btn">➕ Add New Type</button>
                        <p class="ticket-error" style="color:red; display:none;">At least one ticket is required.</p>
                    </div>

                    <!-- Payment Methods -->
                    <div>
                        <label>Payment Methods <span style="color:red">*</span></label>
                        <div class="payments-list"></div>
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
                            required></textarea>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="create-event-container">
                <button type="submit" class="create-event-btn">Create Event</button>
            </div>
        </form>
    </div>

    <style>
    /* General */
    .event-form-container { margin: 2rem 0; }
    .event-form-wrapper, .event-form-wrapper * { box-sizing: border-box; }
    .card { flex: 1; padding: 1rem; border: 1px solid #ddd; border-radius: .5rem; background: #fafafa; min-width: 0; }
    input, textarea, button { font-family: inherit; }

    /* Back button */
    .back-button-container { margin-bottom: 1rem; }
    .back-btn {
        padding: .5rem 1rem;
        color: #fff;
        border: none;
        cursor: pointer;
        font-size: 1rem;
        border-radius:30px;
        background:linear-gradient(135deg,rgb(255,75,43) 0%,rgb(125,63,255) 100%);
    }
    .back-btn:hover { opacity:0.9; }

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
    .remove-ticket { background:none; border:none; color:#c00; font-size:1.2rem; cursor:pointer; }
    .add-ticket-btn { margin-top:0.5rem; padding:.5rem .75rem; border:1px solid #ccc; background:#fff; color:#333; border-radius:.375rem; cursor:pointer; }
    .add-ticket-btn:hover { background:#f0f0f0; }

    /* Payments */
    .payments-list { display:grid; gap:.5rem; }
    .payment-item { display:grid; grid-template-columns:2fr 3fr auto; gap:.5rem; align-items:center; }
    .remove-payment { background:none; border:none; color:#c00; font-size:1.2rem; cursor:pointer; }
    .add-payment-btn { margin-top:0.5rem; padding:.5rem .75rem; border:1px solid #ccc; background:#fff; color:#333; border-radius:.375rem; cursor:pointer; }
    .add-payment-btn:hover { background:#f0f0f0; }

    /* Submit */
    .create-event-container { margin-top:1.5rem; text-align:right; }
    .create-event-btn {
        padding:.75rem 1.25rem;
        color:#fff;
        border:none;
        cursor:pointer;
        font-size:1rem;
        border-radius:30px;
        background:linear-gradient(135deg,rgb(255,75,43) 0%,rgb(125,63,255) 100%);
    }
    .create-event-btn:hover{ opacity:0.9; }

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
        let ticketIdx = 0;

        function addTicket() {
            ticketIdx++;
            const item = document.createElement('div');
            item.className = 'ticket-item';
            item.innerHTML = `
                <input type="text" name="ticket_name_${ticketIdx}" placeholder="Ticket Name" required />
                <input type="number" name="ticket_price_${ticketIdx}" placeholder="Price" min="0" step="0.01" required />
                <button type="button" class="remove-ticket">✖</button>`;
            ticketsList.appendChild(item);
            item.querySelector('.remove-ticket').addEventListener('click', () => item.remove());
        }
        addTicket();
        addTicketBtn.addEventListener('click', addTicket);

        /* ---------------- Payments ---------------- */
        const paymentsList = root.querySelector('.payments-list');
        const addPaymentBtn = root.querySelector('.add-payment-btn');
        const paymentError = root.querySelector('.payment-error');
        let paymentIdx = 0;

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
        addPayment();
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
