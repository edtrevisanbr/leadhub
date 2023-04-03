<?php

namespace Inc\LibMautic;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class Leadhub_Dash_Api_Mautic_Campaigns extends Leadhub_Dash_Api_Mautic_Api {

	protected $endpoint = 'campaigns';

	public function create( array $parameters ) {
		return $this->actionNotSupported( 'create' );
	}

	public function edit( $id, array $parameters, $createIfNotExists = false ) {
		return $this->actionNotSupported( 'edit' );
	}

	public function delete( $id ) {
		return $this->actionNotSupported( 'delete' );
	}

	public function addLead( $id, $leadId ) {
		return $this->addContact( $id, $leadId );
	}

	public function addContact( $id, $contactId ) {
		return $this->makeRequest( $this->endpoint . '/' . $id . '/contact/add/' . $contactId, array(), 'POST' );
	}

	public function removeLead( $id, $leadId ) {
		return $this->removeContact( $id, $leadId );
	}

	public function removeContact( $id, $contactId ) {
		return $this->makeRequest( $this->endpoint . '/' . $id . '/contact/remove/' . $contactId, array(), 'POST' );
	}
}
