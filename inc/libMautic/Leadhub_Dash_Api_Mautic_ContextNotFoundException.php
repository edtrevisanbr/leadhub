<?php

namespace Inc\LibMautic;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class Leadhub_Dash_Api_Mautic_ContextNotFoundException extends Exception {

	public function __construct( $message = 'Context not found.', $code = 500, Exception $previous = null ) {
		parent::__construct( $message, $code, $previous );
	}
}
