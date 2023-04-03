<?php

namespace Inc\LibMautic;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class Leadhub_Dash_Api_Mautic {

	private static $instance = null;

	private function __construct() {
	  // construtor privado
	}
  
	public static function getInstance() {
	  if (self::$instance === null) {
		self::$instance = new Leadhub_Dash_Api_Mautic();
	  }
	  return self::$instance;
	}
	  
	public static function getContext( $apiContext, Leadhub_Dash_Api_Mautic_AuthInterface $auth, $baseUrl = '' ) {
		static $contexts = array();

		$apiContext = ucfirst( $apiContext );

		if ( ! isset( $context[ $apiContext ] ) ) {
			$class = 'Leadhub_Dash_Api_Mautic_' . $apiContext;

			if ( ! class_exists( $class ) ) {
				throw new Leadhub_Dash_Api_Mautic_ContextNotFoundException( "A context of '$apiContext' was not found." );
			}

			error_log("Leadhub_Dash_Api_Mautic: Início do newApi()");

			$contexts[ $apiContext ] = new $class( $auth, $baseUrl );
		}

		return $contexts[ $apiContext ];
		error_log("Leadhub_Dash_Api_Mautic: Fim do newApi()");

	}


	public function newApi( $apiContext, Leadhub_Dash_Api_Mautic_AuthInterface $auth, $baseUrl = '' ) {
		$apiContext = ucfirst( $apiContext );

		$class = 'Leadhub_Dash_Api_Mautic_' . $apiContext;

		if ( ! class_exists( $class ) ) {
			throw new Leadhub_Dash_Api_Mautic_ContextNotFoundException( "A context of '$apiContext' was not found." );
		}

		return new $class( $auth, $baseUrl );
	}
}