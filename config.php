<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

return [
	'ad_units' => [
		'footer' => [
			'sizes'         => [ '320x50', '468x60', '728x90', '970x90' ],
			'sizes_desktop' => [ '728x90', '970x90' ],
			'sizes_tablet'  => [ '320x50', '468x60' ],
			'sizes_mobile'  => [ '320x50' ],
			'post_title'    => __( 'Footer', 'mai-gam' ),
			'post_content'  => '',
		],
		'fullscreen' => [
			'sizes'         => [ 'Fluid', '320x480', '468x60', '480x320', '768x1024', '1024x768' ],
			'sizes_desktop' => [ '768x1024', '1024x768' ],
			'sizes_tablet'  => [ '468x60', '480x320' ],
			'sizes_mobile'  => [ '320x50' ],
			'post_title'    => __( 'Fullscreen', 'mai-gam' ),
			'post_content'  => '',
		],
		'header' => [
			'sizes'         => [ '320x50', '468x60', '728x90', '970x90', '970x250' ],
			'sizes_desktop' => [],
			'sizes_tablet'  => [],
			'sizes_mobile'  => [],
			'post_title'    => __( 'Header', 'mai-gam' ),
			'post_content'  => '',
		],
		'incontent' => [
			'sizes'         => [ '300x100', '300x250', '336x280', '750x100', '750x200', '750x300', '970x66', '970x250' ],
			'sizes_desktop' => [],
			'sizes_tablet'  => [],
			'sizes_mobile'  => [],
			'post_title'    => __( 'In-content', 'mai-gam' ),
			'post_content'  => '',
		],
		'infeed' => [
			'sizes'         => [ '240x400', '300x250', '300x600' ],
			'sizes_desktop' => [],
			'sizes_tablet'  => [],
			'sizes_mobile'  => [],
			'post_title'    => __( 'In-feed', 'mai-gam' ),
			'post_content'  => '',
		],
		'inrecipe' => [
			'sizes'         => [ '200x200', '250x250', '300x300', '400x400' ],
			'sizes_desktop' => [],
			'sizes_tablet'  => [],
			'sizes_mobile'  => [],
			'post_title'    => __( 'In-recipe', 'mai-gam' ),
			'post_content'  => '',
		],
		'podcast-footer' => [
			'sizes'         => [ '320x50', '468x60', '728x90', '970x90' ],
			'sizes_desktop' => [],
			'sizes_tablet'  => [],
			'sizes_mobile'  => [],
			'post_title'    => __( 'Podcast Footer', 'mai-gam' ),
			'post_content'  => '',
		],
		'podcast-header' => [
			'sizes'         => [ '320x50', '468x60', '728x90', '970x90', '970x250' ],
			'sizes_desktop' => [],
			'sizes_tablet'  => [],
			'sizes_mobile'  => [],
			'post_title'    => __( 'Podcast Header', 'mai-gam' ),
			'post_content'  => '',
		],
	]
];