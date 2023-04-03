<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
