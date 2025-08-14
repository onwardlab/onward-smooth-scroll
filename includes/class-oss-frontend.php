<?php
/**
 * Frontend enqueue and initialization.
 *
 * @package OnwardSmoothScrolling\Frontend
 */

namespace OnwardSmoothScrolling\Frontend;

use OnwardSmoothScrolling\Plugin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Frontend
 *
 * Handles conditional enqueue of scripts/styles and initialization configuration.
 */
class Frontend {
	/**
	 * Constructor. Hooks into wp_enqueue_scripts.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue assets for the active library and pass configuration to JS.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		$options        = Plugin::get_options();
		$active_library = Plugin::get_active_library();

		// Skip on mobile if disabled.
		if ( wp_is_mobile() && empty( $options['enable_mobile'] ) ) {
			return;
		}

		$in_footer = ( 'footer' === $options['script_location'] );

		// Base stylesheet (very light footprint).
		wp_enqueue_style(
			'oss-frontend',
			OSS_PLUGIN_URL . 'assets/css/oss-frontend.css',
			array(),
			OSS_VERSION
		);

		// Determine library asset paths.
		$lib_js   = $this->map_library_to_js( $active_library );
		$lib_css  = $this->map_library_to_css( $active_library );

		// Register library CSS if present.
		if ( $lib_css && file_exists( OSS_PLUGIN_DIR . $lib_css ) ) {
			wp_enqueue_style(
				'oss-library',
				OSS_PLUGIN_URL . $lib_css,
				array(),
				OSS_VERSION
			);
		}

		// Register and enqueue the library wrapper script.
		wp_register_script(
			'oss-library',
			OSS_PLUGIN_URL . $lib_js,
			array(),
			OSS_VERSION,
			$in_footer
		);
		wp_enqueue_script( 'oss-library' );

		// Orchestrator initializer.
		wp_register_script(
			'oss-init',
			OSS_PLUGIN_URL . 'assets/js/oss-init.js',
			array( 'oss-library' ),
			OSS_VERSION,
			$in_footer
		);

		$localized = array(
			'activeLibrary' => $active_library,
			'speed'         => (float) $options['scroll_speed'],
			'easing'        => (string) $options['easing'],
			'anchorOffset'  => (int) $options['anchor_offset'],
			'enableMobile'  => (int) $options['enable_mobile'] ? true : false,
			'isMobile'      => wp_is_mobile(),
			'pluginUrl'     => rtrim( OSS_PLUGIN_URL, '/' ),
			'pluginVersion' => OSS_VERSION,
		);

		/**
		 * Filter the localized settings passed to the frontend script.
		 *
		 * @param array $localized Localized data.
		 * @param array $options   Full plugin options.
		 */
		$localized = (array) apply_filters( 'oss_localized_settings', $localized, $options );

		wp_localize_script( 'oss-init', 'ossSettings', $localized );
		wp_enqueue_script( 'oss-init' );

		/**
		 * Fire after enqueueing Onward Smooth Scrolling scripts.
		 *
		 * @param string $active_library Active library.
		 * @param array  $options        Options.
		 */
		do_action( 'oss_enqueue_scripts', $active_library, $options );
	}

	/**
	 * Map the active library to its wrapper JS path relative to plugin root.
	 *
	 * @param string $library Library key.
	 * @return string Relative path to JS file.
	 */
	private function map_library_to_js( string $library ): string {
		switch ( $library ) {
			case 'locomotive':
				return 'libraries/locomotive.js';
			case 'lenis':
				return 'libraries/lenis.js';
			case 'scrollbar':
				return 'libraries/scrollbar.js';
			case 'native':
			default:
				return 'libraries/native.js';
		}
	}

	/**
	 * Map the active library to an optional CSS path relative to plugin root.
	 *
	 * @param string $library Library key.
	 * @return string|null Relative path to CSS file or null.
	 */
	private function map_library_to_css( string $library ): ?string {
		switch ( $library ) {
			case 'locomotive':
				return 'libraries/locomotive.css';
			case 'lenis':
				return 'libraries/lenis.css';
			case 'scrollbar':
				return 'libraries/scrollbar.css';
			case 'native':
			default:
				return null;
		}
	}
}