jQuery(document).ready(function($) {
    'use strict';

    // Initialize enhanced search functionality
    initEnhancedSearchFilter();

    function initEnhancedSearchFilter() {
        var searchInput = $('#pgs-search-input');
        var postTypeFilter = $('#pgs-post-type-filter');
        var clearButton = $('#pgs-search-clear');
        var clearSearch = $('#pgs-clear-search');
        var resultsInfo = $('#pgs-search-results-info');
        var resultsCount = $('#pgs-results-count');
        var searchTimeout;

        // Show/hide clear button based on input
        searchInput.on('input', function() {
            var value = $(this).val();
            toggleClearButton(value);

            // Real-time search with debounce
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                performEnhancedSearch();
            }, 300);
        });

        // Post type filter change
        postTypeFilter.on('change', function() {
            performEnhancedSearch();
        });

        // Clear search input
        clearButton.on('click', function() {
            searchInput.val('');
            toggleClearButton('');
            searchInput.focus();
            performEnhancedSearch();
        });

        // Clear all filters
        clearSearch.on('click', function() {
            searchInput.val('');
            postTypeFilter.val('');
            toggleClearButton('');
            resultsInfo.hide();
            performEnhancedSearch();
        });

        // Enter key search
        searchInput.on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                performEnhancedSearch();
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

            performEnhancedSearch(page);
            scrollToGrid();
        });

        function toggleClearButton(value) {
            if (value && value.length > 0) {
                clearButton.show();
            } else {
                clearButton.hide();
            }
        }

        function performEnhancedSearch(page) {
            page = page || 1;
            
            var searchQuery = searchInput.val();
            var selectedPostType = postTypeFilter.val();
            var targetWidget = searchInput.data('target-widget');
            
            // Find target grid widgets
            var $grids = targetWidget ? 
                $('#' + targetWidget) : 
                $('.pgs-posts-grid');

            if ($grids.length === 0) return;

            $grids.each(function() {
                var $grid = $(this);
                var $container = $grid.find('.pgs-posts-container');
                var $pagination = $grid.find('.pgs-pagination');
                
                // Get widget configuration
                var templateId = $grid.data('template-id') || 0;
                var originalPostType = $grid.data('post-type') || 'post';
                var postsPerPage = $grid.data('posts-per-page') || 6;
                var widgetSettings = $grid.data('widget-settings') || {};
                
                // Use filtered post type if available, otherwise use widget's original post type
                var postType = selectedPostType || originalPostType;
                
                // Show loading state
                showLoadingState($container);

                $.ajax({
                    url: pgs_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pgs_filter_posts',
                        search_query: searchQuery,
                        post_type: postType,
                        template_id: templateId,
                        posts_per_page: postsPerPage,
                        page: page,
                        widget_settings: widgetSettings,
                        nonce: pgs_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $container.html(response.data.posts_html || '');
                            
                            updateEnhancedPagination($pagination, response.data.current_page || page, response.data.total_pages || 1);
                            
                            updateResultsInfo(searchQuery, selectedPostType, response.data.total_posts || 0);
                        } else {
                            console.error('Search failed:', response.data);
                            $container.html('<div class="pgs-no-posts">' + 
                                (response.data.message || 'Search failed. Please try again.') + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error);
                        $container.html('<div class="pgs-no-posts">Search failed. Please try again.</div>');
                    },
                    complete: function() {
                        hideLoadingState($container);
                    }
                });
            });
        }

        function updateResultsInfo(searchQuery, postType, totalPosts) {
            if (searchQuery || postType) {
                var message = 'Found ' + totalPosts + ' posts';
                
                if (searchQuery && postType) {
                    var postTypeLabel = postTypeFilter.find('option[value="' + postType + '"]').text();
                    message += ' for "' + searchQuery + '" in ' + postTypeLabel;
                } else if (searchQuery) {
                    message += ' for "' + searchQuery + '"';
                } else if (postType) {
                    var postTypeLabel = postTypeFilter.find('option[value="' + postType + '"]').text();
                    message += ' in ' + postTypeLabel;
                }
                
                resultsCount.text(message);
                resultsInfo.show();
            } else {
                resultsInfo.hide();
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

        function scrollToGrid() {
            var $firstGrid = $('.pgs-posts-grid').first();
            if ($firstGrid.length) {
                $('html, body').animate({
                    scrollTop: $firstGrid.offset().top - 50
                }, 500);
            }
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
        
        $(document).on('mouseenter', '.pgs-post-card, .pgs-post-list, .pgs-post-minimal', function() {
            var $post = $(this);
            clearTimeout(hoverTimeout);
            $post.addClass('pgs-hover');
        });
        
        $(document).on('mouseleave', '.pgs-post-card, .pgs-post-list, .pgs-post-minimal', function() {
            var $post = $(this);
            hoverTimeout = setTimeout(function() {
                $post.removeClass('pgs-hover');
            }, 100);
        });
    }
    
    initEnhancedPostHovers();

    // Keyboard navigation for accessibility
    function initKeyboardNavigation() {
        $(document).on('keydown', '.pgs-pagination-btn', function(e) {
            if (e.which === 13 || e.which === 32) { // Enter or Space
                e.preventDefault();
                $(this).click();
            }
        });
    }
    
    initKeyboardNavigation();

    // Auto-refresh functionality for dynamic content
    function initAutoRefresh() {
        var refreshInterval = 300000; // 5 minutes
        
        setInterval(function() {
            var $grids = $('.pgs-posts-grid');
            if ($grids.length && !$('.pgs-search-input').val()) {
                // Only auto-refresh if no active search
                $grids.each(function() {
                    var $grid = $(this);
                    var currentPage = $grid.find('.pgs-pagination-current').text() || 1;
                    // Trigger a silent refresh
                    performSilentRefresh($grid, parseInt(currentPage, 10));
                });
            }
        }, refreshInterval);
    }
    
    function performSilentRefresh($grid, page) {
        var $container = $grid.find('.pgs-posts-container');
        var templateId = $grid.data('template-id') || 0;
        var postType = $grid.data('post-type') || 'post';
        var postsPerPage = $grid.data('posts-per-page') || 6;
        var widgetSettings = $grid.data('widget-settings') || {};
        
        $.ajax({
            url: pgs_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'pgs_filter_posts',
                search_query: '',
                post_type: postType,
                template_id: templateId,
                posts_per_page: postsPerPage,
                page: page,
                widget_settings: widgetSettings,
                nonce: pgs_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.posts_html) {
                    $container.html(response.data.posts_html);
                }
            }
        });
    }
    
    // Initialize auto-refresh (optional feature)
    // initAutoRefresh();
});