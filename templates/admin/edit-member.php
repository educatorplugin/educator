<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! current_user_can( 'manage_educator' ) ) {
	echo '<p>' . __( 'Access denied', 'educator' ) . '</p>';
	exit;
}

$edr_payments = Edr_Payments::get_instance();
$edr_memberships = Edr_Memberships::get_instance();
$memberships = $edr_memberships->get_memberships();
$statuses = $edr_memberships->get_statuses();

$member_id = null;

if ( isset( $_GET['id'] ) ) {
	$member_id = intval( $_GET['id'] );
} elseif ( isset( $_POST['id'] ) ) {
	$member_id = intval( $_POST['id'] );
}

$user = null;
$payments = null;
$user_membership = ( $member_id ) ? $edr_memberships->get_user_membership_by( 'user_id', $member_id ) : null;

if ( $user_membership ) {
	$user = get_user_by( 'id', $user_membership['user_id'] );
	$payments = $edr_payments->get_payments( array(
		'payment_type' => 'membership',
		'user_id'      => $user_membership['user_id'],
	) );
} else {
	$user_membership = array(
		'ID'            => null,
		'user_id'       => null,
		'membership_id' => null,
		'status'        => '',
		'expiration'    => '',
	);
}

$form_action = admin_url( 'admin.php?page=edr_admin_members&edr-action=edit-member' );
$user_select_name = edr_get_user_name( $user, 'select' );
?>
<div class="wrap">
	<h2><?php
		if ( $user_membership['ID'] ) {
			_e( 'Edit Member', 'educator' );
		} else {
			_e( 'Add Member', 'educator' );
		}
	?></h2>

	<?php
		$errors = edr_internal_message( 'edit_member_errors' );

		if ( $errors ) {
			$messages = $errors->get_error_messages();

			foreach ( $messages as $message ) {
				echo '<div class="error"><p>' . $message . '</p></div>';
			}
		}
	?>

	<?php if ( isset( $_GET['edr-message'] ) && 'saved' == $_GET['edr-message'] ) : ?>
		<div id="message" class="updated below-h2">
			<p><?php _e( 'Member updated.', 'educator' ); ?></p>
		</div>
	<?php endif; ?>

	<form id="edr-edit-member-form" class="edr-admin-form" action="<?php echo esc_url( $form_action ); ?>" method="post">
		<?php wp_nonce_field( 'edr_edit_member' ); ?>
		<input type="hidden" id="member-id" name="id" value="<?php if ( $member_id ) echo intval( $member_id ); ?>">

		<div id="member-details" class="edr-box">
			<div class="edr-box__header">
				<div class="edr-box__title"><?php _e( 'Member Details', 'educator' ); ?></div>
			</div>
			<div class="edr-box__body">
				<!-- Member -->
				<div class="edr-field">
					<div class="edr-field__label"><label for="member"><?php _e( 'Member', 'educator' ); ?></label></div>
					<div class="edr-field__control">
						<div class="edr-select-values">
							<input
								type="text"
								id="user-id"
								name="user_id"
								class="regular-text"
								autocomplete="off"
								value="<?php if ( $user_membership['user_id'] ) echo intval( $user_membership['user_id'] ); ?>"
								data-label="<?php echo esc_attr( $user_select_name ); ?>"<?php if ( $user_membership['user_id'] ) echo ' disabled="disabled"'; ?>>
						</div>

						<?php if ( current_user_can( 'edit_user', $user_membership['user_id'] ) ) : ?>
							<div class="edr-field__info">
								<a href="<?php echo esc_url( get_edit_user_link( $user_membership['user_id'] ) ); ?>" target="_blank"><?php _e( 'Edit Profile', 'educator' ); ?></a>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<!-- Membership Level -->
				<div class="edr-field">
					<div class="edr-field__label"><label for="membership-id"><?php _e( 'Membership Level', 'educator' ); ?></label></div>
					<div class="edr-field__control">
						<select id="membership-id" name="membership_id">
							<?php
								if ( $memberships ) {
									foreach ( $memberships as $membership ) {
										$selected = ( $membership->ID == $user_membership['membership_id'] ) ? ' selected="selected"' : '';
										echo '<option value="' . intval( $membership->ID ) . '"' . $selected . '>' .
											esc_html( $membership->post_title ) . '</option>';
									}
								}
							?>
						</select>
					</div>
				</div>

				<!-- Status -->
				<div class="edr-field">
					<div class="edr-field__label"><label for="membership-status"><?php _e( 'Status', 'educator' ); ?></label></div>
					<div class="edr-field__control">
						<select id="membership-status" name="membership_status">
							<?php
								foreach ( $statuses as $key => $value ) {
									$selected = ( $key == $user_membership['status'] ) ? ' selected="selected"' : '';
									echo '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $value ) . '</option>';
								}
							?>
						</select>
					</div>
				</div>

				<!-- Expiration Date -->
				<div class="edr-field">
					<div class="edr-field__label"><label for="membership-expiration"><?php _e( 'Expiration Date', 'educator' ); ?></label></div>
					<div class="edr-field__control">
						<input type="text" id="membership-expiration" name="expiration" value="<?php echo ( ! empty( $user_membership['expiration'] ) ) ? esc_attr( date( 'Y-m-d H:i:s', $user_membership['expiration'] ) ) : '0000-00-00 00:00:00'; ?>">
						<div class="edr-field__info">
							<?php _e( 'Enter the date like yyyy-mm-dd hh:mm:ss', 'educator' ); ?>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="edr-box">
			<div class="edr-box__header">
				<div class="edr-box__title"><?php _e( 'Payments', 'educator' ); ?></div>
			</div>
			<div class="edr-box__body">
				<?php if ( ! empty( $payments ) ) : ?>
					<ul class="edr-member-payments">
						<?php
							foreach ( $payments as $payment ) {
								$edit_payment_url = admin_url( 'admin.php?page=edr_admin_payments&edr-action=edit-payment&payment_id=' . $payment->ID );
								echo '<li><a href="' . esc_url( $edit_payment_url ) . '">#' . intval( $payment->ID );
								echo ' (' . esc_html( date( 'Y-m-d', strtotime( $payment->payment_date ) ) ) . ')</a></li>';
							}
						?>
					</ul>
				<?php else : ?>
					<p><?php _e( 'No payments found.', 'educator' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<?php submit_button( null, 'primary', 'submit', false ); ?>
	</form>
</div>

<script>
jQuery(document).ready(function() {
	EdrLib.select(document.getElementById('user-id'), {
		key:      'id',
		label:    'name',
		searchBy: 'name',
		url:      '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
		ajaxArgs: {
			action: 'edr_select_users',
			_wpnonce: '<?php echo esc_js( wp_create_nonce( 'edr_select_users' ) ); ?>'
		}
	});

	postboxes.add_postbox_toggles(pagenow);
});
</script>
