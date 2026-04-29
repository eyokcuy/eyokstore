<?php
/**
 * GameTopUp Pro - Dashboard
 * Summary cards, charts, and recent transactions
 */

require_once '../../middleware/auth.php';
require_once '../../config/database.php';

$pageTitle = 'Dashboard';

$db = getDBConnection();

// Summary statistics
$totalTransactions = $db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
$totalRevenue = $db->query("SELECT COALESCE(SUM(total), 0) FROM transactions WHERE status = 'success'")->fetchColumn();
$totalGames = $db->query("SELECT COUNT(*) FROM games WHERE status = 'active'")->fetchColumn();
$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Transactions per day (last 7 days)
$chartData = $db->query("
    SELECT DATE(created_at) as date, COUNT(*) as count
    FROM transactions
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_KEY_PAIR);

$last7Days = [];
$counts = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $last7Days[] = date('M d', strtotime($date));
    $counts[] = $chartData[$date] ?? 0;
}

// Status breakdown
$statusData = $db->query("
    SELECT status, COUNT(*) as count
    FROM transactions
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$statusLabels = ['pending', 'success', 'failed'];
$statusCounts = [];
foreach ($statusLabels as $status) {
    $statusCounts[] = $statusData[$status] ?? 0;
}

// Recent transactions
$recentTransactions = $db->query("
    SELECT t.*, g.name as game_name
    FROM transactions t
    JOIN games g ON t.game_id = g.id
    ORDER BY t.created_at DESC
    LIMIT 10
")->fetchAll();

// Status badge helper
function getStatusBadge(string $status): string {
    $classes = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'success' => 'bg-green-100 text-green-800',
        'failed' => 'bg-red-100 text-red-800',
    ];
    return $classes[$status] ?? 'bg-gray-100 text-gray-800';
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<!-- Main Content -->
<main class="lg:ml-64 pt-16 min-h-screen">
    <div class="p-4 lg:p-8">
        <!-- Page Title -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4" 
             x-data="{ 
                lastId: <?php echo $recentTransactions[0]['id'] ?? 0; ?>,
                checkNew() {
                    fetch('check_new.php?last_id=' + this.lastId)
                        .then(res => res.json())
                        .then(data => {
                            if (data.new_order) {
                                // Ada pesanan baru!
                                Swal.fire({
                                    title: 'New Order!',
                                    text: 'A new top-up request has just arrived.',
                                    icon: 'info',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    timerProgressBar: true
                                });
                                // Refresh halaman setelah sedikit delay agar admin sempat lihat toast
                                setTimeout(() => window.location.reload(), 1500);
                            }
                        });
                }
             }" 
             x-init="setInterval(() => checkNew(), 10000)">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-gray-500 mt-1">Overview of your game top-up business</p>
            </div>
            <div class="flex items-center gap-2 px-3 py-1.5 bg-green-50 border border-green-100 rounded-full">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                <span class="text-xs font-bold text-green-700 uppercase tracking-wider">Live Monitoring Active</span>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- Total Transactions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Transactions</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo number_format($totalTransactions); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">Rp <?php echo number_format($totalRevenue, 0, ',', '.'); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Games -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Active Games</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo number_format($totalGames); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Users -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Users</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1"><?php echo number_format($totalUsers); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Bar Chart -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Transactions (Last 7 Days)</h3>
                <div class="h-64">
                    <canvas id="transactionsChart"></canvas>
                </div>
            </div>

            <!-- Doughnut Chart -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Transaction Status</h3>
                <div class="h-64 flex items-center justify-center">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Recent Transactions</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                        <tr>
                            <th class="px-6 py-3">Invoice</th>
                            <th class="px-6 py-3">Game</th>
                            <th class="px-6 py-3">Customer</th>
                            <th class="px-6 py-3">Total</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($recentTransactions as $t): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($t['invoice_code']); ?></td>
                            <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($t['game_name']); ?></td>
                            <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($t['customer_name']); ?></td>
                            <td class="px-6 py-4 font-medium text-gray-900">Rp <?php echo number_format($t['total'], 0, ',', '.'); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getStatusBadge($t['status']); ?>">
                                    <?php echo ucfirst($t['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500"><?php echo date('M d, Y H:i', strtotime($t['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
    // Bar Chart
    new Chart(document.getElementById('transactionsChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($last7Days); ?>,
            datasets: [{
                label: 'Transactions',
                data: <?php echo json_encode($counts); ?>,
                backgroundColor: 'rgba(79, 70, 229, 0.8)',
                borderColor: 'rgb(79, 70, 229)',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });

    // Doughnut Chart
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Success', 'Failed'],
            datasets: [{
                data: <?php echo json_encode($statusCounts); ?>,
                backgroundColor: [
                    'rgb(234, 179, 8)',
                    'rgb(34, 197, 94)',
                    'rgb(239, 68, 68)'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 20, usePointStyle: true }
                }
            }
        }
    });
</script>

<?php require_once '../../includes/footer.php'; ?>

