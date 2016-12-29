<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$entry_id = isset( $_GET['entry_id'] ) ? intval( $_GET['entry_id'] ) : 0;
$entry = edr_get_entry( $entry_id );
$who = '';

if ( current_user_can( 'manage_educator' ) ) {
	$who = 'admin';
} elseif ( $entry->course_id && current_user_can( 'edit_' . EDR_PT_COURSE, $entry->course_id ) ) {
	$who = 'lecturer';
}

// Check capabilities.
if ( empty( $who ) ) {
	// Current user cannot create entries.
	echo '<p>' . __( 'Access denied', 'educator' ) . '</p>';
	return;
}

$statuses = edr_get_entry_statuses();
$origins = edr_get_entry_origins();
$student = null;
$course = null;
$quizzes = null;
$input = array(
	'payment_id'    => isset( $_POST['payment_id'] ) ? $_POST['payment_id'] : $entry->payment_id,
	'membership_id' => isset( $_POST['membership_id'] ) ? $_POST['membership_id'] : $entry->object_id,
	'entry_origin'  => isset( $_POST['entry_origin'] ) ? $_POST['entry_origin'] : $entry->entry_origin,
	'entry_status'  => isset( $_POST['entry_status'] ) ? $_POST['entry_status'] : $entry->entry_status,
	'grade'         => isset( $_POST['grade'] ) ? $_POST['grade'] : $entry->grade,
	'entry_date'    => isset( $_POST['entry_date'] ) ? $_POST['entry_date'] : ( ! empty( $entry->entry_date ) ? $entry->entry_date : date( 'Y-m-d H:i:s' ) ),
);

if ( 'admin' == $who && isset( $_POST['student_id'] ) ) {
	$student = get_user_by( 'id', $_POST['student_id'] );
} elseif ( $entry->ID ) {
	$student = get_user_by( 'id', $entry->user_id );
}

if ( 'admin' == $who && isset( $_POST['course_id'] ) ) {
	$course = get_post( $_POST['course_id'] );
} elseif ( $entry->ID ) {
	$course = get_post( $entry->course_id );
}

if ( $entry->ID ) {
	$quizzes = new WP_Query( array(
		'post_type'      => EDR_PT_LESSON,
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
		'meta_query'     => array(
			'relation' => 'AND',
			array(
				'key'     => '_edr_quiz',
				'value'   => 1,
				'compare' => '='
			),
			array(
				'key'     => '_edr_course_id',
				'value'   => $entry->course_id,
				'compare' => '='
			),
		),
	) );
}

