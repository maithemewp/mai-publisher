<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Adds settings page.
 *
 * @link /wp-admin/edit.php?post_type=mai_ad&page=ads
 */
class Mai_Publisher_Settings_Ad_Units_Config {
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
		add_action( 'admin_menu', [ $this, 'add_menu_item' ], 12 );
	}

	/**
	 * Adds menu item for settings page.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function add_menu_item() {
		add_submenu_page(
			'', // No parent page, so no menu item.
			__( 'Ad Units Config', 'mai-publisher' ), // page_title
			'', // No menu title.
			'manage_options', // capability
			'ads', // menu_slug
			[ $this, 'add_content' ], // callback
		);
	}

	/**
	 * Adds setting page content.
	 *
	 * 1. We need to build JSON from CSV so we can easily copy and paste into the categories.json file.
	 *    This is so we can update easier the next time IAB updates the tsv file.
	 *
	 * 2. We need to show id => label with visual hierarchy for the search picker. Direct from categories.json. Simple.
	 *
	 * 3. We need to show a hierarchical ist of all the categories visually to help make a choice.
	 *    Don't need IDs, just build hierarchy from categories.json on the fly, only for that setting page.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function add_content() {
		echo '<div class="wrap">';
			printf( '<h2>%s</h2>', __( 'Ad Units Config', 'mai-publisher' ) );

			echo '<p>';
				echo __( 'This page is for reference only, for easier copy & paste into the plugin\'s ad config files.', 'mai-publisher' );
			echo '</p>';

			$json  = [];
			$query = new WP_Query(
				[
					'post_type'              => 'mai_ad',
					'posts_per_page'         => 100,
					'offset'                 => 0,
					'post_status'            => 'any',
					'orderby'                => 'menu_order',
					'order'                  => 'ASC',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				]
			);

			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) : $query->the_post();
					global $post;

					$meta = [];
					$all  = get_post_meta( $post->ID );

					foreach ( $all as $key => $value ) {
						// Skip if the key does not start with 'maipub';
						if ( ! str_starts_with( $key, 'maipub' ) ) {
							continue;
						}

						// Skip if the value is empty.
						if ( ! isset( $value[0] ) || empty( $value[0] ) ) {
							continue;
						}

						$meta[ $key ] = maybe_unserialize( $value[0] );
					}


					$json[ $post->post_name ] = [
						'menu_order'   => $post->menu_order,
						'post_title'   => $post->post_title,
						'post_content' => $post->post_content,
						'meta_input'   => $meta,
					];

				endwhile;
			}
			wp_reset_postdata();

			echo '<textarea rows="10" cols="1" style="width:100%;min-height:50vh;background:white;" readonly>';
				echo json_encode( $json, JSON_PRETTY_PRINT );
			echo '</textarea>';

		echo '</div>';
	}

	/**
	 * Function to recursively loop through associative array data to build nested unordered lists.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	function get_list( $array, $indent = "&ndash;&nbsp;" ) {
		$html = $array ? '<ul>' : '';

		foreach ( $array as $key => $value ) {
			$html .= sprintf( '<li>%s%s</li>', $indent, $key );

			if ( $value ) {
				$html .= $this->get_list( $value, $indent . "&ndash;&nbsp;" );
			}
		}

		$html .= $array ? '</ul>' : '';

		return $html;
	}
}
