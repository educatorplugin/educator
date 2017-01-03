<?php
/**
 * Renders each course in the shortcode-courses.php template.
 *
 * @version 1.0.1
 */

$edr_courses = Edr_Courses::get_instance();
$course_id = get_the_ID();
$price = $edr_courses->get_course_price( $course_id );
$price_str = ( $price > 0 ) ? edr_format_price( $price ) : _x( 'Free', 'price', 'novolearn' );
$thumb_size = apply_filters( 'edr_courses_thumb_size', 'thumbnail' );
?>
<article id="course-<?php echo intval( $course_id ); ?>" class="edr-course">
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="edr-course__image">
			<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( $thumb_size ); ?></a>
		</div>
	<?php endif; ?>

	<header class="edr-course__header">
		<h2 class="edr-course__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<div class="edr-course__price"><?php echo $price_str; ?></div>
	</header>

	<div class="edr-course__summary">
		<?php the_excerpt(); ?>
	</div>
</article>
