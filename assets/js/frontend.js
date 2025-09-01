jQuery(document).ready(function($) {
    'use strict';

    // Initialize all enhanced functionality
    initEnhancedSearchFilter();
    initACFGridFilters();
    initEnhancedPostHovers();
    initKeyboardNavigation();

    function initEnhancedSearchFilter() {
        var searchInput = $('.pgs-search-input');
        var postTypeFilter = $('.pgs-post-type-select');
        var clearButton = $('.pgs-search-clear');
        var clearSearch = $('.pgs-clear-search');
        var resultsInfo = $('.pgs-search-results-info');
        var resultsCount = $('.pgs-results-count');
        var searchTimeout;

        // Show/hide clear button based on input
        searchInput.on('input', function() {
            var value = $(this).val();
            var $clearBtn = $(this).siblings('.pgs-search-clear');
            toggleClearButton($clearBtn, value);

            // Real-time search with debounce
            clearTimeout(searchTimeout);
            var $input = $(this);
            searchTimeout = setTimeout(function() {
                performEnhancedSearch($input);
            }, 300);
        });

        // Post type filter change
        postTypeFilter.on('change', function() {
            performEnhancedSearch($(this));
        });

        // Clear search input
        clearButton.on('click', function() {
            var $input = $(this).siblings('.pgs-search-input');
            $input.val('');
            toggleClearButton($(this), '');
            $input.focus();
            performEnhancedSearch($input);
        });

        // Clear all filters
        clearSearch.on('click', function() {
            var $container = $(this).closest('.pgs-search-filter');
            var $searchInput = $container.find('.pgs-search-input');
            var $postTypeFilter = $container.find('.pgs-post-type-select');
            
            $searchInput.val('');
            $postTypeFilter.val('');
            toggleClearButton($container.find('.pgs-search-clear'), '');
            $container.find('.pgs-search-results-info').hide();
            performEnhancedSearch($searchInput);
        });

        // Enter key search
        searchInput.on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                performEnhancedSearch($(this));
            }
        });

        // Enhanced pagination handling
        $(document).on('click', '.pgs-pagination-btn:not(.pgs-pagination-current)', function(e) {
            e.preventDefault();
            
            var page = 1;
            var $btn = $(this);
            
            // Get page from data attribute (AJAX pagination)
            if ($btn.data('page')) {
                page = parseInt($btn.data('page'), 10);
            } else {
                // Get page from href (server-side pagination)
                var url = $btn.attr('href');
                if (url) {
                    var match = url.match(/page\/(\d+)/) || url.match(/[?&](?:paged|page)=([0-9]+)/);
                    if (match && match[1]) {
                        page = parseInt(match[1], 10);
                    }
                }
            }

            var $grid = $btn.closest('.pgs-posts-grid');
            performEnhancedSearch($grid.find('.pgs-search-input').first(), page);
            scrollToGrid($grid);
        });

        function toggleClearButton($clearBtn, value) {
            if (value && value.length > 0) {
                $clearBtn.show();
            } else {
                $clearBtn.hide();
            }
        }

        function performEnhancedSearch($element, page) {
            page = page || 1;
            
            var $container = $element.closest('.pgs-search-filter, .pgs-posts-grid');
            var $searchInput = $container.find('.pgs-search-input');
            var $postTypeFilter = $container.find('.pgs-post-type-select');
            var $resultsInfo = $container.find('.pgs-search-results-info');
            var $resultsCount = $container.find('.pgs-results-count');
            
            var searchQuery = $searchInput.val();
            var selectedPostType = $postTypeFilter.val();
            var targetWidget = $searchInput.data('target-widget') || $searchInput.data('target-grid');
            
            // Find target grid widgets
            var $grids = targetWidget ? 
                $('#' + targetWidget) : 
                $('.pgs-posts-grid');

            if ($grids.length === 0) return;

            $grids.each(function() {
                var $grid = $(this);
                var $postsContainer = $grid.find('.pgs-posts-container');
                var $pagination = $grid.find('.pgs-pagination');
                
                // Get widget configuration
                var templateId = $grid.data('template-id') || 0;
                var layout = $grid.data('layout') || 'default';
                var originalPostType = $grid.data('post-type') || 'post';
                var postsPerPage = $grid.data('posts-per-page') || 6;
                var widgetSettings = $grid.data('widget-settings') || {};
                
                // Use filtered post type if available, otherwise use widget's original post type
                var postType = selectedPostType || originalPostType;
                
                // Show loading state
                showLoadingState($postsContainer);

                var ajaxAction = $grid.hasClass('pgs-acf-grid') ? 'pgs_acf_filter' : 'pgs_filter_posts';

                $.ajax({
                    url: pgs_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: ajaxAction,
                        search_query: searchQuery,
                        post_type: postType,
                        template_id: templateId,
                        layout: layout,
                        posts_per_page: postsPerPage,
                        page: page,
                        widget_settings: widgetSettings,
                        nonce: pgs_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $postsContainer.html(response.data.posts_html || '');
                            
                            updateEnhancedPagination($pagination, response.data.current_page || page, response.data.total_pages || 1);
                            
                            updateResultsInfo($resultsInfo, $resultsCount, searchQuery, selectedPostType, response.data.total_posts || 0, $postTypeFilter);
                        } else {
                            console.error('Search failed:', response.data);
                            $postsContainer.html('<div class="pgs-no-posts">' + 
                                (response.data.message || 'Search failed. Please try again.') + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error);
                        $postsContainer.html('<div class="pgs-no-posts">Search failed. Please try again.</div>');
                    },
                    complete: function() {
                        hideLoadingState($postsContainer);
                    }
                });
            });
        }

        function updateResultsInfo($resultsInfo, $resultsCount, searchQuery, postType, totalPosts, $postTypeFilter) {
            if (searchQuery || postType) {
                var message = 'Found ' + totalPosts + ' posts';
                
                if (searchQuery && postType) {
                    var postTypeLabel = $postTypeFilter.find('option[value="' + postType + '"]').text();
                    message += ' for "' + searchQuery + '" in ' + postTypeLabel;
                } else if (searchQuery) {
                    message += ' for "' + searchQuery + '"';
                } else if (postType) {
                    var postTypeLabel = $postTypeFilter.find('option[value="' + postType + '"]').text();
                    message += ' in ' + postTypeLabel;
                }
                
                $resultsCount.text(message);
                $resultsInfo.show();
            } else {
                $resultsInfo.hide();
            }
        }

        function showLoadingState($container) {
            $container.addClass('pgs-loading');
            if ($container.find('.pgs-loading-overlay').length === 0) {
                $container.append('<div class="pgs-loading-overlay"><div class="pgs-spinner"></div></div>');
            }
        }

        function hideLoadingState($container) {
            $container.removeClass('pgs-loading');
            $container.find('.pgs-loading-overlay').remove();
        }

        function scrollToGrid($grid) {
            if ($grid.length) {
                $('html, body').animate({
                    scrollTop: $grid.offset().top - 50
                }, 500);
            }
        }
    }

    function initACFGridFilters() {
        var filterTimeout;
        
        // ACF field filters
        $(document).on('input', '.pgs-acf-filter-input', function() {
            clearTimeout(filterTimeout);
            var $input = $(this);
            filterTimeout = setTimeout(function() {
                performACFFilter($input);
            }, 500);
        });
        
        // ACF search input
        $(document).on('input', '.pgs-acf-search-input', function() {
            clearTimeout(filterTimeout);
            var $input = $(this);
            filterTimeout = setTimeout(function() {
                performACFFilter($input);
            }, 300);
        });
        
        // ACF post type filter
        $(document).on('change', '.pgs-acf-post-type-select', function() {
            performACFFilter($(this));
        });
        
        function performACFFilter($element) {
            var $grid = $('#' + $element.data('target-grid'));
            if ($grid.length === 0) return;
            
            var $container = $grid.find('.pgs-posts-container');
            var searchQuery = $grid.find('.pgs-acf-search-input').val() || '';
            var postType = $grid.find('.pgs-acf-post-type-select').val() || $grid.data('post-type');
            
            // Collect ACF filter values
            var acfFilters = {};
            $grid.find('.pgs-acf-filter-input').each(function() {
                var fieldName = $(this).data('field');
                var fieldValue = $(this).val();
                if (fieldValue) {
                    acfFilters[fieldName] = fieldValue;
                }
            });
            
            showLoadingState($container);
            
            $.ajax({
                url: pgs_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'pgs_acf_filter',
                    search_query: searchQuery,
                    post_type: postType,
                    template_id: $grid.data('template-id') || 0,
                    layout: $grid.data('layout') || 'default',
                    posts_per_page: $grid.data('posts-per-page') || 6,
                    page: 1,
                    widget_settings: $grid.data('widget-settings') || {},
                    acf_filters: acfFilters,
                    nonce: pgs_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $container.html(response.data.posts_html || '');
                        updateEnhancedPagination($grid.find('.pgs-pagination'), 1, response.data.total_pages || 1);
                    } else {
                        $container.html('<div class="pgs-no-posts">No posts found.</div>');
                    }
                },
                error: function() {
                    $container.html('<div class="pgs-no-posts">Search failed. Please try again.</div>');
                },
                complete: function() {
                    hideLoadingState($container);
                }
            });
        }
        
        function showLoadingState($container) {
            $container.addClass('pgs-loading');
            if ($container.find('.pgs-loading-overlay').length === 0) {
                $container.append('<div class="pgs-loading-overlay"><div class="pgs-spinner"></div></div>');
            }
        }
        
        function hideLoadingState($container) {
            $container.removeClass('pgs-loading');
            $container.find('.pgs-loading-overlay').remove();
        }
    }

    function updateEnhancedPagination(paginationContainer, currentPage, totalPages) {
        if (totalPages <= 1) {
            paginationContainer.hide();
            return;
        }

        paginationContainer.show();

        var paginationHtml = '';
        var prevIcon = '←';
        var nextIcon = '→';

        function appendPage(page) {
            if (page === currentPage) {
                paginationHtml += '<span class="pgs-pagination-btn pgs-pagination-current">' + page + '</span>';
            } else {
                paginationHtml += '<a href="#" data-page="' + page + '" class="pgs-pagination-btn">' + page + '</a>';
            }
        }

        // Previous button
        if (currentPage > 1) {
            paginationHtml += '<a href="#" data-page="' + (currentPage - 1) + '" class="pgs-pagination-btn pgs-pagination-prev">' + prevIcon + '</a>';
        }

        // Smart pagination logic
        if (totalPages <= 7) {
            // Show all pages if 7 or fewer
            for (var i = 1; i <= totalPages; i++) {
                appendPage(i);
            }
        } else {
            // Always show first page
            appendPage(1);

            // Calculate middle range
            var start = Math.max(2, currentPage - 1);
            var end = Math.min(totalPages - 1, currentPage + 1);

            // Adjust range if too close to start or end
            if (currentPage <= 3) {
                end = 4;
            } else if (currentPage >= totalPages - 2) {
                start = totalPages - 3;
            }

            // Ellipsis after first page
            if (start > 2) {
                paginationHtml += '<span class="pgs-pagination-ellipsis">...</span>';
            }

            // Middle pages
            for (var i = start; i <= end; i++) {
                appendPage(i);
            }

            // Ellipsis before last page
            if (end < totalPages - 1) {
                paginationHtml += '<span class="pgs-pagination-ellipsis">...</span>';
            }

            // Always show last page
            if (totalPages > 1) {
                appendPage(totalPages);
            }
        }

        // Next button
        if (currentPage < totalPages) {
            paginationHtml += '<a href="#" data-page="' + (currentPage + 1) + '" class="pgs-pagination-btn pgs-pagination-next">' + nextIcon + '</a>';
        }

        paginationContainer.html(paginationHtml);
    }

    // Enhanced post hover effects with performance optimization
    function initEnhancedPostHovers() {
        var hoverTimeout;
        
        $(document).on('mouseenter', '.pgs-post-card', function() {
            var $post = $(this);
            clearTimeout(hoverTimeout);
            $post.addClass('pgs-hover');
        });
        
        $(document).on('mouseleave', '.pgs-post-card', function() {
            var $post = $(this);
            hoverTimeout = setTimeout(function() {
                $post.removeClass('pgs-hover');
            }, 100);
        });
    }
    
    function initKeyboardNavigation() {
        $(document).on('keydown', '.pgs-pagination-btn', function(e) {
            if (e.which === 13 || e.which === 32) { // Enter or Space
                e.preventDefault();
                $(this).click();
            }
        });
    }

    // Performance optimization: Debounced resize handler
    var resizeTimeout;
    $(window).on('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            // Recalculate masonry layouts if needed
            $('.pgs-layout-masonry .pgs-posts-container').each(function() {
                // Trigger layout recalculation
                $(this).css('column-count', 'auto');
            });
        }, 250);
    });

    // Enhanced error handling
    $(document).ajaxError(function(event, xhr, settings, error) {
        if (settings.url.indexOf('pgs_filter_posts') !== -1 || settings.url.indexOf('pgs_acf_filter') !== -1) {
            console.error('PGS AJAX Error:', error);
            $('.pgs-posts-container.pgs-loading').each(function() {
                $(this).html('<div class="pgs-no-posts">Connection error. Please try again.</div>');
                hideLoadingState($(this));
            });
        }
    });

    function hideLoadingState($container) {
        $container.removeClass('pgs-loading');
        $container.find('.pgs-loading-overlay').remove();
    }
});