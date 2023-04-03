<?php

namespace Inc\LibMautic;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class Leadhub_Dash_Api_Mautic_UnexpectedResponseFormatException extends Exception {

	public function __construct( $message = 'The response returned is in an unexpected format.', $code = 500, Exception $previous = null ) {
		if ( empty( $message ) ) {
			$message = 'The response returned is in an unexpected format.';
		}

		parent::__construct( $message, $code, $previous );
	}
}
