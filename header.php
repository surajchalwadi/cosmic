 <?php
// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['user']['role'] ?? '';
$name = $_SESSION['user']['name'] ?? 'User';
?>

<!-- Enhanced Topbar with Global Search -->
<div class="topbar">
    <div class="d-flex align-items-center">
        <button class="btn btn-link d-md-none me-2" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <h5 class="mb-0">Welcome, <?= ucfirst($role) ?> - <?= htmlspecialchars($name) ?></h5>
    </div>
    
    <div class="d-flex align-items-center gap-3">
        <!-- Global Search -->
        <?php include 'components/global_search.php'; ?>
        
        <!-- Admin Notifications (only for admin role) -->
        <?php if ($role === 'admin'): ?>
        <div class="dropdown notification-dropdown">
            <button class="btn btn-outline-primary position-relative" type="button" data-bs-toggle="dropdown" id="notificationBtn">
                <i class="fas fa-bell"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display: none;">
                    0
                </span>
            </button>
            <div class="dropdown-menu dropdown-menu-end notification-menu" style="width: 380px; max-height: 500px; overflow-y: auto;">
                <div class="dropdown-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Admin Notifications</h6>
                    <button class="btn btn-sm btn-outline-secondary" onclick="markAllAsRead()">
                        <i class="fas fa-check-double"></i> Mark All Read
                    </button>
                </div>
                <div class="dropdown-divider"></div>
                <div id="notificationsList">
                    <div class="text-center p-3">
                        <i class="fas fa-spinner fa-spin"></i> Loading...
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- User Actions -->
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

<style>
.topbar {
    background: white;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

/* Notification Styles */
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
    justify-content: between;
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

@media (max-width: 768px) {
    .topbar {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .topbar > div {
        justify-content: center;
    }
    
    .notification-menu {
        width: 320px !important;
    }
}
</style>