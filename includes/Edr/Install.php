<?php

class Edr_Install {
	/**
	 * @var string
	 */
	private $payments;

	/**
	 * @var string
	 */
	private $entries;

	/**
	 * @var string
	 */
	private $questions;

	/**
	 * @var string
	 */
	private $choices;

	/**
	 * @var string
	 */
	private $answers;

	/**
	 * @var string
	 */
	private $grades;

	public function __construct() {
		$tables = edr_db_tables();
		$this->payments      = $tables['payments'];
		$this->entries       = $tables['entries'];
		$this->questions     = $tables['questions'];
		$this->choices       = $tables['choices'];
		$this->answers       = $tables['answers'];
		$this->grades        = $tables['grades'];
		$this->members       = $tables['members'];
		$this->tax_rates     = $tables['tax_rates'];
		$this->payment_lines = $tables['payment_lines'];
		$this->entry_meta    = $tables['entry_meta'];
	}

	/**
	 * Install.
	 *
	 * @param bool $inc_post_types
	 * @param bool $inc_endpoints
	 */
	public function activate( $inc_post_types = true, $inc_endpoints = true ) {
		// Setup the database tables.
		$this->setup_tables();

		// Setup the user roles and capabilities.
		$this->setup_roles();

		// Post types and taxonomies.
		if ( $inc_post_types || $inc_endpoints ) {
			if ( $inc_post_types ) {
				Edr_PostTypes::register_post_types();
				Edr_PostTypes::register_taxonomies();
			}

			if ( $inc_endpoints ) {
				Edr_Main::get_instance()->add_rewrite_endpoints();
			}

			flush_rewrite_rules();
		}

		// Setup email templates.
		$this->setup_email_templates();

		// Schedule cron events.
		$this->schedule_events();

		// Setup tax settings.
		$this->setup_taxes();

		// Plugin activation hook.
		do_action( 'edr_activation' );

		// Update the plugin version in database.
		update_option( 'edr_version', EDR_VERSION );
	}

	/**
	 * Plugin deactivation cleanup.
	 */
	public function deactivate() {
		$this->remove_scheduled_events();
		flush_rewrite_rules();
	}

	/**
	 * Schedule CRON events.
	 */
	public function schedule_events() {
		// CRON: process expired memberships.
		if ( ! wp_next_scheduled( 'edr_expired_memberships' ) ) {
			wp_schedule_event( strtotime( date( 'Y-m-d 00:00:00' ) ), 'daily', 'edr_expired_memberships' );
		}

		// CRON: send the membership expiration notifications.
		if ( ! wp_next_scheduled( 'edr_membership_notifications' ) ) {
			wp_schedule_event( strtotime( date( 'Y-m-d 00:00:00' ) ), 'daily', 'edr_membership_notifications' );
		}
	}

	/**
	 * Remove scheduled CRON events.
	 */
	public function remove_scheduled_events() {
		wp_clear_scheduled_hook( 'edr_expired_memberships' );
		wp_clear_scheduled_hook( 'edr_membership_notifications' );
	}

