<?php
/**
 * Renders the [memberships_page] shortcode.
 *
 * @version 1.0.0
 */

$query = new WP_Query( array(
	'post_type'      => EDR_PT_MEMBERSHIP,
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'order'          => 'ASC',
	'orderby'        => 'menu_order',
) );

if ( $query->have_posts() ) :
	$tmp_more = $GLOBALS['more'];
	$GLOBALS['more'] = 0;
	$columns = isset( $atts['columns'] ) ? $atts['columns'] : 1;
	?>
	<div class="edr-memberships edr-memberships_<?php echo intval( $columns ); ?>">
	<?php
		while ( $query->have_posts() ) {
			$query->the_post();
			Edr_View::template_part( 'content', 'membership' );
		}
	?>
	</div>
	<?php
	$GLOBALS['more'] = $tmp_more;
	wp_reset_postdata();
else :
	echo '<p>' . __( 'No memberships found.', 'educator' ) . '</p>';
endif;
?>
