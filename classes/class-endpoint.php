<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Endpoint {
	/**
	 * Construct the class.
	 */
	function __construct() {
		$this->hooks();
	}

	/**
	 * Add hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function hooks() {
		add_filter( 'rest_api_init', [ $this, 'register_endpoint' ] );
	}

	/**
	 * Register the endpoints.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function register_endpoint() {
		register_rest_route( 'mai-publisher/v1', 'seller', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'handle_request' ],
			'permission_callback' => '__return_true',
		] );
	}

	/**
	 * Handle the request.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function handle_request( $request ) {
		$data = [
			'ad_mode'                => (string) maipub_get_option( 'ad_mode', false ),
			'gam_domain'             => (string) maipub_get_option( 'gam_domain', false ),
			'gam_network_code'       => (int) maipub_get_option( 'gam_network_code', false ),
			'gam_sellers_id'         => (string) maipub_get_option( 'gam_sellers_id', false ),
			'gam_sellers_name'       => (string) maipub_get_option( 'gam_sellers_name', false ),
			'gam_targets'            => (string) maipub_get_option( 'gam_targets', false ),
			'category'               => (string) maipub_get_option( 'category', false ),
			'amazon_uam_enabled'     => (bool) maipub_get_option( 'amazon_uam_enabled', false ),
			'matomo_enabled_global'  => (bool) maipub_get_option( 'matomo_enabled_global', false ),
			'matomo_enabled'         => (bool) maipub_get_option( 'matomo_enabled', false ),
			'matomo_url'             => (string) maipub_get_option( 'matomo_url', false ),
			'matomo_site_id'         => (int) maipub_get_option( 'matomo_site_id', false ),
			'views_api'              => (string) maipub_get_option( 'views_api', false ),
			'views_years'            => (int) maipub_get_option( 'views_years', false ),
			'views_interval'         => (int) maipub_get_option( 'views_interval', false ),
			'trending_days'          => (int) maipub_get_option( 'trending_days', false ),
		];

		// Set plugin version.
		$data['version'] = MAI_PUBLISHER_VERSION;

		// Ad mode defaults to active.
		$data['ad_mode'] = $data['ad_mode'] ? $data['ad_mode'] : 'active';

		// IAB category.
		$categories       = maipub_get_all_iab_categories();
		$data['category'] = $data['category'] ? $data['category'] . ' - ' . $categories[ $data['category'] ] : 'all';

		return $data;
	}
}