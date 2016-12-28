<?php

/**
 * Educator plugin's admin setup.
 */
class Edr_Admin {
	/**
	 * Initialize admin.
	 */
	public static function init() {
		self::includes();

		add_action( 'current_screen', array( __CLASS__, 'maybe_includes' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 9 );
		add_action( 'admin_init', array( __CLASS__, 'admin_actions' ) );
		add_action( 'admin_init', array( __CLASS__, 'check_uploads_protect_files' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts_styles' ), 9 );
		add_filter( 'set-screen-option', array( __CLASS__, 'set_screen_option' ), 10, 3 );
		add_action( 'init', array( __CLASS__, 'update_check' ), 9 );
	}

	/**
	 * Include the necessary files.
	 */
	public static function includes() {
		new Edr_Admin_Settings_General();
		new Edr_Admin_Settings_Learning();
		new Edr_Admin_Settings_Payment();
		new Edr_Admin_Settings_Taxes();
		new Edr_Admin_Settings_Email();
		new Edr_Admin_Settings_Memberships();
		Edr_Admin_PostTypes::init();
		Edr_Admin_Meta::init();
		Edr_Admin_Quiz::init();
		new Edr_Admin_Syllabus();
		new Edr_AdminNotices();
		new Edr_Admin_Ajax();
	}

	/**
	 * Include the files based on the current screen.
	 *
	 * @param WP_Screen $screen
	 */
	public static function maybe_includes( $screen ) {
		switch ( $screen->id ) {
			case 'options-permalink':
				new Edr_Admin_Settings_Permalink();
				break;
		}
	}

	/**
	 * Setup admin menu.
	 */
	public static function admin_menu() {
		add_menu_page(
			__( 'Educator', 'educator' ),
			__( 'Educator', 'educator' ),
			'manage_educator',
			'edr_admin_settings',
			array( __CLASS__, 'settings_page' ),
			EDR_PLUGIN_URL . '/assets/admin/images/educator-icon.png'
		);

		add_submenu_page(
			'edr_admin_settings',
			__( 'Educator Settings', 'educator' ),
			__( 'Settings', 'educator' ),
			'manage_educator',
			'edr_admin_settings'
		);

		$payments_hook = add_submenu_page(
			'edr_admin_settings',
			__( 'Educator Payments', 'educator' ),
			__( 'Payments', 'educator' ),
			'manage_educator',
			'edr_admin_payments',
			array( __CLASS__, 'admin_payments' )
		);

		if ( $payments_hook ) {
			add_action( "load-$payments_hook", array( __CLASS__, 'add_payments_screen_options' ) );
		}

		$entries_hook = null;
		$quiz_grades_hook = null;

		if ( current_user_can( 'manage_educator' ) ) {
			$entries_hook = add_submenu_page(
				'edr_admin_settings',
				__( 'Educator Entries', 'educator' ),
				__( 'Entries', 'educator' ),
				'manage_educator',
				'edr_admin_entries',
				array( __CLASS__, 'admin_entries' )
			);

			$quiz_grades_hook = add_submenu_page(
				'edr_admin_settings',
				__( 'Quiz Grades', 'educator' ),
				__( 'Quiz Grades', 'educator' ),
				'edr_edit_quiz_grades_all',
				'edr_admin_quiz_grades',
				array( __CLASS__, 'admin_quiz_grades' )
			);
		} elseif ( current_user_can( 'educator_edit_entries' ) ) {
			$entries_hook = add_menu_page(
				__( 'Educator Entries', 'educator' ),
				__( 'Entries', 'educator' ),
				'educator_edit_entries',
				'edr_admin_entries',
				array( __CLASS__, 'admin_entries' )
			);
		}

		if ( $entries_hook ) {
			add_action( "load-$entries_hook", array( __CLASS__, 'add_entries_screen_options' ) );
		}

		if ( $quiz_grades_hook ) {
			add_action( "load-$quiz_grades_hook", array( __CLASS__, 'add_quiz_grades_screen_options' ) );
		}

		$members_hook = add_submenu_page(
			'edr_admin_settings',
			__( 'Educator Members', 'educator' ),
			__( 'Members', 'educator' ),
			'manage_educator',
			'edr_admin_members',
			array( __CLASS__, 'admin_members' )
		);

		if ( $members_hook ) {
			add_action( "load-$members_hook", array( __CLASS__, 'add_members_screen_options' ) );
		}
	}

	/**
	 * Output the settings page.
	 */
	public static function settings_page() {
		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : '';

		do_action( 'edr_settings_page', $tab );
	}

	/**
	 * Process the admin actions.
	 */
	public static function admin_actions() {
		if ( isset( $_GET['edr-action'] ) ) {
			$action = sanitize_key( $_GET['edr-action'] );

			do_action( 'edr_action_' . $action );

			switch ( $action ) {
				case 'edit-entry':
				case 'edit-payment':
				case 'edit-member':
				case 'edit-payment-gateway':
				case 'delete-entry':
				case 'delete-payment':
					$method = str_replace( '-', '_', $action );
					Edr_Admin_Actions::$method();
					break;
			}
		}
	}

	/**
	 * Check and create uploads protection files if
	 * necessary (.htaccess).
	 */
	public static function check_uploads_protect_files() {
		if ( false === get_transient( 'edr_check_uploads_protect_files' ) ) {
			$upload = new Edr_Upload();
			$upload->create_protect_files();

			set_transient( 'edr_check_uploads_protect_files', 1, 3600 * 24 );
		}
	}

	/**
	 * Output payments page.
	 */
	public static function admin_payments() {
		$action = isset( $_GET['edr-action'] ) ? $_GET['edr-action'] : 'payments';

		switch ( $action ) {
			case 'payments':
			case 'edit-payment':
				require( EDR_PLUGIN_DIR . 'templates/admin/' . $action . '.php' );
				break;
		}
	}

	/**
	 * Output entries page.
	 */
	public static function admin_entries() {
		$action = isset( $_GET['edr-action'] ) ? $_GET['edr-action'] : 'entries';

		switch ( $action ) {
			case 'entries':
			case 'edit-entry':
				require( EDR_PLUGIN_DIR . 'templates/admin/' . $action . '.php' );
				break;
		}
	}

	/**
	 * Output quiz grades page.
	 */
	public static function admin_quiz_grades() {
		$action = isset( $_GET['edr-action'] ) ? $_GET['edr-action'] : 'quiz-grades';

		switch ( $action ) {
			case 'quiz-grades':
			case 'edit-quiz-grade':
				require( EDR_PLUGIN_DIR . 'templates/admin/' . $action . '.php' );
				break;
		}
	}

	/**
	 * Add screen options to the payments admin page.
	 */
	public static function add_payments_screen_options() {
		$screen = get_current_screen();

		if ( ! $screen || 'educator_page_edr_admin_payments' != $screen->id || isset( $_GET['edr-action'] ) ) {
			return;
		}

		$args = array(
			'option'  => 'payments_per_page',
			'label'   => __( 'Payments per page', 'educator' ),
			'default' => 10,
		);

		add_screen_option( 'per_page', $args );
	}

	/**
	 * Add screen options to the entries admin page.
	 */
	public static function add_entries_screen_options() {
		$screen = get_current_screen();

		if ( ! $screen || 'educator_page_edr_admin_entries' != $screen->id || isset( $_GET['edr-action'] ) ) {
			return;
		}

		$args = array(
			'option'  => 'entries_per_page',
			'label'   => __( 'Entries per page', 'educator' ),
			'default' => 10,
		);

		add_screen_option( 'per_page', $args );
	}

	/**
	 * Add screen options to the quiz grades admin page.
	 */
	public static function add_quiz_grades_screen_options() {
		$screen = get_current_screen();

		if ( ! $screen || 'educator_page_edr_admin_quiz_grades' != $screen->id || isset( $_GET['edr-action'] ) ) {
			return;
		}

		$args = array(
			'option'  => 'quiz_grades_per_page',
			'label'   => __( 'Grades per page', 'educator' ),
			'default' => 10,
		);

		add_screen_option( 'per_page', $args );
	}

	/**
	 * Add screen options to the members admin page.
	 */
	public static function add_members_screen_options() {
		$screen = get_current_screen();

		if ( ! $screen || 'educator_page_edr_admin_members' != $screen->id || isset( $_GET['edr-action'] ) ) {
			return;
		}

		$args = array(
			'option'  => 'members_per_page',
			'label'   => __( 'Members per page', 'educator' ),
			'default' => 10,
		);

		add_screen_option( 'per_page', $args );
	}

	/**
	 * Output Educator members page.
	 */
	public static function admin_members() {
		$action = isset( $_GET['edr-action'] ) ? $_GET['edr-action'] : 'members';

		switch ( $action ) {
			case 'members':
			case 'edit-member':
				require( EDR_PLUGIN_DIR . 'templates/admin/' . $action . '.php' );
				break;
		}
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public static function enqueue_scripts_styles() {
		wp_enqueue_style( 'edr-select', EDR_PLUGIN_URL . 'assets/shared/css/select.css', array(), '1.0' );
		wp_enqueue_style( 'edr-admin', EDR_PLUGIN_URL . 'assets/admin/css/admin.css', array(), '1.0' );

		wp_enqueue_script( 'edr-lib', EDR_PLUGIN_URL . 'assets/admin/js/lib.js', array( 'jquery' ), '1.0', true );
		wp_enqueue_script( 'edr-select', EDR_PLUGIN_URL . 'assets/shared/js/select.js', array( 'jquery' ), '1.0' );

		$screen = get_current_screen();

		if ( $screen ) {
			$action = ! empty( $_GET['edr-action'] ) ? $_GET['edr-action'] : '';

			wp_register_script( 'edr-edit-quiz-grade-form', EDR_PLUGIN_URL . 'assets/admin/js/edit-quiz-grade-form.js',
				array( 'jquery', 'edr-lib' ), '1.0.0', true );

			if ( 'educator_page_edr_admin_payments' == $screen->id ) {
				// Payments.
				wp_enqueue_script( 'postbox' );
				wp_enqueue_script( 'edr-edit-payment', EDR_PLUGIN_URL . 'assets/admin/js/edit-payment.js',
					array( 'jquery' ), '1.0', true );
			} elseif ( 'educator_page_edr_admin_entries' == $screen->id ) {
				// Entries.
				wp_enqueue_script( 'postbox' );

				if ( 'edit-entry' == $action ) {
					wp_enqueue_script( 'edr-edit-quiz-grade-form' );
				}
			} elseif ( 'educator_page_edr_admin_quiz_grades' == $screen->id ) {
				// Quiz grades.
				wp_enqueue_script( 'postbox' );

				if ( 'edit-quiz-grade' == $action ) {
					wp_enqueue_script( 'edr-edit-quiz-grade-form' );
				}
			} elseif ( 'educator_page_edr_admin_members' == $screen->id ) {
				// Members.
				wp_enqueue_script( 'postbox' );
			} elseif ( 'toplevel_page_edr_admin_settings' == $screen->id && isset( $_GET['tab'] ) && 'taxes' == $_GET['tab'] ) {
				// Taxes.
				wp_enqueue_script( 'edr-admin-tax-rates', EDR_PLUGIN_URL . 'assets/admin/js/tax-rates.js',
					array( 'backbone', 'jquery-ui-sortable' ), '1.0.0', true );
			}
		}
	}

	/**
	 * Save screen options for various admin pages.
	 *
	 * @param mixed $result
	 * @param string $option
	 * @param mixed $value
	 * @return mixed
	 */
	public static function set_screen_option( $result, $option, $value ) {
		$pagination_options = array(
			'payments_per_page',
			'entries_per_page',
			'members_per_page',
			'quiz_grades_per_page',
		);

		if ( in_array( $option, $pagination_options ) ) {
			$result = intval( $value );
		}

		return $result;
	}

	/**
	 * Check whether to run the update script or not.
	 */
	public static function update_check() {
		$current_version = get_option( 'edr_version' );

		if ( $current_version != EDR_VERSION ) {
			$install = new Edr_Install();
			$install->activate( false, false );
		}
	}
}
