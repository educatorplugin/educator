<?php

class Edr_Question_Factory extends WP_UnitTest_Factory_For_Thing {
	public function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = array(
			'lesson_id'        => 0,
			'question'         => new WP_UnitTest_Generator_Sequence( 'Question# %s' ),
			'question_type'    => '',
			'question_content' => '',
			'menu_order'       => '',
		);
	}

	public function create_object( $args ) {
		$question = new Edr_Question( (object) $args );
		$saved = $question->save();

		return ( $saved ) ? $question->ID : false;
	}

	public function update_object( $id, $fields ) {
		$question = new Edr_Question( $id );
		$question->set_data( (object) $fields );
		$saved = $question->save();

		return $saved;
	}

	public function get_object_by_id( $id ) {
		$question = new Edr_Question( $id );

		return $question;
	}
}
