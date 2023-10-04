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
	 * @since TBD
	 *
	 * @return void
	 */
	function hooks() {
		add_filter( 'wp_nav_menu',    [ $this, 'add_menu_attributes' ], 10, 2 );
		add_filter( 'maicca_content', [ $this, 'add_cca_attributes' ], 12, 2 );
		add_filter( 'maiam_ad',       [ $this, 'add_ad_attributes' ], 12, 2 );
		add_filter( 'render_block',   [ $this, 'render_navigation_block' ], 10, 2 );
		add_filter( 'render_block',   [ $this, 'render_post_preview_block' ], 10, 2 );
	}

	/**
	 * Add attributes to menu.
	 *
	 * @since TBD
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

		// if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		// 	return $nav_menu;
		// }

		// $tags = new WP_HTML_Tag_Processor( $nav_menu );

		// while ( $tags->next_tag( [ 'tag_name' => 'ul' ] ) ) {
		// 	$tags->set_attribute( 'data-content-name', 'mai-menu-' . esc_attr( $slug ) );
		// 	$tags->set_attribute( 'data-track-content', '' );
		// 	break;
		// }

		// while ( $tags->next_tag( [ 'tag_name' => 'a' ] ) ) {
		// 	$tags->set_attribute( 'data-content-piece', '' );
		// }

		// return $tags->get_updated_html();

		return $this->add_attributes( $nav_menu, 'mai-menu-' . $this->get_menu_slug( $slug ) );
	}

	/**
	 * Maybe add attributes to Mai CCA.
	 *
	 * @since TBD
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

		return $this->add_attributes( $content, get_the_title( $args['id'] ) );
	}

	/**
	 * Maybe add attributes to Mai Ad.
	 *
	 * @since TBD
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

		return $this->add_attributes( $content, trim( $args['name'] ) );
	}

	/**
	 * Add attributes to Navigation menu block.
	 *
	 * @since TBD
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

		return $this->add_attributes( $block_content, 'mai-menu-' . $this->get_menu_slug( $slug ) );
	}

	/**
	 * Maybe add attributes to Mai Post Preview block.
	 *
	 * @since TBD
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

		return $this->add_attributes( $block_content, $name );
	}

	/**
	 * Adds element attributes.
	 *
	 * If you set the same attribute or the same class on multiple elements within one block,
	 * the first element found will always win. Nested content blocks are currently not supported in Matomo.
	 * This would happen if a Mai Ad block was used inside of a Mai CCA (i think, this is from Mai Analytics),
	 * the CCA would take precedence and the Ad links will have the content piece.
	 *
	 * @since TBD
	 *
	 * @param string $content The content.
	 * @param string $name    The name.
	 * @param bool   $force   Whether to force override existing tracking attributes, if they already exist.
	 *
	 * @return string
	 */
	function add_attributes( $content, $name ) {
		// Bail if no content.
		if ( ! $content ) {
			return $content;
		}

		$dom      = maipub_get_dom_document( $content );
		$children = $dom->childNodes;

		// Bail if no nodes.
		if ( ! $children->length ) {
			return $content;
		}

		// Remove trackers from children.
		$xpath   = new DOMXPath( $dom );
		$tracked = $xpath->query( '//*[@data-track-content] | //*[@data-tcontent-name]' );

		if ( $tracked->length ) {
			foreach ( $tracked as $node ) {
				// Skip if not an element we can add attributes to.
				if ( 'DOMElement' !== get_class( $node ) ) {
					continue;
				}

				$node->removeAttribute( 'data-content-name' );
				$node->removeAttribute( 'data-track-content' );
				$node->normalize();
			}
		}

		if ( 1 === $children->length ) {
			// Get first element and set main attributes.
			$first = $children->item(0);

			// Make sure it's an element we can add attributes to.
			if ( 'DOMElement' === get_class( $first ) ) {
				$first->setAttribute( 'data-content-name', esc_attr( $name ) );
				$first->setAttribute( 'data-track-content', '' );
			}

		} else {
			foreach ( $children as $node ) {
				// Skip if not an element we can add attributes to.
				if ( 'DOMElement' !== get_class( $node ) ) {
					continue;
				}

				// Set main attributes to all top level child elements.
				$node->setAttribute( 'data-content-name', esc_attr( $name ) );
				$node->setAttribute( 'data-track-content', '' );
			}
		}

		// Query elements.
		$xpath   = new DOMXPath( $dom );
		$actions = $xpath->query( '//a | //button | //input[@type="submit"]' );

		if ( $actions->length ) {
			foreach ( $actions as $node ) {
				$piece = 'input' === $node->tagName ? $node->getAttribute( 'value' ) : $node->textContent;
				$piece = trim( esc_attr( $piece ) );

				if ( $piece ) {
					if ( ! $node->hasAttribute( 'data-content-piece' ) ) {
						$node->setAttribute( 'data-content-piece', $piece );
					}
				}

				// Disabled, because target should happen automatically via href in Matomo.
				// $target = 'a' === $node->tagName ? $node->getAttribute( 'href' ) : '';
				// if ( $target ) {
				// 	$node->setAttribute( 'data-content-target', $target );
				// }
			}
		}

		// Save new content.
		$content = $dom->saveHTML();

		return $content;
	}

	/**
	 * Get incremented menu slug.
	 *
	 * @since TBD
	 *
	 * @param string $slug The menu slug.
	 *
	 * @return string
	 */
	function get_menu_slug( $slug ) {
		$slugs = $this->get_menus( $slug );
		$slug  = $slugs[ $slug ] > 2 ? $slug . '-' . $slugs[ $slug ] - 1 : $slug;

		return $slug;
	}

	/**
	 * Get current page menus to increment.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return bool
	 */
	function should_track() {
		return ! is_admin() && maipub_should_track();
	}
}
