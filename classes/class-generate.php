<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_GAM_Generate_Ads {

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
		add_action( 'admin_post_maigam_generate_ads_action', [ $this, 'action' ] );
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
				$notice = sprintf( '%s %s', $missing, __( 'default Mai Ad needs to be created.', 'mai-gam' ) );
			} else {
				$notice = sprintf( '%s %s', $missing, __( 'default Mai Ads need to be created.', 'mai-gam' ) );
			}

			$generate_url = add_query_arg( [ 'action' => 'maigam_generate_ads_action' ], admin_url( 'admin-post.php' ) );
			$generate_url = wp_nonce_url( $generate_url, 'maigam_generate_ads_action', 'maigam_generate_ads_nonce' );
			$button       = sprintf( '<a class="button button-primary" href="%s">%s</a>', $generate_url, __( 'Generate Now', 'mai-gam' ) );

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

		$notice = isset( $_GET['maigam_notice'] ) ? esc_html( $_GET['maigam_notice'] ) : false;

		if ( ! $notice ) {
			return;
		}

		add_action( 'admin_notices', function() use ( $notice ) {
			printf( '<div class="notice notice-success"><p>%s</p></div>', $notice );
		});
	}

	/**
	 * Listener for generating default ads.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function action() {
		$referrer = check_admin_referer( 'maigam_generate_ads_action', 'maigam_generate_ads_nonce' );
		$nonce    = isset( $_GET[ 'maigam_generate_ads_nonce' ] ) ? esc_html( $_GET[ 'maigam_generate_ads_nonce' ] ) : null;
		$action   = isset( $_GET[ 'action' ] ) ? esc_html( $_GET[ 'action' ] ) : null;

		if ( ! ( current_user_can( 'edit_theme_options' ) && $referrer && $nonce && $action && wp_verify_nonce( $nonce, $action ) ) ) {
			wp_die(
				__( 'Mai Ads failed to generate.', 'mai-gam' ),
				__( 'Error', 'mai-gam' ),
				[
					'link_url'  => admin_url( 'edit.php?post_type=mai_ad' ),
					'link_text' => __( 'Go back.', 'mai-gam' ),
				]
			);
		}

		$redirect = admin_url( 'edit.php?post_type=mai_ad' );
		$ads      = $this->create_ads();
		$count    = count( $ads );

		switch ( $count ) {
			case 0:
				$message = __( 'Sorry, no ads are available.', 'mai-gam' );
			break;
			case 1:
				$message = sprintf( '%s %s', $count, __( 'default ads successfully created.', 'mai-gam' ) );
			break;
			default:
				$message = sprintf( '%s %s', $count, __( 'default ads successfully created.', 'mai-gam' ) );
		}

		if ( $message ) {
			$redirect = add_query_arg( 'maigam_notice', urlencode( esc_html( $message ) ), $redirect );
		}

		wp_safe_redirect( $redirect );
		exit;
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
					'post_name'    => $slug,
					'post_status'  => 'publish',
					'post_title'   => $ad['post_title'],
					'post_content' => $ad['post_content'],
					// 'menu_order'   => $ad['menu_order'],
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
		$config   = maigam_get_config( 'ad_units' );
		$existing = $this->get_existing_ads();

		if ( ! ( $config && $existing ) ) {
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
}
