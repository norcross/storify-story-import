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

		// Bail without our key.
		if ( empty( $_REQUEST['fetch-action'] ) ) {
			return;
		}

		// Handle my user fetching.
		if ( ! empty( $_POST['fetch-action'] ) && ! empty( $_POST['fetch-user-field'] ) && 'fetch-user' === $_POST['fetch-action'] ) {
			self::fetch_user_stories( $_POST['fetch-user-field'] );
		}

		// Handle my elements fetching.
		if ( ! empty( $_GET['fetch-action'] ) && ! empty( $_GET['fetch-id'] ) && 'fetch-elements' === $_GET['fetch-action'] ) {
			self::fetch_story_elements( $_GET['fetch-id'] );
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

		// Fetch our items.
		$call   = self::make_api_call( 'stories/' . $user );

		// preprint( $call, true );

		// Bail with no request data.
		if ( empty( $call ) || empty( $call['content'] ) ) {
			wp_die( 'No content', 'Data error' ); // Need some real error returns.
		}

		// Bail with no stories.
		if ( empty( $call['content']['stories'] ) ) {
			wp_die( 'No stories', 'Data error' ); // Need some real error returns.
		}

		// Parse my list.
		if ( false === $stories = StorifyStoryImport_Helper::parse_story_list( $call['content']['stories'] ) ) {
			wp_die( 'No story data', 'Data error' ); // Need some real error returns.
		}

		// Run the creation.
		if ( false === $create = self::process_user_stories( $stories, $user ) ) {
			wp_die( 'No story created', 'Data error' ); // Need some real error returns.
		}

		// First make the link.
		$link   = add_query_arg( array( 'post_type' => 'storify-stories', 'page' => 'storify-import-settings', 'fetch-completed' => 1  ), admin_url( 'edit.php' ) );

		// Redirect and exit.
		wp_redirect( $link );
		exit();
	}

	/**
	 * Fetch the elements of a single story.
	 *
	 * @param  integer $post_id  The ID we want to get our elements for.
	 *
	 * @return mixed
	 */
	public static function fetch_story_elements( $post_id = 0 ) {

		// Get our meta items.
		$user   = get_post_meta( $post_id, '_storify_username', true );
		$slug   = get_post_meta( $post_id, '_storify_slug', true );

		// Fetch our items.
		$call   = self::make_api_call( 'stories/' . $user . '/' . $slug );

		// preprint( $items, true );

		// Bail with no request data.
		if ( empty( $call ) || empty( $call['content'] ) ) {
			wp_die( 'No element content', 'Data error' ); // Need some real error returns.
		}

		// Bail with no stories.
		if ( empty( $call['content']['elements'] ) ) {
			wp_die( 'No elements', 'Data error' ); // Need some real error returns.
		}

		// Parse my list.
		if ( false === $elements = StorifyStoryImport_Helper::parse_element_list( $call['content']['elements'], $post_id ) ) {
			wp_die( 'No elements data', 'Data error' ); // Need some real error returns.
		}

		// Run the creation.
		if ( false === $create = self::process_story_elements( $elements, $post_id ) ) {
			wp_die( 'No elements created', 'Data error' ); // Need some real error returns.
		}

		// First make the link.
		$link   = add_query_arg( array( 'post_type' => 'storify-stories', 'fetch-completed' => 1  ), admin_url( 'edit.php' ) );

		// Redirect and exit.
		wp_redirect( $link );
		exit();
	}

	/**
	 * Run our API call to Storify.
	 *
	 * @param  string $endpoint  The endpoint of the API we are going to.
	 *
	 * @return mixed
	 */
	public static function make_api_call( $endpoint = '' ) {

		// Set my URL.
		$url   = 'http://api.storify.com/v1/' . $endpoint;

		// Set my args.
		$args = array();

		// Make the API call.
		$call = wp_remote_get( $url, $args );

		// Pull the guts.
		$guts = wp_remote_retrieve_body( $call );

		// Return my stuff.
		return json_decode( $guts, true );
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

		// Check my status.
		$status = 'published' === $story['status'] ? 'publish' : 'draft';

		// Set my meta array args.
		$meta   = array(
			'_storify_sid'         => esc_attr( $story['sid'] ),
			'_storify_created'     => esc_attr( $story['created'] ),
			'_storify_published'   => esc_attr( $story['published'] ),
			'_storify_username'    => esc_attr( $user ),
			'_storify_slug'        => esc_attr( $story['slug'] ),
			'_storify_insert_date' => current_time( 'timestamp' ),
			'_storify_elements'    => false,
		);

		// Build the args.
		$setup  = array(
			'post_title'      => $story['title'],
			'post_name'       => $story['slug'],
			'post_excerpt'    => $story['description'],
			'post_date'       => date( 'Y-m-d H:i:s', $story['published'] ),
			'post_status'     => $status,
			'post_author'     => get_current_user_id(),
			'post_type'       => 'storify-stories',
			'comment_status'  => 'closed',
			'ping_status'     => 'closed',
			'meta_input'      => $meta,
		);

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

		// Check my status.
		$status = 'published' === $story['status'] ? 'publish' : 'draft';

		// Set my meta array args.
		$meta   = array(
			'_storify_sid'         => esc_attr( $story['sid'] ),
			'_storify_created'     => esc_attr( $story['created'] ),
			'_storify_published'   => esc_attr( $story['published'] ),
			'_storify_username'    => esc_attr( $user ),
			'_storify_slug'        => esc_attr( $story['slug'] ),
			'_storify_update_date' => current_time( 'timestamp' ),
		);

		// Build the args.
		$setup  = array(
			'ID'              => absint( $post_id ),
			'post_title'      => $story['title'],
			'post_name'       => $story['slug'],
			'post_excerpt'    => $story['description'],
			'post_date'       => date( 'Y-m-d H:i:s', $story['published'] ),
			'post_status'     => $status,
			'comment_status'  => 'closed',
			'ping_status'     => 'closed',
			'meta_input'      => $meta,
		);

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

		// Loop the elements.
		foreach ( $elements as $element ) {

			// preprint( $element, true );

			// Setup my args.
			$setup  = array(
				'comment_post_ID'       => absint( $post_id ),
				'comment_author'        => $user->display_name,
				'comment_content'       => '',
				'comment_author_url'    => '',
				'comment_author_email'  => $user->user_email,
				'comment_type'          => 'storify-element',
				'user_id'               => $user->ID,
				'comment_date'          => date( 'Y-m-d H:i:s', $element['posted'] ),
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
		}

		// Loop my stories.
		foreach ( $elements as $element ) {

			// preprint( $element, true );

			// Run my exists check.
			$exists = StorifyStoryImport_Helper::maybe_story_exists( $element['id'] );

			// And handle it.
			$create = ! empty( $exists ) ? self::update_single_element( $exists, $element, $post_id, $user ) : self::create_single_element( $element, $post_id, $user );
		}

		// Update my elements flag.
		update_post_meta( $post_id, '_storify_elements', true );

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
	public static function create_single_element( $post_id = 0, $element = array(), $user ) {

		// Setup my args.
		$setup  = array(
			'comment_post_ID'       => absint( $post_id ),
			'comment_author'        => $user->display_name,
			'comment_content'       => '',
			'comment_author_url'    => '',
			'comment_author_email'  => $user->user_email,
			'comment_type'          => 'storify-element',
			'user_id'               => $user->ID,
			'comment_date'          => date( 'Y-m-d H:i:s', $element['posted'] ),
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
	public static function update_single_element( $comment_id = 0, $post_id = 0, $element = array(), $user ) {

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
			'comment_date'          => date( 'Y-m-d H:i:s', $element['posted'] ),
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
