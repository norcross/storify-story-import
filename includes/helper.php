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
	 * @return boolean
	 */
	public static function maybe_story_exists( $slug = '' ) {

		// Do the exists check.
		$exists = get_page_by_path( $slug, OBJECT, 'storify-stories' );

		// Return the boolean of existence.
		return ! empty( $exists ) ? $exists->ID : false;
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
				'created'      => $story['date']['created'],
				'published'    => $story['date']['published'],
			);
		}

		// Return my data.
		return $data;
	}

	// End our class.
}

// Call our class.
//new StorifyStoryImport_Helper();
