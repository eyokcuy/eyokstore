<?php
/**
 * GameTopUp Pro - Games Index
 * Paginated list with thumbnail, category name, status badge
 */

require_once '../../middleware/auth.php';
require_once '../../config/database.php';

$pageTitle = 'Games';

$db = getDBConnection();

// Search filter
$search = trim($_GET['search'] ?? '');
$params = [];

// Build query
$where = '';
if ($search !== '') {
    $where = 'WHERE g.name LIKE :search';
    $params[':search'] = '%' . $search . '%';
}

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;

// Get total count
$countSql = "SELECT COUNT(*) FROM games g $where";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Get games with category info
$sql = "SELECT g.*, c.name as category_name,
        (SELECT COUNT(*) FROM transactions WHERE game_id = g.id) as transactions_count
        FROM games g
        LEFT JOIN categories c ON g.category_id = c.id
        $where
        ORDER BY g.created_at DESC
        LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);

foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$games = $stmt->fetchAll();

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<!-- Main Content -->
<main class="lg:ml-64 pt-16 min-h-screen">
    <div class="p-4 lg:p-8">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Games</h1>
                <p class="text-gray-500 mt-1">Manage games and their details</p>
            </div>
            <a href="create.php" class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Game
            </a>
        </div>

        <!-- Search -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
            <form method="GET" 
                  hx-get="index.php" 
                  hx-trigger="input from:input[name='search'] delay:500ms" 
                  hx-target="#search-results" 
                  hx-select="#search-results" 
                  hx-push-url="true"
                  class="flex gap-3">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by game name..." class="flex-1 max-w-md px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Search</button>
                <?php if ($search): ?>
                    <a href="index.php" class="px-4 py-2 text-gray-500 hover:text-gray-700">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Search Results Container -->
        <div id="search-results">
            <!-- Games Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">Thumbnail</th>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Category</th>
                                <th class="px-6 py-3">Publisher</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($games)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        <?php echo $search ? 'No games found matching your search.' : 'No games yet. <a href="create.php" class="text-indigo-600 hover:underline">Add one</a>'; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($games as $game): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <?php if (!empty($game['thumbnail']) && file_exists('../../uploads/thumbnails/' . $game['thumbnail'])): ?>
                                            <img src="../../uploads/thumbnails/<?php echo htmlspecialchars($game['thumbnail']); ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" class="w-12 h-12 rounded-lg object-cover">
                                        <?php else: ?>
                                            <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center">
                                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($game['name']); ?></td>
                                    <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($game['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td class="px-6 py-4 text-gray-500"><?php echo htmlspecialchars($game['publisher'] ?? '-'); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $game['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo ucfirst($game['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="edit.php?id=<?php echo $game['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100">Edit</a>
                                            <button onclick="confirmDelete(<?php echo $game['id']; ?>, '<?php echo htmlspecialchars(addslashes($game['name']), ENT_QUOTES); ?>')" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 rounded-lg hover:bg-red-100">Delete</button>
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
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="px-3 py-1 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Previous</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="px-3 py-1 text-sm font-medium text-white bg-indigo-600 rounded-lg"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="px-3 py-1 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="px-3 py-1 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200">Next</a>
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

<script>
function confirmDelete(id, name) {
    Swal.fire({
        title: 'Delete Game?',
        text: 'Are you sure you want to delete "' + name + '"? This cannot be undone.',
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
