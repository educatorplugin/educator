<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap">
	<h2><?php _e( 'Educator Settings', 'educator' ); ?></h2>

	<?php
		settings_errors( 'general' );
		self::settings_tabs( 'email' );
		echo '<form action="options.php" method="post">';
		settings_fields( 'edr_email_settings' );
		do_settings_sections( 'edr_email_page' );
		submit_button();
		echo '</form>';
	?>
</div>
