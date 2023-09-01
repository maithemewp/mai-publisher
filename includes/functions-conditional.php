<?php

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
	return is_home() || is_post_type_archive() || is_category() || is_tag() || is_tax() || is_search() || maipub_is_product_archive();
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
 * Check if a string contains at least one specified string.
 *
 * @since 0.1.0
 *
 * @param string|array $needle   String or array of strings to check for.
 * @param string       $haystack String to check in.
 *
 * @return string
 */
function maipub_has_string( $needle, $haystack ) {
	if ( ! $haystack ) {
		return false;
	}

	if ( is_array( $needle ) ) {
		foreach ( $needle as $string ) {
			if ( false !== strpos( $haystack, $string ) ) {
				return true;
			}
		}

		return false;
	}

	return false !== strpos( $haystack, $needle );
}