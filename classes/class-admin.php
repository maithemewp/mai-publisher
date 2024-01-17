<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Admin {
	protected $descriptions;

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
		add_action( 'current_screen',                    [ $this, 'add_description' ] );
		add_filter( 'display_post_states',               [ $this, 'post_states' ], 10, 2 );
		add_filter( 'manage_mai_ad_posts_columns',       [ $this, 'add_columns' ] );
		add_action( 'manage_mai_ad_posts_custom_column', [ $this, 'display_columns' ], 10, 2 );
		add_filter( 'get_user_option_posts_list_mode',   [ $this, 'force_excerpt_mode' ], 10, 3 );
	}

	/**
	 * Check if on the main Mai Ad admin post list
	 * and force excerpt mode so descriptions/excerpts display.
	 *
	 * @since 0.23.0
	 *
	 * @param WP_Screen $current_screen The current screen object.
	 *
	 * @return void
	 */
	function add_description( $current_screen ) {
		if ( ! $current_screen || 'edit-mai_ad' !== $current_screen->id ) {
			return;
		}

		/**
		 * Adds CSS to hide the row actions until hovered.
		 *
		 * @since 0.23.0
		 *
		 * @return void
		 */
		add_action( 'admin_head', function() {
			?>
			<style>
				.mai-ad-status {
					box-sizing: border-box;
					display: inline-block;
					min-width: 64px;
					margin-bottom: 6px;
					padding: 2px 6px;
					color: white;
					text-align: center;
				}
				.mai-ad-status__draft {
					background: #50575e;
				}
				.mai-ad-status__active {
					background: #00a32a;
				}
				.mai-ad-status__inactive {
					background: #dba617;
				}
				.row-actions {
					opacity: 0;
					transition: opacity 0.1s ease-in-out;
				}
				#the-list tr:hover .row-actions,
				#the-list tr:focus .row-actions {
					opacity: 1;
				}
			</style>
			<?php
		});

		// Get user settings.
		global $_updated_user_settings;
		$_updated_user_settings = is_null( $_updated_user_settings ) ? [] : $_updated_user_settings;

		// If user settings are not set, force excerpt.
		if ( ! isset( $_updated_user_settings['posts_list_mode'] ) ) {
			$_updated_user_settings['posts_list_mode'] = 'excerpt';
		}

		// Get descriptions.
		$this->descriptions = [
			'before_header'            => __( 'Display large ads before the header. Typically uses leaderboard-wide.', 'mai-publisher' ),
			'after_header'             => __( 'Display large ads after the header. Typically uses leaderboard-wide.', 'mai-publisher' ),
			'before_loop'              => __( 'Display large ads before archive entries. Typically uses leaderboard-wide or billboard-wide.', 'mai-publisher' ),
			'before_entry'             => __( 'Display large ads before a single entry, before the title. The available space is limited by the content width. Typically uses leaderboard or billboard.', 'mai-publisher' ),
			'before_entry_content'     => __( 'Display ads before single entry content, after the title. The available space is limited by the content width. Typically uses leaderboard or billboard.', 'mai-publisher' ),
			'before_entry_content_toc' => __( 'Display an ad with a table of contents before single entry content. Typically uses rectangle-medium.', 'mai-publisher' ),
			'entries'                  => __( 'Display ads in between rows of archive entries. The available space is limited by the content width. Typically uses billboard or leaderboard.', 'mai-publisher' ),
			'entries_wide'             => __( 'Display large ads in between rows of archive entries. Typically uses billboard-wide or leaderboard-wide.', 'mai-publisher' ),
			'entries_native'           => __( 'N/A', 'mai-publisher' ),
			'content'                  => __( 'Display ads inside single entry content. The available space is limited by the content width. Typically uses leaderboard, billboard, or rectangle-large.', 'mai-publisher' ),
			'content_wide'             => __( 'Display large ads inside single entry content. Typically uses leaderboard-wide or billboard-wide.', 'mai-publisher' ),
			'content_video'            => __( 'Display video ads inside single entry content. Typically uses Mai Ad Video block.', 'mai-publisher' ),
			'recipe'                   => __( 'Display ads inside recipe cards from WP Recipe Maker.', 'mai-publisher' ),
			'after_entry_content'      => __( 'Display ads after single entry content. The available space is limited by the content width. Typically uses leaderboard, billboard, or rectangle-large.', 'mai-publisher' ),
			'after_entry'              => __( 'Display ads after a single entry. The available space is limited by the content width. Typically uses leaderboard, billboard, or rectangle-large.', 'mai-publisher' ),
			'comments'                 => __( 'Display ads in between comments. Typically uses billboard or medium-rectangle', 'mai-publisher' ),
			'after_loop'               => __( 'Display large ads after archive entries. Typically uses leaderboard-wide or billboard-wide.', 'mai-publisher' ),
			'before_sidebar_content'   => __( 'Display ads before the sidebar content. Typically uses rectangle-medium.', 'mai-publisher' ),
			'after_sidebar_content'    => __( 'Display ads after the sidebar content. Typically uses rectangle-medium.', 'mai-publisher' ),
			'before_footer'            => __( 'Display large ads before the footer. Typically uses leaderboard-wide or billboard-wide.', 'mai-publisher' ),
			'after_footer'             => __( 'Display large ads fixed to the footer of browser window. Typically uses leaderboard-wide.', 'mai-publisher' ),
		];

		/**
		 * Add descriptions as the post excerpt, so they display in excerpt mode.
		 *
		 * @since 0.23.0
		 *
		 * @param string  $excerpt The post excerpt.
		 * @param WP_Post $post    The post object.
		 *
		 * @return string
		 */
		add_filter( 'get_the_excerpt', function( $excerpt, $post ) {
			// Bail if we already have a custom excerpt.
			if ( $excerpt ) {
				return $excerpt;
			}

			// If we have a custom description.
			if ( isset( $this->descriptions[ $post->post_name ] ) ) {
				$excerpt = $this->descriptions[ $post->post_name ];
			}

			return $excerpt;

		}, 30, 2 );
	}

	/**
	 * Remove draft post state.
	 *
	 * @since 0.1.0
	 *
	 * @param array   $states Array of post states.
	 * @param WP_Post $post   Post object.
	 *
	 * @return array
	 */
	function post_states( $states, $post ) {
		if ( 'mai_ad' !== $post->post_type ) {
			return $states;
		}

		// Remove draft.
		unset( $states['draft'] );

		// Get default locations.
		$locations = maipub_get_locations();

		// If not a default location, add custom state.
		if ( ! isset( $this->descriptions[ $post->post_name ] ) ) {
			$states['custom'] = __( 'Custom', 'mai-publisher' );
		}

		return $states;
	}

	/**
	 * Adds the custom column.
	 *
	 * @since 0.1.0
	 *
	 * @param string[] $columns An associative array of column headings.
	 *
	 * @return array
	 */
	function add_columns( $columns ) {
		// Remove date column.
		unset( $columns['date'] );

		// Add custom column.
		$location                   = [ 'maipub_status' => __( 'Status', 'mai-publisher' ) ];
		$columns                    = maipub_array_insert_after( $columns, 'title', $location );
		$columns['maipub_ad_units'] = __( 'Ad Units', 'mai-publisher' );

		return $columns;
	}

	/**
	 * Adds the display taxonomy column after the title.
	 *
	 * @since 0.1.0
	 *
	 * @param string $column  The name of the column to display.
	 * @param int    $post_id The current post ID.
	 *
	 * @return void
	 */
	function display_columns( $column, $post_id ) {
		if ( 'maipub_status' === $column ) {
			$this->display_status( $post_id );
		}

		if ( 'maipub_ad_units' === $column ) {
			$this->display_ad_data( $post_id );
		}
	}

	/**
	 * Display status info.
	 *
	 * @since 0.7.0
	 *
	 * @param int $post_id The current post ID.
	 *
	 * @return void
	 */
	function display_status( $post_id ) {
		$html       = '';
		$global     = get_post_meta( $post_id, 'maipub_global_location', true );
		$single     = get_post_meta( $post_id, 'maipub_single_location', true );
		$singles    = get_post_meta( $post_id, 'maipub_single_types', true );
		$archive    = get_post_meta( $post_id, 'maipub_archive_location', true );
		$archives   = get_post_meta( $post_id, 'maipub_archive_types', true );
		$taxonomies = get_post_meta( $post_id, 'maipub_archive_taxonomies', true );
		$terms      = get_post_meta( $post_id, 'maipub_archive_terms', true );
		$choices    = maipub_get_location_choices();

		// Bail if no locations.
		if ( ! ( $global || ( $single && $singles ) || ( $archive && ( $archives || $taxonomies || $terms ) ) ) ) {
			echo '<strong class="mai-ad-status mai-ad-status__inactive">' . __( 'Inactive', 'mai-publisher' ) . '</strong><br>';
			return;
		}

		// Get post data.
		$post    = get_post( $post_id );
		$status  = $post->post_status;
		$content = $post->post_content;

		// If published with content.
		if ( 'publish' === $status && $content ) {
			$html .= '<strong class="mai-ad-status mai-ad-status__active">' . __( 'Active', 'mai-publisher' ) . '</strong><br>';
		}

		if ( 'draft' === $status ) {
			$html .= '<strong class="mai-ad-status mai-ad-status__draft">' . __( 'Draft', 'mai-publisher' ) . '</strong><br>';
		}

		// Global.
		if ( $global ) {
			$html .= sprintf( '%s (%s)', __( 'Global', 'mai-publisher' ), $choices['global'][ $global ] ) . '<br>';
		}

		// Single.
		if ( $single && $singles ) {
			$array = [];

			foreach ( $singles as $post_type ) {
				$object = get_post_type_object( $post_type );

				if ( $object ) {
					$array[] = $object->label;
				} elseif ( '*' === $post_type ) {
					$array[] = __( 'All post types', 'mai-publisher' );
				}
			}

			if ( $array ) {
				$html .= sprintf( '%s (%s) -- %s', __( 'Single', 'mai-publisher' ), $choices['single'][ $single ], implode( ' | ', $array ) ) . '<br>';
			}
		}

		// Archives.
		if ( $archive && ( $archives || $taxonomies || $terms ) ) {
			$array = [];

			if ( $archives ) {
				foreach ( $archives as $post_type ) {
					$object = get_post_type_object( $post_type );

					if ( $object ) {
						$array[] = $object->label;
					} elseif ( '*' === $post_type ) {
						$array[] = __( 'All post types', 'mai-publisher' );
					}
				}
			}

			if ( $taxonomies ) {
				foreach ( $taxonomies as $taxonomy ) {
					$object = get_taxonomy( $taxonomy );

					if ( $object ) {
						$array[] = $object->label;
					} elseif ( '*' === $taxonomy ) {
						$array[] = __( 'All taxonomies', 'mai-publisher' );
					}
				}
			}

			if ( $terms ) {
				foreach ( $terms as $term ) {
					$object = get_term( $term );

					if ( $object && ! is_wp_error( $object ) ) {
						$array[] = $object->name;
					}
				}

				if ( $array ) {
					$html .= sprintf( '%s (%s) -- %s', __( 'Terms', 'mai-publisher' ), $choices['archive'][ $archive ], implode( ' | ', $array ) ) . '<br>';
				}
			}

			if ( $array ) {
				$html .= sprintf( '%s (%s) -- %s', __( 'Archives', 'mai-publisher' ), $choices['archive'][ $archive ], implode( ' | ', $array ) ) . '<br>';
			}
		}

		echo wptexturize( $html );
	}

	/**
	 * Display location info.
	 *
	 * @since 0.7.0
	 *
	 * @param int $post_id The current post ID.
	 *
	 * @return void
	 */
	function display_ad_data( $post_id ) {
		$ad_units = maipub_get_config( 'ad_units' );
		$legacy   = maipub_get_legacy_ad_units();
		$post     = get_post( $post_id );
		$data     = $this->get_ad_unit_data( $post->post_content );

		if ( ! $data ) {
			return;
		}

		$sizes = [];

		foreach ( $data as $values ) {
			$slug    = $values['slug'];
			$targets = $values['targets'];

			// If GAM ad unit.
			if ( isset( $ad_units[ $slug ]['sizes'] ) ) {
				$label = $slug;

				if ( isset( $legacy[ $slug ] ) ) {
					$label .= ' <span style="color:red;">' . __( '(legacy)', 'mai-publisher' ) . '</span>';
				}

				$sizes[] = '<strong>' . $label . '</strong>: ' . $this->format_sizes( $ad_units[ $slug ]['sizes'] );
			}
			// Not GAM, only video for now.
			else {
				$sizes[] = '<strong>' . $slug . '</strong>: ' . __( 'video', 'mai-publisher' );
			}

			// Targets.
			foreach ( $targets as $key => $value ) {
				$sizes[] = '<strong>' . $key . '</strong>: ' . $value;
			}
		}

		if ( ! $sizes ) {
			return;
		}

		echo implode( '<br>', $sizes );
	}

	/**
	 * Gets ad unit data from post content.
	 *
	 * @since 0.7.0
	 *
	 * @param array|string $input The post content or parsed blocks.
	 *
	 * @return array
	 */
	function get_ad_unit_data( $input ) {
		$units  = [];
		$blocks = is_array( $input ) ? $input : parse_blocks( $input );

		$types       = get_field_object( 'maipub_ad_unit_type' );
		$types       = $types ? $types['choices'] : [];
		$positions   = get_field_object( 'maipub_ad_unit_position' );
		$positions   = $positions ? $positions['choices'] : [];
		$split_tests = get_field_object( 'maipub_ad_unit_split_test' );
		$split_tests = $split_tests ? $split_tests['choices'] : [];
		$videos      = get_field_object( 'maipub_ad_video_id' );
		$videos      = $videos ? $videos['choices'] : [];

		foreach ( $blocks as $block ) {
			if ( 'acf/mai-ad-unit' === $block['blockName'] && isset( $block['attrs']['data']['id'] ) && ! empty( $block['attrs']['data']['id'] ) ) {
				$array            = [];
				$array['slug']    = $block['attrs']['data']['id'];
				$array['targets'] = [];

				// Ad type.
				if ( isset( $block['attrs']['data']['type'] ) && ! empty( $block['attrs']['data']['type'] ) ) {
					$array['targets']['type'] = isset( $types[ $block['attrs']['data']['type'] ] ) ? $types[ $block['attrs']['data']['type'] ] : $block['attrs']['data']['type'];
				}

				// Position.
				if ( isset( $block['attrs']['data']['position'] ) && ! empty( $block['attrs']['data']['position'] ) ) {
					$array['targets']['position'] = isset( $positions[ $block['attrs']['data']['position'] ] ) ? $positions[ $block['attrs']['data']['position'] ] : $block['attrs']['data']['position'];
				}

				// Split test.
				if ( isset( $block['attrs']['data']['split_test'] ) && ! empty( $block['attrs']['data']['split_test'] ) ) {
					$array['targets']['split-test'] = isset( $split_tests[ $block['attrs']['data']['split_test'] ] ) ? $split_tests[ $block['attrs']['data']['split_test'] ] : $block['attrs']['data']['split_test'];
				}

				// Targets.
				if ( isset( $block['attrs']['data']['targets'] ) && ! empty( $block['attrs']['data']['targets'] ) ) {
					$array['targets']['targets'] = $block['attrs']['data']['targets'];
				}

				$units[] = $array;
			}

			if ( 'acf/mai-ad-video' === $block['blockName'] && isset( $block['attrs']['data']['id'] ) && ! empty( $block['attrs']['data']['id'] ) ) {
				$units[] = [
					'slug'    => isset( $videos[ $block['attrs']['data']['id'] ] ) ? sanitize_title_with_dashes( $videos[ $block['attrs']['data']['id'] ] ) : $block['attrs']['data']['id'],
					'targets' => [],
				];
			}
		}

		// If we have inner blocks, recurse.
		if ( isset( $block['innerBlocks'] ) && $block['innerBlocks'] ) {
			$units = array_merge( $units, $this->get_ad_unit_data( $block['innerBlocks'] ) );
		}

		return $units;
	}

	/**
	 * Format sizes for display in admin column.
	 *
	 * @since 0.7.0
	 *
	 * @param array $array The sizes array.
	 *
	 * @return string
	 */
	function format_sizes( $array ) {
		$result = array_map( function( $inner ) {
			return '[' . implode(', ', (array) $inner) . ']';
		}, $array );

		return implode( ', ', $result );
	}
}