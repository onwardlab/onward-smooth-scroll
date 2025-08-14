<?php
/**
 * Admin settings for Onward Smooth Scrolling.
 *
 * @package OnwardSmoothScrolling\Admin
 */

namespace OnwardSmoothScrolling\Admin;

use OnwardSmoothScrolling\Plugin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin
 *
 * Handles the Settings → Smooth Scroll page and Settings API.
 */
class Admin {
	/**
	 * Screen hook suffix for the settings page.
	 *
	 * @var string|null
	 */
	private ?string $hook_suffix = null;

	/**
	 * Constructor. Hooks admin actions.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the submenu under Settings.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		$this->hook_suffix = add_options_page(
			__( 'Smooth Scroll', OSS_TEXT_DOMAIN ),
			__( 'Smooth Scroll', OSS_TEXT_DOMAIN ),
			'manage_options',
			'oss-smooth-scroll',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings and fields.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'oss_settings_group',
			'oss_options',
			array(
				'type'              => 'array',
				'description'       => __( 'Onward Smooth Scrolling options.', OSS_TEXT_DOMAIN ),
				'sanitize_callback' => array( $this, 'sanitize_options' ),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Sanitize and validate options.
	 *
	 * @param mixed $input Raw input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_options( $input ): array {
		// Nonce verification (Settings API also verifies, this is an extra guard).
		if ( isset( $_POST['_wpnonce'] ) ) {
			check_admin_referer( 'oss_settings_group-options' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return Plugin::get_options();
		}

		$defaults  = Plugin::get_default_options();
		$input     = is_array( $input ) ? $input : array();
		$sanitized = array();

		$allowed_libraries = array( 'locomotive', 'gsap', 'lenis' );
		$allowed_location  = array( 'header', 'footer' );

		$active_library = isset( $input['active_library'] ) ? sanitize_text_field( $input['active_library'] ) : $defaults['active_library'];
		if ( ! in_array( $active_library, $allowed_libraries, true ) ) {
			add_settings_error( 'oss_options', 'oss_active_library', __( 'Invalid library selected. Reverted to default.', OSS_TEXT_DOMAIN ), 'error' );
			$active_library = $defaults['active_library'];
		}
		$sanitized['active_library'] = $active_library;

		$sanitized['anchor_offset']   = isset( $input['anchor_offset'] ) ? (int) $input['anchor_offset'] : (int) $defaults['anchor_offset'];
		$sanitized['enable_mobile']   = ! empty( $input['enable_mobile'] ) ? 1 : 0;
		$sanitized['script_location'] = isset( $input['script_location'] ) && in_array( $input['script_location'], $allowed_location, true ) ? $input['script_location'] : $defaults['script_location'];

		// Locomotive options.
		$loc = isset( $input['locomotive'] ) && is_array( $input['locomotive'] ) ? $input['locomotive'] : array();
		$sanitized['locomotive'] = array(
			'elSelector'            => isset( $loc['elSelector'] ) ? sanitize_text_field( $loc['elSelector'] ) : $defaults['locomotive']['elSelector'],
			'smooth'                => ! empty( $loc['smooth'] ) ? true : false,
			'smoothMobile'          => ! empty( $loc['smoothMobile'] ) ? true : false,
			'lerp'                  => isset( $loc['lerp'] ) ? (float) $loc['lerp'] : (float) $defaults['locomotive']['lerp'],
			'multiplier'            => isset( $loc['multiplier'] ) ? (float) $loc['multiplier'] : (float) $defaults['locomotive']['multiplier'],
			'firefoxMultiplier'     => isset( $loc['firefoxMultiplier'] ) ? (float) $loc['firefoxMultiplier'] : (float) $defaults['locomotive']['firefoxMultiplier'],
			'touchMultiplier'       => isset( $loc['touchMultiplier'] ) ? (float) $loc['touchMultiplier'] : (float) $defaults['locomotive']['touchMultiplier'],
			'direction'             => isset( $loc['direction'] ) && in_array( $loc['direction'], array( 'vertical', 'horizontal' ), true ) ? $loc['direction'] : $defaults['locomotive']['direction'],
			'gestureDirection'      => isset( $loc['gestureDirection'] ) && in_array( $loc['gestureDirection'], array( 'vertical', 'horizontal' ), true ) ? $loc['gestureDirection'] : $defaults['locomotive']['gestureDirection'],
			'class'                 => isset( $loc['class'] ) ? sanitize_text_field( $loc['class'] ) : $defaults['locomotive']['class'],
			'scrollbarClass'        => isset( $loc['scrollbarClass'] ) ? sanitize_text_field( $loc['scrollbarClass'] ) : $defaults['locomotive']['scrollbarClass'],
			'scrollingClass'        => isset( $loc['scrollingClass'] ) ? sanitize_text_field( $loc['scrollingClass'] ) : $defaults['locomotive']['scrollingClass'],
			'draggingClass'         => isset( $loc['draggingClass'] ) ? sanitize_text_field( $loc['draggingClass'] ) : $defaults['locomotive']['draggingClass'],
			'smoothClass'           => isset( $loc['smoothClass'] ) ? sanitize_text_field( $loc['smoothClass'] ) : $defaults['locomotive']['smoothClass'],
			'initClass'             => isset( $loc['initClass'] ) ? sanitize_text_field( $loc['initClass'] ) : $defaults['locomotive']['initClass'],
			'getDirection'          => ! empty( $loc['getDirection'] ) ? true : false,
			'scrollFromAnywhere'    => ! empty( $loc['scrollFromAnywhere'] ) ? true : false,
			'reloadOnContextChange' => ! empty( $loc['reloadOnContextChange'] ) ? true : false,
			'resetNativeScroll'     => ! empty( $loc['resetNativeScroll'] ) ? true : false,
			'tablet'                => array(
				'smooth'            => isset( $loc['tablet']['smooth'] ) ? (bool) $loc['tablet']['smooth'] : (bool) $defaults['locomotive']['tablet']['smooth'],
				'breakpoint'        => isset( $loc['tablet']['breakpoint'] ) ? (int) $loc['tablet']['breakpoint'] : (int) $defaults['locomotive']['tablet']['breakpoint'],
				'direction'         => isset( $loc['tablet']['direction'] ) && in_array( $loc['tablet']['direction'], array( 'vertical', 'horizontal' ), true ) ? $loc['tablet']['direction'] : $defaults['locomotive']['tablet']['direction'],
				'gestureDirection'  => isset( $loc['tablet']['gestureDirection'] ) && in_array( $loc['tablet']['gestureDirection'], array( 'vertical', 'horizontal' ), true ) ? $loc['tablet']['gestureDirection'] : $defaults['locomotive']['tablet']['gestureDirection'],
				'multiplier'        => isset( $loc['tablet']['multiplier'] ) ? (float) $loc['tablet']['multiplier'] : (float) $defaults['locomotive']['tablet']['multiplier'],
				'firefoxMultiplier' => isset( $loc['tablet']['firefoxMultiplier'] ) ? (float) $loc['tablet']['firefoxMultiplier'] : (float) $defaults['locomotive']['tablet']['firefoxMultiplier'],
			),
			'smartphone'            => array(
				'smooth'            => isset( $loc['smartphone']['smooth'] ) ? (bool) $loc['smartphone']['smooth'] : (bool) $defaults['locomotive']['smartphone']['smooth'],
				'breakpoint'        => isset( $loc['smartphone']['breakpoint'] ) ? (int) $loc['smartphone']['breakpoint'] : (int) $defaults['locomotive']['smartphone']['breakpoint'],
				'direction'         => isset( $loc['smartphone']['direction'] ) && in_array( $loc['smartphone']['direction'], array( 'vertical', 'horizontal' ), true ) ? $loc['smartphone']['direction'] : $defaults['locomotive']['smartphone']['direction'],
				'gestureDirection'  => isset( $loc['smartphone']['gestureDirection'] ) && in_array( $loc['smartphone']['gestureDirection'], array( 'vertical', 'horizontal' ), true ) ? $loc['smartphone']['gestureDirection'] : $defaults['locomotive']['smartphone']['gestureDirection'],
				'multiplier'        => isset( $loc['smartphone']['multiplier'] ) ? (float) $loc['smartphone']['multiplier'] : (float) $defaults['locomotive']['smartphone']['multiplier'],
				'firefoxMultiplier' => isset( $loc['smartphone']['firefoxMultiplier'] ) ? (float) $loc['smartphone']['firefoxMultiplier'] : (float) $defaults['locomotive']['smartphone']['firefoxMultiplier'],
			),
			'custom'               => isset( $loc['custom'] ) ? wp_kses_post( $loc['custom'] ) : $defaults['locomotive']['custom'],
		);

		// Lenis options.
		$le = isset( $input['lenis'] ) && is_array( $input['lenis'] ) ? $input['lenis'] : array();
		$sanitized['lenis'] = array(
			'duration'           => isset( $le['duration'] ) ? (float) $le['duration'] : (float) $defaults['lenis']['duration'],
			'easing'             => isset( $le['easing'] ) ? sanitize_text_field( $le['easing'] ) : $defaults['lenis']['easing'],
			'lerp'               => isset( $le['lerp'] ) ? (float) $le['lerp'] : (float) $defaults['lenis']['lerp'],
			'smoothWheel'        => ! empty( $le['smoothWheel'] ) ? true : false,
			'smoothTouch'        => ! empty( $le['smoothTouch'] ) ? true : false,
			'wheelMultiplier'    => isset( $le['wheelMultiplier'] ) ? (float) $le['wheelMultiplier'] : (float) $defaults['lenis']['wheelMultiplier'],
			'touchMultiplier'    => isset( $le['touchMultiplier'] ) ? (float) $le['touchMultiplier'] : (float) $defaults['lenis']['touchMultiplier'],
			'infinite'           => ! empty( $le['infinite'] ) ? true : false,
			'autoResize'         => ! empty( $le['autoResize'] ) ? true : false,
			'normalizeWheel'     => ! empty( $le['normalizeWheel'] ) ? true : false,
			'orientation'        => isset( $le['orientation'] ) && in_array( $le['orientation'], array( 'vertical', 'horizontal' ), true ) ? $le['orientation'] : $defaults['lenis']['orientation'],
			'gestureOrientation' => isset( $le['gestureOrientation'] ) && in_array( $le['gestureOrientation'], array( 'vertical', 'horizontal' ), true ) ? $le['gestureOrientation'] : $defaults['lenis']['gestureOrientation'],
			'syncTouch'          => ! empty( $le['syncTouch'] ) ? true : false,
			'wrapperSelector'    => isset( $le['wrapperSelector'] ) ? sanitize_text_field( $le['wrapperSelector'] ) : $defaults['lenis']['wrapperSelector'],
			'contentSelector'    => isset( $le['contentSelector'] ) ? sanitize_text_field( $le['contentSelector'] ) : $defaults['lenis']['contentSelector'],
			'custom'             => isset( $le['custom'] ) ? wp_kses_post( $le['custom'] ) : $defaults['lenis']['custom'],
		);

		// GSAP options.
		$gs = isset( $input['gsap'] ) && is_array( $input['gsap'] ) ? $input['gsap'] : array();
		$sanitized['gsap'] = array(
			'enableScrollTo'        => ! empty( $gs['enableScrollTo'] ) ? true : false,
			'duration'               => isset( $gs['duration'] ) ? (float) $gs['duration'] : (float) $defaults['gsap']['duration'],
			'ease'                   => isset( $gs['ease'] ) ? sanitize_text_field( $gs['ease'] ) : $defaults['gsap']['ease'],
			'autoKill'              => ! empty( $gs['autoKill'] ) ? true : false,
			'overwrite'             => ! empty( $gs['overwrite'] ) ? true : false,
			'offset'                => isset( $gs['offset'] ) ? (int) $gs['offset'] : (int) $defaults['gsap']['offset'],
			'scrollTriggerDefaults' => array(
				'scrub'           => ! empty( $gs['scrollTriggerDefaults']['scrub'] ) ? true : false,
				'markers'         => ! empty( $gs['scrollTriggerDefaults']['markers'] ) ? true : false,
				'pin'             => ! empty( $gs['scrollTriggerDefaults']['pin'] ) ? true : false,
				'toggleActions'   => isset( $gs['scrollTriggerDefaults']['toggleActions'] ) ? sanitize_text_field( $gs['scrollTriggerDefaults']['toggleActions'] ) : $defaults['gsap']['scrollTriggerDefaults']['toggleActions'],
				'anticipatePin'   => isset( $gs['scrollTriggerDefaults']['anticipatePin'] ) ? (int) $gs['scrollTriggerDefaults']['anticipatePin'] : (int) $defaults['gsap']['scrollTriggerDefaults']['anticipatePin'],
				'fastScrollEnd'   => ! empty( $gs['scrollTriggerDefaults']['fastScrollEnd'] ) ? true : false,
				'preventOverlaps' => ! empty( $gs['scrollTriggerDefaults']['preventOverlaps'] ) ? true : false,
				'once'            => ! empty( $gs['scrollTriggerDefaults']['once'] ) ? true : false,
				'horizontal'      => ! empty( $gs['scrollTriggerDefaults']['horizontal'] ) ? true : false,
			),
			'triggersJSON'          => isset( $gs['triggersJSON'] ) ? wp_kses_post( $gs['triggersJSON'] ) : $defaults['gsap']['triggersJSON'],
		);

		return $sanitized;
	}

	/**
	 * Enqueue admin assets only on our settings page.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( empty( $this->hook_suffix ) || $hook !== $this->hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'oss-admin',
			OSS_PLUGIN_URL . 'admin/css/oss-admin.css',
			array(),
			OSS_VERSION
		);

		wp_enqueue_script(
			'oss-admin',
			OSS_PLUGIN_URL . 'admin/js/oss-admin.js',
			array( 'jquery' ),
			OSS_VERSION,
			true
		);
	}

	/**
	 * Render settings page markup.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', OSS_TEXT_DOMAIN ) );
		}

		$options = Plugin::get_options();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Onward Smooth Scrolling', OSS_TEXT_DOMAIN ); ?></h1>
			<form method="post" action="options.php" class="oss-form">
				<?php settings_fields( 'oss_settings_group' ); ?>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="oss_active_library"><?php echo esc_html__( 'Active Library', OSS_TEXT_DOMAIN ); ?></label></th>
							<td>
								<select id="oss_active_library" name="oss_options[active_library]">
									<?php foreach ( $this->get_allowed_libraries() as $value => $label ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $options['active_library'], $value ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php echo esc_html__( 'Choose which smooth scroll engine to use.', OSS_TEXT_DOMAIN ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="oss_anchor_offset"><?php echo esc_html__( 'Anchor Offset (px)', OSS_TEXT_DOMAIN ); ?></label></th>
							<td>
								<input type="number" step="1" id="oss_anchor_offset" name="oss_options[anchor_offset]" value="<?php echo esc_attr( $options['anchor_offset'] ); ?>" />
								<p class="description"><?php echo esc_html__( 'Adjust the anchor point for in-page links. Increase if you have a fixed header.', OSS_TEXT_DOMAIN ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php echo esc_html__( 'Enable on Mobile', OSS_TEXT_DOMAIN ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="oss_options[enable_mobile]" value="1" <?php checked( (int) $options['enable_mobile'], 1 ); ?> />
									<?php echo esc_html__( 'Enable smooth scrolling on mobile devices', OSS_TEXT_DOMAIN ); ?>
								</label>
								<p class="description"><?php echo esc_html__( 'Uncheck to disable custom smooth scrolling on mobile devices.', OSS_TEXT_DOMAIN ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="oss_script_location"><?php echo esc_html__( 'Script Loading Location', OSS_TEXT_DOMAIN ); ?></label></th>
							<td>
								<select id="oss_script_location" name="oss_options[script_location]">
									<?php foreach ( $this->get_script_locations() as $value => $label ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $options['script_location'], $value ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php echo esc_html__( 'Choose whether to load scripts in the header (earlier) or footer (recommended).', OSS_TEXT_DOMAIN ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>

				<hr />

				<div class="oss-library-options" data-library="locomotive">
					<h2><?php echo esc_html__( 'Locomotive Scroll Options', OSS_TEXT_DOMAIN ); ?></h2>
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row"><label for="oss_loco_elSelector"><?php echo esc_html__( 'Container Selector', OSS_TEXT_DOMAIN ); ?></label></th>
								<td>
									<input type="text" id="oss_loco_elSelector" name="oss_options[locomotive][elSelector]" value="<?php echo esc_attr( $options['locomotive']['elSelector'] ); ?>" class="regular-text" />
									<p class="description"><?php echo esc_html__( 'CSS selector for the scroll container. Typically body or a wrapper.', OSS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							<tr><th scope="row"><?php echo esc_html__( 'Smooth', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[locomotive][smooth]" value="1" <?php checked( ! empty( $options['locomotive']['smooth'] ) ); ?> /> <?php echo esc_html__( 'Enable smooth scrolling', OSS_TEXT_DOMAIN ); ?></label><p class="description"><?php echo esc_html__( 'Turns on interpolation-based smoothing for wheel/trackpad.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Smooth on Mobile', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[locomotive][smoothMobile]" value="1" <?php checked( ! empty( $options['locomotive']['smoothMobile'] ) ); ?> /> <?php echo esc_html__( 'Enable smooth on mobile', OSS_TEXT_DOMAIN ); ?></label><p class="description"><?php echo esc_html__( 'Use smoothing on touch devices; may increase CPU usage.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Lerp', OSS_TEXT_DOMAIN ); ?></th><td><input type="number" step="0.01" name="oss_options[locomotive][lerp]" value="<?php echo esc_attr( $options['locomotive']['lerp'] ); ?>" /><p class="description"><?php echo esc_html__( 'Interpolation factor (0–1). Lower = smoother, higher = snappier.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Multiplier', OSS_TEXT_DOMAIN ); ?></th><td><input type="number" step="0.1" name="oss_options[locomotive][multiplier]" value="<?php echo esc_attr( $options['locomotive']['multiplier'] ); ?>" /><p class="description"><?php echo esc_html__( 'General scroll speed multiplier. Higher = faster scroll.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Firefox Multiplier', OSS_TEXT_DOMAIN ); ?></th><td><input type="number" step="0.1" name="oss_options[locomotive][firefoxMultiplier]" value="<?php echo esc_attr( $options['locomotive']['firefoxMultiplier'] ); ?>" /><p class="description"><?php echo esc_html__( 'Additional multiplier for Firefox users to normalize feel.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Touch Multiplier', OSS_TEXT_DOMAIN ); ?></th><td><input type="number" step="0.1" name="oss_options[locomotive][touchMultiplier]" value="<?php echo esc_attr( $options['locomotive']['touchMultiplier'] ); ?>" /><p class="description"><?php echo esc_html__( 'Scroll speed multiplier for touch gestures.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Direction', OSS_TEXT_DOMAIN ); ?></th><td><select name="oss_options[locomotive][direction]"><option value="vertical" <?php selected( $options['locomotive']['direction'], 'vertical' ); ?>>vertical</option><option value="horizontal" <?php selected( $options['locomotive']['direction'], 'horizontal' ); ?>>horizontal</option></select><p class="description"><?php echo esc_html__( 'Primary scroll direction.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Gesture Direction', OSS_TEXT_DOMAIN ); ?></th><td><select name="oss_options[locomotive][gestureDirection]"><option value="vertical" <?php selected( $options['locomotive']['gestureDirection'], 'vertical' ); ?>>vertical</option><option value="horizontal" <?php selected( $options['locomotive']['gestureDirection'], 'horizontal' ); ?>>horizontal</option></select><p class="description"><?php echo esc_html__( 'Direction of gestures considered valid for scrolling.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Class', OSS_TEXT_DOMAIN ); ?></th><td><input type="text" name="oss_options[locomotive][class]" value="<?php echo esc_attr( $options['locomotive']['class'] ); ?>" /><p class="description"><?php echo esc_html__( 'CSS class applied to elements when in view.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Scrollbar Class', OSS_TEXT_DOMAIN ); ?></th><td><input type="text" name="oss_options[locomotive][scrollbarClass]" value="<?php echo esc_attr( $options['locomotive']['scrollbarClass'] ); ?>" /><p class="description"><?php echo esc_html__( 'CSS class for the custom scrollbar node.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Scrolling Class', OSS_TEXT_DOMAIN ); ?></th><td><input type="text" name="oss_options[locomotive][scrollingClass]" value="<?php echo esc_attr( $options['locomotive']['scrollingClass'] ); ?>" /><p class="description"><?php echo esc_html__( 'CSS class toggled on the scrolling element while scrolling.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Dragging Class', OSS_TEXT_DOMAIN ); ?></th><td><input type="text" name="oss_options[locomotive][draggingClass]" value="<?php echo esc_attr( $options['locomotive']['draggingClass'] ); ?>" /><p class="description"><?php echo esc_html__( 'CSS class toggled while dragging the scrollbar.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Smooth Class', OSS_TEXT_DOMAIN ); ?></th><td><input type="text" name="oss_options[locomotive][smoothClass]" value="<?php echo esc_attr( $options['locomotive']['smoothClass'] ); ?>" /><p class="description"><?php echo esc_html__( 'CSS class added to document when smooth is enabled.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Init Class', OSS_TEXT_DOMAIN ); ?></th><td><input type="text" name="oss_options[locomotive][initClass]" value="<?php echo esc_attr( $options['locomotive']['initClass'] ); ?>" /><p class="description"><?php echo esc_html__( 'CSS class added to document after Locomotive is initialized.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Get Direction', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[locomotive][getDirection]" value="1" <?php checked( ! empty( $options['locomotive']['getDirection'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Track scroll direction and expose it via data attributes.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Scroll From Anywhere', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[locomotive][scrollFromAnywhere]" value="1" <?php checked( ! empty( $options['locomotive']['scrollFromAnywhere'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Allow scrolling from anywhere on the page, not just wheel area.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Reload On Context Change', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[locomotive][reloadOnContextChange]" value="1" <?php checked( ! empty( $options['locomotive']['reloadOnContextChange'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Re-initialize when device or layout context changes.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Reset Native Scroll', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[locomotive][resetNativeScroll]" value="1" <?php checked( ! empty( $options['locomotive']['resetNativeScroll'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Reset native scroll position to 0 on init to avoid jumps.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row" class="oss-subheading"><?php echo esc_html__( 'Tablet', OSS_TEXT_DOMAIN ); ?></th><td></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Tablet Smooth', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[locomotive][tablet][smooth]" value="1" <?php checked( ! empty( $options['locomotive']['tablet']['smooth'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Enable smoothing for tablet devices.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Tablet Breakpoint', OSS_TEXT_DOMAIN ); ?></th><td><input type="number" step="1" name="oss_options[locomotive][tablet][breakpoint]" value="<?php echo esc_attr( $options['locomotive']['tablet']['breakpoint'] ); ?>" /><p class="description"><?php echo esc_html__( 'Viewport width (px) under which tablet settings apply.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Tablet Direction', OSS_TEXT_DOMAIN ); ?></th><td><select name="oss_options[locomotive][tablet][direction]"><option value="vertical" <?php selected( $options['locomotive']['tablet']['direction'], 'vertical' ); ?>>vertical</option><option value="horizontal" <?php selected( $options['locomotive']['tablet']['direction'], 'horizontal' ); ?>>horizontal</option></select><p class="description"><?php echo esc_html__( 'Primary scroll axis on tablet.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Tablet Gesture Direction', OSS_TEXT_DOMAIN ); ?></th><td><select name="oss_options[locomotive][tablet][gestureDirection]"><option value="vertical" <?php selected( $options['locomotive']['tablet']['gestureDirection'], 'vertical' ); ?>>vertical</option><option value="horizontal" <?php selected( $options['locomotive']['tablet']['gestureDirection'], 'horizontal' ); ?>>horizontal</option></select><p class="description"><?php echo esc_html__( 'Gesture direction recognized on tablet.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Tablet Multiplier', OSS_TEXT_DOMAIN ); ?></th><td><input type="number" step="0.1" name="oss_options[locomotive][tablet][multiplier]" value="<?php echo esc_attr( $options['locomotive']['tablet']['multiplier'] ); ?>" /><p class="description"><?php echo esc_html__( 'Speed multiplier on tablet devices.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Tablet Firefox Multiplier', OSS_TEXT_DOMAIN ); ?></th><td><input type="number" step="0.1" name="oss_options[locomotive][tablet][firefoxMultiplier]" value="<?php echo esc_attr( $options['locomotive']['tablet']['firefoxMultiplier'] ); ?>" /><p class="description"><?php echo esc_html__( 'Additional speed multiplier on Firefox (tablet).', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row" class="oss-subheading"><?php echo esc_html__( 'Smartphone', OSS_TEXT_DOMAIN ); ?></th><td></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Smartphone Smooth', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[locomotive][smartphone][smooth]" value="1" <?php checked( ! empty( $options['locomotive']['smartphone']['smooth'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Enable smoothing for smartphones.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Smartphone Breakpoint', OSS_TEXT_DOMAIN ); ?></th><td><input type="number" step="1" name="oss_options[locomotive][smartphone][breakpoint]" value="<?php echo esc_attr( $options['locomotive']['smartphone']['breakpoint'] ); ?>" /><p class="description"><?php echo esc_html__( 'Viewport width (px) under which smartphone settings apply.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Smartphone Direction', OSS_TEXT_DOMAIN ); ?></th><td><select name="oss_options[locomotive][smartphone][direction]"><option value="vertical" <?php selected( $options['locomotive']['smartphone']['direction'], 'vertical' ); ?>>vertical</option><option value="horizontal" <?php selected( $options['locomotive']['smartphone']['direction'], 'horizontal' ); ?>>horizontal</option></select><p class="description"><?php echo esc_html__( 'Primary scroll axis on smartphones.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Smartphone Gesture Direction', OSS_TEXT_DOMAIN ); ?></th><td><select name="oss_options[locomotive][smartphone][gestureDirection]"><option value="vertical" <?php selected( $options['locomotive']['smartphone']['gestureDirection'], 'vertical' ); ?>>vertical</option><option value="horizontal" <?php selected( $options['locomotive']['smartphone']['gestureDirection'], 'horizontal' ); ?>>horizontal</option></select><p class="description"><?php echo esc_html__( 'Gesture direction recognized on smartphones.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Smartphone Multiplier', OSS_TEXT_DOMAIN ); ?></th><td><input type="number" step="0.1" name="oss_options[locomotive][smartphone][multiplier]" value="<?php echo esc_attr( $options['locomotive']['smartphone']['multiplier'] ); ?>" /><p class="description"><?php echo esc_html__( 'Speed multiplier on smartphones.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Smartphone Firefox Multiplier', OSS_TEXT_DOMAIN ); ?></th><td><input type="number" step="0.1" name="oss_options[locomotive][smartphone][firefoxMultiplier]" value="<?php echo esc_attr( $options['locomotive']['smartphone']['firefoxMultiplier'] ); ?>" /><p class="description"><?php echo esc_html__( 'Additional speed multiplier on Firefox (smartphone).', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr>
								<th scope="row"><label for="oss_loco_custom"><?php echo esc_html__( 'Advanced Options (JSON)', OSS_TEXT_DOMAIN ); ?></label></th>
								<td><textarea id="oss_loco_custom" name="oss_options[locomotive][custom]" rows="4" class="large-text code"><?php echo esc_textarea( $options['locomotive']['custom'] ); ?></textarea><p class="description"><?php echo esc_html__( 'Provide a JSON object to merge into the Locomotive Scroll options.', OSS_TEXT_DOMAIN ); ?></p></td>
							</tr>
						</tbody>
					</table>
				</div>

				<div class="oss-library-options" data-library="lenis">
					<h2><?php echo esc_html__( 'Lenis Options', OSS_TEXT_DOMAIN ); ?></h2>
					<table class="form-table" role="presentation">
						<tbody>
							<tr><th scope="row"><?php echo esc_html__( 'Duration', OSS_TEXT_DOMAIN ); ?></th><td><input type="number" step="0.01" name="oss_options[lenis][duration]" value="<?php echo esc_attr( $options['lenis']['duration'] ); ?>" /><p class="description"><?php echo esc_html__( 'Controls time factor for scrolling animations. Higher = slower movement.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Easing (CSS or JS)', OSS_TEXT_DOMAIN ); ?></th><td><input type="text" name="oss_options[lenis][easing]" value="<?php echo esc_attr( $options['lenis']['easing'] ); ?>" class="regular-text" /><p class="description"><?php echo esc_html__( 'How scroll accelerates/decelerates. Accepts CSS cubic-bezier or JS easing.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Lerp', OSS_TEXT_DOMAIN ); ?></th><td><input type="number" step="0.01" name="oss_options[lenis][lerp]" value="<?php echo esc_attr( $options['lenis']['lerp'] ); ?>" /><p class="description"><?php echo esc_html__( 'Controls smoothness; lower is smoother, higher is snappier.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Smooth Wheel', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[lenis][smoothWheel]" value="1" <?php checked( ! empty( $options['lenis']['smoothWheel'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Smooth out mouse wheel transitions.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Smooth Touch', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[lenis][smoothTouch]" value="1" <?php checked( ! empty( $options['lenis']['smoothTouch'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Smooth out touch scrolling sessions on mobile.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Wheel Multiplier', OSS_TEXT_DOMAIN ); ?></th><td><input type="number" step="0.1" name="oss_options[lenis][wheelMultiplier]" value="<?php echo esc_attr( $options['lenis']['wheelMultiplier'] ); ?>" /><p class="description"><?php echo esc_html__( 'Adjust sensitivity for mouse wheel; higher = faster scroll.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Touch Multiplier', OSS_TEXT_DOMAIN ); ?></th><td><input type="number" step="0.1" name="oss_options[lenis][touchMultiplier]" value="<?php echo esc_attr( $options['lenis']['touchMultiplier'] ); ?>" /><p class="description"><?php echo esc_html__( 'Adjust sensitivity for touch gestures.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Infinite', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[lenis][infinite]" value="1" <?php checked( ! empty( $options['lenis']['infinite'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Loop scrolling content infinitely.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Auto Resize', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[lenis][autoResize]" value="1" <?php checked( ! empty( $options['lenis']['autoResize'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Automatically handle layout changes and resize events.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Normalize Wheel', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[lenis][normalizeWheel]" value="1" <?php checked( ! empty( $options['lenis']['normalizeWheel'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Normalize wheel delta across browsers for consistent feel.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Orientation', OSS_TEXT_DOMAIN ); ?></th><td><select name="oss_options[lenis][orientation]"><option value="vertical" <?php selected( $options['lenis']['orientation'], 'vertical' ); ?>>vertical</option><option value="horizontal" <?php selected( $options['lenis']['orientation'], 'horizontal' ); ?>>horizontal</option></select><p class="description"><?php echo esc_html__( 'Primary scroll axis.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Gesture Orientation', OSS_TEXT_DOMAIN ); ?></th><td><select name="oss_options[lenis][gestureOrientation]"><option value="vertical" <?php selected( $options['lenis']['gestureOrientation'], 'vertical' ); ?>>vertical</option><option value="horizontal" <?php selected( $options['lenis']['gestureOrientation'], 'horizontal' ); ?>>horizontal</option></select><p class="description"><?php echo esc_html__( 'Gesture axis for interaction.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Sync Touch', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[lenis][syncTouch]" value="1" <?php checked( ! empty( $options['lenis']['syncTouch'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Synchronize touch input with animations.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><label for="oss_lenis_wrapper"><?php echo esc_html__( 'Wrapper Selector', OSS_TEXT_DOMAIN ); ?></label></th><td><input id="oss_lenis_wrapper" type="text" name="oss_options[lenis][wrapperSelector]" value="<?php echo esc_attr( $options['lenis']['wrapperSelector'] ); ?>" class="regular-text" /><p class="description"><?php echo esc_html__( 'CSS selector for the scroll wrapper element.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><label for="oss_lenis_content"><?php echo esc_html__( 'Content Selector', OSS_TEXT_DOMAIN ); ?></label></th><td><input id="oss_lenis_content" type="text" name="oss_options[lenis][contentSelector]" value="<?php echo esc_attr( $options['lenis']['contentSelector'] ); ?>" class="regular-text" /><p class="description"><?php echo esc_html__( 'CSS selector for the inner content element.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr>
								<th scope="row"><label for="oss_lenis_custom"><?php echo esc_html__( 'Advanced Options (JSON)', OSS_TEXT_DOMAIN ); ?></label></th>
								<td><textarea id="oss_lenis_custom" name="oss_options[lenis][custom]" rows="4" class="large-text code"><?php echo esc_textarea( $options['lenis']['custom'] ); ?></textarea><p class="description"><?php echo esc_html__( 'Provide a JSON object to merge into Lenis options.', OSS_TEXT_DOMAIN ); ?></p></td>
							</tr>
						</tbody>
					</table>
				</div>

				<div class="oss-library-options" data-library="gsap">
					<h2><?php echo esc_html__( 'GSAP / ScrollTrigger Options', OSS_TEXT_DOMAIN ); ?></h2>
					<table class="form-table" role="presentation">
						<tbody>
							<tr><th scope="row"><?php echo esc_html__( 'Use ScrollTo for anchors', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[gsap][enableScrollTo]" value="1" <?php checked( ! empty( $options['gsap']['enableScrollTo'] ) ); ?> /> <?php echo esc_html__( 'Enable GSAP ScrollTo for anchor links (requires ScrollToPlugin).', OSS_TEXT_DOMAIN ); ?></label><p class="description"><?php echo esc_html__( 'When enabled, anchor links use GSAP for scrolling instead of native behavior.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'ScrollTo Duration (s)', OSS_TEXT_DOMAIN ); ?></th><td><input type="number" step="0.01" name="oss_options[gsap][duration]" value="<?php echo esc_attr( $options['gsap']['duration'] ); ?>" /><p class="description"><?php echo esc_html__( 'Controls the time of the animation. Longer = slower movement.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'ScrollTo Ease', OSS_TEXT_DOMAIN ); ?></th><td><input type="text" name="oss_options[gsap][ease]" value="<?php echo esc_attr( $options['gsap']['ease'] ); ?>" class="regular-text" /><p class="description"><?php echo esc_html__( 'How the scroll accelerates and decelerates. Example: power2.out.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'ScrollTo AutoKill', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[gsap][autoKill]" value="1" <?php checked( ! empty( $options['gsap']['autoKill'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Automatically aborts scrolling if user interaction occurs.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Overwrite', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[gsap][overwrite]" value="1" <?php checked( ! empty( $options['gsap']['overwrite'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Whether to overwrite existing tweens on the same properties.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Extra Offset (px)', OSS_TEXT_DOMAIN ); ?></th><td><input type="number" step="1" name="oss_options[gsap][offset]" value="<?php echo esc_attr( $options['gsap']['offset'] ); ?>" /><p class="description"><?php echo esc_html__( 'Additional pixels to offset from the target position.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row" class="oss-subheading"><?php echo esc_html__( 'ScrollTrigger Defaults', OSS_TEXT_DOMAIN ); ?></th><td></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Scrub', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[gsap][scrollTriggerDefaults][scrub]" value="1" <?php checked( ! empty( $options['gsap']['scrollTriggerDefaults']['scrub'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Bind animation progress to scroll position.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Markers', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[gsap][scrollTriggerDefaults][markers]" value="1" <?php checked( ! empty( $options['gsap']['scrollTriggerDefaults']['markers'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Show start/end markers for debugging.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Pin', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[gsap][scrollTriggerDefaults][pin]" value="1" <?php checked( ! empty( $options['gsap']['scrollTriggerDefaults']['pin'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Pin the trigger element during scroll.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Toggle Actions', OSS_TEXT_DOMAIN ); ?></th><td><input type="text" name="oss_options[gsap][scrollTriggerDefaults][toggleActions]" value="<?php echo esc_attr( $options['gsap']['scrollTriggerDefaults']['toggleActions'] ); ?>" class="regular-text" /><p class="description"><?php echo esc_html__( 'Actions on enter/leave/enterBack/leaveBack. Example: play pause resume reset.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Anticipate Pin', OSS_TEXT_DOMAIN ); ?></th><td><input type="number" step="1" name="oss_options[gsap][scrollTriggerDefaults][anticipatePin]" value="<?php echo esc_attr( $options['gsap']['scrollTriggerDefaults']['anticipatePin'] ); ?>" /><p class="description"><?php echo esc_html__( 'Add extra pixel cushion to reduce jumps when pinning.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Fast Scroll End', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[gsap][scrollTriggerDefaults][fastScrollEnd]" value="1" <?php checked( ! empty( $options['gsap']['scrollTriggerDefaults']['fastScrollEnd'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Complete animations quickly on fast scrolls.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Prevent Overlaps', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[gsap][scrollTriggerDefaults][preventOverlaps]" value="1" <?php checked( ! empty( $options['gsap']['scrollTriggerDefaults']['preventOverlaps'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Avoid conflicting triggers running at the same time.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Once', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[gsap][scrollTriggerDefaults][once]" value="1" <?php checked( ! empty( $options['gsap']['scrollTriggerDefaults']['once'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Run the animation only once when it first enters.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr><th scope="row"><?php echo esc_html__( 'Horizontal', OSS_TEXT_DOMAIN ); ?></th><td><label><input type="checkbox" name="oss_options[gsap][scrollTriggerDefaults][horizontal]" value="1" <?php checked( ! empty( $options['gsap']['scrollTriggerDefaults']['horizontal'] ) ); ?> /></label><p class="description"><?php echo esc_html__( 'Use horizontal scrolling instead of vertical.', OSS_TEXT_DOMAIN ); ?></p></td></tr>
							<tr>
								<th scope="row"><label for="oss_gsap_triggers"><?php echo esc_html__( 'ScrollTrigger Configs (JSON Array)', OSS_TEXT_DOMAIN ); ?></label></th>
								<td><textarea id="oss_gsap_triggers" name="oss_options[gsap][triggersJSON]" rows="6" class="large-text code"><?php echo esc_textarea( $options['gsap']['triggersJSON'] ); ?></textarea><p class="description"><?php echo esc_html__( 'Provide an array of ScrollTrigger configs. Example: [{"trigger": ".el", "start":"top center", "end":"+=300", "scrub": true, "animation": {"from": {"opacity":0}, "to": {"opacity":1, "duration":1}}}]', OSS_TEXT_DOMAIN ); ?></p></td>
							</tr>
						</tbody>
					</table>
				</div>

				<?php submit_button( __( 'Save Changes', OSS_TEXT_DOMAIN ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Allowed libraries map.
	 *
	 * @return array<string, string>
	 */
	private function get_allowed_libraries(): array {
		return array(
			'locomotive' => __( 'Locomotive Scroll', OSS_TEXT_DOMAIN ),
			'gsap'       => __( 'GSAP / ScrollTrigger', OSS_TEXT_DOMAIN ),
			'lenis'      => __( 'Lenis', OSS_TEXT_DOMAIN ),
		);
	}

	/**
	 * Script locations map.
	 *
	 * @return array<string, string>
	 */
	private function get_script_locations(): array {
		return array(
			'header' => __( 'Header', OSS_TEXT_DOMAIN ),
			'footer' => __( 'Footer', OSS_TEXT_DOMAIN ),
		);
	}
}