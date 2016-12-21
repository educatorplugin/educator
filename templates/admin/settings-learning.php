<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap">
	<h2><?php _e( 'Educator Settings', 'educator' ); ?></h2>

	<?php
		settings_errors( 'general' );
		self::settings_tabs( 'learning' );
		echo '<form action="options.php" method="post">';
		settings_fields( 'edr_learning_settings' );
		do_settings_sections( 'edr_learning_page' );
		submit_button();
		echo '</form>';
	?>
</div>
