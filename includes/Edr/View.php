<?php

class Edr_View {
	/**
	 * Locate template file path.
	 *
	 * @param array $template_names
	 * @return string|boolean Template path if found, otherwise false.
	 */
	public static function locate_template( $template_names ) {
		$templates_dir = 'educator';
		$located_path = false;

		foreach ( $template_names as $name ) {
			$file_path = trailingslashit( get_stylesheet_directory() ) . $templates_dir . '/' . $name;

			if ( file_exists( $file_path ) ) {
				$located_path = $file_path;
				break;
			}

			$file_path = trailingslashit( get_template_directory() ) . $templates_dir . '/' . $name;

			if ( file_exists( $file_path ) ) {
				$located_path = $file_path;
				break;
			}

			$file_path = trailingslashit( EDR_PLUGIN_DIR . 'templates' ) . $name;

			if ( file_exists( $file_path ) ) {
				$located_path = $file_path;
				break;
			}
		}

		return $located_path;
	}

	/**
	 * Output template part.
	 *
	 * @param string $template_name
	 * @param string $suffix
	 */
	public static function template_part( $template_name, $suffix = '' ) {
		$template_names = array();

		if ( $suffix ) {
			$template_names[] = $template_name . '-' . $suffix . '.php';
		}

		$template_names[] = $template_name . '.php';

		$template_path = self::locate_template( $template_names );

		if ( $template_path ) {
			load_template( $template_path, false );
		}
	}

	/**
	 * Output template.
	 *
	 * @param string $template_name
	 * @param array|null $vars
	 */
	public static function the_template( $template_name, $vars = null ) {
		$template_path = self::locate_template( array(
			$template_name . '.php'
		) );

		if ( $template_path ) {
			if ( is_array( $vars ) ) {
				extract( $vars );
			}

			include $template_path;
		}
	}
}
