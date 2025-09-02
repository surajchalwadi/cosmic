let notificationInterval;
let currentNotificationCount = 0;
let shownNotifications = new Set();

$(document).ready(function() {
    // Only initialize for admin users
    if (typeof userRole !== 'undefined' && userRole === 'admin') {
        initializeNotifications();
        startNotificationPolling();
        createNotificationContainer();
    }
});

/**
 * Initialize notification system
 */
function initializeNotifications() {
    loadNotifications();
    
    // Refresh notifications when dropdown is opened
    $('#notificationBtn').on('click', function() {
        loadNotifications();
    });
}

/**
 * Start polling for new notifications every 30 seconds
 */
function startNotificationPolling() {
    notificationInterval = setInterval(function() {
        loadNotifications(true); // Silent refresh
    }, 30000);
}

/**
 * Create notification container
 */
function createNotificationContainer() {
    if ($('#notification-container').length === 0) {
        $('body').append(`
            <div id="notification-container" style="
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
            "></div>
        `);
    }
}

/**
 * Load notifications from API
 */
function loadNotifications(silent = false) {
    $.ajax({
        url: 'api/notifications.php?action=get',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                updateNotificationBadge(response.unread_count);
                displayNotifications(response.notifications);
                
                // Show new notification alert if count increased
                if (!silent && response.unread_count > currentNotificationCount) {
                    showNewNotificationAlert(response.unread_count - currentNotificationCount);
                }
                currentNotificationCount = response.unread_count;
            }
        },
        error: function() {
            if (!silent) {
                console.error('Failed to load notifications');
            }
        }
    });
}

/**
 * Update notification badge
 */
function updateNotificationBadge(count) {
    const badge = $('#notificationBadge');
    if (count > 0) {
        badge.text(count > 99 ? '99+' : count);
        badge.show();
        
        // Add pulse animation for new notifications
        if (count > currentNotificationCount) {
            badge.addClass('animate__animated animate__pulse');
            setTimeout(() => badge.removeClass('animate__animated animate__pulse'), 1000);
        }
    } else {
        badge.hide();
    }
}

/**
 * Display notifications in dropdown
 */
function displayNotifications(notifications) {
    const container = $('#notificationsList');
    
    if (notifications.length === 0) {
        container.html(`
            <div class="text-center p-4">
                <i class="fas fa-bell-slash text-muted" style="font-size: 2rem;"></i>
                <p class="text-muted mt-2 mb-0">No notifications</p>
            </div>
        `);
        return;
    }
    
    let html = '';
    notifications.forEach(notification => {
        const priorityClass = notification.priority === 'urgent' ? 'urgent' : 
                            notification.priority === 'high' ? 'high' : '';
        const unreadClass = !notification.is_read ? 'unread' : '';
        
        html += `
            <div class="notification-item ${unreadClass} ${priorityClass}" 
                 onclick="markAsRead(${notification.id})" 
                 data-notification-id="${notification.id}">
                <div class="d-flex align-items-start">
                    <div class="me-3">
                        <i class="${notification.icon} ${notification.color}" style="font-size: 1.1rem;"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="notification-title">${notification.title}</div>
                        <div class="notification-message">${notification.message}</div>
                        <div class="notification-meta">
                            <span class="notification-user">${notification.user_role}</span>
                            <span class="notification-time">${notification.time_ago}</span>
                        </div>
                    </div>
                    ${!notification.is_read ? '<div class="ms-2"><i class="fas fa-circle text-primary" style="font-size: 0.5rem;"></i></div>' : ''}
                </div>
            </div>
        `;
    });
    
    container.html(html);
}

/**
 * Mark notification as read
 */
function markAsRead(notificationId) {
    $.ajax({
        url: 'api/notifications.php?action=mark_read',
        method: 'POST',
        data: {
            notification_id: notificationId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update UI
                const item = $(`.notification-item[data-notification-id="${notificationId}"]`);
                item.removeClass('unread');
                item.find('.fa-circle').remove();
                
                // Update badge
                const currentBadge = parseInt($('#notificationBadge').text()) || 0;
                updateNotificationBadge(Math.max(0, currentBadge - 1));
                
                // Reload notifications to refresh the list
                loadNotifications(true);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error marking notification as read:', error);
        }
    });
}

/**
 * Mark all notifications as read
 */
