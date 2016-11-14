<?php

class PostTypesClassTest extends WP_UnitTestCase {
	public function test_protect_content() {
		$course_id = $this->factory->post->create( array(
			'post_type' => EDR_PT_COURSE
		) );
		$lesson_id = $this->factory->post->create( array(
			'post_type'    => EDR_PT_LESSON,
			'post_content' => 'Restricted content',
		) );
		update_post_meta( $lesson_id, '_edr_course_id', $course_id );

		$user_id = $this->factory->user->create( array( 'role' => 'student' ) );
		wp_set_current_user( $user_id );

		// No access, not the single lesson page.
		$this->go_to( get_post_type_archive_link( EDR_PT_LESSON ) );
		$actual_content = 'xx';
		while ( have_posts() ) {
			the_post();
			ob_start();
			the_content();
			$actual_content = ob_get_clean();
		}
		$this->assertEquals( '', $actual_content );

		// No access, single lesson page.
		$this->go_to( get_permalink( $lesson_id ) );
		$actual_content = 'xx';
		while ( have_posts() ) {
			the_post();
			ob_start();
			the_content();
			$actual_content = ob_get_clean();
		}
		$this->assertEquals( 0, preg_match( '/Restricted content/', $actual_content ) );
		$this->assertEquals( 1, preg_match( '/<div class="edr-no-access-notice">/', $actual_content ) );

		// Has access.
		$entry_factory = new Edr_Entry_Factory();
		$entry_id = $entry_factory->create( array(
			'course_id'    => $course_id,
			'entry_status' => 'inprogress',
			'user_id'      => $user_id,
		) );
		$actual_content = 'xx';
		while ( have_posts() ) {
			the_post();
			ob_start();
			the_content();
			$actual_content = ob_get_clean();
		}
		$this->assertEquals( 1, preg_match( '/Restricted content/', $actual_content ) );
	}

	public function test_lesson_content_hidden_in_feed() {
		$lesson_id = $this->factory->post->create( array(
			'post_type'    => EDR_PT_LESSON,
			'post_name'    => 'hide-lesson-content-in-feed',
			'post_content' => 'Restricted content',
		) );

		$lessons_feed_link = get_post_type_archive_feed_link( EDR_PT_LESSON );
		$this->go_to( $lessons_feed_link );

		$this->assertTrue( have_posts() );

		while ( have_posts() ) {
			the_post();

			// Check rss2 feed.
			$actual_content = get_the_content_feed( 'rss2' );
			$this->assertEquals( '', $actual_content );

			// Check atom feed.
			$actual_content = get_the_content_feed( 'atom' );
			$this->assertEquals( '', $actual_content );

			// Check rss feed.
			$actual_content = get_the_content_feed( 'rss' );
			$this->assertEquals( '', $actual_content );

			// Check rdf feed.
			$actual_content = get_the_content_feed( 'rdf' );
			$this->assertEquals( '', $actual_content );
		}
	}

	public function test_use_only_excerpt_in_feed() {
		$lesson_id = $this->factory->post->create( array( 'post_type' => EDR_PT_LESSON ) );
		$this->go_to( get_permalink( $lesson_id ) );
		update_option( 'rss_use_excerpt', 0 );
		$this->assertEquals( 1, get_option( 'rss_use_excerpt' ) );
	}

	public function test_hide_comments_in_feed() {
		global $comment;
		$user_id = $this->factory->user->create( array( 'role' => 'student' ) );
		$course_id = $this->factory->post->create( array( 'post_type' => EDR_PT_COURSE ) );
		$lesson_id = $this->factory->post->create( array(
			'post_type' => EDR_PT_LESSON,
			'post_name' => 'lesson-with-comments',
			'meta_input' => array( '_edr_course_id' => $course_id )
		) );
		$lesson_comment_id = $this->factory->comment->create( array( 'comment_post_ID' => $lesson_id ) );
		$post_id = $this->factory->post->create();
		$post_comment_id = $this->factory->comment->create( array( 'comment_post_ID' => $post_id ) );

		// Single lesson comments feed, no access.
		$lesson_comments_feed_link = '?lesson=lesson-with-comments&feed=rss2';
		$this->go_to( $lesson_comments_feed_link );
		$this->assertFalse( have_comments() );

		// Single lesson comments feed, has access.
		$entry_factory = new Edr_Entry_Factory();
		$entry_id = $entry_factory->create( array(
			'course_id'    => $course_id,
			'entry_status' => 'inprogress',
			'user_id'      => $user_id,
		) );
		wp_set_current_user( $user_id );
		$this->go_to( $lesson_comments_feed_link );
		$this->assertTrue( have_comments() );

		// General comments feed query.
		$comments_feed_link = get_search_comments_feed_link( '', 'rss2' );
		$this->go_to( $comments_feed_link );
		$num_comments = 0;
		$no_lesson_comments = true;
		while ( have_comments() ) {
			the_comment();
			if ( $comment->comment_post_ID == $lesson_id ) {
				$no_lesson_comments = false;
			}
			$num_comments += 1;
		}
		$this->assertEquals( 1, $num_comments );
		$this->assertTrue( $no_lesson_comments );
	}

	public function test_protect_comments() {
		$lesson_id = $this->factory->post->create( array( 'post_type' => EDR_PT_LESSON ) );
		$lesson_comment_id = $this->factory->comment->create( array( 'comment_post_ID' => $lesson_id ) );
		$comments = get_comments( array(
			'status'      => 'approve',
			'post_status' => 'publish',
		) );
		$this->assertEquals( 0, count( $comments ) );
	}

	public function test_protect_comments_template() {
		$user_id = $this->factory->user->create( array(
			'role' => 'student',
		) );
		$course_id = $this->factory->post->create( array(
			'post_type' => EDR_PT_COURSE,
		) );
		$lesson_id = $this->factory->post->create( array(
			'post_type'  => EDR_PT_LESSON,
			'meta_input' => array( '_edr_course_id' => $course_id ),
		) );
		$comment_id = $this->factory->comment->create( array(
			'comment_post_ID' => $lesson_id,
			'comment_content' => '1',
		) );

		$this->go_to( get_permalink( $lesson_id ) );

		// Current user doesn't have access to view lesson comments.
		$html = get_echo( 'comments_template' );
		$this->assertEquals( '', $html );

		// Current user has access.
		$entry_factory = new Edr_Entry_Factory();
		$entry_id = $entry_factory->create( array(
			'course_id'    => $course_id,
			'entry_status' => 'inprogress',
			'user_id'      => $user_id,
		) );
		wp_set_current_user( $user_id );
		$html = get_echo( 'comments_template' );
		$comments = preg_match_all( '/id="comment-([0-9]+)"/', $html, $matches );
		$found_cids = array_map( 'intval', $matches[1] );
		$this->assertSame( array( $comment_id ), $found_cids );
	}
}
