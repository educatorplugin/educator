<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! current_user_can( 'manage_educator' ) ) {
	echo '<p>' . __( 'Access denied', 'educator' ) . '</p>';
	return;
}

$payment_id = isset( $_GET['payment_id'] ) ? intval( $_GET['payment_id'] ) : null;
$payment = edr_get_payment( $payment_id );
$payment_statuses = edr_get_payment_statuses();
$types = edr_get_payment_types();
$lines = null;
$student = null;
$post = null;
$edr_entries = Edr_Entries::get_instance();
$edr_countries = Edr_Countries::get_instance();

if ( ! $payment->ID ) {
	if ( isset( $_POST['payment_type'] ) && array_key_exists( $_POST['payment_type'], $types ) ) {
		$payment->payment_type = $_POST['payment_type'];
	} else {
		$payment->payment_type = 'course';
	}
} else {
	$lines = $payment->get_lines();
}

if ( isset( $_POST['object_id'] ) ) {
	$payment->object_id = intval( $_POST['object_id'] );
}

if ( isset( $_POST['student_id'] ) ) {
	$payment->user_id = intval( $_POST['student_id'] );
}

if ( $payment->user_id ) {
	$student = get_user_by( 'id', $payment->user_id );
}

if ( $payment->object_id ) {
	$post = get_post( $payment->object_id );
}

$form_action = 'admin.php?page=edr_admin_payments&edr-action=edit-payment';

if ( $payment_id ) {
	$form_action .= '&payment_id=' . $payment_id;
}

