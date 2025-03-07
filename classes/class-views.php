<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

class Mai_Publisher_Views {
	protected $api;
	protected $trending_days;
	protected $views_years;
	protected $interval;
	protected $id;
	protected $type;
	protected $url;
	protected $current;

	/**
	 * Construct the class.
	 *
	 * @return void
	 */
	function __construct() {
		$this->hooks();
	}

	/**
	 * Runs hooks.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	function hooks() {
		// Register meta.
		add_action( 'init', [ $this, 'register_meta' ] );

		// Shortcode.
		add_shortcode( 'mai_views', [ $this, 'add_shortcode' ] );

		// Mai Post Grid filters.
		add_filter( 'acf/load_field/key=mai_grid_block_query_by',                 [ $this, 'add_trending_choice' ] );
		add_filter( 'acf/load_field/key=mai_grid_block_posts_orderby',            [ $this, 'add_views_choice' ] );
		add_filter( 'acf/load_field/key=mai_grid_block_post_taxonomies',          [ $this, 'add_show_conditional_logic' ] );
		add_filter( 'acf/load_field/key=mai_grid_block_post_taxonomies_relation', [ $this, 'add_show_conditional_logic' ] );
		add_filter( 'acf/load_field/key=mai_grid_block_posts_orderby',            [ $this, 'add_hide_conditional_logic' ] );
		add_filter( 'acf/load_field/key=mai_grid_block_posts_order',              [ $this, 'add_hide_conditional_logic' ] );

		// Mai Tax Grid filters.
		add_filter( 'acf/load_field/key=mai_grid_block_tax_query_by',             [ $this, 'add_trending_choice' ] );
		add_filter( 'acf/load_field/key=mai_grid_block_tax_orderby',              [ $this, 'add_views_choice' ] );
		add_filter( 'acf/load_field/key=mai_grid_block_tax_orderby',              [ $this, 'add_hide_conditional_logic' ] );
		add_filter( 'acf/load_field/key=mai_grid_block_tax_order',                [ $this, 'add_hide_conditional_logic' ] );

		// Mai Trending Post is priorty 20. This takes over for any legacy sites still running that plugin.
		add_filter( 'mai_post_grid_query_args', [ $this, 'edit_query' ], 30, 2 );
		add_filter( 'mai_term_grid_query_args', [ $this, 'edit_query' ], 30, 2 );

		// Update.
		add_action( 'wp_ajax_maipub_views',        [ $this, 'update_views' ] );
		add_action( 'wp_ajax_nopriv_maipub_views', [ $this, 'update_views' ] );
	}

	/**
	 * Registers the post meta keys.
	 *
	 * @since 0.12.0
	 *
	 * @return void
	 */
	function register_meta() {
		register_meta( 'post', 'mai_trending', [
			'type'              => 'integer',
			'description'       => __( 'The number of views in the last n days.', 'mai-publisher' ),
			'sanitize_callback' => 'absint',
			'single'            => true,
			'show_in_rest'      => true,
		]);

		register_meta( 'post', 'mai_views', [
			'type'              => 'integer',
			'description'       => __( 'The total number of views', 'mai-publisher' ),
			'sanitize_callback' => 'absint',
			'single'            => true,
			'show_in_rest'      => true,
		]);

		register_meta( 'post', 'mai_views_updated', [
			'type'              => 'integer',
			'description'       => __( 'The last updated timestamp', 'mai-publisher' ),
			'sanitize_callback' => 'absint',
			'single'            => true,
			'show_in_rest'      => true,
		]);
	}

	/**
	 * Adds shortcode to display views.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	function add_shortcode( $atts ) {
		return maipub_get_views( $atts );
	}

	/**
	 * Adds Trending as an "Get Entries By" choice.
	 *
	 * @since 0.4.0
	 *
	 * @param array $field The existing field array.
	 *
	 * @return array
	 */
	function add_trending_choice( $field ) {
		if ( ! is_admin() ) {
			return $field;
		}

		$field['choices'][ 'trending' ] = __( 'Trending', 'mai-publisher' ) . ' (Mai Publisher)';

		return $field;
	}

