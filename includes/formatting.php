<?php

/**
 * Format price.
 *
 * @param float $price
 * @param boolean $apply_filters
 * @param boolean $symbol
 * @return string
 */
function edr_format_price( $price, $apply_filters = true, $symbol = true ) {
	$currency = edr_get_currency();
	$decimal_point = edr_get_option( 'settings', 'decimal_point' );
	$thousands_sep = edr_get_option( 'settings', 'thousands_sep' );
	$currency_position = edr_get_option( 'settings', 'currency_position' );
	$decimal_point = ! empty( $decimal_point ) ? esc_html( $decimal_point ) : '.';
	$thousands_sep = ! empty( $thousands_sep ) ? esc_html( $thousands_sep ) : ',';
	$formatted = number_format( $price, 2, $decimal_point, $thousands_sep );

	// Trim zeroes after the decimal point (e.g., 10.00 becomes 10).
	$formatted = preg_replace( '/' . preg_quote( $decimal_point, '/' ) . '0+$/', '', $formatted );

	if ( $symbol ) {
		$currency_symbol = edr_get_currency_symbol( $currency );
	} else {
		$currency_symbol = preg_replace( '/[^a-z]+/i', '', $currency );
	}

	if ( 'after' == $currency_position ) {
		$formatted = "$formatted $currency_symbol";
	} else {
		$formatted = "$currency_symbol $formatted";
	}

	if ( $apply_filters ) {
		return apply_filters( 'edr_format_price', $formatted, $currency, $price );
	}

	return $formatted;
}

/**
 * Format membership price.
 *
 * @param float $price
 * @param int $duration
 * @param string $period days, months, years
 * @param boolean $symbol
 */
function edr_format_membership_price( $price, $duration, $period, $symbol = true ) {
	$price_str = edr_format_price( $price, true, $symbol );

	switch ( $period ) {
		case 'days':
			$price_str .= ' ' . sprintf( _n( 'per day', 'per %d days', $duration, 'educator' ), intval( $duration ) );
			break;

		case 'months':
			$price_str .= ' ' . sprintf( _n( 'per month', 'per %d months', $duration, 'educator' ), intval( $duration ) );
			break;

		case 'years':
			$price_str .= ' ' . sprintf( _n( 'per year', 'per %d years', $duration, 'educator' ), intval( $duration ) );
			break;
	}

	return $price_str;
}

/**
 * Round price.
 *
 * @param float $price
 * @return float
 */
function edr_round_price( $price ) {
	return round( $price, 2 );
}

/**
 * Round tax amount for display.
 *
 * @param float $amount
 * @return float
 */
function edr_round_tax_amount( $amount ) {
	$inclusive = edr_get_option( 'taxes', 'tax_inclusive' );

	if ( ! $inclusive ) {
		$inclusive = 'y';
	}

	$tax_round_mode = 'y' == $inclusive ? PHP_ROUND_HALF_DOWN : PHP_ROUND_HALF_UP;

	return round( $amount, 2, $tax_round_mode );
}

/**
 * Format grade.
 *
 * @param int|float $grade
 * @return string
 */
function edr_format_grade( $grade ) {
	$formatted = (float) round( $grade, 2 );

	return apply_filters( 'edr_format_grade', $formatted . '%', $grade );
}

/**
 * Allowed HTML tags.
 * Used to sanitize question content.
 *
 * @return array
 */
function edr_kses_allowed_tags() {
	return array(
		// Lists.
		'ul'         => array(),
		'ol'         => array(),
		'li'         => array(),

		// Code.
		'pre'        => array(),
		'code'       => array(),

		// Links.
		'a'          => array(
			'href'   => array(),
			'title'  => array(),
			'rel'    => array(),
			'target' => array(),
		),

		// Formatting.
		'strong'     => array(),
		'em'         => array(),

		// Images.
		'img'        => array(
			'src'    => true,
			'alt'    => true,
			'height' => true,
			'width'  => true,
		),
	);
}

/**
 * Sanitize data leaving whitelisted tags only.
 *
 * @see edr_kses_allowed_tags()
 * @param string $data
 * @return string
 */
function edr_kses_data( $data ) {
	return wp_kses( $data, edr_kses_allowed_tags() );
}
