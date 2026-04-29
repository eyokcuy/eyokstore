<?php
/**
 * GameTopUp Pro - Export Transactions
 * PDF via jsPDF + AutoTable, Excel via SheetJS
 */

require_once '../../middleware/auth.php';
require_once '../../config/database.php';

$type = $_GET['type'] ?? 'pdf';
if (!in_array($type, ['pdf', 'excel'])) {
    $type = 'pdf';
}

$db = getDBConnection();

// Search and filter parameters
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$params = [];
$whereClauses = [];

// Search filter
if ($search !== '') {
    $whereClauses[] = "(t.invoice_code LIKE :search OR t.customer_name LIKE :search OR g.name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

// Status filter
if ($status !== '' && in_array($status, ['pending', 'success', 'failed'])) {
    $whereClauses[] = "t.status = :status";
    $params[':status'] = $status;
}

// Date range filters
if ($dateFrom !== '') {
    $whereClauses[] = "DATE(t.created_at) >= :date_from";
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $whereClauses[] = "DATE(t.created_at) <= :date_to";
    $params[':date_to'] = $dateTo;
}

$where = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Get all filtered transactions
$sql = "SELECT t.*, g.name as game_name 
        FROM transactions t
        LEFT JOIN games g ON t.game_id = g.id
        $where
        ORDER BY t.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$pageTitle = 'Export ' . strtoupper($type);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exporting...</title>
    
    <?php if ($type === 'pdf'): ?>
        <!-- jsPDF & AutoTable -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <?php else: ?>
        <!-- SheetJS -->
        <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
    <?php endif; ?>
    
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="text-center">
        <svg class="animate-spin h-10 w-10 text-indigo-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <h2 class="text-xl font-semibold text-gray-900">Generating <?php echo strtoupper($type); ?>...</h2>
        <p class="text-gray-500 mt-2">Please wait while your file is being prepared.</p>
        <p class="text-sm mt-4 text-gray-400">You will be redirected back automatically.</p>
    </div>

    <!-- Hidden Table with Data -->
    <table id="exportTable" style="display: none;">
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Customer</th>
                <th>Game</th>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $txn): ?>
            <tr>
                <td><?php echo htmlspecialchars($txn['invoice_code']); ?></td>
                <td><?php echo htmlspecialchars($txn['customer_name']); ?></td>
                <td><?php echo htmlspecialchars($txn['game_name'] ?? 'Unknown'); ?></td>
                <td><?php echo htmlspecialchars($txn['item_name']); ?></td>
                <td><?php echo number_format($txn['quantity']); ?></td>
                <td><?php echo number_format($txn['price'], 2); ?></td>
                <td><?php echo number_format($txn['total'], 2); ?></td>
                <td><?php echo ucfirst($txn['status']); ?></td>
                <td><?php echo date('Y-m-d H:i:s', strtotime($txn['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const type = '<?php echo $type; ?>';
        const dateStr = new Date().toISOString().split('T')[0];
        const filename = 'Transactions_' + dateStr;

        if (type === 'pdf') {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('landscape');
            
            doc.text('GameTopUp Pro - Transactions Report', 14, 15);
            doc.setFontSize(10);
            doc.text('Generated on: ' + new Date().toLocaleString(), 14, 22);

            doc.autoTable({
                html: '#exportTable',
                startY: 28,
                theme: 'striped',
                headStyles: { fillColor: [79, 70, 229] }, // indigo-600
                styles: { fontSize: 9 }
            });

            doc.save(filename + '.pdf');
            
            // Go back after a short delay
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1000);
            
        } else if (type === 'excel') {
            const table = document.getElementById('exportTable');
            const wb = XLSX.utils.table_to_book(table, {sheet: "Transactions"});
            XLSX.writeFile(wb, filename + '.xlsx');
            
            // Go back after a short delay
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1000);
        }
    });
    </script>
</body>
</html>
