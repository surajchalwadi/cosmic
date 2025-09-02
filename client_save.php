<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $client_name = mysqli_real_escape_string($conn, $_POST['client_name']);
    $company = mysqli_real_escape_string($conn, $_POST['company']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $country = mysqli_real_escape_string($conn, $_POST['country']);
    $state = mysqli_real_escape_string($conn, $_POST['state']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $postal = mysqli_real_escape_string($conn, $_POST['postal']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    // Handle created_by field safely
    $created_by = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null;

    // Validate required fields
    if (empty($client_name)) {
        $_SESSION['error'] = "Client name is required.";
        header("Location: add_client.php");
        exit;
    }

    // Check if email already exists (if provided)
    if (!empty($email)) {
        $check_email = "SELECT client_id FROM clients WHERE email = '$email'";
        $email_result = mysqli_query($conn, $check_email);
        if (mysqli_num_rows($email_result) > 0) {
            $_SESSION['error'] = "Email address already exists.";
            header("Location: add_client.php");
            exit;
        }
    }

    // Insert client using prepared statement
    $stmt = mysqli_prepare($conn, "INSERT INTO clients (client_name, company, email, phone, address, country, state, city, postal, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'ssssssssssi', $client_name, $company, $email, $phone, $address, $country, $state, $city, $postal, $status, $created_by);

    if (mysqli_stmt_execute($stmt)) {
        $client_id = mysqli_insert_id($conn);
        // Create admin notification for new client
        include 'includes/notification_functions.php';
        if ($_SESSION['user']['role'] === 'sales') {
            $clientData = [
                'client_id' => $client_id,
                'client_name' => $client_name,
                'company' => $company
            ];
            
            notifyNewClientAdded($_SESSION['user']['id'], $_SESSION['user']['name'], $clientData);
        }
        
        $_SESSION['success'] = "Client added successfully!";
        header("Location: client_list.php");
    } else {
        $_SESSION['error'] = "Error adding client: " . mysqli_error($conn);
        header("Location: add_client.php");
    }
    mysqli_stmt_close($stmt);
} else {
    header("Location: add_client.php");
}
exit;
?>
