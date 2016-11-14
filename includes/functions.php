<?php

/**
 * Get plugin option.
 *
 * @param string $section
 * @param string $key
 * @return mixed Returns null on failure.
 */
function edr_get_option( $section, $key = null ) {
	$options = null;

	switch ( $section ) {
		case 'settings':
		case 'learning':
		case 'taxes':
		case 'email':
		case 'memberships':
			$options = get_option( 'edr_' . $section );
			break;
	}

	if ( false !== $options ) {
		if ( $key ) {
			return ( is_array( $options ) && isset( $options[ $key ] ) )
				? $options[ $key ] : null;
		} else {
			return $options;
		}
	}

	return null;
}

/**
 * Get database table names.
 *
 * @return array
 */
function edr_db_tables() {
	static $tables = null;
	global $wpdb;
	$prefix = $wpdb->prefix . 'edr_';

	if ( is_null( $tables ) ) {
		$tables = array(
			'payments'      => $prefix . 'payments',
			'entries'       => $prefix . 'entries',
			'questions'     => $prefix . 'questions',
			'choices'       => $prefix . 'choices',
			'answers'       => $prefix . 'answers',
			'grades'        => $prefix . 'grades',
			'members'       => $prefix . 'members',
			'tax_rates'     => $prefix . 'tax_rates',
			'payment_lines' => $prefix . 'payment_lines',
			'entry_meta'    => $prefix . 'entry_meta',
		);
	}

	return $tables;
}

/**
 * Get the list of available currencies.
 *
 * @return array
 */
function edr_get_currencies() {
	return apply_filters( 'edr_currencies', array(
		'AUD' => __( 'Australian Dollars', 'edr' ),
		'AZN' => __( 'Azerbaijani Manat', 'edr' ),
		'BRL' => __( 'Brazilian Real', 'edr' ),
		'CAD' => __( 'Canadian Dollars', 'edr' ),
		'CNY' => __( 'Chinese Yuan', 'edr' ),
		'CZK' => __( 'Czech Koruna', 'edr' ),
		'DKK' => __( 'Danish Krone', 'edr' ),
		'EUR' => __( 'Euros', 'edr' ),
		'HKD' => __( 'Hong Kong Dollar', 'edr' ),
		'HUF' => __( 'Hungarian Forint', 'edr' ),
		'INR' => __( 'Indian Rupee', 'edr' ),
		'IRR' => __( 'Iranian Rial', 'edr' ),
		'ILS' => __( 'Israeli Shekel', 'edr' ),
		'JPY' => __( 'Japanese Yen', 'edr' ),
		'MYR' => __( 'Malaysian Ringgits', 'edr' ),
		'MXN' => __( 'Mexican Peso', 'edr' ),
		'NZD' => __( 'New Zealand Dollar', 'edr' ),
		'NOK' => __( 'Norwegian Krone', 'edr' ),
		'PHP' => __( 'Philippine Pesos', 'edr' ),
		'PLN' => __( 'Polish Zloty', 'edr' ),
		'GBP' => __( 'Pounds Sterling', 'edr' ),
		'RUB' => __( 'Russian Rubles', 'edr' ),
		'SGD' => __( 'Singapore Dollar', 'edr' ),
		'SEK' => __( 'Swedish Krona', 'edr' ),
		'KRW' => __( 'South Korean Won', 'edr' ),
		'CHF' => __( 'Swiss Franc', 'edr' ),
		'TWD' => __( 'Taiwan New Dollars', 'edr' ),
		'THB' => __( 'Thai Baht', 'edr' ),
		'TRY' => __( 'Turkish Lira', 'edr' ),
		'USD' => __( 'US Dollars', 'edr' ),
		'UAH' => __( 'Ukrainian Hryvnia', 'edr' ),
	) );
}

/**
 * Get current currency.
 *
 * @return string
 */
function edr_get_currency() {
	$currency = edr_get_option( 'settings', 'currency' );

	if ( ! $currency ) {
		$currency = '';
	}

	return apply_filters( 'edr_currency', $currency );
}

/**
 * Get currency symbol.
 *
 * @param string $currency
 * @return string
 */
