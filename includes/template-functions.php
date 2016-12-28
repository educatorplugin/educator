<?php

function edr_filter_single_course_content( $content ) {
	$post = get_post();

	if ( ! empty( $post ) && EDR_PT_COURSE == $post->post_type && is_singular( $post->post_type ) && is_main_query() && in_the_loop() ) {
		ob_start();
		do_action( 'edr_before_single_course_content', $post->ID );
		$content = ob_get_contents() . $content;
		ob_clean();
		do_action( 'edr_after_single_course_content', $post->ID );
		$content .= ob_get_clean();
	}

	return $content;
}

function edr_filter_single_lesson_content( $content ) {
	$post = get_post();

	if ( ! empty( $post ) && EDR_PT_LESSON == $post->post_type && is_singular( $post->post_type ) && is_main_query() && in_the_loop() ) {
		ob_start();
		do_action( 'edr_before_single_lesson_content', $post->ID );
		$content = ob_get_contents() . $content;
		ob_clean();
		do_action( 'edr_after_single_lesson_content', $post->ID );
		$content .= ob_get_clean();
	}

	return $content;
}

/**
 * Get the membership price ready to display.
 *
 * @param int $membership_id
 * @return string
 */
function edr_get_the_membership_price( $membership_id ) {
	$obj_memberships = Edr_Memberships::get_instance();
	$price = $obj_memberships->get_price( $membership_id );

	if ( $price > 0 ) {
		$duration = $obj_memberships->get_duration( $membership_id );
		$period = $obj_memberships->get_period( $membership_id );
		$price_str = edr_format_membership_price( $price, $duration, $period );
	} else {
		$price_str = _x( 'Free', 'price', 'educator' );
	}

	return $price_str;
}

/**
 * Get buy widget.
 *
 * @param array $atts
 * @return string
 */
function edr_get_buy_widget( $atts ) {
	$atts = wp_parse_args( $atts, array(
		'object_id'   => 0,
		'object_type' => '',
		'label'       => '',
	) );
	$user_id = get_current_user_id();
	$price_str = '';
	$link_url = '';
	$link_text = '';
	$obj_memberships = Edr_Memberships::get_instance();

	if ( EDR_PT_COURSE == $atts['object_type'] ) {
		$obj_courses = Edr_Courses::get_instance();

		if ( 'closed' == $obj_courses->get_register_status( $atts['object_id'] ) ) {
			return '';
		}

		$obj_access = Edr_Access::get_instance();
		$access_status = $obj_access->get_course_access_status( $atts['object_id'], $user_id );

		if ( 'inprogress' == $access_status ) {
			return '';
		}

		$membership_allows = $obj_memberships->can_join_course( $atts['object_id'], $user_id );

		$html = apply_filters( 'edr_get_course_buy_widget_pre', null, $atts, $user_id, $membership_allows );

		if ( ! is_null( $html ) ) {
			return $html;
		}

		if ( $membership_allows ) {
			$link_url = add_query_arg( array(
				'edr-action' => 'join',
				'course_id'  => $atts['object_id'],
			), get_permalink() );
			$link_url = wp_nonce_url( $link_url, 'edr_join_course', '_wpnonce' );
			$link_text = $atts['label'] ? $atts['label'] : __( 'Join', 'educator' );
		} else {
			$html = apply_filters( 'edr_get_membership_buy_widget_pre', null, $atts, $user_id );

			if ( ! is_null( $html ) ) {
				return $html;
			}

			$price = $obj_courses->get_course_price( $atts['object_id'] );

			if ( $price > 0 ) {
				$price_str = edr_format_price( $price );
			} else {
				$price_str = _x( 'Free', 'price', 'educator' );
			}

			$payment_page_url = get_permalink( edr_get_page_id( 'payment' ) );
			$link_url = edr_get_endpoint_url( 'edr-object', $atts['object_id'], $payment_page_url );
			$link_text = $atts['label'] ? $atts['label'] : __( 'Register', 'educator' );
		}
	} elseif ( EDR_PT_MEMBERSHIP == $atts['object_type'] ) {
		$html = apply_filters( 'edr_get_membership_buy_widget_pre', null, $atts, $user_id );

		if ( ! is_null( $html ) ) {
			return $html;
		}

		$price_str = edr_get_the_membership_price( $atts['object_id'] );
		$link_url = $obj_memberships->get_payment_url( $atts['object_id'] );
		$link_text = $atts['label'] ? $atts['label'] : __( 'Purchase', 'educator' );
	}

	$html = '<div class="edr-buy-widget">';

	if ( $price_str ) {
		$html .= '<span class="edr-buy-widget__price">' . $price_str . '</span>';
	}

	$html .= '<a class="edr-buy-widget__link" href="' . esc_url( $link_url ) . '">' . $link_text . '</a>';
	$html .= '</div>';

	return $html;
}

