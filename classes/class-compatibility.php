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
		add_filter( 'ep_prepare_meta_whitelist_key',    [ $this, 'elasticpress_meta_keys' ], 10, 3 );
		add_filter( 'mai_table_of_contents_has_custom', [ $this, 'has_custom' ], 10, 2 );
	}

	/**
	 * Allow meta keys to be indexed by ElasticPress.
	 *
	 * @since 0.12.0
	 *
	 * @param bool    $allow Whether to allow the meta key.
	 * @param string  $key   The meta key name.
	 * @param WP_Post $post  The post object.
	 *
	 * @return bool
	 */
	function elasticpress_meta_keys( $allow, $key, $post ) {
		if ( in_array( $key, [ 'mai_trending', 'mai_views' ] ) ) {
			$allow = true;
		}

		return $allow;
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
		$ads = maipub_get_page_ads();

		if ( ! $ads ) {
			return $bool;
		}

		// Check for custom TOC in ad content.
		foreach( $ads as $ad ) {
			if ( ! $ad['content'] ) {
				continue;
			}

			if ( has_block( 'acf/mai-table-of-contents', $ad['content'] ) ) {
				$bool = true;
				break;
			}
		}

		return $bool;
	}
}
