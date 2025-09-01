<?php
/**
 * Enhanced Posts Grid Widget Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class PGS_Posts_Grid_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'pgs_posts_grid',
            __('Posts Grid', 'posts-grid-search'),
            array(
                'description' => __('Display posts using saved templates with post type filtering and pagination.', 'posts-grid-search'),
                'classname' => 'pgs-posts-grid-widget'
            )
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $template_id = !empty($instance['template_id']) ? intval($instance['template_id']) : 0;
        $post_type = !empty($instance['post_type']) ? $instance['post_type'] : 'post';
        $posts_per_page = !empty($instance['posts_per_page']) ? intval($instance['posts_per_page']) : 6;
        $show_pagination = !empty($instance['show_pagination']);
        $pagination_style = !empty($instance['pagination_style']) ? $instance['pagination_style'] : 'numbers';
        
        // Get current page
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        
        $query_args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => $posts_per_page,
            'paged' => $paged
        );
        
        $posts_query = new WP_Query($query_args);
        
        // Generate unique widget ID for targeting
        $widget_id = 'pgs-widget-' . $this->id;
        
        echo '<div class="pgs-posts-grid" id="' . esc_attr($widget_id) . '" data-template-id="' . esc_attr($template_id) . '" data-post-type="' . esc_attr($post_type) . '" data-posts-per-page="' . esc_attr($posts_per_page) . '" data-widget-settings="' . esc_attr(json_encode($instance)) . '">';
        echo '<div class="pgs-posts-container">';
        
        if ($posts_query->have_posts()) {
            while ($posts_query->have_posts()) {
                $posts_query->the_post();
                $this->render_post($template_id, $instance);
            }
        } else {
            echo '<div class="pgs-no-posts">' . __('No posts found.', 'posts-grid-search') . '</div>';
        }
        
        echo '</div>'; // .pgs-posts-container
        
        // Pagination
        if ($show_pagination && $posts_query->max_num_pages > 1) {
            $this->render_pagination($posts_query, $pagination_style, $instance);
        }
        
        echo '</div>'; // .pgs-posts-grid
        
        wp_reset_postdata();
        echo $args['after_widget'];
    }
    
    private function render_post($template_id, $instance) {
        if ($template_id && $template_id > 0) {
            // Use saved template
            $template_content = get_post_field('post_content', $template_id);
            if ($template_content) {
                echo $this->replace_template_placeholders($template_content);
                return;
            }
        }
        
        // Fallback to default card template
        $this->render_default_template($instance);
    }
    
    private function replace_template_placeholders($content) {
        $post_id = get_the_ID();
        $title = get_the_title();
        $excerpt = get_the_excerpt();
        $author = get_the_author();
        $date = get_the_date();
        $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium');
        $permalink = get_permalink();
        
        $replacements = array(
            '{{post_title}}' => esc_html($title),
            '{{post_excerpt}}' => esc_html($excerpt),
            '{{post_author}}' => esc_html($author),
            '{{post_date}}' => esc_html($date),
            '{{post_thumbnail}}' => $thumbnail_url ? esc_url($thumbnail_url) : '',
            '{{post_link}}' => esc_url($permalink),
            '{{post_id}}' => $post_id
        );
        
        foreach ($replacements as $placeholder => $value) {
            $content = str_replace($placeholder, $value, $content);
        }
        
        return $content;
    }
    
    private function render_default_template($instance) {
    $post_id   = get_the_ID();
    $title     = get_the_title();
    $excerpt   = get_the_excerpt();
    $author    = get_the_author();
    $date      = get_the_date();
    $thumbnail = get_the_post_thumbnail($post_id, 'medium');
    $permalink = get_permalink();
    $post_type = get_post_type($post_id);

    $show_excerpt = !empty($instance['show_excerpt']) ? true : false;
    $show_author  = !empty($instance['show_author']) ? true : false;
    $show_date    = !empty($instance['show_date']) ? true : false;

    echo '<div class="pgs-post-card type-' . esc_attr($post_type) . '">';

    if ($post_type === 'locations') {
        // Get the custom ACF fields
        $acf_tags         = get_field('tags', $post_id);          // ACF 'tags' field
        $acf_channel_logo = get_field('channel_logo', $post_id); // ACF 'channel_logo' image field

        echo '<a href="' . esc_url($permalink) . '" class="wctl-custom-post-card">';

        // Top section with date and tag
        echo '<div class="wctl-card-top">';
        echo '<div class="wctl-card-meta">';
        echo '<span class="wctl-card-date">' . esc_html($date) . '</span>';
        if (!empty($acf_tags)) {
            echo '<span class="wctl-card-tag">' . esc_html($acf_tags) . '</span>';
        }
        echo '</div>';

        // Title
        echo '<h3 class="wctl-card-title">';
        echo  esc_html($title);
        echo '</h3>';
        echo '</div>';

        // Bottom section
        echo '<div class="wctl-card-bottom">';
        echo '<div class="wctl-card-image">';

        // Thumbnail
        if ($thumbnail) {
            echo $thumbnail;
        }

        // Channel Logo (handle all ACF return types)
        if (!empty($acf_channel_logo)) {
            $channel_logo_url = '';

            if (is_array($acf_channel_logo) && isset($acf_channel_logo['url'])) {
                $channel_logo_url = $acf_channel_logo['url'];
            } elseif (is_string($acf_channel_logo)) {
                $channel_logo_url = $acf_channel_logo;
            } elseif (is_int($acf_channel_logo)) {
                $channel_logo_url = wp_get_attachment_url($acf_channel_logo);
            }

            if ($channel_logo_url) {
                echo '<div class="wctl-card-channel-logo">';
                echo '<img src="' . esc_url($channel_logo_url) . '" alt="Channel Logo">';
                echo '</div>';
            }
        }

        // Hover Arrow
        echo '<div class="wctl-card-hover-arrow">➝</div>';

        echo '</div>'; // wctl-card-image
        echo '</div>'; // wctl-card-bottom

        echo '</a>';

    } elseif ($post_type === 'post') {
        $acf_icon      = get_field('corner_icon', $post_id);  // Image
        $acf_video_url = get_field('video_url', $post_id);    // URL

        // Background image
        $bg_image_url = '';
        if (has_post_thumbnail($post_id)) {
            $bg_image = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), 'full');
            $bg_image_url = $bg_image ? $bg_image[0] : '';
        }

        echo '<div class="custom-post-card" style="background-image: url(\'' . esc_url($bg_image_url) . '\');">';

        // Corner Icon (safe handling)
        if (!empty($acf_icon)) {
            $icon_url = '';
            if (is_array($acf_icon) && isset($acf_icon['url'])) {
                $icon_url = $acf_icon['url'];
            } elseif (is_string($acf_icon)) {
                $icon_url = $acf_icon;
            } elseif (is_int($acf_icon)) {
                $icon_url = wp_get_attachment_url($acf_icon);
            }

            if ($icon_url) {
                echo '<img style="width: 100px; height: 100px;" src="' . esc_url($icon_url) . '" alt="Corner Icon">';
            }
        }

        // Title
        echo '<h3 class="custom-post-title">' . esc_html($title) . '</h3>';

        // Video button
        if (!empty($acf_video_url)) {
            echo '<a class="video-popup-btn"  data-fancybox href="' . esc_url($acf_video_url) . '">Watch Video</a>';
        }

        echo '</div>';

        // Video Popup HTML
        ?>
        <div id="video-popup" style="display:none;">
            <div class="video-container">
                <span id="close-popup">×</span>
                <iframe id="popup-video" width="560" height="315" src="" frameborder="0" allowfullscreen></iframe>
            </div>
        </div>
        <?php

    } else {
        // Default layout
        echo '<a href="' . esc_url($permalink) . '" class="pgs-post-link">';
        if ($thumbnail) {
            echo '<div class="pgs-post-thumbnail">' . $thumbnail . '</div>';
        }
        echo '<div class="pgs-post-content">';
        echo '<h3 class="pgs-post-title">' . esc_html($title) . '</h3>';
        if ($show_excerpt) {
            echo '<p class="pgs-post-excerpt">' . esc_html($excerpt) . '</p>';
        }
        if ($show_author || $show_date) {
            echo '<div class="pgs-post-meta">';
            if ($show_author) {
                echo '<span class="pgs-post-author">By ' . esc_html($author) . '</span>';
            }
            if ($show_date) {
                echo '<span class="pgs-post-date">' . esc_html($date) . '</span>';
            }
            echo '</div>';
        }
        echo '</div>';
        echo '</a>';
    }

    echo '</div>';
}



    
    private function render_pagination($query, $style, $instance) {
        $current_page = max(1, get_query_var('paged'));
        $total_pages = $query->max_num_pages;
        
        $pagination_bg = !empty($instance['pagination_bg']) ? $instance['pagination_bg'] : '#1a202c';
        $pagination_active_color = !empty($instance['pagination_active_color']) ? $instance['pagination_active_color'] : '#14b8a6';
        $pagination_text_color = !empty($instance['pagination_text_color']) ? $instance['pagination_text_color'] : '#ffffff';
        $prev_icon = !empty($instance['prev_icon']) ? $instance['prev_icon'] : '←';
        $next_icon = !empty($instance['next_icon']) ? $instance['next_icon'] : '→';
        
        echo '<div class="pgs-pagination" style="--pagination-bg: ' . esc_attr($pagination_bg) . '; --pagination-active: ' . esc_attr($pagination_active_color) . '; --pagination-text: ' . esc_attr($pagination_text_color) . ';">';
        
        if ($style === 'numbers') {
            $links = paginate_links(array(
                'base' => get_pagenum_link(1) . '%_%',
                'format' => 'page/%#%/',
                'current' => $current_page,
                'total' => $total_pages,
                'mid_size' => 1,
                'end_size' => 1,
                'prev_text' => $prev_icon,
                'next_text' => $next_icon,
                'type' => 'array',
            ));
            
            if (!empty($links)) {
                foreach ($links as $link) {
                    $link = str_replace('page-numbers', 'pgs-pagination-btn', $link);
                    $link = str_replace('current', 'pgs-pagination-current', $link);
                    echo $link;
                }
            }
        } elseif ($style === 'simple') {
            echo '<div class="pgs-pagination-simple">';
            if ($current_page > 1) {
                echo '<a href="' . esc_url(get_pagenum_link($current_page - 1)) . '" class="pgs-pagination-btn">' . __('Previous', 'posts-grid-search') . '</a>';
            }
            echo '<span class="pgs-pagination-info">' . sprintf(__('Page %d of %d', 'posts-grid-search'), $current_page, $total_pages) . '</span>';
            if ($current_page < $total_pages) {
                echo '<a href="' . esc_url(get_pagenum_link($current_page + 1)) . '" class="pgs-pagination-btn">' . __('Next', 'posts-grid-search') . '</a>';
            }
            echo '</div>';
        } elseif ($style === 'arrows') {
            if ($current_page > 1) {
                echo '<a href="' . esc_url(get_pagenum_link($current_page - 1)) . '" class="pgs-pagination-btn pgs-pagination-arrow">' . esc_html($prev_icon) . '</a>';
            }
            if ($current_page < $total_pages) {
                echo '<a href="' . esc_url(get_pagenum_link($current_page + 1)) . '" class="pgs-pagination-btn pgs-pagination-arrow">' . esc_html($next_icon) . '</a>';
            }
        }
        
        echo '</div>';
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $template_id = !empty($instance['template_id']) ? $instance['template_id'] : '';
        $post_type = !empty($instance['post_type']) ? $instance['post_type'] : 'post';
        $posts_per_page = !empty($instance['posts_per_page']) ? $instance['posts_per_page'] : '6';
        $show_pagination = !empty($instance['show_pagination']);
        $pagination_style = !empty($instance['pagination_style']) ? $instance['pagination_style'] : 'numbers';
        $prev_icon = !empty($instance['prev_icon']) ? $instance['prev_icon'] : '←';
        $next_icon = !empty($instance['next_icon']) ? $instance['next_icon'] : '→';
        $pagination_bg = !empty($instance['pagination_bg']) ? $instance['pagination_bg'] : '#1a202c';
        $pagination_active_color = !empty($instance['pagination_active_color']) ? $instance['pagination_active_color'] : '#14b8a6';
        $pagination_text_color = !empty($instance['pagination_text_color']) ? $instance['pagination_text_color'] : '#ffffff';
        $show_excerpt = !empty($instance['show_excerpt']);
        $show_author = !empty($instance['show_author']);
        $show_date = !empty($instance['show_date']);
        
        $saved_templates = PostsGridSearchPlugin::get_saved_templates();
        $post_types = PostsGridSearchPlugin::get_post_types();
        ?>
        <div class="pgs-widget-form">
            <p>
                <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'posts-grid-search'); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
            </p>
            
            <h4><?php _e('Template & Content Settings', 'posts-grid-search'); ?></h4>
            
            <p>
                <label for="<?php echo $this->get_field_id('template_id'); ?>"><?php _e('Saved Template:', 'posts-grid-search'); ?></label>
                <select class="widefat" id="<?php echo $this->get_field_id('template_id'); ?>" name="<?php echo $this->get_field_name('template_id'); ?>">
                    <option value=""><?php _e('Default Template', 'posts-grid-search'); ?></option>
                    <?php foreach ($saved_templates as $id => $name): ?>
                        <option value="<?php echo esc_attr($id); ?>" <?php selected($template_id, $id); ?>><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                </select>
                <small><?php _e('Select a saved template from Elementor or other page builders.', 'posts-grid-search'); ?></small>
            </p>
            
            <p>
                <label for="<?php echo $this->get_field_id('post_type'); ?>"><?php _e('Post Type:', 'posts-grid-search'); ?></label>
                <select class="widefat" id="<?php echo $this->get_field_id('post_type'); ?>" name="<?php echo $this->get_field_name('post_type'); ?>">
                    <?php foreach ($post_types as $type => $label): ?>
                        <option value="<?php echo esc_attr($type); ?>" <?php selected($post_type, $type); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            
            <p>
                <label for="<?php echo $this->get_field_id('posts_per_page'); ?>"><?php _e('Posts per page:', 'posts-grid-search'); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id('posts_per_page'); ?>" name="<?php echo $this->get_field_name('posts_per_page'); ?>" type="number" value="<?php echo esc_attr($posts_per_page); ?>" min="1" max="50">
            </p>
            
            <div class="pgs-default-template-settings" style="<?php echo $template_id ? 'display: none;' : ''; ?>">
                <h4><?php _e('Default Template Content', 'posts-grid-search'); ?></h4>
                <small><?php _e('These settings only apply when using the default template.', 'posts-grid-search'); ?></small>
                
                <p>
                    <input class="checkbox" type="checkbox" <?php checked($show_excerpt, true); ?> id="<?php echo $this->get_field_id('show_excerpt'); ?>" name="<?php echo $this->get_field_name('show_excerpt'); ?>">
                    <label for="<?php echo $this->get_field_id('show_excerpt'); ?>"><?php _e('Show excerpt', 'posts-grid-search'); ?></label>
                </p>
                
                <p>
                    <input class="checkbox" type="checkbox" <?php checked($show_author, true); ?> id="<?php echo $this->get_field_id('show_author'); ?>" name="<?php echo $this->get_field_name('show_author'); ?>">
                    <label for="<?php echo $this->get_field_id('show_author'); ?>"><?php _e('Show author', 'posts-grid-search'); ?></label>
                </p>
                
                <p>
                    <input class="checkbox" type="checkbox" <?php checked($show_date, true); ?> id="<?php echo $this->get_field_id('show_date'); ?>" name="<?php echo $this->get_field_name('show_date'); ?>">
                    <label for="<?php echo $this->get_field_id('show_date'); ?>"><?php _e('Show date', 'posts-grid-search'); ?></label>
                </p>
            </div>
            
            <h4><?php _e('Pagination Settings', 'posts-grid-search'); ?></h4>
            
            <p>
                <input class="checkbox" type="checkbox" <?php checked($show_pagination, true); ?> id="<?php echo $this->get_field_id('show_pagination'); ?>" name="<?php echo $this->get_field_name('show_pagination'); ?>">
                <label for="<?php echo $this->get_field_id('show_pagination'); ?>"><?php _e('Show pagination', 'posts-grid-search'); ?></label>
            </p>
            
            <div class="pgs-pagination-settings" style="<?php echo !$show_pagination ? 'display: none;' : ''; ?>">
                <p>
                    <label for="<?php echo $this->get_field_id('pagination_style'); ?>"><?php _e('Pagination style:', 'posts-grid-search'); ?></label>
                    <select class="widefat" id="<?php echo $this->get_field_id('pagination_style'); ?>" name="<?php echo $this->get_field_name('pagination_style'); ?>">
                        <option value="numbers" <?php selected($pagination_style, 'numbers'); ?>><?php _e('Numbers', 'posts-grid-search'); ?></option>
                        <option value="simple" <?php selected($pagination_style, 'simple'); ?>><?php _e('Simple', 'posts-grid-search'); ?></option>
                        <option value="arrows" <?php selected($pagination_style, 'arrows'); ?>><?php _e('Arrows only', 'posts-grid-search'); ?></option>
                    </select>
                </p>
                
                <p>
                    <label for="<?php echo $this->get_field_id('prev_icon'); ?>"><?php _e('Previous icon:', 'posts-grid-search'); ?></label>
                    <input class="widefat" id="<?php echo $this->get_field_id('prev_icon'); ?>" name="<?php echo $this->get_field_name('prev_icon'); ?>" type="text" value="<?php echo esc_attr($prev_icon); ?>">
                </p>
                
                <p>
                    <label for="<?php echo $this->get_field_id('next_icon'); ?>"><?php _e('Next icon:', 'posts-grid-search'); ?></label>
                    <input class="widefat" id="<?php echo $this->get_field_id('next_icon'); ?>" name="<?php echo $this->get_field_name('next_icon'); ?>" type="text" value="<?php echo esc_attr($next_icon); ?>">
                </p>
                
                <h4><?php _e('Pagination Colors', 'posts-grid-search'); ?></h4>
                
                <p>
                    <label for="<?php echo $this->get_field_id('pagination_bg'); ?>"><?php _e('Background color:', 'posts-grid-search'); ?></label>
                    <input class="widefat" id="<?php echo $this->get_field_id('pagination_bg'); ?>" name="<?php echo $this->get_field_name('pagination_bg'); ?>" type="color" value="<?php echo esc_attr($pagination_bg); ?>">
                </p>
                
                <p>
                    <label for="<?php echo $this->get_field_id('pagination_active_color'); ?>"><?php _e('Active color:', 'posts-grid-search'); ?></label>
                    <input class="widefat" id="<?php echo $this->get_field_id('pagination_active_color'); ?>" name="<?php echo $this->get_field_name('pagination_active_color'); ?>" type="color" value="<?php echo esc_attr($pagination_active_color); ?>">
                </p>
                
                <p>
                    <label for="<?php echo $this->get_field_id('pagination_text_color'); ?>"><?php _e('Text color:', 'posts-grid-search'); ?></label>
                    <input class="widefat" id="<?php echo $this->get_field_id('pagination_text_color'); ?>" name="<?php echo $this->get_field_name('pagination_text_color'); ?>" type="color" value="<?php echo esc_attr($pagination_text_color); ?>">
                </p>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Toggle default template settings visibility
            $('#<?php echo $this->get_field_id('template_id'); ?>').on('change', function() {
                var templateId = $(this).val();
                var $defaultSettings = $(this).closest('.pgs-widget-form').find('.pgs-default-template-settings');
                
                if (templateId) {
                    $defaultSettings.hide();
                } else {
                    $defaultSettings.show();
                }
            });
            
            // Toggle pagination settings visibility
            $('#<?php echo $this->get_field_id('show_pagination'); ?>').on('change', function() {
                var $paginationSettings = $(this).closest('.pgs-widget-form').find('.pgs-pagination-settings');
                
                if ($(this).is(':checked')) {
                    $paginationSettings.show();
                } else {
                    $paginationSettings.hide();
                }
            });
        });
        </script>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['template_id'] = (!empty($new_instance['template_id'])) ? intval($new_instance['template_id']) : 0;
        $instance['post_type'] = (!empty($new_instance['post_type'])) ? sanitize_text_field($new_instance['post_type']) : 'post';
        $instance['posts_per_page'] = (!empty($new_instance['posts_per_page'])) ? intval($new_instance['posts_per_page']) : 6;
        $instance['show_pagination'] = !empty($new_instance['show_pagination']);
        $instance['pagination_style'] = (!empty($new_instance['pagination_style'])) ? sanitize_text_field($new_instance['pagination_style']) : 'numbers';
        $instance['prev_icon'] = (!empty($new_instance['prev_icon'])) ? sanitize_text_field($new_instance['prev_icon']) : '←';
        $instance['next_icon'] = (!empty($new_instance['next_icon'])) ? sanitize_text_field($new_instance['next_icon']) : '→';
        $instance['pagination_bg'] = (!empty($new_instance['pagination_bg'])) ? sanitize_hex_color($new_instance['pagination_bg']) : '#1a202c';
        $instance['pagination_active_color'] = (!empty($new_instance['pagination_active_color'])) ? sanitize_hex_color($new_instance['pagination_active_color']) : '#14b8a6';
        $instance['pagination_text_color'] = (!empty($new_instance['pagination_text_color'])) ? sanitize_hex_color($new_instance['pagination_text_color']) : '#ffffff';
        $instance['show_excerpt'] = !empty($new_instance['show_excerpt']);
        $instance['show_author'] = !empty($new_instance['show_author']);
        $instance['show_date'] = !empty($new_instance['show_date']);
        
        return $instance;
    }
}