	/**
	 * Adds Views as an "Order By" choice.
	 *
	 * @since 0.1.0
	 *
	 * @param array $field The existing field array.
	 *
	 * @return array
	 */
	function add_views_choice( $field ) {
		if ( ! is_admin() ) {
			return $field;
		}

		$field['choices'] = array_merge( [ 'views' => __( 'Views', 'mai-publisher' ) . ' (Mai Publisher)' ], $field['choices'] );

		return $field;
	}

	/**
	 * Adds conditional logic to show if query by is trending.
	 * This duplicates existing conditions and changes query_by from 'tax_meta' to 'trending'.
	 *
	 * @since 0.4.0
	 *
	 * @param array $field The existing field array.
	 *
	 * @return array
	 */
	function add_show_conditional_logic( $field ) {
		if ( ! is_admin() ) {
			return $field;
		}

		$conditions = [];

		foreach ( $field['conditional_logic'] as $index => $values ) {
			$condition = $values;

			if ( isset( $condition['field'] ) && 'mai_grid_block_query_by' === $condition['field'] ) {
				$condition['value']    = 'trending';
				$condition['operator'] = '==';
			}

			$conditions[] = $condition;
		};

		$field['conditional_logic'] = $conditions ? [ $field['conditional_logic'], $conditions ] : $field['conditional_logic'];

		return $field;
	}

	/**
	 * Adds conditional logic to hide if query by is trending in Mai Post Grid.
	 *
	 * @since 0.4.0
	 *
	 * @param array $field The existing field array.
	 *
	 * @return array
	 */
	function add_hide_conditional_logic( $field ) {
		if ( ! is_admin() ) {
			return $field;
		}

		$key = false !== strpos( $field['key'], '_tax_' ) ? 'mai_grid_block_tax_query_by' : 'mai_grid_block_query_by';

		$field['conditional_logic'][] = [
			'field'    => $key,
			'operator' => '!=',
			'value'    => 'trending',
		];

		return $field;
	}

	/**
	 * Modify Mai Post Grid query args.
	 *
	 * @since 0.4.0
	 *
	 * @return array
	 */
	function edit_query( $query_args, $args ) {
		if ( isset( $args['query_by'] ) && $args['query_by'] && 'trending' === $args['query_by'] ) {
			$query_args['meta_key'] = 'mai_trending';
			$query_args['orderby']  = 'meta_value_num';
			$query_args['order']    = 'DESC';
		}

		if ( isset( $args['orderby'] ) && $args['orderby'] && 'views' === $args['orderby'] ) {
			$query_args['meta_key'] = 'mai_views';
			$query_args['orderby']  = 'meta_value_num';
		}

		return $query_args;
	}

