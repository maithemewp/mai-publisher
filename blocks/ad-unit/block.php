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
		$label      = get_field( 'label' );
		$label_hide = get_field( 'label_hide' );
		$label      = $label ? $label : maipub_get_option( 'ad_label', false );
		$label      = $label_hide ? '' : $label;
		$ad_units   = maipub_get_config( 'ad_units' );
		$unit       = $id ? $ad_units[ $id ] : [];
		$sizes      = $id ? $this->get_sizes( $unit ) : [];
		$styles     = $id ? $this->get_styles( $sizes, $is_preview ) : '';
		$slot       = $id ? $this->get_slot( $id ) : '';

		// If previewing in editor, show placeholder.
		if ( $is_preview ) {
			$attr = [
				'class' => 'mai-ad-unit',
			];

			if ( isset( $block['className'] ) && $block['className'] ) {
				$attr['class'] .= ' ' . esc_attr( $block['className'] );
			}

			if ( $styles ) {
				$attr['style'] = $styles;
			}

			if ( $label ) {
				$attr['data-label'] = esc_attr( $label );
			}

			// $text  = $id ? __( 'Ad Placeholder', 'mai-publisher' ) : __( 'No Ad Unit Selected', 'mai-publisher' );
			$text  = $id ? $id : __( 'No Ad Unit Selected', 'mai-publisher' );
			printf( '<div%s><span style="font-size:1.1rem;">%s</span></div>', maipub_build_attributes( $attr ), $text );
			return;
		}

		// Bail if no ID.
		if ( ! ( $id && isset( $ad_units[ $id ] ) ) ) {
			return;
		}

		// Build slot.
		$slot = sprintf( 'mai-ad-%s', $slot );

		// Build script.
		if ( 'demo' === maipub_get_option( 'ad_mode', false ) ) {
			$mobile  = end( $unit['sizes_mobile'] );  // Last should be the largest.
			$tablet  = end( $unit['sizes_tablet'] );  // Last should be the largest.
			$desktop = end( $unit['sizes_desktop'] ); // Last should be the largest.
			$mobile  = ! is_array( $mobile ) ? [ 300, 350 ] : $mobile;
			$tablet  = ! is_array( $tablet ) ? [ 300, 350 ] : $tablet;
			$desktop = ! is_array( $desktop ) ? [ 300, 350 ] : $desktop;

			$script  = '';
			$script .= '<picture>';
				$script .= sprintf( '<source srcset="https://placehold.co/%s" media="(max-width: 727px)" />', implode( 'x', $mobile ) );
				$script .= sprintf( '<source srcset="https://placehold.co/%s" media="(min-width: 728px) and (max-width: 1023px)" />', implode( 'x', $tablet ) );
				$script .= sprintf( '<source srcset="https://placehold.co/%s" media="(min-width: 1024px)" />', implode( 'x', $desktop ) );
				$script .= sprintf( '<img src="https://placehold.co/%s" />', implode( 'x', $desktop ) );
			$script .= '</picture>';

		} else {
			$script = sprintf( '<script>window.googletag = window.googletag || {};googletag.cmd = googletag.cmd || [];if ( window.googletag && googletag.apiReady ) { googletag.cmd.push(function(){ googletag.display("%s"); }); }</script>', $slot );
		}

		// Start HTML.
		$html = '';

		// Sticky footer (bottoms sticky) adds another wrapper.
		if ( in_array( $pos, [ 'ts', 'bs' ] ) ) {
			$location = 'ts' === $pos ? 'header' : 'footer';

			// Start outer attributes.
			$attr_outer = [
				'class' => "mai-ad-unit-container mai-ad-unit-has-sticky mai-ad-unit-has-sticky-{$location}",
			];

			// Add custom classes.
			if ( isset( $block['className'] ) && $block['className'] ) {
				$attr_outer['class'] .= ' ' . esc_attr( $block['className'] );
			}

			// Add styles.
			if ( $styles ) {
				$attr_outer['style'] = $styles;
			}

			// Start inner attributes.
			$attr_inner = [
				'id'    => $slot,
				'class' => "mai-ad-unit mai-ad-unit-sticky mai-ad-unit-sticky-{$location}",
			];

			// Add type.
			if ( $type ) {
				$attr_inner['data-at'] = esc_attr( $type );
			}

			// Add position.
			if ( $pos ) {
				$attr_inner['data-ap'] = esc_attr( $pos );
			}

			// Add label.
			if ( $label ) {
				$attr_inner['data-label'] = esc_attr( $label );
			}

			// Add analytics tracking.
			$attr_inner['data-content-name']  = esc_attr( $slot );
			$attr_inner['data-track-content'] = null;

			// Get attributes string.
			$attributes = get_block_wrapper_attributes( $attr_outer );
			$attributes = str_replace( ' wp-block-acf-mai-ad-unit', '', $attributes );

			// Build HTML with extra wrap.
			$html .= sprintf( '<div %s>', $attributes );
				$html .= sprintf( '<div%s>', maipub_build_attributes( $attr_inner ) );
					$html .= $script;
				$html .= '</div>';
			$html .= '</div>';
		}
		// Not sticky footer.
		else {
			// Start attributes.
			$attr = [
				'id'    => $slot,
				'class' => 'mai-ad-unit',
			];

			// Add custom classes.
			if ( isset( $block['className'] ) && $block['className'] ) {
				$attr['class'] .= ' ' . esc_attr( $block['className'] );
			}

			// Add styles.
			if ( $styles ) {
				$attr['style'] = $styles;
			}

			// Add type.
			if ( $type ) {
				$attr['data-at'] = esc_attr( $type );
			}

			// Add position.
			if ( $pos ) {
				$attr['data-ap'] = esc_attr( $pos );
			}

			// Add label.
			if ( $label ) {
				$attr['data-label'] = esc_attr( $label );
			}

			// Add analytics tracking.
			$attr['data-content-name']  = esc_attr( $slot );
			$attr['data-track-content'] = null;

			// Get attributes string.
			$attributes = get_block_wrapper_attributes( $attr );
			$attributes = str_replace( ' wp-block-acf-mai-ad-unit', '', $attributes );

			// Build HTML.
			$html .= sprintf( '<div %s>', $attributes );
				$html .= $script;
			$html .= '</div>';
		}

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

			$largest_width  = 0;
			$largest_height = 0;

			// Check for largest width and height.
			foreach ( $item as $subitem ) {
				// Bail if not array, mostly for fluid.
				if ( ! is_array( $subitem ) ) {
					continue;
				}

				$width  = $subitem[0];
				$height = $subitem[1];

				if ( $width > $largest_width ) {
					$largest_width = $width;
				}

				if ( $height > $largest_height ) {
					$largest_height = $height;
				}
			}

			// Bail if we don't have at least a width.
			if ( ! $largest_width ) {
				continue;
			}

			// Set sizes.
			$sizes[ $key ] = array( $largest_width, $largest_height );
		}

		return $sizes;
	}

	/**
	 * Gets the inline styles.
	 *
	 * @since 0.1.0
	 *
	 * @param array $sizes      The ad unit responsive sizes.
	 * @param bool  $is_preview Whether the block is being previewed in the editor.
	 *
	 * @return string
	 */
	function get_styles( $sizes, $is_preview ) {
		$styles = '';

		if ( $is_preview ) {
			$styles .= 'background:rgba(0,0,0,0.05);border:2px dashed rgba(0,0,0,0.25);';
		}

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
	 * Increments the slot ID, if needed.
	 *
	 * @since 0.1.0
	 *
	 * @param string $slot
	 *
	 * @return string
	 */
	function get_slot( $slot ) {
		static $counts  = [];

		if ( isset( $counts[ $slot ] ) ) {
			$counts[ $slot ]++;
			$slot = $slot . '-' . $counts[ $slot ];
		} else {
			$counts[ $slot ] = 1;
		}

		return $slot;
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

		foreach ( $units as $slug => $unit ) {
			$choices[ $slug ] = $slug;
		}

		$field['choices'] = $choices;

		return $field;
	}
}
