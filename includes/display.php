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

		// If we don't have the embed type turned on, bail.
		/*
		if ( 'embed' !== $embed = StorifyStoryImport_Helper::get_single_setting( 'storify_display_type', null, 'embed' ) ) {
			return;
		}
		*/

		// Load the filters.
		add_filter( 'oembed_fetch_url',                 array( $this, 'add_oembed_args'     ),  10, 3   );
		add_filter( 'the_content',                      array( $this, 'display_thread'      ),  10, 2   );
	}

	/**
	 * Add the handler for not including the reply.
	 *
	 * @param string $provider  The oembed provider
	 * @param string $url       The URL we are trying to embed.
	 * @param mixed  $args      Whatever args we passed.
	 */
	public function add_oembed_args( $provider, $url, $args ) {

		// Set an empty.
		$items  = array();

		// Only run this on twitter links.
		if ( strpos( $provider, 'twitter.com' ) !== false ) {

			// Set my possible new args.
			// List of args for a single Tweet: https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/get-statuses-oembed
			$items  = array(
				'lang',
				'theme',
				'align',
				'hide_thread',
				'widget_type',
				'omit_script',
			);
		}

		// Only run this on Youtube links.
		if ( strpos( $provider, 'youtube' ) !== false ) {

			// Set my possible new args.
			// List of args for a single Tweet: https://developers.google.com/youtube/player_parameters
			$items  = array(
				'autoplay',
				'controls',
				'loop',
			);
		}

		// Just return my provider if I have no args.
		if ( empty( $items ) ) {
			return $provider;
		}

		// Now loop my custom items and add them.
		foreach ( $items as $item ) {

			// Add the arg if present.
			if ( isset( $args[ $item ] ) ) {
				$provider = add_query_arg( $item, $args[ $item ], $provider );
			}

		}

		// Return my provider.
		return $provider;
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

		// Set my story ID.
		$embed  = storify_story_import()->build_display_markup( get_the_ID() );

		// Update the body.
		// StorifyStoryImport_Helper::update_story_content( $story, $build );

		// Return our build.
		return $content . $embed;
	}

	/**
	 * Handle our generic display.
	 *
	 * @param  string $type     What type of element it is.
	 * @param  array  $element  The entire element.
	 *
	 * @return HTML
	 */
	public static function display_generic_element( $type = '', $element = array() ) {

		// Start my empty.
		$setup  = '';

		// Start my switch.
		switch ( $type ) {

			// Handle our images.
			case 'image' :
				$setup .= '<img src="' . esc_url( $element['link'] ) . ' />';
				break;

			// Handle our basic link.
			case 'link' :
				$setup .= ! empty( $element['title'] ) ? '<h4>' . esc_html( $element['title'] ) . '</h4>' : '';
				$setup .= ! empty( $element['text'] ) ? wpautop( wp_kses_post( $element['text'] ) ) : '';
				break;

			// End all case breaks.
		}

		// And an empty return.
		return apply_filters( 'storify_story_import_display_generic', $setup, $type, $element );
	}

	// End our class.
}

// Call our class.
$StorifyStoryImport_Display = new StorifyStoryImport_Display();
$StorifyStoryImport_Display->init();
