<?php
// Debug script to check exact column count in estimates table
include 'config/db.php';

echo "<h2>Database Column Analysis</h2>";

// Get column information from estimates table
$query = "DESCRIBE estimates";
$result = mysqli_query($conn, $query);

if ($result) {
    $columns = [];
    echo "<h3>Estimates Table Columns:</h3>";
    echo "<ol>";
    while ($row = mysqli_fetch_assoc($result)) {
        $columns[] = $row['Field'];
        echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
    }
    echo "</ol>";
    
    echo "<p><strong>Total columns: " . count($columns) . "</strong></p>";
    
    // Show the columns we're trying to insert
    $insert_columns = [
        'estimate_number', 'estimate_date', 'status', 'currency_format', 'template',
        'client_id', 'reference', 'currency', 'salesperson', 'global_tax', 'tax_type', 'tax_calculate_after_discount',
        'bill_company', 'bill_client_name', 'bill_address', 'bill_country', 'bill_city', 'bill_state', 'bill_postal',
        'ship_company', 'ship_client_name', 'ship_address', 'ship_country', 'ship_city', 'ship_state', 'ship_postal',
        'estimate_comments', 'subtotal', 'tax_amount', 'discount_amount', 'fees_amount', 'total_amount', 'created_by'
    ];
    
    echo "<h3>Columns we're trying to insert (" . count($insert_columns) . "):</h3>";
    echo "<ol>";
    foreach ($insert_columns as $col) {
        $exists = in_array($col, $columns);
        echo "<li style='color: " . ($exists ? 'green' : 'red') . "'>" . $col . ($exists ? ' ✓' : ' ✗') . "</li>";
    }
    echo "</ol>";
    
    // Check for missing columns
    $missing = array_diff($insert_columns, $columns);
    if (!empty($missing)) {
        echo "<h3 style='color: red;'>Missing columns:</h3>";
        echo "<ul>";
        foreach ($missing as $col) {
            echo "<li>$col</li>";
        }
        echo "</ul>";
    }
    
    // Check for extra columns in table
    $extra = array_diff($columns, $insert_columns);
    if (!empty($extra)) {
        echo "<h3 style='color: blue;'>Extra columns in table (not being inserted):</h3>";
        echo "<ul>";
        foreach ($extra as $col) {
            echo "<li>$col</li>";
        }
        echo "</ul>";
    }
    
} else {
    echo "Error: " . mysqli_error($conn);
}

echo "<br><a href='add_quotation.php'>← Back to Add Quotation</a>";
?>
