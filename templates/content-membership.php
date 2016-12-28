<?php
/**
 * Renders each membership in the [memberships_page] shortcode.
 *
 * @version 1.1.0
 */

$obj_memberships = Edr_Memberships::get_instance();
$membership_id = get_the_ID();
$classes = apply_filters( 'edr_membership_classes', array( 'edr-membership' ) );
?>
<article id="membership-<?php the_ID(); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<div class="edr-membership__header">
		<h2 class="edr-membership__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
		<div class="edr-membership__price"><?php echo edr_get_the_membership_price( $membership_id ); ?></div>
	</div>
	<div class="edr-membership__summary">
		<?php the_content( '' ); ?>
	</div>
	<div class="edr-membership__footer">
		<a class="edr-membership__more" href="<?php the_permalink(); ?>"><?php _e( 'Read more', 'educator' ); ?></a>
		<?php echo edr_get_membership_buy_link( $membership_id ); ?>
	</div>
</article>
