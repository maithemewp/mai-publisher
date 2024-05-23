<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Ad_Block {
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
		add_action( 'acf/init',                        [ $this, 'register_block' ] );
		add_action( 'acf/init',                        [ $this, 'register_field_group' ] );
		add_filter( 'acf/load_field/key=maipub_ad_id', [ $this, 'load_ad_choices' ] );
		// add_filter( 'allowed_block_types_all',         [ $this, 'allowed_post_type' ], 10, 2 );
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
		$id   = get_field( 'id' );
		$post = $id ? get_post( $id ) : false;

		// If not the data we need.
		if ( ! ( $id && $post ) ) {
			// If in editor.
			if ( $is_preview ) {
				// This styling should match Mai Ad Unit block.
				printf( '<div style="text-align:center;background:rgba(0,0,0,0.05);border:2px dashed rgba(0,0,0,0.25);"><span style="font-size:1.1rem;">%s</span></div>',  __( 'No Ad Selected', 'mai-publisher' ) );
			}
			return;
		}

		echo maipub_get_processed_content( $post->post_content );
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
				'key'      => 'maipub_ad_block_field_group',
				'title'    => __( 'Locations Settings', 'mai-publisher' ),
				'fields'   =>[
					[
						'label'   => __( 'Ad', 'mai-publisher' ),
						'key'     => 'maipub_ad_id',
						'name'    => 'id',
						'type'    => 'select',
						'choices' => [],
					],
				],
				'location' => [
					[
						[
							'param'    => 'block',
							'operator' => '==',
							'value'    => 'acf/mai-ad',
						],
					],
				],
			]
		);
	}

	/**
	 * Loads ad choices.
	 *
	 * @since 0.1.0
	 *
	 * @param array $field The field data.
	 *
	 * @return array
	 */
	function load_ad_choices( $field ) {
		if ( ! is_admin() ) {
			return $field;
		}

		$field['choices'] = [ '' => __( 'None', 'mai-publisher' ) ];
		$query            = new WP_Query(
			[
				'post_type'              => 'mai_ad',
				'posts_per_page'         => 500,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'suppress_filters'       => false, // https://github.com/10up/Engineering-Best-Practices/issues/116
				'orderby'                => 'title',
				'order'                  => 'ASC',
			]
		);

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) : $query->the_post();
				$field['choices'][ get_the_ID() ] = get_the_title();
			endwhile;
		}
		wp_reset_postdata();

		return $field;
	}

	/**
	 * Limit the blocks allowed in Gutenberg.
	 *
	 * @param mixed $allowed_blocks Array of allowable blocks for Gutenberg Editor.
	 * @param mixed $post Gets current post type.
	 *
	 * @return mixed $allowed_blocks Returns the allowed blocks.
	 * */
	function allowed_post_type( $allowed_blocks, $post ) {
		// if ( 'mai_ad' === $post->post_type ) {
		// 	$allowed_blocks[] = 'acf/mai-ad-unit';
		// }

		return $allowed_blocks;
	}
}
