<?php

namespace Inc\LibMautic;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class Leadhub_Dash_Api_Mautic_Data extends Leadhub_Dash_Api_Mautic_Api
{

    protected $endpoint = 'data';

    public function get($id, $options)
    {
        return $this->makeRequest("{$this->endpoint}/$id", $options);
    }
}
