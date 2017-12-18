<?php
/**
 * Our actual processing setup.
 *
 * Do the various checks for query keys.
 *
 * @package StorifyStoryImport
 */

/**
 * Start our engines.
 */
class StorifyStoryImport_Process {

	/**
	 * Call our hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_init',                             array( $this, 'run_storify_request' )           );
		add_filter( 'duplicate_comment_id',                   array( $this, 'bypass_dupe_check'   ),  10, 2   );
	}

	/**
	 * Check for our query string.
	 *
	 * @return void
	 */
	public function run_storify_request() {

		// Our trigger from the settings page.
		if ( ! empty( $_POST['storify-import-trigger'] ) ) {

			// preprint( $_POST, true );

			// Set some variables.
			$action = ! empty( $_POST['fetch-action'] ) ? sanitize_text_field( $_POST['fetch-action'] ) : '';
			$user   = ! empty( $_POST['fetch-user-field'] ) ? sanitize_text_field( $_POST['fetch-user-field'] ) : '';
			$single = ! empty( $_POST['fetch-single-field'] ) ? sanitize_text_field( $_POST['fetch-single-field'] ) : '';

			// @@todo add the nonce check

			// Handle my user fetching.
			if ( 'fetch-user' === esc_attr( $action ) ) {
				self::fetch_user_stories( $user );
			}

			// Handle my single fetching.
			if ( 'fetch-single' === esc_attr( $action ) ) {
				self::fetch_single_story( $single );
			}

		}

		// Our trigger from the posts page.
		if ( ! empty( $_GET['storify-elements-trigger'] ) ) {

			// preprint( $_GET, true );

			// Parse out my ID.
			$id = ! empty( $_GET['fetch-id'] ) ? absint( $_GET['fetch-id'] ) : 0;

			// Handle my elements fetching.
			self::fetch_story_elements( $id );
		}
	}

	/**
	 * Fetch all the user stories and make a data array.
	 *
	 * @param  string $user  The user we have.
	 *
	 * @return mixed
	 */
	public static function fetch_user_stories( $user = '' ) {

		// Bail with no single user.
		if ( empty( $user ) ) {
			storify_story_import()->admin_redirect( array( 'page' => 'storify-import-settings', 'storify-fetch-error' => 'missing_username' ) );
		}

		// Fetch our items.
		$call   = storify_story_import()->make_api_call( 'stories/' . $user );

		// preprint( $call, true );

		// Bail with no request data.
		if ( empty( $call ) || empty( $call['content'] ) ) {
			storify_story_import()->admin_redirect( array( 'page' => 'storify-import-settings', 'storify-fetch-error' => 'no_user_content' ) );
		}

		// preprint( $call['content'], true );

		// Bail with no stories.
		if ( empty( $call['content']['stories'] ) ) {
			storify_story_import()->admin_redirect( array( 'page' => 'storify-import-settings', 'storify-fetch-error' => 'no_user_stories' ) );
		}

		// preprint( $call['content']['stories'], true );

		// Parse my list.
		if ( false === $stories = StorifyStoryImport_Helper::parse_story_list( $call['content']['stories'] ) ) {
			storify_story_import()->admin_redirect( array( 'page' => 'storify-import-settings', 'storify-fetch-error' => 'no_user_story_data' ) );
		}

		// preprint( $stories, true );

		// Run the creation.
		if ( false === $create = self::process_user_stories( $stories, $user ) ) {
			storify_story_import()->admin_redirect( array( 'page' => 'storify-import-settings', 'storify-fetch-error' => 'no_user_story_create' ) );
		}

		// Process the admin redirect.
		storify_story_import()->admin_redirect( array( 'page' => 'storify-import-settings' ), false );
	}

