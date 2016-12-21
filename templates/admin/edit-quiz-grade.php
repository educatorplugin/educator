<?php
$obj_quizzes = Edr_Quizzes::get_instance();
$grade_id = ! empty( $_GET['grade_id'] ) ? intval( $_GET['grade_id'] ) : 0;
$grade = edr_get_quiz_grade( $grade_id );

if ( ! $grade->ID ) {
	return;
}

$post = get_post( $grade->lesson_id );
$user = get_user_by( 'id', $grade->user_id );
$form_action = admin_url( 'admin.php?page=edr_admin_quiz_grades&edr-action=edit-quiz-grade&grade_id=' . $grade_id );
?>
<div class="wrap">
	<h2><?php _e( 'Edit Quiz Grade', 'educator' ); ?></h2>

	<form id="edr-edit-quiz-grade-form" class="edr-admin-form" action="<?php echo esc_url( $form_action ); ?>" method="post">
		<?php wp_nonce_field( 'edr_edit_quiz_grade_' . $grade->ID ); ?>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-1">
				<div id="postbox-container-1" class="postbox-container">
					<div id="normal-sortables" class="meta-box-sortables">
						<div id="quiz-grade-info" class="postbox">
							<div class="handlediv"><br></div>
							<h3 class="hndle"><span><?php _e( 'Grade Info', 'educator' ); ?></span></h3>
							<div class="inside">
								<div class="edr-field">
									<div class="edr-field__label">
										<label><?php _e( 'User', 'educator' ); ?></label>
									</div>
									<div class="edr-field__control">
										<?php
											if ( $user ) {
												echo esc_html( edr_get_user_full_name( $user ) );
											}
										?>
									</div>
								</div>

								<div class="edr-field">
									<div class="edr-field__label">
										<label><?php _e( 'Post', 'educator' ); ?></label>
									</div>
									<div class="edr-field__control">
										<?php
											if ( $post ) {
												echo esc_html( $post->post_title );
											}
										?>
									</div>
								</div>

								<div class="edr-field">
									<div class="edr-field__label">
										<label><?php _e( 'Entry', 'educator' ); ?></label>
									</div>
									<div class="edr-field__control">
										<?php if ( $grade->entry_id ) : ?>
											<?php
												$url_edit_entry = add_query_arg( array(
													'page'       => 'edr_admin_entries',
													'edr-action' => 'edit-entry',
													'entry_id'   => $grade->entry_id,
												), admin_url( 'admin.php' ) );
											?>
											<a href="<?php echo esc_url( $url_edit_entry ); ?>" title="<?php _e( 'Edit Entry', 'educator' ); ?>" target="_blank"><?php echo intval( $grade->entry_id ); ?></a>
										<?php else : ?>
											<?php _e( 'None', 'educator' ); ?>
										<?php endif; ?>
									</div>
								</div>

								<div class="edr-field">
									<div class="edr-field__label">
										<label><?php _e( 'Status', 'educator' ); ?></label>
									</div>
									<div class="edr-field__control">
										<?php
											echo esc_html( $grade->status );
										?>
									</div>
								</div>

								<?php if ( $post ) : ?>
									<?php
										Edr_View::the_template( 'admin/edit-quiz-grade-form', array( 'grade' => $grade ) );
									?>
								<?php endif; ?>
							</div>
						</div><!-- end #quiz-grade-info -->
					</div><!-- end #normal-sortables -->
				</div><!-- end #postbox-container-2 -->
			</div><!-- end #post-body -->
		</div><!-- end #poststuff -->
	</form>
</div>

<script>
jQuery(document).ready(function() {
	postboxes.add_postbox_toggles(pagenow);
});
</script>
