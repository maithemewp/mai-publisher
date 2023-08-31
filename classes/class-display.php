<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_GAM_Display {
	protected $ads;
	protected $slugs;
	protected $locations;

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
		add_action( 'wp_enqueue_scripts', [ $this, 'run' ], 0 );
	}

	/**
	 * Enqueue JS for GPT ads.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function run() {
		$ads    = maigam_get_ads();
		$domain = maigam_get_domain();

		if ( ! $ads && ! $domain ) {
			return;
		}

		$this->ads       = $ads;
		$this->slugs     = $this->get_unique_slugs( $ads );
		$this->locations = maigam_get_locations();

		if ( ! $this->slugs ) {
			return;
		}

		wp_enqueue_script( 'google-gpt', 'https://securepubads.g.doubleclick.net/tag/js/gpt.js', [],  $this->get_file_data( 'version' ), false );
		wp_enqueue_script( 'mai-gam', $this->get_file_data( 'url' ), [ 'google-gpt' ],  $this->get_file_data( 'version' ), false );
		wp_localize_script( 'mai-gam', 'maiGAMVars',
			[
				'ads' => $this->slugs,
			]
		);

		$this->display();
	}

	/**
	 * Display ads.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function display() {
		foreach ( $this->ads as $args ) {
			// Bail if no registered location.
			if ( ! isset( $this->locations[ $args['location'] ] ) ) {
				continue;
			}

			$priority = isset( $this->locations[ $args['location'] ]['priority'] ) && $this->locations[ $args['location'] ]['priority'] ? $this->locations[ $args['location'] ]['priority'] : 10;

			// In Content (Single).
			if ( 'content' === $args['location'] ) {

				/**
				 * Adds content to the_content.
				 *
				 * @since 0.1.0
				 *
				 * @param string $content The existing content.
				 *
				 * @return string
				 */
				add_filter( 'the_content', function( $content ) use ( $args ) {
					if ( ! ( is_main_query() && in_the_loop() ) ) {
						return $content;
					}

					$content = maigam_get_content( $content, $args );

					return $content;
				});
			}
			// In Entries (Archive).
			elseif ( 'entries' === $args['location'] ) {
				// Show CSS in the head.
				add_action( 'wp_head', [ $this, 'do_archive_css' ] );

				// Add attributes to entries-wrap.
				add_filter( 'genesis_attr_entries-wrap', [ $this, 'add_entries_wrap_atts' ], 10, 3 );

				/**
				 * Adds inline CSS and CCA markup before the closing entries-wrap element.
				 *
				 * @since 0.1.0
				 *
				 * @param string $close       The closing element.
				 * @param array  $markup_args The args Mai passes to the element.
				 *
				 * @return string
				 */
				add_filter( 'genesis_markup_entries-wrap_close', function( $close, $markup_args ) use ( $args ) {
					if ( ! $close ) {
						return $close;
					}

					if ( ! isset( $markup_args['params']['args']['context'] ) || 'archive' !== $markup_args['params']['args']['context'] ) {
						return $close;
					}

					$ad = sprintf( '<div class="maigam-ad" style="order:calc(var(--maicca-columns) * %s);">%s</div>', $args['content_count'], maigam_get_processed_content( $args['content'] ) );

					return $ad . $close;

				}, 10, 2 );
			}
			// Global, Single, Archive.
			else {

				/**
				 * Renders content via hook and priority from config.
				 *
				 * @since 0.1.0
				 *
				 * @return string
				 */
				add_action( $this->locations[ $args['location'] ]['hook'], function() use ( $args, $priority ) {
					echo maigam_get_processed_content( $args['content'] );
				}, $priority );
			 }
		}
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

	/**
	 * Displays archive CSS.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function do_archive_css() {
		static $has_css = false;

		if ( $has_css ) {
			return;
		}

		?>
		<style>
			.maigam-ad {
				flex:1 1 100%;
			}
			@media only screen and (max-width: 599px) {
				.entries-wrap {
					--maigam-columns: var(--maigam-columns-xs);
				}
			}
			@media only screen and (min-width: 600px) and (max-width: 799px) {
				.entries-wrap {
					--maigam-columns: var(--maigam-columns-sm);
				}
			}
			@media only screen and (min-width: 800px) and (max-width: 999px) {
				.entries-wrap {
					--maigam-columns: var(--maigam-columns-md);
				}
			}
			@media only screen and (min-width: 1000px) {
				.entries-wrap {
					--maigam-columns: var(--maigam-columns-lg);
				}
			}
		</style>
		<?php
		$has_css = true;
	}

	/**
	 * Adds custom properties for the column count as an integer.
	 * Mai Engine v2.22.0 changed --columns from integer to fraction, which broke Mai CCAs.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $atts        The existing element attributes.
	 * @param string $context     The element context.
	 * @param array  $markup_args The args Mai passes to the element.
	 *
	 * @return array
	 */
	function add_entries_wrap_atts( $atts, $context, $markup_args ) {
		if ( ! isset( $markup_args['params']['args']['context'] ) || 'archive' !== $markup_args['params']['args']['context'] ) {
			return $atts;
		}

		if ( ! function_exists( 'mai_get_breakpoint_columns' ) ) {
			return $atts;
		}

		// Static variable since these filters would run for each CCA.
		static $has_atts = false;

		if ( $has_atts ) {
			return $atts;
		}

		$atts['style'] = isset( $atts['style'] ) ? $atts['style'] : '';
		$columns       = array_reverse( mai_get_breakpoint_columns( $markup_args['params']['args'] ) );

		foreach ( $columns as $break => $column ) {
			$atts['style'] .= sprintf( '--maigam-columns-%s:%s;', $break, $column );
		}

		$has_atts = true;

		return $atts;
	}
}