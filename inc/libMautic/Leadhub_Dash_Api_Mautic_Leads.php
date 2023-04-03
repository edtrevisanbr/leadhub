<?php

namespace Inc\LibMautic;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class Leadhub_Dash_Api_Mautic_Leads extends Leadhub_Dash_Api_Mautic_Contacts {

	public function getLists() {
		return $this->makeRequest( 'contacts/list/segments' );
	}

	public function getLeadNotes( $id, $search = '', $start = 0, $limit = 0, $orderBy = '', $orderByDir = 'ASC' ) {
		$parameters = array();

		$args = array( 'search', 'start', 'limit', 'orderBy', 'orderByDir' );

		foreach ( $args as $arg ) {
			if ( ! empty( $$arg ) ) {
				$parameters[ $arg ] = $$arg;
			}
		}

		return $this->makeRequest( 'contacts/' . $id . '/notes', $parameters );
	}

	public function getLeadLists( $id ) {
		return $this->makeRequest( 'contacts/' . $id . '/segments' );
	}

	public function getLeadCampaigns( $id ) {
		return $this->makeRequest( 'contacts/' . $id . '/campaigns' );
	}
}
