<?php

/**
 * Plugin Name: LeadHub
 * Plugin URI: https://studymaps.com.br
 * Description: Compacta imagens em um post, gerando um ZIP que será usado como leadmagnet no Mautic
 * Version: 1.0.0
 * Author: Study Maps
 * Author URI: https://studymaps.com.br
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: leadhub
 * Domain Path: /languages
 */

// If this file is called directly, abort.
 if (!defined('WPINC')) {
     die;
 }
 
 define('LEADHUB_VERSION', '1.0.0');

 add_filter('nonce_life', function() {
    return 86400; // 24 horas em segundos
});
 
 $autoload_file = dirname(__FILE__) . '/vendor/autoload.php';
  if (file_exists($autoload_file)) {
     require_once $autoload_file;
 } else {
     die("Autoload file not found: $autoload_file");
 }
 
 use Src\ClassLeadhubActivator;
 use Src\ClassLeadhubDeactivator;
 use Src\ClassLeadhub;
 
 function activate_leadhub()
 {
     ClassLeadHubActivator::activate();
 }
 
 function deactivate_leadhub()
 {
     ClassLeadhubDeactivator::deactivate();
 }
 
 register_activation_hook(__FILE__, 'activate_leadhub');
 register_deactivation_hook(__FILE__, 'deactivate_leadhub');
 
 function run_leadhub()
 {
     $plugin = new ClassLeadhub();
     $plugin->run();
 }
 
 run_leadhub();
 
 // Hooks para processamento dos arquivos .zip
 add_action('save_post', 'process_post_images_zip');
 add_action('post_updated', 'process_post_images_zip');
 
 ?>