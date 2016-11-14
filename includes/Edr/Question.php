<?php

class Edr_Question {
	public $ID = 0;
	public $lesson_id = 0;
	public $question = '';
	public $question_type = '';
	public $question_content = '';
	public $optional = 0;
	public $menu_order = 0;
	protected $tbl_questions;

	/**
	 * Constructor.
	 *
	 * @param mixed $data
	 */
	public function __construct( $data = null ) {
		global $wpdb;
		$tables = edr_db_tables();
		$this->tbl_questions = $tables['questions'];

		if ( is_numeric( $data ) ) {
			$data = $wpdb->get_row( $wpdb->prepare(
				"
				SELECT *
				FROM   $this->tbl_questions
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
			case 'lesson_id':
			case 'optional':
			case 'menu_order':
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
		$fields = array( 'ID', 'lesson_id', 'question', 'question_type',
			'question_content', 'optional', 'menu_order' );

		foreach ( $fields as $field ) {
			if ( property_exists( $data, $field ) ) {
				$this->$field = $this->sanitize_field( $field, $data->$field );
			}
		}
	}

	/**
	 * Save to database.
	 *
	 * @return boolean
	 */
	public function save() {
		global $wpdb;
		$affected_rows = null;
		$data = array(
			'lesson_id'        => $this->lesson_id,
			'question'         => $this->question,
			'question_type'    => $this->question_type,
			'question_content' => $this->question_content,
			'optional'         => $this->optional,
			'menu_order'       => $this->menu_order,
		);
		$data_format = array( '%d', '%s', '%s', '%s', '%d', '%d' );

		if ( is_numeric( $this->ID ) && $this->ID > 0 ) {
			$where = array( 'ID' => $this->ID );
			$where_format = array( '%d' );
			$affected_rows = $wpdb->update( $this->tbl_questions, $data, $where,
				$data_format, $where_format );
		} else {
			$affected_rows = $wpdb->insert( $this->tbl_questions, $data, $data_format );

			if ( false !== $affected_rows ) {
				$this->ID = $wpdb->insert_id;
			}
		}

		return ( false !== $affected_rows );
	}

	/**
	 * Delete from database.
	 *
	 * @return boolean
	 */
	public function delete() {
		global $wpdb;
		$where = array( 'ID' => $this->ID );
		$where_format = array( '%d' );

		if ( $wpdb->delete( $this->tbl_questions, $where, $where_format ) ) {
			return true;
		}

		return false;
	}
}
