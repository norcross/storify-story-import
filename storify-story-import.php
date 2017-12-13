<?php
/**
 * Plugin Name: Storify Story Import
 * Plugin URI: https://github.com/norcross/storify-story-import
 * Description: Import your Storify stories to WordPress
 * Author: Andrew Norcross
 * Author URI: http://andrewnorcross.com/
 * Version: 0.0.1
 * Text Domain: storify-story-import
 * Requires WP: 4.4
 * Domain Path: languages
 * GitHub Plugin URI: https://github.com/norcross/storify-story-import
 * @package StorifyStoryImport
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Call our class.
 */
final class StorifyStoryImport {

	/**
	 * StorifyStoryImport instance.
	 *
	 * @access private
	 * @since  1.0
	 * @var    StorifyStoryImport The one true StorifyStoryImport
	 */
	private static $instance;

	/**
	 * The version number of StorifyStoryImport.
	 *
	 * @access private
	 * @since  1.0
	 * @var    string
	 */
	private $version = '0.0.1';

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return $instance
	 */
	public static function instance() {

		// Run the check to see if we have the instance yet.
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof StorifyStoryImport ) ) {

			// Set our instance.
			self::$instance = new StorifyStoryImport;

			// Set my plugin constants.
			self::$instance->setup_constants();

			// Run our version compare.
			if ( version_compare( PHP_VERSION, '5.4', '<' ) ) {

				// Deactivate the plugin.
				deactivate_plugins( STORIFY_STORY_IMPORT_BASE );

				// And display the notice.
				wp_die( sprintf( __( 'Your current version of PHP is below the minimum version required by Storify Story Import. Please contact your host and request that your version be upgraded to 5.4 or later. <a href="%s">Click here</a> to return to the plugins page.', 'storify-story-import' ), admin_url( '/plugins.php' ) ) );
			}

			// Set my file includes.
			self::$instance->includes();

			// Load our textdomain.
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
		}

		// And return the instance.
		return self::$instance;
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since 1.0
	 * @access protected
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'storify-story-import' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @since 1.0
	 * @access protected
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'storify-story-import' ), '1.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	private function setup_constants() {

		// Define our file base.
		if ( ! defined( 'STORIFY_STORY_IMPORT_BASE' ) ) {
			define( 'STORIFY_STORY_IMPORT_BASE', plugin_basename( __FILE__ ) );
		}

		// Set our base directory constant.
		if ( ! defined( 'STORIFY_STORY_IMPORT_DIR' ) ) {
			define( 'STORIFY_STORY_IMPORT_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Folder URL.
		if ( ! defined( 'STORIFY_STORY_IMPORT_URL' ) ) {
			define( 'STORIFY_STORY_IMPORT_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin root file.
		if( ! defined( 'STORIFY_STORY_IMPORT_FILE' ) ) {
			define( 'STORIFY_STORY_IMPORT_FILE', __FILE__ );
		}

		// Set our includes directory constant.
		if ( ! defined( 'STORIFY_STORY_IMPORT_INCLS' ) ) {
			define( 'STORIFY_STORY_IMPORT_INCLS', __DIR__ . '/includes' );
		}

		// Set our assets directory constant.
		if ( ! defined( 'STORIFY_STORY_IMPORT_ASSETS' ) ) {
			define( 'STORIFY_STORY_IMPORT_ASSETS', __DIR__ . '/assets' );
		}

		// Set our API base URL constant.
		if ( ! defined( 'STORIFY_STORY_IMPORT_API_BASE' ) ) {
			define( 'STORIFY_STORY_IMPORT_API_BASE', 'http://api.storify.com/v1/' );
		}

		// Set our version constant.
		if ( ! defined( 'STORIFY_STORY_IMPORT_VER' ) ) {
			define( 'STORIFY_STORY_IMPORT_VER', $this->version );
		}
	}

	/**
	 * Load our actual files in the places they belong.
	 *
	 * @return void
	 */
	public function includes() {

		// Load our helper first.
		require_once STORIFY_STORY_IMPORT_INCLS . '/helper.php';

		// Load our various classes.
		require_once STORIFY_STORY_IMPORT_INCLS . '/post-types.php';

		// Load the classes that are only accessible via admin.
		if ( is_admin() ) {
			require_once STORIFY_STORY_IMPORT_INCLS . '/admin.php';
			require_once STORIFY_STORY_IMPORT_INCLS . '/process.php';
		}

		// Load the classes that are only accessible via front end.
		if ( ! is_admin() ) {
			require_once STORIFY_STORY_IMPORT_INCLS . '/display.php';
		}
	}

	/**
	 * Loads the plugin language files
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function load_textdomain() {

		// Set filter for plugin's languages directory.
		$lang_dir = dirname( plugin_basename( STORIFY_STORY_IMPORT_FILE ) ) . '/languages/';

		/**
		 * Filters the languages directory path to use for StorifyStoryImport.
		 *
		 * @param string $lang_dir The languages directory path.
		 */
		$lang_dir = apply_filters( 'storify_story_import_languages_dir', $lang_dir );

		// Traditional WordPress plugin locale filter.

		global $wp_version;

		$get_locale = get_locale();

		if ( $wp_version >= 4.7 ) {
			$get_locale = get_user_locale();
		}

		/**
		 * Defines the plugin language locale used in StorifyStoryImport.
		 *
		 * @var $get_locale The locale to use. Uses get_user_locale()` in WordPress 4.7 or greater,
		 *                  otherwise uses `get_locale()`.
		 */
		$locale = apply_filters( 'plugin_locale', $get_locale, 'storify-story-import' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'storify-story-import', $locale );

		// Setup paths to current locale file.
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/storify-story-import/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/storify-story-import/ folder
			load_textdomain( 'storify-story-import', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/storify-story-import/languages/ folder
			load_textdomain( 'storify-story-import', $mofile_local );
		} else {
			// Load the default language files.
			load_plugin_textdomain( 'storify-story-import', false, $lang_dir );
		}
	}

	// End our class.
}

/**
 * The main function responsible for returning the one true StorifyStoryImport
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $storify_story_import = storify_story_import(); ?>
 *
 * @since 1.0
 * @return StorifyStoryImport The one true StorifyStoryImport Instance
 */
function storify_story_import() {
	return StorifyStoryImport::instance();
}
storify_story_import();
