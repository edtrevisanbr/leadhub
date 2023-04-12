<?php

namespace Src\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Silence is golden
}

use Src\Admin\Partials\LeadhubDashboard;
use Src\Admin\Partials\LeadhubLeadMagnetLog;
use Src\Admin\Partials\LeadhubContactsLog;
use Src\Admin\Partials\LeadhubSendersAvatar;
use Src\Admin\Partials\LeadhubMauticSettings;

class ClassLeadhubAdmin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        add_action('admin_menu', array($this, 'leadhub_menu'));
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/leadhub-admin.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/leadhub-admin.js', array('jquery'), $this->version, false);
    }
    

    public function leadhub_menu() {
        $leadhub_dashboard = new LeadhubDashboard();
        add_menu_page("LeadHub", "LeadHub", "manage_options", "leadhub", array($leadhub_dashboard, "render"), "dashicons-email-alt2");
        add_submenu_page("leadhub", "Dashboard", "Dashboard", "manage_options", "leadhub", array($leadhub_dashboard, "render"));

        $leadhub_mautic_integration_setting = new LeadhubMauticSettings();
        add_submenu_page("leadhub", "Mautic Settings", "Mautic Settings", "manage_options", "leadhub-mautic-integration-setting", array($leadhub_mautic_integration_setting, "render"));

        $leadhub_leadmagnet_log = new LeadhubLeadMagnetLog();
        add_submenu_page("leadhub", "Log > Lead-Magnets", "Log > Lead-Magnets", "manage_options", "leadhub-leadmagnet-log", array($leadhub_leadmagnet_log, "render"));

        $leadhub_contacts_log = new LeadhubContactsLog();
        add_submenu_page("leadhub", "Log > Contacts", "Log > Contacts", "manage_options", "leadhub-contacts-log", array($leadhub_contacts_log, "render"));

        $leadhub_senders_avatar = new LeadhubSendersAvatar();
        add_submenu_page("leadhub", "Senders Avatar", "Senders Avatar", "manage_options", "leadhub-senders-avatar", array($leadhub_senders_avatar, "render"));
    }

}
