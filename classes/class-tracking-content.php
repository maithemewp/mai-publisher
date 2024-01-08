<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The content tracking class.
 *
 * @link https://developer.matomo.org/guides/content-tracking
 */
class Mai_Publisher_Tracking_Content {
	/**
	 * Construct the class.
	 *
	 * @return void
	 */
	function __construct() {
		$this->hooks();
	}

	/**
	 * Runs frontend hooks.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	function hooks() {
		add_filter( 'wp_nav_menu',            [ $this, 'add_menu_attributes' ], 10, 2 );
		add_filter( 'maicca_content',         [ $this, 'add_cca_attributes' ], 12, 2 );
		add_filter( 'maiam_ad',               [ $this, 'add_ad_attributes' ], 12, 2 );
		add_filter( 'render_block',           [ $this, 'render_navigation_block' ], 10, 2 );
		add_filter( 'render_block',           [ $this, 'render_post_preview_block' ], 10, 2 );
		add_filter( 'render_block',           [ $this, 'render_elasticpress_facet_blocks' ], 10, 2 );
		add_filter( 'ep_facet_search_widget', [ $this, 'render_elasticpress_taxonomy_facet' ], 10, 5 );
	}

	/**
	 * Add attributes to menu.
	 *
	 * @since 0.3.0
	 *
	 * @param string   $nav_menu The HTML content for the navigation menu.
	 * @param stdClass $args     An object containing wp_nav_menu() arguments.
	 *
	 * @return string
	 */
	function add_menu_attributes( $nav_menu, $args ) {
		// Bail if not tracking.
		if ( ! $this->should_track() ) {
			return $nav_menu;
		}

		$slug = $args->menu instanceof WP_Term ? $args->menu->slug : $args->menu;

		if ( ! $slug ) {
			return $nav_menu;
		}

		return maipub_add_attributes( $nav_menu, 'mai-menu-' . $this->get_menu_slug( $slug ) );
	}

	/**
	 * Maybe add attributes to Mai CCA.
	 *
	 * @since 0.3.0
	 *
	 * @param string $content The CCA content.
	 * @param array  $args    The CCA args.
	 *
	 * @return string
	 */
	function add_cca_attributes( $content, $args ) {
		// Bail if not tracking.
		if ( ! $this->should_track() ) {
			return $content;
		}

		// Bail if no name.
		if ( ! isset( $args['id'] ) || empty( $args['id'] ) ) {
			return $content;
		}

		return maipub_add_attributes( $content, get_the_title( $args['id'] ) );
	}

	/**
	 * Maybe add attributes to Mai Ad.
	 *
	 * @since 0.3.0
	 *
	 * @param string $content The CCA content.
	 * @param string $args    The CCA args.
	 *
	 * @return string
	 */
	function add_ad_attributes( $content, $args ) {
		// Bail if not tracking.
		if ( ! $this->should_track() ) {
			return $content;
		}

		// Bail if no name.
		if ( ! isset( $args['name'] ) || empty( $args['name'] ) ) {
			return $content;
		}

		return maipub_add_attributes( $content, trim( $args['name'] ) );
	}

	/**
	 * Add attributes to Navigation menu block.
	 *
	 * @since 0.3.0
	 *
	 * @param string $block_content The existing block content.
	 * @param array  $block         The button block object.
	 *
	 * @return string
	 */
	function render_navigation_block( $block_content, $block ) {
		// Bail if not tracking.
		if ( ! $this->should_track() ) {
			return $block_content;
		}

		// Bail if no content.
		if ( ! $block_content ) {
			return $block_content;
		}

		// Bail if not the block(s) we want.
		if ( 'core/navigation' !== $block['blockName'] ) {
			return $block_content;
		}

		// Bail if no ref.
		if (  ! isset( $block['attrs']['ref'] ) || ! $block['attrs']['ref'] ) {
			return $block_content;
		}

		// Get nav menu slug.
		$menu = get_post( $block['attrs']['ref'] );
		$slug = $menu && $menu instanceof WP_Post ? $menu->post_name : '';

		// Bail if no slug.
		if ( ! $slug ) {
			return $block_content;
		}

		return maipub_add_attributes( $block_content, 'mai-menu-' . $this->get_menu_slug( $slug ) );
	}

