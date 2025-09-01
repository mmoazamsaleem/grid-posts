<?php
/**
 * Post Layout Manager Class
 * Centralized post layout rendering for all widgets and filters
 */

if (!defined('ABSPATH')) {
    exit;
}

class PGS_Post_Layout_Manager {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get available layout options
     */
    public static function get_layout_options() {
        return array(
            'default' => __('Default Card Layout', 'posts-grid-search'),
            'minimal' => __('Minimal Layout', 'posts-grid-search'),
            'list' => __('List Layout', 'posts-grid-search'),
            'masonry' => __('Masonry Layout', 'posts-grid-search'),
            'custom' => __('Custom Post Type Layout', 'posts-grid-search')
        );
    }
    
    /**
     * Render post based on layout and template settings
     */
    public function render_post($layout, $template_id = 0, $widget_settings = array()) {
        $post_id = get_the_ID();
        $post_type = get_post_type($post_id);
        
        // If template ID is provided, use saved template
        if ($template_id && $template_id > 0) {
            return $this->render_saved_template($template_id);
        }
        
        // Use layout-based rendering
        switch ($layout) {
            case 'minimal':
                return $this->render_minimal_layout($widget_settings);
            case 'list':
                return $this->render_list_layout($widget_settings);
            case 'masonry':
                return $this->render_masonry_layout($widget_settings);
            case 'custom':
                return $this->render_custom_post_type_layout($widget_settings);
            default:
                return $this->render_default_layout($widget_settings);
        }
    }
    
    /**
     * Render saved template with placeholder replacement
     */
    private function render_saved_template($template_id) {
        $template_content = get_post_field('post_content', $template_id);
        if (!$template_content) {
            return $this->render_default_layout();
        }
        
        return $this->replace_template_placeholders($template_content);
    }
    
    /**
     * Replace template placeholders with actual post data
     */
    private function replace_template_placeholders($content) {
        $post_id = get_the_ID();
        $title = get_the_title();
        $excerpt = get_the_excerpt();
        $author = get_the_author();
        $date = get_the_date();
        $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium');
        $permalink = get_permalink();
        $post_type = get_post_type($post_id);
        
        // Basic replacements
        $replacements = array(
            '{{post_title}}' => esc_html($title),
            '{{post_excerpt}}' => esc_html($excerpt),
            '{{post_author}}' => esc_html($author),
            '{{post_date}}' => esc_html($date),
            '{{post_thumbnail}}' => $thumbnail_url ? esc_url($thumbnail_url) : '',
            '{{post_link}}' => esc_url($permalink),
            '{{post_id}}' => $post_id,
            '{{post_type}}' => esc_html($post_type)
        );
        
        // Add ACF field replacements
        $acf_fields = get_fields($post_id);
        if ($acf_fields) {
            foreach ($acf_fields as $field_name => $field_value) {
                if (is_array($field_value) && isset($field_value['url'])) {
                    // Handle ACF image fields
                    $replacements['{{' . $field_name . '}}'] = esc_url($field_value['url']);
                } elseif (is_string($field_value) || is_numeric($field_value)) {
                    $replacements['{{' . $field_name . '}}'] = esc_html($field_value);
                }
            }
        }
        
        foreach ($replacements as $placeholder => $value) {
            $content = str_replace($placeholder, $value, $content);
        }
        
        return $content;
    }
    
