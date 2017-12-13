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
	}

	/**
	 * Check for our query string.
	 *
	 * @return void
	 */
	public function run_storify_request() {

		// Bail without our key.
		if ( empty( $_POST['storify-import-trigger'] ) || empty( $_POST['fetch-action'] ) ) {
			return;
		}

		// Handle my user fetching.
		if ( 'fetch-user' === $_POST['fetch-action'] && ! empty( $_POST['fetch-user-field'] ) ) {
			self::fetch_user_stories( $_POST['fetch-user-field'] );
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

		// preprint( $items, true );

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
		if ( false === $create = self::create_single_stories( $stories ) ) {
			wp_die( 'No story created', 'Data error' ); // Need some real error returns.
		}
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
	 *
	 * @return void
	 */
	public static function create_single_stories( $stories = array() ) {

		// Loop my stories.
		foreach ( $stories as $story ) {

			// preprint( $story, true );

			// Run my exists check.
			$exists = StorifyStoryImport_Helper::maybe_story_exists( $story['slug'] );

			// And handle it.
			$create = ! empty( $exists ) ? self::update_single_story( $exists, $story ) : self::create_single_story( $story );
		}

		// Return how many we made.
		return count( $stories );
	}

	/**
	 * Create a single story.
	 *
	 * @param  array $story  The array of story data.
	 *
	 * @return void
	 */
	public static function create_single_story( $story = array() ) {

		// Check my status.
		$status = 'published' === $story['status'] ? 'publish' : 'draft';

		// Set my meta array args.
		$meta   = array(
			'_storify_sid'       => esc_attr( $story['sid'] ),
			'_storify_created'   => esc_attr( $story['created'] ),
			'_storify_published' => esc_attr( $story['published'] ),
			'_storify_elements'  => false,
		);

		// Build the args.
		$setup  = array(
			'post_title'    => $story['title'],
			'post_name'     => $story['slug'],
			'post_excerpt'  => $story['description'],
			'post_status'   => $status,
			'post_author'   => get_current_user_id(),
			'post_type'     => 'storify-stories',
			'meta_input'    => $meta,
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
	 *
	 * @return void
	 */
	public static function update_single_story( $post_id = 0, $story = array() ) {

		// Check my status.
		$status = 'published' === $story['status'] ? 'publish' : 'draft';

		// Set my meta array args.
		$meta   = array(
			'_storify_sid'       => esc_attr( $story['sid'] ),
			'_storify_created'   => esc_attr( $story['created'] ),
			'_storify_published' => esc_attr( $story['published'] ),
		);

		// Build the args.
		$setup  = array(
			'ID'            => absint( $post_id ),
			'post_title'    => $story['title'],
			'post_name'     => $story['slug'],
			'post_excerpt'  => $story['description'],
			'post_status'   => $status,
			'meta_input'    => $meta,
		);

		// Update it.
		$update = wp_insert_post( $setup );

		// Check to error first.
		if ( is_wp_error( $update ) ) {
			return false;
		}

		// Return true.
		return true;
	}

	// End our class.
}

// Call our class.
$StorifyStoryImport_Process = new StorifyStoryImport_Process();
$StorifyStoryImport_Process->init();
