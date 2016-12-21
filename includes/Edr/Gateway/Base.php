<?php

abstract class Edr_Gateway_Base {
	/**
	 * @var string
	 */
	protected $id = '';

	/**
	 * @var string
	 */
	protected $title = '';

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * @var array
	 */
	protected $values = array();

	/**
	 * @var bool
	 */
	protected $editable = true;

	/**
	 * Get gateway id.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get gateway title.
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Is the gateway set as default?
	 *
	 * @return int
	 */
	public function is_default() {
		return $this->get_option( 'default' );
	}

	/**
	 * Is the current enabled?
	 *
	 * @return int
	 */
	public function is_enabled() {
		return $this->get_option( 'enabled' );
	}

	/**
	 * Is the current gateway editable?
	 *
	 * @return bool
	 */
	public function is_editable() {
		return $this->editable;
	}

	/**
	 * Initialize gateway options.
	 *
	 * @param array $options
	 */
	public function init_options( $options = array() ) {
		$this->options['enabled'] = array(
			'type'  => 'checkbox',
			'label' => __( 'Enabled', 'educator' ),
			'id'    => 'gateway-enabled',
		);
		$this->options['default'] = array(
			'type'  => 'checkbox',
			'label' => __( 'Default', 'educator' ),
			'id'    => 'gateway-default',
		);
		$this->options = array_merge( $this->options, $options );
		$values = get_option( 'edr_payment_gateways', array() );
		$this->values = isset( $values[ $this->id ] ) ? $values[ $this->id ] : array();
	}

	/**
	 * Get gateway options.
	 *
	 * @param string $option_name
	 * @return mixed
	 */
	public function get_option( $option_name ) {
		if ( isset( $this->values[ $option_name ] ) ) {
			return $this->values[ $option_name ];
		}

		return null;
	}

	/**
	 * Save gateway options.
	 *
	 * @return boolean
	 */
	public function save_admin_options() {
		if ( ! $this->is_editable() ) {
			return false;
		}

		if ( ! count( $_POST ) ) {
			return false;
		}

		$input = array();
		$gateways_options = get_option( 'edr_payment_gateways', array() );

		foreach ( $this->options as $option_name => $data ) {
			$value = isset( $_POST[ 'edr_' . $this->id . '_' . $option_name ] )
				? $_POST[ 'edr_' . $this->id . '_' . $option_name ] : '' ;

			if ( 'checkbox' == $data['type'] ) {
				if ( $value != 1 ) {
					$value = 0;
				}
			}

			if ( 'default' == $option_name && $value == 1 ) {
				// Clear "default" option from other gateways.
				foreach ( $gateways_options as $gateway_id => $options ) {
					$gateways_options[ $gateway_id ]['default'] = 0;
				}
			}

			$input[ $option_name ] = $value;
		}
		
		$gateways_options[ $this->id ] = $this->values = $this->sanitize_admin_options( $input );

		return update_option( 'edr_payment_gateways', $gateways_options );
	}

	/**
	 * Output gateway options form.
	 */
	public function admin_options_form() {
		if ( ! $this->is_editable() ) {
			return;
		}

		$form = new Edr_Form();

		$form->default_decorators();

		foreach ( $this->options as $name => $data ) {
			$data['name'] = 'edr_' . $this->id . '_' . $name;
			$form->set_value( $data['name'], $this->get_option( $name ) );
			$form->add( $data );
		}

		$form->display();
	}
	
	/**
	 * Sanitize gateway options.
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitize_admin_options( $input ) {
		return $input;
	}

	/**
	 * Process payment.
	 *
	 * @param int $object_id ID of the object the payment is to be associated with.
	 * @param int $user_id
	 * @param string $payment_type
	 */
	public function process_payment( $object_id, $user_id, $payment_type, $atts = array() ) {}

	/**
	 * Create payment.
	 *
	 * @param int $object_id ID of the object the payment is to be associated with.
	 * @param int $user_id
	 * @param string $payment_type
	 * @return Edr_Payment
	 */
	public function create_payment( $object_id, $user_id, $payment_type, $atts = array() ) {
		$payment = edr_get_payment();
		$payment->user_id = $user_id;
		$payment->payment_type = $payment_type;
		$payment->payment_status = 'pending';
		$payment->payment_gateway = $this->get_id();
		$payment->currency = edr_get_currency();
		$payment->object_id = $object_id;

		if ( 'course' == $payment_type ) {
			$payment->amount = Edr_Courses::get_instance()->get_course_price( $object_id );
		} elseif ( 'membership' == $payment_type ) {
			$payment->amount = Edr_Memberships::get_instance()->get_price( $object_id );
		}

		$tax_data = null;

		if ( edr_collect_billing_data( $object_id ) ) {
			// Save billing data.
			$billing = get_user_meta( $user_id, '_edr_billing', true );

			if ( ! is_array( $billing ) ) {
				$billing = array();
			}

			$payment->first_name = get_user_meta( $user_id, 'first_name', true );
			$payment->last_name  = get_user_meta( $user_id, 'last_name', true );
			$payment->address    = isset( $billing['address'] ) ? $billing['address'] : '';
			$payment->address_2  = isset( $billing['address_2'] ) ? $billing['address_2'] : '';
			$payment->city       = isset( $billing['city'] ) ? $billing['city'] : '';
			$payment->state      = isset( $billing['state'] ) ? $billing['state'] : '';
			$payment->postcode   = isset( $billing['postcode'] ) ? $billing['postcode'] : '';
			$payment->country    = isset( $billing['country'] ) ? $billing['country'] : '';

			// Calculate tax.
			$tax_manager = Edr_TaxManager::get_instance();
			$tax_data = $tax_manager->calculate_tax( $tax_manager->get_tax_class_for( $object_id ),
				$payment->amount, $payment->country, $payment->state );
			$payment->tax = $tax_data['tax'];
			$payment->amount = $tax_data['total'];
		}
		
		if ( ! empty( $atts['ip'] ) ) {
			$payment->ip = inet_pton( $atts['ip'] );
		}

		$payment->save();

		// Save tax data.
		if ( $tax_data ) {
			foreach ( $tax_data['taxes'] as $tax ) {
				$line = new stdClass();
				$line->object_id = $tax->ID;
				$line->line_type = 'tax';
				$line->amount    = $tax->amount;
				$line->name      = $tax->name;

				$payment->update_line( $line );
			}
		}

		return $payment;
	}

	/**
	 * Get the url to the "thank you" page.
	 *
	 * @param array $args
	 * @return string
	 */
	public function get_redirect_url( $args ) {
		if ( ! isset( $args['value'] ) ) {
			$args['value'] = '';
		}

		$payment_page_url = get_permalink( edr_get_page_id( 'payment' ) );
		$redirect_url = edr_get_endpoint_url( 'edr-payment', $args['value'], $payment_page_url );

		return $redirect_url;
	}
}