    /**
     * Default card layout
     */
    private function render_default_layout($widget_settings = array()) {
        $post_id = get_the_ID();
        $title = get_the_title();
        $excerpt = get_the_excerpt();
        $author = get_the_author();
        $date = get_the_date();
        $thumbnail = get_the_post_thumbnail($post_id, 'medium');
        $permalink = get_permalink();
        $post_type = get_post_type($post_id);
        
        $show_excerpt = !empty($widget_settings['show_excerpt']);
        $show_author = !empty($widget_settings['show_author']);
        $show_date = !empty($widget_settings['show_date']);
        
        ob_start();
        ?>
        <article class="pgs-post-card pgs-layout-default type-<?php echo esc_attr($post_type); ?>">
            <a href="<?php echo esc_url($permalink); ?>" class="pgs-post-link">
                <?php if ($thumbnail): ?>
                <div class="pgs-post-thumbnail">
                    <?php echo $thumbnail; ?>
                </div>
                <?php endif; ?>
                <div class="pgs-post-content">
                    <h3 class="pgs-post-title"><?php echo esc_html($title); ?></h3>
                    <?php if ($show_excerpt): ?>
                    <p class="pgs-post-excerpt"><?php echo esc_html($excerpt); ?></p>
                    <?php endif; ?>
                    <?php if ($show_author || $show_date): ?>
                    <div class="pgs-post-meta">
                        <?php if ($show_author): ?>
                        <span class="pgs-post-author">By <?php echo esc_html($author); ?></span>
                        <?php endif; ?>
                        <?php if ($show_date): ?>
                        <span class="pgs-post-date"><?php echo esc_html($date); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
        </article>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Minimal layout
     */
    private function render_minimal_layout($widget_settings = array()) {
        $post_id = get_the_ID();
        $title = get_the_title();
        $date = get_the_date();
        $permalink = get_permalink();
        $post_type = get_post_type($post_id);
        
        ob_start();
        ?>
        <article class="pgs-post-card pgs-layout-minimal type-<?php echo esc_attr($post_type); ?>">
            <a href="<?php echo esc_url($permalink); ?>" class="pgs-post-link">
                <div class="pgs-post-content">
                    <h3 class="pgs-post-title"><?php echo esc_html($title); ?></h3>
                    <span class="pgs-post-date"><?php echo esc_html($date); ?></span>
                </div>
            </a>
        </article>
        <?php
        return ob_get_clean();
    }
    
    /**
     * List layout
     */
    private function render_list_layout($widget_settings = array()) {
        $post_id = get_the_ID();
        $title = get_the_title();
        $excerpt = get_the_excerpt();
        $author = get_the_author();
        $date = get_the_date();
        $thumbnail = get_the_post_thumbnail($post_id, 'thumbnail');
        $permalink = get_permalink();
        $post_type = get_post_type($post_id);
        
        $show_excerpt = !empty($widget_settings['show_excerpt']);
        $show_author = !empty($widget_settings['show_author']);
        $show_date = !empty($widget_settings['show_date']);
        
        ob_start();
        ?>
        <article class="pgs-post-card pgs-layout-list type-<?php echo esc_attr($post_type); ?>">
            <a href="<?php echo esc_url($permalink); ?>" class="pgs-post-link">
                <?php if ($thumbnail): ?>
                <div class="pgs-post-thumbnail">
                    <?php echo $thumbnail; ?>
                </div>
                <?php endif; ?>
                <div class="pgs-post-content">
                    <h3 class="pgs-post-title"><?php echo esc_html($title); ?></h3>
                    <?php if ($show_excerpt): ?>
                    <p class="pgs-post-excerpt"><?php echo esc_html($excerpt); ?></p>
                    <?php endif; ?>
                    <?php if ($show_author || $show_date): ?>
                    <div class="pgs-post-meta">
                        <?php if ($show_author): ?>
                        <span class="pgs-post-author">By <?php echo esc_html($author); ?></span>
                        <?php endif; ?>
                        <?php if ($show_date): ?>
                        <span class="pgs-post-date"><?php echo esc_html($date); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
        </article>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Masonry layout
     */
    private function render_masonry_layout($widget_settings = array()) {
        $post_id = get_the_ID();
        $title = get_the_title();
        $excerpt = get_the_excerpt();
        $author = get_the_author();
        $date = get_the_date();
        $thumbnail = get_the_post_thumbnail($post_id, 'large');
        $permalink = get_permalink();
        $post_type = get_post_type($post_id);
        
        $show_excerpt = !empty($widget_settings['show_excerpt']);
        $show_author = !empty($widget_settings['show_author']);
        $show_date = !empty($widget_settings['show_date']);
        
        ob_start();
        ?>
        <article class="pgs-post-card pgs-layout-masonry type-<?php echo esc_attr($post_type); ?>">
            <a href="<?php echo esc_url($permalink); ?>" class="pgs-post-link">
                <?php if ($thumbnail): ?>
                <div class="pgs-post-thumbnail">
                    <?php echo $thumbnail; ?>
                </div>
                <?php endif; ?>
                <div class="pgs-post-content">
                    <h3 class="pgs-post-title"><?php echo esc_html($title); ?></h3>
                    <?php if ($show_excerpt): ?>
                    <p class="pgs-post-excerpt"><?php echo esc_html($excerpt); ?></p>
                    <?php endif; ?>
                    <?php if ($show_author || $show_date): ?>
                    <div class="pgs-post-meta">
                        <?php if ($show_author): ?>
                        <span class="pgs-post-author">By <?php echo esc_html($author); ?></span>
                        <?php endif; ?>
                        <?php if ($show_date): ?>
                        <span class="pgs-post-date"><?php echo esc_html($date); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
        </article>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Custom post type layout with ACF support
     */
    private function render_custom_post_type_layout($widget_settings = array()) {
        $post_id = get_the_ID();
        $post_type = get_post_type($post_id);
        $title = get_the_title();
        $date = get_the_date();
        $permalink = get_permalink();
        
        ob_start();
        
        if ($post_type === 'locations') {
            $this->render_locations_layout($post_id, $title, $date, $permalink);
        } elseif ($post_type === 'post') {
            $this->render_custom_post_layout($post_id, $title, $date, $permalink);
        } else {
            // Fallback to default for other post types
            echo $this->render_default_layout($widget_settings);
            return ob_get_clean();
        }
        
        return ob_get_clean();
    }
    
    /**
     * Locations post type layout
     */
    private function render_locations_layout($post_id, $title, $date, $permalink) {
        $acf_tags = get_field('tags', $post_id);
        $acf_channel_logo = get_field('channel_logo', $post_id);
        $thumbnail = get_the_post_thumbnail($post_id, 'medium');
        ?>
        <article class="pgs-post-card pgs-layout-custom type-locations">
            <a href="<?php echo esc_url($permalink); ?>" class="wctl-custom-post-card">
                <div class="wctl-card-top">
                    <div class="wctl-card-meta">
                        <span class="wctl-card-date"><?php echo esc_html($date); ?></span>
                        <?php if (!empty($acf_tags)): ?>
                        <span class="wctl-card-tag"><?php echo esc_html($acf_tags); ?></span>
                        <?php endif; ?>
                    </div>
                    <h3 class="wctl-card-title"><?php echo esc_html($title); ?></h3>
                </div>
                
                <div class="wctl-card-bottom">
                    <div class="wctl-card-image">
                        <?php if ($thumbnail): ?>
                        <?php echo $thumbnail; ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($acf_channel_logo)): ?>
                        <div class="wctl-card-channel-logo">
                            <?php echo $this->render_acf_image($acf_channel_logo, 'Channel Logo'); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="wctl-card-hover-arrow">➝</div>
                    </div>
                </div>
            </a>
        </article>
        <?php
    }
    
    /**
     * Custom post layout with video support
     */
    private function render_custom_post_layout($post_id, $title, $date, $permalink) {
        $acf_icon = get_field('corner_icon', $post_id);
        $acf_video_url = get_field('video_url', $post_id);
        
        $bg_image_url = '';
        if (has_post_thumbnail($post_id)) {
            $bg_image = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), 'full');
            $bg_image_url = $bg_image ? $bg_image[0] : '';
        }
        ?>
        <article class="pgs-post-card pgs-layout-custom type-post">
            <div class="custom-post-card" style="background-image: url('<?php echo esc_url($bg_image_url); ?>');">
                <?php if (!empty($acf_icon)): ?>
                <div class="corner-icon">
                    <?php echo $this->render_acf_image($acf_icon, 'Corner Icon', 'width: 100px; height: 100px;'); ?>
                </div>
                <?php endif; ?>
                
                <h3 class="custom-post-title">
                    <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
                </h3>
                
                <?php if (!empty($acf_video_url)): ?>
                <button class="video-popup-btn" data-fancybox data-src="<?php echo esc_url($acf_video_url); ?>">
                    Watch Video
                </button>
                <?php endif; ?>
            </div>
        </article>
        <?php
    }
    
