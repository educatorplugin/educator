<?php

class Edr_Access {
	/**
	 * @var Edr_Access
	 */
	protected static $instance = null;

	/**
	 * @var string
	 */
	protected $payments;

	/**
	 * @var string
	 */
	protected $entries;

	/**
	 * Get the single instance of this class.
	 *
	 * @return Edr_Access
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$tables = edr_db_tables();
		$this->payments = $tables['payments'];
		$this->entries = $tables['entries'];
	}

	/**
	 * Get access status of a lesson.
	 *
	 * @param int $lesson_id
	 * @return string
	 */
	public function get_lesson_access( $lesson_id ) {
		return get_post_meta( $lesson_id, '_edr_access', true );
	}

	/**
	 * Get course access status.
	 *
	 * @param int $course_id
	 * @param int $user_id
	 * @return string
	 */
	public function get_course_access_status( $course_id, $user_id ) {
		global $wpdb;

		if ( ! $user_id ) {
			return '';
		}

		$status = '';
		$user_courses = wp_cache_get( $user_id, 'edr_courses_access' );

		if ( ! $user_courses ) {
			$user_courses = array();
			$sql = 'SELECT course_id, entry_status FROM ' . $this->entries . ' WHERE user_id = %d';
			$results = $wpdb->get_results( $wpdb->prepare( $sql, $user_id ) );

			if ( ! empty( $results ) ) {
				foreach ( $results as $result ) {
					$user_courses[ $result->course_id ] = $result->entry_status;
				}
			}

			wp_cache_add( $user_id, $user_courses, 'edr_courses_access' );
		}

		if ( isset( $user_courses[ $course_id ] ) ) {
			$status = $user_courses[ $course_id ];
		}

		return $status;
	}

	/**
	 * Can user study a lesson.
	 *
	 * @param int $lesson_id
	 * @return boolean
	 */
	public function can_study_lesson( $lesson_id ) {
		$lesson_access = $this->get_lesson_access( $lesson_id );
		$user_id = get_current_user_id();
		$access = false;

		if ( 'public' == $lesson_access ) {
			$access = true;
		} elseif ( $user_id ) {
			$author_id = get_post_field( 'post_author', $lesson_id );

			if ( 'logged_in' == $lesson_access || $author_id == $user_id ) {
				$access = true;
			} else {
				$edr_courses = Edr_Courses::get_instance();
				$course_id = $edr_courses->get_course_id( $lesson_id );

				if ( $course_id ) {
					$access_status = $this->get_course_access_status( $course_id, $user_id );

					if ( in_array( $access_status, array( 'inprogress' ) ) ) {
						$access = true;
					}
				}
			}
		}

		return $access;
	}

	/**
	 * Check the current user can edit a given lesson.
	 *
	 * @param int $lesson_id
	 * @return boolean
	 */
	function can_edit_lesson( $lesson_id ) {
		if ( current_user_can( 'manage_educator' ) ) {
			return true;
		}

		$can_edit = false;
		$edr_courses = Edr_Courses::get_instance();
		$course_id = $edr_courses->get_course_id( $lesson_id );

		if ( $course_id ) {
			$user_id = get_current_user_id();
			$lecturer_courses = $edr_courses->get_lecturer_courses( $user_id );
			$can_edit = in_array( $course_id, $lecturer_courses );
		}

		return $can_edit;
	}
}
