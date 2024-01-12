<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Ad_Unit_Block {
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
		add_action( 'acf/init',                             [ $this, 'register_block' ] );
		add_action( 'acf/init',                             [ $this, 'register_field_group' ] );
		add_filter( 'acf/load_field/key=maipub_ad_unit_id', [ $this, 'load_ad_unit_choices' ] );
	}

	/**
	 * Registers block.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function register_block() {
		register_block_type( __DIR__ . '/block.json',
			[
				'render_callback' => [ $this, 'render_block' ],
			]
		);
	}

	/**
	 * Callback function to render the block.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $block      The block settings and attributes.
	 * @param string $content    The block inner HTML (empty).
	 * @param bool   $is_preview True during AJAX preview.
	 * @param int    $post_id    The post ID this block is saved to.
	 *
	 * @return void
	 */
	function render_block( $block, $content = '', $is_preview = false, $post_id = 0 ) {
		$id         = get_field( 'id' );
		$type       = get_field( 'type' );
		$pos        = get_field( 'position' );
		$split_test = get_field( 'split_test' );
		$targets    = get_field( 'targets' );
		$label      = get_field( 'label' );
		$label_hide = get_field( 'label_hide' );
		$label      = $label ? $label : maipub_get_option( 'ad_label', false );
		$label      = $label_hide ? '' : $label;
		$ad_units   = maipub_get_config( 'ad_units' );

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
			$mobile  = end( $unit['sizes_mobile'] );  // Last should be the largest.
			$tablet  = end( $unit['sizes_tablet'] );  // Last should be the largest.
			$desktop = end( $unit['sizes_desktop'] ); // Last should be the largest.
			$mobile  = ! is_array( $mobile ) ? [ 300, 350 ] : $mobile;
			$tablet  = ! is_array( $tablet ) ? [ 300, 350 ] : $tablet;
			$desktop = ! is_array( $desktop ) ? [ 300, 350 ] : $desktop;

			if ( 'sidebar' === $id ) {
				$mobile  = [ 300, 250 ];
				$tablet  = [ 300, 250 ];
				$desktop = [ 300, 250 ];
			}

			// Build inner HTML.
			$inner .= '<picture>';
				$inner .= sprintf( '<source srcset="https://placehold.co/%s" media="(max-width: 727px)">', implode( 'x', $mobile ) );
				$inner .= sprintf( '<source srcset="https://placehold.co/%s" media="(min-width: 728px) and (max-width: 1023px)">', implode( 'x', $tablet ) );
				$inner .= sprintf( '<source srcset="https://placehold.co/%s" media="(min-width: 1024px)">', implode( 'x', $desktop ) );
				$inner .= sprintf( '<img src="https://placehold.co/%s">', implode( 'x', $desktop ) );
			$inner .= '</picture>';
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

		// Check if sticky.
		$is_sticky = ! $is_preview && in_array( $pos, [ 'ts', 'bs' ] );

		// Handle sticky.
		if ( $is_sticky ) {
			$location             = 'ts' === $pos ? 'header' : 'footer';
			$outer_attr['class'] .= " mai-ad-unit-sticky mai-ad-unit-sticky-{$location}";
		}

		// Add custom classes.
		if ( isset( $block['className'] ) && $block['className'] ) {
			$outer_attr['class'] .= ' ' . esc_attr( $block['className'] );
		}

		// Add styles.
		if ( $styles ) {
			$spacer_attr['style'] = $styles;
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

		// Get attributes string.
		$spacer_attr = get_block_wrapper_attributes( $spacer_attr );
		$spacer_attr = str_replace( ' wp-block-acf-mai-ad-unit', '', $spacer_attr );
		$outer_attr  = maipub_build_attributes( $outer_attr );
		$inner_attr  = str_replace( ' wp-block-acf-mai-ad-unit', '', get_block_wrapper_attributes( $inner_attr ) );

		// Build HTML.
		$html .= $is_sticky ? sprintf( '<div %s></div>', $spacer_attr ) : '';
		$html .= sprintf( '<div %s>', $outer_attr );
			$html .= sprintf( '<div %s>', $inner_attr );
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

	/**
	 * Registers field group.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function register_field_group() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key'      => 'maipub_ad_unit_block_field_group',
				'title'    => __( 'Locations Settings', 'mai-publisher' ),
				'fields'   =>[
					[
						'label'   => __( 'Ad unit', 'mai-publisher' ),
						'key'     => 'maipub_ad_unit_id',
						'name'    => 'id',
						'type'    => 'select',
						'choices' => [],
					],
					[
						'label'   => __( 'Ad type', 'mai-publisher' ),
						'key'     => 'maipub_ad_unit_type',
						'name'    => 'type',
						'type'    => 'select',
						'choices' => [
							''   => __( 'Not set', 'mai-publisher' ),
							'sp' => __( 'Sponsorship', 'mai-publisher' ),
							'st' => __( 'Standard', 'mai-publisher' ),
							'h'  => __( 'House', 'mai-publisher' ),
							'n'  => __( 'Native', 'mai-publisher' ),
							'ng' => __( 'Native Grid', 'mai-publisher' ),
							'nc' => __( 'Native Comments', 'mai-publisher' ),
						],
					],
					[
						'label'   => __( 'Ad Position', 'mai-publisher' ),
						'key'     => 'maipub_ad_unit_position',
						'name'    => 'position',
						'type'    => 'select',
						'choices' => [
							''     => __( 'Not set', 'mai-publisher' ),
							'atf'  => __( 'Above the Fold', 'mai-publisher' ),
							'btf'  => __( 'Below the Fold', 'mai-publisher' ),
							's'    => __( 'Sidebar', 'mai-publisher' ),
							'satf' => __( 'Sidebar Above the Fold', 'mai-publisher' ),
							'vs'   => __( 'Sticky Vertical', 'mai-publisher' ),
							'ts'   => __( 'Sticky Top Horizontal', 'mai-publisher' ),
							'bs'   => __( 'Sticky Bottom Horizontal', 'mai-publisher' ),
						],
					],
					[
						'label'   => __( 'Split Testing', 'mai-publisher' ),
						'key'     => 'maipub_ad_unit_split_test',
						'name'    => 'split_test',
						'type'    => 'select',
						'choices' => [
							''     => __( 'Not set', 'mai-publisher' ),
							'rand' => __( 'Random (0-99)', 'mai-publisher' ),
						],
					],
					[
						'label'        => __( 'Key/Value Pairs', 'mai-publisher' ),
						'instructions' => __( 'Comma-separated key value pairs. Example: a=b, d=f', 'mai-publisher' ),
						'key'          => 'maipub_ad_unit_targets',
						'name'         => 'targets',
						'type'         => 'text',
					],
					[
						'label'             => __( 'Label override', 'mai-publisher' ),
						'key'               => 'maipub_ad_unit_label',
						'name'              => 'label',
						'type'              => 'text',
						'conditional_logic' => [
							[
								[
									'field'    => 'maipub_ad_unit_label_hide',
									'operator' => '!=',
									'value'    => '1',
								],
							],
						],
					],
					[
						'message' => __( 'Hide label', 'mai-publisher' ),
						'key'     => 'maipub_ad_unit_label_hide',
						'name'    => 'label_hide',
						'type'    => 'true_false',
					],
				],
				'location' => [
					[
						[
							'param'    => 'block',
							'operator' => '==',
							'value'    => 'acf/mai-ad-unit',
						],
					],
				],
			]
		);
	}

	/**
	 * Loads ad unit choices.
	 *
	 * @since 0.1.0
	 *
	 * @param array $field The field data.
	 *
	 * @return array
	 */
	function load_ad_unit_choices( $field ) {
		if ( ! is_admin() ) {
			return $field;
		}

		$choices = [ '' => __( 'None', 'mai-publisher' ) ];
		$units   = maipub_get_config( 'ad_units' );
		$legacy  = maipub_get_legacy_ad_units();

		foreach ( $units as $slug => $unit ) {
			$label = $slug;

			if ( isset( $legacy[ $slug ] ) ) {
				$label .= ' ' . __( '(legacy)', 'mai-publisher' );
			}

			$choices[ $slug ] = $label;
		}

		$field['choices'] = $choices;

		return $field;
	}
}
