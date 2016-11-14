<?php

class MembershipsRunClassTest extends WP_UnitTestCase {
	protected $ms;

	public function setUp() {
		parent::setUp();
		$this->ms = Edr_Memberships::get_instance();
	}

	public function test_check_user_membership() {
		$user_id = $this->factory->user->create();
		$userm = array(
			'user_id'    => $user_id,
			'status'     => 'active',
			'expiration' => strtotime( '- 1 minute' ),
		);
		$userm['ID'] = $this->ms->update_user_membership( $userm );
		$entry_factory = new Edr_Entry_Factory();
		$entry_id = $entry_factory->create( array(
			'user_id'      => $user_id,
			'entry_origin' => 'membership',
			'entry_status' => 'inprogress',
		) );

		wp_set_current_user( $user_id );

		Edr_MembershipsRun::check_user_membership();

		$actual_userm = $this->ms->get_user_membership_by( 'id', $userm['ID'] );
		$actual_entry = edr_get_entry( $entry_id );
		$this->assertEquals( 'expired', $actual_userm['status'] );
		$this->assertEquals( 'paused', $actual_entry->entry_status );
	}

	public function test_process_expired_memberships() {
		$user1_id = 1;
		$user2_id = 2;
		$user1m = array(
			'user_id' => $user1_id,
			'status'  => 'active',
			'expiration' => strtotime( '- 1 minute' ),
		);
		$user2m = array(
			'user_id' => $user2_id,
			'status'  => 'active',
			'expiration' => strtotime( '- 1 second' ),
		);
		$user1m['ID'] = $this->ms->update_user_membership( $user1m );
		$user2m['ID'] = $this->ms->update_user_membership( $user2m );
		$entry_factory = new Edr_Entry_Factory();
		$entry1_id = $entry_factory->create( array(
			'user_id'      => $user1_id,
			'entry_origin' => 'membership',
			'entry_status' => 'inprogress',
		) );
		$entry2_id = $entry_factory->create( array(
			'user_id'      => $user2_id,
			'entry_origin' => 'membership',
			'entry_status' => 'inprogress',
		) );

		Edr_MembershipsRun::process_expired_memberships();

		$actual_user1m = $this->ms->get_user_membership_by( 'id', $user1m['ID'] );
		$actual_user2m = $this->ms->get_user_membership_by( 'id', $user2m['ID'] );
		$actual_entry1 = edr_get_entry( $entry1_id );
		$actual_entry2 = edr_get_entry( $entry2_id );
		$this->assertEquals( 'expired', $actual_user1m['status'] );
		$this->assertEquals( 'expired', $actual_user2m['status'] );
		$this->assertEquals( 'paused', $actual_entry1->entry_status );
		$this->assertEquals( 'paused', $actual_entry2->entry_status );
	}

	public function test_send_expiration_notifications() {
		$user_id = $this->factory->user->create( array(
			'user_login' => 'student_user_login',
			'user_email' => 'student@educatorplugin.com',
		) );
		$membership_id = $this->factory->post->create( array(
			'post_type'  => EDR_PT_MEMBERSHIP,
			'post_title' => 'membership_title',
		) );
		update_post_meta( $membership_id, '_edr_price', 100 );
		update_post_meta( $membership_id, '_edr_duration', 1 );
		update_post_meta( $membership_id, '_edr_period', 'months' );
		update_post_meta( $membership_id, '_edr_categories', array() );

		$in5days = strtotime( '+ 5 days', strtotime( date( 'Y-m-d 23:59:59' ) ) );
		$um = array(
			'user_id'       => $user_id,
			'status'        => 'active',
			'membership_id' => $membership_id,
			'expiration'    => $in5days,
		);
		$um['ID'] = $this->ms->update_user_membership( $um );
		$settings = get_option( 'edr_settings' );
		$settings['payment_page'] = $this->factory->post->create( array(
			'post_type' => 'page',
			'post_slug' => 'payment',
		) );
		update_option( 'edr_settings', $settings );

		$assert = function( $args ) use ( $membership_id ) {
			$date_format = get_option( 'date_format' );
			$expires_date = date( $date_format, strtotime( '+ 5 days', strtotime( date( 'Y-m-d 23:59:59' ) ) ) );
			$payment_url = edr_get_endpoint_url( 'edr-object', $membership_id, get_permalink( edr_get_page_id( 'payment' ) ) );
			$message = <<<MESSAGE
Dear student_user_login,

Your membership_title membership expires on $expires_date.

Please renew your membership: $payment_url

Log in: http://example.org/wp-login.php

Best regards,
Administration
MESSAGE;
			$this->assertEquals( array(
				'to'      => 'student@educatorplugin.com',
				'subject' => 'Your membership expires',
				'message' => $message,
			), array(
				'to'      => $args['to'][0],
				'subject' => $args['subject'],
				'message' => $args['message'],
			) );

			return array(
				'to'          => array(),
				'subject'     => '',
				'message'     => '',
				'headers'     => '',
				'attachments' => array(),
			);
		};
		add_filter( 'wp_mail', $assert );

		$_SERVER['SERVER_NAME'] = 'localhost';
		$emails_sent = Edr_MembershipsRun::send_expiration_notifications();

		$this->assertSame( 1, $emails_sent );
	}

	public function test_on_deleted_user() {
		global $wpdb;
		$tables = edr_db_tables();
		$user_id = $this->factory->user->create();
		$um = array(
			'user_id' => $user_id,
		);
		$this->ms->update_user_membership( $um );

		wp_delete_user( $user_id );

		$member_id = $wpdb->get_var( $wpdb->prepare(
			"
			SELECT ID
			FROM {$tables['members']}
			WHERE user_id = %d
			",
			$user_id
		) );

		$this->assertNull( $member_id );
	}
}
