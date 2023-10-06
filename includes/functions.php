<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Gets ads to be displayed on the current page.
 *
 * @since 0.1.0
 *
 * @return void
 */
function maipub_get_ads() {
	static $ads = null;

	if ( ! is_null( $ads ) ) {
		return $ads;
	}

	$ads = [];

	// If singular, check for manuall added ad blocks.
	if ( maipub_is_singular() ) {
		$post = get_post();

		if ( has_block( 'acf/mai-ad', $post ) || has_block( 'acf/mai-ad-unit', $post ) ) {
			$ads[] = [
				'id'      => $post->ID,
				'content' => $post->post_content,
			];
		}
	}

	// Check for sidebars.
	$sidebars = apply_filters( 'mai_publisher_sidebars', [] );
	$sidebars = $sidebars ? array_unique( array_map( 'esc_attr', $sidebars ) ) : [];

	if ( $sidebars && class_exists( 'WP_HTML_Tag_Processor' ) ) {
		$ad_ids = [];

		foreach ( $sidebars as $id ) {
			// Render sidebar to variable.
			ob_start();
			dynamic_sidebar( $id );
			$sidebar = ob_get_clean();

			if ( ! $sidebar ) {
				continue;
			}

			// Check for ad-units.
			$tags = new WP_HTML_Tag_Processor( $sidebar );

			while ( $tags->next_tag( [ 'tag_name' => 'div', 'class_name' => 'mai-ad-unit' ] ) ) {
				// Get slug from ID. Converts `mai-ad-infeed` and `mai-ad-infeed-2` to `infeed`.
				$id   = $tags->get_attribute( 'id' );
				$slug = maipub_get_string_between_strings( $id, 'mai-ad-', '-' );

				if ( ! $slug ) {
					continue;
				}

				$ad_ids[] = $slug;
			}
		}

		// Add to ads.
		if ( $ad_ids ) {
			$ads[] = [
				'id'      => 'sidebars',
				'content' => '',
				'ad_ids'  => $ad_ids,
			];
		}
	}

	// Get ad data from location settings.
	$data = maipub_get_ads_data();

	// Bail if no actual values.
	if ( ! array_filter( array_values( $data ) ) ) {
		return $ads;
	}

	// Loop through each type.
	foreach ( $data as $type => $items ) {
		// Loop through each item.
		foreach ( $items as $args ) {
			// Validate.
			$args = maipub_validate_args( $args, $type );

			// Bail if not valid args.
			if ( ! $args ) {
				continue;
			}

			// Add to ads.
			$ads[] = $args;
		}
	}

	return $ads;
}

/**
 * Gets processed content.
 *
 * @since 0.1.0
 *
 * @param string $content
 *
 * @return string
 */
function maipub_get_processed_ad_content( $content ) {
	return do_blocks( $content );
}

/**
 * Get processed content.
 * Take from mai_get_processed_content() in Mai Engine.
 *
 * @since 0.1.0
 *
 * @return string
 */
function maipub_get_processed_content( $content ) {
	if ( function_exists( 'mai_get_processed_content' ) ) {
		return mai_get_processed_content( $content );
	}

	/**
	 * Embed.
	 *
	 * @var WP_Embed $wp_embed Embed object.
	 */
	global $wp_embed;

	$blocks  = has_blocks( $content );
	$content = $wp_embed->autoembed( $content );           // WP runs priority 8.
	$content = $wp_embed->run_shortcode( $content );       // WP runs priority 8.
	$content = $blocks ? do_blocks( $content ) : $content; // WP runs priority 9.
	$content = wptexturize( $content );                    // WP runs priority 10.
	$content = ! $blocks ? wpautop( $content ) : $content; // WP runs priority 10.
	$content = shortcode_unautop( $content );              // WP runs priority 10.
	$content = function_exists( 'wp_filter_content_tags' ) ? wp_filter_content_tags( $content ) : wp_make_content_images_responsive( $content ); // WP runs priority 10. WP 5.5 with fallback.
	$content = do_shortcode( $content );                   // WP runs priority 11.
	$content = convert_smilies( $content );                // WP runs priority 20.

	return $content;
}

/**
 * Adds content area to existing content/HTML.
 *
 * @since 0.1.0
 *
 * @uses DOMDocument
 *
 * @param string $content The existing html.
 * @param array  $args    The ad args.
 * @param bool   $counts  Whether to return the valid content_count instead of content.
 *                        This keeps the logic in one place.
 *
 * @return string.
 */
