<?php

// Sanitize question title before saving.
add_filter( 'edr_question_pre_title', 'sanitize_text_field' );

// Sanitize question content before saving.
add_filter( 'edr_question_pre_content', 'edr_kses_data' );

// Sanitize choice text before saving.
add_filter( 'edr_choice_pre_text', 'edr_kses_data' );

// Sanitize question title before output.
add_filter( 'edr_get_question_title', 'esc_html' );
