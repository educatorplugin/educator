<?php
/**
 * Renders the student's payments page.
 *
 * @version 1.1.0
 */

$user_id = get_current_user_id();

if ( ! $user_id ) {
	echo '<p>' . __( 'Please log in to view this page.', 'educator' ) . '</p>';
	return;
}

$edr_payments = Edr_Payments::get_instance();
$payments = $edr_payments->get_payments( array( 'user_id' => $user_id ) );
$payment_page_url = get_permalink( edr_get_page_id( 'payment' ) );
$statuses = edr_get_payment_statuses();

// Output status message.
$message = get_query_var( 'edr-message' );

if ( 'payment-cancelled' == $message ) {
	echo '<div class="edr-messages"><div class="edr-message success">' . __( 'Payment has been cancelled.', 'educator' ) . '</div></div>';
}
?>

<?php if ( ! empty( $payments ) ) : ?>
	<table class="edr-user-payments">
		<thead>
			<tr>
				<th><?php _e( 'ID', 'educator' ); ?></th>
				<th><?php _e( 'Date', 'educator' ); ?></th>
				<th><?php _e( 'Payment Status', 'educator' ); ?></th>
				<th><?php _e( 'Amount', 'educator' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $payments as $payment ) : ?>
				<?php
					$invoice_url = edr_get_endpoint_url( 'edr-payment', $payment->ID, $payment_page_url );
				?>
				<tr>
					<td><?php echo intval( $payment->ID ); ?></td>
					<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $payment->payment_date ) ) ); ?></td>
					<td>
						<?php
							if ( array_key_exists( $payment->payment_status, $statuses ) ) {
								echo esc_html( $statuses[ $payment->payment_status ] );
							}
						?>
					</td>
					<td><?php echo edr_format_price( $payment->amount, false ); ?></td>
					<td class="actions-group">
						<a href="<?php echo esc_url( $invoice_url ); ?>"><?php _e( 'Details', 'educator' ); ?></a>
						<?php if ( 'pending' == $payment->payment_status ) : ?>
							<?php
								$cancel_payment_url = add_query_arg( array(
									'edr-action' => 'cancel-payment',
									'payment_id' => $payment->ID,
									'page_id'    => get_the_ID(),
								), get_permalink() );
								$cancel_payment_url = wp_nonce_url( $cancel_payment_url, 'edr_cancel_payment', '_wpnonce' );
							?>
							<a href="<?php echo esc_url( $cancel_payment_url ); ?>" class="cancel-payment"><?php _e( 'Cancel', 'educator' ); ?></a>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php else : ?>
	<p><?php _e( 'No payments found.', 'educator' ); ?></p>
<?php endif; ?>
