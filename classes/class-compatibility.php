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
		add_filter( 'wprm_recipe_shortcode_output',     [ $this, 'do_recipe_hook' ], 10, 4 );
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

	/**
	 * Adds a hook to the recipe instructions.
	 *
	 * @since TBD
	 *
	 * @param string      $output          The recipe markup.
	 * @param WPRM_Recipe $recipe          The recipe object.
	 * @param string      $type            The recipe type.
	 * @param string      $recipe_template The recipe template.
	 *
	 * @return string
	 */
	function do_recipe_hook( $output, $recipe, $type, $recipe_template ) {
		if ( ! $output ) {
			return $output;
		}

		// Get instructions node.
		$dom          = maipub_get_dom_document( $output );
		$xpath        = new DOMXPath( $dom );
		$instructions = $xpath->query( '//ul[contains(concat(" ", normalize-space(@class), " "), " wprm-recipe-instructions ")]' );

		// Bail if no instructions.
		if ( ! $instructions->length ) {
			return $output;
		}

		$before = '';
		ob_start();
		do_action( 'maipub_before_recipe_instructions' );
		$before .= ob_get_clean();

		// Bail if nothing hooked here.
		if ( ! $before ) {
			return $output;
		}

		/**
		 * Build the temporary dom.
		 * Special characters were causing issues with `appendXML()`.
		 *
		 * This needs to happen inside the loop, otherwise the slot IDs are not correctly incremented.
		 *
		 * @link https://stackoverflow.com/questions/4645738/domdocument-appendxml-with-special-characters
		 * @link https://www.py4u.net/discuss/974358
		 */
		$tmp  = maipub_get_dom_document( $before );
		$node = $dom->importNode( $tmp->documentElement, true );

		// Bail if no node.
		if ( ! $node ) {
			return $output;
		}

		foreach ( $instructions as $element ) {
			// // Add ad before this element.
			// $element->parentNode->insertBefore( $node, $element );
			/**
			 * Add ad after this element. There is no insertAfter() in PHP ¯\_(ツ)_/¯.
			 *
			 * @link https://gist.github.com/deathlyfrantic/cd8d7ef8ba91544cdf06
			 */
			$element->parentNode->insertBefore( $node, $element->nextSibling );
			// Bail, only run once.
			break;
		}

		$output = $dom->saveHTML();

		return $output;
	}
}