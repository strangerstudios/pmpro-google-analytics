<?php
/**
 * Plugin Name: Paid Memberships Pro - Google Analytics Integration
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-google-analytics-integration/
 * Description: Adds Google Analytics Ecommerce Tracking to Paid Memberships Pro.
 * Version: 0.1
 * Author: Paid Memberships Pro
 * Author URI: https://www.paidmembershipspro.com
 * Text Domain: pmpro-google-analytics
 * Domain Path: /languages
 */

// Constants
define( 'PMPROGA_DIR', dirname( __FILE__ ) );
define( 'PMPROGA_BASENAME', plugin_basename( __FILE__ ) );

// Includes
include PMPROGA_DIR . '/includes/settings.php';
include PMPROGA_DIR . '/includes/functions.php';

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
                esc_html__( 'Paid Memberships Pro - Google Analytics', 'pmpro-google-analytics' ),
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
                esc_html__( 'Paid Memberships Pro - Google Analytics', 'pmpro-google-analytics' ),
                implode( ', ', $activate_plugins ) // $activate_plugins was escaped when built.
            )
        );

        return; // Bail here, so we only show one notice at a time.
    }
}
add_action( 'admin_notices', 'pmproga4_required_installed' );

/**
 * Add a link to the settings page to the plugin action links.
 *
 * @param [type] $links
 * @return void
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
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pmproga4_plugin_action_links' );