<?php

/**
 * PlayGrid Settings
 * 
 * @package PlayGrid
 * @subpackage Settings
 */

add_action('admin_menu', 'playgrid_settings_menu');


function playgrid_settings_menu() {
	add_options_page('PlayGrid Settings', 'PlayGrid', 'manage_options', 'playgrid_settings', 'playgrid_options');
}


function playgrid_options() {
	if (!current_user_can('manage_options')) {
		wp_die( __('You do not have sufficient permissions to access this page.'));
	}
	include (PG__PLUGIN_DIR . '/templates/settings.php');
}

