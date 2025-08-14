<?php
/**
 * Uninstall handler for Onward Smooth Scrolling.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove plugin options.
delete_option( 'oss_options' );