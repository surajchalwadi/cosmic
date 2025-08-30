<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';
$role = $_SESSION['user']['role'];

// Get client ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Client ID not provided.";
    header("Location: client_list.php");
    exit;
}

$client_id = (int)$_GET['id'];

// Fetch client data
$query = "SELECT * FROM clients WHERE client_id = $client_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = "Client not found.";
    header("Location: client_list.php");
    exit;
}

$client = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'head.php'; ?>
<body>

<?php include 'sidebar.php'; ?>
<div class="main">
    <?php include 'header.php'; ?>
    <div class="container-fluid py-4">
        <div class="form-section">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <div class="form-header d-flex justify-content-between align-items-center mb-4">
                <h4 class="form-title"><i class="fas fa-user-edit me-2"></i>Edit Client</h4>
                <div>
                    <a href="client_list.php" class="btn btn-secondary me-2">
                        <i class="fas fa-list me-1"></i> View Clients
                    </a>
                </div>
            </div>

            <form action="client_update.php" method="POST">
                <input type="hidden" name="client_id" value="<?= $client['client_id'] ?>">
                
                <!-- Basic Information Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Client Name <span class="text-danger">*</span></label>
                                <input type="text" name="client_name" class="form-control" 
                                       value="<?= htmlspecialchars($client['client_name']) ?>" required>
                                <small class="text-muted">Full name of the client</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Company</label>
                                <input type="text" name="company" class="form-control" 
                                       value="<?= htmlspecialchars($client['company']) ?>">
                                <small class="text-muted">Company or organization name</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($client['email']) ?>">
                                <small class="text-muted">Primary email address</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($client['phone']) ?>">
                                <small class="text-muted">Primary contact number</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($client['address']) ?></textarea>
                                <small class="text-muted">Complete address including street, area, etc.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location Information Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Location Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" class="form-control" 
                                       value="<?= htmlspecialchars($client['country']) ?>">
                                <small class="text-muted">Country name</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">State</label>
                                <input type="text" name="state" class="form-control" 
                                       value="<?= htmlspecialchars($client['state']) ?>">
                                <small class="text-muted">State or province</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" 
                                       value="<?= htmlspecialchars($client['city']) ?>">
                                <small class="text-muted">City or town</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="postal" class="form-control" 
                                       value="<?= htmlspecialchars($client['postal']) ?>">
                                <small class="text-muted">ZIP or postal code</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="Active" <?= $client['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                                    <option value="Inactive" <?= $client['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                                <small class="text-muted">Client status</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-end gap-2">
                    <a href="client_list.php" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Update Client
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
