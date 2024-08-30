<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Categories_Field_Group {

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
		add_filter( 'acf/load_field/key=maipub_category',               [ $this, 'load_all_categories' ] );
		add_filter( 'acf/location/rule_match/maipub_mapped_taxonomies', [ $this, 'taxonomy_rule_match' ], 10, 4 );
	}

	/**
	 * Register field group.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function register_field_group() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key'      => 'maipub_categories_field_group',
				'title'    => __( 'Mai Publisher IAB Category', 'mai-publisher' ),
				'fields'   => [
					[

						'label'        => __( 'IAB Category', 'mai-publisher' ),
						'instructions' => __( 'Choose the IAB Category that is most fitting for this category. Be specific. If the category you\'re editing is Duke Basketball, choose College Basketball, not Basketball or Sports.', 'mai-publisher' ) . sprintf( ' <a href="%s">%s</a>', admin_url( 'edit.php?post_type=mai_ad&page=categories' ), __( 'View full list of categories.', 'mai-publisher' ) ),
						'key'          => 'maipub_category',
						'name'         => 'maipub_category',
						'type'         => 'select',
						'choices'      => [],
						'multiple'     => 0,
						'allow_null'   => 1,
						'ui'           => 1,
						'ajax'         => 1,
						'placeholder'  => '',
					],
				],
				// 'location' => [
				// 	[
				// 		[
				// 			'param'    => 'taxonomy',
				// 			'operator' => '==',
				// 			'value'    => 'category',
				// 		],
				// 	]
				// ],
				'location'  => [
					[
						[
							'param'    => 'maipub_mapped_taxonomies',
							'operator' => '==', // Currently unused.
							'value'    => true, // Currently unused.
						],
					],
				],
				'menu_order' => 5,
				'position'   => 'high',
			]
		);
	}

	/**
	 * Gets term choices.
	 *
	 * @since 0.1.0
	 *
	 * @param array $field The ACF field array.
	 *
	 * @return mixed
	 */
	function load_all_categories( $field ) {
		if ( ! is_admin() ) {
			return $field;
		}

		$field['choices'] = [];

		foreach ( (array) maipub_get_all_iab_categories() as $id => $label ) {
			$field['choices'][ $id ] = $label;
		}

		return $field;
	}

	/**
	 * Shows field group when editing public taxonomy terms.
	 *
	 * @since TBD
	 *
	 * @param bool      $result Whether the rule matches.
	 * @param array     $rule   Current rule to match (param, operator, value).
	 * @param WP_Screen $screen The current screen.
	 *
	 * @return bool
	 */
	function taxonomy_rule_match( $result, $rule, $screen, $field_group ) {
		$taxonomies = array_values( (array) maipub_get_option( 'category_mapping', false ) );

		return $taxonomies && isset( $screen['taxonomy'] ) && in_array( $screen['taxonomy'], $taxonomies );
	}
}
