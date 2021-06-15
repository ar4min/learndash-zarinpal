<?php
/**
 * Plugin Name: LearnDash For Zarinpal 
 * Plugin URI: 
 * Description: Zarinpal payment gateway with LearnDash. 
 * Version: 1.0
 * Author: Armin Zahedi
 * Author URI: http://ar4min.ir/
 * Text Domain: learndash-for-zarinpal
 * Domain Path: languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}


/*
?>
<style>
    .ld-currency{
        display: none;
    }
</style>
<?php
*/
// Check if class name already exists
if ( ! class_exists( 'LearnDash_Zarinpal' ) ) :

/**
* Main class
*
* @since  0.1
*/
final class LearnDash_Zarinpal {
	
	/**
	 * The one and only true LearnDash_Zarinpal instance
	 *
	 * @since 0.1
	 * @access private
	 * @var object $instance
	 */
	private static $instance;

	/**
	 * Instantiate the main class
	 *
	 * This function instantiates the class, initialize all functions and return the object.
	 * 
	 * @since 0.1
	 * @return object The one and only true LearnDash_Zarinpal instance.
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) && ( ! self::$instance instanceof LearnDash_Zarinpal ) ) {

			self::$instance = new LearnDash_Zarinpal();
			self::$instance->setup_constants();
			
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
            
			self::$instance->includes();
		}

		return self::$instance;
	}	
	
	/**
	 * Function for setting up constants
	 *
	 * This function is used to set up constants used throughout the plugin.
	 *
	 * @since 0.1
	 */
	public function setup_constants() {

		// Plugin version
		if ( ! defined( 'LEARNDASH_ZARINPAL_VERSION' ) ) {
			define( 'LEARNDASH_ZARINPAL_VERSION', '1.0.0' );
		}

		// Plugin file
		if ( ! defined( 'LEARNDASH_ZARINPAL_FILE' ) ) {
			define( 'LEARNDASH_ZARINPAL_FILE', __FILE__ );
		}		

		// Plugin folder path
		if ( ! defined( 'LEARNDASH_ZARINPAL_PLUGIN_PATH' ) ) {
			define( 'LEARNDASH_ZARINPAL_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
		}

		// Plugin folder URL
		if ( ! defined( 'LEARNDASH_ZARINPAL_PLUGIN_URL' ) ) {
			define( 'LEARNDASH_ZARINPAL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}
	}

	/**
	 * Load text domain used for translation
	 *
	 * This function loads mo and po files used to translate text strings used throughout the 
	 * plugin.
	 *
	 * @since 0.1
	 */
	public function load_textdomain() {

		// Set filter for plugin language directory
		$lang_dir = dirname( plugin_basename( LEARNDASH_ZARINPAL_FILE ) ) . '/languages/';
		$lang_dir = apply_filters( 'learndash_zarinpal_languages_directory', $lang_dir );

		// Load plugin translation file
		load_plugin_textdomain( 'learndash-zarinpal', false, $lang_dir );
		
		// include translation/update class
		//include LEARNDASH_ZarinPal_PLUGIN_PATH . 'includes/class-translations-ld-zarinpal.php';
	}

	/**
	 * Includes all necessary PHP files
	 *
	 * This function is responsible for including all necessary PHP files.
	 *
	 * @since  0.1
	 */
	public function includes() {
		$options = get_option( 'learndash_zarinpal_settings', array() );

		if ( is_admin() ) {
		    add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			include LEARNDASH_ZARINPAL_PLUGIN_PATH . '/includes/admin/settings/class-settings.php';
		}
		include LEARNDASH_ZARINPAL_PLUGIN_PATH . '/includes/class-zarinpal-legacy-checkout-integration.php';
	}
	
	public function enqueue_scripts() {
       wp_enqueue_style( 'learndash-zarinpal-admin-style', LEARNDASH_ZARINPAL_PLUGIN_URL . 'assets/css/learndash-zarinpal-admin-style.css', array(), LEARNDASH_ZARINPAL_VERSION );
	}
}

endif; // End if class exists check

/**
 * The main function for returning instance
 *
 * @since 0.1
 * @return object The one and only true instance.
 */
function learndash_zarinpal() {
	return LearnDash_Zarinpal::instance();
}

// Run plugin
learndash_Zarinpal();


//End
