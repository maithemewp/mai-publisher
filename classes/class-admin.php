<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Admin {
	/**
	 * Construct the class.
	 */
	function __construct() {
		$this->hooks();
	}

	/**
	 * Add hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function hooks() {
		add_filter( 'display_post_states',                [ $this, 'add_post_state' ], 10, 2 );
		add_filter( 'manage_mai_ad_posts_columns',        [ $this, 'add_columns' ] );
		add_action( 'manage_mai_ad_posts_custom_column' , [ $this, 'display_columns' ], 10, 2 );
	}

	/**
	 * Display active content areas.
	 *
	 * @since 0.1.0
	 *
	 * @param array   $states Array of post states.
	 * @param WP_Post $post   Post object.
	 *
	 * @return array
	 */
	function add_post_state( $states, $post ) {
		if ( 'mai_ad' !== $post->post_type ) {
			return $states;
		}

		// Bail if not published with content.
		if ( ! ( 'publish' === $post->post_status && $post->post_content ) ) {
			return $states;
		}

		// Get vars.
		$global     = get_post_meta( $post->ID, 'maipub_global_location', true );
		$single     = get_post_meta( $post->ID, 'maipub_single_location', true );
		$singles    = get_post_meta( $post->ID, 'maipub_single_types', true );
		$archive    = get_post_meta( $post->ID, 'maipub_archive_location', true );
		$archives   = get_post_meta( $post->ID, 'maipub_archive_types', true );
		$taxonomies = get_post_meta( $post->ID, 'maipub_archive_taxonomies', true );
		$terms      = get_post_meta( $post->ID, 'maipub_archive_terms', true );

		// Bail if no locations.
		if ( ! ( $global || ( $single && $singles ) || ( $archive && ( $archives || $taxonomies || $terms ) ) ) ) {
			return $states;
		}

		$states[] = __( 'Active', 'mai-publisher' );

		return $states;
	}

	/**
	 * Adds the display taxonomy column after the title.
	 *
	 * @since 0.1.0
	 *
	 * @param string[] $columns An associative array of column headings.
	 *
	 * @return array
	 */
	function add_columns( $columns ) {
		// Remove date column.
		unset( $columns['date'] );

		// Add location column.
		$location                = [ 'maipub_location' => __( 'Location', 'mai-publisher' ) ];
		$columns                 = maipub_array_insert_after( $columns, 'title', $location );
		$columns['maipub_sizes'] = __( 'Sizes', 'mai-publisher' );

		return $columns;
	}

	/**
	 * Adds the display taxonomy column after the title.
	 *
	 * @since 0.1.0
	 *
	 * @param string $column  The name of the column to display.
	 * @param int    $post_id The current post ID.
	 *
	 * @return void
	 */
	function display_columns( $column, $post_id ) {
		if ( 'maipub_location' === $column ) {
			$this->display_location( $post_id );
		}

		if ( 'maipub_sizes' === $column ) {
			$this->display_sizes( $post_id );
		}
	}

	/**
	 * Display location info.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The current post ID.
	 *
	 * @return void
	 */
	function display_location( $post_id ) {

		$html       = '';
		$global     = get_post_meta( $post_id, 'maipub_global_location', true );
		$single     = get_post_meta( $post_id, 'maipub_single_location', true );
		$singles    = get_post_meta( $post_id, 'maipub_single_types', true );
		$archive    = get_post_meta( $post_id, 'maipub_archive_location', true );
		$archives   = get_post_meta( $post_id, 'maipub_archive_types', true );
		$taxonomies = get_post_meta( $post_id, 'maipub_archive_taxonomies', true );
		$terms      = get_post_meta( $post_id, 'maipub_archive_terms', true );
		$choices    = maipub_get_location_choices();

		// Bail if no locations.
		if ( ! ( $global || ( $single && $singles ) || ( $archive && ( $archives || $taxonomies || $terms ) ) ) ) {
			return;
		}

		if ( $global ) {
			$html .= sprintf( '%s (%s)', __( 'Global', 'mai-publisher' ), $choices['global'][ $global ] ) . '<br>';
		}

		if ( $singles ) {
			$array = [];

			foreach ( $singles as $post_type ) {
				$array[] = get_post_type_object( $post_type )->label;
			}

			if ( $array ) {
				$html .= sprintf( '%s (%s) -- %s', __( 'Single', 'mai-publisher' ), $choices['single'][ $single ], implode( ', ', $array ) ) . '<br>';
			}
		}

		if ( $archives || $taxonomies ) {
			$array = [];

			if ( $archives ) {
				foreach ( $archives as $post_type ) {
					$object = get_post_type_object( $post_type );

					if ( $object ) {
						$array[] = $object->label;
					}
				}
			}

			if ( $taxonomies ) {
				foreach ( $taxonomies as $taxonomy ) {
					$object = get_taxonomy( $taxonomy );

					if ( $object ) {
						$array[] = $object->label;
					}
				}
			}

			if ( $array ) {
				$html .= sprintf( '%s (%s) -- %s', __( 'Archives', 'mai-publisher' ), $choices['archive'][ $archive ], implode( ', ', $array ) ) . '<br>';
			}
		}

		if ( $terms ) {
			$array = [];

			foreach ( $terms as $term ) {
				$object = get_term( $term );

				if ( $object && ! is_wp_error( $object ) ) {
					$array[] = $object->name;
				}
			}

			if ( $array ) {
				// $html .= 'Terms -- ' . implode( ', ', $array ) . '<br>';
				$html .= sprintf( '%s (%s) -- %s', __( 'Terms', 'mai-publisher' ), $choices['archive'][ $archive ], implode( ', ', $array ) ) . '<br>';
			}
		}

		echo wptexturize( $html );
	}

	/**
	 * Display location info.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The current post ID.
	 *
	 * @return void
	 */
	function display_sizes( $post_id ) {
		$ad_units = maipub_get_config( 'ad_units' );
		$post     = get_post( $post_id );
		$slugs    = $this->get_ad_unit_slugs( $post->post_content );

		ray( $slugs );

		if ( ! $slugs ) {
			return;
		}

		$sizes = [];

		foreach ( $slugs as $slug ) {
			if ( ! isset( $ad_units[ $slug ]['sizes'] ) ) {
				continue;
			}

			$sizes[] = $slug . ': ' . $this->format_sizes( $ad_units[ $slug ]['sizes'] );
		}

		if ( ! $sizes ) {
			return;
		}

		echo implode( '<br>', $sizes );
	}

	function get_ad_unit_slugs( $input ) {
		$units  = [];
		$blocks = is_array( $input ) ? $input : parse_blocks( $input );

		foreach ( $blocks as $block ) {
			if ( 'acf/mai-ad-unit' === $block['blockName'] && isset( $block['attrs']['data']['id'] ) && ! empty( $block['attrs']['data']['id'] ) ) {
				$units[] = $block['attrs']['data']['id'];
			}
		}

		// If we have inner blocks, recurse.
		if ( isset( $block['innerBlocks'] ) && $block['innerBlocks'] ) {
			$units = array_merge( $units, $this->get_ad_unit_slugs( $block['innerBlocks'] ) );
		}

		return $units;
	}

	/**
	 * Format sizes for display in admin column.
	 *
	 * @since TBD
	 *
	 * @param array $array The sizes array.
	 *
	 * @return string
	 */
	function format_sizes( $array ) {
		$result = array_map( function( $inner_array ) {
			return '[' . implode(', ', $inner_array) . ']';
		}, $array );

		return implode( ', ', $result );
	}
}