	/**
	 * Fetch a single story.
	 *
	 * @param  string $single  The URL of the story.
	 *
	 * @return mixed
	 */
	public static function fetch_single_story( $single = '' ) {

		// Bail with no single URL.
		if ( empty( $single ) ) {
			storify_story_import()->admin_redirect( array( 'page' => 'storify-import-settings', 'storify-fetch-error' => 'missing_single_url' ) );
		}

		// Get the path parsed out for the API call.
		$parsed = parse_url( $single, PHP_URL_PATH );

		// preprint( $parsed, true );

		// Bail with no single URL.
		if ( empty( $parsed ) ) {
			storify_story_import()->admin_redirect( array( 'page' => 'storify-import-settings', 'storify-fetch-error' => 'invalid_single_url' ) );
		}

		// Fetch our items.
		$call   = storify_story_import()->make_api_call( 'stories' . $parsed );

		// preprint( $call, true );

		// Bail with no request data.
		if ( empty( $call ) || empty( $call['content'] ) ) {
			storify_story_import()->admin_redirect( array( 'page' => 'storify-import-settings', 'storify-fetch-error' => 'no_single_story_content' ) );
		}

		// unset( $call['content']['stats'] );
		// preprint( $call['content'], true );
		// preprint( array( $call['content'] ), true );

		// Parse my list.
		if ( false === $stories = StorifyStoryImport_Helper::parse_story_list( array( $call['content'] ) ) ) {
			storify_story_import()->admin_redirect( array( 'page' => 'storify-import-settings', 'storify-fetch-error' => 'no_single_story_data' ) );
		}

		// preprint( $stories, true );

		// Parse out the username.
		$user   = $call['content']['author']['username'];

		// Run the creation.
		if ( false === $create = self::process_user_stories( $stories, $user ) ) {
			storify_story_import()->admin_redirect( array( 'page' => 'storify-import-settings', 'storify-fetch-error' => 'no_single_story_create' ) );
		}

		// Process the admin redirect.
		storify_story_import()->admin_redirect( array( 'page' => 'storify-import-settings' ), false );
	}

	/**
	 * Fetch the elements of a single story.
	 *
	 * @param  integer $post_id  The ID we want to get our elements for.
	 *
	 * @return mixed
	 */
	public static function fetch_story_elements( $post_id = 0 ) {

		// Bail with no post ID.
		if ( empty( $post_id ) ) {
			storify_story_import()->admin_redirect( array( 'storify-fetch-error' => 'missing_story_id' ) );
		}

		// Bail on an invalid post type.
		if ( 'storify-stories' !== get_post_type( $post_id ) ) {
			storify_story_import()->admin_redirect( array( 'storify-fetch-error' => 'invalid_story_id' ) );
		}

		// Get our meta items.
		$user   = get_post_meta( $post_id, '_storify_username', true );
		$slug   = get_post_meta( $post_id, '_storify_slug', true );

		// Bail on a missing username.
		if ( empty( $user ) ) {
			storify_story_import()->admin_redirect( array( 'storify-fetch-error' => 'missing_username' ) );
		}

		// Bail on a missing story slug.
		if ( empty( $slug ) ) {
			storify_story_import()->admin_redirect( array( 'storify-fetch-error' => 'missing_story_slug' ) );
		}

		// Make our endpoint.
		$endpnt = 'stories/' . esc_attr( $user ) . '/' . esc_attr( $slug );

		// Fetch our items.
		$call   = storify_story_import()->make_api_call( $endpnt );

		// preprint( $call, true );

		// Bail with no request data.
		if ( empty( $call ) || empty( $call['content'] ) ) {
			storify_story_import()->admin_redirect( array( 'storify-fetch-error' => 'no_single_story_content' ) );
		}

		// unset( $call['content']['stats'] );
		// unset( $call['content']['author'] );

		// preprint( $call['content'], true );

		// preprint( $call['content']['totalElements'] );
		// preprint( count( $call['content']['elements'] ) );
		// preprint( $call['content']['elements'], true );

		// Bail with no stories.
		if ( empty( $call['content']['elements'] ) ) {
			storify_story_import()->admin_redirect( array( 'storify-fetch-error' => 'no_single_story_elements' ) );
		}

		// preprint( $call['content']['elements'], true );

		// Do the looping to figure it out all the elements.
		if ( false === $merged = StorifyStoryImport_Helper::merge_story_elements( $call['content'], $endpnt ) ) {
			storify_story_import()->admin_redirect( array( 'storify-fetch-error' => 'invalid_single_story_elements' ) );
		}

		// preprint( $merged, true );

		// Parse my list.
		if ( false === $elements = StorifyStoryImport_Helper::parse_element_list( $merged, $post_id ) ) {
			storify_story_import()->admin_redirect( array( 'storify-fetch-error' => 'no_single_elements_data' ) );
		}

		// preprint( $elements, true );

		// Run the creation.
		if ( false === $create = self::process_story_elements( $elements, $post_id ) ) {
			storify_story_import()->admin_redirect( array( 'storify-fetch-error' => 'no_single_elements_create' ) );
		}

		// Process the admin redirect.
		storify_story_import()->admin_redirect( null, false );
	}

