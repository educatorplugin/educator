<?php

class Edr_Payment {
	public $ID = 0;
	public $parent_id = 0;
	public $course_id = 0;
	public $user_id = 0;
	public $object_id = 0;
	public $txn_id = '';
	public $payment_type = '';
	public $payment_gateway = '';
	public $payment_status = '';
	public $amount = 0.00;
	public $tax = 0.00;
	public $currency = '';
	public $payment_date = '';
	public $first_name = '';
	public $last_name = '';
	public $address = '';
	public $address_2 = '';
	public $city = '';
	public $state = '';
	public $postcode = '';
	public $country = '';
	public $ip = '';
	protected $tbl_payments;
	protected $tbl_lines;

	/**
	 * Constructor
	 *
	 * @param mixed $data
	 */
	public function __construct( $data = null ) {
		global $wpdb;
		$tables = edr_db_tables();
		$this->tbl_payments = $tables['payments'];
		$this->tbl_lines = $tables['payment_lines'];

		if ( is_numeric( $data ) ) {
			$data = $wpdb->get_row( $wpdb->prepare(
				"
				SELECT *
				FROM   $this->tbl_payments
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
			case 'parent_id':
			case 'course_id':
			case 'user_id':
			case 'object_id':
				$value = (int) $value;
				break;
			case 'amount':
			case 'tax':
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
		$fields = array( 'ID', 'parent_id', 'course_id', 'user_id', 'object_id', 'txn_id',
			'payment_type', 'payment_gateway', 'payment_status', 'amount', 'tax', 'currency',
			'payment_date', 'first_name', 'last_name', 'address', 'address_2', 'city', 'state',
			'postcode', 'country', 'ip' );

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
		$affected_rows = 0;
		$update = ( is_numeric( $this->ID ) && $this->ID > 0 );

		if ( ! $update && empty( $this->payment_date ) ) {
			$this->payment_date = date( 'Y-m-d H:i:s' );
		}

		$data = array(
			'parent_id'       => $this->parent_id,
			'course_id'       => $this->course_id,
			'user_id'         => $this->user_id,
			'object_id'       => $this->object_id,
			'txn_id'          => $this->txn_id,
			'payment_type'    => $this->payment_type,
			'payment_gateway' => $this->payment_gateway,
			'payment_status'  => $this->payment_status,
			'amount'          => $this->amount,
			'tax'             => $this->tax,
			'currency'        => $this->currency,
			'payment_date'    => $this->payment_date,
			'first_name'      => $this->first_name,
			'last_name'       => $this->last_name,
			'address'         => $this->address,
			'address_2'       => $this->address_2,
			'city'            => $this->city,
			'state'           => $this->state,
			'postcode'        => $this->postcode,
			'country'         => $this->country,
			'ip'              => $this->ip,
		);

		$data_format = array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%f', '%f', '%s',
			'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

		if ( $update ) {
			$where = array( 'ID' => $this->ID );
			$where_format = array( '%d' );
			$affected_rows = $wpdb->update( $this->tbl_payments, $data, $where, $data_format, $where_format );
		} else {
			$affected_rows = $wpdb->insert( $this->tbl_payments, $data, $data_format );

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
		
		if ( $wpdb->delete( $this->tbl_payments, $where, $where_format ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Update payment status.
	 *
	 * @param string $new_status
	 * @return int Number of rows updated.
	 */
	public function update_status( $new_status ) {
		global $wpdb;
		$data = array( 'payment_status' => $new_status );
		$where = array( 'ID' => $this->ID );
		$data_format = array( '%s' );
		$where_format = array( '%d' );

		return $wpdb->update( $this->tbl_payments, $data, $where, $data_format, $where_format );
	}

	/**
	 * Get lines.
	 *
	 * @return array
	 */
	public function get_lines() {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare(
			"
			SELECT *
			FROM   $this->tbl_lines
			WHERE  payment_id = %d
			",
			$this->ID
		) );
	}

	/**
	 * Update line.
	 *
	 * @param array $meta_item
	 */
	public function update_line( $line ) {
		global $wpdb;
		$update = isset( $line->ID ) && is_numeric( $line->ID ) && $line->ID > 0;
		$data = array(
			'payment_id' => $this->ID,
			'object_id'  => $line->object_id,
			'line_type'  => $line->line_type,
			'amount'     => $line->amount,
			'name'       => $line->name,
		);
		$data_format = array( '%d', '%d', '%s', '%f', '%s' );

		if ( isset( $line->tax ) ) {
			$data['tax'] = $line->tax;
			$data_format[] = '%f';
		}

		if ( $update ) {
			$where = array( 'ID' => $line->ID );
			$where_format = array( '%d' );
			$wpdb->update( $this->tbl_lines, $data, $where, $data_format, $where_format );
		} else {
			$wpdb->insert( $this->tbl_lines, $data, $data_format );
		}
	}
}
