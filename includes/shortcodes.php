<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * SHORTCODE: output student's courses.
 */
function edr_sc_student_courses( $atts, $content = null ) {
	$template = Edr_View::locate_template( array( 'shortcodes/student-courses.php' ) );

	if ( ! $template ) {
		return;
	}

	ob_start();
	include $template;
	return ob_get_clean();
}
add_shortcode( 'edr_student_courses', 'edr_sc_student_courses' );

/**
 * SHORTCODE: output payment page.
 */
function edr_sc_payment( $atts, $content = null ) {
	$template = Edr_View::locate_template( array( 'shortcodes/payment.php' ) );

	if ( ! $template ) {
		return;
	}

	ob_start();
	include $template;
	return ob_get_clean();
}
add_shortcode( 'edr_payment', 'edr_sc_payment' );

/**
 * SHORTCODE: output membership page.
 */
function edr_sc_memberships( $atts, $content = null ) {
	$template = Edr_View::locate_template( array( 'shortcodes/memberships.php' ) );

	if ( ! $template ) {
		return;
	}

	ob_start();
	include $template;
	return ob_get_clean();
}
add_shortcode( 'edr_memberships', 'edr_sc_memberships' );

/**
 * SHORTCODE: output membership page.
 */
function edr_sc_user_membership( $atts, $content = null ) {
	$template = Edr_View::locate_template( array( 'shortcodes/user-membership.php' ) );

	if ( ! $template ) {
		return;
	}

	ob_start();
	include $template;
	return ob_get_clean();
}
add_shortcode( 'edr_user_membership', 'edr_sc_user_membership' );

/**
 * SHORTCODE: output the user's payments page.
 */
function edr_sc_user_payments( $atts, $content = null ) {
	$template = Edr_View::locate_template( array( 'shortcodes/user-payments.php' ) );

	if ( ! $template ) {
		return;
	}

	ob_start();
	include $template;
	return ob_get_clean();
}
add_shortcode( 'edr_user_payments', 'edr_sc_user_payments' );

/**
 * SHORTCODE: courses.
 */
function edr_sc_courses( $atts, $content = null ) {
	$template = Edr_View::locate_template( array( 'shortcodes/courses.php' ) );

	if ( ! $template ) {
		return;
	}

	$args = array(
		'post_type'      => EDR_PT_COURSE,
		'posts_per_page' => isset( $atts['number'] ) ? intval( $atts['number'] ) : 10,
		'post_status'    => 'publish',
	);

	if ( ! isset( $atts['nopaging'] ) || 1 != $atts['nopaging'] ) {
		if ( get_query_var( 'paged' ) ) {
			$args['paged'] = get_query_var( 'paged' );
		} elseif ( get_query_var( 'page' ) ) {
			$args['paged'] = get_query_var( 'page' );
		} else {
			$args['paged'] = 1;
		}
	}

	if ( isset( $atts['categories'] ) ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => EDR_TX_CATEGORY,
				'field'    => 'term_id',
				'terms'    => array_map( 'intval', explode( ',', $atts['categories'] ) ),
			),
		);
	}

	if ( isset( $atts['ids'] ) ) {
		$args['post__in'] = array_map( 'intval', explode( ',', $atts['ids'] ) );
	}

	if ( isset( $atts['orderby'] ) ) {
		switch ( $atts['orderby'] ) {
			case 'id':
				$args['orderby'] = 'ID';
				break;

			case 'random':
				$args['orderby'] = 'rand';
				break;

			default:
				$args['orderby'] = $atts['orderby'];
		}
	}

	if ( isset( $atts['order'] ) ) {
		$args['order'] = $atts['order'];
	}

	$query_args = apply_filters( 'edr_courses_query_args', $args, $atts );
	$courses = new WP_Query( $query_args );

	ob_start();
	include $template;
	return ob_get_clean();
}
add_shortcode( 'courses', 'edr_sc_courses' );

/**
 * SHORTCODE: output the course prerequisites.
 */
function edr_sc_course_prerequisites( $atts, $content = null ) {
	$template = Edr_View::locate_template( array( 'shortcodes/course-prerequisites.php' ) );

	if ( ! $template ) {
		return;
	}

	$edr_courses = Edr_Courses::get_instance();
	$prerequisites = $edr_courses->get_course_prerequisites( get_the_ID() );
	$courses = null;

	if ( ! empty( $prerequisites ) ) {
		$courses = get_posts( array(
			'post_type'   => EDR_PT_COURSE,
			'post_status' => 'publish',
			'include'     => $prerequisites,
		) );
	} else {
		$courses = array();
	}
	
	ob_start();
	include $template;
	return ob_get_clean();
}
add_shortcode( 'course_prerequisites', 'edr_sc_course_prerequisites' );
