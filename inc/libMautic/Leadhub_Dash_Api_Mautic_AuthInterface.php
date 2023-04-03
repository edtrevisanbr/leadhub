<?php

namespace Inc\LibMautic;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

interface Leadhub_Dash_Api_Mautic_AuthInterface {

	public function makeRequest( $url, array $parameters = array(), $method = 'GET', array $settings = array() );

	public function isAuthorized();
}
