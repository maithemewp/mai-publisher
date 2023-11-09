<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Gets the content age.
 * Index 0 is the min months, index 1 is the readable string label.
 *
 * @since 0.9.0
 *
 * @return array
 */
function maipub_get_content_age() {
	static $age = null;

	if ( ! is_null( $age ) ) {
		return $age;
	}

	$age = [];

	if ( ! is_singular() ) {
		return $age;
	}

	$date = get_the_date( 'F j, Y' );

	if ( ! $date ) {
		return $age;
	}

	$date  = new DateTime( $date );
	$today = new DateTime( 'now' );
	$days  = $today->diff( $date )->format( '%a' );

	if ( ! $days ) {
		return $age;
	}

	// Ranges. Key is min months, value is min/max days.
	$ranges = [
		'0'  => [ 0, 29 ],      // Under 1 month.
		'1'  => [ 30, 89 ],     // 1-3 months.
		'3'  => [ 90, 179 ],    // 3-6 months.
		'6'  => [ 180, 364 ],   // 6-12 months.
		'12' => [ 367, 729 ],   // 1-2 years.
	];

	foreach ( $ranges as $key => $values ) {
		if ( ! filter_var( $days, FILTER_VALIDATE_INT,
			[
				'options' => [
					'min_range' => $values[0],
					'max_range' => $values[1],
				],
			],
		)) {
			continue;
		}

		switch ( $key ) {
			case '0':
				$age = [ $key, __( 'Under 1 month', 'mai-ads-manager' ) ];
			break;
			case '1':
				$age = [ $key, __( '1-3 months', 'mai-ads-manager' ) ];
			break;
			case '3':
				$age = [ $key, __( '3-6 months', 'mai-ads-manager' ) ];
			break;
			case '6':
				$age = [ $key, __( '6-12 months', 'mai-ads-manager' ) ];
			break;
			case '12':
				$age = [ $key, __( '1-2 years', 'mai-ads-manager' ) ];
			break;
		}
	}

	if ( ! $age && $days > 729 ) {
		$age = [ '24', __( 'Over 2 years', 'mai-ads-manager' ) ];
	}

	return $age;
}

/**
 * Get content creator.
 *
 * @since TBD
 *
 * @return int|false
 */
function maipub_get_content_creator() {
	if ( ! is_singular() ) {
		return false;
	}

	$author_id = get_post_field( 'post_author', get_the_ID() );

	return absint( $author_id );
}

/**
 * Get content group/category slug.
 *
 * @since TBD
 *
 * @return string|false
 */
function maipub_get_content_group() {
	if ( ! is_singular() ) {
		return false;
	}

	$term = maipub_get_primary_term( 'category', get_the_ID() );

	return $term ? $term->slug : false;
}

/**
 * Get content type.
 *
 * @since TBD
 *
 * @return string|false
 */
