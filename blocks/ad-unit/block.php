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
		$id       = get_field( 'id' );
		$ad_units = maipub_get_config( 'ad_units' );
		$unit     = $id ? $ad_units[ $id ] : [];
		$sizes    = $id ? $this->get_sizes( $unit ) : [];
		$styles   = $id ? $this->get_styles( $sizes, $is_preview ) : '';
		$slot     = $id ? $this->maybe_increment_slot( $id ) : '';
		$label    = maipub_get_option( 'label' );

		if ( $is_preview ) {
			$label = $id ? maipub_get_option( 'label' ) : __( 'No Ad Unit Selected', 'mai-publisher' );
			$text  = $id ? __( 'Ad Placeholder', 'mai-publisher' ) : __( 'No Ad Unit Selected', 'mai-publisher' );
			printf( '<div class="mai-ad-unit" data-label="%s"%s><span style="font-size:1.1rem;font-variant:all-small-caps;letter-spacing:1px;">%s</span></div>', $label, $styles, $text );
			return;
		}

		// Bail if no ID.
		if ( ! ( $id && isset( $ad_units[ $id ] ) ) ) {
			return;
		}

		printf( '<div class="mai-ad-unit" data-label="%s"%s><div id="mai-ad-%s"><script>googletag.cmd.push(function(){googletag.display("mai-ad-%s")});</script></div></div>', maipub_get_option( 'label' ), $styles, $slot, $slot );
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
			'lg' => $unit['sizes_desktop'],
			'md' => $unit['sizes_tablet'],
			'sm' => $unit['sizes_mobile'],
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

		foreach ( $sizes as $break => $values ) {
			if ( ! $values ) {
				continue;
			}

			// Max width.
			$styles .= sprintf( '--mai-ad-unit-max-width-%s:%spx;', $break, $values[0] );

			// Aspect ratio.
			if ( 2 === count( $values ) ) {
				$styles .= sprintf( '--mai-ad-unit-aspect-ratio-%s:%s/%s;', $break, $values[0], $values[1] );
			}
		}

		return $styles ? sprintf( ' style="%s"', $styles ) : '';
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
	function maybe_increment_slot( $slot ) {
		static $counts = [];

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
				'key'      => 'mai_field_group',
				'title'    => __( 'Locations Settings', 'mai-publisher' ),
				'fields'   =>[
					[
						'label'        => __( 'Ad Unit', 'mai-publisher' ),
						'key'          => 'maipub_ad_unit_id',
						'name'         => 'id',
						'type'         => 'select',
						'choices'      => [],
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
		$choices = [ '' => __( 'None', 'mai-publisher' ) ];
		$units   = maipub_get_config( 'ad_units' );

		foreach ( $units as $slug => $unit ) {
			$choices[ $slug ] = $unit['post_title'];
		}

		$field['choices'] = $choices;

		return $field;
	}
}