function maipub_get_content( $content, $args, $counts = false ) {
	$count = [];
	$dom   = maipub_get_dom_document( $content );
	$xpath = new DOMXPath( $dom );
	$all   = $xpath->query( '/*[not(self::script or self::style or self::link)]' );

	if ( ! $all->length ) {
		return $content;
	}

	$last     = $all->item( $all->length - 1 );
	$tags     = 'before' !== $args['content_location'] ? [ 'div', 'p', 'ol', 'ul', 'blockquote', 'figure', 'iframe' ] : [ 'h2', 'h3' ];
	$tags     = apply_filters( 'mai_publisher_content_elements', $tags, $args );
	$tags     = array_filter( $tags );
	$tags     = array_unique( $tags );
	$elements = [];

	foreach ( $all as $node ) {
		if ( ! $node->childNodes->length || ! in_array( $node->nodeName, $tags ) ) {
			continue;
		}

		$elements[] = $node;
	}

	if ( ! $elements ) {
		return $counts ? [] : $content;
	}

	$item       = 0;
	$tmp_counts = array_flip( $args['content_count'] );

	foreach ( $elements as $index => $element ) {
		$item++;

		// Bail if there are no more counts to check.
		if ( ! $tmp_counts ) {
			break;
		}

		// Bail if not an element we need.
		if ( ! isset( $tmp_counts[ $item ] ) ) {
			continue;
		}

		// If modifying content.
		if ( ! $counts ) {
			/**
			 * Build the temporary dom.
			 * Special characters were causing issues with `appendXML()`.
			 *
			 * This needs to happen inside the loop, otherwise the slot IDs are not correctly incremented.
			 *
			 * @link https://stackoverflow.com/questions/4645738/domdocument-appendxml-with-special-characters
			 * @link https://www.py4u.net/discuss/974358
			 */
			$tmp  = maipub_get_dom_document( maipub_get_processed_ad_content( $args['content'] ) );
			$node = $dom->importNode( $tmp->documentElement, true );

			// Skip if no node.
			if ( ! $node ) {
				continue;
			}
		}

		// After elements.
		if ( 'before' !== $args['content_location'] ) {

			// TODO: 2 Mai Ad blocks manually in content aren't generating the right mai-ad-unit slug.

			/**
			 * Bail if this is the last element.
			 * This avoids duplicates since this location would technically be "after entry content" at this point.
			 */
			if ( $element->getLineNo() === $last->getLineNo() || null === $element->nextSibling ) {
				break;
			}

			// If modifying content.
			if ( ! $counts ) {
				/**
				 * Add cca after this element. There is no insertAfter() in PHP ¯\_(ツ)_/¯.
				 *
				 * @link https://gist.github.com/deathlyfrantic/cd8d7ef8ba91544cdf06
				 */
				$element->parentNode->insertBefore( $node, $element->nextSibling );
			}
		}
		// Before headings.
		else {
			// If modifying content.
			if ( ! $counts ) {
				$element->parentNode->insertBefore( $node, $element );
			}
		}

		// Add to count.
		$count[] = $item;

		// Remove from temp counts.
		unset( $tmp_counts[ $item ] );
	}

	// If modifying content.
	if ( ! $counts ) {
		// Save new HTML.
		$content = $dom->saveHTML();
	}

	// Return what we need.
	return $counts ? $count : $content;
}

/**
 * Gets DOMDocument object.
 *
 * @since 0.1.0
 *
 * @link https://stackoverflow.com/questions/29493678/loadhtml-libxml-html-noimplied-on-an-html-fragment-generates-incorrect-tags
 *
 * @param string $html Any given HTML string.
 *
 * @return DOMDocument
 */
function maipub_get_dom_document( $html ) {
	// Create the new document.
	$dom = new DOMDocument();

	// Modify state.
	$libxml_previous_state = libxml_use_internal_errors( true );

	// Encode.
	$html = mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' );

	// Load the content in the document HTML.
	$dom->loadHTML( "<div>$html</div>" );

	// Handle wraps.
	$container = $dom->getElementsByTagName('div')->item(0);
	$container = $container->parentNode->removeChild( $container );

	while ( $dom->firstChild ) {
		$dom->removeChild( $dom->firstChild );
	}

	while ( $container->firstChild ) {
		$dom->appendChild( $container->firstChild );
	}

	// Handle errors.
	libxml_clear_errors();

	// Restore.
	libxml_use_internal_errors( $libxml_previous_state );

	return $dom;
}

/**
 * Returns an array of ads.
 * Slugs must exist in the config.
 *
 * @since 0.1.0
 *
 * @return array
 */