	/**
	 * Maybe add attributes to Mai Post Preview block.
	 *
	 * @since 0.3.0
	 *
	 * @param string $block_content The existing block content.
	 * @param array  $block         The button block object.
	 *
	 * @return string
	 */
	function render_post_preview_block( $block_content, $block ) {
		// Bail if not tracking.
		if ( ! $this->should_track() ) {
			return $block_content;
		}

		// Bail if no content.
		if ( ! $block_content ) {
			return $block_content;
		}

		// Bail if not the block(s) we want.
		if ( 'acf/mai-post-preview' !== $block['blockName'] ) {
			return $block_content;
		}

		// Get url from attributes.
		$url = isset( $block['attrs']['data']['url'] ) && ! empty( $block['attrs']['data']['url'] ) ? $block['attrs']['data']['url'] : '';

		// Bail if no url.
		if ( ! $url ) {
			return $block_content;
		}

		// Get name from url.
		$url  = wp_parse_url( $url );
		$url  = isset( $url['host'] ) ? $url['host'] : '' . $url['path'];
		$name = 'Mai Post Preview | ' . $url;

		return maipub_add_attributes( $block_content, $name );
	}

	/**
	 * Add attributes to Elasticpress filter blocks.
	 *
	 * @since TBD
	 *
	 * @param string $block_content The existing block content.
	 * @param array  $block         The button block object.
	 *
	 * @return string
	 */
	function render_elasticpress_facet_blocks( $block_content, $block ) {
		// Bail if Elasticpress is not active.
		if ( ! defined( 'EP_URL' ) ) {
			return $block_content;
		}

		// Bail if not tracking.
		if ( ! $this->should_track() ) {
			return $block_content;
		}

		// Bail if no content.
		if ( ! $block_content ) {
			return $block_content;
		}

		// Facet blocks.
		$facets = [
			'elasticpress/facet-date',
			'elasticpress/facet-meta',
			'elasticpress/facet-meta-range',
			'elasticpress/facet-post-type',
			'elasticpress/related-posts',
			// 'elasticpress/facet', // This has a widget as well, so we're using the filter below instead.
		];

		// Flip.
		$facets = array_flip( $facets );

		// Bail if not the block(s) we want.
		if ( ! isset( $facets[ $block['blockName'] ] ) ) {
			return $block_content;
		}

		// Get slug from block name.
		$slug = str_replace( 'elasticpress/facet-', '', $block['blockName'] );

		// Bail if no slug.
		if ( ! $slug ) {
			return $block_content;
		}

		return maipub_add_attributes( $block_content, "mai-elasticpress-{$slug}-filter" );
	}

	/**
	 * Add attributes to Elasticpress taxonomy filter.
	 *
	 * @since TBD
	 *
	 * @param string $facet_html       Facet HTML
	 * @param array  $selected_filters Selected filters
	 * @param array  $terms_by_slug    Terms by slug
	 * @param array  $outputted_terms  Outputted $terms
	 * @param string $title            Widget title
	 *
	 * @return string
	 */
	function render_elasticpress_taxonomy_facet( $facet_html, $selected_filters, $terms_by_slug, $outputted_terms, $title ) {
		$first    = reset( $terms_by_slug );
		$taxonomy = isset( $first->taxonomy ) ? $first->taxonomy : 'taxonomy';

		return maipub_add_attributes( $facet_html, "mai-elasticpress-{$taxonomy}-filter" );
	}

	/**
	 * Get incremented menu slug.
	 *
	 * @since 0.3.0
	 *
	 * @param string $slug The menu slug.
	 *
	 * @return string
	 */
	function get_menu_slug( $slug ) {
		$slugs = $this->get_menus( $slug );
		$slug  = $slugs[ $slug ] > 2 ? $slug . '-' . ($slugs[ $slug ] - 1) : $slug;

		return $slug;
	}

	/**
	 * Get current page menus to increment.
	 *
	 * @since 0.3.0
	 *
	 * @param string $slug The menu slug.
	 *
	 * @return array
	 */
	function get_menus( $slug = '' ) {
		static $menus = [];

		if ( $slug ) {
			if ( isset( $menus[ $slug ] ) ) {
				$menus[ $slug ]++;
			} else {
				$menus[ $slug ] = 1;
			}
		}

		return $menus;
	}

	/**
	 * Checks if we should track.
	 *
	 * @since 0.3.0
	 *
	 * @return bool
	 */
	function should_track() {
		return ! is_admin() && maipub_should_track();
	}
}