function maipub_get_content_type() {
	$type = 'ot';

	// Blog page.
	if ( is_home() ) {
		$type = 'bp';
	}
	// Single page.
	elseif ( is_singular( 'page' ) ) {
		$type = 'pa';

		// Home page.
		if ( is_front_page() ) {
			$type = 'hp';
		}
	}
	// Single post.
	elseif ( is_singular( 'post' ) ) {
		$type    = 'po';
		$primary = maipub_get_primary_term( 'category', get_the_ID() );

		// Check for category specific content type.
		if ( $primary ) {
			// Podcast.
			if ( str_contains( $primary->slug, 'podcast' ) ) {
				$type = 'pod';
			}
			// Recipe.
			elseif ( str_contains( $primary->slug, 'recipe' ) ) {
				$type = 're';
			}

			// If it's not specific, check ancestors.
			if ( 'po' === $type ) {
				$ancestors = get_ancestors( $primary->term_id, 'category', 'taxonomy' );

				if ( $ancestors ) {
					$contains  = [ $primary->slug ];

					foreach ( $ancestors as $ancestor ) {
						$term = get_term( $ancestor );

						if ( $term && ! is_wp_error( $term ) ) {
							$contains[] = $term->slug;
						}
					}

					if ( $contains ) {
						foreach ( $contains as $slug ) {
							if ( str_contains( $slug, 'podcast' ) ) {
								$type = 'pod';
								break;
							}

							if ( str_contains( $slug, 'recipe' ) ) {
								$type = 're';
								break;
							}
						}
					}
				}
			}
		}
	}
	// CPT.
	elseif ( is_singular() ) {
		$type      = 'cpt';
		$post_type = get_post_type();

		// Podcast.
		if ( str_contains( $post_type, 'podcast' ) ) {
			$type = 'pod';
		}
		// Recipe.
		elseif ( str_contains( $post_type, 'recipe' ) ) {
			$type = 're';
		}
	}
	// Category.
	elseif ( is_category() ) {
		$type = 'ca';
	}
	// Tag.
	elseif ( is_tag() ) {
		$type = 'ta';
	}
	// Custom taxonomy.
	elseif ( is_tax() ) {
		$type = 'te';
	}
	// Author.
	elseif ( is_author() ) {
		$type = 'au';
	}
	// Date.
	elseif ( is_date() ) {
		$type = 'da';
	}
	// Search.
	elseif ( is_search() ) {
		$type = 'se';
	}
	// 404.
	elseif ( is_404() ) {
		$type = 'fo';
	}

	return $type;
}

/**
 * TODO: Use this?
 * Gets localized args for OpenRTB.
 *
 * OpenRTB 2.6 spec / Content Taxonomy.
 * @link https://iabtechlab.com/wp-content/uploads/2022/04/OpenRTB-2-6_FINAL.pdf
 *
 * @since TBD
 *
 * @return array
 */
function maipub_get_ortb2_vars() {
	/**
	 * 3.2.13 Object: Site
	 */
	$site = [
		'name'          => get_bloginfo( 'name' ),
		'domain'        => (string) maipub_get_url_host( home_url() ),
		'page'          => is_singular() ? get_permalink() : home_url( add_query_arg( [] ) ),
		// 'kwarray'       => [ 'sports', 'news', 'rumors', 'gossip' ],
		'mobile'        => 1,
		'privacypolicy' => 1,
		// 'content'       => [],
	];

	$cattax      = 7; // IAB Tech Lab Content Taxonomy 3.0.
	$cat         = maipub_get_option( 'category' ); // Sitewide category.
	$section_cat = ''; // Category.
	$page_cat    = ''; // Child category.
	$term_id     = 0;

	if ( is_singular( 'post' ) ) {
		$post_id = get_the_ID();
		$primary = maipub_get_primary_term( 'category', $post_id );
		$term_id = $primary ? $primary->term_id : 0;

		/**
		 * 3.2.16 Object: Content
		 */
		$site['content'] = [
			'id'       => $post_id,
			'title'    => get_the_title(),
			'url'      => get_permalink(),
			'context'  => 5,                                        // Text (i.e., primarily textual document such as a web page, eBook, or news article.
			// 'kwarray'  => [ 'philadelphia 76ers', 'doc rivers' ],   // Array of keywords about the content.
			// 'language' => '',                                       // Content language using ISO-639-1-alpha-2. Only one of language or langb should be present.
			// 'langb'    => '',                                       // Content language using IETF BCP 47. Only one of language or langb should be present.
			/**
			 * 3.2.21 Object: Data
			 */
			// 'data' => [],
		];

	} elseif ( is_category() ) {
		$object    = get_queried_object();
		$term_id   = $object && $object instanceof WP_Term ? $object->term_id : 0;
	}

	if ( $term_id ) {
		$hierarchy = $this->get_term_hierarchy( $term_id );

		if ( $hierarchy ) {
			$page_cat    = array_pop( $hierarchy );
			$section_cat = array_pop( $hierarchy );
			$section_cat = $section_cat ?: $page_cat;

			// Check for IATB category.
			$page_cat    = $page_cat ? get_term_meta( $term_id, 'maipub_category', true ) : 0;
			$section_cat = $section_cat ? get_term_meta( $term_id, 'maipub_category', true ) : 0;
		}
	}

	if ( $cat || $section_cat || $page_cat ) {
		$site['cattax']            = $cattax;
		$site['content']['cattax'] = $cattax;

		if ( $cat ) {
			$site['cat']            = [ $cat ];
			$site['content']['cat'] = [ $cat ];
		}

		if ( $section_cat ) {
			$site['sectioncat']            = [ $section_cat ];
			$site['content']['sectioncat'] = [ $section_cat ];
		}

		if ( $page_cat ) {
			$site['pagecat']            = [ $page_cat ];
			$site['content']['pagecat'] = [ $page_cat ];
		}
	}

	return $site;
}

