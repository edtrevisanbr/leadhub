<?php

namespace Inc\LibMautic;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class Leadhub_Dash_Api_Mautic_Points extends Leadhub_Dash_Api_Mautic_Api {

	protected $endpoint = 'points';

	public function create( array $parameters ) {
		return $this->actionNotSupported( 'create' );
	}

	public function edit( $id, array $parameters, $createIfNotExists = false ) {
		return $this->actionNotSupported( 'edit' );
	}

	public function delete( $id ) {
		return $this->actionNotSupported( 'delete' );
	}
}