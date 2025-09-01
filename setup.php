<?php
/**
 * Cosmic Inventory System - Complete Setup Script
 * Run this script once to set up the entire database and system
 */

$host = "localhost";
$username = "root";
$password = "";
$database = "cosmic_inventory";

echo "<!DOCTYPE html>
<html>
<head>
    <title>Cosmic Inventory Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .step { margin: 10px 0; padding: 10px; border-left: 4px solid #007bff; background: #f8f9fa; }
        .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px 0 0; }
    </style>
</head>
<body>";

echo "<h1>🚀 Cosmic Inventory System Setup</h1>";

// Step 1: Database Connection
echo "<div class='step'><h3>Step 1: Database Connection</h3>";
$conn = new mysqli($host, $username, $password);
if ($conn->connect_error) {
    echo "<p class='error'>❌ Connection failed: " . $conn->connect_error . "</p>";
    echo "<p><strong>Please ensure XAMPP/MySQL is running</strong></p>";
    exit;
} else {
    echo "<p class='success'>✅ MySQL connection successful</p>";
}

// Step 2: Create Database
echo "</div><div class='step'><h3>Step 2: Create Database</h3>";
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'>✅ Database '$database' created/verified</p>";
} else {
    echo "<p class='error'>❌ Error creating database: " . $conn->error . "</p>";
}

$conn->select_db($database);

// Step 3: Create Tables
echo "</div><div class='step'><h3>Step 3: Create Tables</h3>";

$tables = [
    'users' => "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'inventory', 'sales', 'user') DEFAULT 'user',
        status TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    'clients' => "CREATE TABLE IF NOT EXISTS clients (
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
    )",
    
    'products' => "CREATE TABLE IF NOT EXISTS products (
        product_id INT AUTO_INCREMENT PRIMARY KEY,
        product_name VARCHAR(255) NOT NULL UNIQUE,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        status ENUM('Active', 'Inactive') DEFAULT 'Active',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
    )",
    
    'estimates' => "CREATE TABLE IF NOT EXISTS estimates (
        estimate_id INT AUTO_INCREMENT PRIMARY KEY,
        estimate_number VARCHAR(50) NOT NULL UNIQUE,
        estimate_date DATE NOT NULL,
        status ENUM('Draft', 'Sent', 'Approved', 'Rejected') DEFAULT 'Draft',
        invoice_created BOOLEAN DEFAULT FALSE,
        currency_format ENUM('INR', 'USD', 'EUR') DEFAULT 'INR',
        template VARCHAR(50) DEFAULT 'Default',
        client_id INT,
        reference VARCHAR(100),
        currency ENUM('INR', 'USD', 'EUR') DEFAULT 'INR',
        salesperson VARCHAR(255),
        global_tax DECIMAL(5,2) DEFAULT 18.00,
        tax_type ENUM('Percentage', 'Fixed') DEFAULT 'Percentage',
        tax_calculate_after_discount BOOLEAN DEFAULT TRUE,
        bill_company VARCHAR(255),
        bill_client_name VARCHAR(255),
        bill_address TEXT,
        bill_country VARCHAR(100),
        bill_city VARCHAR(100),
        bill_state VARCHAR(100),
        bill_postal VARCHAR(20),
        ship_company VARCHAR(255),
        ship_client_name VARCHAR(255),
        ship_address TEXT,
        ship_country VARCHAR(100),
        ship_city VARCHAR(100),
        ship_state VARCHAR(100),
        ship_postal VARCHAR(20),
        estimate_comments TEXT,
        subtotal DECIMAL(12,2) DEFAULT 0,
        tax_amount DECIMAL(12,2) DEFAULT 0,
        discount_amount DECIMAL(12,2) DEFAULT 0,
        fees_amount DECIMAL(12,2) DEFAULT 0,
        total_amount DECIMAL(12,2) DEFAULT 0,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
        FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE SET NULL
    )",
    
    'estimate_items' => "CREATE TABLE IF NOT EXISTS estimate_items (
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
    )"
];

foreach ($tables as $table_name => $sql) {
    if ($conn->query($sql)) {
        echo "<p class='success'>✅ Table '$table_name' created</p>";
    } else {
        echo "<p class='error'>❌ Error creating table '$table_name': " . $conn->error . "</p>";
    }
}

// Step 4: Insert Default Data
echo "</div><div class='step'><h3>Step 4: Insert Default Data</h3>";

