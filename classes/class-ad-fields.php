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
		add_action( 'acf/render_field/key=maipub_global_tab',             [ $this, 'admin_css' ] );
		add_filter( 'acf/load_field/key=maipub_global_location',          [ $this, 'load_global_locations' ] );
		add_filter( 'acf/load_field/key=maipub_single_location',          [ $this, 'load_single_locations' ] );
		add_filter( 'acf/load_field/key=maipub_archive_location',         [ $this, 'load_archive_locations' ] );
		add_filter( 'acf/load_field/key=maipub_single_types',             [ $this, 'load_content_types' ] );
		add_filter( 'acf/load_field/key=maipub_single_taxonomy',          [ $this, 'load_single_taxonomy' ] );
		add_filter( 'acf/load_field/key=maipub_archive_types',            [ $this, 'load_archive_post_types' ] );
		add_filter( 'acf/load_field/key=maipub_archive_taxonomies',       [ $this, 'load_all_taxonomies' ] );
		add_filter( 'acf/load_field/key=maipub_archive_terms',            [ $this, 'load_all_terms' ] );
		add_filter( 'acf/load_field/key=maipub_archive_exclude_terms',    [ $this, 'load_all_terms' ] );
		add_filter( 'acf/prepare_field/key=maipub_archive_terms',         [ $this, 'load_saved_terms' ] );
		add_filter( 'acf/prepare_field/key=maipub_archive_exclude_terms', [ $this, 'load_saved_terms' ] );
		add_filter( 'acf/load_field/key=maipub_single_terms',             [ $this, 'load_single_terms' ] );
		add_filter( 'acf/prepare_field/key=maipub_single_terms',          [ $this, 'prepare_single_terms' ] );
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
	 * Loads global locations.
	 *
	 * @since 1.7.0
	 *
	 * @param array $field The field data.
	 *
	 * @return array
	 */
	function load_global_locations( $field ) {
		if ( ! ( maipub_is_editor() || wp_doing_ajax() ) ) {
			return $field;
		}

		$field['choices'] = maipub_get_location_choices( 'global' );

		return $field;
	}

	/**
	 * Loads singular locations.
	 *
	 * @since 1.7.0
	 *
	 * @param array $field The field data.
	 *
	 * @return array
	 */
	function load_single_locations( $field ) {
		if ( ! ( maipub_is_editor() || wp_doing_ajax() ) ) {
			return $field;
		}

		$field['choices'] = maipub_get_location_choices( 'single' );

		return $field;
	}

	/**
	 * Loads archive locations.
	 *
	 * @since 1.7.0
	 *
	 * @param array $field The field data.
	 *
	 * @return array
	 */
	function load_archive_locations( $field ) {
		if ( ! ( maipub_is_editor() || wp_doing_ajax() ) ) {
			return $field;
		}

		$field['choices'] = maipub_get_location_choices( 'archive' );

		return $field;
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
		if ( ! ( maipub_is_editor() || wp_doing_ajax() ) ) {
			return $field;
		}

		// Set static cache.
		static $choices = null;

		// Check cache.
		if ( ! is_null( $choices ) ) {
			$field['choices'] = $choices;

			return $field;
		}

		$field['choices'] = array_merge(
			[ '*' => __( 'All Content Types', 'mai-publisher' ) ],
			$this->get_post_type_choices()
		);

		$choices = $field['choices'];

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
		if ( ! ( maipub_is_editor() || wp_doing_ajax() ) ) {
			return $field;
		}

		// Set static cache.
		static $choices = null;

		// Check cache.
		if ( ! is_null( $choices ) ) {
			$field['choices'] = $choices;

			return $field;
		}

		$choices          = $this->get_taxonomy_choices();
		$field['choices'] = $choices;

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
		if ( ! ( maipub_is_editor() || wp_doing_ajax() ) ) {
			return $field;
		}

		// Set static cache.
		static $choices = null;

		// Check cache.
		if ( ! is_null( $choices ) ) {
			$field['choices'] = $choices;

			return $field;
		}

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

		$choices = $field['choices'];

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
		if ( ! ( maipub_is_editor() || wp_doing_ajax() ) ) {
			return $field;
		}

		// Set static cache.
		static $choices = null;

		// Check cache.
		if ( ! is_null( $choices ) ) {
			$field['choices'] = $choices;

			return $field;
		}

		$choices = array_merge(
			[ '*' => __( 'All Taxonomies', 'mai-publisher' ) ],
			$this->get_taxonomy_choices()
		);

		$field['choices'] = $choices;

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
		if ( ! ( maipub_is_editor() || wp_doing_ajax() ) ) {
			return $field;
		}

		// Set vars.
		$field['choices'] = [];
		$number           = isset( $_POST['s'] ) && $_POST['s'] ? 0 : 50;
		$taxonomies       = $this->get_taxonomies();

		foreach( $taxonomies as $taxonomy ) {
			$terms = get_terms(
				[
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'fields'     => 'id=>name',
					'number'     => $number, // No limit when searching, otherwise 100 per taxonomy.
				]
			);

			if ( ! $terms ) {
				continue;
			}

			// Add taxonomy name to term name, and add to choices.
			foreach ( $terms as $term_id => $name ) {
				$field['choices'][ $term_id ] = "$name ($taxonomy)";
			}

			// Old optgroup method. Doesn't work when ajax => 1 in field settings.
			// $optgroup                      = sprintf( '%s (%s)', get_taxonomy( $taxonomy )->label, $taxonomy );
			// $field['choices'][ $optgroup ] = $terms;
		}

		return $field;
	}

	/**
	 * Makes sure saved terms are loaded as choices.
	 *
	 * @since 1.7.0
	 *
	 * @param array $field The ACF field array.
	 *
	 * @return mixed
	 */
	function load_saved_terms( $field ) {
		if ( ! ( maipub_is_editor() || wp_doing_ajax() ) ) {
			return $field;
		}

		// If we have a value, make sure those terms are loaded by default.
		if ( $field['value'] ) {
			foreach ( $field['value'] as $term_id ) {
				if ( ! isset( $field['choices'][ $term_id ] ) ) {
					$term = get_term( $term_id );

					if ( $term ) {
						$field['choices'][ $term_id ] = sprintf( '%s (%s)', $term->name, $term->taxonomy );
					}
				}
			}
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
		if ( ! ( maipub_is_editor() || wp_doing_ajax() ) ) {
			return $field;
		}

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
		if ( ! ( maipub_is_editor() || wp_doing_ajax() ) ) {
			return $field;
		}

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
		if ( ! ( maipub_is_editor() || wp_doing_ajax() ) ) {
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
		if ( ! ( maipub_is_editor() || wp_doing_ajax() ) ) {
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
				'fields'     => 'id=>name',
			]
		);

		if ( ! $terms ) {
			return $choices;
		}

		$choices = $terms;

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
		if ( ! function_exists( 'acf_verify_ajax' ) ) {
			return false;
		}

		$nonce  = isset( $_REQUEST['nonce'] ) ? $_REQUEST['nonce'] : '';
		$action = isset( $_REQUEST['field_key'] ) ? $_REQUEST['field_key'] : '';
		$return = isset( $_REQUEST[ $request ] ) ? $_REQUEST[ $request ] : false;

		return $nonce && $action && $return && acf_verify_ajax( $nonce, $action ) ? $return : false;
	}
}
