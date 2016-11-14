<?php

class StudentAccountClassTest extends WP_UnitTestCase {
	protected function set_billing_data_post() {
		$_POST['billing_first_name'] = 'John';
		$_POST['billing_last_name']  = 'Doe';
		$_POST['billing_address']    = 'example street 1';
		$_POST['billing_address_2']  = 'house 2';
		$_POST['billing_city']       = 'ExampleCity';
		$_POST['billing_state']      = 'ExampleState';
		$_POST['billing_postcode']   = '12345ABC';
		$_POST['billing_country']    = 'ExampleCountry';
	}

	protected function include_billing() {
		$options = get_option( 'edr_taxes', array() );
		$options['enable'] = 1;
		update_option( 'edr_taxes', $options );
	}

	public function test_parse_register_errors() {
		$parse_register_errors = new ReflectionMethod( 'Edr_StudentAccount', 'parse_register_errors' );
		$parse_register_errors->setAccessible(true);

		// account_info_empty.
		$actual = $parse_register_errors->invoke( 'Edr_StudentAccount', array( 'account_info_empty' ) );
		$expected = array( 'account_username' => true, 'account_email' => true );
		$this->assertEquals( $expected, $actual );

		// invalid_username.
		$actual = $parse_register_errors->invoke( 'Edr_StudentAccount', array( 'invalid_username' ) );
		$expected = array( 'account_username' => true );
		$this->assertEquals( $expected, $actual );

		// existing_user_login.
		$actual = $parse_register_errors->invoke( 'Edr_StudentAccount', array( 'existing_user_login' ) );
		$expected = array( 'account_username' => true );
		$this->assertEquals( $expected, $actual );

		// invalid_email.
		$actual = $parse_register_errors->invoke( 'Edr_StudentAccount', array( 'invalid_email' ) );
		$expected = array( 'account_email' => true );
		$this->assertEquals( $expected, $actual );

		// existing_user_email.
		$actual = $parse_register_errors->invoke( 'Edr_StudentAccount', array( 'existing_user_email' ) );
		$expected = array( 'account_email' => true );
		$this->assertEquals( $expected, $actual );
	}

	public function test_register_form_validate() {
		$course_id = $this->factory->post->create( array(
			'post_type' => EDR_PT_COURSE,
			'meta_input' => array(
				'_edr_price' => 18.99,
			),
		) );
		$course = get_post( $course_id );
		$errors = new WP_Error();

		// Basic validation.
		Edr_StudentAccount::register_form_validate( $errors, $course );
		$expected_error_codes = array( 'account_info_empty' );
		$this->assertEquals( $expected_error_codes, $errors->get_error_codes() );

		// Include validation of the billing fields.
		$this->include_billing();
		Edr_StudentAccount::register_form_validate( $errors, $course );
		$expected_error_codes = array(
			'account_info_empty',
			'billing_first_name_empty',
			'billing_last_name_empty',
			'billing_address_empty',
			'billing_city_empty',
			'billing_state_empty',
			'billing_postcode_empty',
			'billing_country_empty',
		);
		$this->assertEquals( $expected_error_codes, $errors->get_error_codes() );
	}

	public function test_register_form() {
		$course_id = $this->factory->post->create( array(
			'post_type' => EDR_PT_COURSE,
			'meta_input' => array(
				'_edr_price' => 18.99,
			),
		) );
		$course = get_post( $course_id );

		ob_start();
		Edr_StudentAccount::register_form( null, $course );
		$actual_html = ob_get_clean();

		$this->assertTrue( strpos( $actual_html, 'name="account_username"' ) !== false );
		$this->assertTrue( strpos( $actual_html, 'name="account_email"' ) !== false );
		$this->assertTrue( strpos( $actual_html, 'name="billing_first_name"' ) === false );

		// Include billing fields, values, and errors.
		$this->include_billing();
		$_POST['account_username'] = 'test';
		$_POST['account_email'] = 'test@example.com';
		$wp_error = new WP_Error();
		$wp_error->add('invalid_username', 'Invalid username');
		ob_start();
		Edr_StudentAccount::register_form( $wp_error, $course );
		$actual_html = ob_get_clean();
		$this->assertTrue( strpos( $actual_html, 'name="billing_first_name"' ) !== false );
		$this->assertTrue( strpos( $actual_html, 'name="billing_last_name"' ) !== false );
		$this->assertTrue( strpos( $actual_html, 'name="billing_address"' ) !== false );
		$this->assertTrue( strpos( $actual_html, 'name="billing_address_2"' ) !== false );
		$this->assertTrue( strpos( $actual_html, 'name="billing_city"' ) !== false );
		$this->assertTrue( strpos( $actual_html, 'name="billing_state"' ) !== false );
		$this->assertTrue( strpos( $actual_html, 'name="billing_postcode"' ) !== false );
		$this->assertTrue( strpos( $actual_html, 'name="billing_country"' ) !== false );
		$this->assertTrue( strpos( $actual_html, 'id="account-username" class="error"' ) !== false );
		$this->assertTrue( strpos( $actual_html, 'name="account_username" value="test"' ) !== false );
		$this->assertTrue( strpos( $actual_html, 'name="account_email" value="test@example.com"' ) !== false );
	}

