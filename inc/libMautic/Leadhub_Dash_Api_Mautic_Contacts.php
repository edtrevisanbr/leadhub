<?php

namespace Inc\LibMautic;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class Leadhub_Dash_Api_Mautic_Contacts extends Leadhub_Dash_Api_Mautic_Api {

	protected $endpoint = 'contacts';

	public function getOwners() {
		return $this->makeRequest( $this->endpoint . '/list/owners' );
	}

	public function getFieldList() {
		return $this->makeRequest( $this->endpoint . '/list/fields' );
	}

	public function getSegments() {
		return $this->makeRequest( $this->endpoint . '/list/segments' );
	}

	public function getContactNotes( $id, $search = '', $start = 0, $limit = 0, $orderBy = '', $orderByDir = 'ASC' ) {
		$parameters = array();

		$args = array( 'search', 'start', 'limit', 'orderBy', 'orderByDir' );

		foreach ( $args as $arg ) {
			if ( ! empty( $$arg ) ) {
				$parameters[ $arg ] = $$arg;
			}
		}

		return $this->makeRequest( $this->endpoint . '/' . $id . '/notes', $parameters );
	}

	public function getContactSegments( $id ) {
		return $this->makeRequest( $this->endpoint . '/' . $id . '/segments' );
	}

	public function getContactCampaigns( $id ) {
		return $this->makeRequest( $this->endpoint . '/' . $id . '/campaigns' );
	}
}
