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