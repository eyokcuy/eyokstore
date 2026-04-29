<?php
/**
 * GameTopUp Pro - Categories Index
 * Paginated list with search and action buttons
 */

require_once '../../middleware/auth.php';
require_once '../../config/database.php';

$pageTitle = 'Categories';

$db = getDBConnection();

// Search filter
$search = trim($_GET['search'] ?? '');
$params = [];

// Build query
$where = '';
if ($search !== '') {
    $where = 'WHERE name LIKE :search';
    $params[':search'] = '%' . $search . '%';
}

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;

// Get total count
$countSql = "SELECT COUNT(*) FROM categories $where";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Get categories
$sql = "SELECT c.*, (SELECT COUNT(*) FROM games WHERE category_id = c.id) as games_count
        FROM categories c
        $where
        ORDER BY c.created_at DESC
        LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);

foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$categories = $stmt->fetchAll();

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<!-- Main Content -->
<main class="lg:ml-64 pt-16 min-h-screen">
    <div class="p-4 lg:p-8">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Categories</h1>
                <p class="text-gray-500 mt-1">Manage game categories</p>
            </div>
            <a href="create.php" class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Category
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
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name..." class="flex-1 max-w-md px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Search</button>
                <?php if ($search): ?>
                    <a href="index.php" class="px-4 py-2 text-gray-500 hover:text-gray-700">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Search Results Container -->
        <div id="search-results">
            <!-- Categories Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Description</th>
                                <th class="px-6 py-3">Games Linked</th>
                                <th class="px-6 py-3">Created</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        <?php echo $search ? 'No categories found matching your search.' : 'No categories yet. <a href="create.php" class="text-indigo-600 hover:underline">Create one</a>'; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $cat): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($cat['name']); ?></td>
                                    <td class="px-6 py-4 text-gray-600 max-w-xs truncate"><?php echo htmlspecialchars($cat['description'] ?? '-'); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <?php echo number_format($cat['games_count']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500"><?php echo date('M d, Y', strtotime($cat['created_at'])); ?></td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="edit.php?id=<?php echo $cat['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100">Edit</a>
                                            <button onclick="confirmDelete(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars(addslashes($cat['name']), ENT_QUOTES); ?>')" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 rounded-lg hover:bg-red-100">Delete</button>
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
        title: 'Delete Category?',
        text: 'Are you sure you want to delete "' + name + '"? This cannot be undone if no games are linked.',
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
