<?php
// Count parameters from the actual error message
$actual_params = [
    'QT140', '2025-08-29', 'Draft', 'INR', 'Default', 
    2, 'QT140', 'INR', '', 18.0, 'Percentage', 1,
    '', '', '', 'India', '', '', '', 
    '', '', '', 'India', '', '', '', 
    '', 3030.0, 534.492, 60.6, 0, 3503.892, 1
];

echo "<h2>Parameter Count Analysis</h2>";
echo "<p><strong>Actual parameter count:</strong> " . count($actual_params) . "</p>";

$current_type = "ssssissdsissssssssssssddddds";
echo "<p><strong>Current type string:</strong> $current_type</p>";
echo "<p><strong>Type string length:</strong> " . strlen($current_type) . "</p>";

echo "<h3>Parameters with types:</h3>";
echo "<table border='1'>";
echo "<tr><th>Index</th><th>Value</th><th>Type</th><th>Current Type Char</th></tr>";
foreach ($actual_params as $i => $param) {
    $expected_type = is_int($param) ? 'i' : (is_float($param) ? 'd' : 's');
    $current_char = isset($current_type[$i]) ? $current_type[$i] : 'MISSING';
    $color = ($current_char === 'MISSING') ? 'red' : 'black';
    echo "<tr style='color: $color'>";
    echo "<td>$i</td><td>$param</td><td>$expected_type</td><td>$current_char</td>";
    echo "</tr>";
}
echo "</table>";

// Generate correct type string
$correct_types = [];
foreach ($actual_params as $param) {
    if (is_int($param)) {
        $correct_types[] = 'i';
    } elseif (is_float($param)) {
        $correct_types[] = 'd';
    } else {
        $correct_types[] = 's';
    }
}

$correct_string = implode('', $correct_types);
echo "<h3>Correct Type String:</h3>";
echo "<p><strong>$correct_string</strong></p>";
echo "<p><strong>Length:</strong> " . strlen($correct_string) . "</p>";
?>
