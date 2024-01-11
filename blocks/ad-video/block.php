<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Ad_Video_Block {
	/**
	 * Construct the class.
	 */
	function __construct() {
		$this->hooks();
	}

	/**
	 * Add hooks.
	 *
	 * @since 0.15.0
	 *
	 * @return void
	 */
	function hooks() {
		add_action( 'acf/init', [ $this, 'register_block' ] );
		add_action( 'acf/init', [ $this, 'register_field_group' ] );
	}

	/**
	 * Registers block.
	 *
	 * @since 0.15.0
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
	 * @since 0.15.0
	 *
	 * @param array  $block      The block settings and attributes.
	 * @param string $content    The block inner HTML (empty).
	 * @param bool   $is_preview True during AJAX preview.
	 * @param int    $post_id    The post ID this block is saved to.
	 *
	 * @return void
	 */
	function render_block( $block, $content = '', $is_preview = false, $post_id = 0 ) {
		$id = get_field( 'id' );

		// If previewing in editor and no video selected, show placeholder.
		if ( $is_preview || 'demo' === maipub_get_option( 'ad_mode', false ) ) {
			$text = $id ? __( 'Mai Video Ad Placeholder', 'mai-publisher' ) : __( 'No Video Selected', 'mai-publisher' );
			printf( '<div class="mai-ad-video mai-connatix" data-unit="video" style="display:grid;place-content:center;aspect-ratio:16/9;margin-block:24px;text-align:center;background:rgba(0,0,0,0.05);border:2px dashed rgba(0,0,0,0.25);"><span style="font-size:1.1rem;">%s</span></div>', $text );
			return;
		}

		// Bail if no video.
		if ( ! $id ) {
			return;
		}

		// Set the attributes.
		$attr = [
			'class'     => 'mai-ad-video',
			'data-unit' => 'video',
		];

		// Get the video script.
		switch ( $id ) {
			// Cool Stuff.
			case 'd98b3dc2-bc10-43cf-b1b9-bd2c68c9615b':
				$attr['class'] .= ' mai-connatix';
				$attr['style']  = '--mai-connatix-aspect-ratio:8/3;';
				$html           = '<script id="af853f8b9afa4c828dc709c0715055b2">(new Image()).src = "https://capi.connatix.com/tr/si?token=d98b3dc2-bc10-43cf-b1b9-bd2c68c9615b&cid=db8b4096-c769-48da-a4c5-9fbc9ec753f0"; cnx.cmd.push(function() { cnx({ playerId: "d98b3dc2-bc10-43cf-b1b9-bd2c68c9615b" }).render("af853f8b9afa4c828dc709c0715055b2"); });</script>';
			break;
			// This Day In History.
			case '6f704650-514c-4dc1-8481-8a75bbfb2eea':
				$attr['class'] .= ' mai-connatix';
				$html           = '<script id="b242539108714840b61d0122f61a84b0">(new Image()).src = "https://capi.connatix.com/tr/si?token=6f704650-514c-4dc1-8481-8a75bbfb2eea&cid=db8b4096-c769-48da-a4c5-9fbc9ec753f0"; cnx.cmd.push(function() { cnx({ playerId: "6f704650-514c-4dc1-8481-8a75bbfb2eea" }).render("b242539108714840b61d0122f61a84b0"); });</script>';
			break;
			default:
				$html = '';
		}

		// Bail if no video script.
		if ( ! $html ) {
			return;
		}

		// Output the video.
		printf( '<div%s>%s</div>', maipub_build_attributes( $attr ), $html );
	}

	/**
	 * Registers field group.
	 *
	 * @since 0.15.0
	 *
	 * @return void
	 */
	function register_field_group() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key'      => 'maipub_ad_video_block_field_group',
				'title'    => __( 'Locations Settings', 'mai-publisher' ),
				'fields'   =>[
					[
						'label'   => __( 'Video ID', 'mai-publisher' ),
						'key'     => 'maipub_ad_video_id',
						'name'    => 'id',
						'type'    => 'select',
						'choices' => [
							''                                     => __( 'None', 'mai-publisher' ),
							'd98b3dc2-bc10-43cf-b1b9-bd2c68c9615b' => __( 'Cool Stuff', 'mai-publisher' ),
							'6f704650-514c-4dc1-8481-8a75bbfb2eea' => __( 'This Day In History', 'mai-publisher' ),
						],
					],
				],
				'location' => [
					[
						[
							'param'    => 'block',
							'operator' => '==',
							'value'    => 'acf/mai-ad-video',
						],
					],
				],
			]
		);
	}

	/**
	 * Loads ad unit choices.
	 *
	 * @since 0.15.0
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
