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
		add_action( 'acf/init',                           [ $this, 'register_field_group' ] );
		add_filter( 'acf/load_field/key=maipub_category', [ $this, 'load_all_categories' ] );
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
				'title'    => __( 'Mai Publisher', 'mai-publisher' ),
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
				'location' => [
					[
						[
							'param'    => 'taxonomy',
							'operator' => '==',
							'value'    => 'category',
						],
					]
				],
				'menu_order' => -1,
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
		$categories       = $this->flatten_array( maipub_get_all_categories() );

		ray( $categories );

		foreach ( $categories as $name => $value ) {
			$field['choices'][ $name ] = $value;
			// $last = end( $values );

			// // Add a tab before $last string value for item in label array.
			// foreach ( $values as $index => $value ) {
			// 	$last = sprintf( '%s%s', '&ndash;&nbsp;', $last );
			// }

			// $field['choices'][ $name ] = $last;
		}

		return $field;
	}

	function flatten_array( $array, $spacer = '&ndash;&nbsp;' ) {
		$flat = [];

		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				$flat = array_merge( $flat, $this->flatten_array( $value, $spacer ) );
			} else {
				$flat[ $key ] = $value;
			}
		}

		return $flat;
	}
}
