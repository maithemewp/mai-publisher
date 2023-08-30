<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Gets ads to be displayed on the current page.
 *
 * @since 0.1.0
 *
 * @return void
 */
function maigam_get_ads() {
	static $ads = null;

	if ( ! is_null( $ads ) ) {
		return $ads;
	}

	$ads  = [];
	$data = maigam_get_ads_data();

	// Bail if no actual values.
	if ( ! array_filter( array_values( $data ) ) ) {
		return [];
	}

	// Loop through each type.
	foreach ( $data as $type => $items ) {
		// Loop through each item.
		foreach ( $items as $args ) {
			// Validate.
			$args = maigam_validate_args( $args, $type );

			// Bail if not valid args.
			if ( ! $args ) {
				continue;
			}

			// Add to ads.
			$ads[] = $args;
		}
	}

	return $ads;
}

/**
 * Gets valid args.
 *
 * @since 0.1.0
 *
 * @param array  $args The ad args.
 * @param string $type The ad type. Either 'global', 'single', or 'archive'.
 *
 * @return array
 */
function maigam_validate_args( $args, $type ) {
	$valid = [];

	// Bail if no id, content, and location.
	if ( ! ( $args['id'] && $args['location'] && $args['content'] ) ) {
		return $valid;
	}

	// Set variables.
	$locations = maigam_get_locations();

	// Bail if no location hook. Only check isset for location since 'content' has no hook.
	if ( ! isset( $locations[ $args['location'] ] ) ) {
		return $valid;
	}

	// Validate by type.
	switch ( $type ) {
		case 'global':
			$valid = maigam_validate_args_global( $args );
			break;

		case 'single':
			$valid = maigam_validate_args_single( $args );
			break;

		case 'archive':
			$valid = maigam_validate_args_archive( $args );
			break;
	}

	return $valid;
}

function maigam_validate_args_global( $args ) {
	// Parse.
	$args = wp_parse_args( $args, [
		'id'       => '',
		'slug'     => '',
		'location' => '',
		'content'  => '',
	] );

	// Sanitize.
	$args = [
		'id'       => absint( $args['id'] ),
		'slug'     => sanitize_key( $args['slug'] ),
		'location' => esc_html( $args['location'] ),
		'content'  => trim( wp_kses_post( $args['content'] ) ),
	];

	return $args;
}

