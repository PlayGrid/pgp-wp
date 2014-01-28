<?php

/**
 * PlayGrid Options Main Section Template
 *
 * @package PlayGrid
 * @subpackage Options
 */

$url = PlayGrid::callback_url( 'playgrid', array( 'action' => 'verify' ) );

?>

<p>
	Enter your <em>ID</em> and <em>Secret</em> below along with your <em>PlayGrid URL</em>. 
	<ul style="margin-left: 15px;">
		<li>If you have a site on PlayGrid, then you already know your <em>PlayGrid URL</em>. Your URL should look something like - http://mysite.playgrid.com/</li>
		<li>If you do not have a site, you can create one by registering at <a href="http://www.playgrid.com/" target="_blank">http://www.playgrid.com/</a></li>
	</ul>
</p>

<p>
	Once you have a PlayGrid site, register your Wordpress site to generate your <em>ID</em> and <em>Secret</em>. 
	<ul style="margin-left: 15px;">
		<li>Navigate to your <em>Admin</em> -> <em>Plugins</em> -> <em>Wordpress</em> to register your Wordpress site.</li>
		<li>Enter the following URL into the <em>Redirect URIs</em> field. <br><div style="background-color: lightgray; margin-top: 5px; margin-left: 20px; padding: 5px; display: inline-block;"><?= $url ?></div></li>
	</ul>
	Now that you have registered your Wordpress site with PlayGrid, you can complete form below with your <em>ID</em>, <em>Secret</em> and <em>PlayGrid URL</em>. 
</p>
<hr>