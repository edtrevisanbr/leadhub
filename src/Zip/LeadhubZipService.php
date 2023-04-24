<?php

namespace Src\Zip;

if (!defined('ABSPATH')) {
    exit; // Silence is golden
}

use ZipArchive;
use WP_Query;
use Src\Zip\LeadhubZipDb;

class LeadhubZipService{

    protected $leadhubDb;

    public function __construct()
    {
        $this->leadhubDb = new LeadhubZipDb();
        $this->leadhubDb->create_table_if_not_exists();
        add_action('admin_notices', [$this, 'show_category_error_notice']);
        add_filter('wp_insert_post_data', [$this, 'filter_post_data'], 10, 2);
        add_filter('post_updated_messages', array($this, 'post_updated_messages'));
    }

    public function show_category_error_notice()
    {
        $error_message = get_transient('leadhub_zip_category_error');
        if (!empty($error_message)) {
            echo '<div class="notice notice-error is-dismissible"><p>' . $error_message . '</p></div>';
            delete_transient('leadhub_zip_category_error');
        }
    }

    public function post_updated_messages($messages) {
        $post = get_post();
        if ($post->post_type == 'post' && $post->post_status == 'draft') {
            $messages['post'][6] = '';
        }
        return $messages;
    }

    public function is_category_assigned_to_cpts($category_id) {
        // Verifica se o tipo de post atual é "post"
        if (get_post_type() !== 'post') {
            return false;
        }
    
        $cpt1 = 'leadhub_emails';
        $cpt2 = 'leadhub_senders';
        
        $cpt1_args = array(
            'post_type' => $cpt1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'category',
                    'field'    => 'term_id',
                    'terms'    => $category_id,
                ),
            ),
        );
    
        $cpt2_args = array(
            'post_type' => $cpt2,
            'tax_query' => array(
                array(
                    'taxonomy' => 'category',
                    'field'    => 'term_id',
                    'terms'    => $category_id,
                ),
            ),
        );
    
        $cpt1_query = new WP_Query($cpt1_args);
        $cpt2_query = new WP_Query($cpt2_args);
    
        if (!$cpt1_query->have_posts()) {
            return $cpt1;
        } elseif (!$cpt2_query->have_posts()) {
            return $cpt2;
        } else {
            return false;
        }
    }
    

    public function filter_post_data($data, $postarr) {
        if ($data['post_status'] == 'publish') {
            $categories = isset($postarr['post_category']) ? array_filter($postarr['post_category']) : [];
            $default_category_id = get_option('default_category');
            $category_error = false;
    
            if (count($categories) === 1 && in_array($default_category_id, $categories)) {
                $error_message = 'Please assign at least one category other than the default before publishing or updating the item.';
                $category_error = true;
            } else {
                foreach ($categories as $category_id) {
                    $missing_cpt = $this->is_category_assigned_to_cpts($category_id);
                    if ($missing_cpt) {
                        $error_message = 'The selected category is not assigned to the Custom Post Type "' . $missing_cpt . '".';
                        $category_error = true;
                        break;
                    }
                }
            }
    
            if ($category_error) {
                $data['post_status'] = 'draft';
                set_transient('leadhub_zip_category_error', $error_message, 45);
            }
        }
    
        return $data;
    }
    

    public function remove_default_category($post_id) {
        $default_category_id = get_option('default_category');
        $post_categories = wp_get_post_categories($post_id);
    
        if (count($post_categories) > 1 && in_array($default_category_id, $post_categories)) {
            $key = array_search($default_category_id, $post_categories);
            unset($post_categories[$key]);
            wp_set_post_categories($post_id, $post_categories);
        }
    }
    

    public function process_post_images_zip($post_id) {
        
        $post = get_post($post_id);

        // Get the categories for the post
        $categories = get_the_category($post_id);

        // Check if the categories list is empty
        if (empty($categories)) {
            $error_message = 'Please assign at least one category before publishing or updating the item.';
            // Display the error message using the 'admin_notices' action hook
            add_action('admin_notices', function() use ($error_message) {
                echo '<div class="notice notice-error is-dismissible"><p>' . $error_message . '</p></div>';
            });
            return; // Stop the execution of the method
        }

        if ($post->post_type == 'post' && $post->post_status == 'publish') {
            
            global $wpdb;
            $images = $wpdb->get_results("SELECT wp_postmeta.meta_id, wp_postmeta.meta_value, wp_posts.ID, wp_posts.post_parent
                                        FROM wp_posts INNER JOIN wp_postmeta ON wp_posts.ID = wp_postmeta.post_id
                                        WHERE (((wp_postmeta.meta_key)='_wp_attached_file') AND ((wp_posts.post_parent)={$post_id}))");

                if (!empty($images)) {
                    $last_folder = $this->get_last_folder_path();
                    $zip_filepath = $last_folder . '/' . $post->post_name . '.zip';

                    $zip_filepath = str_replace('\\', '/', $zip_filepath);

                    $this->delete_existing_zip($zip_filepath);
                    $zip_created = $this->create_zip($images, $zip_filepath);

                // Verifique se este post já tem um sufixo aleatório armazenado no banco de dados
                $wp_file_key = $this->get_wp_file_key($post_id);

                // Se não houver sufixo aleatório, gere um novo e armazene-o no banco de dados
                if (!$wp_file_key) {
                    $wp_file_key = $this->generate_wp_file_key();
                    $this->store_wp_file_key($post_id, $wp_file_key);
                }

                // Adicione o sufixo aleatório ao nome do arquivo ZIP
                $zip_filename = $post->post_name . '_' . $wp_file_key . '.zip';

                // Altere a linha onde o arquivo ZIP é criado para usar $zip_filename
                $zip_filepath = $last_folder . '/' . $zip_filename;
                $zip_filepath = str_replace('\\', '/', $zip_filepath);
                

                if ($zip_created) {
                    $zip_file_url_with_name = $zip_file_url . '/' . $zip_filename . '_' . $wp_file_key . '.zip';
                    $this->store_zip_data($post_id, $zip_file_url_with_name, $wp_file_key);
                }
                
                }

            }
        }

    private function generate_wp_file_key() {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $wp_file_key = '';
        for ($i = 0; $i < 5; $i++) {
            $wp_file_key .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $wp_file_key;
    }
    
    private function get_wp_file_key($post_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'leadhub_mautic_emails';
        $wp_file_key = $wpdb->get_var($wpdb->prepare("SELECT wp_wp_file_key FROM $table_name WHERE wp_post_id = %d", $post_id));
        return $wp_file_key;
    }
    
    private function store_wp_file_key($post_id, $wp_file_key) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'leadhub_mautic_emails';
        $wpdb->update(
            $table_name,
            array('wp_wp_file_key' => $wp_file_key),
            array('wp_post_id' => $post_id),
            array('%s'),
            array('%d')
        );
    }


    private function create_zip($post_images, $zip_filepath) {
        $zip = new \ZipArchive;
        if ($zip->open($zip_filepath, ZipArchive::CREATE) === TRUE) {

            foreach ($post_images as $image) {
                $file_path = get_attached_file($image->ID);
                $file_path = str_replace(ABSPATH, '', $file_path);
                $file_path = str_replace('\\', '/', $file_path);
                $file_name = basename($file_path);
            
                if (file_exists(ABSPATH . $file_path)) {
                    $zip->addFile(ABSPATH . $file_path, $file_name);
                    
                } else {
                    
                }
            }

            $zip->close();
            return true;
        } else {
            
            return false;
        }
    }

    private function delete_existing_zip($zip_filepath) {
        if (file_exists($zip_filepath)) {
            unlink($zip_filepath);
        } else {
            
        }
    }

    private function get_last_folder_path() {
        $upload_dir = wp_upload_dir();
        $base_dir = trailingslashit($upload_dir['basedir'] . '/zips');

        // Create the base directory if it doesn't exist.
        if (!file_exists($base_dir)) {
            mkdir($base_dir, 0777, true);
        }

        // Get a list of existing folders in the base directory.
        $folders = glob($base_dir . '*' , GLOB_ONLYDIR);

        // If no folders exist, create a new one.
        if (empty($folders)) {
            $new_folder = $base_dir . '00001';
            mkdir($new_folder, 0777, true);
            return $new_folder;
        }

        // Find the last folder in the list and check if it has room for another file.
        $last_folder = end($folders);
        $files_in_folder = glob($last_folder . '/*.zip');
        if (count($files_in_folder) >= 100) {
            // If the last folder is full, create a new one.
            $new_folder = $base_dir . sprintf('%05d', intval(basename($last_folder)) + 1);
            mkdir($new_folder, 0777, true);
            return $new_folder;
        } else {
            // If the last folder has room, use it.
            return $last_folder;
        }
    }

    private function store_zip_data($post_id, $zip_file_path, $wp_file_key)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'leadhub_mautic_emails';
    
        // Check if there is an existing entry for the given post_id
        $existing_entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE wp_post_id = %d", $post_id));
    
        if (!$existing_entry) {
            // Insert a new row with the post_id, zip_file_path, and wp_file_key
            $wpdb->insert(
                $table_name,
                array(
                    'wp_post_id' => $post_id,
                    'wp_attached_file_path' => $zip_file_path,
                    'wp_file_key' => $wp_file_key
                ),
                array('%d', '%s', '%s')
            );
        } else {
            // Update the existing row with the new zip_file_path and keep the same wp_file_key
            $wpdb->update(
                $table_name,
                array('wp_attached_file_path' => $zip_file_path, 'wp_file_key' => $wp_file_key),
                array('wp_post_id' => $post_id),
                array('%s', '%s'),
                array('%d')
            );
        }
    }
    
    
}