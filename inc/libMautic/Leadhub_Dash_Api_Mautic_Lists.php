<?php

namespace Inc\LibMautic;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class Leadhub_Dash_Api_Mautic_Lists extends Leadhub_Dash_Api_Mautic_Segments {

	protected $endpoint = 'segments';

	public function addLead( $id, $leadId ) {
		return $this->makeRequest( $this->endpoint . '/' . $id . '/contact/add/' . $leadId, array(), 'POST' );
	}

	public function removeLead( $id, $leadId ) {
		return $this->makeRequest( $this->endpoint . '/' . $id . '/contact/remove/' . $leadId, array(), 'POST' );
	}
}
