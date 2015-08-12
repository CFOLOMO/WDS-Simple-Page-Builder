<?php

/**
 * Function to register a new layout programmatically
 * @since  1.3
 * @param  string $name       The layout name
 * @param  array  $templates  An array of templates to add to the layout
 * @param  bool   $allow_edit If false, layout will not appear in the Page Builder Options
 *                            Saved Layouts. If true, users can edit the layout after it's
 *                            registered.
 * @return null
 */
function register_page_builder_layout( $name = '', $templates = array(), $allow_edit = false ) {
	// don't register anything if no layout name or templates were passed
	if ( '' == $name || empty( $templates ) ) {
		return false;
	}

	wp_cache_delete ( 'alloptions', 'options' );

	// if allow edit is true, add the template to the same options group as the other templates. this will enable users to update the layout after it's registered.
	if ( $allow_edit ) {

		$old_options = get_option( 'wds_page_builder_options' );
		$new_options = $old_options;
		$new_options['parts_saved_layouts'][] = array(
			'layouts_name'   => esc_attr( $name ),
			'default_layout' => false,
			'template_group' => $templates
		);

		// check existing layouts for the one we're trying to add to see if it exists
		$existing_layouts = isset( $old_options['parts_saved_layouts'] ) ? $old_options['parts_saved_layouts'] : array();
		$layout_exists    = saved_page_builder_layout_exists( esc_attr( $name ) );

		// if the layout doesn't exist already, add it. this allows that layout to be edited
		if ( ! $layout_exists ) {
			update_option( 'wds_page_builder_options', $new_options );
		}

		return;

	}

	// This is a hard coded layout

	$options = get_option( 'wds_page_builder_layouts' );

	// check existing layouts for the one we're trying to add to see if it exists
	$layout_exists   = false;
	$updated_options = false;
	if ( is_array( $options ) ) {
		$i = 0;
		foreach( $options as $layout ) {
			if ( saved_page_builder_layout_exists( esc_attr( $name ), false ) ) {
				// check if the group has changed. if it hasn't, this layout exists
				if ( $templates !== $layout['template_group'] ) {
					$layout_exists = true;
				} else {
					// if the group is different, delete the option, then insert the new templates into the template group
					delete_option( 'wds_page_builder_layouts' );
					unset( $options[$i] );
					$options[$i]['layouts_name']   = esc_attr( $name );
					$options[$i]['template_group'] = $templates;
					$updated_options = true;
				}
			}
			$i++;
		}
	}

	if ( $updated_options ) {
		$new_options = $options;
	} else {
		$new_options = $options;
		$new_options[] = array(
			'layouts_name'   =>  esc_attr( $name ),
			'template_group' => $templates
		);
	}

	// only run update_option if the layout doesn't exist already
	if ( ! $layout_exists ) {
		update_option( 'wds_page_builder_layouts', $new_options );
	}

	return;

}

/**
 * Check if a given layout exists
 * @since  1.4.2
 * @param  string  $layout_name The name of the saved layout
 * @param  boolean $editable    Whether the layout is editable or hard-coded
 * @return boolean              True if it exists, false if it doesn't
 */
function saved_page_builder_layout_exists( $layout_name = '', $editable = true ) {
	if ( '' == $layout_name ) {
		return false;
	}

	if ( $editable ) {
		$options          = get_option( 'wds_page_builder_options' );
		$existing_layouts = isset( $options['parts_saved_layouts'] ) ? $options['parts_saved_layouts'] : array();
		$layout_exists    = false;

		if ( ! $options ) {
			return $layout_exists;
		}

		foreach( $existing_layouts as $layout ) {
			if ( esc_attr( $layout_name ) == $layout['layouts_name'] ) {
				$layout_exists = true;
			}
		}
	} else {
		$options       = get_option( 'wds_page_builder_layouts' );
		$layout_exists = false;

		if ( ! $options  ) {
			return $layout_exists;
		}

		foreach( $options as $layout ) {
			if ( esc_attr( $layout_name ) == $layout['layouts_name'] ) {
				$layout_exists = true;
			}
		}
	}

	return $layout_exists;

}

/**
 * Function to remove a registered layout. Best used in a deactivation hook.
 * @since  1.4
 * @param  string $name      The layout name. Pass 'all' to delete all registered layouts.
 * @return null
 */
