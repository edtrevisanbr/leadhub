<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

namespace Inc\LeadhubZip;

function get_post_images($post_id) {
    global $wpdb;
    $query = "SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_wp_attached_file'";
    $results = $wpdb->get_results($wpdb->prepare($query, $post_id));

    return $results;
}

function get_mautic_api_credentials() {
    // Substitua com a lógica para obter as credenciais da API do Mautic
    return array(
        'url' => 'https://your-mautic-url.com',
        'client_id' => 'your-client-id',
        'client_secret' => 'your-client-secret',
        'access_token' => 'your-access-token'
    );
}
?>
