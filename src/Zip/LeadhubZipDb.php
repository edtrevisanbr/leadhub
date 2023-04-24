<?php
namespace Src\Zip;
if (!defined('ABSPATH')) {
    exit; // Silence is golden
}

class LeadhubZipDb{
    public function create_table_if_not_exists() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'leadhub_mautic_emails';

        // Verifique se a tabela já existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            // Crie a tabela, caso ela não exista
            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
                id INT AUTO_INCREMENT PRIMARY KEY,
                {$wpdb->prefix}post_id INT NOT NULL,
                {$wpdb->prefix}email_id INT,
                {$wpdb->prefix}sender_id INT,
                {$wpdb->prefix}category_id INT,
                {$wpdb->prefix}attached_file_path VARCHAR(255),
                mautic_email_id INT,
                {$wpdb->prefix}attached_file_key VARCHAR(5) NULL,
                UNIQUE KEY ({$wpdb->prefix}post_id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
}