function edr_get_currency_symbol( $currency ) {
	switch ( $currency ) {
		case 'USD':
		case 'AUD':
		case 'CAD':
		case 'HKD':
		case 'MXN':
		case 'NZD':
		case 'SGD':
			$cs = "&#36;";
			break;
		case 'BRL': $cs = "&#82;&#36;"; break;
		case 'CNY': $cs = "&#165;"; break;
		case 'CZK': $cs = "&#75;&#269;"; break;
		case 'DKK': $cs = "&#107;&#114;"; break;
		case 'EUR': $cs = "&euro;"; break;
		case 'HUF': $cs = "&#70;&#116;"; break;
		case 'INR': $cs = "&#8377;"; break;
		case 'IRR': $cs = "&#65020;"; break;
		case 'ILS': $cs = "&#8362;"; break;
		case 'JPY': $cs = "&yen;"; break;
		case 'MYR': $cs = "&#82;&#77;"; break;
		case 'NOK': $cs = "&#107;&#114;"; break;
		case 'PHP': $cs = "&#8369;"; break;
		case 'PLN': $cs = "&#122;&#322;"; break;
		case 'GBP': $cs = "&pound;"; break;
		case 'RUB': $cs = "&#1088;&#1091;&#1073;."; break;
		case 'SEK': $cs = "&#107;&#114;"; break;
		case 'CHF': $cs = "&#67;&#72;&#70;"; break;
		case 'TWD': $cs = "&#78;&#84;&#36;"; break;
		case 'THB': $cs = "&#3647;"; break;
		case 'TRY': $cs = "&#84;&#76;"; break;
		case 'UAH': $cs = "&#8372;"; break;
		default: $cs = $currency;
	}

	return apply_filters( 'edr_currency_symbol', $cs, $currency );
}

/**
 * Get permalink endpoint URL.
 *
 * @param string $endpoint
 * @param string $value
 * @param string $url
 * @return string
 */
function edr_get_endpoint_url( $endpoint, $value, $url ) {
	if ( get_option( 'permalink_structure' ) ) {
		$url = trailingslashit( $url ) . $endpoint . '/' . $value;
	} else {
		$url = add_query_arg( $endpoint, $value, $url );
	}

	return $url;
}

/**
 * Get page id given page alias.
 *
 * @param string $key Example: payment.
 * @return int
 */
function edr_get_page_id( $key ) {
	$page_id = edr_get_option( 'settings', $key . '_page' );

	return intval( $page_id );
}

/**
 * Pass the message from the back-end to the template.
 *
 * @param string $key
 * @param mixed $value
 * @return mixed
 */
function edr_internal_message( $key, $value = null ) {
	static $messages = array();

	if ( is_null( $value ) ) {
		return isset( $messages[ $key ] ) ? $messages[ $key ] : null;
	}

	$messages[ $key ] = $value;
}

/**
 * Get available course difficulty levels.
 *
 * @return array
 */
function edr_get_difficulty_levels() {
	return array(
		'beginner'     => __( 'Beginner', 'edr' ),
		'intermediate' => __( 'Intermediate', 'edr' ),
		'advanced'     => __( 'Advanced', 'edr' ),
	);
}

/**
 * Get course difficulty.
 *
 * @param int $course_id
 * @return null|array
 */
function edr_get_difficulty( $course_id ) {
	$difficulty = get_post_meta( $course_id, '_edr_difficulty', true );
	
	if ( $difficulty ) {
		$levels = edr_get_difficulty_levels();

		return array(
			'key'   => $difficulty,
			'label' => isset( $levels[ $difficulty ] ) ? $levels[ $difficulty ] : '',
		);
	}

	return null;
}

/**
 * Send email notification.
 *
 * @param string $to
 * @param string $template
 * @param array $subject_vars
 * @param array $template_vars
 * @return boolean
 */
function edr_send_notification( $to, $template, $subject_vars, $template_vars ) {
	// Set default template vars.
	$template_vars['login_link'] = wp_login_url();

	// Send email.
	$email = new Edr_EmailAgent();
	$email->set_template( $template );
	$email->parse_subject( $subject_vars );
	$email->parse_template( $template_vars );
	$email->add_recipient( $to );

	return $email->send();
}

/**
 * Are we on the payment page?
 *
 * @param string $key
 * @return bool
 */
function edr_is_page( $key ) {
	$page_id = edr_get_page_id( $key );

	return ( $page_id && is_page( $page_id ) );
}

/**
 * Find out whether to collect billing data or not.
 *
 * @param mixed $object
 * @return bool
 */
function edr_collect_billing_data( $object ) {
	if ( is_numeric( $object ) ) {
		$object = get_post( $object );
	}

	$result = false;

	if ( $object ) {
		$price = null;

		if ( EDR_PT_MEMBERSHIP == $object->post_type ) {
			$price = Edr_Memberships::get_instance()->get_price( $object->ID );
		} elseif ( EDR_PT_COURSE == $object->post_type ) {
			$price = Edr_Courses::get_instance()->get_course_price( $object->ID );
		}

		if ( $price && edr_get_option( 'taxes', 'enable' ) ) {
			$result = true;
		}
	}

	return $result;
}

/**
 * Get the business location.
 *
 * @param string $part
 * @return mixed
 */
