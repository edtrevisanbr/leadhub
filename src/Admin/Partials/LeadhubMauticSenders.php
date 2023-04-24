<?php
namespace Src\Admin\Partials;

if (!defined('ABSPATH')) {
    exit; // Silence is golden
}

class LeadhubMauticSenders{

    public function __construct(){
        add_action('admin_enqueue_scripts', array($this, 'enqueue_senders_style'));
        add_action('init', array($this, 'create_senders_cpt'));
        add_action('add_meta_boxes', array($this, 'add_senders_meta_boxes'));
        add_action('save_post', array($this, 'save_senders_meta_data'));
        add_action('admin_notices', array($this, 'show_category_error_notice'));
        add_filter('post_updated_messages', array($this, 'custom_post_updated_messages'));
        add_filter('wp_insert_post_data', array($this, 'filter_post_data'), 10, 2);
        add_filter('manage_leadhub_senders_posts_columns', array($this, 'custom_senders_columns'));
        add_action('manage_leadhub_senders_posts_custom_column', array($this, 'custom_senders_column_content'), 10, 2);
        add_filter('manage_edit-leadhub_senders_sortable_columns', array($this, 'custom_senders_sortable_columns'));
        add_filter('posts_clauses', array($this, 'senders_posts_clauses'), 10, 2);


    }

    // Mudar a cor do dashicon
    public function enqueue_senders_style()
    {
        wp_enqueue_style('leadhub-senders-css', plugin_dir_url(__FILE__) . 'css/leadhub-admin.css');
    }

/**********************************************************************************
Post Type = senders
**********************************************************************************/

    public function create_senders_cpt()
    {
        $labels = array(
            'name' => _x('Senders', 'Post Type General Name', 'leadhub'),
            'singular_name' => _x('Sender', 'Post Type Singular Name', 'leadhub'),
            'menu_name' => __('Senders', 'leadhub'),
            'all_items' => __('All Senders', 'leadhub'),
            'add_new_item' => __('Add New Sender', 'leadhub'),
            'edit_item' => __('Edit Sender', 'leadhub'),
            'view_item' => __('View Sender', 'leadhub'),
        );
        $args = array(
            'label' => __('Senders', 'leadhub'),
            'labels' => $labels,
            'supports' => array('title'),
            'taxonomies' => array('category'),
            'hierarchical' => false,
            'public' => false, // Altere para false
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-visibility',
            'menu_position' => 42,
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => false, // Altere para false
            'can_export' => true,
            'has_archive' => false,
            'exclude_from_search' => true,
            'publicly_queryable' => false, // Altere para false
            'capability_type' => 'post',
        );
        
        register_post_type('leadhub_senders', $args);
    }

    public function add_senders_meta_boxes()
    {
        add_meta_box(
            'senders_meta_box',
            __('Sender Information', 'leadhub'),
            array($this, 'render_senders_meta_box'),
            'leadhub_senders',
            'normal',
            'default'
        );
        wp_enqueue_style('leadhub-senders-css', plugin_dir_url(__FILE__) . '/css/leadhub-admin.css');
    }

    public function render_senders_meta_box($post)
    {
        wp_nonce_field('senders_nonce', 'senders_nonce_field');

        $sender_name = get_post_meta($post->ID, 'sender_name', true);
        $sender_email = get_post_meta($post->ID, 'sender_email', true);

        echo '<label for="sender_name">' . __('Sender Name', 'leadhub') . '</label>';
        echo '<input type="text" id="sender_name" name="sender_name" value="' . esc_attr($sender_name) . '" />';
        echo '<br /><br />';
        echo '<label for="sender_email">' . __('Sender E-mail', 'leadhub') . '</label>';
        echo '<input type="email" id="sender_email" name="sender_email" value="' . esc_attr($sender_email) . '" />';
    }

    public function save_senders_meta_data($post_id) {
        if (!isset($_POST['senders_nonce_field']) || !wp_verify_nonce($_POST['senders_nonce_field'], 'senders_nonce')) {
            return $post_id;
        }
    
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }
    
        if ('leadhub_senders' !== get_post_type($post_id)) {
            return $post_id;
        }
    
        // Atualize os metadados dos campos personalizados antes de verificar a categoria duplicada
        if (isset($_POST['sender_name'])) {
            $sender_name = sanitize_text_field($_POST['sender_name']);
            update_post_meta($post_id, 'sender_name', $sender_name);
        }
    
        if (isset($_POST['sender_email'])) {
            $sender_email = sanitize_email($_POST['sender_email']);
            if (is_email($sender_email)) {
                update_post_meta($post_id, 'sender_email', $sender_email);
            } else {
                delete_post_meta($post_id, 'sender_email');
            }
        }
    }

