<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Output {
	/**
	 * Constructs the class.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function __construct() {
		$this->hooks();
	}

	/**
	 * Runs hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function hooks() {
		add_action( 'template_redirect', [ $this, 'start' ], 99998 );
		add_action( 'shutdown',          [ $this, 'end' ], 99998 );
	}

	/**
	 * Starts output buffering.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function start() {
		ob_start( [ $this, 'callback' ] );
	}

	/**
	 * Ends output buffering.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function end() {
		/**
		 * I hope this is right.
		 *
		 * @link https://stackoverflow.com/questions/7355356/whats-the-difference-between-ob-flush-and-ob-end-flush
		 */
		if ( ob_get_length() ) {
			ob_flush();
		}
	}

	/**
	 * Buffer callback.
	 *
	 * @since TBD
	 *
	 * @param string $buffer The full dom markup.
	 *
	 * @return string
	 */
	function callback( $buffer ) {
		// Bail if no buffer.
		if ( ! $buffer ) {
			return $buffer;
		}

		// Bail if XML.
		if ( str_starts_with( trim( $buffer ), '<?xml' ) ) {
			return $buffer;
		}

		// Get ads.
		$ads          = maipub_get_ads_data();
		$global_ads   = isset( $ads['global'] ) ? $ads['global'] : [];
		$singular_ads = maipub_is_singular() && isset( $ads['single'] ) ? $ads['single']: [];
		$archive_ads  = maipub_is_archive() && isset( $ads['archive'] ) ? $ads['archive']: [];

		// Bail if no ads.
		if ( ! ( $global_ads || $singular_ads || $archive_ads ) ) {
			return $buffer;
		}

		// Get DOM.
		$dom = maipub_get_dom_document( $content );
		// $xpath = new DOMXPath( $dom );
		// $all   = $xpath->query( '/*[not(self::script or self::style or self::link)]' );

		if ( $singular_ads ) {
			$singular = [];

			foreach ( $singular_ads as $ad ) {
				// Make sure location key is set.
				$location              = $ad['location'];
				$singular[ $location ] = isset( $singular[ $location ] ) ? $singular[ $location ] : [];

				// Unset location.
				unset( $ad['location'] );

				// Add to new array.
				$singular[ $location ][] = $ad;
			}
		}

		// Save HTML.
		// $buffer = $dom->saveHTML();

		return $buffer;
	}

	function locations() {
		$locations = [
			'before_header' => [
				'query'  => '.site-header',
				'insert' => 'prepend',
			],
			'after_header' => [
				'query'  => '.site-header',
				'insert' => 'append',
			],
			'before_loop' => [
				'query'  => '.content',
				'insert' => 'prependchild',
			],
			'after_loop' => [
				'query'  => '.content',
				'insert' => 'appendchild',
			],
			'before_entry' => [
				'query'  => '.content',
				'insert' => 'prependchild',
			],
			'after_entry' => [
				'query'  => '.content',
				'insert' => 'appendchild',
			],
			'before_entry_content' => [
				'query'  => '.entry-content',
				'insert' => 'prepend',
			],
			'after_entry_content'  => [
				'query'  => '.entry-content',
				'insert' => 'append',
			],
			'content' => [
				'query'  => '.entry-content',
				'insert' => 'between',
			],
			'entries' => [
				'query'  => '.entries-wrap > .entry',
				'insert' => 'innerbefore',
			],
			'recipe' => [
				'query'  => '.wprm-recipe-ingredients',
				'insert' => 'before',
			],
			'before_footer' => [
				'query'  => '.site-footer',
				'insert' => 'before',
			],
			'after_footer' => [
				'query'  => '.site-footer',
				'insert' => 'before',
			],
		];

		return $locations;
	}
}

// add_action( 'genesis_before_loop', function() {
// 	echo '<h2>Here</h2>';
// }, 5 );

add_action( 'genesis_before_entry_content', function() {
	echo '<h2>Here</h2>';
}, 10 );