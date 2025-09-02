<?php
/**
 * Admin Notification System Functions
 * Handles creation and management of admin notifications
 */

/**
 * Create a new admin notification
 */
function createAdminNotification($userId, $userName, $userRole, $actionType, $title, $message, $referenceId = null, $referenceType, $priority = 'medium') {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO admin_notifications (user_id, user_name, user_role, action_type, title, message, reference_id, reference_type, priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssss", $userId, $userName, $userRole, $actionType, $title, $message, $referenceId, $referenceType, $priority);
    
    return $stmt->execute();
}

/**
 * Sales Activity Notifications
 */
function notifyQuotationCreated($userId, $userName, $quotationData) {
    $title = "New Quotation Created";
    $message = "Sales team created quotation {$quotationData['estimate_number']} for {$quotationData['bill_client_name']} worth ₹" . number_format($quotationData['total_amount'], 2);
    
    return createAdminNotification($userId, $userName, 'sales', 'quotation_created', $title, $message, $quotationData['estimate_id'], 'quotation', 'medium');
}

function notifyQuotationApproved($userId, $userName, $quotationData) {
    $title = "Quotation Approved";
    $message = "Sales team got approval for quotation {$quotationData['estimate_number']} from {$quotationData['bill_client_name']} worth ₹" . number_format($quotationData['total_amount'], 2);
    
    return createAdminNotification($userId, $userName, 'sales', 'quotation_approved', $title, $message, $quotationData['estimate_id'], 'quotation', 'high');
}

function notifyInvoiceConverted($userId, $userName, $invoiceData) {
    $title = "Quotation Converted to Invoice";
    $message = "Sales team converted quotation {$invoiceData['estimate_number']} to invoice for {$invoiceData['bill_client_name']} worth ₹" . number_format($invoiceData['total_amount'], 2);
    
    return createAdminNotification($userId, $userName, 'sales', 'invoice_converted', $title, $message, $invoiceData['estimate_id'], 'invoice', 'high');
}

function notifyLargeQuotation($userId, $userName, $quotationData) {
    if ($quotationData['total_amount'] >= 100000) { // ₹1 Lakh or more
        $title = "Large Quotation Created";
        $message = "Sales team created high-value quotation {$quotationData['estimate_number']} for {$quotationData['bill_client_name']} worth ₹" . number_format($quotationData['total_amount'], 2);
        
        return createAdminNotification($userId, $userName, 'sales', 'large_quotation', $title, $message, $quotationData['estimate_id'], 'quotation', 'urgent');
    }
    return true;
}

/**
 * Inventory Activity Notifications
 */
function notifyPurchaseCreated($userId, $userName, $purchaseData, $totalAmount) {
    $title = "New Purchase Added";
    $message = "Inventory team added purchase invoice {$purchaseData['invoice_no']} from {$purchaseData['party_name']} worth ₹" . number_format($totalAmount, 2);
    
    $priority = $totalAmount >= 50000 ? 'high' : 'medium';
    
    return createAdminNotification($userId, $userName, 'inventory', 'purchase_created', $title, $message, $purchaseData['purchase_id'], 'purchase', $priority);
}

function notifyLargePurchase($userId, $userName, $purchaseData, $totalAmount) {
    if ($totalAmount >= 100000) { // ₹1 Lakh or more
        $title = "Large Purchase Alert";
        $message = "Inventory team made high-value purchase {$purchaseData['invoice_no']} from {$purchaseData['party_name']} worth ₹" . number_format($totalAmount, 2);
        
        return createAdminNotification($userId, $userName, 'inventory', 'large_purchase', $title, $message, $purchaseData['purchase_id'], 'purchase', 'urgent');
    }
    return true;
}

function notifyLowStockAlert($userId, $userName, $productData) {
    $title = "Low Stock Alert";
    $message = "Product '{$productData['product_name']}' is running low on stock. Current quantity: {$productData['quantity']}";
    
    return createAdminNotification($userId, $userName, 'inventory', 'low_stock', $title, $message, $productData['product_id'], 'product', 'medium');
}

/**
 * Client Activity Notifications
 */
function notifyNewClientAdded($userId, $userName, $clientData) {
    $title = "New Client Added";
    $message = "Sales team added new client: {$clientData['client_name']} from {$clientData['company']}";
    
    return createAdminNotification($userId, $userName, 'sales', 'client_added', $title, $message, $clientData['client_id'], 'client', 'low');
}

/**
 * Get unread notifications count for admin
 */
function getUnreadNotificationCount() {
    global $conn;
    
    $query = "SELECT COUNT(*) as count FROM admin_notifications WHERE is_read = FALSE";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row['count'] ?? 0;
    }
    
    return 0;
}

/**
 * Get recent notifications for admin
 */
function getRecentNotifications($limit = 10) {
    global $conn;
    
    $query = "SELECT * FROM admin_notifications ORDER BY created_at DESC LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($notificationId) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE admin_notifications SET is_read = TRUE, read_at = CURRENT_TIMESTAMP WHERE notification_id = ?");
    $stmt->bind_param("i", $notificationId);
    
    return $stmt->execute();
}

/**
 * Mark all notifications as read
 */
function markAllNotificationsAsRead() {
    global $conn;
    
    $query = "UPDATE admin_notifications SET is_read = TRUE, read_at = CURRENT_TIMESTAMP WHERE is_read = FALSE";
    
    return mysqli_query($conn, $query);
}

/**
 * Delete old notifications (older than 30 days)
 */
function cleanupOldNotifications() {
    global $conn;
    
    $query = "DELETE FROM admin_notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
    
    return mysqli_query($conn, $query);
}

/**
 * Get notification icon based on action type
 */
function getNotificationIcon($actionType) {
    $icons = [
        'quotation_created' => 'fas fa-file-invoice',
        'quotation_approved' => 'fas fa-check-circle',
        'invoice_converted' => 'fas fa-file-invoice-dollar',
        'large_quotation' => 'fas fa-exclamation-triangle',
        'purchase_created' => 'fas fa-shopping-cart',
        'large_purchase' => 'fas fa-exclamation-triangle',
        'low_stock' => 'fas fa-box-open',
        'client_added' => 'fas fa-user-plus',
        'default' => 'fas fa-bell'
    ];
    
    return $icons[$actionType] ?? $icons['default'];
}

/**
 * Get notification color based on priority
 */
function getNotificationColor($priority) {
    $colors = [
        'low' => 'text-secondary',
        'medium' => 'text-primary',
        'high' => 'text-warning',
        'urgent' => 'text-danger'
    ];
    
    return $colors[$priority] ?? $colors['medium'];
}
?>
