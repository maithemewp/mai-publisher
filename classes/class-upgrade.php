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
		$version_plugin = MAI_PUBLISHER_VERSION;
		$version_db     = (string) maipub_get_option( 'version_db', false );

		// If no first version.
		if ( ! maipub_get_option( 'version_first', false ) ) {
			// Set first version and default label.
			maipub_update_option( 'version_first', $version_plugin );
			maipub_update_option( 'label', __( 'Sponsored', 'mai-publisher' ) );
		}

		// Return early if at latest.
		if ( $version_plugin === $version_db ) {
			return;
		}

		// Run upgrade routines.
		if ( version_compare( $version_db, '1.6.0', '<' ) ) {
			$this->upgrade_1_6_0();
		}

		// Update database version after upgrade.
		maipub_update_option( 'version_db', $version_plugin );
	}

	/**
	 * Upgrade to 1.6.0.
	 * This update changes active mode from empty/null to 'active'.
	 *
	 * @since 1.6.0
	 *
	 * @return void
	 */
	function upgrade_1_6_0() {
		$mode = maipub_get_option( 'ad_mode', false );

		if ( ! $mode ) {
			maipub_update_option( 'ad_mode', 'active' );
		}
	}
}