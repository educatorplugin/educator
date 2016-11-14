<?php
class MembershipsClassTest extends WP_UnitTestCase {
	protected $ms;

	public function setUp() {
		parent::setUp();
		$this->ms = Edr_Memberships::get_instance();
	}

	public function test_get_periods() {
		$expected = array( 'onetime', 'days', 'months', 'years' );
		$actual = array_keys( $this->ms->get_periods() );
		$this->assertSame( $expected, $actual );
	}

	public function test_get_statuses() {
		$expected = array( 'expired', 'active' );
		$actual = array_keys( $this->ms->get_statuses() );
		$this->assertSame( $expected, $actual );
	}

	public function test_get_memberships() {
		$m1_id = $this->factory->post->create( array(
			'post_type'  => EDR_PT_MEMBERSHIP,
			'menu_order' => 2,
		) );
		$m2_id = $this->factory->post->create( array(
			'post_type'  => EDR_PT_MEMBERSHIP,
			'menu_order' => 1,
		) );

		$memberships = $this->ms->get_memberships();
		$this->assertSame(
			array( $m2_id, $m1_id ),
			array( $memberships[0]->ID, $memberships[1]->ID )
		);
	}

	public function test_get_membership() {
		$m_id = $this->factory->post->create( array( 'post_type' => EDR_PT_MEMBERSHIP ) );
		$membership = $this->ms->get_membership( $m_id );
		$this->assertEquals( get_post( $m_id ), $membership );
	}

	public function test_get_members() {
		$m1_id = $this->factory->post->create( array( 'post_type' => EDR_PT_MEMBERSHIP ) );
		$member1_id = $this->factory->user->create( array( 'role' => 'student' ) );
		$this->ms->update_user_membership( array(
			'user_id'       => $member1_id,
			'membership_id' => $m1_id,
			'status'        => 'active',
			'expiration'    => date( 'Y-m-d H:i:s' ),
		) );
		$members = $this->ms->get_members( array( 'role' => 'student' ) );
		$this->assertEquals( array( get_user_by( 'id', $member1_id ) ), $members->results );
	}

	public function test_get_price() {
		$m_id = $this->factory->post->create( array( 'post_type' => EDR_PT_MEMBERSHIP ) );
		$expected_price = 987.65;
		update_post_meta( $m_id, '_edr_price', $expected_price );
		$this->assertSame( $expected_price, $this->ms->get_price( $m_id ) );
	}

	public function test_get_period() {
		$m_id = $this->factory->post->create( array( 'post_type' => EDR_PT_MEMBERSHIP ) );
		$expected_period = 'months';
		update_post_meta( $m_id, '_edr_period', $expected_period );
		$this->assertSame( $expected_period, $this->ms->get_period( $m_id ) );
	}

	public function test_get_duration() {
		$m_id = $this->factory->post->create( array( 'post_type' => EDR_PT_MEMBERSHIP ) );
		$expected_duration = 12;
		update_post_meta( $m_id, '_edr_duration', $expected_duration );
		$this->assertSame( $expected_duration, $this->ms->get_duration( $m_id ) );
	}

	public function test_get_categories() {
		$m_id = $this->factory->post->create( array( 'post_type' => EDR_PT_MEMBERSHIP ) );
		$expected_categories = array( 1, 2, 3 );
		update_post_meta( $m_id, '_edr_categories', $expected_categories );
		$this->assertSame( $expected_categories, $this->ms->get_categories( $m_id ) );
		$this->assertSame( array(), $this->ms->get_categories( 123 ) );
	}

