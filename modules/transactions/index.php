<?php
/**
 * GameTopUp Pro - Transactions Index
 * Paginated list with search, status/date filters, export buttons
 */

require_once '../../middleware/auth.php';
require_once '../../config/database.php';

$pageTitle = 'Transactions';

$db = getDBConnection();

// Search and filter parameters
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$params = [];
$whereClauses = [];

// Search filter (invoice, customer, game)
if ($search !== '') {
    $whereClauses[] = "(t.invoice_code LIKE :search1 OR t.customer_name LIKE :search2 OR g.name LIKE :search3)";
    $params[':search1'] = '%' . $search . '%';
    $params[':search2'] = '%' . $search . '%';
    $params[':search3'] = '%' . $search . '%';
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

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;

// Get total count
$countSql = "SELECT COUNT(*) FROM transactions t LEFT JOIN games g ON t.game_id = g.id $where";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Get transactions with game and user info
$sql = "SELECT t.*, g.name as game_name, g.thumbnail as game_thumbnail, u.full_name as user_name
        FROM transactions t
        LEFT JOIN games g ON t.game_id = g.id
        LEFT JOIN users u ON t.user_id = u.id
        $where
        ORDER BY t.created_at DESC
        LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);

foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$transactions = $stmt->fetchAll();

// Build export URL with current filters
$exportUrl = 'export.php?type=pdf&' . http_build_query(['search' => $search, 'status' => $status, 'date_from' => $dateFrom, 'date_to' => $dateTo]);
$exportExcelUrl = 'export.php?type=excel&' . http_build_query(['search' => $search, 'status' => $status, 'date_from' => $dateFrom, 'date_to' => $dateTo]);

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<!-- Main Content -->
<main class="lg:ml-64 pt-16 min-h-screen">
    <div class="p-4 lg:p-8">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6"
             x-data="{ 
                lastId: <?php echo $transactions[0]['id'] ?? 0; ?>,
                checkNew() {
                    fetch('../dashboard/check_new.php?last_id=' + this.lastId)
                        .then(res => res.json())
                        .then(data => {
                            if (data.new_order) {
                                window.location.reload();
                            }
                        });
                }
             }" 
             x-init="setInterval(() => checkNew(), 10000)">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Transactions</h1>
                <p class="text-gray-500 mt-1">Manage top-up transactions</p>
                <div class="flex items-center gap-2 mt-2">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                    </span>
                    <span class="text-[10px] font-bold text-green-700 uppercase tracking-widest">Live Active</span>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="<?php echo $exportUrl; ?>" class="inline-flex items-center gap-2 bg-red-600 text-white px-3 py-2 rounded-lg text-sm hover:bg-red-700 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    PDF
                </a>
                <a href="<?php echo $exportExcelUrl; ?>" class="inline-flex items-center gap-2 bg-green-600 text-white px-3 py-2 rounded-lg text-sm hover:bg-green-700 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Excel
                </a>
                <a href="create.php" class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition-colors shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Order
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
            <form method="GET" 
                  hx-get="index.php" 
                  hx-trigger="input from:input[name='search'] delay:500ms, change from:select[name='status'], change from:input[type='date']" 
                  hx-target="#search-results" 
                  hx-select="#search-results" 
                  hx-push-url="true"
                  class="flex flex-col lg:flex-row gap-3">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search invoice, customer, game..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                
                <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="success" <?php echo $status === 'success' ? 'selected' : ''; ?>>Success</option>
                    <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                </select>
                
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Filter</button>
                
                <?php if ($search || $status || $dateFrom || $dateTo): ?>
                    <a href="index.php" class="px-4 py-2 text-gray-500 hover:text-gray-700 text-center">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Search Results Container -->
        <div id="search-results">
            <!-- Transactions Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                        <tr>
                            <th class="px-6 py-3">Invoice</th>
                            <th class="px-6 py-3">Customer</th>
                            <th class="px-6 py-3">Contact</th>
                            <th class="px-6 py-3">Game</th>
                            <th class="px-6 py-3">Item</th>
                            <th class="px-6 py-3">Qty</th>
                            <th class="px-6 py-3">Price</th>
                            <th class="px-6 py-3">Total</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Operator</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="10" class="px-6 py-8 text-center text-gray-500">
                                    No transactions found. <a href="create.php" class="text-indigo-600 hover:underline">Create one</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $txn): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-mono text-xs text-gray-600"><?php echo htmlspecialchars($txn['invoice_code']); ?></td>
                                <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($txn['customer_name']); ?></td>
                                <td class="px-6 py-4 text-gray-600">
                                    <?php if (!empty($txn['contact'])): ?>
                                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $txn['contact']); ?>" target="_blank" class="text-indigo-600 hover:underline inline-flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.898-4.45 9.898-9.898 0-5.448-4.45-9.898-9.898-9.898-5.448 0-9.898 4.45-9.898 9.898 0 2.01.543 3.902 1.571 5.568l-1.096 4.004 4.133-1.066zm10.598-7.859c-.067-.113-.244-.18-.51-.314-.265-.134-1.564-.772-1.807-.861-.242-.089-.418-.134-.594.134-.176.268-.682.861-.836 1.04-.153.179-.307.202-.572.068-.266-.134-1.116-.412-2.126-1.309-.785-.698-1.314-1.558-1.468-1.826-.154-.268-.016-.412.117-.546.12-.122.266-.314.398-.47.133-.157.178-.268.267-.446.088-.179.044-.336-.022-.47-.067-.134-.595-1.436-.814-1.966-.214-.516-.431-.446-.595-.455-.153-.008-.33-.008-.507-.008-.177 0-.464.067-.707.335-.243.268-.925.904-.925 2.202s.948 2.548 1.08 2.726c.133.178 1.862 2.842 4.512 3.985.631.272 1.124.435 1.508.556.634.201 1.21.173 1.666.104.512-.077 1.565-.639 1.786-1.256.222-.617.222-1.144.156-1.256z"/></svg>
                                            <?php echo htmlspecialchars($txn['contact']); ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($txn['game_name'] ?? 'Unknown'); ?></td>
                                <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($txn['item_name']); ?></td>
                                <td class="px-6 py-4 text-gray-600"><?php echo number_format($txn['quantity']); ?></td>
                                <td class="px-6 py-4 text-gray-600">Rp <?php echo number_format($txn['price'], 0, ',', '.'); ?></td>
                                <td class="px-6 py-4 font-medium text-gray-900">Rp <?php echo number_format($txn['total'], 0, ',', '.'); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?php 
                                        echo $txn['status'] === 'success' ? 'bg-green-100 text-green-800' : 
                                             ($txn['status'] === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); 
                                    ?>">
                                        <?php echo ucfirst($txn['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500">
                                    <?php echo htmlspecialchars($txn['user_name'] ?? 'Public'); ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <?php if ($txn['status'] === 'pending'): ?>
                                            <button onclick="updateStatus(<?php echo $txn['id']; ?>, 'success')" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">Success</button>
                                            <button onclick="updateStatus(<?php echo $txn['id']; ?>, 'failed')" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Failed</button>
                                        <?php endif; ?>
                                        <a href="edit.php?id=<?php echo $txn['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100">Edit</a>
                                        <button onclick="confirmDelete(<?php echo $txn['id']; ?>, '<?php echo htmlspecialchars(addslashes($txn['invoice_code']), ENT_QUOTES); ?>')" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 rounded-lg hover:bg-red-100">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <p class="text-sm text-gray-500">Showing <?php echo $offset + 1; ?>–<?php echo min($offset + $perPage, $totalRows); ?> of <?php echo $totalRows; ?> results</p>
                <div class="flex items-center gap-1">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>" class="px-3 py-1 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Previous</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="px-3 py-1 text-sm font-medium text-white bg-indigo-600 rounded-lg"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>" class="px-3 py-1 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>" class="px-3 py-1 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Next</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        </div>
    </div>
</main>

<!-- Delete Form -->
<form id="deleteForm" method="POST" action="delete.php" class="hidden">
    <input type="hidden" name="id" id="deleteId">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
</form>

<!-- Status Update Form -->
<form id="statusForm" method="POST" action="update_status.php" class="hidden">
    <input type="hidden" name="id" id="statusId">
    <input type="hidden" name="status" id="statusValue">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
</form>

<script>
function updateStatus(id, status) {
    const title = status === 'success' ? 'Mark as Success?' : 'Mark as Failed?';
    const text = status === 'success' ? 'This will confirm the transaction is completed.' : 'This will mark the transaction as failed.';
    const color = status === 'success' ? '#16a34a' : '#dc2626';

    Swal.fire({
        title: title,
        text: text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: color,
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, proceed',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('statusId').value = id;
            document.getElementById('statusValue').value = status;
            document.getElementById('statusForm').submit();
        }
    });
}
function confirmDelete(id, invoice) {
    Swal.fire({
        title: 'Delete Transaction?',
        text: 'Are you sure you want to delete transaction ' + invoice + '? This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteForm').submit();
        }
    });
}
</script>

<?php require_once '../../includes/footer.php'; ?>
