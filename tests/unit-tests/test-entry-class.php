<?php

class EntryClassTest extends WP_UnitTestCase {
	public function get_sample_entry_data() {
		$entry_data = new stdClass();
		$entry_data->ID = 12;
		$entry_data->course_id = 11;
		$entry_data->object_id = 10;
		$entry_data->user_id = 9;
		$entry_data->payment_id = 8;
		$entry_data->grade = 91.23;
		$entry_data->entry_origin = 'payment';
		$entry_data->entry_status = 'inprogress';
		$entry_data->entry_date = '2016-07-01 07:00:00';
		$entry_data->complete_date = '2016-09-30 19:00:00';

		return $entry_data;
	}

	public function entry_exists( $entry_id ) {
		global $wpdb;
		$tables = edr_db_tables();
		$entry_exists = $wpdb->get_var( $wpdb->prepare(
			"
			SELECT 'yes'
			FROM   {$tables['entries']}
			WHERE  ID = %d
			",
			$entry_id
		) );

		return ( 'yes' == $entry_exists );
	}

	public function test_construct() {
		$entry_factory = new Edr_Entry_Factory();
		$entry_id = $entry_factory->create();

		$entry = new Edr_Entry( $entry_id );
		$this->assertSame( $entry_id, $entry->ID );
	}

	public function test_set_data() {
		$entry_data = $this->get_sample_entry_data();
		$entry = new Edr_Entry();
		$entry->set_data( $entry_data );
		$expected = (array) $entry_data;
		$actual = get_object_vars( $entry );

		$this->assertSame( $expected, $actual );
	}

	public function test_save() {
		$entry_data = $this->get_sample_entry_data();
		$entry_data->ID = null;
		$entry = new Edr_Entry( $entry_data );
		$entry->save();

		$saved_entry = edr_get_entry( $entry->ID );
		$entry_data->ID = $entry->ID;
		$expected = (array) $entry_data;
		$actual = get_object_vars( $saved_entry );

		$this->assertSame( $expected, $actual );
	}

	public function test_delete() {
		$entry_data = $this->get_sample_entry_data();
		$entry_data->ID = null;
		$entry = new Edr_Entry( $entry_data );
		$entry->save();

		$entry_exists = $this->entry_exists( $entry->ID );
		$this->assertTrue( $entry_exists );

		$entry->delete();

		$entry_exists = $this->entry_exists( $entry->ID );
		$this->assertFalse( $entry_exists );
	}
}
