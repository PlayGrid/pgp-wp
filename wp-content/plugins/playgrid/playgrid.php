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
 * Description: Integrate Wordpress and PlayGrid
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

/**
 * The PlayGrid plugin allows you to integrate your Wordpress site with PlayGrid
 * information and functionality. Features:
 * 
 * - User sign-up / authentication using PlayGrid OAuth2 implementation
 * - Server Status widget 
 *
 *
 * Template Tags
 * *************  
 * 
 * get_playgrid_button()
 * 
 * Returns the rendering for the 'Login with PlayGrid' button. 
 * 
 * To use as a template tag:
 * <?php get_playgrid_button(); ?>
 * 
 * @see includes/functions.php 
 * 
 *
 * Configuration Constants 
 * ***********************
 * Helpful configuration constants to define in you wp-config.php
 * 
 * PLAYGRID__APP_ID
 * - set to PlayGrid applicatin ID 
 *  
 * PLAYGRID__APP_SECRET
 * - set to PlayGrid applicatin secret

 * PLAYGRID__OAUTH_URL
 * - set to the base url of your PlayGrid site, i.e. http://mygame.playgrid.com/
 * 
 */

add_action( 'plugins_loaded', array( PlayGrid::get_instance(), 'plugin_setup' ) );

DEFINE("PLAYGRID_PLUGIN_URL",dirname(__FILE__));

class PlayGrid {
  
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

    $this->plugin_url    = plugins_url( '/', __FILE__ );
    $this->plugin_path   = plugin_dir_path( __FILE__ );

    // includes
    require_once ( $this->plugin_path . '/includes/functions.php' );
    require_once ( $this->plugin_path . '/includes/services/extended/playgrid.php' );

    // Register request handler
    add_action( 'init', array( $this, 'request_handler'), 100);

    // Register actions and filters
    add_action( 'login_form', array( $this, 'add_playgrid_login' ) );

    if ( is_admin() ) {
      add_action( 'admin_menu', array( $this, 'settings_menu' ) );
      add_action( 'admin_init', array( $this, 'register_settings' ) );
    }
  }

  /**
   * Constructor. Intentionally left empty and public.
   *
   * @see plugin_setup()
   */
  public function __construct() {}

  /**
   * Configure
   */
  function get_config() {

    $config = get_option( 'playgrid_options' );

    if( !is_array( $config ) ) $config = array(
      "api_url" => "",
      "app_id" => "",
      "app_secret" => "",
      "oauth_url" => ""
    );

    $config["api_url"] =  defined( 'PLAYGRID__API_URL' ) ? constant('PLAYGRID__API_URL' ) : "http://api.playgrid.com/";

    defined( 'PLAYGRID__APP_ID' ) && $config['app_id'] = constant("PLAYGRID__APP_ID");
    defined( 'PLAYGRID__APP_SECRET' ) && $config['app_secret'] = constant("PLAYGRID__APP_SECRET");
    defined( 'PLAYGRID__OAUTH_URL' )  && $config['oauth_url'] = constant("PLAYGRID__OAUTH_URL");

    return $config;

  }

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
    add_options_page('PlayGrid Settings', 'PlayGrid', 'manage_options', 'playgrid_options', array( $this, 'playgrid_options' ) );
  }
  
  /**
   * Options
   */
  function playgrid_options() {
    if (!current_user_can('manage_options')) {
      wp_die( __('You do not have sufficient permissions to access this page.'));
    }
    include ( $this->plugin_path . '/templates/options.php');
  }
  
  /**
   * Register Settings
   */
  function register_settings() {
    register_setting( 'playgrid_options', 'playgrid_options', array( $this, 'playgrid_options_validate' ) );
    add_settings_section( 'playgrid_main_options', 'Main Settings', array( $this, 'playgrid_options_main_description'), 'playgrid_options' );
    add_settings_field('playgrid_options_id', 'Site ID', array( $this, 'playgrid_options_main'), 'playgrid_options', 'playgrid_main_options', array( 'label_for' => 'app_id' ) );
  }
  
  /**
   * Options Main Description
   */
  function playgrid_options_main_description() {
  	include ( $this->plugin_path . '/templates/options_main_description.php' );
  }
  
  /**
   * Options Main 	
   */
  function playgrid_options_main() {
  	include ( $this->plugin_path . '/templates/options_main.php');
  }
  
  /**
   * Options Validate
   */
  function playgrid_options_validate( $input ) {
    $options = get_option('playgrid_options');

    $options['app_id']     = trim($input['app_id']);
    $options['app_secret'] = trim($input['app_secret']);
    $options['oauth_url']  = trim($input['oauth_url']);

    return $options;
  }

  /**
   * Request Handler
   */
  function request_handler() {

    if (
      !empty( $_REQUEST['page'] ) && $_REQUEST['page']
      &&
      in_array( $_REQUEST['page'], array( 'playgrid' ) )                        // intentionally hardcoded
      &&
      !empty( $_REQUEST['service'] )
      &&
      in_array( $_REQUEST['service'], array( "playgrid" ) )
      &&			
      !empty( $_REQUEST['action'] )
      &&
      in_array( $_REQUEST['action'], array( 'request', 'verify' ) )
      ) {

        $config = $this->get_config();

        switch( $_REQUEST["action"] ):

          case "request" : 

            $service = new Service_PlayGrid( $config );

            header("Location: ".$service->getLoginUrl());
            exit;

          break;

          case "verify" :

            $service = new Service_PlayGrid( $config );

            if( isset($_REQUEST["code"] ) ) :

              $token = $service->getOAuthToken( $_REQUEST["code"] , true );

              $service->setAccessToken( $token );

              $service->loginUser();

            else :
              // "code" wasn't returned in redirect URL from PlayGrid.com
              wp_die(
                "Unable to verify your request at this time. Please try again. <br/><br/><a href='".wp_login_url()."'>&laquo; Back</a>",
                "Verification Error"
              );

              exit;

            endif;

          break;

        endswitch;

    }

  }

  static function callback_url( $service = false, $params = array() ) {

    $url = home_url();

    if ( $service )
      $url = add_query_arg( array( 'page' =>  $service, 'service' => $service ), $url );

    if ( count( $params ) )
      $url = add_query_arg( $params, $url );

    return $url;
  }

}

