<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="wrap">
	<h2><?php _e( 'Educator Settings', 'edr' ); ?></h2>

	<?php
		settings_errors( 'general' );
		self::settings_tabs( 'taxes' );
		echo '<form action="options.php" method="post">';
		settings_fields( 'edr_taxes_settings' );
		do_settings_sections( 'edr_taxes_page' );
		submit_button();
		echo '</form>';
	?>
</div>
