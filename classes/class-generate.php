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
		add_action( 'load-edit.php',                          [ $this, 'admin_notice' ] );
		add_action( 'load-edit.php',                          [ $this, 'admin_notice_success' ] );
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

		if ( ! ( $screen && 'mai_ad' === $screen->post_type ) ) {
			return;
		}

		$ads = maigam_get_config( 'ad_units' );

		if ( ! $ads ) {
			return;
		}

		$slugs     = array_keys( $ads );
		$count     = count( $slugs );
		$existing  = maigam_get_ads_data();
		// $existing  = wp_list_pluck( $existing, 'post_name' );
		// $intersect = count( array_intersect( $slugs, $existing ) );

		// // Bail if we have the right amount.
		// if ( $count === $intersect ) {
		// 	return;
		// }

		// $available = ( $count - $intersect );

		// // Bail if none available.
		// if ( ! $available ) {
		// 	return;
		// }

		// add_action( 'admin_notices', function() use ( $available ) {

		// 	if ( 1 === $available ) {
		// 		$notice = sprintf( '%s %s', $available, __( 'default Mai Ad needs to be created.', 'mai-gam' ) );
		// 	} else {
		// 		$notice = sprintf( '%s %s', $available, __( 'default Mai Ads need to be created.', 'mai-gam' ) );
		// 	}

		// 	$generate_url = add_query_arg( [ 'action' => 'maigam_generate_ads_action' ], admin_url( 'admin-post.php' ) );
		// 	$generate_url = wp_nonce_url( $generate_url, 'maigam_generate_ads_action', 'maigam_generate_ads_nonce' );
		// 	$button       = sprintf( '<a class="button button-primary" href="%s">%s</a>', $generate_url, __( 'Generate Now', 'mai-gam' ) );

		// 	printf(
		// 		'<div class="notice notice-warning"><p>%s</p><p>%s</p></div>',
		// 		$notice,
		// 		$button
		// 	);
		// });
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
		$gam_ads  = $this->create_ads();
		$count    = count( $gam_ads );

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
		$created = [];

		foreach ( maigam_get_config( 'ad_units' ) as $slug => $ad ) {
			if ( $this->ad_exists( $slug ) ) {
				continue;
			}

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
	 * Checks whether the ad exists.
	 *
	 * @since 0.1.0
	 *
	 * @param string $slug Template part slug.
	 *
	 * @return bool
	 */
	function ad_exists( $slug ) {
		return false;

		// TODO: Fix this.

		// $existing  = maigam_get_ads_data();
		// $existing  = wp_list_pluck( $existing, 'post_name' );

		// return isset( $existing[ $slug ] );
	}
}
