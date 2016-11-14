<?php

class AccessClassTest extends WP_UnitTestCase {
	protected $edr_access;

	public function setUp() {
		parent::setUp();

		$this->edr_access = Edr_Access::get_instance();
	}

	public function test_get_lesson_access() {
		$post_id = $this->factory->post->create( array( 'post_type' => EDR_PT_LESSON ) );
		update_post_meta( $post_id, '_edr_access', 'logged_in' );
		$actual_access_status = $this->edr_access->get_lesson_access( $post_id );
		$this->assertEquals( 'logged_in', $actual_access_status );
	}

	public function test_get_course_access_status() {
		$user_id = $this->factory->user->create( array( 'role' => 'student' ) );
		$course_id = $this->factory->post->create( array( 'post_type' => EDR_PT_COURSE ) );
		$entry_factory = new Edr_Entry_Factory();
		$entry_id = $entry_factory->create( array(
			'course_id'    => $course_id,
			'entry_status' => 'inprogress',
			'user_id'      => $user_id,
		) );

		// Anonymous user.
		$actual_course_access_status = $this->edr_access->get_course_access_status( $course_id, 0 );
		$this->assertEquals( '', $actual_course_access_status );

		// User logged in and entry in progress.
		$actual_course_access_status = $this->edr_access->get_course_access_status( $course_id, $user_id );
		$this->assertEquals( 'inprogress', $actual_course_access_status );

		// Course does not exist.
		$actual_course_access_status = $this->edr_access->get_course_access_status( 123456789, $user_id );
		$this->assertEquals( '', $actual_course_access_status );
	}

	public function test_can_study_lesson() {
		$user_id = $this->factory->user->create( array( 'role' => 'student' ) );
		$course_id = $this->factory->post->create( array( 'post_type' => EDR_PT_COURSE ) );
		$lesson_id = $this->factory->post->create( array( 'post_type' => EDR_PT_LESSON ) );
		$lesson2_id = $this->factory->post->create( array( 'post_type' => EDR_PT_LESSON ) );
		update_post_meta( $lesson_id, '_edr_course_id', $course_id );
		update_post_meta( $lesson2_id, '_edr_access', 'public' );
		$entry_factory = new Edr_Entry_Factory();
		$entry_id = $entry_factory->create( array(
			'course_id' => $course_id,
			'entry_status' => 'pending',
			'user_id' => $user_id,
		) );

		// Anonymous user.
		$can_study_lesson = $this->edr_access->can_study_lesson( $lesson_id );
		$this->assertFalse( $can_study_lesson );

		// Anonymous user, lesson access is "public".
		$can_study_lesson = $this->edr_access->can_study_lesson( $lesson2_id );
		$this->assertTrue( $can_study_lesson );

		// Logged in user, entry status is pending.
		wp_set_current_user( $user_id );
		$can_study_lesson = $this->edr_access->can_study_lesson( $lesson_id );
		$this->assertFalse( $can_study_lesson );

		// Logged in user, entry status is inprogress.
		$entry = edr_get_entry( $entry_id );
		$entry->entry_status = 'inprogress';
		$entry->save();
		wp_cache_delete( $user_id, 'edr_courses_access' );
		$can_study_lesson = $this->edr_access->can_study_lesson( $lesson_id );
		$this->assertTrue( $can_study_lesson );

		// User can study because, he/she is logged in and lesson access is "logged_in".
		update_post_meta( $lesson2_id, '_edr_access', 'logged_in' );
		$can_study_lesson = $this->edr_access->can_study_lesson( $lesson2_id );
		$this->assertTrue( $can_study_lesson );
	}

	public function test_can_edit_lesson() {
		$admin_user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		$lecturer_user_id = $this->factory->user->create( array( 'role' => 'lecturer' ) );
		$lesson_id = $this->factory->post->create( array( 'post_type' => EDR_PT_LESSON ) );

		wp_set_current_user( $admin_user_id );

		// Admin user can edit any lesson.
		$can_edit_lesson = $this->edr_access->can_edit_lesson( $lesson_id );
		$this->assertTrue( $can_edit_lesson );

		wp_set_current_user( $lecturer_user_id );

		// Basic user cannot.
		$can_edit_lesson = $this->edr_access->can_edit_lesson( $lesson_id );
		$this->assertFalse( $can_edit_lesson );

		// Course author can.
		$course_id = $this->factory->post->create( array(
			'post_type'   => EDR_PT_COURSE,
			'post_author' => $lecturer_user_id
		) );
		update_post_meta( $lesson_id, '_edr_course_id', $course_id );
		$can_edit_lesson = $this->edr_access->can_edit_lesson( $lesson_id );
		$this->assertTrue( $can_edit_lesson );
	}
}
