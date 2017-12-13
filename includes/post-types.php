<?php
/**
 * Post types setup.
 *
 * Create our post type for each Storify story.
 *
 * @package StorifyStoryImport
 */

/**
 * Start our engines.
 */
class StorifyStoryImport_PostTypes {

	/**
	 * Call our hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init',                                 array( $this, '_register_storify_post_type'     ),  11      );
	}

	/**
	 * Register our Storify post type.
	 *
	 * @return void
	 */
	public function _register_storify_post_type() {

		// Set my labels first.
		$labels = array(
			'name'                => __( 'Storify Stories', 'storify-story-import' ),
			'menu_name'           => __( 'Stories', 'storify-story-import' ),
			'singular_name'       => __( 'Story', 'storify-story-import' ),
			'add_new'             => __( 'Add New Story', 'storify-story-import' ),
			'add_new_item'        => __( 'Add New Story', 'storify-story-import' ),
			'edit'                => __( 'Edit', 'storify-story-import' ),
			'edit_item'           => __( 'Edit Story', 'storify-story-import' ),
			'new_item'            => __( 'New Story', 'storify-story-import' ),
			'view'                => __( 'View Story', 'storify-story-import' ),
			'view_item'           => __( 'View Story', 'storify-story-import' ),
			'search_items'        => __( 'Search Stories', 'storify-story-import' ),
			'not_found'           => __( 'No Stories found', 'storify-story-import' ),
			'not_found_in_trash'  => __( 'No Stories found in Trash', 'storify-story-import' ),
		);

		// Now set the args.
		$args   = array(
			'labels'                => apply_filters( 'storify_story_import_post_type_labels', $labels ),
			'description'           => __( 'Storify Stories are imported from the service.', 'storify-story-import' ),
			'public'                => true,
			'show_in_nav_menus'     => true,
			'show_ui'               => true,
		//	'show_in_menu'          => 'storify-import-root',
			'publicly_queryable'    => true,
			'exclude_from_search'   => false,
			'hierarchical'          => false,
			'menu_position'         => null,
			'menu_icon'             => 'dashicons-book-alt',
			'capability_type'       => apply_filters( 'storify_story_import_post_type_capability_type', 'post' ),
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'storify', 'with_front' => false ),
			'has_archive'           => 'storify',
			'supports'              => array( 'title', 'editor', 'excerpt', 'comments', 'thumbnail' ),
		);

		// Our last-chance filter for everything. If someone cleared the args, just bail.
		if ( false === $args = apply_filters( 'storify_story_import_post_type_args', $args ) ) {
			return;
		}

		// Register the post type.
		register_post_type( 'storify-stories', $args );
	}

	// End our class.
}

// Call our class.
$StorifyStoryImport_PostTypes = new StorifyStoryImport_PostTypes();
$StorifyStoryImport_PostTypes->init();
