<?php
/**
 * Renders the student's courses page.
 *
 * @version 1.0.0
 */

$user_id = get_current_user_id();

if ( ! $user_id ) {
	echo '<p>' . __( 'Please log in to view this page.', 'educator' ) . '</p>';
	return;
}

$edr_courses = Edr_Courses::get_instance();
$courses = $edr_courses->get_student_courses( $user_id );
$pending_courses = $edr_courses->get_pending_courses( $user_id );
$edr_memberships = Edr_Memberships::get_instance();

// Output status message.
$message = get_query_var( 'edr-message' );

if ( 'payment-cancelled' == $message ) {
	echo '<div class="edr-messages"><div class="edr-message success">' . __( 'Payment has been cancelled.', 'educator' ) . '</div></div>';
}

if ( $courses || $pending_courses ) {
	if ( $pending_courses ) {
		/**
		 * Pending Payment.
		 */
		echo '<h3>' . __( 'Pending Payment', 'educator' ) . '</h3>';
		echo '<table class="edr-student-courses edr-student-courses_pending">';
		echo '<thead><tr><th style="width:20%;">' . _x( 'Payment', 'Table column heading', 'educator' ) . '</th><th style="width:50%;">' . __( 'Course', 'educator' ) . '</th><th>' . __( 'Actions', 'educator' ) . '</th></tr></thead>';
		echo '<tbody>';

		$gateways = Edr_Main::get_instance()->get_gateways();
		
		foreach ( $pending_courses as $course ) {
			?>
			<tr>
				<td><?php echo intval( $course->edr_payment_id ); ?></td>
				<td class="title">
					<a href="<?php echo esc_url( get_permalink( $course->ID ) ); ?>"><?php echo esc_html( $course->post_title ); ?></a>
					<?php
						// Output payment gateway instructions.
						if ( isset( $gateways[ $course->edr_payment->payment_gateway ] ) ) {
							$description = $gateways[ $course->edr_payment->payment_gateway ]->get_option( 'description' );

							if ( $description ) {
								?>
								<div class="payment-description">
									<a class="open-description" href="#"><?php _e( 'View payment instructions', 'educator' ); ?></a>
									<div class="text"><?php echo wpautop( stripslashes( $description ) ); ?></div>
								</div>
								<?php
							}
						}
					?>
				</td>
				<td>
					<?php
						$cancel_payment_url = add_query_arg( array(
							'edr-action' => 'cancel-payment',
							'payment_id' => $course->edr_payment_id,
							'page_id'    => get_the_ID(),
						), get_permalink() );
						$cancel_payment_url = wp_nonce_url( $cancel_payment_url, 'edr_cancel_payment', '_wpnonce' );
					?>
					<a href="<?php echo esc_url( $cancel_payment_url ); ?>" class="cancel-payment"><?php _e( 'Cancel', 'educator' ); ?></a>
				</td>
			</tr>
			<?php
		}

		echo '</tbody></table>';
	}

	if ( $courses && $courses['entries'] ) {
		/**
		 * In Progress.
		 */
		if ( array_key_exists( 'inprogress', $courses['statuses'] ) ) {
			echo '<h3>' . __( 'In Progress', 'educator' ) . '</h3>';
			echo '<table class="edr-student-courses edr-student-courses_inprogress">';
			echo '<thead><tr><th style="width:20%;">' . __( 'Entry ID', 'educator' ) . '</th><th style="width:50%;">' . __( 'Course', 'educator' ) . '</th><th>' . __( 'Date taken', 'educator' ) . '</th></tr></thead>';
			echo '<tbody>';
			
			foreach ( $courses['entries'] as $entry ) {
				if ( 'inprogress' == $entry->entry_status && isset( $courses['courses'][ $entry->course_id ] ) ) {
					$course = $courses['courses'][ $entry->course_id ];
					$date = date_i18n( get_option( 'date_format' ), strtotime( $entry->entry_date ) );
					?>
					<tr>
						<td><?php echo intval( $entry->ID ); ?></td>
						<td><a class="title" href="<?php echo esc_url( get_permalink( $course->ID ) ); ?>"><?php echo esc_html( $course->post_title ); ?></a></td>
						<td class="date"><?php echo esc_html( $date ); ?></td>
					</tr>
					<?php
				}
			}

			echo '</tbody></table>';
		}

		/**
		 * Complete.
		 */
		if ( array_key_exists( 'complete', $courses['statuses'] ) ) {
			/**
			 * This filter can be used to add/remove headings to/from the courses list table.
			 *
			 * @param array $headings
			 * @param string $entry_status
			 */
			$headings = apply_filters( 'edr_student_courses_headings', array(
				'entry'  => '<th>' . __( 'Entry ID', 'educator' ) . '</th>',
				'course' => '<th>' . __( 'Course', 'educator' ) . '</th>',
				'grade'  => '<th>' . __( 'Grade', 'educator' ) . '</th>',
			), 'complete' );

			echo '<h3>' . __( 'Completed', 'educator' ) . '</h3>';
			echo '<table class="edr-student-courses edr-student-courses_complete">';
			echo '<thead><tr>';

			foreach ( $headings as $th ) {
				echo $th;
			}

			echo '</tr></thead>';
			echo '<tbody>';
			
			foreach ( $courses['entries'] as $entry ) {
				if ( 'complete' == $entry->entry_status && isset( $courses['courses'][ $entry->course_id ] ) ) {
					$course = $courses['courses'][ $entry->course_id ];

					/**
					 * This filter can be used to add/remove column values to/from the courses list table.
					 *
					 * @param array $values
					 * @param string $entry_status
					 * @param Edr_Entry $entry
					 */
					$values = apply_filters( 'edr_student_courses_values', array(
						'entry'  => '<td>' . (int) $entry->ID . '</td>',
						'course' => '<td><a class="title" href="' . esc_url( get_permalink( $course->ID ) ) . '">' . esc_html( $course->post_title ) . '</a></td>',
						'grade'  => '<td class="grade">' . edr_format_grade( $entry->grade ) . '</td>',
					), 'complete', $entry );

					echo '<tr>';

					foreach ( $values as $td ) {
						echo $td;
					}

					echo '</tr>';
				}
			}

			echo '</tbody></table>';
		}

		/**
		 * Paused.
		 */
		if ( array_key_exists( 'paused', $courses['statuses'] ) ) {
			echo '<h3>' . __( 'Paused', 'educator' ) . '</h3>';
			echo '<table class="edr-student-courses edr-student-courses_paused">';
			echo '<thead><tr><th style="width:20%;">' . __( 'Entry ID', 'educator' ) . '</th><th style="width:50%;">' . __( 'Course', 'educator' ) . '</th><th>' . __( 'Actions', 'educator' ) . '</th></tr></thead>';
			echo '<tbody>';
			
			foreach ( $courses['entries'] as $entry ) {
				if ( 'paused' == $entry->entry_status && isset( $courses['courses'][ $entry->course_id ] ) ) {
					$course = $courses['courses'][ $entry->course_id ];
					$membership_allows = $edr_memberships->can_join_course( $course->ID, $user_id );
					$resume_url = '';

					if ( $membership_allows ) {
						$resume_url = add_query_arg( 'edr-action', 'resume-entry', get_permalink() );
						$resume_url = add_query_arg( 'entry_id', $entry->ID, $resume_url );
						$resume_url = wp_nonce_url( $resume_url, 'edr_resume_entry' );
					}
					?>
					<tr>
						<td><?php echo intval( $entry->ID ); ?></td>
						<td><a class="title" href="<?php echo esc_url( get_permalink( $course->ID ) ); ?>"><?php echo esc_html( $course->post_title ); ?></a></td>
						<td>
							<?php if ( $resume_url ) : ?>
								<a href="<?php echo esc_url( $resume_url ); ?>"><?php _e( 'Resume', 'educator' ); ?></a>
							<?php else : ?>
								<span class="resume-disabled"><?php _e( 'Resume', 'educator' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<?php
				}
			}

			echo '</tbody></table>';
		}
	}
} else {
	echo '<p>' . __( 'You are not registered for any course.', 'educator' ) . ' <a href="' . esc_url( get_post_type_archive_link( EDR_PT_COURSE ) ) . '">' . __( 'Browse courses', 'educator' ) . '</a></p>';
}
?>

<script>
(function($) {
	$('.edr-student-courses_pending .open-description').on('click', function(e) {
		e.preventDefault();
		$(this).parent().toggleClass('open');
	});
})(jQuery);
</script>
