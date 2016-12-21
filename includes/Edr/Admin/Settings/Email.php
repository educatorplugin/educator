<?php

class Edr_Admin_Settings_Email extends Edr_Admin_Settings_Base {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'edr_settings_tabs', array( $this, 'add_tab' ) );
		add_action( 'edr_settings_page', array( $this, 'settings_page' ) );
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		add_settings_section(
			'edr_email_settings', // id
			__( 'Email Settings', 'educator' ),
			array( $this, 'section_description' ),
			'edr_email_page' // page
		);

		// Setting: From Name.
		add_settings_field(
			'edr_from_name',
			__( 'From Name', 'educator' ),
			array( $this, 'setting_text' ),
			'edr_email_page', // page
			'edr_email_settings', // section
			array(
				'name'           => 'from_name',
				'settings_group' => 'edr_email',
				'description'    => __( 'The name email notifications are said to come from.', 'educator' ),
				'default'        => get_bloginfo( 'name' ),
			)
		);

		// Setting: From Email.
		add_settings_field(
			'edr_from_email',
			__( 'From Email', 'educator' ),
			array( $this, 'setting_text' ),
			'edr_email_page', // page
			'edr_email_settings', // section
			array(
				'name'           => 'from_email',
				'settings_group' => 'edr_email',
				'description'    => __( 'Email to send notifications from.', 'educator' ),
				'default'        => get_bloginfo( 'admin_email' ),
			)
		);

		// Email templates.
		add_settings_section(
			'edr_email_templates', // id
			__( 'Email Templates', 'educator' ),
			array( $this, 'section_description' ),
			'edr_email_page' // page
		);

		// Subject: student registered.
		add_settings_field(
			'edr_subject_student_registered',
			__( 'Student registered subject', 'educator' ),
			array( $this, 'setting_text' ),
			'edr_email_page', // page
			'edr_email_templates', // section
			array(
				'name'           => 'subject',
				'settings_group' => 'edr_student_registered',
				'description'    => sprintf( __( 'Subject of the student registered notification email. Placeholders: %s', 'educator' ), '{course_title}, {login_link}' ),
			)
		);

		// Template: student registered.
		add_settings_field(
			'edr_template_student_registered',
			__( 'Student registered template', 'educator' ),
			array( $this, 'setting_textarea' ),
			'edr_email_page', // page
			'edr_email_templates', // section
			array(
				'name'           => 'template',
				'settings_group' => 'edr_student_registered',
				'description'    => sprintf( __( 'Placeholders: %s', 'educator' ), '{student_name}, {course_title}, {course_excerpt}, {login_link}' ),
			)
		);

		// Subject: quiz grade.
		add_settings_field(
			'edr_subject_quiz_grade',
			__( 'Quiz grade subject', 'educator' ),
			array( $this, 'setting_text' ),
			'edr_email_page', // page
			'edr_email_templates', // section
			array(
				'name'           => 'subject',
				'settings_group' => 'edr_quiz_grade',
				'description'    => __( 'Subject of the quiz grade email.', 'educator' ),
			)
		);

		// Template: quiz grade.
		add_settings_field(
			'edr_template_quiz_grade',
			__( 'Quiz grade template', 'educator' ),
			array( $this, 'setting_textarea' ),
			'edr_email_page', // page
			'edr_email_templates', // section
			array(
				'name'           => 'template',
				'settings_group' => 'edr_quiz_grade',
				'description'    => sprintf( __( 'Placeholders: %s', 'educator' ), '{student_name}, {lesson_title}, {grade}, {login_link}' ),
			)
		);

		// Subject: membership_register.
		add_settings_field(
			'edr_subject_membership_register',
			__( 'Membership registration subject', 'educator' ),
			array( $this, 'setting_text' ),
			'edr_email_page', // page
			'edr_email_templates', // section
			array(
				'name'           => 'subject',
				'settings_group' => 'edr_membership_register',
			)
		);

		// Template: membership_register.
		add_settings_field(
			'edr_template_membership_register',
			__( 'Membership registration template', 'educator' ),
			array( $this, 'setting_textarea' ),
			'edr_email_page', // page
			'edr_email_templates', // section
			array(
				'name'           => 'template',
				'settings_group' => 'edr_membership_register',
				'description'    => sprintf( __( 'Placeholders: %s', 'educator' ), '{student_name}, {membership}, {expiration}, {price}, {login_link}' ),
			)
		);

		// Subject: membership_renew.
		add_settings_field(
			'edr_subject_membership_renew',
			__( 'Membership renew subject', 'educator' ),
			array( $this, 'setting_text' ),
			'edr_email_page', // page
			'edr_email_templates', // section
			array(
				'name'           => 'subject',
				'settings_group' => 'edr_membership_renew',
			)
		);

		// Template: membership_renew.
		add_settings_field(
			'edr_template_membership_renew',
			__( 'Membership renew template', 'educator' ),
			array( $this, 'setting_textarea' ),
			'edr_email_page', // page
			'edr_email_templates', // section
			array(
				'name'           => 'template',
				'settings_group' => 'edr_membership_renew',
				'description'    => sprintf( __( 'Placeholders: %s', 'educator' ), '{student_name}, {membership}, {membership_payment_url}, {login_link}' ),
			)
		);

		register_setting(
			'edr_email_settings', // option group
			'edr_email',
			array( $this, 'validate' )
		);

		register_setting(
			'edr_email_settings', // option group
			'edr_student_registered',
			array( $this, 'validate_email_template' )
		);

		register_setting(
			'edr_email_settings', // option group
			'edr_quiz_grade',
			array( $this, 'validate_email_template' )
		);

		register_setting(
			'edr_email_settings', // option group
			'edr_membership_register',
			array( $this, 'validate_email_template' )
		);

		register_setting(
			'edr_email_settings', // option group
			'edr_membership_renew',
			array( $this, 'validate_email_template' )
		);
	}

	/**
	 * Validate settings before saving.
	 *
	 * @param array $input
	 * @return array
	 */
	public function validate( $input ) {
		if ( ! is_array( $input ) ) return '';

		$clean = array();

		foreach ( $input as $key => $value ) {
			switch ( $key ) {
				case 'from_name':
					$clean[ $key ] = esc_html( $value );
					break;

				case 'from_email':
					$clean[ $key ] = sanitize_email( $value );
					break;
			}
		}

		return $clean;
	}

	/**
	 * Validate an email template.
	 *
	 * @param string $input
	 * @return string
	 */
	public static function validate_email_template( $input ) {
		return wp_kses_post( $input );
	}

	/**
	 * Add the tab to the tabs on the settings admin page.
	 *
	 * @param array $tabs
	 * @return array
	 */
	public function add_tab( $tabs ) {
		$tabs['email'] = __( 'Email', 'educator' );

		return $tabs;
	}

	/**
	 * Output the settings.
	 *
	 * @param string $tab
	 */
	public function settings_page( $tab ) {
		if ( 'email' == $tab ) {
			include EDR_PLUGIN_DIR . 'templates/admin/settings-email.php';
		}
	}
}
