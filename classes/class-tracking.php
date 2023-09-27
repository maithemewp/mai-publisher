<?php
/**
 * Mai Matomo Tracker Module.
 *  - This code extends the Mai Theme & related plugin functionallity to use Matomo Anlytics
 *  - required Matomo Analytics to be implemented
 *
 * @package   BizBudding
 * @link      https://bizbudding.com/
 * @version   0.2.0
 * @author    BizBudding
 * @copyright Copyright © 2022 BizBudding
 * @license   GPL-2.0-or-later
 *

* Matomo - free/libre analytics platform
*
* For more information, see README.md
*
* @license released under BSD License http://www.opensource.org/licenses/bsd-license.php
* @link https://matomo.org/docs/tracking-api/
*
* @category Matomo
* @package MatomoTracker
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

class Mai_Publisher_Analytics {
	private $dimensions;
	private $primary = false;

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
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function hooks() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ], 99 );
	}

	/**
	 * Enqueues script if we're tracking the current page.
	 * This should not be necessary yet, if we have the main Matomo header script.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function enqueue() {
		if ( ! maipub_get_option( 'matomo' ) ) {
			return;
		}

		$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$file    = "assets/js/mai-publisher-analytics{$suffix}.js";
		$vars    = [
			'dimensions' => $this->get_custom_dimensions(),
		];

		wp_enqueue_script( 'mai-publisher-analytics', maipub_get_file_data( $file, 'url' ), [], maipub_get_file_data( $file, 'version' ), [ 'strategy' => 'async', 'in_footer' => true ] );
		wp_localize_script( 'mai-publisher-analytics', 'maiPubAnalyticsVars', $vars );
	}

	/**
	 * Gets custom dimensions.
	 *
	 * A lot of this is shared with Mai Analytics.
	 * Any changes here should be referenced there.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function get_custom_dimensions() {
		$this->dimensions = [];

		// TODO.
		$this->set_dimension_1();
		// TODO.
		$this->set_dimension_2();
		// TODO.
		$this->set_dimension_3();
		// TODO.
		$this->set_dimension_4();
		// TODO.
		$this->set_dimension_5();
		// TODO.
		$this->set_dimension_6();
		// TODO.
		// Post category.
		$this->set_dimension_7();
		// TODO.
		// Content length.
		$this->set_dimension_8();
		// TODO.
		// Content type.
		$this->set_dimension_9();

		return $this->dimensions;
	}

	/**
	 * Websites.
	 * Sets site domain.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function set_dimension_1() {
		$host = parse_url( get_site_url(), PHP_URL_HOST );
		$host = str_replace( 'www.', '', $host );

		$this->dimensions[1] = esc_html( $host );
	}

	/**
	 * Sets post IAB category.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function set_dimension_2() {
		if ( is_singular( 'post' ) ) {
			$this->primary = maipub_get_primary_term( 'category', get_the_ID() );

		} elseif ( is_category() ) {
			$object        = get_queried_object();
			$this->primary = $object && $object instanceof WP_Term ? $object : 0;
		}

		if ( ! $this->primary ) {
			return;
		}

		$iab = get_term_meta( $this->primary->term_id, 'maipub_category', true );

		if ( ! $iab ) {
			return;
		}

		$categories = maipub_get_all_categories();
		$iab        = isset( $categories[ $iab ] ) ? $categories[ $iab ] : false;

		if ( ! $iab ) {
			return;
		}

		$this->dimensions[2] = esc_html( ltrim( $iab, '– ' ) );
	}

	/**
	 * Sets content category.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function set_dimension_3() {
		$term = false;

		if ( is_singular( 'post' ) ) {
			$term = $this->primary;

		} elseif ( is_category() || is_tag() || is_tax() ) {
			$object = get_queried_object();
			$term   = $object && $object instanceof WP_Term ? $object : 0;
		}

		if ( ! $term ) {
			return;
		}

		$this->dimensions[3] = $term->name; // Term name.
	}

	/**
	 * Gets content type.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function set_dimension_4() {
		// Uses readable name as the type. 'Post' instead of 'post'.
		$type = $this->get_current_page( 'name' );

		if ( ! $type ) {
			return;
		}

		$this->dimensions[4] = $type;
	}

	// TODO.
	function set_dimension_5() {}
	function set_dimension_6() {}
	function set_dimension_7() {}
	function set_dimension_8() {}
	function set_dimension_9() {}

	/**
	 * Get current page data.
	 * Taken from `mai_analytics_get_current_page()` in Mai Analytics.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key
	 *
	 * @return array|string
	 */
	function get_current_page( $key = '' ) {
		static $data = null;

		if ( ! is_null( $data ) ) {
			return $key ? $data[ $key ] : $data;
		}

		$data = [
			'type' => '',
			'name' => '',
			'id'   => '',
			'url'  => '',
		];

		// Single post.
		if ( is_singular() ) {
			$object = get_post_type_object( get_post_type() );

			if ( $object ) {
				$data['type'] = 'post';
				$data['name'] = $object->labels->singular_name; // Singular name.
				$data['id']   = get_the_ID();
				$data['url']  = get_permalink();
			}
		}
		// Post type archive.
		elseif ( is_home() ) {
			$object = get_post_type_object( 'post' );

			if ( $object ) {
				$post_id      = absint( get_option( 'page_for_posts' ) );
				$data['name'] = $object->label; // Plural name.
				$data['id']   = $post_id;
				$data['url']  = $post_id ? get_permalink( $post_id ) : get_home_url();
			}
		}
		// Custom post type archive.
		elseif ( is_post_type_archive() ) {
			$object = get_post_type_object( get_post_type() );

			if ( $object ) {
				$data['name'] = $object->label; // Plural name.
				$data['url']  = get_post_type_archive_link( $object->name );
			}
		}
		// Taxonomy archive.
		elseif ( is_category() || is_tag() || is_tax() ) {
			$object = get_queried_object();

			if ( $object  ) {
				$taxonomy = get_taxonomy( $object->taxonomy );

				if ( $taxonomy ) {
					$data['type'] = 'term';
					$data['name'] = $taxonomy->labels->singular_name; // Singular name.
					$data['id']   = $object->term_id;
					$data['url']  = get_term_link( $object );
				}
			}
		}
		// Date archives.
		elseif ( is_date() || is_year() || is_month() || is_day() || is_time() ) {
			$data['name'] = 'Date';
		}
		// Author archives.
		elseif ( is_author() ) {
			$data['name'] = 'Author';
		}
		// Search results.
		elseif ( is_search() ) {
			$data['name'] = 'Search';
		}

		return $key ? $data[ $key ] : $data;
	}

	/**
	 * Gets the primary term of a post, by taxonomy.
	 * If Yoast Primary Term is used, return it,
	 * otherwise fallback to the first term.
	 *
	 * @version 1.3.0
	 *
	 * @since 0.1.0
	 *
	 * @link https://gist.github.com/JiveDig/5d1518f370b1605ae9c753f564b20b7f
	 * @link https://gist.github.com/jawinn/1b44bf4e62e114dc341cd7d7cd8dce4c
	 *
	 * @param string $taxonomy The taxonomy to get the primary term from.
	 * @param int    $post_id  The post ID to check.
	 *
	 * @return WP_Term|false The term object or false if no terms.
	 */
	function get_primary_term( $taxonomy = 'category', $post_id = false ) {
		if ( ! $taxonomy ) {
			return false;
		}

		// If no post ID, set it.
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		// Bail if no post ID.
		if ( ! $post_id ) {
			return false;
		}

		// Setup caching.
		static $cache = null;

		// Maybe return cached value.
		if ( is_array( $cache ) ) {
			if ( isset( $cache[ $taxonomy ][ $post_id ] ) ) {
				return $cache[ $taxonomy ][ $post_id ];
			}
		} else {
			$cache = [];
		}

		// If checking for WPSEO.
		if ( class_exists( 'WPSEO_Primary_Term' ) ) {

			// Get the primary term.
			$wpseo_primary_term = new WPSEO_Primary_Term( $taxonomy, $post_id );
			$wpseo_primary_term = $wpseo_primary_term->get_primary_term( 'category', get_the_ID());
			// If we have one, return it.
			if ( $wpseo_primary_term ) {
				$cache[ $taxonomy ][ $post_id ] = get_term( $wpseo_primary_term );
				return $cache[ $taxonomy ][ $post_id ];
			}
		}

		// We don't have a primary, so let's get all the terms.
		$terms = get_the_terms( $post_id, $taxonomy );

		// Bail if no terms.
		if ( ! $terms || is_wp_error( $terms ) ) {
			$cache[ $taxonomy ][ $post_id ] = false;
			return $cache[ $taxonomy ][ $post_id ];
		}

		// Get the first, and store in cache.
		$cache[ $taxonomy ][ $post_id ] = reset( $terms );

		// Return the first term.
		return $cache[ $taxonomy ][ $post_id ];
	}
}
