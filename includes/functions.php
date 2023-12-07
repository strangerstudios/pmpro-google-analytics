<?php
/**
 * All general functions goes in this file.
 */
function pmproga4_load_script() {

    // Only run this if PMPro is installed.
    if ( ! defined( 'PMPRO_VERSION' ) ) {
        return;
    }

    extract( $pmproga4_settings = get_option( 'pmproga4_settings',
        array(
            'measurement_id'       => '',
            'dont_track_admins' => '',
            'track_levels'      => array()
        )
    ) );

    /**
     * Determines whether to halt tracking based on specific conditions.
     *
     * This filter provides an opportunity for developers to stop tracking for specific 
     * scenarios, such as based on user ID, post, custom roles, etc. If the filter returns
     * `true`, tracking will be halted.
     *
     * @since 1.0
     *
     * @param bool $stop_tracking Default value is `false`. If set to `true` by any filter, tracking will be halted.
     *
     * @return void
     */
    if ( apply_filters( 'pmproga4_dont_track', false ) ) {
        return;
    }

    // No measurement ID found, let's bail.
    if ( empty( $measurement_id ) ) {
        return;
    }

    // Don't track admins if the option is set.
    if ( ! empty( $dont_track_admins ) && current_user_can( 'manage_options' ) ) {
        return;
    }

    /**
     * Filters the attributes applied to the Google Analytics script tag.
     *
     * Allows developers to customize or add specific attributes to the Google Analytics script tag
     * for enhanced control or additional features.
     *
     * @since 1.0
     *
     * @param string $script_atts Default value is an empty string. Contains attributes for the GA script tag.
     *
     * @return string Modified attributes for the GA script tag.
     */
    $script_atts = apply_filters( 'pmproga4_script_atts', '' );

    // Set the custom dimensions array from the helper function.
    $custom_dimensions = pmproga4_custom_dimensions();

    // Set the user properties array from the helper function.
    $user_properties = pmproga4_user_properties();
    ?>
    <!-- Paid Memberships Pro - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $measurement_id ); ?>"></script>
    <script <?php echo esc_attr($script_atts); ?>>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());
	        gtag('config', 
            '<?php echo esc_attr( $measurement_id ); ?>',
            {
                'currency': '<?php echo get_option( "pmpro_currency" ); ?>',
                <?php if ( is_user_logged_in() ) { ?>
                'user_id': '<?php echo get_current_user_id(); ?>',
                <?php } ?>
                <?php if ( ! empty( $custom_dimensions ) ) { 
                    foreach ( $custom_dimensions as $key => $value ) { ?>
                '<?php echo esc_attr( $key ); ?>': '<?php echo esc_attr( $value ); ?>',
                <?php }
                } ?>
                <?php if ( ! empty( $user_properties ) ) { ?>
                    'user_properties': {
                    <?php foreach ( $user_properties as $key => $value ) { ?>
                    '<?php echo esc_attr( $key ); ?>': '<?php echo esc_attr( $value ); ?>',
                    <?php } ?>
                    }
                <?php } ?>
            }
            );
		</script>
    <?php

    // Load all helper functions for ecommerce events which determine whether to load or not and run the <scripts>.
    pmproga4_view_item_event( $track_levels ); // Levels page
    pmproga4_checkout_events(); // Checkout page includes begin_checkout
    pmproga4_purchase_event(); // Confirmation page, confirmed checkout.

}
add_action( 'wp_head', 'pmproga4_load_script' );

/**
 * Function for view_item event.
 * Runs when post_content contains a levels shortcode or block.
 */
