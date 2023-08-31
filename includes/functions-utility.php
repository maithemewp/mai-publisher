<?php

/**
 * Insert a value or key/value pair after a specific key in an array.
 * If key doesn't exist, value is appended to the end of the array.
 *
 * @since 0.1.0
 *
 * @param array  $array
 * @param string $key
 * @param array  $new
 *
 * @return array
 */
function maigam_array_insert_after( array $array, $key, array $new ) {
	$keys  = array_keys( $array );
	$index = array_search( $key, $keys, true );
	$pos   = false === $index ? count( $array ) : $index + 1;

	return array_merge( array_slice( $array, 0, $pos ), $new, array_slice( $array, $pos ) );
}

/**
 * Sanitizes keyword strings to array.
 *
 * @since 0.1.0
 *
 * @param string $keywords Comma-separated keyword strings.
 *
 * @return array
 */
function maigam_sanitize_keywords( $keywords ) {
	$sanitized = [];
	$keywords  = trim( (string) $keywords );

	if ( ! $keywords ) {
		return $sanitized;
	}

	$sanitized = explode( ',', $keywords );
	$sanitized = array_map( 'trim', $sanitized );
	$sanitized = array_filter( $sanitized );
	$sanitized = array_map( 'maigam_strtolower', $sanitized );

	return $sanitized;
}

/**
 * Sanitizes taxonomy data for CCA.
 *
 * @since 0.1.0
 *
 * @param array $taxonomies The taxonomy data.
 *
 * @return array
 */
function maigam_sanitize_taxonomies( $taxonomies ) {
	if ( ! $taxonomies ) {
		return $taxonomies;
	}

	$sanitized = [];

	foreach ( $taxonomies as $data ) {
		$args = wp_parse_args( $data,
			[
				'taxonomy' => '',
				'terms'    => [],
				'operator' => 'IN',
			]
		);

		// Skip if we don't have all of the data.
		if ( ! ( $args['taxonomy'] && $args['terms'] && $args['operator'] ) ) {
			continue;
		}

		$sanitized[] = [
			'taxonomy' => esc_html( $args['taxonomy'] ),
			'terms'    => array_map( 'absint', (array) $args['terms'] ),
			'operator' => esc_html( $args['operator'] ),
		];
	}

	return $sanitized;
}

/**
 * Removes any array elements where the value is an empty string.
 *
 * @since 0.1.0
 *
 * @param array $array The taxonomy data.
 *
 * @return array
 */
function maigam_filter_associative_array( $array ) {
	foreach( $array as $key => $value ) {
		if ( '' === $value ) {
			unset( $array[ $key ] );
		} elseif ( is_array( $value ) ) {
			$value = maigam_filter_associative_array( $value );
		}
	}

	return $array;
}

/**
 * Sanitized a string to lowercase, keeping character encoding.
 *
 * @since 0.1.0
 *
 * @param string $string The string to make lowercase.
 *
 * @return string
 */
function maigam_strtolower( $string ) {
	return mb_strtolower( (string) $string, 'UTF-8' );
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

/**
 * Validate global content args.
 *
 * @since 0.1.0
 *
 * @param array $args The ad args.
 *
 * @return array
 */
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

/**
 * Validates single content args.
 *
 * @since 0.1.0
 *
 * @param array $args The ad args.
 *
 * @return array
 */
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

/**
 * Validates content archive args.
 *
 * @since 0.1.0
 *
 * @param array $args The ad args.
 *
 * @return array
 */
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