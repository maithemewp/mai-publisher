<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Register widget.
 */
class Mai_Publisher_Ad_Widget extends WP_Widget {

	/**
	 * Register the widget.
	 *
	 * @since 0.7.0
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct(
			'maipub_ad_widget',
			esc_html__( 'Mai Ad', 'mai-publisher' ),
			[
				'description' => esc_html__( 'Display an existing Mai Ad in a widget area.', 'mai-publisher' ),
			]
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @since 0.7.0
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 *
	 * @return void
	 */
	public function widget( $args, $instance ) {
		if ( isset( $instance['ad'] ) && ! empty( $instance['ad'] ) ) {
			$content = trim( get_post_field( 'post_content', $instance['ad'] ) );

			if ( ! $content ) {
				return;
			}

			echo $args['before_widget'];

			// if ( ! empty( $instance['title'] ) ) {
			// 	echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
			// }

			echo maipub_get_processed_ad_content( $content );

			echo $args['after_widget'];
		}
	}

	/**
	 * Outputs the options form on admin.
	 *
	 * @since 0.7.0
	 *
	 * @param array $instance The widget options.
	 *
	 * @return void
	 */
	public function form( $instance ) {
		$title   = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$current = ! empty( $instance['ad'] ) ? $instance['ad'] : '';

		echo '<p>';
		printf( '<label for="%s">%s</label>', esc_attr( $this->get_field_id( 'title' ) ), esc_attr__( 'Admin Label:', 'mai-publisher' ) );
		printf( '<input class="widefat" id="%s" name="%s" type="text" value="%s">', esc_attr( $this->get_field_id( 'title' ) ), esc_attr( $this->get_field_name( 'title' ) ), esc_attr( $title ) );
		echo '</p>';

		$query = new WP_Query(
			[
				'post_type'              => 'mai_ad',
				'post_status'            => 'publish',
				'posts_per_page'         => 100,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		if ( $query->have_posts() ) {
			echo '<p>';
				printf( '<label for="%s">%s</label>', esc_attr( $this->get_field_id( 'ad' ) ), esc_attr__( 'Select from saved Mai Ads: ', 'mai-publisher' ) );
				printf( '<select class="widefat" id="%s" name="%s">', esc_attr( $this->get_field_id( 'ad' ) ), esc_attr( $this->get_field_name( 'ad' ) ) );
					printf( '<option values="">%s</option>', esc_html__( 'Select Mai Ad', 'mai-publisher' ) );

					while ( $query->have_posts() ) {
						$query->the_post();
						$selected = get_the_ID() === (int) $current ? ' selected="selected"' : '';
						printf( '<option value="%s"%s>%s</option>', get_the_ID(), $selected, get_the_title() );
					}
				echo '</select>';
			echo '</p>';

			if ( $current ) {
				echo '<p style="font-size:11px;line-height:13px;">';
					printf( '<a href="%s">%s</a>', get_edit_post_link( $current ), esc_html__( 'Edit the currently saved ad.', 'mai-publisher' ) );
				echo '</p>';
			}

		} else {
			printf( '<p>%s</p>', esc_attr__( 'No saved ads yet.', 'mai-publisher' ) );
		}

		wp_reset_postdata();
	}

	/**
	 * Processing widget options on save.
	 *
	 * @since 0.7.0
	 *
	 * @param array $new_instance The new options.
	 * @param array $old_instance The previous options.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = [];
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['ad']    = ( ! empty( $new_instance['ad'] ) ) ? sanitize_text_field( $new_instance['ad'] ) : '';

		return $instance;
	}
}

/**
 * Register widget.
 *
 * @since 0.7.0
 *
 * @return void
 */
add_action( 'widgets_init', function() {
	register_widget( 'Mai_Publisher_Ad_Widget' );
});
