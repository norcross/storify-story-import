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

	/**
	 * Parse and then run the admin redirect.
	 *
	 * @param  array   $args   The args we wanna add to the query string.
	 * @param  boolean $error  Whether it was a successful action.
	 * @param  string  $url    A URL to use as the base. Optional.
	 *
	 * @return void
	 */
	public function admin_redirect( $args = array(), $error = true, $url = '' ) {

		// Determine the result.
		$result = ! empty( $error ) ? 'error' : 'success';

		// Set my base args.
		$base   = array( 'post_type' => 'storify-stories', 'storify-fetch-result' => $result, 'storify-fetch-completed' => 1 );

		// Merge our args.
		$setup  = ! empty( $args ) ? wp_parse_args( $args, $base ) : $base ;

		// Confirm my URL.
		$url    = ! empty( $url ) ? $url : admin_url( 'edit.php' );

		// Now make the link.
		$link   = add_query_arg( $setup, esc_url_raw( $url ) );

		// Redirect and exit.
		wp_redirect( $link );
		exit();
	}

	/**
	 * Run our API call to Storify.
	 *
	 * @param  string  $endpoint  The endpoint of the API we are going to.
	 * @param  integer $paged     Which page to fetch. Defaults to page 1.
	 *
	 * @return mixed
	 */
	public function make_api_call( $endpoint = '', $page = 1 ) {

		// Set my URL.
		$url   = 'https://api.storify.com/v1/' . $endpoint;

		// Set my args.
		$setup = add_query_arg( array( 'page' => $page, 'per_page' => 30, 'direction' => 'asc' ), esc_url_raw( $url ) );

		// Make the API call.
		$call = wp_remote_get( $setup, array() );

		// Pull the guts.
		$guts = wp_remote_retrieve_body( $call );

		// Return my stuff.
		return json_decode( $guts, true );
	}

	/**
	 * Get my elements tied to a story.
	 *
	 * @param  integer $story_id  The story ID we are pulling from.
	 *
	 * @return array
	 */
	/**
	 * Get my elements tied to a story.
	 *
	 * @param  integer $story_id  The story ID we are pulling from.
	 * @param  boolean $parsed    Whether or not to parse the data.
	 *
	 * @return array
	 */
	public function get_story_elements( $story_id = 0, $parsed = true ) {

		// Bail without a post ID or wrong type.
		if ( empty( $story_id ) || 'storify-stories' !== get_post_type( $story_id ) ) {
			return false;
		}

		// Make our transient key.
		$key    = 'storify_story_elements_' . absint( $story_id );

		// If we don't want the cache'd version, delete the transient first.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			delete_transient( $key );
		}

		// Check the transient.
		if ( false === $data = get_transient( $key )  ) {

			// Setup my comment args.
			$setup  = array(
				'post_id' => absint( $story_id ),
				'type'    => 'storify-element',
				'order'   => 'ASC',
				'orderby' => 'comment_date'
			);

			// And fetch the elements.
			$data   = get_comments( $setup );

			// Bail if the user doesn't exist.
			if ( empty( $data ) || is_wp_error( $data ) ) {
				return false;
			}

			// Set our transient with our data.
			set_transient( $key, $data, WEEK_IN_SECONDS );
		}

		// and return the whole thing.
		return ! empty( $parsed ) ? StorifyStoryImport_Helper::parse_element_display( $data ) : $data;
	}

	/**
	 * Build out the display markup. Can be used to embed or build content.
	 *
	 * @param  integer $story_id  The ID of the story.
	 *
	 * @return HTML
	 */
	public function build_display_markup( $story_id = 0 ) {

		// Bail without a post ID or wrong type.
		if ( empty( $story_id ) || 'storify-stories' !== get_post_type( $story_id ) ) {
			return false;
		}

		// Fetch my items, parsed.
		if ( false === $parsed = storify_story_import()->get_story_elements( $story_id ) ) {
			return false;
		}

		/*
		// Make our transient key.
		$key    = 'storify_story_embed_' . absint( $story_id );

		// If we don't want the cache'd version, delete the transient first.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			delete_transient( $key );
		}

		// Check the transient.
		if ( false === $build = get_transient( $key )  ) {

			// Set our transient with our data.
			set_transient( $key, $build, WEEK_IN_SECONDS );
		}
		*/

		// Set an empty.
		$build  = '';

		// Set a simple counter.
		$i = 0;

		// Loop and display.
		foreach ( $parsed as $element ) {

			// Check for the source and type.
			$type   = ! empty( $element['type'] ) ? $element['type'] : '';
			$source = ! empty( $element['source'] ) ? $element['source'] : '';

			// Start my switch.
			switch ( $source ) {

				// Handle our twitter links.
				case 'twitter' :
					$embed  = wp_oembed_get( $element['link'], array( 'omit_script' => true, 'hide_thread' => true, 'conversation' => 'no' ) );
					break;

				// Handle our Flickr and YouTube links.
				case 'flickr' :
				case 'youtube' :
					$embed  = wp_oembed_get( $element['link'] );
					break;

				default :
					$embed  = StorifyStoryImport_Display::display_generic_element( $type, $element );

				// End all case breaks.
			}

			// And filter it.
			$embed  = apply_filters( 'storify_story_import_display_embed', $embed, $element, $story_id );

			// Skip if I have no embed to use.
			if ( empty( $embed ) ) {
				continue;
			}

			// And the build.
			$build .= '<div id="single-storify-element-' . absint( $i ) . '" class="single-storify-element">';
			$build .= $embed;
			$build .= '<p><a href="' . $element['link'] . '">Link</a></p>';
			$build .= '</div>';

			// Increment the counter.
			$i++;
		}

		// Adding the twitter JS file for embedding.
		// Notes: https://dev.twitter.com/web/embedded-tweets
		$build .= '<div class="storify-twitter-embed-js">';
			$build .= '<script async="" src="' . esc_url( 'https://platform.twitter.com/widgets.js' ) . '" charset="utf-8"></script></p>';
		$build .= '</div>';

		// And return my build.
		return $build;
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



/**
 * Our inital setup function when activated.
 *
 * @return void
 */
function storify_story_import_install() {

	// Set our installed option flag.
	add_option( 'storify_display_type', 'embed', '', false );

	// Include our action so that we may add to this later.
	do_action( 'storify_story_import_install' );

	// And flush our rewrite rules.
	flush_rewrite_rules();
}
register_activation_hook( STORIFY_STORY_IMPORT_FILE, 'storify_story_import_install' );


// Only for debugging as we build this out.
if ( ! function_exists( 'preprint' ) ) {
	/**
	 * Display array results in a readable fashion.
	 *
	 * @param  mixed   $display  The output we want to display.
	 * @param  boolean $die      Whether or not to die as soon as output is generated.
	 * @param  boolean $return   Whether to return the output or show it.
	 *
	 * @return mixed             Our printed (or returned) output.
	 */
	function preprint( $display, $die = false, $return = false ) {

		// Set an empty.
		$code   = '';

		// Add some CSS to make it a bit more readable.
		$style  = 'background-color: #fff; color: #000; font-size: 16px; line-height: 22px; padding: 5px; white-space: pre-wrap; white-space: -moz-pre-wrap; white-space: -pre-wrap; white-space: -o-pre-wrap; word-wrap: break-word;';

		// Filter the style.
		$style  = apply_filters( 'rkv_preprint_style', $style );

		// Generate the actual output.
		$code  .= '<pre style="' . $style . '">';
		$code  .= print_r( $display, 1 );
		$code  .= '</pre>';

		// Return if requested.
		if ( $return ) {
			return $code;
		}

		// Print if requested (the default).
		if ( ! $return ) {
			print $code;
		}

		// Die if you want to die.
		if ( $die ) {
			die();
		}
	}
}
