<?php

class Edr_Gateway_Base_Test extends Edr_Gateway_Base {
	public function __construct() {
		$this->id = 'test-gateway';
		$this->title = 'Test Gateway';

		$this->init_options( array(
			'test_field' => array(
				'type'      => 'textarea',
				'label'     => 'Test Field',
				'id'        => 'test-field',
			),
		) );
	}

	public function sanitize_admin_options( $input ) {
		foreach ( $input as $option_name => $value ) {
			switch ( $option_name ) {
				case 'test_field':
					$input[ $option_name ] = $value . ' SANITIZED';
					break;
			}
		}

		return $input;
	}
}

class GatewayBaseClassTest extends WP_UnitTestCase {
	/**
	 * Test:
	 * - save_admin_options
	 * - get_option
	 * - sanitize_admin_options
	 */
	public function test_save_admin_options_AND_get_option() {
		$_POST['edr_test-gateway_default'] = 0;
		$_POST['edr_test-gateway_enabled'] = 1;
		$_POST['edr_test-gateway_test_field'] = 'TEST';

		$test_gateway = new Edr_Gateway_Base_Test();
		$test_gateway->save_admin_options();

		$this->assertSame( 0, $test_gateway->get_option( 'default' ) );
		$this->assertSame( 1, $test_gateway->get_option( 'enabled' ) );
		$this->assertSame( 'TEST SANITIZED', $test_gateway->get_option( 'test_field' ) );
	}

	public function test_create_payment() {
		$course_id = $this->factory->post->create( array(
			'post_type' => EDR_PT_COURSE,
		) );
		update_post_meta( $course_id, '_edr_price', 10.99 );

		$_POST['billing_address']   = 'address1';
		$_POST['billing_address_2'] = 'address2';
		$_POST['billing_city']      = 'city';
		$_POST['billing_state']     = 'state';
		$_POST['billing_postcode']  = '12345';
		$_POST['billing_country']   = 'UA';

		$user_id = 1;
		wp_set_current_user( $user_id );
		Edr_StudentAccount::save_billing_data( $user_id );
		update_user_meta( $user_id, 'first_name', 'John' );
		update_user_meta( $user_id, 'last_name', 'Doe' );

		$options = get_option( 'edr_taxes', array() );
		$options['enable'] = 1;
		update_option( 'edr_taxes', $options );

		// Add tax rates, to test if taxes calculation is perfomed properly.
		$tax_manager = Edr_TaxManager::get_instance();
		$tax_class = array(
			'name'        => 'class1',
			'description' => 'Class1',
		);
		$tax_manager->add_tax_class( $tax_class );

		$rate1 = new stdClass();
		$rate1->ID         = null;
		$rate1->name       = 'Rate 1';
		$rate1->country    = 'UA';
		$rate1->state      = '';
		$rate1->tax_class  = $tax_class['name'];
		$rate1->priority   = 0;
		$rate1->rate       = 9.54;
		$rate1->rate_order = 0;

		$rate1->ID = $tax_manager->update_tax_rate( $rate1 );

		update_post_meta( $course_id, '_edr_tax_class', $tax_class['name'] );

		$test_gateway = new Edr_Gateway_Base_Test();
		$actual_payment = $test_gateway->create_payment( $course_id, $user_id, 'course', array(
			'ip' => '127.0.0.1',
		) );

		$this->assertTrue( $actual_payment->ID > 0 );

		$expected_payment = new stdClass();
		$expected_payment->ID = $actual_payment->ID;
		$expected_payment->parent_id = 0;
		$expected_payment->course_id = 0;
		$expected_payment->user_id = $user_id;
		$expected_payment->object_id = $course_id;
		$expected_payment->txn_id = '';
		$expected_payment->payment_type = 'course';
		$expected_payment->payment_gateway = 'test-gateway';
		$expected_payment->payment_status = 'pending';
		$expected_payment->amount = 12.04;
		$expected_payment->tax = 1.05;
		$expected_payment->currency = '';
		$expected_payment->payment_date = date( 'Y-m-d H:i:s' );
		$expected_payment->first_name = 'John';
		$expected_payment->last_name = 'Doe';
		$expected_payment->address = 'address1';
		$expected_payment->address_2 = 'address2';
		$expected_payment->city = 'city';
		$expected_payment->state = 'state';
		$expected_payment->postcode = '12345';
		$expected_payment->country = 'UA';
		$expected_payment->ip = inet_pton( '127.0.0.1' );

		$actual_tmp_payment = new stdClass();

		foreach ( $expected_payment as $key => $value ) {
			$actual_tmp_payment->$key = $actual_payment->$key;
		}

		$this->assertEquals( $expected_payment, $actual_tmp_payment );

		$actual_payment_lines = $actual_payment->get_lines();
		$expected_payment_lines = array();
		$expected_payment_lines[0] = new stdClass();
		$expected_payment_lines[0]->ID = $actual_payment_lines[0]->ID;
		$expected_payment_lines[0]->payment_id = $actual_payment->ID;
		$expected_payment_lines[0]->object_id = $rate1->ID;
		$expected_payment_lines[0]->line_type = 'tax';
		$expected_payment_lines[0]->amount = 1.05;
		$expected_payment_lines[0]->tax = 0.0;
		$expected_payment_lines[0]->name = $rate1->name;

		$this->assertEquals( $expected_payment_lines, $actual_payment_lines );
	}

	public function test_get_redirect_url() {
		$test_gateway = new Edr_Gateway_Base_Test();
		$actual_redirect_url = $test_gateway->get_redirect_url( array(
			'value' => '123ABC'
		) );
		$expected_redirect_url = edr_get_endpoint_url( 'edr-payment', '123ABC',
			get_permalink( edr_get_page_id( 'payment' ) ) );

		$this->assertSame( $expected_redirect_url, $actual_redirect_url );
	}
}
