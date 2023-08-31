<?php
/**
 * All admin settings go here.
 */

/**
 * Register the menu link in the WordPress dashboard.
 * @since 0.1
 */
function pmproga_admin_menu() {
    add_submenu_page( 'options-general.php', __( 'PMPro Google Analytics', 'pmpro-google-analytics' ), __( 'PMPro Google Analytics', 'pmpro-google-analytics' ), 'manage_options', 'pmpro-google-analytics', 'pmproga_settings_page' );
}
add_action( 'admin_menu', 'pmproga_admin_menu' );

/**
 * The settings page for the options.
 * @since 0.1
 */
function pmproga_settings_page() {
    // Save settings if data has been posted.
    pmproga_save_settings();

    // Content of the HTML page goes here.
    ?>
    <h1><?php esc_html_e( 'Paid Memberships Pro - Google Analytics Settings', 'pmpro-google-analytics' ); ?></h1>
        <table>
            <form method="POST">
                <tr>
                    <th><?php esc_html_e( 'GA Tracking ID:', 'pmpro-google-analytics' ); ?></th>
                    <td><input type="text" name="pmproga_tracking_id" value="<?php echo get_option( 'pmproga_tracking_id' ); ?>"/><br/><small>Your tracking code will start with "GT-XXXXXXX". "GTM" tracking codes are not supported.</small></td>
                </tr>
                <tr>
                    <td><input type="submit" name="pmproga_save" value="Save Settings" class="button button-primary" /> <?php wp_nonce_field( 'pmproga_save', 'pmproga_save' ); ?></td>
         </form>
        </table>
    <?php
}

/**
 * Helper function to save data.
 */
function pmproga_save_settings() {
    if ( ! empty( $_REQUEST['pmproga_save'] ) ) {
        // Nonce failed, don't save anything.
        if ( ! check_admin_referer( 'pmproga_save', 'pmproga_save' ) ) {
            exit;
        }
    
        // Save all the settings here. ///
        update_option( 'pmproga_tracking_id', sanitize_text_field( $_REQUEST['pmproga_tracking_id'] ) );
    }
}