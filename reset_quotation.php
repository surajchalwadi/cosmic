<?php
// Quick script to reset a quotation for testing email functionality
session_start();
include 'config/db.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'sales'])) {
    die('Unauthorized access');
}

echo "<h2>Reset Quotation for Email Testing</h2>";

// Find quotations that have been converted to invoices
$query = "SELECT e.estimate_id, e.estimate_number, c.client_name, c.email 
          FROM estimates e 
          LEFT JOIN clients c ON e.client_id = c.client_id 
          WHERE e.invoice_created = 1 AND c.email IS NOT NULL 
          LIMIT 5";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<h3>Available Quotations to Reset:</h3>";
    echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
    echo "<tr><th>Quotation #</th><th>Client</th><th>Email</th><th>Action</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['estimate_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['client_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td><a href='?reset=" . $row['estimate_id'] . "' onclick='return confirm(\"Reset this quotation?\")'>Reset</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No converted quotations found with client emails.</p>";
}

// Handle reset request
if (isset($_GET['reset'])) {
    $estimate_id = (int)$_GET['reset'];
    
    $update_query = "UPDATE estimates SET invoice_created = 0 WHERE estimate_id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "i", $estimate_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<div style='color: green; font-weight: bold; margin: 20px 0;'>✅ Quotation reset successfully! You can now test email conversion.</div>";
        echo "<a href='quotation_list.php'>→ Go to Quotations List</a>";
    } else {
        echo "<div style='color: red;'>❌ Failed to reset quotation.</div>";
    }
}

echo "<br><br><a href='quotation_list.php'>← Back to Quotations</a>";
?>
