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
	 * @param bool   $is_preview True during AJAX preview.
	 * @param int    $post_id    The post ID this block is saved to.
	 *
	 * @return void
	 */
	function render_block( $block, $content = '', $is_preview = false, $post_id = 0 ) {
		$id = get_field( 'id' );

		if ( ! $id ) {
			return;
		}

		$post = get_post( $id );

		if ( ! $post ) {
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
		if ( is_admin() ) {
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
				'orderby'                => 'menu_order',
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
