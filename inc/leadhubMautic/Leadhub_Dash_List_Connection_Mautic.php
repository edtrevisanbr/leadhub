<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

namespace Inc\LeadhubMautic;

use Inc\LeadhubMautic\Leadhub_Dash_List_Connection_Abstract;
use Inc\LibMautic\Leadhub_Dash_Api_Mautic_ApiAuth;
use Inc\LibMautic\Leadhub_Dash_Api_Mautic;


class Leadhub_Dash_List_Connection_Mautic extends Leadhub_Dash_List_Connection_Abstract {

	protected $api;
	
	public function __construct($apiInstance) {
		parent::__construct($apiInstance);

		$apiInstance = Leadhub_Dash_Api_Mautic::getInstance();
		$key = "sua_chave_aqui";
		$connection = new Leadhub_Dash_List_Connection_Mautic($apiInstance, $key);

		$this->apiInstance = $apiInstance;
		$this->_custom_fields_transient = 'api_custom_fields_' . $apiInstance->getKey();
	}

	public function getTitle() {
		return 'Mautic';
	}

	public function outputSetupForm() {
		$this->_directFormHtml( 'mautic' );
	}

	public function readCredentials() {
		/** @var Leadhub_Dash_Api_Mautic_OAuth $mautic */
		$mautic = $this->getApi();
	
		error_log("Leadhub_Dash_List_Connection_Mautic: Início do readCredentials()");
		try {
			$mautic->validateAccessToken();
		} catch ( Leadhub_Dash_Api_Mautic_IncorrectParametersReturnedException $e ) {
			return $e->getMessage();
		}
	
		if ( $mautic->accessTokenUpdated() ) {
			$data            = get_option( 'tvd_mautic_credentials' );
			$accessTokenData = $mautic->getAccessTokenData();
			$credentials     = array_merge( $accessTokenData, $data );
	
			$this->setCredentials( $credentials );
			error_log("Leadhub_Dash_List_Connection_Mautic: Fim do readCredentials()");

		}
	
		$result = $this->testConnection();
	
		if ( $result !== true ) {
			return $this->error( sprintf( 'Could not test Mautic connection: %s', $result ) );
		}
	
		/**
		 * finally, save the connection details
		 */
		$this->save();
	
		return true;
	}
	

	/**
	 * Returns the authorize URL by appending the request
	 * token to the end of the Authorize URI, if it exists
	 */
	public function getAuthorizeUrl() {
		$url    = ! empty( $_POST['connection']['baseUrl'] ) ? sanitize_text_field( $_POST['connection']['baseUrl'] ) : '';
		$key    = ! empty( $_POST['connection']['clientKey'] ) ? sanitize_text_field( $_POST['connection']['clientKey'] ) : '';
		$secret = ! empty( $_POST['connection']['clientSecret'] ) ? sanitize_text_field( $_POST['connection']['clientSecret'] ) : '';
	
		if ( empty( $url ) ) {
			return $this->error( 'You must provide a valid Mautic api url' );
		}
	
		if ( empty( $key ) ) {
			return $this->error( 'You must provide a valid Mautic Public Key' );
		}
	
		if ( empty( $secret ) ) {
			return $this->error( 'You must provide a valid Mautic Secret Key' );
		}
	
		/** @var Leadhub_Dash_Api_Mautic_OAuth $mautic */
		$mautic = $this->getApi();
	
		/**
		 * check for trailing slash and remove it
		 */
		if ( substr( $this->param( 'baseUrl' ), - 1 ) == '/' ) {
			$url = substr( $this->param( 'baseUrl' ), 0, - 1 );
		}
	
		update_option( 'tvd_mautic_credentials', array(
			'baseUrl'      => $url,
			'version'      => $this->param( 'version' ),
			'clientKey'    => $this->param( 'clientKey' ),
			'clientSecret' => $this->param( 'clientSecret' ),
			'callback'     => admin_url( 'admin.php?page=leadhub_settings' ),
		) );
	
		try {
			return $mautic->validateAccessToken();
		} catch ( Leadhub_Dash_Api_Mautic_IncorrectParametersReturnedException $e ) {
			$this->error( $e->getMessage() );
		}
	}
	

	/**
	 * test if a connection can be made to the service using the stored credentials
	 */
	public function testConnection() {
		/** @var Leadhub_Dash_Api_Mautic_OAuth $mautic */
		$mautic = $this->getApi();

		$mautic->setAccessTokenDetails( $this->getCredentials() );

		$this->checkResetCredentials();

		$credentials = get_option( 'tvd_mautic_credentials' );

		/**
		 * just try getting a list as a connection test
		 */
		try {
			/** @var Leadhub_Dash_Api_Mautic_Contacts $contactsApi */
			$contactsApi = Leadhub_Dash_Api_Mautic::getContext( 'contacts', $mautic, $credentials['baseUrl'] . '/api/' );
			$contactsApi->getSegments();
		} catch ( Exception $e ) {
			return $e->getMessage();
		}

		return true;
	}

