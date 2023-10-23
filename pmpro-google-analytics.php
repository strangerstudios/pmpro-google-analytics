<?php
/**
 * Plugin Name: Paid Memberships Pro - Google Analytics Integration
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/google-analytics/
 * Description: Connect Paid Memberships Pro to Google Analytics 4 to measure traffic, interactions, and ecommerce conversions.
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
include PMPROGA_DIR . '/includes/admin.php';