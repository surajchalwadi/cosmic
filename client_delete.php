<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';

// Get client ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Client ID not provided.";
    header("Location: client_list.php");
    exit;
}

$client_id = (int)$_GET['id'];

// Check if client exists
$check_query = "SELECT client_name FROM clients WHERE client_id = $client_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    $_SESSION['error'] = "Client not found.";
    header("Location: client_list.php");
    exit;
}

$client = mysqli_fetch_assoc($check_result);

// Check if client is referenced in estimates
$estimate_check = "SELECT COUNT(*) as count FROM estimates WHERE client_id = $client_id";
$estimate_result = mysqli_query($conn, $estimate_check);
$estimate_count = mysqli_fetch_assoc($estimate_result)['count'];

if ($estimate_count > 0) {
    $_SESSION['error'] = "Cannot delete client '{$client['client_name']}' because it is referenced in $estimate_count estimate(s). Please remove or reassign the estimates first.";
    header("Location: client_list.php");
    exit;
}

// Delete client
$delete_query = "DELETE FROM clients WHERE client_id = $client_id";

if (mysqli_query($conn, $delete_query)) {
    $_SESSION['success'] = "Client '{$client['client_name']}' deleted successfully!";
} else {
    $_SESSION['error'] = "Error deleting client: " . mysqli_error($conn);
}

header("Location: client_list.php");
exit;
?>
