<?php

namespace Inc\LibMautic;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class Leadhub_Dash_Api_Mautic_Emails extends Leadhub_Dash_Api_Mautic_Api {

	protected $endpoint = 'emails';

	public function create( array $parameters ) {
		return $this->actionNotSupported( 'create' );
	}

	public function edit( $id, array $parameters, $createIfNotExists = false ) {
		return $this->actionNotSupported( 'edit' );
	}

	public function delete( $id ) {
		return $this->actionNotSupported( 'delete' );
	}

	public function send( $id ) {
		return $this->makeRequest( $this->endpoint . '/' . $id . '/send', array(), 'POST' );
	}

	public function sendToContact( $id, $contactId ) {
		return $this->makeRequest( $this->endpoint . '/' . $id . '/send/contact/' . $contactId, array(), 'POST' );
	}

	public function sendToLead( $id, $leadId ) {
		return $this->sendToContact( $id, $leadId );
	}
}
