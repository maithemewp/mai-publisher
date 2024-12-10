<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

class Mai_Publisher_Tracking {
	private $user;
	private $user_id;
	private $user_email;
	private $site_dimensions   = [];
	private $global_dimensions = [];

	/**
	 * Construct the class.
	 *
	 * @return void
	 */
	function __construct() {
		$this->hooks();
	}

	/**
	 * Runs frontend hooks.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	function hooks() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ], 99 );
	}

	/**
	 * Enqueues script if we're tracking the current page.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	function enqueue() {
		// Can run this too early so it's inside the callback.
		if ( ! maipub_should_track() ) {
			return;
		}

		$suffix = maipub_get_suffix();
		$file   = "build/js/mai-publisher-analytics{$suffix}.js";

		wp_enqueue_script( 'mai-publisher-analytics', maipub_get_file_data( $file, 'url' ), [], maipub_get_file_data( $file, 'version' ), [ 'strategy' => 'async', 'in_footer' => true ] );
		wp_localize_script( 'mai-publisher-analytics', 'maiPubAnalyticsVars', $this->get_vars() );
	}

	/**
	 * Get localized vars for JS.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	function get_vars() {
		$vars             = [];
		$this->user       = wp_get_current_user();
		$this->user_id    = $this->user ? $this->user->ID : 0;
		$this->user_email = $this->user ? $this->user->user_email : '';
		$site_url         = maipub_get_option( 'matomo_url', false );
		$site_id          = maipub_get_option( 'matomo_site_id', false );
		$matomo_enabled   = maipub_get_option( 'matomo_enabled', false );
		$matomo_enabled   = $matomo_enabled && $site_url && $site_id;
		$analytics        = [];

		// Handle site tracking vars.
		if ( $matomo_enabled ) {
			$vars['analytics'] = [];
			$dimensions        = $this->get_site_dimensions();
			$analytics         = [
				'url'    => trailingslashit( $site_url ),
				'id'     => absint( $site_id ),
				'toPush' => [],
			];

			// If user email, set it as setUserId.
			if ( $this->user_email ) {
				$analytics['toPush'][] = [ 'setUserId', $this->user_email ];
			}

			// If dimensions, set them.
			if ( $dimensions ) {
				foreach ( $dimensions as $index => $value ) {
					$analytics['toPush'][] = [ 'setCustomDimension', $index, $value ];
				}
			}

			$analytics['toPush'][] = [ 'enableLinkTracking' ];
			$analytics['toPush'][] = [ 'trackPageView' ];
			$analytics['toPush'][] = [ 'trackVisibleContentImpressions' ];
			// $analytics['toPush'][] = [ 'trackAllContentImpressions' ];
		}

		// Get views API.
		$views_api  = maipub_get_option( 'views_api' );
		$is_matomo  = 'matomo'  === $views_api;
		$is_jetpack = 'jetpack' === $views_api;

		// If an api we can use.
		if ( $is_matomo || $is_jetpack ) {
			$trending_days = (int) maipub_get_option( 'trending_days', false );
			$views_years   = (int) maipub_get_option( 'views_years', false );
			$interval      = (int) maipub_get_option( 'views_interval', false );

			// If we're fetching trending or popular counts.
			if ( ( $trending_days || $views_years ) && $interval ) {
				// Get page data and current timestamp.
				$page    = maipub_get_current_page();
				$current = current_datetime()->getTimestamp();
				$updated = null;

				// If we have an ID, get the last updated timestamp.
				if ( $page['id'] ) {
					switch ( $views_api ) {
						case 'matomo':
							if ( is_singular() ) {
								$updated = get_post_meta( $page['id'], 'mai_views_updated', true );
							} else {
								$updated = get_term_meta( $page['id'], 'mai_views_updated', true );
							}
						break;
						case 'jetpack';
							if ( is_singular() ) {
								$updated = get_post_meta( $page['id'], 'mai_views_updated', true );
							}
						break;
					}
				}

				// If some value other than null.
				if ( ! is_null( $updated ) ) {
					// If last updated timestamp is more than N minutes (converted to seconds) ago.
					if ( ! $updated || $updated < ( $current - ( $interval * 60 ) ) ) {
						$analytics['ajaxUrl'] = admin_url( 'admin-ajax.php' );
						$analytics['body']    = [
							'action'  => 'maipub_views',
							'nonce'   => wp_create_nonce( 'maipub_views_nonce' ),
							'type'    => $page['type'],
							'id'      => $page['id'],
							'url'     => $page['url'],
							'current' => $current,
						];
					}
				}
			}
		}

		// Add site analytics.
		if ( $analytics ) {
			$vars['analytics'][] = $analytics;
		}

		// Handle global tracking vars.
		if ( maipub_get_option( 'matomo_enabled_global', false ) ) {
			$vars['analytics'] = isset( $vars['analytics'] ) ? $vars['analytics'] : [];
			$dimensions        = $this->get_global_dimensions();
			$analytics         = [
				'url'    => 'https://bizbudding.info/',
				'id'     => 1,
				'toPush' => [],
			];

			if ( $dimensions ) {
				foreach ( $dimensions as $index => $value ) {
					$analytics['toPush'][] = [ 'setCustomDimension', $index, $value ];
				}
			}

			$analytics['toPush'][] = [ 'trackPageView' ];

			// Add global analytics.
			$vars['analytics'][] = $analytics;
		}

		return $vars;
	}

	/**
	 * Gets custom dimensions.
	 *
	 * @since 0.3.0
	 *
	 * @return array
	 */
	function get_site_dimensions() {
		$this->site_dimensions = [];

		$this->set_site_dimension_1();
		$this->set_site_dimension_2();
		$this->set_site_dimension_3();
		$this->set_site_dimension_4();
		$this->set_site_dimension_5();
		$this->set_site_dimension_6();
		$this->set_site_dimension_7();
		$this->set_site_dimension_8();
		$this->set_site_dimension_9();

		return apply_filters( 'mai_publisher_site_dimensions', $this->site_dimensions );
	}

