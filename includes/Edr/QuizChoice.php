<?php

class Edr_QuizChoice {
	public $ID = 0;
	public $question_id = 0;
	public $choice_text = '';
	public $correct = 0;
	public $menu_order = 0;
	public $tbl_choices;

	public function __construct( $data = null ) {
		global $wpdb;
		$tables = edr_db_tables();
		$this->tbl_choices = $tables['choices'];

		if ( is_numeric( $data ) ) {
			$data = $wpdb->get_row( $wpdb->prepare(
				"
				SELECT *
				FROM   $this->tbl_choices
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
			case 'correct':
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
		$fields = array( 'ID', 'question_id', 'choice_text', 'correct', 'menu_order' );

		foreach ( $fields as $field ) {
			if ( property_exists( $data, $field ) ) {
				$this->$field = $this->sanitize_field( $field, $data->$field );
			}
		}
	}

	/**
	 * Save this choice.
	 */
	public function save() {
		global $wpdb;

		$data = array(
			'question_id' => $this->question_id,
			'choice_text' => $this->choice_text,
			'correct'     => $this->correct,
			'menu_order'  => $this->menu_order,
		);
		$data_format = array( '%d', '%s', '%d', '%d' );

		if ( $this->ID ) {
			$where = array( 'ID' => $this->ID );
			$where_format = array( '%d' );
			$affected_rows = $wpdb->update( $this->tbl_choices, $data, $where,
				$data_format, $where_format );
		} else {
			$affected_rows = $wpdb->insert( $this->tbl_choices, $data, $data_format );

			if ( false !== $affected_rows ) {
				$this->ID = $wpdb->insert_id;
			}
		}

		return ( false !== $affected_rows );
	}

	/**
	 * Delete this choice.
	 *
	 * @return false|int Number of rows deleted, false on error.
	 */
	public function delete() {
		global $wpdb;

		$where = array( 'ID' => $this->ID );
		$where_format = array( '%d' );

		return $wpdb->delete( $this->tbl_choices, $where, $where_format );
	}
}
