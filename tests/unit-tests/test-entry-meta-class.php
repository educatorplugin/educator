<?php

class EntryMetaClassTest extends WP_UnitTestCase {
	/**
	 * Tests:
	 * - add_meta
	 * - get_meta
	 * - update_meta
	 * - delete_meta
	 */
	public function test_CRUD() {
		$entry_factory = new Edr_Entry_Factory();
		$entry_id = $entry_factory->create();

		// add_meta and get_meta
		$obj_entry_meta = Edr_EntryMeta::get_instance();
		$expected_meta = 'abc';
		$obj_entry_meta->add_meta( $entry_id, 'test_meta', $expected_meta );
		$actual_meta = $obj_entry_meta->get_meta( $entry_id, 'test_meta', true );
		$this->assertSame( $expected_meta, $actual_meta );

		// update_meta
		$expected_meta = 'abc updated';
		$obj_entry_meta->update_meta( $entry_id, 'test_meta', $expected_meta );
		$actual_meta = $obj_entry_meta->get_meta( $entry_id, 'test_meta', true );
		$this->assertSame( $expected_meta, $actual_meta );

		// delete_meta
		$obj_entry_meta->delete_meta( $entry_id, 'test_meta' );
		$actual_meta = $obj_entry_meta->get_meta( $entry_id, 'test_meta', true );
		$this->assertSame( '', $actual_meta );
	}
}
