<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Entries {
	protected $page_ads;
	protected $archive_ads;
	protected $index;

	/**
	 * Construct the class.
	 */
	function __construct() {
		add_action( 'template_redirect',                   [ $this, 'get_page_ads' ] );
		add_filter( 'mai_grid_args',                       [ $this, 'add_grid_args' ] );
		add_filter( 'genesis_attr_entries-wrap',           [ $this, 'add_attributes' ], 10, 3 );
		add_filter( 'genesis_markup_entries_content',      [ $this, 'add_css' ], 10, 2 );
		add_filter( 'genesis_markup_entries-wrap_content', [ $this, 'add_ads' ], 10, 2 );
		add_action( 'mai_after_entry',                     [ $this, 'increment_index' ], 10, 2 );
		add_action( 'acf/init',                            [ $this, 'register_grid_field_group' ] );
		// add_action( 'admin_footer',                        [ $this, 'settings_generate_file' ] );
		// add_action( 'customize_save_after',                [ $this, 'customizer_generate_file' ] );
	}

	/**
	 * Do entries relate things.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	function get_page_ads() {
		$this->page_ads = maipub_get_page_ads();
	}

	/**
	 * Adds custom grid fields as args.
	 *
	 * @since 1.1.0
	 *
	 * @param array $args The existing grid args.
	 *
	 * @return array
	 */
	function add_grid_args( $args ) {
		$args['ad_unit_id']            = get_field( 'maipub_ad_unit_id' );
		$args['ad_unit_content_count'] = array_map( 'absint', array_filter( explode( ',', (string) get_field( 'maipub_ad_unit_content_count' ) ) ) );
		$args['ad_unit_content_item']  = get_field( 'maipub_ad_unit_content_item' );
		$args['ad_unit_type']          = get_field( 'maipub_ad_unit_type' );
		$args['ad_unit_position']      = get_field( 'maipub_ad_unit_position' );
		$args['ad_unit_split_test']    = get_field( 'maipub_ad_unit_split_test' );
		$args['ad_unit_targets']       = get_field( 'maipub_ad_unit_targets' );
		$args['ad_unit_label']         = get_field( 'maipub_ad_unit_label' );
		$args['ad_unit_label_hide']    = get_field( 'maipub_ad_unit_label_hide' );

		return $args;
	}

	/**
	 * Adds custom attributes for ordering ads inside entries.
	 *
	 * @since 1.1.0
	 *
	 * @param array  $attr    The existing attributes.
	 * @param string $context The context.
	 * @param array  $args    The layout args.
	 *
	 * @return array
	 */
	function add_attributes( $attr, $context, $args ) {
		// Sets/resets index consecutive grids/entries.
		$this->index = 0;

		// Bail if not valid entries.
		if ( ! $this->validate_args( $args ) ) {
			return $attr;
		}

		// Get columns.
		$columns = mai_get_breakpoint_columns( $args['params']['args'] );

		// Bail if no columns.
		if ( ! $columns ) {
			return $attr;
		}

		// Set style.
		$attr['style'] = isset( $attr['style'] ) ? $attr['style'] : '';

		// Build styles array.
		$styles = array_filter( explode( ';' , $attr['style'] ) );
		$styles = array_map( 'trim', $styles );

		// Add row breakpoint styles.
		foreach ( $columns as $break => $column ) {
			$styles[] = sprintf( '--maipub-row-%s:%s;', $break, $column );
		}

		// Set new styles.
		$attr['style'] = implode( ';', $styles ) . ';';

		return $attr;
	}

	/**
	 * Adds CSS via `<link>` after opening entries div.
	 *
	 * @since 1.1.0
	 *
	 * @param string $content The existing content.
	 * @param array  $args    The layout args.
	 *
	 * @return string
	 */
	function add_css( $content, $args ) {
		// Bail if not the opening element.
		if ( ! $args['open'] ) {
			return $content;
		}

		// Bail if not valid entries.
		if ( ! $this->validate_args( $args ) ) {
			return $content;
		}

		// Set inserted flag.
		static $inserted = false;

		// Bail if already inserted.
		if ( $inserted ) {
			return $content;
		}

		$inserted = true;

		// Get file (with suffix) and add it after the existing content.
		$suffix   = maipub_get_suffix();
		$file     = "assets/css/mai-engine-entries{$suffix}.css";
		$content .= sprintf( '<link href="%s" rel="stylesheet">', maipub_get_file_data( $file, 'url' ) );

		return $content;
	}

	/**
	 * Adds ads before the entries closing div.
	 * Adds attributes for ordering.
	 *
	 * @since 1.1.0
	 *
	 * @param string $content The existing content.
	 * @param array  $args    The layout args.
	 *
	 * @return string
	 */
	function add_ads( $content, $args ) {
		// Bail if not the closing element.
		if ( ! $args['close'] ) {
			return $content;
		}

		// Bail if not valid entries.
		if ( ! $this->validate_args( $args ) ) {
			return $content;
		}

		// Get ads by context.
		switch ( $args['params']['args']['context'] ) {
			case 'archive':
				$content .= $this->get_archive_ads( $args );
			break;
			case 'block':
				$content .= $this->get_block_ads( $args );
			break;
		}

		return $content;
	}

	/**
	 * Gets ads for archives.
	 * Adds attributes for ordering.
	 *
	 * @since 1.1.0
	 *
	 * @param array $args The archive args.
	 *
	 * @return string
	 */
	function get_archive_ads( $args ) {
		// Set index.
		static $i = -1;
		$i++;

		// Bail if no more archive ads or missing args.
		if ( ! ( isset( $this->archive_ads[ $i ] ) && isset( $args['params']['args'] ) ) ) {
			return;
		}

		// Override args.
		$args = $args['params']['args'];

		// Build ad array.
		$ad            = $this->archive_ads[ $i ];
		$ad['columns'] = mai_get_breakpoint_columns( $args );

		return $this->get_ads( $ad, $args );
	}

	/**
	 * Gets ads for grid blocks.
	 * Adds attributes for ordering.
	 *
	 * @since 1.1.0
	 *
	 * @param array $args The grid args.
	 *
	 * @return string
	 */
	function get_block_ads( $args ) {
		// Bail if missing args.
		if ( ! isset( $args['params']['args'] ) ) {
			return;
		}

		// Override args.
		$args = $args['params']['args'];

		// Build ad array.
		$ad = [];
		ob_start();
		maipub_do_ad_unit(
			[
				'preview'    => is_admin(),
				'id'         => $args['ad_unit_id'],
				'type'       => $args['ad_unit_type'],
				'position'   => $args['ad_unit_position'],
				'split_test' => $args['ad_unit_split_test'],
				'targets'    => $args['ad_unit_targets'],
				'label'      => $args['ad_unit_label'],
				'label_hide' => $args['ad_unit_label_hide'],
			]
		);
		$ad['content']       = ob_get_clean();
		$ad['content_count'] = $args['ad_unit_content_count'];
		$ad['content_item']  = $args['ad_unit_content_item'];
		$ad['columns']       = mai_get_breakpoint_columns( $args );

		return $this->get_ads( $ad, $args );
	}

	/**
	 * Gets ads with attributes.
	 *
	 * @since 1.1.0
	 *
	 * @param array $ad   The add args.
	 * @param array $args The layout args.
	 *
	 * @return array
	 */
	function get_ads( $ad, $args ) {
		$ads     = '';
		$class   = [];
		$style   = [];
		$compare = null;

		// Build atts.
		switch ( $ad['content_item'] ) {
			// Get desktop rows and round up.
			case 'rows':
				$class[] = 'maipub-row';
				$per_row = $this->index / (int) $ad['columns']['lg'];
				$compare = absint( ceil( $per_row ) );
				break;
			// Entries compares to the number of entries.
			case 'entries':
				$class[] = 'maipub-entry';
				$class[] = 'entry-archive';
				$class[] = 'is-column';

				if ( isset( $args['boxed'] ) && $args['boxed'] ) {
					$class[] = 'entry'; // This adds styling for the entry in Mai.
				}

				$compare = $this->index;
				break;
		}

		// Sort counts lowest to highest.
		asort( $ad['content_count'] );

		// If comparing.
		if ( ! is_null( $compare ) ) {
			// Loop through ad count to insert.
			foreach ( $ad['content_count'] as $index => $count ) {
				// Comparing rows/entries plus ads.
				$compare_with_ads = $compare + ( $index + 1 );

				// If this ad is past the compare amount with ads, remove it and any after it.
				if ( (int) $count > $compare_with_ads ) {
					// Remove this one and any after it, and break.
					$ad['content_count'] = array_slice( $ad['content_count'], 0, $index );
					break;
				}
			}
		}

		// Loop through each ad count.
		foreach ( $ad['content_count'] as $index => $count ) {
			$order        = $count - ( $index + 1 );
			$item_class   = $class;
			$item_style   = $style;
			$item_style[] = 'rows' === $ad['content_item'] ? "order:calc(var(--maipub-row) * {$order})" : "order:{$order}";
			$item_atts    = [
				'class' => trim( implode( ' ', $item_class ) ),
				'style' => trim( implode( ';', $item_style ) ),
			];

			// Build ad with wrapper.
			$ads .= sprintf( '<div%s>%s</div>', maipub_build_attributes( $item_atts ), $ad['content'] );
		}

		return $ads;
	}

	/**
	 * Increments the entry index each time one is rendered.
	 *
	 * @since 1.1.0
	 *
	 * @param object $entry The entry object.
	 * @param array  $args  The layout args.
	 *
	 * @return void
	 */
	function increment_index( $entry, $args ) {
		// Increment index.
		$this->index++;
	}

	/**
	 * Whether the args are valid for the context and location we want.
	 *
	 * @since 1.1.0
	 *
	 * @param array $args The existing args.
	 *
	 * @return bool
	 */
	function validate_args( $args ) {
		if ( ! isset( $args['params']['args'] ) ) {
			return false;
		}

		// Set args and context.
		$args    = $args['params']['args'];
		$context = isset( $args['context'] ) ? $args['context'] : false;

		// Bail if not a valid context.
		if ( ! $context || ! in_array( $context, [ 'archive', 'block' ] ) ) {
			return false;
		}

		switch ( $context ) {
			case 'archive':
				$this->archive_ads = [];

				foreach ( $this->page_ads as $ad ) {
					if ( ! isset( $ad['location'] ) || 'entries' !== $ad['location'] ) {
						continue;
					}

					$this->archive_ads[] = $ad;
				}

				if ( $this->archive_ads ) {
					return true;
				}
			break;
			case 'block':
				if ( $args['ad_unit_id'] && $args['ad_unit_content_count'] && $args['ad_unit_content_item'] ) {
					return true;
				}
			break;
		}

		return false;
	}

	/**
	 * Register field groups for the grid block.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	function register_grid_field_group() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		// Register fields so we can order them how we want as clone fields.
		acf_add_local_field_group(
			[
				'key'      => 'maipub_grid_clone_field_group',
				'title'    => __( 'Mai Publisher', 'mai-publisher' ),
				'fields'   => [
					[
						'label'         => __( 'Entry/Row position', 'mai-publisher' ),
						'instructions'  => __( 'Display ads in these entry/row positions. Use comma-separated values to display multiple ads in various locations.', 'mai-publisher' ),
						'key'           => 'maipub_ad_unit_content_count',
						'name'          => 'maipub_ad_unit_content_count',
						'type'          => 'text',
						'default_value' => '3, 6, 9, 12, 14, 17, 20, 23, 26, 29, 32, 35, 38, 41, 44',
					],
					[
						'label'         => __( 'Entry/Row count', 'mai-publisher' ),
						'instructions'  => __( 'Whether to count entries or rows.', 'mai-publisher' ),
						'key'           => 'maipub_ad_unit_content_item',
						'name'          => 'maipub_ad_unit_content_item',
						'type'          => 'select',
						'default_value' => 'entries',
						'choices'       => [
							'rows'    => __( 'Rows', 'mai-publisher' ),
							'entries' => __( 'Entries', 'mai-publisher' ),
						],
					],
				],
				'location' => false,
				'active'   => true,
			]
		);

		acf_add_local_field_group(
			[
				'key'      => 'maipub_grid_field_group',
				'title'    => __( 'Mai Publisher Post/Term Grid', 'mai-publisher' ),
				'fields'   => [
					[
						'label'        => __( 'Mai Publisher', 'mai-publisher' ),
						'key'          => 'maipub_ad_unit_panel',
						'type'         => 'accordion',
						'open'         => 0,
						'multi_expand' => 1,
						'endpoint'     => 0,
					],
					[
						'label'        => __( 'Mai Publisher', 'mai-publisher' ),
						'key'          => 'maipub_ad_unit',
						'name'         => 'maipub_ad_unit',
						'type'         => 'clone',
						'display'      => 'seamless',
						'layout'       => 'block',
						'prefix_label' => 0,
						'prefix_name'  => 0,
						'clone'        => [
							'maipub_ad_unit_id',
							'maipub_ad_unit_content_count',
							'maipub_ad_unit_content_item',
							'maipub_ad_unit_type',
							'maipub_ad_unit_position',
							'maipub_ad_unit_split_test',
							'maipub_ad_unit_targets',
							'maipub_ad_unit_label',
							'maipub_ad_unit_label_hide',
						],
					],
				],
				'location' => [
					[
						[
							'param'    => 'block',
							'operator' => '==',
							'value'    => 'acf/mai-post-grid',
						],
					],
					[
						[
							'param'    => 'block',
							'operator' => '==',
							'value'    => 'acf/mai-term-grid',
						],
					],
				],
				'active' => true,
			]
		);
	}

	/**
	 * Maybe generate CSS for Mai Engine native ads.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	function settings_generate_file() {
		$screen = get_current_screen();

		if ( ! $screen || 'mai_ad_page_settings' !== $screen->id ) {
			return;
		}

		maipub_generate_mai_engine_css();
	}

	/**
	 * Force generate CSS.
	 *
	 * @since 1.1.0
	 *
	 * @param WP_Customize_Manager $customizer The customizer manager.
	 *
	 * @return void
	 */
	function customizer_generate_file( $customizer ) {
		// Maybe generate. This will generate if the file does not exist.
		$generate = maipub_generate_mai_engine_css();

		// Bail if generated on save.
		if ( $generate ) {
			return;
		}

		foreach ( $customizer->changeset_data() as $key => $value ) {
			if ( str_starts_with( $key, 'mai-engine[color-' ) ) {
				$generate = true;
				break;
			}

			if ( str_starts_with( $key, 'mai-engine[body-typography' ) ) {
				$generate = true;
				break;
			}

			if ( str_starts_with( $key, 'mai-engine[heading-typography' ) ) {
				$generate = true;
				break;
			}
		}

		// Bail if not saving anything that matters.
		if ( ! $generate ) {
			return;
		}

		// Force generate.
		maipub_generate_mai_engine_css( true );
	}
}