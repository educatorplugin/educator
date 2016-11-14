<?php

class QuizChoiceClassTest extends WP_UnitTestCase {
	protected function _get_sample_data() {
		$data = new Edr_QuizChoice();

		$data->question_id = 1;
		$data->choice_text = 'Sample choice text';
		$data->correct = 1;
		$data->menu_order = 2;

		return $data;
	}

	public function test_construct() {
		$sampleChoice = $this->_get_sample_data();

		// Sample data object is provided.
		$choice1 = new Edr_QuizChoice( $sampleChoice );

		$this->assertEquals( $sampleChoice, $choice1 );

		// Existing ID is provided.
		$choice1->save();

		$choice2 = new Edr_QuizChoice( $choice1->ID );

		$this->assertEquals( $choice1, $choice2 );
	}

	public function test_sanitize_field() {
		$sanitize_field = new ReflectionMethod( 'Edr_QuizChoice', 'sanitize_field' );
		$sanitize_field->setAccessible(true);

		$int_fields = array( 'ID', 'question_id', 'correct', 'menu_order' );

		$choice = new Edr_QuizChoice();

		foreach ( $int_fields as $field ) {
			$this->assertSame( 1, $sanitize_field->invoke( $choice, $field, '1' ) );
		}
	}

	public function test_set_data() {
		$sampleChoice = $this->_get_sample_data();
		$choice = new Edr_QuizChoice();
		$choice->set_data( $sampleChoice );

		$this->assertEquals( $sampleChoice, $choice );
	}

	public function test_save() {
		$sampleChoice = $this->_get_sample_data();
		$choiceToSave = new Edr_QuizChoice( $sampleChoice );
		$choiceToSave->save();

		$savedChoice = new Edr_QuizChoice( $choiceToSave->ID );

		$this->assertEquals( $choiceToSave, $savedChoice );
	}

	public function test_delete() {
		$choice = new Edr_QuizChoice();
		$choice->save();

		$savedChoice = new Edr_QuizChoice( $choice->ID );
		$this->assertEquals( $choice->ID, $savedChoice->ID );

		$choice->delete();

		$savedChoice = new Edr_QuizChoice( $choice->ID );
		$this->assertEquals( 0, $savedChoice->ID );
	}
}
