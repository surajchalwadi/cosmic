<?php
// Setup test users for notification system testing
include 'config/db.php';

// Password hash for "password"
$passwordHash = password_hash('password', PASSWORD_DEFAULT);

// Test users data
$testUsers = [
    [
        'name' => 'Sales Manager',
        'email' => 'sales@cosmic.com',
        'password' => $passwordHash,
        'role' => 'sales'
    ],
    [
        'name' => 'Inventory Manager', 
        'email' => 'inventory@cosmic.com',
        'password' => $passwordHash,
        'role' => 'inventory'
    ],
    [
        'name' => 'John Sales',
        'email' => 'john@cosmic.com', 
        'password' => $passwordHash,
        'role' => 'sales'
    ],
    [
        'name' => 'Mary Inventory',
        'email' => 'mary@cosmic.com',
        'password' => $passwordHash,
        'role' => 'inventory'
    ]
];

echo "<h2>Creating Test Users...</h2>";

foreach ($testUsers as $user) {
    // Check if user already exists
    $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $user['email']);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<p>✓ User {$user['email']} already exists</p>";
    } else {
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, 1)");
        $stmt->bind_param("ssss", $user['name'], $user['email'], $user['password'], $user['role']);
        
        if ($stmt->execute()) {
            echo "<p>✓ Created user: {$user['name']} ({$user['email']}) - Role: {$user['role']}</p>";
        } else {
            echo "<p>✗ Failed to create user: {$user['email']}</p>";
        }
        $stmt->close();
    }
    $checkStmt->close();
}

echo "<h3>Test Login Credentials:</h3>";
echo "<ul>";
echo "<li><strong>Sales Manager:</strong> sales@cosmic.com / password</li>";
echo "<li><strong>Inventory Manager:</strong> inventory@cosmic.com / password</li>";
echo "<li><strong>John Sales:</strong> john@cosmic.com / password</li>";
echo "<li><strong>Mary Inventory:</strong> mary@cosmic.com / password</li>";
echo "</ul>";

echo "<p><strong>Instructions:</strong></p>";
echo "<ol>";
echo "<li>Login as Admin to see notification bell</li>";
echo "<li>Open another browser tab/window</li>";
echo "<li>Login as Sales or Inventory user</li>";
echo "<li>Create quotations, purchases, or clients</li>";
echo "<li>Switch back to Admin tab to see notifications</li>";
echo "</ol>";

$conn->close();
?>
