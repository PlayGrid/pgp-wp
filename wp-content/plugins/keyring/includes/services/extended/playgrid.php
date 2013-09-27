<?php

/**
 * PlayGrid service definition for Keyring.
 * http://www.playgrid.com/
 */

// Setup actions
add_action( 'keyring_load_services', array( 'Keyring_Service_PlayGrid', 'init' ) );



class Keyring_Service_PlayGrid extends Keyring_Service_OAuth2 {
	const NAME  = 'playgrid';
	const LABEL = 'PlayGrid';

	function __construct() {
		parent::__construct();

		// Enable "basic" UI for entering key/secret
		if ( ! KEYRING__HEADLESS_MODE ) {
			add_action( 'keyring_playgrid_manage_ui', array( $this, 'basic_ui' ) );
			add_filter( 'keyring_playgrid_basic_ui_intro', array( $this, 'basic_ui_intro' ) );
		}
		
		// Handle limitation of not allowing us to redirect to a dynamic URL
		add_action( 'pre_keyring_playgrid_verify', array( $this, 'redirect_incoming_verify' ) );

		add_action( 'keyring_connection_verified', array( $this, 'connection_verified' ), 10, 3 );
		add_filter( 'keyring_verified_redirect', array( $this, 'verified_redirect' ), 10, 2 );

		
		$this->set_endpoint( 'authorize',    'http://local.playgrid.com:8000/o/authorize/', 'GET' );
		$this->set_endpoint( 'access_token', 'http://local.playgrid.com:8000/o/token/', 'POST' );
		$this->set_endpoint( 'self',         'http://local.playgrid.com:8000/api/1.1/users/self/',   'GET' );

		// Remove nonces, since they can not be saved in pgp application configuration
		$this->callback_url = remove_query_arg( array( 'nonce', 'kr_nonce' ), $this->callback_url );
		
		$creds = $this->get_credentials();
		$this->app_id  = $creds['app_id'];
		$this->key     = $creds['key'];
		$this->secret  = $creds['secret'];

		$this->consumer = new OAuthConsumer( $this->key, $this->secret, $this->callback_url );
		$this->signature_method = new OAuthSignatureMethod_HMAC_SHA1;

		$this->authorization_header    = false; // Send in querystring
		$this->authorization_parameter = 'access_token';

	}

	function basic_ui_intro() {
		echo '<p>' . sprintf( __( "To get started, <a href='http://playgrid.com/developer/clients/register/'>register an OAuth client on PlayGrid</a>. The most important setting is the <strong>OAuth redirect_uri</strong>, which should be set to <code>%s</code>. You can set the other values to whatever you like.", 'keyring' ), Keyring_Util::admin_url( 'playgrid', array( 'action' => 'verify' ) ) ) . '</p>';
		echo '<p>' . __( "Once you've saved those changes, copy the <strong>CLIENT ID</strong> value into the <strong>API Key</strong> field, and the <strong>CLIENT SECRET</strong> value into the <strong>API Secret</strong> field and click save (you don't need an App ID value for PlayGrid).", 'keyring' ) . '</p>';
	}
	
	function _get_credentials() {
		if ( defined( 'KEYRING__PLAYGRID_ID' ) && defined( 'KEYRING__PLAYGRID_SECRET' ) ) {
			return array(
					'app_id' => constant( 'KEYRING__PLAYGRID_ID' ),
					'key'    => constant( 'KEYRING__PLAYGRID_ID' ),
					'secret' => constant( 'KEYRING__PLAYGRID_SECRET' ),
			);
		} else {
			$all = apply_filters( 'keyring_credentials', get_option( 'keyring_credentials' ) );
			if ( !empty( $all['playgrid'] ) ) {
				$creds = $all['playgrid'];
				$creds['app_id'] = $creds['key'];
				return $creds;
			}
	
			// Return null to allow fall-thru to checking generic constants + DB
			return null;
		}
	}

	function redirect_incoming_verify( $request ) {
		if ( !isset( $request['kr_nonce'] ) ) {
			// Fix request from PlayGrid - nonce it and move on.
			$kr_nonce = wp_create_nonce( 'keyring-verify' );
			$nonce = wp_create_nonce( 'keyring-verify-' . $this->get_name() );
			wp_safe_redirect(
				Keyring_Util::admin_url(
					$this->get_name(),
					array(
						'action'   => 'verify',
						'kr_nonce' => $kr_nonce,
						'nonce'    => $nonce,
						'code'     => $request['code'],                         // Auth code from successful response (maybe)
						'state'    => $request['state'],                        // state from successful response (maybe)
					)
				)
			);
			exit;
		}
	}
	
	function build_token_meta( $token ) {
		$token = new Keyring_Access_Token( $this->get_name(), $token['access_token'], array() );
		$this->set_token( $token );
		
		$response = $this->request( $this->self_url, array( 'method' => $this->self_method ) );
		
		if ( Keyring_Util::is_error( $response ) ) {
			$meta = array();
		} else {
			$user = $response->resources;
			$meta = array(
					'uri'         => $user->url,                                // url is unique to pgp
					'username'    => $user->username,
					'first_name'  => $user->first_name,
					'last_name'   => $user->last_name,
					'email'       => $user->email,
					'is_active'   => $user->is_active,
					'last_login'  => $user->last_login,
					'date_joined' => $user->date_joined,
			);
		}

		return apply_filters( 'keyring_access_token_meta', $meta, 'playgrid', $token, $response, $this );
	}

	
	function connection_verified($service, $id, $request_token) {
		
		Keyring_Util::debug( '&&&&&&&&&&&&&&&&&&&&' );  // FIXME: Debugging

		$access_token = Keyring::get_token_store()->get_token( array( 'service' => $service, 'id' => $id ) );
		$email = $access_token->meta['email'];

		$existing_user =  WP_User::get_data_by( 'email', $email );
		if ( !$existing_user ) {
			$userdata = new WP_User();                                          // Register a new user
			$userdata->first_name = $access_token->meta['first_name'];
			$userdata->last_name = $access_token->meta['last_name'];
			$userdata->user_email = $access_token->meta['email'];
			$userdata->user_login = $access_token->meta['username'];
			$password = wp_generate_password(16, FALSE);
			$userdata->user_pass = $password;
			$res = wp_insert_user($userdata);
			if(is_wp_error($res)) {
				// TODO: Do something here
			}
			$existing_user = WP_User::get_data_by( 'email', $email );
			
		}
		
		update_user_meta($existing_user->ID, 'playgrid_token_id', $id);
		
		$user = wp_set_current_user( $existing_user->ID, $existing_user->user_nicename );
		wp_set_auth_cookie( $existing_user->ID );
		do_action( 'wp_login', $existing_user->ID );
		
		Keyring_Util::debug( $user );  // FIXME: Debugging
		Keyring_Util::debug( '&&&&&&&&&&&&&&&&&&&&' );  // FIXME: Debugging
	
	}
	
	function verified_redirect ($url, $service) {
		return get_edit_profile_url(wp_get_current_user()->ID);
	}
	
	function get_display( Keyring_Access_Token $token ) {
		return $token->get_meta( 'name' );
	}

	function test_connection() {                                                // This is actually tested in build_token_meta()
		$res = $this->request( $this->self_url, array( 'method' => $this->self_method ) );
		if ( !Keyring_Util::is_error( $res ) ) {
			return true;
		}

		return $res;
	}
}

