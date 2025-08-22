<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'sales'])) {
    header("Location: index.php");
    exit;
}

include 'config/db.php';
$role = $_SESSION['user']['role'];

// Fetch estimates that have been converted to invoices
$query = "SELECT e.*, c.client_name 
          FROM estimates e
          LEFT JOIN clients c ON e.client_id = c.client_id
          WHERE e.invoice_created = 1
          ORDER BY e.created_at DESC";
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
                    <h4 class="form-title"><i class="fas fa-file-invoice-dollar me-2"></i>Invoice List</h4>
                    <small class="text-muted">Quotations converted to invoices</small>
                </div>
                <a href="quotation_list.php" class="btn btn-outline-warning">
                    <i class="fas fa-arrow-left me-1"></i> Back to Quotations
                </a>
            </div>

            <!-- Search Bar -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="input-group">
                        <span class="input-group-text bg-success text-white">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" placeholder="Search invoices..." id="searchInput">
                    </div>
                </div>
            </div>

            <!-- Invoice Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="invoiceTable">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">Sr.NO</th>
                            <th width="15%">Invoice </th>
                            <th width="12%">Date</th>
                            <th width="20%">Bill To</th>
                            <th width="20%">Ship To</th>
                            <th width="10%">Status</th>
                            <th width="15%">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php 
                            $i = 0;
                            while ($row = mysqli_fetch_assoc($result)): 
                            ?>
                                <?php
                                // Status badge - show as Invoice since it's converted
                                $status_class = 'bg-success';
                                $status_text = 'Invoice Created';
                                
                                // Currency symbol
                                $currency_symbols = [
                                    'INR' => '₹',
                                    'USD' => '$',
                                    'EUR' => '€',
                                    'GBP' => '£',
                                    'JPY' => '¥'
                                ];
                                $currency = $row['currency'] ?? 'INR';
                                $symbol = $currency_symbols[$currency] ?? $currency . ' ';
                                ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <a href="quotation_print.php?id=<?= $row['estimate_id'] ?>" target="_blank">
                                            <strong><?= htmlspecialchars($row['estimate_number']) ?></strong>
                                        </a>
                                    </td>
                                    <td>
                                        <?= date('M d, Y', strtotime($row['estimate_date'])) ?>
                                    </td>
                                    <td>
                                        <div>
                                            <?php if (!empty($row['bill_client_name'])): ?>
                                                <strong><?= htmlspecialchars($row['bill_client_name']) ?></strong>
                                            <?php endif; ?>
                                            <?php if (!empty($row['bill_company'])): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($row['bill_company']) ?></small>
                                            <?php endif; ?>
                                            <?php if (empty($row['bill_client_name']) && empty($row['bill_company'])): ?>
                                                <span class="text-muted">Not specified</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <?php if (!empty($row['ship_client_name'])): ?>
                                                <strong><?= htmlspecialchars($row['ship_client_name']) ?></strong>
                                            <?php endif; ?>
                                            <?php if (!empty($row['ship_company'])): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($row['ship_company']) ?></small>
                                            <?php endif; ?>
                                            <?php if (empty($row['ship_client_name']) && empty($row['ship_company'])): ?>
                                                <span class="text-muted">Not specified</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                                    </td>
                                    <td>
                                        <strong><?= $symbol . ' ' . number_format($row['total_amount'], 2) ?></strong>
                                    </td>
                                </tr>
                            <?php 
                                $i++;
                            endwhile; 
                            ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-file-invoice-dollar fa-3x mb-3"></i>
                                        <h5>No Invoices Found</h5>
                                        <p>Convert quotations to invoices to see them here</p>
                                        <a href="quotation_list.php" class="btn btn-warning">
                                            <i class="fas fa-file-invoice me-1"></i> View Quotations
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
                        Showing <?= mysqli_num_rows($result) ?> invoices
                    </small>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1">Previous</a>
                            </li>
                            <li class="page-item active">
                                <a class="page-link" href="#">1</a>
                            </li>
                            <li class="page-item disabled">
                                <a class="page-link" href="#">Next</a>
                            </li>
                        </ul>
                    </nav>
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
    const tableRows = document.querySelectorAll('#invoiceTable tbody tr');
    
    tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});
</script>

</body>
</html>
