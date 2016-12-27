<?php

class Edr_Admin_Settings_Taxes extends Edr_Admin_Settings_Base {
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
			'edr_taxes_settings', // id
			__( 'Tax Settings', 'educator' ),
			array( $this, 'section_description' ),
			'edr_taxes_page' // page
		);

		// Setting: Enable Taxes.
		add_settings_field(
			'edr_taxes_enable',
			__( 'Enable Taxes', 'educator' ),
			array( $this, 'setting_checkbox' ),
			'edr_taxes_page', // page
			'edr_taxes_settings', // section
			array(
				'name'           => 'enable',
				'settings_group' => 'edr_taxes',
				'default'        => 5,
				'id'             => 'edr_taxes_enable',
			)
		);

		// Setting: Prices Entered With Tax.
		add_settings_field(
			'edr_tax_inclusive',
			__( 'Prices Entered With Tax', 'educator' ),
			array( $this, 'setting_select' ),
			'edr_taxes_page', // page
			'edr_taxes_settings', // section
			array(
				'name'           => 'tax_inclusive',
				'settings_group' => 'edr_taxes',
				'default'        => 'y',
				'id'             => 'edr_tax_inclusive',
				'choices'        => array(
					'y' => __( 'Yes', 'educator' ),
					'n' => __( 'No', 'educator' ),
				),
			)
		);

		register_setting(
			'edr_taxes_settings', // option group
			'edr_taxes',
			array( $this, 'validate' )
		);
	}

	/**
	 * Validate settings before saving.
	 *
	 * @param array $input
	 * @return array
	 */
	public function validate( $input ) {
		if ( ! is_array( $input ) ) {
			return '';
		}

		$clean = array();

		foreach ( $input as $key => $value ) {
			switch ( $key ) {
				case 'enable':
					$clean[ $key ] = ( 1 != $value ) ? 0 : 1;
					break;

				case 'tax_inclusive':
					$clean[ $key ] = ( 'y' != $value ) ? 'n' : 'y';
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
		$tabs['taxes'] = __( 'Taxes', 'educator' );

		return $tabs;
	}

	/**
	 * Output the settings.
	 *
	 * @param string $tab
	 */
	public function settings_page( $tab ) {
		if ( 'taxes' == $tab ) {
			include EDR_PLUGIN_DIR . 'templates/admin/settings-taxes.php';
		}
	}
}