function unregister_page_builder_layout( $name = '' ) {
	// bail if no name was passed
	if ( '' == $name ) {
		return;
	}

	wp_cache_delete ( 'alloptions', 'options' );

	// if 'all' is passed, delete the option entirely
	if ( 'all' == $name ) {
		delete_option( 'wds_page_builder_layouts' );
		return;
	}

	$old_options = ( is_array( get_option( 'wds_page_builder_layouts' ) ) ) ? get_option( 'wds_page_builder_layouts' ) : false;

	if ( $old_options ) {
		foreach( $old_options as $layout ) {
			// check for the passed layout name. save the layout as long as it does NOT match.
			if ( esc_attr( $name ) !== $layout['layouts_name'] ) {
				$new_options[] = $layout;
			}
		}

		// delete the saved layout before updating
		delete_option( 'wds_page_builder_layouts' );
		update_option( 'wds_page_builder_layouts', $new_options );

	}

	return;

}

/**
 * Function to register a new page builder "area"
 * @param  string $name      The area name
 * @param  array  $templates You can define the templates that go in this area the same way you
 *                           would with register_page_builder_layout
 * @return void
 */
function register_page_builder_area( $name = '', $templates = array() ) {
	// bail if no name was passed
	if ( '' == $name ) {
		return;
	}

	wp_cache_delete( 'alloptions', 'options' );

	$old_options    = get_option( 'wds_page_builder_areas' );
	$update_options = false;

	// check existing layouts for the one we're trying to add to see if it exists
	if ( is_array( $old_options ) ) {

		if ( isset( $old_options[$name] ) ) {
			unset( $old_options[$name]['template_group'] );
			unset( $old_options[$name] );
		}

		$new_options = $old_options;

		if ( ! in_array( sanitize_title( $name ), $old_options ) ) {
			$update_options = true;
		}

	} else {
		$update_options = true;
	}

	if ( $update_options ) {
		$new_options[ esc_attr( $name ) ]['template_group'] = $templates;
		delete_option( 'wds_page_builder_areas' );
		update_option( 'wds_page_builder_areas', $new_options );
	}

}

/**
 * Gets the page builder areas
 * @return mixed False if there are no areas or an array of layouts if there's more than one.
 */
function get_page_builder_areas() {
	$areas = get_option( 'wds_page_builder_areas' );

	if ( ! $areas ) {
		return false;
	}

	return $areas;
}


/**
 * Function that can be used to return a specific page builder area
 * @param  string  $area    The area by slug/name
 * @param  integer $post_id Optional. The post id. If none is passed, we will try to get one if
 *                          it's necessary.
 * @return void
 */
function get_page_builder_area( $area = '', $post_id = 0 ) {
	// first, get the page builder areas
	$areas = get_page_builder_areas();

	// if there were no page builder areas, bail
	if ( ! $areas ) {
		return;
	}

	// if no post ID was passed, try to get one
	if ( 0 == $post_id ) {
		$post_id = get_the_ID();
	}

	// if the area we're looking for doesn't exist, bail
	if ( ! isset( $areas[$area] ) ) {
		return;
	}

	// if it's not singular -- like an archive or a 404 or something -- you can only add template
	// parts by registering the area
	if ( ! is_singular() ) {
		return $areas[$area]['template_group'];
	}

	if ( get_post_meta( $post_id, '_wds_builder_' . $area . '_template', true ) ) {
		$templates = get_post_meta( $post_id, '_wds_builder_' . $area . '_template', true );
		foreach( $templates as $template ) {
			$out[] = $template['_page_builder_area-' . $area];
		}

		return $out;
	}

	return;
}

/**
 * Load an array of template parts (by slug). If no array is passed, used as a wrapper
 * for the wds_page_builder_load_parts action.
 * @since  1.3
 * @param  mixed  $parts     Optional. A specific layout or an array of parts to
 *                           display
 * @param  string $container Optional. Container HTML element.
 * @param  string $class     Optional. Custom container class to wrap around individual parts
 * @param  string $area      Optional. The area which these parts belong to.
 *
 * @return null
 */
function wds_page_builder_load_parts( $parts = '', $container = '', $class = '', $area = '' ) {
	$GLOBALS['WDS_Page_Builder']->set_area( $area );
	if ( ! is_array( $parts ) ) {
		do_action( 'wds_page_builder_load_parts', $parts, $container, $class );
		return;
	}

	// parts are specified by their slugs, we pass them to the load_part function which uses the load_template_part method in the WDS_Page_Builder class
	foreach ( $parts as $index => $part ) {
		$GLOBALS['WDS_Page_Builder']->set_parts_index( $index );
		wds_page_builder_load_part( $part );
	}

	return;
}

