<?php
$ms = Edr_Memberships::get_instance();
$price = $ms->get_price( $post->ID );
$duration = $ms->get_duration( $post->ID );
$period = $ms->get_period( $post->ID );
$categories = $ms->get_categories( $post->ID );
$periods = $ms->get_periods();

// Setup form object.
$form = new Edr_Form();
$form->default_decorators();

// Price.
$form->set_value( '_edr_price', $price );
$form->add( array(
	'type'   => 'text',
	'name'   => '_edr_price',
	'id'     => 'edr-membership-price',
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

// Period.
$period_html = '<select name="_edr_period">';

foreach ( $periods as $mp_value => $mp_name ) {
	$period_html .= '<option value="' . esc_attr( $mp_value ) . '"' . selected( $period, $mp_value, false ) . '>' . esc_html( $mp_name ) . '</option>';
}

$period_html .= '</select>';

// Duration.
$form->set_value( '_edr_duration', $duration );
$form->add( array(
	'type'   => 'text',
	'name'   => '_edr_duration',
	'id'     => 'edr-membership-duration',
	'class'  => 'small-text',
	'label'  => __( 'Duration', 'educator' ),
	'after'  => " $period_html",
) );

// Categories.
$categories_options = array( '' => __( 'Select Categories', 'educator' ) );
$terms = get_terms( EDR_TX_CATEGORY );

if ( $terms && ! is_wp_error( $terms ) ) {
	foreach ( $terms as $term ) {
		$categories_options[ $term->term_id ] = $term->name;
	}
}

$form->set_value( '_edr_categories', $categories );
$form->add( array(
	'type'     => 'select',
	'name'     => '_edr_categories',
	'label'    => __( 'Categories', 'educator' ),
	'multiple' => true,
	'size'     => 5,
	'options'  => $categories_options,
) );

// Display the form.
wp_nonce_field( 'edr_membership_meta_box', 'edr_membership_meta_box_nonce' );
$form->display();
?>