$form_action = admin_url( $form_action );
?>
<div class="wrap">
	<h2><?php
		if ( $payment->ID ) {
			_e( 'Edit Payment', 'educator' );
		} else {
			_e( 'Add Payment', 'educator' );
		}
	?></h2>

	<?php
		$errors = edr_internal_message( 'edit_payment_errors' );

		if ( $errors ) {
			echo '<div class="error below-h2"><ul>';

			foreach ( $errors as $error ) {
				switch ( $error ) {
					case 'empty_student_id':
						echo '<li>' . __( 'Please select a student', 'educator' ) . '</li>';
						break;
					case 'empty_object_id':
						if ( 'course' == $payment->payment_type ) {
							echo '<li>' . __( 'Please select a course', 'educator' ) . '</li>';
						} else {
							echo '<li>' . __( 'Please select a membership', 'educator' ) . '</li>';
						}
						break;
				}
			}

			echo '</ul></div>';
		}
	?>

	<?php if ( isset( $_GET['edr-message'] ) && 'saved' == $_GET['edr-message'] ) : ?>
		<div id="message" class="updated below-h2">
			<p><?php _e( 'Payment saved.', 'educator' ); ?></p>
		</div>
	<?php endif; ?>

	<form id="edr-edit-payment-form" class="edr-admin-form" action="<?php echo esc_url( $form_action ); ?>" method="post">
		<?php wp_nonce_field( 'edr_edit_payment_' . $payment->ID ); ?>
		<input type="hidden" id="edr-get-states-nonce" value="<?php echo esc_attr( wp_create_nonce( 'edr_get_states' ) ); ?>">

		<div id="payment-details" class="edr-box">
			<div class="edr-box__header">
				<div class="edr-box__title"><?php _e( 'Payment Details', 'educator' ); ?></div>
			</div>
			<div class="edr-box__body">
				<!-- Student -->
				<div class="edr-field">
					<div class="edr-field__label">
						<label><?php _e( 'Student', 'educator' ); ?><span class="required">*</span></label>
					</div>
					<div class="edr-field__control">
						<div class="edr-select-values">
							<input
								type="text"
								name="student_id"
								id="payment-student-id"
								class="regular-text"
								autocomplete="off"
								value="<?php echo ( $student ) ? intval( $student->ID ) : ''; ?>"
								data-label="<?php echo esc_attr( edr_get_user_name( $student, 'select' ) ); ?>"<?php if ( $payment->ID ) echo ' disabled="disabled"'; ?>>
						</div>
					</div>
				</div>

				<!-- Payment Type -->
				<div class="edr-field">
					<div class="edr-field__label">
						<label for="payment-type"><?php _e( 'Payment Type', 'educator' ); ?></label>
					</div>
					<div class="edr-field__control">
						<select name="payment_type" id="payment-type"<?php if ( $payment->ID ) echo ' disabled="disabled"'; ?>>
							<?php foreach ( $types as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"<?php if ( $key == $payment->payment_type ) echo ' selected="selected"'; ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<!-- Course -->
				<div class="edr-field" data-type="course"<?php if ( 'course' != $payment->payment_type ) echo ' style="display:none;"'; ?>>
					<div class="edr-field__label"><label><?php _e( 'Course', 'educator' ); ?><span class="required">*</span></label></div>
					<div class="edr-field__control">
						<?php
							$course_title = $payment->object_id ? get_the_title( $payment->object_id ) : '';
						?>
						<div class="edr-select-values">
							<input
								type="text"
								name="object_id<?php if ( 'course' != $payment->payment_type ) echo '_alt'; ?>"
								id="payment-course-id"
								class="regular-text"
								autocomplete="off"
								value="<?php echo ( $payment->object_id ) ? intval( $payment->object_id ) : ''; ?>"
								data-label="<?php echo esc_attr( $course_title ); ?>"<?php if ( $payment->ID ) echo ' disabled="disabled"'; ?>>
						</div>
					</div>
				</div>

				<!-- Membership -->
				<?php
					$ms = Edr_Memberships::get_instance();
					$memberships = $ms->get_memberships();
					$user_membership = $ms->get_user_membership_by( 'user_id', $payment->user_id );
				?>
				<div class="edr-field" data-type="membership"<?php if ( 'membership' != $payment->payment_type ) echo ' style="display:none;"'; ?>>
					<div class="edr-field__label"><label for="payment-membership-id"><?php _e( 'Membership', 'educator' ); ?><span class="required">*</span></label></div>
					<div class="edr-field__control">
						<div>
							<select id="payment-membership-id" name="object_id<?php if ( 'membership' != $payment->payment_type ) echo '_alt'; ?>">
								<option value=""><?php _e( 'Select Membership', 'educator' ); ?></option>
								<?php
									if ( $memberships ) {
										foreach ( $memberships as $membership ) {
											$selected = ( $membership->ID == $payment->object_id ) ? ' selected="selected"' : '';

											echo '<option value="' . intval( $membership->ID ) . '"' . $selected . '>'
												 . esc_html( $membership->post_title ) . '</option>';
										}
									}
								?>
							</select>

							<p>
								<label><input type="checkbox" name="setup_membership" value="1"> <?php
									if ( $user_membership ) {
										_e( 'Update membership for this student', 'educator' );
									} else {
										_e( 'Setup membership for this student', 'educator' );
									}
								?></label>
							</p>
						</div>
					</div>
				</div>

				<!-- Entry ID -->
				<div class="edr-field" data-type="course"<?php if ( 'course' != $payment->payment_type ) echo ' style="display:none;"'; ?>>
					<div class="edr-field__label">
						<label for="entry-id"><?php _e( 'Entry ID', 'educator' ); ?></label>
					</div>
					<div class="edr-field__control">
						<?php
							if ( $payment->ID ) {
								$entry = $edr_entries->get_entry( array( 'payment_id' => $payment->ID ) );
							} else {
								$entry = false;
							}

							$entry_value = $entry ? intval( $entry->ID ) : __( 'This payment is not connected to any entry.', 'educator' );
						?>
						<input type="text" id="entry-id" value="<?php echo $entry_value; ?>" disabled="disabled">
						<?php if ( ! $entry ) : ?>
							<p id="edr-create-entry-checkbox">
								<label><input type="checkbox" name="create_entry" value="1"> <?php _e( 'Create an entry for this student', 'educator' ); ?></label>
							</p>
						<?php endif; ?>
					</div>
				</div>

				<!-- Status -->
				<div class="edr-field">
					<div class="edr-field__label"><label for="payment-status"><?php _e( 'Status', 'educator' ); ?></label></div>
					<div class="edr-field__control">
						<select name="payment_status" id="payment-status">
							<?php foreach ( $payment_statuses as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"<?php if ( $key == $payment->payment_status ) echo ' selected="selected"'; ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<!-- Payment Method -->
				<div class="edr-field">
					<div class="edr-field__label">
						<label for="payment-gateway"><?php _e( 'Payment Method', 'educator' ); ?></label>
					</div>
					<div class="edr-field__control">
						<select name="payment_gateway" id="payment-gateway">
							<option value="">&mdash; <?php _e( 'Select', 'educator' ); ?> &mdash;</option>
							<?php
								$gateways = Edr_Main::get_instance()->get_gateways();

								foreach ( $gateways as $gateway ) {
									echo '<option value="' . esc_attr( $gateway->get_id() ) . '" '
										 . selected( $payment->payment_gateway, $gateway->get_id() ) . '>'
										 . esc_html( $gateway->get_title() ) . '</option>';
								}
							?>
						</select>
					</div>
				</div>

				<!-- Transaction ID -->
				<div class="edr-field">
					<div class="edr-field__label">
						<label for="payment-txn-id"><?php _e( 'Transaction ID', 'educator' ); ?></label>
					</div>
					<div class="edr-field__control">
						<input type="text" id="payment-txn-id" name="txn_id" value="<?php echo esc_attr( $payment->txn_id ); ?>">
					</div>
				</div>

				<!-- IP -->
				<?php if ( $payment->ip ) : ?>
					<div class="edr-field">
						<div class="edr-field__label"><label><?php _e( 'IP', 'educator' ); ?></label></div>
						<div class="edr-field__control"><?php echo esc_html( inet_ntop( $payment->ip ) ); ?></div>
					</div>
				<?php endif; ?>

				<!-- Tax -->
				<div class="edr-field">
					<div class="edr-field__label"><label for="payment-tax"><?php _e( 'Tax', 'educator' ); ?></label></div>
					<div class="edr-field__control">
						<input type="text" id="payment-tax" class="regular-text" name="tax" value="<?php echo ( $payment->tax ) ? edr_round_tax_amount( $payment->tax ) : 0.00; ?>">
					</div>
				</div>

				<!-- Total Amount -->
				<div class="edr-field">
					<div class="edr-field__label"><label for="payment-amount"><?php _e( 'Total Amount', 'educator' ); ?></label></div>
					<div class="edr-field__control">
						<input type="text" id="payment-amount" class="regular-text" name="amount" value="<?php echo ( $payment->amount ) ? (float) $payment->amount : 0.00; ?>">
						<div class="edr-field__info"><?php _e( 'A number with a maximum of 2 figures after the decimal point (for example, 9.99).', 'educator' ); ?></div>
					</div>
				</div>

				<!-- Currency -->
				<div class="edr-field">
					<div class="edr-field__label"><label for="payment-currency"><?php _e( 'Currency', 'educator' ); ?></label></div>
					<div class="edr-field__control">
						<select id="payment-currency" name="currency">
							<option value=""><?php _e( 'Select Currency', 'educator' ); ?></option>
							<?php
								$current_currency = empty( $payment->currency ) ? edr_get_currency() : $payment->currency;
								$currencies = edr_get_currencies();

								foreach ( $currencies as $key => $value ) {
									$selected = ( $key == $current_currency ) ? ' selected="selected"' : '';

									echo '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $value ) . '</option>';
								}
							?>
						</select>
					</div>
				</div>

				<!-- First Name -->
				<div class="edr-field">
					<div class="edr-field__label"><label for="payment-first-name"><?php _e( 'First Name', 'educator' ); ?></label></div>
					<div class="edr-field__control">
						<input type="text" id="payment-first-name" class="regular-text" name="first_name" value="<?php echo esc_attr( $payment->first_name ); ?>">
					</div>
				</div>

				<!-- Last Name -->
				<div class="edr-field">
					<div class="edr-field__label"><label for="payment-last-name"><?php _e( 'Last Name', 'educator' ); ?></label></div>
					<div class="edr-field__control">
						<input type="text" id="payment-last-name" class="regular-text" name="last_name" value="<?php echo esc_attr( $payment->last_name ); ?>">
					</div>
				</div>

				<!-- Address -->
				<div class="edr-field">
					<div class="edr-field__label"><label for="payment-address"><?php _e( 'Address', 'educator' ); ?></label></div>
					<div class="edr-field__control">
						<input type="text" id="payment-address" class="regular-text" name="address" value="<?php echo esc_attr( $payment->address ); ?>">
					</div>
				</div>

				<!-- Address Line 2 -->
				<div class="edr-field">
					<div class="edr-field__label"><label for="payment-address-2"><?php _e( 'Address Line 2', 'educator' ); ?></label></div>
					<div class="edr-field__control">
						<input type="text" id="payment-address-2" class="regular-text" name="address_2" value="<?php echo esc_attr( $payment->address_2 ); ?>">
					</div>
				</div>

				<!-- City -->
				<div class="edr-field">
					<div class="edr-field__label"><label for="payment-city"><?php _e( 'City', 'educator' ); ?></label></div>
					<div class="edr-field__control">
						<input type="text" id="payment-city" class="regular-text" name="city" value="<?php echo esc_attr( $payment->city ); ?>">
					</div>
				</div>

				<!-- Postcode / Zip -->
				<div class="edr-field">
					<div class="edr-field__label"><label for="payment-postcode"><?php _e( 'Postcode / Zip', 'educator' ); ?></label></div>
					<div class="edr-field__control">
						<input type="text" id="payment-postcode" class="regular-text" name="postcode" value="<?php echo esc_attr( $payment->postcode ); ?>">
					</div>
				</div>

				<!-- State / Province -->
				<div class="edr-field">
					<div class="edr-field__label"><label for="payment-state"><?php _e( 'State / Province', 'educator' ); ?></label></div>
					<div class="edr-field__control">
						<?php
							$states = ! empty( $payment->country ) ? $edr_countries->get_states( $payment->country ) : null;

							if ( ! empty( $states ) ) {
								echo '<select id="payment-state" name="state"><option value=""></option>';

								foreach ( $states as $scode => $sname ) {
									echo '<option value="' . esc_attr( $scode ) . '"' . selected( $payment->state, $scode, false ) . '>' . esc_html( $sname ) . '</option>';
								}

								echo '</select>';
							} else {
								echo '<input type="text" id="payment-state" class="regular-text" name="state" value="' . esc_attr( $payment->state ) . '">';
							}
						?>
					</div>
				</div>

				<!-- Country -->
				<div class="edr-field">
					<div class="edr-field__label"><label for="payment-country"><?php _e( 'Country', 'educator' ); ?></label></div>
					<div class="edr-field__control">
						<select id="payment-country" class="regular-text" name="country">
							<option value=""></option>
							<?php
								$countries = $edr_countries->get_countries();

								foreach ( $countries as $code => $country ) {
									echo '<option value="' . esc_attr( $code ) . '"' . selected( $payment->country, $code, false ) . '>' . esc_html( $country ) . '</option>';
								}
							?>
						</select>
					</div>
				</div>
			</div>
		</div>

		<?php if ( ! empty( $lines ) ) : ?>
			<div class="edr-box">
				<div class="edr-box__header">
					<div class="edr-box__title"><?php _e( 'Payment Lines', 'educator' ); ?></div>
				</div>
				<div class="edr-box__body">
					<table class="edr-payment-lines">
						<thead>
							<tr>
								<th><?php _e( 'Type', 'educator' ); ?></th>
								<th><?php _e( 'Reference ID', 'educator' ); ?></th>
								<th><?php _e( 'Amount', 'educator' ); ?></th>
								<th><?php _e( 'Tax', 'educator' ); ?></th>
								<th><?php _e( 'Name', 'educator' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
								foreach ( $lines as $line ) {
									?>
									<tr>
										<td>
											<input type="hidden" name="line_id[]" value="<?php echo intval( $line->ID ); ?>">
											<select name="line_type[]">
												<?php
													$types = array(
														'tax'  => __( 'Tax', 'educator' ),
														'item' => __( 'Item', 'educator' ),
													);

													foreach ( $types as $key => $value ) {
														echo '<option value="' . esc_attr( $key ) . '"' . selected( $line->line_type, $key, false ) . '>' . esc_html( $value ) . '</option>';
													}
												?>
											</select>
										</td>
										<td><input type="text" name="line_object_id[]" value="<?php echo (int) $line->object_id; ?>"></td>
										<td><input type="text" name="line_amount[]" value="<?php echo (float) $line->amount; ?>"></td>
										<td><input type="text" name="line_tax[]" value="<?php echo (float) $line->tax; ?>"></td>
										<td><input type="text" name="line_name[]" value="<?php echo esc_attr( $line->name ); ?>"></td>
									</tr>
									<?php
								}
							?>
						</tbody>
					</table>
				</div>
			</div>
		<?php endif; ?>

		<?php submit_button( null, 'primary', 'submit', false ); ?>
	</form>
</div>

<script>
jQuery(document).ready(function() {
	function fieldsByType( type ) {
		jQuery('#edr-edit-payment-form .edr-field').each(function() {
			var forType = this.getAttribute('data-type');

			if ( forType && forType !== type ) {
				this.style.display = 'none';
			} else {
				this.style.display = 'block';
			}
		});

		if (type === 'course') {
			jQuery('#payment-course-id').attr('name', 'object_id');
			jQuery('#payment-membership-id').attr('name', 'object_id_alt');
		} else {
			jQuery('#payment-course-id').attr('name', 'object_id_alt');
			jQuery('#payment-membership-id').attr('name', 'object_id');
		}
	}

	jQuery('#payment-type').on('change', function() {
		fieldsByType(this.value);
	});

	EdrLib.select(document.getElementById('payment-student-id'), {
		key:      'id',
		label:    'name',
		searchBy: 'name',
		url:      '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
		ajaxArgs: {
			action: 'edr_select_users',
			_wpnonce: '<?php echo esc_js( wp_create_nonce( 'edr_select_users' ) ); ?>'
		}
	});

	EdrLib.select(document.getElementById('payment-course-id'), {
		key:      'id',
		label:    'title',
		searchBy: 'title',
		url:      '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
		ajaxArgs: {
			action: 'edr_select_posts',
			post_type: '<?php echo EDR_PT_COURSE; ?>',
			_wpnonce: '<?php echo esc_js( wp_create_nonce( 'edr_select_posts' ) ); ?>'
		}
	});

	postboxes.add_postbox_toggles(pagenow);
});
</script>
