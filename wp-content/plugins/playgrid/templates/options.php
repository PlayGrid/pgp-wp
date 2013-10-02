<?php

/**
 * PlayGrid Options Template
 * 
 * @package PlayGrid
 * @subpackage Options
 */

?>

<div class="wrap">
	<?php screen_icon(); ?>
	<h2>PlayGrid Settings</h2>

	<form method="post" action="options.php">
	
	<?php 
	settings_fields( 'playgrid_options' );
	do_settings_sections( 'playgrid_options' );
	submit_button(); 
	?>
	
	</form>
</div>