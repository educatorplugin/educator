<?php

class Edr_Gateway_Stripe extends Edr_Gateway_Base {
	/**
	 * Setup payment gateway.
	 */
	public function __construct() {
		$this->id = 'stripe';
		$this->title = __( 'Stripe', 'educator' );

		// Setup options.
		$this->init_options( array(
			'secret_key' => array(
				'type'      => 'text',
				'label'     => __( 'Secret key', 'educator' ),
				'id'        => 'edr-stripe-secret-key',
			),
			'publishable_key' => array(
				'type'      => 'text',
				'label'     => __( 'Publishable key', 'educator' ),
				'id'        => 'edr-stripe-publishable-key',
			),
			'thankyou_message' => array(
				'type'      => 'textarea',
				'label'     => __( 'Thank you message', 'educator' ),
				'id'        => 'edr-stripe-thankyou-message',
				'rich_text' => true,
			),
		) );

		add_action( 'edr_pay_' . $this->get_id(), array( $this, 'pay_page' ) );
		add_action( 'edr_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'edr_request_stripe_token', array( $this, 'process_stripe_token' ) );
	}

	/**
	 * Process payment.
	 *
	 * @return array
	 */
	public function process_payment( $object_id, $user_id = null, $payment_type = 'course', $atts = array() ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return array( 'redirect' => home_url( '/' ) );
		}

		$payment = $this->create_payment( $object_id, $user_id, $payment_type, $atts );
		$redirect_url = edr_get_endpoint_url( 'edr-pay', ( $payment->ID ? $payment->ID : '' ),
			get_permalink( edr_get_page_id( 'payment' ) ) );

		return array(
			'status'   => 'pending',
			'redirect' => $redirect_url,
			'payment'  => $payment,
		);
	}

	/**
	 * Output the Stripe's payment dialog.
	 * Step 2 in the payment process.
	 */
	public function pay_page() {
		$payment_id = intval( get_query_var( 'edr-pay' ) );

		if ( ! $payment_id ) {
			return;
		}

		$user = wp_get_current_user();

		if ( 0 == $user->ID ) {
			return;
		}

		$payment = edr_get_payment( $payment_id );

		if ( ! $payment->ID || $user->ID != $payment->user_id ) {
			// The payment must exist and it must be associated with the current user.
			return;
		}

		$post = get_post( $payment->object_id );

		if ( ! $post ) {
			return;
		}

		$payment_summary_url = edr_get_endpoint_url( 'edr-payment', $payment->ID, get_permalink( edr_get_page_id( 'payment' ) ) );
		?>
		<p id="edr-payment-processing-msg"><?php _e( 'The payment is being processed...', 'educator' ); ?></p>
		<script src="https://checkout.stripe.com/checkout.js"></script>
		<script>
		(function($) {
			var handler = StripeCheckout.configure({
				key: <?php echo json_encode( $this->get_option( 'publishable_key' ) ); ?>,
				image: '',
				email: <?php echo json_encode( sanitize_email( $user->user_email ) ); ?>,
				token: function(token) {
					$.ajax({
						type: 'POST',
						cache: false,
						url: <?php echo json_encode( Edr_RequestDispatcher::get_url( 'stripe_token' ) ); ?>,
						data: {
							payment_id: <?php echo intval( $payment->ID ); ?>,
							token: token.id,
							_wpnonce: <?php echo json_encode( wp_create_nonce( 'edr_stripe_token' ) ); ?>
						},
						success: function(response) {
							if (response === '1') {
								$('#edr-payment-processing-msg').text(<?php echo json_encode( __( 'Redirecting to the payment summary page...', 'educator' ) ); ?>);
								var redirectTo = <?php echo json_encode( $payment_summary_url ); ?>;
								document.location = redirectTo;
							}
						}
					});
				}
			});

			handler.open({
				name: '<?php echo esc_js( $post->post_title ); ?>',
				description: '<?php echo esc_js( edr_format_price( $payment->amount, false, false ) ); ?>',
				currency: '<?php echo esc_js( edr_get_currency() ); ?>',
				amount: <?php echo edr_round_price( $payment->amount ) * 100; ?>
			});

			$(window).on('popstate', function() {
				handler.close();
			});
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Output thank you information.
	 */
	public function thankyou_page() {
		// Thank you message.
		$thankyou_message = $this->get_option( 'thankyou_message' );

		if ( ! empty( $thankyou_message ) ) {
			echo '<div class="edr-gateway-description">' . wpautop( stripslashes( $thankyou_message ) ) . '</div>';
		}
	}

	/**
	 * Sanitize options.
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitize_admin_options( $input ) {
		foreach ( $input as $option_name => $value ) {
			switch ( $option_name ) {
				case 'thankyou_message':
					$input[ $option_name ] = wp_kses_data( $value );
					break;

				case 'secret_key':
				case 'publishable_key':
					$input[ $option_name ] = sanitize_text_field( $value );
					break;
			}
		}

		return $input;
	}

	/**
	 * Charge the card using Stripe.
	 * It's an AJAX action.
	 */
	public function process_stripe_token() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'edr_stripe_token' ) ) {
			exit( '0' );
		}

		if ( ! isset( $_POST['token'] ) || ! isset( $_POST['payment_id'] ) ) {
			exit( '0' );
		}

		$user = wp_get_current_user();

		if ( 0 == $user->ID ) {
			exit( '0' );
		}

		$payment = edr_get_payment( $_POST['payment_id'] );

		if ( ! $payment->ID || $user->ID != $payment->user_id ) {
			// The payment must exist and it must be associated with the current user.
			exit( '0' );
		}

		require_once EDR_PLUGIN_DIR . 'lib/Stripe/Stripe.php';

		$token = $_POST['token'];
		$amount = edr_round_price( $payment->amount );
		$description = sprintf( __( 'Payment #%d', 'educator' ), $payment->ID );
		$description .= ' , ' . get_the_title( $payment->object_id );

		try {
			Stripe::setApiKey( $this->get_option( 'secret_key' ) );
			Stripe_Charge::create( array(
				'amount'      => $amount * 100,
				'currency'    => $payment->currency,
				'card'        => $token,
				'description' => $description,
			) );

			// Update the payment status.
			$payment->payment_status = 'complete';
			$payment->save();

			// Setup course or membership for the student.
			Edr_Payments::get_instance()->setup_payment_item( $payment );

			exit( '1' );
		} catch ( Exception $e ) {}

		exit( '0' );
	}
}
