<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

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
	$content = $wp_embed->autoembed( $content );            // WP runs priority 8.
	$content = $wp_embed->run_shortcode( $content );        // WP runs priority 8.
	$content = $blocks ? do_blocks( $content ) : $content;  // WP runs priority 9.
	$content = wptexturize( $content );                     // WP runs priority 10.
	$content = ! $blocks ? wpautop( $content ) : $content;  // WP runs priority 10.
	$content = shortcode_unautop( $content );               // WP runs priority 10.
	$content = do_shortcode( $content );                    // WP runs priority 11.
	$content = wp_filter_content_tags( $content );          // WP runs priority 12.
	$content = convert_smilies( $content );                 // WP runs priority 20.
	$content = str_replace( ']]>', ']]&gt;', $content );

	return $content;
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
	$html = mb_encode_numericentity( $html, [0x80, 0x10FFFF, 0, ~0], 'UTF-8' );

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
 * Saves HTML from DOMDocument and decode entities.
 *
 * @since 1.1.0
 *
 * @param DOMDocument $dom
 *
 * @return string
 */
function maipub_get_dom_html( $dom ) {
	$html = $dom->saveHTML();

	return $html;
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

	$after_header_hook     = 'genesis_after_header';
	$after_header_priority = 15;
	$before_entry_hook     = 'genesis_before_entry';

	// If Mai Engine.
	if ( class_exists( 'Mai_Engine' ) ) {
		$page_header  = mai_has_page_header();
		$trans_header = mai_has_transparent_header_enabled();
		$full_first   = mai_has_alignfull_first();

		// If page header, so after the page header.
		if ( $page_header ) {
			$after_header_hook     = 'genesis_before_content_sidebar_wrap';
			$after_header_priority = 11; // Mai Engine page header is 10 and breadcrumbs are 12.
		}
		// No page header but has transparent header and alignfull first. This will break layout so we disable these ads.
		elseif ( $trans_header && $full_first ) {
			$after_header_hook = '';
			$before_entry_hook = '';
		}
	}

	$locations = [
		'before_header' => [
			'hook'     => 'genesis_before_header',
			'priority' => 5, // Before header default content area is 10.
			'target'   => 'bh',
		],
		'after_header' => [
			'hook'     => $after_header_hook,
			'priority' => $after_header_priority,
			'target'   => 'ah',
		],
		'before_loop' => [
			'hook'     => 'genesis_loop',
			'priority' => 5,
			'target'   => 'bl',
		],
		'before_entry' => [
			'hook'     => $before_entry_hook,
			'priority' => 10,
			'target'   => 'be',
		],
		'before_entry_content' => [
			'hook'     => 'genesis_before_entry_content',
			'priority' => 10,
			'target'   => 'bec',
		],
		'content' => [
			'hook'     => '', // No hooks, handled in class-output.
			'priority' => null,
			'target'   => 'icn',
		],
		'recipe' => [
			'hook'     => '', // No hooks, handled in class-output.
			'priority' => null,
			'target'   => 'ir',
		],
		'comments' => [
			'hook'     => '', // No hooks, handled in class-output.
			'priority' => null,
			'target'   => 'icm',
		],
		'entries' => [
			'hook'     => '', // No hooks, handled in class-output.
			'priority' => null,
			'target'   => 'if',
		],
		'after_entry_content'  => [
			'hook'     => 'genesis_after_entry_content',
			'priority' => 10,
			'target'   => 'aec',
		],
		'after_entry' => [
			'hook'     => 'genesis_after_entry',
			'priority' => 8, // Comments are at 10.
			'target'   => 'ae',
		],
		'after_loop' => [
			'hook'     => 'genesis_loop',
			'priority' => 15,
			'target'   => 'al',
		],
		'before_sidebar_content' => [
			'hook'     => '', // No hooks, handled in class-output.
			'priority' => null,
			'target'   => 'bsc',
		],
		'after_sidebar_content' => [
			'hook'     => '', // No hooks, handled in class-output.
			'priority' => null,
			'target'   => 'asc',
		],
		'before_footer' => [
			'hook'     => 'genesis_after_content_sidebar_wrap',
			'priority' => 10,
			'target'   => 'bf',
		],
		'after_footer' => [
			'hook'     => 'wp_footer',
			'priority' => 20,
			'target'   => 'af',
		],
	];

	// Handle WooCommerce locations.
	if ( maipub_is_product_archive() || maipub_is_product_singular() ) {
		$locations['before_loop']['hook']     = 'woocommerce_before_shop_loop';
		$locations['before_loop']['priority'] = 12; // Notices are at 10.

		$locations['before_entry']['hook']     = 'woocommerce_before_single_product';
		$locations['before_entry']['priority'] = 12; // Notices are at 10.

		$locations['before_entry_content']['hook']     = 'woocommerce_after_single_product_summary';
		$locations['before_entry_content']['priority'] = 8;// Tabs are at 10.

		$locations['after_entry_content']['hook']     = 'woocommerce_after_single_product_summary';
		$locations['after_entry_content']['priority'] = 12; // Tabs are at 10, upsells and related products are 15.

		$locations['after_entry']['hook']     = 'woocommerce_after_single_product';
		$locations['after_entry']['priority'] = 10;

		$locations['after_loop']['hook']     = 'woocommerce_after_shop_loop';
		$locations['after_loop']['priority'] = 12; // Pagination is at 10.
	}

	// Filter.
	$locations = apply_filters( 'mai_publisher_locations', $locations );

	// If locations.
	if ( $locations ) {
		// Make sure all locations have the required keys.
		foreach ( $locations as $name => $location ) {
			$locations[ $name ] = wp_parse_args( (array) $location,
				[
					'hook'     => '',
					'priority' => null,
					'target'   => '',
				]
			);
		}
	}

	return $locations;
}

