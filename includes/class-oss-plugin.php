<?php
/**
 * Core Plugin orchestrator.
 *
 * @package OnwardSmoothScrolling
 */

namespace OnwardSmoothScrolling;

use OnwardSmoothScrolling\Admin\Admin;
use OnwardSmoothScrolling\Frontend\Frontend;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 *
 * Initializes the plugin, loads text domain, and exposes utilities for options.
 */
class Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Admin module instance.
	 *
	 * @var Admin|null
	 */
	private static ?Admin $admin = null;

	/**
	 * Frontend module instance.
	 *
	 * @var Frontend|null
	 */
	private static ?Frontend $frontend = null;

	/**
	 * Initialize plugin.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( null !== self::$instance ) {
			return;
		}

		self::$instance = new self();
		self::$instance->load_textdomain();

		// Instantiate modules.
		self::$admin    = new Admin();
		self::$frontend = new Frontend();
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	private function load_textdomain(): void {
		load_plugin_textdomain( OSS_TEXT_DOMAIN, false, dirname( plugin_basename( OSS_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Get default options.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_default_options(): array {
		$defaults = array(
			'active_library'  => 'native',
			'scroll_speed'    => 1.0,
			'easing'          => 'ease',
			'anchor_offset'   => 0,
			'enable_mobile'   => 1,
			'script_location' => 'footer',
		);

		/**
		 * Filter the default options.
		 *
		 * @param array $defaults Default options.
		 */
		return (array) apply_filters( 'oss_default_options', $defaults );
	}

	/**
	 * Retrieve saved options merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_options(): array {
		$saved    = get_option( 'oss_options', array() );
		$defaults = self::get_default_options();
		$options  = wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );

		// Defensive type enforcement.
		$options['active_library']  = in_array( $options['active_library'], array( 'locomotive', 'lenis', 'scrollbar', 'native' ), true ) ? $options['active_library'] : 'native';
		$options['scroll_speed']    = is_numeric( $options['scroll_speed'] ) ? (float) $options['scroll_speed'] : (float) $defaults['scroll_speed'];
		$options['easing']          = in_array( $options['easing'], array( 'ease', 'linear', 'ease-in', 'ease-out', 'cubic-bezier' ), true ) ? $options['easing'] : $defaults['easing'];
		$options['anchor_offset']   = is_numeric( $options['anchor_offset'] ) ? (int) $options['anchor_offset'] : (int) $defaults['anchor_offset'];
		$options['enable_mobile']   = (int) ( ! empty( $options['enable_mobile'] ) ? 1 : 0 );
		$options['script_location'] = in_array( $options['script_location'], array( 'header', 'footer' ), true ) ? $options['script_location'] : $defaults['script_location'];

		/**
		 * Filter the fully-resolved options.
		 *
		 * @param array $options Options array.
		 */
		return (array) apply_filters( 'oss_options', $options );
	}

	/**
	 * Get the active library, after applying filters.
	 *
	 * @return string One of: locomotive|lenis|scrollbar|native
	 */
	public static function get_active_library(): string {
		$options = self::get_options();
		$active  = $options['active_library'] ?? 'native';

		/**
		 * Filter the active library.
		 *
		 * @param string $active Active library.
		 * @param array  $options Full options.
		 */
		return (string) apply_filters( 'oss_active_library', $active, $options );
	}
}