<?php
global $post;
$current_lesson_id = get_the_ID();
?>
<?php if ( ! empty( $syllabus ) ) : ?>
	<div class="syllabus-widget">
		<?php foreach ( $syllabus as $group ) : ?>
			<?php if ( ! empty( $group['lessons'] ) ) : ?>
				<div class="group">
					<div class="group-header"><span class="group-title"><?php echo esc_html( $group['title'] ); ?></span></div>
					<div class="group-body">
						<ul class="syllabus-widget__lessons">
							<?php
								foreach ( $group['lessons'] as $lesson_id ) {
									if ( isset( $lessons[ $lesson_id ] ) ) {
										$post = $lessons[ $lesson_id ];
										setup_postdata( $post );
										?>
											<li<?php echo ( $current_lesson_id == $lesson_id ) ? ' class="current-lesson"' : '' ?>>
												<a class="lesson-title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
											</li>
										<?php
									}
								}
								wp_reset_postdata();
							?>
						</ul>
					</div>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
