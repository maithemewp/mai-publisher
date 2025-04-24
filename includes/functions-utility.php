<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Get consent.
 *
 * @since TBD
 *
 * @return bool
 */
function maipub_get_consent() {
	return isset( $_COOKIE['maipub_consent'] ) ? (bool) $_COOKIE['maipub_consent'] : false;
}

/**
 * Get a GAM-compliant PPID from the logged in user.
 * PHP equivalent of JavaScript generatePpid function in mai-publisher-ads.js
 * except this doesn't generate a random PPID if no identifier is provided and it
 * only checks for cookie since session storage is not available in PHP.
 *
 * @since TBD
 *
 * @param string $identifier The identifier (Matomo Visitor ID or user email).
 *
 * @return string A GAM-compliant PPID (64-character hexadecimal) or null if generation fails.
 */
function maipub_get_ppid() {
	try {
		// If user is logged in, get user email.
		$user_email = '';
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$user_email   = $current_user->user_email;
		}

		// Convert input to string to handle unexpected types (e.g., null, number).
		// Ensures compatibility with hash function, which requires a string.
		$input = strval( $user_email ?: '' );

		// Set local PPID variable.
		$local_ppid = '';

		// Check for existing PPID in cookie.
		// We don't need to check for consent, because this would only be stored
		// via JS if consent was given.
		if ( isset( $_COOKIE['maipub_ppid'] ) && preg_match( '/^[0-9a-f]{64}$/', $_COOKIE['maipub_ppid'] ) ) {
			$local_ppid = $_COOKIE['maipub_ppid'];
		}

		// If we don't have an identifier and we have a cached PPID.
		if ( ! $input && $local_ppid ) {
			return $local_ppid;
		}

		// Bail if no input.
		if ( ! $input ) {
			return '';
		}

		// Compute SHA-256 hash of the input.
		// SHA-256 produces a 64-character hexadecimal hash, ensuring the output is cryptographically secure
		// and meaningless to Google, meeting GAM's encryption requirement.
		$final_ppid = hash( 'sha256', $input );

		// Return without storing in cookies or session storage.
		// This makes sure the JS checks against local PPID are always accurate
		// instead of trusting the PHP and JS checks are the same,
		// which could cause issues if the PHP and JS checks are ever different.
		return $final_ppid;

	} catch ( Exception $error ) {
		// Catch any errors (e.g., invalid input, random_bytes failure) to prevent
		// script failures that could break ad functionality (e.g., GAM, Prebid).
		// Log the error for debugging without disrupting execution.
		error_log( 'Mai Publisher: Error transforming ppid: ' . $error->getMessage() );

		// Return empty string to allow calling code to skip PPID usage safely.
		return '';
	}
}

/**
 * Check if a post has a term or a child term.
 *
 * @access private
 *
 * @version 1.5.0
 *
 * @param string|int  $slug_or_id The 'slug', 'name', 'term_id' (or 'id', 'ID'), or 'term_taxonomy_id'.
 * @param string      $taxonomy   The taxonomy name.
 * @param int|WP_Post $post       The post or post ID to check.
 *
 * @return  bool
 */
function maipub_has_term_or_descendant( $slug_or_id, $taxonomy, $post = 0 ) {
	static $cache = [];

	// Set required vars.
	$type = is_numeric( $slug_or_id ) ? 'id' : 'slug';
	$term = get_term_by( $type, $slug_or_id, $taxonomy );
	$post = get_post( $post ?: get_the_ID() );

	// Bail if no post or term.
	if ( ! ( $post && $term ) ) {
		return false;
	}

	// Check cache.
	if ( isset( $cache[ $post->ID ][ $taxonomy ][ $term->term_id ] ) ) {
		return $cache[ $post->ID ][ $taxonomy ][ $term->term_id ];
	}

	// If has main term.
	if ( has_term( $term->term_id, $taxonomy, $post ) ) {
		$cache[ $post->ID ][ $taxonomy ][ $term->term_id ] = true;
		return $cache[ $post->ID ][ $taxonomy ][ $term->term_id ];
	}

	// Get the term children. Only accepts term ID. Returns array of term IDs.
	$children = get_term_children( $term->term_id, $taxonomy );

	// Bail if no children.
	if ( ! $children ) {
		$cache[ $post->ID ][ $taxonomy ][ $term->term_id ] = false;
		return $cache[ $post->ID ][ $taxonomy ][ $term->term_id ];
	}

	// Bail if not in child term.
	if ( ! has_term( $children, $taxonomy, $post ) ) {
		$cache[ $post->ID ][ $taxonomy ][ $term->term_id ] = false;
		return $cache[ $post->ID ][ $taxonomy ][ $term->term_id ];
	}

	// Store in cache.
	$cache[ $post->ID ][ $taxonomy ][ $term->term_id ] = true;

	// Yep!
	return $cache[ $post->ID ][ $taxonomy ][ $term->term_id ];
}

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
 * This would happen if a Mai Ad block was used inside of a Mai CCA (i think, this comment is from Mai Analytics),
 * the CCA would take precedence and the Ad links will have the content piece.
 *
 * @since 0.7.0
 *
 * @param string $content The content.
 * @param string $name    The name.
 *
 * @return string
 */
function maipub_add_attributes( $content, $name ) {
	// Bail if no content.
	if ( ! $content ) {
		return $content;
	}

	$dom = maipub_get_dom_document( $content );

	$children = $dom->childNodes;

	// Bail if no nodes.
	if ( ! $children->length ) {
		return $content;
	}

	// Get existing trackers.
	$xpath   = new DOMXPath( $dom );
	$tracked = $xpath->query( '//*[@data-track-content] | //*[@data-tcontent-name]' );

	// If we have existing trackers, remove them.
	if ( $tracked->length ) {
		foreach ( $tracked as $node ) {
			// Skip if not an element we can add attributes to.
			if ( ! $node instanceof DOMElement ) {
				continue;
			}

			$node->removeAttribute( 'data-content-name' );
			$node->removeAttribute( 'data-track-content' );
			$node->normalize();
		}
	}

	// Set attributes to first child element
	foreach ( $children as $node ) {
		// Skip if not an element we can add attributes to.
		if ( ! $node instanceof DOMElement ) {
			continue;
		}

		// Set main attributes to all top level child elements.
		$node->setAttribute( 'data-content-name', esc_attr( $name ) );
		$node->setAttribute( 'data-track-content', '' );

		// Break after first element.
		break;
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
	$content = maipub_get_dom_html( $dom );

	return $content;
}

/**
 * Encodes a string and removes special characters.
 * This is for encoding GAM Network Code to Sellers ID.
 * This needs to match what is in Mai Sellers JSON plugin.
 *
 * @access private
 *
 * @since 0.13.0
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
 * @since 0.13.0
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