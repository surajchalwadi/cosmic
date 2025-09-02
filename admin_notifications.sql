-- Admin Notification System for Cosmic Inventory
-- Add this to your existing database

-- Create admin_notifications table
CREATE TABLE IF NOT EXISTS admin_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    user_role ENUM('sales', 'inventory') NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    reference_id INT,
    reference_type ENUM('quotation', 'invoice', 'purchase', 'client', 'product') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    INDEX idx_unread (is_read, created_at),
    INDEX idx_user_action (user_id, action_type),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Insert sample notifications for testing
INSERT INTO admin_notifications (user_id, user_name, user_role, action_type, title, message, reference_id, reference_type, priority) VALUES
(1, 'Sales User', 'sales', 'quotation_created', 'New Quotation Created', 'Sales team created quotation EST-2024-003 for Tech Solutions Inc worth ₹85,000', 1, 'quotation', 'medium'),
(1, 'Inventory User', 'inventory', 'large_purchase', 'Large Purchase Added', 'Inventory team added purchase invoice INV-2024-015 from ABC Suppliers worth ₹1,25,000', 1, 'purchase', 'high'),
(1, 'Sales User', 'sales', 'invoice_converted', 'Quotation Converted to Invoice', 'Sales team converted quotation EST-2024-002 to invoice for Digital Marketing Co', 2, 'invoice', 'medium');
