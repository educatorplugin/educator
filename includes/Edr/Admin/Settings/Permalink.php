<?php

class Edr_Admin_Settings_Permalink extends Edr_Admin_Settings_Base {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_settings();
		$this->save_settings();
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		add_settings_section(
			'edr_permalink_settings', // id
			__( 'Educator Permalink Settings', 'educator' ),
			array( $this, 'section_description' ),
			'permalink' // page
		);

		add_settings_field(
			'edr_category_base',
			__( 'Course category base', 'educator' ),
			array( $this, 'setting_text' ),
			'permalink', // page
			'edr_permalink_settings', // section
			array(
				'name'           => 'category_base',
				'settings_group' => 'edr_permalinks',
				'default'        => 'course-category',
				'id'             => 'edr_category_base',
			)
		);

		add_settings_field(
			'edr_courses_archive_base',
			__( 'Courses archive base', 'educator' ),
			array( $this, 'setting_text' ),
			'permalink', // page
			'edr_permalink_settings', // section
			array(
				'name'           => 'courses_archive_base',
				'settings_group' => 'edr_permalinks',
				'default'        => _x( 'courses', 'courses archive slug', 'educator' ),
				'id'             => 'edr_courses_archive_base',
			)
		);

		add_settings_field(
			'edr_course_base',
			__( 'Course base', 'educator' ),
			array( $this, 'setting_text' ),
			'permalink', // page
			'edr_permalink_settings', // section
			array(
				'name'           => 'course_base',
				'settings_group' => 'edr_permalinks',
				'default'        => _x( 'courses', 'course slug', 'educator' ),
				'id'             => 'edr_course_base',
			)
		);

		add_settings_field(
			'edr_lessons_archive_base',
			__( 'Lessons archive base', 'educator' ),
			array( $this, 'setting_text' ),
			'permalink', // page
			'edr_permalink_settings', // section
			array(
				'name'           => 'lessons_archive_base',
				'settings_group' => 'edr_permalinks',
				'default'        => _x( 'lessons', 'lessons archive slug', 'educator' ),
				'id'             => 'edr_lessons_archive_base',
			)
		);

		add_settings_field(
			'edr_lesson_base',
			__( 'Lesson base', 'educator' ),
			array( $this, 'setting_text' ),
			'permalink', // page
			'edr_permalink_settings', // section
			array(
				'name'           => 'lesson_base',
				'settings_group' => 'edr_permalinks',
				'default'        => _x( 'lessons', 'lesson slug', 'educator' ),
				'id'             => 'edr_lesson_base',
			)
		);
	}

	/**
	 * Validate the settings before saving.
	 *
	 * @param array $input
	 * @return array
	 */
	public function validate( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$clean = array();

		foreach ( $input as $key => $value ) {
			switch ( $key ) {
				case 'category_base':
				case 'courses_archive_base':
				case 'course_base':
				case 'lessons_archive_base':
				case 'lesson_base':
					$clean[ $key ] = trim( str_replace( array( 'http://', '#' ), '', esc_url_raw( $value ) ), '/' );
					break;
			}
		}

		return $clean;
	}

	/**
	 * Save the settings.
	 */
	public function save_settings() {
		if ( isset( $_POST['edr_permalinks'] ) ) {
			$clean = $this->validate( $_POST['edr_permalinks'] );
			$permalink_settings = get_option( 'edr_permalinks' );

			if ( ! is_array( $permalink_settings ) ) {
				$permalink_settings = array();
			}

			if ( isset( $clean['category_base'] ) ) {
				$permalink_settings['category_base'] = $clean['category_base'];
			}

			if ( isset( $clean['courses_archive_base'] ) ) {
				$permalink_settings['courses_archive_base'] = $clean['courses_archive_base'];
			}

			if ( isset( $clean['course_base'] ) ) {
				$permalink_settings['course_base'] = $clean['course_base'];
			}

			if ( isset( $clean['lessons_archive_base'] ) ) {
				$permalink_settings['lessons_archive_base'] = $clean['lessons_archive_base'];
			}

			if ( isset( $clean['lesson_base'] ) ) {
				$permalink_settings['lesson_base'] = $clean['lesson_base'];
			}

			update_option( 'edr_permalinks', $permalink_settings );
		}
	}
}
