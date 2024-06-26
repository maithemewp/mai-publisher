<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Checks if current page is an singular.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function maipub_is_singular() {
	return is_singular();
}

/**
 * Checks if current page is an archive.
 *
 * @since 0.1.0
 *
 * @return bool
 */
function maipub_is_archive() {
	return is_home() || is_post_type_archive() || is_category() || is_tag() || is_tax() || is_author() || is_search() || maipub_is_product_archive();
}

/**
 * Checks if current page is a WooCommerce shop.
 *
 * @since 1.2.0
 *
 * @return bool
 */
function maipub_is_shop_archive() {
	return class_exists( 'WooCommerce' ) && is_shop();
}

/**
 * Checks if current page is a WooCommerce product archive.
 *
 * @since 1.2.0
 *
 * @return bool
 */
function maipub_is_product_archive() {
	return class_exists( 'WooCommerce' ) && ( is_shop() || is_product_taxonomy() );
}

/**
 * Checks if current page is a WooCommerce single product.
 *
 * @since 1.2.0
 *
 * @return bool
 */
function maipub_is_product_singular() {
	return class_exists( 'WooCommerce' ) && is_product();
}

/**
 * Checks if currently editing a single post by post type.
 *
 * @since 1.7.0
 *
 * @return bool
 */
function maipub_is_editor( $post_type = 'mai_ad' ) {
	static $cache = null;

	// If cache is set, return it.
	if ( ! is_null( $cache ) ) {
		return $cache;
	}

	// Start cache as false.
	$cache = false;

	// If in the back end.
	if ( is_admin() ) {
		// Check if editing a Mai Ad.
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$cache  = ( $screen && $post_type === $screen->id ) || ( isset( $_REQUEST['post'] ) && $post_type === get_post_type( $_REQUEST['post'] ) );
	}

	return $cache;
}

/**
 * Check if a string contains at least one specified string.
 *
 * @since 0.1.0
 *
 * @param string       $haystack String to check in.
 * @param string|array $needle   String or array of strings to check for.
 *
 * @return string
 */
function maipub_str_contains( $haystack, $needle ) {
	if ( ! $haystack ) {
		return false;
	}

	if ( is_array( $needle ) ) {
		foreach ( $needle as $string ) {
			if ( str_contains( $haystack, $string ) ) {
				return true;
			}
		}

		return false;
	}

	return str_contains( $haystack, $needle );
}

/**
 * If on a page that we should be tracking.
 *
 * @access private
 *
 * @since 0.3.0
 *
 * @return bool
 */
function maipub_should_track() {
	static $cache = null;

	if ( ! is_null( $cache ) ) {
		return $cache;
	}

	$cache          = false;
	$enabled_global = maipub_get_option( 'matomo_enabled_global', false );
	$site_url       = maipub_get_option( 'matomo_url', false );
	$site_id        = maipub_get_option( 'matomo_site_id', false );
	$enabled        = maipub_get_option( 'matomo_enabled', false );
	$enabled        = $enabled && $site_url && $site_id;
	$views_api      = maipub_get_option( 'views_api' );
	$is_matomo      = 'matomo'  === $views_api && $enabled && ( is_singular() || is_category() || is_tag() || is_tax() );
	$is_jetpack     = 'jetpack' === $views_api && class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'stats' ) && class_exists( 'Automattic\Jetpack\Stats\WPCOM_Stats' ) && is_singular();

	// Bail if not enabled.
	if ( ! ( $enabled_global || $enabled || $is_matomo || $is_jetpack ) ) {
		return $cache;
	}

	// Bail if contributor or above.
	if ( current_user_can( 'edit_posts' ) ) {
		return $cache;
	}

	// Bail if we are in an ajax call.
	if ( wp_doing_ajax() ) {
		return $cache;
	}

	// Bail if this is a JSON request.
	if ( wp_is_json_request() ) {
		return $cache;
	}

	// Bail if this running via a CLI command.
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return $cache;
	}

	// Bail if admin page and we're not tracking.
	// if ( ! maipub_get_option( 'matomo_enabled_backend' ) && is_admin() ) {
	// 	return $cache;
	// }

	// We got here, set cache and let's track it.
	$cache = true;

	return $cache;
}