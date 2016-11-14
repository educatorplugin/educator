<?php

class QuizAnswerClassTest extends WP_UnitTestCase {
	protected function _get_sample_data() {
		$data = new Edr_QuizAnswer();

		$data->question_id = 1;
		$data->grade_id    = 2;
		$data->entry_id    = 3;
		$data->choice_id   = 4;
		$data->correct     = 1;
		$data->answer_text = 'Sample answer text';

		return $data;
	}

	public function test_construct() {
		$sampleAnswer = $this->_get_sample_data();

		// Sample data object is provided.
		$quizAnswer1 = new Edr_QuizAnswer( $sampleAnswer );

		$this->assertEquals( $sampleAnswer, $quizAnswer1 );

		// Existing ID is provided.
		$quizAnswer1->save();

		$quizAnswer2 = new Edr_QuizAnswer( $quizAnswer1->ID );

		$this->assertEquals( $quizAnswer1, $quizAnswer2 );
	}

	public function test_sanitize_field() {
		$sanitize_field = new ReflectionMethod( 'Edr_QuizAnswer', 'sanitize_field' );
		$sanitize_field->setAccessible(true);

		$int_fields = array( 'ID', 'question_id', 'grade_id', 'entry_id', 'choice_id', 'correct' );

		$answer = new Edr_QuizAnswer();

		foreach ( $int_fields as $field ) {
			$this->assertSame( 1, $sanitize_field->invoke( $answer, $field, '1' ) );
		}
	}

	public function test_set_data() {
		$sampleAnswer = $this->_get_sample_data();
		$answer = new Edr_QuizAnswer();
		$answer->set_data( $sampleAnswer );

		$this->assertEquals( $sampleAnswer, $answer );
	}

	public function test_save() {
		$sampleAnswer = $this->_get_sample_data();
		$answerToSave = new Edr_QuizAnswer( $sampleAnswer );
		$answerToSave->save();

		$savedAnswer = new Edr_QuizAnswer( $answerToSave->ID );

		$this->assertEquals( $answerToSave, $savedAnswer );
	}

	public function test_delete() {
		$answer = new Edr_QuizAnswer();
		$answer->save();

		$savedAnswer = new Edr_QuizAnswer( $answer->ID );
		$this->assertEquals( $answer->ID, $savedAnswer->ID );

		$answer->delete();

		$savedAnswer = new Edr_QuizAnswer( $answer->ID );
		$this->assertEquals( 0, $savedAnswer->ID );
	}
}
