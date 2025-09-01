<?php
/**
 * ACF Loop Grid Filters Class
 * Optimized and enhanced ACF integration with centralized layout management
 */

if (!defined('ABSPATH')) {
    exit;
}

class PGS_ACF_Loop_Grid_Filters {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Hook into ACF if available
        if (function_exists('acf_add_local_field_group')) {
            add_action('acf/init', array($this, 'register_acf_fields'));
        }
        
        // Add shortcode support
        add_shortcode('pgs_acf_grid', array($this, 'render_acf_grid_shortcode'));
        
        // AJAX handlers for ACF grid filtering
        add_action('wp_ajax_pgs_acf_filter', array($this, 'ajax_acf_filter'));
        add_action('wp_ajax_nopriv_pgs_acf_filter', array($this, 'ajax_acf_filter'));
    }
    
    /**
     * Register ACF fields for enhanced post layouts
     */
    public function register_acf_fields() {
        // Register field group for locations post type
        acf_add_local_field_group(array(
            'key' => 'group_pgs_locations',
            'title' => 'Location Fields',
            'fields' => array(
                array(
                    'key' => 'field_pgs_tags',
                    'label' => 'Tags',
                    'name' => 'tags',
                    'type' => 'text',
                    'instructions' => 'Enter tags for this location',
                ),
                array(
                    'key' => 'field_pgs_channel_logo',
                    'label' => 'Channel Logo',
                    'name' => 'channel_logo',
                    'type' => 'image',
                    'instructions' => 'Upload a channel logo image',
                    'return_format' => 'array',
                    'preview_size' => 'thumbnail',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'locations',
                    ),
                ),
            ),
        ));
        
        // Register field group for posts with video support
        acf_add_local_field_group(array(
            'key' => 'group_pgs_posts_video',
            'title' => 'Post Video Fields',
            'fields' => array(
                array(
                    'key' => 'field_pgs_corner_icon',
                    'label' => 'Corner Icon',
                    'name' => 'corner_icon',
                    'type' => 'image',
                    'instructions' => 'Upload an icon for the corner of the post card',
                    'return_format' => 'array',
                    'preview_size' => 'thumbnail',
                ),
                array(
                    'key' => 'field_pgs_video_url',
                    'label' => 'Video URL',
                    'name' => 'video_url',
                    'type' => 'url',
                    'instructions' => 'Enter the video URL (YouTube, Vimeo, etc.)',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
                    ),
                ),
            ),
        ));
    }
    
    /**
     * Render ACF grid shortcode
     */
    public function render_acf_grid_shortcode($atts) {
        $atts = shortcode_atts(array(
            'post_type' => 'post',
            'posts_per_page' => 6,
            'layout' => 'default',
            'template_id' => 0,
            'show_search' => 'true',
            'show_pagination' => 'true',
            'pagination_style' => 'numbers',
            'show_excerpt' => 'true',
            'show_author' => 'true',
            'show_date' => 'true',
        ), $atts, 'pgs_acf_grid');
        
        // Convert string booleans to actual booleans
        $atts['show_search'] = filter_var($atts['show_search'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_pagination'] = filter_var($atts['show_pagination'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_excerpt'] = filter_var($atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_author'] = filter_var($atts['show_author'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_date'] = filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN);
        
        ob_start();
        
        // Generate unique ID for this grid
        $grid_id = 'pgs-acf-grid-' . uniqid();
        
        // Render search filter if enabled
        if ($atts['show_search']) {
            $this->render_acf_search_filter($grid_id, $atts);
        }
        
        // Render the grid
        $this->render_acf_grid($grid_id, $atts);
        
        return ob_get_clean();
    }
    
    /**
     * Render ACF search filter
     */
    private function render_acf_search_filter($grid_id, $atts) {
        $post_types = PostsGridSearchPlugin::get_post_types();
        ?>
        <div class="pgs-search-filter pgs-acf-search" data-target-grid="<?php echo esc_attr($grid_id); ?>">
            <div class="pgs-search-container">
                <div class="pgs-search-input-wrapper">
                    <svg class="pgs-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input 
                        type="text" 
                        class="pgs-search-input pgs-acf-search-input" 
                        placeholder="<?php _e('Search posts...', 'posts-grid-search'); ?>"
                        data-target-grid="<?php echo esc_attr($grid_id); ?>"
                    >
                    <button type="button" class="pgs-search-clear" style="display: none;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                
                <div class="pgs-post-type-filter">
                    <select class="pgs-post-type-select pgs-acf-post-type-select" data-target-grid="<?php echo esc_attr($grid_id); ?>">
                        <option value=""><?php _e('All Post Types', 'posts-grid-search'); ?></option>
                        <?php foreach ($post_types as $type => $label): ?>
                            <option value="<?php echo esc_attr($type); ?>" <?php selected($atts['post_type'], $type); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="pgs-search-results-info" style="display: none;">
                <span class="pgs-results-count"></span>
                <button type="button" class="pgs-clear-search">
                    <?php _e('Clear search', 'posts-grid-search'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render ACF grid
     */
    private function render_acf_grid($grid_id, $atts) {
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        
        $query_args = array(
            'post_type' => $atts['post_type'],
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['posts_per_page']),
            'paged' => $paged
        );
        
        $posts_query = new WP_Query($query_args);
        
        // Get layout CSS variables
        $layout_vars = PGS_Post_Layout_Manager::get_layout_css_vars($atts['layout'], $atts);
        $css_vars = '';
        foreach ($layout_vars as $var => $value) {
            $css_vars .= $var . ': ' . $value . '; ';
        }
        
        echo '<div class="pgs-posts-grid pgs-acf-grid" id="' . esc_attr($grid_id) . '" 
                   data-template-id="' . esc_attr($atts['template_id']) . '" 
                   data-layout="' . esc_attr($atts['layout']) . '"
                   data-post-type="' . esc_attr($atts['post_type']) . '" 
                   data-posts-per-page="' . esc_attr($atts['posts_per_page']) . '" 
                   data-widget-settings="' . esc_attr(json_encode($atts)) . '"
                   style="' . esc_attr($css_vars) . '">';
        
        $container_classes = PGS_Post_Layout_Manager::get_container_classes($atts['layout']);
        echo '<div class="' . esc_attr($container_classes) . '">';
        
        if ($posts_query->have_posts()) {
            $layout_manager = PGS_Post_Layout_Manager::get_instance();
            while ($posts_query->have_posts()) {
                $posts_query->the_post();
                echo $layout_manager->render_post($atts['layout'], intval($atts['template_id']), $atts);
            }
        } else {
            echo '<div class="pgs-no-posts">' . __('No posts found.', 'posts-grid-search') . '</div>';
        }
        
        echo '</div>'; // .pgs-posts-container
        
        // Pagination
        if ($atts['show_pagination'] && $posts_query->max_num_pages > 1) {
            $this->render_acf_pagination($posts_query, $atts);
        }
        
        echo '</div>'; // .pgs-posts-grid
        
        wp_reset_postdata();
    }
    
    /**
     * Render ACF pagination
     */
    private function render_acf_pagination($query, $atts) {
        $current_page = max(1, get_query_var('paged'));
        $total_pages = $query->max_num_pages;
        
        echo '<div class="pgs-pagination pgs-acf-pagination">';
        
        if ($atts['pagination_style'] === 'numbers') {
            $links = paginate_links(array(
                'base' => get_pagenum_link(1) . '%_%',
                'format' => 'page/%#%/',
                'current' => $current_page,
                'total' => $total_pages,
                'mid_size' => 1,
                'end_size' => 1,
                'prev_text' => '←',
                'next_text' => '→',
                'type' => 'array',
            ));
            
            if (!empty($links)) {
                foreach ($links as $link) {
                    $link = str_replace('page-numbers', 'pgs-pagination-btn', $link);
                    $link = str_replace('current', 'pgs-pagination-current', $link);
                    echo $link;
                }
            }
        } elseif ($atts['pagination_style'] === 'simple') {
            echo '<div class="pgs-pagination-simple">';
            if ($current_page > 1) {
                echo '<a href="' . esc_url(get_pagenum_link($current_page - 1)) . '" class="pgs-pagination-btn">' . __('Previous', 'posts-grid-search') . '</a>';
            }
            echo '<span class="pgs-pagination-info">' . sprintf(__('Page %d of %d', 'posts-grid-search'), $current_page, $total_pages) . '</span>';
            if ($current_page < $total_pages) {
                echo '<a href="' . esc_url(get_pagenum_link($current_page + 1)) . '" class="pgs-pagination-btn">' . __('Next', 'posts-grid-search') . '</a>';
            }
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * AJAX handler for ACF grid filtering
     */
    public function ajax_acf_filter() {
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
        
        // Add ACF field filtering if needed
        $acf_filters = $_POST['acf_filters'] ?? array();
        if (!empty($acf_filters) && is_array($acf_filters)) {
            $meta_query = array('relation' => 'AND');
            foreach ($acf_filters as $field => $value) {
                if (!empty($value)) {
                    $meta_query[] = array(
                        'key' => $field,
                        'value' => sanitize_text_field($value),
                        'compare' => 'LIKE'
                    );
                }
            }
            if (count($meta_query) > 1) {
                $args['meta_query'] = $meta_query;
            }
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
    
    /**
     * Get ACF fields for a post type
     */
    public static function get_acf_fields_for_post_type($post_type) {
        if (!function_exists('acf_get_field_groups')) {
            return array();
        }
        
        $field_groups = acf_get_field_groups(array(
            'post_type' => $post_type
        ));
        
        $fields = array();
        foreach ($field_groups as $group) {
            $group_fields = acf_get_fields($group['key']);
            if ($group_fields) {
                foreach ($group_fields as $field) {
                    $fields[$field['name']] = $field['label'];
                }
            }
        }
        
        return $fields;
    }
    
    /**
     * Render ACF field filters
     */
    public function render_acf_field_filters($post_type, $grid_id) {
        $acf_fields = self::get_acf_fields_for_post_type($post_type);
        
        if (empty($acf_fields)) {
            return;
        }
        
        echo '<div class="pgs-acf-filters" data-target-grid="' . esc_attr($grid_id) . '">';
        echo '<h4>' . __('Filter by Fields', 'posts-grid-search') . '</h4>';
        
        foreach ($acf_fields as $field_name => $field_label) {
            echo '<div class="pgs-acf-filter-field">';
            echo '<label for="pgs-acf-' . esc_attr($field_name) . '">' . esc_html($field_label) . ':</label>';
            echo '<input type="text" id="pgs-acf-' . esc_attr($field_name) . '" class="pgs-acf-filter-input" data-field="' . esc_attr($field_name) . '" data-target-grid="' . esc_attr($grid_id) . '">';
            echo '</div>';
        }
        
        echo '</div>';
    }
}