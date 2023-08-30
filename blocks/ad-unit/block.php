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
	public static function do_block( $block, $content = '', $is_preview = false, $post_id = 0 ) {
		echo '<h2>TBD</h2>';
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
		add_filter( 'acf/load_field/key=maigam_ad_unit_id', [ $this, 'load_ids' ] );
	}

	/**
	 * Registers block.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function register_block() {
		register_block_type( __DIR__ . '/block.json' );
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
	function load_ids( $field ) {
		$choices = [ '' => __( 'None', 'mai-gam' ) ];
		$units   = maigam_get_config( 'ad_units' );

		foreach ( $units as $slug => $unit ) {
			$choices[ $slug ] = $unit['post_title'];
		}

		$field['choices'] = $choices;

		return $field;
	}
}

/**
 * Procedural function to render the block.
 * Called via block.json.
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
function maigam_do_ad_unit_block( $block, $content = '', $is_preview = false, $post_id = 0 ) {
	Mai_GAM_Ad_Unit_Block::do_block( $block, $content, $is_preview, $post_id );
}
