<?php

namespace Inc\LibMautic;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class Leadhub_Dash_Api_Mautic_ActionNotSupportedException extends Exception {
	/**
	 * {@inheritdoc}
	 */
	public function __construct( $message = 'Action is not supported at this time.', $code = 500, Exception $previous = null ) {
		parent::__construct( $message, $code, $previous );
	}
}
