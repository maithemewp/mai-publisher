<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Gets views for display.
 *
 * @since 0.3.0
 *
 * @param array $atts The shortcode atts.
 *
 * @return string
 */
function maipub_get_views( $atts = [] ) {
	global $mai_term;

	// Atts.
	$atts = shortcode_atts(
		[
			'object'             => ! is_null( $mai_term ) ? 'term' : 'post',  // Either 'post' or 'term'.
			'id'                 => '',      // The post/term ID.
			'views'              => '',      // Empty for all, and 'trending' to view trending views.
			'min'                => 20,      // Minimum number of views before displaying.
			'format'             => 'short', // Use short format (2k+) or show full number (2,143). Currently accepts 'short', '', or a falsey value.
			'style'              => 'display:inline-flex;align-items:center;',
			'before'             => '',
			'after'              => '',
			'icon'               => 'heart',
			'icon_style'         => 'solid',
			'icon_size'          => '0.85em',
			'icon_margin_top'    => '0',
			'icon_margin_right'  => '0.25em',
			'icon_margin_bottom' => '0',
			'icon_margin_left'   => '0',
		],
		$atts,
		'mai_views'
	);

	// Sanitize.
	$atts = [
		'object'             => sanitize_key( $atts['object'] ),
		'id'                 => absint( $atts['id'] ),
		'views'              => sanitize_key( $atts['views'] ),
		'min'                => absint( $atts['min'] ),
		'format'             => esc_html( $atts['format'] ),
		'style'              => esc_attr( $atts['style'] ),
		'before'             => esc_html( $atts['before'] ),
		'after'              => esc_html( $atts['after'] ),
		'icon'               => sanitize_key( $atts['icon'] ),
		'icon_style'         => sanitize_key( $atts['icon_style'] ),
		'icon_size'          => esc_attr( $atts['icon_size'] ),
		'icon_margin_top'    => esc_attr( $atts['icon_margin_top'] ),
		'icon_margin_right'  => esc_attr( $atts['icon_margin_right'] ),
		'icon_margin_bottom' => esc_attr( $atts['icon_margin_bottom'] ),
		'icon_margin_left'   => esc_attr( $atts['icon_margin_left'] ),
	];

	// Get views.
	$views = maipub_get_view_count( $atts );

	// Bail if no views or not over the minimum.
	if ( ! $views || $views < $atts['min'] ) {
		return;
	}

	// Get markup/values.
	$views = 'short' === $atts['format'] ? maipub_get_short_number( $views ) : number_format_i18n( $views );
	$style = $atts['style'] ? sprintf( ' style="%s"', $atts['style'] ) : '';
	$icon  = $atts['icon'] && function_exists( 'mai_get_icon' ) ? mai_get_icon(
		[
			'icon'          => $atts['icon'],
			'style'         => $atts['icon_style'],
			'size'          => $atts['icon_size'],
			'margin_top'    => $atts['icon_margin_top'],
			'margin_right'  => $atts['icon_margin_right'],
			'margin_bottom' => $atts['icon_margin_bottom'],
			'margin_left'   => $atts['icon_margin_left'],
		]
	) : '';

	// Build markup.
	$html = sprintf( '<span class="mai-views"%s>%s%s<span class="mai-views__count">%s</span>%s</span>', $style, $atts['before'], $icon, $views, $atts['after'] );

	// Allow filtering of markup.
	$views = apply_filters( 'mai_publisher_entry_views', $html );

	return $views;
}

/**
 * Retrieve view count for a post.
 *
 * @since 0.3.0
 *
 * @param array $args The view args.
 *
 * @return int $views Post View.
 */
function maipub_get_view_count( $args = [] ) {
	global $mai_term;

	$args = wp_parse_args( $args,
		[
			'object' => ! is_null( $mai_term ) ? 'term' : 'post',
			'id'     => '',
			'views'  => '',
		]
	);

	$args['object'] = sanitize_key( $args['object'] );
	$args['views']  = sanitize_key( $args['views'] );
	$args['id']     = ! $args['id'] && 'term' === $args['object'] && ! is_null( $mai_term ) ? $mai_term->term_id : get_the_ID();

	if ( ! $args['id'] ) {
		return 0;
	}

	$key   = 'trending' === $args['views'] ? 'mai_trending' : 'mai_views';
	$count = 'term' === $args['object'] ? get_term_meta( $args['id'], $key, true ) : get_post_meta( $args['id'], $key, true );

	return absint( $count );
}

/**
 * Gets a shortened number value for number.
 *
 * @since 0.3.0
 *
 * @param int $number The number.
 *
 * @return string
 */
function maipub_get_short_number( int $number ) {
	if ( $number < 1000 ) {
		return sprintf( '%d', $number );
	}

	if ( $number < 1000000 ) {
		return sprintf( '%d%s', floor( $number / 1000 ), 'K+' );
	}

	if ( $number >= 1000000 && $number < 1000000000 ) {
		return sprintf( '%d%s', floor( $number / 1000000 ), 'M+' );
	}

	if ( $number >= 1000000000 && $number < 1000000000000 ) {
		return sprintf( '%d%s', floor( $number / 1000000000 ), 'B+' );
	}

	return sprintf( '%d%s', floor( $number / 1000000000000 ), 'T+' );
};