/**
 * Get current page data.
 *
 * @since 0.3.0
 *
 * @param string $key The key to return.
 *
 * @return array|string
 */
function maipub_get_current_page( $key = '' ) {
	static $data = null;

	if ( ! is_null( $data ) ) {
		return $key ? $data[ $key ] : $data;
	}

	$data = [
		'type'  => '',   // Object type: post, term, user, etc.
		'name'  => '',   // Object readable label: Post, Page, Category, Tag, Product, Product Category, etc.
		'id'    => '',
		'url'   => '',
		'iabct' => '',   // IAB content taxonomy ID.
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
		$date['type'] = 'date';
		$data['name'] = 'Date';
	}
	// Author archives.
	elseif ( is_author() ) {
		$data['type'] = 'user';
		$data['name'] = 'Author';
	}
	// Search results.
	elseif ( is_search() ) {
		$data['type'] = 'search';
		$data['name'] = 'Search';
	}

	// IAB category ID.
	$iabct = maipub_get_iab_category();

	if ( $iabct ) {
		$data['iabct'] = $iabct;
	}

	return $key ? $data[ $key ] : $data;
}

/**
 * Gets current page id.
 *
 * @since TBD
 *
 * @return int
 */
function maipub_get_current_page_id() {
	static $post_id = null;

	if ( ! is_null( $post_id ) ) {
		return $post_id;
	}

	$post_id = 0;

	// Get post ID.
	if ( maipub_is_singular() ) {
		$post_id = get_the_ID();
	} elseif ( is_home() && ! is_front_page() ) {
		$post_id = get_option( 'page_for_posts' );
	} elseif ( maipub_is_shop_archive() ) {
		$post_id = get_option( 'woocommerce_shop_page_id' );
	}

	return $post_id;
}

/**
 * Gets the IAB category taxonomy ID.
 *
 * @since 0.9.0
 *
 * @return string|false
 */
function maipub_get_iab_category() {
	static $iab = null;

	if ( ! is_null( $iab ) ) {
		return $iab;
	}

	$primary = false;

	if ( is_singular( 'post' ) ) {
		$primary = maipub_get_primary_term( 'category', get_the_ID() );

	} elseif ( is_category() ) {
		$object  = get_queried_object();
		$primary = $object && $object instanceof WP_Term ? $object : 0;
	}

	if ( $primary ) {
		$iab = get_term_meta( $primary->term_id, 'maipub_category', true );
	}

	return $iab;
}

/**
 * Gets all categories from categories.json.
 * This is for the category picker.
 *
 * @since 0.1.0
 *
 * @return array
 */
function maipub_get_all_iab_categories() {
	static $cache = null;

	if ( ! is_null( $cache ) ) {
		return $cache;
	}

	$cache = json_decode( file_get_contents( MAI_PUBLISHER_DIR . '/categories.json' ), true );

	return $cache;
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
 * @author Mike Hemberger @JiveDig.
 *
 * @param string $taxonomy The taxonomy to get the primary term from.
 * @param int    $post_id  The post ID to check.
 *
 * @return WP_Term|false The term object or false if no terms.
 */
function maipub_get_primary_term( $taxonomy = 'category', $post_id = false ) {
	// Bail if no taxonomy.
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
		$wpseo_primary_term = $wpseo_primary_term->get_primary_term();

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
