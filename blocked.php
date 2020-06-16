<?php
/**
 * @package BlockedWP
 * @version 0.6
 */
/*
Plugin Name: Blocked-WP
Plugin URI: https://www.blocked.org.uk/wp-plugin/
Description: A plugin that adds UK ISP parental control monitoring to the Wordpress Admin panel.
Author: Open Rights Group
Version: 0.7.1
Author URI: https://www.openrightsgroup.org/
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

define( 'BLOCKED_WP_VERSION', '0.7.1' );
define( 'BLOCKED_WP__MINIMUM_WP_VERSION', '4.0' );
define( 'BLOCKED_WP__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once( BLOCKED_WP__PLUGIN_DIR . 'class.blocked.php' );

add_action( 'init', array( 'BlockedWP', 'init' ) );

register_activation_hook( __FILE__, array( 'BlockedWP', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'BlockedWP', 'plugin_deactivation' ) );

