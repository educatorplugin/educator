<?php

class Edr_Entry_Factory extends WP_UnitTest_Factory_For_Thing {
	public function __construct( $factory = null ) {
		parent::__construct( $factory );
		$this->default_generation_definitions = array(
		);
	}

	public function create_object( $args ) {
		$entry = new Edr_Entry( (object) $args );
		$saved = $entry->save();

		return ( $saved ) ? $entry->ID : false;
	}

	public function update_object( $id, $fields ) {
		$entry = new Edr_Entry( $id );
		$entry->set_data( (object) $fields );
		$saved = $entry->save();

		return $saved;
	}

	public function get_object_by_id( $id ) {
		$entry = new Edr_Entry( $id );

		return $entry;
	}
}
