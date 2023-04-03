<?php

namespace Src;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class ClassLeadhubi18n {

	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'leadhub',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}

}
