<?php
/**
 * Plugin Name: Posts Grid & Search Widgets
 * Plugin URI: https://example.com
 * Description: Advanced posts grid widget with saved template selection, post type filtering, and enhanced search functionality.
 * Version: 3.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: posts-grid-search
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PGS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PGS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PGS_VERSION', '3.0.0');

class PostsGridSearchPlugin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('posts-grid-search', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->include_files();
        
        // Register widgets
        add_action('widgets_init', array($this, 'register_widgets'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_pgs_filter_posts', array($this, 'ajax_filter_posts'));
        add_action('wp_ajax_nopriv_pgs_filter_posts', array($this, 'ajax_filter_posts'));
        
        // Add admin menu for template management
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Initialize ACF integration
        PGS_ACF_Loop_Grid_Filters::get_instance();
    }
    
    private function include_files() {
        require_once PGS_PLUGIN_PATH . 'includes/class-post-layout-manager.php';
        require_once PGS_PLUGIN_PATH . 'includes/class-posts-grid-widget.php';
        require_once PGS_PLUGIN_PATH . 'includes/class-search-filter-widget.php';
        require_once PGS_PLUGIN_PATH . 'includes/class-acf-loop-grid-filters.php';
    }
    
    public function register_widgets() {
        register_widget('PGS_Posts_Grid_Widget');
        register_widget('PGS_Search_Filter_Widget');
    }
    
    public function enqueue_frontend_assets() {
        wp_enqueue_style('pgs-frontend-style', PGS_PLUGIN_URL . 'assets/css/frontend.css', array(), PGS_VERSION);
        wp_enqueue_script('pgs-frontend-script', PGS_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), PGS_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('pgs-frontend-script', 'pgs_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pgs_nonce')
        ));
        
        // Enqueue jQuery
        wp_enqueue_script('jquery');
        
        // Fancybox for video popups
        wp_enqueue_style(
            'fancybox-css',
            'https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.0/dist/fancybox/fancybox.css',
            array(),
            '6.0'
        );
        
        wp_enqueue_script(
            'fancybox-js',
            'https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.0/dist/fancybox/fancybox.umd.js',
            array('jquery'),
            '6.0',
            true
        );
        
        $fancybox_init = <<<JS
        jQuery(document).ready(function($) {
            if (typeof Fancybox !== 'undefined') {
                Fancybox.bind("[data-fancybox]", {
                    Thumbs: false,
                    Toolbar: {
                        display: [
                            { id: "counter", position: "center" },
                            "close",
                        ],
                    },
                });
            }
        });
        JS;
        
        wp_add_inline_script('fancybox-js', $fancybox_init);
    }
    
    public function enqueue_admin_assets($hook) {
        if ($hook === 'widgets.php' || strpos($hook, 'posts-grid-templates') !== false) {
            wp_enqueue_style('pgs-admin-style', PGS_PLUGIN_URL . 'assets/css/admin.css', array(), PGS_VERSION);
            wp_enqueue_script('pgs-admin-script', PGS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), PGS_VERSION, true);
            wp_enqueue_media();
            
            // Localize admin script
            wp_localize_script('pgs-admin-script', 'pgs_admin', array(
                'template_page_url' => admin_url('options-general.php?page=posts-grid-templates'),
                'nonce' => wp_create_nonce('pgs_admin_nonce')
            ));
        }
    }
    
    public function add_admin_menu() {
        add_options_page(
            __('Posts Grid Templates', 'posts-grid-search'),
            __('Posts Grid Templates', 'posts-grid-search'),
            'manage_options',
            'posts-grid-templates',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Posts Grid Templates', 'posts-grid-search') . '</h1>';
        echo '<p>' . __('Manage your saved templates here. Templates are created using page builders like Elementor.', 'posts-grid-search') . '</p>';
        
        // Display available templates
        $templates = self::get_saved_templates();
        if (!empty($templates)) {
            echo '<h2>' . __('Available Templates', 'posts-grid-search') . '</h2>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>' . __('Template Name', 'posts-grid-search') . '</th><th>' . __('Type', 'posts-grid-search') . '</th><th>' . __('Actions', 'posts-grid-search') . '</th></tr></thead>';
            echo '<tbody>';
            foreach ($templates as $id => $name) {
                $post = get_post($id);
                $post_type = $post ? $post->post_type : 'unknown';
                echo '<tr>';
                echo '<td>' . esc_html($name) . '</td>';
                echo '<td>' . esc_html($post_type) . '</td>';
                echo '<td><a href="' . esc_url(get_edit_post_link($id)) . '">' . __('Edit', 'posts-grid-search') . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>' . __('No templates found. Create templates using Elementor or other page builders.', 'posts-grid-search') . '</p>';
        }
        
        echo '</div>';
    }
    
    public function ajax_filter_posts() {
        check_ajax_referer('pgs_nonce', 'nonce');
        
        $search_query = sanitize_text_field($_POST['search_query'] ?? '');
        $posts_per_page = intval($_POST['posts_per_page'] ?? 6);
        $template_id = intval($_POST['template_id'] ?? 0);
        $layout = sanitize_text_field($_POST['layout'] ?? 'default');
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        $page = intval($_POST['page'] ?? 1);
        $widget_settings = $_POST['widget_settings'] ?? array();
        
        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => $posts_per_page,
            'paged' => $page,
        );
        
        if (!empty($search_query)) {
            $args['s'] = $search_query;
        }
        
        $query = new WP_Query($args);
        
        ob_start();
        if ($query->have_posts()) {
            $layout_manager = PGS_Post_Layout_Manager::get_instance();
            while ($query->have_posts()) {
                $query->the_post();
                echo $layout_manager->render_post($layout, $template_id, $widget_settings);
            }
        } else {
            echo '<div class="pgs-no-posts">' . __('No posts found.', 'posts-grid-search') . '</div>';
        }
        wp_reset_postdata();
        
        $posts_html = ob_get_clean();
        
        wp_send_json_success(array(
            'posts_html' => $posts_html,
            'total_pages' => $query->max_num_pages,
            'current_page' => $page,
            'total_posts' => $query->found_posts
        ));
    }
    
    public static function get_saved_templates() {
        $templates = get_posts(array(
            'post_type' => array('elementor_library', 'page', 'post'),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_elementor_template_type',
                    'value' => array('loop-item', 'single-post', 'archive'),
                    'compare' => 'IN'
                ),
                array(
                    'key' => '_pgs_template',
                    'value' => '1',
                    'compare' => '='
                )
            )
        ));
        
        $template_options = array();
        foreach ($templates as $template) {
            $template_options[$template->ID] = $template->post_title;
        }
        
        return $template_options;
    }
    
    public static function get_post_types() {
        $post_types = get_post_types(array(
            'public' => true,
            'show_ui' => true
        ), 'objects');
        
        $options = array();
        foreach ($post_types as $post_type) {
            if ($post_type->name !== 'attachment') {
                $options[$post_type->name] = $post_type->label;
            }
        }
        
        return $options;
    }
}

// Initialize the plugin
PostsGridSearchPlugin::get_instance();