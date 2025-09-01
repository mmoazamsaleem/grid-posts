jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize enhanced admin functionality
    initEnhancedWidgetSettings();
    
    function initEnhancedWidgetSettings() {
        // Initialize on widget events
        $(document).on('widget-added widget-updated', function() {
            initColorPickers();
            initTemplatePreview();
            initConditionalSettings();
            initFormValidation();
        });
        
        // Initialize on page load
        initColorPickers();
        initTemplatePreview();
        initConditionalSettings();
        initFormValidation();
        initHelpSystem();
        
        // Live preview updates
        $(document).on('change', '.pgs-widget-form input, .pgs-widget-form select', function() {
            updateLivePreview($(this));
            validateForm($(this));
        });
    }
    
    function initColorPickers() {
        $('.pgs-widget-form input[type="color"]').each(function() {
            var $input = $(this);
            
            // Add color preview if not exists
            if ($input.siblings('.pgs-color-preview').length === 0) {
                var currentColor = $input.val();
                var $wrapper = $('<div class="pgs-color-picker-wrapper"></div>');
                var $preview = $('<div class="pgs-color-preview" style="background-color: ' + currentColor + ';"></div>');
                
                $input.wrap($wrapper);
                $input.after($preview);
            }
            
            // Update preview on change
            $input.on('change input', function() {
                var color = $(this).val();
                $(this).siblings('.pgs-color-preview').css('background-color', color);
                
                // Live preview in widget if possible
                updateColorPreview($(this), color);
            });
        });
    }
    
    function updateColorPreview($input, color) {
        var inputId = $input.attr('id');
        
        if (inputId.indexOf('pagination_bg') !== -1) {
            // Update pagination background preview
            console.log('Updating pagination background:', color);
        } else if (inputId.indexOf('search_bg') !== -1) {
            // Update search background preview
            console.log('Updating search background:', color);
        }
    }
    
    function initTemplatePreview() {
        $('.pgs-widget-form select[id*="template_id"]').each(function() {
            var $select = $(this);
            var $widget = $select.closest('.widget');
            
            // Add template preview container
            if ($widget.find('.pgs-template-preview').length === 0) {
                $select.closest('p').after('<div class="pgs-template-preview"></div>');
            }
            
            updateTemplatePreview($select);
            
            $select.on('change', function() {
                updateTemplatePreview($(this));
                toggleDefaultTemplateSettings($(this));
            });
        });
    }
    
    function updateTemplatePreview($select) {
        var templateId = $select.val();
        var $preview = $select.closest('.pgs-widget-form').find('.pgs-template-preview');
        
        if (templateId && templateId !== '') {
            // Show template info
            var templateName = $select.find('option:selected').text();
            var previewHtml = '<div class="pgs-settings-section">' +
                '<h4>Selected Template</h4>' +
                '<p><strong>' + templateName + '</strong></p>' +
                '<small>This template will be used to display posts. Template placeholders like {{post_title}}, {{post_excerpt}}, etc. will be replaced with actual post data.</small>' +
                '</div>';
            $preview.html(previewHtml);
        } else {
            // Show default template preview
            var previewHtml = '<div class="pgs-settings-section">' +
                '<h4>Default Template Preview</h4>' +
                '<div style="border: 1px solid #ddd; border-radius: 8px; padding: 12px; margin: 10px 0; background: white;">' +
                '<div style="background: #f0f0f0; height: 80px; margin-bottom: 10px; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #666; font-size: 12px;">Featured Image</div>' +
                '<div style="font-weight: 600; margin-bottom: 6px; color: #1a202c;">Post Title</div>' +
                '<div style="font-size: 13px; color: #4a5568; margin-bottom: 8px; line-height: 1.4;">Post excerpt will be displayed here...</div>' +
                '<div style="font-size: 11px; color: #718096; display: flex; justify-content: space-between;">' +
                '<span>By Author</span><span>Date</span></div>' +
                '</div>' +
                '</div>';
            $preview.html(previewHtml);
        }
    }
    
    function toggleDefaultTemplateSettings($select) {
        var templateId = $select.val();
        var $defaultSettings = $select.closest('.pgs-widget-form').find('.pgs-default-template-settings');
        
        if (templateId && templateId !== '') {
            $defaultSettings.slideUp(300);
        } else {
            $defaultSettings.slideDown(300);
        }
    }
    
    function initConditionalSettings() {
        // Handle pagination settings visibility
        $('.pgs-widget-form input[id*="show_pagination"]').each(function() {
            var $checkbox = $(this);
            var $paginationSettings = $checkbox.closest('.pgs-widget-form').find('.pgs-pagination-settings');
            
            function togglePaginationSettings() {
                if ($checkbox.is(':checked')) {
                    $paginationSettings.slideDown(300);
                } else {
                    $paginationSettings.slideUp(300);
                }
            }
            
            $checkbox.on('change', togglePaginationSettings);
            togglePaginationSettings(); // Initial state
        });
    }
    
    function initFormValidation() {
        // Validate posts per page
        $('.pgs-widget-form input[id*="posts_per_page"]').on('input', function() {
            var value = parseInt($(this).val(), 10);
            var $input = $(this);
            
            if (value < 1 || value > 50) {
                $input.addClass('pgs-invalid');
                showValidationMessage($input, 'Posts per page must be between 1 and 50.');
            } else {
                $input.removeClass('pgs-invalid');
                hideValidationMessage($input);
            }
        });
        
        // Validate color inputs
        $('.pgs-widget-form input[type="color"]').on('change', function() {
            var $input = $(this);
            var color = $input.val();
            
            if (!/^#[0-9A-F]{6}$/i.test(color)) {
                $input.addClass('pgs-invalid');
                showValidationMessage($input, 'Please enter a valid hex color.');
            } else {
                $input.removeClass('pgs-invalid');
                hideValidationMessage($input);
            }
        });
    }
    
    function showValidationMessage($input, message) {
        var $existing = $input.siblings('.pgs-validation-message');
        if ($existing.length) {
            $existing.text(message);
        } else {
            $input.after('<div class="pgs-validation-message pgs-error">' + message + '</div>');
        }
    }
    
    function hideValidationMessage($input) {
        $input.siblings('.pgs-validation-message').remove();
    }
    
    function validateForm($input) {
        var $form = $input.closest('.pgs-widget-form');
        var isValid = true;
        
        // Check all validation rules
        $form.find('input.pgs-invalid').each(function() {
            isValid = false;
        });
        
        // Enable/disable save button based on validation
        var $saveButton = $form.closest('.widget').find('.widget-control-save');
        if (isValid) {
            $saveButton.removeClass('pgs-disabled');
        } else {
            $saveButton.addClass('pgs-disabled');
        }
    }
    
    function updateLivePreview($input) {
        var inputId = $input.attr('id');
        var value = $input.val();
        
        // Enhanced live preview functionality
        if (inputId && inputId.indexOf('template_id') !== -1) {
            updateTemplatePreview($input);
        }
        
        // Add visual feedback for changes
        $input.addClass('pgs-changed');
        setTimeout(function() {
            $input.removeClass('pgs-changed');
        }, 1000);
    }
    
    function initHelpSystem() {
        // Enhanced help tooltips
        $('.pgs-widget-form label').each(function() {
            var $label = $(this);
            var text = $label.text();
            
            var helpTexts = {
                'Target Posts Grid Widget ID': 'Leave empty to target all Posts Grid widgets on the same page. Use specific widget ID to target only one widget.',
                'Saved Template': 'Select a template created with Elementor or other page builders. Use placeholders like {{post_title}}, {{post_excerpt}}, etc.',
                'Post Type': 'Choose which post type to display. Custom post types will appear here if they are public.',
                'Posts per page': 'Number of posts to display per page. Recommended: 6-12 for optimal performance.'
            };
            
            for (var key in helpTexts) {
                if (text.indexOf(key) !== -1) {
                    $label.append(' <span class="pgs-help-icon" title="' + helpTexts[key] + '">?</span>');
                    break;
                }
            }
        });
        
        // Initialize tooltips
        $('.pgs-help-icon').on('mouseenter', function() {
            var $icon = $(this);
            var title = $icon.attr('title');
            
            if (title && !$icon.siblings('.pgs-tooltip').length) {
                var $tooltip = $('<div class="pgs-tooltip">' + title + '</div>');
                $icon.after($tooltip);
                
                setTimeout(function() {
                    $tooltip.addClass('pgs-tooltip-visible');
                }, 10);
            }
        });
        
        $('.pgs-help-icon').on('mouseleave', function() {
            var $tooltip = $(this).siblings('.pgs-tooltip');
            $tooltip.removeClass('pgs-tooltip-visible');
            setTimeout(function() {
                $tooltip.remove();
            }, 300);
        });
    }
    
    // Enhanced widget save functionality
    $(document).on('click', '.widget-control-save', function() {
        var $widget = $(this).closest('.widget');
        var $form = $widget.find('.pgs-widget-form');
        
        // Add saving state
        $form.addClass('pgs-saving');
        
        // Show success message after save
        setTimeout(function() {
            $form.removeClass('pgs-saving');
            
            // Add temporary success indicator
            var $success = $('<div class="pgs-success">Settings saved successfully!</div>');
            $form.prepend($success);
            
            setTimeout(function() {
                $success.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 2000);
        }, 1000);
    });
    
    // Template management integration
    function initTemplateManagement() {
        // Add quick links to template management
        $('.pgs-widget-form select[id*="template_id"]').each(function() {
            var $select = $(this);
            if ($select.siblings('.pgs-template-actions').length === 0) {
                var $actions = $('<div class="pgs-template-actions" style="margin-top: 8px;">' +
                    '<a href="' + pgs_admin.template_page_url + '" target="_blank" style="font-size: 12px; color: #14b8a6; text-decoration: none;">Manage Templates</a>' +
                    '</div>');
                $select.after($actions);
            }
        });
    }
    
    // Initialize template management if admin URL is available
    if (typeof pgs_admin !== 'undefined' && pgs_admin.template_page_url) {
        initTemplateManagement();
    }
});

// Add enhanced CSS for admin improvements
$('<style>' +
'.pgs-changed { background-color: rgba(20, 184, 166, 0.1) !important; }' +
'.pgs-invalid { border-color: #ef4444 !important; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important; }' +
'.pgs-disabled { opacity: 0.5; pointer-events: none; }' +
'.pgs-tooltip { position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); background: #1a202c; color: white; padding: 8px 12px; border-radius: 6px; font-size: 11px; white-space: nowrap; opacity: 0; transition: all 0.3s ease; z-index: 1000; margin-bottom: 5px; }' +
'.pgs-tooltip::after { content: ""; position: absolute; top: 100%; left: 50%; transform: translateX(-50%); border: 5px solid transparent; border-top-color: #1a202c; }' +
'.pgs-tooltip-visible { opacity: 1; }' +
'</style>').appendTo('head');