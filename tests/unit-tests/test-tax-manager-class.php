<?php

class TaxManagerClassTest extends WP_UnitTestCase {
	protected $tm;

	public function setUp() {
		parent::setUp();
		$this->tm = Edr_TaxManager::get_instance();
	}

	public function setUpTaxRates() {
		$tax_class = array(
			'name'        => 'class1',
			'description' => 'Class1',
		);
		$this->tm->add_tax_class( $tax_class );

		$rate1 = new stdClass();
		$rate1->ID         = null;
		$rate1->name       = 'Rate 1';
		$rate1->country    = 'CA';
		$rate1->state      = '';
		$rate1->tax_class  = $tax_class['name'];
		$rate1->priority   = 0;
		$rate1->rate       = 2.11;
		$rate1->rate_order = 0;
		$rate1->ID         = $this->tm->update_tax_rate( $rate1 );

		$rate2 = new stdClass();
		$rate2->ID = null;
		$rate2->name       = 'Rate 2';
		$rate2->country    = 'CA';
		$rate2->state      = 'AB';
		$rate2->tax_class  = $tax_class['name'];
		$rate2->priority   = 2;
		$rate2->rate       = 2.89;
		$rate2->rate_order = 1;
		$rate2->ID         = $this->tm->update_tax_rate( $rate2 );

		$rate3 = new stdClass();
		$rate3->ID = null;
		$rate3->name       = 'Rate 3';
		$rate3->country    = 'CA';
		$rate3->state      = 'AB';
		$rate3->tax_class  = $tax_class['name'];
		$rate3->priority   = 2;
		$rate3->rate       = 3.78;
		$rate3->rate_order = 2;
		$rate3->ID         = $this->tm->update_tax_rate( $rate3 );

		$rate4 = new stdClass();
		$rate4->ID = null;
		$rate4->name       = 'Rate 4';
		$rate4->country    = 'CA';
		$rate4->state      = 'AB';
		$rate4->tax_class  = $tax_class['name'];
		$rate4->priority   = 1;
		$rate4->rate       = 1.56;
		$rate4->rate_order = 3;
		$rate4->ID         = $this->tm->update_tax_rate( $rate4 );

		return array(
			'tax_class' => $tax_class,
			'rate1'     => $rate1,
			'rate2'     => $rate2,
			'rate3'     => $rate3,
			'rate4'     => $rate4,
		);
	}

	public function test_get_tax_rate() {
		extract( $this->setUpTaxRates() );

		$get_tax_rate = new ReflectionMethod( 'Edr_TaxManager', 'get_tax_rate' );
		$get_tax_rate->setAccessible(true);

		// Assertion 1:
		// Get tax rates for a country.
		$expected_rate = array(
			'inclusive' => 0.0,
			'rates'     => array( $rate1 ),
		);
		$actual_rate = $get_tax_rate->invokeArgs( $this->tm, array( 'class1', 'CA' ) );
		$this->assertEquals( $expected_rate, $actual_rate );

		// Assertion 2:
		// Get tax rates for a country and state.
		// Should return 1st 2 rates and ignore the 3rd one,
		// because rate2 and rate3 have same priority group.
		// rate4 applies before rate3, because its priority
		// group comes earlier.
		$expected_rate = array(
			'inclusive' => 0.0,
			'rates'     => array( $rate1, $rate4, $rate2 ),
		);
		$actual_rate = $get_tax_rate->invokeArgs( $this->tm, array( 'class1', 'CA', 'AB' ) );
		$this->assertEquals( $expected_rate, $actual_rate );

		// Test inclusive taxes, store's country and state match.
		$settings = get_option( 'edr_taxes', array() );
		$settings['tax_inclusive'] = 'y';
		update_option( 'edr_taxes', $settings );
		$settings = get_option( 'edr_settings', array() );
		$settings['location'] = 'CA;AB';
		update_option( 'edr_settings', $settings );

		// Assertion 1:
		// "inclusive" is the sum of the tax rates
		// which belong to the current shop's location
		// (CA and AB in this case).
		// "rates" contains the requested rates only
		// (CA in this case).
		$expected_rate = array(
			'inclusive' => 6.56,
			'rates'     => array( $rate1 ),
		);
		$actual_rate = $get_tax_rate->invokeArgs( $this->tm, array( 'class1', 'CA' ) );
		$this->assertEquals( $expected_rate, $actual_rate );

		// Assertion 2:
		$expected_rate = array(
			'inclusive' => 6.56,
			'rates'     => array( $rate1, $rate4, $rate2 ),
		);
		$actual_rate = $get_tax_rate->invokeArgs( $this->tm, array( 'class1', 'CA', 'AB' ) );
		$this->assertEquals( $expected_rate, $actual_rate );
	}

