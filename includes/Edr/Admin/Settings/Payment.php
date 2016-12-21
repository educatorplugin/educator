<?php

class Edr_Admin_Settings_Payment extends Edr_Admin_Settings_Base {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'edr_settings_tabs', array( $this, 'add_tab' ) );
		add_action( 'edr_settings_page', array( $this, 'settings_page' ) );
	}

	/**
	 * Add the tab to the tabs on the settings admin page.
	 *
	 * @param array $tabs
	 * @return array
	 */
	public function add_tab( $tabs ) {
		$tabs['payment'] = __( 'Payment Gateways', 'educator' );

		return $tabs;
	}
	
	/**
	 * Output the settings.
	 *
	 * @param string $tab
	 */
	public function settings_page( $tab ) {
		if ( 'payment' == $tab ) {
			include EDR_PLUGIN_DIR . 'templates/admin/settings-payment.php';
		}
	}
}
