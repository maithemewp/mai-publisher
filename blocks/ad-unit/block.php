<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_GAM_Ad_Unit_Block {

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
		add_filter( 'acf/load_field/key=maigam_ad_unit_id', [ $this, 'load_ad_unit_choices' ] );
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
		$styles = 'display:grid;place-items:center;aspect-ratio:728/90;background:rgba(0,0,0,0.1);font-variant:all-small-caps;letter-spacing:1px;';
		$id     = get_field( 'id' );

		if ( $is_preview ) {
			$text = $id ? __( 'Ad Placeholder', 'mai-gam' ) : __( 'No Ad Unit Selected', 'mai-gam' );
			printf( '<div style="%s">%s</div>', $styles, $text );
			return;
		}

		// Bail if no ID.
		if ( ! $id ) {
			return;
		}

		// Get formatted slot.
		$slot = $id ? $this->maybe_increment_slot( $id ) : '';

		printf( '<div style="%s"><div id="mai-ad-%s"><script>googletag.cmd.push(function(){googletag.display("mai-ad-%s")});</script></div></div>', $styles, $slot, $slot );
	}

	/**
	 * Increments the slot ID, if needed.
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
				'title'    => __( 'Locations Settings', 'mai-gam' ),
				'fields'   =>[
					[
						'label'        => __( 'Ad Unit', 'mai-gam' ),
						'key'          => 'maigam_ad_unit_id',
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
		$choices = [ '' => __( 'None', 'mai-gam' ) ];
		$units   = maigam_get_config( 'ad_units' );

		foreach ( $units as $slug => $unit ) {
			$choices[ $slug ] = $unit['post_title'];
		}

		$field['choices'] = $choices;

		return $field;
	}
}