	/**
	 * Gets global custom dimensions.
	 *
	 * @since 0.3.0
	 *
	 * @return array
	 */
	function get_global_dimensions() {
		$this->global_dimensions = [];

		$this->set_global_dimension_1();
		$this->set_global_dimension_2();
		$this->set_global_dimension_3();
		$this->set_global_dimension_4();
		$this->set_global_dimension_5();
		$this->set_global_dimension_6();
		$this->set_global_dimension_7();
		$this->set_global_dimension_8();
		$this->set_global_dimension_9();

		return apply_filters( 'mai_publisher_global_dimensions', $this->global_dimensions );
	}

	// TODO.
	function set_site_dimension_1() {}
	function set_site_dimension_2() {}
	function set_site_dimension_3() {}
	function set_site_dimension_4() {}

	/**
	 * Gets user group/membership/team.
	 *
	 * There is a filter that passes generic args for the group.
	 * This leaves us open to use dimension 5 for any sort of User Grouping we want, not just WooCommerce.
	 * We could use WP User Groups (taxonomy) or anything else, without modifying the plugin code.
	 *
	 * @since 0.3.0
	 *
	 * @return array
	 */
	function set_site_dimension_5() {
		$args = [];
		$args = $this->set_membership_plan_ids( $args );
		$args = $this->set_user_taxonomies( $args );

		/**
		 * Filter to manually add group per-site.
		 *
		 * @param string $name    The group name (empty for now).
		 * @param int    $user_id The logged in user ID.
		 * @param array  $args    The user data args.
		 *
		 * @return string
		 */
		$name  = '';
		$group = apply_filters( 'mai_publisher_group_name', $name, $this->user_id, $args );
		$group = trim( esc_html( $group ) );

		if ( ! $group ) {
			return;
		}

		// Set the Group data.
		$this->site_dimensions[5] = $group;
	}

	/**
	 * Gets readable content age label.
	 *
	 * @since 0.3.0
	 *
	 * @return array
	 */
	function set_site_dimension_6() {
		$age = maipub_get_content_age();

		if ( ! $age ) {
			return;
		}

		$this->site_dimensions[6] = $age[1];
	}

