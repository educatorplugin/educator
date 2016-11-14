<?php

class Edr_Quizzes {
	/**
	 * @var Edr_Quizzes
	 */
	protected static $instance = null;

	/**
	 * @var string
	 */
	protected $tbl_questions;

	/**
	 * @var string
	 */
	protected $tbl_choices;

	/**
	 * @var string
	 */
	protected $tbl_grades;

	/**
	 * @var string
	 */
	protected $tbl_answers;

	/**
	 * Get the single instance of this class.
	 *
	 * @return Edr_Quizzes
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$tables = edr_db_tables();
		$this->tbl_questions = $tables['questions'];
		$this->tbl_choices   = $tables['choices'];
		$this->tbl_grades    = $tables['grades'];
		$this->tbl_answers   = $tables['answers'];
	}

	/**
	 * Check if a post has a quiz.
	 *
	 * @param int $post_id
	 * @return int 1 - true, 0 - false
	 */
	public function post_has_quiz( $post_id ) {
		return (boolean) get_post_meta( $post_id, '_edr_quiz', true );
	}

	/**
	 * Get the maximum number of attempts per quiz.
	 *
	 * @param int $lesson_id
	 * @return int
	 */
	public function get_max_attempts_number( $lesson_id ) {
		return (int) get_post_meta( $lesson_id, '_edr_attempts', true );
	}

	/**
	 * Get the number of attempts per quiz per entry.
	 *
	 * @param int $entry_id
	 * @param int $lesson_id
	 * @return int
	 */
	public function get_attempts_number( $lesson_id, $entry_id = null ) {
		global $wpdb;
		$query = "
			SELECT count(1)
			FROM   $this->tbl_grades
			WHERE  lesson_id = %d
			       AND status <> 'draft'
		";
		$values = array( $lesson_id );

		if ( $entry_id ) {
			$query .= ' AND entry_id = %d';
			$values[] = $entry_id;
		} else {
			$query .= ' AND user_id = %d AND entry_id = 0';
			$values[] = get_current_user_id();
		}

		$attempts_number = (int) $wpdb->get_var( $wpdb->prepare( $query, $values ) );

		return $attempts_number;
	}

	/**
	 * Get quiz questions.
	 *
	 * @param int $lesson_id
	 * @return array
	 */
	public function get_questions( $lesson_id ) {
		global $wpdb;
		$questions = $wpdb->get_results( $wpdb->prepare(
			"
			SELECT *
			FROM   $this->tbl_questions
			WHERE  lesson_id = %d
			ORDER  BY menu_order ASC
			",
			$lesson_id
		) );

		if ( ! empty( $questions ) ) {
			$questions = array_map( 'edr_get_question', $questions );
		}

		return $questions;
	}

	/**
	 * Delete question answer choices given a question ID.
	 *
	 * @param int $question_id
	 * @return false|int Number of rows deleted or false on error.
	 */
	public function delete_choices( $question_id ) {
		global $wpdb;

		return $wpdb->delete(
			$this->tbl_choices,
			array( 'question_id' => $question_id ),
			array( '%d' )
		);
	}

	/**
	 * Get available choices for a multiple choice question.
	 *
	 * @param int $question_id
	 * @return array
	 */
	public function get_question_choices( $question_id ) {
		global $wpdb;

		$results = $wpdb->get_results( $wpdb->prepare(
			"
			SELECT   *
			FROM     $this->tbl_choices
			WHERE    question_id = %d
			ORDER BY menu_order ASC
			",
			$question_id
		), OBJECT_K );

		if ( ! empty( $results ) ) {
			$results = array_map( 'edr_get_quiz_choice', $results );
		}

		return $results;
	}

	/**
	 * Get all choices for a given lesson.
	 *
	 * @param int $lesson_id
	 * @return array
	 */
	public function get_choices( $lesson_id, $sorted = false ) {
		global $wpdb;

		$choices = $wpdb->get_results( $wpdb->prepare(
			"
			SELECT   *
			FROM     $this->tbl_choices
			WHERE    question_id IN (
			             SELECT question_id
			             FROM   $this->tbl_questions
			             WHERE  lesson_id = %d
			         )
			ORDER BY menu_order ASC
			",
			$lesson_id
		) );

		if ( $sorted ) {
			$sorted_arr = array();

			foreach ( $choices as $row ) {
				if ( ! isset( $sorted_arr[ $row->question_id ] ) ) {
					$sorted_arr[ $row->question_id ] = array();
				}

				$sorted_arr[ $row->question_id ][ $row->ID ] = edr_get_quiz_choice( $row );
			}

			return $sorted_arr;
		} else {
			$choices = array_map( 'edr_get_quiz_choice', $choices );
		}

		return $choices;
	}

