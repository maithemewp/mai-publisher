<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Generate_Ads {

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
		add_action( 'load-edit.php',                         [ $this, 'admin_notice' ] );
		add_action( 'load-edit.php',                         [ $this, 'admin_notice_success' ] );
		add_filter( 'post_row_actions',                      [ $this, 'content_import_link' ], 10, 2);
		add_action( 'admin_post_maipub_generate_ads_action', [ $this, 'post_action' ] );
	}

	/**
	 * Adds admin notice to content areas.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function admin_notice() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;

		// Bail if not the admin CPT archive.
		if ( ! ( $screen && 'mai_ad' === $screen->post_type ) ) {
			return;
		}

		// Get missing ad count.
		$missing = count( $this->get_missing_ads() );

		// Bail if none missing.
		if ( ! $missing ) {
			return;
		}

		// Add admin notice.
		add_action( 'admin_notices', function() use ( $missing ) {

			if ( 1 === $missing ) {
				$notice = sprintf( '%s %s', $missing, __( 'default Mai Ad needs to be created.', 'mai-publisher' ) );
			} else {
				$notice = sprintf( '%s %s', $missing, __( 'default Mai Ads need to be created.', 'mai-publisher' ) );
			}

			$generate_url = add_query_arg( [ 'action' => 'maipub_generate_ads_action' ], admin_url( 'admin-post.php' ) );
			$generate_url = wp_nonce_url( $generate_url, 'maipub_generate_ads_action', 'maipub_generate_ads_nonce' );
			$button       = sprintf( '<a class="button button-primary" href="%s">%s</a>', $generate_url, __( 'Generate Now', 'mai-publisher' ) );

			printf(
				'<div class="notice notice-warning"><p>%s</p><p>%s</p></div>',
				$notice,
				$button
			);
		});
	}

	/**
	 * Adds admin notice to content areas.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function admin_notice_success() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;

		// Bail if not the admin CPT archive.
		if ( ! ( $screen && 'mai_ad' === $screen->post_type ) ) {
			return;
		}

		$notice = isset( $_GET['maipub_notice'] ) ? esc_html( $_GET['maipub_notice'] ) : false;

		if ( ! $notice ) {
			return;
		}

		add_action( 'admin_notices', function() use ( $notice ) {
			printf( '<div class="notice notice-success"><p>%s</p></div>', $notice );
		});
	}

	/**
	 * Create link to reimport content for each post.
	 *
	 * @since 0.4.0
	 *
	 * @param array   $actions The existing actions.
	 * @param WP_Post $post    The post object.
	 *
	 * @return array
	 */
	function content_import_link( $actions, $post ) {
		if ( 'mai_ad' !== $post->post_type ) {
			return $actions;
		}

		$config = $this->get_ads_json_config();

		// Bail if slug is not in our config.
		if ( ! isset( $config[ $post->post_name ] ) ) {
			return $actions;
		}

		// Check if disabling reimport.
		$disable = get_post_meta( $post->ID, 'maipub_disable_reimport_content', true );

		// If disabled.
		if ( $disable ) {
			$actions['import_content'] = __( 'Reimport Disabled', 'mai-publisher' );
		}
		// Add reimport link.
		else {
			$generate_url              = add_query_arg( [ 'action' => 'maipub_generate_ads_action', 'id' => $post->ID ], admin_url( 'admin-post.php' ) );
			$generate_url              = wp_nonce_url( $generate_url, 'maipub_generate_ads_action', 'maipub_generate_ads_nonce' );
			$actions['import_content'] = sprintf( '<a href="%s">%s</a>', $generate_url, __( 'Reimport Content', 'mai-publisher' ) );
		}

		return $actions;
	}

	/**
	 * Listener for generating default ads.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function post_action() {
		$referrer = check_admin_referer( 'maipub_generate_ads_action', 'maipub_generate_ads_nonce' );
		$nonce    = isset( $_GET[ 'maipub_generate_ads_nonce' ] ) ? esc_html( $_GET[ 'maipub_generate_ads_nonce' ] ) : null;
		$action   = isset( $_GET[ 'action' ] ) ? esc_html( $_GET[ 'action' ] ) : null;

		if ( ! ( current_user_can( 'edit_theme_options' ) && $referrer && $nonce && $action && wp_verify_nonce( $nonce, $action ) ) ) {
			wp_die(
				__( 'Mai Ads failed to generate.', 'mai-publisher' ),
				__( 'Error', 'mai-publisher' ),
				[
					'link_url'  => admin_url( 'edit.php?post_type=mai_ad' ),
					'link_text' => __( 'Go back.', 'mai-publisher' ),
				]
			);
		}

		$redirect = admin_url( 'edit.php?post_type=mai_ad' );
		$post_id  = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : null;

		// If we have a post ID and this post ID is not disabled from reimporting.
		if ( $post_id && ! (bool) get_post_meta( $post_id, 'maipub_disable_reimport_content', true ) ) {
			$ads    = $this->import_content( $post_id );
			$action = __( 'updated', 'mai-publisher' );
		}
		// Create all ad.
		else {
			$ads    = $this->create_ads();
			$action = __( 'created', 'mai-publisher' );
		}

		// Get ads count.
		$count = count( $ads );

		// Set message text.
		switch ( $count ) {
			case 0:
				$message = __( 'Sorry, no ads are available.', 'mai-publisher' );
			break;
			case 1:
				$message = sprintf( '%s %s %s', $count, __( 'default ad successfully', 'mai-publisher' ), $action );
			break;
			default:
				$message = sprintf( '%s %s %s', $count, __( 'default ads successfully', 'mai-publisher' ), $action );
		}

		if ( $message ) {
			$redirect = add_query_arg( 'maipub_notice', urlencode( esc_html( $message ) ), $redirect );
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Import content for a single ad.
	 *
	 * @since 0.4.0
	 *
	 * @param int $post_id The ad post ID.
	 *
	 * @return array
	 */
	function import_content( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return [];
		}

		$slug   = $post->post_name;
		$config = $this->get_ads_json_config();

		if ( ! isset( $config[ $slug ] ) ) {
			return [];
		}

		// Update post content.
		wp_update_post(
			[
				'ID'           => $post_id,
				'post_content' => $config[ $slug ]['post_content'],
			]
		);

		return [ $post_id => $slug ];
	}

	/**
	 * Creates default ads from config.
	 * Skips existing ads.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function create_ads() {
		$created  = [];
		$generate = $this->get_missing_ads();

		foreach ( $generate as $slug => $ad ) {
			$post_id = wp_insert_post(
				[
					'post_type'    => 'mai_ad',
					'post_status'  => 'draft',
					'post_name'    => $slug,
					'post_title'   => $ad['post_title'],
					'post_content' => $ad['post_content'],
					'menu_order'   => $ad['menu_order'],
					'meta_input'   => $ad['meta_input'],
				]
			);

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			$created[ $post_id ] = $slug;
		}

		return $created;
	}

	/**
	 * Get missing ads.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function get_missing_ads() {
		$ads      = [];
		$config   = $this->get_ads_json_config();
		$existing = $this->get_existing_ads();

		if ( ! $config ) {
			return $ads;
		}

		// Get missing ads.
		$slugs    = array_keys( $config );
		$existing = array_keys( $existing );
		$diff     = array_diff( $slugs, $existing );

		// Get ads from config that match diff.
		$matches = array_filter( $slugs, function( $slug ) use ( $diff ) {
			return in_array( $slug, $diff );
		});

		// Get ads from config.
		foreach ( $matches as $slug ) {
			if ( ! isset( $config[ $slug ] ) ) {
				continue;
			}

			$ads[ $slug ] = $config[ $slug ];
		}

		return $ads;
	}

	/**
	 * Get existing ads from DB.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function get_existing_ads() {
		$existing = [];

		$query = new WP_Query(
			[
				'post_type'              => 'mai_ad',
				'posts_per_page'         => 500,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'suppress_filters'       => false, // https://github.com/10up/Engineering-Best-Practices/issues/116
				'orderby'                => 'menu_order',
				'order'                  => 'ASC',
			]
		);

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) : $query->the_post();
				$existing[ get_post_field( 'post_name', get_the_ID() ) ] = get_post();
			endwhile;
		}
		wp_reset_postdata();

		return $existing;
	}

	/**
	 * Gets the ads config.
	 *
	 * @since 0.4.0
	 *
	 * @return array
	 */
	function get_ads_json_config() {
		static $cache = null;

		if ( ! is_null( $cache ) ) {
			return $cache;
		}

		// Get ads, allow filtering, set cache.
		$config = json_decode( file_get_contents( MAI_PUBLISHER_DIR . '/ads.json' ), true );
		$config = apply_filters( 'mai_publisher_default_ads', $config );
		$cache  = $config;

		return $cache;
	}
}
