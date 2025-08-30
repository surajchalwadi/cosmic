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
    $created_by = $_SESSION['user']['user_id'];

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

    // Insert client
    $query = "INSERT INTO clients (client_name, company, email, phone, address, country, state, city, postal, status, created_by) 
              VALUES ('$client_name', '$company', '$email', '$phone', '$address', '$country', '$state', '$city', '$postal', '$status', '$created_by')";

    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Client added successfully!";
        header("Location: client_list.php");
    } else {
        $_SESSION['error'] = "Error adding client: " . mysqli_error($conn);
        header("Location: add_client.php");
    }
} else {
    header("Location: add_client.php");
}
exit;
?>