function maipub_get_ads_data() {
	static $ads = null;

	if ( ! is_null( $ads ) ) {
		return $ads;
	}

	// Set default ad array.
	$ads = [
		'global'  => [],
		'single'  => [],
		'archive' => [],
	];

	// Check visibility.
	$visibility = maipub_is_singular() ? get_post_meta( get_the_ID(), 'maipub_visibility', true ) : false;

	// Bail if hidding all ads.
	if ( $visibility && in_array( 'all', $visibility ) ) {
		return $ads;
	}

	$query = new WP_Query(
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
			$post_id          = get_the_ID();
			$slug             = get_post()->post_name;
			$content          = get_post()->post_content;
			$global_location  = get_field( 'maipub_global_location' );
			$single_location  = get_field( 'maipub_single_location' );
			$archive_location = get_field( 'maipub_archive_location' );

			if ( $global_location ) {
				$ads['global'][] = maipub_filter_associative_array(
					[
						'id'       => $post_id,
						'slug'     => $slug,
						'location' => $global_location,
						'content'  => $content,
					]
				);
			}

			if ( $single_location ) {
				$ads['single'][] = maipub_filter_associative_array(
					[
						'id'                  => $post_id,
						'slug'                => $slug,
						'location'            => $single_location,
						'content'             => $content,
						'content_location'    => get_field( 'maipub_single_content_location' ),
						'content_count'       => get_field( 'maipub_single_content_count' ),
						'types'               => get_field( 'maipub_single_types' ),
						'keywords'            => get_field( 'maipub_single_keywords' ),
						'taxonomies'          => get_field( 'maipub_single_taxonomies' ),
						'taxonomies_relation' => get_field( 'maipub_single_taxonomies_relation' ),
						'authors'             => get_field( 'maipub_single_authors' ),
						'include'             => get_field( 'maipub_single_entries' ),
						'exclude'             => get_field( 'maipub_single_exclude_entries' ),
					]
				);
			}

			if ( $archive_location ) {
				$ads['archive'][] = maipub_filter_associative_array(
					[
						'id'            => $post_id,
						'slug'          => $slug,
						'location'      => $archive_location,
						'content'       => $content,
						'content_count' => get_field( 'maipub_archive_content_count' ),
						'types'         => get_field( 'maipub_archive_types' ),
						'taxonomies'    => get_field( 'maipub_archive_taxonomies' ),
						'terms'         => get_field( 'maipub_archive_terms' ),
						'exclude'       => get_field( 'maipub_archive_exclude_terms' ),
						'includes'      => get_field( 'maipub_archive_includes' ),
					]
				);
			}

		endwhile;
	}
	wp_reset_postdata();

	// Now that we have data, maybe check visibility for incontent ads.
	if ( $visibility && in_array( 'incontent', $visibility ) ) {
		foreach ( $ads['single'] as $index => $values ) {
			if ( 'content' !== $values['location'] ) {
				continue;
			}

			unset( $ads['single'][ $index ] );
		}
	}

	return $ads;
}

/**
 * Get content area hook locations.
 *
 * @since 0.1.0
 *
 * @return array
 */
function maipub_get_locations() {
	static $locations = null;

	if ( ! is_null( $locations ) ) {
		return $locations;
	}

	$locations = [
		'before_header'        => [
			'hook'     => 'genesis_before_header',
			'priority' => 5, // Before header default content area is 10.
		],
		'after_header'        => [
			'hook'     => 'genesis_after_header',
			'priority' => 15,
		],
		'before_loop'         => [
			'hook'     => 'genesis_loop',
			'priority' => 5,
		],
		'before_entry'         => [
			'hook'     => 'genesis_before_entry',
			'priority' => 10,
		],
		'before_entry_content' => [
			'hook'     => 'genesis_before_entry_content',
			'priority' => 10,
		],
		'content'              => [
			'hook'     => '', // No hooks, counted in content.
			'priority' => null,
		],
		'entries'              => [
			'hook'     => '', // No hooks, handled in function.
			'priority' => 10,
		],
		'after_entry_content'  => [
			'hook'     => 'genesis_after_entry_content',
			'priority' => 10,
		],
		'after_entry'          => [
			'hook'     => 'genesis_after_entry',
			'priority' => 8, // Comments are at 10.
		],
		'after_loop'           => [
			'hook'     => 'genesis_loop',
			'priority' => 15,
		],
		'before_footer'        => [
			'hook'     => 'genesis_after_content_sidebar_wrap',
			'priority' => 10,
		],
		'after_footer'        => [
			'hook'     => 'wp_footer',
			'priority' => 20,
		],
	];

	if ( maipub_is_product_archive() || maipub_is_product_singular() ) {
		$locations['before_loop'] = [
			'hook'     => 'woocommerce_before_shop_loop',
			'priority' => 12, // Notices are at 10.
		];

		$locations['before_entry']         = [
			'hook'     => 'woocommerce_before_single_product',
			'priority' => 12, // Notices are at 10.
		];

		$locations['before_entry_content'] = [
			'hook'     => 'woocommerce_after_single_product_summary',
			'priority' => 8, // Tabs are at 10.
		];

		$locations['after_entry_content']  = [
			'hook'     => 'woocommerce_after_single_product_summary',
			'priority' => 12, // Tabs are at 10, upsells and related products are 15.
		];

		$locations['after_entry']          = [
			'hook'     => 'woocommerce_after_single_product',
			'priority' => 10,
		];

		$locations['after_loop']           = [
			'hook'     => 'woocommerce_after_shop_loop',
			'priority' => 12, // Pagination is at 10.
		];
	}

	$locations = apply_filters( 'mai_publisher_locations', $locations );

	if ( $locations ) {
		foreach ( $locations as $name => $location ) {
			$locations[ $name ] = wp_parse_args( (array) $location,
				[
					'hook'     => '',
					'priority' => null,
				]
			);
		}
	}

	return $locations;
}

