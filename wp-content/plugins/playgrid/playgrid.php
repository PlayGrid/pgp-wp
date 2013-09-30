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


add_action( 'plugins_loaded', array( PlayGrid::get_instance(), 'plugin_setup' ) );


class PlayGrid {
	
	/**
	 * Keyring dependency version
	 *  
	 * @type string
	 */
	const KEYRING_VERSION   = '1.5'; // Minimum version of Keyring required
	
	/**
	 * Plugin instance
	 * @see get_instance()
	 * @type object
	 */
	protected static $instance = NULL;
	
	/**
	 * Path to this plugin's directory
	 *
	 * @type string
	 */
	public $plugin_path = '';
	
	/**
	 * Access this plugin's working instance
	 *
	 * @wp-hook plugins_loaded
	 * @return  object of this class
	 */
	public static function get_instance() {
		NULL === self::$instance and self::$instance = new self;
	
		return self::$instance;
	}
	
	/**
	 * Used for regular plugin work.
	 *
	 * @wp-hook plugins_loaded
	 * @return  void
	 */
	public function plugin_setup() {
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
		
		$this->plugin_url    = plugins_url( '/', __FILE__ );
		$this->plugin_path   = plugin_dir_path( __FILE__ );
		
		// // require_once ( $this->plugin_path . '/includes/services/extended/playgrid.php' );  // FIXME: Not working right now

		// Register request handler
		add_action( 'init', array( $this, 'request_handler'), 100);
		
		// Register actions and filters
		add_action( 'login_form', array( $this, 'add_playgrid_login' ) );
		add_action( 'admin_menu', array( $this, 'settings_menu' ) );
		
	}
	
	/**
	 * Constructor. Intentionally left empty and public.
	 *
	 * @see plugin_setup()
	 */
	public function __construct() {}


	/**
	 * Add PlayGrid Login
	 */
	function add_playgrid_login() {
		include ( $this->plugin_path . '/templates/login_page.php' );
	}
	
	/**
	 * Settings Menu
	 */
	function settings_menu() {
		add_options_page('PlayGrid Settings', 'PlayGrid', 'manage_options', 'playgrid_settings', array( $this, 'playgrid_options' ) );
	}
	
	/**
	 * Options
	 */
	function playgrid_options() {
		if (!current_user_can('manage_options')) {
			wp_die( __('You do not have sufficient permissions to access this page.'));
		}
		include ( $this->plugin_path . '/templates/settings.php');
	}
	
	/**
	 * Request Handler
	 */
	function request_handler() {

		if (
			!empty( $_REQUEST['page'] ) && $_REQUEST['page']
			&&
			in_array( $_REQUEST['page'], 'playgrid' )                           // intentionally hardcoded
			&&
			!empty( $_REQUEST['service'] )
			&&
			in_array( $_REQUEST['service'], array_keys( Keyring::get_registered_services() ) )
			&&			
			!empty( $_REQUEST['action'] )
			&&
			in_array( $_REQUEST['action'], apply_filters( 'keyring_core_actions', array( 'request', 'verify' ) ) )
			) {
			
			// We have an action here to allow us to do things pre-authorization, just in case
			do_action( "pre_keyring_{$_REQUEST['service']}_{$_REQUEST['action']}", $_REQUEST );
				
			Keyring_Util::debug( "keyring_{$_REQUEST['service']}_{$_REQUEST['action']}" );
			Keyring_Util::debug( $_GET );
			do_action( "keyring_{$_REQUEST['service']}_{$_REQUEST['action']}", $_REQUEST );
		}
		
	}
	
}

