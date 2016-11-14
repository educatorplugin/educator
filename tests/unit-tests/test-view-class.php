<?php

class ViewClassTest extends WP_UnitTestCase {
	public function create_child_theme() {
		$parent_theme_name = 'twentysixteen';
		$child_theme_name = 'test-child-theme';
		$theme_root = get_theme_root();
		$child_theme_dir = $theme_root . '/' . $child_theme_name;

		if ( file_exists( $child_theme_dir ) ) {
			return;
		}

		mkdir( $child_theme_dir );
		file_put_contents( $child_theme_dir . '/functions.php', "<?php\n" );
		$theme_info = <<<TEXT
/*
 Theme Name: $child_theme_name
 Template:   $parent_theme_name
 Version:    1.0.0
*/
TEXT;
		file_put_contents( $child_theme_dir . '/style.css', $theme_info );
	}

	public function create_test_templates() {
		$template_dirs = array(
			'parent' => get_theme_root() . '/twentysixteen/edr',
			'child'  => get_theme_root() . '/test-child-theme/edr',
		);

		foreach ( $template_dirs as $template => $path ) {
			if ( ! file_exists( $template_dirs[ $template ] ) ) {
				mkdir( $template_dirs[ $template ] );
			}

			file_put_contents( $path . '/sometemplate.php', $template . '-sometemplate' );
			file_put_contents( $path . '/sometemplate-suffix.php', $template . '-sometemplate-suffix' );
			file_put_contents( $path . '/templatewithvars.php', '<?php if ( "test" == $some_var ) echo "test succeeded"; ?>' );

			if ( 'parent' == $template ) {
				file_put_contents( $path . '/sometemplate-parent-only.php', $template . '-sometemplate-parent-only' );
			}
		}
	}

	public function locate_templates() {
		$template_default_content = '';
		$template_default = Edr_View::locate_template( array(
			'sometemplate.php'
		) );

		$template_suffix_content = '';
		$template_suffix = Edr_View::locate_template( array(
			'sometemplate-suffix.php'
		) );

		$template_parent_only_content = '';
		$template_parent_only = Edr_View::locate_template( array(
			'sometemplate-parent-only.php'
		) );

		if ( $template_default ) {
			$template_default_content = file_get_contents( $template_default );
		}

		if ( $template_suffix ) {
			$template_suffix_content = file_get_contents( $template_suffix );
		}

		if ( $template_parent_only ) {
			$template_parent_only_content = file_get_contents( $template_parent_only );
		}

		return array(
			'default'     => $template_default_content,
			'suffix'      => $template_suffix_content,
			'parent_only' => $template_parent_only_content,
		);
	}

	public function test_locate_template() {
		$this->create_child_theme();
		$this->create_test_templates();

		// Search templates in a child theme.
		switch_theme( 'test-child-theme' );

		$template_contents = $this->locate_templates();

		$this->assertSame( 'child-sometemplate', $template_contents['default'] );
		$this->assertSame( 'child-sometemplate-suffix', $template_contents['suffix'] );
		$this->assertSame( 'parent-sometemplate-parent-only', $template_contents['parent_only'] );

		// Search templates in a parent theme.
		switch_theme( 'twentysixteen' );

		$template_contents = $this->locate_templates();

		$this->assertSame( 'parent-sometemplate', $template_contents['default'] );
		$this->assertSame( 'parent-sometemplate-suffix', $template_contents['suffix'] );
		$this->assertSame( 'parent-sometemplate-parent-only', $template_contents['parent_only'] );

		// Search templates in the plugin's templates directory.
		$template_path = Edr_View::locate_template( array( 'comments-no-access.php' ) );
		$this->assertSame( "<?php\n", file_get_contents( $template_path ) );

		// Search for a missing template.
		$this->assertSame( false, Edr_View::locate_template( array( 'missingtemplate.php' ) ) );
	}

	public function test_template_part() {
		$this->create_child_theme();
		$this->create_test_templates();

		switch_theme( 'test-child-theme' );

		// Get template part with a suffix.
		ob_start();
		Edr_View::template_part( 'sometemplate', 'suffix' );
		$actual_content = ob_get_clean();
		$this->assertSame( 'child-sometemplate-suffix', $actual_content );

		// Get template part when the template with the specified suffix is missing.
		// Returns the default template if it exists.
		ob_start();
		Edr_View::template_part( 'sometemplate', 'suffix-missing' );
		$actual_content = ob_get_clean();
		$this->assertSame( 'child-sometemplate', $actual_content );

		// Cannot find a template.
		ob_start();
		Edr_View::template_part( 'missingtemplate' );
		$actual_content = ob_get_clean();
		$this->assertSame( '', $actual_content );
	}

	public function test_the_content() {
		$this->create_child_theme();
		$this->create_test_templates();

		switch_theme( 'twentysixteen' );

		ob_start();
		Edr_View::the_template( 'templatewithvars', array( 'some_var' => 'test' ) );
		$actual_content = ob_get_clean();
		$this->assertSame( 'test succeeded', $actual_content );

		ob_start();
		Edr_View::the_template( 'missingtemplate' );
		$actual_content = ob_get_clean();
		$this->assertSame( '', $actual_content );
	}
}
