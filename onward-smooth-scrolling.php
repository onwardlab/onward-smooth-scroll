<?php
/**
 * Plugin Name: Onward Smooth Scrolling
 * Plugin URI: https://github.com/onwardlab/onward-smooth-scroll
 * Description: Modular smooth scrolling supporting Locomotive Scroll, Lenis, Smooth Scrollbar, or native CSS smooth scroll. Includes admin settings.
 * Version: 1.0.0
 * Author: OnwardLab
 * Author URI: https://onwardlab.co/
 * Requires PHP: 8.0
 * Requires at least: 6.0
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: onward-smooth-scrolling
 * Domain Path: /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
if ( ! defined( 'OSS_VERSION' ) ) {
	define( 'OSS_VERSION', '1.0.0' );
}

if ( ! defined( 'OSS_PLUGIN_FILE' ) ) {
	define( 'OSS_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'OSS_PLUGIN_DIR' ) ) {
	define( 'OSS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'OSS_PLUGIN_URL' ) ) {
	define( 'OSS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'OSS_TEXT_DOMAIN' ) ) {
	define( 'OSS_TEXT_DOMAIN', 'onward-smooth-scrolling' );
}

// Includes.
require_once OSS_PLUGIN_DIR . 'includes/class-oss-plugin.php';
require_once OSS_PLUGIN_DIR . 'includes/class-oss-admin.php';
require_once OSS_PLUGIN_DIR . 'includes/class-oss-frontend.php';

// Activation: set defaults if not present.
register_activation_hook( __FILE__, function () {
	$defaults = \OnwardSmoothScrolling\Plugin::get_default_options();
	$current  = get_option( 'oss_options', array() );
	if ( empty( $current ) || ! is_array( $current ) ) {
		update_option( 'oss_options', $defaults );
	}
} );

// Initialize the plugin.
add_action( 'plugins_loaded', function () {
	\OnwardSmoothScrolling\Plugin::init();
} );