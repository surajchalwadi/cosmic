-- Create database
CREATE DATABASE IF NOT EXISTS cosmic_inventory;
USE cosmic_inventory;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'inventory', 'sales', 'user') DEFAULT 'user',
    status TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create purchase_invoices table (main purchase record)
CREATE TABLE IF NOT EXISTS purchase_invoices (
    purchase_id INT AUTO_INCREMENT PRIMARY KEY,
    party_name VARCHAR(255) NOT NULL,
    invoice_no VARCHAR(100) NOT NULL,
    delivery_date DATE NOT NULL,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Create purchase_items table (individual products in each purchase)
CREATE TABLE IF NOT EXISTS purchase_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES purchase_invoices(purchase_id) ON DELETE CASCADE
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Create estimates table (updated from quotations)
CREATE TABLE IF NOT EXISTS estimates (
    estimate_id INT AUTO_INCREMENT PRIMARY KEY,
    estimate_number VARCHAR(50) NOT NULL UNIQUE,
    estimate_date DATE NOT NULL,
    status ENUM('Draft', 'Sent', 'Approved', 'Rejected') DEFAULT 'Draft',
    invoice_created BOOLEAN DEFAULT FALSE,
    currency_format ENUM('INR', 'USD', 'EUR') DEFAULT 'INR',
    template VARCHAR(50) DEFAULT 'Default',
    
    -- Client Details
    client_id INT,
    reference VARCHAR(100),
    currency ENUM('INR', 'USD', 'EUR') DEFAULT 'INR',
    salesperson VARCHAR(255),
    global_tax DECIMAL(5,2) DEFAULT 18.00,
    tax_type ENUM('Percentage', 'Fixed') DEFAULT 'Percentage',
    tax_calculate_after_discount BOOLEAN DEFAULT TRUE,
    
    -- Bill To Information
    bill_company VARCHAR(255),
    bill_client_name VARCHAR(255),
    bill_address TEXT,
    bill_country VARCHAR(100),
    bill_city VARCHAR(100),
    bill_state VARCHAR(100),
    bill_postal VARCHAR(20),
    
    -- Ship To Information
    ship_company VARCHAR(255),
    ship_client_name VARCHAR(255),
    ship_address TEXT,
    ship_country VARCHAR(100),
    ship_city VARCHAR(100),
    ship_state VARCHAR(100),
    ship_postal VARCHAR(20),
    
    -- Comments and Totals
    estimate_comments TEXT,
    subtotal DECIMAL(12,2) DEFAULT 0,
    tax_amount DECIMAL(12,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    fees_amount DECIMAL(12,2) DEFAULT 0,
    total_amount DECIMAL(12,2) DEFAULT 0,
    
    -- Audit fields
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Create estimate_items table (updated from quotation_items)
CREATE TABLE IF NOT EXISTS estimate_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    estimate_id INT NOT NULL,
    product_description TEXT NOT NULL,
    quantity_unit VARCHAR(50) DEFAULT 'Quantity',
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    tax_discount_type ENUM('Select', 'Tax', 'Discount') DEFAULT 'Select',
    tax_discount_value DECIMAL(5,2) DEFAULT 0,
    amount DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (estimate_id) REFERENCES estimates(estimate_id) ON DELETE CASCADE
);

-- Create clients table for client management
CREATE TABLE IF NOT EXISTS clients (
    client_id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(255) NOT NULL,
    company VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    country VARCHAR(100),
    city VARCHAR(100),
    state VARCHAR(100),
    postal VARCHAR(20),
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Drop old quotation tables if they exist (disable foreign key checks first)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS quotation_items;
DROP TABLE IF EXISTS quotations;
SET FOREIGN_KEY_CHECKS = 1;

-- Insert default admin user (password: admin123)
-- Note: Use a proper password hash generator for production
INSERT INTO users (name, email, password, role) VALUES 
('Admin', 'admin@cosmic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample clients
INSERT INTO clients (client_name, company, email, phone, address, country, city, state, postal, status) VALUES 
('John Smith', 'Tech Solutions Inc', 'john@techsolutions.com', '+91-9876543210', '123 Business Park, Sector 1', 'India', 'Mumbai', 'Maharashtra', '400001', 'Active'),
('Sarah Johnson', 'Digital Marketing Co', 'sarah@digitalmarketing.com', '+91-9876543211', '456 Corporate Plaza, Block A', 'India', 'Delhi', 'Delhi', '110001', 'Active');

-- Insert sample products
INSERT INTO products (product_name, description, price, status) VALUES 
('Laptop Computer', 'High-performance laptop for business use', 45000.00, 'Active'),
('Desktop Monitor', '24-inch LED monitor with full HD resolution', 12000.00, 'Active'),
('Wireless Mouse', 'Ergonomic wireless mouse with USB receiver', 800.00, 'Active'),
('Keyboard', 'Mechanical keyboard with backlight', 2500.00, 'Active'),
('Printer', 'All-in-one inkjet printer with scanner', 8500.00, 'Active'),
('Software License', 'Annual software license for productivity suite', 15000.00, 'Active'),
('Network Router', 'Enterprise-grade wireless router', 5500.00, 'Active'),
('External Hard Drive', '2TB external storage device', 3500.00, 'Active');

-- Insert sample estimates/quotations
INSERT INTO estimates (estimate_number, estimate_date, status, client_id, bill_company, bill_client_name, bill_address, bill_country, bill_city, bill_state, bill_postal, subtotal, tax_amount, total_amount, created_by) VALUES 
('EST-2024-001', '2024-08-30', 'Draft', 1, 'Tech Solutions Inc', 'John Smith', '123 Business Park, Sector 1', 'India', 'Mumbai', 'Maharashtra', '400001', 57800.00, 10404.00, 68204.00, 1),
('EST-2024-002', '2024-08-30', 'Sent', 2, 'Digital Marketing Co', 'Sarah Johnson', '456 Corporate Plaza, Block A', 'India', 'Delhi', 'Delhi', '110001', 20300.00, 3654.00, 23954.00, 1);

-- Insert sample estimate items
INSERT INTO estimate_items (estimate_id, product_description, quantity, unit_price, amount) VALUES 
(1, 'Laptop Computer - High-performance laptop for business use', 1.00, 45000.00, 45000.00),
(1, 'Desktop Monitor - 24-inch LED monitor with full HD resolution', 1.00, 12000.00, 12000.00),
(1, 'Wireless Mouse - Ergonomic wireless mouse with USB receiver', 1.00, 800.00, 800.00),
(2, 'Software License - Annual software license for productivity suite', 1.00, 15000.00, 15000.00),
(2, 'Network Router - Enterprise-grade wireless router', 1.00, 5500.00, 5500.00); 