	public function test_calculate_expiration_date() {
		$tomorrow = strtotime( '+ 1 days', strtotime( date( 'Y-m-d 23:59:59' ) ) );

		// Days.
		$expiration = $this->ms->calculate_expiration_date( 53, 'days', strtotime( '2014-01-01 23:59:59' ) );
		$this->assertEquals( '2014-02-23 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		$expiration = $this->ms->calculate_expiration_date( 1, 'days' );
		$this->assertEquals( date( 'Y-m-d H:i:s', strtotime( '+ 1 days', time() ) ), date( 'Y-m-d H:i:s', $expiration ) );

		// 3 Years.
		$expiration = $this->ms->calculate_expiration_date( 3, 'years', strtotime( '2014-09-18' ) );
		$this->assertEquals( '2017-09-18 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// 9 Years.
		$expiration = $this->ms->calculate_expiration_date( 9, 'years', strtotime( '2014-09-18' ) );
		$this->assertEquals( '2023-09-18 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// 19 Years.
		$expiration = $this->ms->calculate_expiration_date( 19, 'years', strtotime( '2014-12-18' ) );
		$this->assertEquals( '2033-12-18 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// Leap year.
		$expiration = $this->ms->calculate_expiration_date( 2, 'years', strtotime( '2016-02-29 14:30:24' ) );
		$this->assertEquals( '2018-02-28 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		$expiration = $this->ms->calculate_expiration_date( 1, 'years', strtotime( '2015-02-28' ) );
		$this->assertEquals( '2016-02-29 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// 7 Months.
		$expiration = $this->ms->calculate_expiration_date( 7, 'months', strtotime( '2014-01-01' ) );
		$this->assertEquals( '2014-08-01 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// 6 Months.
		$expiration = $this->ms->calculate_expiration_date( 6, 'months', strtotime( '2014-08-31' ) );
		$this->assertEquals( '2015-02-28 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// February.
		$expiration = $this->ms->calculate_expiration_date( 1, 'months', strtotime( '2015-01-31' ) );
		$this->assertEquals( '2015-02-28 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// Leap year.
		$expiration = $this->ms->calculate_expiration_date( 1, 'months', strtotime( '2016-01-31' ) );
		$this->assertEquals( '2016-02-29 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// Test the last day of the month rule.
		$expiration = $this->ms->calculate_expiration_date( 1, 'months', strtotime( '2015-02-28 22:45:23' ) );
		$this->assertEquals( '2015-03-31 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		$expiration = $this->ms->calculate_expiration_date( 1, 'months', strtotime( '2015-03-31' ) );
		$this->assertEquals( '2015-04-30 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		$expiration = $this->ms->calculate_expiration_date( 1, 'months', strtotime( '2017-01-31 12:00:01' ) );
		$this->assertEquals( '2017-02-28 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		$expiration = $this->ms->calculate_expiration_date( 1, 'months', strtotime( '2016-01-31' ) );
		$this->assertEquals( '2016-02-29 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );
	}

	public function test_modify_expiration_date() {
		// -1 Day.
		$expiration = $this->ms->modify_expiration_date( 1, 'days', '-', strtotime( '2016-01-01 23:59:59' ) );
		$this->assertEquals( '2015-12-31 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// -3 Days.
		$expiration = $this->ms->modify_expiration_date( 3, 'days', '-', strtotime( '2016-03-01 23:59:59' ) );
		$this->assertEquals( '2016-02-27 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// -30 Days.
		$expiration = $this->ms->modify_expiration_date( 30, 'days', '-', strtotime( '2016-02-29 23:59:59' ) );
		$this->assertEquals( '2016-01-30 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// -1 Month.
		$expiration = $this->ms->modify_expiration_date( 1, 'months', '-', strtotime( '2016-03-31 23:59:59' ) );
		$this->assertEquals( '2016-02-29 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// -3 Months.
		$expiration = $this->ms->modify_expiration_date( 3, 'months', '-', strtotime( '2015-04-05 23:59:59' ) );
		$this->assertEquals( '2015-01-05 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// -6 Months.
		$expiration = $this->ms->modify_expiration_date( 6, 'months', '-', strtotime( '2016-03-31 23:59:59' ) );
		$this->assertEquals( '2015-09-30 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// -1 Year.
		$expiration = $this->ms->modify_expiration_date( 1, 'years', '-', strtotime( '2016-08-25 23:59:59' ) );
		$this->assertEquals( '2015-08-25 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// -2 Years (from common year to leap year).
		$expiration = $this->ms->modify_expiration_date( 2, 'years', '-', strtotime( '2018-02-28 23:59:59' ) );
		$this->assertEquals( '2016-02-29 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );

		// -3 Years (from common year to common year).
		$expiration = $this->ms->modify_expiration_date( 3, 'years', '-', strtotime( '2018-02-28 23:59:59' ) );
		$this->assertEquals( '2015-02-28 23:59:59', date( 'Y-m-d H:i:s', $expiration ) );
	}

	public function test_get_user_membership_by() {
		$m1_id = $this->factory->post->create( array( 'post_type' => EDR_PT_MEMBERSHIP ) );
		$m2_id = $this->factory->post->create( array( 'post_type' => EDR_PT_MEMBERSHIP ) );
		$user1_id = $this->factory->user->create();
		$user2_id = $this->factory->user->create();
		$user1_membership = array(
			'ID'            => null,
			'user_id'       => $user1_id,
			'membership_id' => $m1_id,
			'status'        => 'active',
			'expiration'    => strtotime( '+ 1 month' ),
		);
		$user2_membership = array(
			'ID'            => null,
			'user_id'       => $user2_id,
			'membership_id' => $m2_id,
			'status'        => 'expired',
			'expiration'    => strtotime( '2016-01-31 23:59:59' ),
		);
		$member1_id = $this->ms->update_user_membership( $user1_membership );
		$member2_id = $this->ms->update_user_membership( $user2_membership );
		$user1_membership['ID'] = $member1_id;
		$user2_membership['ID'] = $member2_id;

		$u1_membership = $this->ms->get_user_membership_by( 'id', $member1_id );
		$this->assertSame( $user1_membership, $u1_membership );

		$u2_membership = $this->ms->get_user_membership_by( 'user_id', $user2_id );
		$this->assertSame( $user2_membership, $u2_membership );
	}

	public function test_update_user_membership() {
		$user_membership = array(
			'ID'            => null,
			'user_id'       => 123,
			'membership_id' => 456,
			'status'        => 'active',
			'expiration'    => strtotime( '+ 1 month' ),
		);

		$user_membership['ID'] = $this->ms->update_user_membership( $user_membership );
		$actual_user_membership = $this->ms->get_user_membership_by( 'id', $user_membership['ID'] );
		$this->assertSame( $user_membership, $actual_user_membership );

		$user_membership['status'] = 'expired';
		$user_membership['expiration'] = strtotime( '- 1 month' );
		$this->ms->update_user_membership( $user_membership );
		$actual_user_membership = $this->ms->get_user_membership_by( 'id', $user_membership['ID'] );
		$this->assertSame( $user_membership, $actual_user_membership );
	}

	public function test_setup_membership() {
		$user_id = $this->factory->user->create();
		$entry_factory = new Edr_Entry_Factory();
		$entry_id = $entry_factory->create( array(
			'user_id'      => $user_id,
			'entry_origin' => 'membership',
			'entry_status' => 'inprogress',
		) );
		$m1_id = $this->factory->post->create( array( 'post_type' => EDR_PT_MEMBERSHIP ) );
		update_post_meta( $m1_id, '_edr_duration', 1 );
		update_post_meta( $m1_id, '_edr_period', 'months' );
		$m2_id = $this->factory->post->create( array( 'post_type' => EDR_PT_MEMBERSHIP ) );
		update_post_meta( $m2_id, '_edr_duration', 10 );
		update_post_meta( $m2_id, '_edr_period', 'days' );

		$member_id = $this->ms->setup_membership( $user_id, $m1_id );
		$expected_user_membership = array(
			'ID'            => $member_id,
			'user_id'       => $user_id,
			'membership_id' => $m1_id,
			'status'        => 'active',
			'expiration'    => $this->ms->calculate_expiration_date( 1, 'months' ),
		);
		$actual_user_membership = $this->ms->get_user_membership_by( 'id', $member_id );
		$this->assertSame( $expected_user_membership, $actual_user_membership );

		$this->ms->setup_membership( $user_id, $m2_id );
		$expected_user_membership['membership_id'] = $m2_id;
		$expected_user_membership['expiration'] = $this->ms->calculate_expiration_date( 10, 'days' );
		$actual_user_membership = $this->ms->get_user_membership_by( 'id', $member_id );
		$this->assertSame( $expected_user_membership, $actual_user_membership );
		$entry = edr_get_entry( $entry_id );
		$this->assertSame( 'paused', $entry->entry_status );
	}

	public function test_has_expired() {
		$m_id = $this->factory->post->create( array( 'post_type' => EDR_PT_MEMBERSHIP ) );
		update_post_meta( $m_id, '_edr_period', 'onetime' );

		$this->assertTrue( $this->ms->has_expired( null ) );
		$user_membership = array();
		$user_membership['membership_id'] = $m_id;
		$user_membership['status'] = 'expired';
		$this->assertTrue( $this->ms->has_expired( $user_membership ) );
		$user_membership['status'] = 'active';
		$this->assertFalse( $this->ms->has_expired( $user_membership ) );
		$user_membership['expiration'] = strtotime( '- 1 day' );
		update_post_meta( $m_id, '_edr_period', 'months' );
		$this->assertTrue( $this->ms->has_expired( $user_membership ) );
		$user_membership['expiration'] = strtotime( '+ 1 day' );
		$this->assertFalse( $this->ms->has_expired( $user_membership ) );
	}

	public function test_get_membership_post_ids() {
		$term_id = $this->factory->term->create( array( 'taxonomy' => EDR_TX_CATEGORY ) );
		$categories = array( $term_id );
		$membership_id = $this->factory->post->create( array( 'post_type' => EDR_PT_MEMBERSHIP ) );
		$course_id = $this->factory->post->create( array( 'post_type' => EDR_PT_COURSE ) );
		wp_set_post_terms( $course_id, $categories, EDR_TX_CATEGORY );
		update_post_meta( $membership_id, '_edr_categories', $categories );
		$post_ids = $this->ms->get_membership_post_ids( $membership_id );
		$this->assertEquals( array( $course_id ), $post_ids );
	}

	public function test_can_join_course() {
		$term_id = $this->factory->term->create( array( 'taxonomy' => EDR_TX_CATEGORY ) );
		$categories = array( $term_id );
		$membership_id = $this->factory->post->create( array( 'post_type' => EDR_PT_MEMBERSHIP ) );
		$course_id = $this->factory->post->create( array( 'post_type' => EDR_PT_COURSE ) );
		wp_set_post_terms( $course_id, $categories, EDR_TX_CATEGORY );
		update_post_meta( $membership_id, '_edr_categories', $categories );
		update_post_meta( $membership_id, '_edr_period', 'months' );
		$user_id = $this->factory->user->create();

		$can_join_course = $this->ms->can_join_course( $course_id, $user_id );
		$this->assertFalse( $can_join_course );

		$user_membership = array(
			'ID'            => null,
			'user_id'       => $user_id,
			'membership_id' => $membership_id,
			'status'        => 'active',
			'expiration'    => strtotime( '+ 1 month' ),
		);
		$user_membership['ID'] = $this->ms->update_user_membership( $user_membership );
		$can_join_course = $this->ms->can_join_course( $course_id, $user_id );
		$this->assertTrue( $can_join_course );
	}

	public function test_update_membership_entries() {
		$user_id = $this->factory->user->create();
		$entry_factory = new Edr_Entry_Factory();
		$entry_id = $entry_factory->create( array(
			'user_id'      => $user_id,
			'entry_origin' => 'membership',
			'entry_status' => 'inprogress',
		) );

		$this->ms->update_membership_entries( $user_id, 'paused' );
		$entry = edr_get_entry( $entry_id );
		$this->assertEquals( 'paused', $entry->entry_status );
	}
}