/**********************************************************************************
Validação das categorias
**********************************************************************************/

    public function show_category_error_notice(){
        $error_message = get_transient('leadhub_senders_category_error');
        if (!empty($error_message)) {
            echo '<div class="notice notice-error is-dismissible"><p>' . $error_message . '</p></div>';
            delete_transient('leadhub_senders_category_error');
        }
    }

    public function custom_post_updated_messages($messages) {
        $post = get_post();
        if ($post->post_type == 'leadhub_senders' && $post->post_status == 'draft') {
            $messages['post'][6] = ''; // Remove a mensagem "Post publicado. Ver post" para o post_type 'leadhub_senders'
        }
        return $messages;
    }

    public function filter_post_data($data, $postarr){
        if ($data['post_type'] == 'leadhub_senders' && $data['post_status'] == 'publish'){
            $categories = isset($postarr['post_category']) ? array_filter($postarr['post_category']) : [];
            $duplicate_post_title = $this->has_duplicate_category($categories);
            if (empty($categories)){
                $error_message = 'Please assign at least one category before publishing or updating the item.';
                $category_error = true;
            } else if ($duplicate_post_title) {
                $error_message = 'The selected category is already in use by the post "' . $duplicate_post_title . '". Please choose a different category.';
                $category_error = true;
            } else {
                $category_error = false;
            }
            if ($category_error){
                $data['post_status'] = 'draft';
                set_transient('leadhub_senders_category_error', $error_message, 45);
            }
        }
        return $data;
    }
    
    
    private function has_duplicate_category($categories){
        global $post;
        $args = array(
            'post_type' => 'leadhub_senders',
            'post_status' => 'publish',
            'post__not_in' => array($post->ID),
            'tax_query' => array(
                array(
                    'taxonomy' => 'category',
                    'field' => 'term_id',
                    'terms' => $categories,
                ),
            ),
        );
        $query = new \WP_Query($args);
        if ($query->have_posts()) {
            return $query->posts[0]->post_title; // Retorna o título do post em vez de um valor booleano
        }
        return false;
    }

/**********************************************************************************
Criação de colunas personalizadas
**********************************************************************************/

    public function custom_senders_columns($columns) {
        // Remove a coluna de categorias padrão
        unset($columns['categories']);

        // Adiciona as novas colunas
        $columns['class'] = __('Class', 'leadhub');
        $columns['group'] = __('Group', 'leadhub');
        $columns['category'] = __('Category', 'leadhub');

        return $columns;
    }

    private function get_category_hierarchy($post_id){
        $terms = wp_get_post_terms($post_id, 'category', array('orderby' => 'parent', 'order' => 'ASC'));
        $hierarchy = array();

        foreach ($terms as $term) {
            $category_hierarchy = array('class' => '', 'group' => '', 'category' => '');
            
            if ($term->parent === 0) {
                $category_hierarchy['class'] = $term->name;
            } else {
                $parent_term = get_term($term->parent, 'category');
                if ($parent_term->parent === 0) {
                    $category_hierarchy['group'] = $term->name;
                    $category_hierarchy['class'] = $parent_term->name;
                } else {
                    $category_hierarchy['category'] = $term->name;
                    $grandparent_term = get_term($parent_term->parent, 'category');
                    $category_hierarchy['group'] = $parent_term->name;
                    $category_hierarchy['class'] = $grandparent_term->name;
                }
            }
            $hierarchy[] = $category_hierarchy;
        }

        return $hierarchy;
    }


    public function custom_senders_column_content($column, $post_id){
        if ($column === 'class' || $column === 'group' || $column === 'category') {
            $hierarchy = $this->get_category_hierarchy($post_id);
            $class_names = array();
            $group_names = array();
            $category_names = array();

            foreach ($hierarchy as $category_hierarchy) {
                if (!empty($category_hierarchy['class']) && !in_array($category_hierarchy['class'], $class_names)) {
                    $class_names[] = $category_hierarchy['class'];
                }
                if (!empty($category_hierarchy['group']) && !in_array($category_hierarchy['group'], $group_names)) {
                    $group_names[] = $category_hierarchy['group'];
                }
                if (!empty($category_hierarchy['category']) && !in_array($category_hierarchy['category'], $category_names)) {
                    $category_names[] = $category_hierarchy['category'];
                }
            }

            if ($column === 'class') {
                echo implode(', ', $class_names);
            } else if ($column === 'group') {
                echo implode(', ', $group_names);
            } else if ($column === 'category') {
                echo implode(', ', $category_names);
            }
        }
    }



    public function custom_senders_sortable_columns($columns) {
        $columns['class'] = 'class';
        $columns['group'] = 'group';
        $columns['category'] = 'category';
    
        return $columns;
    }

    public function senders_posts_clauses($clauses, $query) {
        global $wpdb;
    
        if (!is_admin() || !$query->is_main_query()) {
            return $clauses;
        }
    
        $orderby = $query->get('orderby');
    
        if ($orderby === 'class' || $orderby === 'group' || $orderby === 'category') {
            $order = $query->get('order') ?: 'ASC';
    
            $clauses['join'] .= " LEFT JOIN {$wpdb->term_relationships} ON {$wpdb->posts}.ID={$wpdb->term_relationships}.object_id";
            $clauses['join'] .= " LEFT JOIN {$wpdb->term_taxonomy} ON {$wpdb->term_relationships}.term_taxonomy_id={$wpdb->term_taxonomy}.term_taxonomy_id";
            $clauses['join'] .= " LEFT JOIN {$wpdb->terms} ON {$wpdb->term_taxonomy}.term_id={$wpdb->terms}.term_id";
            $clauses['join'] .= " LEFT JOIN {$wpdb->term_taxonomy} AS tt_parent ON {$wpdb->term_taxonomy}.parent=tt_parent.term_id";
            $clauses['join'] .= " LEFT JOIN {$wpdb->terms} AS terms_parent ON tt_parent.term_id=terms_parent.term_id";
    
            $clauses['where'] .= " AND {$wpdb->term_taxonomy}.taxonomy='category'";
    
            if ($orderby === 'class') {
                $clauses['orderby'] = "terms_parent.name {$order}, {$wpdb->terms}.name {$order}";
            } elseif ($orderby === 'group') {
                $clauses['orderby'] = "tt_parent.parent=0 AND {$wpdb->term_taxonomy}.parent>0 {$order}, {$wpdb->terms}.name {$order}";
            } elseif ($orderby === 'category') {
                $clauses['orderby'] = "tt_parent.parent>0 {$order}, {$wpdb->terms}.name {$order}";
            }
    
            $clauses['groupby'] = "{$wpdb->posts}.ID";
        }
    
        return $clauses;
    }
    
    
}