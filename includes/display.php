<?php
/**
 * Display the Storify thread.
 *
 * Do the various checks for query keys.
 *
 * @package StorifyStoryImport
 */

/**
 * Start our engines.
 */
class StorifyStoryImport_Display {

	/**
	 * Call our hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'the_content',                      array( $this, 'display_thread'      ),  10, 2   );
		add_filter( 'oembed_fetch_url',                 array( $this, 'add_twitter_args'    ),  10, 3   );
	}

	/**
	 * Handle displaying our thread.
	 *
	 * @param  mixed $content  The existing content.
	 *
	 * @return mixed
	 */
	public function display_thread( $content ) {

		// Bail on others.
		if ( ! is_singular( 'storify-stories' ) ) {
			return $content;
		}

		// Setup my comment args.
		$setup  = array(
			'post_id' => get_the_ID(),
			'type'    => 'storify-element',
			'order'   => 'ASC',
			'orderby' => 'comment_date'
		);

		// Fetch my items.
		$fetch  = get_comments( $setup );

		// preprint( $fetch, true );

		// Parse it.
		$items  = StorifyStoryImport_Helper::parse_element_display( $fetch );

		// preprint( $items, true );

		// Set an empty.
		$build  = '';

		// Loop and display.
		foreach ( $items as $item ) {

			// Handle our twitter links.
			if ( 'twitter' === $item['source'] ) {
				$embed  = wp_oembed_get( $item['link'], array( 'hide_thread' => true, 'conversation' => 'no' ) );
			}

			// And the build.
			$build .= '<div>';
			$build .= $embed;
			$build .= '<p><a href="' . $item['link'] . '">Link</a></p>';
			$build .= '</div>';
		}

		// Return our build.
		return $content . $build;
	}

	/**
	 * Add the handler for not including the reply.
	 *
	 * @param string $provider  The oembed provider
	 * @param string $url       The URL we are trying to embed.
	 * @param mixed  $args      Whatever args we passed.
	 */
	public function add_twitter_args( $provider, $url, $args ) {

		// Only run this on twitter links.
		if ( strpos( $provider, 'twitter.com' ) !== false ) {

			// Set my possible new args.
			$items  = array(
				'lang',
				'theme',
				'align',
				'hide_thread',
				'widget_type'
			);

			// List of args for a single Tweet: https://dev.twitter.com/rest/reference/get/statuses/oembed
			foreach ( $items as $item ) {

				// Add the arg if present.
				if ( isset( $args[ $item ] ) ) {
					$provider = add_query_arg( $item, $args[ $item ], $provider );
				}

			}
		}

		// Return my provider.
		return $provider;
	}

	// End our class.
}

// Call our class.
$StorifyStoryImport_Display = new StorifyStoryImport_Display();
$StorifyStoryImport_Display->init();
