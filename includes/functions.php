<?php
/**
 * All general functions goes in this file.
 */
function pmproga_load_script() {
    extract( $pmproga_settings = get_option( 'pmproga_settings',
        array(
            'tracking_id'       => '',
            'dont_track_admins' => '',
            'track_levels'      => array()
        )
    ) );

    // Filter to stop tracking for whatever reason needed. (user ID, post, etc or custom roles etc)
    if ( apply_filters( 'pmproga_dont_track', false ) ) {
        return;
    }

    // No tracking ID found, lets bail.
    if ( empty( $tracking_id ) ) {
        return;
    }

    // Don't track admins if the option is set, added in a filter to allow further customizations and return true/false beyond this.
    if ( ! empty( $dont_track_admins ) && current_user_can( 'manage_options' ) ) {
        return;
    }

    $script_atts_ext = apply_filters('pmpro_ga_script_atts_ext', ' async');
    $script_atts = apply_filters( 'pmproga_script_atts', '' );

    $custom_dimensions = pmproga_custom_dimensions();
    ?>
    <!-- Paid Memberships Pro - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $tracking_id ); ?>"></script>
    <script <?php echo esc_attr($script_atts); ?>>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());
	        gtag('config', 
            '<?php echo esc_attr( $tracking_id ); ?>', 
            { 
                'currency': '<?php echo get_option( "pmpro_currency" ); ?>',
                <?php if ( ! empty( $custom_dimensions ) ) { 
                    foreach ( $custom_dimensions as $key => $value ) { ?>
                '<?php echo esc_attr( $key ); ?>': '<?php echo esc_attr( $value ); ?>',
                <?php }
                 } ?>
            }
            );
		</script>
    <?php

    // Load all helper functions which determine whether to load or not and runs the <scripts>
    pmproga_view_item_event( $track_levels ); // Levels page
    pmproga_checkout_events(); // Checkout page includes begin_checkout
    pmproga_purchase_event(); // Confirmation page, confirmed checkout.

}
add_action( 'wp_head', 'pmproga_load_script' );

/**
 * Function to build the view_item event. 
 * Runs on the level select page.
 */
function pmproga_view_item_event( $track_levels = null ) {
    global $pmpro_pages, $post, $pmpro_level;

    // Only run this script on the levels page and no where else.
    if ( ! is_page( $pmpro_pages['levels'] ) || strpos( $post->post_content, 'pmpro_levels' ) == false ) {
        return;
    }

    // Make sure pmpro_levels has all levels.
    if ( ! isset( $pmpro_all_levels ) ) {
        $pmpro_all_levels = pmpro_getAllLevels( false, true );
    }

    // Get our specific levels for the levels page.
    $our_levels_to_track = array();
    $our_levels = apply_filters( 'pmproga_track_level_ids', $track_levels );

    // Get all available level ID's if no levels are passed to specifically tra
    if ( empty( $our_levels ) ) {
        $our_levels = wp_list_pluck( $pmpro_all_levels, 'id' );
    }

    foreach ( $our_levels as $level_id ) {
        foreach ( $pmpro_all_levels as $level ) {
            if ( $level->id == $level_id && true == $level->allow_signups ) {
                $our_levels_to_track[$level->id] = $level;
                break;
            }
        }
    }

    // Build an array of events.
    $gtag_config_events_push = array();

    // Create a unique event per level viewed.
    foreach ( $our_levels_to_track as $pmpro_level ) {

        // Build an array of Product Data.
        $gtag_config_ecommerce_products = array();
        $gtag_config_ecommerce_products['item_id'] = 'pmpro-' . $pmpro_level->id;
        $gtag_config_ecommerce_products['item_name'] = $pmpro_level->name;
        $gtag_config_ecommerce_products['affiliation'] = get_bloginfo( 'name' );
        $gtag_config_ecommerce_products['index'] = 0;
        $gtag_config_ecommerce_products['quantity'] = 1;


        // Add the product data to the ecommerce data.
        $gtag_config_event_push['event'] = 'view_item';
        $gtag_config_event_push['value'] = $pmpro_level->initial_payment;
        $gtag_config_event_push['items'] = $gtag_config_ecommerce_products;

        // Add this complete event to the array of events.
        $gtag_config_events_push[] = $gtag_config_event_push;
    } 
    ?>
<script>
    <?php foreach( $gtag_config_events_push as $gtag_config_event_push ) { ?>
        gtag( 'event', 
            '<?php echo $gtag_config_event_push["event"]; ?>',  // event type.
            {
                value: <?php echo $gtag_config_event_push['value']; ?>, // Value (amount/price)
                items: [<?php echo json_encode( $gtag_config_event_push['items'] ); ?>] // Product data.
            }
        ); // End of gtag method.
    <?php }  // end of foreach ?>
</script>
    <?php
} // End of view_item event.