    /**
     * Helper method to render ACF images safely
     */
    private function render_acf_image($acf_field, $alt_text = '', $style = '') {
        if (empty($acf_field)) {
            return '';
        }
        
        $image_url = '';
        $image_alt = $alt_text;
        
        if (is_array($acf_field)) {
            $image_url = isset($acf_field['url']) ? $acf_field['url'] : '';
            $image_alt = isset($acf_field['alt']) ? $acf_field['alt'] : $alt_text;
        } elseif (is_string($acf_field)) {
            $image_url = $acf_field;
        } elseif (is_int($acf_field)) {
            $image_url = wp_get_attachment_url($acf_field);
            $image_alt = get_post_meta($acf_field, '_wp_attachment_image_alt', true) ?: $alt_text;
        }
        
        if ($image_url) {
            $style_attr = $style ? ' style="' . esc_attr($style) . '"' : '';
            return '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_alt) . '"' . $style_attr . '>';
        }
        
        return '';
    }
    
    /**
     * Get container classes based on layout
     */
    public static function get_container_classes($layout) {
        $base_classes = 'pgs-posts-container';
        
        switch ($layout) {
            case 'minimal':
                return $base_classes . ' pgs-layout-minimal';
            case 'list':
                return $base_classes . ' pgs-layout-list';
            case 'masonry':
                return $base_classes . ' pgs-layout-masonry';
            case 'custom':
                return $base_classes . ' pgs-layout-custom';
            default:
                return $base_classes . ' pgs-layout-default';
        }
    }
    
    /**
     * Get grid CSS variables based on layout
     */
    public static function get_layout_css_vars($layout, $widget_settings = array()) {
        $vars = array();
        
        switch ($layout) {
            case 'minimal':
                $vars['--grid-columns'] = 'repeat(auto-fit, minmax(200px, 1fr))';
                $vars['--grid-gap'] = '12px';
                break;
            case 'list':
                $vars['--grid-columns'] = '1fr';
                $vars['--grid-gap'] = '16px';
                break;
            case 'masonry':
                $vars['--grid-columns'] = 'repeat(auto-fit, minmax(280px, 1fr))';
                $vars['--grid-gap'] = '20px';
                break;
            case 'custom':
                $vars['--grid-columns'] = 'repeat(auto-fit, minmax(320px, 1fr))';
                $vars['--grid-gap'] = '24px';
                break;
            default:
                $vars['--grid-columns'] = 'repeat(auto-fit, minmax(300px, 1fr))';
                $vars['--grid-gap'] = '20px';
                break;
        }
        
        return $vars;
    }
}