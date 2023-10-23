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
