<?php

class Edr_TaxManager {
	/**
	 * @var Edr_TaxManager
	 */
	protected static $instance = null;

	/**
	 * @var string
	 */
	protected $tbl_tax_rates;

	/**
	 * @var float
	 */
	protected $inclusive_rate;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$tables = edr_db_tables();
		$this->tbl_tax_rates = $tables['tax_rates'];
	}

	/**
	 * Get insntance of this class (singleton).
	 *
	 * @return Edr_TaxManager
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get a tax rate.
	 *
	 * @param string $tax_class
	 * @param string $country Country code.
	 * @param string $state
	 * @return float Percentage tax rate.
	 */
	protected function get_tax_rate( $tax_class, $country, $state = '' ) {
		global $wpdb;
		$rates = array();
		$priorities = array();
		$location = edr_get_location();
		$inclusive = edr_get_option( 'taxes', 'tax_inclusive' );

		if ( ! $inclusive ) {
			$inclusive = 'y';
		}

		$inc_rate = 0.0;
		$inc_priorities = array();
		$results = $wpdb->get_results( $wpdb->prepare(
			"
			SELECT   *
			FROM     $this->tbl_tax_rates
			WHERE    tax_class = %s
			         AND country IN ('', %s, %s)
			         AND state IN ('', %s, %s)
			ORDER BY priority ASC, rate_order ASC
			",
			$tax_class,
			$location[0],
			$country,
			$location[1],
			$state
		) );

		foreach ( $results as $row ) {
			if ( 'y' == $inclusive
				&& ! in_array( $row->priority, $inc_priorities )
				&& ( $location[0] == $row->country || '' == $row->country )
				&& ( $location[1] == $row->state || '' == $row->state ) ) {
				// Calculate inclusive tax rate.
				$inc_rate += $row->rate;
				$inc_priorities[] = $row->priority;
			}

			if ( ! in_array( $row->priority, $priorities )
				&& ( $country == $row->country || '' == $row->country )
				&& ( $state == $row->state || '' == $row->state ) ) {
				// Select tax rates.
				$rates[] = $row;
				$priorities[] = $row->priority;
			}
		}

		if ( $inc_rate ) {
			$this->inclusive_rate = $inc_rate;
		}

		return array(
			'inclusive' => $inc_rate,
			'rates'     => $rates,
		);
	}

	/**
	 * Calculate tax.
	 *
	 * @param string $tax_class
	 * @param float $price
	 * @param string $country
	 * @param string $state
	 * @return array Tax data (tax, subtotal, total).
	 */
	public function calculate_tax( $tax_class, $price, $country, $state ) {
		// Are prices entered with tax?
		$inclusive = edr_get_option( 'taxes', 'tax_inclusive' );

		if ( ! $inclusive ) {
			$inclusive = 'y';
		}

		$rates_data = $this->get_tax_rate( $tax_class, $country, $state );
		$tax_data = array(
			'taxes' => array(),
			'tax'   => 0.0,
		);

		$tax_round_mode = 'y' == $inclusive ? PHP_ROUND_HALF_DOWN : PHP_ROUND_HALF_UP;

		if ( 'y' == $inclusive ) {
			$tax_data['subtotal'] = round( $price / ( 1 + $rates_data['inclusive'] / 100 ), 4 );
			$tax_data['total'] = $tax_data['subtotal'];
		} else {
			$tax_data['subtotal'] = $price;
			$tax_data['total'] = $price;
		}

		foreach ( $rates_data['rates'] as $rate ) {
			// Calculate tax amount.
			$tax = round( $tax_data['subtotal'] * $rate->rate / 100, 4, $tax_round_mode );

			// Setup tax object.
			$tmp = new stdClass;
			$tmp->ID = $rate->ID;
			$tmp->name = $rate->name;
			$tmp->rate = $rate->rate;
			$tmp->amount = $tax;
			$tax_data['taxes'][] = $tmp;

			// Totals.
			$tax_data['tax'] += $tax;
			$tax_data['total'] += $tax;
		}

		return $tax_data;
	}

	/**
	 * Sanitize tax class data.
	 *
	 * @param array $input
	 * @return WP_Error|array
	 */
	public function sanitize_tax_class( $input ) {
		$data = array();
		$errors = new WP_Error();

		if ( empty( $input['name'] ) ) {
			$errors->add( 'name_empty', __( 'Name cannot be empty.', 'educator' ) );
		} else {
			$data['name'] = preg_replace( '/[^a-zA-Z0-9-_]+/', '', $input['name'] );

			if ( empty( $data['name'] ) ) {
				$errors->add( 'name_invalid', __( 'Invalid name.', 'educator' ) );
			}
		}

		if ( empty( $input['description'] ) ) {
			$errors->add( 'description_empty', __( 'Description cannot be empty.', 'educator' ) );
		} else {
			$data['description'] = sanitize_text_field( $input['description'] );
		}

		if ( count( $errors->get_error_messages() ) ) {
			return $errors;
		}

		return $data;
	}

	/**
	 * Sanitize tax rate.
	 *
	 * @param stdClass $input
	 * @param string $context
	 * @return stdClass
	 */
	public function sanitize_tax_rate( $input, $context = 'save' ) {
		$clean = new stdClass();

		foreach ( $input as $key => $value ) {
			switch ( $key ) {
				case 'ID':
				case 'priority':
				case 'rate_order':
					$clean->$key = (int) $value;
					break;
				case 'rate':
					$clean->$key = number_format( (float) $value, 4, '.', '' );
					break;
				case 'tax_class':
				case 'country':
				case 'state':
				case 'name':
					if ( 'save' == $context ) {
						$clean->$key = sanitize_text_field( $value );
					} elseif ( 'lite' == $context ) {
						$clean->$key = $value;
					} elseif ( 'display' == $context ) {
						$clean->$key = esc_html( $value );
					}
					break;
			}
		}

		return $clean;
	}

	/**
	 * Add tax class.
	 *
	 * @param array $tax_class
	 */
	public function add_tax_class( $tax_class ) {
		$classes = $this->get_tax_classes();
		$classes[ $tax_class['name'] ] = $tax_class['description'];
		update_option( 'edr_tax_classes', $classes );
	}

	/**
	 * Delete tax class.
	 *
	 * @param string $name
	 */
	public function delete_tax_class( $name ) {
		// Do not delete default tax class.
		if ( 'default' == $name ) {
			return;
		}

		$classes = $this->get_tax_classes();
		
		if ( isset( $classes[ $name ] ) ) {
			unset( $classes[ $name ] );
			update_option( 'edr_tax_classes', $classes );
		}
	}

	/**
	 * Get tax classes.
	 *
	 * @return array
	 */
	public function get_tax_classes() {
		$classes = get_option( 'edr_tax_classes' );

		return ( is_array( $classes ) ) ? $classes : array();
	}

	/**
	 * Get a tax class name for a given object (course, membership).
	 *
	 * @param int $object_id
	 * @return string
	 */
	public function get_tax_class_for( $object_id ) {
		$tax_class = get_post_meta( $object_id, '_edr_tax_class', true );

		return ( $tax_class ) ? $tax_class : 'default';
	}

	/**
	 * Get tax rates.
	 *
	 * @param string $tax_class
	 * @return array
	 */
	public function get_tax_rates( $tax_class ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare(
			"
			SELECT   *
			FROM     $this->tbl_tax_rates
			WHERE    tax_class = %s
			ORDER BY rate_order ASC
			",
			$tax_class
		) );
	}

	/**
	 * Update tax rate.
	 *
	 * @param stdClass $input
	 * @return int
	 */
	public function update_tax_rate( $input ) {
		global $wpdb;
		$id = isset( $input->ID ) ? intval( $input->ID ) : 0;
		$data = array();
		$format = array();

		foreach ( $input as $key => $value ) {
			switch ( $key ) {
				case 'name':
				case 'country':
				case 'state':
				case 'tax_class':
					$data[ $key ] = $value;
					$format[] = '%s';
					break;
				case 'rate':
					$data[ $key ] = $value;
					$format[] = '%f';
					break;
				case 'priority':
				case 'rate_order':
					$data[ $key ] = $value;
					$format[] = '%d';
					break;
			}
		}

		if ( $id ) {
			$where = array( 'ID' => $id );
			$where_format = array( '%d' );
			$wpdb->update( $this->tbl_tax_rates, $data, $where, $format, $where_format );
		} else {
			$wpdb->insert( $this->tbl_tax_rates, $data, $format );
			$id = $wpdb->insert_id;
		}

		return $id;
	}

	/**
	 * Delete a tax rate.
	 *
	 * @param int $id
	 */
	public function delete_tax_rate( $id ) {
		global $wpdb;

		$wpdb->delete( $this->tbl_tax_rates, array( 'ID' => $id ), array( '%d' ) );
	}
}
