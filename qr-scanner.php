<?php
/**
 * Plugin Name: QR Scanner Shortcode
 * Description: Provides a shortcode [qr_scanner] to scan QR codes using camera.
 * Version: 1.0
 * Author: Your Name
 */

// Enqueue scripts
function qr_scanner_enqueue_scripts() {
    // Load html5-qrcode from CDN
    wp_enqueue_script(
        'html5-qrcode',
        'https://unpkg.com/html5-qrcode@2.3.7/html5-qrcode.min.js',
        array(),
        null,
        true
    );

    // Load our custom scanner logic
    wp_enqueue_script(
        'qr-scanner-script',
        plugin_dir_url(__FILE__) . 'qr-scanner.js',
        array('html5-qrcode'), // depends on html5-qrcode
        null,
        true
    );
}
add_action('wp_enqueue_scripts', 'qr_scanner_enqueue_scripts');

// Shortcode [qr_scanner]
function qr_scanner_shortcode() {
    ob_start(); ?>
    
    <div id="qr-scanner-container">
        <button id="open-qr-btn" style="padding:10px 20px; background:#0073aa; color:#fff; border:none; border-radius:5px; cursor:pointer;">
            Open QR Scanner
        </button>

        <div id="qr-reader-wrapper" style="display:none; margin-top:15px;">
            <div id="qr-reader" style="width:300px; height:300px; border:1px solid #ccc;"></div>
            <button id="close-qr-btn" style="margin-top:10px; padding:8px 15px; background:#d63638; color:#fff; border:none; border-radius:5px; cursor:pointer;">
                Close Scanner
            </button>
        </div>

        <div id="qr-result" style="margin-top:15px; font-weight:bold;"></div>
    </div>
    
    <?php
    return ob_get_clean();
}
add_shortcode('qr_scanner', 'qr_scanner_shortcode');
