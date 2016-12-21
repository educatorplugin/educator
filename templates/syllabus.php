<?php
global $post;
?>
<?php if ( ! empty( $syllabus ) ) : ?>
	<h2 class="edr-syllabus-title"><?php _e( 'Lessons', 'educator' ); ?></h2>
	<div class="edr-syllabus">
		<?php foreach ( $syllabus as $group ) : ?>
			<?php if ( ! empty( $group['lessons'] ) ) : ?>
				<div class="group">
					<div class="group-header"><h3 class="group-title"><?php echo esc_html( $group['title'] ); ?></h3></div>
					<div class="group-body">
						<ul class="edr-lessons">
							<?php
								foreach ( $group['lessons'] as $lesson_id ) {
									if ( isset( $lessons[ $lesson_id ] ) ) {
										$post = $lessons[ $lesson_id ];
										setup_postdata( $post );
										?>
											<li class="lesson">
												<div class="lesson-header"><a class="lesson-title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></div>
												<?php if ( has_excerpt() ) : ?>
													<div class="lesson-excerpt"><?php the_excerpt(); ?></div>
												<?php endif; ?>
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
