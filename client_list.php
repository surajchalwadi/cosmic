<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';
$role = $_SESSION['user']['role'];

// Fetch clients
$query = "SELECT * FROM clients ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
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
                <div>
                    <h4 class="form-title"><i class="fas fa-users me-2"></i>Client List</h4>
                    <small class="text-muted">Manage your Clients</small>
                </div>
                <a href="add_client.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add Client
                </a>
            </div>

            <!-- Search Bar -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" placeholder="Search clients..." id="searchInput">
                    </div>
                </div>
            </div>

            <!-- Clients Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="clientTable">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <th width="5%"></th>
                            <th width="20%">Client Name</th>
                            <th width="20%">Company</th>
                            <th width="15%">Email</th>
                            <th width="12%">Phone</th>
                            <th width="15%">Location</th>
                            <th width="8%">Status</th>
                            <th width="10%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <?php
                                $status_class = $row['status'] == 'Active' ? 'bg-success' : 'bg-secondary';
                                $location = trim($row['city'] . ', ' . $row['state'], ', ');
                                ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input row-checkbox">
                                    </td>
                                    <td>
                                        <div class="client-avatar">
                                            <i class="fas fa-user-circle fa-2x text-primary"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($row['client_name']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            <?= htmlspecialchars($row['company'] ?: 'N/A') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['email']): ?>
                                            <a href="mailto:<?= htmlspecialchars($row['email']) ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($row['email']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['phone']): ?>
                                            <a href="tel:<?= htmlspecialchars($row['phone']) ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($row['phone']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-muted"><?= htmlspecialchars($location ?: 'N/A') ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $status_class ?>"><?= $row['status'] ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="client_edit.php?id=<?= $row['client_id'] ?>" 
                                               class="btn btn-outline-primary btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="client_delete.php?id=<?= $row['client_id'] ?>" 
                                               class="btn btn-outline-danger btn-sm" 
                                               onclick="return confirm('Are you sure you want to delete this client?')" 
                                               title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-users fa-3x mb-3"></i>
                                        <h5>No Clients Found</h5>
                                        <p>Start by adding your first client</p>
                                        <a href="add_client.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i> Add Client
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">
                        Showing <?= mysqli_num_rows($result) ?> clients
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const tableRows = document.querySelectorAll('#clientTable tbody tr');
    
    tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Select all checkbox functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

</script>

<!-- Search Highlighting Script -->
<script src="assets/js/highlight.js"></script>

</body>
</html>
