<?php
// Register a custom post type called 'Event'
function ep_register_event_cpt() {
    $args = [
        'labels' => [
            'name'          => 'Events',                    // Plural name in admin menu
            'singular_name' => 'Event',                     // Singular name
        ],
        'public'      => true,                              // Make the post type publicly accessible
        'has_archive' => true,                              // Enable archive page
        'menu_icon'   => 'dashicons-calendar',              // Icon in the admin menu
        'supports'    => ['title', 'editor', 'thumbnail'],  // Features supported
        'rewrite'     => ['slug' => 'events'],              // URL slug
    ];
    register_post_type('event', $args);
}
add_action('init', 'ep_register_event_cpt');

// Automatically delete the featured image when an Event post is deleted
function ep_delete_event_image($post_id) {
    // Only act on 'event' post type
    if (get_post_type($post_id) !== 'event') return;

    // Get the ID of the post thumbnail
    $thumbnail_id = get_post_thumbnail_id($post_id);

    // Delete the attachment if it exists
    if ($thumbnail_id) wp_delete_attachment($thumbnail_id, true);
}
add_action('before_delete_post', 'ep_delete_event_image');
