-- Create test users for notification system testing
-- Run this SQL file to create sales and inventory users

-- Insert Sales User
INSERT INTO users (name, email, password, role, status) VALUES 
('Sales Manager', 'sales@cosmic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sales', 'active');

-- Insert Inventory User  
INSERT INTO users (name, email, password, role, status) VALUES 
('Inventory Manager', 'inventory@cosmic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'inventory', 'active');

-- Insert another Sales User
INSERT INTO users (name, email, password, role, status) VALUES 
('John Sales', 'john@cosmic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sales', 'active');

-- Insert another Inventory User
INSERT INTO users (name, email, password, role, status) VALUES 
('Mary Inventory', 'mary@cosmic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'inventory', 'active');

-- Password for all test users is: password
-- You can login with:
-- Email: sales@cosmic.com, Password: password (Sales role)
-- Email: inventory@cosmic.com, Password: password (Inventory role)  
-- Email: john@cosmic.com, Password: password (Sales role)
-- Email: mary@cosmic.com, Password: password (Inventory role)
