<?php

class Edr_QuizGrade {
	public $ID = 0;
	public $lesson_id = 0;
	public $entry_id = 0;
	public $user_id = 0;
	public $grade = 0.0;
	public $status = 'draft';
	protected $tbl_grades;

	public function __construct( $data = null ) {
		global $wpdb;

		$tables = edr_db_tables();
		$this->tbl_grades = $tables['grades'];

		if ( is_numeric( $data ) ) {
			$data = $wpdb->get_row( $wpdb->prepare(
				"
				SELECT *
				FROM   $this->tbl_grades
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
			case 'entry_id':
			case 'user_id':
				$value = (int) $value;
				break;

			case 'grade':
				$value = (float) $value;
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
		$fields = array( 'ID', 'lesson_id', 'entry_id', 'user_id', 'grade', 'status' );

		foreach ( $fields as $field ) {
			if ( property_exists( $data, $field ) ) {
				$this->$field = $this->sanitize_field( $field, $data->$field );
			}
		}
	}

	/**
	 * Save this grade.
	 */
	public function save() {
		global $wpdb;

		$data = array(
			'lesson_id' => $this->lesson_id,
			'entry_id'  => $this->entry_id,
			'user_id'   => $this->user_id,
			'grade'     => $this->grade,
			'status'    => $this->status,
		);
		$data_format = array( '%d', '%d', '%d', '%f', '%s' );

		if ( $this->ID ) {
			$where = array( 'ID' => $this->ID );
			$where_format = array( '%d' );
			$affected_rows = $wpdb->update( $this->tbl_grades, $data, $where,
				$data_format, $where_format );
		} else {
			$affected_rows = $wpdb->insert( $this->tbl_grades, $data, $data_format );

			if ( false !== $affected_rows ) {
				$this->ID = $wpdb->insert_id;
			}
		}

		return ( false !== $affected_rows );
	}

	/**
	 * Delete this grade.
	 *
	 * @return false|int Number of rows deleted, false on error.
	 */
	public function delete() {
		global $wpdb;

		$where = array( 'ID' => $this->ID );
		$where_format = array( '%d' );

		return $wpdb->delete( $this->tbl_grades, $where, $where_format );
	}
}