function maigam_validate_args_single( $args ) {
	if ( ! maigam_is_singular() ) {
		return [];
	}

	// Parse.
	$args = wp_parse_args( $args, [
		'id'                  => '',
		'slug'                => '',
		'location'            => '',
		'content'             => '',
		'content_location'    => 'after',
		'content_count'       => 6,
		'types'               => [],
		'keywords'            => '',
		'taxonomies'          => [],
		'taxonomies_relation' => 'AND',
		'authors'             => [],
		'include'             => [],
		'exclude'             => [],
	] );

	// Sanitize.
	$args = [
		'id'                  => absint( $args['id'] ),
		'slug'                => sanitize_key( $args['slug'] ),
		'location'            => esc_html( $args['location'] ),
		'content'             => trim( wp_kses_post( $args['content'] ) ),
		'content_location'    => esc_html( $args['content_location'] ),
		'content_count'       => $args['content_count'] ? array_map( 'absint', explode( ',', $args['content_count'] ) ) : [],
		'types'               => $args['types'] ? array_map( 'esc_html', (array) $args['types'] ) : [],
		'keywords'            => maigam_sanitize_keywords( $args['keywords'] ),
		'taxonomies'          => maigam_sanitize_taxonomies( $args['taxonomies'] ),
		'taxonomies_relation' => esc_html( $args['taxonomies_relation'] ),
		'authors'             => $args['authors'] ? array_map( 'absint', (array) $args['authors'] ) : [],
		'include'             => $args['include'] ? array_map( 'absint', (array) $args['include'] ) : [],
		'exclude'             => $args['exclude'] ? array_map( 'absint', (array) $args['exclude'] ) : [],
	];

	// Set variables.
	$post_id      = get_the_ID();
	$post_type    = get_post_type();
	$post_content = null;

	// Bail if excluding this entry.
	if ( $args['exclude'] && in_array( $post_id, $args['exclude'] ) ) {
		return [];
	}

	// If including this entry.
	$include = $args['include'] && in_array( $post_id, $args['include'] );

	// If not already including, check post types.
	if ( ! $include && ! ( in_array( '*', $args['types'] ) || ! in_array( $post_type, $args['types'] ) ) ) {
		return [];
	}

	// If not already including, and have keywords, check for them.
	if ( ! $include && $args['keywords'] ) {
		$post         = get_post( $post_id );
		$post_content = maigam_strtolower( strip_tags( do_shortcode( trim( $post->post_content ) ) ) );

		if ( ! maigam_has_string( $args['keywords'], $post_content ) ) {
			return [];
		}
	}

	// If not already including, check taxonomies.
	if ( ! $include && $args['taxonomies'] ) {

		if ( 'AND' === $args['taxonomies_relation'] ) {

			// Loop through all taxonomies to give a chance to bail if NOT IN.
			foreach ( $args['taxonomies'] as $data ) {
				$has_term = has_term( $data['terms'], $data['taxonomy'] );

				// Bail if we have a term and we aren't displaying here.
				if ( $has_term && 'NOT IN' === $data['operator'] ) {
					return [];
				}

				// Bail if we have don't a term and we are dislaying here.
				if ( ! $has_term && 'IN' === $data['operator'] ) {
					return [];
				}
			}

		} elseif ( 'OR' === $args['taxonomies_relation'] ) {

			$meets_any = [];

			foreach ( $args['taxonomies'] as $data ) {
				$has_term = has_term( $data['terms'], $data['taxonomy'] );

				if ( $has_term && 'IN' === $data['operator'] ) {
					$meets_any = true;
					break;
				}

				if ( ! $has_term && 'NOT IN' === $data['operator'] ) {
					$meets_any = true;
					break;
				}
			}

			if ( ! $meets_any ) {
				return [];
			}
		}
	}

	// If not already including, check authors.
	if ( ! $include && $args['authors'] ) {
		$author_id = get_post_field( 'post_author', $post_id );

		if ( ! in_array( $author_id, $args['authors'] ) ) {
			return [];
		}
	}

	// Check content count.
	if ( 'content' === $args['location'] && $args['content_count'] ) {
		if ( is_null( $post_content ) ) {
			$post         = get_post( $post_id );
			$post_content = trim( $post->post_content );
		}

		if ( ! $post_content ) {
			return [];
		}

		// Get valid counts.
		$count = maigam_get_content( $post_content, $args, true );

		// Update counts or bail.
		if ( $count ) {
			$args['content_count'] = $count;
		} else {
			return [];
		}
	}

	return $args;
}

function maigam_validate_args_archive( $args ) {
	if ( ! maigam_is_archive() ) {
		return [];
	}

	// Parse.
	$args = wp_parse_args( $args, [
		'id'            => '',
		'slug'          => '',
		'location'      => '',
		'content'       => '',
		'content_count' => 3,
		'types'         => [],
		'taxonomies'    => [],
		'terms'         => [],
		'exclude'       => [],
		'includes'      => [],
	] );

	// Sanitize.
	$args = [
		'id'            => absint( $args['id'] ),
		'slug'          => sanitize_key( $args['slug'] ),
		'location'      => esc_html( $args['location'] ),
		'content'       => trim( wp_kses_post( $args['content'] ) ),
		'content_count' => absint( $args['content_count'] ),
		'types'         => $args['types'] ? array_map( 'esc_html', (array) $args['types'] ) : [],
		'taxonomies'    => $args['taxonomies'] ? array_map( 'esc_html', (array) $args['taxonomies'] ) : [],
		'terms'         => $args['terms'] ? array_map( 'absint', (array) $args['terms'] ) : [],
		'exclude'       => $args['exclude'] ? array_map( 'absint', (array) $args['exclude'] ) : [],
		'includes'      => $args['includes'] ? array_map( 'sanitize_key', (array) $args['includes'] ) : [],
	];

	// Blog.
	if ( is_home() ) {
		// Bail if not showing on post archive.
		if ( ! $args['types'] && ( ! in_array( '*', $args['types'] ) || ! in_array( 'post', $args['types'] ) ) ) {
			return [];
		}
	}
	// CPT archive. WooCommerce shop returns false for `is_post_type_archive()`.
	elseif ( is_post_type_archive() || maigam_is_shop_archive() ) {
		// Bail if shop page and not showing here.
		if ( maigam_is_shop_archive() ) {
			if ( ! $args['types'] && ( ! in_array( '*', $args['types'] ) || ! in_array( 'product', $args['types'] ) ) ) {
				return [];
			}
		}
		// Bail if not showing on this post type archive.
		else {
			global $wp_query;

			$post_type = isset( $wp_query->query['post_type'] ) ? $wp_query->query['post_type'] : '';

			if ( ! $args['types'] && ( ! in_array( '*', $args['types'] ) || ! is_post_type_archive( $post_type ) ) ) {
				return [];
			}
		}
	}
	// Term archive.
	elseif ( is_tax() || is_category() || is_tag() ) {
		$object = get_queried_object();

		// Bail if excluding this term archive.
		if ( $args['exclude'] && in_array( $object->term_id, $args['exclude'] ) ) {
			return [];
		}

		// If including this entry.
		$include = $args['terms'] && in_array( $object->term_id, $args['terms'] );

		// If not already including, check taxonomies if we're restricting to specific taxonomies.
		if ( ! $include && ! ( $args['taxonomies'] && in_array( $object->taxonomy, $args['taxonomies'] ) ) ) {
			return [];
		}
	}
	// Search results;
	elseif ( is_search() ) {
		// Bail if not set to show on search results.
		if ( ! ( $args['includes'] || in_array( 'search', $args['includes'] ) ) ) {
			return [];
		}
	}

	return $args;
}

