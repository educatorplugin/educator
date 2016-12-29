<?php

/**
 * Quiz admin.
 */
class Edr_Admin_Quiz {
	/**
	 * Initialize the quiz admin.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts_styles' ), 9 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'set_quiz' ), 10, 3 );
		add_action( 'wp_ajax_edr_quiz_question', array( __CLASS__, 'quiz_question' ) );
		add_action( 'wp_ajax_edr_sort_questions', array( __CLASS__, 'sort_questions' ) );
		add_action( 'wp_ajax_edr_edit_quiz_grade', array( __CLASS__, 'ajax_edit_quiz_grade' ) );
		add_action( 'admin_init', array( __CLASS__, 'process_actions' ) );
	}

	/**
	 * Enqueue scripts and stylesheets.
	 */
	public static function enqueue_scripts_styles() {
		$post_types = get_option( 'edr_quiz_support', array( EDR_PT_LESSON ) );
		$screen = get_current_screen();

		if ( 'post' == $screen->base && in_array( $screen->post_type, $post_types ) ) {
			wp_enqueue_style( 'edr-quiz', EDR_PLUGIN_URL . 'assets/admin/css/quiz.css', array(), '1.0' );
			wp_enqueue_script( 'edr-quiz', EDR_PLUGIN_URL . 'assets/admin/js/quiz/quiz.min.js', array( 'jquery', 'underscore', 'backbone' ), '1.1', true );
			wp_localize_script( 'edr-quiz', 'EdrQuiz', array(
				'text' => array(
					'confirmDelete' => __( 'Are you sure you want to delete this item?', 'educator' ),
				)
			) );
		}
	}

