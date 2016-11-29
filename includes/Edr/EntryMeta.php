<?php

class Edr_EntryMeta {
	protected static $instance = null;

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'register_table' ) );
	}

	public function register_table() {
		global $wpdb;
		$tables = edr_db_tables();
		$wpdb->edr_entrymeta = $tables['entry_meta'];
	}

	public function get_meta( $entry_id, $meta_key = '', $single = false ) {
		return get_metadata( 'edr_entry', $entry_id, $meta_key, $single );
	}

	public function add_meta( $entry_id, $meta_key, $meta_value, $unique = false ) {
		return add_metadata( 'edr_entry', $entry_id, $meta_key, $meta_value, $unique );
	}

	public function update_meta( $entry_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_metadata( 'edr_entry', $entry_id, $meta_key, $meta_value, $prev_value );
	}

	public function delete_meta( $entry_id, $meta_key, $meta_value = null ) {
		return delete_metadata( 'edr_entry', $entry_id, $meta_key, $meta_value );
	}
}
