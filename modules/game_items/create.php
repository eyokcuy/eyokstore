<?php
require_once '../../middleware/auth.php';
require_once '../../config/database.php';

$db = getDBConnection();
$pageTitle = 'Add New Game Item';
$errors = [];
$name = '';
$price = '';
$game_id = 0;
$status = 'active';

// Fetch games for dropdown
$games = $db->query("SELECT id, name, currency FROM games ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $game_id = intval($_POST['game_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $status = $_POST['status'] ?? 'active';

    if ($game_id <= 0) $errors['game_id'] = 'Please select a game.';
    if (empty($name)) $errors['name'] = 'Item name is required.';
    if ($price <= 0) $errors['price'] = 'Price must be greater than zero.';

    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO game_items (game_id, name, price, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$game_id, $name, $price, $status]);
        header('Location: index.php');
        exit;
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<main class="lg:pl-64 pt-16">
    <div class="p-4 lg:p-8 max-w-2xl">
        <div class="mb-8">
            <a href="index.php" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 flex items-center gap-2 mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Items
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Add New Game Item</h1>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">

            <form action="" method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Select Game</label>
                    <select name="game_id" required class="w-full bg-gray-50 border <?php echo isset($errors['game_id']) ? 'border-red-500 ring-1 ring-red-500' : 'border-gray-200'; ?> rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                        <option value="">-- Choose Game --</option>
                        <?php foreach ($games as $game): ?>
                            <option value="<?php echo $game['id']; ?>" <?php echo (isset($game_id) && $game_id == $game['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($game['name'] . ' (' . $game['currency'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['game_id'])): ?>
                        <p class="mt-1.5 text-xs font-medium text-red-500"><?php echo $errors['game_id']; ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Item Amount/Name (e.g. 50)</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required class="w-full bg-gray-50 border <?php echo isset($errors['name']) ? 'border-red-500 ring-1 ring-red-500' : 'border-gray-200'; ?> rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none transition-all" placeholder="50">
                    <p class="mt-1 text-xs text-gray-500">Currency suffix will be added automatically.</p>
                    <?php if (isset($errors['name'])): ?>
                        <p class="mt-1.5 text-xs font-medium text-red-500"><?php echo $errors['name']; ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Price (IDR)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm">Rp</span>
                        <input type="number" name="price" value="<?php echo $price ?? ''; ?>" required step="0.01" class="w-full bg-gray-50 border <?php echo isset($errors['price']) ? 'border-red-500 ring-1 ring-red-500' : 'border-gray-200'; ?> rounded-lg pl-10 pr-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none transition-all" placeholder="15000">
                    </div>
                    <?php if (isset($errors['price'])): ?>
                        <p class="mt-1.5 text-xs font-medium text-red-500"><?php echo $errors['price']; ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="submit" class="flex-1 bg-indigo-600 text-white font-bold py-3 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">Save Item</button>
                    <a href="index.php" class="flex-1 bg-gray-100 text-gray-600 font-bold py-3 rounded-lg hover:bg-gray-200 transition-colors text-center">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include '../../includes/footer.php'; ?>
