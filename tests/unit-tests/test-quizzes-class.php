<?php

class QuizzesClassTest extends WP_UnitTestCase {
	public $edr_quizzes;

	public function setUp() {
		parent::setUp();

		$this->edr_quizzes = Edr_Quizzes::get_instance();
	}

	public function test_post_has_quiz() {
		$course_id = $this->factory->post->create( array( 'post_type' => EDR_PT_COURSE ) );

		$this->assertSame( false, $this->edr_quizzes->post_has_quiz( $course_id ) );

		update_post_meta( $course_id, '_edr_quiz', 1 );

		$this->assertSame( true, $this->edr_quizzes->post_has_quiz( $course_id ) );
	}

	public function test_get_max_attempts_number() {
		$lesson_id = $this->factory->post->create( array( 'post_type' => EDR_PT_LESSON ) );

		$this->assertSame( 0, $this->edr_quizzes->get_max_attempts_number( $lesson_id ) );

		update_post_meta( $lesson_id, '_edr_attempts', 4 );

		$this->assertSame( 4, $this->edr_quizzes->get_max_attempts_number( $lesson_id ) );
	}

	public function test_get_attempts_number() {
		$course_id = $this->factory->post->create( array( 'post_type' => EDR_PT_COURSE ) );
		$lesson_id = $this->factory->post->create( array( 'post_type' => EDR_PT_LESSON ) );
		update_post_meta( $lesson_id, '_edr_course_id', $course_id );
		$entry_factory = new Edr_Entry_Factory();
		$entry_id = $entry_factory->create( array(
			'course_id'    => $course_id,
			'user_id'      => get_current_user_id(),
			'entry_status' => 'inprogress',
		) );

		$actual_attempts_number = $this->edr_quizzes->get_attempts_number( $lesson_id, $entry_id );
		$this->assertSame( 0, $actual_attempts_number );

		$grade = edr_get_quiz_grade();
		$grade->lesson_id = $lesson_id;
		$grade->entry_id  = $entry_id;
		$grade->user_id   = get_current_user_id();
		$grade->grade     = 99.99;
		$grade->status    = 'pending';
		$grade->save();

		$actual_attempts_number = $this->edr_quizzes->get_attempts_number( $lesson_id, $entry_id );
		$this->assertSame( 1, $actual_attempts_number );
	}

	public function test_get_questions() {
		global $wpdb;
		$question_factory = new Edr_Question_Factory();
		$question_id = $question_factory->create( array(
			'lesson_id' => 1,
		) );
		$questions = $this->edr_quizzes->get_questions( 1 );
		$this->assertSame( 1, count( $questions ) );
		$this->assertSame( $question_id, $questions[0]->ID );
	}

	public function test_add_update_delete_choice() {
		$expected_choice = edr_get_quiz_choice();
		$expected_choice->ID          = '0';
		$expected_choice->question_id = '1';
		$expected_choice->choice_text = 'Choice #1';
		$expected_choice->correct     = '1';
		$expected_choice->menu_order  = '0';

		// Create a choice.
		$expected_choice->save();
		$choice_id = $expected_choice->ID;
		$choices = $this->edr_quizzes->get_question_choices( 1 );
		$actual_choice = array_shift( $choices );
		$this->assertEquals( $expected_choice, $actual_choice );

		// Update created choice.
		$expected_choice->choice_text = 'Choice #1 updated';
		$expected_choice->correct = '0';
		$expected_choice->menu_order = '1';
		$expected_choice->save();
		$choices = $this->edr_quizzes->get_question_choices( 1 );
		$actual_choice = array_shift( $choices );
		$this->assertEquals( $expected_choice, $actual_choice );

		// Delete created choice.
		$actual_choice->delete();
		$choices = $this->edr_quizzes->get_question_choices( 1 );
		$this->assertSame( 0, count( $choices ) );
	}

	public function test_get_delete_question_choices() {
		$expected_choice = edr_get_quiz_choice();
		$expected_choice->ID          = '0';
		$expected_choice->question_id = '1';
		$expected_choice->choice_text = 'Choice #1';
		$expected_choice->correct     = '1';
		$expected_choice->menu_order  = '0';
		$expected_choice->save();

		$choices = $this->edr_quizzes->get_question_choices( 1 );
		$this->assertSame( 1, count( $choices ) );
		$actual_choice = array_shift( $choices );
		$expected_choice->ID = $actual_choice->ID;
		$this->assertEquals( $expected_choice, $actual_choice );

		// Delete question choices.
		$this->edr_quizzes->delete_choices( 1 );
		$choices = $this->edr_quizzes->get_question_choices( 1 );
		$this->assertSame( 0, count( $choices ) );
	}