/**
 * Get location choices.
 *
 * @string TBD
 *
 * @param string $type
 *
 * @return array
 */
function maipub_get_location_choices( $type = '' ) {
	static $cache = null;

	if ( ! is_null( $cache ) ) {
		if ( $type ) {
			return $cache[ $type ];
		}

		return $cache;
	}

	$cache = [
		'global' => [
			''                     => __( 'None (inactive)', 'mai-publisher' ),
			'before_header'        => __( 'Before header', 'mai-publisher' ),
			'after_header'         => __( 'After header', 'mai-publisher' ),
			'before_footer'        => __( 'Before footer', 'mai-publisher' ),
			'after_footer'         => __( 'After footer', 'mai-publisher' ),
		],
		'single' => [
			''                     => __( 'None (inactive)', 'mai-publisher' ),
			'before_header'        => __( 'Before header', 'mai-publisher' ),
			'after_header'         => __( 'After header', 'mai-publisher' ),
			'before_entry'         => __( 'Before entry', 'mai-publisher' ),
			'before_entry_content' => __( 'Before entry content', 'mai-publisher' ),
			'content'              => __( 'In content', 'mai-publisher' ),
			'after_entry_content'  => __( 'After entry content', 'mai-publisher' ),
			'after_entry'          => __( 'After entry', 'mai-publisher' ),
			'before_footer'        => __( 'Before footer', 'mai-publisher' ),
		],
		'archive' => [
			''                     => __( 'None (inactive)', 'mai-publisher' ),
			'before_header'        => __( 'Before header', 'mai-publisher' ),
			'after_header'         => __( 'After header', 'mai-publisher' ),
			'before_loop'          => __( 'Before entries', 'mai-publisher' ),
			'entries'              => __( 'In entries', 'mai-publisher' ),        // TODO: Is this doable without breaking columns, etc?
			'after_loop'           => __( 'After entries', 'mai-publisher' ),
			'before_footer'        => __( 'Before footer', 'mai-publisher' ),
		],
	];

	if ( $type ) {
		return $cache[ $type ];
	}

	return $cache;
}

/**
 * Returns the sub config.
 *
 * @since 0.1.0
 *
 * @param string $sub_config Name of config to get.
 *
 * @return array
 */
function maipub_get_config( $sub_config = '' ) {
	static $config = null;

	if ( ! is_array( $config ) ) {
		$config = require MAI_PUBLISHER_DIR . '/config.php';
	}

	if ( $sub_config ) {
		return isset( $config[ $sub_config ] ) ? $config[ $sub_config ] : [];
	}

	return $config;
}

/**
 * Gets a single option value.
 *
 * @since 0.1.0
 *
 * @param string $option   Option name.
 * @param bool   $fallback Whether to fallback to default if no value.
 *
 * @return string
 */
function maipub_get_option( $option, $fallback = true ) {
	$options = maipub_get_options();

	if ( isset( $options[ $option ] ) && $options[ $option ] ) {
		return $options[ $option ];
	}

	return $fallback ? maipub_get_default_option( $option ) : null;
}

/**
 * Gets all option values.
 *
 * @since 0.1.0
 *
 * @return array
 */
function maipub_get_options() {
	static $options = null;

	if ( ! is_null( $options ) ) {
		return $options;
	}

	return (array) get_option( 'mai_publisher', [] );
}