/**
 * Helper function for loading a single template part
 * @since  1.3
 * @param  string $part The part slug
 * @return null
 */
function wds_page_builder_load_part( $part = '' ) {
	// bail if no part was specified
	if ( '' == $part ) {
		return;
	}

	$page_builder = new WDS_Page_Builder;
	$page_builder->load_template_part( array( 'template_group' => $part ) );
}


/**
 * The function to load a specific page builder area
 * @param  string  $area    Which area to load. If no page builder area is found, will
 *                          look for a saved layout with the same name.
 * @param  integer $post_id Optional. The post id.
 * @return void
 */
function wds_page_builder_area( $area = '', $post_id = 0 ) {
	// bail if no area was specified
	if ( '' == $area ) {
		return;
	}

	$parts = get_page_builder_area( $area, $post_id );

	if ( $parts ) {
		do_action( 'wds_page_builder_before_load_parts', $parts, $area, $post_id );
		wds_page_builder_load_parts( $parts, '', '', $area );
		do_action( 'wds_page_builder_after_load_parts', $parts, $area, $post_id );
	} else {
		wds_page_builder_load_parts( $area );
	}
}

/**
 * Display the classes for the template part wrapper
 * @since  1.5
 * @param  string|array $class     One or more classes to add to the class list
 * @return null
 */
function page_builder_class( $class = '' ) {
	echo 'class="' . get_the_page_builder_classes( $class ) . '"';
}

/**
 * Return the classes for the template part wrapper
 * @since  1.5
 * @param  string|array $class     One or more classes to add to the class list
 * @return string       A parsed list of classes as they would appear in a div class attribute
 */
function get_the_page_builder_classes( $class = '' ) {
	// Separates classes with a single space, collates classes for template part wrapper DIV
	$classes = join( ' ', get_page_builder_class( $class ) );

	/**
	 * Filter the list of CSS classes
	 * @since  1.5
	 * @param  array  $classes   An array of pagebuilder part classes
	 */
	return apply_filters( 'page_builder_classes', $classes );
}

/**
 * Retrieve the class names for the template part as an array
 *
 * Based on post_class, but we're not getting as much information as post_class.
 * We just want to return a generic class, the current template part slug, and any
 * custom class names that were passed to the function.
 *
 * @param  string|array $class     One or more classes to add to the class list
 * @return array                   Array of classes.
 */
function get_page_builder_class( $class = '' ) {

	if ( $class ) {
		if ( ! is_array( $class ) ) {
		        $class = preg_split( '#\s+#', $class );
		}
		$classes = array_map( 'esc_attr', $class );
	}

	$classes[] = wds_page_builder_container_class();

	return array_unique( $classes );

}

/**
 * Gets an array of page builder parts.
 *
 * Note, this function ONLY returns values AFTER the parts have been loaded, so hook into
 * wds_page_builder_after_load_parts or later for this to be populated
 * @since  1.5
 * @return array An array of template parts in use on the page
 */
function get_page_builder_parts() {
	$page_builder = new WDS_Page_Builder;
	return $page_builder->page_builder_parts();
}

/**
 * Helper function to display page builder with a full wrap.
 *
 * Note, this should be used only if the option to use a wrapper is _disabled_, otherwise, you'll
 * get the page builder contents twice
 * @param  string $container Optional. Unique container html element or use the default
 * @param  string $class     Optional. Unique class to pass to the wrapper -- this is the only way
 *                           to change the container classes without a filter.
 * @param  string $layout    Optional. The specific layout name to load, or the default.
 * @return void
 */
function wds_page_builder_wrap( $container = '', $class = '', $layout = '' ) {
	$page_builder = new WDS_Page_Builder;
	add_action( 'wds_page_builder_before_load_template', array( $page_builder, 'before_parts' ), 10, 2 );
	add_action( 'wds_page_builder_after_load_template', array( $page_builder, 'after_parts' ), 10, 2 );

	// do the page builder stuff
	wds_page_builder_load_parts( $layout, $container, $class );

}

