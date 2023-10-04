<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

class Mai_Publisher_Tracking {
	private $user              = null;
	private $user_email        = null;
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
		$file   = "assets/js/mai-publisher-analytics{$suffix}.js";

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
		$vars       = [];
		$this->user = wp_get_current_user();

		if ( maipub_get_option( 'matomo_enabled', false ) ) {
			$site_url = maipub_get_option( 'matomo_url', false );
			$site_id  = maipub_get_option( 'matomo_site_id', false );

			if ( $site_url && $site_id ) {
				$vars['analytics'] = [];
				$dimensions        = $this->get_site_dimensions();
				$analytics         = [
					'url'  => trailingslashit( $site_url ),
					'id'   => absint( $site_id ),
					'push' => [],
				];

				if ( $this->user->ID ) {
					$analytics['push'][] = [ 'setUserId', $this->user->user_email ];
				}

				if ( $dimensions ) {
					foreach ( $dimensions as $index => $value ) {
						$analytics['push'][] = [ 'setCustomDimension', $index, $value ];
					}
				}

				$analytics['push'][] = [ 'enableLinkTracking' ];
				$analytics['push'][] = [ 'trackPageView' ];
				$analytics['push'][] = [ 'trackVisibleContentImpressions' ];
				// $analytics['push'][] = [ 'trackAllContentImpressions' ];

				// Add site analytics.
				$vars['analytics'][] = $analytics;

				// If singular or a term archive (all we care about now).
				if ( is_singular() || is_category() || is_tag() || is_tax() ) {
					$trending_days = (int) maipub_get_option( 'trending_days', false );
					$views_days    = (int) maipub_get_option( 'views_days', false );
					$interval      = (int) maipub_get_option( 'views_interval', false );

					// If we're fetching trending or popular counts.
					if ( ( $trending_days || $views_days ) && $interval ) {
						// Get page data and current timestamp.
						$page    = $this->get_current_page();
						$current = current_datetime()->getTimestamp();

						// Get last updated timestamp.
						if ( is_singular() ) {
							$updated = get_post_meta( $page['id'], 'mai_views_updated', true );
						} else {
							$updated = get_term_meta( $page['id'], 'mai_views_updated', true );
						}

						// If last updated timestampe is more than N minutes (converted to seconds) ago.
						if ( ! $updated || $updated < ( $current - ( $interval * 60 ) ) ) {
							$vars['ajaxUrl'] = admin_url( 'admin-ajax.php' );
							$vars['body']    = [
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
		}

		if ( maipub_get_option( 'matomo_enabled_global', false ) ) {
			$vars['analytics'] = isset( $vars['analytics'] ) ? $vars['analytics'] : [];
			$dimensions        = $this->get_global_dimensions();
			$analytics         = [
				'url'  => 'https://bizbudding.info/',
				'id'   => 1,
				'push' => [],
			];

			if ( $dimensions ) {
				foreach ( $dimensions as $index => $value ) {
					$analytics['push'][] = [ 'setCustomDimension', $index, $value ];
				}
			}

			$analytics['push'][] = [ 'trackPageView' ];

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

		return $this->site_dimensions;
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

		return $this->global_dimensions;
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
		$group = apply_filters( 'mai_publisher_group_name', $name, $this->user->ID, $args );
		$group = trim( esc_html( $group ) );

		if ( ! $group ) {
			return;
		}

		// Set the Group data.
		$this->site_dimensions[5] = $group;
	}

	/**
	 * Gets content age.
	 *
	 * @since 0.3.0
	 *
	 * @return array
	 */
	function set_site_dimension_6() {
		$date = get_the_date( 'F j, Y' );

		if ( ! $date ) {
			return;
		}

		$range  = false;
		$date   = new DateTime( $date );
		$today  = new DateTime( 'now' );
		$days   = $today->diff( $date )->format( '%a' );
		$ranges = [
			[ 0, 29 ],
			[ 30, 89 ],
			[ 90, 179 ],
			[ 180, 364 ],
			[ 367, 729 ],
		];

		foreach ( $ranges as $index => $values ) {
			if ( ! filter_var( $days, FILTER_VALIDATE_INT,
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

		if ( ! $range && $days > 729 ) {
			$range = '2000+';
		}

		if ( ! $range ) {
			return;
		}

		$this->site_dimensions[6] = $range . ' days';
	}

	/**
	 * Gets post category.
	 *
	 * @todo Add support for CPT and Custom Taxonomies?
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
		$range   = false;
		$content = '';

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

		if ( ! $content ) {
			return;
		}

		$content = maipub_get_processed_content( $content );
		$count   = str_word_count( strip_tags( $content ) );
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
		$type = $this->get_current_page( 'name' );

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
		$plan_ids = $this->get_membership_plan_ids( $this->user->ID );

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
		if ( ! ( class_exists( 'WooCommerce' ) && function_exists( 'wc_memberships_get_user_memberships' ) ) ) {
			return $cache[ $user_id ];
		}

		// Get active memberships.
		$memberships = wc_memberships_get_user_memberships( $user_id, array( 'status' => 'active' ) );

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
		$taxonomies = $this->get_user_taxonomies( $this->user->ID );

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

		$this->global_dimensions[1] = esc_html( $host );
	}

	/**
	 * Sets post IAB category.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	function set_global_dimension_7() {
		$iab = $primary = false;

		if ( is_singular( 'post' ) ) {
			$primary = maipub_get_primary_term( 'category', get_the_ID() );

		} elseif ( is_category() ) {
			$object  = get_queried_object();
			$primary = $object && $object instanceof WP_Term ? $object : 0;
		}

		if ( $primary ) {
			$iab = get_term_meta( $primary->term_id, 'maipub_category', true );
		}

		if ( ! $iab ) {
			$iab = maipub_get_option( 'category', false );
		}

		if ( ! $iab ) {
			return;
		}

		// Get category label from ID.
		$categories = maipub_get_all_categories();
		$iab        = isset( $categories[ $iab ] ) ? $categories[ $iab ] : false;

		if ( ! $iab ) {
			return;
		}

		$this->global_dimensions[2] = esc_html( ltrim( $iab, '– ' ) );
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

		$this->global_dimensions[3] = $term->name; // Term name.
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
		$type = $this->get_current_page( 'name' );

		if ( ! $type ) {
			return;
		}

		$this->global_dimensions[4] = $type;
	}

	/**
	 * Get current page data.
	 *
	 * @since 0.3.0
	 *
	 * @param string $key
	 *
	 * @return array|string
	 */
	function get_current_page( $key = '' ) {
		static $data = null;

		if ( ! is_null( $data ) ) {
			return $key ? $data[ $key ] : $data;
		}

		$data = [
			'type' => '',
			'name' => '',
			'id'   => '',
			'url'  => '',
		];

		// Single post.
		if ( is_singular() ) {
			$object = get_post_type_object( get_post_type() );

			if ( $object ) {
				$data['type'] = 'post';
				$data['name'] = $object->labels->singular_name; // Singular name.
				$data['id']   = get_the_ID();
				$data['url']  = get_permalink();
			}
		}
		// Post type archive.
		elseif ( is_home() ) {
			$object = get_post_type_object( 'post' );

			if ( $object ) {
				$post_id      = absint( get_option( 'page_for_posts' ) );
				$data['name'] = $object->label; // Plural name.
				$data['id']   = $post_id;
				$data['url']  = $post_id ? get_permalink( $post_id ) : get_home_url();
			}
		}
		// Custom post type archive.
		elseif ( is_post_type_archive() ) {
			$object = get_post_type_object( get_post_type() );

			if ( $object ) {
				$data['name'] = $object->label; // Plural name.
				$data['url']  = get_post_type_archive_link( $object->name );
			}
		}
		// Taxonomy archive.
		elseif ( is_category() || is_tag() || is_tax() ) {
			$object = get_queried_object();

			if ( $object  ) {
				$taxonomy = get_taxonomy( $object->taxonomy );

				if ( $taxonomy ) {
					$data['type'] = 'term';
					$data['name'] = $taxonomy->labels->singular_name; // Singular name.
					$data['id']   = $object->term_id;
					$data['url']  = get_term_link( $object );
				}
			}
		}
		// Date archives.
		elseif ( is_date() || is_year() || is_month() || is_day() || is_time() ) {
			$data['name'] = 'Date';
		}
		// Author archives.
		elseif ( is_author() ) {
			$data['name'] = 'Author';
		}
		// Search results.
		elseif ( is_search() ) {
			$data['name'] = 'Search';
		}

		return $key ? $data[ $key ] : $data;
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
