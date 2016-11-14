<?php

class TestEdr_Upload extends Edr_Upload {
	protected function is_uploaded_file( $file_path ) {
		return true;
	}

	protected function move_uploaded_file( $from_path, $to_path ) {
		return rename( $from_path, $to_path );
	}
}

class UploadClassTest extends WP_UnitTestCase {
	public function test_get_file_path() {
		$wp_upload_dir = wp_upload_dir();
		$tmp_file_path = $wp_upload_dir['basedir'] . '/testfile.txt';

		file_put_contents( $tmp_file_path, 'abc' );

		$upload = new Edr_Upload();

		$get_file_path = new ReflectionMethod( 'Edr_Upload', 'get_file_path' );
		$get_file_path->setAccessible(true);
		$new_file_path = $get_file_path->invokeArgs( $upload, array( $tmp_file_path ) );
		$expected_file_path = array(
			'dir'  => 'a9/99',
			'name' => 'a9993e364706816aba3e25717850c26c9cd0d89d',
		);

		$this->assertSame( $expected_file_path, $new_file_path );
	}

	public function test_get_error_message() {
		$upload = new Edr_Upload();

		$this->assertSame( '', $upload->get_error_message( UPLOAD_ERR_OK ) );
		$this->assertSame( __( 'No file sent.', 'edr' ), $upload->get_error_message( UPLOAD_ERR_NO_FILE ) );
		$this->assertSame( __( 'Exceeded file size limit.', 'edr' ), $upload->get_error_message( UPLOAD_ERR_INI_SIZE ) );
		$this->assertSame( __( 'Exceeded file size limit.', 'edr' ), $upload->get_error_message( UPLOAD_ERR_FORM_SIZE ) );
		$this->assertSame( __( 'Unknown upload error.', 'edr' ), $upload->get_error_message( -1 ) );
	}

	public function test_get_allowed_mime_types() {
		$upload = new Edr_Upload();

		$get_allowed_mime_types = new ReflectionMethod( 'Edr_Upload', 'get_allowed_mime_types' );
		$get_allowed_mime_types->setAccessible(true);
		$allowed_mime_types = $get_allowed_mime_types->invokeArgs( $upload, array() );

		$this->assertSame( 'image/png', $allowed_mime_types['png'] );
		$this->assertSame( 'application/pdf', $allowed_mime_types['pdf'] );
		$this->assertSame( 'image/jpeg', $allowed_mime_types['jpg|jpeg'] );
	}

	public function test_check_mime_type() {
		$upload = new Edr_Upload();
		$wp_upload_dir = wp_upload_dir();
		$file_path = $wp_upload_dir['basedir'] . '/testfile.txt';

		file_put_contents( $file_path, 'abc' );

		$check_mime_type = new ReflectionMethod( 'Edr_Upload', 'check_mime_type' );
		$check_mime_type->setAccessible(true);
		$actual_mime_type = $check_mime_type->invokeArgs( $upload, array( $file_path ) );
		$expected_mime_type = array(
			'type'       => 'text/plain',
			'ext_regexp' => 'txt',
		);

		$this->assertSame( $expected_mime_type, $actual_mime_type );
	}

	public function test_upload_file() {
		$wp_upload_dir = wp_upload_dir();
		$file = array(
			'name'        => 'testfile.txt',
			'tmp_name'    => $wp_upload_dir['basedir'] . '/testfile.txt',
			'context_dir' => 'test-context-dir',
		);

		$expected_file_path = edr_get_private_uploads_dir();
		$expected_file_path .= '/test-context-dir/a9/99/a9993e364706816aba3e25717850c26c9cd0d89d.txt';

		if ( file_exists( $expected_file_path ) ) {
			unlink( $expected_file_path );
		}

		$upload = new TestEdr_Upload();
		$actual_uploaded_file = $upload->upload_file( $file );
		$expected_uploaded_file = array(
			'name'          => 'a9993e364706816aba3e25717850c26c9cd0d89d.txt',
			'dir'           => 'a9/99',
			'original_name' => 'testfile.txt',
		);

		$this->assertSame( $expected_uploaded_file, $actual_uploaded_file );
	}

	public function test_generate_protect_htaccess() {
		$expected_htaccess_content = "Options -Indexes\n";
		$expected_htaccess_content .= "deny from all\n";
		$upload = new Edr_Upload();
		$actual_htaccess_content = $upload->generate_protect_htaccess();

		$this->assertSame( $expected_htaccess_content, $actual_htaccess_content );
	}

	public function test_create_protect_files() {
		$expected_htaccess_content = "Options -Indexes\n";
		$expected_htaccess_content .= "deny from all\n";
		$htaccess_file_path = edr_get_private_uploads_dir() . '/.htaccess';

		if ( file_exists( $htaccess_file_path ) ) {
			unlink( $htaccess_file_path );
			$this->assertFalse( file_exists( $htaccess_file_path ) );
		}

		$upload = new Edr_Upload();
		$upload->create_protect_files();

		$actual_htaccess_content = file_get_contents( $htaccess_file_path );

		$this->assertSame( $expected_htaccess_content, $actual_htaccess_content );
	}
}
