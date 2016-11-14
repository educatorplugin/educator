<?php

class QuestionClassTest extends WP_UnitTestCase {
	public function get_sample_question_data() {
		$data = new stdClass();
		$data->ID               = 0;
		$data->lesson_id        = 1;
		$data->question         = 'Question #1';
		$data->question_type    = 'multiplechoice';
		$data->question_content = 'Some content';
		$data->optional         = 0;
		$data->menu_order       = 0;

		return $data;
	}

	public function question_exists( $question_id ) {
		global $wpdb;
		$tables = edr_db_tables();
		$question_exists = $wpdb->get_var( $wpdb->prepare(
			"
			SELECT 'yes'
			FROM   {$tables['questions']}
			WHERE  ID = %d
			",
			$question_id
		) );

		return ( 'yes' == $question_exists );
	}

	public function test_construct() {
		$question_factory = new Edr_Question_Factory();
		$question_id = $question_factory->create();

		$question = new Edr_Question( $question_id );
		$this->assertSame( $question_id, $question->ID );
	}

	public function test_set_data() {
		$question_data = $this->get_sample_question_data();
		$question = new Edr_Question();
		$question->set_data( $question_data );
		$expected = (array) $question_data;
		$actual = get_object_vars( $question );

		$this->assertSame( $expected, $actual );
	}

	public function test_save() {
		$question_data = $this->get_sample_question_data();
		$question_data->ID = null;
		$question = new Edr_Question( $question_data );
		$question->save();

		$saved_question = edr_get_question( $question->ID );
		$question_data->ID = $question->ID;
		$expected = (array) $question_data;
		$actual = get_object_vars( $saved_question );

		$this->assertSame( $expected, $actual );
	}

	public function test_delete() {
		$question_data = $this->get_sample_question_data();
		$question_data->ID = null;
		$question = new Edr_Question( $question_data );
		$question->save();

		$question_exists = $this->question_exists( $question->ID );
		$this->assertTrue( $question_exists );

		$question->delete();

		$question_exists = $this->question_exists( $question->ID );
		$this->assertFalse( $question_exists );
	}
}