/**
 * Function for add_to_cart event.
 * Runs on the Paid Memberships Pro checkout page (page load).
 */
function pmproga_checkout_events() {
    global $pmpro_level;
    // Only run this on the checkout page.
    if ( ! pmpro_is_checkout() ) {
        return;
    }
		
    // Build an array of Product Data.
    $gtag_config_ecommerce_products = array();
    $gtag_config_ecommerce_products['item_id'] = 'pmpro-' . $pmpro_level->id;
    $gtag_config_ecommerce_products['item_name'] = $pmpro_level->name;
    $gtag_config_ecommerce_products['affiliation'] = get_bloginfo( 'name' );
    $gtag_config_ecommerce_products['index'] = 0;
    $gtag_config_ecommerce_products['quantity'] = 1;

    // Add the product data to the ecommerce data.
    $gtag_config_event_push['event'] = 'add_to_cart';
    $gtag_config_event_push['value'] = $pmpro_level->initial_payment;
    $gtag_config_event_push['items'] = $gtag_config_ecommerce_products;

    ?>
    <script>
    jQuery(document).ready(function() {

        // Used later to interact with the
        var interacted = 0;
        
        gtag( 'event', 
            '<?php echo $gtag_config_event_push["event"]; ?>',  // event type.
            {
                value: <?php echo $gtag_config_event_push['value']; ?>, // Value (amount/price)
                items: [<?php echo json_encode( $gtag_config_event_push['items'] ); ?>] // Product data.
            }
        ); // End of gtag method.
            
        // User has either clicked or pressed any key, assume they've started the checkout process.
        jQuery(document).on( "click keypress", function () {                 
            
        // Run this only once.
        if ( interacted > 0 ) {
            return;
        }

        // Begin checkout once the key has been pressed once.
        gtag( 'event', 
            'begin_checkout',  // event type.
            {
                value: <?php echo $gtag_config_event_push['value']; ?>, // Value (amount/price)
                items: [<?php echo json_encode( $gtag_config_event_push['items'] ); ?>] // Product data.
            }
            ); // End of gtag method.
        
            // local storage to confirm the user has interacted and cross referenced in the purchase event
            localStorage.setItem( 'pmproga_purchased_level', '<?php echo $pmpro_level->id; ?>' );

        interacted++;
        });
    });
    </script>
        <?php
}

/**
 * Add purchase event for GA.
 * This loads on the confirmation page and only if there's local storage.
 */
function pmproga_purchase_event() {
    global $pmpro_pages, $pmpro_invoice, $current_user;

    // Only run on the confirmation page.
    if ( ! is_page( $pmpro_pages['confirmation'] ) ) {
        return;
    }


    // User completed a free checkout. Get their last invoice for the data layer.
    if ( empty( $pmpro_invoice ) && pmpro_isLevelFree( $current_user->membership_level ) ) {
        $pmpro_invoice = new MemberOrder();
        $pmpro_invoice->getLastMemberOrder( $current_user->ID, apply_filters( 'pmpro_confirmation_order_status', array( 'success', 'pending', 'token' ) ) );
    }

    // Set the ecommerce dataLayer script if the order ID matches the session variable.
    if ( ! empty( $pmpro_invoice ) && ! empty( $pmpro_invoice->id ) ) {
        $pmpro_invoice->getMembershipLevel();

        // Set the ecommerce dataLayer script.
        $gtag_config_ecommerce_data = array();
        $gtag_config_ecommerce_data['transaction_id'] = $pmpro_invoice->code;
        $gtag_config_ecommerce_data['value'] = $pmpro_invoice->membership_level->initial_payment;
        
        if ( ! empty( $pmpro_invoice->tax ) ) {
            $gtag_config_ecommerce_data['tax'] = $pmpro_invoice->tax;
        } else {
            $gtag_config_ecommerce_data['tax'] = 0;
        }

        if ( $pmpro_invoice->getDiscountCode() ) {
            $gtag_config_ecommerce_data['coupon'] = $pmpro_invoice->discount_code->code;
        } else {
            $gtag_config_ecommerce_data['coupon'] = '';
        }

        // Build an array of product data.
        $gtag_config_ecommerce_products = array();
        $gtag_config_ecommerce_products['item_id'] = 'pmpro-' . $pmpro_invoice->membership_level->id;
        $gtag_config_ecommerce_products['item_name'] = $pmpro_invoice->membership_level->name;
        $gtag_config_ecommerce_products['affiliation'] = get_bloginfo( 'name' );
        if ( $pmpro_invoice->getDiscountCode() ) {
            $gtag_config_ecommerce_products['coupon'] = $pmpro_invoice->discount_code->code;
        }
        $gtag_config_ecommerce_products['index'] = 0;
        $gtag_config_ecommerce_products['price'] = $pmpro_invoice->membership_level->initial_payment;
        $gtag_config_ecommerce_products['quantity'] = 1;
        ?>
        <script>
            jQuery(document).ready(function(){
            // Only run this if the user has interacted with the checkout page within a single session.
            if ( localStorage.getItem( 'pmproga_purchased_level' ) !== '<?php echo $pmpro_invoice->membership_level->id; ?>' ) {
                return;
            }

            gtag( 'event', 'purchase', {
                transaction_id: '<?php echo $gtag_config_ecommerce_data['transaction_id']; ?>',
                value: <?php echo $gtag_config_ecommerce_data['value']; ?>,
               <?php if ( ! empty( $gtag_config_ecommerce_data['tax'] ) ) { ?>
                tax: <?php echo $gtag_config_ecommerce_data['tax']; ?>,
                <?php } ?>
                <?php if( ! empty( $gtag_config_ecommerce_data['coupon'] ) ) { ?>
                coupon: '<?php echo $gtag_config_ecommerce_data['coupon']; ?>',
                <?php } ?>
                items: [ <?php echo json_encode( $gtag_config_ecommerce_products ); ?> ]
            });
            });

        </script>
        <?php

    }
}

