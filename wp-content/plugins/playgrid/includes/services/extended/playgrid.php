<?php

/**
 * PlayGrid Wordpress Plugin
 * 
 * @package PlayGrid
 */

class Service_PlayGrid {

  private $_authorize_url = '';
  private $_access_token_url = '';

  private $api_url;
  private $_apikey;
  private $_apisecret;
  private $_callbackurl;
  private $_accesstoken;


  public function __construct($config) {

    if (true === is_array($config) && $config["oauth_url"]!=="" && $config["app_id"]!=="" && $config["app_secret"]!==""  ) {
	      // if you want to access user data
    		$this->api_url = $config['api_url'];
			$this->_authorize_url = $config['oauth_url']. 'o/authorize/';
			$this->_access_token_url = $config['oauth_url']. 'o/token/';
			$this->_callbackurl = PlayGrid::callback_url( "playgrid", array( 'action' => 'verify' ) );
		    $this->setApiKey($config['app_id']);
		    $this->setApiSecret($config['app_secret']);
    } else {
      	// class was initialized without config options
		wp_die(
			"Login over Playgrid is not configured yet.<br/><br/><a href='".wp_login_url()."'>« Back</a>",
			"Login Error"
		);
    }
  }

  public function getLoginUrl($scope = array('basic')) {
   
     return $this->_authorize_url . '?client_id=' . urlencode($this->getApiKey()) . '&redirect_uri=' . urlencode($this->getApiCallback()) . '&response_type=code';

  }

  public function getOAuthToken($code, $token = false) {
    $apiData = array(
      'grant_type'      => 'authorization_code',
      'client_id'       => $this->getApiKey(),
      'client_secret'   => $this->getApiSecret(),
      'redirect_uri'    => $this->getApiCallback(),
      'code'            => $code
    );
    
    $result = $this->_makeOAuthCall($apiData);
    return (false === $token) ? $result : $result["access_token"];
  }

  private function _makeCall($function, $auth = false, $params = null, $method = 'GET') {
    if (false === $auth) {
      // if the call doesn't requires authentication
      $authMethod = '?client_id=' . $this->getApiKey();
    } else {
      // if the call needs an authenticated user
      if (true === isset($this->_accesstoken)) {
        $authMethod = '?access_token=' . $this->getAccessToken();
      } else {
      	// access token is missing
		wp_die(
			"User Info Request Error : " . $jsonData,
			"Login Error"
		);
      }
    }
    
    if (isset($params) && is_array($params)) {
      $paramString = '&' . http_build_query($params);
    } else {
      $paramString = null;
    }
    
    $apiCall = $this->api_url . $function . $authMethod . (('GET' === $method) ? $paramString : null);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiCall);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if ('POST' === $method) {
      curl_setopt($ch, CURLOPT_POST, count($params));
      curl_setopt($ch, CURLOPT_POSTFIELDS, ltrim($paramString, '&'));
    } else if ('DELETE' === $method) {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    $jsonData = curl_exec($ch);
    if (false === $jsonData) {
    	// response returned wasn't JSON formatted.
      throw new Exception("Error: _makeCall() - cURL error: " . curl_error($ch));
    }
    curl_close($ch);
    
    return json_decode($jsonData,true);
  }

  private function _makeOAuthCall($apiData) {
    $apiHost = $this->_access_token_url;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiHost);
    curl_setopt($ch, CURLOPT_POST, count($apiData));
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($apiData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, array (
        "Authorization: Basic " . base64_encode($this->getApiKey() . ":" . $this->getApiSecret()),
    )); 
    
    $jsonData = curl_exec($ch);

    if (false === $jsonData) {
    	// response returned wasn't JSON formatted.
		wp_die(
			"Access Token Request Error : " . $jsonData,
			"Login Error"
		);
    }
    curl_close($ch);
    
    return json_decode($jsonData,true);
  }

  public function setAccessToken($data) {
    (true === is_object($data)) ? $token = $data->access_token : $token = $data;
    $this->_accesstoken = $token;
  }

  public function getAccessToken() {
    return $this->_accesstoken;
  }

  public function setApiKey($apiKey) {
    $this->_apikey = $apiKey;
  }

  public function getApiKey() {
    return $this->_apikey;
  }


  public function setApiSecret($apiSecret) {
    $this->_apisecret = $apiSecret;
  }

  public function getApiSecret() {
    return $this->_apisecret;
  }
  
  public function setApiCallback($apiCallback) {
    $this->_callbackurl = $apiCallback;
  }

  public function getApiCallback() {
    return $this->_callbackurl;
  }

  public function getUserInfo(){

  	$request = $this->_makeCall( "users/self/" , true );

  	if( isset( $request["resources"] ) && isset($request["resources"]["email"]) ):
  		return $request["resources"];
  	else :
  		return false;
  	endif;

  }

  public function loginUser(){

  	  $userinfo = $this->getUserInfo();

  	  if( $userinfo !== false ):

  	  	$existing_user =  WP_User::get_data_by( 'email', $userinfo["email"] );
		if ( !$existing_user ) {
			$userdata = new WP_User();                                          // Register a new user
			$userdata->first_name = $userinfo["first_name"];
			$userdata->last_name = $userinfo["last_name"];
			$userdata->user_email =$userinfo["email"];
			$userdata->user_login = $userinfo["username"];
			$password = wp_generate_password(16, FALSE);
			$userdata->user_pass = $password;
			$res = wp_insert_user($userdata);
			if(is_wp_error($res)) {
				// TODO: Do something here
			}
			$existing_user = WP_User::get_data_by( 'email', $userinfo["email"] );
			
		}
		
		$user = wp_set_current_user( $existing_user->ID, $existing_user->user_nicename );
		wp_set_auth_cookie( $existing_user->ID );
		do_action( 'wp_login', $existing_user->ID );
		wp_redirect(home_url());
		exit;

	  else : 
	  	// no user info were returned from API 
		wp_die(
			"Something went wrong... Go back and try again..<br/><br/><a href='".wp_login_url()."'>« Back</a>",
			"Login Error"
		);

  	  endif;
  }
}
