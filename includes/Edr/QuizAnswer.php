<?php

class Edr_QuizAnswer {
	public $ID = 0;
	public $question_id = 0;
	public $grade_id    = 0;
	public $entry_id    = 0;
	public $choice_id   = 0;
	public $correct     = 0;
	public $answer_text = '';
	protected $tbl_answers;

	public function __construct( $data = null ) {
		global $wpdb;
		$tables = edr_db_tables();
		$this->tbl_answers = $tables['answers'];

		if ( is_numeric( $data ) ) {
			$data = $wpdb->get_row( $wpdb->prepare(
				"
				SELECT *
				FROM   $this->tbl_answers
				WHERE  ID = %d
				",
				$data
			) );
		}

		if ( ! empty( $data ) ) {
			$this->set_data( $data );
		}
	}

	/**
	 * Sanitize a field.
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return mixed
	 */
	protected function sanitize_field( $field, $value ) {
		switch ( $field ) {
			case 'ID':
			case 'question_id':
			case 'grade_id':
			case 'entry_id':
			case 'choice_id':
			case 'correct':
				$value = (int) $value;
				break;
		}

		return $value;
	}

	/**
	 * Set data.
	 *
	 * @param object $data
	 */
	public function set_data( $data ) {
		$fields = array( 'ID', 'question_id', 'grade_id', 'entry_id', 'choice_id',
			'correct', 'answer_text' );

		foreach ( $fields as $field ) {
			if ( property_exists( $data, $field ) ) {
				$this->$field = $this->sanitize_field( $field, $data->$field );
			}
		}
	}

	/**
	 * Save this answer.
	 */
	public function save() {
		global $wpdb;

		$data = array(
			'question_id' => $this->question_id,
			'grade_id'    => $this->grade_id,
			'entry_id'    => $this->entry_id,
			'choice_id'   => $this->choice_id,
			'correct'     => $this->correct,
			'answer_text' => $this->answer_text,
		);
		$data_format = array( '%d', '%d', '%d', '%d', '%d', '%s' );

		if ( $this->ID ) {
			$where = array( 'ID' => $this->ID );
			$where_format = array( '%d' );
			$affected_rows = $wpdb->update( $this->tbl_answers, $data, $where,
				$data_format, $where_format );
		} else {
			$affected_rows = $wpdb->insert( $this->tbl_answers, $data, $data_format );

			if ( false !== $affected_rows ) {
				$this->ID = $wpdb->insert_id;
			}
		}

		return ( false !== $affected_rows );
	}

	/**
	 * Delete this answer.
	 *
	 * @return false|int Number of rows deleted, false on error.
	 */
	public function delete() {
		global $wpdb;

		$where = array( 'ID' => $this->ID );
		$where_format = array( '%d' );

		return $wpdb->delete( $this->tbl_answers, $where, $where_format );
	}
}
