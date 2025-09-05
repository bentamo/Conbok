<?php
/**
 * ============================================================
 * 📍 Location Picker Shortcode
 * Usage: [location-picker]
 * ============================================================
 */

add_shortcode('location-picker', function ($atts = []) {
    $a = shortcode_atts([
        'label'    => 'Set Location / Address',
        'name'     => 'location',
        'required' => 'false',
        'default'  => '',
        'class'    => '',
        'placeholder' => 'Enter a location...',
    ], $atts, 'location-picker');

    $uid = uniqid('loc-');
    $required = filter_var($a['required'], FILTER_VALIDATE_BOOLEAN);

    ob_start(); ?>
    <div id="<?php echo esc_attr($uid); ?>" class="location-wrap <?php echo esc_attr($a['class']); ?>">
        <label for="<?php echo esc_attr($uid); ?>-input"><?php echo esc_html($a['label']); ?></label>
        <input type="text"
            id="<?php echo esc_attr($uid); ?>-input"
            name="<?php echo esc_attr($a['name']); ?>"
            value="<?php echo esc_attr($a['default']); ?>"
            placeholder="<?php echo esc_attr($a['placeholder']); ?>"
            <?php echo $required ? 'required' : ''; ?>
        />
    </div>

    <style>
    .location-wrap { display: grid; gap: .25rem; }
    .location-wrap input[type="text"] {
        width: 100%;
        padding: .5rem;
        border: 1px solid #ccc;
        border-radius: .375rem;
    }
    </style>
    <?php
    return ob_get_clean();
});
