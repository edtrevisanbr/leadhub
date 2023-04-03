<?php

namespace Src;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class ClassLeadhubActivator {

	public static function activate() {
		flush_rewrite_rules();
	}

}