	/**
	 * Setup database tables.
	 */
	public function setup_tables() {
		global $wpdb;
		$installed_ver = get_option( 'edr_db_version' );

		if ( $installed_ver != EDR_DB_VERSION ) {
			$charset_collate = '';

			if ( $wpdb->has_cap( 'collation' ) ) {
				if ( ! empty( $wpdb->charset ) ) {
					$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
				}
				
				if ( ! empty( $wpdb->collate ) ) {
					$charset_collate .= " COLLATE $wpdb->collate";
				}
			}

			// Entries and payments.
			$sql = "CREATE TABLE $this->entries (
  ID bigint(20) unsigned NOT NULL auto_increment,
  course_id bigint(20) unsigned NOT NULL,
  user_id bigint(20) unsigned NOT NULL,
  payment_id bigint(20) unsigned NOT NULL,
  object_id bigint(20) unsigned NOT NULL,
  grade decimal(5,2) unsigned NOT NULL,
  entry_origin varchar(20) NOT NULL default 'payment',
  entry_status varchar(20) NOT NULL,
  entry_date datetime NOT NULL default '0000-00-00 00:00:00',
  complete_date datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (ID),
  KEY record_status (entry_status),
  KEY user_id (user_id),
  KEY course_id (course_id)
) $charset_collate;
CREATE TABLE $this->payments (
  ID bigint(20) unsigned NOT NULL auto_increment,
  parent_id bigint(20) unsigned NOT NULL,
  user_id bigint(20) unsigned NOT NULL,
  course_id bigint(20) unsigned NOT NULL,
  object_id bigint(20) unsigned default NULL,
  payment_type varchar(20) NOT NULL default 'course',
  payment_gateway varchar(20) NOT NULL,
  payment_status varchar(20) NOT NULL,
  txn_id varchar(20) NOT NULL default '',
  amount decimal(8, 2) NOT NULL,
  tax decimal(10, 4) NOT NULL default 0.0000,
  currency char(3) NOT NULL,
  payment_date datetime NOT NULL default '0000-00-00 00:00:00',
  first_name varchar(255) NOT NULL default '',
  last_name varchar(255) NOT NULL default '',
  address varchar(255) NOT NULL default '',
  address_2 varchar(255) NOT NULL default '',
  city varchar(255) NOT NULL default '',
  state varchar(255) NOT NULL default '',
  postcode varchar(32) NOT NULL default '',
  country char(2) NOT NULL default '',
  ip varbinary(16) NOT NULL default '',
  PRIMARY KEY  (ID),
  KEY user_id (user_id),
  KEY course_id (course_id),
  KEY object_id (object_id),
  KEY parent_id (parent_id),
  KEY txn_id (txn_id)
) $charset_collate;
CREATE TABLE $this->payment_lines (
  ID bigint(20) unsigned NOT NULL auto_increment,
  payment_id bigint(20) unsigned NOT NULL,
  object_id bigint(20) unsigned NOT NULL,
  line_type enum('','tax','item') NOT NULL default '',
  amount decimal(10, 4) NOT NULL default 0.0000,
  tax decimal(10, 4) NOT NULL default 0.0000,
  name text NOT NULL,
  PRIMARY KEY  (ID),
  KEY payment_id (payment_id),
  KEY line_type (line_type)
) $charset_collate;
CREATE TABLE $this->tax_rates (
  ID mediumint unsigned NOT NULL auto_increment,
  tax_class varchar(128) NOT NULL,
  name varchar(128) NOT NULL,
  country char(2) NOT NULL,
  state varchar(128) NOT NULL,
  rate decimal(6,4) NOT NULL,
  priority mediumint unsigned NOT NULL default 0,
  rate_order mediumint unsigned NOT NULL default 0,
  PRIMARY KEY  (ID),
  KEY tax_class (tax_class),
  KEY rate_order (rate_order)
) $charset_collate;
CREATE TABLE $this->questions (
  ID bigint(20) unsigned NOT NULL auto_increment,
  lesson_id bigint(20) unsigned NOT NULL,
  question text default NULL,
  question_type enum('','multiplechoice', 'writtenanswer', 'fileupload'),
  question_content longtext default NULL,
  optional tinyint NOT NULL default 0,
  menu_order int(10) NOT NULL default 0,
  PRIMARY KEY  (ID),
  KEY lesson_id (lesson_id)
) $charset_collate;
CREATE TABLE $this->choices (
  ID bigint(20) unsigned NOT NULL auto_increment,
  question_id bigint(20) unsigned NOT NULL,
  choice_text text default NULL,
  correct tinyint(1) NOT NULL,
  menu_order tinyint(3) unsigned NOT NULL default 0,
  PRIMARY KEY  (ID),
  KEY question_id (question_id),
  KEY menu_order (menu_order)
) $charset_collate;
CREATE TABLE $this->answers (
  ID bigint(20) unsigned NOT NULL auto_increment,
  question_id bigint(20) unsigned NOT NULL,
  grade_id bigint(20) unsigned NOT NULL,
  entry_id bigint(20) unsigned NOT NULL,
  choice_id bigint(20) unsigned NOT NULL,
  correct tinyint(2) NOT NULL default -1,
  answer_text text default NULL,
  PRIMARY KEY  (ID),
  KEY entry_id (entry_id),
  KEY grade_id (grade_id)
) $charset_collate;
CREATE TABLE $this->grades (
  ID bigint(20) unsigned NOT NULL auto_increment,
  lesson_id bigint(20) unsigned NOT NULL,
  entry_id bigint(20) unsigned NOT NULL,
  user_id bigint(20) unsigned NOT NULL,
  grade decimal(5,2) unsigned NOT NULL,
  status enum('pending','approved','draft') NOT NULL default 'pending',
  PRIMARY KEY  (ID),
  KEY lesson_id (lesson_id),
  KEY entry_id (entry_id)
) $charset_collate;
CREATE TABLE $this->members (
  ID bigint(20) unsigned NOT NULL auto_increment,
  user_id bigint(20) unsigned NOT NULL,
  membership_id bigint(20) unsigned NOT NULL,
  status varchar(20) NOT NULL default '',
  expiration datetime NOT NULL default '0000-00-00 00:00:00',
  paused datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (ID),
  KEY user_id (user_id),
  KEY status (status),
  KEY expiration (expiration)
) $charset_collate;
CREATE TABLE $this->entry_meta (
  meta_id bigint(20) unsigned NOT NULL auto_increment,
  edr_entry_id bigint(20) unsigned NOT NULL,
  meta_key varchar(255) NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY entry_id (edr_entry_id),
  KEY meta_key (meta_key)
) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
			update_option( 'edr_db_version', EDR_DB_VERSION );
		}
	}

	/**
	 * Setup user roles and capabilities.
	 */
	public function setup_roles() {
		global $wp_roles;

		if ( isset( $wp_roles ) && is_object( $wp_roles ) ) {
			// Lecturer role.
			add_role( 'lecturer', __( 'Lecturer', 'educator' ), array(
				'read' => true,
			) );

			// Student role.
			add_role( 'student', __( 'Student', 'educator' ), array(
				'read' => true,
			) );

			// Assign capabilities to administrator.
			$all_capabilities = $this->get_role_capabilities( 'administrator' );

			foreach ( $all_capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->add_cap( 'administrator', $cap );
				}
			}

			// Assign capabilities to lecturer.
			$lecturer_capabilities = $this->get_role_capabilities( 'lecturer' );

			foreach ( $lecturer_capabilities as $cap_group ) {
				foreach ( $cap_group as $cap ) {
					$wp_roles->add_cap( 'lecturer', $cap );
				}
			}
		}
	}

	/**
	 * Get the capabilities for a given role.
	 *
	 * @param string $role
	 * @return array
	 */
	public function get_role_capabilities( $role ) {
		$capabilities = array();

		switch ( $role ) {
			// ROLE: administrator.
			case 'administrator':
				// Various capabilities.
				$capabilities['core'] = array(
					'manage_educator',
					'educator_edit_entries',
					'edr_edit_quiz_grades_all',
				);

				// Capabilities for custom post types.
				$capability_types = array( EDR_PT_COURSE, EDR_PT_LESSON, EDR_PT_MEMBERSHIP );

				// Post types capabilities.
				foreach ( $capability_types as $capability_type ) {
					$capabilities[ $capability_type ] = array(
						"edit_{$capability_type}",
						"read_{$capability_type}",
						"delete_{$capability_type}",
						"edit_{$capability_type}s",
						"edit_others_{$capability_type}s",
						"publish_{$capability_type}s",
						"read_private_{$capability_type}s",
						"delete_{$capability_type}s",
						"delete_private_{$capability_type}s",
						"delete_published_{$capability_type}s",
						"delete_others_{$capability_type}s",
						"edit_private_{$capability_type}s",
						"edit_published_{$capability_type}s",
					);
				}
				break;

			// ROLE: lecturer.
			case 'lecturer':
				// Various capabilities.
				$capabilities['core'] = array(
					'read',
					'upload_files',
					'educator_edit_entries',
					'edr_edit_quiz_grades_own',
					'level_2',
				);

				// Course capabilities.
				$capabilities[EDR_PT_COURSE] = array(
					'edit_' . EDR_PT_COURSE . 's',
					'publish_' . EDR_PT_COURSE . 's',
					'delete_' . EDR_PT_COURSE . 's',
					'delete_published_' . EDR_PT_COURSE . 's',
					'edit_published_' . EDR_PT_COURSE . 's',
				);

				// Lesson capabilities.
				$capabilities[EDR_PT_LESSON] = array(
					'edit_' . EDR_PT_LESSON . 's',
					'publish_' . EDR_PT_LESSON . 's',
					'delete_' . EDR_PT_LESSON . 's',
					'delete_published_' . EDR_PT_LESSON . 's',
					'edit_published_' . EDR_PT_LESSON . 's',
				);

				// Allow lecturers to add posts, administrator has to approve these posts.
				$capabilities['post'] = array(
					'delete_posts',
					'edit_posts',
				);
				break;
		}

		return $capabilities;
	}

	/**
	 * Setup email templates.
	 */
	public function setup_email_templates() {
		if ( ! get_option( 'edr_student_registered' ) ) {
			update_option( 'edr_student_registered', array(
				'subject'  => sprintf( __( 'Registration for %s is complete', 'educator' ), '{course_title}' ),
				'template' => 'Dear {student_name},

You\'ve got access to {course_title}.

{course_excerpt}

Log in: {login_link}

Best regards,
Administration',
			) );
		}

		if ( ! get_option( 'edr_quiz_grade' ) ) {
			update_option( 'edr_quiz_grade', array(
				'subject' => __( 'You\'ve got a grade', 'educator' ),
				'template' => 'Dear {student_name},

You\'ve got {grade} for {lesson_title}.

Log in: {login_link}

Best regards,
Administration',
			) );
		}

		if ( ! get_option( 'edr_membership_register' ) ) {
			update_option( 'edr_membership_register', array(
				'subject' => __( 'You\'ve been registered for a membership', 'educator' ),
				'template' => 'Dear {student_name},

Thank you for registering for the {membership} membership.

Membership: {membership}
Expiration: {expiration}
Price: {price}

Log in: {login_link}

Best regards,
Administration',
			) );
		}

		if ( ! get_option( 'edr_membership_renew' ) ) {
			update_option( 'edr_membership_renew', array(
				'subject' => __( 'Your membership expires', 'educator' ),
				'template' => 'Dear {student_name},

Your {membership} membership expires on {expiration}.

Please renew your membership: {membership_payment_url}

Log in: {login_link}

Best regards,
Administration',
			) );
		}
	}

	/**
	 * Setup default tax settings.
	 */
	public function setup_taxes() {
		$classes = get_option( 'edr_tax_classes' );

		if ( ! is_array( $classes ) ) {
			$classes = array();
		}

		if ( ! isset( $classes['default'] ) ) {
			$classes['default'] = 'Default';
		}

		update_option( 'edr_tax_classes', $classes );
	}
}