	/**
	 * Add meta box.
	 */
	public static function add_meta_boxes() {
		$post_types = get_option( 'edr_quiz_support', array( EDR_PT_LESSON ) );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'edr_quiz_meta',
				__( 'Quiz', 'educator' ),
				array( __CLASS__, 'quiz_meta_box' ),
				$post_type
			);
		}
	}

	/**
	 * Output quiz meta box.
	 *
	 * @param WP_Post $post
	 */
	public static function quiz_meta_box( $post ) {
		include EDR_PLUGIN_DIR . 'templates/admin/mb-quiz.php';
	}

	/**
	 * Save multiple choices to the database.
	 *
	 * @param int $question_id
	 * @param array $choices
	 * @return array Saved choices.
	 */
	protected static function save_question_choices( $question_id, $choices ) {
		global $wpdb;
		$choice_ids = array();

		foreach ( $choices as $choice_input ) {
			if ( isset( $choice_input->choice_id ) && is_numeric( $choice_input->choice_id ) ) {
				$choice_ids[] = $choice_input->choice_id;
			}
		}

		// Delete choices that are not sent by the user.
		if ( ! empty( $choice_ids ) ) {
			$tables = edr_db_tables();
			$query = 'DELETE FROM ' . $tables['choices'] . ' WHERE question_id = %d AND ID NOT IN (' . implode( ',', $choice_ids ) . ')';
			$wpdb->query( $wpdb->prepare( $query, $question_id ) );
		}

		// Add choices to the question.
		$obj_quizzes = Edr_Quizzes::get_instance();
		$current_choices = $obj_quizzes->get_question_choices( $question_id );
		$saved_choices = array();

		foreach ( $choices as $choice_input ) {
			$choice_id = isset( $choice_input->choice_id ) ? intval( $choice_input->choice_id ) : 0;
			$choice_text = isset( $choice_input->choice_text ) ? $choice_input->choice_text : '';

			$choice = isset( $current_choices[ $choice_id ] )
				? $current_choices[ $choice_id ] : edr_get_quiz_choice();

			$choice->choice_text = apply_filters( 'edr_choice_pre_text', $choice_text );
			$choice->correct     = isset( $choice_input->correct ) ? intval( $choice_input->correct ) : 0;
			$choice->menu_order  = isset( $choice_input->menu_order ) ? intval( $choice_input->menu_order ) : 0;

			if ( ! $choice->ID ) {
				$choice->question_id = $question_id;
			}

			$choice->save();
			$choice->choice_id = $choice->ID;
			$saved_choices[] = $choice;
		}

		return $saved_choices;
	}

	/**
	 * AJAX: process quiz question admin requests.
	 */
	public static function quiz_question() {
		switch ( $_SERVER['REQUEST_METHOD'] ) {
			case 'POST':
				$response = array(
					'status' => '',
					'errors' => array(),
					'id'     => 0
				);
				$input = json_decode( file_get_contents( 'php://input' ) );

				// Input given?
				if ( ! $input || ! isset( $input->lesson_id ) || ! is_numeric( $input->lesson_id ) ) {
					status_header( 400 );

					exit();
				}

				// Verify nonce.
				if ( ! isset( $input->_wpnonce ) || ! wp_verify_nonce( $input->_wpnonce, 'edr_quiz_' . $input->lesson_id ) ) {
					status_header( 403 );

					exit();
				}

				$post_type = get_post_type( $input->lesson_id );

				// Verify capabilities.
				if ( ! current_user_can( 'edit_' . $post_type, $input->lesson_id ) ) {
					exit();
				}

				// Process input.
				$question = edr_get_question();

				$question->lesson_id = $input->lesson_id;

				if ( isset( $input->question ) && ! empty( $input->question ) ) {
					$question->question = apply_filters( 'edr_question_pre_title', $input->question );
				} else {
					$response['errors'][] = 'question';
				}

				if ( isset( $input->question_type ) && ! empty( $input->question_type ) ) {
					$question->question_type = $input->question_type;
				} else {
					$response['errors'][] = 'question_type';
				}

				if ( isset( $input->question_content ) ) {
					$question->question_content = apply_filters( 'edr_question_pre_content', $input->question_content );
				} else {
					$response['errors'][] = 'question_content';
				}

				if ( isset( $input->optional ) ) {
					$question->optional = intval( $input->optional );
				}

				if ( isset( $input->menu_order ) && is_numeric( $input->menu_order ) ) {
					$question->menu_order = $input->menu_order;
				} else {
					$response['errors'][] = 'order';
				}

				if ( ! count( $response['errors'] ) ) {
					$question->save();

					if ( $question->ID && isset( $input->choices ) && is_array( $input->choices ) ) {
						$response['choices'] = self::save_question_choices( $question->ID, $input->choices );
					}

					if ( ! get_post_meta( $question->lesson_id, '_edr_quiz', true ) ) {
						// Set default settings for the quiz.
						update_post_meta( $question->lesson_id, '_edr_quiz', 1 );
						update_post_meta( $question->lesson_id, '_edr_attempts', 1 );
					}

					$response['question'] = $question->question;
					$response['question_content'] = $question->question_content;
					$response['status'] = 'success';
					$response['id'] = $question->ID;
				} else {
					$response['status'] = 'error';

					status_header( 400 );
				}

				echo json_encode( $response );
				break;

			case 'PUT':
				$response = array(
					'status' => '',
					'errors' => array()
				);
				$question_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
				$input = json_decode( file_get_contents( 'php://input' ) );

				// Input given?
				if ( ! $question_id || ! $input ) {
					status_header( 400 );

					exit();
				}

				$question = edr_get_question( $question_id );

				// Question found?
				if ( ! $question->ID ) {
					status_header( 400 );

					exit();
				}

				// Verify nonce.
				if ( ! isset( $input->_wpnonce ) || ! wp_verify_nonce( $input->_wpnonce, 'edr_quiz_' . $question->lesson_id ) ) {
					status_header( 403 );

					exit();
				}

				$post_type = get_post_type( $question->lesson_id );

				// Verify capabilities.
				if ( ! current_user_can( 'edit_' . $post_type, $question->lesson_id ) ) {
					exit();
				}

				if ( isset( $input->question ) && ! empty( $input->question ) ) {
					$question->question = apply_filters( 'edr_question_pre_title', $input->question );
				} else {
					$response['errors'][] = 'question';
				}

				if ( isset( $input->question_content ) ) {
					$question->question_content = apply_filters( 'edr_question_pre_content', $input->question_content );
				} else {
					$response['errors'][] = 'question_content';
				}

				if ( isset( $input->optional ) ) {
					$question->optional = intval( $input->optional );
				}

				if ( ! count( $response['errors'] ) ) {
					$question->save();

					if ( isset( $input->choices ) && is_array( $input->choices ) ) {
						$response['choices'] = self::save_question_choices( $question->ID, $input->choices );
					}

					$response['question'] = $question->question;
					$response['question_content'] = $question->question_content;
					$response['status'] = 'success';
				} else {
					$response['status'] = 'error';

					status_header( 400 );
				}

				echo json_encode( $response );
				break;

			case 'DELETE':
				$response = array(
					'status' => ''
				);
				$question_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

				// Input given?
				if ( ! $question_id ) {
					status_header( 400 );

					exit();
				}

				$question = edr_get_question( $question_id );

				// Question found?
				if ( ! $question->ID ) {
					status_header( 400 );

					exit();
				}

				// Verify nonce.
				$input = json_decode( file_get_contents( 'php://input' ) );
				
				if ( ! isset( $input->_wpnonce ) || ! wp_verify_nonce( $input->_wpnonce, 'edr_quiz_' . $question->lesson_id ) ) {
					status_header( 403 );

					exit();
				}

				$post_type = get_post_type( $question->lesson_id );

				// Verify capabilities.
				if ( ! current_user_can( 'edit_' . $post_type, $question->lesson_id ) ) {
					exit();
				}

				if ( $question->ID ) {
					$quizzes = Edr_Quizzes::get_instance();

					// First, delete question choices.
					$choices_deleted = true;

					if ( 'multiplechoice' == $question->question_type ) {
						$choices_deleted = $quizzes->delete_choices( $question->ID );
					}

					if ( false !== $choices_deleted ) {
						$response['status'] = $question->delete() ? 'success' : 'error';
					}
				}

				echo json_encode( $response );
				break;
		}

		exit();
	}

	/**
	 * Indicate that the post has a quiz.
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 * @param boolean $update
	 */
	public static function set_quiz( $post_id, $post, $update ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$post_types = get_option( 'edr_quiz_support', array( EDR_PT_LESSON ) );
		
		if ( ! in_array( $post->post_type, $post_types ) || ! current_user_can( 'edit_' . $post->post_type, $post_id ) ) {
			return;
		}

		if ( isset( $_POST['_edr_attempts'] ) ) {
			$attempts_number = absint( $_POST['_edr_attempts'] );

			if ( ! $attempts_number ) {
				$attempts_number = 1;
			}

			update_post_meta( $post_id, '_edr_attempts', $attempts_number );
		}

		$has_quiz = 0;
		$quizzes = Edr_Quizzes::get_instance();
		$questions = $quizzes->get_questions( $post_id );

		if ( ! empty( $questions ) ) {
			$has_quiz = 1;
		}

		update_post_meta( $post_id, '_edr_quiz', $has_quiz );
	}

	/**
	 * AJAX: sort quiz questions.
	 */
	public static function sort_questions() {
		global $wpdb;

		$lesson_id = isset( $_POST['lesson_id'] ) ? absint( $_POST['lesson_id'] ) : 0;

		if ( ! $lesson_id || ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'edr_quiz_' . $lesson_id ) ) {
			exit;
		}

		if ( ! Edr_Access::get_instance()->can_edit_lesson( $lesson_id ) ) {
			exit;
		}

		$ids = isset( $_POST['question_id'] ) && is_array( $_POST['question_id'] ) ? $_POST['question_id'] : null;
		$order = isset( $_POST['order'] ) && is_array( $_POST['order'] ) ? $_POST['order'] : null;

		if ( $ids && $order ) {
			foreach ( $ids as $key => $question_id ) {
				if ( is_numeric( $question_id ) && isset( $order[ $key ] ) && is_numeric( $order[ $key ] ) ) {
					$tables = edr_db_tables();
					$wpdb->update(
						$tables['questions'],
						array( 'menu_order' => $order[ $key ] ),
						array( 'ID' => $question_id, 'lesson_id' => $lesson_id ), // lesson_id is for access control
						array( '%d' ),
						array( '%d', '%d' )
					);
				}
			}
		}

		exit;
	}

	/**
	 * AJAX: add grade for a quiz.
	 */
	public static function ajax_edit_quiz_grade() {
		$grade_id = isset( $_POST['grade_id'] ) ? intval( $_POST['grade_id'] ) : 0;

		// Verify nonce.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'edr_edit_quiz_grade_' . $grade_id ) ) {
			exit();
		}

		$obj_quizzes = Edr_Quizzes::get_instance();
		$grade = edr_get_quiz_grade( $grade_id );

		if ( ! $grade->ID ) {
			exit();
		}

		$post = get_post( $grade->lesson_id );

		if ( ! $post || ! current_user_can( 'edit_' . $post->post_type, $post->ID ) ) {
			exit();
		}

		$grade_value = isset( $_POST['grade'] ) ? floatval( $_POST['grade'] ) : 0.0;

		$grade->grade = $grade_value;
		$grade->status = 'approved';
		$grade->save();

		// Send notification email to the student.
		$student = get_user_by( 'id', $grade->user_id );

		if ( $student ) {
			$lesson_title = get_the_title( $grade->lesson_id );

			edr_send_notification(
				$student->user_email,
				'quiz_grade',
				array(
					'lesson_title' => $lesson_title,
				),
				array(
					'student_name' => $student->display_name,
					'lesson_title' => $lesson_title,
					'grade'        => edr_format_grade( $grade_value ),
				)
			);
		}

		echo json_encode( array( 'status' => 'success' ) );

		exit();
	}

	/**
	 * Process actions (e.g., delete grade).
	 */
	public static function process_actions() {
		$action = isset( $_GET['edr-action'] ) ? $_GET['edr-action'] : '';

		if ( 'delete-quiz-grade' == $action ) {
			self::action_delete_quiz_grade();
		}
	}

	/**
	 * ACTION: Delete grade.
	 */
	protected static function action_delete_quiz_grade() {
		$grade_id = isset( $_GET['grade_id'] ) ? intval( $_GET['grade_id'] ) : 0;

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'edr_delete_quiz_grade_' . $grade_id ) ) {
			exit();
		}

		$grade = edr_get_quiz_grade( $grade_id );

		if ( ! $grade->ID ) {
			exit();
		}

		$post = get_post( $grade->lesson_id );

		if ( ! $post ) {
			return;
		}

		if ( ! current_user_can( "edit_$post->post_type", $grade->lesson_id ) ) {
			exit();
		}

		$obj_quizzes = Edr_Quizzes::get_instance();
		$answers_deleted = $obj_quizzes->delete_answers( array( 'grade_id' => $grade_id ) );

		if ( false !== $answers_deleted ) {
			$grade->delete();
		}

		$redirect = isset( $_GET['redirect'] ) ? $_GET['redirect'] : '';

		if ( $redirect ) {
			wp_safe_redirect( $redirect );
			exit();
		}
	}
}
