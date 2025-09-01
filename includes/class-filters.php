<?php
//custom filters
class ACF_Loop_Grid_Filters {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_acf_filter_posts', array($this, 'ajax_filter_posts'));
        add_action('wp_ajax_nopriv_acf_filter_posts', array($this, 'ajax_filter_posts'));
        add_shortcode('acf_loop_filters', array($this, 'render_filters_shortcode'));
        
        // Widget registration
        add_action('widgets_init', array($this, 'register_widget'));
    }
    
    public function init() {
        // Initialize the system
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        
        // Enqueue Flatpickr
        wp_enqueue_script('flatpickr', 'https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js', array(), '4.6.13', true);
        wp_enqueue_style('flatpickr', 'https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css', array(), '4.6.13');
        
        wp_localize_script('jquery', 'acf_filter_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('acf_filter_nonce')
        ));
        
        // Add inline scripts
        add_action('wp_footer', array($this, 'add_inline_script'));
        add_action('wp_head', array($this, 'add_inline_styles'));

    }

    public function add_inline_styles() {
        ?>
            <style>
            .acf-filters-wrapper {
                background: #ffffff;
                overflow: auto;
                height: 85vh;
            }

            .light-theme .acf-filters-wrapper {
                background: #001319;
            }

            .acf-selected-filters {
                padding-bottom: 20px;
                border-bottom: 1px solid #f0f0f0;
                display: none;
            }

            .acf-selected-filters.has-selections {
                display: block;
            }

            .acf-selected-tag {
                display: inline-flex;
                font-family: "Oswald", Sans-serif;
                flex-direction: row-reverse;
                align-items: center;
                cursor: pointer;
                color: #001319;
                padding: 6px 8px;
                margin: 4px 8px 4px 0;
                border-radius: 16px;
                font-size: 12px;
                font-weight: 600;
                border: 1px solid #EBEDEE;
            }

            .light-theme .acf-selected-tag {
                color: white;
                border-color: #1F2F35;
            }

            .acf-selected-tag .remove-tag {
                margin-right: 8px;
                cursor: pointer;
                font-weight: 600;
                color: #001319;
                font-size: 12px;
                line-height: 1;
            }

            .light-theme .acf-selected-tag .remove-tag {
                color: white;
            }

            .acf-selected-tag:hover .remove-tag svg path {
                stroke: #d32f2f;
            }

            .acf-filter-accordion {
                border-top: 1px solid #EBEDEE;
                overflow: hidden;
                transition: border-top-color 0.3s ease;
            }
                .light-theme .acf-filter-accordion{
                    border-top-color:#1F2F35;
                }

            /* Blue border-top when accordion is open */
            .acf-filter-accordion.open {
                border-top: 1px solid #00AAD8;
            }

            .acf-filter-accordion:last-child {
                border-bottom: none;
            }

            .acf-accordion-header {
                background: #ffffff;
                padding: 24px 0px;
                cursor: pointer;
                display: flex;
                justify-content: space-between;
                align-items: center;
                transition: background-color 0.2s ease;
                user-select: none;
            }

            .light-theme .acf-accordion-header {
                background: #001319;
            }

            .acf-accordion-title {
                font-size: 20px;
                font-family: "Oswald", Sans-serif;
                font-weight: 500;
                color: #001319;
                text-transform: uppercase;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .light-theme .acf-accordion-title {
                color: white;
            }

            .acf-filters-inner__wrapper {
                height: 100%;
                display: flex;
                width: 100%;
                flex-direction: column;
                justify-content: space-between;
            }

            .acf-filter-count {
                color: #7A8487;
                font-family: "Oswald", Sans-serif;
                font-size: 12px;
                font-weight: 500;
                padding: 2px 8px;
                border-radius: 12px;
                min-width: 20px;
                text-align: center;
            }

            .acf-accordion-icon {
                font-size: 18px;
                color: #7A8487;
                transition: transform 0.3s ease;
            }

            .acf-accordion-icon.open {
                transform: rotate(180deg);
            }

            .acf-accordion-content {
                background: #fff;
                padding: 0;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease;
            }

            .light-theme .acf-accordion-content {
                background: #001319;
            }

            .acf-accordion-content.open {
                max-height: 400px;
            }

            .acf-filter-options {
                padding-bottom: 24px;
                background: #fff;
            }

            .light-theme .acf-filter-options {
                background: #001319;
            }

            .acf-filter-item {
                display: flex;
                align-items: center;
                padding: 5px 0;
                cursor: pointer;
                transition: background-color 0.2s ease;
            }

            .acf-filter-checkbox {
                width: 20px;
                height: 20px;
                border: 2px solid #EBEDEE;
                border-radius: 100%;
                margin-right: 8px;
                position: relative;
                flex-shrink: 0;
                transition: all 0.2s ease;
            }
                .light-theme .acf-filter-checkbox{
                    border-color: #7A8487;
                }

            .acf-filter-checkbox.checked {
                background: #00AAD8;
                border-color: #00AAD8;
            }

            .acf-filter-checkbox.checked::after {
                content: '✓';
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                color: white;
                font-size: 12px;
                font-weight: bold;
            }

            .acf-filter-label {
                font-size: 15px;
                color: #001319;
                flex: 1;
            }

            .light-theme .acf-filter-label {
                color: white;
            }

            .acf-date-filter {
                padding-bottom: 20px;
            }

            .acf-date-inputs {
                display: flex;
                gap: 15px;
                flex-wrap: wrap;
            }

            .acf-date-field {
                flex: 1;
                min-width: 140px;
            }

            .acf-date-label {
                display: block;
                font-size: 14px;
                font-weight: 600;
                color: #001319;
                margin-bottom: 8px;
                text-transform: uppercase;
                font-family: "Oswald", Sans-serif;
            }

            .light-theme .acf-date-label {
                color: white;
            }

            .acf-date-input {
                width: 100%;
                padding: 12px;
                border: 1px solid #EBEDEE;
                border-radius: 6px;
                font-size: 14px;
                transition: border-color 0.2s ease;
                background: #ffffff;
                color: #001319;
                font-family: "Oswald", Sans-serif;
                font-size: 14px;
                font-weight: 500;
                text-transform: uppercase;
                line-height: 100%;
            }

            .light-theme .acf-date-input {
                border-color: #7A8487;
                background: #001319;
                color: white;
            }

            .acf-date-input:focus {
                outline: none;
                border-color: #00AAD8;
            }

            /* Flatpickr theme customization */
            .flatpickr-calendar {
                font-family: "Oswald", Sans-serif;
            }

            .flatpickr-day.selected {
                background: #00AAD8;
                border-color: #00AAD8;
            }

            .flatpickr-day:hover {
                background: rgba(0, 170, 216, 0.1);
            }

            .light-theme .flatpickr-calendar {
                background: #001319;
                color: white;
                border-color: #7A8487;
            }

            .light-theme .flatpickr-day {
                color: white;
            }

            .light-theme .flatpickr-day:hover {
                background: rgba(0, 170, 216, 0.2);
            }

            .acf-filter-actions {
                padding: 16px;
                background: #ffffff;
                border-top: 1px solid #EBEDEE;
                display: none;
                gap: 12px;
                position: fixed;
                bottom: 0;
                right:0;
                width: 100%;
                max-width:640px;
            }

            .light-theme .acf-filter-actions {
                background: #001319;
                border-top-color:#7A8487;
            }

            .acf-filter-actions.show {
                display: flex;
                justify-content: center;
            }

            .acf-action-btn {
                padding: 14px 20px;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
                text-transform: uppercase;
            }

            .acf-reset-btn {
                background: transparent;
                color: #001319;
                height: 43px;
                font-size: 14px;
                font-weight: 600;
                border: 1px solid #EBEDEE;
                border-radius: 999999px;
            }

            .light-theme .acf-reset-btn {
                color: white;
                border-color:#1F2F35;
            }

            .acf-confirm-btn {
                background: #00AAD8;
                color: white;
                width: 147px;
                font-size: 14px;
                height: 43px;
                font-weight: 600;
                border-radius: 999999px;
                border: 1px solid #00AAD8;
            }

            .acf-loading {
                display: none;
                text-align: center;
                padding: 40px 20px;
                background: rgba(255, 255, 255, 0.9);
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 1000;
            }
                .light-theme .acf-loading {
                    background:rgb(0, 19, 25 , 0.9)
                }
                .light-theme .acf-loading p{
                    color: white;
                }

            .acf-loading-spinner {
                width: 40px;
                height: 40px;
                border: 4px solid #f3f3f3;
                border-top: 4px solid #00AAD8;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto 15px;
            }

            @keyframes spin {
                0% {
                    transform: rotate(0deg);
                }

                100% {
                    transform: rotate(360deg);
                }
            }

            @media (max-width: 768px) {
                .acf-date-inputs {
                    flex-direction: column;
                }

                .acf-date-field {
                    min-width: 100%;
                }

                .acf-accordion-content.open {
                    max-height: 300px;
                }
            }

            .blog-filters.has-filters::before {
                content: '';
                position: absolute;
                top: 0px;
                right: 0px;
                width: 10px;
                height: 10px;
                background: #00AEEF;
                border-radius: 50%;
                z-index: 100;
            }

            .acf-filters_inner_section {
                padding-bottom: 70px;
            }
                
                .light-theme .flatpickr-months .flatpickr-next-month svg path, .flatpickr-months .flatpickr-prev-month svg path{
                    fill: white;
                }
                .light-theme span.flatpickr-weekday, .light-theme .flatpickr-monthDropdown-months, .light-theme .flatpickr-current-month input.cur-year{
                    color:white;
                }

        </style>
        <?php
    }
    
    public function add_inline_script() {
        ?>
        <script>
            jQuery(document).ready(function($) {
                'use strict';
                
                // Initialize filter functionality
                initFilter();
                
                function initFilter() {
                    var postsGrid = $('.pgs-posts-grid');
                    var postsContainer = $('.pgs-posts-container');
                    var resultsInfo = $('#pgs-search-results-info');
                    var resultsCount = $('#pgs-results-count');
                    var fromDatePicker = null;
                    var toDatePicker = null;
                    var savedFilters = {};
                    
                    // Initialize Flatpickr date pickers
                    function initializeDatePickers() {
                        // Destroy existing instances first
                        if (fromDatePicker) {
                            fromDatePicker.destroy();
                            fromDatePicker = null;
                        }
                        if (toDatePicker) {
                            toDatePicker.destroy();
                            toDatePicker = null;
                        }

                        // Wait for DOM elements to be ready
                        setTimeout(function() {
                            if ($('.acf-date-from').length) {
                                fromDatePicker = flatpickr('.acf-date-from', {
                                    dateFormat: 'm-d-y',
                                    placeholder: 'Select start date',
                                    allowInput: true,
                                    disableMobile: true,
                                    onChange: function(selectedDates, dateStr, instance) {
                                        updateFilterActions();
                                        if (toDatePicker && dateStr) {
                                            toDatePicker.set('minDate', dateStr);
                                        }
                                    }
                                });
                            }

                            if ($('.acf-date-to').length) {
                                toDatePicker = flatpickr('.acf-date-to', {
                                    dateFormat: 'm-d-y',
                                    placeholder: 'Select end date',
                                    allowInput: true,
                                    disableMobile: true,
                                    onChange: function(selectedDates, dateStr, instance) {
                                        updateFilterActions();
                                        if (fromDatePicker && dateStr) {
                                            fromDatePicker.set('maxDate', dateStr);
                                        }
                                    }
                                });
                            }
                        }, 50);
                    }

                    // Initialize date pickers on page load
                    setTimeout(initializeDatePickers, 100);

                    // Accordion functionality
                    $(document).on('click', '.acf-accordion-header', function() {
                        var $header = $(this);
                        var $content = $header.next('.acf-accordion-content');
                        var $icon = $header.find('.acf-accordion-icon');
                        var $accordion = $header.parent('.acf-filter-accordion');
                        
                        $content.toggleClass('open');
                        $icon.toggleClass('open');
                        $accordion.toggleClass('open');
                        
                        if ($content.hasClass('open')) {
                            $content.css('max-height', $content[0].scrollHeight + 'px');
                        } else {
                            $content.css('max-height', '0');
                        }
                    });
                    
                    // Filter item click functionality
                    $(document).on('click', '.acf-filter-item', function(e) {
                        e.preventDefault();
                        var $item = $(this);
                        var $checkbox = $item.find('.acf-filter-checkbox');
                        
                        $checkbox.toggleClass('checked');
                        
                        updateSelectedFilters();
                        updateFilterActions();
                    });
                    
                    // Date input change
                    $(document).on('change input', '.acf-date-input', function() {
                        updateFilterActions();
                    });
                    
                    // Remove selected tag
                    $(document).on('click', '.remove-tag', function(e) {
                        e.stopPropagation();
                        var $tag = $(this).closest('.acf-selected-tag');
                        var fieldKey = $tag.data('field');
                        var value = $tag.data('value');
                        
                        if (fieldKey === 'publish_date_from') {
                            $('.acf-date-from').val('');
                            if (fromDatePicker) {
                                fromDatePicker.clear();
                            }
                        } else if (fieldKey === 'publish_date_to') {
                            $('.acf-date-to').val('');
                            if (toDatePicker) {
                                toDatePicker.clear();
                            }
                        } else {
                            $('.acf-filter-item').each(function() {
                                var $item = $(this);
                                var itemField = $item.closest('.acf-filter-accordion').data('field-key');
                                var itemValue = $item.data('value');
                                
                                if (itemField === fieldKey && itemValue === value) {
                                    $item.find('.acf-filter-checkbox').removeClass('checked');
                                    return false;
                                }
                            });
                        }
                        
                        updateSelectedFilters();
                        updateFilterActions();
                        applyFilters(1);
                    });
                    
                    // Reset all filters
                    $(document).on('click', '.acf-reset-btn', function() {
                        $('.acf-filter-checkbox').removeClass('checked');
                        if (fromDatePicker) {
                            fromDatePicker.clear();
                        }
                        if (toDatePicker) {
                            toDatePicker.clear();
                        }
                        $('.acf-date-input').val('');
                        
                        updateSelectedFilters();
                        updateFilterActions();
                        applyFilters(1);
                    });
                    
                    // Confirm/Apply filters
                    $(document).on('click', '.acf-confirm-btn', function() {
                        applyFilters(1);
                    });
                    
                    function updateSelectedFilters() {
                        var $selectedContainer = $('.acf-selected-filters');
                        var $tagsContainer = $selectedContainer.find('.acf-selected-tags');
                        
                        if (!$tagsContainer.length) {
                            $selectedContainer.append('<div class="acf-selected-tags"></div>');
                            $tagsContainer = $selectedContainer.find('.acf-selected-tags');
                        }
                        
                        $tagsContainer.empty();
                        
                        $('.acf-filter-checkbox.checked').each(function() {
                            var $checkbox = $(this);
                            var $item = $checkbox.closest('.acf-filter-item');
                            var $accordion = $item.closest('.acf-filter-accordion');
                            var fieldKey = $accordion.data('field-key');
                            var value = $item.data('value');
                            var label = $item.find('.acf-filter-label').text();
                            
                            var $tag = $(`
                                <div class="acf-selected-tag" data-field="${fieldKey}" data-value="${value}">
                                    ${label}
                                    <span class="remove-tag">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                            <path d="M9.375 2.625L2.625 9.375" stroke="#001319" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M9.375 9.375L2.625 2.625" stroke="#001319" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                </div>
                            `);
                            $tagsContainer.append($tag);
                        });

                        var dateFrom = $('.acf-date-from').val();
                        var dateTo = $('.acf-date-to').val();

                        if (dateFrom) {
                            var $dateTag = $(`
                                <div class="acf-selected-tag" data-field="publish_date_from" data-value="${dateFrom}">
                                    From: ${dateFrom}
                                    <span class="remove-tag">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                            <path d="M9.375 2.625L2.625 9.375" stroke="#001319" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M9.375 9.375L2.625 2.625" stroke="#001319" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                </div>
                            `);
                            $tagsContainer.append($dateTag);
                        }

                        if (dateTo) {
                            var $dateTag = $(`
                                <div class="acf-selected-tag" data-field="publish_date_to" data-value="${dateTo}">
                                    To: ${dateTo}
                                    <span class="remove-tag">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                            <path d="M9.375 2.625L2.625 9.375" stroke="#001319" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M9.375 9.375L2.625 2.625" stroke="#001319" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                </div>
                            `);
                            $tagsContainer.append($dateTag);
                        }
                        
                        if ($tagsContainer.children().length > 0) {
                            $selectedContainer.addClass('has-selections');
                        } else {
                            $selectedContainer.removeClass('has-selections');
                        }
                    }
                    
                    function updateFilterActions() {
                        var hasSelections = $('.acf-filter-checkbox.checked').length > 0 ||
                            $('.acf-date-input').filter(function() { return $(this).val() !== ''; }).length > 0;

                        if (hasSelections) {
                            $('.acf-filter-actions').addClass('show');
                        } else {
                            $('.acf-filter-actions').removeClass('show');
                        }
                    }
                    
                    function collectFilterData() {
                        var filters = {};
                        
                        $('.acf-filter-accordion[data-field-key]').each(function() {
                            var $accordion = $(this);
                            var fieldKey = $accordion.data('field-key');
                            
                            if (fieldKey === 'publish_date') return;
                            
                            var selectedValues = [];
                            
                            $accordion.find('.acf-filter-checkbox.checked').each(function() {
                                var $item = $(this).closest('.acf-filter-item');
                                selectedValues.push($item.data('value'));
                            });
                            
                            if (selectedValues.length > 0) {
                                filters[fieldKey] = selectedValues;
                            }
                        });
                        
                        var dateFrom = $('.acf-date-from').val();
                        var dateTo = $('.acf-date-to').val();
                        
                        if (dateFrom) filters['publish_date_from'] = dateFrom;
                        if (dateTo) filters['publish_date_to'] = dateTo;
                        
                        return filters;
                    }
                    
                    function applyFilters(page) {
                        page = page || 1;
                        
                        var $wrapper = $('.acf-filters-wrapper');
                        var postsContainer = $('.pgs-posts-container');
                        var pagination = $('.pgs-pagination');
                        var resultsInfo = $('#pgs-search-results-info');
                        var resultsCount = $('#pgs-results-count');
                        
                        if (postsContainer.length === 0) return;
                        
                        var postsPerPage = postsGrid.data('posts-per-page') || 6;
                        var template = postsGrid.data('template') || 'card';
                        
                        // Show loading state
                        postsContainer.addClass('pgs-loading');
                        if (postsContainer.find('.pgs-loading-overlay').length === 0) {
                            postsContainer.append('<div class="pgs-loading-overlay"><div class="pgs-spinner"></div></div>');
                        }
                        
                        var filters = collectFilterData();
                        savedFilters = filters;
                        
                        if (Object.keys(filters).length > 0) {
                            $('.acf-filters-wrapper').addClass('has-filters');
                        } else {
                            $('.acf-filters-wrapper').removeClass('has-filters');
                        }
                        
                        $.ajax({
                            url: acf_filter_ajax.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'acf_filter_posts',
                                filters: filters,
                                post_type: $wrapper.data('post-type'),
                                posts_per_page: postsPerPage,
                                template: template,
                                page: page,
                                show_excerpt: postsGrid.data('show-excerpt'),
                                show_author: postsGrid.data('show-author'),
                                show_date: postsGrid.data('show-date'),
                                nonce: acf_filter_ajax.nonce
                            },
                            success: function(response) {
                                if (response.success) {
                                    postsContainer.html(response.data.posts);
                                    
                                    // Update pagination
                                    updatePagination(pagination, response.data.current_page, response.data.total_pages);
                                    
                                    // Update results info
                                    if (Object.keys(filters).length > 0) {
                                        var totalPosts = response.data.total_pages * postsPerPage;
                                        resultsCount.text('Found ' + totalPosts + ' posts for applied filters');
                                        resultsInfo.show();
                                    } else {
                                        resultsInfo.hide();
                                    }
                                    
                                    // Scroll to top of posts grid
                                    $('html, body').animate({
                                        scrollTop: $('.pgs-posts-grid').offset().top - 50
                                    }, 500);
                                } else {
                                    console.error('Filter failed:', response.data);
                                    postsContainer.html('<div class="pgs-no-posts">Filtering failed. Please try again.</div>');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX error:', error);
                                postsContainer.html('<div class="pgs-no-posts">Filtering failed. Please try again.</div>');
                            },
                            complete: function() {
                                postsContainer.removeClass('pgs-loading');
                                postsContainer.find('.pgs-loading-overlay').remove();
                            }
                        });
                    }
                    
                    function updatePagination(paginationContainer, currentPage, totalPages) {
                        if (totalPages <= 1) {
                            paginationContainer.hide();
                            return;
                        }

                        paginationContainer.show();

                        var paginationHtml = '';
                        var prevIcon = '←';
                        var nextIcon = '→';

                        // Previous button
                        if (currentPage > 1) {
                            paginationHtml += '<a href="#" data-page="' + (currentPage - 1) + '" class="pgs-pagination-btn pgs-pagination-prev">' + prevIcon + '</a>';
                        }

                        // Always show first page
                        if (currentPage > 3) {
                            paginationHtml += '<a href="#" data-page="1" class="pgs-pagination-btn">1</a>';
                            if (currentPage > 4) {
                                paginationHtml += '<span class="pgs-pagination-dots">...</span>';
                            }
                        }

                        // Show middle pages (current ±2)
                        var startPage = Math.max(1, currentPage - 2);
                        var endPage = Math.min(totalPages, currentPage + 2);

                        for (var i = startPage; i <= endPage; i++) {
                            if (i === currentPage) {
                                paginationHtml += '<span class="pgs-pagination-btn pgs-pagination-current">' + i + '</span>';
                            } else {
                                paginationHtml += '<a href="#" data-page="' + i + '" class="pgs-pagination-btn">' + i + '</a>';
                            }
                        }

                        // Always show last page
                        if (currentPage < totalPages - 2) {
                            if (currentPage < totalPages - 3) {
                                paginationHtml += '<span class="pgs-pagination-dots">...</span>';
                            }
                            paginationHtml += '<a href="#" data-page="' + totalPages + '" class="pgs-pagination-btn">' + totalPages + '</a>';
                        }

                        // Next button
                        if (currentPage < totalPages) {
                            paginationHtml += '<a href="#" data-page="' + (currentPage + 1) + '" class="pgs-pagination-btn pgs-pagination-next">' + nextIcon + '</a>';
                        }

                        paginationContainer.html(paginationHtml);
                    }

                    
                    // Pagination click handler
                    $(document).on('click', '.pgs-pagination-btn[data-page]', function(e) {
                        e.preventDefault();
                        
                        var page = parseInt($(this).data('page'));
                        applyFilters(page);
                    });
                    
                    // Restore filters on popup show
                    $(document).on('elementor/popup/show', function(event, id, instance) {
                        if (Object.keys(savedFilters).length > 0) {
                            $('.acf-filter-checkbox').removeClass('checked');
                            
                            for (var fieldKey in savedFilters) {
                                var fieldValues = savedFilters[fieldKey];
                                
                                if (fieldKey === 'publish_date_from') {
                                    $('.acf-date-from').val(fieldValues);
                                    if (fromDatePicker) {
                                        fromDatePicker.setDate(fieldValues);
                                    }
                                    continue;
                                }
                                if (fieldKey === 'publish_date_to') {
                                    $('.acf-date-to').val(fieldValues);
                                    if (toDatePicker) {
                                        toDatePicker.setDate(fieldValues);
                                    }
                                    continue;
                                }
                                
                                if (Array.isArray(fieldValues)) {
                                    fieldValues.forEach(function(val) {
                                        $('.acf-filter-accordion[data-field-key="' + fieldKey + '"] .acf-filter-item[data-value="' + val + '"] .acf-filter-checkbox').addClass('checked');
                                    });
                                } else {
                                    $('.acf-filter-accordion[data-field-key="' + fieldKey + '"] .acf-filter-item[data-value="' + fieldValues + '"] .acf-filter-checkbox').addClass('checked');
                                }
                            }
                            
                            updateSelectedFilters();
                            updateFilterActions();
                        }
                    });
                    
                    // Initialize filter counts
                    updateFilterCounts();
                    
                    function updateFilterCounts() {
                        $('.acf-filter-accordion').each(function() {
                            var $accordion = $(this);
                            var count = $accordion.find('.acf-filter-item').length;
                            $accordion.find('.acf-filter-count').text(count);
                        });
                    }
                }
            });
        </script>
        <?php
    }
    
    public function render_filters_shortcode($atts) {
        $atts = shortcode_atts(array(
            'post_type' => 'post',
            'fields' => '',
            'show_date_filter' => 'true',
            'filter_titles' => ''
        ), $atts);
        
        return $this->generate_filters_html($atts);
    }
    
    private function generate_filters_html($args) {
        $post_type = sanitize_text_field($args['post_type']);
        $fields = !empty($args['fields']) ? array_map('trim', explode(',', $args['fields'])) : array();
        $show_date_filter = $args['show_date_filter'] === 'true';
        $filter_titles = !empty($args['filter_titles']) ? array_map('trim', explode(',', $args['filter_titles'])) : array();
        
        if (empty($fields) && !$show_date_filter) {
            return '<p>No filters configured. Please specify ACF fields or enable date filter.</p>';
        }
        
        ob_start();
        ?>
        <div class="acf-filters-wrapper" data-post-type="<?php echo esc_attr($post_type); ?>" data-posts-per-page="-1">
            <div class="acf-filters-inner__wrapper">
                <div class="acf-filters_inner_section">
                    <!-- Selected Filters Display -->
                    <div class="acf-selected-filters">
                        <div class="acf-selected-tags"></div>
                    </div>

                    <div class="acf-filters-acc">
                        <?php foreach ($fields as $index => $field_key): 
                            $field_key = trim($field_key);
                            $field_values = $this->get_field_values($field_key, $post_type);

                            if (empty($field_values)) {
                                echo '<!-- Field "' . esc_html($field_key) . '" has no values or doesn\'t exist -->';
                                continue;
                            }

                            $field_title = isset($filter_titles[$index]) && !empty($filter_titles[$index]) ? 
                                        $filter_titles[$index] : 
                                        $this->get_field_label($field_key, $post_type);
                            ?>

                            <div class="acf-filter-accordion" data-field-key="<?php echo esc_attr($field_key); ?>">
                                <div class="acf-accordion-header">
                                    <div class="acf-accordion-title">
                                        <?php echo esc_html(strtoupper($field_title)); ?>
                                        <span class="acf-filter-count"><?php echo count($field_values); ?></span>
                                    </div>
                                    <div class="acf-accordion-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 2.5V13.5" stroke="#001319" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M3.5 9L8 13.5L12.5 9" stroke="#001319" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg></div>
                                </div>

                                <div class="acf-accordion-content">
                                    <div class="acf-filter-options">
                                        <?php foreach ($field_values as $value): ?>
                                            <div class="acf-filter-item" data-value="<?php echo esc_attr($value); ?>">
                                                <div class="acf-filter-checkbox"></div>
                                                <div class="acf-filter-label"><?php echo esc_html($value); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; 

                        if ($show_date_filter): ?>
                            <div class="acf-filter-accordion" data-field-key="publish_date">
                                <div class="acf-accordion-header">
                                    <div class="acf-accordion-title">
                                        DATE
                                    </div>
                                    <div class="acf-accordion-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 2.5V13.5" stroke="#001319" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><path d="M3.5 9L8 13.5L12.5 9" stroke="#001319" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg></div>
                                </div>

                                <div class="acf-accordion-content">
                                    <div class="acf-date-filter">
                                        <div class="acf-date-inputs">
                                            <div class="acf-date-field">
                                                <label class="acf-date-label">From</label>
                                                <input type="text" class="acf-date-input acf-date-from" placeholder="Select start date" readonly>
                                            </div>
                                            <div class="acf-date-field">
                                                <label class="acf-date-label">To</label>
                                                <input type="text" class="acf-date-input acf-date-to" placeholder="Select end date" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif;?>
                    </div>
                </div>
                <!-- Action Buttons -->
                <div class="acf-filter-actions">
                    <button class="acf-action-btn acf-reset-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                            <path d="M9.375 2.625L2.625 9.375" stroke="#001319" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9.375 9.375L2.625 2.625" stroke="#001319" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg> RESET ALL FILTERS
                    </button>
                    <button class="acf-action-btn acf-confirm-btn">CONFIRM</button>
                </div>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    private function get_field_label($field_key, $post_type) {
        $field_object = get_field_object($field_key);
        
        if ($field_object && isset($field_object['label'])) {
            return $field_object['label'];
        }
        
        $posts = get_posts(array(
            'post_type' => $post_type,
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ));
        
        if (!empty($posts)) {
            $field_object = get_field_object($field_key, $posts[0]->ID);
            if ($field_object && isset($field_object['label'])) {
                return $field_object['label'];
            }
        }
        
        return ucwords(str_replace(array('_', '-'), ' ', $field_key));
    }
    
    private function get_field_values($field_key, $post_type) {
        $values = array();
        
        $posts = get_posts(array(
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => $field_key,
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        foreach ($posts as $post) {
            $field_value = get_field($field_key, $post->ID);
            
            if (!empty($field_value)) {
                if (is_array($field_value)) {
                    foreach ($field_value as $val) {
                        if (is_object($val)) {
                            if (isset($val->name)) {
                                $values[] = $val->name;
                            } elseif (isset($val->post_title)) {
                                $values[] = $val->post_title;
                            } elseif (isset($val->label)) {
                                $values[] = $val->label;
                            }
                        } elseif (is_string($val) && !empty(trim($val))) {
                            $values[] = trim($val);
                        }
                    }
                } elseif (is_object($field_value)) {
                    if (isset($field_value->name)) {
                        $values[] = $field_value->name;
                    } elseif (isset($field_value->post_title)) {
                        $values[] = $field_value->post_title;
                    } elseif (isset($field_value->label)) {
                        $values[] = $field_value->label;
                    }
                } elseif (is_string($field_value) && !empty(trim($field_value))) {
                    $values[] = trim($field_value);
                }
            }
        }
        
        $values = array_unique(array_filter($values, function($value) {
            return !empty(trim($value));
        }));
        
        sort($values);
        
        return $values;
    }
    
    public function ajax_filter_posts() {
        check_ajax_referer('acf_filter_nonce', 'nonce');

        $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
        $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'post';
        $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 6;
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $template = isset($_POST['template']) ? sanitize_text_field($_POST['template']) : 'card';
        $show_excerpt = isset($_POST['show_excerpt']) ? filter_var($_POST['show_excerpt'], FILTER_VALIDATE_BOOLEAN) : false;
        $show_author = isset($_POST['show_author']) ? filter_var($_POST['show_author'], FILTER_VALIDATE_BOOLEAN) : false;
        $show_date = isset($_POST['show_date']) ? filter_var($_POST['show_date'], FILTER_VALIDATE_BOOLEAN) : false;

        // Build query args
        $query_args = array(
            'post_type' => $post_type,
            'posts_per_page' => $posts_per_page,
            'paged' => $page,
            'post_status' => 'publish'
        );

        $meta_queries = array('relation' => 'AND');
        $has_meta_filters = false;

        foreach ($filters as $field_key => $values) {
            if (in_array($field_key, array('publish_date_from', 'publish_date_to'))) continue;

            if (!empty($values) && is_array($values)) {
                $has_meta_filters = true;
                $meta_queries[] = array(
                    'relation' => 'OR',
                    array(
                        'key' => $field_key,
                        'value' => $values,
                        'compare' => 'IN'
                    ),
                    array(
                        'key' => $field_key,
                        'value' => '"' . implode('"|"', array_map('esc_sql', $values)) . '"',
                        'compare' => 'REGEXP'
                    ),
                    array(
                        'key' => $field_key,
                        'value' => implode('|', array_map('esc_sql', $values)),
                        'compare' => 'REGEXP'
                    )
                );
            }
        }

        if ($has_meta_filters) {
            $query_args['meta_query'] = $meta_queries;
        }

        if (isset($filters['publish_date_from']) || isset($filters['publish_date_to'])) {
            $date_query = array();

            if (isset($filters['publish_date_from']) && !empty($filters['publish_date_from'])) {
                $from_date = $this->convert_date_format($filters['publish_date_from']);
                if ($from_date) {
                    $date_query['after'] = $from_date;
                }
            }

            if (isset($filters['publish_date_to']) && !empty($filters['publish_date_to'])) {
                $to_date = $this->convert_date_format($filters['publish_date_to']);
                if ($to_date) {
                    $date_query['before'] = $to_date;
                }
            }

            if (!empty($date_query)) {
                $date_query['inclusive'] = true;
                $query_args['date_query'] = array($date_query);
            }
        }

        $query = new WP_Query($query_args);

        // Render posts based on template
        ob_start();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $title = get_the_title();
                $excerpt = get_the_excerpt();
                $author = get_the_author();
                $date = get_the_date();
                $thumbnail = get_the_post_thumbnail($post_id, 'medium');
                $permalink = get_permalink();
                
                switch ($template) {
                    case 'card':
                        echo '<article class="pgs-post-card">';
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
                        echo '</article>';
                        break;
                        
                    case 'list':
                        echo '<article class="pgs-post-list">';
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
                        echo '</article>';
                        break;
                        
                    case 'minimal':
                        echo '<article class="pgs-post-minimal">';
                        echo '<a href="' . esc_url($permalink) . '" class="pgs-post-link">';
                        echo '<h3 class="pgs-post-title">' . esc_html($title) . '</h3>';
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
                        echo '</a>';
                        echo '</article>';
                        break;
                }
            }
        } else {
            echo '<div class="pgs-no-posts">' . __('No posts found.', 'posts-grid-search') . '</div>';
        }
        wp_reset_postdata();
        
        $posts_html = ob_get_clean();

        wp_send_json_success(array(
            'posts' => $posts_html,
            'current_page' => $page,
            'total_pages' => $query->max_num_pages
        ));
    }

    private function convert_date_format($date_string) {
        if (empty($date_string)) {
            return false;
        }
        
        $date = DateTime::createFromFormat('m-d-y', $date_string);
        
        if (!$date) {
            $date = DateTime::createFromFormat('n-j-y', $date_string);
        }
        
        if (!$date) {
            $date = DateTime::createFromFormat('m/d/y', $date_string);
        }
        
        if (!$date) {
            $date = DateTime::createFromFormat('n/j/y', $date_string);
        }
        
        if (!$date) {
            error_log('ACF Filter Debug - Could not parse date: ' . $date_string);
            return false;
        }
        
        return $date->format('Y-m-d');
    }
    
    public function register_widget() {
        register_widget('ACF_Loop_Grid_Filter_Widget');
    }
}

class ACF_Loop_Grid_Filter_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'acf_loop_grid_filter_widget',
            'ACF Loop Grid Filters',
            array('description' => 'Advanced ACF-based filters for posts')
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $filter_args = array(
            'post_type' => $instance['post_type'] ?? 'post',
            'fields' => $instance['acf_fields'] ?? '',
            'show_date_filter' => $instance['show_date_filter'] ?? 'true',
            'filter_titles' => $instance['filter_titles'] ?? ''
        );
        
        $acf_filters = new ACF_Loop_Grid_Filters();
        echo $acf_filters->render_filters_shortcode($filter_args);
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Filter Posts';
        $post_type = !empty($instance['post_type']) ? $instance['post_type'] : 'post';
        $acf_fields = !empty($instance['acf_fields']) ? $instance['acf_fields'] : '';
        $filter_titles = !empty($instance['filter_titles']) ? $instance['filter_titles'] : '';
        $show_date_filter = !empty($instance['show_date_filter']) ? $instance['show_date_filter'] : 'true';
        ?>
        
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('post_type'); ?>">Post Type:</label>
            <select class="widefat" id="<?php echo $this->get_field_id('post_type'); ?>" name="<?php echo $this->get_field_name('post_type'); ?>">
                <?php
                $post_types = get_post_types(array('public' => true), 'objects');
                foreach ($post_types as $pt) {
                    echo '<option value="' . esc_attr($pt->name) . '"' . selected($post_type, $pt->name, false) . '>' . esc_html($pt->label) . '</option>';
                }
                ?>
            </select>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('acf_fields'); ?>">ACF Fields (comma-separated):</label>
            <input class="widefat" id="<?php echo $this->get_field_id('acf_fields'); ?>" name="<?php echo $this->get_field_name('acf_fields'); ?>" type="text" value="<?php echo esc_attr($acf_fields); ?>">
            <small>Example: location, category, tags</small>
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('filter_titles'); ?>">Filter Titles (comma-separated):</label>
            <input class="widefat" id="<?php echo $this->get_field_id('filter_titles'); ?>" name="<?php echo $this->get_field_name('filter_titles'); ?>" type="text" value="<?php echo esc_attr($filter_titles); ?>">
            <small>Custom titles for each filter. Leave empty to use ACF field labels.</small>
        </p>
        
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_date_filter, 'true'); ?> id="<?php echo $this->get_field_id('show_date_filter'); ?>" name="<?php echo $this->get_field_name('show_date_filter'); ?>" value="true">
            <label for="<?php echo $this->get_field_id('show_date_filter'); ?>">Show Date Filter</label>
        </p>
        
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['post_type'] = (!empty($new_instance['post_type'])) ? sanitize_text_field($new_instance['post_type']) : 'post';
        $instance['acf_fields'] = (!empty($new_instance['acf_fields'])) ? sanitize_text_field($new_instance['acf_fields']) : '';
        $instance['filter_titles'] = (!empty($new_instance['filter_titles'])) ? sanitize_text_field($new_instance['filter_titles']) : '';
        $instance['show_date_filter'] = (!empty($new_instance['show_date_filter'])) ? 'true' : 'false';
        
        return $instance;
    }
}

// Initialize the system
new ACF_Loop_Grid_Filters();