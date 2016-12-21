<?php

class Edr_Admin_Settings_General extends Edr_Admin_Settings_Base {
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
			'edr_pages', // id
			__( 'Pages', 'educator' ),
			array( $this, 'section_description' ),
			'edr_general_page' // page
		);

		// Get pages.
		$tmp_pages = get_pages();
		$pages = array();
		foreach ( $tmp_pages as $page ) {
			$pages[ $page->ID ] = $page->post_title;
		}
		unset( $tmp_pages );

		$student_courses_sc = apply_filters( 'edr_shortcode_tag', 'edr_student_courses' );

		add_settings_field(
			'student_courses_page',
			__( 'Student\'s Courses', 'educator' ),
			array( $this, 'setting_select' ),
			'edr_general_page', // page
			'edr_pages', // section
			array(
				'name'           => 'student_courses_page',
				'settings_group' => 'edr_settings',
				'choices'        => $pages,
				'description'    => sprintf( __( 'This page outputs the student\'s pending, in progress and complete courses. Add the following shortcode to this page: %s', 'educator' ), '[' . esc_html( $student_courses_sc ) . ']' ),
			)
		);

		$payment_sc = apply_filters( 'edr_shortcode_tag', 'edr_payment' );

		add_settings_field(
			'payment_page',
			__( 'Payment', 'educator' ),
			array( $this, 'setting_select' ),
			'edr_general_page', // page
			'edr_pages', // section
			array(
				'name'           => 'payment_page',
				'settings_group' => 'edr_settings',
				'choices'        => $pages,
				'description'    => sprintf( __( 'This page outputs the payment details of the course. Add the following shortcode to this page: %s', 'educator' ), '[' . esc_html( $payment_sc ) . ']' ),
			)
		);

		$memberships_sc = apply_filters( 'edr_shortcode_tag', 'edr_memberships' );

		add_settings_field(
			'edr_memberships_page',
			__( 'Memberships', 'educator' ),
			array( $this, 'setting_select' ),
			'edr_general_page', // page
			'edr_pages', // section
			array(
				'name'           => 'memberships_page',
				'settings_group' => 'edr_settings',
				'choices'        => $pages,
				'description'    => sprintf( __( 'This page outputs all memberships. Shortcode: %s', 'educator' ), '[' . esc_html( $memberships_sc ) . ']' ),
			)
		);

		$user_membership_sc = apply_filters( 'edr_shortcode_tag', 'edr_user_membership' );

		add_settings_field(
			'edr_user_membership_page',
			__( 'User\'s Membership', 'educator' ),
			array( $this, 'setting_select' ),
			'edr_general_page', // page
			'edr_pages', // section
			array(
				'name'           => 'user_membership_page',
				'settings_group' => 'edr_settings',
				'choices'        => $pages,
				'description'    => sprintf( __( 'This page outputs the membership settings for the current user. Shortcode: %s', 'educator' ), '[' . esc_html( $user_membership_sc ) . ']' ),
			)
		);

		$user_payments_sc = apply_filters( 'edr_shortcode_tag', 'edr_user_payments' );

		add_settings_field(
			'edr_user_payments_page',
			__( 'User\'s Payments', 'educator' ),
			array( $this, 'setting_select' ),
			'edr_general_page', // page
			'edr_pages', // section
			array(
				'name'           => 'user_payments_page',
				'settings_group' => 'edr_settings',
				'choices'        => $pages,
				'description'    => sprintf( __( 'This page outputs the user\'s payments. Shortcode: %s', 'educator' ), '[' . esc_html( $user_payments_sc ) . ']' ),
			)
		);

		// Selling settings.
		add_settings_section(
			'edr_selling', // id
			__( 'Selling', 'educator' ),
			array( $this, 'section_description' ),
			'edr_general_page' // page
		);

		// Location.
		add_settings_field(
			'edr_location',
			__( 'Location', 'educator' ),
			array( $this, 'setting_location' ),
			'edr_general_page', // page
			'edr_selling', // section
			array(
				'name'           => 'location',
				'settings_group' => 'edr_settings',
				'description'    => __( 'The location where you sell from.', 'educator' ),
			)
		);

		// Currency settings.
		add_settings_section(
			'edr_currency', // id
			__( 'Currency', 'educator' ),
			array( $this, 'section_description' ),
			'edr_general_page' // page
		);

		// Currency.
		add_settings_field(
			'currency',
			__( 'Currency', 'educator' ),
			array( $this, 'setting_select' ),
			'edr_general_page', // page
			'edr_currency', // section
			array(
				'name'           => 'currency',
				'settings_group' => 'edr_settings',
				'choices'        => edr_get_currencies(),
			)
		);

		// Currency position.
		add_settings_field(
			'currency_position',
			__( 'Currency Position', 'educator' ),
			array( $this, 'setting_select' ),
			'edr_general_page', // page
			'edr_currency', // section
			array(
				'name'           => 'currency_position',
				'settings_group' => 'edr_settings',
				'choices'        => array(
					'before' => __( 'Before', 'educator' ),
					'after'  => __( 'After', 'educator' ),
				),
			)
		);

		// Decimal point separator.
		add_settings_field(
			'decimal_point',
			__( 'Decimal Point Separator', 'educator' ),
			array( $this, 'setting_text' ),
			'edr_general_page', // page
			'edr_currency', // section
			array(
				'name'           => 'decimal_point',
				'settings_group' => 'edr_settings',
				'size'           => 3,
				'default'        => '.',
			)
		);

		// Thousands separator.
		add_settings_field(
			'thousands_sep',
			__( 'Thousands Separator', 'educator' ),
			array( $this, 'setting_text' ),
			'edr_general_page', // page
			'edr_currency', // section
			array(
				'name'           => 'thousands_sep',
				'settings_group' => 'edr_settings',
				'size'           => 3,
				'default'        => ',',
			)
		);

		register_setting(
			'edr_general_settings', // option group
			'edr_settings',
			array( $this, 'validate' )
		);
	}

	/**
	 * The description of the section.
	 *
	 * @param array $args
	 */
	public function section_description( $args ) {
		if ( is_array( $args ) && isset( $args['id'] ) ) {
			switch ( $args['id'] ) {
				case 'edr_pages':
					?>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row"><?php _e( 'Courses Archive', 'educator' ); ?></th>
								<td>
									<?php
										$archive_link = get_post_type_archive_link( EDR_PT_COURSE );

										if ( $archive_link ) {
											echo '<a href="' . esc_url( $archive_link ) . '" target="_blank">' . esc_url( $archive_link ) . '</a>';
										}
									?>
								</td>
							</tr>
						</tbody>
					</table>
					<?php
					break;
			}
		}
	}

	/**
	 * Validate settings before saving.
	 *
	 * @param array $input
	 * @return array
	 */
	public function validate( $input ) {
		$clean = array();

		foreach ( $input as $key => $value ) {
			switch ( $key ) {
				case 'student_courses_page':
				case 'payment_page':
				case 'memberships_page':
				case 'user_membership_page':
				case 'user_payments_page':
					$clean[ $key ] = intval( $value );
					break;

				case 'currency':
					if ( array_key_exists( $input[ $key ], edr_get_currencies() ) ) {
						$clean[ $key ] = $input[ $key ];
					}
					break;

				case 'currency_position':
					if ( in_array( $value, array( 'before', 'after' ) ) ) {
						$clean[ $key ] = $value;
					}
					break;

				case 'decimal_point':
				case 'thousands_sep':
					$clean[ $key ] = preg_replace( '/[^,. ]/', '', $value );
					break;

				case 'location':
					$clean[ $key ] = sanitize_text_field( $value );
					break;
			}
		}

		return $clean;
	}

	/**
	 * Add the tab to the tabs on the settings admin page.
	 *
	 * @param array $tabs
	 * @return array
	 */
	public function add_tab( $tabs ) {
		$tabs['general'] = __( 'General', 'educator' );

		return $tabs;
	}

	/**
	 * Output the settings.
	 *
	 * @param string $tab
	 */
	public function settings_page( $tab ) {
		if ( empty( $tab ) || 'general' == $tab ) {
			include EDR_PLUGIN_DIR . 'templates/admin/settings-general.php';
		}
	}
}