	public function test_calculate_tax() {
		extract( $this->setUpTaxRates() );

		// Inclusive - n.
		$settings = get_option( 'edr_taxes', array() );
		$settings['tax_inclusive'] = 'n';
		update_option( 'edr_taxes', $settings );

		// Location - CA.
		$settings = get_option( 'edr_settings', array() );
		$settings['location'] = 'CA';
		update_option( 'edr_settings', $settings );

		// Country level.
		$tax1 = new stdClass();
		$tax1->ID     = $rate1->ID;
		$tax1->name   = $rate1->name;
		$tax1->rate   = 2.1100;
		$tax1->amount = 0.19;

		$expected_tax = array(
			'subtotal' => 8.79,
			'total'    => 8.98,
			'tax'      => 0.19,
			'taxes'    => array( $tax1 ),
		);
		$actual_tax = $this->tm->calculate_tax( $tax_class['name'], 8.79, 'CA', '' );
		$this->assertEquals( $expected_tax, $actual_tax );

		// Country + state for which no additional rates exist.
		$actual_tax = $this->tm->calculate_tax( $tax_class['name'], 8.79, 'CA', 'SK' );
		$this->assertEquals( $expected_tax, $actual_tax );

		// Country + state for which rates exist.
		$tax4 = new stdClass();
		$tax4->ID     = $rate4->ID;
		$tax4->name   = $rate4->name;
		$tax4->rate   = 1.56;
		$tax4->amount = 0.14;

		$tax2 = new stdClass();
		$tax2->ID     = $rate2->ID;
		$tax2->name   = $rate2->name;
		$tax2->rate   = 2.89;
		$tax2->amount = 0.25;

		$actual_tax = $this->tm->calculate_tax( $tax_class['name'], 8.79, 'CA', 'AB' );
		$expected_tax = array(
			'subtotal' => 8.79,
			'total'    => 9.37,
			'tax'      => 0.58,
			'taxes'    => array( $tax1, $tax4, $tax2 ),
		);
		$this->assertEquals( $expected_tax, $actual_tax );

		// Inclusive - y.
		$settings = get_option( 'edr_taxes', array() );
		$settings['tax_inclusive'] = 'y';
		update_option( 'edr_taxes', $settings );

		// Location - CA;AB.
		$settings = get_option( 'edr_settings', array() );
		$settings['location'] = 'CA;AB';
		update_option( 'edr_settings', $settings );

		// Inclusive, country level.
		$tax1 = new stdClass();
		$tax1->ID     = $rate1->ID;
		$tax1->name   = $rate1->name;
		$tax1->rate   = 2.11;
		$tax1->amount = 0.12;

		$expected_tax = array(
			'subtotal' => 5.85,
			'total'    => 5.97,
			'tax'      => 0.12,
			'taxes'    => array( $tax1 ),
		);
		$actual_tax = $this->tm->calculate_tax( $tax_class['name'], 6.23, 'CA', '' );
		$this->assertEquals( $expected_tax, $actual_tax );

		// Country + state for which no additional rates exist.
		$actual_tax = $this->tm->calculate_tax( $tax_class['name'], 6.23, 'CA', 'SK' );
		$this->assertEquals( $expected_tax, $actual_tax );

		// Country + state for which rates exist.
		$tax1->amount = 0.15;

		$tax4 = new stdClass();
		$tax4->ID     = $rate4->ID;
		$tax4->name   = $rate4->name;
		$tax4->rate   = 1.56;
		$tax4->amount = 0.11;

		$tax2 = new stdClass();
		$tax2->ID     = $rate2->ID;
		$tax2->name   = $rate2->name;
		$tax2->rate   = 2.89;
		$tax2->amount = 0.2;

		$actual_tax = $this->tm->calculate_tax( $tax_class['name'], 7.34, 'CA', 'AB' );
		$expected_tax = array(
			'subtotal' => 6.89,
			'total'    => 7.35,
			'tax'      => 0.46,
			'taxes'    => array( $tax1, $tax4, $tax2 ),
		);
		$this->assertEquals( $expected_tax, $actual_tax );
	}

