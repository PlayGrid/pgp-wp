<?php

/**
 * PlayGrid Wordpress Plugin
 *
 * @package PlayGrid
 * @subpackage Functions
 */


/**
 * Display PlayGrid Button
 * 
 * Returns the rendering for the 'Login with PlayGrid' button. 
 * 
 * To use as a template tag:
 * <?php get_playgrid_button(); ?>
 * 
 * @param boolean $echo - defaults to echo and not returning the button url
 * @return string - 'Login with PlayGrid' link/button
 */
function get_playgrid_button( $echo=true ) {

	$url = Keyring_Service_PlayGrid::callback_url( 'playgrid', array( 'action' => 'request' ) );
	$button = '<a href="' . $url . '" class="button button-primary button-large">Login with PlayGrid</a>';
	
	if ( $echo )
		echo $button;
	else
		return $button;

	
}