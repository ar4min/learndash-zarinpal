<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit();

/**
* LearnDash_Zarinpal_Settings class
*
* This class is responsible for managing plugin settings.
*
* @since 0.1
*/
class LearnDash_Zarinpal_Settings {

	/**
	 * Plugin options
	 *
	 * @since 0.1
	 * @var array
	 */
	protected $options;

	/**
	 * Class __construct function
	 *
	 * @since 0.1
	 */
	public function __construct() {

		$this->options = get_option( 'learndash_Zarinpal_settings', array() );

		add_action( 'admin_init', array( $this, 'check_learndash_plugin' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu') );
		add_filter( 'learndash_admin_tabs', array( $this, 'admin_tabs' ), 10, 1 );
		add_filter( 'learndash_admin_tabs_on_page', array( $this, 'admin_tabs_on_page' ), 10, 3 );
	}

	/**
	 * Check if LearnDash plugin is active
	 */
	public function check_learndash_plugin() {
		if ( ! is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			//deactivate_plugins( plugin_basename( LEARNDASH_ZARINPAL_FILE ) );
			unset( $_GET['activate'] );
		}
	}

	/**
	 * Display admin notice when LearnDash plugin is not activated
	 */
	public function admin_notices() {
		echo '<div class="error"><p>' . __( 'LearnDash plugin is required to activate LearnDash Zarinpal add-on plugin. Please activate it first.', 'learndash-zarinpal' ) . '</p></div>';
	}

	/**
	 * Enqueue admin scripts and styles
	 */
	public function enqueue_scripts() {
		if ( ! is_admin() || ! isset( $_GET['page'] ) || ( isset( $_GET['page'] ) && 'learndash-zarinpal-settings' != $_GET['page'] ) ) {
			return;
		}

		// we need to load the LD plugin style.css, sfwd_module.css and sfwd_module.js because we want to replicate the styling on the admin tab.
		wp_enqueue_style( 'learndash_style', LEARNDASH_LMS_PLUGIN_URL . 'assets/css/style.css' );
		wp_enqueue_style( 'sfwd-module-style', LEARNDASH_LMS_PLUGIN_URL . 'assets/css/sfwd_module.css' );
		wp_enqueue_script( 'sfwd-module-script', LEARNDASH_LMS_PLUGIN_URL . 'assets/js/sfwd_module.js', array( 'jquery' ), LEARNDASH_VERSION, true );

		// Need this because sfwd_module.js expects a json data array to be passed.
		$data = array();
		$data = array( 'json' => json_encode( $data ) );
		wp_localize_script( 'sfwd-module-script', 'sfwd_data', $data );

		// Load our admin JS
		wp_enqueue_script( 'learndash-zarinpal-admin-script', LEARNDASH_ZARINPAL_PLUGIN_URL . 'assets/js/learndash-zarinpal-admin-script.js', array( 'jquery' ), LEARNDASH_ZARINPAL_VERSION, true );
	}

	/**
	 * Register plugin settings, add initial values and define settings section and fields
	 */
	public function register_settings() {
		register_setting( 'learndash_zarinpal_settings_group', 'learndash_zarinpal_settings', array( $this, 'sanitize_settings' ) );

		$options = array(
			'MerchantID'     => '',
			'test_mode'            => 0,
			'currency'             => 'ریال',
		    'return_url'   =>'',
		    'zaringit'   =>0,
		);

		add_option( 'learndash_zarinpal_settings', $options );

		add_settings_section( 'learndash_zarinpal_settings', __return_null(), __return_empty_array(), 'learndash-zarinpal-settings' );

		$settings = $this->get_zarinpal_settings();
	}

	/**
	 * Sanitize setting inputs
	 * @param  array $inputs Non-sanitized inputs
	 * @return array         Sanitized inputs
	 */
	public function sanitize_settings( $inputs ) {

		foreach ( $inputs as $key => $input ) {
			$inputs[ $key ] = sanitize_text_field( $input );
		}

		return $inputs;
	}

	/**
	 * Add submenu page for settings page
	 */
	public function admin_menu() {
		add_submenu_page( 'edit.php?post_type=sfwd-courses', __( 'Zarinpal Settings', 'learndash-zarinpal' ), __('Zarinpal Settings','learndash-zarinpal'), 'manage_options', 'admin.php?page=learndash-zarinpal-settings', array( $this, 'zarinpal_settings_page' ) );

		add_submenu_page( 'learndash-lms-non-existant', __( 'Zarinpal Settings', 'learndash-zarinpal' ), __( 'Zarinpal Settings', 'learndash-zarinpal' ), 'manage_options', 'learndash-zarinpal-settings', array( $this, 'zarinpal_settings_page' )  );
	}

	/**
	 * Add admin tabs for settings page
	 * @param  array $tabs Original tabs
	 * @return array       New modified tabs
	 */
	public function admin_tabs( $tabs ) {
		$tabs['zarinpal'] = array(
			'link'      => 'admin.php?page=learndash-zarinpal-settings',
			'name'      => __( 'Zarinpal Settings', 'learndash-zarinpal' ),
			'id'        => 'admin_page_learndash-zarinpal-settings',
			'menu_link' => 'edit.php?post_type=sfwd-courses&page=sfwd-lms_sfwd_lms.php_post_type_sfwd-courses',
		);

		return $tabs;
	}

	/**
	 * Display active tab on settings page
	 * @param  array $admin_tabs_on_page Original active tabs
	 * @param  array $admin_tabs         Available admin tabs
	 * @param  int 	 $current_page_id    ID of current page
	 * @return array                     Currenct active tabs
	 */
	public function admin_tabs_on_page( $admin_tabs_on_page, $admin_tabs, $current_page_id ) {
		
		// $admin_tabs_on_page['admin_page_learndash-zarinpal-settings'] = array_merge( $admin_tabs_on_page['sfwd-courses_page_sfwd-lms_sfwd_lms_post_type_sfwd-courses'], (array) $admin_tabs_on_page['admin_page_learndash-zarinpal-settings'] );

		foreach ( $admin_tabs as $key => $value ) {
			if( $value['id'] == $current_page_id && $value['menu_link'] == 'edit.php?post_type=sfwd-courses&page=sfwd-lms_sfwd_lms.php_post_type_sfwd-courses' ) {

				$admin_tabs_on_page[$current_page_id][] = 'zarinpal';
				return $admin_tabs_on_page;
			}
		}

		return $admin_tabs_on_page;
	}

	/**
	 * Output settings page
	 */
	public function zarinpal_settings_page() {

		if ( ! current_user_can( 'manage_options' )	) {
			wp_die( __( 'Cheatin huh?', 'learndash-zarinpal' ) );
		}

		$options = $this->get_zarinpal_settings();

		?>
		<div class="wrap">
			<h2 class="learndash-zarinpal-settings-header"><?php _e( 'zarinpal Settings', 'learndash-zarinpal' ); ?></h2>
			<form method="post" action="options.php">
				<div class="sfwd_options_wrapper sfwd_settings_left">
					<div id="advanced-sortables" class="meta-box-sortables">
						<div id="sfwd-courses_metabox" class="postbox learndash-zarinpal-settings-postbox">
							<div class="handlediv" title="<?php _e( 'Click to toggle', 'learndash-zarinpal' ); ?>"><br></div>
							<h3 class="hndle"><span><?php _e( 'Zarinpal Settings', 'learndash-zarinpal' ); ?></span></h3>

							<div class="inside">
								<div class="sfwd sfwd_options sfwd-courses_settings">
									<?php settings_fields( 'learndash_zarinpal_settings_group' ); ?>
									<?php foreach ( $options as $key => $option ) : ?>
										<?php $option['id'] = $key; ?>

										<div class="sfwd_input " id="sfwd-zarinpal_<?php echo $key; ?>">
											<span class="sfwd_option_label" style="text-align:right;vertical-align:top;">
												<a class="sfwd_help_text_link" style="cursor:pointer;" title="Click for Help!" onclick="toggleVisibility('sfwd-zarinpal_<?php echo $key; ?>_tip');">
													<img src="<?php echo LEARNDASH_ZARINPAL_PLUGIN_URL . 'assets/images/question.png' ?>">
													<label class="sfwd_label textinput"><?php echo $option['name']; ?></label>
												</a>
											</span>
											<span class="sfwd_option_input">
												<div class="sfwd_option_div">
													
													<?php $callback = $option['type'] . '_callback'; ?>

													<?php $this->$callback( $option ); ?>

												</div>
												<div class="sfwd_help_text_div" style="display:none" id="sfwd-zarinpal_<?php echo $key; ?>_tip"><label class="sfwd_help_text"><?php echo $option['desc']; ?></label></div>
											</span>
											<p style="clear:left"></p>
										</div>

									<?php endforeach; ?>
								</div>
							</div>
						
						</div>
					</div>
				</div>
				<p class="submit" style="clear: both;">
				<?php submit_button( __( 'Update Options »', 'learndash-zarinpal' ), 'primary', 'submit', false );?>
				</p>
			</form>
		</div>
		<?php
	}
	

	/**
	 * Define settings fields
	 * @return array Settings of the plugin
	 */
	public function get_zarinpal_settings() {
		
		$settings = array(
			// 'test_mode' => array(
			// 	'name'  => __( 'Test Mode', 'learndash-zarinpal' ),
			// 	'desc'  => __( 'Check this box to enable test mode.', 'learndash-zarinpal' ),
			// 	'type'  => 'checkbox',
			// 	'value' => isset( $this->options['test_mode'] ) ? $this->options['test_mode'] : 0,
			// ),
			'MerchantID' => array(
				'name' => __( 'MerchantID', 'learndash-zarinpal' ),
				'desc' => __( 'MerchantID used on this site.', 'learndash-zarinpal' ),
				'type' => 'text',
				'value' => isset( $this->options['MerchantID'] ) ? $this->options['MerchantID'] : '',
			),
			'return_url' => array(
				'name' => __( 'return_url', 'learndash-zarinpal' ),
				'desc' => __( 'return_url used on this site.', 'learndash-zarinpal' ),
				'type' => 'text',
				'value' => isset( $this->options['return_url'] ) ? $this->options['return_url'] : '',
			),
			// 'zaringit' => array(
			// 	'name'  => __( 'zaringit', 'learndash-zarinpal' ),
			// 	'desc'  => __( 'Check this box to enable zaringit.', 'learndash-zarinpal' ),
			// 	'type'  => 'checkbox',
			// 	'value' => isset( $this->options['zaringit'] ) ? $this->options['zaringit'] : 0,
			// ),
			
		);
        //return_url
		return apply_filters( 'learndash_zarinpal_settings', $settings );
	}

	/**
	 * Callback function for text type settings
	 * @param  array $args Arguments of the settings
	 */
	public function text_callback( $args ) {
		$attributes = '';
		if ( isset( $args['attributes'] ) && is_array( $args['attributes'] ) ) {
			$attributes = array_map( function( $key, $value ) {
				return "{$key}=\"{$value}\"";
			}, array_keys( $args['attributes'] ), $args['attributes'] );

			$attributes = implode( ' ', $attributes );
		}

		$html  = '<input type="text" name="learndash_zarinpal_settings[' . $args['id'] . ']" value="' . $args['value'] . '" class="regular-text"' . $attributes . '>';

		echo $html;
	}

	/**
	 * Callback function for checkbox type settings
	 * @param  array $args Arguments of the settings
	 */
	public function checkbox_callback( $args ) {
		$html = '<input type="checkbox" name="learndash_zarinpal_settings[' . $args['id'] .']" value="1" ' . checked( $this->options[ $args['id'] ], 1, false ) . '>';

		echo $html;
	}

	/**
	 * Callback function for dropdown type settings
	 * @param  array $args Arguments of the settings
	 */
	public function dropdown_callback( $args ) {
		$html = '<select name="learndash_zarinpal_settings[' . $args['id'] .']">';
			foreach ( $args['options'] as $value => $label ) {
				$html .= '<option value="' . $value . '" ' . selected( $value, $args['value'], false ) . '>' . $label . '</option>';
			}
		$html .= '</select>';

		echo $html;
	}
}

new LearnDash_Zarinpal_Settings();
