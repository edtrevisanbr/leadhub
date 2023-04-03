<?php

namespace Inc\LibMautic;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class Leadhub_Dash_Api_Mautic_OAuth extends Leadhub_Dash_Api_Mautic_ApiAuth implements Leadhub_Dash_Api_Mautic_AuthInterface {

	protected $_client_id;
	protected $_client_secret;
	protected $_callback;
	protected $_access_token;
	protected $_access_token_secret;
	protected $_expires;
	protected $_refresh_token;
	protected $_token_type;
	protected $_access_token_updated = false;
	protected $_redirect_type = 'code';
	protected $_scope = array();
	protected $_authorize_url;
	protected $_access_token_url;
	protected $_request_token_url;
	protected $_debug = false;

	public function setup(
		$baseUrl = null,
		$clientKey = null,
		$clientSecret = null,
		$accessToken = null,
		$accessTokenExpires = null,
		$callback = null,
		$scope = null,
		$refreshToken = null
	) {
		$this->_client_id = $clientKey;
		$this->_client_secret = $clientSecret;
		$this->_access_token = $accessToken;
		$this->_callback = $callback;
	
		if ($baseUrl) {
			if (!$this->_access_token_url) {
				$this->_access_token_url = $baseUrl . '/oauth/v2/token';
			}
			if (!$this->_authorize_url) {
				$this->_authorize_url = $baseUrl . '/oauth/v2/authorize';
			}
		}
	
		if (!empty($scope)) {
			$this->setScope($scope);
		}
	
		if (!empty($accessToken)) {
			$this->setAccessTokenDetails(
				array(
					'access_token' => $accessToken,
					'expires' => $accessTokenExpires,
					'refresh_token' => $refreshToken,
				)
			);
		}
	}
	

	public function setAccessTokenUrl( $url ) {
		$this->_access_token_url = $url;

		return $this;
	}

	public function setRedirectType( $type ) {
		$this->_redirect_type = $type;

		return $this;
	}

	public function setScope( $scope ) {
		if ( ! is_array( $scope ) ) {
			$this->_scope = explode( ',', $scope );
		} else {
			$this->_scope = $scope;
		}

		return $this;
	}

	public function setAccessTokenDetails( array $accessTokenDetails ) {
		$this->_access_token        = isset( $accessTokenDetails['access_token'] ) ? $accessTokenDetails['access_token'] : null;
		$this->_access_token_secret = isset( $accessTokenDetails['access_token_secret'] ) ? $accessTokenDetails['access_token_secret'] : null;
		$this->_expires             = isset( $accessTokenDetails['expires'] ) ? $accessTokenDetails['expires'] : null;
		$this->_refresh_token       = isset( $accessTokenDetails['refresh_token'] ) ? $accessTokenDetails['refresh_token'] : null;

		return $this;
	}

	public function getAccessTokenData() {
		return array(
			'access_token'  => $this->_access_token,
			'expires'       => $this->_expires,
			'token_type'    => $this->_token_type,
			'refresh_token' => $this->_refresh_token,
		);
	}
	

	public function enableDebugMode() {
		$this->_debug = true;

		return $this;
	}

	public function accessTokenUpdated() {
		return $this->_access_token_updated;
	}

	public function getDebugInfo() {
		return ( $this->_debug && ! empty( $_SESSION['oauth']['debug'] ) ) ? $_SESSION['oauth']['debug'] : array();
	}

	public function isAuthorized() {
		//Check for existing access token
		if ( ! empty( $this->_request_token_url ) ) {
			if ( strlen( $this->_access_token ) > 0 && strlen( $this->_access_token_secret ) > 0 ) {
				return true;
			}
		}

		//Check to see if token in session has expired
		if ( ! empty( $this->_expires ) && $this->_expires < time() ) {
			return false;
		}

		if ( strlen( $this->_access_token ) > 0 ) {
			return true;
		}

		return false;
	}

