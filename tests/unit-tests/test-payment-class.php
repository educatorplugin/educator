<?php

class PaymentClassTest extends WP_UnitTestCase {
	public function get_sample_payment_data() {
		$data = new stdClass();
		$data->ID              = 1;
		$data->parent_id       = 2;
		$data->course_id       = 3;
		$data->user_id         = 4;
		$data->object_id       = 5;
		$data->txn_id          = 'Transaction# %s';
		$data->payment_type    = 'course';
		$data->payment_gateway = 'bank-transfer';
		$data->payment_status  = 'pending';
		$data->amount          = 19.87;
		$data->tax             = 3.87;
		$data->currency        = 'USD';
		$data->payment_date    = date( 'Y-m-d H:i:s' );
		$data->first_name      = 'John';
		$data->last_name       = 'Doe';
		$data->address         = 'address line #1';
		$data->address_2       = 'address line #2';
		$data->city            = 'Boston';
		$data->state           = 'Massachusetts';
		$data->postcode        = '12345ABC';
		$data->country         = 'US';
		$data->ip              = inet_pton( '192.168.0.16' );

		return $data;
	}

	public function payment_exists( $payment_id ) {
		global $wpdb;
		$tables = edr_db_tables();
		$payment_exists = $wpdb->get_var( $wpdb->prepare(
			"
			SELECT 'yes'
			FROM   {$tables['payments']}
			WHERE  ID = %d
			",
			$payment_id
		) );

		return ( 'yes' == $payment_exists );
	}

	public function test_construct() {
		$payment_factory = new Edr_Payment_Factory();
		$payment_id = $payment_factory->create();

		$payment = new Edr_Payment( $payment_id );
		$this->assertSame( $payment_id, $payment->ID );
	}

	public function test_set_data() {
		$payment_data = $this->get_sample_payment_data();
		$payment = new Edr_Payment();
		$payment->set_data( $payment_data );
		$expected = (array) $payment_data;
		$actual = get_object_vars( $payment );

		$this->assertSame( $expected, $actual );
	}

	public function test_save() {
		$payment_data = $this->get_sample_payment_data();
		$payment_data->ID = null;
		$payment = new Edr_Payment( $payment_data );
		$payment->save();

		$this->assertTrue( $payment->ID > 0 );

		$saved_payment = edr_get_payment( $payment->ID );
		$payment_data->ID = $payment->ID;
		$expected = (array) $payment_data;
		$actual = get_object_vars( $saved_payment );

		$this->assertSame( $expected, $actual );
	}

	public function test_delete() {
		$payment_data = $this->get_sample_payment_data();
		$payment_data->ID = null;
		$payment = new Edr_Payment( $payment_data );
		$payment->save();

		$payment_exists = $this->payment_exists( $payment->ID );
		$this->assertTrue( $payment_exists );

		$payment->delete();

		$payment_exists = $this->payment_exists( $payment->ID );
		$this->assertFalse( $payment_exists );
	}

	public function test_update_status() {
		$payment_factory = new Edr_Payment_Factory();
		$payment_id = $payment_factory->create( array(
			'payment_status' => 'pending',
		) );
		$payment = edr_get_payment( $payment_id );

		$this->assertSame( 'pending', $payment->payment_status );

		$payment->update_status( 'complete' );

		$payment = edr_get_payment( $payment_id );

		$this->assertSame( 'complete', $payment->payment_status );
	}

	public function test_get_lines() {
		$payment_factory = new Edr_Payment_Factory();
		$payment_id = $payment_factory->create();
		$payment = edr_get_payment( $payment_id );

		$line = new stdClass();
		$line->payment_id = $payment_id;
		$line->object_id  = 11;
		$line->line_type  = 'item';
		$line->amount     = 9.87;
		$line->name       = 'Some line';
		$line->tax        = 1.23;

		$payment->update_line( $line );

		$lines = $payment->get_lines();

		$line->ID = $lines[0]->ID;

		$this->assertEquals( $line, $lines[0] );
	}

	public function test_update_line() {
		$payment_factory = new Edr_Payment_Factory();
		$payment_id = $payment_factory->create();
		$payment = edr_get_payment( $payment_id );

		$line = new stdClass();
		$line->payment_id = $payment_id;
		$line->object_id  = 11;
		$line->line_type  = 'item';
		$line->amount     = 9.87;
		$line->name       = 'Some line';
		$line->tax        = 1.23;

		$payment->update_line( $line );

		$lines = $payment->get_lines();

		$line->ID = $lines[0]->ID;

		$this->assertEquals( $line, $lines[0] );

		$line->object_id  = 12;
		$line->line_type  = 'tax';
		$line->amount     = 4.56;
		$line->name       = 'Some line updated';
		$line->tax        = 3.21;

		$payment->update_line( $line );

		$lines = $payment->get_lines();

		$this->assertEquals( 1, count( $lines ) );
		$this->assertEquals( $line, $lines[0] );
	}
}
