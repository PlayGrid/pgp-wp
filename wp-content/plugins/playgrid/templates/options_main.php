<?php

/**
 * PlayGrid Options Main Section Template
 *
 * @package PlayGrid
 * @subpackage Options
 */

$options = get_option('playgrid_options');

?>
		<input id='playgrid_app_id' name='playgrid_options[app_id]' size='40' type='text' value='<?= $options['app_id']?>' />
	</td>
</tr>
<tr valign="top">
	<th scope="row">
		<label for="app_secret">Site Secret</label>
	</th>
	<td>
		<textarea id='playgrid_app_secret' name='playgrid_options[app_secret]' rows='5' cols='41' type='textarea' ><?= $options['app_secret'] ?></textarea>
	</td>
</tr>
<tr valign="top">
	<th scope="row">
		<label for="oauth_url">PlayGrid URL</label>
	</th>
	<td>
		<input id='oauth_url' name='playgrid_options[oauth_url]' size='40' type='text' value='<?= $options['oauth_url'] ?>' />
