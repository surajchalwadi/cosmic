<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'sales'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        // Get estimate details with proper escaping
        $estimate_number = mysqli_real_escape_string($conn, $_POST['estimate_number'] ?? '');
        $estimate_date = mysqli_real_escape_string($conn, $_POST['estimate_date'] ?? date('Y-m-d'));
        $status = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Draft');
        $currency_format = mysqli_real_escape_string($conn, $_POST['currency_format'] ?? 'INR');
        $template = mysqli_real_escape_string($conn, $_POST['template'] ?? 'Default');
        
        // Get client details - handle empty client_id properly
        $client_id = (!empty($_POST['client_id']) && $_POST['client_id'] !== 'Select Client') ? intval($_POST['client_id']) : NULL;
        $reference = mysqli_real_escape_string($conn, $_POST['reference'] ?? '');
        $currency = mysqli_real_escape_string($conn, $_POST['currency'] ?? 'INR');
        $salesperson = mysqli_real_escape_string($conn, $_POST['salesperson'] ?? '');
        $global_tax = floatval($_POST['global_tax'] ?? 18.0);
        $tax_type = mysqli_real_escape_string($conn, $_POST['tax_type'] ?? 'Percentage');
        $tax_calculate_after_discount = isset($_POST['tax_calculate_after_discount']) ? 1 : 0;
        
        // Get Bill To details
        $bill_company = mysqli_real_escape_string($conn, $_POST['bill_company'] ?? '');
        $bill_client_name = mysqli_real_escape_string($conn, $_POST['bill_client_name'] ?? '');
        $bill_address = mysqli_real_escape_string($conn, $_POST['bill_address'] ?? '');
        $bill_country = mysqli_real_escape_string($conn, $_POST['bill_country'] ?? '');
        $bill_city = mysqli_real_escape_string($conn, $_POST['bill_city'] ?? '');
        $bill_state = mysqli_real_escape_string($conn, $_POST['bill_state'] ?? '');
        $bill_postal = mysqli_real_escape_string($conn, $_POST['bill_postal'] ?? '');
        
        // Get Ship To details
        $ship_company = mysqli_real_escape_string($conn, $_POST['ship_company'] ?? '');
        $ship_client_name = mysqli_real_escape_string($conn, $_POST['ship_client_name'] ?? '');
        $ship_address = mysqli_real_escape_string($conn, $_POST['ship_address'] ?? '');
        $ship_country = mysqli_real_escape_string($conn, $_POST['ship_country'] ?? '');
        $ship_city = mysqli_real_escape_string($conn, $_POST['ship_city'] ?? '');
        $ship_state = mysqli_real_escape_string($conn, $_POST['ship_state'] ?? '');
        $ship_postal = mysqli_real_escape_string($conn, $_POST['ship_postal'] ?? '');
        
        // Get comments
        $estimate_comments = mysqli_real_escape_string($conn, $_POST['estimate_comments'] ?? '');
        
        // Check session structure and set created_by
        if (isset($_SESSION['user']['user_id'])) {
            $created_by = $_SESSION['user']['user_id'];
        } elseif (isset($_SESSION['user']['id'])) {
            $created_by = $_SESSION['user']['id'];
        } else {
            $created_by = 1; // Default fallback
        }
        
        // Calculate totals from line items
        $subtotal = 0;
        $total_tax = 0;
        $total_discount = 0;
        $fees_amount = 0;
        $item_amounts = []; // Store calculated amounts for each item
        
        if (isset($_POST['product_description']) && is_array($_POST['product_description'])) {
            for ($i = 0; $i < count($_POST['product_description']); $i++) {
                if (!empty($_POST['product_description'][$i])) {
                    $quantity = floatval($_POST['quantity'][$i] ?? 0);
                    $unit_price = floatval($_POST['unit_price'][$i] ?? 0);
                    $tax_discount_type = $_POST['tax_discount_type'][$i] ?? 'Select';
                    $tax_discount_value = floatval($_POST['tax_discount_value'][$i] ?? 0);
                    
                    // Calculate base amount for this item
                    $item_base_amount = $quantity * $unit_price;
                    $item_final_amount = $item_base_amount;
                    
                    // Apply individual item tax/discount
                    if ($tax_discount_type === 'Tax' && $tax_discount_value > 0) {
                        $item_tax = ($item_base_amount * $tax_discount_value / 100);
                        $total_tax += $item_tax;
                        $item_final_amount = $item_base_amount; // Tax is added separately
                    } elseif ($tax_discount_type === 'Discount' && $tax_discount_value > 0) {
                        $item_discount = ($item_base_amount * $tax_discount_value / 100);
                        $total_discount += $item_discount;
                        $item_final_amount = $item_base_amount - $item_discount;
                    }
                    
                    $subtotal += $item_base_amount;
                    $item_amounts[$i] = $item_final_amount; // Store final amount for this item
                }
            }
        }
        
        // Add global tax (applied to subtotal after individual discounts)
        if ($global_tax > 0) {
            $global_tax_amount = ($subtotal - $total_discount) * $global_tax / 100;
            $total_tax += $global_tax_amount;
        }
        
        $total_amount = $subtotal + $total_tax - $total_discount + $fees_amount;
        
        // Insert estimate using regular query instead of prepared statement to avoid binding issues
        
        // Count parameters from your error: 'QT961', '2025-08-29', 'Draft', 'INR', 'Default', 2, 'QT961', 'INR', '', 18.0, 'Percentage', 1, '', '', '', 'India', '', '', '', '', '', '', 'India', '', '', '', '', 2020.0, 606.0, 0, 0, 2626.0, 1
        // That's 33 parameters total
        
        // Let me debug first
        $params_debug = [
            $estimate_number, $estimate_date, $status, $currency_format, $template,
            $client_id, $reference, $currency, $salesperson, $global_tax, 
            $tax_type, $tax_calculate_after_discount, $bill_company, $bill_client_name, $bill_address,
            $bill_country, $bill_city, $bill_state, $bill_postal, $ship_company,
            $ship_client_name, $ship_address, $ship_country, $ship_city, $ship_state,
            $ship_postal, $estimate_comments, $subtotal, $total_tax, $total_discount,
            $fees_amount, $total_amount, $created_by
        ];
        error_log("DEBUG: Parameter count = " . count($params_debug));
        
        // Use different approach - build query without prepared statements
        $estimate_query = "INSERT INTO estimates (
            estimate_number, estimate_date, status, currency_format, template,
            client_id, reference, currency, salesperson, global_tax, tax_type, tax_calculate_after_discount,
            bill_company, bill_client_name, bill_address, bill_country, bill_city, bill_state, bill_postal,
            ship_company, ship_client_name, ship_address, ship_country, ship_city, ship_state, ship_postal,
            estimate_comments, subtotal, tax_amount, discount_amount, fees_amount, total_amount, created_by
        ) VALUES (
            '" . mysqli_real_escape_string($conn, $estimate_number) . "',
            '" . mysqli_real_escape_string($conn, $estimate_date) . "',
            '" . mysqli_real_escape_string($conn, $status) . "',
            '" . mysqli_real_escape_string($conn, $currency_format) . "',
            '" . mysqli_real_escape_string($conn, $template) . "',
            " . ($client_id ? $client_id : 'NULL') . ",
            '" . mysqli_real_escape_string($conn, $reference) . "',
            '" . mysqli_real_escape_string($conn, $currency) . "',
            '" . mysqli_real_escape_string($conn, $salesperson) . "',
            " . $global_tax . ",
            '" . mysqli_real_escape_string($conn, $tax_type) . "',
            " . $tax_calculate_after_discount . ",
            '" . mysqli_real_escape_string($conn, $bill_company) . "',
            '" . mysqli_real_escape_string($conn, $bill_client_name) . "',
            '" . mysqli_real_escape_string($conn, $bill_address) . "',
            '" . mysqli_real_escape_string($conn, $bill_country) . "',
            '" . mysqli_real_escape_string($conn, $bill_city) . "',
            '" . mysqli_real_escape_string($conn, $bill_state) . "',
            '" . mysqli_real_escape_string($conn, $bill_postal) . "',
            '" . mysqli_real_escape_string($conn, $ship_company) . "',
            '" . mysqli_real_escape_string($conn, $ship_client_name) . "',
            '" . mysqli_real_escape_string($conn, $ship_address) . "',
            '" . mysqli_real_escape_string($conn, $ship_country) . "',
            '" . mysqli_real_escape_string($conn, $ship_city) . "',
            '" . mysqli_real_escape_string($conn, $ship_state) . "',
            '" . mysqli_real_escape_string($conn, $ship_postal) . "',
            '" . mysqli_real_escape_string($conn, $estimate_comments) . "',
            " . $subtotal . ",
            " . $total_tax . ",
            " . $total_discount . ",
            " . $fees_amount . ",
            " . $total_amount . ",
            " . $created_by . "
        )";
        
        if (!mysqli_query($conn, $estimate_query)) {
            throw new Exception("Error inserting estimate: " . mysqli_error($conn));
        }
        
        $estimate_id = mysqli_insert_id($conn);
        
        
        $estimate_id = mysqli_insert_id($conn);
        
        // Insert estimate items using prepared statements
        if (isset($_POST['product_description']) && is_array($_POST['product_description'])) {
            $item_query = "INSERT INTO estimate_items (
                estimate_id, product_description, quantity_unit, quantity, 
                unit_price, tax_discount_type, tax_discount_value, amount
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $item_stmt = mysqli_prepare($conn, $item_query);
            if (!$item_stmt) {
                throw new Exception("Prepare item query failed: " . mysqli_error($conn));
            }
            
            for ($i = 0; $i < count($_POST['product_description']); $i++) {
                if (!empty($_POST['product_description'][$i])) {
                    $product_description = $_POST['product_description'][$i];
                    $quantity_unit = $_POST['quantity_unit'][$i] ?? 'Quantity';
                    $quantity = floatval($_POST['quantity'][$i] ?? 0);
                    $unit_price = floatval($_POST['unit_price'][$i] ?? 0);
                    $tax_discount_type = $_POST['tax_discount_type'][$i] ?? 'Select';
                    $tax_discount_value = floatval($_POST['tax_discount_value'][$i] ?? 0);
                    
                    // Use the calculated amount from our calculation above
                    $amount = isset($item_amounts[$i]) ? $item_amounts[$i] : ($quantity * $unit_price);
                    
                    mysqli_stmt_bind_param($item_stmt, "issddsdd", 
                        $estimate_id, $product_description, $quantity_unit, $quantity,
                        $unit_price, $tax_discount_type, $tax_discount_value, $amount
                    );
                    
                    if (!mysqli_stmt_execute($item_stmt)) {
                        throw new Exception("Error inserting estimate item: " . mysqli_stmt_error($item_stmt));
                    }
                }
            }
            mysqli_stmt_close($item_stmt);
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        $_SESSION['success'] = "Quotation saved successfully! Quotation #: " . $estimate_number;
        header("Location: quotation_list.php");
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_rollback($conn);
        $_SESSION['error'] = $e->getMessage();
        header("Location: add_quotation.php");
        exit;
    }
} else {
    header("Location: add_quotation.php");
    exit;
}
?>