/**
 * Get membership buy link.
 *
 * @param int $membership_id
 * @return string
 */
function edr_get_membership_buy_link( $membership_id ) {
	$html = apply_filters( 'edr_get_membership_buy_link_pre', null, $membership_id );

	if ( ! is_null( $html ) ) {
		return $html;
	}

	$obj_memberships = Edr_Memberships::get_instance();
	$payment_url = $obj_memberships->get_payment_url( $membership_id );
	$html = '<a class="edr-membership-buy-link" href="' . esc_url( $payment_url ) . '">' . __( 'Purchase', 'educator' ) . '</a>';

	return $html;
}

/**
 * Display course registration errors.
 *
 * @param int $course_id
 */
function edr_display_course_errors( $course_id ) {
	$errors = edr_internal_message( 'course_join_errors' );

	if ( $errors ) {
		$messages = $errors->get_error_messages();

		echo '<div class="edr-messages">';

		foreach ( $messages as $message ) {
			echo '<div class="edr-message error">', $message, '</div>';
		}

		echo '</div>';
	}
}

/**
 * Get course difficulty HTML.
 *
 * @param int $course_id
 * @return string
 */
function edr_get_course_difficulty_html( $course_id ) {
	$html = '';
	$difficulty = edr_get_difficulty( $course_id );

	if ( $difficulty ) {
		$html = '<span class="edr-course-difficulty">' . esc_html( $difficulty['label'] ) . '</span>';
	}

	return $html;
}

/**
 * Get course categories HTML.
 *
 * @param int $course_id
 * @return string
 */
function edr_get_course_categories_html( $course_id ) {
	$html = '';
	$categories = get_the_term_list( $course_id, EDR_TX_CATEGORY, '', __( ', ', 'educator' ) );

	if ( $categories ) {
		$html = '<span class="edr-course-categories">' . $categories . '</span>';
	}

	return $html;
}

/**
 * Get course meta HTML.
 *
 * @param int $course_id
 * @return string
 */
function edr_get_course_meta_html( $course_id ) {
	$html = edr_get_course_categories_html( $course_id );
	$html .= edr_get_course_difficulty_html( $course_id );

	if ( $html ) {
		$html = '<ul class="edr-meta edr-meta_course">' . $html . '</ul>';
	}

	return $html;
}

/**
 * Display course info.
 *
 * @param int $course_id
 */
function edr_display_course_info( $course_id ) {
	echo '<div class="edr-course-info">';
	echo edr_get_course_meta_html( $course_id );
	echo edr_get_buy_widget( array( 'object_id' => $course_id, 'object_type' => EDR_PT_COURSE ) );
	echo '</div>';
}

/**
 * Display course lessons.
 *
 * @param int $course_id
 */
function edr_display_lessons( $course_id ) {
	$obj_courses = Edr_Courses::get_instance();
	$syllabus = $obj_courses->get_syllabus( $course_id );

	if ( ! empty( $syllabus ) ) {
		Edr_View::the_template( 'syllabus', array(
			'syllabus' => $syllabus,
			'lessons'  => $obj_courses->get_syllabus_lessons( $syllabus ),
		) );
	} else {
		Edr_View::the_template( 'lessons', array(
			'lessons' => $obj_courses->get_course_lessons( $course_id ),
		) );
	}
}

/**
 * Display breadcrumbs on the lesson page.
 */