	public function validateAccessToken() {
		$this->log('validateAccessToken()');
	
		// Check to see if token in session has expired
		if (!empty($this->_expires) && $this->_expires < time()) {
			$this->log('access token expired so reauthorize');
	
			if (strlen($this->_refresh_token) > 0) {
				// Use a refresh token to get a new token
				return $this->requestAccessToken();
			}
	
			// Reauthorize
			return $this->authorize($this->_scope);
		}
	
		// Check for existing access token
		if (strlen($this->_access_token) > 0) {
			$this->log('has access token');
	
			return true;
		}
	
		// Reauthorize if no token was found
		if (strlen($this->_access_token) == 0) {
			$this->log('access token empty so authorize');
	
			// OAuth 2.0
			$this->log('authorizing with OAuth2 spec');
	
			// Authorize app
			if (!isset($_GET['state']) && !isset($_GET['code'])) {
				return $this->authorize($this->_scope);
			}
	
			if ($this->_debug) {
				$_SESSION['oauth']['debug']['received_state'] = $_GET['state'];
			}
	
			// Request an access token
			if ($_GET['state'] != get_option('tvd_mautic_state')) {
				delete_option('tvd_mautic_state');
	
				return false;
			}
	
			$_SESSION['oauth']['state'] = get_option('tvd_mautic_state');
			delete_option('tvd_mautic_state');
			$this->requestAccessToken('POST', array(), 'json');
	
			return true;
		}
	}
	