/**
 * Custom Dimensions that _we_ need.
 * @returns array $custom_dimensions The custom dimensions we want as a key=>value pair.
 */
function pmproga_custom_dimensions() {
    // Set up the Custom Dimension data.
	$gtag_config_custom_dimensions = apply_filters( 'pmproga_default_custom_dimension', array( 
        'post_type' => '', 
        'author' => '', 
        'category' => '', 
        'membership_level' => ''
     ) );


    // Track 'post_type' dimensions.
    if ( isset( $gtag_config_custom_dimensions['post_type'] ) ) {
        $post_type = '';
        if ( is_singular() ) {
            $post_type = get_post_type( get_the_ID() );
        }
        if ( ! empty( $post_type ) ) {
            $gtag_config_custom_dimensions['post_type'] = esc_html( $post_type );
        }
    }

    // Track 'author' data  
    if ( isset( $gtag_config_custom_dimensions['author'] ) ) {
        $author = '';
        if ( is_singular() ) {
            if ( have_posts() ) {
                while ( have_posts() ) {
                    the_post();
                }
            }
            $firstname = get_the_author_meta( 'user_firstname' );
            $lastname  = get_the_author_meta( 'user_lastname' );
            if ( ! empty( $firstname ) || ! empty( $lastname ) ) {
                    $author = trim( $firstname . ' ' . $lastname );
            } else {
                $author = 'user-' . get_the_author_meta( 'ID' );
            }
        }
        if ( ! empty( $author ) ) {
            $gtag_config_custom_dimensions['author'] = esc_html( $author );
        }
    }

    // Track post category if applicable.
    if ( isset( $gtag_config_custom_dimensions['category'] ) ) {
        $category = '';
        if ( is_single() ) {
            $categories = get_the_category( get_the_ID() );
            if ( $categories ) {
                foreach ( $categories as $category ) {
                    $category_names[] = $category->slug;
                }
                $category =  implode( ',', $category_names );
            }

            // If the category is still empty, let's remove it.
            if ( empty( $category ) ) {
                unset( $gtag_config_custom_dimensions['category'] );
            }

        }
        if ( ! empty( $category ) ) {
            $gtag_config_custom_dimensions['category'] = esc_html( $category );
        }
    }

    // Track members membership level.
    if ( isset( $gtag_config_custom_dimensions['membership_level'] ) ) {
        $membership_level = '';
        // Get the value to track for the current user.
        if ( is_user_logged_in() && function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
            // Get the current users's membership level ID. 
            $current_user_membership_level = pmpro_getMembershipLevelForUser( get_current_user_id() );
            if ( empty( $current_user_membership_level ) ) {
                // Set the tracked membership level ID to no_level.
                $membership_level = 'no_level';			
            } else {
                $membership_level = $current_user_membership_level->ID;
            }
        } else {
            // Set the tracked membership level ID to no_level.
            $membership_level = 'no_level';
        }
        if ( ! empty( $membership_level ) ) {
            $gtag_config_custom_dimensions['membership_level'] = esc_html( $membership_level );
        }
    }

    /**
     * Filter the custom dimensions we want to track and allow developers to add or remove custom dimensions.
     * @param array $gtag_config_custom_dimensions The custom dimensions we want as a key=>value pair.
     * @return array $gtag_config_custom_dimensions The custom dimensions we want as a key=>value pair.
     */
    return apply_filters( 'pmproga_custom_dimensions', $gtag_config_custom_dimensions );
}