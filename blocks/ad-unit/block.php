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
	 * @param bool   $is_preview True during backend preview render.
	 * @param int    $post_id    The post ID this block is saved to.
	 * @param array  $context    The context provided to the block by the post or its parent block.
	 *
	 * @return void
	 */
	function render_block( $block, $content, $is_preview, $post_id, $context ) {
		maipub_do_ad_unit(
			[
				'preview'    => $this->is_preview( $is_preview ),
				'id'         => get_field( 'id' ),
				'type'       => get_field( 'type' ),
				'position'   => get_field( 'position' ),
				'split_test' => get_field( 'split_test' ),
				'targets'    => get_field( 'targets' ),
				'label'      => get_field( 'label' ),
				'label_hide' => get_field( 'label_hide' ),
			]
		);
	}

	/**
	 * Checks if in preview mode.
	 * Sometimes `$is_preview` was not working correctly when
	 * an Mai GAM Ad Unit block was inside a Mai Ad block,
	 * so this statically caches the result so re-rendering Mai Ad block
	 * via ajax will still show the correct value if in the editor.
	 *
	 * @since 1.6.9
	 *
	 * @link https://github.com/maithemewp/mai-publisher/issues/22
	 *
	 * @param bool $is_preview The current preview state.
	 *
	 * @return bool
	 */
	function is_preview( $is_preview ) {
		static $cache = null;

		if ( ! is_null( $cache ) ) {
			return $cache;
		}

		global $pagenow;

		$cache = $is_preview || 'admin-ajax.php' === $pagenow;

		return $cache;
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
							'nt' => __( 'Native Top', 'mai-publisher' ),
							'nl' => __( 'Native Left', 'mai-publisher' ),
							'nr' => __( 'Native Right', 'mai-publisher' ),
							'nv' => __( 'Native Video', 'mai-publisher' ),
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
		$units   = (array) maipub_get_config( 'ad_units' );

		foreach ( $units as $slug => $unit ) {
			$choices[ $slug ] = $slug;
		}

		$field['choices'] = $choices;

		return $field;
	}
}
