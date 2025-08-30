<?php
// Debug script to count exact parameters and type string
echo "<h2>Parameter Count Debug</h2>";

// Type string from code
$type_string = "sssssissdsissssssssssssdddddi";
echo "<p><strong>Type string:</strong> $type_string</p>";
echo "<p><strong>Type string length:</strong> " . strlen($type_string) . "</p>";

// Parameters list
$params = [
    'estimate_number', 'estimate_date', 'status', 'currency_format', 'template',
    'client_id', 'reference', 'currency', 'salesperson', 'global_tax', 'tax_type', 'tax_calculate_after_discount',
    'bill_company', 'bill_client_name', 'bill_address', 'bill_country', 'bill_city', 'bill_state', 'bill_postal',
    'ship_company', 'ship_client_name', 'ship_address', 'ship_country', 'ship_city', 'ship_state', 'ship_postal',
    'estimate_comments', 'subtotal', 'tax_amount', 'discount_amount', 'fees_amount', 'total_amount', 'created_by'
];

echo "<p><strong>Parameter count:</strong> " . count($params) . "</p>";

echo "<h3>Parameters with expected types:</h3>";
echo "<ol>";
foreach ($params as $i => $param) {
    $type_char = isset($type_string[$i]) ? $type_string[$i] : 'MISSING';
    $color = ($type_char === 'MISSING') ? 'red' : 'green';
    echo "<li style='color: $color'>$param -> $type_char</li>";
}
echo "</ol>";

// Show correct type string
echo "<h3>Correct Type String (33 chars):</h3>";
$correct_types = [
    's', 's', 's', 's', 's',  // estimate_number, estimate_date, status, currency_format, template
    'i', 's', 's', 's', 'd', 's', 'i',  // client_id, reference, currency, salesperson, global_tax, tax_type, tax_calculate_after_discount
    's', 's', 's', 's', 's', 's', 's',  // bill_company, bill_client_name, bill_address, bill_country, bill_city, bill_state, bill_postal
    's', 's', 's', 's', 's', 's', 's',  // ship_company, ship_client_name, ship_address, ship_country, ship_city, ship_state, ship_postal
    's', 'd', 'd', 'd', 'd', 'd', 'i'   // estimate_comments, subtotal, tax_amount, discount_amount, fees_amount, total_amount, created_by
];

$correct_string = implode('', $correct_types);
echo "<p><strong>Correct type string:</strong> $correct_string</p>";
echo "<p><strong>Length:</strong> " . strlen($correct_string) . "</p>";

echo "<br><a href='add_quotation.php'>← Back to Add Quotation</a>";
?>
