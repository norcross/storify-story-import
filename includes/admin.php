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
		add_filter( 'post_row_actions',                       array( $this, 'add_elements_link'   ),  10, 2   );
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

		// Set a fields array.
		$fields = self::get_fields();

		// Handle the form wrap.
		echo '<div class="wrap storify-import-wrap">';

			// Output the title.
			echo '<h1>' . get_admin_page_title() . '</h1>';

			// Loop the fields.
			foreach ( $fields as $action => $args ) {

				// Wrap our form.
				echo '<form method="post" action="' . menu_page_url( self::$menu_slug, 0 ) . '">';

					echo self::fetch_field( $action, $args );

					// And a quick hidden field to trigger it all.
					echo '<input type="hidden" name="storify-import-trigger" value="1">';

				// Close the form.
				echo '</form>';
			}

		// And the entire div.
		echo '</div>';
	}

	/**
	 * Get my array of fields.
	 *
	 * @return array
	 */
	public static function get_fields() {

		// Set a fields array.
		return array(

			// Set my user field array.
			'user'  => array(
				'label'  => __( 'Username', 'storify-story-import' ),
				'button' => __( 'Get User Stories', 'storify-story-import' ),
				'desc'   => __( 'Enter the Storify username to retrieve data.', 'storify-story-import' ),
				'type'   => 'text',
			),

			// Set my single field array.
			'single'  => array(
				'label'  => __( 'Story URL', 'storify-story-import' ),
				'button' => __( 'Get Single Story', 'storify-story-import' ),
				'desc'   => __( 'Enter the single Storify URL to retrieve data.', 'storify-story-import' ),
				'type'   => 'url',
			),
		);
	}

	/**
	 * Create our combo text and button field.
	 *
	 * @param  boolean $echo    Whether to echo it out or not.
	 *
	 * @return HTML
	 */
	public static function fetch_field( $action = '', $args = array(), $echo = false ) {

		//preprint( $args, true );

		// Set an empty.
		$field  = '';

		// Open it up.
		$field .= '<p class="storify-import-field-wrapper">';

			// Add the label.
			$field .= '<span class="storify-import-label">' . esc_html( $args['label'] ) . '</span>';

			// The input field.
			$field .= '<input id="fetch-' . esc_attr( $action ) . '" name="fetch-' . esc_attr( $action ) . '-field" class="storify-import-field" type="' . esc_attr( $args['type'] ) . '" value="">';

			// The button field.
			$field .= '<button id="fetch-' . esc_attr( $action ) . '-action" name="fetch-action" class="storify-import-button button button-small button-secondary" value="fetch-' . esc_attr( $action ) . '" type="submit">' . esc_html( $args['button'] ) . '</button>';

			// And some text.
			$field .= '<span class="description storify-import-description">' . esc_html( $args['desc'] ) . '</span>';

			// And a nonce.
			$field .= wp_nonce_field( 'fetch-' . esc_attr( $action ) . '-action', 'fetch-' . esc_attr( $action ) . '-nonce', false, false );

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
	 * Filters the array of row action links on the supported post type list table.
	 *
	 * @param  array   $actions  The existing array of actions.
	 * @param  WP_Post $post     The post object.
	 *
	 * @return array   $actions  The modified array of actions.
	 */
	public function add_elements_link( $actions, $post ) {

		// Bail if we aren't on the post type.
		if ( 'storify-stories' !== $post->post_type ) {
			return $actions;
		}

		// Check if we've done this.
		$check  = get_post_meta( $post->ID, '_storify_elements', true );

		// Get my slug.
		$slug   = get_post_meta( $post->ID, '_storify_slug', true );

		// Make my label.
		$label  = empty( $check ) ? __( 'Fetch Elements', 'storify-story-import' ) : __( 'Update Elements', 'storify-story-import' );

		// First make the link.
		$link   = add_query_arg( array( 'post_type' => 'storify-stories', 'storify-posts-trigger' => 1, 'fetch-action' => 'fetch-elements', 'fetch-id' => $post->ID ), admin_url( 'edit.php' ) );

		// Return the string or the markup.
		$actions['storify'] = '<a title="' . esc_attr( $label ) . '" href="' . esc_url( $link ) . '">' . esc_html( $label ) . '</a>';

		// And return our actions.
		return $actions;
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
