<?php
/**
 * GameTopUp Pro - Create Transaction
 * Auto invoice code, live total via Alpine.js
 */

require_once '../../middleware/auth.php';
require_once '../../config/database.php';

$pageTitle = 'New Transaction';

$db = getDBConnection();

// Fetch active games for dropdown
$games = $db->query("SELECT id, name FROM games WHERE status = 'active' ORDER BY name ASC")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid CSRF token.'];
        header('Location: index.php');
        exit;
    }

    $gameId = intval($_POST['game_id'] ?? 0);
    $customerName = trim($_POST['customer_name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $gameUid = trim($_POST['game_uid'] ?? '');
    $itemName = trim($_POST['item_name'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 1);
    $price = floatval($_POST['price'] ?? 0);
    $status = $_POST['status'] ?? 'pending';
    $notes = trim($_POST['notes'] ?? '');

    // Invoice code auto-generation
    $invoiceCode = 'INV-' . date('Ymd') . '-' . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 5));

    // Validation
    if ($gameId <= 0) {
        $errors['game_id'] = 'Please select a game.';
    }

    if ($customerName === '') {
        $errors['customer_name'] = 'Customer name is required.';
    } elseif (strlen($customerName) > 100) {
        $errors['customer_name'] = 'Customer name must not exceed 100 characters.';
    }

    if ($contact === '') {
        $errors['contact'] = 'Contact number is required.';
    }

    if ($gameUid === '') {
        $errors['game_uid'] = 'Game UID is required.';
    } elseif (strlen($gameUid) > 50) {
        $errors['game_uid'] = 'Game UID must not exceed 50 characters.';
    }

    if ($itemName === '') {
        $errors['item_name'] = 'Item name is required.';
    }

    if ($quantity < 1) {
        $errors['quantity'] = 'Quantity must be at least 1.';
    }

    if ($price <= 0) {
        $errors['price'] = 'Price must be greater than 0.';
    }

    if (!in_array($status, ['pending', 'success', 'failed'])) {
        $status = 'pending';
    }

    // Check if invoice code already exists (unlikely but possible)
    $checkInvoice = $db->prepare("SELECT id FROM transactions WHERE invoice_code = :invoice");
    $checkInvoice->execute([':invoice' => $invoiceCode]);
    if ($checkInvoice->fetch()) {
        // Generate a new one
        $invoiceCode = 'INV-' . date('Ymd') . '-' . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 5));
    }

    if (empty($errors)) {
        $total = $quantity * $price;
        $userId = $_SESSION['user_id'];

        $stmt = $db->prepare("INSERT INTO transactions (invoice_code, user_id, game_id, customer_name, contact, game_uid, item_name, quantity, price, total, status, notes) 
                              VALUES (:invoice_code, :user_id, :game_id, :customer_name, :contact, :game_uid, :item_name, :quantity, :price, :total, :status, :notes)");
        $stmt->execute([
            ':invoice_code' => $invoiceCode,
            ':user_id' => $userId,
            ':game_id' => $gameId,
            ':customer_name' => $customerName,
            ':contact' => $contact,
            ':game_uid' => $gameUid,
            ':item_name' => $itemName,
            ':quantity' => $quantity,
            ':price' => $price,
            ':total' => $total,
            ':status' => $status,
            ':notes' => $notes ?: null,
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Transaction created successfully. Invoice: ' . $invoiceCode];
        header('Location: index.php');
        exit;
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<!-- Main Content -->
<main class="lg:ml-64 pt-16 min-h-screen">
    <div class="p-4 lg:p-8 max-w-2xl" x-data="{ quantity: <?php echo intval($_POST['quantity'] ?? 1); ?>, price: <?php echo floatval($_POST['price'] ?? 0); ?> }">
        <!-- Page Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">New Transaction</h1>
                <p class="text-gray-500 mt-1">Record a new top-up transaction</p>
            </div>
            <a href="index.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">← Back to list</a>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="space-y-5">
                    <!-- Game -->
                    <div>
                        <label for="game_id" class="block text-sm font-medium text-gray-700 mb-1">Game <span class="text-red-500">*</span></label>
                        <select id="game_id" name="game_id" class="w-full px-4 py-2 border <?php echo isset($errors['game_id']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                            <option value="">Select a game</option>
                            <?php foreach ($games as $g): ?>
                                <option value="<?php echo $g['id']; ?>" <?php echo (isset($_POST['game_id']) && $_POST['game_id'] == $g['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($g['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['game_id'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['game_id']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Customer Name & Contact -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-1">Customer Name <span class="text-red-500">*</span></label>
                            <input type="text" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($_POST['customer_name'] ?? ''); ?>" maxlength="100" class="w-full px-4 py-2 border <?php echo isset($errors['customer_name']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" placeholder="e.g. John Doe">
                            <?php if (isset($errors['customer_name'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo $errors['customer_name']; ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label for="contact" class="block text-sm font-medium text-gray-700 mb-1">WhatsApp / Contact <span class="text-red-500">*</span></label>
                            <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($_POST['contact'] ?? ''); ?>" maxlength="50" class="w-full px-4 py-2 border <?php echo isset($errors['contact']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" placeholder="e.g. 081234567890">
                            <?php if (isset($errors['contact'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo $errors['contact']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Game UID -->
                    <div>
                        <label for="game_uid" class="block text-sm font-medium text-gray-700 mb-1">Game UID <span class="text-red-500">*</span></label>
                        <input type="text" id="game_uid" name="game_uid" value="<?php echo htmlspecialchars($_POST['game_uid'] ?? ''); ?>" maxlength="50" class="w-full px-4 py-2 border <?php echo isset($errors['game_uid']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" placeholder="e.g. 123456789">
                        <?php if (isset($errors['game_uid'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['game_uid']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Item Name -->
                    <div>
                        <label for="item_name" class="block text-sm font-medium text-gray-700 mb-1">Item Name <span class="text-red-500">*</span></label>
                        <input type="text" id="item_name" name="item_name" value="<?php echo htmlspecialchars($_POST['item_name'] ?? ''); ?>" maxlength="100" class="w-full px-4 py-2 border <?php echo isset($errors['item_name']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" placeholder="e.g. 500 Diamonds">
                        <?php if (isset($errors['item_name'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['item_name']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Quantity & Price -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity <span class="text-red-500">*</span></label>
                            <input type="number" id="quantity" name="quantity" x-model.number="quantity" min="1" class="w-full px-4 py-2 border <?php echo isset($errors['quantity']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                            <?php if (isset($errors['quantity'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo $errors['quantity']; ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price (per item) <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">Rp</span>
                                <input type="number" id="price" name="price" x-model.number="price" min="0.01" step="0.01" class="w-full pl-8 px-4 py-2 border <?php echo isset($errors['price']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                            </div>
                            <?php if (isset($errors['price'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo $errors['price']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Total Price Calculation -->
                    <div class="bg-gray-50 rounded-lg p-4 flex justify-between items-center">
                        <span class="text-gray-700 font-medium">Total Amount:</span>
                        <span class="text-xl font-bold text-indigo-600" x-text="'Rp ' + (quantity * price).toLocaleString('id-ID')">Rp 0</span>
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                            <option value="pending" <?php echo (isset($_POST['status']) && $_POST['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="success" <?php echo (isset($_POST['status']) && $_POST['status'] === 'success') ? 'selected' : ''; ?>>Success</option>
                            <option value="failed" <?php echo (isset($_POST['status']) && $_POST['status'] === 'failed') ? 'selected' : ''; ?>>Failed</option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea id="notes" name="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>

                    <!-- Submit -->
                    <div class="pt-4">
                        <button type="submit" class="w-full bg-indigo-600 text-white font-medium py-2.5 rounded-lg hover:bg-indigo-700 transition-colors">
                            Create Transaction
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>
