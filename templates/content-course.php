<?php
/**
 * Renders each course in the shortcode-courses.php template.
 *
 * @version 1.0.0
 */

$thumb_size = apply_filters( 'edr_courses_thumb_size', 'thumbnail' );
?>
<article id="course-<?php the_ID(); ?>" class="edr-course">
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="edr-course__image">
			<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( $thumb_size ); ?></a>
		</div>
	<?php endif; ?>

	<header class="edr-course__header">
		<h2 class="edr-course__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
	</header>

	<div class="edr-course__summary">
		<?php the_excerpt(); ?>
	</div>
</article>
