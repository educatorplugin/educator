<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap">
	<h2><?php _e( 'Educator Settings', 'educator' ); ?></h2>

	<?php
		settings_errors( 'general' );
		self::settings_tabs( 'general' );
		echo '<form action="options.php" method="post">';
		settings_fields( 'edr_general_settings' );
		do_settings_sections( 'edr_general_page' );
		submit_button();
		echo '</form>';
	?>
</div>
