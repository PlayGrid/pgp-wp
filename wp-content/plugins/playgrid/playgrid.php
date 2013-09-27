<?php
/**
 * PlayGrid Wordpress Plugin
 * 
 * @package PlayGrid
 * @subpackage Main
 */

/**
 * Plugin Name: PlayGrid
 * Plugin URI: http://www.playgrid.com/wordpress_plugin
 * Description: Integrate PlayGrid with Wordpress.
 * Version: 0.1
 * Author: PlayGrid
 * Author URI: http://www.playgrid.com
 * License: GPL2
 */

/**  
 * Copyright 2013  PlayGrid  (email : support@playgrid.com)
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

define( 'PG__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );


require_once (PG__PLUGIN_DIR . '/includes/settings.php');
// require_once (PG__PLUGIN_DIR . '/includes/services/extended/playgrid.php');  // FIXME: Not working right now

// Setup actions
add_action('login_form', 'add_playgrid_login');


function add_playgrid_login() {
	include (PG__PLUGIN_DIR . '/templates/login_page.php');
}


class PlayGrid {
	
	const KEYRING_VERSION   = '1.5'; // Minimum version of Keyring required
	
	
	function __construct() {
// 		echo "<H1>PLAYGRID CONSTRUCT</H1>";
		
		// Can't do anything if Keyring is not available.
		// Prompt user to install Keyring (if they can), and bail
		if ( !defined( 'KEYRING__VERSION' ) || version_compare( KEYRING__VERSION, static::KEYRING_VERSION, '<' ) ) {
			if ( current_user_can( 'install_plugins' ) ) {
				add_thickbox();
				wp_enqueue_script( 'plugin-install' );
				add_filter( 'admin_notices', array( $this, 'require_keyring' ) );
			}
			return false;
		}
	
// 		$kr = Keyring::init();
// 		$service = Keyring::get_service_by_name('playgrid');
		
// 		Keyring_Util::debug( '+++++++++++TESTING' );  // FIXME: Debugging
// 		Keyring_Util::debug( $service );  // FIXME: Debugging
// 		Keyring_Util::debug( $service->is_connected() );  // FIXME: Debugging
		
// 		Keyring_Util::connect_to($service->get_name(), 'playgrid_login_form');
		
		// 	Keyring_Util::debug( $keyring_request_token );  // FIXME: Debugging
	
	}
	
}

$pg = new PlayGrid();