	protected function requestAccessToken($method = 'POST', array $params = array(), $responseType = 'flat')
	{
		$this->log('requestAccessToken()');
	
		// OAuth 2.0
		$this->log('using OAuth2 spec');
		$parameters = array(
			'client_id' => $this->_client_id,
			'redirect_uri' => $this->_callback,
			'client_secret' => $this->_client_secret,
			'grant_type' => 'authorization_code',
		);
	
		if (isset($_GET['code'])) {
			$parameters['code'] = $_GET['code'];
		}
	
		if (strlen($this->_refresh_token) > 0) {
			$this->log('Using refresh token');
			$parameters['grant_type'] = 'refresh_token';
			$parameters['refresh_token'] = $this->_refresh_token;
		}
	
		$parameters = array_merge($parameters, $params);
	
		// Make the request
		$settings = array(
			'responseType' => $responseType,
			'includeCallback' => true,
			'includeVerifier' => true,
		);
	
		$params = $this->makeRequest($this->_access_token_url, $parameters, $method, $settings);
	
		// Add the token and secret to session
		if (is_array($params)) {
			// OAuth 2.0
			if (isset($params['access_token']) && isset($params['expires_in'])) {
				$this->log('access token set as ' . $params['access_token']);
				$this->_access_token = $params['access_token'];
				$this->_expires = time() + $params['expires_in'];
				$this->_token_type = (isset($params['token_type'])) ? $params['token_type'] : null;
				$this->_refresh_token = (isset($params['refresh_token'])) ? $params['refresh_token'] : null;
				$this->_access_token_updated = true;
	
				if ($this->_debug) {
					$_SESSION['oauth']['debug']['tokens']['access_token'] = $params['access_token'];
					$_SESSION['oauth']['debug']['tokens']['expires_in'] = $params['expires_in'];
					$_SESSION['oauth']['debug']['tokens']['token_type'] = $params['token_type'];
					$_SESSION['oauth']['debug']['tokens']['refresh_token'] = $params['refresh_token'];
				}
	
				return true;
			}
		}
	
		$this->log('response did not have an access token');
	
		if ($this->_debug) {
			$_SESSION['oauth']['debug']['response'] = $params;
		}
	
		if (is_array($params)) {
			if (isset($params['errors'])) {
				$errors = array();
				foreach ($params['errors'] as $error) {
					$errors[] = $error['message'];
				}
				$response = implode("; ", $errors);
			} elseif (isset($params['error'])) {
				if (is_array($params['error'])) {
					if (isset($params['error']['message'])) {
						$response = $params['error']['message'];
					} else {
						$response = print_r($params['error'], true);
					}
				} elseif ( isset( $params['error_description'] ) ) {
					$response = $params['error_description'];
				} else {
					$response = $params['error'];
				}
			} else {
				$response = print_r( $params, true );
			}
		} else {
			$response = $params;
		}

		throw new Leadhub_Dash_Api_Mautic_IncorrectParametersReturnedException( 'Incorrect access token parameters returned: ' . $response );
	}

	
	protected function authorize(array $scope = array(), $scope_separator = ',', $attach = null)
{
    $authUrl = $this->_authorize_url;

    // OAuth 2.0
    $authUrl .= '?client_id=' . $this->_client_id . '&redirect_uri=' . urlencode($this->_callback);
    $state = md5(time() . mt_rand());

    update_option('tvd_mautic_state', $state);
    $_SESSION['oauth']['state'] = $state;
    if ($this->_debug) {
        $_SESSION['oauth']['debug']['generated_state'] = $state;
    }

    $authUrl .= '&state=' . $state . '&scope=' . implode($scope_separator, $scope) . $attach;
    $authUrl .= '&response_type=' . $this->_redirect_type;

    $this->log('redirecting to auth url ' . $authUrl);

    return $authUrl;
}


public function makeRequest($url, array $parameters = array(), $method = 'GET', array $settings = array())
{
    list($url, $parameters) = $this->separateUrlParams($url, $parameters);

    // make sure $method is capitalized for congruency
    $method = strtoupper($method);

    // OAuth 2.0
    $parameters['access_token'] = $this->_access_token;

    // Create a querystring for GET/DELETE requests
    if (count($parameters) > 0 && in_array($method, array('GET', 'DELETE')) && strpos($url, '?') === false) {
        $url = $url . '?' . http_build_query($parameters);
    }

    $args = array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $this->_access_token,
            'Expect' => ''
        ),
        'body' => http_build_query($parameters)
    );

    switch ($method) {
        case 'POST':
            $result = tve_dash_api_remote_post($url, $args);
            break;
        case 'GET':
        default:
            $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($parameters);
            $get_args = array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->_access_token,
                    'Expect' => ''
                )
            );
            $result = tve_dash_api_remote_get($url, $get_args);
            break;
    }

    if ($result instanceof WP_Error) {
        throw new Leadhub_Dash_Api_Mautic_UnexpectedResponseFormatException($result->get_error_message());
    }

    $body = $result['body'];
    if (is_string($body) && !is_array(json_decode($body, true))) {
        parse_str($result['body'], $body);
    }

    $the_headers = array();
    if (is_object($result['headers'])) {
        foreach ($result['headers'] as $k => $v) {
            $the_headers[$k] = $v;
        }
    } else {
        $the_headers = $result['headers'];
    }

    $header = implode("\r\n\r\n", $the_headers);

    $responseGood = false;
    // Check to see if the response is JSON
    $parsed = !is_array($body) ? json_decode($body, true) : $body;
    if ($parsed !== null) {
        $responseGood = true;
    }

    // Show error when http_code is not appropriate
    if (!in_array($result['response'], array(200, 201))) {
        if ($responseGood) {
            return $parsed;
        }
        throw new Leadhub_Dash_Api_Mautic_UnexpectedResponseFormatException($body);
    }

    return $responseGood ? $parsed : $body;
}

	protected function log( $message ) {
		if ( $this->_debug ) {
			$_SESSION['oauth']['debug']['flow'][ date( 'm-d H:i:s' ) ][] = $message;
		}
	}

	protected function separateUrlParams( $url, $params ) {
		$a = parse_url( $url );

		if ( ! empty( $a['query'] ) ) {
			parse_str( $a['query'], $qparts );
			foreach ( $qparts as $k => $v ) {
				$cleanParams[ $k ] = $v ? $v : '';
			}
			$params = array_merge( $params, $cleanParams );
			$url    = explode( '?', $url, 2 )[0];
		}

		return array( $url, $params );
	}
}