<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Ad_Visibility {

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
		add_action( 'acf/init',                                         [ $this, 'register_field_group' ] );
		add_filter( 'acf/location/rule_match/maipub_public_post_types', [ $this, 'post_type_rule_match' ], 10, 4 );
		add_filter( 'acf/location/rule_match/maipub_public_taxonomies', [ $this, 'taxonomy_rule_match' ], 10, 4 );
	}

	function register_field_group() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key'    => 'maipub_ad_disable_field_group',
				'title'  => __( 'Mai Ad Visibility', 'mai-publisher' ),
				'fields' => [
					[
						// 'label'        => __( 'Mai Ad Visiblity', 'mai-publisher' ),
						// 'instructions' => __( 'Hide ads on miscellaneous areas of the website.', 'mai-publisher' ),
						'key'          => 'maipub_visibility',
						'name'         => 'maipub_visibility',
						'type'         => 'checkbox',
						'choices'      => [
							'all'       => __( 'Hide all ads', 'mai-publisher' ),
							'incontent' => __( 'Hide in-content ads', 'mai-publisher' ),
						],
					],
				],
				'location' => [
					[
						[
							'param'    => 'maipub_public_post_types',
							'operator' => '==', // Currently unused.
							'value'    => true, // Currently unused.
						],
					],
					[
						[
							'param'    => 'maipub_public_taxonomies',
							'operator' => '==', // Currently unused.
							'value'    => true, // Currently unused.
						],
					],
				],
				'position' => 'side',
			]
		);
	}

	/**
	 * Shows "Mai Ad Visiblity" metabox on all public post types.
	 *
	 * @since 0.1.0
	 *
	 * @param bool      $result Whether the rule matches.
	 * @param array     $rule   Current rule to match (param, operator, value).
	 * @param WP_Screen $screen The current screen.
	 *
	 * @return bool
	 */
	function post_type_rule_match( $result, $rule, $screen, $field_group ) {
		$post_types = get_post_types( [ 'public' => true ] );

		return $post_types && isset( $screen['post_type'] ) && isset( $post_types[ $screen['post_type'] ] );
	}

	/**
	 * Shows "Mai Ad Visiblity" metabox on all public taxonomys types.
	 *
	 * @since 0.1.0
	 *
	 * @param bool      $result Whether the rule matches.
	 * @param array     $rule   Current rule to match (param, operator, value).
	 * @param WP_Screen $screen The current screen.
	 *
	 * @return bool
	 */
	function taxonomy_rule_match( $result, $rule, $screen, $field_group ) {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return $result;
		}

		$current    = get_current_screen();
		$term       = isset( $current->base ) && 'term' === $current->base;
		$taxonomies = $term ? get_taxonomies( [ 'public' => 'true' ], 'names' ) : [];

		return $taxonomies && isset( $screen['taxonomy'] ) && isset( $taxonomies[ $screen['taxonomy'] ] );
	}
}