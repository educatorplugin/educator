<?php
/**
 * Renders the student's membership page.
 *
 * @version 1.1.0
 */

$user_id = get_current_user_id();

if ( ! $user_id ) {
	echo '<p>' . __( 'Please log in to view this page.', 'educator' ) . '</p>';
	return;
}

$edr_memberships = Edr_Memberships::get_instance();

// Get current user's membership data.
$user_membership = $edr_memberships->get_user_membership_by( 'user_id', $user_id );

if ( ! $user_membership ) {
	echo '<p>' . __( 'Your account is not connected to a membership.', 'educator' ) . '</p>';
	return;
}

// Get membership data.
$membership = $edr_memberships->get_membership( $user_membership['membership_id'] );
$period = $edr_memberships->get_period( $user_membership['membership_id'] );
$statuses = $edr_memberships->get_statuses();

if ( ! $membership ) {
	return;
}
?>
<table class="edr-user-membership">
	<tbody>
		<tr>
			<th style="width:30%;"><?php _e( 'Membership Level', 'educator' ); ?></th>
			<td>
				<?php echo esc_html( $membership->post_title ); ?>
				<div>
					<?php
						echo edr_get_buy_widget( array(
							'object_id'   => $membership->ID,
							'object_type' => EDR_PT_MEMBERSHIP,
							'label'       => __( 'Extend', 'educator' ),
						) );
					?>
				</div>
			</td>
		</tr>
		<tr>
			<th><?php _e( 'Status', 'educator' ); ?></th>
			<td>
				<?php
					if ( ! empty( $user_membership['status'] ) && array_key_exists( $user_membership['status'], $statuses ) ) {
						echo esc_html( $statuses[ $user_membership['status'] ] );
					}
				?>
			</td>
		</tr>
		<tr>
			<th><?php _e( 'Expiration Date', 'educator' ); ?></th>
			<td>
				<?php
					if ( ! $user_membership['expiration'] ) {
						_e( 'None', 'educator' );
					} else {
						$date_format = get_option( 'date_format' );

						if ( 'days' == $period ) {
							$date_format .= ' ' . get_option( 'time_format' );
						}

						echo esc_html( date_i18n( $date_format, $user_membership['expiration'] ) );
					}
				?>
			</td>
		</tr>
	</tbody>
</table>
