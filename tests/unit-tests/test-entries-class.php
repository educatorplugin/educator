<?php

class EntriesClassTest extends WP_UnitTestCase {
	protected $edr_entries;

	public function setUp() {
		parent::setUp();

		$this->edr_entries = Edr_Entries::get_instance();
	}

	public function test_get_entry() {
		$entry_factory = new Edr_Entry_Factory();
		$entry1_id = $entry_factory->create( array(
			'payment_id'   => 123,
			'entry_status' => 'pending',
		) );
		$entry2_id = $entry_factory->create( array(
			'user_id'      => 789,
			'course_id'    => 456,
			'entry_status' => 'pending',
		) );
		$entry3_id = $entry_factory->create( array(
			'user_id'      => 789,
			'entry_status' => 'inprogress',
		) );

		$entry = $this->edr_entries->get_entry( array( 'payment_id' => 123 ) );
		$this->assertEquals( $entry1_id, $entry->ID );

		$entry = $this->edr_entries->get_entry( array( 'course_id' => 456 ) );
		$this->assertEquals( $entry2_id, $entry->ID );

		$entry = $this->edr_entries->get_entry( array(
			'user_id'      => 789,
			'entry_status' => 'inprogress',
		) );
		$this->assertEquals( $entry3_id, $entry->ID );
	}

	public function test_get_entries() {
		$entry_factory = new Edr_Entry_Factory();
		$entry1_id = $entry_factory->create( array(
			'user_id'      => 1,
			'course_id'    => 2,
			'entry_origin' => 'payment',
			'entry_status' => 'pending',
			'payment_id'   => 1,
		) );
		$entry2_id = $entry_factory->create( array(
			'user_id'      => 2,
			'course_id'    => 2,
			'entry_origin' => 'payment',
			'entry_status' => 'pending',
		) );
		$entry3_id = $entry_factory->create( array(
			'user_id'      => 2,
			'course_id'    => 3,
			'entry_origin' => 'membership',
			'entry_status' => 'inprogress',
		) );

		$entries = $this->edr_entries->get_entries( array( 'entry_status' => 'pending' ) );
		$this->assertSame(
			array( $entry1_id, $entry2_id ),
			array( (int) $entries[0]->ID, (int) $entries[1]->ID )
		);

		$entries = $this->edr_entries->get_entries( array( 'course_id' => 2 ) );
		$this->assertSame(
			array( $entry1_id, $entry2_id ),
			array( (int) $entries[0]->ID, (int) $entries[1]->ID )
		);

		$entries = $this->edr_entries->get_entries( array(
			'user_id'  => 2,
			'page'     => 2,
			'per_page' => 1,
		) );
		$expected = array(
			'num_pages' => 2,
			'num_items' => 2,
			'rows'      => array( edr_get_entry( $entry3_id ) )
		);
		$this->assertEquals( $expected, $entries );

		$entries = $this->edr_entries->get_entries( array( 'payment_id' => 1 ) );
		$this->assertSame(
			array( $entry1_id ),
			array( (int) $entries[0]->ID
		) );

		$entries = $this->edr_entries->get_entries( array( 'entry_origin' => 'membership' ) );
		$this->assertSame(
			array( $entry3_id ),
			array( (int) $entries[0]->ID
		) );

		$entries = $this->edr_entries->get_entries( array( 'entry_id' => $entry2_id ), ARRAY_A );
		$this->assertEquals( 1, count( $entries ) );
		$this->assertEquals( $entry2_id, $entries[0]['ID'] );
	}
}