function edr_get_location( $part = null ) {
	$result = array('', '');
	$location = edr_get_option( 'settings', 'location' );

	if ( $location ) {
		$delimiter = strpos( $location, ';' );

		if ( false === $delimiter ) {
			$result[0] = $location;
		} else {
			$result[0] = substr( $location, 0, $delimiter );
			$result[1] = substr( $location, $delimiter + 1 );
		}
	}

	if ( 'country' == $part ) {
		return $result[0];
	} elseif ( 'state' == $part ) {
		return $result[1];
	}

	return $result;
}

/**
 * Get a payment.
 *
 * @param int|object|null $data
 * @return Edr_Payment
 */
function edr_get_payment( $data = null ) {
	return new Edr_Payment( $data );
}

/**
 * Get the available payment statuses.
 *
 * @return array
 */
function edr_get_payment_statuses() {
	return array(
		'pending'   => __( 'Pending', 'edr' ),
		'complete'  => __( 'Complete', 'edr' ),
		'failed'    => __( 'Failed', 'edr' ),
		'cancelled' => __( 'Cancelled', 'edr' ),
	);
}

/**
 * Get the available payment types.
 *
 * @return array
 */
function edr_get_payment_types() {
	return array(
		'course'     => __( 'Course', 'edr' ),
		'membership' => __( 'Membership', 'edr' ),
	);
}

/**
 * Get an entry.
 *
 * @param int|object|null $data
 * @return Edr_Entry
 */
function edr_get_entry( $data = null ) {
	return new Edr_Entry( $data );
}

/**
 * Get the available entry statuses.
 *
 * @return array
 */
function edr_get_entry_statuses() {
	return array(
		'pending'    => __( 'Pending', 'edr' ),
		'inprogress' => __( 'In progress', 'edr' ),
		'complete'   => __( 'Complete', 'edr' ),
		'cancelled'  => __( 'Cancelled', 'edr' ),
		'paused'     => __( 'Paused', 'edr' ),
	);
}

/**
 * Get the available entry origins.
 *
 * @return array
 */
function edr_get_entry_origins() {
	return apply_filters( 'edr_entry_origins', array(
		'payment'    => __( 'Payment', 'edr' ),
		'membership' => __( 'Membership', 'edr' ),
	) );
}

/**
 * Get a question.
 *
 * @param int|object|null $data
 * @return Edr_Question
 */
function edr_get_question( $data = null ) {
	return new Edr_Question( $data );
}

/**
 * Get a quiz answer.
 *
 * @param int|object|null $data
 * @return Edr_QuizAnswer
 */
function edr_get_quiz_answer( $data = null ) {
	return new Edr_QuizAnswer( $data );
}

/**
 * Get a quiz question answer choice.
 *
 * @param int|object|null $data
 * @return Edr_QuizChoice
 */
function edr_get_quiz_choice( $data = null ) {
	return new Edr_QuizChoice( $data );
}

/**
 * Get a quiz grade.
 *
 * @param int|object|null $data
 * @return Edr_QuizGrade
 */
function edr_get_quiz_grade( $data = null ) {
	return new Edr_QuizGrade( $data );
}

/**
 * Get directory path for private file uploads.
 *
 * @return string
 */
function edr_get_private_uploads_dir() {
	$dir = apply_filters( 'edr_private_uploads_dir', '' );

	if ( ! $dir ) {
		$upload_dir = wp_upload_dir();

		if ( $upload_dir && false === $upload_dir['error'] ) {
			$dir = $upload_dir['basedir'] . '/edr';
		}
	}

	return $dir;
}

/**
 * Check if the protection .htaccess file exists
 * in the private file uploads directory.
 *
 * @return boolean
 */
function edr_protect_htaccess_exists() {
	$dir = edr_get_private_uploads_dir();

	return file_exists( $dir . '/.htaccess' );
}

/**
 * Get full name of a user.
 *
 * @param WP_User $user
 * @return string
 */
function edr_get_user_full_name( $user ) {
	$full_name = $user->first_name;
	$full_name .= ( $user->last_name ) ? ' ' . $user->last_name : '';

	if ( ! $full_name ) {
		$full_name = $user->display_name;
	}

	return $full_name;
}

/**
 * Display deprecated function notice.
 *
 * @param string $function
 * @param string $version
 * @param string|null $replacement
 */
function edr_deprecated_function( $function, $version, $replacement = null ) {
	if ( WP_DEBUG ) {
		if ( is_null( $replacement ) ) {
			trigger_error( sprintf( __( '%1$s is <strong>deprecated</strong> since Educator version %2$s with no alternative available.', 'edr' ), $function, $version ) );
		} else {
			trigger_error( sprintf( __( '%1$s is <strong>deprecated</strong> since Educator version %2$s! Use %3$s instead.', 'edr'), $function, $version, $replacement ) );
		}
	}
}