$obj_quizzes = Edr_Quizzes::get_instance();
$form_action = admin_url( 'admin.php?page=edr_admin_entries&edr-action=edit-entry&entry_id=' . $entry_id );
?>
<div class="wrap">
	<h2><?php
		if ( $entry->ID ) {
			_e( 'Edit Entry', 'educator' );
		} else {
			_e( 'Add Entry', 'educator' );
		}
	?></h2>

	<?php if ( isset( $_GET['edr-message'] ) && 'saved' == $_GET['edr-message'] ) : ?>
		<div id="message" class="updated below-h2">
			<p><?php _e( 'Entry saved.', 'educator' ); ?></p>
		</div>
	<?php endif; ?>

	<?php
		// Output error messages.
		$errors = edr_internal_message( 'edit_entry_errors' );

		if ( $errors ) {
			$messages = $errors->get_error_messages();

			foreach ( $messages as $message ) {
				echo '<div class="error"><p>' . $message . '</p></div>';
			}
		}
	?>

	<form id="edr-edit-entry-form" class="edr-admin-form" action="<?php echo esc_url( $form_action ); ?>" method="post">
		<?php wp_nonce_field( 'edr_edit_entry_' . $entry->ID ); ?>

		<?php do_action( 'edr_edit_entry_form_before', $entry ); ?>

		<div id="entry-details" class="edr-box">
			<div class="edr-box__body">
				<!-- Student -->
				<div class="edr-field">
					<div class="edr-field__label">
						<label><?php _e( 'Student', 'educator' ); ?></label>
					</div>
					<div class="edr-field__control">
						<div class="edr-select-values">
							<input
								type="text"
								name="student_id"
								id="entry-student-id"
								class="regular-text"
								autocomplete="off"
								value="<?php if ( $student ) echo intval( $student->ID ); ?>"
								data-label="<?php echo esc_attr( edr_get_user_name( $student, 'select' ) ); ?>"<?php if ( 'admin' != $who ) echo ' disabled="disabled"'; ?>>
						</div>
					</div>
				</div>

				<!-- Course -->
				<div class="edr-field">
					<div class="edr-field__label">
						<label><?php _e( 'Course', 'educator' ); ?></label>
					</div>
					<div class="edr-field__control">
						<div class="edr-select-values">
							<input
								type="text"
								name="course_id"
								id="entry-course-id"
								class="regular-text"
								autocomplete="off"
								value="<?php if ( $course ) echo intval( $course->ID ); ?>"
								data-label="<?php if ( $course ) echo esc_attr( $course->post_title ); ?>"<?php if ( 'admin' != $who ) echo ' disabled="disabled"'; ?>>
						</div>
					</div>
				</div>

				<!-- Status -->
				<div class="edr-field">
					<div class="edr-field__label">
						<label for="entry-status"><?php _e( 'Status', 'educator' ); ?></label>
					</div>
					<div class="edr-field__control">
						<select name="entry_status" id="entry-status">
							<?php foreach ( $statuses as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"<?php if ( $key == $input['entry_status'] ) echo ' selected="selected"'; ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<!-- Date -->
				<div class="edr-field">
					<div class="edr-field__label">
						<label for="entry-date"><?php _e( 'Date', 'educator' ); ?></label>
					</div>
					<div class="edr-field__control">
						<input type="text" id="entry-date" class="regular-text" maxlength="19" size="19" name="entry_date" value="<?php echo esc_attr( $input['entry_date'] ); ?>">
						<div class="edr-field__info"><?php _e( 'Date format: yyyy-mm-dd hh:mm:ss', 'educator' ); ?></div>
					</div>
				</div>

				<?php if ( 'admin' == $who ) : ?>
					<!-- Membership -->
					<?php
						$ms = Edr_Memberships::get_instance();
						$memberships = $ms->get_memberships();
					?>
					<div class="edr-field" data-origin="membership"<?php if ( 'membership' != $input['entry_origin'] ) echo ' style="display:none;"'; ?>>
						<div class="edr-field__label">
							<label for="entry-membership-id"><?php _e( 'Membership', 'educator' ); ?></label>
						</div>
						<div class="edr-field__control">
							<select name="membership_id" id="entry-membership-id">
								<option value=""><?php _e( 'Select Membership', 'educator' ); ?></option>
								<?php
									if ( $memberships ) {
										foreach ( $memberships as $membership ) {
											$selected = ( $input['membership_id'] == $membership->ID ) ? ' selected="selected"' : '';
											echo '<option value="' . esc_attr( $membership->ID ) . '"' . $selected . '>' . esc_html( $membership->post_title ) . '</option>';
										}
									}
								?>
							</select>
						</div>
					</div>

					<!-- Payment ID -->
					<div class="edr-field" data-origin="payment"<?php if ( 'payment' != $input['entry_origin'] ) echo ' style="display:none;"'; ?>>
						<div class="edr-field__label">
							<label for="entry-payment-id"><?php _e( 'Payment ID', 'educator' ); ?></label>
						</div>
						<div class="edr-field__control">
							<input type="text" id="entry-payment-id" class="small-text" maxlength="20" size="6" name="payment_id" value="<?php echo intval( $input['payment_id'] ); ?>">
							<div class="edr-field__info">
								<?php
									printf( __( 'Please find payment ID on %s page.', 'educator' ), '<a href="'
										. admin_url( 'admin.php?page=edr_admin_payments' ) . '" target="_blank">'
										. __( 'Payments', 'educator' ) . '</a>' );
								?>
							</div>
						</div>
					</div>

					<!-- Origin -->
					<div class="edr-field">
						<div class="edr-field__label">
							<label for="entry-origin"><?php _e( 'Origin', 'educator' ); ?></label>
						</div>
						<div class="edr-field__control">
							<select name="entry_origin" id="entry-origin">
								<?php foreach ( $origins as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>"<?php if ( $key == $input['entry_origin'] ) echo ' selected="selected"'; ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				<?php endif; ?>

				<!-- Grade -->
				<div class="edr-field">
					<div class="edr-field__label">
						<label for="entry-grade"><?php _e( 'Final Grade', 'educator' ); ?></label>
					</div>
					<div class="edr-field__control">
						<input type="text" id="entry-grade" class="small-text" maxlength="6" size="6" name="grade" value="<?php echo esc_attr( $input['grade'] ); ?>">
						<div class="edr-field__info"><?php _e( 'A number between 0 and 100.', 'educator' ); ?></div>
					</div>
				</div>

				<!-- Prerequisites -->
				<?php if ( 'admin' == $who ) : ?>
					<div class="edr-field" data-origin="payment"<?php if ( 'payment' != $input['entry_origin'] ) echo ' style="display:none;"'; ?>>
						<div class="edr-field__label">
							<label><?php _e( 'Prerequisites', 'educator' ); ?></label>
						</div>
						<div class="edr-field__control">
							<label><input type="checkbox" name="ignore_prerequisites"> <?php _e( 'Ignore prerequisites', 'educator' ); ?></label>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div><!-- end #entry-details -->

		<?php if ( $quizzes && $quizzes->have_posts() ) : ?>
			<div id="entry-quiz-grades" class="edr-box">
				<div class="edr-box__header">
					<div class="edr-box__title"><?php _e( 'Quiz Grades', 'educator' ); ?></div>
				</div>
				<div class="edr-box__body">
					<div class="edr-quiz-grades">
						<?php while ( $quizzes->have_posts() ) : $quizzes->the_post(); ?>
							<div class="edr-quiz-grade edr-quiz-grade_closed">
								<div class="edr-quiz-grade__title"><span><?php the_title(); ?></span></div>
								<?php
									$grade = $obj_quizzes->get_grade( get_the_ID(), $entry_id );

									Edr_View::the_template( 'admin/edit-quiz-grade-form', array( 'grade' => $grade ) );
								?>
							</div>
						<?php endwhile; ?>

						<?php wp_reset_postdata(); ?>
					</div>
				</div>
			</div><!-- end #entry-quiz-grades -->
		<?php endif; ?>

		<?php submit_button( null, 'primary', 'submit', false ); ?>

		<?php do_action( 'edr_edit_entry_form_after', $entry ); ?>
	</form>
</div>

<script>
jQuery(document).ready(function() {
	function fieldsByOrigin( origin ) {
		jQuery('#edr-edit-entry-form .edr-field').each(function() {
			var forOrigin = this.getAttribute('data-origin');

			if ( forOrigin && forOrigin !== origin ) {
				this.style.display = 'none';
			} else {
				this.style.display = 'block';
			}
		});
	}

	var entryOrigin = jQuery('#entry-origin');

	entryOrigin.on('change', function() {
		fieldsByOrigin(this.value);
	});

	EdrLib.select(document.getElementById('entry-student-id'), {
		key:      'id',
		label:    'name',
		searchBy: 'name',
		url:      <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
		ajaxArgs: {
			action:  'edr_select_users',
			_wpnonce: <?php echo wp_json_encode( wp_create_nonce( 'edr_select_users' ) ); ?>
		}
	});

	EdrLib.select(document.getElementById('entry-course-id'), {
		key:      'id',
		label:    'title',
		searchBy: 'title',
		url:      <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
		ajaxArgs: {
			action:    'edr_select_posts',
			post_type: <?php echo wp_json_encode( EDR_PT_COURSE ); ?>,
			_wpnonce:  <?php echo wp_json_encode( wp_create_nonce( 'edr_select_posts' ) ); ?>
		}
	});

	postboxes.add_postbox_toggles(pagenow);
});
</script>
