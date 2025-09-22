<?php
/**
 * SECTION: QR Code Generator Script
 * Description: This script generates and outputs a QR code image based on URL parameters.
 *
 * This script requires the 'phpqrcode' library. It takes 'text' and 'size'
 * as GET parameters to customize the QR code's content and size. It
 * checks for the presence of the PHP GD library and outputs a PNG image
 * of the QR code directly to the browser.
 *
 * @param string $_GET['text'] The text content to encode in the QR code (default: 'Hello World').
 * @param int    $_GET['size'] The size of the QR code module (default: 4).
 *
 * @return void Outputs a PNG image directly to the browser.
 */
require_once __DIR__ . '/phpqrcode/qrlib.php';

$text = isset($_GET['text']) ? htmlspecialchars($_GET['text']) : 'Hello World';
$size = isset($_GET['size']) ? intval($_GET['size']) : 4;

if (!function_exists('imagepng')) {
    header('Content-Type: text/plain');
    echo "Error: PHP GD library is not installed or enabled.";
    exit;
}

header('Content-Type: image/png');
QRcode::png($text, false, QR_ECLEVEL_L, $size, 2);
exit;
