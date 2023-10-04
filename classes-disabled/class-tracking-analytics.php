<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

class Mai_Publisher_Tracking {
	private $user;
	private $user_email;
	private $dimensions;

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
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function hooks() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	/**
	 * Enqueues script if we're tracking the current page.
	 * This should not be necessary yet, if we have the main Matomo header script.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function enqueue() {
		// Bail if not tracking.
		if ( ! mai_analytics_should_track() ) {
			return;
		}

		// Set user.
		$this->user = wp_get_current_user(); // Returns 0 if not logged in.

		// Set vars for JS.
		$vars = [
			'siteId'     => mai_analytics_get_option( 'site_id' ),
			'trackerUrl' => mai_analytics_get_option( 'url' ),
			'userId'     => $this->user ? $this->user->user_email : '',
			'dimensions' => $this->get_custom_dimensions(),
		];

		// If singular or a term archive (all we care about now).
		if ( is_singular() || is_category() || is_tag() || is_tax() ) {
			$trending_days = (int) mai_analytics_get_option( 'trending_days' );
			$views_days    = (int) mai_analytics_get_option( 'views_days' );
			$interval      = (int) mai_analytics_get_option( 'views_interval' );

			// If we're fetching trending or popular counts.
			if ( ( $trending_days || $views_days ) && $interval ) {
				// Get page data and current timestamp.
				$page     = mai_analytics_get_current_page();
				$current  = current_datetime()->getTimestamp();

				// Get last updated timestamp.
				if ( is_singular() ) {
					$updated = get_post_meta( $page['id'], 'mai_views_updated', true );
				} else {
					$updated = get_term_meta( $page['id'], 'mai_views_updated', true );
				}

				// If last updated timestampe is more than N minutes (converted to seconds) ago.
				if ( ! $updated || $updated < ( $current - ( $interval * 60 ) ) ) {
					$vars['ajaxUrl'] = admin_url( 'admin-ajax.php' );
					$vars['nonce']   = wp_create_nonce( 'maipub_views_nonce' );
					$vars['type']    = $page['type'];
					$vars['id']      = $page['id'];
					$vars['url']     = $page['url'];
					$vars['current'] = $current;
				}
			}
		}

		$version   = MAI_ANALYTICS_VERSION;
		$handle    = 'mai-analytics';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$file      = "assets/js/{$handle}{$suffix}.js";
		$file_path = MAI_ANALYTICS_PLUGIN_DIR . $file;
		$file_url  = MAI_ANALYTICS_PLUGIN_URL . $file;

		if ( file_exists( $file_path ) ) {
			$version .= '.' . date( 'njYHi', filemtime( $file_path ) );

			wp_enqueue_script( $handle, $file_url, [], $version, [ 'strategy' => 'async', 'in_footer' => true ] );
			wp_localize_script( $handle, 'maiAnalyticsVars', $vars );
		}
	}

	/**
	 * Gets custom dimensions.
	 *
	 * A lot of this is shared with Mai Publisher.
	 * Any changes here should be referenced there.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function get_custom_dimensions() {
		$this->dimensions = [];

		if ( ! $this->user ) {
			return;
		}

		$this->set_dimension_1();
		$this->set_dimension_2();
		$this->set_dimension_3();
		$this->set_dimension_4();
		$this->set_dimension_5();
		$this->set_dimension_6();
		$this->set_dimension_7();
		$this->set_dimension_8();
		$this->set_dimension_9();

		return $this->dimensions;
	}

	// TODO.
	function set_dimension_1() {}
	function set_dimension_2() {}
	function set_dimension_3() {}
	function set_dimension_4() {}

	/**
	 * Gets user group/membership/team.
	 *
	 * There is a filter that passes generic args for the group.
	 * This leaves us open to use dimension 5 for any sort of User Grouping we want, not just WooCommerce.
	 * We could use WP User Groups (taxonomy) or anything else, without modifying the plugin code.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function set_dimension_5() {
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

		// Handles group as custom dimension.
		if ( $group ) {
			mai_analytics_debug( sprintf( 'Group name / %s', $group ) );

			// Set the Group data.
			$this->dimensions[5] = $group;

		} else {
			mai_analytics_debug( 'No Group name found' );
		}

		return;
	}

	/**
	 * Gets content age.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function set_dimension_6() {
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

		$this->dimensions[6] = $range . ' days';
	}

	/**
	 * Gets post category.
	 *
	 * @todo Add support for CPT and Custom Taxonomies?
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function set_dimension_7() {
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		$primary = maipub_get_primary_term( 'category', get_the_ID() );

		if ( ! $primary ) {
			return;
		}

		$this->dimensions[7] = $primary->name; // Term name.
	}

	/**
	 * Gets content length.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function set_dimension_8() {
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

		$content = mai_analytics_get_processed_content( $content );
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

		$this->dimensions[8] = $range . ' words';
	}

	/**
	 * Gets content type.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function set_dimension_9() {
		// Uses readable name as the type. 'Post' instead of 'post'.
		$type = mai_analytics_get_current_page( 'name' );

		if ( ! $type ) {
			return;
		}

		$this->dimensions[9] = $type;
	}

	/**
	 * Sets membership plan IDs in args.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	function set_membership_plan_ids( $args ) {
		$plan_ids = mai_analytics_get_membership_plan_ids( $this->user->ID );

		// Handles plan IDs.
		if ( $plan_ids ) {
			$args['plan_ids'] = $plan_ids;
			mai_analytics_debug( sprintf( 'Woo Membership Plan IDs / %s', implode( ', ', $args['plan_ids'] ) ) );
		} else {
			mai_analytics_debug( 'No Woo Membership Plans' );
		}

		return $args;
	}

	/**
	 * Sets user taxonomies.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	function set_user_taxonomies( $args ) {
		$taxonomies = mai_analytics_get_user_taxonomies( $this->user->ID );

		// If taxonomies.
		if ( $taxonomies ) {
			foreach ( $taxonomies as $name => $values ) {
				$args[ $name ] = $values;
				mai_analytics_debug( sprintf( 'User Taxonomy / %s / %s', $name, implode( ', ', array_values( $values ) ) ) );
			}
		}

		return $args;
	}
}