/**
 * Gets a single default option value.
 *
 * @since 0.1.0
 *
 * @param string $option Option name.
 *
 * @return mixed|null
 */
function maipub_get_default_option( $option ) {
	$options = maipub_get_default_options();

	return isset( $options[ $option ] ) ? $options[ $option ] : null;
}

/**
 * Gets all default option values.
 *
 * @since 0.1.0
 *
 * @return array
 */
function maipub_get_default_options() {
	static $options = null;

	if ( ! is_null( $options ) ) {
		return $options;
	}

	$options = [
		'version_first'          => '',
		'version_db'             => '',
		'gam_domain'             => (string) maipub_get_url_host( home_url() ),
		'category'               => '',
		'matomo_enabled_global'  => defined( 'MAI_PUBLISHER_MATOMO_ENABLED_GLOBAL' ) ? MAI_PUBLISHER_MATOMO_ENABLED_GLOBAL : 1,
		'matomo_enabled'         => defined( 'MAI_PUBLISHER_MATOMO_ENABLED' ) ? MAI_PUBLISHER_MATOMO_ENABLED : 0,
		'matomo_enabled_backend' => defined( 'MAI_PUBLISHER_MATOMO_ENABLED_BACKEND' ) ? MAI_PUBLISHER_MATOMO_ENABLED_BACKEND : 0,
		'matomo_url'             => defined( 'MAI_PUBLISHER_MATOMO_URL' ) ? MAI_PUBLISHER_MATOMO_URL : '',
		'matomo_site_id'         => defined( 'MAI_PUBLISHER_MATOMO_SITE_ID' ) ? MAI_PUBLISHER_MATOMO_SITE_ID : '',
		'matomo_token'           => defined( 'MAI_PUBLISHER_MATOMO_TOKEN' ) ? MAI_PUBLISHER_MATOMO_TOKEN : '',
		'trending_days'          => 30,
		'views_days'             => 365,
		'views_interval'         => 60,
		'ad_label'               => __( 'Sponsored', 'mai-publisher' ),
		'header'                 => '',
		'footer'                 => '',
	];

	return $options;
}

/**
 * Returns the GAM domain.
 *
 * @since 0.1.0
 *
 * @param bool $fallback Whether to fallback to home_url() if no domain.
 *
 * @return string
 */
function maipub_get_gam_domain( $fallback = true ) {
	return maipub_get_url_host( (string) maipub_get_option( 'gam_domain', $fallback ) );
}

/**
 * Sanitizes domain to be used in GAM.
 *
 * @since 0.1.0
 *
 * @param string $domain The domain.
 *
 * @return string
 */
function maipub_get_url_host( string $domain ) {
	$domain = $domain ? (string) wp_parse_url( esc_url( (string) $domain ), PHP_URL_HOST ) : '';
	$domain = str_replace( 'www.', '', $domain );

	return $domain;
}

/**
 * Update a single option from mai_publisher array of options.
 *
 * @since 0.1.0
 *
 * @param string $option Option name.
 * @param mixed  $value  Option value.
 *
 * @return void
 */
function maipub_update_option( $option, $value ) {
	$handle             = 'mai_publisher';
	$options            = get_option( $handle, [] );
	$options[ $option ] = $value;

	update_option( $handle, $options );
}

/**
 * Gets file suffix.
 *
 * @since 0.1.0
 *
 * @return string
 */
function maipub_get_suffix() {
	static $suffix = null;

	if ( ! is_null( $suffix ) ) {
		return $suffix;
	}

	$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

	return $suffix;
}

/**
 * Gets file data.
 *
 * @since 0.1.0
 *
 * @param string $file The file path name.
 * @param string $key The specific key to return
 *
 * @return array|string
 */
function maipub_get_file_data( $file, $key = '' ) {
	static $cache = null;

	if ( ! is_null( $cache ) && isset( $cache[ $file ] ) ) {
		if ( $key ) {
			return $cache[ $file ][ $key ];
		}

		return $cache[ $file ];
	}

	$file_path      = MAI_PUBLISHER_DIR . $file;
	$file_url       = MAI_PUBLISHER_URL . $file;
	$version        = MAI_PUBLISHER_VERSION . '.' . date( 'njYHi', filemtime( $file_path ) );
	$cache[ $file ] = [
		'path'    => $file_path,
		'url'     => $file_url,
		'version' => $version,
	];

	if ( $key ) {
		return $cache[ $file ][ $key ];
	}

	return $cache[ $file ];
}

/**
 * Gets all categories from categories.json.
 * This is for the category picker.
 *
 * @since 0.1.0
 *
 * @return array
 */
function maipub_get_all_categories() {
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