	public function test_get_choices() {
		$question_factory = new Edr_Question_Factory();
		$question1_id = $question_factory->create( array( 'lesson_id' => 1 ) );
		$question2_id = $question_factory->create( array( 'lesson_id' => 1 ) );

		$choice1 = edr_get_quiz_choice();
		$choice1->question_id = $question1_id;
		$choice1->choice_text = 'Choice #1';
		$choice1->correct     = 1;
		$choice1->menu_order  = 0;
		$choice1->save();

		$choice2 = edr_get_quiz_choice();
		$choice2->question_id = $question2_id;
		$choice2->choice_text = 'Another choice #1';
		$choice2->correct     = 1;
		$choice2->menu_order  = 0;
		$choice2->save();

		$choice1_id = $choice1->ID;
		$choice2_id = $choice2->ID;

		// Get choices sorted by question.
		$choices = $this->edr_quizzes->get_choices( 1, true );
		$this->assertEquals( array_keys( $choices ), array( $question1_id, $question2_id ) );
		$this->assertSame( 1, count( $choices[ $question1_id ] ) );
		$this->assertSame( 1, count( $choices[ $question2_id ] ) );

		// Get unsorted choices.
		$choices = $this->edr_quizzes->get_choices( 1 );
		$this->assertSame( 2, count( $choices ) );
		$this->assertEquals( $choice1_id, $choices[0]->ID );
		$this->assertEquals( $choice2_id, $choices[1]->ID );
	}

	public function test_get_grade() {
		// Get grade by lesson id.
		$expected_grade = edr_get_quiz_grade();
		$expected_grade->lesson_id = 1;
		$expected_grade->entry_id  = 0; // necessary
		$expected_grade->user_id   = get_current_user_id(); // necessary
		$expected_grade->grade     = 99.99;
		$expected_grade->status    = 'pending';
		$expected_grade->save();

		$grade = $this->edr_quizzes->get_grade( 1 );
		$expected_grade->ID = $grade->ID;
		$this->assertEquals( $expected_grade, $grade );

		// Get grade by lesson id and entry id.
		$expected_grade->entry_id = 123;
		$expected_grade->ID = 0;
		$expected_grade->save();
		$grade = $this->edr_quizzes->get_grade( 1, 123 );
		$this->assertEquals( $expected_grade, $grade );

		// Grade doesn't exist.
		$this->assertSame( null, $this->edr_quizzes->get_grade( 1111, 2222 ) );
	}

	public function test_add_grade_and_update_grade() {
		$expected_grade = edr_get_quiz_grade();
		$expected_grade->lesson_id = 1;
		$expected_grade->entry_id  = 2;
		$expected_grade->user_id   = 3;
		$expected_grade->grade     = 99.99;
		$expected_grade->status    = 'pending';
		$expected_grade->save();

		$grade = edr_get_quiz_grade( $expected_grade->ID );
		$this->assertEquals( $expected_grade, $grade );

		// Grade doesn't exist.
		$this->assertEquals( edr_get_quiz_grade(), edr_get_quiz_grade( 1111 ) );

		// Update grade.
		$expected_grade->grade = 100.0;
		$expected_grade->status = 'approved';
		$expected_grade->save();
		$grade = edr_get_quiz_grade( $expected_grade->ID );
		$this->assertEquals( $expected_grade, $grade );
	}

	public function test_get_answer_update_answer_get_answers() {
		// Add answer and get answers.
		$expected_answer = edr_get_quiz_answer();
		$expected_answer->question_id = 1;
		$expected_answer->entry_id    = 2;
		$expected_answer->grade_id    = 123;
		$expected_answer->choice_id   = 4;
		$expected_answer->correct     = 1;
		$expected_answer->answer_text = 'Answer text';
		$expected_answer->save();

		$actual_answers = $this->edr_quizzes->get_answers( 123 );
		$this->assertSame( 1, count( $actual_answers ) );
		$this->assertEquals( $expected_answer, array_pop( $actual_answers ) );

		// Update answer.
		$expected_answer->question_id = 2;
		$expected_answer->entry_id = 3;
		$expected_answer->grade_id = 1234;
		$expected_answer->choice_id = 5;
		$expected_answer->correct = 0;
		$expected_answer->answer_text = 'Answer text updated';
		$expected_answer->save();
		$actual_answers = $this->edr_quizzes->get_answers( 1234 );
		$this->assertSame( 1, count( $actual_answers ) );
		$this->assertEquals( $expected_answer, array_pop( $actual_answers ) );
	}

	public function test_delete_answers() {
		$answer1 = edr_get_quiz_answer();
		$answer1->grade_id = 123;
		$answer1->save();

		$answer2 = edr_get_quiz_answer();
		$answer2->grade_id = 123;
		$answer2->save();

		$deleted = $this->edr_quizzes->delete_answers( array( 'grade_id' => 123456789 ) );
		$this->assertSame(0, $deleted);

		$deleted = $this->edr_quizzes->delete_answers( array( 'grade_id' => 123 ) );
		$this->assertSame(2, $deleted);

		$deleted = $this->edr_quizzes->delete_answers( array() );
		$this->assertSame(false, $deleted);
	}

	public function test_check_for_pending_quizzes() {
		$grade = edr_get_quiz_grade();
		$grade->lesson_id = 1;
		$grade->entry_id  = 2;
		$grade->user_id   = 3;
		$grade->grade     = 99.99;
		$grade->status    = 'pending';
		$grade->save();

		$this->assertEquals( array( 2 ), $this->edr_quizzes->check_for_pending_quizzes( array( 2 ) ) );
	}

	public function test_get_file_url() {
		$lesson_id = 1;
		$question_id = 2;
		$grade_id = 3;

		$expected_url = add_query_arg( array(
			'edr-action'  => 'quiz-file-download',
			'grade_id'    => $grade_id,
			'question_id' => $question_id,
		), get_permalink( $lesson_id ) );

		$this->assertSame( $expected_url, $this->edr_quizzes->get_file_url( $lesson_id, $question_id, $grade_id ) );
	}
}
