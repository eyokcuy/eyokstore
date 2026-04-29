<?php
require_once '../../middleware/auth.php';
require_once '../../config/database.php';

$db = getDBConnection();
$pageTitle = 'Manage Game Items';

// Fetch all items with game names
$query = "SELECT gi.*, g.name as game_name, g.currency 
          FROM game_items gi 
          JOIN games g ON gi.game_id = g.id 
          ORDER BY g.name ASC, gi.price ASC";
$items = $db->query($query)->fetchAll();

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="lg:pl-64 pt-16">
    <div class="p-4 lg:p-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Game Items</h1>
                <p class="text-sm text-gray-500">Manage top-up packages and prices for each game.</p>
            </div>
            <a href="create.php" class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add New Item
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Game</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Item Name</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($items as $item): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['game_name']); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600"><?php echo htmlspecialchars($item['name'] . ' ' . $item['currency']); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-bold text-indigo-600">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $item['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($item['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="edit.php?id=<?php echo $item['id']; ?>" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Edit</a>
                                <button onclick="confirmDelete(<?php echo $item['id']; ?>)" class="text-red-600 hover:text-red-900 text-sm font-medium">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 italic">No items found. Click "Add New Item" to get started.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
function confirmDelete(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This item will be permanently deleted!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete.php?id=' + id;
        }
    })
}
</script>

<?php include '../../includes/footer.php'; ?>
