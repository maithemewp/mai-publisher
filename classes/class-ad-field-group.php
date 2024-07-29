<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Ad_Field_Group {

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
		add_action( 'acf/init',              [ $this, 'register_field_groups' ] );
	}

	/**
	 * Enqueue script.
	 *
	 * @since 0.1.0
	 *
	 * @param string $hook The current hook.
	 *
	 * @return void
	 */
	function enqueue_script( $hook ) {
		if ( 'post.php' !== $hook ) {
			return;
		}

		if ( 'mai_ad' !== get_post_type() ) {
			return;
		}

		$min  = maipub_get_min_dir();
		$file = "assets/js/{$min}block.js";

		wp_enqueue_script( 'mai-publisher-admin', maipub_get_file_data( $file, 'url' ), [], maipub_get_file_data( $file, 'version' ), [ 'in_footer' => true ] );
	}

	/**
	 * Register field groups.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function register_field_groups() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key'      => 'maipub_ad_field_group',
				'title'    => __( 'Locations Settings', 'mai-publisher' ),
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

		acf_add_local_field_group(
			[
				'key'      => 'maipub_ad_sidebar_field_group',
				'title'    => __( 'Ad Settings', 'mai-publisher' ),
				'fields'   => [
					array(
						'key'     => 'maipub_disable_reimport_content',
						'name'    => 'maipub_disable_reimport_content',
						'type'    => 'true_false',
						'message' => __( 'Disable reimporting ad content', 'mai-publisher' ),
					),
				],
				'position' => 'side',
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
				'key'       => 'maipub_global_tab',
				'label'     => __( 'Sitewide', 'mai-publisher' ),
				'type'      => 'tab',
				'placement' => 'top',
			],
			[
				'label'   => '',
				'key'     => 'maipub_global_heading',
				'type'    => 'message',
				'message' => $this->get_section_heading( __( 'Sitewide Settings', 'mai-publisher' ) ),
			],
			[
				'label'        => __( 'Display location', 'mai-publisher' ),
				'instructions' => __( 'Location of content area globally on the site.', 'mai-publisher' ),
				'key'          => 'maipub_global_location',
				'name'         => 'maipub_global_location',
				'type'         => 'select',
				'allow_null'   => 1,
				'ajax'         => 1,
				'ui'           => 1,
				'choices'      => [],
			],
			[
				'key'       => 'maipub_single_tab',
				'label'     => __( 'Single Content', 'mai-publisher' ),
				'type'      => 'tab',
				'placement' => 'top',
			],
			[
				'label'   => '',
				'key'     => 'maipub_single_heading',
				'type'    => 'message',
				'message' => $this->get_section_heading( __( 'Single Content Settings', 'mai-publisher' ) ),
			],
			[
				'label'        => __( 'Display location', 'mai-publisher' ),
				'instructions' => __( 'Location of content area on single posts, pages, and custom post types.', 'mai-publisher' ),
				'key'          => 'maipub_single_location',
				'name'         => 'maipub_single_location',
				'type'         => 'select',
				'allow_null'   => 1,
				'ajax'         => 1,
				'ui'           => 1,
				'choices'      => [],
			],
			[
				'label'         => __( 'Content location', 'mai-publisher' ),
				'key'           => 'maipub_single_content_location',
				'name'          => 'maipub_single_content_location',
				'type'          => 'select',
				'default_value' => 'after',
				'choices'       => [
					'after'  => __( 'After elements', 'mai-publisher' ) . ' (div, p, ol, ul, blockquote, figure, iframe)',
					'before' => __( 'Before headings', 'mai-publisher' ) . ' (h2, h3)',
				],
				'conditional_logic' => [
					[
						[
							'field'    => 'maipub_single_location',
							'operator' => '==',
							'value'    => 'content',
						],
					],
				],
			],
			[
				'label'             => __( 'Element count', 'mai-publisher' ),
				'instructions'      => __( 'Count this many elements before displaying content. Use comma-separated values to repeat this ad after a different number of elements.', 'mai-publisher' ),
				'key'               => 'maipub_single_content_count',
				'name'              => 'maipub_single_content_count',
				'type'              => 'text',
				'append'            => __( 'elements', 'mai-publisher' ),
				'required'          => 1,
				'default_value'     => '4, 12, 20, 28, 36, 44, 56, 64',
				'conditional_logic' => [
					[
						[
							'field'    => 'maipub_single_location',
							'operator' => '==',
							'value'    => 'content',
						],
					],
				],
			],
			[
				'label'             => __( 'Comment count', 'mai-publisher' ),
				'instructions'      => __( 'Set comment interval for ad repetition.', 'mai-publisher' ),
				'key'               => 'maipub_single_comment_count',
				'name'              => 'maipub_single_comment_count',
				'type'              => 'number',
				'append'            => __( 'comments', 'mai-publisher' ),
				'required'          => 1,
				'default_value'     => 4,
				'min'               => 1,
				'step'              => 1,
				'conditional_logic' => [
					[
						[
							'field'    => 'maipub_single_location',
							'operator' => '==',
							'value'    => 'comments',
						],
					],
				],
			],
			[
				'label'        => __( 'Content types', 'mai-publisher' ),
				'instructions' => __( 'Show on entries of these content types.', 'mai-publisher' ),
				'key'          => 'maipub_single_types',
				'name'         => 'maipub_single_types',
				'type'         => 'select',
				'allow_null'   => 1,
				'ajax'         => 1,
				'ui'           => 1,
				'multiple'     => 1,
				'choices'      => [],
			],
			[
				'label'             => __( 'Taxonomy conditions', 'mai-publisher' ),
				'instructions'      => __( 'Show on entries with taxonomy conditions.', 'mai-publisher' ),
				'key'               => 'maipub_single_taxonomies',
				'name'              => 'maipub_single_taxonomies',
				'type'              => 'repeater',
				'collapsed'         => 'mai_single_taxonomy',
				'layout'            => 'block',
				'button_label'      => __( 'Add Taxonomy Condition', 'mai-publisher' ),
				'sub_fields'        => $this->get_taxonomies_sub_fields(),
				'conditional_logic' => [
					[
						'field'    => 'maipub_single_types',
						'operator' => '!=empty',
					],
				],
			],
			[
				'label'   => __( 'Taxonomies relation', 'mai-publisher' ),
				'key'     => 'maipub_single_taxonomies_relation',
				'name'    => 'maipub_single_taxonomies_relation',
				'type'    => 'select',
				'default' => 'AND',
				'choices' => [
					'AND' => __( 'And', 'mai-publisher' ),
					'OR'  => __( 'Or', 'mai-publisher' ),
				],
				'conditional_logic' => [
					[
						'field'    => 'maipub_single_types',
						'operator' => '!=empty',
					],
					[
						'field'    => 'maipub_single_taxonomies',
						'operator' => '>',
						'value'    => '1', // More than 1 row.
					],
				],
			],
			[
				'label'        => __( 'Keyword conditions', 'mai-publisher' ),
				'instructions' => __( 'Show on entries any of the following keyword strings. Comma-separate multiple keyword strings to check. Keyword search is case-insensitive.', 'mai-publisher' ),
				'key'          => 'maipub_single_keywords',
				'name'         => 'maipub_single_keywords',
				'type'         => 'text',
			],
			[
				'label'         => __( 'Author conditions', 'mai-publisher' ),
				'instructions'  => __( 'Show on entries with the following authors.', 'mai-publisher' ),
				'key'           => 'maipub_single_authors',
				'name'          => 'maipub_single_authors',
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
				'label'         => __( 'Include entries', 'mai-publisher' ),
				'instructions'  => __( 'Show on specific entries regardless of content type and taxonomy conditions.', 'mai-publisher' ),
				'key'           => 'maipub_single_entries',
				'name'          => 'maipub_single_entries',
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
				'label'         => __( 'Exclude entries', 'mai-publisher' ),
				'instructions'  => __( 'Hide on specific entries regardless of content type and taxonomy conditions.', 'mai-publisher' ),
				'key'           => 'maipub_single_exclude_entries',
				'name'          => 'maipub_single_exclude_entries',
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
				'label'     => __( 'Content Archives', 'mai-publisher' ),
				'key'       => 'maipub_archive_tab',
				'type'      => 'tab',
				'placement' => 'top',
			],
			[
				'label'   => '',
				'key'     => 'maipub_archive_heading',
				'type'    => 'message',
				'message' => $this->get_section_heading( __( 'Content Archive Settings', 'mai-publisher' ) ),
			],
			[
				'label'        => __( 'Display location', 'mai-publisher' ),
				'instructions' => __( 'Location of content area on archives.', 'mai-publisher' ),
				'key'          => 'maipub_archive_location',
				'name'         => 'maipub_archive_location',
				'type'         => 'select',
				'allow_null'   => 1,
				'ajax'         => 1,
				'ui'           => 1,
				'choices'      => [],
			],
			[
				'label'             => __( 'Entry/Row position', 'mai-publisher' ),
				'instructions'      => __( 'Display ads in these entry/row positions. Use comma-separated values to display multiple ads in various locations.', 'mai-publisher' ),
				'key'               => 'maipub_archive_content_count',
				'name'              => 'maipub_archive_content_count',
				'type'              => 'text',
				'required'          => 1,
				'default_value'     => '2, 7, 12, 17, 22, 27, 32',
				'wrapper'           => [ 'width' => '75' ],
				'conditional_logic' => [
					[
						[
							'field'    => 'maipub_archive_location',
							'operator' => '==',
							'value'    => 'entries',
						],
					],
				],
			],
			[
				'label'         => __( 'Entry/Row count', 'mai-publisher' ),
				'instructions'  => __( 'Whether to count entries or rows.', 'mai-publisher' ),
				'key'           => 'maipub_archive_content_item',
				'name'          => 'maipub_archive_content_item',
				'type'          => 'select',
				'default_value' => 'rows',
				'choices'       => [
					'rows'    => __( 'Rows', 'mai-publisher' ),
					'entries' => __( 'Entries', 'mai-publisher' ),
				],
				'wrapper'           => [ 'width' => '25' ],
				'conditional_logic' => [
					[
						[
							'field'    => 'maipub_archive_location',
							'operator' => '==',
							'value'    => 'entries',
						],
					],
				],
			],
			[
				'label'        => __( 'Post type archives', 'mai-publisher' ),
				'instructions' => __( 'Show on post type archives.', 'mai-publisher' ),
				'key'          => 'maipub_archive_types',
				'name'         => 'maipub_archive_types',
				'type'         => 'select',
				'allow_null'   => 1,
				'ajax'         => 1,
				'ui'           => 1,
				'multiple'     => 1,
				'choices'      => [],
			],
			[
				'label'        => __( 'Taxonomy archives', 'mai-publisher' ),
				'instructions' => __( 'Show on taxonomy archives.', 'mai-publisher' ),
				'key'          => 'maipub_archive_taxonomies',
				'name'         => 'maipub_archive_taxonomies',
				'type'         => 'select',
				'allow_null'   => 1,
				'ajax'         => 1,
				'ui'           => 1,
				'multiple'     => 1,
				'choices'      => [],
			],
			[
				'label'        => __( 'Term archives', 'mai-publisher' ),
				'instructions' => __( 'Show on specific term archives regardless of post type and taxonomy conditions. This field only shows 50 terms from each taxonomy. Search for any terms that aren\'t displayed by default.', 'mai-publisher' ),
				'key'          => 'maipub_archive_terms',
				'name'         => 'maipub_archive_terms',
				'type'         => 'select',
				'allow_null'   => 1,
				'ajax'         => 1,
				'ui'           => 1,
				'multiple'     => 1,
				'choices'      => [],
			],
			[
				'label'        => __( 'Exclude term archives', 'mai-publisher' ),
				'instructions' => __( 'Hide on specific term archives regardless of post type and taxonomy conditions. This field only shows 50 terms from each taxonomy. Search for any terms that aren\'t displayed by default.', 'mai-publisher' ),
				'key'          => 'maipub_archive_exclude_terms',
				'name'         => 'maipub_archive_exclude_terms',
				'type'         => 'select',
				'allow_null'   => 1,
				'ajax'         => 1,
				'ui'           => 1,
				'multiple'     => 1,
				'choices'      => [],
			],
			[
				'label'        => __( 'Includes', 'mai-publisher' ),
				'instructions' => 'Show on miscellaneous areas of the website.',
				'key'          => 'maipub_archive_includes',
				'name'         => 'maipub_archive_includes',
				'type'         => 'checkbox',
				'choices'      => [
					'author' => __( 'Author Archives', 'mai-publisher' ),
					'search' => __( 'Search Results', 'mai-publisher' ),
				],
			],
			[
				'key'       => 'maipub_scripts_tab',
				'label'     => __( 'Scripts', 'mai-publisher' ),
				'type'      => 'tab',
				'placement' => 'top',
			],
			[
				'label'        => __( 'Header scripts', 'mai-publisher' ),
				'instructions' => __( 'Add any scripts that will be included in the <code>head</code> tag any time this Ad is displayed.', 'mai-publisher' ),
				'key'          => 'maipub_header_scripts',
				'name'         => 'maipub_header_scripts',
				'type'         => 'textarea',
			],
			[
				'label'        => __( 'Footer scripts', 'mai-publisher' ),
				'instructions' => __( 'Add any scripts that will be included before the closing <code>body</code> tag any time this Ad is displayed.', 'mai-publisher' ),
				'key'          => 'maipub_footer_scripts',
				'name'         => 'maipub_footer_scripts',
				'type'         => 'textarea',
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
				'label'             => __( 'Taxonomy', 'mai-publisher' ),
				'key'               => 'maipub_single_taxonomy',
				'name'              => 'taxonomy',
				'type'              => 'select',
				'choices'           => [],
				'allow_null'   => 1,
				'ajax'              => 1,
				'ui'                => 1,
			],
			[
				'label'             => __( 'Terms', 'mai-publisher' ),
				'key'               => 'maipub_single_terms',
				'name'              => 'terms',
				'type'              => 'select',
				'choices'           => [],
				'allow_null'   => 1,
				'ajax'              => 1,
				'ui'                => 1,
				'multiple'          => 1,
				'conditional_logic' => [
					[
						[
							'field'    => 'maipub_single_taxonomy',
							'operator' => '!=empty',
						],
					],
				],
			],
			[
				'key'        => 'maipub_single_operator',
				'name'       => 'operator',
				'label'      => __( 'Operator', 'mai-publisher' ),
				'type'       => 'select',
				'default'    => 'IN',
				'choices'    => [
					'IN'     => __( 'In', 'mai-publisher' ),
					'NOT IN' => __( 'Not In', 'mai-publisher' ),
				],
				'conditional_logic' => [
					[
						[
							'field'    => 'maipub_single_taxonomy',
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
