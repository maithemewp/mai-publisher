<?php

/**
 * Plugin Name:     Mai GAM
 * Plugin URI:      https://bizbudding.com
 * Description:     Manage Google Ad Manager ads in Mai Theme and beyond.
 * Version:         0.1.0
 *
 * Author:          BizBudding
 * Author URI:      https://bizbudding.com
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// Must be at the top of the file.
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * Main Mai_GAM_Plugin Class.
 *
 * @since 0.1.0
 */
final class Mai_GAM_Plugin {

	/**
	 * @var Mai_GAM_Plugin The one true Mai_GAM_Plugin
	 *
	 * @since 0.1.0
	 */
	private static $instance;

	/**
	 * Main Mai_GAM_Plugin Instance.
	 *
	 * Insures that only one instance of Mai_GAM_Plugin exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   0.1.0
	 * @static  var array $instance
	 * @uses    Mai_GAM_Plugin::setup_constants() Setup the constants needed.
	 * @uses    Mai_GAM_Plugin::includes() Include the required files.
	 * @uses    Mai_GAM_Plugin::hooks() Activate, deactivate, etc.
	 * @see     Mai_GAM_Plugin()
	 * @return  object | Mai_GAM_Plugin The one true Mai_GAM_Plugin
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup.
			self::$instance = new Mai_GAM_Plugin;
			// Methods.
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->hooks();
		}
		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mai-gam' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mai-gam' ), '1.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function setup_constants() {
		// Plugin version.
		if ( ! defined( 'MAI_GAM_VERSION' ) ) {
			define( 'MAI_GAM_VERSION', '0.1.0' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'MAI_GAM_DIR' ) ) {
			define( 'MAI_GAM_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Folder URL.
		if ( ! defined( 'MAI_GAM_URL' ) ) {
			define( 'MAI_GAM_URL', plugin_dir_url( __FILE__ ) );
		}
	}

	/**
	 * Include required files.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function includes() {
		// Include vendor libraries.
		require_once __DIR__ . '/vendor/autoload.php';
		// Includes.
		foreach ( glob( MAI_GAM_DIR . 'includes/' . '*.php' ) as $file ) { include $file; }
		// Classes.
		foreach ( glob( MAI_GAM_DIR . 'classes/' . '*.php' ) as $file ) { include $file; }
		// Blocks.
		// include MAI_GAM_DIR . 'blocks/ad/block.php';
		include MAI_GAM_DIR . 'blocks/ad-unit/block.php';
		// Instantiate classes.
		$settings      = new Mai_GAM_Settings;
		$admin         = new Mai_GAM_Admin;
		$metabox       = new Mai_GAM_Ad_Field_Group;
		$fields        = new Mai_GAM_Ad_Fields;
		$generate      = new Mai_GAM_Generate_Ads;
		// $ad_block      = new Mai_GAM_Ad_Block;
		$ad_unit_block = new Mai_GAM_Ad_Unit_Block;
		$display       = new Mai_GAM_Display;
	}

	/**
	 * Run the hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'plugins_loaded', [ $this, 'updater' ] );
		add_action( 'init',           [ $this, 'register_content_types' ] );

		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
	}

	/**
	 * Setup the updater.
	 *
	 * composer require yahnis-elsts/plugin-update-checker
	 *
	 * @since 0.1.0
	 *
	 * @uses https://github.com/YahnisElsts/plugin-update-checker/
	 *
	 * @return void
	 */
	public function updater() {
		// Bail if plugin updater is not loaded.
		if ( ! class_exists( 'YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
			return;
		}

		// Setup the updater.
		$updater = PucFactory::buildUpdateChecker( 'https://github.com/maithemewp/mai-gam/', __FILE__, 'mai-gam' );

		// Set the branch that contains the stable release.
		$updater->setBranch( 'main' );

		// Maybe set github api token.
		if ( defined( 'MAI_GITHUB_API_TOKEN' ) ) {
			$updater->setAuthentication( MAI_GITHUB_API_TOKEN );
		}

		// Add icons for Dashboard > Updates screen.
		if ( function_exists( 'mai_get_updater_icons' ) && $icons = mai_get_updater_icons() ) {
			$updater->addResultFilter(
				function ( $info ) use ( $icons ) {
					$info->icons = $icons;
					return $info;
				}
			);
		}
	}

	/**
	 * Register content types.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_content_types() {

		/***********************
		 *  Custom Post Types  *
		 ***********************/

		register_post_type( 'mai_ad',
			[
				'exclude_from_search' => false,
				'has_archive'         => true,
				'hierarchical'        => false,
				'labels'              => [
					'name'               => _x( 'Ads', 'Ad general name', 'mai-gam' ),
					'singular_name'      => _x( 'Ad',  'Ad singular name', 'mai-gam' ),
					'menu_name'          => _x( 'Ads', 'Ad admin menu', 'mai-gam' ),
					'name_admin_bar'     => _x( 'Ad',  'Ad add new on admin bar', 'mai-gam' ),
					'add_new'            => _x( 'Add New', 'Ad', 'mai-gam' ),
					'add_new_item'       => __( 'Add New Mai Ad', 'mai-gam' ),
					'new_item'           => __( 'New Ad', 'mai-gam' ),
					'edit_item'          => __( 'Edit Ad', 'mai-gam' ),
					'view_item'          => __( 'View Ad', 'mai-gam' ),
					'all_items'          => __( 'All Mai Ads', 'mai-gam' ),
					'search_items'       => __( 'Search Mai Ads', 'mai-gam' ),
					'parent_item_colon'  => __( 'Parent Mai Ads:', 'mai-gam' ),
					'not_found'          => __( 'No Mai Ads found.', 'mai-gam' ),
					'not_found_in_trash' => __( 'No Mai Ads found in Trash.', 'mai-gam' )
				],
				'menu_icon'          => 'dashicons-media-code',
				'menu_position'      => 50,
				'public'             => false,
				'publicly_queryable' => false,
				'show_in_menu'       => true,
				'show_in_nav_menus'  => false,
				'show_in_rest'       => true,
				'show_ui'            => true,
				'rewrite'            => false,
				'supports'           => [ 'title', 'editor' ],
			]
		);
	}

	/**
	 * Plugin activation.
	 *
	 * @since 0.1.0
	 *
	 * @return  void
	 */
	public function activate() {
		$this->register_content_types();
		flush_rewrite_rules();
	}
}

/**
 * The main function for that returns Mai_GAM_Plugin
 *
 * The main function responsible for returning the one true Mai_GAM_Plugin
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $plugin = Mai_GAM_Plugin(); ?>
 *
 * @since 0.1.0
 *
 * @return object|Mai_GAM_Plugin The one true Mai_GAM_Plugin Instance.
 */
function maigam_plugin() {
	return Mai_GAM_Plugin::instance();
}

// Get Mai_GAM_Plugin Running.
maigam_plugin();
