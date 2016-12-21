<?php if ( ! empty( $lessons ) ) : ?>
	<h2 class="edr-lessons-title"><?php _e( 'Lessons', 'educator' ); ?></h2>
	<ul class="edr-lessons">
		<?php foreach ( $lessons as $lesson ) : ?>
			<li class="lesson">
				<div class="lesson-header">
					<a class="lesson-title" href="<?php echo esc_url( get_permalink( $lesson->ID ) ); ?>"><?php echo esc_html( $lesson->post_title ); ?></a>
				</div>
				<?php if ( $lesson->post_excerpt ) : ?>
					<div class="lesson-excerpt"><?php echo esc_html( $lesson->post_excerpt ); ?></div>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