function edr_display_breadcrumbs() {
	$breadcrumbs = array();
	$edr_courses = Edr_Courses::get_instance();
	$lesson_id = get_the_ID();
	$course_id = $edr_courses->get_course_id( $lesson_id );

	if ( $course_id ) {
		$course = get_post( $course_id );

		if ( $course ) {
			$breadcrumbs[] = array(
				'href'  => get_permalink( $course->ID ),
				'label' => $course->post_title,
			);
		}
	}

	$breadcrumbs[] = array(
		'label' => get_the_title()
	);

	$html = '<ul class="edr-breadcrumbs">';

	foreach ( $breadcrumbs as $item ) {
		if ( isset( $item['href'] ) ) {
			$html .= '<li><a href="' . esc_url( $item['href'] ) . '">' . esc_html( $item['label'] ) . '</a></li>';
		} else {
			$html .= '<li>' . esc_html( $item['label'] ) . '</li>';
		}
	}

	$html .= '</ul>';

	echo $html;
}

/**
 * Display quiz.
 *
 * @param string $content
 * @return string
 */
function edr_display_quiz( $content ) {
	if ( is_singular() && is_main_query() && in_the_loop() ) {
		$supported_post_types = get_option( 'edr_quiz_support', array( EDR_PT_LESSON ) );

		if ( in_array( get_post_type(), $supported_post_types ) ) {
			ob_start();
			Edr_View::template_part( 'quiz' );
			$content .= ob_get_clean();
		}
	}

	return $content;
}

/**
 * Display membership buy widget.
 *
 * @param string $content
 * @return string
 */
function edr_display_membership_buy_widget( $content ) {
	$post = get_post();

	if ( ! empty( $post ) && is_singular( EDR_PT_MEMBERSHIP ) && is_main_query() && in_the_loop() ) {
		$buy_widget = edr_get_buy_widget( array( 'object_id' => $post->ID, 'object_type' => $post->post_type ) );

		return $buy_widget . $content;
	}

	return $content;
}

/**
 * Get link to an adjacent lesson.
 *
 * @param string $dir
 * @param string $format
 * @param string $title
 * @return string
 */
function edr_get_adjacent_lesson_link( $dir, $format, $title ) {
	$is_previous = ( 'previous' == $dir ) ? true : false;

	if ( ! $lesson = Edr_Courses::get_instance()->get_adjacent_lesson( $is_previous ) ) {
		return '';
	}

	$url = get_permalink( $lesson->ID );
	$title = str_replace( '%title', esc_html( $lesson->post_title ), $title );
	$link = '<a href="' . esc_url( $url ) . '">' . $title . '</a>';

	return str_replace( '%link', $link, $format );
}

/**
 * Display lessons prev/next links.
 */
function edr_lessons_nav_links() {
	echo '<div class="lessons-nav-links">';
	echo edr_get_adjacent_lesson_link(
		'previous',
		'<div class="lessons-nav-links__link lessons-nav-links__link_prev">%link</div>',
		'<span class="hint">' . __( 'Previous', 'educator' ) . '</span><span class="lesson-title">%title</span>'
	);
	echo edr_get_adjacent_lesson_link(
		'next',
		'<div class="lessons-nav-links__link lessons-nav-links__link_next">%link</div>',
		'<span class="hint">' . __( 'Next', 'educator' ) . '</span><span class="lesson-title">%title</span>'
	);
	echo '</div>';
}

/**
 * Get question content.
 *
 * @param Edr_Question $question
 * @return string
 */
function edr_get_question_content( $question ) {
	/**
	 * Filter question content.
	 *
	 * @param string $question_content
	 * @param Edr_Question $question
	 */
	return apply_filters( 'edr_get_question_content', $question->question_content, $question );
}

/**
 * Display a multiple choice question.
 *
 * @param Edr_Question $question
 * @param mixed $answer If $edit is false, must be an object, else string (user input).
 * @param boolean $edit Display either a form or result.
 * @param array $choices
 */
