<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_GAM_Ad_Field_Group {

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
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_script' ] );
		add_action( 'acf/init',              [ $this, 'register_field_group' ] );
	}

	function enqueue_script( $hook ) {
		if ( 'post.php' !== $hook ) {
			return;
		}

		if ( 'mai_ad' !== get_post_type() ) {
			return;
		}

		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$file      = "/assets/js/mai-gam-admin{$suffix}.js";
		$file_path = MAI_GAM_DIR . $file;
		$file_url  = MAI_GAM_URL . $file;
		$version   = MAI_GAM_VERSION . '.' . date( 'njYHi', filemtime( $file_path ) );

		wp_enqueue_script( 'mai-gam-admin', $file_url, [], $version, true );
		wp_localize_script( 'mai-gam-admin', 'maiGAMVars',
			[
			]
		);
	}

	function register_field_group() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key'      => 'maigam_field_group',
				'title'    => __( 'Locations Settings', 'mai-gam' ),
				'fields'   => $this->get_fields(),
				'location' => [
					[
						[
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'mai_ad',
						],
					],
				],
			]
		);
	}

	/**
	 * Gets content type settings fields.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function get_fields() {
		static $fields = null;

		if ( ! is_null( $fields ) ) {
			return $fields;
		}

		$fields = [
			[
				'key'       => 'maigam_global_tab',
				'label'     => __( 'Sitewide', 'mai-gam' ),
				'type'      => 'tab',
				'placement' => 'top',
			],
			[
				'label'   => '',
				'key'     => 'maigam_global_heading',
				'type'    => 'message',
				'message' => $this->get_section_heading( __( 'Sitewide Settings', 'mai-gam' ) ),
			],
			[
				'label'        => __( 'Display location', 'mai-gam' ),
				'instructions' => __( 'Location of content area globally on the site.', 'mai-gam' ),
				'key'          => 'maigam_global_location',
				'name'         => 'maigam_global_location',
				'type'         => 'select',
				'choices'      => [
					''              => __( 'None (inactive)', 'mai-gam' ),
					'before_header' => __( 'Before header', 'mai-gam' ),
					'after_header'  => __( 'After header', 'mai-gam' ),
					'before_footer' => __( 'Before footer', 'mai-gam' ),
					'after_footer'  => __( 'After footer', 'mai-gam' ),
				],
			],
			[
				'key'       => 'maigam_single_tab',
				'label'     => __( 'Single Content', 'mai-gam' ),
				'type'      => 'tab',
				'placement' => 'top',
			],
			[
				'label'   => '',
				'key'     => 'maigam_single_heading',
				'type'    => 'message',
				'message' => $this->get_section_heading( __( 'Single Content Settings', 'mai-gam' ) ),
			],
			[
				'label'        => __( 'Display location', 'mai-gam' ),
				'instructions' => __( 'Location of content area on single posts, pages, and custom post types.', 'mai-gam' ),
				'key'          => 'maigam_single_location',
				'name'         => 'maigam_single_location',
				'type'         => 'select',
				'choices'      => [
					''                     => __( 'None (inactive)', 'mai-gam' ),
					'before_header'        => __( 'Before header', 'mai-gam' ),
					'after_header'         => __( 'After header', 'mai-gam' ),
					'before_entry'         => __( 'Before entry', 'mai-gam' ),
					'before_entry_content' => __( 'Before entry content', 'mai-gam' ),
					'content'              => __( 'In content', 'mai-gam' ),
					'after_entry_content'  => __( 'After entry content', 'mai-gam' ),
					'after_entry'          => __( 'After entry', 'mai-gam' ),
					'before_footer'        => __( 'Before footer', 'mai-gam' ),
				],
			],
			[
				'label'         => __( 'Content location', 'mai-gam' ),
				'key'           => 'maigam_single_content_location',
				'name'          => 'maigam_single_content_location',
				'type'          => 'select',
				'default_value' => 'after',
				'choices'       => [
					'after'  => __( 'After elements', 'mai-gam' ) . ' (div, p, ol, ul, blockquote, figure, iframe)',
					'before' => __( 'Before headings', 'mai-gam' ) . ' (h2, h3)',
				],
				'conditional_logic' => [
					[
						[
							'field'    => 'maigam_single_location',
							'operator' => '==',
							'value'    => 'content',
						],
					],
				],
			],
			[
				'label'             => __( 'Element count', 'mai-gam' ),
				'instructions'      => __( 'Count this many elements before displaying content. Use comma-separated values to repeat this add after a different number of elements.', 'mai-gam' ),
				'key'               => 'maigam_single_content_count',
				'name'              => 'maigam_single_content_count',
				'type'              => 'text',
				'append'            => __( 'elements', 'mai-gam' ),
				'required'          => 1,
				'default_value'     => 6,
				'conditional_logic' => [
					[
						[
							'field'    => 'maigam_single_location',
							'operator' => '==',
							'value'    => 'content',
						],
					],
				],
			],
			[
				'label'        => __( 'Content types', 'mai-gam' ),
				'instructions' => __( 'Show on entries of these content types.', 'mai-gam' ),
				'key'          => 'maigam_single_types',
				'name'         => 'maigam_single_types',
				'type'         => 'select',
				'ui'           => 1,
				'multiple'     => 1,
				'choices'      => [],
			],
			[
				'label'             => __( 'Taxonomy conditions', 'mai-gam' ),
				'instructions'      => __( 'Show on entries with taxonomy conditions.', 'mai-gam' ),
				'key'               => 'maigam_single_taxonomies',
				'name'              => 'maigam_single_taxonomies',
				'type'              => 'repeater',
				'collapsed'         => 'mai_single_taxonomy',
				'layout'            => 'block',
				'button_label'      => __( 'Add Taxonomy Condition', 'mai-gam' ),
				'sub_fields'        => $this->get_taxonomies_sub_fields(),
				'conditional_logic' => [
					[
						'field'    => 'maigam_single_types',
						'operator' => '!=empty',
					],
				],
			],
			[
				'label'   => __( 'Taxonomies relation', 'mai-gam' ),
				'key'     => 'maigam_single_taxonomies_relation',
				'name'    => 'maigam_single_taxonomies_relation',
				'type'    => 'select',
				'default' => 'AND',
				'choices' => [
					'AND' => __( 'And', 'mai-gam' ),
					'OR'  => __( 'Or', 'mai-gam' ),
				],
				'conditional_logic' => [
					[
						'field'    => 'maigam_single_types',
						'operator' => '!=empty',
					],
					[
						'field'    => 'maigam_single_taxonomies',
						'operator' => '>',
						'value'    => '1', // More than 1 row.
					],
				],
			],
			[
				'label'        => __( 'Keyword conditions', 'mai-gam' ),
				'instructions' => __( 'Show on entries any of the following keyword strings. Comma-separate multiple keyword strings to check. Keyword search is case-insensitive.', 'mai-gam' ),
				'key'          => 'maigam_single_keywords',
				'name'         => 'maigam_single_keywords',
				'type'         => 'text',
			],
			[
				'label'         => __( 'Author conditions', 'mai-gam' ),
				'instructions'  => __( 'Show on entries with the following authors.', 'mai-gam' ),
				'key'           => 'maigam_single_authors',
				'name'          => 'maigam_single_authors',
				'type'          => 'user',
				'allow_null'    => 1,
				'multiple'      => 1,
				'return_format' => 'id',
				'role'          => [
					'contributor',
					'author',
					'editor',
					'administrator',
				],
			],
			[
				'label'         => __( 'Include entries', 'mai-gam' ),
				'instructions'  => __( 'Show on specific entries regardless of content type and taxonomy conditions.', 'mai-gam' ),
				'key'           => 'maigam_single_entries',
				'name'          => 'maigam_single_entries',
				'type'          => 'relationship',
				'required'      => 0,
				'post_type'     => '',
				'taxonomy'      => '',
				'min'           => '',
				'max'           => '',
				'return_format' => 'id',
				'filters'       => [
					'search',
					'post_type',
					'taxonomy',
				],
				'elements'          => [
					'featured_image',
				],
			],
			[
				'label'         => __( 'Exclude entries', 'mai-gam' ),
				'instructions'  => __( 'Hide on specific entries regardless of content type and taxonomy conditions.', 'mai-gam' ),
				'key'           => 'maigam_single_exclude_entries',
				'name'          => 'maigam_single_exclude_entries',
				'type'          => 'relationship',
				'required'      => 0,
				'post_type'     => '',
				'taxonomy'      => '',
				'min'           => '',
				'max'           => '',
				'return_format' => 'id',
				'filters'       => [
					'search',
					'post_type',
					'taxonomy',
				],
				'elements'          => [
					'featured_image',
				],
			],
			[
				'label'     => __( 'Content Archives', 'mai-gam' ),
				'key'       => 'maigam_archive_tab',
				'type'      => 'tab',
				'placement' => 'top',
			],
			[
				'label'   => '',
				'key'     => 'maigam_archive_heading',
				'type'    => 'message',
				'message' => $this->get_section_heading( __( 'Content Archive Settings', 'mai-gam' ) ),
			],
			[
				'label'        => __( 'Display location', 'mai-gam' ),
				'instructions' => __( 'Location of content area on archives.', 'mai-gam' ),
				'key'          => 'maigam_archive_location',
				'name'         => 'maigam_archive_location',
				'type'         => 'select',
				'choices'      => [
					''              => __( 'None (inactive)', 'mai-gam' ),
					'before_header' => __( 'Before header', 'mai-gam' ),
					'after_header'  => __( 'After header', 'mai-gam' ),
					'before_loop'   => __( 'Before entries', 'mai-gam' ),
					'entries'       => __( 'In entries', 'mai-gam' ),        // TODO: Is this doable without breaking columns, etc?
					'after_loop'    => __( 'After entries', 'mai-gam' ),
					'before_footer' => __( 'Before footer', 'mai-gam' ),
				],
			],
			[
				'label'             => __( 'Row count', 'mai-gam' ),
				'instructions'      => __( 'Count this many rows of entries before displaying content. Use comma-separated values to repeat this add after a different number of elements.', 'mai-gam' ),
				'key'               => 'maigam_archive_content_count',
				'name'              => 'maigam_archive_content_count',
				'type'              => 'text',
				'append'            => __( 'entries', 'mai-gam' ),
				'required'          => 1,
				'default_value'     => 3,
				'conditional_logic' => [
					[
						[
							'field'    => 'maigam_archive_location',
							'operator' => '==',
							'value'    => 'entries',
						],
					],
				],
			],
			[
				'label'        => __( 'Post type archives', 'mai-gam' ),
				'instructions' => __( 'Show on post type archives.', 'mai-gam' ),
				'key'          => 'maigam_archive_types',
				'name'         => 'maigam_archive_types',
				'type'         => 'select',
				'ui'           => 1,
				'multiple'     => 1,
				'choices'      => [],
			],
			[
				'label'        => __( 'Taxonomy archives', 'mai-gam' ),
				'instructions' => __( 'Show on taxonomy archives.', 'mai-gam' ),
				'key'          => 'maigam_archive_taxonomies',
				'name'         => 'maigam_archive_taxonomies',
				'type'         => 'select',
				'ui'           => 1,
				'multiple'     => 1,
				'choices'      => [],
			],
			[
				'label'        => __( 'Term archives', 'mai-gam' ),
				'instructions' => __( 'Show on specific term archives.', 'mai-gam' ),
				'key'          => 'maigam_archive_terms',
				'name'         => 'maigam_archive_terms',
				'type'         => 'select',
				'ui'           => 1,
				'multiple'     => 1,
				'choices'      => [],
			],
			[
				'label'        => __( 'Exclude term archives', 'mai-gam' ),
				'instructions' => __( 'Hide on specific term archives.', 'mai-gam' ),
				'key'          => 'maigam_archive_exclude_terms',
				'name'         => 'maigam_archive_exclude_terms',
				'type'         => 'select',
				'ui'           => 1,
				'multiple'     => 1,
				'choices'      => [],
			],
			[
				'label'        => __( 'Includes', 'mai-gam' ),
				'instructions' => 'Show on miscellaneous areas of the website.',
				'key'          => 'maigam_archive_includes',
				'name'         => 'maigam_archive_includes',
				'type'         => 'checkbox',
				'choices'      => [
					'search' => __( 'Search Results', 'mai-gam' ),
				],
			],
		];

		return $fields;
	}

	/**
	 * Gets taxonomies sub fields.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	function get_taxonomies_sub_fields() {
		return [
			[
				'label'             => __( 'Taxonomy', 'mai-gam' ),
				'key'               => 'maigam_single_taxonomy',
				'name'              => 'taxogam_nomy',
				'type'              => 'select',
				'choices'           => [],
				'ui'                => 1,
				'ajax'              => 1,
			],
			[
				'label'             => __( 'Terms', 'mai-gam' ),
				'key'               => 'maigam_single_terms',
				'name'              => 'termgam_s',
				'type'              => 'select',
				'choices'           => [],
				'ui'                => 1,
				'ajax'              => 1,
				'multiple'          => 1,
				'conditional_logic' => [
					[
						[
							'field'    => 'maigam_single_taxonomy',
							'operator' => '!=empty',
						],
					],
				],
			],
			[
				'key'        => 'maigam_single_operator',
				'name'       => 'opergam_ator',
				'label'      => __( 'Operator', 'mai-gam' ),
				'type'       => 'select',
				'default'    => 'IN',
				'choices'    => [
					'IN'     => __( 'In', 'mai-gam' ),
					'NOT IN' => __( 'Not In', 'mai-gam' ),
				],
				'conditional_logic' => [
					[
						[
							'field'    => 'maigam_single_taxonomy',
							'operator' => '!=empty',
						],
					],
				],
			],
		];
	}

	/**
	 * Gets heading text with inline styles.
	 *
	 * @since 0.1.0
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	function get_section_heading( $text ) {
		return sprintf( '<h2 style="padding:0;margin:0;font-size:18px;">%s</h2>', $text );
	}
}
