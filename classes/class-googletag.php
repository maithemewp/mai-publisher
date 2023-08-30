<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_GAM_GoogleTag {

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
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_script' ], 0 );
	}

	/**
	 * Enqueue JS for GPT ads.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function enqueue_script() {
		$ads = maigam_get_ads();

		if ( ! $ads ) {
			return;
		}

		foreach( $ads as $ad ) {

		}
		$domain   = maiam_get_gam_domain();

		if ( ! ( $slot_ids && $domain ) ) {
			return;
		}

		wp_enqueue_script( 'google-gpt', 'https://securepubads.g.doubleclick.net/tag/js/gpt.js', [],  $this->get_file_data( 'version' ), false );
		wp_enqueue_script( 'mai-ads-manager', $this->get_file_data( 'url' ), [ 'google-gpt' ],  $this->get_file_data( 'version' ), false );
		wp_localize_script( 'mai-ads-manager', 'maiAdsHelperVars',
			[
				'slot_ids' => $slot_ids,
				'domain'   => $domain,
			]
		);
	}
}