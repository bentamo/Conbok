<?php

/**
 * =========================================================
 * PART 1: REGISTER THE 'EVENT' CUSTOM POST TYPE
 * =========================================================
 *
 * This function creates the 'Event' post type and makes it available
 * in your WordPress admin dashboard.
 *
 * NOTE: After adding this code, go to Settings > Permalinks and click 'Save Changes'
 * to ensure the new post type is correctly registered.
 */
function create_event_post_type() {
    $labels = array(
        'name'                  => _x( 'Events', 'Post type general name', 'textdomain' ),
        'singular_name'         => _x( 'Event', 'Post type singular name', 'textdomain' ),
        'menu_name'             => _x( 'Events', 'Admin Menu text', 'textdomain' ),
        'name_admin_bar'        => _x( 'Event', 'Add New on Toolbar', 'textdomain' ),
        'add_new'               => __( 'Add New', 'textdomain' ),
        'add_new_item'          => __( 'Add New Event', 'textdomain' ),
        'new_item'              => __( 'New Event', 'textdomain' ),
        'edit_item'             => __( 'Edit Event', 'textdomain' ),
        'view_item'             => __( 'View Event', 'textdomain' ),
        'all_items'             => __( 'All Events', 'textdomain' ),
        'search_items'          => __( 'Search Events', 'textdomain' ),
        'parent_item_colon'     => __( 'Parent Events:', 'textdomain' ),
        'not_found'             => __( 'No events found.', 'textdomain' ),
        'not_found_in_trash'    => __( 'No events found in Trash.', 'textdomain' ),
        'featured_image'        => _x( 'Event Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'textdomain' ),
        'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
        'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
        'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
        'archives'              => _x( 'Event archives', 'The post type archive label used in nav menus. Title-tag for your site when viewing the archive. Added in 4.4', 'textdomain' ),
    );
    
    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'events' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 5,
        'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
    );
    
    register_post_type( 'event', $args );
}
add_action( 'init', 'create_event_post_type' );


/**
 * =========================================================
 * PART 2: THE MAIN SHORTCODE FUNCTION
 * =========================================================
 *
 * This function fetches events, sorts them into upcoming/past, and generates the HTML.
 * It assumes you have a custom field named 'event_start_date' for each event.
 */
