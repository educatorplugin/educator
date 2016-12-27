<?php

class Edr_Admin_Ajax {
	public function __construct() {
		add_action( 'wp_ajax_edr_taxes', array( $this, 'ajax_taxes' ) );
	}

	public function ajax_taxes() {
		if ( ! isset( $_GET['method'] ) ) {
			return;
		}

		// Check capability.
		if ( ! current_user_can( 'manage_educator' ) ) {
			return;
		}

		switch ( $_GET['method'] ) {
			case 'add-tax-class':
			case 'edit-tax-class':
				$input = json_decode( file_get_contents( 'php://input' ), true );

				if ( $input ) {
					if ( empty( $input['_wpnonce'] ) || ! wp_verify_nonce( $input['_wpnonce'], 'edr_tax_rates' ) ) {
						return;
					}

					$tax_manager = Edr_TaxManager::get_instance();

					// Get and sanitize input.
					$data = $tax_manager->sanitize_tax_class( $input );

					if ( is_wp_error( $data ) ) {
						http_response_code( 400 );
						return;
					}

					// Save the tax class.
					$tax_manager->add_tax_class( $data );

					echo json_encode( $data );
				}
				break;

			case 'delete-tax-class':
				if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'edr_tax_rates' ) ) {
					http_response_code( 400 );
					return;
				}

				if ( ! isset( $_GET['name'] ) || 'default' == $_GET['name'] ) {
					http_response_code( 400 );
					return;
				}

				Edr_TaxManager::get_instance()->delete_tax_class( $_GET['name'] );
				break;

			case 'rates':
				switch ( $_SERVER['REQUEST_METHOD'] ) {
					case 'POST':
					case 'PUT':
						$input = json_decode( file_get_contents( 'php://input' ) );

						if ( $input ) {
							if ( empty( $input->_wpnonce ) || ! wp_verify_nonce( $input->_wpnonce, 'edr_tax_rates' ) ) {
								return;
							}

							$tax_manager = Edr_TaxManager::get_instance();
							$input = $tax_manager->sanitize_tax_rate( $input );
							$input->ID = $tax_manager->update_tax_rate( $input );

							echo json_encode( $input );
						}
						break;

					case 'GET':
						if ( empty( $_GET['class_name'] ) ) {
							return;
						}

						$class_name = preg_replace( '/[^a-zA-Z0-9-_]+/', '', $_GET['class_name'] );
						$tax_manager = Edr_TaxManager::get_instance();
						$rates = $tax_manager->get_tax_rates( $class_name );
						$edr_countries = Edr_Countries::get_instance();
						$countries = $edr_countries->get_countries();

						if ( ! empty( $rates ) ) {
							foreach ( $rates as $key => $rate ) {
								$rate = $tax_manager->sanitize_tax_rate( $rate, 'lite' );

								// Get country name.
								if ( $rate->country ) {
									if ( isset( $countries[ $rate->country ] ) ) {
										$rates[ $key ]->country_name = $countries[ $rate->country ];
									}
								}

								// Get state name.
								if ( $rate->state ) {
									$states = $edr_countries->get_states( $rate->country );

									if ( isset( $states[ $rate->state ] ) ) {
										$rates[ $key ]->state_name = $states[ $rate->state ];
									} else {
										$rates[ $key ]->state_name = $rate->state;
									}
								}
							}

							header( 'Content-Type: application/json' );
							echo json_encode( $rates );
						}
						break;

					case 'DELETE':
						if ( empty( $_GET['ID'] ) || empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'edr_tax_rates' ) ) {
							return;
						}

						Edr_TaxManager::get_instance()->delete_tax_rate( $_GET['ID'] );
						break;
				}
				break;

			case 'save-rates-order':
				if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'edr_tax_rates' ) ) {
					return;
				}

				if ( ! isset( $_POST['order'] ) || ! is_array( $_POST['order'] ) ) {
					return;
				}

				global $wpdb;
				$tables = edr_db_tables();

				foreach ( $_POST['order'] as $id => $order ) {
					if ( ! is_numeric( $id ) || ! is_numeric( $order ) ) {
						continue;
					}

					$wpdb->update( $tables['tax_rates'], array( 'rate_order' => $order ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
				}
				break;
		}

		exit();
	}
}
