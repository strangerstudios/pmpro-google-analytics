<?php
/**
 * All admin settings go here.
 */

/**
 * Register the menu link in the WordPress dashboard.
 *
 * @since 1.0
 */
function pmproga4_admin_menu() {
	
	if ( ! defined( 'PMPRO_VERSION' ) ) {
		return;
	}

	add_submenu_page( 'options-general.php', __( 'PMPro Google Analytics', 'pmpro-google-analytics' ), __( 'PMPro Google Analytics', 'pmpro-google-analytics' ), 'manage_options', 'pmpro-google-analytics', 'pmproga4_settings_page' );
}
add_action( 'admin_menu', 'pmproga4_admin_menu' );

/**
 * The settings page for the options.
 *
 * @since 1.0
 */
function pmproga4_settings_page() {

	// Save settings if data has been posted.
	pmproga4_save_settings();

	// Get the options for settings here.
	$pmproga4_settings = get_option( 'pmproga4_settings',
		array(
			'measurement_id'	=> '',
			'dont_track_admins' => '',
            'track_levels'      => array()
		)
	);

	require_once( PMPRO_DIR . '/adminpages/admin_header.php' );

	// Get all level ID's
    $all_levels = pmpro_getAllLevels( false, true );
	?>
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Paid Memberships Pro - Google Analytics Settings', 'pmpro-google-analytics' ); ?></h1>
	<hr class="wp-header-end">
	<form method="POST">
		<h2><?php esc_html_e( 'Connect to Google Analytics', 'pmpro-google-analytics' ); ?></h2>
		<p><?php esc_html_e( 'Enter your Google Analytics measurement ID. This allows Google Analytics to measure traffic and interactions across your website, as well as ecommerce conversions across the checkout experience.', 'pmpro-google-analytics' ); ?></p>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top"><label for="pmproga4_measurement_id"><?php esc_html_e( 'Measurement ID', 'pmpro-google-analytics' ); ?></label></th>
					<td>
						<input type="text" name="pmproga4_measurement_id" value="<?php echo esc_attr( $pmproga4_settings['measurement_id'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Your measurement ID must start with "G-XXXXXXX". "GTM" tracking codes are not supported.', 'pmpro-google-analytics' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<h2><?php esc_html_e( 'Enhanced Ecommerce Tracking', 'pmpro-google-analytics' ); ?></h2>
		<p><?php esc_html_e( 'Limit which membership level checkouts are tracked as conversion events in Google Analytics. If you do not adjust this setting, all membership checkouts will be tracked, including free checkouts.', 'pmpro-google-analytics' ); ?></p>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top"><?php esc_html_e( 'Levels to Track', 'pmpro-google-analytics' ); ?></th>
					<td>
						<select id="pmproga4_track_levels" name="pmproga4_track_levels[]" multiple="multiple">
						<?php 
							foreach( $all_levels as $level ) {
								$selected = in_array( $level->id, $pmproga4_settings['track_levels'] ) ? 'selected' : '';
								echo '<option value="' . esc_attr( $level->id ) . '" ' . $selected . '>' . esc_html( $level->name ) . '</option>';
							}
						?>
						</select>
						<p class="description"><?php esc_html_e( 'Leave this option blank to track all levels that allow registrations.', 'pmpro-google-analytics' ); ?></p>
					</td>
					<script>
						// Convert to Select 2
						jQuery(document).ready(function($) {
							$('#pmproga4_track_levels').select2();
						});
					</script>
				</tr>
			</tbody>
		</table>
		<h2><?php esc_html_e( 'Exclude From Tracking', 'pmpro-google-analytics' ); ?></h2>
		<p><?php esc_html_e( 'Optionally exclude administrator users from tracking.', 'pmpro-google-analytics' ); ?></p>
		<table class="form-table">
			<tbody>	
				<tr>
					<th scope="row" valign="top"><?php esc_html_e( 'Admin Users', 'pmpro-google-analytics' ); ?></th>
					<td>
						<input type="checkbox" name="pmproga4_dont_track_admins" id="pmproga4_dont_track_admins" value="1" <?php checked( $pmproga4_settings['dont_track_admins'], 1 ); ?>/>
						<label for="pmproga4_dont_track_admins"><?php esc_html_e( 'Disable tracking of adminstrator users.', 'pmpro-google-analytics' ); ?></label>
					</td>
				</tr>
			</tbody>
		</table>
		<p class="submit"><input type="submit" name="pmproga4_save" value="Save Settings" class="button button-primary" /></p>
		<?php wp_nonce_field( 'pmproga4_save', 'pmproga4_save' ); ?>
	</form>
	<hr />
	<p><a href="https://www.paidmembershipspro.com/add-ons/google-analytics/?utm_source=plugin&utm_medium=pmpro-google-analytics-admin&utm_campaign=add-ons" target="_blank"><?php esc_html_e( 'Documentation', 'pmpro-google-analytics' ); ?></a> | <a href="https://www.paidmembershipspro.com/support/?utm_source=plugin&utm_medium=pmpro-google-analytics-admin&utm_campaign=support" target="_blank"><?php esc_html_e( 'Support', 'pmpro-google-analytics' ); ?></a></p>
	<?php
	require_once( PMPRO_DIR . "/adminpages/admin_footer.php" );
}

/**
 * Helper function to save data.
 */
function pmproga4_save_settings() {
    global $pmpro_msg, $pmpro_msgt;
	if ( ! empty( $_REQUEST['pmproga4_save'] ) ) {
		// Nonce failed, don't save anything.
		if ( ! check_admin_referer( 'pmproga4_save', 'pmproga4_save' ) ) {
			exit;
		}

        // Get the options here and put them into an array and save them.
		$pmproga4_settings = array();

		// Get setting for the Google Analytics Property's Measurement ID.
		if ( isset( $_REQUEST['pmproga4_measurement_id'] ) ) {
			$pmproga4_settings['measurement_id'] = sanitize_text_field( $_REQUEST['pmproga4_measurement_id'] );
		} else {
			$pmproga4_settings['measurement_id'] = '';
		}

		// Get setting for whether to track admins.
		if ( isset( $_REQUEST['pmproga4_dont_track_admins'] ) ) {
			$pmproga4_settings['dont_track_admins'] = sanitize_text_field( $_REQUEST['pmproga4_dont_track_admins'] );
		} else {
			$pmproga4_settings['dont_track_admins'] = '';
		}

		// Get setting for which levels to track.
		if ( isset( $_REQUEST['pmproga4_track_levels'] ) ) {
			$pmproga4_settings['track_levels'] = is_array( $_REQUEST['pmproga4_track_levels'] ) ? array_map( 'intval', $_REQUEST['pmproga4_track_levels'] ) : array();
		} else {
			$pmproga4_settings['track_levels'] = array();
		}

		// Save all the settings here.
		if ( update_option( 'pmproga4_settings', $pmproga4_settings ) ) {

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