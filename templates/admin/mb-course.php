<?php
// Setup form object.
$form = new Edr_Form();
$form->default_decorators();

// Registration.
$form->set_value( '_edr_register', get_post_meta( $post->ID, '_edr_register', true ) );
$form->add( array(
	'type'    => 'select',
	'name'    => '_edr_register',
	'label'   => __( 'Registration', 'educator' ),
	'options' => array(
		'open'   => __( 'Open', 'educator' ),
		'closed' => __( 'Closed', 'educator' ),
	),
	'default' => 'open',
) );

// Price.
$form->set_value( '_edr_price', Edr_Courses::get_instance()->get_course_price( $post->ID ) );
$form->add( array(
	'type'   => 'text',
	'name'   => '_edr_price',
	'class'  => '',
	'id'     => 'edr-price',
	'label'  => __( 'Price', 'educator' ),
	'before' => esc_html( edr_get_currency_symbol( edr_get_currency() ) ) . ' ',
) );

// Tax Class.
$tax_manager = Edr_TaxManager::get_instance();
$form->set_value( '_edr_tax_class', $tax_manager->get_tax_class_for( $post->ID ) );
$form->add( array(
	'type'    => 'select',
	'name'    => '_edr_tax_class',
	'label'   => __( 'Tax Class', 'educator' ),
	'options' => $tax_manager->get_tax_classes(),
	'default' => 'default',
) );

// Difficulty.
$form->set_value( '_edr_difficulty', get_post_meta( $post->ID, '_edr_difficulty', true ) );
$form->add( array(
	'type'    => 'select',
	'name'    => '_edr_difficulty',
	'id'      => 'edr-difficulty',
	'label'   => __( 'Difficulty', 'educator' ),
	'options' => array_merge( array( '' => __( 'None', 'educator' ) ), edr_get_difficulty_levels() ),
) );

// Prerequisite.
$courses = array( '' => __( 'None', 'educator' ) );
$tmp = get_posts( array(
	'post_type'      => EDR_PT_COURSE,
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'exclude'        => array( $post->ID ),
) );

foreach ( $tmp as $course ) {
	$courses[ $course->ID ] = $course->post_title;
}

$prerequisites = Edr_Courses::get_instance()->get_course_prerequisites( $post->ID );
$form->set_value( '_edr_prerequisites', array_pop( $prerequisites ) );
$form->add( array(
	'type'    => 'select',
	'name'    => '_edr_prerequisites',
	'id'      => 'edr-prerequisite',
	'label'   => __( 'Prerequisite', 'educator' ),
	'options' => $courses,
) );

wp_nonce_field( 'edr_course_meta_box', 'edr_course_meta_box_nonce' );

$form->display();
?>
<script>
(function($) {
	$(document).on('edrSelect.shown', function(e, edrSelect) {
		var txtCreateLesson = '<?php echo esc_js( __( 'Press enter to create this lesson', 'educator' ) ); ?>';
		var choicesContainer = edrSelect.choicesDiv.find('.choices');

		if (edrSelect.currentFilterValue && choicesContainer.html() == '') {
			choicesContainer.html('<div class="choices-not-found">' + txtCreateLesson + '</div>');
		}
	});
})(jQuery);
</script>