function edr_question_multiple_choice( $question, $answer, $edit, $choices ) {
	$answer_choice_id = is_object( $answer ) ? $answer->choice_id : $answer;

	$output = '<div class="edr-question">';
	$output .= '<div class="label">' . apply_filters( 'edr_get_question_title', $question->question ) . '</div>';

	if ( '' != $question->question_content ) {
		$output .= '<div class="content">' . edr_get_question_content( $question ) . '</div>';
	}

	$output .= '<ul class="answers">';

	if ( $edit ) {
		foreach ( $choices as $choice ) {
			$checked = ( $answer_choice_id == $choice->ID ) ? ' checked="checked"' : '';
			$choice_text = apply_filters( 'edr_get_choice_text', $choice->choice_text );

			$output .= '<li><label><input type="radio" class="choice-checkbox" name="answers[' . intval( $question->ID )
				. ']" value="' . intval( $choice->ID ) . '"' . $checked . '><span class="choice-text">'
				. $choice_text . '</span></label></li>';
		}
	} elseif ( ! is_null( $answer ) ) {
		foreach ( $choices as $choice ) {
			$classes = array();

			if ( 1 == $choice->correct ) {
				$classes[] = 'choice-correct';
			} else {
				$classes[] = 'choice-wrong';
			}

			if ( $choice->ID == $answer_choice_id ) {
				$classes[] = 'choice-selected';
			}

			$choice_text = apply_filters( 'edr_get_choice_text', $choice->choice_text );

			$class = ! empty( $classes ) ? ' class="' . implode( ' ', $classes ) . '"' : '';

			$output .= '<li' . $class . '><div class="choice-text">' . $choice_text . '</div></li>';
		}
	}

	$output .= '</ul>';
	$output .= '</div>';

	echo $output;
}

/**
 * Display a multiple choice question.
 *
 * @param Edr_Question $question
 * @param mixed $answer If $edit is false, must be an object, else string (user input).
 * @param boolean $edit Display either a form or result.
 */
function edr_question_written_answer( $question, $answer, $edit ) {
	$answer_text = is_object( $answer ) ? $answer->answer_text : $answer;

	echo '<div class="edr-question">';
	echo '<div class="label">' . apply_filters( 'edr_get_question_title', $question->question ) . '</div>';

	if ( '' != $question->question_content ) {
		echo '<div class="content">' . edr_get_question_content( $question ) . '</div>';
	}

	if ( $edit ) {
		echo '<div class="answer">'
			. '<textarea name="answers[' . intval( $question->ID ) . ']" cols="50" rows="3">'
			. esc_textarea( $answer_text )
			. '</textarea>'
			. '</div>';
	} elseif ( $answer_text ) {
		echo '<div class="answer">' . esc_html( $answer_text ) . '</div>';
	}

	echo '</div>';
}

/**
 * Display quiz answer file uploads list.
 *
 * @param array $files
 * @param int $lesson_id
 * @param int $question_id
 * @param int $grade_id
 */
function edr_quiz_file_list( $files, $question_id, $grade_id, $lesson_id ) {
	if ( is_array( $files ) ) {
		$quizzes = Edr_Quizzes::get_instance();

		echo '<ul>';

		foreach ( $files as $file ) {
			$file_url = $quizzes->get_file_url( $lesson_id, $question_id, $grade_id );

			echo '<li><a href="' . esc_url( $file_url ) . '">' . esc_html( $file['original_name'] ) . '</a></li>';
		}

		echo '</ul>';
	}
}

/**
 * Display a file upload question.
 *
 * @param Edr_Question $question
 * @param mixed $answer
 * @param boolean $edit
 * @param object $grade
 */
function edr_question_file_upload( $question, $answer, $edit, $grade ) {
	echo '<div class="edr-question">';
	echo '<div class="label">' . apply_filters( 'edr_get_question_title', $question->question ) . '</div>';

	if ( '' != $question->question_content ) {
		echo '<div class="content">' . edr_get_question_content( $question ) . '</div>';
	}

	$files = is_object( $answer ) ? maybe_unserialize( $answer->answer_text ) : array();

	if ( $edit ) {
		echo '<div class="edr-question-answer">';

		if ( ! empty( $files ) ) {
			edr_quiz_file_list( $files, $question->ID, $grade->ID, $grade->lesson_id );
		}

		echo '<input type="file" name="answer_' . intval( $question->ID ) . '">';
		echo '</div>';
	} elseif ( ! empty( $answer ) ) {
		edr_quiz_file_list( $files, $question->ID, $grade->ID, $grade->lesson_id );
	}

	echo '</div>';
}

/**
 * Get the name of a user.
 *
 * @param WP_User $user
 * @param string $context
 * @return string
 */
function edr_get_user_name( $user, $context ) {
	$name = '';

	if ( $user ) {
		if ( 'select' == $context ) {
			$name = $user->user_login;

			if ( $user->first_name ) {
				$name .= ' ' . $user->first_name;
			}

			if ( $user->last_name ) {
				$name .= ' ' . $user->last_name;
			}
		}
	}

	return $name;
}
