<?php
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include '../config/db.php';

$search_term = $_GET['q'] ?? '';
$search_term = trim($search_term);

if (empty($search_term)) {
    echo json_encode(['results' => []]);
    exit;
}

$results = [];

// Search in clients
$client_query = "SELECT client_id, client_name, company, email, 'client' as type 
                 FROM clients 
                 WHERE (client_name LIKE ? OR company LIKE ? OR email LIKE ?) 
                 AND status = 'Active' 
                 LIMIT 10";
$stmt = $conn->prepare($client_query);
$search_param = "%$search_term%";
$stmt->bind_param("sss", $search_param, $search_param, $search_param);
$stmt->execute();
$client_result = $stmt->get_result();

while ($row = $client_result->fetch_assoc()) {
    $results[] = [
        'id' => $row['client_id'],
        'title' => $row['client_name'],
        'subtitle' => $row['company'] ?: $row['email'],
        'type' => 'client',
        'url' => 'client_list.php?search=' . urlencode($row['client_name']),
        'icon' => 'fas fa-user'
    ];
}

// Search in products
$product_query = "SELECT product_id, product_name, description, price, 'product' as type 
                  FROM products 
                  WHERE (product_name LIKE ? OR description LIKE ?) 
                  AND status = 'Active' 
                  LIMIT 10";
$stmt = $conn->prepare($product_query);
$stmt->bind_param("ss", $search_param, $search_param);
$stmt->execute();
$product_result = $stmt->get_result();

while ($row = $product_result->fetch_assoc()) {
    $results[] = [
        'id' => $row['product_id'],
        'title' => $row['product_name'],
        'subtitle' => '₹' . number_format($row['price'], 2),
        'type' => 'product',
        'url' => 'product_list.php?search=' . urlencode($row['product_name']),
        'icon' => 'fas fa-box'
    ];
}

// Search in estimates/quotations
$estimate_query = "SELECT estimate_id, estimate_number, bill_client_name, total_amount, status, 'estimate' as type 
                   FROM estimates 
                   WHERE (estimate_number LIKE ? OR bill_client_name LIKE ?) 
                   LIMIT 10";
$stmt = $conn->prepare($estimate_query);
$stmt->bind_param("ss", $search_param, $search_param);
$stmt->execute();
$estimate_result = $stmt->get_result();

while ($row = $estimate_result->fetch_assoc()) {
    $results[] = [
        'id' => $row['estimate_id'],
        'title' => $row['estimate_number'],
        'subtitle' => $row['bill_client_name'] . ' - ₹' . number_format($row['total_amount'], 2),
        'type' => 'estimate',
        'url' => 'quotation_list.php?search=' . urlencode($row['estimate_number']),
        'icon' => 'fas fa-file-invoice'
    ];
}

// Search in purchase invoices (if table exists)
$purchase_query = "SELECT purchase_id, party_name, invoice_no, 'purchase' as type 
                   FROM purchase_invoices 
                   WHERE (party_name LIKE ? OR invoice_no LIKE ?) 
                   LIMIT 10";
try {
    $stmt = $conn->prepare($purchase_query);
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $purchase_result = $stmt->get_result();

    while ($row = $purchase_result->fetch_assoc()) {
        $results[] = [
            'id' => $row['purchase_id'],
            'title' => $row['invoice_no'],
            'subtitle' => $row['party_name'],
            'type' => 'purchase',
            'url' => 'purchase_list.php?search=' . urlencode($row['invoice_no']),
            'icon' => 'fas fa-shopping-cart'
        ];
    }
} catch (Exception $e) {
    // Purchase table might not exist, skip silently
}

header('Content-Type: application/json');
echo json_encode(['results' => $results]);
?>
