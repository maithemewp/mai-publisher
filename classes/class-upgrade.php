<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Handles upgrade routines.
 */
class Mai_GAM_Upgrade {
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
		$plugin_version = MAI_GAM_VERSION;

		// Set first version.
		if ( false === maigam_get_option( 'first-version', false ) ) {
			maigam_update_option( 'first-version', $plugin_version );
		}

		$db_version = maigam_get_option( 'db-version', false );

		// Return early if at latest.
		if ( $plugin_version === $db_version ) {
			return;
		}

		// Update database version after upgrade.
		maigam_update_option( 'db-version', $plugin_version );
	}
}