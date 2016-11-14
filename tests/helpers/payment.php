<?php

class Edr_Payment_Factory extends WP_UnitTest_Factory_For_Thing {
	public function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = array(
			'parent_id'       => 0,
			'course_id'       => 0,
			'user_id'         => 0,
			'object_id'       => 0,
			'txn_id'          => new WP_UnitTest_Generator_Sequence( 'Transaction# %s' ),
			'payment_type'    => 'course',
			'payment_gateway' => 'bank-transfer',
			'payment_status'  => 'pending',
			'amount'          => 19.87,
			'tax'             => 3.87,
			'currency'        => 'USD',
			'payment_date'    => date( 'Y-m-d H:i:s' ),
			'first_name'      => 'John',
			'last_name'       => 'Doe',
			'address'         => 'address line #1',
			'address_2'       => 'address line #2',
			'city'            => 'Boston',
			'state'           => 'Massachusetts',
			'postcode'        => '12345ABC',
			'country'         => 'US',
			'ip'              => inet_pton( '192.168.0.16' ),
		);
	}

	public function create_object( $args ) {
		$payment = new Edr_Payment( (object) $args );
		$saved = $payment->save();

		return ( $saved ) ? $payment->ID : false;
	}

	public function update_object( $id, $fields ) {
		$payment = new Edr_Payment( $id );
		$payment->set_data( (object) $fields );
		$saved = $payment->save();

		return $saved;
	}

	public function get_object_by_id( $id ) {
		$payment = new Edr_Payment( $id );

		return $payment;
	}
}
