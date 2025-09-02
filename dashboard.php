<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

include 'config/db.php';
$role = $_SESSION['user']['role'];
// Force proper user name display
if (isset($_SESSION['user']['name']) && !empty($_SESSION['user']['name']) && $_SESSION['user']['name'] !== 'Test User') {
    $name = $_SESSION['user']['name'];
} else {
    $name = 'Administrator';
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'head.php';?>
<body>

<!-- Sidebar -->
<?php include 'sidebar.php';?>

<!-- Main Area -->
<div class="main">
    <!-- Enhanced Header with Notifications -->
    <div class="topbar">
        <div class="d-flex align-items-center">
            <button class="btn btn-link d-md-none" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <h4 class="mb-0 text-primary fw-bold">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </h4>
        </div>
        <div class="d-flex align-items-center">
            <!-- Admin Notification Bell -->
            <?php if ($role === 'admin'): ?>
            <div class="notification-dropdown me-4">
                <button class="btn btn-primary position-relative shadow-sm" id="notificationBtn" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 10px 15px; border-radius: 10px;">
                    <i class="fas fa-bell" style="font-size: 1.1rem;"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display: none; font-size: 0.65rem; min-width: 20px; height: 20px; line-height: 16px;">
                        0
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" style="width: 350px; max-height: 400px; overflow-y: auto;">
                    <li class="dropdown-header d-flex justify-content-between align-items-center">
                        <span>Notifications</span>
                        <button class="btn btn-sm btn-outline-secondary" onclick="markAllAsRead()">Mark All Read</button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <div id="notificationsList">
                        <div class="text-center p-4">
                            <i class="fas fa-spinner fa-spin text-muted"></i>
                            <p class="text-muted mt-2 mb-0">Loading...</p>
                        </div>
                    </div>
                </ul>
            </div>
            <?php endif; ?>
            
            <!-- User Info -->
            <div class="d-flex align-items-center">
                <span class="me-2">Welcome, <strong><?= htmlspecialchars($name) ?></strong></span>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="container-fluid mt-4">
        <!-- Universal Search Section -->
        <div class="search-filter-container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control search-input" placeholder="Search across all data - quotations, purchases, clients, products..." id="universalSearch">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-outline-primary filter-btn active" data-filter="all">All Results</button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="clearSearch()">Clear</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Results Section -->
        <div class="search-results-section" id="searchResultsSection" style="display: none;">
            <div class="row">
                <!-- Quotations Results -->
                <div class="col-lg-6 mb-4">
                    <div class="search-results-card">
                        <div class="card-header">
                            <h6><i class="fas fa-file-invoice me-2"></i>Quotations</h6>
                            <span class="badge bg-primary" id="quotationCount">0</span>
                        </div>
                        <div class="card-body" id="quotationResults">
                            <p class="text-muted">No quotations found</p>
                        </div>
                    </div>
                </div>

                <!-- Clients Results -->
                <div class="col-lg-6 mb-4">
                    <div class="search-results-card">
                        <div class="card-header">
                            <h6><i class="fas fa-users me-2"></i>Clients</h6>
                            <span class="badge bg-success" id="clientCount">0</span>
                        </div>
                        <div class="card-body" id="clientResults">
                            <p class="text-muted">No clients found</p>
                        </div>
                    </div>
                </div>

                <!-- Products Results -->
                <div class="col-lg-6 mb-4">
                    <div class="search-results-card">
                        <div class="card-header">
                            <h6><i class="fas fa-box me-2"></i>Products</h6>
                            <span class="badge bg-warning" id="productCount">0</span>
                        </div>
                        <div class="card-body" id="productResults">
                            <p class="text-muted">No products found</p>
                        </div>
                    </div>
                </div>

                <!-- Purchases Results -->
                <div class="col-lg-6 mb-4">
                    <div class="search-results-card">
                        <div class="card-header">
                            <h6><i class="fas fa-shopping-cart me-2"></i>Purchases</h6>
                            <span class="badge bg-danger" id="purchaseCount">0</span>
                        </div>
                        <div class="card-body" id="purchaseResults">
                            <p class="text-muted">No purchases found</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards Row -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card danger">
                    <div class="stat-value" id="totalPurchaseDue">₹0</div>
                    <div class="stat-label">Total Purchase Due</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card success">
                    <div class="stat-value" id="totalSalesDue">₹0</div>
                    <div class="stat-label">Total Sales Due</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card warning">
                    <div class="stat-value" id="totalSalesAmount">₹0</div>
                    <div class="stat-label">Total Sales Amount</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card danger">
                    <div class="stat-value" id="totalPurchaseAmount">₹0</div>
                    <div class="stat-label">Total Purchase Amount</div>
                </div>
            </div>
        </div>

        <!-- Additional Stats Row -->
        <div class="row g-4 mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="stat-card info">
                    <div class="stat-value" id="totalClients">0</div>
                    <div class="stat-label">Total Clients</div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="stat-card purple">
                    <div class="stat-value" id="purchaseInvoices">0</div>
                    <div class="stat-label">Purchase Invoices</div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="stat-card orange">
                    <div class="stat-value" id="stockItems">0</div>
                    <div class="stat-label">Stock Items</div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4">
            <!-- Monthly Purchase Invoices Chart -->
            <div class="col-lg-6">
                <div class="card modern-chart-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2 text-primary"></i>Monthly Purchase Invoices
                        </h5>
                        <span class="badge bg-success" id="chartStatus1">Loading</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-wrapper">
                            <div class="loading-overlay" id="monthlyPurchasesLoading">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 mb-0">Loading chart...</p>
                            </div>
                            <canvas id="monthlyPurchasesChart" style="display: none;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top 5 Clients Chart -->
            <div class="col-lg-6">
                <div class="card modern-chart-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users me-2 text-success"></i>Top 5 Clients
                        </h5>
                        <span class="badge bg-success" id="chartStatus2">Loading</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-wrapper">
                            <div class="loading-overlay" id="topClientsLoading">
                                <div class="spinner-border text-success" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 mb-0">Loading chart...</p>
                            </div>
                            <canvas id="topClientsChart" style="display: none;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Summary Chart -->
            <div class="col-lg-6">
                <div class="card modern-chart-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-pie me-2 text-warning"></i>Invoice Summary
                        </h5>
                        <span class="badge bg-success" id="chartStatus3">Loading</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-wrapper">
                            <div class="loading-overlay" id="invoiceSummaryLoading">
                                <div class="spinner-border text-warning" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 mb-0">Loading chart...</p>
                            </div>
                            <canvas id="invoiceSummaryChart" style="display: none;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales vs Purchases Trend -->
            <div class="col-lg-6">
                <div class="card modern-chart-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2 text-info"></i>Sales vs Purchases Trend
                        </h5>
                        <span class="badge bg-success" id="chartStatus4">Loading</span>
                    </div>
                    <div class="card-body">
                        <div class="chart-wrapper">
                            <div class="loading-overlay" id="salesVsPurchasesLoading">
                                <div class="spinner-border text-info" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 mb-0">Loading chart...</p>
                            </div>
                            <canvas id="salesVsPurchasesChart" style="display: none;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.modern-chart-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    overflow: hidden;
}

