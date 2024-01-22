<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Mai_Engine {

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
		add_action( 'admin_footer',         [ $this, 'settings_generate_file' ] );
		add_action( 'customize_save_after', [ $this, 'customizer_generate_file' ] );
	}

	/**
	 * Maybe generate CSS for Mai Engine native ads.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function settings_generate_file() {
		$screen = get_current_screen();

		if ( ! $screen || 'mai_ad_page_settings' !== $screen->id ) {
			return;
		}

		maipub_generate_mai_engine_css();
	}

	/**
	 * Force generate CSS.
	 *
	 * @since TBD
	 *
	 * @param WP_Customize_Manager $customizer The customizer manager.
	 *
	 * @return void
	 */
	function customizer_generate_file( $customizer ) {
		// Maybe generate. This will generate if the file does not exist.
		$generate = maipub_generate_mai_engine_css();

		// Bail if generated on save.
		if ( $generate ) {
			return;
		}

		foreach ( $customizer->changeset_data() as $key => $value ) {
			if ( str_starts_with( $key, 'mai-engine[color-' ) ) {
				$generate = true;
				break;
			}

			if ( str_starts_with( $key, 'mai-engine[body-typography' ) ) {
				$generate = true;
				break;
			}

			if ( str_starts_with( $key, 'mai-engine[heading-typography' ) ) {
				$generate = true;
				break;
			}
		}

		// Bail if not saving anything that matters.
		if ( ! $generate ) {
			return;
		}

		// Force generate.
		maipub_generate_mai_engine_css( true );
	}
}