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
class Edr_Admin_EntriesTable extends WP_List_Table {
	/**
	 * @var array
	 */
	protected $pending_quiz_entries = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( array(
			'ajax' => false,
		) );

		$this->process_bulk_action();
	}

	/**
	 * Get the entry IDs that have at least one quiz pending.
	 *
	 * @return array
	 */
	protected function get_pending_quiz_entries() {
		if ( null === $this->pending_quiz_entries ) {
			if ( is_array( $this->items ) ) {
				$entry_ids = array();

				foreach ( $this->items as $item ) {
					$entry_ids[] = $item['ID'];
				}

				$quizzes = Edr_Quizzes::get_instance();
				$this->pending_quiz_entries = $quizzes->check_for_pending_quizzes( $entry_ids );
			} else {
				$this->pending_quiz_entries = array();
			}
		}

		return $this->pending_quiz_entries;
	}

	/**
	 * Display the filters form.
	 */
	public function display_entry_filters() {
		$statuses = edr_get_entry_statuses();
		$access = '';

		if ( current_user_can( 'manage_educator' ) ) {
			$access = 'all';
		} elseif ( current_user_can( 'educator_edit_entries' ) ) {
			$access = 'own';
		}

		$courses = null;

		if ( ! empty( $access ) ) {
			$course_args = array(
				'post_type'      => EDR_PT_COURSE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			);

			if ( 'own' == $access ) {
				$course_args['include'] = Edr_Courses::get_instance()->get_lecturer_courses( get_current_user_id() );
			}

			$courses = get_posts( $course_args );
		}

		$student = null;

		if ( isset( $_GET['student'] ) ) {
			$student = get_user_by( 'id', $_GET['student'] );
		}
		?>
		<div class="edr-table-nav top">
			<form class="edr-admin-filters" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="get">
				<input type="hidden" name="page" value="edr_admin_entries">
				<div class="block">
					<label for="search-entry-id"><?php echo _x( 'ID', 'ID of an item', 'educator' ); ?></label>
					<input type="text" id="search-entry-id" name="id" value="<?php if ( ! empty( $_GET['id'] ) ) echo intval( $_GET['id'] ); ?>">
				</div>
				<div class="block">
					<label for="search-entry-status"><?php _e( 'Status', 'educator' ); ?></label>
					<select id="search-entry-status" name="status">
						<option value=""><?php _e( 'All', 'educator' ); ?></option>
						<?php
							foreach ( $statuses as $key => $value ) {
								$selected = ( isset( $_GET['status'] ) && $key == $_GET['status'] ) ? ' selected="selected"' : '';

								echo '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $value ) . '</option>';
							}
						?>
					</select>
				</div>
				<div class="block">
					<label for="search-student"><?php _e( 'Student', 'educator' ); ?></label>
					<div class="edr-select-values">
						<input
							type="text"
							name="student"
							id="search-student"
							autocomplete="off"
							value="<?php if ( $student ) echo intval( $student->ID ); ?>"
							data-label="<?php if ( $student ) echo esc_attr( edr_get_user_name( $student, 'select' ) ); ?>">
					</div>
				</div>
				<?php if ( ! empty( $courses ) ) : ?>
					<div class="block">
						<label><?php _e( 'Course', 'educator' ); ?></label>
						<select name="course_id">
							<option value=""><?php _e( 'All', 'educator' ); ?></option>
							<?php
								foreach ( $courses as $course ) {
									$selected = ( isset( $_GET['course_id'] ) && $course->ID == $_GET['course_id'] ) ? ' selected="selected"' : '';

									echo '<option value="' . intval( $course->ID ) . '"' . $selected . '>' . esc_html( $course->post_title ) . '</option>';
								}
							?>
						</select>
					</div>
				<?php endif; ?>
				<div class="block">
					<input type="submit" class="button" value="<?php _e( 'Search', 'educator' ); ?>">
				</div>
			</form>
		</div>

		<script>
			EdrLib.select(document.getElementById('search-student'), {
				key:      'id',
				label:    'name',
				searchBy: 'name',
				url:      '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
				ajaxArgs: {
					action: 'edr_select_users_with_entries',
					_wpnonce: '<?php echo esc_js( wp_create_nonce( 'edr_select_users_with_entries' ) ); ?>'
				}
			});
		</script>
		<?php
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox">',
			'ID'        => _x( 'ID', 'ID of an item', 'educator' ),
			'course_id' => __( 'Course', 'educator' ),
			'user_id'   => __( 'User', 'educator' ),
			'status'    => __( 'Status', 'educator' ),
			'grade'     => __( 'Grade', 'educator' ),
			'date'      => __( 'Date', 'educator' ),
		);

		return $columns;
	}

	/**
	 * Column: checkbox.
	 *
	 * @param array $item
	 * @return string
	 */
	public function column_cb( $item ) {
		return '<input type="checkbox" name="entry[]" value="' . intval( $item['ID'] ) . '">';
	}

	/**
	 * Column: ID.
	 *
	 * @param array $item
	 * @return string
	 */
	public function column_ID( $item ) {
		$html = intval( $item['ID'] );

		if ( in_array( $item['ID'], $this->get_pending_quiz_entries() ) ) {
			$html .= ' (' . __( 'quiz pending', 'educator' ) . ')';
		}

		$base_url = admin_url( 'admin.php?page=edr_admin_entries' );
		$edit_url = admin_url( 'admin.php?page=edr_admin_entries&edr-action=edit-entry&entry_id=' . $item['ID'] );
		$delete_url = wp_nonce_url( add_query_arg( array( 'edr-action' => 'delete-entry', 'entry_id' => $item['ID'] ), $base_url ), 'edr_delete_entry' );

		$actions = array();
		$actions['edit'] = '<a href="' . esc_url( $edit_url ) . '">' . __( 'Edit', 'educator' ) . '</a>';

		if ( current_user_can( 'manage_educator' ) ) {
			$actions['delete'] = '<a href="' . esc_url( $delete_url ) . '" class="delete-entry">' . __( 'Delete', 'educator' ) . '</a>';
		}

		$html .= $this->row_actions( $actions );

		return $html;
	}

	/**
	 * Column: course_id.
	 *
	 * @param array $item
	 * @return string
	 */
	public function column_course_id( $item ) {
		$title = '';
		$course = get_post( $item['course_id'] );

		if ( $course ) {
			$title = $course->post_title;
		}

		return esc_html( $title );
	}

	/**
	 * Column: user_id.
	 *
	 * @param array $item
	 * @return string
	 */
	public function column_user_id( $item ) {
		$user = get_user_by( 'id', $item['user_id'] );

		if ( $user ) {
			$full_name = $user->first_name;
			$full_name .= ( $user->last_name ) ? ' ' . $user->last_name : '';

			if ( ! $full_name ) {
				$full_name = $user->display_name;
			}

			$student_url = add_query_arg(
				array( 'student' => $user->ID ),
				admin_url( 'admin.php?page=edr_admin_entries' )
			);

			return '<a href="' . esc_url( $student_url ) . '">' . esc_html( $full_name ) . '</a>';
		}

		return '';
	}

	/**
	 * Column: status.
	 *
	 * @param array $item
	 * @return string
	 */
	public function column_status( $item ) {
		return sanitize_title( $item['entry_status'] );
	}

	/**
	 * Column: grade.
	 *
	 * @param array $item
	 * @return string
	 */
	public function column_grade( $item ) {
		return edr_format_grade( $item['grade'] );
	}

	/**
	 * Column: date.
	 *
	 * @param array $item
	 * @return string
	 */
	public function column_date( $item ) {
		return date( 'j M, Y H:i', strtotime( $item['entry_date'] ) );
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

		$ids = isset( $_POST['entry'] ) ? $_POST['entry'] : null;

		if ( ! is_array( $ids ) || empty( $ids ) ) {
			return;
		}

		$action = $this->current_action();

		foreach ( $ids as $id ) {
			if ( 'delete' === $action ) {
				$entry = edr_get_entry( $id );

				if ( $entry->ID ) {
					$entry->delete();
				}
			}
		}
	}

	/**
	 * Prepare items.
	 * Fetch and setup entries(items).
	 */
	public function prepare_items() {
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$entries = null;
		$edr_entries = Edr_Entries::get_instance();
		$statuses = edr_get_entry_statuses();
		$args = array(
			'per_page' => $this->get_items_per_page( 'entries_per_page', 10 ),
			'page'     => $this->get_pagenum(),
		);

		/**
		 * Search by status.
		 */
		if ( ! empty( $_GET['status'] ) && array_key_exists( $_GET['status'], $statuses ) ) {
			$args['entry_status'] = $_GET['status'];
		}

		// Search by ID.
		if ( ! empty( $_GET['id'] ) ) {
			$args['entry_id'] = $_GET['id'];
		}

		// Search by course id.
		if ( ! empty( $_GET['course_id'] ) ) {
			$args['course_id'] = $_GET['course_id'];
		}

		if ( ! empty( $_GET['student'] ) ) {
			$args['user_id'] = $_GET['student'];
		}

		// Check capabilities.
		if ( current_user_can( 'manage_educator' ) ) {
			// Get all entries.
			$entries = $edr_entries->get_entries( $args, ARRAY_A );
		} elseif ( current_user_can( 'educator_edit_entries' ) ) {
			// Get the entries for the current lecturer's courses only.
			$course_ids = Edr_Courses::get_instance()->get_lecturer_courses( get_current_user_id() );

			if ( ! empty( $course_ids ) ) {
				if ( empty( $args['course_id'] ) || ! in_array( $args['course_id'], $course_ids) ) {
					$args['course_id'] = $course_ids;
				}

				$entries = $edr_entries->get_entries( $args, ARRAY_A );
			}
		}

		if ( ! empty( $entries ) ) {
			$this->set_pagination_args( array(
				'total_items' => $entries['num_items'],
				'per_page'    => $args['per_page'],
			) );

			$this->items = $entries['rows'];
		}
	}
}
