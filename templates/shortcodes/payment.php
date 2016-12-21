<?php
/**
 * Renders the payment page.
 *
 * @version 1.0.0
 */

$edr_payments = Edr_Payments::get_instance();
$user_id = get_current_user_id();
$payment_id = get_query_var( 'edr-payment' );

if ( $payment_id ) {
	// Payment summary page.
	if ( ! $user_id ) {
		return;
	}

	if ( ! is_numeric( $payment_id ) ) {
		return;
	}
	
	$payment = edr_get_payment( $payment_id );

	if ( ! $payment->ID || $payment->user_id != $user_id ) {
		return;
	}

	$post = get_post( $payment->object_id );

	if ( ! $post || ! in_array( $post->post_type, array( EDR_PT_COURSE, EDR_PT_MEMBERSHIP ) ) ) {
		return;
	}

	$lines = $payment->get_lines();
	?>
	<h2><?php _e( 'Payment Summary', 'educator' ); ?></h2>

	<dl id="payment-details" class="edr-dl">
		<dt class="payment-id"><?php _e( 'Payment', 'educator' ); ?></dt>
		<dd><?php echo intval( $payment->ID ); ?></dd>

		<dt class="payment-date"><?php _e( 'Date', 'educator' ); ?></dt>
		<dd><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $payment->payment_date ) ) ); ?></dd>

		<dt class="payment-status"><?php _e( 'Payment Status', 'educator' ); ?></dt>
		<dd>
			<?php
				$statuses = edr_get_payment_statuses();

				if ( array_key_exists( $payment->payment_status, $statuses ) ) {
					echo esc_html( $statuses[ $payment->payment_status ] );
				}
			?>
		</dd>
	</dl>

	<table class="edr-payment-table">
		<thead>
			<tr>
				<th><?php _e( 'Item', 'educator' ); ?></th>
				<th><?php _e( 'Price', 'educator' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php echo esc_html( $post->post_title ); ?></td>
				<td><?php echo edr_format_price( $payment->amount - $payment->tax, false ); ?></td>
			</tr>
		</tbody>
	</table>

	<dl class="edr-payment-summary edr-dl">
		<?php
			if ( $payment->tax > 0.0 ) {
				echo '<dt class="payment-subtotal">' . __( 'Subtotal', 'educator' ) .'</dt><dd>' . edr_format_price( $payment->amount - $payment->tax, false ) . '</dd>';

				foreach ( $lines as $line ) {
					if ( 'tax' == $line->line_type ) {
						echo '<dt>' . esc_html( $line->name ) . '</dt><dd>' . edr_format_price( $line->amount, false ) . '</dd>';
					}
				}
			}
		?>

		<dt class="payment-total"><?php _e( 'Total', 'educator' ); ?></dt>
		<dd><?php echo edr_format_price( $payment->amount, false ) ?></dd>
	</dl>
	<?php
	if ( $payment->ID && $payment->user_id == $user_id ) {
		do_action( 'edr_thankyou_' . $payment->payment_gateway );
	}

	// Show link to the payments page.
	$payments_page = get_post( edr_get_page_id( 'user_payments' ) );
	
	if ( $payments_page ) {
		echo '<p>' . sprintf( __( 'Go to %s page', 'educator' ), '<a href="' . esc_url( get_permalink( $payments_page->ID ) ) .
			'">' . esc_html( $payments_page->post_title ) . '</a>' ) . '</p>';
	}
} elseif ( ( $payment_id = get_query_var( 'edr-pay' ) ) ) {
	// Step 2 of the payment process. It can be used by
	// a payment gateway. PayPal gateway uses it.
	if ( ! is_numeric( $payment_id ) ) {
		return;
	}

	$payment = edr_get_payment( $payment_id );

	// The payment must exist and it must belong to the current user.
	if ( $payment->ID && $payment->user_id == $user_id ) {
		do_action( 'edr_pay_' . $payment->payment_gateway );
	}
} else {
	// Step 1 of the payment process.
	$object_id = get_query_var( 'edr-object' );

	if ( ! is_numeric( $object_id ) && isset( $_POST['object_id'] ) ) {
		$object_id = intval( $_POST['object_id'] );
	}

	$post = ( $object_id ) ? get_post( $object_id ) : null;

	if ( ! $post || ! in_array( $post->post_type, array( EDR_PT_COURSE, EDR_PT_MEMBERSHIP ) ) ) {
		return;
	}

	$edr_courses = Edr_Courses::get_instance();

	if ( EDR_PT_COURSE == $post->post_type ) {
		$course_status = $edr_courses->get_register_status( $post->ID );

		if ( 'closed' == $course_status ) {
			echo '<p>' . __( 'Registration for this course is closed.', 'educator' ) . '</p>';
			return;
		}

		if ( $user_id ) {
			$payments = $edr_payments->get_payments( array(
				'user_id'        => $user_id,
				'course_id'      => $post->ID,
				'payment_status' => array( 'pending' ),
			) );

			if ( ! empty( $payments ) ) {
				echo '<p>' . __( 'The payment for this course is pending.', 'educator' ) . '</p>';

				$payments_page = get_post( edr_get_page_id( 'user_payments' ) );

				if ( $payments_page ) {
					$payments_link = '<a href="' . esc_url( get_permalink( $payments_page->ID ) ) . '">' .
						esc_html( $payments_page->post_title ) . '</a>';
					echo '<p>' . sprintf( __( 'Go to %s', 'educator' ), $payments_link ) . '</p>';
				}

				return;
			}
		}
	}

	if ( ! $user_id ) {
		$login_url = wp_login_url( edr_get_endpoint_url( 'edr-object', $post->ID, get_permalink() ) );
		echo '<p>' . __( 'Already have an account?', 'educator' ) . ' <a href="' .
			esc_url( $login_url ) . '">' . __( 'Log in', 'educator' ) . '</a></p>';
	}

	// Output error messages.
	$errors = edr_internal_message( 'payment_errors' );
	$error_codes = $errors ? $errors->get_error_codes() : array();

	if ( ! empty( $error_codes ) ) {
		$messages = $errors->get_error_messages();

		echo '<div class="edr-messages">';

		foreach ( $messages as $message ) {
			echo '<div class="edr-message edr-messages__message error">' . $message . '</div>';
		}

		echo '</div>';
	}

	$form_action = add_query_arg( 'edr-action', 'payment', get_permalink() );
	?>
		<form id="edr-payment-form" class="edr-form" action="<?php echo esc_url( $form_action ); ?>" method="post">
			<input type="hidden" id="payment-object-id" name="object_id" value="<?php echo intval( $post->ID ); ?>">
			<?php wp_nonce_field( 'edr_submit_payment' ); ?>
			<?php
				/**
				 * Hook into payment form output.
				 *
				 * @param null|WP_Error $errors
				 * @param mixed $post
				 */
				do_action( 'edr_register_form', $errors, $post );
			?>

			<div class="edr-form__fields">
				<div class="edr-form__legend"><?php _e( 'Payment Information', 'educator' ); ?></div>

				<?php
					$args = array();
					$billing = $edr_payments->get_billing_data( $user_id );

					// Get country.
					if ( isset( $_POST['billing_country'] ) ) {
						$args['country'] = $_POST['billing_country'];
					} elseif ( ! empty( $billing['country'] ) ) {
						$args['country'] = $billing['country'];
					} else {
						$args['country'] = edr_get_location( 'country' );
					}

					// Get state.
					if ( isset( $_POST['billing_state'] ) ) {
						$args['state'] = $_POST['billing_state'];
					} elseif ( ! empty( $billing['state'] ) ) {
						$args['state'] = $billing['state'];
					} else {
						$args['state'] = edr_get_location( 'state' );
					}

					// Get price.
					if ( EDR_PT_COURSE == $post->post_type ) {
						$args['price'] = $edr_courses->get_course_price( $post->ID );
					} elseif ( EDR_PT_MEMBERSHIP == $post->post_type ) {
						$args['price'] = Edr_Memberships::get_instance()->get_price( $post->ID );
					}

					// Output payment summary.
					echo '<div id="edr-payment-info" class="edr-payment-info">' . Edr_StudentAccount::payment_info( $post, $args ) . '</div>';

					// Payment gateways.
					$gateways = Edr_Main::get_instance()->get_gateways();
				?>

				<?php if ( $args['price'] && ! empty( $gateways ) ) : ?>
					<div class="edr-field<?php if ( in_array( 'empty_payment_method', $error_codes ) ) echo ' edr-field_error'; ?>">
						<div class="edr-field__label">
							<label><?php _e( 'Payment Method', 'educator' ); ?><span class="required">*</span></label>
						</div>
						<div class="edr-field__control">
							<ul class="edr-payment-methods">
								<?php
									$current_gateway_id = isset( $_POST['payment_method'] ) ? $_POST['payment_method'] : '';

									foreach ( $gateways as $gateway_id => $gateway ) {
										if ( 'free' == $gateway_id ) {
											continue;
										}

										$checked = '';

										if ( ! empty( $current_gateway_id ) && $current_gateway_id === $gateway_id ) {
											$checked = ' checked';
										} elseif ( empty( $current_gateway_id ) && $gateway->is_default() ) {
											$checked = ' checked';
										}
										?>
										<li>
											<label class="edr-radio">
												<input type="radio" name="payment_method" value="<?php echo esc_attr( $gateway_id ); ?>"<?php echo $checked ?>><span><?php echo esc_html( $gateway->get_title() ); ?></span>
											</label>
										</li>
										<?php
									}
								?>
							</ul>
						</div>
					</div>
				<?php elseif ( 0.0 == $args['price'] ) : ?>
					<input type="hidden" name="payment_method" value="free">
				<?php endif; ?>
			</div>

			<div class="edr-form__actions">
				<button type="submit" class="edr-button"><?php _e( 'Continue', 'educator' ) ?></button>
			</div>
		</form>
	<?php
}
?>