function pmproga4_view_item_event( $track_levels = null ) {
    global $pmpro_pages;

    /**
     * Determines whether to track the 'view item' event based on specific conditions.
     *
     * This filter allows developers to control when the 'view item' event should be 
     * tracked. By default, it tracks the event when the current page matches the PMPro levels page.
     *
     * @since 1.0
     *
     * @param bool $track_view_item_event Default value is determined by whether the current page is the PMPro levels page.
     *
     * @return bool Modified flag for whether to track the 'view item' event.
     */
    $track_view_item_event = apply_filters( 'pmproga4_track_view_item_event', is_page( $pmpro_pages['levels'] ) );

    // Return if we should not track this event.
    if ( empty( $track_view_item_event ) ) {
        return;
    }

    // Make sure $pmpro_all_levels has all levels.
    if ( ! isset( $pmpro_all_levels ) ) {
        $pmpro_all_levels = pmpro_getAllLevels( false, true );
    }

    /**
     * Filters the membership levels to be tracked for analytics purposes.
     *
     * Allows developers to specify or modify which membership levels should be 
     * tracked by the Google Analytics integration.
     *
     * @since 1.0
     *
     * @param array $track_levels Default array of membership level IDs to track from plugin settings.
     *
     * @return array Modified array of membership level IDs for tracking.
     */
    $our_levels = apply_filters( 'pmproga4_track_level_ids', $track_levels );

    // Get all available level IDs if no levels are passed to specifically track.
    if ( empty( $our_levels ) ) {
        $our_levels = wp_list_pluck( $pmpro_all_levels, 'id' );
    }

    // Set up the array of levels to track.
    $our_levels_to_track = array();

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

        // Build an array of product data.
        $gtag_config_ecommerce_products = array();
        $gtag_config_ecommerce_products['item_id'] = 'pmpro-' . $pmpro_level->id;
        $gtag_config_ecommerce_products['item_name'] = $pmpro_level->name;
        $gtag_config_ecommerce_products['affiliation'] = get_bloginfo( 'name' );
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
            '<?php echo $gtag_config_event_push["event"]; ?>',  // Event type.
            {
                value: <?php echo $gtag_config_event_push['value']; ?>, // Value (initial payment)
                items: [<?php echo json_encode( $gtag_config_event_push['items'] ); ?>] // Product data.
            }
        ); // End of gtag method.
    <?php }  // end of foreach ?>
</script>
    <?php
} // End of view_item event.

/**
 * Function for add_to_cart and begin_checkout events.
 * Runs on the Paid Memberships Pro checkout page (page load).
 */
function pmproga4_checkout_events() {
    global $pmpro_level;

    // Only run this on the checkout page.
    if ( ! pmpro_is_checkout() ) {
        return;
    }

    // Build an array of product data.
    $gtag_config_ecommerce_products = array();
    $gtag_config_ecommerce_products['item_id'] = $pmpro_level->id;
    $gtag_config_ecommerce_products['item_name'] = $pmpro_level->name;
    $gtag_config_ecommerce_products['affiliation'] = get_bloginfo( 'name' );
    $gtag_config_ecommerce_products['index'] = 0;
    $gtag_config_ecommerce_products['price'] = $pmpro_level->initial_payment;
    $gtag_config_ecommerce_products['quantity'] = 1;

    // Add the product data to the ecommerce data.
    $gtag_config_event_push['event'] = 'add_to_cart';
    $gtag_config_event_push['value'] = $pmpro_level->initial_payment;
    $gtag_config_event_push['items'] = $gtag_config_ecommerce_products;

    ?>
    <script>
    jQuery(document).ready(function() {

        // Used later to trigger the begin_checkout event.
        var interacted = 0;
        
        gtag( 'event', 
            '<?php echo $gtag_config_event_push["event"]; ?>',  // Event type.
            {
                value: <?php echo $gtag_config_event_push['value']; ?>, // Value (initial payment).
                items: [<?php echo json_encode( $gtag_config_event_push['items'] ); ?>] // Product data.
            }
        ); // End of gtag method.
            
        // User has either clicked or pressed any key, assume they've started the checkout process.
        jQuery(document).on( "click keypress", function () {                 
            
        // Run this only once.
        if ( interacted > 0 ) {
            return;
        }

        // Send the begin_checkout event once the key has been pressed once.
        gtag( 'event', 
            'begin_checkout',  // Event type.
            {
                value: <?php echo $gtag_config_event_push['value']; ?>, // Value (initial payment).
                items: [<?php echo json_encode( $gtag_config_event_push['items'] ); ?>] // Product data.
            }
            ); // End of gtag method.
        
            // Use local storage to confirm the user has interacted. Cross referenced in the purchase event.
            localStorage.setItem( 'pmproga4_purchased_level', '<?php echo $pmpro_level->id; ?>' );

        interacted++;
        });
    });
    </script>
    <?php
}

/**
 * Function for purchase event.
 * This loads on the confirmation page and only if there's local storage.
 */
