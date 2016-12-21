<?php

class Edr_Widget_CourseCategories extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'course_categories',
			__( 'Course Categories', 'educator' ),
			array(
				'description' => __( 'List course categories.', 'educator' ),
			)
		);
	}

	public function widget( $args, $instance ) {
		echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'], apply_filters( 'widget_title', $instance['title'] ),
				$args['after_title'];
		}

		echo '<ul class="course-categories-list">';
		wp_list_categories( array(
			'taxonomy'     => EDR_TX_CATEGORY,
			'title_li'     => '',
			'order_by'     => 'name',
			'hierarchical' => true,
		) );
		echo '</ul>';

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title']
			: __( 'Course Categories', 'educator' );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title', 'educator' ); ?></label>
			<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ! empty( $new_instance['title'] ) ? esc_html( $new_instance['title'] ) : '';

		return $instance;
	}
}
