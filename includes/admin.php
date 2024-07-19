<?php
/**
 * All admin (WordPress dashboard) related functions go here.
 */

/**
 * Show a message if Paid Memberships Pro is inactive or not installed.
 */
function pmproga4_required_installed() {

    // The required plugins for this Add On to work.
    $required_plugins = array(
        'paid-memberships-pro' => __( 'Paid Memberships Pro', 'pmpro-google-analytics' ),
    );

    // Check if the required plugins are installed.
    $missing_plugins = array();
    foreach ( $required_plugins as $plugin => $name ) {
        if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin ) ) {
            $missing_plugins[$plugin] = $name;
        }
    }

    // If there are missing plugins, show a notice.
    if ( ! empty( $missing_plugins ) ) {
        // Build install links here.
        $install_plugins = array();
        foreach( $missing_plugins as $path => $name ) {
            $install_plugins[] = sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $path ), 'install-plugin_' . $path ) ), esc_html( $name ) );
        }

        // Show notice with install_plugin links.
        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            sprintf(
                esc_html__( 'The following plugin(s) are required for the %1$s plugin to work: %2$s', 'pmpro-google-analytics' ),
                esc_html__( 'Google Analytics Integration', 'pmpro-google-analytics' ),
                implode( ', ', $install_plugins ) // $install_plugins was escaped when built.
            )
        );

        return; // Bail here, so we only show one notice at a time.
    }

    // Check if the required plugins are active and show a notice with activation links if they are not
    $inactive_plugins = array();
    foreach ( $required_plugins as $plugin => $name ) {
        $full_path = $plugin . '/' . $plugin . '.php';
        if ( ! is_plugin_active( $full_path ) ) {
            $inactive_plugins[$plugin] = $name;
        }
    }

    // If there are inactive plugins, show a notice.
    if ( ! empty( $inactive_plugins ) ) {
        // Build activate links here.
        $activate_plugins = array();
        foreach( $inactive_plugins as $path => $name ) {
            $full_path = $path . '/' . $path . '.php';
            $activate_plugins[] = sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . $full_path ), 'activate-plugin_' . $full_path ) ), esc_html( $name ) );
        }

        // Show notice with activate_plugin links.
        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            sprintf(
                esc_html__( 'The following plugin(s) are required for the %1$s plugin to work: %2$s', 'pmpro-google-analytics' ),
                esc_html__( 'Google Analytics Integration', 'pmpro-google-analytics' ),
                implode( ', ', $activate_plugins ) // $activate_plugins was escaped when built.
            )
        );

        return; // Bail here, so we only show one notice at a time.
    }
}
add_action( 'admin_notices', 'pmproga4_required_installed' );

/**
 * Show an admin notice to finish set up if there are no settings enabled.
 */
function pmproga4_show_setup_notice() {

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Only show this notice on certain pages.
    if ( ! isset( $_REQUEST['page'] ) ) {
        return;
    }

    // Only show this on the PMPro pages.
    if ( strpos( $_REQUEST['page'], 'pmpro' ) === false ) {
        return;
    }

    // Don't show on the actual settings page.
    if ( $_REQUEST['page'] === 'pmpro-google-analytics' ) {
        return;
    }

    $pmproga4_options = get_option( 'pmproga4_settings' );

    //Show admin notice if options are empty.
    if ( ! $pmproga4_options || empty( $pmproga4_options['measurement_id'] ) ) {
        ?>
        <div class="notice notice-warning">
            <p><?php esc_html_e( 'Please configure the Google Analytics settings for Paid Memberships Pro.', 'pmpro-google-analytics' ); ?></p>
            <p><a href="<?php echo esc_url( admin_url( 'options-general.php?page=pmpro-google-analytics' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Configure Google Analytics Settings', 'pmpro-google-analytics' ); ?></a></p>
        </div>
        <?php
    }
}
add_action( 'admin_notices', 'pmproga4_show_setup_notice' );

/**
 * Load the Google Analytics script on the admin pages.
 *
 * @since TBD
 */
function pmproga4_load_admin_script() {

    // Only run this if PMPro is installed.
    if ( ! defined( 'PMPRO_VERSION' ) ) {
        return;
    }

    // Only show on PMPro admin pages.
	if ( empty( $_REQUEST['page'] ) || strpos( $_REQUEST['page'], 'pmpro' ) === false ) {
		return;
	}

    extract( $pmproga4_settings = get_option( 'pmproga4_settings',
        array(
            'measurement_id'       => '',
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

    /**
     * Filters the attributes applied to the Google Analytics script tag loaded in wp_admin.
     *
     * Allows developers to customize or add specific attributes to the Google Analytics script tag
     * for enhanced control or additional features.
     *
     * @since 1.0
     *
     * @param string $script_atts Default value is an empty string. Contains attributes for the GA script tag.
     *
     * @return string Modified attributes for the GA admin script tag.
     */
    $script_atts = apply_filters( 'pmproga4_admin_script_atts', '' );

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
                'send_page_view': false,
            }
        );
	</script>
    <?php

}
add_action( 'admin_enqueue_scripts', 'pmproga4_load_admin_script' );

/**
 * Load the pmproga4_refund_event function on the pmpro_updated_order hook if status is refunded.
 *
 * @since TBD
 */
function pmproga4_refund_event_on_order_status_refunded( $pmpro_invoice, $original_status ) {
    // Prevent unnecessary data sent to GA4 by only running this if the order wasn't already in refund status.
    if ( 'refunded' != $original_status ) {
        pmproga4_refund_event( $pmpro_invoice );
    }
}
add_action( 'pmpro_order_status_refunded', 'pmproga4_refund_event_on_order_status_refunded', 10, 2 );

/**
 * Enqueue the Google Analytics script for a refunded order.
 *
 * @since TBD
 */
function pmproga4_load_admin_order_refunded_script( $pmpro_invoice ) {
    pmproga4_refund_event( $pmpro_invoice );
}

/**
 * Function for refund event.
 */
function pmproga4_refund_event( $pmpro_invoice ) {

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
            jQuery(document).ready(function() {
                gtag( 'event', 'refund', {
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
 * Add a link to the settings page to the plugin action links.
 */
function pmproga4_plugin_action_links( $links ) {

    // Paid Memberships Pro not activated, let's bail.
	if ( ! defined( 'PMPRO_VERSION' ) ) {
        return $links;
    }

    // Check if the user is an admin
    if ( ! current_user_can( 'manage_options' ) ) {
        return $links;
    }

	$new_links = array(
		'<a href="' . admin_url( 'options-general.php?page=pmpro-google-analytics' ) . '">' . esc_html__( 'Settings', 'pmpro-google-analytics' ) . '</a>',
	);
	return array_merge( $new_links, $links );
}
add_filter( 'plugin_action_links_' . PMPROGA_BASENAME, 'pmproga4_plugin_action_links' );

/**
 * Function to add links to the plugin row meta
 *
 * @param array  $links Array of links to be shown in plugin meta.
 * @param string $file Filename of the plugin meta is being shown for.
 */
function pmproga_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'pmpro-google-analytics.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/add-ons/google-analytics/' ) . '" title="' . esc_attr__( 'View Documentation', 'pmpro-google-analytics' ) . '">' . esc_html__( 'Docs', 'pmpro-google-analytics' ) . '</a>',
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/support/' ) . '" title="' . esc_attr__( 'Visit Customer Support Forum', 'pmpro-google-analytics' ) . '">' . esc_html__( 'Support', 'pmpro-google-analytics' ) . '</a>',
		);
		$links = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmproga_plugin_row_meta', 10, 2 );
