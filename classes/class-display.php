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
			wp_enqueue_script( 'sovrn-beacon', 'https://ap.lijit.com/www/sovrn_beacon_standalone/sovrn_standalone_beacon.js?iid=472780', [], $this->get_file_data( $file, 'version' ), [ 'strategy' => 'async' ] );

			// Prebid.
			wp_enqueue_script( 'prebid-js', '//cdn.jsdelivr.net/npm/prebid.js@latest/dist/not-for-prod/prebid.js', [], '8.16.0', [ 'strategy' => 'async' ] ); // https://www.jsdelivr.com/package/npm/prebid.js
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

		// If tracking with Matomo.
		if ( maipub_get_option( 'matomo' ) ) {
			$file = "assets/js/mai-publisher-analytics{$suffix}.js";

			wp_enqueue_script( 'mai-publisher-analytics', $this->get_file_data( $file, 'url' ), [ 'google-gpt' ], $this->get_file_data( $file, 'version' ), true );
			wp_localize_script( 'mai-publisher-analytics', 'maiPubAnalyticsVars',
				[
					'token' => maipub_get_option( 'matomo_token' ),
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
	 * OpenRTB 2.6 spec / Content Taxonomy.
	 * @link https://iabtechlab.com/wp-content/uploads/2022/04/OpenRTB-2-6_FINAL.pdf
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function get_ortb2_vars() {
		/**
		 * 3.2.13 Object: Site
		 */
		$site = [
			'name'          => get_bloginfo( 'name' ),
			'domain'        => (string) maipub_get_url_host( home_url() ),
			'page'          => is_singular() ? get_permalink() : home_url( add_query_arg( [] ) ),
			// 'kwarray'       => [ 'sports', 'news', 'rumors', 'gossip' ],
			'mobile'        => 1,
			'privacypolicy' => 1,
			// 'content'       => [],
		];

		$cattax      = 7; // IAB Tech Lab Content Taxonomy 3.0.
		$cat         = maipub_get_option( 'category' ); // Sitewide category.
		$section_cat = ''; // Category.
		$page_cat    = ''; // Child category.
		$term_id     = 0;

		if ( is_singular( 'post' ) ) {
			$post_id = get_the_ID();
			$primary = $this->get_primary_term( 'category', $post_id );
			$term_id = $primary ? $primary->term_id : 0;

			/**
			 * 3.2.16 Object: Content
			 */
			$site['content'] = [
				'id'       => $post_id,
				'title'    => get_the_title(),
				'url'      => get_permalink(),
				'context'  => 5,                                        // Text (i.e., primarily textual document such as a web page, eBook, or news article.
				// 'kwarray'  => [ 'philadelphia 76ers', 'doc rivers' ],   // Array of keywords about the content.
				// 'language' => '',                                       // Content language using ISO-639-1-alpha-2. Only one of language or langb should be present.
				// 'langb'    => '',                                       // Content language using IETF BCP 47. Only one of language or langb should be present.
				/**
				 * 3.2.21 Object: Data
				 */
				// 'data' => [],
			];

		} elseif ( is_category() ) {
			$object    = get_queried_object();
			$term_id   = $object && $object instanceof WP_Term ? $object->term_id : 0;
		}

		if ( $term_id ) {
			$hierarchy = $this->get_term_hierarchy( $term_id );

			if ( $hierarchy ) {
				$page_cat    = array_pop( $hierarchy );
				$section_cat = array_pop( $hierarchy );
				$section_cat = $section_cat ?: $page_cat;

				// Check for IATB category.
				$page_cat    = $page_cat ? get_term_meta( $term_id, 'maipub_category', true ) : 0;
				$section_cat = $section_cat ? get_term_meta( $term_id, 'maipub_category', true ) : 0;
			}
		}

		if ( $cat || $section_cat || $page_cat ) {
			$site['cattax']            = $cattax;
			$site['content']['cattax'] = $cattax;

			if ( $cat ) {
				$site['cat']            = [ $cat ];
				$site['content']['cat'] = [ $cat ];
			}

			if ( $section_cat ) {
				$site['sectioncat']            = [ $section_cat ];
				$site['content']['sectioncat'] = [ $section_cat ];
			}

			if ( $page_cat ) {
				$site['pagecat']            = [ $page_cat ];
				$site['content']['pagecat'] = [ $page_cat ];
			}
		}

		return $site;
	}

	/**
	 * Gets the primary term of a post, by taxonomy.
	 * If Yoast Primary Term is used, return it,
	 * otherwise fallback to the first term.
	 *
	 * @version 1.3.0
	 *
	 * @since 0.1.0
	 *
	 * @link https://gist.github.com/JiveDig/5d1518f370b1605ae9c753f564b20b7f
	 * @link https://gist.github.com/jawinn/1b44bf4e62e114dc341cd7d7cd8dce4c
	 * @author Mike Hemberger @JiveDig.
	 *
	 * @param string $taxonomy The taxonomy to get the primary term from.
	 * @param int    $post_id  The post ID to check.
	 *
	 * @return WP_Term|false The term object or false if no terms.
	 */
	function get_primary_term( $taxonomy = 'category', $post_id = false ) {
		// Bail if no taxonomy.
		if ( ! $taxonomy ) {
			return false;
		}

		// If no post ID, set it.
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		// Bail if no post ID.
		if ( ! $post_id ) {
			return false;
		}

		// Setup caching.
		static $cache = null;

		// Maybe return cached value.
		if ( is_array( $cache ) ) {
			if ( isset( $cache[ $taxonomy ][ $post_id ] ) ) {
				return $cache[ $taxonomy ][ $post_id ];
			}
		} else {
			$cache = [];
		}

		// If checking for WPSEO.
		if ( class_exists( 'WPSEO_Primary_Term' ) ) {

			// Get the primary term.
			$wpseo_primary_term = new WPSEO_Primary_Term( $taxonomy, $post_id );
			$wpseo_primary_term = $wpseo_primary_term->get_primary_term();

			// If we have one, return it.
			if ( $wpseo_primary_term ) {
				$cache[ $taxonomy ][ $post_id ] = get_term( $wpseo_primary_term );
				return $cache[ $taxonomy ][ $post_id ];
			}
		}

		// We don't have a primary, so let's get all the terms.
		$terms = get_the_terms( $post_id, $taxonomy );

		// Bail if no terms.
		if ( ! $terms || is_wp_error( $terms ) ) {
			$cache[ $taxonomy ][ $post_id ] = false;
			return $cache[ $taxonomy ][ $post_id ];
		}

		// Get the first, and store in cache.
		$cache[ $taxonomy ][ $post_id ] = reset( $terms );

		// Return the first term.
		return $cache[ $taxonomy ][ $post_id ];
	}

	/**
	 * Gets the hierarchy of a term.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $term_id
	 * @param string $taxonomy
	 *
	 * @return int[]
	 */
	function get_term_hierarchy( $term_id, $taxonomy = 'category' ) {
		$term_ids  = [ $term_id ];
		$parent_id = wp_get_term_taxonomy_parent_id( $term_id, $taxonomy );

		if ( $parent_id ) {
			$term_ids = array_merge( $this->get_term_hierarchy( $parent_id, $taxonomy ), $term_ids );
		}

		return $term_ids;
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