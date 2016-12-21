<?php

class Edr_FrontActions {
	/**
	 * Cancel student's payment for a course.
	 */
	public static function cancel_payment() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'edr_cancel_payment' ) ) {
			return;
		}

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		$payment_id = isset( $_GET['payment_id'] ) ? intval( $_GET['payment_id'] ) : 0;

		if ( ! $payment_id ) {
			return;
		}

		$payment = edr_get_payment( $payment_id );

		// User may cancel his/her pending payments only.
		if ( 'pending' == $payment->payment_status && $payment->user_id == $user_id ) {
			if ( $payment->update_status( 'cancelled' ) ) {
				$page_id = isset( $_GET['page_id'] ) ? intval( $_GET['page_id'] ) : null;
				wp_redirect( add_query_arg( 'edr-message', 'payment-cancelled', get_permalink( $page_id ) ) );
				exit;
			}
		}
	}

	/**
	 * Submit quiz.
	 */
	public static function submit_quiz() {
		if ( empty( $_POST ) ) {
			return;
		}

		// Get lesson id and verify nonce.
		$lesson_id = isset( $_POST['lesson_id'] ) ? intval( $_POST['lesson_id'] ) : null;

		if ( ! $lesson_id ) {
			return;
		}

		check_admin_referer( 'edr_submit_quiz_' . $lesson_id );

		// Get user id.
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		$obj_quizzes = Edr_Quizzes::get_instance();

		// Get questions.
		$questions = $obj_quizzes->get_questions( $lesson_id );

		if ( empty( $questions ) ) {
			return;
		}

		// Get the student's entry.
		$entry = Edr_Entries::get_instance()->get_entry( array(
			'user_id'      => $user_id,
			'course_id'    => Edr_Courses::get_instance()->get_course_id( $lesson_id ),
			'entry_status' => 'inprogress',
		) );

		$entry_id = ( $entry ) ? $entry->ID : 0;

		if ( ! $entry_id && EDR_PT_LESSON == get_post_type() ) {
			return;
		}

		$max_attempts_number = $obj_quizzes->get_max_attempts_number( $lesson_id );

		if ( ! is_numeric( $max_attempts_number ) ) {
			$max_attempts_number = 1;
		}

		$attempts_number = $obj_quizzes->get_attempts_number( $lesson_id, $entry_id );

		// Check if the student exceeded the number of allowed attempts.
		if ( $attempts_number >= $max_attempts_number ) {
			return;
		}

		// Get current grade.
		$current_answers = array();
		$grade = $obj_quizzes->get_grade( $lesson_id, $entry_id );

		if ( $grade && 'draft' == $grade->status ) {
			// Continue editing the current grade if it is still a draft.
			$current_answers = $obj_quizzes->get_answers( $grade->ID );
		} else {
			// Create a new grade.
			$grade = edr_get_quiz_grade();
			$grade->lesson_id = $lesson_id;
			$grade->entry_id  = $entry_id;
			$grade->user_id   = $user_id;
			$grade->grade     = 0;
			$grade->status    = 'draft';
			$grade->save();

			if ( ! $grade->ID ) {
				return;
			}
		}

		$answer = null;
		$user_answer = '';
		$answered_questions_num = 0;
		$correct_answers_num = 0;
		$automatic_grade = true;
		$choices = null;
		$obj_upload = new Edr_Upload();
		$errors = new WP_Error();
		$uploads_dir = edr_get_private_uploads_dir();
		$posted_answers = array();
		$question_num = 1;

		if ( isset( $_POST['answers'] ) && is_array( $_POST['answers'] ) ) {
			$posted_answers = $_POST['answers'];
		}

		// Check answers to the quiz questions.
		foreach ( $questions as $question ) {
			// Every question type needs a specific way to check for the valid answer.
			switch ( $question->question_type ) {
				// Multiple Choice Question.
				case 'multiplechoice':
					$user_answer = isset( $posted_answers[ $question->ID ] )
						? intval( $posted_answers[ $question->ID ] ) : null;

					if ( ! $user_answer ) {
						if ( ! $question->optional ) {
							$errors->add( "q_$question->ID", sprintf( __( 'Please answer question %d', 'educator' ), $question_num ) );
						}

						continue;
					}

					if ( null === $choices ) {
						$choices = $obj_quizzes->get_choices( $lesson_id, true );
					}

					if ( isset( $choices[ $question->ID ] ) && isset( $choices[ $question->ID ][ $user_answer ] ) ) {
						$choice = $choices[ $question->ID ][ $user_answer ];
						$answer = isset( $current_answers[ $question->ID ] )
							? $current_answers[ $question->ID ] : edr_get_quiz_answer();

						$answer->question_id = $question->ID;
						$answer->grade_id    = $grade->ID;
						$answer->entry_id    = $entry_id;
						$answer->correct     = $choice->correct;
						$answer->choice_id   = $choice->ID;
						$answer = apply_filters( 'edr_submit_answer_pre', $answer, $question );

						$saved = $answer->save();

						if ( ! $saved ) {
							$errors->add( "q_$question->ID", __( 'The answer could not be saved.', 'educator' ) );
						}

						if ( 1 == $choice->correct ) {
							$correct_answers_num += 1;
						}

						$answered_questions_num += 1;
					}

					break;

				// Written Answer Question.
				case 'writtenanswer':
					$answer = isset( $current_answers[ $question->ID ] )
						? $current_answers[ $question->ID ] : edr_get_quiz_answer();

					$user_answer = isset( $posted_answers[ $question->ID ] )
						? stripslashes( $posted_answers[ $question->ID ] ) : '';

					if ( ! $question->optional || ! empty( $user_answer ) || ! empty( $answer->answer_text ) ) {
						// If the question is required or the user has provided the answer or the answer
						// exists in the database already, then the quiz cannot be graded automatically.
						$automatic_grade = false;
					}

					if ( empty( $user_answer ) ) {
						if ( ! $question->optional && empty( $answer->answer_text ) ) {
							// If the question is not optional and the answer doesn't exist
							// in the database already, ask to answer the question.
							$errors->add( "q_$question->ID", sprintf( __( 'Please answer question %d', 'educator' ), $question_num ) );
						}

						continue;
					}

					$answer->question_id = $question->ID;
					$answer->grade_id    = $grade->ID;
					$answer->entry_id    = $entry_id;
					$answer->correct     = -1;
					$answer->answer_text = $user_answer;
					$answer = apply_filters( 'edr_submit_answer_pre', $answer, $question );

					$saved = $answer->save();

					if ( ! $saved ) {
						$errors->add( "q_$question->ID", __( 'The answer could not be saved.', 'educator' ) );
					}

					break;

				// File Upload Question.
				case 'fileupload':
					$current_file = '';

					if ( isset( $current_answers[ $question->ID ] ) ) {
						$answer = $current_answers[ $question->ID ];
						$files = maybe_unserialize( $answer->answer_text );

						if ( ! empty( $files ) ) {
							$current_file = $uploads_dir . '/quiz/' . $files[0]['dir'] . '/' . $files[0]['name'];
						}
					} else {
						$answer = edr_get_quiz_answer();
					}

					if ( ! isset( $_FILES['answer_' . $question->ID] ) ) {
						$errors->add( "q_$question->ID", __( 'No file sent.', 'educator' ) );
						continue;
					}

					$file = $_FILES['answer_' . $question->ID];
					$file_sent = ( UPLOAD_ERR_NO_FILE != $file['error'] );

					if ( ! $question->optional || $file_sent || $current_file ) {
						// If the question is required or the user has sent the file or the file
						// exists already, then the quiz cannot be graded automatically.
						$automatic_grade = false;
					}

					if ( $file['error'] ) {
						if ( ! $file_sent && ( $current_file || $question->optional ) ) {
							continue;
						}

						$errors->add( "q_$question->ID", $obj_upload->get_error_message( $file['error'] ) );
						continue;
					}

					if ( file_exists( $current_file ) && ! unlink( $current_file ) ) {
						$errors->add( "q_$question->ID", __( 'Could not replace the current file.', 'educator' ) );
						continue;
					}

					$upload = $obj_upload->upload_file( array(
						'name'        => $file['name'],
						'tmp_name'    => $file['tmp_name'],
						'context_dir' => 'quiz',
					) );

					if ( isset( $upload['error'] ) ) {
						$errors->add( "q_$question->ID", $upload['error'] );
						continue;
					}

					$uploads = array(
						array(
							'name'          => $upload['name'],
							'dir'           => $upload['dir'],
							'original_name' => $upload['original_name'],
						),
					);

					$answer->question_id = $question->ID;
					$answer->grade_id    = $grade->ID;
					$answer->entry_id    = $entry_id;
					$answer->correct     = -1;
					$answer->answer_text = maybe_serialize( $uploads );
					$answer = apply_filters( 'edr_submit_answer_pre', $answer, $question );

					$saved = $answer->save();

					if ( ! $saved ) {
						$errors->add( "q_$question->ID", __( 'The answer could not be saved.', 'educator' ) );
					}

					break;
			}

			$question_num += 1;
		}

		if ( $errors->get_error_code() ) {
			edr_internal_message( 'quiz', $errors );

			return;
		}

		if ( $automatic_grade && $answered_questions_num ) {
			$grade->grade = round( $correct_answers_num / $answered_questions_num * 100 );
			$grade->status = 'approved';
		} else {
			$grade->status = 'pending';
		}

		$saved = $grade->save();

		if ( $saved ) {
			wp_redirect( add_query_arg( 'edr-message', 'quiz-submitted', get_permalink( $lesson_id ) ) );
			exit();
		}
	}

	/**
	 * Pay for a course.
	 */
	public static function payment() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'edr_submit_payment' ) ) {
			return;
		}

		do_action( 'edr_before_payment' );

		// Get post id and payment type (course or membership).
		$post_id = isset( $_POST['object_id'] ) ? intval( $_POST['object_id'] ) : null;

		if ( ! $post_id ) {
			return;
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		$payment_type = '';

		if ( EDR_PT_COURSE == $post->post_type ) {
			$payment_type = 'course';
		} elseif ( EDR_PT_MEMBERSHIP == $post->post_type ) {
			$payment_type = 'membership';
		} else {
			return;
		}

		$user_id = get_current_user_id();
		$errors = new WP_Error();

		// Check the course prerequisites.
		if ( 'course' == $payment_type ) {
			$edr_courses = Edr_Courses::get_instance();

			// Registration allowed?
			if ( 'closed' == $edr_courses->get_register_status( $post_id ) ) {
				return;
			}

			// Check prerequisites.
			if ( ! $edr_courses->check_course_prerequisites( $post_id, $user_id ) ) {
				$prerequisites_html = '';
				$prerequisites = $edr_courses->get_course_prerequisites( $post_id );
				$courses = get_posts( array(
					'post_type'   => EDR_PT_COURSE,
					'post_status' => 'publish',
					'include'     => $prerequisites,
				) );

				if ( ! empty( $courses ) ) {
					foreach ( $courses as $course ) {
						$prerequisites_html .= '<br><a href="' . esc_url( get_permalink( $course->ID ) ) .
							'">' . esc_html( $course->post_title ) . '</a>';
					}
				}

				$errors->add( 'prerequisites', sprintf( __( 'You have to complete the prerequisites for this course: %s', 'educator' ), $prerequisites_html ) );
				edr_internal_message( 'payment_errors', $errors );

				return;
			}
		}
		
		// Get the payment method.
		$payment_method = '';
		$gateways = Edr_Main::get_instance()->get_gateways();
		
		if ( isset( $_POST['payment_method'] ) && array_key_exists( $_POST['payment_method'], $gateways ) ) {
			$payment_method = $_POST['payment_method'];
		} else {
			$errors->add( 'empty_payment_method', __( 'Please select a payment method.', 'educator' ) );
		}

		/**
		 * Filter the validation of the payment form.
		 *
		 * @param WP_Error $errors
		 */
		$errors = apply_filters( 'edr_register_form_validate', $errors, $post );

		// Attempt to register the user.
		if ( $errors->get_error_code() ) {
			edr_internal_message( 'payment_errors', $errors );
			return;
		} elseif ( ! $user_id ) {
			$user_data = apply_filters( 'edr_register_user_data', array( 'role' => 'student' ), $post );
			$user_id = wp_insert_user( $user_data );

			if ( is_wp_error( $user_id ) ) {
				edr_internal_message( 'payment_errors', $user_id );
				return;
			} else {
				// Setup the password change nag.
				update_user_option( $user_id, 'default_password_nag', true, true );

				// Send the new user notifications.
				wp_new_user_notification( $user_id, null, 'both' );

				do_action( 'edr_new_student', $user_id, $post );

				// Log the user in.
				wp_set_auth_cookie( $user_id );
			}
		} else {
			do_action( 'edr_update_student', $user_id, $post );
		}

		$can_pay = true;

		if ( 'course' == $payment_type ) {
			$edr_access = Edr_Access::get_instance();
			$access_status = $edr_access->get_course_access_status( $post_id, $user_id );

			// Student can pay for a course only if he/she completed this course or didn't register for it yet.
			$can_pay = ( ! $access_status || 'complete' == $access_status );
		}

		if ( $can_pay ) {
			$atts = array(
				'ip' => $_SERVER['REMOTE_ADDR'],
			);

			$result = $gateways[ $payment_method ]->process_payment( $post_id, $user_id, $payment_type, $atts );
			
			/**
			 * Fires when the payment record has been created.
			 *
			 * The payment may not be confirmed yet.
			 *
			 * @param null|Edr_Payment
			 */
			do_action( 'edr_payment_processed', ( isset( $result['payment'] ) ? $result['payment'] : null ) );

			// Go to the next step(e.g. thank you page).
			wp_safe_redirect( $result['redirect'] );

			exit;
		}
	}

	/**
	 * Join the course if membership allows.
	 */
	public static function join() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'edr_join_course' ) ) {
			return;
		}

		// Get the current user id.
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		// Get course id.
		$course_id = isset( $_GET['course_id'] ) ? intval( $_GET['course_id'] ) : null;

		if ( ! $course_id ) {
			return;
		}

		// Registration allowed?
		$edr_courses = Edr_Courses::get_instance();

		if ( 'closed' == $edr_courses->get_register_status( $course_id ) ) {
			return;
		}

		// Get course.
		$course = get_post( $course_id );

		if ( ! $course || EDR_PT_COURSE != $course->post_type ) {
			return;
		}

		$errors = new WP_Error();

		// Check the course prerequisites.
		if ( ! $edr_courses->check_course_prerequisites( $course_id, $user_id ) ) {
			$prerequisites_html = '';
			$prerequisites = $edr_courses->get_course_prerequisites( $course_id );
			$courses = get_posts( array(
				'post_type'   => EDR_PT_COURSE,
				'post_status' => 'publish',
				'include'     => $prerequisites,
			) );

			if ( ! empty( $courses ) ) {
				foreach ( $courses as $course ) {
					$prerequisites_html .= '<br><a href="' . esc_url( get_permalink( $course->ID ) ) . '">' . esc_html( $course->post_title ) . '</a>';
				}
			}

			$errors->add( 'prerequisites', sprintf( __( 'You have to complete the prerequisites for this course: %s', 'educator' ), $prerequisites_html ) );
			edr_internal_message( 'course_join_errors', $errors );

			return;
		}

		// Make sure the user can join this course.
		$edr_memberships = Edr_Memberships::get_instance();

		if ( ! $edr_memberships->can_join_course( $course_id, $user_id ) ) {
			return;
		}

		// Check if the user already has an inprogress entry for this course.
		$edr_entries = Edr_Entries::get_instance();
		$entries = $edr_entries->get_entries( array(
			'course_id'    => $course_id,
			'user_id'      => $user_id,
			'entry_status' => 'inprogress',
		) );

		if ( ! empty( $entries ) ) {
			return;
		}

		$user_membership = $edr_memberships->get_user_membership_by( 'user_id', $user_id );

		$entry = edr_get_entry();
		$entry->course_id    = $course_id;
		$entry->object_id    = $user_membership['membership_id'];
		$entry->user_id      = $user_id;
		$entry->entry_origin = 'membership';
		$entry->entry_status = 'inprogress';
		$entry->entry_date   = date( 'Y-m-d H:i:s' );
		$entry->save();
	}

	/**
	 * Resume entry.
	 */
	public static function resume_entry() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'edr_resume_entry' ) ) {
			return;
		}

		// Get the current user id.
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		// Get entry id.
		$entry_id = isset( $_GET['entry_id'] ) ? intval( $_GET['entry_id'] ) : null;

		if ( ! $entry_id ) {
			return;
		}

		// Get entry.
		$entry = edr_get_entry( $entry_id );

		if ( ! $entry->ID ) {
			return;
		}

		$edr_memberships = Edr_Memberships::get_instance();
		$edr_entries = Edr_Entries::get_instance();

		// Check if there is an "inprogress" entry for this course.
		$inprogress_entry = $edr_entries->get_entry( array(
			'entry_status' => 'inprogress',
			'course_id'    => $entry->course_id,
			'user_id'      => $user_id,
		) );

		// Make sure that this entry belongs to the current user.
		// Make sure that the current membership gives access to this entry's course.
		if ( $inprogress_entry || $entry->user_id != $user_id || ! $edr_memberships->can_join_course( $entry->course_id, $user_id ) ) {
			return;
		}

		$entry->entry_status = 'inprogress';
		$entry->save();

		wp_safe_redirect( get_permalink() );
	}

	/**
	 * Send the requested quiz answer file,
	 * if the user has permissions to view it.
	 */
	public static function quiz_file_download() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		$grade_id = isset( $_GET['grade_id'] ) ? intval( $_GET['grade_id'] ) : null;
		$question_id = isset( $_GET['question_id'] ) ? intval( $_GET['question_id'] ) : null;

		if ( ! $grade_id || ! $question_id ) {
			return;
		}

		$quizzes = Edr_Quizzes::get_instance();
		$grade = edr_get_quiz_grade( $grade_id );

		if ( ! $grade->ID ) {
			return;
		}

		// Verify user's capabilities.
		if ( $grade->user_id != $user_id ) {
			$entry = edr_get_entry( $grade->entry_id );

			if ( ! $entry || ! current_user_can( 'edit_' . EDR_PT_COURSE, $entry->course_id ) ) {
				exit( __( 'Access denied.', 'educator' ) );
			}
		}

		$answers = $quizzes->get_answers( $grade_id );

		if ( empty( $answers ) || ! isset( $answers[ $question_id ] ) ) {
			exit();
		}

		$files = maybe_unserialize( $answers[ $question_id ]->answer_text );

		if ( ! is_array( $files ) || empty( $files ) ) {
			exit();
		}

		$file = $files[0];

		if ( ! preg_match( '#^[0-9a-z]+/[0-9a-z]+$#', $file['dir'] )
			|| ! preg_match( '#^[0-9a-z-]+(\.[0-9a-z]+)?$#', $file['name'] ) ) {
			exit();
		}

		$file_dir = edr_get_private_uploads_dir();

		if ( ! $file_dir ) {
			exit();
		}

		$file_path = $file_dir . '/quiz/' . $file['dir'] . '/' . $file['name'];

		if ( ! file_exists( $file_path ) ) {
			exit();
		}

		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $file['original_name'] ) . '"' );
		header( 'Content-Length: ' . filesize( $file_path ) );

		readfile( $file_path );

		exit();
	}
}
