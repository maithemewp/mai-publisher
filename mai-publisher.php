<?php

/**
 * Plugin Name:     Mai Publisher
 * Plugin URI:      https://bizbudding.com
 * Description:     Manage ads and more for websites in the Mai Publisher network.
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
 * Main Mai_Publisher_Plugin Class.
 *
 * @since 0.1.0
 */
final class Mai_Publisher_Plugin {

	/**
	 * @var Mai_Publisher_Plugin The one true Mai_Publisher_Plugin
	 *
	 * @since 0.1.0
	 */
	private static $instance;

	/**
	 * Main Mai_Publisher_Plugin Instance.
	 *
	 * Insures that only one instance of Mai_Publisher_Plugin exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   0.1.0
	 * @static  var array $instance
	 * @uses    Mai_Publisher_Plugin::setup_constants() Setup the constants needed.
	 * @uses    Mai_Publisher_Plugin::includes() Include the required files.
	 * @uses    Mai_Publisher_Plugin::hooks() Activate, deactivate, etc.
	 * @see     Mai_Publisher_Plugin()
	 * @return  object | Mai_Publisher_Plugin The one true Mai_Publisher_Plugin
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup.
			self::$instance = new Mai_Publisher_Plugin;
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
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mai-publisher' ), '1.0' );
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
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'mai-publisher' ), '1.0' );
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
		if ( ! defined( 'MAI_PUBLISHER_VERSION' ) ) {
			define( 'MAI_PUBLISHER_VERSION', '0.1.0' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'MAI_PUBLISHER_DIR' ) ) {
			define( 'MAI_PUBLISHER_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Folder URL.
		if ( ! defined( 'MAI_PUBLISHER_URL' ) ) {
			define( 'MAI_PUBLISHER_URL', plugin_dir_url( __FILE__ ) );
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
		foreach ( glob( MAI_PUBLISHER_DIR . 'includes/' . '*.php' ) as $file ) { include $file; }
		// Classes.
		foreach ( glob( MAI_PUBLISHER_DIR . 'classes/' . '*.php' ) as $file ) { include $file; }
		// Blocks.
		include MAI_PUBLISHER_DIR . 'blocks/ad/block.php';
		include MAI_PUBLISHER_DIR . 'blocks/ad-unit/block.php';
		// Instantiate classes.
		$admin         = new Mai_Publisher_Admin;
		$settings      = new Mai_Publisher_Settings;
		$field_group   = new Mai_Publisher_Ad_Field_Group;
		$fields        = new Mai_Publisher_Ad_Fields;
		$visibility    = new Mai_Publisher_Ad_Visibility;
		$generate      = new Mai_Publisher_Generate_Ads;
		$ad_block      = new Mai_Publisher_Ad_Block;
		$ad_unit_block = new Mai_Publisher_Ad_Unit_Block;
		$display       = new Mai_Publisher_Display;
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
		add_action( 'pre_get_posts',  [ $this, 'orderby_title' ] );

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
		$updater = PucFactory::buildUpdateChecker( 'https://github.com/maithemewp/mai-publisher/', __FILE__, 'mai-publisher' );

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
					'name'               => _x( 'Mai Ads', 'Mai Ad general name', 'mai-publisher' ),
					'singular_name'      => _x( 'Mai Ad',  'Mai Ad singular name', 'mai-publisher' ),
					'menu_name'          => _x( 'Mai Ads', 'Mai Ad admin menu', 'mai-publisher' ),
					'name_admin_bar'     => _x( 'Mai Ad',  'Mai Ad add new on admin bar', 'mai-publisher' ),
					'add_new'            => _x( 'Add New', 'Ad', 'mai-publisher' ),
					'add_new_item'       => __( 'Add New Mai Ad', 'mai-publisher' ),
					'new_item'           => __( 'New Mai Ad', 'mai-publisher' ),
					'edit_item'          => __( 'Edit Mai Ad', 'mai-publisher' ),
					'view_item'          => __( 'View Mai Ad', 'mai-publisher' ),
					'all_items'          => __( 'All Mai Ads', 'mai-publisher' ),
					'search_items'       => __( 'Search Mai Ads', 'mai-publisher' ),
					'parent_item_colon'  => __( 'Parent Mai Ads:', 'mai-publisher' ),
					'not_found'          => __( 'No Mai Ads found.', 'mai-publisher' ),
					'not_found_in_trash' => __( 'No Mai Ads found in Trash.', 'mai-publisher' )
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
	 * Order dashboard ad list by title.
	 *
	 * @since 0.1.0
	 *
	 * @param object $query The query object.
	 *
	 * @return void
	 */
	public function orderby_title( $query ) {
		// Bail if not in admin.
		if ( ! is_admin() ) {
			return;
		}

		// Bail if not the main query.
		if ( ! $query->is_main_query() ) {
			return;
		}

		// Bail if not the mai_ad post type archive.
		if ( ! $query->is_post_type_archive( 'mai_ad' ) ) {
			return;
		}

		// Set order.
		$query->set( 'orderby', 'title' );
		$query->set( 'order','ASC' );
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
 * The main function for that returns Mai_Publisher_Plugin
 *
 * The main function responsible for returning the one true Mai_Publisher_Plugin
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $plugin = Mai_Publisher_Plugin(); ?>
 *
 * @since 0.1.0
 *
 * @return object|Mai_Publisher_Plugin The one true Mai_Publisher_Plugin Instance.
 */
function maipub_plugin() {
	return Mai_Publisher_Plugin::instance();
}

// Get Mai_Publisher_Plugin Running.
maipub_plugin();
