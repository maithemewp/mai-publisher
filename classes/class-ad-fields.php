<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Ad_Fields {

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
		add_action( 'acf/render_field/key=maipub_global_tab',          [ $this, 'admin_css' ] );
		add_filter( 'acf/load_field/key=maipub_single_types',          [ $this, 'load_content_types' ] );
		add_filter( 'acf/load_field/key=maipub_single_taxonomy',       [ $this, 'load_single_taxonomy' ] );
		add_filter( 'acf/load_field/key=maipub_archive_types',         [ $this, 'load_archive_post_types' ] );
		add_filter( 'acf/load_field/key=maipub_archive_taxonomies',    [ $this, 'load_all_taxonomies' ] );
		add_filter( 'acf/load_field/key=maipub_archive_terms',         [ $this, 'load_all_terms' ] );
		add_filter( 'acf/load_field/key=maipub_archive_exclude_terms', [ $this, 'load_all_terms' ] );
		add_filter( 'acf/load_field/key=maipub_single_terms',          [ $this, 'load_single_terms' ] );
		add_filter( 'acf/prepare_field/key=maipub_single_terms',       [ $this, 'prepare_single_terms' ] );
	}

	/**
	 * Adds custom CSS in the first field.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function admin_css( $field ) {
		echo '<style>
		#acf-maipub_ad_field_group > .acf-fields {
			padding-bottom: 10vh !important;
		}
		.acf-field-maipub-single-taxonomies .acf-repeater .acf-actions {
			text-align: start;
		}
		.acf-field-maipub-single-taxonomies .acf-repeater .acf-button {
			float: none;
		}
		</style>';
	}


	/**
	 * Loads singular content types.
	 *
	 * @since 0.1.0
	 *
	 * @param array $field The field data.
	 *
	 * @return array
	 */
	function load_content_types( $field ) {
		$field['choices'] = array_merge(
			[ '*' => __( 'All Content Types', 'mai-publisher' ) ],
			$this->get_post_type_choices()
		);

		return $field;
	}

	/**
	 * Loads display terms as choices.
	 *
	 * @since 0.1.0
	 *
	 * @param array $field The field data.
	 *
	 * @return array
	 */
	function load_single_taxonomy( $field ) {
		$field['choices'] = $this->get_taxonomy_choices();

		return $field;
	}

	/**
	 * Gets post type archive choices.
	 *
	 * @since 0.1.0
	 *
	 * @param array $field The ACF field array.
	 *
	 * @return mixed
	 */
	function load_archive_post_types( $field ) {
		$post_types = $this->get_post_type_choices();

		foreach ( $post_types as $name => $label ) {
			$object = get_post_type_object( $name );

			if ( 'post' === $name || $object->has_archive ) {
				continue;
			}

			unset( $post_types[ $name ] );
		}

		$field['choices'] = array_merge(
			[ '*' => __( 'All Post Types', 'mai-publisher' ) ],
			$post_types
		);

		return $field;
	}

	/**
	 * Gets taxonomy archive choices.
	 *
	 * @since 0.1.0
	 *
	 * @param array $field The ACF field array.
	 *
	 * @return mixed
	 */
	function load_all_taxonomies( $field ) {
		$field['choices'] = array_merge(
			[ '*' => __( 'All Taxonomies', 'mai-publisher' ) ],
			$this->get_taxonomy_choices()
		);

		return $field;
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
	function load_all_terms( $field ) {
		$field['choices'] = [];
		$taxonomies       = $this->get_taxonomies();

		foreach( $taxonomies as $taxonomy ) {
			$terms = get_terms(
				[
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				]
			);

			if ( ! $terms ) {
				continue;
			}

			$optgroup                      = sprintf( '%s (%s)', get_taxonomy( $taxonomy )->label, $taxonomy );
			$field['choices'][ $optgroup ] = wp_list_pluck( $terms, 'name', 'term_id' );
		}

		return $field;
	}

	/**
	 * Gets post type choices with name => label.
	 * If two share the same label, the name is appended in parenthesis.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function get_post_type_choices() {
		static $choices = null;

		if ( ! is_null( $choices ) ) {
			return $choices;
		}

		$choices = $this->get_post_types();
		$choices = function_exists( 'acf_get_pretty_post_types' ) ? acf_get_pretty_post_types( $choices ) : $choices;

		return $choices;
	}

	/**
	 * Gets available post types for content areas.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function get_post_types() {
		static $post_types = null;

		if ( ! is_null( $post_types ) ) {
			return $post_types;
		}

		$post_types = get_post_types( [ 'public' => true ], 'names' );
		unset( $post_types['attachment'] );

		$post_types = apply_filters( 'mai_publisher_post_types', array_values( $post_types ) );

		$post_types = array_unique( array_filter( (array) $post_types ) );

		foreach ( $post_types as $index => $post_type ) {
			if ( post_type_exists( $post_type ) ) {
				continue;
			}

			unset( $post_types[ $index ] );
		}

		return array_values( $post_types );
	}

	/**
	 * Gets taxonomy choices with name => label.
	 * If two share the same label, the name is appended in parenthesis.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function get_taxonomy_choices() {
		static $choices = null;

		if ( ! is_null( $choices ) ) {
			return $choices;
		}

		$choices = $this->get_taxonomies();
		$choices = function_exists( 'acf_get_pretty_taxonomies' ) ? acf_get_pretty_taxonomies( $choices ) : $choices;

		return $choices;
	}

	/**
	 * Gets taxonomies
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function get_taxonomies() {
		static $taxonomies = null;

		if ( ! is_null( $taxonomies ) ) {
			return $taxonomies;
		}

		$taxonomies = get_taxonomies( [ 'public' => 'true' ], 'names' );
		$taxonomies = apply_filters( 'mai_publisher_taxonomies', array_values( $taxonomies ) );
		$taxonomies = array_unique( array_filter( (array) $taxonomies ) );

		foreach ( $taxonomies as $index => $taxonomy ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			unset( $taxonomy[ $index ] );
		}

		return array_values( $taxonomies );
	}

	/**
	 * Get terms from an ajax query.
	 * The taxonomy is passed via JS on select2_query_args filter.
	 *
	 * @since 0.1.0
	 *
	 * @param array $field The ACF field array.
	 *
	 * @return mixed
	 */
	function load_single_terms( $field ) {
		$field = $this->load_terms( $field );

		return $field;
	}

	/**
	 * Get terms from an ajax query.
	 * The taxonomy is passed via JS on select2_query_args filter.
	 *
	 * @since 0.1.0
	 *
	 * @param array $field The ACF field array.
	 *
	 * @return mixed
	 */
	function prepare_single_terms( $field ) {
		$field = $this->prepare_terms( $field );

		return $field;
	}

	/**
	 * Get terms from an ajax query.
	 * The taxonomy is passed via JS on select2_query_args filter.
	 *
	 * @since 0.1.0
	 *
	 * @param array $field The ACF field array.
	 *
	 * @return mixed
	 */
	function load_terms( $field ) {
		if ( ! is_admin() ) {
			return $field;
		}

		$taxonomy = $this->get_acf_request( 'taxonomy' );

		if ( ! $taxonomy ) {
			return $field;
		}

		$field['choices'] = $this->get_term_choices_from_taxonomy( $taxonomy );

		return $field;
	}

	/**
	 * Load term choices based on existing saved field value.
	 * Ajax loading terms was working, but if a term was already saved
	 * it was not loading correctly when editing a post.
	 *
	 * @link  https://github.com/maithemewp/mai-engine/issues/93
	 *
	 * @since 0.1.0
	 *
	 * @param array $field The ACF field array.
	 *
	 * @return mixed
	 */
	function prepare_terms( $field ) {
		if ( ! is_admin() ) {
			return $field;
		}

		if ( ! $field['value'] ) {
			return $field;
		}

		$term_id = $field['value'][0];

		if ( ! $term_id ) {
			return $field;
		}

		$term = get_term( $term_id );

		if ( ! $term ) {
			return $field;
		}

		$field['choices'] = $this->get_term_choices_from_taxonomy( $term->taxonomy );

		return $field;
	}

	/**
	 * Get term choices from a taxonomy
	 *
	 * @since 0.1.0
	 *
	 * @param string $taxonomy A registered taxonomy name.
	 *
	 * @return array
	 */
	function get_term_choices_from_taxonomy( $taxonomy = '' ) {
		$choices = [];
		$terms   = get_terms(
			$taxonomy,
			[
				'hide_empty' => false,
			]
		);

		if ( ! $terms ) {
			return $choices;
		}

		foreach ( $terms as $term ) {
			$choices[ $term->term_id ] = $term->name;
		}

		return $choices;
	}


	/**
	 * Gets an ACF request, checking nonce and value.
	 *
	 * @since 0.1.0
	 *
	 * @param string $request Request data.
	 *
	 * @return bool
	 */
	function get_acf_request( $request ) {
		if ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'acf_nonce' ) && isset( $_REQUEST[ $request ] ) && ! empty( $_REQUEST[ $request ] ) ) {
			return $_REQUEST[ $request ];
		}

		return false;
	}
}
