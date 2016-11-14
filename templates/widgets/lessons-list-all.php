<?php
$current_lesson_id = get_the_ID();
?>
<?php if ( ! empty( $lessons ) ) : ?>
	<ul class="lessons-widget">
		<?php foreach ( $lessons as $lesson ) : ?>
			<li<?php echo ( $current_lesson_id == $lesson->ID ) ? ' class="current-lesson"' : '' ?>>
				<a href="<?php echo esc_url( get_permalink( $lesson->ID ) ); ?>"><?php echo esc_html( $lesson->post_title ); ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
