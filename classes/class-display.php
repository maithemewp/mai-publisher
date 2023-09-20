<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Display {
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
		add_action( 'wp_head',            [ $this, 'header' ] );
		add_action( 'wp_footer',          [ $this, 'footer' ], 20 );
	}

	/**
	 * Enqueue JS for GPT ads.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function run() {
		$ads    = maipub_get_ads();
		$domain = maipub_get_gam_domain();
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

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
			$file = "assets/js/mai-publisher-ads{$suffix}.js";

			// Google Ad Manager GPT.
			wp_enqueue_script( 'google-gpt', 'https://securepubads.g.doubleclick.net/tag/js/gpt.js', [], $this->get_file_data( $file, 'version' ), [ 'strategy' => 'async' ] );

			// Sovrn.
			wp_enqueue_script( 'sovrn-beacon', '//ap.lijit.com/www/sovrn_beacon_standalone/sovrn_standalone_beacon.js?iid=472780', [], $this->get_file_data( $file, 'version' ), [ 'strategy' => 'async' ] );

			// Prebid.
			wp_enqueue_script( 'prebid-js', 'https://cdn.jsdelivr.net/npm/prebid.js@8.15.0/dist/not-for-prod/prebid.min.js', [], '8.15.0', [ 'strategy' => 'async' ] ); // https://www.jsdelivr.com/package/npm/prebid.js
			wp_localize_script( 'prebid-js', 'maiPubPrebidVars', $this->get_ortb2_vars() );

			// Mai Publisher.
			wp_enqueue_script( 'mai-publisher-ads', $this->get_file_data( $file, 'url' ), [ 'google-gpt', 'sovrn-beacon', 'prebid-js' ], $this->get_file_data( $file, 'version' ), false ); // Asyncing broke ads.
			wp_localize_script( 'mai-publisher-ads', 'maiPubAdsVars',
				[
					'gamDomain' => $this->domain,
					'ads'       => $gam_ads,
				]
			);
		}

		// Display the ads.
		$this->render();
	}

	/**
	 * Outputs header.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function header() {
		$header = maipub_get_option( 'header' );

		if ( ! $header ) {
			return;
		}

		echo $header;
	}

	/**
	 * Outputs footer.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function footer() {
		$footer = maipub_get_option( 'footer' );

		if ( ! $footer ) {
			return;
		}

		echo $footer;
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
		$config = maipub_get_config( 'ad_units' );

		foreach ( $ad_ids as $slug ) {
			if ( ! isset( $config[ $slug ] ) ) {
				continue;
			}

			if ( isset( $counts[ $slug ] ) ) {
				$counts[ $slug ]++;
				$ads[ $slug . '-' . $counts[ $slug ] ] = [
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

		foreach ( $this->ads as $ad ) {
			if ( isset( $ad['ad_ids'] ) && $ad['ad_ids'] ) {
				$ad_ids = array_merge( $ad_ids, $ad['ad_ids'] );
			}
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
			// If Mai Ad block with an ID.
			if ( 'acf/mai-ad' === $block['blockName'] && isset( $block['attrs']['data']['id'] ) && ! empty( $block['attrs']['data']['id'] ) ) {
				// Get the post object.
				$post = get_post( $block['attrs']['data']['id'] );

				// If we have a post, get the ad unit IDs from the post content.
				if ( $post ) {
					$ad_ids = array_merge( $ad_ids, $this->get_block_ad_ids( $post->post_content, 1 ) );
				}
			}
			// If Mai Ad unit block with an ID.
			elseif ( 'acf/mai-ad-unit' === $block['blockName'] && isset( $block['attrs']['data']['id'] ) && ! empty( $block['attrs']['data']['id'] ) ) {
				// Loop through the $count and add the ad ID to the array.
				for ( $i = 0; $i < $count; $i++ ) {
					$ad_ids[] = $block['attrs']['data']['id'];
				}
			}

			// If we have inner blocks, recurse.
			if ( isset( $block['innerBlocks'] ) && $block['innerBlocks'] ) {
				$ad_ids = array_merge( $ad_ids, $this->get_block_ad_ids( $block['innerBlocks'], $count ) );
			}
		}

		return $ad_ids;
	}

	/**
	 * Gets localized args for OpenRTB.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function get_ortb2_vars() {
		$site = [
			'name'          => get_bloginfo( 'name' ),
			'domain'        => (string) maipub_get_url_host( home_url() ),
			'page'          => is_singular() ? get_permalink() : home_url( add_query_arg( [] ) ),   // URL of the page where the impression will be shown.
			'kwarray'       => [ 'sports', 'news', 'rumors', 'gossip' ],                            // Array of keywords about the site.
			'mobile'        => 1,                                                                   // Indicates if the site has been programmed to optimize layout when viewed on mobile devices, where 0 = no, 1 = yes.
			'privacypolicy' => 1,                                                                   // Indicates if the site has a privacy policy, where 0 = no, 1 = yes.
			'content'       => [],
		];


		// The taxonomy in use. Refer to the AdCOM list List: Category Taxonomies for values. If no cattax field is supplied IAB Content Category Taxonomy 1.0 is assumed.
		// @link https://iabtechlab.com/standards/content-taxonomy/
		// cattax: 7, // IAB Tech Lab Content Taxonomy 3.0.
		// Array of IABTL content categories of the site. The taxonomy to be used is defined by the cattax field.
		// cat: 483, // Sitewode category. Sports.
		// Array of IABTL content categories that describe the current section of the site. The taxonomy to be used is defined by the cattax field.
		// sectioncat: 547, // Category. Basketball.
		// Array of IABTL content categories that describe the current page or view of the site.
		// pagecat: 547, // Child category. Basketball.


		return $site;
	}

	/**
	 * Display ads.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function render() {
		$locations = maipub_get_locations();

		foreach ( $this->ads as $args ) {
			// Bail if no location. This may happen for manually added ad blocks.
			if ( ! isset( $args['location'] ) ) {
				continue;
			}

			// Bail if not a registered location.
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

					$content = maipub_get_content( $content, $args );

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

					$ad = sprintf( '<div class="maipub-ad" style="order:calc(var(--maicca-columns) * %s);">%s</div>', $args['content_count'], maipub_get_processed_content( $args['content'] ) );

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
					echo maipub_get_processed_content( $args['content'] );
				}, $priority );
			 }
		}
	}

	/**
	 * Gets file data.
	 *
	 * @since 0.1.0
	 *
	 * @param string $file The file path name.
	 * @param string $key The specific key to return
	 *
	 * @return array|string
	 */
	function get_file_data( $file, $key = '' ) {
		static $cache = null;

		if ( ! is_null( $cache ) && isset( $cache[ $file ] ) ) {
			if ( $key ) {
				return $cache[ $file ][ $key ];
			}

			return $cache[ $file ];
		}

		$file_path      = MAI_PUBLISHER_DIR . $file;
		$file_url       = MAI_PUBLISHER_URL . $file;
		$version        = MAI_PUBLISHER_VERSION . '.' . date( 'njYHi', filemtime( $file_path ) );
		$cache[ $file ] = [
			'path'    => $file_path,
			'url'     => $file_url,
			'version' => $version,
		];

		if ( $key ) {
			return $cache[ $file ][ $key ];
		}

		return $cache[ $file ];
	}

	/**
	 * Gets file suffix.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	function get_suffix() {
		static $suffix = null;

		if ( ! is_null( $suffix ) ) {
			return $suffix;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		return $suffix;
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
			.maipub-ad {
				flex:1 1 100%;
			}
			@media only screen and (max-width: 599px) {
				.entries-wrap {
					--maipub-columns: var(--maipub-columns-xs);
				}
			}
			@media only screen and (min-width: 600px) and (max-width: 799px) {
				.entries-wrap {
					--maipub-columns: var(--maipub-columns-sm);
				}
			}
			@media only screen and (min-width: 800px) and (max-width: 999px) {
				.entries-wrap {
					--maipub-columns: var(--maipub-columns-md);
				}
			}
			@media only screen and (min-width: 1000px) {
				.entries-wrap {
					--maipub-columns: var(--maipub-columns-lg);
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
			$atts['style'] .= sprintf( '--maipub-columns-%s:%s;', $break, $column );
		}

		$has_atts = true;

		return $atts;
	}
}