/**
 * Get location choices.
 *
 * @string 0.1.0
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
			''                       => __( 'None (inactive)', 'mai-publisher' ),
			'before_header'          => __( 'Before header', 'mai-publisher' ),
			'after_header'           => __( 'After header', 'mai-publisher' ),
			'before_sidebar_content' => __( 'Before sidebar content', 'mai-publisher' ),
			'after_sidebar_content'  => __( 'After sidebar content', 'mai-publisher' ),
			'before_footer'          => __( 'Before footer', 'mai-publisher' ),
			'after_footer'           => __( 'After footer', 'mai-publisher' ),
		],
		'single' => [
			''                       => __( 'None (inactive)', 'mai-publisher' ),
			'before_header'          => __( 'Before header', 'mai-publisher' ),
			'after_header'           => __( 'After header', 'mai-publisher' ),
			'before_entry'           => __( 'Before entry', 'mai-publisher' ),
			'before_entry_content'   => __( 'Before entry content', 'mai-publisher' ),
			'content'                => __( 'In content', 'mai-publisher' ),
			'comments'               => __( 'In comments', 'mai-publisher' ),
			'recipe'                 => __( 'In recipe', 'mai-publisher' ),
			'after_entry_content'    => __( 'After entry content', 'mai-publisher' ),
			'after_entry'            => __( 'After entry', 'mai-publisher' ),
			'before_sidebar_content' => __( 'Before sidebar content', 'mai-publisher' ),
			'after_sidebar_content'  => __( 'After sidebar content', 'mai-publisher' ),
			'before_footer'          => __( 'Before footer', 'mai-publisher' ),
			'after_footer'           => __( 'After footer', 'mai-publisher' ),
		],
		'archive' => [
			''                       => __( 'None (inactive)', 'mai-publisher' ),
			'before_header'          => __( 'Before header', 'mai-publisher' ),
			'after_header'           => __( 'After header', 'mai-publisher' ),
			'before_loop'            => __( 'Before entries', 'mai-publisher' ),
			'entries'                => __( 'In entries', 'mai-publisher' ),
			'after_loop'             => __( 'After entries', 'mai-publisher' ),
			'before_sidebar_content' => __( 'Before sidebar content', 'mai-publisher' ),
			'after_sidebar_content'  => __( 'After sidebar content', 'mai-publisher' ),
			'before_footer'          => __( 'Before footer', 'mai-publisher' ),
			'after_footer'           => __( 'After footer', 'mai-publisher' ),
		],
	];

	// Filter.
	$cache = apply_filters( 'mai_publisher_location_choices', $cache );

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

		// Allow filtering.
		$config = apply_filters( 'mai_publisher_config', $config );
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
 * @since 1.7.2 Added filter.
 *
 * @return array
 */
