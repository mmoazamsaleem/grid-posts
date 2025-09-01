<?php
/**
 * Enhanced Search Filter Widget Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class PGS_Search_Filter_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'pgs_search_filter',
            __('Posts Search Filter', 'posts-grid-search'),
            array(
                'description' => __('Advanced search filter for Posts Grid widget with post type filtering.', 'posts-grid-search'),
                'classname' => 'pgs-search-filter-widget'
            )
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $placeholder = !empty($instance['placeholder']) ? $instance['placeholder'] : __('Search posts...', 'posts-grid-search');
        $target_widget = !empty($instance['target_widget']) ? $instance['target_widget'] : '';
        $show_post_type_filter = !empty($instance['show_post_type_filter']);
        $search_bg = !empty($instance['search_bg']) ? $instance['search_bg'] : '#1a202c';
        $search_text_color = !empty($instance['search_text_color']) ? $instance['search_text_color'] : '#ffffff';
        $search_border_color = !empty($instance['search_border_color']) ? $instance['search_border_color'] : '#14b8a6';
        $button_bg = !empty($instance['button_bg']) ? $instance['button_bg'] : '#14b8a6';
        $button_text_color = !empty($instance['button_text_color']) ? $instance['button_text_color'] : '#ffffff';
        
        $post_types = PostsGridSearchPlugin::get_post_types();
        ?>
        <div class="pgs-search-filter" style="--search-bg: <?php echo esc_attr($search_bg); ?>; --search-text: <?php echo esc_attr($search_text_color); ?>; --search-border: <?php echo esc_attr($search_border_color); ?>; --button-bg: <?php echo esc_attr($button_bg); ?>; --button-text: <?php echo esc_attr($button_text_color); ?>;">
            <div class="pgs-search-container">
                <div class="pgs-search-input-wrapper">
                    <svg class="pgs-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input 
                        type="text" 
                        id="pgs-search-input" 
                        class="pgs-search-input" 
                        placeholder="<?php echo esc_attr($placeholder); ?>"
                        data-target-widget="<?php echo esc_attr($target_widget); ?>"
                    >
                    <button type="button" class="pgs-search-clear" id="pgs-search-clear" style="display: none;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                
                <?php if ($show_post_type_filter && count($post_types) > 1): ?>
                <div class="pgs-post-type-filter">
                    <select id="pgs-post-type-filter" class="pgs-post-type-select">
                        <option value=""><?php _e('All Post Types', 'posts-grid-search'); ?></option>
                        <?php foreach ($post_types as $type => $label): ?>
                            <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="pgs-search-results-info" id="pgs-search-results-info" style="display: none;">
                <span id="pgs-results-count"></span>
                <button type="button" class="pgs-clear-search" id="pgs-clear-search">
                    <?php _e('Clear search', 'posts-grid-search'); ?>
                </button>
            </div>
        </div>
        <?php
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $placeholder = !empty($instance['placeholder']) ? $instance['placeholder'] : __('Search posts...', 'posts-grid-search');
        $target_widget = !empty($instance['target_widget']) ? $instance['target_widget'] : '';
        $show_post_type_filter = !empty($instance['show_post_type_filter']);
        $search_bg = !empty($instance['search_bg']) ? $instance['search_bg'] : '#1a202c';
        $search_text_color = !empty($instance['search_text_color']) ? $instance['search_text_color'] : '#ffffff';
        $search_border_color = !empty($instance['search_border_color']) ? $instance['search_border_color'] : '#14b8a6';
        $button_bg = !empty($instance['button_bg']) ? $instance['button_bg'] : '#14b8a6';
        $button_text_color = !empty($instance['button_text_color']) ? $instance['button_text_color'] : '#ffffff';
        ?>
        <div class="pgs-widget-form">
            <p>
                <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'posts-grid-search'); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
            </p>
            
            <h4><?php _e('Filter Settings', 'posts-grid-search'); ?></h4>
            
            <p>
                <label for="<?php echo $this->get_field_id('placeholder'); ?>"><?php _e('Search placeholder:', 'posts-grid-search'); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id('placeholder'); ?>" name="<?php echo $this->get_field_name('placeholder'); ?>" type="text" value="<?php echo esc_attr($placeholder); ?>">
            </p>
            
            <p>
                <input class="checkbox" type="checkbox" <?php checked($show_post_type_filter, true); ?> id="<?php echo $this->get_field_id('show_post_type_filter'); ?>" name="<?php echo $this->get_field_name('show_post_type_filter'); ?>">
                <label for="<?php echo $this->get_field_id('show_post_type_filter'); ?>"><?php _e('Show post type filter', 'posts-grid-search'); ?></label>
            </p>
            
            <p>
                <label for="<?php echo $this->get_field_id('target_widget'); ?>"><?php _e('Target Posts Grid Widget ID:', 'posts-grid-search'); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id('target_widget'); ?>" name="<?php echo $this->get_field_name('target_widget'); ?>" type="text" value="<?php echo esc_attr($target_widget); ?>">
                <small><?php _e('Leave empty to target all Posts Grid widgets on the page.', 'posts-grid-search'); ?></small>
            </p>
            
            <h4><?php _e('Search Input Styling', 'posts-grid-search'); ?></h4>
            
            <p>
                <label for="<?php echo $this->get_field_id('search_bg'); ?>"><?php _e('Background color:', 'posts-grid-search'); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id('search_bg'); ?>" name="<?php echo $this->get_field_name('search_bg'); ?>" type="color" value="<?php echo esc_attr($search_bg); ?>">
            </p>
            
            <p>
                <label for="<?php echo $this->get_field_id('search_text_color'); ?>"><?php _e('Text color:', 'posts-grid-search'); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id('search_text_color'); ?>" name="<?php echo $this->get_field_name('search_text_color'); ?>" type="color" value="<?php echo esc_attr($search_text_color); ?>">
            </p>
            
            <p>
                <label for="<?php echo $this->get_field_id('search_border_color'); ?>"><?php _e('Border color:', 'posts-grid-search'); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id('search_border_color'); ?>" name="<?php echo $this->get_field_name('search_border_color'); ?>" type="color" value="<?php echo esc_attr($search_border_color); ?>">
            </p>
            
            <p>
                <label for="<?php echo $this->get_field_id('button_bg'); ?>"><?php _e('Button background:', 'posts-grid-search'); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id('button_bg'); ?>" name="<?php echo $this->get_field_name('button_bg'); ?>" type="color" value="<?php echo esc_attr($button_bg); ?>">
            </p>
            
            <p>
                <label for="<?php echo $this->get_field_id('button_text_color'); ?>"><?php _e('Button text color:', 'posts-grid-search'); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id('button_text_color'); ?>" name="<?php echo $this->get_field_name('button_text_color'); ?>" type="color" value="<?php echo esc_attr($button_text_color); ?>">
            </p>
        </div>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['placeholder'] = (!empty($new_instance['placeholder'])) ? sanitize_text_field($new_instance['placeholder']) : '';
        $instance['target_widget'] = (!empty($new_instance['target_widget'])) ? sanitize_text_field($new_instance['target_widget']) : '';
        $instance['show_post_type_filter'] = !empty($new_instance['show_post_type_filter']);
        $instance['search_bg'] = (!empty($new_instance['search_bg'])) ? sanitize_hex_color($new_instance['search_bg']) : '#1a202c';
        $instance['search_text_color'] = (!empty($new_instance['search_text_color'])) ? sanitize_hex_color($new_instance['search_text_color']) : '#ffffff';
        $instance['search_border_color'] = (!empty($new_instance['search_border_color'])) ? sanitize_hex_color($new_instance['search_border_color']) : '#14b8a6';
        $instance['button_bg'] = (!empty($new_instance['button_bg'])) ? sanitize_hex_color($new_instance['button_bg']) : '#14b8a6';
        $instance['button_text_color'] = (!empty($new_instance['button_text_color'])) ? sanitize_hex_color($new_instance['button_text_color']) : '#ffffff';
        
        return $instance;
    }
}