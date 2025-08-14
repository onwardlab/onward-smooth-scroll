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

		// Base stylesheet (light footprint).
		wp_enqueue_style(
			'oss-frontend',
			OSS_PLUGIN_URL . 'assets/css/oss-frontend.css',
			array(),
			OSS_VERSION
		);

		// Determine library asset paths.
		$lib_js        = $this->map_library_to_js( $active_library );
		$lib_css       = $this->map_library_to_css( $active_library );
		$vendor_handles = array();

		// Register library CSS if present.
		if ( $lib_css && file_exists( OSS_PLUGIN_DIR . $lib_css ) ) {
			wp_enqueue_style(
				'oss-library',
				OSS_PLUGIN_URL . $lib_css,
				array(),
				OSS_VERSION
			);
		}

		// Enqueue local vendor files based on library selection.
		switch ( $active_library ) {
			case 'locomotive':
				$vendor_path = 'libraries/locomotive.min.js';
				if ( file_exists( OSS_PLUGIN_DIR . $vendor_path ) ) {
					wp_register_script( 'oss-vendor-locomotive', OSS_PLUGIN_URL . $vendor_path, array(), OSS_VERSION, $in_footer );
					$vendor_handles[] = 'oss-vendor-locomotive';
				}
				break;
			case 'lenis':
				$vendor_path = 'libraries/lenis.min.js';
				if ( file_exists( OSS_PLUGIN_DIR . $vendor_path ) ) {
					wp_register_script( 'oss-vendor-lenis', OSS_PLUGIN_URL . $vendor_path, array(), OSS_VERSION, $in_footer );
					$vendor_handles[] = 'oss-vendor-lenis';
				}
				break;
			case 'gsap':
				$gsap_core = 'libraries/gsap.min.js';
				$st_plugin = 'libraries/ScrollTrigger.min.js';
				if ( file_exists( OSS_PLUGIN_DIR . $gsap_core ) ) {
					wp_register_script( 'oss-vendor-gsap', OSS_PLUGIN_URL . $gsap_core, array(), OSS_VERSION, $in_footer );
					$vendor_handles[] = 'oss-vendor-gsap';
				}
				if ( file_exists( OSS_PLUGIN_DIR . $st_plugin ) ) {
					wp_register_script( 'oss-vendor-gsap-st', OSS_PLUGIN_URL . $st_plugin, array( 'oss-vendor-gsap' ), OSS_VERSION, $in_footer );
					$vendor_handles[] = 'oss-vendor-gsap-st';
				}
				break;
		}

		// Register and enqueue the library wrapper script if it exists.
		if ( file_exists( OSS_PLUGIN_DIR . $lib_js ) ) {
			wp_register_script(
				'oss-library',
				OSS_PLUGIN_URL . $lib_js,
				$vendor_handles,
				OSS_VERSION,
				$in_footer
			);
			wp_enqueue_script( 'oss-library' );
		}

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
			'general'       => array(
				'anchorOffset' => (int) $options['anchor_offset'],
				'enableMobile' => (int) $options['enable_mobile'] ? true : false,
				'isMobile'     => wp_is_mobile(),
			),
			'locomotive'    => isset( $options['locomotive'] ) && is_array( $options['locomotive'] ) ? $options['locomotive'] : array(),
			'lenis'         => isset( $options['lenis'] ) && is_array( $options['lenis'] ) ? $options['lenis'] : array(),
			'gsap'          => isset( $options['gsap'] ) && is_array( $options['gsap'] ) ? $options['gsap'] : array(),
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
			case 'gsap':
				return 'libraries/gsap.js';
			default:
				return 'libraries/lenis.js';
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
			case 'gsap':
				return 'libraries/gsap.css';
			default:
				return null;
		}
	}
}