<?php
/**
 * Plugin Name: QR Ticket Generator
 * Description: Provides a shortcode [qr_input_generator] to generate QR codes from input fields.
 * Version: 1.0
 * Author: Your Name
 */

function qr_input_generator_shortcode() {
    ob_start(); ?>
    <div id="qr-input-generator">
        <label>
            Guest Name:
            <input type="text" id="guest-name" placeholder="Enter guest name">
        </label><br><br>

        <label>
            Event ID:
            <input type="text" id="event-id" placeholder="Enter event ID">
        </label><br><br>

        <label>
            Ticket Code:
            <input type="text" id="ticket-code" placeholder="Enter ticket code">
        </label><br><br>

        <button id="generate-qr">Generate QR Code</button>

        <div id="generated-qr" style="margin-top:20px;"></div>
    </div>

    <!-- QR Code library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const generateBtn = document.getElementById("generate-qr");
        const qrContainer = document.getElementById("generated-qr");

        generateBtn.addEventListener("click", function() {
            const name = document.getElementById("guest-name").value.trim();
            const eventId = document.getElementById("event-id").value.trim();
            const ticketCode = document.getElementById("ticket-code").value.trim();

            if (!name || !eventId || !ticketCode) {
                alert("Please fill out all fields!");
                return;
            }

            // Create JSON data
            const qrData = JSON.stringify({
                guest: name,
                event: eventId,
                ticket: ticketCode
            });

            // Clear old QR
            qrContainer.innerHTML = "";

            // Generate QR
            new QRCode(qrContainer, {
                text: qrData,
                width: 200,
                height: 200
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('qr_input_generator', 'qr_input_generator_shortcode');