function markAllAsRead() {
    $.ajax({
        url: 'api/notifications.php?action=mark_all_read',
        method: 'POST',
        data: {},
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update UI
                $('.notification-item').removeClass('unread');
                $('.notification-item .fa-circle').remove();
                updateNotificationBadge(0);
                
                // Reload notifications to refresh the list
                loadNotifications(true);
                
                // Show success message
                showNotificationToast('All notifications marked as read', 'success');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error marking all notifications as read:', error);
        }
    });
}

/**
 * Show new notification alert
 */
function showNewNotificationAlert(newCount) {
    // Play notification sound (optional)
    // new Audio('assets/sounds/notification.mp3').play().catch(() => {});
    
    // Show toast notification
    showNotificationToast(`${newCount} new notification${newCount > 1 ? 's' : ''}`, 'info');
    
    // Animate notification bell
    $('#notificationBtn').addClass('animate__animated animate__swing');
    setTimeout(() => {
        $('#notificationBtn').removeClass('animate__animated animate__swing');
    }, 1000);
}

/**
 * Show toast notification
 */
function showNotificationToast(message, type = 'info') {
    const toastId = 'toast-' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : 
                   type === 'error' ? 'bg-danger' : 'bg-info';
    
    const toast = $(`
        <div class="toast align-items-center text-white ${bgClass} border-0" id="${toastId}" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-bell me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);
    
    // Add to toast container or create one
    let toastContainer = $('#toast-container');
    if (toastContainer.length === 0) {
        toastContainer = $('<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>');
        $('body').append(toastContainer);
    }
    
    toastContainer.append(toast);
    
    // Initialize and show toast
    const bsToast = new bootstrap.Toast(toast[0]);
    bsToast.show();
    
    // Remove from DOM after hiding
    toast.on('hidden.bs.toast', function() {
        $(this).remove();
    });
}

/**
 * Cleanup function
 */
function cleanupNotifications() {
    if (notificationInterval) {
        clearInterval(notificationInterval);
    }
}

// Cleanup on page unload
$(window).on('beforeunload', cleanupNotifications);

// Global notification functions
window.notificationSystem = {
    load: loadNotifications,
    markRead: markAsRead,
    markAllRead: markAllAsRead,
    cleanup: cleanupNotifications
};

/**
 * Show notification popup
 */
function showNotificationPopup(notification) {
    const priorityColors = {
        'urgent': 'danger',
        'high': 'warning', 
        'medium': 'primary',
        'low': 'secondary'
    };
    
    const alertClass = priorityColors[notification.priority] || 'primary';
    const notificationId = 'notification-' + notification.id;
    
    const popup = $(`
        <div class="alert alert-${alertClass} alert-dismissible fade show shadow-lg" id="${notificationId}" style="
            margin-bottom: 10px;
            border-radius: 10px;
            border: none;
            animation: slideInRight 0.5s ease-out;
        ">
            <div class="d-flex align-items-start">
                <div class="me-3">
                    <i class="${notification.icon}" style="font-size: 1.2rem;"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="alert-heading mb-1" style="font-size: 0.9rem;">
                        ${notification.title}
                        <small class="badge bg-light text-dark ms-2">${notification.user_role}</small>
                    </h6>
                    <p class="mb-1" style="font-size: 0.8rem;">${notification.message}</p>
                    <small class="text-muted">${notification.time_ago}</small>
                </div>
                <button type="button" class="btn-close" onclick="dismissNotification(${notification.id})"></button>
            </div>
        </div>
    `);
    
    $('#notification-container').append(popup);
    
    // Auto-dismiss after 8 seconds for non-urgent notifications
    if (notification.priority !== 'urgent') {
        setTimeout(() => {
            dismissNotification(notification.id);
        }, 8000);
    }
}

/**
 * Dismiss notification popup
 */
function dismissNotification(notificationId) {
    const popup = $(`#notification-${notificationId}`);
    
    // Mark as read in backend
    $.ajax({
        url: 'api/notifications.php',
        method: 'POST',
        data: {
            action: 'mark_read',
            notification_id: notificationId
        }
    });
    
    // Remove from UI
    popup.fadeOut(300, function() {
        $(this).remove();
    });
}

/**
 * Add slide-in animation CSS
 */
$('head').append(`
<style>
@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>
`);

/**
 * Cleanup function
 */
function cleanupNotifications() {
    if (notificationInterval) {
        clearInterval(notificationInterval);
    }
}

// Cleanup on page unload
$(window).on('beforeunload', cleanupNotifications);
