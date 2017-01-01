<?php

class QuizGradesTableClassTest extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		$admin_user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );

		wp_set_current_user( $admin_user_id );

		$GLOBALS['hook_suffix'] = 'educator_page_edr_admin_quiz_grades';
	}

	public function test_get_filters_input() {
		// filters_input is set when the class is constructed.
		$_GET['status'] = 'inprogress';
		$_GET['post'] = 1;
		$gradesTable = new Edr_Admin_QuizGradesTable();
		$actual_input = $gradesTable->get_filters_input();
		$expected_input = array(
			'status' => 'inprogress',
			'post' => 1,
		);
		$this->assertEquals( $expected_input, $actual_input );
	}

	public function test_get_quiz_posts() {
		$user_id = get_current_user_id();
		$post_id_1 = $this->factory->post->create( array(
			'post_type'   => EDR_PT_LESSON,
			'post_status' => 'publish',
			'post_author' => $user_id,
			'meta_input'  => array(
				'_edr_quiz' => 1
			),
		) );
		$post_id_2 = $this->factory->post->create( array(
			'post_type'   => 'post',
			'post_status' => 'publish',
			'post_author' => $user_id,
			'meta_input'  => array(
				'_edr_quiz' => 1
			),
		) );

		// This post should not be included.
		$this->factory->post->create( array(
			'post_type'   => 'post',
			'post_status' => 'publish',
			'post_author' => 123456789,
			'meta_input'  => array(
				'_edr_quiz' => 1
			),
		) );

		$gradesTable = new Edr_Admin_QuizGradesTable();
		$actual_posts = $gradesTable->get_quiz_posts( $user_id );
		$actual_posts = array_map( function( $post ) { return $post->ID; }, $actual_posts );
		$expected_posts = array( $post_id_2, $post_id_1 );
		$this->assertEquals( $expected_posts, $actual_posts );
	}

	public function test_get_permission_to_edit() {
		$get_permission_to_edit = new ReflectionMethod( 'Edr_Admin_QuizGradesTable', 'get_permission_to_edit' );
		$get_permission_to_edit->setAccessible(true);
		$gradesTable = new Edr_Admin_QuizGradesTable();

		// Administrators.
		$permission = $get_permission_to_edit->invoke( $gradesTable );
		$this->assertSame( 'edit_all', $permission );

		// Lecturers.
		$lecturer_user_id = $this->factory->user->create( array( 'role' => 'lecturer' ) );
		wp_set_current_user( $lecturer_user_id );
		$permission = $get_permission_to_edit->invoke( $gradesTable );
		$this->assertSame( 'edit_own', $permission );

		// Other users.
		$other_user_id = $this->factory->user->create();
		wp_set_current_user( $other_user_id );
		$permission = $get_permission_to_edit->invoke( $gradesTable );
		$this->assertSame( '', $permission );
	}

	public function test_get_filter_lesson_id() {
		$lecturer_user_id = $this->factory->user->create( array( 'role' => 'lecturer' ) );
		$post_id_1 = $this->factory->post->create( array(
			'post_type'   => EDR_PT_LESSON,
			'post_status' => 'publish',
			'post_author' => $lecturer_user_id,
			'meta_input'  => array(
				'_edr_quiz' => 1
			),
		) );
		$post_id_2 = $this->factory->post->create( array(
			'post_type'   => 'post',
			'post_status' => 'publish',
			'post_author' => $lecturer_user_id,
			'meta_input'  => array(
				'_edr_quiz' => 1
			),
		) );
		$post_id_3 = $this->factory->post->create( array(
			'post_type'   => 'post',
			'post_status' => 'publish',
			'post_author' => 123456789,
			'meta_input'  => array(
				'_edr_quiz' => 1
			),
		) );

		$get_permission_to_edit = new ReflectionMethod( 'Edr_Admin_QuizGradesTable', 'get_permission_to_edit' );
		$get_permission_to_edit->setAccessible( true );
		$get_filter_lesson_id = new ReflectionMethod( 'Edr_Admin_QuizGradesTable', 'get_filter_lesson_id' );
		$get_filter_lesson_id->setAccessible( true );

		$gradesTable = new Edr_Admin_QuizGradesTable();

		// Administrators.
		$gradesTable->set_filters_input( array( 'post' => $post_id_3 ) );
		$permission = $get_permission_to_edit->invoke( $gradesTable );
		$actual_lesson_id = $get_filter_lesson_id->invoke( $gradesTable, $permission );
		$this->assertSame( $post_id_3, $actual_lesson_id );

		// Lecturers.
		wp_set_current_user( $lecturer_user_id );

		$gradesTable->set_filters_input( array( 'post' => $post_id_3 ) );
		$permission = $get_permission_to_edit->invoke( $gradesTable );
		$actual_lesson_id = $get_filter_lesson_id->invoke( $gradesTable, $permission );
		$this->assertSame( array( $post_id_2, $post_id_1 ), $actual_lesson_id );

		$gradesTable->set_filters_input( array( 'post' => $post_id_1 ) );
		$actual_lesson_id = $get_filter_lesson_id->invoke( $gradesTable, $permission );
		$this->assertSame( $post_id_1, $actual_lesson_id );

		// Other users.
		$other_user_id = $this->factory->user->create();
		wp_set_current_user( $other_user_id );

		$gradesTable->set_filters_input( array( 'post' => 0 ) );
		$permission = $get_permission_to_edit->invoke( $gradesTable );
		$actual_lesson_id = $get_filter_lesson_id->invoke( $gradesTable, $permission );
		$this->assertSame( 0, $actual_lesson_id );

		$gradesTable->set_filters_input( array( 'post' => $post_id_1 ) );
		$actual_lesson_id = $get_filter_lesson_id->invoke( $gradesTable, $permission );
		$this->assertSame( 0, $actual_lesson_id );
	}
}
