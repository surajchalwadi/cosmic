<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $client_id = (int)$_POST['client_id'];
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

    // Validate required fields
    if (empty($client_name)) {
        $_SESSION['error'] = "Client name is required.";
        header("Location: client_edit.php?id=$client_id");
        exit;
    }

    // Check if email already exists for other clients (if provided)
    if (!empty($email)) {
        $check_email = "SELECT client_id FROM clients WHERE email = '$email' AND client_id != $client_id";
        $email_result = mysqli_query($conn, $check_email);
        if (mysqli_num_rows($email_result) > 0) {
            $_SESSION['error'] = "Email address already exists for another client.";
            header("Location: client_edit.php?id=$client_id");
            exit;
        }
    }

    // Update client
    $query = "UPDATE clients SET 
              client_name = '$client_name',
              company = '$company',
              email = '$email',
              phone = '$phone',
              address = '$address',
              country = '$country',
              state = '$state',
              city = '$city',
              postal = '$postal',
              status = '$status',
              updated_at = CURRENT_TIMESTAMP
              WHERE client_id = $client_id";

    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Client updated successfully!";
        header("Location: client_list.php");
    } else {
        $_SESSION['error'] = "Error updating client: " . mysqli_error($conn);
        header("Location: client_edit.php?id=$client_id");
    }
} else {
    header("Location: client_list.php");
}
exit;
?>
