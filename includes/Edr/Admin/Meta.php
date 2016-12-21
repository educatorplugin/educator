<?php

class Edr_Admin_Meta {
	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_lesson_meta_box' ), 10, 3 );
		add_action( 'save_post', array( __CLASS__, 'save_course_meta_box' ), 10, 3 );
		add_action( 'save_post', array( __CLASS__, 'save_membership_meta_box' ), 10, 3 );
	}

	/**
	 * Add meta boxes.
	 */
	public static function add_meta_boxes() {
		// Course meta box.
		add_meta_box(
			'edr_course_meta',
			__( 'Course Settings', 'educator' ),
			array( __CLASS__, 'course_meta_box' ),
			EDR_PT_COURSE
		);

		// Lesson meta box.
		add_meta_box(
			'edr_lesson_meta',
			__( 'Lesson Settings', 'educator' ),
			array( __CLASS__, 'lesson_meta_box' ),
			EDR_PT_LESSON
		);

		// Membership meta box.
		add_meta_box(
			'edr_membership',
			__( 'Membership Settings', 'educator' ),
			array( __CLASS__, 'membership_meta_box' ),
			EDR_PT_MEMBERSHIP
		);
	}

	/**
	 * Output course meta box.
	 *
	 * @param WP_Post $post
	 */
	public static function course_meta_box( $post ) {
		include EDR_PLUGIN_DIR . 'templates/admin/mb-course.php';
	}

	/**
	 * Output lesson meta box.
	 *
	 * @param WP_Post $post
	 */
	public static function lesson_meta_box( $post ) {
		include EDR_PLUGIN_DIR . 'templates/admin/mb-lesson.php';
	}

	/**
	 * Output membership meta box.
	 *
	 * @param WP_Post $post
	 */
	public static function membership_meta_box( $post ) {
		include EDR_PLUGIN_DIR . 'templates/admin/mb-membership.php';
	}

	/**
	 * Save tax data for a course or membership.
	 */
	protected static function save_tax_data( $post_id ) {
		if ( isset( $_POST['_edr_tax_class'] ) ) {
			update_post_meta( $post_id, '_edr_tax_class', sanitize_text_field( $_POST['_edr_tax_class'] ) );
		}
	}

	/**
	 * Save course meta box.
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 * @param boolean $update
	 */
	public static function save_course_meta_box( $post_id, $post, $update ) {
		if ( ! isset( $_POST['edr_course_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['edr_course_meta_box_nonce'], 'edr_course_meta_box' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( EDR_PT_COURSE != $post->post_type || ! current_user_can( 'edit_' . EDR_PT_COURSE, $post_id ) ) {
			return;
		}

		// Registration.
		$register = ( isset( $_POST['_edr_register'] ) && 'open' != $_POST['_edr_register'] ) ? 'closed' : 'open';
		update_post_meta( $post_id, '_edr_register', $register );

		// Price.
		$price = ( isset( $_POST['_edr_price'] ) && is_numeric( $_POST['_edr_price'] ) ) ? $_POST['_edr_price'] : '';
		update_post_meta( $post_id, '_edr_price', $price );

		// Difficulty.
		$difficulty = ( isset( $_POST['_edr_difficulty'] ) ) ? $_POST['_edr_difficulty'] : '';
		$difficulty_levels = edr_get_difficulty_levels();

		if ( empty( $difficulty ) || array_key_exists( $difficulty, $difficulty_levels ) ) {
			update_post_meta( $post_id, '_edr_difficulty', $difficulty );
		}

		// Prerequisite.
		if ( isset( $_POST['_edr_prerequisites'] ) ) {
			$prerequisites = array();

			if ( is_numeric( $_POST['_edr_prerequisites'] ) ) {
				$prerequisites[] = $_POST['_edr_prerequisites'];
			}

			update_post_meta( $post_id, '_edr_prerequisites', $prerequisites );
		}

		self::save_tax_data( $post_id );
	}

	/**
	 * Save lesson meta box.
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 * @param boolean $update
	 */
	public static function save_lesson_meta_box( $post_id, $post, $update ) {
		if ( ! isset( $_POST['edr_lesson_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['edr_lesson_meta_box_nonce'], 'edr_lesson_meta_box' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( EDR_PT_LESSON != $post->post_type || ! current_user_can( 'edit_' . EDR_PT_LESSON, $post_id ) ) {
			return;
		}

		// Lesson access.
		$access_options = array( 'registered', 'logged_in', 'public' );
		$access = 'registered';

		if ( isset( $_POST['_edr_access'] ) && in_array( $_POST['_edr_access'], $access_options ) ) {
			$access = $_POST['_edr_access'];
		}

		update_post_meta( $post_id, '_edr_access', $access );

		// Course.
		$value = ( isset( $_POST['_edr_course_id'] ) && is_numeric( $_POST['_edr_course_id'] ) ) ? $_POST['_edr_course_id'] : '';
		update_post_meta( $post_id, '_edr_course_id', $value );
	}

	/**
	 * Save membership meta box.
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 * @param boolean $update
	 */
	public static function save_membership_meta_box( $post_id, $post, $update ) {
		if ( ! isset( $_POST['edr_membership_meta_box_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['edr_membership_meta_box_nonce'], 'edr_membership_meta_box' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( EDR_PT_MEMBERSHIP != get_post_type( $post_id ) || ! current_user_can( 'edit_' . EDR_PT_MEMBERSHIP, $post_id ) ) {
			return;
		}

		if ( isset( $_POST['_edr_price'] ) ) {
			update_post_meta( $post_id, '_edr_price', (float) $_POST['_edr_price'] );
		}

		if ( isset( $_POST['_edr_duration'] ) ) {
			update_post_meta( $post_id, '_edr_duration', intval( $_POST['_edr_duration'] ) );
		}

		if ( isset( $_POST['_edr_period'] ) ) {
			update_post_meta( $post_id, '_edr_period', sanitize_text_field( $_POST['_edr_period'] ) );
		}

		$categories = isset( $_POST['_edr_categories'] ) ? $_POST['_edr_categories'] : array();

		if ( is_array( $categories ) && ! empty( $categories ) ) {
			$categories = array_map( 'intval', $categories );
		}

		update_post_meta( $post_id, '_edr_categories', $categories );

		self::save_tax_data( $post_id );
	}
}
