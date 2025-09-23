<?php
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

function dynamic_qrcode_shortcode($atts){
    $atts = shortcode_atts([
        'data' => '',
    ], $atts);

    $data = trim($atts['data']);

    if(empty($data)){
        return '<p><strong>Error:</strong> No data provided for QR code.</p>';
    }

    // configure QR code options
    $options = new QROptions([
        'outputType' => QRCode::OUTPUT_IMAGE_PNG,
        'scale'      => 5, // size multiplier
    ]);

    // render QR code as base64 data URI
    $qrcode = new QRCode($options);
    $imageData = $qrcode->render($data);

    // output <img> tag
    return '<img src="'.$imageData.'" alt="QR Code" />';
}

add_shortcode('qrcode', 'dynamic_qrcode_shortcode');
