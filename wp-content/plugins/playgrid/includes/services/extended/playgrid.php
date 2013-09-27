<?php


function Keyring_Service_PlayGrid() {

echo '<h1>DEBUGGING: Before Class</h1>';

class Keyring_Service_PlayGrid extends Keyring_Service_HTTP_Basic {
	const NAME  = 'playgrid';
	const LABEL = 'playgrid.com';
	
	function __construct() {
		echo '<h1>DEBUGGING: In Construct</h1>';
	
		parent::__construct();
		$this->set_endpoint('verify', 'https://www.playgrid.com/v1/posts/update', 'GET');
		$this->requires_token(true);
	}

	function _get_credentials() {
		return false;
	}

	function parse_response($data) {
		return simplexml_load_string($data);
	}

	function get_display(Keyring_Access_Token $token) {
		return $token->get_meta('username');
	}
}
echo '<h1>DEBUGGING: After Class</h1>';

add_action('keyring_load_services', array('Keyring_Service_PlayGrid', 'init'));


echo '<pre>';
var_dump(count(Keyring::get_registered_services()));
debug_print_backtrace();
echo '</pre>';

}  // end function

// add_action('init', 'Keyring_Service_PlayGrid');
add_action('init', function() {
	Keyring_Service_PlayGrid();
	add_action('keyring_load_services', array('Keyring_Service_PlayGrid', 'init'));
});
echo '<h1>DEBUGGING: Bottom</h1>';