	/**
	 * Update post/term trending and popular view counts via ajax.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	function update_views() {
		// Bail if failed nonce check.
		if ( false === check_ajax_referer( 'maipub_views_nonce', 'nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'mai-publisher' ) );
			wp_die();
		}

		// Get options.
		$this->api           = maipub_get_option( 'views_api' );
		$this->trending_days = maipub_get_option( 'trending_days' );
		$this->views_years   = maipub_get_option( 'views_years' );
		$this->interval      = maipub_get_option( 'views_interval' );

		// Bail if API is disabled.
		if ( ! $this->api || 'disabled' === $this->api ) {
			wp_send_json_error( __( 'Views API is disabled.', 'mai-publisher' ) );
			wp_die();
		}

		// Bail if not Matomo or Jetpack.
		if ( ! in_array( $this->api, [ 'matomo', 'jetpack' ] ) ) {
			wp_send_json_error( __( 'Not a valid API option.', 'mai-publisher' ) );
			wp_die();
		}

		// Bail if nothing to fetch.
		if ( ! ( ( $this->trending_days || $this->views_years ) && $this->interval ) ) {
			wp_send_json_error( __( 'Missing views years, trending days, or interval.', 'mai-publisher' ) );
			wp_die();
		}

		// Get post data.
		$this->id      = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : '';
		$this->type    = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : '';
		$this->url     = isset( $_POST['url'] ) ? esc_url( $_POST['url'] ) : '';
		$this->current = isset( $_POST['current'] ) ? absint( $_POST['current'] ) : '';
		$return        = [];

		// Bail if we don't have the post data we need.
		if ( ! ( $this->id && $this->type && $this->url && $this->current ) ) {
			wp_send_json_error( __( 'Missing id, type, url, or current.', 'mai-publisher' ) );
			wp_die();
		}

		// Update updated time.
		switch ( $this->type ) {
			case 'post':
				update_post_meta( $this->id, 'mai_views_updated', $this->current );
			break;
			case 'term':
				update_term_meta( $this->id, 'mai_views_updated', $this->current );
			break;
		}

		// Get API data.
		switch ( $this->api ) {
			case 'matomo':
				$return = $this->update_views_from_matomo();
			break;
			case 'jetpack':
				$return = $this->update_views_from_jetpack();
			break;
		}

		// If error.
		if ( is_wp_error( $return ) ) {
			// Set future time. Current time + interval (minutes) x2 (in seconds).
			$future = $this->current + (($this->interval * 2) * 60);

			// Update updated time.
			switch ( $this->type ) {
				case 'post':
					update_post_meta( $this->id, 'mai_views_updated', $future );
				break;
				case 'term':
					update_term_meta( $this->id, 'mai_views_updated', $future );
				break;
			}

			// If debugging, log it to error.log.
			// if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				$permalink = 'post' === $this->type ? get_permalink( $this->id ) : get_term_link( $this->id );

				error_log( 'Mai Publisher (Views) Error: ' . (string) $return->get_error_code() . ' - ' . (string) $return->get_error_message() . ' - ' . (string) $permalink );

				// Check for additional error data.
				$error_data = $return->get_error_data();

				// If the error has additional data, log that as well
				if ( $error_data ) {
					error_log( 'Mai Publisher (Views) Data: ' . print_r( $error_data, true ) );
				}
			// }

			// Send error.
			wp_send_json_error( $return->get_error_message(), $return->get_error_code() );
			wp_die();
		}

		// Send it home.
		wp_send_json_success( $return );
		wp_die();
	}

	/**
	 * Get views from Matomo.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function update_views_from_matomo() {
		$site_url = maipub_get_option( 'matomo_url' );
		$site_id  = maipub_get_option( 'matomo_site_id' );
		$token    = maipub_get_option( 'matomo_token' );

		// Bail if no API data.
		if ( ! ( $site_url && $site_id && $token ) ) {
			return new WP_Error( 'missing_api_data', __( 'Missing Matomo API data.', 'mai-publisher' ) );
		}

		// Start API data.
		$api_url = trailingslashit( $site_url ) . 'index.php';
		$fetch   = [];
		$return = [
			'views'    => null,
			'trending' => null,
		];

		// Add trending first incase views times out.
		if ( $this->trending_days ) {
			$fetch[] = [
				'key'    => 'trending',
				'period' => 'day',
				'date'   => 'last' . $this->trending_days,
			];
		}

		// Add views.
		if ( $this->views_years ) {
			$fetch[] = [
				'key'    => 'views',
				'period' => 'year',
				'date'   => 'last' . $this->views_years,
			];
		}

		// Start API args.
		$url_count = 0;
		$api_args  = [
			'module' => 'API',
			'method' => 'API.getBulkRequest',
			'format' => 'json',
		];

		// Bundle each API hit for bulk request.
		foreach ( $fetch as $values ) {
			$string = add_query_arg( [
				'module'      => 'API',
				'method'      => 'Actions.getPageUrl',
				'idSite'      => $site_id,
				'token_auth'  => $token,
				'pageUrl'     => $this->url,
				'hideColumns' => 'label',
				'showColumns' => 'nb_visits',
				'period'      => $values['period'],
				'date'        => $values['date'],
				'format'      => 'json',
			], '' );

			// Add args.
			$api_args[ sprintf( 'urls[%s]', $url_count ) ] = urlencode( '&' . ltrim( $string, '?' ) );

			// Increment count.
			$url_count++;
		}

		// Get API url.
		$api_url = add_query_arg( $api_args, $api_url );

		// Send a GET request to the Matomo API.
		$response = wp_remote_get( $api_url, [
			'user-agent' => 'BizBudding/1.0',
		] );

		// Check for a successful request.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Get the response code.
		$code = wp_remote_retrieve_response_code( $response );

		// Bail if not a successful response.
		if ( 200 !== $code ) {
			return new WP_Error( 'matomo_api_error', wp_remote_retrieve_response_message( $response ), $code );
		}

		// Get the data.
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Bail if no data.
		if ( ! $data ) {
			return new WP_Error( 'matomo_no_data', __( 'No data returned.', 'mai-publisher' ) );
		}

		// If matomo returns an error, return it.
		if ( isset( $data['result'] ) && 'error' === $data['result'] ) {
			$message = isset( $data['message'] ) ? $data['message'] : __( 'Unknown error in class-views.php.', 'mai-publisher' );

			return new WP_Error( 'matomo_api_error', $message, $code );
		}

		// Loop through each item in the bulk request.
		foreach ( $data as $index => $row ) {
			if ( ! isset( $fetch[ $index ]['key'] ) ) {
				continue;
			}

			$key    = $fetch[ $index ]['key'];
			$visits = null;

			foreach ( $row as $values ) {
				if ( ! $values || ! isset( $values[0]['nb_visits'] ) ) {
					continue;
				}

				$visits  = is_null( $visits ) ? 0 : $visits;
				$visits += absint( $values[0]['nb_visits'] );
			}

			// Only update if not null.
			if ( ! is_null( $visits ) ) {
				// Update meta. `mai_trending` or `mai_views`.
				switch ( $this->type ) {
					case 'post':
						update_post_meta( $this->id, "mai_{$key}", $visits );
					break;
					case 'term':
						update_term_meta( $this->id, "mai_{$key}", $visits );
					break;
				}
			}

			// Add to return.
			$return[ $key ] = $visits;
		}

		return $return;
	}

	/**
	 * Get views from Jetpack.
	 *
	 * @since 0.23.0
	 *
	 * @return array
	 */
	function update_views_from_jetpack() {
		$return = [
			'views'    => null,
			'trending' => null,
		];

		// Bail if Jetpack is not active.
		if ( ! ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'stats' ) && class_exists( 'Automattic\Jetpack\Stats\WPCOM_Stats' ) ) ) {
			return new WP_Error( 'jetpack_not_active', __( 'Jetpack Stats module is not active.', 'mai-publisher' ) );
		}

		// Get views data.
		$stats = new Automattic\Jetpack\Stats\WPCOM_Stats;
		$views = $stats->get_post_views( $this->id );

		// Bail if error.
		if ( is_wp_error( $views ) ) {
			return $views;
		}

		// Get total views.
		if ( isset( $views['views'] ) ) {
			$total = absint( $views['views'] );

			update_post_meta( $this->id, 'mai_views', $total );

			$return['views'] = $total;
		}

		// Get trending views.
		if ( isset( $views['data'] ) ) {
			$i        = 1;
			$trending = null;
			$days     = array_reverse( $views['data'] );

			// Loop through days.
			foreach ( $days as $day ) {
				// Break the loop if we've reached the trending days.
				if ( $i > $this->trending_days ) {
					break;
				}

				// Skip if no count.
				if ( ! isset( $day[1] ) ) {
					continue;
				}

				// Add to the count.
				$trending  = is_null( $trending ) ? 0 : $trending;
				$trending += absint( $day[1] );

				// Increment.
				$i++;
			}

			// Set trending.
			if ( ! is_null( $trending ) ) {
				update_post_meta( $this->id, 'mai_trending', $trending );

				$return['trending'] = $trending;
			}
		}

		return $return;
	}
}