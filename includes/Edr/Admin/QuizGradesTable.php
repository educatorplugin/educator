<?php

// Forbid direct access.
if ( ! defined( 'ABSPATH' ) ) exit();

// Load the WP_List_Table class.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Entries list table.
 */
class Edr_Admin_QuizGradesTable extends WP_List_Table {
	protected $filters_input = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( array(
			'ajax' => false,
		) );

		$this->get_filters_input();
		$this->process_bulk_action();
	}

	/**
	 * Get filters input.
	 *
	 * @return array
	 */
	public function get_filters_input() {
		if ( is_null( $this->filters_input ) ) {
			$this->set_filters_input( $_GET );
		}

		return $this->filters_input;
	}

	/**
	 * Set the grades list filters.
	 *
	 * @param array $input
	 */
	public function set_filters_input( $input ) {
		$this->filters_input = array();
		$this->filters_input['status'] = ! empty( $input['status'] )
			? $input['status'] : 'pending';
		$this->filters_input['post'] = ! empty( $input['post'] )
			? (int) $input['post'] : 0;
	}

	/**
	 * Display the filters form.
	 */
	public function display_quiz_grade_filters() {
		$statuses = array(
			'pending'  => __( 'Pending', 'educator' ),
			'approved' => __( 'Approved', 'educator' ),
		);
		$permission = $this->get_permission_to_edit();
		$own_quiz_posts = $this->get_quiz_posts(
			'edit_own' == $permission ? get_current_user_id() : 0
		);
		?>
		<div class="edr-table-nav top">
			<form class="edr-admin-filters" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="get">
				<input type="hidden" name="page" value="edr_admin_quiz_grades">
				<div class="block">
					<label for="search-quiz-grade-status"><?php _e( 'Status', 'educator' ); ?></label>
					<select id="search-quiz-grade-status" name="status">
						<option value="all"><?php _e( 'All', 'educator' ); ?></option>
						<?php
							foreach ( $statuses as $status_key => $status_label ) {
								$selected = ( $this->filters_input['status'] == $status_key )
									? ' selected="selected"' : '';
								echo '<option value="' . esc_attr( $status_key ) . '"' . $selected . '>' .
									esc_html( $status_label ) . '</option>';
							}
						?>
					</select>
				</div>
				<div class="block">
					<label for="search-quiz-grade-post"><?php _e( 'Post', 'educator' ); ?></label>
					<select id="search-quiz-grade-post" name="post">
						<option value="all"><?php _e( 'All', 'educator' ); ?></option>
						<?php
							foreach ( $own_quiz_posts as $post ) {
								$selected = ( $this->filters_input['post'] == $post->ID )
									? ' selected="selected"' : '';
								echo '<option value="' . intval( $post->ID ) . '"' . $selected . '>' .
									esc_html( $post->post_title ) . '</option>';
							}
						?>
					</select>
				</div>
				<div class="block">
					<input type="submit" class="button" value="<?php _e( 'Search', 'educator' ); ?>">
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'     => '<input type="checkbox">',
			'ID'     => _x( 'ID', 'ID of an item', 'educator' ),
			'user'   => __( 'User', 'educator' ),
			'post'   => __( 'Post', 'educator' ),
			'entry'  => __( 'Entry', 'educator' ),
			'status' => __( 'Status', 'educator' ),
			'grade'  => __( 'Grade', 'educator' ),
		);

		return $columns;
	}

	/**
	 * Column: checkbox.
	 *
	 * @param stdClass $item
	 * @return string
	 */
	public function column_cb( $item ) {
		return '<input type="checkbox" name="quiz_grade[]" value="' . intval( $item->ID ) . '">';
	}

	/**
	 * Column: ID.
	 *
	 * @param stdClass $item
	 * @return string
	 */
	public function column_ID( $item ) {
		$base_url = admin_url( 'admin.php?page=edr_admin_quiz_grades' );

		$edit_url = add_query_arg( array(
			'edr-action' => 'edit-quiz-grade',
			'grade_id'   => $item->ID,
		), $base_url );

		$delete_url = add_query_arg( array(
			'edr-action' => 'delete-quiz-grade',
			'grade_id'   => $item->ID,
			'redirect'   => urlencode( add_query_arg( '', '' ) ),
		), $base_url );

		$delete_url = wp_nonce_url( $delete_url, 'edr_delete_quiz_grade_' . $item->ID );

		$actions = array();
		$actions['edit'] = '<a href="' . esc_url( $edit_url ) . '">' . __( 'Edit', 'educator' ) . '</a>';

		if ( current_user_can( 'manage_educator' ) ) {
			$actions['delete'] = '<a href="' . esc_url( $delete_url ) . '" class="delete-quiz-grade">' . __( 'Delete', 'educator' ) . '</a>';
		}

		return intval( $item->ID ) . $this->row_actions( $actions );
	}

	/**
	 * Column: user.
	 *
	 * @param stdClass $item
	 * @return string
	 */
	public function column_user( $item ) {
		$full_name = '';
		$user = get_user_by( 'id', $item->user_id );

		if ( $user ) {
			$full_name = edr_get_user_full_name( $user );
		}

		return esc_html( $full_name );
	}

	/**
	 * Column: title.
	 *
	 * @param stdClass $item
	 * @return string
	 */
	public function column_post( $item ) {
		return get_the_title( $item->lesson_id );
	}

	/**
	 * Column: entry.
	 *
	 * @param stdClass $item
	 * @return string
	 */
	public function column_entry( $item ) {
		$entry_link = '';

		if ( $item->entry_id ) {
			$url_edit_entry = add_query_arg( array(
				'page'       => 'edr_admin_entries',
				'edr-action' => 'edit-entry',
				'entry_id'   => $item->entry_id,
			), admin_url( 'admin.php' ) );

			$entry_link = sprintf( '<a href="%s" target="_blank" title="%s">%d</a>',
				esc_url( $url_edit_entry ), __( 'Edit Entry', 'educator' ), $item->entry_id );
		}

		return $entry_link;
	}

	/**
	 * Column: status.
	 *
	 * @param stdClass $item
	 * @return string
	 */
	public function column_status( $item ) {
		return esc_html( $item->status );
	}

	/**
	 * Column: grade.
	 *
	 * @param stdClass $item
	 * @return string
	 */
	public function column_grade( $item ) {
		return edr_format_grade( $item->grade );
	}

	/**
	 * Define bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', 'educator' ),
		);

		return $actions;
	}

	/**
	 * Process bulk actions.
	 */
	public function process_bulk_action() {
		$nonce_action = 'bulk-' . $this->_args['plural'];

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $nonce_action ) ) {
			return;
		}

		$ids = isset( $_POST['quiz_grade'] ) ? $_POST['quiz_grade'] : null;

		if ( ! is_array( $ids ) || empty( $ids ) ) {
			return;
		}

		$action = $this->current_action();
		$obj_quizzes = Edr_Quizzes::get_instance();

		foreach ( $ids as $id ) {
			if ( 'delete' === $action ) {
				$grade = edr_get_quiz_grade( $id );

				if ( $grade->ID ) {
					$answers_deleted = $obj_quizzes->delete_answers( array(
						'grade_id' => $grade->ID,
					) );

					if ( false !== $answers_deleted ) {
						$grade->delete();
					}
				}
			}
		}
	}

	/**
	 * Get user's post ids.
	 *
	 * @param int $user_id
	 * @param string $format
	 * @return array
	 */
	public function get_quiz_posts( $user_id = 0 ) {
		$posts = get_posts( array(
			'post_type'   => 'any',
			'post_status' => 'publish',
			'orderby'     => 'ID',
			'order'       => 'DESC',
			'author'      => $user_id,
			'meta_query'  => array(
				array(
					'key'     => '_edr_quiz',
					'value'   => '1',
					'compare' => '=',
				),
			),
		) );

		return $posts;
	}

	/**
	 * Check if current user has permission to edit grades.
	 *
	 * @return string ('', 'edit_own', 'edit_all')
	 */
	protected function get_permission_to_edit() {
		$permission = '';

		if ( current_user_can( 'edr_edit_quiz_grades_own' ) ) {
			$permission = 'edit_own';
		} elseif ( current_user_can( 'edr_edit_quiz_grades_all' ) ) {
			$permission = 'edit_all';
		}

		return $permission;
	}

	/**
	 * Get the lesson id to filter the grades list by.
	 *
	 * @param $permission
	 * @return int|array
	 */
	protected function get_filter_lesson_id( $permission ) {
		$lesson_id = 0;

		if ( 'edit_own' == $permission ) {
			$own_post_ids = array();
			$own_posts = $this->get_quiz_posts( get_current_user_id() );

			if ( ! empty( $own_posts ) ) {
				foreach ( $own_posts as $post ) {
					$own_post_ids[] = $post->ID;
				}

				if ( in_array( $this->filters_input['post'], $own_post_ids ) ) {
					$lesson_id = $this->filters_input['post'];
				} else {
					$lesson_id = $own_post_ids;
				}
			}
		} elseif ( 'edit_all' == $permission ) {
			if ( $this->filters_input['post'] ) {
				$lesson_id = $this->filters_input['post'];
			}
		}

		return $lesson_id;
	}

	/**
	 * Prepare items.
	 * Fetch and setup quiz grades(items).
	 */
	public function prepare_items() {
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$permission = $this->get_permission_to_edit();

		$args = array(
			'per_page' => $this->get_items_per_page( 'quiz_grades_per_page', 10 ),
			'page'     => $this->get_pagenum(),
		);

		if ( 'all' != $this->filters_input['status'] ) {
			$args['status'] = $this->filters_input['status'];
		}

		$lesson_id = $this->get_filter_lesson_id( $permission );

		if ( $lesson_id ) {
			$args['lesson_id'] = $lesson_id;
		} elseif ( 'edit_all' != $permission ) {
			return;
		}

		$obj_quizzes = Edr_Quizzes::get_instance();
		$quiz_grades = $obj_quizzes->get_grades( $args );

		// Cache data to avoid multiple queries when columns are printed.
		$post_ids = array();
		$user_ids = array();

		// Cache posts.
		foreach ( $quiz_grades['rows'] as $item ) {
			if ( ! in_array( $item->lesson_id, $post_ids ) )
				$post_ids[] = $item->lesson_id;

			if ( ! in_array( $item->user_id, $user_ids ) )
				$user_ids[] = $item->user_id;
		}

		new WP_Query( array(
			'post__in'            => $post_ids,
			'ignore_sticky_posts' => true,
		) );

		// Cache users.
		new WP_User_Query( array(
			'include' => $user_ids,
		) );

		if ( ! empty( $quiz_grades ) ) {
			$this->set_pagination_args( array(
				'total_items' => $quiz_grades['num_items'],
				'per_page'    => $args['per_page'],
			) );

			$this->items = $quiz_grades['rows'];
		}
	}
}
