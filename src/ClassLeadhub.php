<?php

namespace Src;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

use Src\ClassLeadhubLoader;
use Src\ClassLeadhubi18n;
use Src\Admin\ClassLeadhubAdmin;
use Src\Public\ClassLeadhubPublic;


class ClassLeadhub {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        if ( defined( 'LEADHUB_VERSION' ) ) {
            $this->version = LEADHUB_VERSION;
        } else {
            $this->version = '1.0.0';
        }

        $this->plugin_name = 'leadhub';
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        if ( file_exists( dirname( __FILE__) . '/vendor/autoload.php') ){
            require_once dirname( __FILE__) . '/vendor/autoload.php';
            }
        $this->loader = new ClassLeadhubLoader();
    }
    
    private function set_locale() {
        $plugin_i18n = new ClassLeadhubi18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    private function define_admin_hooks() {
        $plugin_admin = new ClassLeadhubAdmin ( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'leadhub_menu' );
    }

    private function define_public_hooks() {
        $plugin_public = new ClassLeadhubPublic( $this->get_plugin_name(), $this->get_version() );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }
}
?>