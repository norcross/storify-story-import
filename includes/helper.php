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
class StorifyStoryImport_Helper {

	/**
	 * Get the API settings we need, whether they are stored or in the config.
	 *
	 * @param  string $key      Our option table key.
	 * @param  string $single   Get 1 get out of the array.
	 * @param  mixed  $default  What the default value should be.
	 *
	 * @return string / array
	 */
	public static function get_single_setting( $key = '', $single = '', $default = false, $return = 'array' ) {

		// Get my initial data.
		$settings   = get_option( $key, $default );

		// If we didn't specify a single key, then return the entire array.
		if ( empty( $single ) ) {
			return $settings;
		}

		// Return the single key requested, or false.
		return isset( $settings[ $single ] ) ? $settings[ $single ] : false;
	}

	/**
	 * Get the pieces of user info we need for a story or element.
	 *
	 * @param  integer $post_id  The post (story) ID.
	 *
	 * @return array
	 */
	public static function get_userdata_for_import( $post_id = 0 ) {

		// If we have no post ID, just use the current.
		$user   = ! empty( $post_id ) ? get_userdata( get_post_field( 'post_author', $post_id, 'raw' ) ) : wp_get_current_user();

		// Now parse my bits.
		$name   = ! empty( $user->display_name ) ? $user->display_name : __( 'Storify Element', 'storify-story-import' );
		$email  = ! empty( $user->user_email ) ? $user->user_email : get_option( 'admin_email' );

		// Now build out the user data array we need.
		$data   = array(
			'id'    => absint( $user->ID ),
			'name'  => esc_attr( $name ),
			'email' => sanitize_email( $email ),
		);

		// Return our data, filtered.
		return apply_filters( 'storify_story_import_userdata', $data, $user, $post_id );
	}

