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
		add_action( 'admin_notices',                          array( $this, 'data_import_result'    )           );
		add_filter( 'parse_comment_query',                    array( $this, 'exclude_comment_list'  )           );
		add_action( 'admin_enqueue_scripts',                  array( $this, 'scripts_styles'        ),  10      );
		add_action( 'admin_menu',                             array( $this, 'settings_menu'         )           );
		add_filter( 'post_row_actions',                       array( $this, 'add_elements_link'     ),  10, 2   );
		add_filter( 'plugin_action_links',                    array( $this, 'quick_link'            ),  10, 2   );
	}

	/**
	 * Set the default sort order for various items
	 *
	 * @param  object $query  The existing WP_Comment_Query Object.
	 *
	 * @return object $query  The modified WP_Comment_Query Object.
	 */
	public function exclude_comment_list( $query ) {

		// preprint( $query->query_vars );

		// Remove the story-element type unless bypassed.
		if ( false !== apply_filters( 'storify_story_import_exclude_comments', true, $query ) ) {
			$query->query_vars['type__not_in'] = array( 'storify-element' );
		}

		// preprint( $query, true );

		// And send back the query.
		return $query;
	}

	/**
	 * Display the message based on our fetch result.
	 *
	 * @return void
	 */
	public function data_import_result() {

		// Make sure we are handling a fetch.
		if ( empty( $_GET['storify-fetch-completed'] ) ) {
			return;
		}

		// If it worked, handle it quickly.
		if ( ! empty( $_GET['storify-fetch-result'] ) && 'success' === sanitize_key( $_GET['storify-fetch-result'] ) ) {

			// And handle the notice.
			echo '<div class="notice notice-success is-dismissible storify-fetch-result-message">';
				echo '<p>' . esc_html__( 'The requested Storify data has been successfully imported!', 'storify-story-import' ) . '</p>';
			echo '</div>';

			// Then be done.
			return;
		}

		// Determine my error type.
		$error  = ! empty( $_GET['storify-fetch-error'] ) ? sanitize_key( $_GET['storify-fetch-error'] ) : false;

		// Start my switch.
		switch ( $error ) {

			case 'missing_username' :
				$errmsg = __( 'There was no username provided.', 'storify-story-import' );
				break;

			case 'no_user_content' :
				$errmsg = __( 'There was no content returned by the Storify API for this user.', 'storify-story-import' );
				break;

			case 'no_user_stories' :
				$errmsg = __( 'There was no stories returned by the Storify API for this user.', 'storify-story-import' );
				break;

			case 'no_user_story_data' :
				$errmsg = __( 'The requested stories had no data returned by the Storify API for this user.', 'storify-story-import' );
				break;

			case 'no_user_story_create' :
				$errmsg = __( 'Stories could not be created based on the provided data for this user.', 'storify-story-import' );
				break;

			case 'missing_single_url' :
				$errmsg = __( 'The URL for a story was not provided.', 'storify-story-import' );
				break;

			case 'invalid_single_url' :
				$errmsg = __( 'The URL for a story was invalid or otherwise malformed.', 'storify-story-import' );
				break;

			case 'no_single_story_content' :
				$errmsg = __( 'There was no content returned by the Storify API for this story.', 'storify-story-import' );
				break;

			case 'no_single_story_elements' :
				$errmsg = __( 'There were no elements returned by the Storify API for this story.', 'storify-story-import' );
				break;

			case 'no_single_story_data' :
				$errmsg = __( 'The data for this story could not be properly formatted.', 'storify-story-import' );
				break;

			case 'no_single_story_create' :
				$errmsg = __( 'This story could not be created based on the provided data.', 'storify-story-import' );
				break;

			case 'missing_story_id' :
				$errmsg = __( 'No post ID was provided for this story.', 'storify-story-import' );
				break;

			case 'invalid_story_id' :
				$errmsg = __( 'The post ID provided is invalid.', 'storify-story-import' );
				break;

			case 'missing_story_slug' :
				$errmsg = __( 'The requested story has no saved Storify username.', 'storify-story-import' );
				break;

			case 'no_single_elements_data' :
				$errmsg = __( 'There were no data elements returned by the Storify API for this story.', 'storify-story-import' );
				break;

			case 'invalid_single_story_elements' :
				$errmsg = __( 'The element data for this story could not be properly formatted.', 'storify-story-import' );
				break;

			case 'no_single_elements_create' :
				$errmsg = __( 'This story could not be updated based on the provided data.', 'storify-story-import' );
				break;

			default :
				$errmsg = __( 'There was an error with your request.', 'storify-story-import' );

			// End all case breaks.
		}

		// And the actual message.
		echo '<div class="notice notice-error is-dismissible storify-fetch-result-message">';
				echo '<p>' . esc_html( $errmsg ) . '</p>';
		echo '</div>';

		// And bail.
		return;
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
			$field .= '<button id="fetch-' . esc_attr( $action ) . '-action" name="fetch-action" class="storify-import-button button button-small button-secondary" value="' . esc_attr( $action ) . '" type="submit">' . esc_html( $args['button'] ) . '</button>';

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

		// Bail on trash or draft page.
		if ( ! empty( $_GET['post_status'] ) && in_array( $_GET['post_status'], array( 'trash', 'draft' ) ) ) {
			return $actions;
		}

		// Check if we've done this.
		$check  = get_post_meta( $post->ID, '_storify_elements', true );

		// Get my slug.
		$slug   = get_post_meta( $post->ID, '_storify_slug', true );

		// Make my label.
		$label  = empty( $check ) ? __( 'Fetch Elements', 'storify-story-import' ) : __( 'Update Elements', 'storify-story-import' );

		// First make the link.
		$link   = add_query_arg( array( 'post_type' => 'storify-stories', 'storify-elements-trigger' => 1, 'fetch-action' => 'elements', 'fetch-id' => $post->ID ), admin_url( 'edit.php' ) );

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