function my_events_dashboard_shortcode() {
    
    // Check if the 'show_seeded_events' parameter is in the URL.
    // This allows the "Create Event" button to trigger the display.
    $show_seeded_events = isset($_GET['show_seeded_events']);

    // --- SEED DATA ---
    // Hardcoded data for demonstration.
    // We will use this only when the 'show_seeded_events' parameter is present.
    $upcoming_events_data = [
        [
            'title' => 'Upcoming Seed Event 1',
            'date_time' => 'Dec 15, 2025 • 03:00 PM',
            'image_url' => 'https://via.placeholder.com/600x400.png?text=Upcoming+Event',
            'link' => '#'
        ],
    ];

    $past_events_data = [
        [
            'title' => 'Past Seed Event 1',
            'date_time' => 'Aug 20, 2025 • 05:00 PM',
            'image_url' => 'https://via.placeholder.com/600x400.png?text=Past+Event',
            'link' => '#'
        ],
    ];
    // --- END SEED DATA ---

    ob_start();

    // If the URL parameter is NOT set, show the "No Events" message.
    if (!$show_seeded_events) {
        ?>
        <div class="wp-block-uagb-container uagb-block-201be420 alignfull uagb-is-root-container">
            <div class="uagb-container-inner-blocks-wrap">
                <div class="wp-block-uagb-info-box uagb-block-e134fcf3 uagb-infobox__content-wrap  uagb-infobox-icon-above-title uagb-infobox-image-valign-top">
                    <div class="uagb-ifb-content">
                        <div class="uagb-ifb-icon-wrap">
                            <svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256zM371.8 211.8C382.7 200.9 382.7 183.1 371.8 172.2C360.9 161.3 343.1 161.3 332.2 172.2L224 280.4L179.8 236.2C168.9 225.3 151.1 225.3 140.2 236.2C129.3 247.1 129.3 264.9 140.2 275.8L204.2 339.8C215.1 350.7 232.9 350.7 243.8 339.8L371.8 211.8z"></path></svg>
                        </div>
                        <div class="uagb-ifb-title-wrap">
                            <h3 class="uagb-ifb-title">No Events Yet!</h3>
                        </div>
                        <p class="uagb-ifb-desc">Every great gathering starts with one step. Create your first event today.</p>
                    </div>
                </div>
                <div class="wp-block-uagb-buttons uagb-buttons__outer-wrap uagb-btn__small-btn uagb-btn-tablet__default-btn uagb-btn-mobile__default-btn uagb-block-eee0cead">
                    <div class="uagb-buttons__wrap uagb-buttons-layout-wrap ">
                        <div class="wp-block-uagb-buttons-child uagb-buttons__outer-wrap uagb-block-3536decf wp-block-button">
                            <div class="uagb-button__wrapper">
                                <a class="uagb-buttons-repeater wp-block-button__link" aria-label="" href="?show_seeded_events=true" rel="follow noopener" target="_self" role="button">
                                    <span class="uagb-button__icon uagb-button__icon-position-before">
                                        <svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 448 512" aria-hidden="true" focussable="false"><path d="M432 256c0 17.69-14.33 32.01-32 32.01H256v144c0 17.69-14.33 31.99-32 31.99s-32-14.3-32-31.99v-144H48c-17.67 0-32-14.32-32-32.01s14.33-31.99 32-31.99H192v-144c0-17.69 14.33-32.01 32-32.01s32 14.32 32 32.01v144h144C417.7 224 432 238.3 432 256z"></path></svg>
                                    </span>
                                    <div class="uagb-button__link">Create Event</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // If the URL parameter IS set, show the tabbed interface with seeded data.
    ob_start();
    ?>
    <style>
        .tabs-header-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .tabs-header-wrap .uagb-buttons__outer-wrap {
            margin: 0 !important;
        }
    </style>

    <div class="wp-block-uagb-tabs uagb-block-e0fe4d2a uagb-tabs__wrap uagb-tabs__hstyle2-desktop uagb-tabs__hstyle2-tablet uagb-tabs__stack1-mobile" data-tab-active="0">
        <div class="tabs-header-wrap">
            <ul class="uagb-tabs__panel uagb-tabs__align-center" role="tablist">
                <li class="uagb-tab uagb-tabs__active" role="none">
                    <a href="#uagb-tabs__tab1" class="uagb-tabs-list uagb-tabs__icon-position-left" data-tab="0" role="tab">
                        <div>Upcoming Events</div>
                    </a>
                </li>
                <li class="uagb-tab" role="none">
                    <a href="#uagb-tabs__tab2" class="uagb-tabs-list uagb-tabs__icon-position-left" data-tab="1" role="tab">
                        <div>Past Events</div>
                    </a>
                </li>
            </ul>
            <div class="wp-block-uagb-buttons uagb-buttons__outer-wrap uagb-btn__small-btn uagb-btn-tablet__default-btn uagb-btn-mobile__default-btn uagb-block-eee0cead">
                <div class="uagb-buttons__wrap uagb-buttons-layout-wrap ">
                    <div class="wp-block-uagb-buttons-child uagb-buttons__outer-wrap uagb-block-3536decf wp-block-button">
                        <div class="uagb-button__wrapper">
                            <a class="uagb-buttons-repeater wp-block-button__link" aria-label="" href="#" rel="follow noopener" target="_self" role="button">
                                <span class="uagb-button__icon uagb-button__icon-position-before">
                                    <svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 448 512" aria-hidden="true" focussable="false"><path d="M432 256c0 17.69-14.33 32.01-32 32.01H256v144c0 17.69-14.33 31.99-32 31.99s-32-14.3-32-31.99v-144H48c-17.67 0-32-14.32-32-32.01s14.33-31.99 32-31.99H192v-144c0-17.69 14.33-32.01 32-32.01s32 14.32 32 32.01v144h144C417.7 224 432 238.3 432 256z"></path></svg>
                                </span>
                                <div class="uagb-button__link">Create Event</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="uagb-tabs__body-wrap">
            <div class="wp-block-uagb-tabs-child uagb-tabs__body-container uagb-inner-tab-0" aria-labelledby="uagb-tabs__tab1">
                <div class="wp-block-uagb-container uagb-layout-grid uagb-block-f0457227 alignfull uagb-is-root-container">
                    <div class="uagb-container-inner-blocks-wrap">
                        <?php foreach ($upcoming_events_data as $event) : ?>
                            <div class="wp-block-uagb-container uagb-block-449a9824">
                                <figure class="wp-block-image size-full has-custom-border">
                                    <img src="<?php echo esc_url($event['image_url']); ?>" alt="" class="wp-image-33" style="border-radius:15px;aspect-ratio:3/2;object-fit:cover"/>
                                </figure>
                                <div class="wp-block-uagb-container uagb-block-b9a0de21">
                                    <p class="has-text-align-center" style="margin-top:0;margin-bottom:0;font-size:13px"><?php echo esc_html($event['date_time']); ?></p>
                                    <p class="has-text-align-center" style="margin-top:0;margin-bottom:0;font-size:22px"><strong><?php echo esc_html($event['title']); ?></strong></p>
                                    <div class="wp-block-buttons">
                                        <div class="wp-block-button has-custom-width wp-block-button__width-75">
                                            <a class="wp-block-button__link wp-element-button" href="<?php echo esc_url($event['link']); ?>">Manage</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="wp-block-uagb-tabs-child uagb-tabs__body-container uagb-inner-tab-1" aria-labelledby="uagb-tabs__tab2" style="display: none;">
                <div class="wp-block-uagb-container uagb-layout-grid uagb-block-342b6343 alignfull uagb-is-root-container">
                    <div class="uagb-container-inner-blocks-wrap">
                        <?php foreach ($past_events_data as $event) : ?>
                            <div class="wp-block-uagb-container uagb-block-15d7d46e">
                                <figure class="wp-block-image size-full has-custom-border">
                                    <img src="<?php echo esc_url($event['image_url']); ?>" alt="" class="wp-image-33" style="border-radius:15px;aspect-ratio:3/2;object-fit:cover"/>
                                </figure>
                                <div class="wp-block-uagb-container uagb-block-0f2a31db">
                                    <p class="has-text-align-center" style="margin-top:0;margin-bottom:0;font-size:13px"><?php echo esc_html($event['date_time']); ?></p>
                                    <p class="has-text-align-center" style="margin-top:0;margin-bottom:0;font-size:22px"><strong><?php echo esc_html($event['title']); ?></strong></p>
                                    <div class="wp-block-buttons">
                                        <div class="wp-block-button has-custom-width wp-block-button__width-75">
                                            <a class="wp-block-button__link wp-element-button" href="<?php echo esc_url($event['link']); ?>">View</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        var tabsContainer = document.querySelector(".uagb-block-e0fe4d2a");
        if (tabsContainer) {
            var tabs = tabsContainer.querySelectorAll(".uagb-tab");
            var tabBodies = tabsContainer.querySelectorAll(".uagb-tabs__body-container");

            tabs.forEach(function(tab, index) {
                tab.addEventListener("click", function(event) {
                    event.preventDefault();
                    
                    tabs.forEach(function(t) {
                        t.classList.remove("uagb-tabs__active");
                    });
                    
                    tabBodies.forEach(function(body) {
                        body.style.display = "none";
                    });

                    tab.classList.add("uagb-tabs__active");
                    tabBodies[index].style.display = "block";
                });
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode( 'my_events_dashboard', 'my_events_dashboard_shortcode' );