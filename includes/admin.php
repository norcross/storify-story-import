<?php
/**
 * Admin setup.
 *
 * It is all our admin things.
 *
 * @package StorifyStoryImport
 */

/**
 * Start our engines.
 */
class StorifyStoryImport_Admin {

	/**
	 * The slugs being used for the menus.
	 */
	public static $root_slug = 'edit.php?post_type=storify-stories';
	public static $menu_slug = 'storify-import-settings';
	public static $hook_slug = 'storify-stories_page_storify-import-settings';

	/**
	 * Call our hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_enqueue_scripts',                  array( $this, 'scripts_styles'      ),  10      );
		add_action( 'admin_menu',                             array( $this, 'settings_menu'       )           );
		add_filter( 'plugin_action_links',                    array( $this, 'quick_link'          ),  10, 2   );
	}

	/**
	 * Load our admin CSS file.
	 *
	 * @param  string $hook  Where on the admin we are.
	 *
	 * @return void
	 */
	public function scripts_styles( $hook ) {

		// Bail if no hook or doesn't match.
		if ( empty( $hook ) || $hook !== self::$hook_slug ) {
			return;
		}

		// Set a file suffix structure based on whether or not we want a minified version.
		$file   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'storify.import.admin.css' : 'storify.import.admin.min.css';

		// Set a version for whether or not we're debugging.
		$vers   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : STORIFY_STORY_IMPORT_VER;

		// Load the CSS file.
		wp_enqueue_style( 'storify-import-admin', STORIFY_STORY_IMPORT_URL . '/assets/' . $file, array(), $vers, 'all' );
	}

	/**
	 * Call our top-level nav item.
	 *
	 * @return void
	 */
	public function settings_menu() {

		// Now add back our settings page.
		add_submenu_page( self::$root_slug, __( 'Storify Data Import', 'storify-story-import' ), __( 'Data Import', 'storify-story-import' ), apply_filters( 'storify_story_import_menu_item_cap', 'manage_options' ), self::$menu_slug, array( __class__, 'settings_page' ) );
	}

	/**
	 * Our actual settings page.
	 *
	 * @return HTML
	 */
	public static function settings_page() {

		// Handle the form wrap.
		echo '<div class="wrap storify-import-wrap">';

			// Output the title.
			echo '<h1>' . get_admin_page_title() . '</h1>';

			// Wrap our form.
			echo '<form method="post" action="' . menu_page_url( self::$menu_slug, 0 ) . '">';

				// Our username + fetch button.
				echo self::fetch_field( 'fetch-user', __( 'Username', 'storify-story-import' ), __( 'Get Stories', 'storify-story-import' ) );

				// And a quick hidden field to trigger it all.
				echo '<input type="hidden" name="storify-import-trigger" value="1">';

			// Close the form.
			echo '</form>';

		// And the entire div.
		echo '</div>';
	}

	/**
	 * Create our combo text and button field.
	 *
	 * @param  string  $action  The name of the action we are doing.
	 * @param  string  $label   The label for the field.
	 * @param  string  $text    The button text.
	 * @param  boolean $echo    Whether to echo it out or not.
	 *
	 * @return HTML
	 */
	public static function fetch_field( $action = '', $label = '', $text = '', $echo = false ) {

		// Make my nonce item.
		$nonce  = $action . '-nonce';

		// Set an empty.
		$field  = '';

		// Open it up.
		$field .= '<p class="storify-import-field-wrapper">';

			// Add the label.
			$field .= '<span class="storify-import-label">' . esc_html( $label ) . '</span>';

			// The input field.
			$field .= '<input id="' . esc_attr( $action ) . '" name="' . esc_attr( $action ) . '-field" class="storify-import-field" type="text" value="">';

			// The button field.
			$field .= '<button id="' . esc_attr( $action ) . '-action" name="fetch-action" class="storify-import-button button button-small button-secondary" value="' . esc_attr( $action ) . '" type="submit">' . esc_html( $text ) . '</button>';

			// And some text.
			$field .= '<span class="description">' . __( 'Enter the required field to retrieve data.', 'storify-story-import' ) . '</span>';

			// And a nonce.
			$field .= wp_nonce_field( $nonce, $nonce, false, false );

		// Close it up.
		$field .= '</p>';

		// Echo if requested.
		if ( ! empty( $echo ) ) {
			echo $field;
		}

		// Just return it.
		return $field;
	}

	/**
	 * Add our "settings" links to the plugins page.
	 *
	 * @param  array  $links  The existing array of links.
	 * @param  string $file   The file we are actually loading from.
	 *
	 * @return array  $links  The updated array of links.
	 */
	public function quick_link( $links, $file ) {

		// Check to make sure we are on the correct plugin.
		if ( ! empty( $file ) && $file == STORIFY_STORY_IMPORT_BASE ) {

			// Get the link based on license status.
			$quick  = '<a href="' . menu_page_url( self::$menu_slug, 0 ) . '">' . __( 'Settings', 'storify-story-import' ) . '</a>';;

			// Add the link to the array.
			array_push( $links, $quick );
		}

		// Return the links.
		return $links;
	}

	// End our class.
}

// Call our class.
$StorifyStoryImport_Admin = new StorifyStoryImport_Admin();
$StorifyStoryImport_Admin->init();
