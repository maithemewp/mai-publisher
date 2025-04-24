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
		add_action( 'acf/init',       [ $this, 'register_styles' ] );
		add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_item' ], 9999 );
		add_action( 'get_header',     [ $this, 'maybe_run' ] );
	}

	/**
	 * Register styles.
	 *
	 * @since 0.11.0
	 *
	 * @return void
	 */
	function register_styles() {
		$asset_data = maipub_get_asset_data( 'mai-publisher.css', 'style' );
		wp_register_style( 'mai-publisher', $asset_data['url'], [], $asset_data['version'] );
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
		// Bail if ads are disabled.
		if ( 'disabled' === maipub_get_option( 'ad_mode', false ) ) {
			return;
		}

		$this->ads       = maipub_get_page_ads();
		$this->locations = maipub_get_locations();

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'wp_head',            [ $this, 'header' ] );
		add_action( 'wp_footer',          [ $this, 'footer' ], 20 );
		add_filter( 'the_content',        [ $this, 'content' ] );

		$this->render();
	}

	/**
	 * Enqueue style.
	 *
	 * @since 0.11.0
	 *
	 * @return void
	 */
	function enqueue() {
		// Bail if not the data we need.
		if ( ! ( $this->ads && $this->locations ) ) {
			return;
		}

		wp_enqueue_style( 'mai-publisher' );
	}

	/**
	 * Outputs header.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function header() {
		$header = trim( (string) maipub_get_option( 'header' ) );

		foreach ( $this->ads as $ad ) {
			if ( isset( $ad['header'] ) && $ad['header'] ) {
				$header .= trim( (string) $ad['header'] );
			}
		}

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
		$footer = trim( (string) maipub_get_option( 'footer' ) );

		foreach ( $this->ads as $ad ) {
			if ( isset( $ad['footer'] ) && $ad['footer'] ) {
				$footer .= trim( (string) $ad['footer'] );
			}
		}

		if ( ! $footer ) {
			return;
		}

		echo $footer;
	}

	/**
	 * Adds targetting and tracking attributes to manually inserted ads.
	 *
	 * @since 0.23.0
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	function content( $content ) {
		// Set location targets. Auto displayed ads are handled in `class-output.php`.
		return maipub_add_location_attributes( $content, 'content' );
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

			// Skip if a location handled directly in the dom or with custom implementation (entries).
			if ( in_array( $args['location'], [ 'content', 'entries', 'recipe', 'comments', 'before_sidebar_content', 'after_sidebar_content' ] ) ) {
				continue;
			}

			// TODO: Should entries and other DOMDocument locations be handled here now that we added open/close/count?

			// Set opening and closing markup, count, and priority.
			$open     = isset( $this->locations[ $args['location'] ]['open'] ) ? $this->locations[ $args['location'] ]['open'] : '';
			$close    = isset( $this->locations[ $args['location'] ]['close'] ) ? $this->locations[ $args['location'] ]['close'] : '';
			$count    = isset( $this->locations[ $args['location'] ]['content_count'] ) ? $this->locations[ $args['location'] ]['content_count'] : '';
			$count    = $count ? array_filter( explode( ',', $count ) ) : [];
			$priority = isset( $this->locations[ $args['location'] ]['priority'] ) && $this->locations[ $args['location'] ]['priority'] ? $this->locations[ $args['location'] ]['priority'] : 10;

			/**
			 * Renders content via hook and priority from config.
			 *
			 * @since 0.1.0
			 *
			 * @return string
			 */
			add_action( $this->locations[ $args['location'] ]['hook'], function() use ( $args, $open, $close, $count, $priority ) {
				// Maybe check counts.
				if ( $count ) {
					static $i = 0;
					$i++;

					// Bail if not in count.
					if ( ! in_array( $i, $count ) ) {
						return;
					}
				}

				echo $open;
				echo $args['content']; // Content is already processed via `maipub_get_page_ads()`.
				echo $close;

			}, $priority );
		}
	}
}
