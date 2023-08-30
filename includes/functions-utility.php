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