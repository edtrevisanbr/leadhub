<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

namespace Inc\LeadhubZip;

function process_post_images_zip($post_id){
    //error_log("Hook save_post ou post_updated disparado para o post ID: " . $post_id);
    $post = get_post($post_id);

    //error_log("Informações do post: ID - " . $post->ID . ", Título - " . $post->post_title . ", Status - " . $post->post_status);

    if ($post->post_type == 'post' && $post->post_status == 'publish') {
        //error_log("O post é do tipo 'post'");
        global $wpdb;
        $images = $wpdb->get_results("SELECT wp_postmeta.meta_id, wp_postmeta.meta_value, wp_posts.ID, wp_posts.post_parent
                                      FROM wp_posts INNER JOIN wp_postmeta ON wp_posts.ID = wp_postmeta.post_id
                                      WHERE (((wp_postmeta.meta_key)='_wp_attached_file') AND ((wp_posts.post_parent)={$post_id}))");

            if (!empty($images)) {
                $last_folder = get_last_folder_path();
                $zip_filepath = $last_folder . '/' . $post->post_name . '.zip';

                // Adicione esta linha para substituir as barras invertidas por barras normais
                $zip_filepath = str_replace('\\', '/', $zip_filepath);

                delete_existing_zip($zip_filepath);
                $zip_created = create_zip($images, $zip_filepath);

            if ($zip_created) {
                //error_log("Arquivo ZIP criado com sucesso: " . $zip_filepath);
            } else {
                //error_log("Falha ao criar o arquivo ZIP: " . $zip_filepath);
            }

            handle_mautic_form_process();
        }
    }
}

function has_previous_published_status($post_id) {
    return true;
}

function is_status_change_to_publish($post_id) {
    return true;
}

function get_zip_folder_path() {
    $upload_dir = wp_upload_dir();
    $base_dir = trailingslashit($upload_dir['basedir'] . '/zips');
    if (!file_exists($base_dir)) {
        mkdir($base_dir, 0777, true);
    }
    return $base_dir;
}

function create_zip($post_images, $zip_filepath) {
    $zip = new ZipArchive;
    if ($zip->open($zip_filepath, ZipArchive::CREATE) === TRUE) {

        //error_log("ZipArchive aberto com sucesso");

        foreach ($post_images as $image) {
            $file_path = get_attached_file($image->ID);
            $file_path = str_replace(ABSPATH, '', $file_path);
            $file_path = str_replace('\\', '/', $file_path);
            $file_name = basename($file_path);
        
            //error_log('File path: ' . ABSPATH . $file_path);
        
            if (file_exists(ABSPATH . $file_path)) {
                $zip->addFile(ABSPATH . $file_path, $file_name);
                //error_log("Arquivo adicionado ao ZIP: " . $file_path);
            } else {
                //error_log("Arquivo não encontrado: " . $file_path);
            }
        }

        $zip->close();
        return true;
    } else {
        //error_log("Falha ao abrir o ZipArchive. Verifique as permissões do diretório: " . dirname($zip_filepath));
        return false;
    }
}

function delete_existing_zip($zip_filepath) {
    if (file_exists($zip_filepath)) {
        unlink($zip_filepath);
    } else {
        //error_log("Arquivo ZIP não encontrado para exclusão: " . $zip_filepath); // Adicione esta linha
    }
}

function get_last_folder_path() {
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

function handle_mautic_form_process() {
    // Substitua com a lógica para se conectar e trocar informações com o Mautic via API
    $mautic_credentials = get_mautic_api_credentials();

    if (check_mautic_connection($mautic_credentials)) {
        // Lógica para executar a conexão API e fazer a troca de informações
    } else {
        // Envie um e-mail para o administrador
    }
}

function check_mautic_connection($mautic_credentials) {
    // Substitua com a lógica para verificar se é possível estabelecer uma conexão API com o Mautic
    return true;
}

?>