	public function test_sanitize_tax_class() {
		$tax_class = array();
		$expected_result = new WP_Error();
		$expected_result->add( 'name_empty', __( 'Name cannot be empty.', 'educator' ) );
		$expected_result->add( 'description_empty', __( 'Description cannot be empty.', 'educator' ) );
		$actual_result = $this->tm->sanitize_tax_class( $tax_class );
		$this->assertEquals( $expected_result, $actual_result );

		$tax_class['name'] = '<script>...</script>name';
		$tax_class['description'] = '<script>...</script>description';
		$expected_result = array(
			'name'        => 'scriptscriptname',
			'description' => 'description',
		);
		$actual_result = $this->tm->sanitize_tax_class( $tax_class );
		$this->assertEquals( $expected_result, $actual_result );
	}

	public function test_sanitize_tax_rate() {
		$rate = new stdClass();
		$rate->ID         = 'x';
		$rate->name       = '<script></script>x';
		$rate->country    = '<script></script>x';
		$rate->state      = '<script></script>x';
		$rate->tax_class  = '<script></script>x';
		$rate->priority   = 'x';
		$rate->rate       = 'x';
		$rate->rate_order = 'x';

		$expected_rate = new stdClass();
		$expected_rate->ID         = 0;
		$expected_rate->name       = 'x';
		$expected_rate->country    = 'x';
		$expected_rate->state      = 'x';
		$expected_rate->tax_class  = 'x';
		$expected_rate->priority   = 0;
		$expected_rate->rate       = 0.0;
		$expected_rate->rate_order = 0;
		$actual_rate = $this->tm->sanitize_tax_rate( $rate );
		$this->assertEquals( $expected_rate, $actual_rate );
	}

	public function test_add_tax_class() {
		$tax_class = array(
			'name'        => 'class_name',
			'description' => 'class_description',
		);
		$this->tm->add_tax_class( $tax_class );
		$expected_tax_classes = array(
			'default'    => 'Default',
			'class_name' => 'class_description',
		);
		$actual_tax_classes = get_option( 'edr_tax_classes' );
		$this->assertEquals( $expected_tax_classes, $actual_tax_classes );
	}

	public function test_delete_tax_class() {
		$tax_class = array(
			'name'        => 'class_name',
			'description' => 'class_description',
		);
		$this->tm->add_tax_class( $tax_class );
		$expected_tax_classes = array(
			'default'    => 'Default',
			'class_name' => 'class_description',
		);
		$actual_tax_classes = get_option( 'edr_tax_classes' );
		$this->assertEquals( $expected_tax_classes, $actual_tax_classes );

		$this->tm->delete_tax_class( 'class_name' );
		$this->tm->delete_tax_class( 'default' );
		$expected_tax_classes = array(
			// Cannot delete default tax class.
			'default' => 'Default',
		);
		$actual_tax_classes = get_option( 'edr_tax_classes' );
		$this->assertEquals( $expected_tax_classes, $actual_tax_classes );
	}

	public function test_get_tax_classes() {
		$expected_tax_classes = array(
			'default' => 'Default',
		);
		$actual_tax_classes = $this->tm->get_tax_classes();
		$this->assertEquals( $expected_tax_classes, $actual_tax_classes );
	}

	public function test_get_tax_class_for() {
		$course_id = $this->factory->post->create( array( 'post_type' => EDR_PT_COURSE ) );
		$expected_result = 'default';
		$actual_result = $this->tm->get_tax_class_for( $course_id );
		$this->assertEquals( $expected_result, $actual_result );

		$expected_result = 'tax_class_1';
		update_post_meta( $course_id, '_edr_tax_class', $expected_result );
		$actual_result = $this->tm->get_tax_class_for( $course_id );
		$this->assertEquals( $expected_result, $actual_result );
	}

	public function test_get_tax_rates() {
		extract( $this->setUpTaxRates() );
		$expected_rates = array( $rate1, $rate2, $rate3, $rate4 );
		$actual_rates = $this->tm->get_tax_rates( $tax_class['name'] );
		$this->assertEquals( $expected_rates, $actual_rates );
	}

	public function test_update_delete_tax_rate() {
		$rate = new stdClass();
		$rate->ID         = null;
		$rate->name       = 'rate1';
		$rate->country    = 'CA';
		$rate->state      = 'AB';
		$rate->tax_class  = 'class1';
		$rate->priority   = 1;
		$rate->rate       = 1.23;
		$rate->rate_order = 2;
		$rate->ID = $this->tm->update_tax_rate( $rate );

		$expected_rates = array( $rate );
		$actual_rates = $this->tm->get_tax_rates( 'class1' );
		$this->assertEquals( $expected_rates, $actual_rates );

		$this->tm->delete_tax_rate( $rate->ID );
		$expected_rates = array();
		$actual_rates = $this->tm->get_tax_rates( 'class1' );
		$this->assertEquals( $expected_rates, $actual_rates );
	}
}