function pmproga4_purchase_event() {
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
        $gtag_config_ecommerce_products['item_id'] = $pmpro_invoice->membership_level->id;
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
            if ( localStorage.getItem( 'pmproga4_purchased_level' ) !== '<?php echo $pmpro_invoice->membership_level->id; ?>' ) {
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
 *
 * @return array $gtag_config_custom_dimensions Custom dimensions as a key=>value pair.
 */
function pmproga4_custom_dimensions() {
    /**
     * Sets up the Custom Dimension data for Google Analytics.
     *
     * This function allows for the customization of default custom dimensions
     * by applying the 'pmproga4_default_custom_dimension' filter.
     *
     * @since 1.0
     *
     * @param array $gtag_config_custom_dimensions An array of custom dimensions as key=>value pairs.
     *
     * @return array Filtered custom dimensions.
     */
	$gtag_config_custom_dimensions = apply_filters( 'pmproga4_default_custom_dimension', array( 
        'post_type' => '', 
        'author' => '', 
        'category' => ''
     ) );

    // Track 'post_type' dimension.
    if ( isset( $gtag_config_custom_dimensions['post_type'] ) ) {
        $post_type = '';
        if ( is_singular() ) {
            $post_type = get_post_type( get_the_ID() );
        }
        if ( ! empty( $post_type ) ) {
            $gtag_config_custom_dimensions['post_type'] = esc_html( $post_type );
        } else {
            unset( $gtag_config_custom_dimensions['post_type'] );
        }
    }

    // Track 'author' dimension.
    if ( isset( $gtag_config_custom_dimensions['author'] ) ) {
        $author = '';
        if ( is_singular() ) {
            if ( have_posts() ) {
                while ( have_posts() ) {
                    the_post();
                    $author = get_the_author_meta( 'display_name' );
                }
            }
        }
        if ( ! empty( $author ) ) {
            $gtag_config_custom_dimensions['author'] = esc_html( $author );
        } else {
            unset( $gtag_config_custom_dimensions['author'] );
        }
    }

    // Track post category, if applicable.
    if ( isset( $gtag_config_custom_dimensions['category'] ) ) {
        $category = '';
        if ( is_singular( 'post' ) ) {
            $categories = get_the_category( get_the_ID() );
            if ( $categories ) {
                foreach ( $categories as $category ) {
                    $category_names[] = $category->slug;
                }
                $category =  implode( ',', $category_names );
            }
        }

        if ( ! empty( $category ) ) {
            $gtag_config_custom_dimensions['category'] = esc_html( $category );
        } else {
            unset( $gtag_config_custom_dimensions['category'] );
        }
    }

    /**
     * Filters the custom dimensions to allow developers to add or remove custom dimensions.
     *
     * @since 1.0
     *
     * @param array $gtag_config_custom_dimensions Custom dimensions as a key=>value pair.
     * @return array Filtered custom dimensions as a key=>value pair.
     */
    return apply_filters( 'pmproga4_custom_dimensions', $gtag_config_custom_dimensions );
}

/**
 * User Properties to track.
 *
 * @return array $gtag_config_user_properties User properties as a key=>value pair.
 */

function pmproga4_user_properties() {
    /**
     * Sets up the User Properties data for Google Analytics.
     *
     * This function allows for the customization of default user properties
     * by applying the 'pmproga4_default_user_properties' filter.
     *
     * @since 1.0
     *
     * @param array $gtag_config_user_properties An array of user properties as key=>value pairs.
     *
     * @return array Filtered user properties.
     */
	$gtag_config_user_properties = apply_filters( 'pmproga4_default_user_properties', array(
        'membership_level' => ''
     ) );

    // Track members membership level.
    if ( isset( $gtag_config_user_properties['membership_level'] ) ) {
        $membership_level = '';
        // Get the value to track for the current user.
        if ( is_user_logged_in() && function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
            // Get the current user's membership level ID.
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
            $gtag_config_user_properties['membership_level'] = esc_html( $membership_level );
        }
    }

   /**
     * Filters the user properties to allow developers to add or remove properties.
     *
     * @since 1.0
     *
     * @param array $gtag_config_user_properties User properties as a key=>value pair.
     * @return array Filtered user properties as a key=>value pair.
     */
    return apply_filters( 'pmproga4_user_properties', $gtag_config_user_properties );
}
