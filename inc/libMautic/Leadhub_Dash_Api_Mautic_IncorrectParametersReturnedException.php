<?php

namespace Inc\LibMautic;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

/**
 * Exception representing an incorrect parameter set for an OAuth token request
 */
class Leadhub_Dash_Api_Mautic_IncorrectParametersReturnedException extends Exception {

}