/**
 * Adds content area to existing content/HTML.
 *
 * @since 0.1.0
 *
 * @uses DOMDocument
 *
 * @param string $content The existing html.
 * @param array  $args    The ad args.
 * @param bool   $counts  Whether to return the valid content_count instead of content.
 *                        This keeps the logic in one place.
 *
 * @return string.
 */
function maigam_get_content( $content, $args, $counts = false ) {
	$count = [];
	$dom   = maigam_get_dom_document( $content );
	$xpath = new DOMXPath( $dom );
	$all   = $xpath->query( '/*[not(self::script or self::style or self::link)]' );

	if ( ! $all->length ) {
		return $content;
	}

	$last     = $all->item( $all->length - 1 );
	$tags     = 'before' !== $args['location'] ? [ 'div', 'p', 'ol', 'ul', 'blockquote', 'figure', 'iframe' ] : [ 'h2', 'h3' ];
	$tags     = apply_filters( 'maigam_content_elements', $tags, $args );
	$tags     = array_filter( $tags );
	$tags     = array_unique( $tags );
	$elements = [];

	foreach ( $all as $node ) {
		if ( ! $node->childNodes->length || ! in_array( $node->nodeName, $tags ) ) {
			continue;
		}

		$elements[] = $node;
	}

	if ( ! $elements ) {
		return $counts ? [] : $content;
	}

	/**
	 * Build the temporary dom.
	 * Special characters were causing issues with `appendXML()`.
	 *
	 * @link https://stackoverflow.com/questions/4645738/domdocument-appendxml-with-special-characters
	 * @link https://www.py4u.net/discuss/974358
	 */
	$tmp  = maigam_get_dom_document( $args['content'] );
	$node = $dom->importNode( $tmp->documentElement, true );

	if ( ! $node ) {
		return $counts ? [] : $content;
	}

	$item       = 0;
	$tmp_counts = array_flip( $args['content_count'] );

	foreach ( $elements as $index => $element ) {
		$item++;

		// Bail if there are no more counts to check.
		if ( ! $tmp_counts ) {
			break;
		}

		// Bail if not an element we need.
		if ( ! isset( $tmp_counts[ $item ] ) ) {
			continue;
		}

		// After elements.
		if ( 'before' !== $args['location'] ) {

			/**
			 * Bail if this is the last element.
			 * This avoids duplicates since this location would technically be "after entry content" at this point.
			 */
			if ( $element === $last || null === $element->nextSibling ) {
				break;
			}

			/**
			 * Add cca after this element. There is no insertAfter() in PHP ¯\_(ツ)_/¯.
			 *
			 * @link https://gist.github.com/deathlyfrantic/cd8d7ef8ba91544cdf06
			 */
			$element->parentNode->insertBefore( $node, $element->nextSibling );
		}
		// Before headings.
		else {
			$element->parentNode->insertBefore( $node, $element );
		}

		// Add to count.
		$count[] = $item;

		// Remove from temp counts.
		unset( $tmp_counts[ $item ] );
	}

	// Save new HTML.
	$content = $dom->saveHTML();

	return $counts ? $count : $content;
}

