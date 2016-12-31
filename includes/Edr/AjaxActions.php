<?php

class Edr_AjaxActions {
	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'wp_ajax_edr_calculate_tax', array( __CLASS__, 'ajax_calculate_tax' ) );
		add_action( 'wp_ajax_nopriv_edr_calculate_tax', array( __CLASS__, 'ajax_calculate_tax' ) );
		add_action( 'wp_ajax_edr_get_states', array( __CLASS__, 'ajax_get_states' ) );
		add_action( 'wp_ajax_nopriv_edr_get_states', array( __CLASS__, 'ajax_get_states' ) );

		add_action( 'wp_ajax_edr_select_users', array( __CLASS__, 'ajax_select_users' ) );
		add_action( 'wp_ajax_edr_select_users_with_entries', array( __CLASS__, 'ajax_select_users_with_entries' ) );
		add_action( 'wp_ajax_edr_select_posts', array( __CLASS__, 'ajax_select_posts' ) );
	}

	/**
	 * Calculate tax.
	 */
	public static function ajax_calculate_tax() {
		if ( ! isset( $_GET['country'] ) || ! isset( $_GET['object_id'] ) ) {
			exit;
		}

		$object = get_post( intval( $_GET['object_id'] ) );

		if ( ! $object || ! in_array( $object->post_type, array( EDR_PT_COURSE, EDR_PT_MEMBERSHIP ) ) ) {
			exit;
		}

		$args = array();
		$args['country'] = $_GET['country'];
		$args['state'] = isset( $_GET['state'] ) ? $_GET['state'] : '';

		echo Edr_StudentAccount::payment_info( $object, $args );
		exit;
	}

	/**
	 * Get states.
	 */
	public static function ajax_get_states() {
		if ( empty( $_GET['country'] ) || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'edr_get_states' ) ) {
			exit;
		}

		$country = preg_replace( '/[^a-z]+/i', '', $_GET['country'] );
		$edr_countries = Edr_Countries::get_instance();
		$states = $edr_countries->get_states( $country );

		$json = '[';
		$i = 0;

		foreach ( $states as $scode => $sname ) {
			if ( $i > 0 ) $json .= ',';
			$json .= '{"code": ' . json_encode( esc_html( $scode ) ) . ',"name":' . json_encode( esc_html( $sname ) ) . '}';
			++$i;
		}

		$json .= ']';

		echo $json;
		exit;
	}

	protected static function select_users( $args ) {
		$users = array();
		$user_query = new WP_User_Query( $args );

		if ( ! empty( $user_query->results ) ) {
			foreach ( $user_query->results as $user ) {
				$users[] = array(
					'id'   => intval( $user->ID ),
					'name' => esc_html( edr_get_user_name( $user, 'select' ) ),
				);
			}
		}

		return $users;
	}

	public static function ajax_select_users() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'edr_select_users' ) ) {
			exit;
		}

		if ( ! current_user_can( 'manage_educator' ) ) {
			exit;
		}

		$args = array(
			'number' => 10,
		);

		if ( ! empty( $_GET['input'] ) ) {
			$args['search'] = '*' . $_GET['input'] . '*';
		}

		$allowed_roles = array( 'student' );

		if ( isset( $_GET['role'] ) && in_array( $_GET['role'], $allowed_roles ) ) {
			$args['role'] = $_GET['role'];
		}

		$users = self::select_users( $args );

		echo json_encode( $users );
		exit;
	}

	public static function ajax_select_users_with_entries() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'edr_select_users_with_entries' ) ) {
			exit;
		}

		$args = array(
			'number' => 10,
		);

		if ( ! current_user_can( 'manage_educator' ) ) {
			if ( current_user_can( 'educator_edit_entries' ) ) {
				$cur_user_id = get_current_user_id();
				$edr_courses = Edr_Courses::get_instance();
				$course_ids = $edr_courses->get_lecturer_courses( $cur_user_id );

				if ( ! empty( $course_ids ) ) {
					global $wpdb;
					$tables = edr_db_tables();
					$course_ids_sql = implode( ',', $course_ids );
					$student_ids = $wpdb->get_col(
						"
						SELECT DISTINCT user_id
						FROM   {$tables['entries']}
						WHERE  course_id IN ($course_ids_sql)
						"
					);

					if ( ! empty( $student_ids ) ) {
						$args['include'] = $student_ids;
					} else {
						exit;
					}
				} else {
					exit;
				}
			} else {
				exit;
			}
		}

		if ( ! empty( $_GET['input'] ) ) {
			$args['search'] = '*' . $_GET['input'] . '*';
		}

		$users = self::select_users( $args );

		echo json_encode( $users );
		exit;
	}

	public static function ajax_select_posts() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'edr_select_posts' ) ) {
			exit;
		}

		if ( ! current_user_can( 'manage_educator' ) ) {
			exit;
		}

		$posts = array();
		$args = array(
			'post_status'    => 'publish',
			'posts_per_page' => 15,
		);

		if ( ! empty( $_GET['input'] ) ) {
			$args['s'] = $_GET['input'];
		}

		if ( ! empty( $_GET['post_type'] ) ) {
			$args['post_type'] = $_GET['post_type'];
		}

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$posts[] = array(
					'id'    => get_the_ID(),
					'title' => get_the_title(),
				);
			}
			wp_reset_postdata();
		}

		echo json_encode( $posts );
		exit;
	}
}