	/**
	 * Determine if a story already exists.
	 *
	 * @param  string $slug  The slug we're checking for.
	 *
	 * @return boolean / integer
	 */
	public static function maybe_story_exists( $slug = '' ) {

		// Call the global database.
		global $wpdb;

		// Set up our query.
		$setup  = $wpdb->prepare("
			SELECT  post_id
			FROM    $wpdb->postmeta
			WHERE   meta_key = '%s'
			AND     meta_value = '%s'
			LIMIT   1
		", esc_sql( '_storify_slug' ), esc_sql( $slug ) );

		// Process the query.
		$query  = $wpdb->get_col( $setup );

		// Bail if no items came back.
		if ( empty( $query ) || empty( $query[0] ) ) {
			return false;
		}

		// Return my post ID.
		return absint( $query[0] );
	}

	/**
	 * Format the post args array before we insert or update a story.
	 *
	 * @param  array $args   Any additional args to add.
	 * @param  array $story  The base story to get our data from.
	 * @param  array $meta   Any metadata that gets added.
	 *
	 * @return array
	 */
	public static function format_single_story_setup( $args = array(), $story = array(), $meta = array() ) {

		// Check my status.
		$status = 'published' === $story['status'] ? 'publish' : 'draft';

		// Build the args.
		$base   = array(
			'post_title'      => $story['title'],
			'post_name'       => $story['slug'],
			'post_excerpt'    => $story['description'],
			'post_date'       => date( 'Y-m-d H:i:s', $story['published'] ),
			'post_status'     => $status,
			'post_type'       => 'storify-stories',
			'comment_status'  => 'closed',
			'ping_status'     => 'closed',
			'meta_input'      => $meta,
		);

		// Merge our formatted array.
		$setup   = ! empty( $args ) ? wp_parse_args( $args, $base ) : $base;

		// Return our array with a filter.
		return apply_filters( 'storify_story_import_story_setup', $setup );
	}

	/**
	 * Format the meta array before we insert or update a story.
	 *
	 * @param  array $args   Any additional meta to add.
	 * @param  array $story  The base story to get our data from.
	 *
	 * @return array
	 */
	public static function format_single_story_meta( $args = array(), $story = array() ) {

		// Set my base meta array args.
		$base   = array(
			'_storify_sid'         => esc_attr( $story['sid'] ),
			'_storify_created'     => esc_attr( $story['created'] ),
			'_storify_published'   => esc_attr( $story['published'] ),
			'_storify_slug'        => esc_attr( $story['slug'] ),
		);

		// Merge our formatted array.
		$meta   = ! empty( $args ) ? wp_parse_args( $args, $base ) : $base;

		// Return our array with a filter.
		return apply_filters( 'storify_story_import_story_meta', $meta, $args, $story );
	}

	/**
	 * Format the comment args array before we insert or update an element.
	 *
	 * @param  array $args     Any additional args to add.
	 * @param  array $element  The base story to get our data from.
	 * @param  array $user     The user data to attach.
	 * @param  array $meta     Any metadata that gets added.
	 *
	 * @return array
	 */
	public static function format_single_element_setup( $args = array(), $element = array(), $user = array(), $meta = array() ) {

		// Check for meta (or make our own).
		$meta   = ! empty( $meta ) ? $meta : StorifyStoryImport_Helper::format_single_element_meta( array(), $element );

		// Setup my args.
		$base   = array(
			'comment_author'        => $user['name'],
			'comment_content'       => $element['text'],
			'comment_author_url'    => '',
			'comment_author_email'  => $user['email'],
			'comment_type'          => 'storify-element',
			'user_id'               => $user['id'],
			'comment_date'          => date( 'Y-m-d H:i:s', $element['added'] ),
			'comment_approved'      => 1,
			'comment_meta'          => $meta,
		);

		// Merge our formatted array.
		$setup   = ! empty( $args ) ? wp_parse_args( $args, $base ) : $base;

		// Return our array with a filter.
		return apply_filters( 'storify_story_import_story_setup', $setup, $args, $element, $user );
	}

	/**
	 * Format the meta array before we insert or update a story.
	 *
	 * @param  array $args   Any additional meta to add.
	 * @param  array $story  The base story to get our data from.
	 *
	 * @return array
	 */
	public static function format_single_element_meta( $args = array(), $element = array() ) {

		// Set my base meta array args.
		$base   = array(
			'_storify_element_id'       => esc_attr( $element['id'] ),
			'_storify_element_eid'      => esc_attr( $element['eid'] ),
			'_storify_element_type'     => esc_attr( $element['type'] ),
			'_storify_element_link'     => esc_attr( $element['link'] ),
			'_storify_element_title'    => esc_attr( $element['title'] ),
			'_storify_element_source'   => esc_attr( $element['source'] ),
			'_storify_element_attrib'   => esc_attr( $element['attrib'] ),
		);

		// Merge our formatted array.
		$meta   = ! empty( $args ) ? wp_parse_args( $args, $base ) : $base;

		// Return our array with a filter.
		return apply_filters( 'storify_story_import_element_meta', $meta, $args, $element );
	}

	/**
	 * Check to see if the element (comment) already exists.
	 *
	 * @param  string $id  The original ID from Storify that we kept.
	 *
	 * @return boolean / integer
	 */
	public static function maybe_element_exists( $id = '' ) {

		// Call the global database.
		global $wpdb;

		// Set up our query.
		$setup  = $wpdb->prepare("
			SELECT  comment_id
			FROM    $wpdb->commentmeta
			WHERE   meta_key = '%s'
			AND     meta_value = '%s'
			LIMIT   1
		", esc_sql( '_element_id' ), esc_sql( $id ) );

		// Process the query.
		$query  = $wpdb->get_col( $setup );

		// Bail if no items came back.
		if ( empty( $query ) || empty( $query[0] ) ) {
			return false;
		}

		// Return my post ID.
		return absint( $query[0] );
	}

	/**
	 * Take the large array of stories for a user and break it down.
	 *
	 * @param  array $stories  Our full array.
	 *
	 * @return string / array
	 */
	public static function parse_story_list( $stories = array() ) {

		// Set my empty array.
		$data   = array();

		// Loop the stories and parse what we need.
		foreach ( $stories as $story ) {

			// Set my array.
			$data[] = array(
				'sid'          => $story['sid'],
				'title'        => $story['title'],
				'slug'         => $story['slug'],
				'status'       => $story['status'],
				'description'  => $story['description'],
				'created'      => strtotime( $story['date']['created'] ),
				'published'    => strtotime( $story['date']['published'] ),
			);
		}

		// Return my data.
		return $data;
	}

	/**
	 * Take the large array of elements for a user and break it down.
	 *
	 * @param  array $stories  Our full array.
	 *
	 * @return string / array
	 */
	public static function parse_element_list( $elements = array(), $post_id = 0 ) {

		// preprint( $elements, true );

		// Set my empty array.
		$data   = array();

		// Loop the elements and parse what we need.
		foreach ( $elements as $element ) {

			// preprint( $element );

			// Parse the source and username.
			$source = ! empty( $element['source']['name'] ) ? esc_attr( $element['source']['name'] ) : '';
			$attrib = ! empty( $element['source']['username'] ) ? esc_attr( $element['source']['username'] ) : '';

			// Parse out possible link display info.
			$title  = self::determine_element_title( $element, $source );
			$text   = self::determine_element_text( $element, $source );

			// And check on my link.
			$link   = self::check_link_setup( $element['permalink'] );

			// Set my array.
			$data[] = array(
				'id'      => $element['id'],
				'eid'     => $element['eid'],
				'type'    => $element['type'],
				'link'    => esc_url( $link ),
				'posted'  => strtotime( $element['posted_at'] ),
				'added'   => strtotime( $element['added_at'] ),
				'source'  => $source,
				'attrib'  => $attrib,
				'title'   => $title,
				'text'    => $text,
			);

			// preprint( $data, true );
		}

		//preprint( $data, true );

		// Return my data.
		return $data;
	}

	/**
	 * Take the large array of the element and break it down.
	 *
	 * @param  array $element  Our full array.
	 *
	 * @return array
	 */
	public static function parse_element_display( $elements ) {

		// Set my empty array.
		$data   = array();

		// Loop the element and parse what we need.
		foreach ( $elements as $element ) {

			// Fetch all my meta.
			//get_comment_meta( $element->comment_ID, '_storify_element_id', true );
			//get_comment_meta( $element->comment_ID, '_storify_element_eid', true );
			$type   = get_comment_meta( $element->comment_ID, '_storify_element_type', true );
			$link   = get_comment_meta( $element->comment_ID, '_storify_element_link', true );
			$title  = get_comment_meta( $element->comment_ID, '_storify_element_title', true );
			$text   = $element->comment_content;
			$source = get_comment_meta( $element->comment_ID, '_storify_element_source', true );
			$attrib = get_comment_meta( $element->comment_ID, '_storify_element_attrib', true );

			// Set my array.
			$data[] = array(
				'type'      => $type,
				'link'      => $link,
				'title'     => $title,
				'text'      => $text,
				'source'    => $source,
				'attrib'    => $attrib,
			);
		}

		// Return my data.
		return $data;
	}

	/**
	 * Figure out how many stories we have to fetch.
	 *
	 * @param  string $username  The username to check.
	 *
	 * @return integer
	 */
	public static function get_user_story_count( $username = '' ) {

		// Fetch the user profile.
		$user   = storify_story_import()->make_api_call( 'users/' . $username  );

		// Bail without a user.
		if ( empty( $user ) ) {
			return;
		}

		// preprint( $user, true );

		// @@todo Need more error checking here.

		// Return my count.
		return $user['content']['stats']['stories'];
	}

	/**
	 * Get our whole array of a user's stories.
	 *
	 * @param  array  $content   All the content elements.
	 * @param  string $endpoint  The user story endpoint.
	 *
	 * @return array
	 */
	public static function merge_user_stories( $username = '', $total = 0 ) {

		// Set our stories as an empty variable.
		$data   = array();

		// Set my pages.
		$pages  = ceil( $total / 30 );

		// preprint( $pages, true );

		// Do a simple for loop, but starting at 2 since we already have page 1.
		for ( $i = 1; $i <= absint( $pages ); $i++ ) {

			// Pull more.
			$items  = storify_story_import()->make_api_call( 'stories/' . $username, $i );

			// Bail with no elements.
			if ( empty( $items['content']['stories'] ) ) {
				continue;
			}

			// Merge the data.
			$data   = array_merge( $items['content']['stories'], $data );
		}

		// Return the merged array.
		return $data;
	}

	/**
	 * Get our whole array of story elements.
	 *
	 * @param  array  $content   All the content elements.
	 * @param  string $endpoint  The user story endpoint.
	 *
	 * @return array
	 */
	public static function merge_story_elements( $content = array(), $endpoint = '' ) {

		// preprint( $content, true );
		// preprint( $content['totalElements'] );
		// preprint( $content['elements'], true );

		// Set our elements as a data item, which we may be adding.
		$data   = $content['elements'];

		// preprint( $data, true );

		// Determine how many totals.
		$total  = ! empty( $content['totalElements'] ) ? $content['totalElements'] : 29;

		// preprint( $total, true );

		// If we have less than 30, just return the array.
		if ( absint( $total ) <= 30 ) {
			return $data;
		}

		// Set my pages.
		$pages  = ceil( $total / 30 );

		// preprint( $pages, true );

		// Do a simple for loop, but starting at 2 since we already have page 1.
		for ( $i = 2; $i <= absint( $pages ); $i++ ) {

			// Pull more.
			$more   = storify_story_import()->make_api_call( $endpoint, $i );

			// Bail with no elements.
			if ( empty( $more['content']['elements'] ) ) {
				continue;
			}

			// Merge the data.
			$data   = array_merge( $data, $more['content']['elements'] );
		}

		// Return the merged array.
		return $data;
	}

	/**
	 * Determine if the element has a title to use.
	 *
	 * @param  array  $element  The element data.
	 * @param  string $source   Where the element came from.
	 *
	 * @return mixed
	 */
	public static function determine_element_title( $element = array(), $source = '' ) {

		// Twitter has nothing. we know this.
		if ( 'twitter' === $source ) {
			return;
		}

		// Return the data found in the link element.
		if ( ! empty( $element['data']['link']['title'] ) ) {
			return $element['data']['link']['title'];
		}

		// Return the data found in the video element.
		if ( ! empty( $element['data']['video']['title'] ) ) {
			return $element['data']['video']['title'];
		}

		// Look in the attribution field.
		if ( ! empty( $element['attribution']['title'] ) ) {
			return $element['attribution']['title'];
		}

	}

	/**
	 * Determine if the element has any text to use.
	 *
	 * @param  array  $element  The element data.
	 * @param  string $source   Where the element came from.
	 *
	 * @return mixed
	 */
	public static function determine_element_text( $element = array(), $source = '' ) {

		// Twitter has nothing. we know this.
		if ( 'twitter' === $source ) {
			return;
		}
			// Parse out possible link display info.
			$title  = 'twitter' !== $source && ! empty( $element['data']['link']['title'] ) ? esc_attr( $element['data']['link']['title'] ) : '';
			$text   = 'twitter' !== $source && ! empty( $element['data']['link']['description'] ) ? esc_attr( $element['data']['link']['description'] ) : '';

	}

	/**
	 * Update the actual story content.
	 *
	 * @param  integer $story_id  Our story ID.
	 * @param  string  $content   The content we wanna embed.
	 *
	 * @return void
	 */
	public static function update_story_content( $story_id = 0, $content = '' ) {
		wp_update_post( array( 'ID' => absint( $story_id ), 'post_content' => $content ) );
	}

	/**
	 * Check the permalink returned from Storify.
	 *
	 * @param  string $link      The actual link itself.
	 * @param  string $protocol  Which protocol to attach.
	 *
	 * @return string $link      The potentially modified link.
	 */
	public static function check_link_setup( $link = '', $protocol = 'https' ){

		// Check our intro.
		$intro  = mb_substr( $link, 0, 2 );

		// Add our protocol to the beginning.
		if ( ! empty( $intro ) && '//' === $intro ) {
			$link   = $protocol . ':' . $link;
		}

		// Return my formatted link.
		return $link;
	}

	/**
	 * Handle sorting all my elements by date.
	 *
	 * @param  array $a  One item to check.
	 * @param  array $b  A different item.
	 *
	 * @return mixed
	 */
	public static function date_sort_elements( $a, $b ) {
	    return $a['added_at'] - $b['added_at'];
	}

	// End our class.
}

// Call our class.
//new StorifyStoryImport_Helper();