/**
 * Gets DOMDocument object.
 *
 * @since 0.1.0
 *
 * @link https://stackoverflow.com/questions/29493678/loadhtml-libxml-html-noimplied-on-an-html-fragment-generates-incorrect-tags
 *
 * @param string $html Any given HTML string.
 *
 * @return DOMDocument
 */
function maigam_get_dom_document( $html ) {
	// Create the new document.
	$dom = new DOMDocument();

	// Modify state.
	$libxml_previous_state = libxml_use_internal_errors( true );

	// Encode.
	$html = mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' );

	// Load the content in the document HTML.
	$dom->loadHTML( "<div>$html</div>" );

	// Handle wraps.
	$container = $dom->getElementsByTagName('div')->item(0);
	$container = $container->parentNode->removeChild( $container );

	while ( $dom->firstChild ) {
		$dom->removeChild( $dom->firstChild );
	}

	while ( $container->firstChild ) {
		$dom->appendChild( $container->firstChild );
	}

	// Handle errors.
	libxml_clear_errors();

	// Restore.
	libxml_use_internal_errors( $libxml_previous_state );

	return $dom;
}

/**
 * Returns an array of ads.
 * Slugs must exist in the config.
 *
 * @since 0.1.0
 *
 * @return array
 */
function maigam_get_ads_data() {
	static $ads = null;

	if ( ! is_null( $ads ) ) {
		return $ads;
	}

	$ads = [
		'global'  => [],
		'single'  => [],
		'archive' => [],
	];

	$query = new WP_Query(
		[
			'post_type'              => 'mai_ad',
			'posts_per_page'         => 500,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'suppress_filters'       => false, // https://github.com/10up/Engineering-Best-Practices/issues/116
			'orderby'                => 'menu_order',
			'order'                  => 'ASC',
		]
	);

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) : $query->the_post();
			$post_id          = get_the_ID();
			$slug             = get_post()->post_name;
			$content          = get_post()->post_content;
			$global_location  = get_field( 'maigam_global_location' );
			$single_location  = get_field( 'maigam_single_location' );
			$archive_location = get_field( 'maigam_archive_location' );

			if ( $global_location ) {
				$ads['global'][] = maigam_filter_associative_array(
					[
						'id'       => $post_id,
						'slug'     => $slug,
						'location' => $global_location,
						'content'  => $content,
					]
				);
			}

			if ( $single_location ) {
				$ads['single'][] = maigam_filter_associative_array(
					[
						'id'                  => $post_id,
						'slug'                => $slug,
						'location'            => $single_location,
						'content'             => $content,
						'content_location'    => get_field( 'maigam_single_content_location' ),
						'content_count'       => get_field( 'maigam_single_content_count' ),
						'types'               => get_field( 'maigam_single_types' ),
						'keywords'            => get_field( 'maigam_single_keywords' ),
						'taxonomies'          => get_field( 'maigam_single_taxonomies' ),
						'taxonomies_relation' => get_field( 'maigam_single_taxonomies_relation' ),
						'authors'             => get_field( 'maigam_single_authors' ),
						'include'             => get_field( 'maigam_single_entries' ),
						'exclude'             => get_field( 'maigam_single_exclude_entries' ),
					]
				);
			}

			if ( $archive_location ) {
				$ads['archive'][] = maigam_filter_associative_array(
					[
						'id'            => $post_id,
						'slug'          => $slug,
						'location'      => $archive_location,
						'content'       => $content,
						'content_count' => get_field( 'maigam_archive_content_count' ),
						'types'         => get_field( 'maigam_archive_types' ),
						'taxonomies'    => get_field( 'maigam_archive_taxonomies' ),
						'terms'         => get_field( 'maigam_archive_terms' ),
						'exclude'       => get_field( 'maigam_archive_exclude_terms' ),
						'includes'      => get_field( 'maigam_archive_includes' ),
					]
				);
			}

		endwhile;
	}
	wp_reset_postdata();

	return $ads;
}


/**
 * Get content area hook locations.
 *
 * @since 0.1.0
 *
 * @return array
 */
