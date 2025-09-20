<?php
/**
 * QR Code Image Generator.
 *
 * This file dynamically generates a QR code image based on URL parameters.
 * It is designed to be called by the `qr_code_generator_shortcode()` function
 * to create a QR code on the fly without saving it to the server.
 *
 * It uses the external PHP QR Code library (`qrlib.php`) to handle the
 * image generation.
 *
 * @package QR_Code_Generator
 * @since 1.0.0
 */

/* ==============================================
 * SECTION 1: DEPENDENCIES & SETUP
 * ============================================== */

// Include the necessary QR Code library.
require_once __DIR__ . '/phpqrcode/qrlib.php';

/* ==============================================
 * SECTION 2: INPUT SANITIZATION & VALIDATION
 * ============================================== */

// Sanitize the 'text' parameter from the URL.
// The `GET` request should not contain sensitive information.
// `htmlspecialchars` prevents XSS attacks by encoding special characters.
$text = isset($_GET['text']) ? htmlspecialchars($_GET['text']) : 'Hello World';

// Validate and sanitize the 'size' parameter.
// `intval` ensures the size is a safe integer.
$size = isset($_GET['size']) ? intval($_GET['size']) : 4;

// SECTION 2.1: GD Library Check
// -----------------------------
// Verify that the PHP GD library, which is required for image manipulation, is enabled.
if (!function_exists('imagepng')) {
    // If GD is not enabled, return a plain text error message.
    header('Content-Type: text/plain');
    echo "Error: PHP GD library is not installed or enabled. Please contact your host provider.";
    exit;
}

/* ==============================================
 * SECTION 3: QR CODE GENERATION & OUTPUT
 * ============================================== */

// Set the HTTP header to tell the browser to expect a PNG image.
header('Content-Type: image/png');

// Generate the QR code and output it directly to the browser.
// The parameters are:
// 1. `text`: The string to be encoded in the QR code.
// 2. `false`: The output file path. `false` means output directly to the browser.
// 3. `QR_ECLEVEL_L`: Error correction level (Low).
// 4. `size`: The size of each module in pixels.
// 5. `2`: The margin size.
QRcode::png($text, false, QR_ECLEVEL_L, $size, 2);

// Terminate script execution after the image has been output.
exit;