function maipub_get_options() {
	static $options = null;

	if ( ! is_null( $options ) ) {
		return $options;
	}

	// Get and filter options.
	$options = (array) get_option( 'mai_publisher', [] );
	$options = apply_filters( 'mai_publisher_options', $options );

	return $options;
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

	return $options[ $option ];
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
		'version_first'               => '',
		'version_db'                  => '',
		'ad_mode'                     => 'disabled',
		'gam_domain'                  => (string) maipub_get_url_host( home_url() ),
		'gam_network_code'            => '',
		'gam_hashed_domain'           => (string) maipub_encode( (string) maipub_get_url_host( home_url() ), 14 ),
		'gam_sellers_id'              => '',
		'gam_sellers_name'            => '',
		'gam_targets'                 => '',
		'dc_seg'                      => '',
		'sourcepoint_property_id'     => '',
		'sourcepoint_property_id'     => '',
		'sourcepoint_msps_message_id' => '',
		'sourcepoint_tcf_message_id'  => '',
		'category'                    => '',
		'category_mapping'            => [ 'post' => 'category' ],
		'magnite_enabled'             => 0,
		'amazon_uam_enabled'          => 0,
		'load_delay'                  => '',
		'debug_enabled'               => 0,
		'matomo_enabled_global'       => defined( 'MAI_PUBLISHER_MATOMO_ENABLED_GLOBAL' ) ? MAI_PUBLISHER_MATOMO_ENABLED_GLOBAL : 0,
		'matomo_enabled'              => defined( 'MAI_PUBLISHER_MATOMO_ENABLED' ) ? MAI_PUBLISHER_MATOMO_ENABLED : 0,
		'matomo_enabled_backend'      => defined( 'MAI_PUBLISHER_MATOMO_ENABLED_BACKEND' ) ? MAI_PUBLISHER_MATOMO_ENABLED_BACKEND : 0,
		'matomo_url'                  => defined( 'MAI_PUBLISHER_MATOMO_URL' ) ? MAI_PUBLISHER_MATOMO_URL : '',
		'matomo_site_id'              => defined( 'MAI_PUBLISHER_MATOMO_SITE_ID' ) ? MAI_PUBLISHER_MATOMO_SITE_ID : '',
		'matomo_token'                => defined( 'MAI_PUBLISHER_MATOMO_TOKEN' ) ? MAI_PUBLISHER_MATOMO_TOKEN : '',
		'views_api'                   => 'disabled',
		'views_years'                 => 10,
		'views_interval'              => 60,
		'trending_days'               => 30,
		'ad_label'                    => __( 'Sponsored', 'mai-publisher' ),
		'header'                      => '',
		'footer'                      => '',
	];

	return $options;
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
 * Gets asset file data for a script or style.
 *
 * @since TBD
 *
 * @param string $file    The file path relative to the build directory.
 * @param string $type    The asset type ('script' or 'style').
 * @param array  $options Additional options.
 *
 * @return array Asset data including url, version, and dependencies.
 */
function maipub_get_asset_data( $file, $type = 'script', $options = [] ) {
	static $cache = [];

	// Return cached data if available.
	$cache_key = $file . '|' . $type;
	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	// Set up default data.
	$data = [
		'url'          => MAI_PUBLISHER_URL . 'build/' . $file,
		'version'      => MAI_PUBLISHER_VERSION,
		'dependencies' => [],
	];

	// For scripts, try to get data from the asset file.
	if ( 'script' === $type ) {
		$asset_file = MAI_PUBLISHER_DIR . 'build/' . str_replace( '.js', '.asset.php', $file );

		if ( file_exists( $asset_file ) ) {
			$asset_data = require( $asset_file );
			$data['version'] = $asset_data['version'];
			$data['dependencies'] = $asset_data['dependencies'];
		}
	}

	// For styles, use the corresponding script's version if available.
	if ( 'style' === $type && empty( $options['script_file'] ) ) {
		// Try to find a matching script file.
		$script_file = str_replace( '.css', '.js', $file );
		$asset_file = MAI_PUBLISHER_DIR . 'build/' . str_replace( '.js', '.asset.php', $script_file );

		if ( file_exists( $asset_file ) ) {
			$asset_data = require( $asset_file );
			$data['version'] = $asset_data['version'];
		}
	} elseif ( 'style' === $type && ! empty( $options['script_file'] ) ) {
		// Use the specified script file for version.
		$asset_file = MAI_PUBLISHER_DIR . 'build/' . str_replace( '.js', '.asset.php', $options['script_file'] );

		if ( file_exists( $asset_file ) ) {
			$asset_data = require( $asset_file );
			$data['version'] = $asset_data['version'];
		}
	}

	// Cache and return the data.
	$cache[ $cache_key ] = $data;
	return $data;
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