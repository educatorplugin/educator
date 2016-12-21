<?php
/**
 * This template renders a quiz.
 *
 * @version 1.0.0
 */

$post_id = get_the_ID();
$edr_quizzes = Edr_Quizzes::get_instance();

if ( ! $edr_quizzes->post_has_quiz( $post_id ) ) {
	return;
}

$user_id = get_current_user_id();

if ( ! $user_id ) {
	return;
}

$edr_entries = Edr_Entries::get_instance();
$entry = $edr_entries->get_entry( array(
	'user_id'      => $user_id,
	'course_id'    => Edr_Courses::get_instance()->get_course_id( $post_id ),
	'entry_status' => 'inprogress',
) );

$entry_id = ( $entry ) ? $entry->ID : 0;

if ( ! $entry_id && EDR_PT_LESSON == get_post_type() ) {
	return;
}

$questions = $edr_quizzes->get_questions( $post_id );
?>

<?php if ( ! empty( $questions ) ) : ?>
	<?php
		// Get the maximum number of times a user can complete this quiz.
		$max_attempts_number = $edr_quizzes->get_max_attempts_number( $post_id );

		if ( ! is_numeric( $max_attempts_number ) ) {
			$max_attempts_number = 1;
		}

		// Get the number of times a user attempted to complete this quiz..
		$attempts_number = $edr_quizzes->get_attempts_number( $post_id, $entry_id );

		// Check if a user has enough attempts to complete this quiz.
		$can_attempt = $attempts_number < $max_attempts_number;

		// Is the student is in the process of doing a quiz?
		$do_quiz = false;

		// Get current grade.
		$grade = $edr_quizzes->get_grade( $post_id, $entry_id );

		// Determine the form action.
		$form_action = add_query_arg( 'edr-action', 'submit-quiz', get_permalink() );

		if ( $can_attempt ) {
			if ( isset( $_GET['try_again'] ) && 'true' == $_GET['try_again'] ) {
				$do_quiz = true;
				$form_action = add_query_arg( 'try_again', 'true', $form_action );

				if ( $grade && 'draft' != $grade->status ) {
					$grade = null;
				}
			} elseif ( ! $grade || 'draft' == $grade->status ) {
				$do_quiz = true;
			}
		}

		$current_attempt = $attempts_number;

		// Increment current attempt number if a user is editing the quiz.
		if ( $do_quiz ) {
			$current_attempt += 1;
		}

		// Scroll the page to where the quiz form is displayed, after it is submitted.
		$form_action .= '#edr-quiz-' . $post_id;
	?>

	<div id="edr-quiz-<?php echo intval( $post_id ); ?>" class="edr-quiz <?php echo $do_quiz ? 'edr-quiz_editable' : 'edr-quiz_done'; ?>">
		<h2 class="edr-quiz__title"><?php _e( 'Quiz', 'educator' ); ?></h2>

		<?php
			$messages = edr_internal_message( 'quiz' );
			$error_codes = is_wp_error( $messages ) ? $messages->get_error_codes() : null;

			if ( ! empty( $error_codes ) ) {
				echo '<div class="edr-messages">';

				foreach ( $error_codes as $code ) {
					echo '<div class="edr-message edr-messages__message error">' . $messages->get_error_message( $code ) . '</div>';
				}

				echo '</div>';
			} else {
				switch ( get_query_var( 'edr-message' ) ) {
					case 'quiz-submitted':
						echo '<div class="edr-messages">';
						echo '<div class="edr-message success">' . __( 'Thank you. the quiz has been accepted.', 'educator' ) . '</div>';
						echo '</div>';
						break;
				}
			}
		?>

		<?php if ( ! $do_quiz && $grade ) : ?>
			<div class="edr-quiz__grade">
				<?php
					if ( 'approved' == $grade->status ) {
						printf( __( 'You scored %s for this quiz.', 'educator' ), '<strong>' . edr_format_grade( $grade->grade ) . '</strong>' );
					} else {
						_e( 'Your grade is pending.', 'educator' );
					}
				?>
			</div>
		<?php endif; ?>

		<div class="edr-quiz__attempts">
			<?php printf( __( 'Attempt %1$d of %2$d', 'educator' ), $current_attempt, $max_attempts_number ); ?>

			<?php if ( $can_attempt && ! $do_quiz ) : ?>
				<a class="edr-quiz__attempts__try-again" href="<?php echo esc_url( add_query_arg( 'try_again', 'true', get_permalink() ) ); ?>#edr-quiz"><?php _e( 'Try again', 'educator' ); ?></a>
			<?php endif; ?>
		</div>

		<form id="edr-quiz-form" class="edr-form" method="post" action="<?php echo esc_url( $form_action ); ?>"
			enctype="multipart/form-data">
			<?php wp_nonce_field( 'edr_submit_quiz_' . $post_id ); ?>
			<input type="hidden" name="submit_quiz" value="1">
			<input type="hidden" name="lesson_id" value="<?php echo intval( $post_id ); ?>">

			<div class="edr-questions">
				<?php
					$posted_answers = array();
					$current_answers = array();
					$choices = null;

					if ( isset( $_POST['answers'] ) && is_array( $_POST['answers'] ) ) {
						$posted_answers = $_POST['answers'];
					}

					if ( $grade ) {
						$current_answers = $edr_quizzes->get_answers( $grade->ID );
					}

					foreach ( $questions as $question ) {
						$answer = null;

						if ( isset( $current_answers[ $question->ID ] ) ) {
							$answer = $current_answers[ $question->ID ];
						} elseif ( isset( $posted_answers[ $question->ID ] ) ) {
							$answer = $posted_answers[ $question->ID ];
						}

						switch ( $question->question_type ) {
							// Multiple choice question.
							case 'multiplechoice':
								if ( is_null( $choices ) ) {
									$choices = $edr_quizzes->get_choices( $post_id, true );
								}

								if ( isset( $choices[ $question->ID ] ) ) {
									edr_question_multiple_choice( $question, $answer, $do_quiz, $choices[ $question->ID ] );
								}

								break;

							// Written answer question.
							case 'writtenanswer':
								if ( is_string( $answer ) ) {
									$answer = stripslashes( $answer );
								}

								edr_question_written_answer( $question, $answer, $do_quiz );

								break;

							// File upload question.
							case 'fileupload':
								edr_question_file_upload( $question, $answer, $do_quiz, $grade );

								break;
						}
					}
				?>
			</div>

			<?php if ( $do_quiz ) : ?>
				<div class="edr-form__actions">
					<button class="edr-button" type="submit"><?php _e( 'Submit', 'educator' ); ?></button>
				</div>
			<?php endif; ?>
		</form>
	</div>
<?php endif; ?>
