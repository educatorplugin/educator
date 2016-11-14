<?php

class FunctionsTest extends WP_UnitTestCase {
	public function test_get_option() {
		$settings = array(
			'currency'          => 'EUR',
			'currency_position' => 'before',
		);

		update_option( 'edr_settings', $settings );

		// Get option by section and key.
		$actual_currency = edr_get_option( 'settings', 'currency' );
		$this->assertEquals( $settings['currency'], $actual_currency );

		// Get option by section.
		$actual_settings = edr_get_option( 'settings' );
		$this->assertEquals( $settings, $actual_settings );

		// Key doesn't exist.
		$this->assertNull( edr_get_option( 'settings', 'nonexistantkey' ) );

		// Section doesn't exist.
		$this->assertNull( edr_get_option( 'nonexistantsection' ) );
	}

	public function test_db_tables() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'edr_';
		$expected_tables = array(
			'payments'      => $prefix . 'payments',
			'entries'       => $prefix . 'entries',
			'questions'     => $prefix . 'questions',
			'choices'       => $prefix . 'choices',
			'answers'       => $prefix . 'answers',
			'grades'        => $prefix . 'grades',
			'members'       => $prefix . 'members',
			'tax_rates'     => $prefix . 'tax_rates',
			'payment_lines' => $prefix . 'payment_lines',
			'entry_meta'    => $prefix . 'entry_meta',
		);
		$actual_tables = edr_db_tables();

