<?php

if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'the_content', 'edr_filter_single_course_content', 91 );
add_filter( 'the_content', 'edr_filter_single_lesson_content', 91 );
add_filter( 'the_content', 'edr_display_quiz' );
add_action( 'the_content', 'edr_display_membership_buy_widget', 20 );

add_action( 'edr_before_single_course_content', 'edr_display_course_errors' );
add_action( 'edr_before_single_course_content', 'edr_display_course_info' );
add_action( 'edr_after_single_course_content', 'edr_display_lessons' );

add_action( 'edr_before_single_lesson_content', 'edr_display_breadcrumbs' );
