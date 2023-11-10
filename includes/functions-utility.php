<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

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
function maipub_array_insert_after( array $array, $key, array $new ) {
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
function maipub_sanitize_keywords( $keywords ) {
	$sanitized = [];
	$keywords  = trim( (string) $keywords );

	if ( ! $keywords ) {
		return $sanitized;
	}

	$sanitized = explode( ',', $keywords );
	$sanitized = array_map( 'trim', $sanitized );
	$sanitized = array_filter( $sanitized );
	$sanitized = array_map( 'maipub_strtolower', $sanitized );

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
function maipub_sanitize_taxonomies( $taxonomies ) {
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
 * Generate a string of HTML attributes.
 *
 * @since 0.1.0
 *
 * @link https://github.com/mcaskill/php-html-build-attributes
 *
 * @param array  $attr   Associative array representing attribute names and values.
 * @param string $escape Callback function to escape the values for HTML attributes.
 *
 * @return string Returns a string of HTML attributes
 */
function maipub_build_attributes( $attr, $escape = 'esc_attr' ) {
	$html = '';

	if ( ! $attr ) {
		return $html;
	}

	foreach ( $attr as $name => $value ) {
		if ( is_null( $value ) ) {
			$html .= sprintf( ' %s', $name );
		} else {
			$html .= sprintf( ' %s="%s"', $name, $escape( $value ) );
		}
	}

	return $html;
}

/**
 * Adds element attributes.
 *
 * If you set the same attribute or the same class on multiple elements within one block,
 * the first element found will always win. Nested content blocks are currently not supported in Matomo.
 * This would happen if a Mai Ad block was used inside of a Mai CCA (i think, this is from Mai Analytics),
 * the CCA would take precedence and the Ad links will have the content piece.
 *
 * @since 0.7.0
 *
 * @param string $content The content.
 * @param string $name    The name.
 * @param bool   $force   Whether to force override existing tracking attributes, if they already exist.
 *
 * @return string
 */
function maipub_add_attributes( $content, $name ) {
	// Bail if no content.
	if ( ! $content ) {
		return $content;
	}

	$dom      = maipub_get_dom_document( $content );
	$children = $dom->childNodes;

	// Bail if no nodes.
	if ( ! $children->length ) {
		return $content;
	}

	// Remove trackers from children.
	$xpath   = new DOMXPath( $dom );
	$tracked = $xpath->query( '//*[@data-track-content] | //*[@data-tcontent-name]' );

	if ( $tracked->length ) {
		foreach ( $tracked as $node ) {
			// Skip if not an element we can add attributes to.
			if ( 'DOMElement' !== get_class( $node ) ) {
				continue;
			}

			$node->removeAttribute( 'data-content-name' );
			$node->removeAttribute( 'data-track-content' );
			$node->normalize();
		}
	}

	if ( 1 === $children->length ) {
		// Get first element and set main attributes.
		$first = $children->item(0);

		// Make sure it's an element we can add attributes to.
		if ( 'DOMElement' === get_class( $first ) ) {
			$first->setAttribute( 'data-content-name', esc_attr( $name ) );
			$first->setAttribute( 'data-track-content', '' );
		}

	} else {
		foreach ( $children as $node ) {
			// Skip if not an element we can add attributes to.
			if ( 'DOMElement' !== get_class( $node ) ) {
				continue;
			}

			// Set main attributes to all top level child elements.
			$node->setAttribute( 'data-content-name', esc_attr( $name ) );
			$node->setAttribute( 'data-track-content', '' );
		}
	}

	// Query elements.
	$xpath   = new DOMXPath( $dom );
	$actions = $xpath->query( '//a | //button | //input[@type="submit"]' );

	if ( $actions->length ) {
		foreach ( $actions as $node ) {
			$piece = 'input' === $node->tagName ? $node->getAttribute( 'value' ) : $node->textContent;
			$piece = trim( esc_attr( $piece ) );

			if ( $piece ) {
				if ( ! $node->hasAttribute( 'data-content-piece' ) ) {
					$node->setAttribute( 'data-content-piece', $piece );
				}
			}

			// Disabled, because target should happen automatically via href in Matomo.
			// $target = 'a' === $node->tagName ? $node->getAttribute( 'href' ) : '';
			// if ( $target ) {
			// 	$node->setAttribute( 'data-content-target', $target );
			// }
		}
	}

	// Save new content.
	$content = $dom->saveHTML();

	return $content;
}

/**
 * Encodes a string and removes special characters.
 * This is for encoding GAM Network Code to Sellers ID.
 * This needs to match what is in Mai Sellers JSON plugin.
 *
 * @access private
 *
 * @since TBD
 *
 * @param string    $input
 * @param int|false $limit Character limit.
 *
 * @return string
 */
function maipub_encode( $input, $limit = false ) {
	$base64 = base64_encode( $input );

	// Replace characters not in the custom alphabet with '='.
	$base64 = strtr( $base64, '+/', '-_' );

	// Remove any trailing '=' characters.
	$base64 = rtrim( $base64, '=' );

	// If trimming characters.
	if ( $limit ) {
		$base64 = substr( $base64, 0, $limit );
	}

	return $base64;
}

/**
 * Decodes a string and removes special characters.
 *
 * @since TBD
 *
 * @param string $input
 *
 * @return string
 */
function maipub_decode( $input ) {
	// Add back any trailing '=' characters.
	$input = str_pad( $input, (int) ( ceil( strlen( $input ) / 4 ) * 4 ), '=', STR_PAD_RIGHT );

	// Replace characters in the custom alphabet.
	$input = strtr( $input, '-_', '+/' );

	return base64_decode( $input );
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
function maipub_filter_associative_array( $array ) {
	foreach( $array as $key => $value ) {
		if ( '' === $value ) {
			unset( $array[ $key ] );
		} elseif ( is_array( $value ) ) {
			$value = maipub_filter_associative_array( $value );
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
function maipub_strtolower( $string ) {
	return mb_strtolower( (string) $string, 'UTF-8' );
}