<?php

namespace Src\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

use Inc\LeadhubMautic\Leadhub_Dash_List_Connection_Mautic;
//use Inc\ibMautic\Leadhub_Dash_Api_Mautic_ApiAuth;
//use Inc\Libmautic\Leadhub_Dash_Api_Mautic_Api;

class ClassLeadhubAdmin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        add_action('admin_menu', array($this, 'leadhub_menu'));
        add_action('admin_init', array($this, 'save_form_data'));
    }
    
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/leadhub-admin.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/leadhub-admin.js', array('jquery'), $this->version, false);
    }

    // Create menu method
    public function leadhub_menu() {
        add_menu_page("LeadHub", "LeadHub", "manage_options", "leadhub", array($this, "leadhub_plugin"), "dashicons-email-alt2");

        // Create plugin submenus
        add_submenu_page("leadhub", "Dashboard", "Dashboard", "manage_options", "leadhub", array($this, "leadhub_dashboard"));
        add_submenu_page("leadhub", "Log > Lead-Magnets", "Log > Lead-Magnets", "manage_options", "leadhub_leadmagnet_log", array($this, "leadhub_leadmagnet_log"));
        add_submenu_page("leadhub", "Log > Contacts", "Log > Contacts", "manage_options", "leadhub_contacts_log", array($this, "leadhub_contacts_log"));
        add_submenu_page("leadhub", "Senders Avatar", "Senders Avatar", "manage_options", "leadhub_senders_avatar", array($this, "leadhub_senders_avatar"));
        add_submenu_page("leadhub", "Settings", "Settings", "manage_options", "leadhub_settings", array($this, "leadhub_settings"));
    }


    // Menu callback functions
    public function leadhub_plugin() {
        echo "<h2>Dashboard</h2>";
    }

    public function leadhub_dashboard() {
        echo "<h3>Welcome to Plugin Sub Menu 1</h3>";
    }

    public function leadhub_leadmagnet_log() {
        echo "<h3>Log de processos: Lead Magnet</h3>";
    }

    public function leadhub_contacts_log() {
        echo "<h3>Log de processos: Mautic</h3>";
    }

    public function leadhub_senders_avatar() {
        echo "<h3>Mautic Senders Avatar</h3>";
    }

    public function leadhub_settings() {
        // Show success or error message based on the saved option value
        $connection_result = get_option('leadhub_mautic_api_connection_result');
        if ($connection_result === 'success') {
            echo "<div class='notice notice-success is-dismissible'><p>Connection to Mautic API established.</p></div>";
        } elseif (!empty($connection_result)) {
            echo "<div class='notice notice-error is-dismissible'><p>Error connecting to Mautic API: " . htmlspecialchars($connection_result) . "</p></div>";
        }
    
        // Create nonce
    //    $nonce = isset( $_POST['leadhub_nonce'] ) ? $_POST['leadhub_nonce'] : '';
    //    if ( ! wp_verify_nonce( $nonce, 'leadhub_nonce_action' ) ) {
    //        wp_die( 'Nonce inválido' );
    //    }
        
    
        // Display form
        echo "<h3>API Mautic</h3>";
        echo "<form method='post' action=''>";
        //      echo "<input type='hidden' name='leadhub_nonce' value='" . wp_create_nonce( 'leadhub_nonce_action' ) . "'/>";
        echo "<label for='base_url'>Base URL:</label>";
        echo "<input type='text' id='base_url' name='base_url' value='" . esc_attr(get_option('leadhub_mautic_base_url')) . "'><br>";
        echo "<label for='client_key'>Client Key:</label>";
        echo "<input type='text' id='client_key' name='client_key' value='" . esc_attr(get_option('leadhub_mautic_client_key')) . "'><br>";
        echo "<label for='client_secret'>Client Secret:</label>";
        echo "<input type='text' id='client_secret' name='client_secret' value='" . esc_attr(get_option('leadhub_mautic_client_secret')) . "'><br>";
        echo "<input type='submit' name='submit' value='Save'>";
        echo "</form>";
    }
    

    public function save_form_data() {
    //    check_admin_referer('leadhub_nonce_action', 'leadhub_nonce');
        
        if (isset($_POST['mautic_base_url'])) {
            $baseUrl = sanitize_text_field($_POST['mautic_base_url']);
            update_option('mautic_base_url', $baseUrl);
        }
    
        if (isset($_POST['mautic_client_key'])) {
            $clientKey = sanitize_text_field($_POST['mautic_client_key']);
            update_option('mautic_client_key', $clientKey);
        }
    
        if (isset($_POST['mautic_client_secret'])) {
            $clientSecret = sanitize_text_field($_POST['mautic_client_secret']);
            update_option('mautic_client_secret', $clientSecret);
        }
    
        // Crie a instância da API Mautic e configure com os valores do formulário
        $apiInstance = new MauticApi();
        $auth = ApiAuth::initiate([
            'baseUrl'      => $baseUrl,
            'version'      => 'OAuth2',
            'clientKey'    => $clientKey,
            'clientSecret' => $clientSecret,
            'callback'     => 'admin.php?page=leadhub_settings' // substitua pela URL de retorno apropriada
        ]);
        $apiInstance->setAuth($auth);
    
        // Criar instância de Leadhub_Dash_List_Connection_Mautic com os argumentos corretos
        $mautic = new Leadhub_Dash_List_Connection_Mautic($apiInstance);
    
        // Redirecionar para a página de configuração com uma mensagem de sucesso
        wp_redirect(add_query_arg('page', 'leadhub', admin_url('admin.php')) . '&status=success');
        exit;
    }
    

}