	/**
	 * Create my individual stories.
	 *
	 * @param  array  $stories  The array of story data.
	 * @param  string $user     The username we pulled from.
	 *
	 * @return void
	 */
	public static function process_user_stories( $stories = array(), $user = '' ) {

		// Loop my stories.
		foreach ( $stories as $story ) {

			// preprint( $story, true );

			// Run my exists check.
			$exists = StorifyStoryImport_Helper::maybe_story_exists( $story['slug'] );

			// And handle it.
			$create = ! empty( $exists ) ? self::update_single_story( $exists, $story, $user ) : self::create_single_story( $story, $user );
		}

		// Return how many we made.
		return count( $stories );
	}

	/**
	 * Create a single story.
	 *
	 * @param  array  $story  The array of story data.
	 * @param  string $user   The username we pulled from.
	 *
	 * @return void
	 */
	public static function create_single_story( $story = array(), $user = '' ) {

		// Set my meta array args.
		$format = array( '_storify_username' => esc_attr( $user ), '_storify_insert_date' => current_time( 'timestamp' ), '_storify_elements' => false );
		$meta   = StorifyStoryImport_Helper::format_single_story_meta( $format, $story );

		// Build the args, including the current user ID.
		$setup  = StorifyStoryImport_Helper::format_single_story_setup( array( 'post_author' => get_current_user_id() ), $story, $meta );

		// Insert it.
		$insert = wp_insert_post( $setup );

		// Check to error first.
		if ( is_wp_error( $insert ) ) {
			return false;
		}

		// Return true.
		return true;
	}

	/**
	 * Update my individual stories.
	 *
	 * @param  integer $post_id  The ID we are updating.
	 * @param  array   $story    The array of story data.
	 * @param  string  $user     The username we pulled from.
	 *
	 * @return void
	 */
	public static function update_single_story( $post_id = 0, $story = array(), $user = '' ) {

		// Set my meta array args.
		$format = array( '_storify_username' => esc_attr( $user ), '_storify_update_date' => current_time( 'timestamp' ) );
		$meta   = StorifyStoryImport_Helper::format_single_story_meta( $format, $story );

		// Build the args, adding the post ID so we can update it.
		$setup  = StorifyStoryImport_Helper::format_single_story_setup( array( 'ID' => absint( $post_id ) ), $story, $meta );

		// Update it.
		$update = wp_update_post( $setup );

		// Check to error first.
		if ( is_wp_error( $update ) ) {
			return false;
		}

		// Return true.
		return true;
	}

	/**
	 * Create my individual elements.
	 *
	 * @param  array   $elements  The array of story data.
	 * @param  integer $post_id   The post ID we attach the elements to.
	 *
	 * @return void
	 */
	public static function process_story_elements( $elements = array(), $post_id = 0 ) {

		// Fetch my user.
		$user   = wp_get_current_user();

		// Loop my stories.
		foreach ( $elements as $element ) {

			// preprint( $element, true );

			// Run my exists check.
			$exists = StorifyStoryImport_Helper::maybe_story_exists( $element['id'] );

			// And handle it.
			$create = ! empty( $exists ) ? self::update_single_element( $exists, $element, $post_id, $user ) : self::create_single_element( $element, $post_id, $user );
		}

		// Update my elements flag.
		update_post_meta( $post_id, '_storify_elements', count( $elements ) );

		// And return true.
		return true;
	}