	public function test_register_user_data() {
		$course_id = $this->factory->post->create( array(
			'post_type' => EDR_PT_COURSE,
			'meta_input' => array(
				'_edr_price' => 18.99,
			),
		) );
		$course = get_post( $course_id );

		$_POST['account_username'] = 'test';
		$_POST['account_email'] = 'test@example.com';
		$actual_user_data = Edr_StudentAccount::register_user_data( array(), $course );
		$expected_user_data = array(
			'user_login' => 'test',
			'user_email' => 'test@example.com',
			'user_pass'  => $actual_user_data['user_pass'],
		);
		$this->assertEquals( $expected_user_data, $actual_user_data );

		// Including first and last names.
		$this->include_billing();
		$_POST['billing_first_name'] = 'John';
		$_POST['billing_last_name'] = 'Doe';
		$actual_user_data = Edr_StudentAccount::register_user_data( array(), $course );
		$expected_user_data['first_name'] = 'John';
		$expected_user_data['last_name'] = 'Doe';
		$expected_user_data['user_pass'] = $actual_user_data['user_pass'];
		$this->assertEquals( $expected_user_data, $actual_user_data );
	}

	public function test_save_billing_data() {
		$user_id = $this->factory->user->create( array( 'role' => 'student' ) );

		$this->set_billing_data_post();

		Edr_StudentAccount::save_billing_data( $user_id );

		$actual_user_meta = get_user_meta( $user_id, '_edr_billing', true );
		$expected_user_meta = array(
			'address'   => 'example street 1',
			'address_2' => 'house 2',
			'city'      => 'ExampleCity',
			'state'     => 'ExampleState',
			'postcode'  => '12345ABC',
			'country'   => 'ExampleCountry',
		);
		$this->assertEquals( $expected_user_meta, $actual_user_meta );
	}

	public function test_new_student() {
		$user_id = $this->factory->user->create( array( 'role' => 'student' ) );
		$course_id = $this->factory->post->create( array(
			'post_type' => EDR_PT_COURSE,
			'meta_input' => array(
				'_edr_price' => 18.99,
			),
		) );
		$course = get_post( $course_id );

		Edr_StudentAccount::new_student( $user_id, $course );
		$actual_user_meta = get_user_meta( $user_id, '_edr_billing', true );
		$this->assertEmpty( $actual_user_meta );

		// Including billing data.
		$this->include_billing();
		$this->set_billing_data_post();
		Edr_StudentAccount::new_student( $user_id, $course );
		$actual_user_meta = get_user_meta( $user_id, '_edr_billing', true );
		$expected_user_meta = array(
			'address'   => 'example street 1',
			'address_2' => 'house 2',
			'city'      => 'ExampleCity',
			'state'     => 'ExampleState',
			'postcode'  => '12345ABC',
			'country'   => 'ExampleCountry',
		);
		$this->assertEquals( $expected_user_meta, $actual_user_meta );
	}

	public function test_update_student() {
		$user_id = $this->factory->user->create( array( 'role' => 'student' ) );
		$course_id = $this->factory->post->create( array(
			'post_type'  => EDR_PT_COURSE,
			'meta_input' => array(
				'_edr_price' => 18.99,
			),
		) );
		$course = get_post( $course_id );

		$this->set_billing_data_post();

		Edr_StudentAccount::update_student( $user_id, $course );
		$this->assertEmpty( get_user_meta( $user_id, 'first_name', true ) );

		// Including billing data.
		$this->include_billing();
		Edr_StudentAccount::update_student( $user_id, $course );
		$actual_billing_meta = get_user_meta( $user_id, '_edr_billing', true );
		$expected_billing_meta = array(
			'address'   => 'example street 1',
			'address_2' => 'house 2',
			'city'      => 'ExampleCity',
			'state'     => 'ExampleState',
			'postcode'  => '12345ABC',
			'country'   => 'ExampleCountry',
		);
		$this->assertEquals( $expected_billing_meta, $actual_billing_meta );
		$this->assertSame( 'John', get_user_meta( $user_id, 'first_name', true ) );
		$this->assertSame( 'Doe', get_user_meta( $user_id, 'last_name', true ) );
	}

	public function test_payment_info() {
		$course_id = $this->factory->post->create( array(
			'post_type'  => EDR_PT_COURSE,
			'post_title' => 'The Test Course 1',
			'meta_input' => array(
				'_edr_price' => 18.99,
			),
		) );
		$course = get_post( $course_id );

		$payment_info_html = Edr_StudentAccount::payment_info( $course, array(
			'price' => 18.99,
		) );

		$this->assertTrue( strpos( $payment_info_html, 'The Test Course 1' ) !== false );
		$this->assertSame( 2, substr_count( $payment_info_html, '18.99' ) );// price in items list and total.
	}
}
