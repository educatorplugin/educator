<?php
if ( ! $grade ) {
	return;
}

$obj_quizzes = Edr_Quizzes::get_instance();
$questions = $obj_quizzes->get_questions( $grade->lesson_id );
$answers = $obj_quizzes->get_answers( $grade->ID );
$nonce = wp_create_nonce( 'edr_edit_quiz_grade_' . $grade->ID );
?>
<?php if ( ! empty( $questions ) ) : ?>
	<div class="edr-field edr-field_quiz-answers">
		<div class="edr-field__label">
			<label><?php _e( 'Answers', 'educator' ); ?></label>
		</div>
		<div class="edr-field__control">
			<ul class="edr-quiz-answers">
				<?php foreach ( $questions as $question ) : ?>
					<?php
						$answer = array_key_exists( $question->ID, $answers ) ? $answers[ $question->ID ] : null;
					?>
					<li class="edr-quiz-answers__answer">
						<div class="edr-quiz-answers__answer__status">
							<?php
								if ( $answer ) {
									if ( 1 == $answer->correct ) {
										echo '<span class="dashicons dashicons-yes"></span>';
									} elseif ( -1 == $answer->correct ) {
										echo '<span class="dashicons dashicons-editor-help"></span>';
									} else {
										echo '<span class="dashicons dashicons-no-alt"></span>';
									}
								} else {
									echo '<span class="dashicons dashicons-editor-help"></span>';
								}
							?>
						</div>
						<div class="edr-quiz-answers__answer__question"><?php echo esc_html( $question->question ); ?></div>
						<div class="edr-quiz-answers__answer__result">
							<?php
								if ( 'multiplechoice' == $question->question_type ) {
									if ( $answer ) {
										echo ( 1 == $answer->correct ) ? __( 'Correct', 'educator' ) : __( 'Wrong', 'educator' );
									} else {
										echo __( 'No answer', 'educator' );
									}
								} elseif ( 'writtenanswer' == $question->question_type ) {
									echo ( $answer ) ? esc_html( $answer->answer_text ) : __( 'No answer', 'educator' );
								} elseif ( 'fileupload' == $question->question_type ) {
									if ( $answer ) {
										$answer_files = maybe_unserialize( $answer->answer_text );

										if ( ! empty( $answer_files ) ) {
											edr_quiz_file_list( $answer_files, $question->ID, $grade->ID, $grade->lesson_id );
										}
									} else {
										echo __( 'No answer', 'educator' );
									}
								}
							?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
<?php endif; ?>

<div class="edr-quiz-grade-form">
	<div class="edr-field">
		<div class="edr-field__label">
			<label for="input-grade-<?php echo intval( $grade->ID ); ?>"><?php _e( 'Grade', 'educator' ); ?></label>
		</div>
		<div class="edr-field__control">
			<input type="hidden" class="input-grade-id" value="<?php echo intval( $grade->ID ); ?>">
			<input type="hidden" class="input-nonce" value="<?php echo esc_attr( $nonce ); ?>">
			<input type="text" id="input-grade-<?php echo intval( $grade->ID ); ?>" class="small-text input-grade" value="<?php echo ( $grade ) ? (float) $grade->grade : ''; ?>"<?php if ( ! $grade ) echo ' disabled="disabled"'; ?> autocomplete="off">
			<span class="percentage">%</span>
			<p class="edr-info">
				<?php
					_e( 'Please enter a number between 0 and 100.', 'educator' );
					echo ' ';
					_e( 'The student will receive a notification email.', 'educator' );
				?>
			</p>
		</div>
	</div>

	<div class="edr-field">
		<div class="edr-field__label"></div>
		<div class="edr-field__control">
			<button type="button" class="save-quiz-grade button-primary"<?php if ( ! $grade ) echo ' disabled="disabled"'; ?>><?php _e( 'Save Grade', 'educator' ); ?></button>
		</div>
	</div>
</div>
