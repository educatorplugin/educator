<?php

class Edr_Widget_LessonsList extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'lessons_list',
			__( 'Lessons List', 'educator' ),
			array(
				'description' => __( 'List lessons. This widget should be used the single lesson page.', 'educator' ),
			)
		);
	}

	public function widget( $args, $instance ) {
		$lesson_id = get_the_ID();

		if ( ! $lesson_id || EDR_PT_LESSON != get_post_type( $lesson_id ) ) {
			return;
		}

		echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'], apply_filters( 'widget_title', $instance['title'] ),
				$args['after_title'];
		}

		$obj_courses = Edr_Courses::get_instance();
		$course_id = $obj_courses->get_course_id( $lesson_id );
		$syllabus = $obj_courses->get_syllabus( $course_id );

		if ( ! empty( $syllabus ) ) {
			Edr_View::the_template( 'widgets/lessons-list-syllabus', array(
				'syllabus' => $syllabus,
				'lessons'  => $obj_courses->get_syllabus_lessons( $syllabus ),
			) );
		} else {
			Edr_View::the_template( 'widgets/lessons-list-all', array(
				'lessons' => $obj_courses->get_course_lessons( $course_id ),
			) );
		}

		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title']
			: __( 'Lessons', 'educator' );
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