	/**
	 * Create a single element.
	 *
	 * @param  integer $post_id  The post ID we are attaching the element to.
	 * @param  array   $element  The array of element data.
	 * @param  object  $user     The user object we pulled from.
	 *
	 * @return void
	 */
	public static function create_single_element( $element = array(), $post_id = 0, $user ) {

		// Setup my args.
		$setup  = array(
			'comment_post_ID'       => absint( $post_id ),
			'comment_author'        => $user->display_name,
			'comment_content'       => '',
			'comment_author_url'    => '',
			'comment_author_email'  => $user->user_email,
			'comment_type'          => 'storify-element',
			'user_id'               => $user->ID,
			'comment_date'          => date( 'Y-m-d H:i:s', $element['added'] ),
			'comment_approved'      => 1,
		);

		// Add my "comment",
		$insert = wp_new_comment( $setup );

		// Bail on error.
		if ( empty( $insert ) || is_wp_error( $insert ) ) {
			return false;
		}

		// Now all add my meta.
		add_comment_meta( $insert, '_element_id', $element['id'], true );
		add_comment_meta( $insert, '_element_eid', $element['eid'], true );
		add_comment_meta( $insert, '_element_type', $element['type'], true );
		add_comment_meta( $insert, '_element_link', $element['link'], true );
		add_comment_meta( $insert, '_element_source', $element['source'], true );
		add_comment_meta( $insert, '_element_attrib', $element['attrib'], true );

		// And return true.
		return true;
	}

	/**
	 * Update my individual elements.
	 *
	 * @param  integer $comment_id  The ID we are updating.
	 * @param  integer $post_id     The post ID we are attaching the element to.
	 * @param  array   $element     The array of story data.
	 * @param  object  $user        The user object we pulled from.
	 *
	 * @return void
	 */
	public static function update_single_element( $comment_id = 0, $element = array(), $post_id = 0, $user ) {

		// Setup my args.
		$setup  = array(
			'comment_ID'            => absint( $comment_id ),
			'comment_post_ID'       => absint( $post_id ),
			'comment_author'        => $user->display_name,
			'comment_content'       => '',
			'comment_author_url'    => '',
			'comment_author_email'  => $user->user_email,
			'comment_type'          => 'storify-element',
			'user_id'               => $user->ID,
			'comment_date'          => date( 'Y-m-d H:i:s', $element['added'] ),
			'comment_approved'      => 1,
		);

		// Add my "comment",
		$update = wp_update_comment( $setup );

		// Bail on error.
		if ( empty( $update ) || is_wp_error( $update ) ) {
			return false;
		}

		// Now all add my meta.
		add_comment_meta( $update, '_element_id', $element['id'], true );
		add_comment_meta( $update, '_element_eid', $element['eid'], true );
		add_comment_meta( $update, '_element_type', $element['type'], true );
		add_comment_meta( $update, '_element_link', $element['link'], true );
		add_comment_meta( $update, '_element_source', $element['source'], true );
		add_comment_meta( $update, '_element_attrib', $element['attrib'], true );

		// And return true.
		return true;
	}

	/**
	 * Filters the ID, if any, of the duplicate comment found when creating a new comment.
	 *
	 * Return an empty value from this filter to allow what WP considers a duplicate comment.
	 *
	 * @since 4.4.0
	 *
	 * @param int   $dupe_id     ID of the comment identified as a duplicate.
	 * @param array $commentdata Data for the comment being created.
	 */
	public function bypass_dupe_check( $dupe_id, $commentdata ) {

		// If we are doing a storify element, don't flag them as dupes.
		if ( ! empty( $commentdata['comment_type'] ) && 'storify-element' === $commentdata['comment_type'] ) {
			return false;
		}

		// Return whatever the initial result was.
		return $dupe_id;
	}

	// End our class.
}

// Call our class.
$StorifyStoryImport_Process = new StorifyStoryImport_Process();
$StorifyStoryImport_Process->init();