	protected function _apiInstance() {
		error_log("Leadhub_Dash_List_Connection_Mautic: Início do _apiInstance()");
		if (is_null($this->api)) {
			$settings = $this->readCredentials();
	
			if (isset($settings['mauticUrl'], $settings['mauticUsername'], $settings['mauticPassword'])) {
				$auth = new Inc\LibMautic\Leadhub_Dash_Api_Mautic_BasicAuth($settings['mauticUsername'], $settings['mauticPassword']);
				$this->api = Inc\LibMautic\Leadhub_Dash_Api_Mautic::newApi('contacts', $auth, $settings['mauticUrl']);
			} else {
				return false;
			}
		}
		error_log("Leadhub_Dash_List_Connection_Mautic: Fim do _apiInstance()");
		return $this->api;
		return $this->apiInstance;
	}
	



	
	

	/**
	 * get all Subscriber Lists from this API service
	 */
	protected function _getLists() {
		/** @var Leadhub_Dash_Api_Mautic_OAuth $api */
		$api = $this->getApi();
		$api->setAccessTokenDetails( $this->getCredentials() );
	
		$this->checkResetCredentials();
	
		/** @var Leadhub_Dash_Api_Mautic_Contacts $contactsApi */
		$contactsApi = Leadhub_Dash_Api_Mautic::getContext( 'contacts', $api, $this->param( 'baseUrl' ) . '/api/' );
	
		try {
			$lists = $contactsApi->getSegments();
	
			return $lists;
		} catch ( Exception $e ) {
			$this->_error = $e->getMessage() . ' Please re-check your API connection details.';
	
			return false;
		}
	}
	
	public function addSubscriber( $list_identifier, $arguments ) {
		$args = array();
	
		if ( ! empty( $arguments['name'] ) ) {
			list( $first_name, $last_name ) = $this->_getNameParts( $arguments['name'] );
			$args['firstname'] = $first_name;
			$args['lastname']  = $last_name;
		}
	
		/** @var Leadhub_Dash_Api_Mautic_OAuth $api */
		$api = $this->getApi();
		$api->setAccessTokenDetails( $this->getCredentials() );
	
		$this->checkResetCredentials();
	
		/** @var Leadhub_Dash_Api_Mautic_Contacts $contacts */
		/** @var Leadhub_Dash_Api_Mautic_Lists $list */
		$contacts = Leadhub_Dash_Api_Mautic::getContext( 'contacts', $api, $this->param( 'baseUrl' ) . '/api/' );
		$list     = Leadhub_Dash_Api_Mautic::getContext( 'lists', $api, $this->param( 'baseUrl' ) . '/api/' );
	
	
		if ( isset( $arguments['phone'] ) ) {
			$args['phone'] = $arguments['phone'];
		}
	
		try {
			$args['ipAddress'] = ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '';
			$args['email']     = $arguments['email'];
			$lead              = $contacts->create( $args );
	
			if ( isset( $lead['error'] ) ) {
				throw new Exception( $lead['error']['message'] );
			}
	
			$list->addLead( $list_identifier, $lead['contact']['id'] );
	
			return true;
		} catch ( Exception $e ) {
			return $e->getMessage() ? $e->getMessage() : 'Unknown Error';
		}
	}
	

	/**
	 * Return the connection email merge tag
	 */
	public static function getEmailMergeTag() {
		return '{leadfield=email}';
	}

	/**
	 * Reset the access token and expiration date
	 */
	private function checkResetCredentials() {

		/** @var Leadhub_Dash_Api_Mautic_OAuth $api */
		$api = $this->getApi();
		$api->setAccessTokenDetails( $this->getCredentials() );

		$api->validateAccessToken();

		if ( $api->accessTokenUpdated() ) {
			/**
			 * It seems that, the token was expired and has been updated let's resave the data
			 */
			$accessTokenData = $api->getAccessTokenData();
			$data            = get_option( 'tvd_mautic_credentials' );
			$credentials     = array_merge( $accessTokenData, $data );

			$this->setCredentials( $credentials );

			/**
			 * re-save the connection details
			 */
			$this->save();
		}
	}

	/**
	 * get the API Connection code to use in calls
	 */
	public function getApi() {
		if ( isset( $_REQUEST['oauth_token'] ) || isset( $_REQUEST['state'] ) ) {

			$data = get_option( 'tvd_mautic_credentials' );

			return Leadhub_Dash_Api_Mautic_ApiAuth::initiate( $data );
		} elseif ( ! isset( $this->_api ) ) {
			$this->_api = $this->_apiInstance();
		}

		return $this->_api;
	}

	public function get_automator_autoresponder_fields() {
		 return array( 'mailing_list' );
	}

}
