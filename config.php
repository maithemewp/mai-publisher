<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

return [
	'ad_units' => [
		'footer' => [
			'sizes'         => [ '320x50', '468x60', '728x90', '970x90' ],
			'sizes_desktop' => [ '970x90', '728x90' ],
			'sizes_tablet'  => [ '468x60', '320x50' ],
			'sizes_mobile'  => [ '320x50' ],
			'post_title'    => 'Footer',
			'post_content'  => '',
		],
		'fullscreen' => [
			'ad_sizes'     => [ 'Fluid', '320x480', '468x60', '480x320', '768x1024', '1024x768' ],
			'post_title'   => 'Fullscreen',
			'post_content' => '',
		],
		'header' => [
			'ad_sizes'     => [ '320x50', '468x60', '728x90', '970x90', '970x250' ],
			'post_title'   => 'Header',
			'post_content' => '',
		],
		'incontent' => [
			'ad_sizes'     => [ '300x100', '300x250', '336x280', '750x100', '750x200', '750x300', '970x66', '970x250' ],
			'post_title'   => 'In-content',
			'post_content' => '',
		],
		'infeed' => [
			'ad_sizes'     => [ '240x400', '300x250', '300x600' ],
			'post_title'   => 'In-feed',
			'post_content' => '',
		],
		'inrecipe' => [
			'ad_sizes'     => [ '200x200', '250x250', '300x300', '400x400' ],
			'post_title'   => 'In-recipe',
			'post_content' => '',
		],
		'podcast-footer' => [
			'ad_sizes'     => [ '320x50', '468x60', '728x90', '970x90' ],
			'post_title'   => 'Podcast Footer',
			'post_content' => '',
		],
		'podcast-header' => [
			'ad_sizes'     => [ '320x50', '468x60', '728x90', '970x90', '970x250' ],
			'post_title'   => 'Podcast Header',
			'post_content' => '',
		],
	]
];