// Check and insert admin user
$check_admin = $conn->query("SELECT COUNT(*) as count FROM users WHERE email = 'admin@cosmic.com'");
$admin_exists = $check_admin->fetch_assoc()['count'] > 0;

if (!$admin_exists) {
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $name = 'Admin';
    $email = 'admin@cosmic.com';
    $role = 'admin';
    $stmt->bind_param("ssss", $name, $email, $admin_password, $role);
    
    if ($stmt->execute()) {
        echo "<p class='success'>✅ Admin user created</p>";
    } else {
        echo "<p class='error'>❌ Error creating admin user</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Admin user already exists</p>";
}

// Insert sample data
$sample_data = [
    'clients' => [
        ['John Smith', 'Tech Solutions Inc', 'john@techsolutions.com', '+91-9876543210', '123 Business Park, Sector 1', 'India', 'Mumbai', 'Maharashtra', '400001'],
        ['Sarah Johnson', 'Digital Marketing Co', 'sarah@digitalmarketing.com', '+91-9876543211', '456 Corporate Plaza, Block A', 'India', 'Delhi', 'Delhi', '110001']
    ],
    'products' => [
        ['Laptop Computer', 'High-performance laptop for business use', 45000.00],
        ['Desktop Monitor', '24-inch LED monitor with full HD resolution', 12000.00],
        ['Wireless Mouse', 'Ergonomic wireless mouse with USB receiver', 800.00],
        ['Keyboard', 'Mechanical keyboard with backlight', 2500.00],
        ['Printer', 'All-in-one inkjet printer with scanner', 8500.00],
        ['Software License', 'Annual software license for productivity suite', 15000.00],
        ['Network Router', 'Enterprise-grade wireless router', 5500.00],
        ['External Hard Drive', '2TB external storage device', 3500.00]
    ]
];

// Insert clients
$check_clients = $conn->query("SELECT COUNT(*) as count FROM clients");
$client_count = $check_clients->fetch_assoc()['count'];

if ($client_count == 0) {
    foreach ($sample_data['clients'] as $client) {
        $stmt = $conn->prepare("INSERT INTO clients (client_name, company, email, phone, address, country, city, state, postal, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
        $stmt->bind_param("sssssssss", $client[0], $client[1], $client[2], $client[3], $client[4], $client[5], $client[6], $client[7], $client[8]);
        $stmt->execute();
    }
    echo "<p class='success'>✅ Sample clients inserted</p>";
} else {
    echo "<p class='warning'>⚠️ Clients already exist</p>";
}

// Insert products
$check_products = $conn->query("SELECT COUNT(*) as count FROM products");
$product_count = $check_products->fetch_assoc()['count'];

if ($product_count == 0) {
    foreach ($sample_data['products'] as $product) {
        $stmt = $conn->prepare("INSERT INTO products (product_name, description, price, status) VALUES (?, ?, ?, 'Active')");
        $stmt->bind_param("ssd", $product[0], $product[1], $product[2]);
        $stmt->execute();
    }
    echo "<p class='success'>✅ Sample products inserted</p>";
} else {
    echo "<p class='warning'>⚠️ Products already exist</p>";
}

// Step 5: Create temp directory
echo "</div><div class='step'><h3>Step 5: Create Directories</h3>";
$temp_dir = 'temp/invoices';
if (!file_exists($temp_dir)) {
    if (mkdir($temp_dir, 0755, true)) {
        echo "<p class='success'>✅ Created directory: $temp_dir</p>";
    } else {
        echo "<p class='error'>❌ Failed to create directory: $temp_dir</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Directory already exists: $temp_dir</p>";
}

echo "</div>";

// Final Summary
echo "<div class='step'><h2>🎉 Setup Complete!</h2>";
echo "<h3>Login Credentials:</h3>";
echo "<p><strong>Email:</strong> admin@cosmic.com</p>";
echo "<p><strong>Password:</strong> admin123</p>";

echo "<h3>System Features:</h3>";
echo "<ul>";
echo "<li>✅ User Management</li>";
echo "<li>✅ Client Management</li>";
echo "<li>✅ Product Management</li>";
echo "<li>✅ Quotation/Estimate System</li>";
echo "<li>✅ Invoice Generation</li>";
echo "<li>✅ Email Automation (configured separately)</li>";
echo "</ul>";

echo "<a href='index.php' class='btn'>🔑 Go to Login</a>";
echo "<a href='dashboard.php' class='btn'>📊 Go to Dashboard</a>";
echo "</div>";

$conn->close();
echo "</body></html>";
?>
