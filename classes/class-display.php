<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Display {
	protected $ads;
	protected $locations;

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
		add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_item' ], 9999 );
		add_action( 'get_header',     [ $this, 'maybe_run' ] );
	}

	/**
	 * Add links to toolbar.
	 *
	 * @since 0.9.0
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar object.
	 *
	 * @return void
	 */
	function add_admin_bar_item( $wp_admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$wp_admin_bar->add_node(
			[
				'id'     => 'mai-ads',
				'parent' => 'site-name',
				'title'  => __( 'Mai Ads', 'mai-publisher' ),
				'href'   => admin_url( 'edit.php?post_type=mai_ad' ),
				'meta'   => [
					'title' => __( 'Edit Mai Ads', 'mai-publisher' ),
				],
			]
		);
	}

	/**
	 * Check if ads are active.
	 *
	 * @since 0.11.0
	 *
	 * @return void
	 */
	function maybe_run() {
		if ( 'disabled' === maipub_get_option( 'ad_mode', false ) ) {
			return;
		}

		$this->ads       = maipub_get_page_ads();
		$this->locations = maipub_get_locations();

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'wp_head',            [ $this, 'header' ] );
		add_action( 'wp_footer',          [ $this, 'footer' ], 20 );

		$this->render();
	}

	/**
	 * Enqueue style.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function enqueue() {
		// Bail if not the data we need.
		if ( ! ( $this->ads && $this->locations ) ) {
			return;
		}

		$suffix = maipub_get_suffix();
		$file   = "assets/css/mai-publisher{$suffix}.css";

		wp_enqueue_style( 'mai-publisher', maipub_get_file_data( $file, 'url' ), [], maipub_get_file_data( $file, 'version' ) );
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
	 * Display ads.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function render() {
		// Bail if not the data we need.
		if ( ! ( $this->ads && $this->locations ) ) {
			return;
		}

		foreach ( $this->ads as $args ) {
			// Skip if no location. This may happen for manually added ad blocks.
			if ( ! isset( $args['location'] ) ) {
				continue;
			}

			// Skip if not a registered location.
			if ( ! isset( $this->locations[ $args['location'] ] ) ) {
				continue;
			}

			// Skip if a location handled directly in the dom.
			if ( in_array( $args['location'], [ 'content', 'entries', 'recipe', 'comments' ] ) ) {
				continue;
			}

			// Set priority.
			$priority = isset( $this->locations[ $args['location'] ]['priority'] ) && $this->locations[ $args['location'] ]['priority'] ? $this->locations[ $args['location'] ]['priority'] : 10;

			/**
			 * Renders content via hook and priority from config.
			 *
			 * @since 0.1.0
			 *
			 * @return string
			 */
			add_action( $this->locations[ $args['location'] ]['hook'], function() use ( $args, $priority ) {
				echo maipub_get_processed_ad_content( $args['content'] );
			}, $priority );
		}
	}
}