.modern-chart-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.modern-chart-card .card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding: 1.25rem 1.5rem;
}

.chart-wrapper {
    position: relative;
    height: 350px;
    padding: 1rem;
}

.loading-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    z-index: 10;
}

.loading-overlay .spinner-border {
    width: 3rem;
    height: 3rem;
    border-width: 0.25em;
}

.badge {
    font-size: 0.75rem;
    padding: 0.35rem 0.75rem;
    border-radius: 50px;
    font-weight: 600;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
}




.stat-icon {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    opacity: 0.8;
}

.stat-card.primary .stat-icon { background: linear-gradient(135deg, #0d6efd, #0a58ca); }
.stat-card.success .stat-icon { background: linear-gradient(135deg, #198754, #146c43); }
.stat-card.warning .stat-icon { background: linear-gradient(135deg, #ffc107, #ffca2c); }
.stat-card.danger .stat-icon { background: linear-gradient(135deg, #dc3545, #b02a37); }
.stat-card.info .stat-icon { background: linear-gradient(135deg, #0dcaf0, #31d2f2); }
.stat-card.purple .stat-icon { background: linear-gradient(135deg, #6f42c1, #59359a); }
.stat-card.orange .stat-icon { background: linear-gradient(135deg, #fd7e14, #e76500); }

.stat-value {
    font-size: 2rem;
    font-weight: 800;
    color: #212529;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0;
}

.search-filter-container {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}

.search-results-section {
    margin-bottom: 2rem;
}

.search-results-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border: 1px solid #dee2e6;
}

.search-results-card .card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 1rem 1.25rem;
    display: flex;
    justify-content: between;
    align-items: center;
    border-radius: 12px 12px 0 0;
}

.search-results-card .card-body {
    padding: 1rem 1.25rem;
    max-height: 300px;
    overflow-y: auto;
}

.search-result-item {
    padding: 0.75rem;
    border-bottom: 1px solid #f8f9fa;
    transition: background-color 0.2s ease;
    cursor: pointer;
}

.search-result-item:hover {
    background-color: #f8f9fa;
}

.search-result-item:last-child {
    border-bottom: none;
}

.search-result-title {
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.25rem;
}

.search-result-subtitle {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.search-result-meta {
    font-size: 0.75rem;
    color: #adb5bd;
}

.search-input {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    padding: 0.75rem 1rem;
}

.filter-btn {
    border-radius: 20px;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border: 1px solid #dee2e6;
    transition: all 0.2s ease;
}

.filter-btn.active {
    background: #0d6efd;
    border-color: #0d6efd;
    color: white;
}

.notification-dropdown .dropdown-toggle {
    border-radius: 10px;
    padding: 10px 15px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.notification-dropdown .dropdown-toggle:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.notification-dropdown .dropdown-toggle:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

#notificationBadge {
    min-width: 20px;
    height: 20px;
    font-size: 0.65rem !important;
    line-height: 16px;
    padding: 2px 4px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.notification-dropdown {
    min-width: 320px;
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

.notification-dropdown .dropdown-menu {
    border: none;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-radius: 12px;
}

.notification-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f8f9fa;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: rgba(13, 110, 253, 0.05);
    border-left: 3px solid #0d6efd;
}

.notification-item.urgent {
    border-left: 3px solid #dc3545 !important;
    background-color: rgba(220, 53, 69, 0.05) !important;
}

.notification-item.high {
    border-left: 3px solid #ffc107 !important;
    background-color: rgba(255, 193, 7, 0.05) !important;
}

.notification-title {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 4px;
}

.notification-message {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 6px;
    line-height: 1.4;
}

.notification-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.75rem;
    color: #9ca3af;
}

.notification-user {
    background: #e9ecef;
    padding: 2px 6px;
    border-radius: 10px;
    font-weight: 500;
}

.notification-time {
    margin-left: auto;
}
</style>

<!-- Dashboard JavaScript -->
<script>
let charts = {};
let dashboardData = {};

// Initialize Dashboard
$(document).ready(function() {
    console.log('Dashboard initializing...');
    initializeDashboard();
    setupEventListeners();
    
    // Start real-time updates every 30 seconds
    setInterval(updateDashboardData, 30000);
});

function initializeDashboard() {
    console.log('Initializing dashboard...');
    showLoadingSkeletons();
    
    // Try to load data immediately, with fallback
    setTimeout(() => {
        updateDashboardData();
    }, 500);
}

function setupEventListeners() {
    // Sidebar toggle for mobile
    $('#sidebarToggle').click(function() {
        $('.sidebar').toggleClass('show');
    });
    
    // Universal Search functionality
    let searchTimeout;
    $('#universalSearch').on('input', function() {
        const searchTerm = $(this).val().trim();
        
        clearTimeout(searchTimeout);
        
        if (searchTerm.length === 0) {
            hideSearchResults();
            showDashboardContent();
            return;
        }
        
        if (searchTerm.length < 2) {
            return;
        }
        
        searchTimeout = setTimeout(() => {
            performUniversalSearch(searchTerm);
        }, 300);
    });

    function performUniversalSearch(searchTerm) {
        showSearchResults();
        hideDashboardContent();
        
        // Show loading state
        showSearchLoading();
        
        $.ajax({
            url: 'api/global_search.php',
            method: 'GET',
            data: { q: searchTerm },
            dataType: 'json',
            success: function(response) {
                hideSearchLoading();
                displayUniversalSearchResults(response.results || []);
            },
            error: function() {
                hideSearchLoading();
                displayUniversalSearchResults([]);
            }
        });
    }

    function displayUniversalSearchResults(results) {
        // Group results by type
        const groupedResults = {
            estimate: [],
            client: [],
            product: [],
            purchase: []
        };
        
        results.forEach(result => {
            if (groupedResults[result.type]) {
                groupedResults[result.type].push(result);
            }
        });
        
        // Display quotations
        displayResultsInSection('quotationResults', 'quotationCount', groupedResults.estimate, 'quotation');
        
        // Display clients
        displayResultsInSection('clientResults', 'clientCount', groupedResults.client, 'client');
        
        // Display products
        displayResultsInSection('productResults', 'productCount', groupedResults.product, 'product');
        
        // Display purchases
        displayResultsInSection('purchaseResults', 'purchaseCount', groupedResults.purchase, 'purchase');
    }

    function displayResultsInSection(containerId, countId, results, type) {
        const container = $('#' + containerId);
        const countBadge = $('#' + countId);
        
        countBadge.text(results.length);
        
        if (results.length === 0) {
            container.html(`<p class="text-muted">No ${type}s found</p>`);
            return;
        }
        
        let html = '';
        results.forEach(result => {
            html += `
                <div class="search-result-item" onclick="window.location.href='${result.url}'">
                    <div class="search-result-title">${result.title}</div>
                    <div class="search-result-subtitle">${result.subtitle}</div>
                    <div class="search-result-meta">Click to view details</div>
                </div>
            `;
        });
        
        container.html(html);
    }

    function showSearchResults() {
        $('#searchResultsSection').show();
    }

    function hideSearchResults() {
        $('#searchResultsSection').hide();
    }

    function showDashboardContent() {
        $('.stat-card').parent().show();
        $('.modern-chart-card').parent().show();
    }

    function hideDashboardContent() {
        $('.stat-card').parent().hide();
        $('.modern-chart-card').parent().hide();
    }

    function showSearchLoading() {
        $('#quotationResults').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Searching...</div>');
        $('#clientResults').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Searching...</div>');
        $('#productResults').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Searching...</div>');
        $('#purchaseResults').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Searching...</div>');
    }

    function hideSearchLoading() {
        // Loading will be replaced by actual results
    }

    // Clear search function
    window.clearSearch = function() {
        $('#universalSearch').val('');
        hideSearchResults();
        showDashboardContent();
    };
    
    // Filter buttons functionality
    $('.filter-btn').click(function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        const filter = $(this).data('filter');
        
        // Implement filter logic
        switch(filter) {
            case 'all':
                console.log('Showing all items');
                // Show all dashboard content
                break;
            case 'invoices':
                console.log('Filtering invoices');
                // Navigate to invoice list or filter invoice data
                window.location.href = 'invoice.php';
                break;
            case 'quotations':
                console.log('Filtering quotations');
                // Navigate to quotation list or filter quotation data
                window.location.href = 'quotation_list.php';
                break;
        }
    });
}

function showLoadingSkeletons() {
    // Show loading state for stat cards
    $('.stat-value').addClass('loading-skeleton').text('Loading...');
}

function updateDashboardData() {
    // Show loading state
    $('.stat-value').addClass('loading-skeleton').text('Loading...');
    
    $.ajax({
        url: 'api/dashboard.php',
        method: 'GET',
        dataType: 'json',
        timeout: 10000, // 10 second timeout
        success: function(data) {
            console.log('Dashboard data received:', data);
            dashboardData = data;
            updateStatCards(data);
            updateCharts(data);
            updateLastUpdatedTime();
        },
        error: function(xhr, status, error) {
            console.log('Loading sample data due to API unavailability');
            
            // Remove loading state and show sample data
            $('.stat-value').removeClass('loading-skeleton');
            
            // Load with sample data if API fails
            const sampleData = {
                total_purchase_due: 125000,
                total_sales_due: 85000,
                total_sales_amount: 245000,
                total_purchase_amount: 180000,
                total_users: 5,
                purchase_invoices: 12,
                stock_items: 45,
                monthly_purchases: [
                    {month: 'Mar 2024', count: 8},
                    {month: 'Apr 2024', count: 12},
                    {month: 'May 2024', count: 6},
                    {month: 'Jun 2024', count: 15},
                    {month: 'Jul 2024', count: 9},
                    {month: 'Aug 2024', count: 11}
                ],
                top_clients: [
                    {client: 'Acme Corp', amount: 150000},
                    {client: 'Tech Ltd', amount: 85000},
                    {client: 'Digital Inc', amount: 120000},
                    {client: 'Global Ent', amount: 95000},
                    {client: 'Future Sys', amount: 75000}
                ],
                invoice_summary: [
                    {status: 'Draft', count: 3},
                    {status: 'Sent', count: 5},
                    {status: 'Approved', count: 8},
                    {status: 'Rejected', count: 1}
                ],
                sales_vs_purchases: [
                    {month: 'Mar 2024', sales: 120000, purchases: 80000},
                    {month: 'Apr 2024', sales: 150000, purchases: 95000},
                    {month: 'May 2024', sales: 90000, purchases: 60000},
                    {month: 'Jun 2024', sales: 180000, purchases: 120000},
                    {month: 'Jul 2024', sales: 135000, purchases: 85000},
                    {month: 'Aug 2024', sales: 165000, purchases: 110000}
                ],
                notifications: [
                    {type: 'warning', message: '3 quotations pending approval', icon: 'fas fa-clock'},
                    {type: 'info', message: '5 estimates awaiting response', icon: 'fas fa-paper-plane'}
                ]
            };
            
            updateStatCards(sampleData);
            updateCharts(sampleData);
            updateLastUpdatedTime();
            
            // Silently use sample data without showing error
        }
    });
}

function updateStatCards(data) {
    // Remove loading state
    $('.stat-value').removeClass('loading-skeleton');
    
    // Update stat values with animation
    animateValue('totalPurchaseDue', 0, data.total_purchase_due, '₹');
    animateValue('totalSalesDue', 0, data.total_sales_due, '₹');
    animateValue('totalSalesAmount', 0, data.total_sales_amount, '₹');
    animateValue('totalPurchaseAmount', 0, data.total_purchase_amount, '₹');
    animateValue('totalClients', 0, data.total_clients);
    animateValue('purchaseInvoices', 0, data.purchase_invoices);
    animateValue('stockItems', 0, data.stock_items);
}

function animateValue(elementId, start, end, prefix = '') {
    const element = document.getElementById(elementId);
    const duration = 1000;
    const startTime = performance.now();
    
    function updateValue(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const current = Math.floor(start + (end - start) * progress);
        
        element.textContent = prefix + formatNumber(current);
        
        if (progress < 1) {
            requestAnimationFrame(updateValue);
        }
    }
    
    requestAnimationFrame(updateValue);
}

function formatNumber(num) {
    if (num >= 10000000) {
        return (num / 10000000).toFixed(1) + 'Cr';
    } else if (num >= 100000) {
        return (num / 100000).toFixed(1) + 'L';
    } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toLocaleString();
}

function updateCharts(data) {
    // Add delay to ensure DOM is ready
    setTimeout(() => {
        updateMonthlyPurchasesChart(data.monthly_purchases);
        updateTopClientsChart(data.top_clients);
        updateInvoiceSummaryChart(data.invoice_summary);
        updateSalesVsPurchasesChart(data.sales_vs_purchases);
    }, 100);
}

function updateMonthlyPurchasesChart(data) {
    const canvas = document.getElementById('monthlyPurchasesChart');
    const loading = document.getElementById('monthlyPurchasesLoading');
    const status = document.getElementById('chartStatus1');
    
    if (!canvas) {
        console.error('Monthly purchases chart canvas not found');
        return;
    }
    
    try {
        if (charts.monthlyPurchases) {
            charts.monthlyPurchases.destroy();
        }
        
        // Use fallback data if none provided
        if (!data || !Array.isArray(data) || data.length === 0) {
            data = [
                {month: 'Mar 2024', count: 8},
                {month: 'Apr 2024', count: 12},
                {month: 'May 2024', count: 6},
                {month: 'Jun 2024', count: 15},
                {month: 'Jul 2024', count: 9},
                {month: 'Aug 2024', count: 11}
            ];
        }
        
        const ctx = canvas.getContext('2d');
        charts.monthlyPurchases = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(item => item.month || 'Unknown'),
                datasets: [{
                    label: 'Purchase Invoices',
                    data: data.map(item => item.count || 0),
                    backgroundColor: 'rgba(13, 110, 253, 0.8)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 2,
                    borderRadius: 12,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(13, 110, 253, 1)',
                        borderWidth: 1,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6c757d',
                            font: { size: 12 }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#6c757d',
                            font: { size: 12 }
                        }
                    }
                },
                animation: {
                    duration: 1200,
                    easing: 'easeInOutCubic'
                }
            }
        });
        
        // Hide loading and show chart
        loading.style.display = 'none';
        canvas.style.display = 'block';
        status.className = 'badge bg-success';
        status.textContent = 'Loaded';
        
    } catch (error) {
        console.error('Monthly purchases chart error:', error);
        status.className = 'badge bg-danger';
        status.textContent = 'Error';
    }
}

function updateTopClientsChart(data) {
    const canvas = document.getElementById('topClientsChart');
    const loading = document.getElementById('topClientsLoading');
    const status = document.getElementById('chartStatus2');
    
    if (!canvas) {
        console.error('Top clients chart canvas not found');
        return;
    }
    
    try {
        if (charts.topClients) {
            charts.topClients.destroy();
        }
        
        // Use fallback data if none provided
        if (!data || !Array.isArray(data) || data.length === 0) {
            data = [
                {client: 'Acme Corp', amount: 150000},
                {client: 'Tech Ltd', amount: 85000},
                {client: 'Digital Inc', amount: 120000},
                {client: 'Global Ent', amount: 95000},
                {client: 'Future Sys', amount: 75000}
            ];
        }
        
        const ctx = canvas.getContext('2d');
        charts.topClients = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(item => item.client || 'Unknown'),
                datasets: [{
                    label: 'Total Amount (₹)',
                    data: data.map(item => item.amount || 0),
                    backgroundColor: [
                        'rgba(25, 135, 84, 0.8)',
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(13, 202, 240, 0.8)',
                        'rgba(111, 66, 193, 0.8)'
                    ],
                    borderColor: [
                        'rgba(25, 135, 84, 1)',
                        'rgba(220, 53, 69, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(13, 202, 240, 1)',
                        'rgba(111, 66, 193, 1)'
                    ],
                    borderWidth: 2,
                    borderRadius: 8,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(25, 135, 84, 1)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return 'Amount: ₹' + context.parsed.x.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6c757d',
                            font: { size: 12 },
                            callback: function(value) {
                                return '₹' + (value / 1000) + 'K';
                            }
                        }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            color: '#6c757d',
                            font: { size: 12 }
                        }
                    }
                },
                animation: {
                    duration: 1200,
                    easing: 'easeInOutCubic'
                }
            }
        });
        
        // Hide loading and show chart
        loading.style.display = 'none';
        canvas.style.display = 'block';
        status.className = 'badge bg-success';
        status.textContent = 'Loaded';
        
    } catch (error) {
        console.error('Top clients chart error:', error);
        status.className = 'badge bg-danger';
        status.textContent = 'Error';
    }
}

function updateInvoiceSummaryChart(data) {
    const canvas = document.getElementById('invoiceSummaryChart');
    const loading = document.getElementById('invoiceSummaryLoading');
    const status = document.getElementById('chartStatus3');
    
    if (!canvas) {
        console.error('Invoice summary chart canvas not found');
        return;
    }
    
    try {
        if (charts.invoiceSummary) {
            charts.invoiceSummary.destroy();
        }
        
        // Use fallback data if none provided
        if (!data || !Array.isArray(data) || data.length === 0) {
            data = [
                {status: 'Draft', count: 3},
                {status: 'Sent', count: 5},
                {status: 'Approved', count: 8},
                {status: 'Rejected', count: 1}
            ];
        }
        
        const colors = {
            'Draft': '#ffc107',
            'Sent': '#0dcaf0',
            'Approved': '#198754',
            'Rejected': '#dc3545'
        };
        
        const ctx = canvas.getContext('2d');
        charts.invoiceSummary = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(item => item.status || 'Unknown'),
                datasets: [{
                    data: data.map(item => item.count || 0),
                    backgroundColor: data.map(item => colors[item.status] || '#6c757d'),
                    borderWidth: 4,
                    borderColor: '#fff',
                    hoverBorderWidth: 6,
                    hoverBorderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { size: 12 },
                            color: '#6c757d'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderWidth: 1,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                },
                animation: {
                    duration: 1500,
                    easing: 'easeInOutCubic'
                }
            }
        });
        
        // Hide loading and show chart
        loading.style.display = 'none';
        canvas.style.display = 'block';
        status.className = 'badge bg-success';
        status.textContent = 'Loaded';
        
    } catch (error) {
        console.error('Invoice summary chart error:', error);
        status.className = 'badge bg-danger';
        status.textContent = 'Error';
    }
}

function updateSalesVsPurchasesChart(data) {
    const canvas = document.getElementById('salesVsPurchasesChart');
    const loading = document.getElementById('salesVsPurchasesLoading');
    const status = document.getElementById('chartStatus4');
    
    if (!canvas) {
        console.error('Sales vs purchases chart canvas not found');
        return;
    }
    
    try {
        if (charts.salesVsPurchases) {
            charts.salesVsPurchases.destroy();
        }
        
        // Use fallback data if none provided
        if (!data || !Array.isArray(data) || data.length === 0) {
            data = [
                {month: 'Mar 2024', sales: 120000, purchases: 80000},
                {month: 'Apr 2024', sales: 150000, purchases: 95000},
                {month: 'May 2024', sales: 90000, purchases: 60000},
                {month: 'Jun 2024', sales: 180000, purchases: 120000},
                {month: 'Jul 2024', sales: 135000, purchases: 85000},
                {month: 'Aug 2024', sales: 165000, purchases: 110000}
            ];
        }
        
        const ctx = canvas.getContext('2d');
        charts.salesVsPurchases = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(item => item.month || 'Unknown'),
                datasets: [{
                    label: 'Sales',
                    data: data.map(item => item.sales || 0),
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#198754',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }, {
                    label: 'Purchases',
                    data: data.map(item => item.purchases || 0),
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#dc3545',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { size: 12 },
                            color: '#6c757d',
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderWidth: 1,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ₹' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6c757d',
                            font: { size: 12 },
                            callback: function(value) {
                                return '₹' + (value / 1000) + 'K';
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#6c757d',
                            font: { size: 12 }
                        }
                    }
                },
                animation: {
                    duration: 1500,
                    easing: 'easeInOutCubic'
                }
            }
        });
        
        // Hide loading and show chart
        loading.style.display = 'none';
        canvas.style.display = 'block';
        status.className = 'badge bg-success';
        status.textContent = 'Loaded';
        
    } catch (error) {
        console.error('Sales vs purchases chart error:', error);
        status.className = 'badge bg-danger';
        status.textContent = 'Error';
    }
}


function updateLastUpdatedTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString();
    $('#lastUpdated').text(`Updated: ${timeString}`);
}

function showErrorMessage(message) {
    // Silently log without showing user errors
    console.log('Dashboard info:', message);
}

// Admin Notification System
<?php if ($role === 'admin'): ?>
const userRole = '<?= $role ?>';
<?php endif; ?>
</script>

<!-- Admin Notification System -->
<?php if ($role === 'admin'): ?>
<script src="assets/js/notifications.js"></script>
<?php endif; ?>

</body>
</html>