	/**
	 * Gets post category.
	 *
	 * @todo TODO: Add support for CPT and Custom Taxonomies?
	 *
	 * @since 0.3.0
	 *
	 * @return array
	 */
	function set_site_dimension_7() {
		$category = $this->get_content_category();

		if ( ! $category ) {
			return;
		}

		$this->site_dimensions[7] = $category;
	}

	/**
	 * Gets content length.
	 *
	 * @since 0.3.0
	 *
	 * @return array
	 */
	function set_site_dimension_8() {
		$content = '';
		$range   = false;

		// If single post, add the content.
		if ( is_singular() ) {
			$content .= get_post_field( 'post_content', get_the_ID() );
		}

		// Get ads from Mai Archive Pages.
		elseif ( function_exists( 'maiap_get_archive_page' ) ) {
			$pages = [
				maiap_get_archive_page( true ),
				maiap_get_archive_page( false ),
			];

			$pages = array_filter( $pages );

			if ( $pages ) {
				foreach ( $pages as $page ) {
					$content .= $page->post_content;
				}
			}
		}

		// Bail if no content.
		if ( ! $content ) {
			return;
		}

		// Strip shortcodes, html tags, and count words.
		// Can't process content because it parses blocks and throws off
		// things like Mai Post Grid that counts displayed posts IDs.
		$content = strip_shortcodes( $content );
		$content = wp_strip_all_tags( $content );
		$count   = str_word_count( $content );
		$ranges  = [
			[ 0, 499 ],
			[ 500, 999 ],
			[ 1000, 1999 ],
		];

		foreach ( $ranges as $index => $values ) {
			if ( ! filter_var( $count, FILTER_VALIDATE_INT,
				[
					'options' => [
						'min_range' => $values[0],
						'max_range' => $values[1],
					],
				],
			)) {
				continue;
			}

			$range = sprintf( '%s-%s', $values[0], $values[1] );
		}

		if ( ! $range && $count > 1999 ) {
			$range = '2000+';
		}

		if ( ! $range ) {
			return;
		}

		$this->site_dimensions[8] = $range . ' words';
	}

	/**
	 * Gets content type.
	 *
	 * @since 0.3.0
	 *
	 * @return array
	 */
	function set_site_dimension_9() {
		// Uses readable name as the type. 'Post' instead of 'post'.
		$type = maipub_get_current_page( 'name' );

		if ( ! $type ) {
			return;
		}

		$this->site_dimensions[9] = $type;
	}

	/**
	 * Sets membership plan IDs in args.
	 *
	 * @since 0.3.0
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	function set_membership_plan_ids( $args ) {
		$plan_ids = $this->get_membership_plan_ids( $this->user_id );

		// Handles plan IDs.
		if ( $plan_ids ) {
			$args['plan_ids'] = $plan_ids;
		}

		return $args;
	}

	/**
	 * Gets membership plan IDs.
	 * Cached incase we need to call this again later on same page load.
	 *
	 * @since 0.3.0
	 *
	 * @param int $user_id The logged in user ID.
	 *
	 * @return array|int[]
	 */
	function get_membership_plan_ids( $user_id ) {
		static $cache = [];

		if ( isset( $cache[ $user_id ] ) ) {
			return $cache[ $user_id ];
		}

		$cache[ $user_id ] = [];

		// Bail if Woo Memberships is not active.
		if ( ! ( is_user_logged_in( $user_id ) && class_exists( 'WooCommerce' ) && function_exists( 'wc_memberships_get_user_memberships' ) ) ) {
			return $cache[ $user_id ];
		}

		// Get active memberships.
		$memberships = wc_memberships_get_user_memberships( $user_id, [ 'status' => [ 'active', 'complimentary' ] ] );

		if ( $memberships ) {
			// Get active membership IDs.
			$cache[ $user_id ] = wp_list_pluck( $memberships, 'plan_id' );
		}

		return $cache[ $user_id ];
	}

