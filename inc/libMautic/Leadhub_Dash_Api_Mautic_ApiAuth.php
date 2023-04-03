<?php

namespace Inc\LibMautic;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class Leadhub_Dash_Api_Mautic_ApiAuth implements Leadhub_Dash_Api_Mautic_AuthInterface {
    
	private $callbackUri;
    private $httpClient;
    private $settings;

    public function __construct($settings, $callbackUri = null, $httpClient = null)
    {
        $this->settings = $settings;
        $this->callbackUri = $callbackUri;
        $this->httpClient = $httpClient;
    }


    // Adicione os métodos makeRequest e isAuthorized abaixo
	public function makeRequest($url, $parameters = [], $method = 'GET', $settings = [], $authType = 'oauth')
	{
		$ch = curl_init();
	
		$headers = [
			'Accept: application/json',
			'Content-Type: application/json',
			'Authorization: ' . $this->settings['access_token']
		];
	
		$options = [
			CURLOPT_URL => $url,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
		];
	
		if ($method == 'POST') {
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = json_encode($parameters);
		}
	
		curl_setopt_array($ch, $options);
	
		$response = curl_exec($ch);
	
		if ($response === false) {
			throw new Exception('Error: ' . curl_error($ch));
		}
	
		curl_close($ch);
	
		return json_decode($response, true);
	}
	

	public function isAuthorized()
	{
		return isset($this->settings['access_token']) && !empty($this->settings['access_token']);
	}
	

	public static function initiate( $parameters = array(), $authMethod = 'OAuth' ) {
		$object = new self;

		return $object->newAuth( $parameters, $authMethod );
	}


	/**
	 * Função proposta pelo CHATGPT: Get an API Auth object
	 */

	private function newAuth($settings, $authType = 'OAuth')
	{
		if ($authType === 'OAuth') {
			return new Leadhub_Dash_Api_Mautic_OAuth($settings, $this->callbackUri, $this->httpClient);
		} else {
			throw new \InvalidArgumentException("Invalid auth type '{$authType}'");
		}
	}
	

	/**
	 * Função original do Thrive Leads: Get an API Auth object
	 */

	 /** public function newAuth( $parameters = array(), $authMethod = 'OAuth' ) {
	*	$class      = 'Leadhub_Dash_Api_Mautic_' . $authMethod;
	*	$authObject = new $class();
	*
	*		$reflection = new ReflectionMethod( $class, 'setup' );
	*		$pass       = array();	
	*
	*		foreach ( $reflection->getParameters() as $param ) {
	*			if ( isset( $parameters[ $param->getName() ] ) ) {
	*				$pass[] = $parameters[ $param->getName() ];
	*			} else {
	*				$pass[] = null;
	*			}
	*		}	
	*
	*		$reflection->invokeArgs( $authObject, $pass );	
	*
	*		return $authObject;
	*	}*/	
}