	/**
	 * Get grades.
	 *
	 * @return array
	 */
	public function get_grades( $args = array() ) {
		global $wpdb;

		$sql = "
			SELECT *
			FROM   $this->tbl_grades
			WHERE  1
		";

		if ( isset( $args['status'] ) ) {
			$sql .= $wpdb->prepare( ' AND status = %s', $args['status'] );
		}

		if ( isset( $args['lesson_id'] ) ) {
			if ( is_array( $args['lesson_id'] ) ) {
				if ( ! empty( $args['lesson_id'] ) ) {
					$sql .= ' AND lesson_id IN (' . implode( ',', array_map( 'intval', $args['lesson_id'] ) ) . ')';
				}
			} else {
				$sql .= $wpdb->prepare( ' AND lesson_id = %d', $args['lesson_id'] );
			}
		}

		$pagination_sql = '';
		$has_pagination = isset( $args['page'] ) && isset( $args['per_page'] ) &&
			is_numeric( $args['page'] ) && is_numeric( $args['per_page'] );

		if ( $has_pagination ) {
			$num_rows = $wpdb->get_var( str_replace( 'SELECT *', 'SELECT count(1)', $sql ) );
			$pagination_sql .= ' LIMIT ' . ( ( $args['page'] - 1 ) * $args['per_page'] ) . ', ' . $args['per_page'];
		}

		$grades = $wpdb->get_results( $sql . ' ORDER BY ID DESC' . $pagination_sql );

		if ( ! empty( $grades ) ) {
			$grades = array_map( 'edr_get_quiz_grade', $grades );
		}

		if ( $has_pagination ) {
			return array(
				'num_pages' => (int) ceil( $num_rows / $args['per_page'] ),
				'num_items' => (int) $num_rows,
				'rows'      => $grades,
			);
		}

		return $grades;
	}

	/**
	 * Get the latest grade for a given quiz.
	 *
	 * @param int $lesson_id
	 * @param int $entry_id
	 * @return null|object Returns null if the grade is not found.
	 */
	public function get_grade( $lesson_id, $entry_id = null ) {
		global $wpdb;
		$query = "
			SELECT *
			FROM   $this->tbl_grades
			WHERE  lesson_id = %d
		";
		$values = array();
		$values[] = $lesson_id;

		if ( $entry_id ) {
			$query .= ' AND entry_id = %d';
			$values[] = $entry_id;
		} else {
			$query .= ' AND user_id = %d AND entry_id = 0';
			$values[] = get_current_user_id();
		}

		$query .= ' ORDER BY ID DESC';

		$grade = $wpdb->get_row( $wpdb->prepare( $query, $values ) );

		if ( $grade ) {
			$grade = edr_get_quiz_grade( $grade );
		}

		return $grade;
	}

	/**
	 * Get answers by grade id.
	 *
	 * @param int $grade_id
	 * @return array
	 */
	public function get_answers( $grade_id ) {
		global $wpdb;

		$results = $wpdb->get_results( $wpdb->prepare(
			"
			SELECT question_id, ID, grade_id, entry_id, question_id, choice_id, correct, answer_text
			FROM   $this->tbl_answers
			WHERE  grade_id = %d
			",
			$grade_id
		), OBJECT_K );

		if ( ! empty( $results ) ) {
			$results = array_map( 'edr_get_quiz_answer', $results );
		}

		return $results;
	}

	/**
	 * Delete answers.
	 *
	 * @param array $args
	 * @return int|false Number of deleted answers, or false on error.
	 */
	public function delete_answers( $args ) {
		global $wpdb;

		$sql = "
			DELETE FROM $this->tbl_answers
			WHERE  1
		";
		$sql_where = '';

		if ( isset( $args['grade_id'] ) ) {
			if ( is_array( $args['grade_id'] ) ) {
				$sql_where .= ' AND grade_id IN (' . implode( ',', array_map( 'intval', $args['grade_id'] ) ) . ')';
			} else {
				$sql_where .= $wpdb->prepare( ' AND grade_id = %d', $args['grade_id'] );
			}
		}

		if ( ! $sql_where ) {
			return false;
		}

		$sql .= $sql_where;

		$affected_rows = $wpdb->query( $sql );

		return $affected_rows;
	}

	/**
	 * Get the entries with ungraded quizzes.
	 *
	 * @param array $ids
	 * @return array
	 */
	public function check_for_pending_quizzes( $ids ) {
		global $wpdb;

		if ( empty( $ids ) ) {
			return array();
		}

		$ids = implode( ',', array_map( 'absint', $ids ) );

		return $wpdb->get_col(
			"
			SELECT   entry_id
			FROM     $this->tbl_grades
			WHERE    status = 'pending'
			         AND entry_id IN ($ids)
			GROUP BY entry_id
			"
		);
	}

	/**
	 * Get quiz answer file URL.
	 *
	 * @param int $lesson_id
	 * @param int $question_id
	 * @param int $grade_id
	 * @return string
	 */
	public function get_file_url( $lesson_id, $question_id, $grade_id ) {
		$url = add_query_arg( array(
			'edr-action'  => 'quiz-file-download',
			'grade_id'    => $grade_id,
			'question_id' => $question_id,
		), get_permalink( $lesson_id ) );

		return $url;
	}
}
