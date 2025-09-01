<?php
// Global Search Component - Include this in all pages
?>

<div class="global-search-container">
    <div class="input-group">
        <span class="input-group-text"><i class="fas fa-search"></i></span>
        <input type="text" class="form-control global-search-input" placeholder="Search invoices, quotations, products, clients..." id="globalSearchInput" autocomplete="off">
    </div>
    <div class="search-results-dropdown" id="searchResultsDropdown" style="display: none;">
        <div class="search-results-content">
            <div class="search-loading" id="searchLoading" style="display: none;">
                <i class="fas fa-spinner fa-spin"></i> Searching...
            </div>
            <div class="search-results-list" id="searchResultsList"></div>
            <div class="search-no-results" id="searchNoResults" style="display: none;">
                <i class="fas fa-search"></i>
                <p>No results found</p>
            </div>
        </div>
    </div>
</div>

<style>
.global-search-container {
    position: relative;
    width: 100%;
    max-width: 400px;
}

.global-search-input {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.global-search-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.search-results-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    z-index: 1050;
    margin-top: 2px;
    max-height: 400px;
    overflow-y: auto;
}

.search-results-content {
    padding: 0.5rem 0;
}

.search-loading {
    text-align: center;
    padding: 1rem;
    color: #6c757d;
}

.search-result-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    text-decoration: none;
    color: #212529;
    border-bottom: 1px solid #f8f9fa;
    transition: background-color 0.2s ease;
}

.search-result-item:hover {
    background-color: #f8f9fa;
    color: #212529;
    text-decoration: none;
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-result-icon {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    font-size: 0.875rem;
}

.search-result-icon.client { background: rgba(25, 135, 84, 0.1); color: #198754; }
.search-result-icon.product { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
.search-result-icon.estimate { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
.search-result-icon.purchase { background: rgba(220, 53, 69, 0.1); color: #dc3545; }

.search-result-content {
    flex: 1;
}

.search-result-title {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.search-result-subtitle {
    font-size: 0.8rem;
    color: #6c757d;
}

.search-result-type {
    background: #e9ecef;
    color: #495057;
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.search-no-results {
    text-align: center;
    padding: 2rem 1rem;
    color: #6c757d;
}

.search-no-results i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.search-no-results p {
    margin: 0;
    font-size: 0.9rem;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .global-search-container {
        max-width: 100%;
    }
    
    .search-results-dropdown {
        left: -1rem;
        right: -1rem;
    }
}
</style>

<script>
$(document).ready(function() {
    let searchTimeout;
    const searchInput = $('#globalSearchInput');
    const searchDropdown = $('#searchResultsDropdown');
    const searchLoading = $('#searchLoading');
    const searchResultsList = $('#searchResultsList');
    const searchNoResults = $('#searchNoResults');

    // Search input handler
    searchInput.on('input', function() {
        const query = $(this).val().trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length === 0) {
            hideSearchResults();
            return;
        }
        
        if (query.length < 2) {
            return;
        }
        
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });

    // Hide search results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.global-search-container').length) {
            hideSearchResults();
        }
    });

    // Show search results when focusing on input
    searchInput.on('focus', function() {
        const query = $(this).val().trim();
        if (query.length >= 2) {
            searchDropdown.show();
        }
    });

    function performSearch(query) {
        showSearchLoading();
        
        $.ajax({
            url: 'api/global_search.php',
            method: 'GET',
            data: { q: query },
            dataType: 'json',
            success: function(response) {
                hideSearchLoading();
                displaySearchResults(response.results || []);
            },
            error: function() {
                hideSearchLoading();
                displaySearchResults([]);
            }
        });
    }

    function showSearchLoading() {
        searchDropdown.show();
        searchLoading.show();
        searchResultsList.empty();
        searchNoResults.hide();
    }

    function hideSearchLoading() {
        searchLoading.hide();
    }

    function displaySearchResults(results) {
        searchResultsList.empty();
        
        if (results.length === 0) {
            searchNoResults.show();
            return;
        }
        
        searchNoResults.hide();
        
        results.forEach(result => {
            const resultItem = $(`
                <a href="${result.url}" class="search-result-item">
                    <div class="search-result-icon ${result.type}">
                        <i class="${result.icon}"></i>
                    </div>
                    <div class="search-result-content">
                        <div class="search-result-title">${result.title}</div>
                        <div class="search-result-subtitle">${result.subtitle}</div>
                    </div>
                    <span class="search-result-type">${result.type}</span>
                </a>
            `);
            
            searchResultsList.append(resultItem);
        });
    }

    function hideSearchResults() {
        searchDropdown.hide();
    }
});
</script>
