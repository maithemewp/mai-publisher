<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_GAM_Admin {

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
		add_filter( 'manage_mai_ad_posts_columns',        [ $this, 'add_slug_column' ] );
		add_action( 'manage_mai_ad_posts_custom_column' , [ $this, 'display_slug_column' ], 10, 2 );
	}

	/**
	 * Adds the display taxonomy column after the title.
	 *
	 * @since 0.1.0
	 *
	 * @param string[] $columns An associative array of column headings.
	 *
	 * @return array
	 */
	function add_slug_column( $columns ) {
		unset( $columns['date'] );

		$new = [ 'maigam_slug' => __( 'Slug', 'mai-gam' ) ];

		return maigam_array_insert_after( $columns, 'title', $new );
	}

	/**
	 * Adds the display taxonomy column after the title.
	 *
	 * @since 0.1.0
	 *
	 * @param string $column  The name of the column to display.
	 * @param int    $post_id The current post ID.
	 *
	 * @return void
	 */
	function display_slug_column( $column, $post_id ) {
		if ( 'maigam_slug' !== $column ) {
			return;
		}

		echo get_post_field( 'post_name', $post_id );
	}
}