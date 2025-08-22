<?php
session_start();
include 'config/db.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'inventory'])) {
    die('Unauthorized');
}

$purchase_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$purchase_id) die('Invalid ID');

// Get purchase details
$sql = "SELECT pi.*, u.name as created_by_name 
        FROM purchase_invoices pi 
        LEFT JOIN users u ON pi.created_by = u.user_id 
        WHERE pi.purchase_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $purchase_id);
mysqli_stmt_execute($stmt);
$purchase = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$purchase) die('Not found');

// Get purchase items
$sql = "SELECT * FROM purchase_items WHERE purchase_id = ? ORDER BY item_id";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $purchase_id);
mysqli_stmt_execute($stmt);
$items = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Invoice - <?= htmlspecialchars($purchase['invoice_no']) ?></title>
    <style>
        @media print { .no-print { display: none !important; } }
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .header { background: #ffffff; color: #1d1d1f; padding: 30px; border-bottom: 3px solid #155ba3; }
        .company-info { display: flex; justify-content: space-between; }
        .company-logo { font-size: 24px; font-weight: bold; color: #155ba3; }
        .invoice-title { text-align: right; font-size: 28px; font-weight: bold; color: #155ba3; }
        .content { padding: 30px; }
        .client-section { display: flex; gap: 30px; margin-bottom: 30px; }
        .bill-to, .ship-to { flex: 1; background: #f8f9fa; padding: 20px; border-left: 4px solid #155ba3; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th { background: #155ba3; color: white; padding: 12px; text-align: left; }
        .items-table td { padding: 12px; border-bottom: 1px solid #eee; }
        .summary-table { width: 300px; margin-left: auto; }
        .summary-table td { padding: 8px 15px; border-bottom: 1px solid #eee; }
        .total-row { font-weight: bold; font-size: 18px; color: #155ba3; border-top: 2px solid #155ba3; }
        .print-btn { background: #155ba3; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin: 5px; }
    </style>
</head>
<body>
    <?php if (!isset($_GET['download'])): ?>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button class="print-btn" onclick="window.print()">Print</button>
        <button class="print-btn" onclick="window.close()">Close</button>
        <a href="purchase_list.php" class="print-btn" style="text-decoration: none;">Back to List</a>
    </div>
    <?php else: ?>
    <script>
        // Auto-trigger print dialog for PDF download
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
    <?php endif; ?>

    <div class="container">
        <div class="header">
            <div class="company-info">
                <div>
                    <?php 
                        $logoCandidates = ['assets/img/logo.png', 'assets/img/logo-cosmic.png'];
                        $logoPath = null;
                        foreach ($logoCandidates as $candidate) {
                            if (file_exists($candidate)) { $logoPath = $candidate; break; }
                        }
                    ?>
                    <?php if ($logoPath): ?>
                        <img src="<?= $logoPath ?>?v=<?= @filemtime($logoPath) ?: time() ?>" alt="Cosmic Solutions" style="height:60px; margin-bottom:10px; display:block;">
                    <?php endif; ?>
                    <div class="company-logo">Cosmic Solutions</div>
                    <div style="font-size: 12px; line-height: 1.4;">
                        EF-102, 1st Floor, E-boshan Building<br>
                        Boshan Hotels, Opp. Bodgeshwar Temple<br>
                        Mapusa - Goa. GSTN: 30AAMFC9553C1ZN<br>
                        Goa 403507<br>
                        Email: prajyot@cosmicsolutions.co.in<br>
                        Phone: 8390831122
                    </div>
                </div>
                <div>
                    <div class="invoice-title">PURCHASE INVOICE</div>
                    <div style="text-align: right; font-size: 14px;">
                        <div><strong>Invoice Number:</strong> <?= htmlspecialchars($purchase['invoice_no']) ?></div>
                        <div><strong>Purchase ID:</strong> <?= $purchase_id ?></div>
                        <div><strong>Delivery Date:</strong> <?= date('d-m-Y', strtotime($purchase['delivery_date'])) ?></div>
                        <div><strong>Status:</strong> Completed</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="client-section">
                <div class="bill-to">
                    <div style="font-weight: bold; color: #155ba3; margin-bottom: 15px;">Supplier Details</div>
                    <div style="font-weight: bold; font-size: 16px; margin-bottom: 10px;"><?= htmlspecialchars($purchase['party_name']) ?></div>
                    <div style="font-size: 14px; line-height: 1.4; color: #555;">
                        <div><strong>Invoice No:</strong> <?= htmlspecialchars($purchase['invoice_no']) ?></div>
                        <div><strong>Delivery Date:</strong> <?= date('d-m-Y', strtotime($purchase['delivery_date'])) ?></div>
                    </div>
                </div>
                
                <div class="ship-to">
                    <div style="font-weight: bold; color: #155ba3; margin-bottom: 15px;">Purchase Details</div>
                    <div style="font-size: 14px; line-height: 1.4; color: #555;">
                        <div><strong>Created By:</strong> <?= htmlspecialchars($purchase['created_by_name'] ?? 'System') ?></div>
                        <div><strong>Created Date:</strong> <?= date('d-m-Y H:i', strtotime($purchase['created_at'])) ?></div>
                        <div><strong>Purchase ID:</strong> <?= $purchase_id ?></div>
                    </div>
                </div>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">SR. NO.</th>
                        <th style="width: 10%;">QTY</th>
                        <th style="width: 37%;">PRODUCT</th>
                        <th style="width: 22%;">UNIT PRICE</th>
                        <th style="width: 23%;">LINE TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grand_total = 0;
                    $sr_no = 1;
                    // Reset the result pointer
                    mysqli_data_seek($items, 0);
                    while ($item = mysqli_fetch_assoc($items)): 
                        $grand_total += $item['total_price'];
                    ?>
                        <tr>
                            <td style="text-align: center; font-weight: bold;"><?= $sr_no++ ?></td>
                            <td style="text-align: center;"><?= number_format($item['quantity'], 0) ?></td>
                            <td style="font-weight: bold; color: #155ba3;"><?= htmlspecialchars($item['product_name']) ?></td>
                            <td style="text-align: right;">₹<?= number_format($item['price'], 2) ?></td>
                            <td style="text-align: right;">₹<?= number_format($item['total_price'], 2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <table class="summary-table">
                <tr class="total-row">
                    <td>TOTAL:</td>
                    <td style="text-align: right;">₹<?= number_format($grand_total, 2) ?></td>
                </tr>
            </table>

            <?php if (!empty($purchase['notes'])): ?>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin-top: 30px;">
                <div style="font-weight: bold; color: #155ba3; margin-bottom: 15px;">Notes</div>
                <div style="font-size: 14px; line-height: 1.4; color: #555;">
                    <?= nl2br(htmlspecialchars($purchase['notes'])) ?>
                </div>
            </div>
            <?php endif; ?>

            <div style="text-align: center; padding: 20px; background: #f8f9fa; border-top: 1px solid #eee; margin-top: 30px; font-size: 14px; color: #666;">
                Thank you for your business with Cosmic Solutions
            </div>
        </div>
    </div>
</body>
</html>