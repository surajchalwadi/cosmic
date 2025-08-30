<?php
// Debug script to count exact parameters being passed
echo "<h2>Parameter Debug Analysis</h2>";

// Count the actual parameters from the error message
$params_from_error = [
    'QT153', '2025-08-29', 'Draft', 'INR', 'Default', 
    2, 'QT153', 'INR', '', 18.0, 'Percentage', 1,
    '', '', '', 'India', '', '', '', 
    '', '', '', 'India', '', '', '', 
    '', 24000.0, 4276.8, 240.0, 0, 28036.8, 'NULL'
];

echo "<p><strong>Actual parameters being passed:</strong> " . count($params_from_error) . "</p>";

// Current type string
$current_type_string = "ssssissdsissssssssssssdddddi";
echo "<p><strong>Current type string:</strong> $current_type_string</p>";
echo "<p><strong>Type string length:</strong> " . strlen($current_type_string) . "</p>";

echo "<h3>Parameter Analysis:</h3>";
echo "<ol>";
foreach ($params_from_error as $i => $param) {
    $type_char = isset($current_type_string[$i]) ? $current_type_string[$i] : 'MISSING';
    $color = ($type_char === 'MISSING') ? 'red' : 'green';
    echo "<li style='color: $color'>$param -> $type_char</li>";
}
echo "</ol>";

// Show the mismatch
$param_count = count($params_from_error);
$type_count = strlen($current_type_string);
echo "<h3>Mismatch Analysis:</h3>";
echo "<p>Parameters: $param_count</p>";
echo "<p>Type string length: $type_count</p>";
echo "<p>Difference: " . ($param_count - $type_count) . "</p>";

// Generate correct type string
echo "<h3>Correct Type String:</h3>";
$correct_types = [];
foreach ($params_from_error as $param) {
    if (is_int($param)) {
        $correct_types[] = 'i';
    } elseif (is_float($param)) {
        $correct_types[] = 'd';
    } else {
        $correct_types[] = 's';
    }
}

$correct_string = implode('', $correct_types);
echo "<p><strong>Correct type string:</strong> $correct_string</p>";
echo "<p><strong>Length:</strong> " . strlen($correct_string) . "</p>";

echo "<br><a href='add_quotation.php'>← Back to Add Quotation</a>";
?>
