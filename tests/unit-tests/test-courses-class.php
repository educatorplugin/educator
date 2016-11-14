<?php

class CoursesClassTest extends WP_UnitTestCase {
	protected $edr_courses;

	public function setUp() {
		parent::setUp();

		$this->edr_courses = Edr_Courses::get_instance();
	}

	public function test_get_course_id() {
		$lesson_id = $this->factory->post->create( array(
			'post_type' => EDR_PT_LESSON,
			'meta_input' => array(
				'_edr_course_id' => 123456789,
			),
		) );
		$course_id = $this->edr_courses->get_course_id( $lesson_id );
		$this->assertEquals( 123456789, $course_id );
	}

	public function test_get_course_price() {
		$course_id = $this->factory->post->create( array(
			'post_type' => EDR_PT_COURSE,
			'meta_input' => array(
				'_edr_price' => 129.68,
			),
		) );
		$price = $this->edr_courses->get_course_price( $course_id );
		$this->assertEquals( 129.68, $price );
	}

	public function test_get_register_status() {
		$course_id = $this->factory->post->create( array(
			'post_type' => EDR_PT_COURSE,
			'meta_input' => array(
				'_edr_register' => 'open',
			),
		) );
		$register_status = $this->edr_courses->get_register_status( $course_id );
		$this->assertEquals( 'open', $register_status );
	}

	public function test_get_course_prerequisites() {
		$expected_prerequisites = array( 3, 1, 4, 2, 5 );
		$course_id = $this->factory->post->create( array(
			'post_type' => EDR_PT_COURSE,
			'meta_input' => array(
				'_edr_prerequisites' => $expected_prerequisites,
			),
		) );
		$prerequisites = $this->edr_courses->get_course_prerequisites( $course_id );
		$this->assertSame( $expected_prerequisites, $prerequisites );
	}

	public function test_check_course_prerequisites() {
		$user_id = $this->factory->user->create( array(
			'role' => 'student'
		) );
		$course_id = $this->factory->post->create( array(
			'post_type' => EDR_PT_COURSE
		) );
		$prerequisite_course_id = $this->factory->post->create( array(
			'post_type' => EDR_PT_COURSE
		) );

		wp_set_current_user( $user_id );

		$this->assertTrue( $this->edr_courses->check_course_prerequisites( $course_id, $user_id ) );

		update_post_meta( $course_id, '_edr_prerequisites', array( $prerequisite_course_id ) );

		$this->assertFalse( $this->edr_courses->check_course_prerequisites( $course_id, $user_id ) );

		$entry_factory = new Edr_Entry_Factory();
		$entry_id = $entry_factory->create( array(
			'course_id'    => $prerequisite_course_id,
			'entry_status' => 'complete',
			'user_id'      => $user_id,
		) );

		$this->assertTrue( $this->edr_courses->check_course_prerequisites( $course_id, $user_id ) );
	}

	public function test_get_lesson_access_status() {
		$lesson_id = $this->factory->post->create( array(
			'post_type' => EDR_PT_LESSON,
			'meta_input' => array(
				'_edr_access' => 'public',
			),
		) );
		$access_status = $this->edr_courses->get_lesson_access_status( $lesson_id );
		$this->assertEquals( 'public', $access_status );
	}

	public function test_get_adjacent_lesson() {
		$course_id = 123456789;
		$lesson1_id = $this->factory->post->create( array(
			'post_type'  => EDR_PT_LESSON,
			'menu_order' => 1,
			'meta_input' => array( '_edr_course_id' => $course_id ),
		) );
		$lesson2_id = $this->factory->post->create( array(
			'post_type'  => EDR_PT_LESSON,
			'menu_order' => 2,
			'meta_input' => array( '_edr_course_id' => $course_id ),
		) );

		$this->go_to( get_permalink( $lesson2_id ) );
		$adjacent_lesson = $this->edr_courses->get_adjacent_lesson();
		$this->assertEquals( $lesson1_id, $adjacent_lesson->ID );
	}

	public function test_get_lecturer_courses() {
		$user_id = 123456789;
		$course_id = $this->factory->post->create( array(
			'post_type'  => EDR_PT_COURSE,
			'post_author' => $user_id,
		) );
		$lecturer_courses = $this->edr_courses->get_lecturer_courses( $user_id );
		$this->assertSame( array( $course_id ), $lecturer_courses );
	}

	public function test_get_student_courses() {
		$user_id = 123456789;
		$course1_id = $this->factory->post->create( array( 'post_type'  => EDR_PT_COURSE ) );
		$course2_id = $this->factory->post->create( array( 'post_type'  => EDR_PT_COURSE ) );
		$entry_factory = new Edr_Entry_Factory();
		$entry1_id = $entry_factory->create( array(
			'course_id'    => $course1_id,
			'entry_status' => 'inprogress',
			'user_id'      => $user_id,
		) );
		$entry2_id = $entry_factory->create( array(
			'course_id'    => $course2_id,
			'entry_status' => 'complete',
			'user_id'      => $user_id,
		) );
		$student_courses = $this->edr_courses->get_student_courses( $user_id );
		$expected_courses = array();
		$expected_courses['entries'] = array(
			edr_get_entry( $entry1_id ),
			edr_get_entry( $entry2_id ),
		);
		$expected_courses['courses'] = array(
			$course1_id => get_post( $course1_id ),
			$course2_id => get_post( $course2_id ),
		);
		$expected_courses['statuses'] = array(
			'inprogress' => 1,
			'complete'   => 1,
		);
		$this->assertEquals( $expected_courses, $student_courses );
	}

	public function test_get_pending_courses() {
		$user_id = 123456789;
		$course1_id = $this->factory->post->create( array( 'post_type'  => EDR_PT_COURSE ) );
		$course2_id = $this->factory->post->create( array( 'post_type'  => EDR_PT_COURSE ) );
		$payments_factory = new Edr_Payment_Factory();
		$payment1_id = $payments_factory->create( array(
			'user_id'        => $user_id,
			'object_id'      => $course1_id,
			'payment_type'   => 'course',
			'payment_status' => 'pending',
		) );
		$payment2_id = $payments_factory->create( array(
			'user_id'        => $user_id,
			'object_id'      => $course2_id,
			'payment_type'   => 'course',
			'payment_status' => 'cancelled',
		) );
		$pending_courses = $this->edr_courses->get_pending_courses( $user_id );
		$course1 = get_post( $course1_id );
		$course1->edr_payment_id = $payment1_id;
		$course1->edr_payment = edr_get_payment( $payment1_id );
		$expected_courses = array(
			$course1_id => $course1,
		);
		$this->assertEquals( $expected_courses, $pending_courses );
	}
}
