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
	 * @param array $input Raw input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_options( $input ): array {
		// Nonce verification (Settings API also verifies, this is an extra guard).
		if ( isset( $_POST['_wpnonce'] ) ) {
			check_admin_referer( 'oss_settings_group-options' );
		}

		// Ensure input is an array to avoid type errors.
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return Plugin::get_options();
		}

		$defaults  = Plugin::get_default_options();
		$sanitized = array();

		$allowed_libraries = array( 'locomotive', 'lenis', 'scrollbar', 'native' );
		$allowed_easing    = array( 'ease', 'linear', 'ease-in', 'ease-out', 'cubic-bezier' );
		$allowed_location  = array( 'header', 'footer' );

		$active_library = isset( $input['active_library'] ) ? sanitize_text_field( $input['active_library'] ) : $defaults['active_library'];
		if ( ! in_array( $active_library, $allowed_libraries, true ) ) {
			add_settings_error( 'oss_options', 'oss_active_library', __( 'Invalid library selected. Reverted to default.', OSS_TEXT_DOMAIN ), 'error' );
			$active_library = $defaults['active_library'];
		}
		$sanitized['active_library'] = $active_library;

		$scroll_speed = isset( $input['scroll_speed'] ) ? (float) $input['scroll_speed'] : (float) $defaults['scroll_speed'];
		if ( $scroll_speed < 0 ) {
			$scroll_speed = (float) $defaults['scroll_speed'];
		}
		$sanitized['scroll_speed'] = $scroll_speed;

		$easing = isset( $input['easing'] ) ? sanitize_text_field( $input['easing'] ) : $defaults['easing'];
		if ( ! in_array( $easing, $allowed_easing, true ) ) {
			add_settings_error( 'oss_options', 'oss_easing', __( 'Invalid easing selected. Reverted to default.', OSS_TEXT_DOMAIN ), 'error' );
			$easing = $defaults['easing'];
		}
		$sanitized['easing'] = $easing;

		$anchor_offset = isset( $input['anchor_offset'] ) ? (int) $input['anchor_offset'] : (int) $defaults['anchor_offset'];
		$sanitized['anchor_offset'] = $anchor_offset;

		$enable_mobile = ! empty( $input['enable_mobile'] ) ? 1 : 0;
		$sanitized['enable_mobile'] = $enable_mobile;

		$script_location = isset( $input['script_location'] ) ? sanitize_text_field( $input['script_location'] ) : $defaults['script_location'];
		if ( ! in_array( $script_location, $allowed_location, true ) ) {
			$script_location = $defaults['script_location'];
		}
		$sanitized['script_location'] = $script_location;

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

		wp_localize_script( 'oss-admin', 'ossAdminData', array(
			'i18n' => array(
				'generalTab' => __( 'General', OSS_TEXT_DOMAIN ),
				'libraryTab' => __( 'Library', OSS_TEXT_DOMAIN ),
			),
		) );
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

			<h2 class="nav-tab-wrapper" id="oss-tabs">
				<a href="#oss-tab-general" class="nav-tab nav-tab-active" data-target="#oss-tab-general"><?php echo esc_html__( 'General', OSS_TEXT_DOMAIN ); ?></a>
				<a href="#oss-tab-library" class="nav-tab" data-target="#oss-tab-library"><?php echo esc_html__( 'Library', OSS_TEXT_DOMAIN ); ?></a>
			</h2>

			<form method="post" action="options.php">
				<?php settings_fields( 'oss_settings_group' ); ?>

				<div id="oss-tab-general" class="oss-tab-content active">
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
								<th scope="row"><label for="oss_scroll_speed"><?php echo esc_html__( 'Scroll Speed', OSS_TEXT_DOMAIN ); ?></label></th>
								<td>
									<input type="number" step="0.05" min="0" id="oss_scroll_speed" name="oss_options[scroll_speed]" value="<?php echo esc_attr( $options['scroll_speed'] ); ?>" />
									<p class="description"><?php echo esc_html__( 'General speed multiplier used by libraries that support it.', OSS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="oss_easing"><?php echo esc_html__( 'Easing', OSS_TEXT_DOMAIN ); ?></label></th>
								<td>
									<select id="oss_easing" name="oss_options[easing]">
										<?php foreach ( $this->get_allowed_easing_options() as $value => $label ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $options['easing'], $value ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php echo esc_html__( 'Easing function used for scrolling where supported.', OSS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="oss_anchor_offset"><?php echo esc_html__( 'Anchor Offset (px)', OSS_TEXT_DOMAIN ); ?></label></th>
								<td>
									<input type="number" step="1" id="oss_anchor_offset" name="oss_options[anchor_offset]" value="<?php echo esc_attr( $options['anchor_offset'] ); ?>" />
									<p class="description"><?php echo esc_html__( 'Offset applied when scrolling to anchors (e.g., fixed header height).', OSS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php echo esc_html__( 'Enable on Mobile', OSS_TEXT_DOMAIN ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="oss_options[enable_mobile]" value="1" <?php checked( (int) $options['enable_mobile'], 1 ); ?> />
										<?php echo esc_html__( 'Enable smooth scrolling on mobile devices', OSS_TEXT_DOMAIN ); ?>
									</label>
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
									<p class="description"><?php echo esc_html__( 'Choose whether to load scripts in header or footer.', OSS_TEXT_DOMAIN ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<div id="oss-tab-library" class="oss-tab-content">
					<p><?php echo esc_html__( 'Library-specific settings and tips. Options shown are contextual to your selected library.', OSS_TEXT_DOMAIN ); ?></p>

					<div class="oss-library-options" data-library="locomotive">
						<h2><?php echo esc_html__( 'Locomotive Scroll', OSS_TEXT_DOMAIN ); ?></h2>
						<p class="description"><?php echo esc_html__( 'Ensure the Locomotive Scroll library is loaded on the page. This plugin provides a thin wrapper to initialize it. Use the Scroll Speed as multiplier; some options may not directly map and may require custom code.', OSS_TEXT_DOMAIN ); ?></p>
					</div>

					<div class="oss-library-options" data-library="lenis">
						<h2><?php echo esc_html__( 'Lenis', OSS_TEXT_DOMAIN ); ?></h2>
						<p class="description"><?php echo esc_html__( 'Lenis is a lightweight smooth scroll library. The plugin will attempt to initialize it with the provided speed and easing where applicable.', OSS_TEXT_DOMAIN ); ?></p>
					</div>

					<div class="oss-library-options" data-library="scrollbar">
						<h2><?php echo esc_html__( 'Smooth Scrollbar', OSS_TEXT_DOMAIN ); ?></h2>
						<p class="description"><?php echo esc_html__( 'Smooth Scrollbar replaces native scrollbars on a container. Ensure markup is compatible and the library is loaded.', OSS_TEXT_DOMAIN ); ?></p>
					</div>

					<div class="oss-library-options" data-library="native">
						<h2><?php echo esc_html__( 'Native CSS Smooth Scroll', OSS_TEXT_DOMAIN ); ?></h2>
						<p class="description"><?php echo esc_html__( 'Uses CSS scroll-behavior with a JS helper for anchor offset. Fast and dependency-free.', OSS_TEXT_DOMAIN ); ?></p>
					</div>
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
			'lenis'      => __( 'Lenis', OSS_TEXT_DOMAIN ),
			'scrollbar'  => __( 'Smooth Scrollbar', OSS_TEXT_DOMAIN ),
			'native'     => __( 'Native CSS', OSS_TEXT_DOMAIN ),
		);
	}

	/**
	 * Easing options map.
	 *
	 * @return array<string, string>
	 */
	private function get_allowed_easing_options(): array {
		return array(
			'ease'          => __( 'ease', OSS_TEXT_DOMAIN ),
			'linear'        => __( 'linear', OSS_TEXT_DOMAIN ),
			'ease-in'       => __( 'ease-in', OSS_TEXT_DOMAIN ),
			'ease-out'      => __( 'ease-out', OSS_TEXT_DOMAIN ),
			'cubic-bezier'  => __( 'cubic-bezier', OSS_TEXT_DOMAIN ),
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