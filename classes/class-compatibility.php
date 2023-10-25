<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Plugin_Compatibility {
	/**
	 * Construct the class.
	 */
	function __construct() {
		$this->hooks();
	}

	/**
	 * Add hooks.
	 *
	 * @since 0.9.0
	 *
	 * @return void
	 */
	function hooks() {
		add_filter( 'mai_table_of_contents_has_custom', [ $this, 'has_custom' ], 10, 2 );
	}

	/**
	 * Check if the post has a custom TOC.
	 *
	 * @since 0.9.0
	 *
	 * @return bool
	 */
	function has_custom( $bool, $post_id ) {
		// Bail if we already have a custom TOC.
		if ( $bool ) {
			return $bool;
		}

		// Get ads.
		$ads = maipub_get_ads();

		// Check for custom TOC in ad content.
		if ( $ads ) {
			foreach( $ads as $ad ) {
				if ( ! $ad['content'] ) {
					continue;
				}

				if ( has_block( 'acf/mai-table-of-contents', $ad['content'] ) ) {
					$bool = true;
					break;
				}
			}
		}

		return $bool;
	}
}