function maigam_get_locations() {
	static $locations = null;

	if ( ! is_null( $locations ) ) {
		return $locations;
	}

	$locations = [
		'before_header'        => [
			'hook'     => 'genesis_before_header',
			'priority' => 5, // Before header default content area is 10.
		],
		'after_header'        => [
			'hook'     => 'genesis_after_header',
			'priority' => 15,
		],
		'before_loop'         => [
			'hook'     => 'genesis_loop',
			'priority' => 5,
		],
		'before_entry'         => [
			'hook'     => 'genesis_before_entry',
			'priority' => 10,
		],
		'before_entry_content' => [
			'hook'     => 'genesis_before_entry_content',
			'priority' => 10,
		],
		'content'              => [
			'hook'     => '', // No hooks, counted in content.
			'priority' => null,
		],
		'entries'              => [
			'hook'     => '', // No hooks, handled in function.
			'priority' => 10,
		],
		'after_entry_content'  => [
			'hook'     => 'genesis_after_entry_content',
			'priority' => 10,
		],
		'after_entry'          => [
			'hook'     => 'genesis_after_entry',
			'priority' => 8, // Comments are at 10.
		],
		'after_loop'           => [
			'hook'     => 'genesis_loop',
			'priority' => 15,
		],
		'before_footer'        => [
			'hook'     => 'genesis_after_content_sidebar_wrap',
			'priority' => 10,
		],
	];

	if ( maigam_is_product_archive() || maigam_is_product_singular() ) {
		$locations['before_loop'] = [
			'hook'     => 'woocommerce_before_shop_loop',
			'priority' => 12, // Notices are at 10.
		];

		$locations['before_entry']         = [
			'hook'     => 'woocommerce_before_single_product',
			'priority' => 12, // Notices are at 10.
		];

		$locations['before_entry_content'] = [
			'hook'     => 'woocommerce_after_single_product_summary',
			'priority' => 8, // Tabs are at 10.
		];

		$locations['after_entry_content']  = [
			'hook'     => 'woocommerce_after_single_product_summary',
			'priority' => 12, // Tabs are at 10, upsells and related products are 15.
		];

		$locations['after_entry']          = [
			'hook'     => 'woocommerce_after_single_product',
			'priority' => 10,
		];

		$locations['after_loop']           = [
			'hook'     => 'woocommerce_after_shop_loop',
			'priority' => 12, // Pagination is at 10.
		];
	}

	$locations = apply_filters( 'maigam_locations', $locations );

	if ( $locations ) {
		foreach ( $locations as $name => $location ) {
			$locations[ $name ] = wp_parse_args( (array) $location,
				[
					'hook'     => '',
					'priority' => null,
				]
			);
		}
	}

	return $locations;
}

/**
 * Returns the sub config.
 *
 * @since 0.1.0
 *
 * @param string $sub_config Name of config to get.
 *
 * @return array
 */
function maigam_get_config( $sub_config = '' ) {
	static $config = null;

	if ( ! is_array( $config ) ) {
		$config = require MAI_GAM_DIR . '/config.php';
	}

	if ( $sub_config ) {
		return isset( $config[ $sub_config ] ) ? $config[ $sub_config ] : [];
	}

	return $config;
}

/**
 * Gets all option values.
 *
 * @since 0.1.0
 *
 * @return array
 */
function maigam_get_options() {
	static $options = null;

	if ( ! is_null( $options ) ) {
		return $options;
	}

	return (array) get_option( 'mai_gam', [] );
}

/**
 * Gets a single option value.
 *
 * @since 0.1.0
 *
 * @param string $option Option name.
 * @param mixed  $default Default value.
 *
 * @return string
 */
function maigam_get_option( $option, $default = null ) {
	$options = maigam_get_options();

	return isset( $options[ $option ] ) ? $options[ $option ] : $default;
}

/**
 * Returns the GAM domain.
 *
 * @since 0.1.0
 *
 * @return string
 */
function maigam_get_domain() {
	return maigam_sanitize_domain( (string) maigam_get_option( 'domain' ) );
}

/**
 * Sanitizes domain to be used in GAM.
 *
 * @since 0.1.0
 *
 * @param string $domain
 *
 * @return string
 */
function maigam_sanitize_domain( string $domain ) {
	$domain = $domain ? (string) wp_parse_url( esc_url( (string) $domain ), PHP_URL_HOST ) : '';
	$domain = $domain ? $domain : (string) wp_parse_url( esc_url( home_url() ), PHP_URL_HOST );
	$domain = str_replace( 'www.', '', $domain );

	return $domain;
}

/**
 * Update a single option from mai_gam array of options.
 *
 * @since 0.1.0
 *
 * @param string $option Option name.
 * @param mixed  $value  Option value.
 *
 * @return void
 */
function maigam_update_option( $option, $value ) {
	$handle             = 'mai_gam';
	$options            = get_option( $handle, [] );
	$options[ $option ] = $value;

	update_option( $handle, $options );
}