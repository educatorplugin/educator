<?php

class Edr_Entry {
	public $ID = 0;
	public $course_id = 0;
	public $object_id = 0;
	public $user_id = 0;
	public $payment_id = 0;
	public $grade = 0;
	public $entry_origin = 'payment';
	public $entry_status = '';
	public $entry_date = '';
	public $complete_date = '';
	protected $tbl_entries;

	/**
	 * Constructor
	 *
	 * @param mixed $data
	 */
	public function __construct( $data = null ) {
		global $wpdb;
		$tables = edr_db_tables();
		$this->tbl_entries = $tables['entries'];

		if ( is_numeric( $data ) && $data > 0 ) {
			$data = $wpdb->get_row( $wpdb->prepare(
				"
				SELECT *
				FROM   $this->tbl_entries
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
			case 'course_id':
			case 'object_id':
			case 'user_id':
			case 'payment_id':
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
		$fields = array( 'ID', 'course_id', 'object_id', 'user_id', 'payment_id',
			'grade', 'entry_origin', 'entry_status', 'entry_date', 'complete_date' );

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

		if ( is_numeric( $this->ID ) && $this->ID > 0 ) {
			$affected_rows = $wpdb->update(
				$this->tbl_entries,
				array(
					'course_id'     => $this->course_id,
					'object_id'     => $this->object_id,
					'user_id'       => $this->user_id,
					'payment_id'    => $this->payment_id,
					'grade'         => $this->grade,
					'entry_origin'  => $this->entry_origin,
					'entry_status'  => $this->entry_status,
					'entry_date'    => $this->entry_date,
					'complete_date' => $this->complete_date
				),
				array( 'ID' => $this->ID ),
				array( '%d', '%d', '%d', '%d', '%f', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			$affected_rows = $wpdb->insert(
				$this->tbl_entries,
				array(
					'course_id'     => $this->course_id,
					'object_id'     => $this->object_id,
					'user_id'       => $this->user_id,
					'payment_id'    => $this->payment_id,
					'grade'         => $this->grade,
					'entry_origin'  => $this->entry_origin,
					'entry_status'  => $this->entry_status,
					'entry_date'    => $this->entry_date,
					'complete_date' => $this->complete_date
				),
				array( '%d', '%d', '%d', '%d', '%f', '%s', '%s', '%s', '%s' )
			);

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

		if ( $wpdb->delete( $this->tbl_entries, $where, $where_format ) ) {
			return true;
		}

		return false;
	}
}
