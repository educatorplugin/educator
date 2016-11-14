<?php

class QuizGradeClassTest extends WP_UnitTestCase {
	protected function _get_sample_data() {
		$data = new Edr_QuizGrade();

		$data->lesson_id = 1;
		$data->entry_id = 2;
		$data->user_id = 3;
		$data->grade = 88.99;
		$data->status = 'draft';

		return $data;
	}

	public function test_construct() {
		$sampleGrade = $this->_get_sample_data();

		// Sample data object is provided.
		$grade1 = new Edr_QuizGrade( $sampleGrade );

		$this->assertEquals( $sampleGrade, $grade1 );

		// Existing ID is provided.
		$grade1->save();

		$grade2 = new Edr_QuizGrade( $grade1->ID );

		$this->assertEquals( $grade1, $grade2 );
	}

	public function test_sanitize_field() {
		$sanitize_field = new ReflectionMethod( 'Edr_QuizGrade', 'sanitize_field' );
		$sanitize_field->setAccessible(true);

		$int_fields = array( 'ID', 'lesson_id', 'entry_id', 'user_id' );

		$grade = new Edr_QuizGrade();

		foreach ( $int_fields as $field ) {
			$this->assertSame( 1, $sanitize_field->invoke( $grade, $field, '1' ) );
		}

		$this->assertSame( 88.99, $sanitize_field->invoke( $grade, 'grade', '88.99' ) );
	}

	public function test_set_data() {
		$sampleGrade = $this->_get_sample_data();
		$grade = new Edr_QuizGrade();
		$grade->set_data( $sampleGrade );

		$this->assertEquals( $sampleGrade, $grade );
	}

	public function test_save() {
		$sampleGrade = $this->_get_sample_data();
		$gradeToSave = new Edr_QuizGrade( $sampleGrade );
		$gradeToSave->save();

		$savedGrade = new Edr_QuizGrade( $gradeToSave->ID );

		$this->assertEquals( $gradeToSave, $savedGrade );
	}

	public function test_delete() {
		$grade = new Edr_QuizGrade();
		$grade->save();

		$savedGrade = new Edr_QuizGrade( $grade->ID );
		$this->assertEquals( $grade->ID, $savedGrade->ID );

		$grade->delete();

		$savedGrade = new Edr_QuizGrade( $grade->ID );
		$this->assertEquals( 0, $savedGrade->ID );
	}
}
