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
			'active_library'  => 'lenis',
			'anchor_offset'   => 0,
			'enable_mobile'   => 1,
			'script_location' => 'footer',
			'locomotive'      => array(
				// Target container selector. We pass this to the wrapper to resolve an element for `el`.
				'elSelector'           => 'body',
				'smooth'               => true,
				'smoothMobile'         => true,
				'lerp'                 => 0.1,
				'multiplier'           => 1,
				'firefoxMultiplier'    => 1,
				'touchMultiplier'      => 2,
				'direction'            => 'vertical',
				'gestureDirection'     => 'vertical',
				'class'                => 'is-inview',
				'scrollbarClass'       => 'c-scrollbar',
				'scrollingClass'       => 'has-scroll-scrolling',
				'draggingClass'        => 'has-scroll-dragging',
				'smoothClass'          => 'has-smooth',
				'initClass'            => 'has-scroll-init',
				'getDirection'         => true,
				'scrollFromAnywhere'   => true,
				'reloadOnContextChange'=> false,
				'resetNativeScroll'    => false,
				'tablet'               => array(
					'smooth'            => true,
					'breakpoint'        => 1024,
					'direction'         => 'vertical',
					'gestureDirection'  => 'vertical',
					'multiplier'        => 1,
					'firefoxMultiplier' => 1,
				),
				'smartphone'           => array(
					'smooth'            => false,
					'breakpoint'        => 768,
					'direction'         => 'vertical',
					'gestureDirection'  => 'vertical',
					'multiplier'        => 1,
					'firefoxMultiplier' => 1,
				),
				'custom'               => '', // JSON-encoded object for advanced options
			),
			'lenis'           => array(
				'duration'           => 1.2,
				'easing'             => 'cubic-bezier(0.22, 1, 0.36, 1)',
				'lerp'               => 0.1,
				'smoothWheel'        => true,
				'smoothTouch'        => false,
				'wheelMultiplier'    => 1.0,
				'touchMultiplier'    => 1.5,
				'infinite'           => false,
				'autoResize'         => true,
				'normalizeWheel'     => true,
				'orientation'        => 'vertical',
				'gestureOrientation' => 'vertical',
				'syncTouch'          => false,
				'wrapperSelector'    => '',
				'contentSelector'    => '',
				'custom'             => '', // JSON-encoded object for advanced options
			),
			'gsap'            => array(
				'enableScrollTo'        => true,
				'duration'               => 1.0,
				'ease'                   => 'power2.out',
				'autoKill'              => true,
				'overwrite'             => true,
				'offset'                => 0,
				'scrollTriggerDefaults' => array(
					'scrub'           => false,
					'markers'         => false,
					'pin'             => false,
					'toggleActions'   => 'play pause resume reset',
					'anticipatePin'   => 0,
					'fastScrollEnd'   => true,
					'preventOverlaps' => false,
					'once'            => false,
					'horizontal'      => false,
				),
				'triggersJSON'          => '', // JSON array of ScrollTrigger configs
			),
		);

		/**
		 * Filter the default options.
		 *
		 * @param array $defaults Default options.
		 */
		return (array) apply_filters( 'oss_default_options', $defaults );
	}

	/**
	 * Deep merge two arrays (assoc). Scalar from $overrides wins.
	 *
	 * @param array $base Base array.
	 * @param array $overrides Overrides array.
	 * @return array
	 */
	private static function deep_merge( array $base, array $overrides ): array {
		foreach ( $overrides as $key => $value ) {
			if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) ) {
				$base[ $key ] = self::deep_merge( $base[ $key ], $value );
			} else {
				$base[ $key ] = $value;
			}
		}
		return $base;
	}

	/**
	 * Retrieve saved options merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_options(): array {
		$saved    = get_option( 'oss_options', array() );
		$defaults = self::get_default_options();
		$options  = is_array( $saved ) ? self::deep_merge( $defaults, $saved ) : $defaults;

		// Defensive type enforcement for top-level settings.
		$options['active_library']  = in_array( $options['active_library'], array( 'locomotive', 'gsap', 'lenis' ), true ) ? $options['active_library'] : 'lenis';
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
	 * @return string One of: locomotive|gsap|lenis
	 */
	public static function get_active_library(): string {
		$options = self::get_options();
		$active  = $options['active_library'] ?? 'lenis';

		/**
		 * Filter the active library.
		 *
		 * @param string $active Active library.
		 * @param array  $options Full options.
		 */
		return (string) apply_filters( 'oss_active_library', $active, $options );
	}
}