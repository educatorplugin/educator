<?php
wp_nonce_field( 'edr_lesson_meta_box', 'edr_lesson_meta_box_nonce' );

// Lesson access.
$access = get_post_meta( $post->ID, '_edr_access', true );

if ( empty( $access ) ) {
	$access = 'registered';
}

// Course.
$course_id = get_post_meta( $post->ID, '_edr_course_id', true );
$courses = get_posts( array(
	'post_type'      => EDR_PT_COURSE,
	'posts_per_page' => -1,
) );
?>

<div class="edr-field">
	<div class="edr-field__label">
		<label for="course-access"><?php _e( 'Access', 'educator' ); ?></label>
	</div>
	<div class="edr-field__control">
		<select id="course-access" name="_edr_access">
			<?php
				$access_options = array(
					'registered' => __( 'Registered users', 'educator' ),
					'logged_in'  => __( 'Logged in users', 'educator' ),
					'public'     => __( 'Everyone', 'educator' ),
				);

				foreach ( $access_options as $key => $label ) {
					echo '<option value="' . $key . '"' . selected( $key, $access, false ) . '>' . $label . '</option>';
				}
			?>
		</select>
	</div>
</div>

<?php if ( ! empty( $courses ) ) : ?>
	<div class="edr-field">
		<div class="edr-field__label">
			<label for="course-id"><?php _e( 'Course', 'educator' ); ?></label>
		</div>
		<div class="edr-field__control">
			<select id="course-id" name="_edr_course_id">
				<option value=""><?php _e( 'Select Course', 'educator' ); ?></option>
				<?php foreach ( $courses as $post ) : ?>
					<option value="<?php echo intval( $post->ID ); ?>"<?php if ( $course_id == $post->ID ) echo ' selected="selected"'; ?>>
						<?php echo esc_html( $post->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
<?php endif; ?>