		$this->assertEquals( $expected_tables, $actual_tables );
	}

	public function test_get_currencies() {
		$currencies = edr_get_currencies();

		$this->assertCount(31, $currencies);
	}

	public function test_get_currency() {
		$currency = edr_get_currency();

		$this->assertEquals( '', $currency );

		$settings = edr_get_option( 'settings' );
		$settings['currency'] = 'UAH';
		update_option( 'edr_settings', $settings );

		$currency = edr_get_currency();

		$this->assertEquals( 'UAH', $currency );
	}

	public function test_get_currency_symbol() {
		$symbol = edr_get_currency_symbol( 'SYMBOLNOTFOUND' );

		$this->assertEquals( 'SYMBOLNOTFOUND', $symbol );

		$symbol = edr_get_currency_symbol( 'USD' );

		$this->assertEquals( '&#36;', $symbol );
	}

	public function test_get_endpoint_url() {
		$home_url = home_url( '/' );
		$tmp = get_option( 'permalink_structure' );

		update_option( 'permalink_structure', '' );

		$endpoint_url = edr_get_endpoint_url( 'endpoint-name', 'endpoint-value', $home_url );

		$this->assertEquals( $home_url . '?endpoint-name=endpoint-value', $endpoint_url );

		update_option( 'permalink_structure', '/%postname%/' );

		$endpoint_url = edr_get_endpoint_url( 'endpoint-name', 'endpoint-value', $home_url );

		$this->assertEquals( $home_url . 'endpoint-name/endpoint-value', $endpoint_url );

		update_option( 'permalink_structure', $tmp );
	}

	public function test_get_page_id() {
		$settings = edr_get_option( 'settings' );
		$settings['payment_page'] = 123456789;
		update_option( 'edr_settings', $settings );
		$page_id = edr_get_page_id( 'payment' );

		$this->assertEquals( 123456789, $page_id );

		$page_id = edr_get_page_id( 'notapagekey' );

		$this->assertEquals( 0, $page_id );
	}

	public function test_internal_message() {
		$expected_message = array( 'testkey' => 'testvalue' );

		edr_internal_message( 'mymessage', $expected_message );

		$actual_message = edr_internal_message( 'mymessage' );

		$this->assertEquals( $expected_message, $actual_message );
	}

	public function test_get_difficulty_levels() {
		$difficulty_levels = edr_get_difficulty_levels();

		$this->assertArrayHasKey( 'beginner', $difficulty_levels );
		$this->assertArrayHasKey( 'intermediate', $difficulty_levels );
		$this->assertArrayHasKey( 'advanced', $difficulty_levels );
	}

	public function test_get_difficulty() {
		$post_id = $this->factory->post->create();

		update_post_meta( $post_id, '_edr_difficulty', 'intermediate' );

		$expected = array(
			'key' => 'intermediate',
			'label' => __( 'Intermediate', 'edr' ),
		);
		$actual = edr_get_difficulty( $post_id );

		$this->assertEquals( $expected, $actual );
	}

	public function test_send_email_notification() {
		$assert = function( $args ) {
			if ( in_array( 'mailto@educatorplugin.com', $args['to'] ) ) {
				$this->assertEquals( 'subject is Dummy subject', $args['subject'] );
				$this->assertEquals( 'hello John Doe, please login, ' . wp_login_url(), $args['message'] );
			}
		};

		add_filter( 'wp_mail', $assert );

		update_option( 'edr_emailtemplatename', array(
			'subject'  => 'subject is {subject}',
			'template' => 'hello {name}, please login, {login_link}'
		) );

		$to = 'mailto@educatorplugin.com';
		$template = 'emailtemplatename';
		$subject_vars = array( 'subject' => 'Dummy subject' );
		$template_vars = array( 'name' => 'John Doe' );

		$email_sent = edr_send_notification( $to, $template, $subject_vars, $template_vars );

		$this->assertTrue( $email_sent );
	}

	public function test_is_page() {
		global $wp_query;
		$page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		$settings = edr_get_option( 'settings' );
		$settings['payment_page'] = $page_id;
		update_option( 'edr_settings', $settings );
		$wp_query = new WP_Query( array( 'page_id' => $page_id ) );

		while ( $wp_query->have_posts() ) {
			$wp_query->the_post();
			$this->assertTrue( edr_is_page( 'payment' ) );
		}

		wp_reset_query();
	}

	public function test_collect_billing_data() {
		$course_id = $this->factory->post->create( array( 'post_type' => EDR_PT_COURSE ) );
		$options = get_option( 'edr_taxes', array() );
		$options['enable'] = 1;
		update_option( 'edr_taxes', $options );
		update_post_meta( $course_id, '_edr_price', 10.99 );
		$actual = edr_collect_billing_data( $course_id );
		$this->assertTrue( $actual );

		$options['enable'] = 0;
		update_option( 'edr_taxes', $options );
		$actual = edr_collect_billing_data( $course_id );
		$this->assertFalse( $actual );

		$options['enable'] = 1;
		update_option( 'edr_taxes', $options );
		update_post_meta( $course_id, '_edr_price', 0 );
		$actual = edr_collect_billing_data( $course_id );
		$this->assertFalse( $actual );

		$membership_id = $this->factory->post->create( array( 'post_type' => EDR_PT_MEMBERSHIP ) );
		$options['enable'] = 1;
		update_option( 'edr_taxes', $options );
		update_post_meta( $membership_id, '_edr_price', 10.99 );
		$actual = edr_collect_billing_data( $membership_id );
		$this->assertTrue( $actual );

		$options['enable'] = 0;
		update_option( 'edr_taxes', $options );
		$actual = edr_collect_billing_data( $membership_id );
		$this->assertFalse( $actual );

		$options['enable'] = 1;
		update_option( 'edr_taxes', $options );
		update_post_meta( $membership_id, '_edr_price', 0 );
		$actual = edr_collect_billing_data( $membership_id );
		$this->assertFalse( $actual );
	}

	public function test_get_location() {
		$settings = get_option( 'edr_settings', array() );
		$settings['location'] = 'COUNTRY;STATE';
		update_option( 'edr_settings', $settings );

		$actual_location = edr_get_location();
		$this->assertEquals( array( 'COUNTRY', 'STATE' ), $actual_location );

		$actual_country = edr_get_location( 'country' );
		$this->assertEquals( 'COUNTRY', $actual_country );

		$actual_state = edr_get_location( 'state' );
		$this->assertEquals( 'STATE', $actual_state );

		$settings['location'] = 'COUNTRY';
		update_option( 'edr_settings', $settings );
		$actual_country = edr_get_location();
		$this->assertEquals( array( 'COUNTRY', '' ), $actual_country );
	}

	public function test_get_payment() {
		$payment = edr_get_payment();
		$this->assertInstanceOf( 'Edr_Payment', $payment );

		$payment_factory = new Edr_Payment_Factory();
		$payment_id = $payment_factory->create();
		$actual_payment = edr_get_payment( $payment_id );
		$this->assertEquals( $payment_id, $actual_payment->ID );
	}

	public function test_get_payment_statuses() {
		$expected_statuses = array( 'pending', 'complete', 'failed', 'cancelled' );
		$actual_statuses = array_keys( edr_get_payment_statuses() );
		$this->assertEquals( $expected_statuses, $actual_statuses );
	}

	public function test_get_payment_types() {
		$expected_types = array( 'course', 'membership' );
		$actual_types = array_keys( edr_get_payment_types() );
		$this->assertEquals( $expected_types, $actual_types );
	}

	public function test_get_entry() {
		$entry = edr_get_entry();
		$this->assertInstanceOf( 'Edr_Entry', $entry );

		$entry_factory = new Edr_Entry_Factory();
		$entry_id = $entry_factory->create();
		$actual_entry = edr_get_entry( $entry_id );
		$this->assertEquals( $entry_id, $actual_entry->ID );
	}

	public function test_get_entry_statuses() {
		$expected_statuses = array( 'pending', 'inprogress', 'complete',
			'cancelled', 'paused' );
		$actual_statuses = array_keys( edr_get_entry_statuses() );
		$this->assertEquals( $expected_statuses, $actual_statuses );
	}

	public function test_get_entry_origins() {
		$expected_origins = array( 'payment', 'membership' );
		$actual_origins = array_keys( edr_get_entry_origins() );
		$this->assertEquals( $expected_origins, $actual_origins );
	}

	public function test_get_question() {
		$question = edr_get_question();
		$this->assertInstanceOf( 'Edr_Question', $question );

		$question_factory = new Edr_Question_Factory();
		$question_id = $question_factory->create();
		$actual_question = edr_get_question( $question_id );
		$this->assertEquals( $question_id, $actual_question->ID );
	}

	public function test_get_private_uploads_dir() {
		$upload_dir = wp_upload_dir();
		$expected_dir = $upload_dir['basedir'] . '/edr';
		$actual_dir = edr_get_private_uploads_dir();
		$this->assertEquals( $expected_dir, $actual_dir );

		$private_uploads_dir_filter = function( $dir ) {
			return '/my/private/uploads/dir';
		};
		add_filter( 'edr_private_uploads_dir', $private_uploads_dir_filter );
		$expected_dir = '/my/private/uploads/dir';
		$actual_dir = edr_get_private_uploads_dir();
		$this->assertEquals( $expected_dir, $actual_dir );
		remove_filter( 'edr_private_uploads_dir', $private_uploads_dir_filter );
	}

	public function test_protect_htaccess_exists() {
		$uploads_dir = edr_get_private_uploads_dir();
		$htaccess_path = trailingslashit( $uploads_dir );
		if ( file_exists( $htaccess_path . '.htaccess' ) ) unlink( $htaccess_path . '.htaccess' );
		$actual_result = edr_protect_htaccess_exists();
		$this->assertFalse( $actual_result );

		$uploads_dir = edr_get_private_uploads_dir();
		$htaccess_path = trailingslashit( $uploads_dir );
		if ( ! is_dir( $htaccess_path ) ) mkdir( $htaccess_path );
		file_put_contents( $htaccess_path . '.htaccess', '' );
		$actual_result = edr_protect_htaccess_exists();
		$this->assertTrue( $actual_result );
	}
}
