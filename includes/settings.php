<?php
/**
 * All admin settings go here.
 */

/**
 * Register the menu link in the WordPress dashboard.
 *
 * @since 0.1
 */
function pmproga_admin_menu() {
	add_submenu_page( 'options-general.php', __( 'PMPro Google Analytics', 'pmpro-google-analytics' ), __( 'PMPro Google Analytics', 'pmpro-google-analytics' ), 'manage_options', 'pmpro-google-analytics', 'pmproga_settings_page' );
}
add_action( 'admin_menu', 'pmproga_admin_menu' );

/**
 * The settings page for the options.
 *
 * @since 0.1
 */
function pmproga_settings_page() {
	// Save settings if data has been posted.
	pmproga_save_settings();

	// Get the options for settings here.
	$pmproga_settings = get_option( 'pmproga_settings',
		array(
			'tracking_id'       => '',
			'dont_track_admins' => '',
            'track_levels'      => array()
		)
	);

    // Get all level ID's
    $all_levels = pmpro_getAllLevels( false, true );
	?>
	<h1><?php esc_html_e( 'Paid Memberships Pro - Google Analytics Settings', 'pmpro-google-analytics' ); ?></h1>
		<table class="form-table">
			<form method="POST">
				<tr>
					<th scope="row" valign="top"><label for="pmproga_tracking_id"><?php esc_html_e( 'GA Tracking ID', 'pmpro-google-analytics' ); ?></label></th>
					<td>
						<input type="text" name="pmproga_tracking_id" value="<?php echo esc_attr( $pmproga_settings['tracking_id'] ); ?>"/><br/>
						<small><?php esc_html_e( 'Your tracking code will start with "G-XXXXXXX". "GTM" tracking codes are not supported.', 'pmpro-google-analytics' ); ?></small>
					</td>
				</tr>
				<tr>
					<th scope="row" valign="top"><?php esc_html_e( 'Levels to Track', 'pmpro-google-analytics' ); ?></th>
					<td>
						<select id="pmproga_track_levels" name="pmproga_track_levels[]" multiple="multiple">
                           <?php 
                            foreach( $all_levels as $level ) {
                                $selected = in_array( $level->id, $pmproga_settings['track_levels'] ) ? 'selected' : '';
                                echo '<option value="' . esc_attr( $level->id ) . '" ' . $selected . '>' . esc_html( $level->name ) . '</option>';
                            }
                           ?>
                        </select>
						<br/>
						<small><?php esc_html_e( 'Leave this option blank to track all levels that allow registrations.', 'pmpro-google-analytics' ); ?></small>
					</td>
                    <script>
                        // Convert to Select 2
                        jQuery(document).ready(function($) {
                            $('#pmproga_track_levels').select2();
                        });
                    </script>
				</tr>
                <tr>
                    <th scope="row" valign="top"><?php esc_html_e( 'Admin Users', 'pmpro-google-analytics' ); ?></th>
					<td>
						<input type="checkbox" name="pmproga_dont_track_admins" id="pmproga_dont_track_admins" value="1" <?php checked( $pmproga_settings['dont_track_admins'], 1 ); ?>/>
						<label for="pmproga_dont_track_admins"><?php esc_html_e( 'Disable tracking of Admin-level users.', 'pmpro-google-analytics' ); ?></label>
					</td>
                </tr>
				<tr>
					<td>
						<input type="submit" name="pmproga_save" value="Save Settings" class="button button-primary" /> 
						<?php wp_nonce_field( 'pmproga_save', 'pmproga_save' ); ?>
					</td>
				</tr>
		 </form>
		</table>
	<?php
}

/**
 * Helper function to save data.
 */
function pmproga_save_settings() {
    global $pmpro_msg, $pmpro_msgt;
	if ( ! empty( $_REQUEST['pmproga_save'] ) ) {
		// Nonce failed, don't save anything.
		if ( ! check_admin_referer( 'pmproga_save', 'pmproga_save' ) ) {
			exit;
		}

        // Get the options here and put them into an array and save them.
		$pmproga_settings                      = array();
		$pmproga_settings['tracking_id']       = sanitize_text_field( $_REQUEST['pmproga_tracking_id'] );
		$pmproga_settings['dont_track_admins'] = sanitize_text_field( $_REQUEST['pmproga_dont_track_admins'] );
        $pmproga_settings['track_levels']      = is_array( $_REQUEST['pmproga_track_levels'] ) ? array_map( 'intval', $_REQUEST['pmproga_track_levels'] ) : array();

		// Save all the settings here.
		if ( update_option( 'pmproga_settings', $pmproga_settings ) ) {

            $pmpro_msg = __( 'Settings saved.', 'pmpro-google-analytics' );
            $pmpro_msgt = 'success';

			if ( ! empty( $pmpro_msg ) ) {
				?>
					<div id="message" class="
					<?php
					if ( $pmpro_msgt == 'success' ) {
						echo 'updated fade';
					} else {
						echo 'error';
					}
					?>
					"><p><?php echo $pmpro_msg; ?></p></div>
				<?php
			}
		}
	}
}