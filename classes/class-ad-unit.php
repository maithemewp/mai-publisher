<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Ad_Unit {
	protected $args;

	/**
	 * Construct the class.
	 */
	function __construct( $args ) {
		$this->args = $args;
	}

	/**
	 * Renders an ad.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	function render() {
		// Parse args.
		$args = wp_parse_args( $this->args,
			[
				'preview'    => false,
				'id'         => '',
				'type'       => '',
				'position'   => '',
				'split_test' => '',
				'targets'    => '',
				'label'      => '',
				'label_hide' => '',
				'context'    => '',
			]
		);

		$is_preview = (bool) $args['preview'];
		$id         = sanitize_text_field( $args['id'] );
		$type       = sanitize_text_field( $args['type'] );
		$pos        = sanitize_text_field( $args['position'] );
		$split_test = sanitize_text_field( $args['split_test'] );
		$targets    = sanitize_text_field( $args['targets'] );
		$label      = sanitize_text_field( $args['label'] );
		$label_hide = (bool) sanitize_text_field( $args['label_hide'] );
		$label      = $label ? $label : maipub_get_option( 'ad_label', false );
		$label      = $label_hide ? '' : $label;
		$context    = sanitize_text_field( $args['context'] );
		$config     = maipub_get_config( $context );
		$ad_units   = isset( $config['ad_units'] ) ? $config['ad_units'] : [];

		// If previewing in editor and no ad selected, show placeholder.
		if ( $is_preview && ! ( $id && isset( $ad_units[ $id ] ) ) ) {
			// This styling should match Mai Ad block.
			printf( '<div style="text-align:center;background:rgba(0,0,0,0.05);border:2px dashed rgba(0,0,0,0.25);"><span style="font-size:1.1rem;">%s</span></div>',  __( 'No Ad Unit Selected', 'mai-publisher' ) );
			return;
		}

		// Bail if not a valid ad unit.
		if ( ! ( $id && isset( $ad_units[ $id ] ) ) ) {
			return;
		}

		// Set vars.
		$unit   = $ad_units[ $id ];
		$sizes  = $this->get_sizes( $unit );
		$styles = $this->get_styles( $sizes );

		// Start HTML.
		$html  = '';
		$inner = '';

		// Build script.
		if ( $is_preview || 'demo' === maipub_get_option( 'ad_mode', false ) ) {
			$mobile       = end( $unit['sizes_mobile'] );  // Last should be the largest.
			$tablet       = end( $unit['sizes_tablet'] );  // Last should be the largest.
			$desktop      = end( $unit['sizes_desktop'] ); // Last should be the largest.
			$mobile       = ! is_array( $mobile ) ? [ 300, 350 ] : $mobile;
			$tablet       = ! is_array( $tablet ) ? [ 300, 350 ] : $tablet;
			$desktop      = ! is_array( $desktop ) ? [ 300, 350 ] : $desktop;
			$targets_text = '';

			if ( 'sidebar' === $id ) {
				$mobile  = [ 300, 250 ];
				$tablet  = [ 300, 250 ];
				$desktop = [ 300, 250 ];
			}

			// If targets, add them.
			if ( $targets ) {
				$targets_text  = explode( ',', $targets );
				$targets_text  = array_map( 'trim', $targets_text );
				$targets_text  = implode( ', ', $targets_text );
			}

			// If native.
			if ( 'native' === $id ) {
				// Setup attr.
				$attr = ' style="padding:1em;"';

				// Build text.
				$mobile_text  = '<strong>' . __( 'Native', 'mai-publisher' ) . '</strong><br>' . $id;
				$tablet_text  = '<strong>' . __( 'Native', 'mai-publisher' ) . '</strong><br>' . $id;
				$desktop_text = '<strong>' . __( 'Native', 'mai-publisher' ) . '</strong><br>' . $id;
			}
			// Standard display ad.
			else {
				// Add pipe or break.
				$br = 'leaderboard-small' === $id ? ' | ' : '<br>';

				// Setup attr.
				$attr = sprintf( ' style="--width-mobile:%s;--height-mobile:%s;--width-tablet:%s;--height-tablet:%s;--width-desktop:%s;--height-desktop:%s;"',
					$mobile[0] . 'px',
					$mobile[1] . 'px',
					$tablet[0] . 'px',
					$tablet[1] . 'px',
					$desktop[0] . 'px',
					$desktop[1] . 'px'
				);

				// Build text.
				$mobile_text  = '<strong>' . implode( 'x', $mobile ) . '</strong>' . $br . $id;
				$tablet_text  = '<strong>' . implode( 'x', $tablet ) . '</strong>' . $br . $id;
				$desktop_text = '<strong>' . implode( 'x', $desktop ) . '</strong>' . $br . $id;
			}

			// If targets, add them.
			if ( $targets ) {
				$targets_text  = explode( ',', $targets );
				$targets_text  = array_map( 'trim', $targets_text );
				$targets_text  = implode( ', ', $targets_text );
				$mobile_text  .= $br . $targets_text;
				$tablet_text  .= $br . $targets_text;
				$desktop_text .= $br . $targets_text;
			}

			// Build inner HTML.
			$inner .= sprintf( '<div class="maipub-placeholder"%s>', $attr );
				$inner .= sprintf( '<div class="maipub-placeholder__caption mobile">%s</div>', $mobile_text );
				$inner .= sprintf( '<div class="maipub-placeholder__caption tablet">%s</div>', $tablet_text );
				$inner .= sprintf( '<div class="maipub-placeholder__caption desktop">%s</div>', $desktop_text );
			$inner .= '</div>';
		}

		// Start spacer attributes.
		$spacer_attr = [
			'class' => 'mai-ad-unit-spacer',
		];

		// Start container attributes.
		$outer_attr = [
			'class' => 'mai-ad-unit-container',
		];

		// Start ad attributes.
		$inner_attr = [
			'class'     => 'mai-ad-unit',
			'data-unit' => $id,
		];

		// If context.
		if ( $context ) {
			$inner_attr['class'] .= ' mai-ad-unit-' . $context;
		}

		// Check if sticky.
		$is_sticky = ! $is_preview && in_array( $pos, [ 'ts', 'bs' ] );

		// Handle sticky.
		if ( $is_sticky ) {
			$location             = 'ts' === $pos ? 'header' : 'footer';
			$outer_attr['class'] .= " mai-ad-unit-sticky mai-ad-unit-sticky-{$location}";
		}

		// Handle native.
		if ( 'native' === $id ) {
			$outer_attr['class'] .= ' mai-ad-unit-native';
		}

		// Add custom classes.
		if ( isset( $block['className'] ) && $block['className'] ) {
			$outer_attr['class'] .= ' ' . esc_attr( $block['className'] );
		}

		// Add styles.
		if ( $styles ) {
			if ( $is_sticky ) {
				$spacer_attr['style'] = $styles;
			}

			$outer_attr['style']  = $styles;
		}

		// Add type.
		if ( $type ) {
			$inner_attr['data-at'] = esc_attr( $type );
		}

		// Add position.
		if ( $pos ) {
			$inner_attr['data-ap'] = esc_attr( $pos );
		}

		// Add label.
		if ( $label ) {
			$inner_attr['data-label'] = esc_attr( $label );
		}

		// Custom key value pairs.
		if ( $targets ) {
			$inner_attr['data-targets'] = esc_attr( $targets );
		}

		// Split testing.
		if ( $split_test ) {
			$inner_attr['data-st'] = esc_attr( $split_test );
		}

		// If context.
		if ( $context ) {
			$inner_attr['data-context'] = esc_attr( $context );
		}

		// Get spacer attributes string.
		if ( $is_sticky ) {
			$spacer_attr = $is_preview ? maipub_build_attributes( $spacer_attr ) : ' ' . get_block_wrapper_attributes( $spacer_attr );
			$spacer_attr = str_replace( ' wp-block-acf-mai-ad-unit-client', '', $spacer_attr );
			$spacer_attr = str_replace( ' wp-block-acf-mai-ad-unit', '', $spacer_attr );
		}

		// Get attributes string.
		$outer_attr = maipub_build_attributes( $outer_attr );
		$inner_attr = $is_preview ? maipub_build_attributes( $inner_attr ) : ' ' . get_block_wrapper_attributes( $inner_attr );
		$inner_attr = str_replace( ' wp-block-acf-mai-ad-unit-client', '', $inner_attr );
		$inner_attr = str_replace( ' wp-block-acf-mai-ad-unit', '', $inner_attr );

		// Build HTML.
		$html .= $is_sticky ? sprintf( '<div%s></div>', $spacer_attr ) : '';
		$html .= sprintf( '<div%s>', $outer_attr ); // No space for `maipub_build_attributes()`.
			$html .= sprintf( '<div%s>', $inner_attr );
				$html .= $inner;
			$html .= '</div>';
		$html .= '</div>';

		// Allow filtering.
		$html = apply_filters( 'mai_publisher_ad_unit', $html );

		echo $html;
	}

	/**
	 * Gets the sizes for the ad unit inline CSS.
	 *
	 * @since 0.1.0
	 *
	 * @param array $unit The ad unit data from config.
	 *
	 * @return array
	 */
	function get_sizes( $unit ) {
		$sizes = [];
		$array = [
			'sm' => $unit['sizes_mobile'],
			'md' => $unit['sizes_tablet'],
			'lg' => $unit['sizes_desktop'],
		];

		foreach ( $array as $key => $item ) {
			$sizes[ $key ] = [];

			if ( ! is_array( $item ) ) {
				continue;
			}

			$largest_width = 0;
			$height        = 0;

			// Check for largest width and height.
			foreach ( $item as $subitem ) {
				// Bail if not array, mostly for fluid.
				if ( ! is_array( $subitem ) ) {
					continue;
				}

				$width = $subitem[0];

				if ( $width > $largest_width ) {
					$largest_width = $width;
					$height        = $subitem[1];
				}
			}

			// Bail if we don't have at least a width.
			if ( ! $largest_width ) {
				continue;
			}

			// Set sizes.
			$sizes[ $key ] = array( $largest_width, $height );
		}

		return $sizes;
	}

	/**
	 * Gets the inline styles.
	 *
	 * @since 0.1.0
	 *
	 * @param array $sizes The ad unit responsive sizes.
	 *
	 * @return string
	 */
	function get_styles( $sizes ) {
		$styles = '';

		// Build width.
		foreach ( $sizes as $break => $values ) {
			if ( ! ( $values && is_array( $values ) ) ) {
				continue;
			}

			$styles .= sprintf( '--mai-ad-unit-max-width-%s:%spx;', $break, $values[0] );
		}

		// Build aspect-ratio.
		foreach ( $sizes as $break => $values ) {
			if ( ! ( $values && is_array( $values ) ) ) {
				continue;
			}

			$styles .= sprintf( '--mai-ad-unit-aspect-ratio-%s:%s/%s;', $break, $values[0], $values[1] );
		}

		return $styles;
	}
}