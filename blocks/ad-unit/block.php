<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_GAM_Ad_Unit_Block {

	/**
	 * Construct the class.
	 */
	function __construct() {
		$this->hooks();
	}

	/**
	 * Callback function to render the block.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $block      The block settings and attributes.
	 * @param string $content    The block inner HTML (empty).
	 * @param bool   $is_preview True during AJAX preview.
	 * @param int    $post_id    The post ID this block is saved to.
	 *
	 * @return void
	 */
	public static function do_block( $block, $content = '', $is_preview = false, $post_id = 0 ) {
		echo '<h2>TBD</h2>';
	}

	/**
	 * Add hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function hooks() {
		add_action( 'acf/init', [ $this, 'register_block' ] );
		add_action( 'acf/init', [ $this, 'register_field_group' ] );
	}

	/**
	 * Register block.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	function register_block() {
		register_block_type( __DIR__ . '/block.json' );
	}

	function register_field_group() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		return;

		acf_add_local_field_group(
			[
				'key'      => 'mai_field_group',
				'title'    => __( 'Locations Settings', 'mai-gam' ),
				'fields'   => $this->get_fields(),
				'location' => [
					[
						[
							'param'    => 'mai_ad',
							'operator' => '==',
							'value'    => 'custom',
						],
					],
				],
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
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
				'key'               => 'mai_single_tab',
				'label'             => __( 'Single Content', 'mai-gam' ),
				'type'              => 'tab',
				'placement'         => 'top',
			],
			[
				'label'             => '',
				'key'               => 'mai_single_heading',
				'type'              => 'message',
				'message'           => sprintf( '<h2 style="padding:0;margin:0;font-size:18px;">%s</h2>', __( 'Single Content Settings', 'mai-gam' ) ),
			],
			[
				'label'        => __( 'Display location', 'mai-gam' ),
				'instructions' => __( 'Location of content area on single posts, pages, and custom post types.', 'mai-gam' ),
				'key'          => 'mai_single_location',
				'name'         => 'mai_single_location',
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
				'label'             => __( 'Content location', 'mai-gam' ),
				'key'               => 'mai_single_content_location',
				'name'              => 'mai_single_content_location',
				'type'              => 'select',
				'default_value'     => 'after',
				'choices'           => [
					'after'  => __( 'After elements', 'mai-gam' ) . ' (div, p, ol, ul, blockquote, figure, iframe)',
					'before' => __( 'Before headings', 'mai-gam' ) . ' (h2, h3)',
				],
				'conditional_logic' => [
					[
						[
							'field'    => 'mai_single_location',
							'operator' => '==',
							'value'    => 'content',
						],
					],
				],
			],
			[
				'label'             => __( 'Element count', 'mai-gam' ),
				'instructions'      => __( 'Count this many elements before displaying content.', 'mai-gam' ),
				'key'               => 'mai_single_content_count',
				'name'              => 'mai_single_content_count',
				'type'              => 'number',
				'append'            => __( 'elements', 'mai-gam' ),
				'required'          => 1,
				'default_value'     => 6,
				'min'               => 1,
				'step'              => 1,
				'conditional_logic' => [
					[
						[
							'field'    => 'mai_single_location',
							'operator' => '==',
							'value'    => 'content',
						],
					],
				],
			],
			[
				'label'             => __( 'Content types', 'mai-gam' ),
				'instructions'      => __( 'Show on entries of these content types.', 'mai-gam' ),
				'key'               => 'mai_single_types',
				'name'              => 'mai_single_types',
				'type'              => 'select',
				'ui'                => 1,
				'multiple'          => 1,
				'choices'           => [],
			],
			[
				'label'             => __( 'Keyword conditions', 'mai-gam' ),
				'instructions'      => __( 'Show on entries any of the following keyword strings. Comma-separate multiple keyword strings to check. Keyword search is case-insensitive.', 'mai-gam' ),
				'key'               => 'mai_single_keywords',
				'name'              => 'mai_single_keywords',
				'type'              => 'text',
			],
			[
				'label'             => __( 'Taxonomy conditions', 'mai-gam' ),
				'instructions'      => __( 'Show on entries with taxonomy conditions.', 'mai-gam' ),
				'key'               => 'mai_single_taxonomies',
				'name'              => 'mai_single_taxonomies',
				'type'              => 'repeater',
				'collapsed'         => 'mai_single_taxonomy',
				'layout'            => 'block',
				'button_label'      => __( 'Add Taxonomy Condition', 'mai-gam' ),
				'sub_fields'        => $this->get_taxonomies_sub_fields(),
				'conditional_logic' => [
					[
						'field'    => 'mai_single_types',
						'operator' => '!=empty',
					],
				],
			],
			[
				'label'             => __( 'Taxonomies relation', 'mai-gam' ),
				'key'               => 'mai_single_taxonomies_relation',
				'name'              => 'mai_single_taxonomies_relation',
				'type'              => 'select',
				'default'           => 'AND',
				'choices'           => [
					'AND' => __( 'And', 'mai-gam' ),
					'OR'  => __( 'Or', 'mai-gam' ),
				],
				'conditional_logic' => [
					[
						'field'    => 'mai_single_types',
						'operator' => '!=empty',
					],
					[
						'field'    => 'mai_single_taxonomies',
						'operator' => '>',
						'value'    => '1', // More than 1 row.
					],
				],
			],
			[
				'label'         => __( 'Author conditions', 'mai-gam' ),
				'instructions'  => __( 'Show on entries with the following authors.', 'mai-gam' ),
				'key'           => 'mai_single_authors',
				'name'          => 'mai_single_authors',
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
				'label'             => __( 'Include entries', 'mai-gam' ),
				'instructions'      => __( 'Show on specific entries regardless of content type and taxonomy conditions.', 'mai-gam' ),
				'key'               => 'mai_single_entries',
				'name'              => 'mai_single_entries',
				'type'              => 'relationship',
				'required'          => 0,
				'post_type'         => '',
				'taxonomy'          => '',
				'min'               => '',
				'max'               => '',
				'return_format'     => 'id',
				'filters'           => [
					'search',
					'post_type',
					'taxonomy',
				],
				'elements'          => [
					'featured_image',
				],
			],
			[
				'label'             => __( 'Exclude entries', 'mai-gam' ),
				'instructions'      => __( 'Hide on specific entries regardless of content type and taxonomy conditions.', 'mai-gam' ),
				'key'               => 'mai_single_exclude_entries',
				'name'              => 'mai_single_exclude_entries',
				'type'              => 'relationship',
				'required'          => 0,
				'post_type'         => '',
				'taxonomy'          => '',
				'min'               => '',
				'max'               => '',
				'return_format'     => 'id',
				'filters'           => [
					'search',
					'post_type',
					'taxonomy',
				],
				'elements'          => [
					'featured_image',
				],
			],
			// This proved too tricky (for now) since 404 doesn't have genesis_before_entry and _entry_content hooks.
			// [
			// 	'label'        => __( 'Includes', 'mai-gam' ),
			// 	'instructions' => 'Show on miscellaneous areas of the website.',
			// 	'key'          => 'mai_single_includes',
			// 	'name'         => 'mai_single_includes',
			// 	'type'         => 'checkbox',
			// 	'choices'      => [
			// 		'404-page' => __( '404 Page', 'mai-gam' ),
			// 	],
			// ],
			[
				'label'             => __( 'Content Archives', 'mai-gam' ),
				'key'               => 'mai_archive_tab',
				'type'              => 'tab',
				'placement'         => 'top',
			],
			[
				'label'             => '',
				'key'               => 'mai_archive_heading',
				'type'              => 'message',
				'message'           => sprintf( '<h2 style="padding:0;margin:0;font-size:18px;">%s</h2>', __( 'Content Archive Settings', 'mai-gam' ) ),
			],
			[
				'label'        => __( 'Display location', 'mai-gam' ),
				'instructions' => __( 'Location of content area on archives.', 'mai-gam' ),
				'key'          => 'mai_archive_location',
				'name'         => 'mai_archive_location',
				'type'         => 'select',
				'choices'      => [
					''                     => __( 'None (inactive)', 'mai-gam' ),
					'before_header'        => __( 'Before header', 'mai-gam' ),
					'after_header'         => __( 'After header', 'mai-gam' ),
					'before_loop'          => __( 'Before entries', 'mai-gam' ),
					'entries'              => __( 'In entries', 'mai-gam' ), // TODO: Is this doable without breaking columns, etc?
					'after_loop'           => __( 'After entries', 'mai-gam' ),
					'before_footer'        => __( 'Before footer', 'mai-gam' ),
				],
			],
			// [
			// 	'label'             => __( 'Content location', 'mai-gam' ),
			// 	'key'               => 'mai_archive_content_location',
			// 	'name'              => 'mai_archive_content_location',
			// 	'type'              => 'select',
			// 	'default_value'     => 'after',
			// 	'choices'           => [
			// 		'after'  => __( 'After rows', 'mai-gam' ),
			// 		'before' => __( 'Before rows', 'mai-gam' ),
			// 	],
			// 	'conditional_logic' => [
			// 		[
			// 			[
			// 				'field'    => 'mai_archive_location',
			// 				'operator' => '==',
			// 				'value'    => 'entries',
			// 			],
			// 		],
			// 	],
			// ],
			[
				'label'             => __( 'Row count', 'mai-gam' ),
				'instructions'      => __( 'Count this many rows of entries before displaying content.', 'mai-gam' ),
				'key'               => 'mai_archive_content_count',
				'name'              => 'mai_archive_content_count',
				'type'              => 'number',
				'append'            => __( 'entries', 'mai-gam' ),
				'required'          => 1,
				'default_value'     => 3,
				'min'               => 1,
				'step'              => 1,
				'conditional_logic' => [
					[
						[
							'field'    => 'mai_archive_location',
							'operator' => '==',
							'value'    => 'entries',
						],
					],
				],
			],
			[
				'label'        => __( 'Post type archives', 'mai-gam' ),
				'instructions' => __( 'Show on post type archives.', 'mai-gam' ),
				'key'          => 'mai_archive_types',
				'name'         => 'mai_archive_types',
				'type'         => 'select',
				'ui'           => 1,
				'multiple'     => 1,
				'choices'      => [],
			],
			[
				'label'        => __( 'Taxonomy archives', 'mai-gam' ),
				'instructions' => __( 'Show on taxonomy archives.', 'mai-gam' ),
				'key'          => 'mai_archive_taxonomies',
				'name'         => 'mai_archive_taxonomies',
				'type'         => 'select',
				'ui'           => 1,
				'multiple'     => 1,
				'choices'      => [],
			],
			[
				'label'         => __( 'Term archives', 'mai-gam' ),
				'instructions'  => __( 'Show on specific term archives.', 'mai-gam' ),
				'key'           => 'mai_archive_terms',
				'name'          => 'mai_archive_terms',
				'type'         => 'select',
				'ui'           => 1,
				'multiple'     => 1,
				'choices'      => [],
			],
			[
				'label'         => __( 'Exclude term archives', 'mai-gam' ),
				'instructions'  => __( 'Hide on specific term archives.', 'mai-gam' ),
				'key'           => 'mai_archive_exclude_terms',
				'name'          => 'mai_archive_exclude_terms',
				'type'         => 'select',
				'ui'           => 1,
				'multiple'     => 1,
				'choices'      => [],
			],
			[
				'label'        => __( 'Includes', 'mai-gam' ),
				'instructions' => 'Show on miscellaneous areas of the website.',
				'key'          => 'mai_archive_includes',
				'name'         => 'mai_archive_includes',
				'type'         => 'checkbox',
				'choices'      => [
					'search'   => __( 'Search Results', 'mai-gam' ),
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
				'key'               => 'mai_single_taxonomy',
				'name'              => 'taxonomy',
				'type'              => 'select',
				'choices'           => [],
				'ui'                => 1,
				'ajax'              => 1,
			],
			[
				'label'             => __( 'Terms', 'mai-gam' ),
				'key'               => 'mai_single_terms',
				'name'              => 'terms',
				'type'              => 'select',
				'choices'           => [],
				'ui'                => 1,
				'ajax'              => 1,
				'multiple'          => 1,
				'conditional_logic' => [
					[
						[
							'field'    => 'mai_single_taxonomy',
							'operator' => '!=empty',
						],
					],
				],
			],
			[
				'key'        => 'mai_single_operator',
				'name'       => 'operator',
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
							'field'    => 'mai_single_taxonomy',
							'operator' => '!=empty',
						],
					],
				],
			],
		];
	}

}

function maigam_do_ad_unit_block( $block, $content = '', $is_preview = false, $post_id = 0 ) {
	Mai_GAM_Ad_Unit_Block::do_block( $block, $content, $is_preview, $post_id );
}

new Mai_GAM_Ad_Unit_Block;


// add_filter( 'acf/load_field/key=field_5dd6bca5fa5c6', 'mai_notice_load_type_choices' );
/**
 * Load type choices.
 *
 * @since TBD
 *
 * @param array $field The field data.
 *
 * @return array
 */
function mai_notice_load_type_choices( $field ) {
	$field['choices'] = [];
	$types            = mai_notice_get_types();

	foreach( $types as $name => $type ) {
		$field['choices'][ $name ] = $type['title'];

		if ( isset( $type['default'] ) && $type['default'] ) {
			$field['default'] = $name;
		}
	}

	return $field;
}
