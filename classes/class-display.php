<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_GAM_Display {
	protected $ads;
	protected $domain;

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

		// Bail if no ads.
		if ( ! $ads ) {
			return;
		}

		// Set properties.
		$this->ads    = $ads;
		$this->domain = $domain;

		// Get GAM ads.
		$gam_ads = $this->get_gam_ads();

		// If we have GAM ad IDs, enqueue the JS.
		if ( $gam_ads ) {
			wp_enqueue_script( 'google-gpt', 'https://securepubads.g.doubleclick.net/tag/js/gpt.js', [],  $this->get_file_data( 'version' ), false );
			wp_enqueue_script( 'mai-gam', $this->get_file_data( 'url' ), [ 'google-gpt' ],  $this->get_file_data( 'version' ), false );
			wp_localize_script( 'mai-gam', 'maiGAMVars',
				[
					'domain' => $this->domain,
					'ads'    => $gam_ads,
				]
			);
		}

		// Display the ads.
		$this->render();
	}

	/**
	 * Get GAM ads for JS.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function get_gam_ads() {
		$ads    = [];
		$counts = [];
		$ad_ids = $this->domain ? $this->get_ad_ids() : [];
		$config = maigam_get_config( 'ad_units' );

		foreach ( $ad_ids as $slug ) {
			if ( ! isset( $config[ $slug ] ) ) {
				continue;
			}

			if ( isset( $counts[ $slug ] ) ) {
				$slug_number                       = $counts[ $slug ] + 1;
				$counts[ $slug ]                   = $slug_number;
				$ads[ $slug . '-' . $slug_number ] = [
					'id'           => $slug,
					'sizes'        => $config[ $slug ]['sizes'],
					'sizesDesktop' => $config[ $slug ]['sizes_desktop'],
					'sizesTablet'  => $config[ $slug ]['sizes_tablet'],
					'sizesMobile'  => $config[ $slug ]['sizes_mobile'],
				];
			} else {
				$counts[ $slug ] = 1;
				$ads[ $slug ]    = [
					'id'           => $slug,
					'sizes'        => $config[ $slug ]['sizes'],
					'sizesDesktop' => $config[ $slug ]['sizes_desktop'],
					'sizesTablet'  => $config[ $slug ]['sizes_tablet'],
					'sizesMobile'  => $config[ $slug ]['sizes_mobile'],
				];
			}
		}

		return $ads;
	}

	/**
	 * Get the GAM ad IDs from ads.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function get_ad_ids() {
		$ad_ids = [];

		foreach ( $this->ads as $ad ) {
			$count  = isset( $ad['content_count'] ) && is_array( $ad['content_count'] ) ? count( $ad['content_count'] ) : 1;
			$ad_ids = array_merge( $ad_ids, $this->get_block_ad_ids( $ad['content'], $count ) );
		}

		return $ad_ids;
	}

	/**
	 * Get the GAM ad IDs from blocks in the ads.
	 *
	 * @since 0.1.0
	 *
	 * @param string|array $input The content or blocks.
	 * @param int          $count The number of times the ad is shown.
	 *
	 * @return array
	 */
	function get_block_ad_ids( $input, $count ) {
		$ad_ids = [];
		$blocks = is_array( $input ) ? $input : parse_blocks( $input );

		foreach ( $blocks as $block ) {
			if ( 'acf/mai-ad-unit' === $block['blockName'] && isset( $block['attrs']['data']['id'] ) && ! empty( $block['attrs']['data']['id'] ) ) {
				// Loop through the $count and add the ad ID to the array.
				for ( $i = 0; $i < $count; $i++ ) {
					$ad_ids[] = $block['attrs']['data']['id'];
				}
			}

			if ( isset( $block['innerBlocks'] ) && $block['innerBlocks'] ) {
				$ad_ids = array_merge( $ad_ids, $this->get_block_ad_ids( $block['innerBlocks'], $count ) );
			}
		}

		return $ad_ids;
	}

	/**
	 * Display ads.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function render() {
		$locations = maigam_get_locations();

		foreach ( $this->ads as $args ) {
			// Bail if no registered location.
			if ( ! isset( $locations[ $args['location'] ] ) ) {
				continue;
			}

			$priority = isset( $locations[ $args['location'] ]['priority'] ) && $locations[ $args['location'] ]['priority'] ? $locations[ $args['location'] ]['priority'] : 10;

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
				add_action( $locations[ $args['location'] ]['hook'], function() use ( $args, $priority ) {
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