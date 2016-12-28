<?php
$obj_quizzes = Edr_Quizzes::get_instance();
$lesson_id = (int) $post->ID;
$attempts_number = get_post_meta( $lesson_id, '_edr_attempts', true );

if ( ! $attempts_number ) {
	$attempts_number = 1;
}
?>

<div class="edr-field">
	<div class="edr-field__label">
		<label for="edr-attempts-number"><?php _e( 'Number of attempts', 'educator' ); ?></label>
	</div>
	<div class="edr-field__control">
		<input type="number" id="edr-attempts-number" name="_edr_attempts" value="<?php echo intval( $attempts_number ); ?>">
	</div>
</div>

<div id="edr-quiz" class="edr-quiz">
	<div id="edr-quiz-questions" class="edr-quiz__questions"></div>
	<div class="edr-quiz__add-question">
		<?php
			printf(
				__( 'Add %s question.', 'educator' ),
				'<select id="edr-quiz-question-type">' .
				'<option value="multiplechoice">' . __( 'Multiple Choice', 'educator' ) . '</option>' .
				'<option value="writtenanswer">' . __( 'Written Answer', 'educator' ) . '</option>' .
				'<option value="fileupload">' . __( 'File Upload', 'educator' ) . '</option>' .
				'</select>'
			);
		?>
		<button class="add-question button button-secondary"><?php _e( 'Add', 'educator' ); ?></button>
	</div>
</div>

<input type="hidden" id="edr-quiz-lesson-id" value="<?php echo $lesson_id; ?>">
<input type="hidden" id="edr-quiz-nonce" value="<?php echo wp_create_nonce( 'edr_quiz_' . $lesson_id ); ?>">

<!-- Template: Multiple Choice Question Answer -->
<script type="text/template" id="edr-tpl-multiplechoiceanswer">
<td class="column1"><div class="handle dashicons dashicons-sort"></div></td>
<td class="column2"><input class="answer-correct" type="radio"></td>
<td class="column3"><input class="answer-text" type="text" class="regular-text" value="<%- choice_text %>"></td>
<td class="column4"><button class="edr-action-btn delete-answer"><span class="dashicons dashicons-trash"></span></button></td>
</script>

<!-- Template: Multiple Choice Question -->
<script type="text/template" id="edr-tpl-multiplechoicequestion">
<a class="edr-question__header" href="#">
	<span class="edr-question__label"><%- question %></span>
	<span class="edr-question__trigger"></span>
</a>
<div class="edr-question__body">
	<div class="edr-question__text">
		<label><?php _e( 'Question', 'educator' ); ?></label>
		<input type="text" class="question-text" value="<%- question %>">
	</div>
	<div class="edr-question__content">
		<label><?php _e( 'Content', 'educator' ); ?></label>
		<textarea class="question-content"><%- question_content %></textarea>
	</div>
	<div class="edr-question__answers">
		<label><?php _e( 'Answers', 'educator' ); ?></label>
		<p class="no-answers"><?php _e( 'No answers yet.', 'educator' ); ?></p>
		<table>
			<thead>
				<tr>
					<th></th>
					<th><?php _e( 'Correct?', 'educator' ); ?></th>
					<th><?php _e( 'Answer', 'educator' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody class="js-edr-answers"></tbody>
		</table>
	</div>
	<div class="edr-question__optional">
		<label><input type="checkbox" class="question-optional"<%= (optional == 1) ? ' checked' : '' %>><?php _e( 'Optional', 'educator' ); ?></label>
	</div>
	<div class="edr-question__buttons">
		<button class="save-question button button-primary"><?php _e( 'Save Question', 'educator' ); ?></button>
		<button class="add-answer button button-secondary"><?php _e( 'Add Answer', 'educator' ); ?></button>
		<a class="delete-question" href="#"><?php _e( 'Delete', 'educator' ); ?></a>
	</div>
</div>
</script>

<!-- Template: Written Answer Question -->
<script type="text/template" id="edr-tpl-writtenanswerquestion">
<a class="edr-question__header" href="#">
	<span class="edr-question__label"><%- question %></span>
	<span class="edr-question__trigger"></span>
</a>
<div class="edr-question__body">
	<div class="edr-question__text">
		<label><?php _e( 'Question', 'educator' ); ?></label>
		<input type="text" class="question-text" value="<%- question %>">
	</div>
	<div class="edr-question__content">
		<label><?php _e( 'Content', 'educator' ); ?></label>
		<textarea class="question-content"><%- question_content %></textarea>
	</div>
	<div class="edr-question__optional">
		<label><input type="checkbox" class="question-optional"<%= (optional == 1) ? ' checked' : '' %>><?php _e( 'Optional', 'educator' ); ?></label>
	</div>
	<div class="edr-question__buttons">
		<button class="save-question button button-primary"><?php _e( 'Save Question', 'educator' ); ?></button>
		<a class="delete-question" href="#"><?php _e( 'Delete', 'educator' ); ?></a>
	</div>
</div>
</script>

<!-- Template: File Upload Question -->
<script type="text/template" id="edr-tpl-fileuploadquestion">
<a class="edr-question__header" href="#">
	<span class="edr-question__label"><%- question %></span>
	<span class="edr-question__trigger"></span>
</a>
<div class="edr-question__body">
	<div class="edr-question__text">
		<label><?php _e( 'Question', 'educator' ); ?></label>
		<input type="text" class="question-text" value="<%- question %>">
	</div>
	<div class="edr-question__content">
		<label><?php _e( 'Content', 'educator' ); ?></label>
		<textarea class="question-content"><%- question_content %></textarea>
	</div>
	<div class="edr-question__optional">
		<label><input type="checkbox" class="question-optional"<%= (optional == 1) ? ' checked' : '' %>><?php _e( 'Optional', 'educator' ); ?></label>
	</div>
	<div class="edr-question__buttons">
		<button class="save-question button button-primary"><?php _e( 'Save Question', 'educator' ); ?></button>
		<a class="delete-question" href="#"><?php _e( 'Delete', 'educator' ); ?></a>
	</div>
</div>
</script>

<?php
// Create questions JSON.
$questions_js = '[';
$questions = $obj_quizzes->get_questions( array( 'lesson_id' => $lesson_id ) );

foreach ( $questions as $question ) {
	$questions_js .= '{id: ' . intval( $question->ID ) . ',' .
		'question: ' . json_encode( $question->question ) . ',' .
		'question_type: ' . json_encode( $question->question_type ) . ',' .
		'question_content: ' . json_encode( $question->question_content ) . ',' .
		'optional: ' . intval( $question->optional ) . ',' .
		'menu_order: ' . intval( $question->menu_order ) . '},';
}

$questions_js .= ']';

// Create answers (choices) JSON.
$choices_json = '{';
$choices = $obj_quizzes->get_choices( $lesson_id, true );

foreach ( $choices as $question_id => $question ) {
	$choices_json .= 'question_' . intval( $question_id ) . ':[';

	foreach ( $question as $choice ) {
		$choices_json .= '{choice_id: ' . intval( $choice->ID ) . ', ' .
			'question_id: ' . intval( $choice->question_id ) . ', ' .
			'choice_text: ' . json_encode( $choice->choice_text ) . ', ' .
			'correct: ' . intval( $choice->correct ) . ', ' .
			'menu_order: ' . intval( $choice->menu_order ) . '},';
	}

	$choices_json .= '],';
}

$choices_json .= '}';
?>
<script>
	var educatorQuizQuestions = <?php echo $questions_js; ?>;
	var educatorQuizChoices = <?php echo $choices_json; ?>;
</script>
