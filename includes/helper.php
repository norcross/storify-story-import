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
	 * @param  string $key     Our option table key.
	 * @param  string $single  Get 1 get out of the array.
	 *
	 * @return string / array
	 */
	public static function get_single_setting( $key = '', $single = '', $return = 'array' ) {

		// Get my initial data.
		$settings   = get_option( $key, false );

		// If we didn't specify a single key, then return the entire array.
		if ( empty( $single ) ) {
			return $settings;
		}

		// Return the single key requested, or false.
		return isset( $settings[ $single ] ) ? $settings[ $single ] : false;
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

			// Set my array.
			$data[] = array(
				'id'      => $element['id'],
				'eid'     => $element['eid'],
				'type'    => $element['type'],
				'link'    => $element['permalink'],
				'posted'  => strtotime( $element['posted_at'] ),
				'added'   => strtotime( $element['added_at'] ),
				'source'  => $element['source']['name'],
				'attrib'  => $element['source']['username'],
			);
		}

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
			//get_comment_meta( $element->comment_ID, '_element_id', true );
			//get_comment_meta( $element->comment_ID, '_element_eid', true );
			$type   = get_comment_meta( $element->comment_ID, '_element_type', true );
			$link   = get_comment_meta( $element->comment_ID, '_element_link', true );
			$source = get_comment_meta( $element->comment_ID, '_element_source', true );
			//get_comment_meta( $element->comment_ID, '_element_attrib', true );

			// Set my array.
			$data[] = array(
				'type'         => $type,
				'link'         => $link,
				'source'       => $source,
			);
		}

		// Return my data.
		return $data;
	}

	// End our class.
}

// Call our class.
//new StorifyStoryImport_Helper();