/**
 * Function to programmatically set certain Page Builder options
 * @param  array  $args An array of arguments matching Page Builder settings in the options table.
 *                      'parts_dir'       The directory that template parts are saved in
 *                      'parts_prefix'    The template part prefix being used
 *                      'use_wrap'        'on' to use the container wrap, empty string to omit.
 *                      'container'       A valid HTML container type.
 *                      'container_class' The container class
 *                      'post_types'      A post type name as a string or array of post types
 *                      'hide_options'    True to hide options that have been set, disabled to
 *                                        display them as uneditable fields
 * @return void
 */
function wds_register_page_builder_options( $args = array() ) {
	$defaults = array(
		'hide_options'    => true,
	);

	$args = wp_parse_args( $args, $defaults );

	do_action( 'wds_register_page_builder_options', $args );
}

/**
 * Helper function to add Page Builder theme support
 *
 * Because theme features are all hard-coded, we can't pass arguments directly to
 * add_theme_supports (at least, not that I'm aware of...). This helper function MUST be used in
 * combination with `add_theme_support( 'wds-simple-page-builder' )` in order to pass the correct
 * values to the Page Builder options.
 * @since  1.5
 * @param  array  $args An array of arguments matching Page Builder settings in the options table.
 *                      'parts_dir'       The directory that template parts are saved in
 *                      'parts_prefix'    The template part prefix being used
 *                      'use_wrap'        'on' to use the container wrap, empty string to omit.
 *                      'container'       A valid HTML container type.
 *                      'container_class' The container class
 *                      'post_types'      A post type name as a string or array of post types
 *                      'hide_options'    True to hide options that have been set, disabled to
 *                                        display them as uneditable fields
 * @return void
 */
function wds_page_builder_theme_support( $args = array() ) {
	$defaults = array(
		'hide_options'    => true,
	);

	$args = wp_parse_args( $args, $defaults );
	do_action( 'wds_page_builder_add_theme_support', $args );
}

/**
 * Grabs the value of the current template part's meta key.
 *
 * @since 1.6
 * @param string $meta_key  The meta key to find the value of.
 *
 * @return mixed|null       Null on failure or the value of the meta key on success.
 */
function wds_page_builder_get_this_part_data( $meta_key ) {

	// Add method exists check just in case global was modified.
	$part_slug = $GLOBALS['WDS_Page_Builder']->get_part();

	if ( $part_slug ) {
		return wds_page_builder_get_part_data( $part_slug, $meta_key );
	}

	return null;
}

/**
 * Grabs the value of specific meta keys for specific template parts.
 *
 * $part_slug should be the slug of the template part, for instance if the template
 * part is `part-sample.php` where part is the prefix, the slug would be `sample` excluding
 * the .php extension.
 *
 * @since 1.6
 * @param string $part_slug     The template part slug or index/slug array
 * @param string $meta_key      The meta to find the value of.
 * @param int    $post_id       The Post ID to retrieve the data for (optional)
 *
 * @return null|mixed           Null on failure, the stored meta value on success.
 */
function wds_page_builder_get_part_data( $part_slug, $meta_key, $post_id = 0 ) {

	// Can specify the index if parts are used multiple times on a page
	if ( is_array( $part_slug ) ) {

		// Oops? you're doing it wrong!
		if ( ! isset( $part_slug['index'], $part_slug['slug'] ) ) {
			return new WP_Error( 'index_slug_defined_incorrectly', 'The index/slug array was defined incorrectly. Try array( \'index\' => 0, \'slug\' => \'slug-name\' )' );
		}

		$part_index = $part_slug['index'];
		$part_slug  = $part_slug['slug'];

	} else {
		// Get current part index
		$part_index = $GLOBALS['WDS_Page_Builder']->get_parts_index();
	}

	$area = $GLOBALS['WDS_Page_Builder']->get_area();
	$area_key = $area ? $area . '_' : '';
	$post_id = $post_id ? $post_id : get_the_ID();
	$meta    = get_post_meta( $post_id, '_wds_builder_' . esc_attr( $area_key ) . 'template', 1 );

	if (
		// if index exists and the template_group index is there
		isset( $meta[ $part_index ][ 'template_group' ] )
		// and the template group is rthe same we're looking for
		&& $part_slug == $meta[ $part_index ][ 'template_group' ]
		// And we have the meta_key they're looking for
		&& isset( $meta[ $part_index ][ $meta_key ] )
	) {
		// Send it back.
		return $meta[ $part_index ][ $meta_key ];
	}

	return null;
}
