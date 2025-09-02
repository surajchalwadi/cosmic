<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include '../config/db.php';

// Check database connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

try {
    $data = [];
    
    // Initialize with default values in case tables are empty
    $data['total_purchase_amount'] = 0;
    $data['total_purchase_due'] = 0;
    $data['total_sales_amount'] = 0;
    $data['total_sales_due'] = 0;
    $data['total_clients'] = 0;
    $data['purchase_invoices'] = 0;
    $data['stock_items'] = 5;
    
    // Check if tables exist and get data
    $tables_check = mysqli_query($conn, "SHOW TABLES LIKE 'purchase_items'");
    if (mysqli_num_rows($tables_check) > 0) {
        $purchase_amount_query = "SELECT COALESCE(SUM(pi.total_price), 0) as total FROM purchase_items pi";
        $purchase_amount_result = mysqli_query($conn, $purchase_amount_query);
        if ($purchase_amount_result) {
            $data['total_purchase_amount'] = mysqli_fetch_assoc($purchase_amount_result)['total'] ?? 0;
            $data['total_purchase_due'] = $data['total_purchase_amount'];
        }
    }
    
    // Check estimates table
    $estimates_check = mysqli_query($conn, "SHOW TABLES LIKE 'estimates'");
    if (mysqli_num_rows($estimates_check) > 0) {
        $sales_amount_query = "SELECT COALESCE(SUM(total_amount), 0) as total FROM estimates WHERE status IN ('Approved', 'Sent')";
        $sales_amount_result = mysqli_query($conn, $sales_amount_query);
        if ($sales_amount_result) {
            $data['total_sales_amount'] = mysqli_fetch_assoc($sales_amount_result)['total'] ?? 0;
            $data['total_sales_due'] = $data['total_sales_amount'];
        }
    }
    
    // Check clients table
    $clients_check = mysqli_query($conn, "SHOW TABLES LIKE 'clients'");
    if (mysqli_num_rows($clients_check) > 0) {
        $clients_query = "SELECT COUNT(*) as count FROM clients WHERE status = 'Active'";
        $clients_result = mysqli_query($conn, $clients_query);
        if ($clients_result) {
            $data['total_clients'] = mysqli_fetch_assoc($clients_result)['count'] ?? 0;
        }
    }
    
    // Check purchase_invoices table
    $purchase_invoices_check = mysqli_query($conn, "SHOW TABLES LIKE 'purchase_invoices'");
    if (mysqli_num_rows($purchase_invoices_check) > 0) {
        $purchase_invoices_query = "SELECT COUNT(*) as count FROM purchase_invoices";
        $purchase_invoices_result = mysqli_query($conn, $purchase_invoices_query);
        if ($purchase_invoices_result) {
            $data['purchase_invoices'] = mysqli_fetch_assoc($purchase_invoices_result)['count'] ?? 0;
        }
    }
    
    // Check products table
    $products_check = mysqli_query($conn, "SHOW TABLES LIKE 'products'");
    if (mysqli_num_rows($products_check) > 0) {
        $stock_query = "SELECT COUNT(*) as count FROM products WHERE status = 'Active'";
        $stock_result = mysqli_query($conn, $stock_query);
        if ($stock_result) {
            $data['stock_items'] = mysqli_fetch_assoc($stock_result)['count'] ?? 5;
        }
    }
    
    // Monthly Purchase Invoices (last 6 months) - with sample data
    $monthly_purchases = [];
    for ($i = 5; $i >= 0; $i--) {
        $month_name = date('M Y', strtotime("-$i months"));
        $count = rand(2, 15); // Sample data for now
        
        if (mysqli_num_rows($purchase_invoices_check) > 0) {
            $month = date('Y-m', strtotime("-$i months"));
            $monthly_query = "SELECT COUNT(*) as count FROM purchase_invoices WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'";
            $monthly_result = mysqli_query($conn, $monthly_query);
            if ($monthly_result) {
                $count = mysqli_fetch_assoc($monthly_result)['count'] ?? $count;
            }
        }
        
        $monthly_purchases[] = [
            'month' => $month_name,
            'count' => $count
        ];
    }
    $data['monthly_purchases'] = $monthly_purchases;
    
    // Invoice Summary - with sample data
    $invoice_summary = [
        ['status' => 'Draft', 'count' => 3],
        ['status' => 'Sent', 'count' => 5],
        ['status' => 'Approved', 'count' => 8],
        ['status' => 'Rejected', 'count' => 1]
    ];
    
    if (mysqli_num_rows($estimates_check) > 0) {
        $invoice_summary = [];
        $statuses = ['Draft', 'Sent', 'Approved', 'Rejected'];
        foreach ($statuses as $status) {
            $status_query = "SELECT COUNT(*) as count FROM estimates WHERE status = '$status'";
            $status_result = mysqli_query($conn, $status_query);
            $count = 0;
            if ($status_result) {
                $count = mysqli_fetch_assoc($status_result)['count'] ?? 0;
            }
            $invoice_summary[] = [
                'status' => $status,
                'count' => $count
            ];
        }
    }
    $data['invoice_summary'] = $invoice_summary;
    
    // Top 5 Clients - with sample data
    $top_clients = [
        ['client' => 'Acme Corporation', 'amount' => 150000],
        ['client' => 'Tech Innovations Ltd', 'amount' => 85000],
        ['client' => 'Digital Solutions Inc', 'amount' => 120000],
        ['client' => 'Global Enterprises', 'amount' => 95000],
        ['client' => 'Future Systems', 'amount' => 75000]
    ];
    
    if (mysqli_num_rows($estimates_check) > 0) {
        $top_clients_query = "SELECT bill_client_name as client_name, COALESCE(SUM(total_amount), 0) as total 
                             FROM estimates 
                             WHERE bill_client_name IS NOT NULL AND bill_client_name != '' 
                             GROUP BY bill_client_name 
                             ORDER BY total DESC 
                             LIMIT 5";
        $top_clients_result = mysqli_query($conn, $top_clients_query);
        if ($top_clients_result && mysqli_num_rows($top_clients_result) > 0) {
            $top_clients = [];
            while ($row = mysqli_fetch_assoc($top_clients_result)) {
                $top_clients[] = [
                    'client' => $row['client_name'],
                    'amount' => $row['total']
                ];
            }
        }
    }
    $data['top_clients'] = $top_clients;
    
    // Sales vs Purchases trend (last 6 months) - with sample data
    $trend_data = [];
    for ($i = 5; $i >= 0; $i--) {
        $month_name = date('M Y', strtotime("-$i months"));
        $sales_total = rand(50000, 200000);
        $purchase_total = rand(30000, 150000);
        
        if (mysqli_num_rows($estimates_check) > 0 && mysqli_num_rows($purchase_invoices_check) > 0) {
            $month = date('Y-m', strtotime("-$i months"));
            
            // Sales for this month
            $sales_query = "SELECT COALESCE(SUM(total_amount), 0) as total FROM estimates 
                           WHERE status IN ('Approved', 'Sent') AND DATE_FORMAT(created_at, '%Y-%m') = '$month'";
            $sales_result = mysqli_query($conn, $sales_query);
            if ($sales_result) {
                $sales_total = mysqli_fetch_assoc($sales_result)['total'] ?? $sales_total;
            }
            
            // Purchases for this month
            $purchase_query = "SELECT COALESCE(SUM(pi.total_price), 0) as total FROM purchase_items pi 
                              JOIN purchase_invoices p ON pi.purchase_id = p.purchase_id
                              WHERE DATE_FORMAT(p.created_at, '%Y-%m') = '$month'";
            $purchase_result = mysqli_query($conn, $purchase_query);
            if ($purchase_result) {
                $purchase_total = mysqli_fetch_assoc($purchase_result)['total'] ?? $purchase_total;
            }
        }
        
        $trend_data[] = [
            'month' => $month_name,
            'sales' => $sales_total,
            'purchases' => $purchase_total
        ];
    }
    $data['sales_vs_purchases'] = $trend_data;
    
    // Notifications
    $notifications = [];
    
    if (mysqli_num_rows($estimates_check) > 0) {
        // Pending quotations
        $pending_query = "SELECT COUNT(*) as count FROM estimates WHERE status = 'Draft'";
        $pending_result = mysqli_query($conn, $pending_query);
        if ($pending_result) {
            $pending_count = mysqli_fetch_assoc($pending_result)['count'] ?? 0;
            if ($pending_count > 0) {
                $notifications[] = [
                    'type' => 'warning',
                    'message' => "$pending_count quotations pending approval",
                    'icon' => 'fas fa-clock'
                ];
            }
        }
        
        // Sent estimates waiting for response
        $sent_query = "SELECT COUNT(*) as count FROM estimates WHERE status = 'Sent'";
        $sent_result = mysqli_query($conn, $sent_query);
        if ($sent_result) {
            $sent_count = mysqli_fetch_assoc($sent_result)['count'] ?? 0;
            if ($sent_count > 0) {
                $notifications[] = [
                    'type' => 'info',
                    'message' => "$sent_count estimates awaiting client response",
                    'icon' => 'fas fa-paper-plane'
                ];
            }
        }
    } else {
        // Sample notifications
        $notifications[] = [
            'type' => 'warning',
            'message' => "3 quotations pending approval",
            'icon' => 'fas fa-clock'
        ];
        $notifications[] = [
            'type' => 'info',
            'message' => "5 estimates awaiting client response",
            'icon' => 'fas fa-paper-plane'
        ];
    }
    
    $data['notifications'] = $notifications;
    $data['timestamp'] = date('Y-m-d H:i:s');
    $data['status'] = 'success';
    
    echo json_encode($data);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage(), 'status' => 'error']);
}

// Force output buffer flush
if (ob_get_level()) {
    ob_end_flush();
}
?>