	/**
	 * Sets user taxonomies.
	 *
	 * @since 0.3.0
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	function set_user_taxonomies( $args ) {
		$taxonomies = $this->get_user_taxonomies( $this->user_id );

		// If taxonomies.
		if ( $taxonomies ) {
			foreach ( $taxonomies as $name => $values ) {
				$args[ $name ] = $values;
			}
		}

		return $args;
	}

	/**
	 * Gets user taxonomies.
	 * Cached incase we need to call this again later on same page load.
	 *
	 * Returns:
	 * [
	 *   'taxonomy_one' => [
	 *     123 => 'Term Name 1',
	 *     321 => 'Term Name 2',
	 *   ],
	 *   'taxonomy_two' => [
	 *     456 => 'Term Name 3',
	 *     654 => 'Term Name 4',
	 *   ],
	 * ]
	 *
	 * @since 0.3.0
	 *
	 * @return array
	 */
	function get_user_taxonomies( $user_id = 0 ) {
		static $cache = [];

		if ( isset( $cache[ $user_id ] ) ) {
			return $cache[ $user_id ];
		}

		$cache[ $user_id ] = [];
		$taxonomies        = get_object_taxonomies( 'user' );

		// Bail if no taxonomies registered on users.
		if ( ! $taxonomies ) {
			return $cache[ $user_id ];
		}

		foreach ( $taxonomies as $taxonomy ) {
			$terms = wp_get_object_terms( $user_id, $taxonomy );

			if ( $terms && ! is_wp_error( $terms ) ) {
				$cache[ $user_id ][ $taxonomy ] = wp_list_pluck( $terms, 'name', 'term_id' );
			}
		}

		return $cache[ $user_id ];
	}

	// TODO.
	function set_global_dimension_1() {}
	function set_global_dimension_2() {}
	function set_global_dimension_3() {}
	function set_global_dimension_4() {}
	function set_global_dimension_5() {}

	/**
	 * Websites.
	 * Sets site domain.
	 *
	 * @since 0.3.0
	 *
	 * @return array
	 */
	function set_global_dimension_6() {
		$host = parse_url( get_site_url(), PHP_URL_HOST );
		$host = ltrim( $host, 'www.' );

		$this->global_dimensions[6] = esc_html( $host );
	}

	/**
	 * Sets post IAB category.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	function set_global_dimension_7() {
		$iab = maipub_get_iab_category();

		// Maybe fallback to sitewide category.
		if ( ! $iab ) {
			$iab = maipub_get_option( 'category', false );
		}

		// Get category label from ID.
		$categories = maipub_get_all_iab_categories();
		$iab        = isset( $categories[ $iab ] ) ? $categories[ $iab ] : false;

		if ( ! $iab ) {
			return;
		}

		$this->global_dimensions[7] = esc_html( ltrim( $iab, '– ' ) );
	}

	/**
	 * Sets content category.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	function set_global_dimension_8() {
		$term = false;

		if ( is_singular( 'post' ) ) {
			$term = maipub_get_primary_term( 'category', get_the_ID() );

		} elseif ( is_category() || is_tag() || is_tax() ) {
			$object = get_queried_object();
			$term   = $object && $object instanceof WP_Term ? $object : 0;
		}

		if ( ! $term ) {
			return;
		}

		$this->global_dimensions[8] = $term->name; // Term name.
	}

	/**
	 * Gets content type.
	 *
	 * @since 0.3.0
	 *
	 * @return array
	 */
	function set_global_dimension_9() {
		// Uses readable name as the type. 'Post' instead of 'post'.
		$type = maipub_get_current_page( 'name' );

		if ( ! $type ) {
			return;
		}

		$this->global_dimensions[9] = $type;
	}

	/**
	 * Gets content category name.
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	function get_content_category() {
		static $category = null;

		if ( ! is_null( $category ) ) {
			return $category;
		}

		$term = false;

		if ( is_singular( 'post' ) ) {
			$term = maipub_get_primary_term( 'category', get_the_ID() );

		} elseif ( is_category() || is_tag() || is_tax() ) {
			$object = get_queried_object();
			$term   = $object && $object instanceof WP_Term ? $object : 0;
		}

		if ( ! $term ) {
			$category = '';
			return $category;
		}

		$category = $term->name; // Term name.

		return $category;
	}
}
