<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Analytics_Tracker_Block {
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
		add_action( 'acf/init',   [ $this, 'register_block' ] );
		add_action( 'acf/init',   [ $this, 'register_field_group' ] );
		add_action( 'admin_init', [ $this, 'register_script' ] );
	}

	/**
	 * Register block.
	 *
	 * @since 0.7.0
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
	 * @since 0.7.0
	 *
	 * @param array    $attributes The block attributes.
	 * @param string   $content The block content.
	 * @param bool     $is_preview Whether or not the block is being rendered for editing preview.
	 * @param int      $post_id The current post being edited or viewed.
	 * @param WP_Block $wp_block The block instance (since WP 5.5).
	 * @param array    $context The block context array.
	 *
	 * @return void
	 */
	function render_block( $attributes, $content, $is_preview, $post_id, $wp_block, $context ) {
		if ( $is_preview ) {
			$template = [ [ 'core/paragraph', [], [] ] ];
			$inner    = sprintf( '<InnerBlocks template="%s" />', esc_attr( wp_json_encode( $template ) ) );

			echo $inner;
			return;
		}

		echo maipub_add_attributes( $content, sanitize_text_field( (string) get_field( 'name' ) ) );
	}

	/**
	 * Register field group.
	 *
	 * @since 0.7.0
	 *
	 * @return void
	 */
	function register_field_group() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key'    => 'mai_analytics_tracker_field_group',
				'title'  => __( 'Mai Analytics Tracker', 'mai-publisher' ),
				'fields' => [
					[
						'key'   => 'mai_analytics_tracker_name',
						'label' => __( 'Content Name', 'mai-publisher' ),
						'name'  => 'name',
						'type'  => 'text',
					]
				],
				'location' => [
					[
						[
							'param'    => 'block',
							'operator' => '==',
							'value'    => 'acf/mai-analytics-tracker',
						],
					],
				],
			]
		);
	}

	/**
	 * Enqueue JS for block transforms.
	 *
	 * @since 0.7.0
	 *
	 * @return void
	 */
	function register_script() {
		$min  = maipub_get_min_dir();
		$file = "blocks/analytics-tracker/{$min}block.js";

		wp_enqueue_script( 'mai-publisher-analytics-tracker', maipub_get_file_data( $file, 'url' ), [], maipub_get_file_data( $file, 'version' ), [ 'in_footer' => true ] );
	}
}
