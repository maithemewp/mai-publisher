<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

$array = [
	'header',
	'header',
	'footer',
	'footer',
	'footer',
];

$array = [
	'header',
	'header-2',
	'footer',
	'footer-2',
	'footer-3',
];

class Mai_GAM_Scripts {

	/**
	 * Construct the class.
	 */
	function __construct() {
		$this->hooks();
	}

	/**
	 * Add hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function hooks() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_script' ], 0 );
	}

	/**
	 * Enqueue JS for GPT ads.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function enqueue_script() {
		$ads    = maigam_get_ads();
		$domain = maiam_get_gam_domain();

		if ( ! $ads && ! $domain ) {
			return;
		}

		$slugs = $this->get_unique_slugs( $ads );

		if ( ! $slugs ) {
			return;
		}

		wp_enqueue_script( 'google-gpt', 'https://securepubads.g.doubleclick.net/tag/js/gpt.js', [],  $this->get_file_data( 'version' ), false );
		wp_enqueue_script( 'mai-gam', $this->get_file_data( 'url' ), [ 'google-gpt' ],  $this->get_file_data( 'version' ), false );
		wp_localize_script( 'mai-gam', 'maiGAMVars',
			[
				'ads' => $slugs,
			]
		);
	}

	/**
	 * Gets slugs from ad data for defining slots.
	 * Increments duplicate slugs with -2, -3, etc.
	 *   if they are shown multiple times on the same page.
	 *
	 * @since 0.1.0
	 *
	 * @param array $ads
	 *
	 * @return array
	 */
	function get_unique_slugs( $ads ) {
		$slugs    = [];
		$counters = [];

		foreach ( $ads as $ad ) {
			if ( 'content' === $ad['location'] ) {
				foreach ( $ad['content_count'] as $index => $count ) {
					$slugs[] = $ad['slug'];
				}
			} else {
				$slugs[] = $ad['slug'];
			}
		}

		foreach ( $slugs as &$slug ) {
			if ( isset( $counters[ $slug ] ) ) {
				$slug = $slug . '-' . $counters[ $slug ]++;
			} else {
				$counters[ $slug ] = 1;
			}
		}

		return $slugs;
	}

	/**
	 * Gets file URL.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key The specific key to return
	 *
	 * @return array|string
	 */
	function get_file_data( $key = '' ) {
		static $cache = null;

		if ( ! is_null( $cache ) ) {
			if ( $key ) {
				return $cache[ $key ];
			}

			return $cache;
		}

		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$file      = "/assets/js/mai-gam{$suffix}.js";
		$file_path = MAI_GAM_DIR . $file;
		$file_url  = MAI_GAM_URL . $file;
		$version   = MAI_GAM_VERSION . '.' . date( 'njYHi', filemtime( $file_path ) );
		$cache     = [
			'path'    => $file_path,
			'url'     => $file_url,
			'version' => $version,
		];

		if ( $key ) {
			return $cache[ $key ];
		}

		return $cache;
	}
}