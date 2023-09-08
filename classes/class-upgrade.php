<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Handles upgrade routines.
 */
class Mai_Publisher_Upgrade {
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
		add_action( 'admin_init', [ $this, 'do_upgrade' ] );
	}

	/**
	 * Run setting upgrades during engine update.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function do_upgrade() {
		$plugin_version = MAI_PUBLISHER_VERSION;

		// If no first version.
		if ( is_null( maipub_get_option( 'first-version', false ) ) ) {
			// Set first version and default label.
			maipub_update_option( 'first-version', $plugin_version );
			maipub_update_option( 'label', __( 'Sponsored', 'mai-publisher' ) );
		}

		$db_version = maipub_get_option( 'db-version', false );

		// Return early if at latest.
		if ( $plugin_version === $db_version ) {
			return;
		}

		// Update database version after upgrade.
		maipub_update_option( 'db-version', $plugin_version );
	}
}