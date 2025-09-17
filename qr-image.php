<?php
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
