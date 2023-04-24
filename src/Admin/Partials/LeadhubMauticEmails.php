<?php
namespace Src\Admin\Partials;
if (!defined('ABSPATH')) {
    exit; // Silence is golden
}

class LeadhubMauticEmails
{
    public function __construct()
    {
        // Criação do CPT
        add_action('admin_enqueue_scripts', array($this, 'enqueue_emails_style'));
        add_action('init', array($this, 'create_emails_cpt'));

        // Validações
        add_action('admin_notices', array($this, 'show_category_error_notice'));
        add_filter('post_updated_messages', array($this, 'custom_post_updated_messages'));
        add_filter('wp_insert_post_data', array($this, 'filter_post_data'), 10, 2);
        add_action('save_post', array($this, 'check_download_url'), 1); 
    
        $this->post_type = 'leadhub_emails';

        // Gestão das colunas
        add_filter('manage_leadhub_emails_posts_columns', array($this, 'custom_emails_columns'));
        add_action('manage_leadhub_emails_posts_custom_column', array($this, 'custom_emails_column_content'), 10, 2);
        add_filter('manage_edit-leadhub_emails_sortable_columns', array($this, 'custom_emails_sortable_columns'));

    }

    // Mudar a cor do dashicon
    public function enqueue_emails_style()
    {
        wp_enqueue_style('leadhub-emails-css', plugin_dir_url(__FILE__) . 'css/leadhub-admin.css');
    }

    /**********************************************************************************
     * Post Type=emails
     **********************************************************************************/
    public function create_emails_cpt()
    {
        $labels = array(
            'name' => _x('Emails', 'Post Type General Name', 'leadhub'),
            'singular_name' => _x('Email', 'Post Type Singular Name', 'leadhub'),
            'menu_name' => __('Emails', 'leadhub'),
            'all_items' => __('All Emails', 'leadhub'),
            'add_new_item' => __('Add New Email', 'leadhub'),
            'edit_item' => __('Edit Email', 'leadhub'),
            'view_item' => __('View Email', 'leadhub'),
        );
        $args = array(
            'label' => __('Emails', 'leadhub'),
            'labels' => $labels,
            'supports' => array('title', 'editor'),
            'taxonomies' => array('category'),
            'hierarchical' => false,
            'public' => false, // Altere para false
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-email',
            'menu_position' => 42,
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => false, // Altere para false
            'can_export' => true,
            'has_archive' => false,
            'exclude_from_search' => true,
            'publicly_queryable' => false, // Altere para false
            'capability_type' => 'post',
        );
        register_post_type('leadhub_emails', $args);
    }

/**********************************************************************************
* Validação das categorias e da url no editor
**********************************************************************************/

    public function show_category_error_notice() {
        $error_message = get_transient('leadhub_emails_category_error');
        if (!empty($error_message)) {
            echo '<div class="notice notice-error is-dismissible"><p>' . $error_message . '</p></div>';
            delete_transient('leadhub_emails_category_error');
        }
    }

    public function custom_post_updated_messages($messages) {
        $post = get_post();
        if ($post->post_type == 'leadhub_emails' && $post->post_status == 'draft') {
            $messages['post'][6] = '';
        }
        return $messages;
    }

    public function filter_post_data($data, $postarr) {
        if ($data['post_type'] == 'leadhub_emails' && $data['post_status'] == 'publish') {
            $categories = isset($postarr['post_category']) ? array_filter($postarr['post_category']) : [];
            $duplicate_post_title = $this->has_duplicate_category($categories);
            $check_url = $this->check_download_url($postarr['ID']);

            if (empty($categories)) {
                $error_message = 'Please assign at least one category before publishing or updating the item.';
                $category_error = true;
            } elseif ($duplicate_post_title) {
                $error_message = 'The selected category is already in use by the post "' . $duplicate_post_title . '". Please choose a different category.';
                $category_error = true;
            } elseif ($check_url !== true) {
                $error_message = $check_url;
                $category_error = true;
            } else {
                $category_error = false;
            }

            if ($category_error) {
                $data['post_status'] = 'draft';
                set_transient('leadhub_emails_category_error', $error_message, 45);
            }
        }
        return $data;
    }

    private $post_type;

    public function check_download_url($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }
    
        if ($this->post_type !== get_post_type($post_id)) {
            return $post_id;
        }
    
        $content = get_post_field('post_content', $post_id);
    
        if (strpos($content, '{{DOWNLOAD_URL}}') === false) {
            return 'The content must contain {{DOWNLOAD_URL}}. Please add it and try again.';
        } else {
            delete_transient('leadhub_emails_category_error');
            return true;
        }
    }

    private function has_duplicate_category($categories) {
        global $post;
        $args = array(
            'post_type' => 'leadhub_emails',
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
            return $query->posts[0]->post_title;
        }

        return false;
    }


/**********************************************************************************
Criação de colunas personalizadas
**********************************************************************************/

    public function custom_emails_columns($columns) {
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


    public function custom_emails_column_content($column, $post_id){
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



    public function custom_emails_sortable_columns($columns) {
        $columns['class'] = 'class';
        $columns['group'] = 'group';
        $columns['category'] = 'category';
    
        return $columns;
    }

    public function emails_posts_clauses($clauses, $query) {
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
