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

$autoload_file = dirname(__FILE__) . '/vendor/autoload.php';
if (file_exists($autoload_file)) {
    require_once $autoload_file;
} else {
    die("Autoload file not found: $autoload_file");
}

use Src\ClassLeadhubActivator;
use Src\ClassLeadhubDeactivator;
use Src\ClassLeadhub;
use Src\Zip\LeadhubZipService;

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
$leadhubZipService = new LeadhubZipService();

add_filter('wp_insert_post_data', array($leadhubZipService, 'filter_post_data'), 10, 2);
add_action('post_updated', array($leadhubZipService, 'process_post_images_zip'));
add_action('save_post', array($leadhubZipService, 'remove_default_category'), 20);


////// Testar logs de salvamento ou atualização de posts
function leadhub_save_post_log($post_id) {
    error_log('save_post hook triggered for post ID: ' . $post_id);
}
add_action('save_post', 'leadhub_save_post_log');



?>