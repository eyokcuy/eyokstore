<?php
/**
 * GameTopUp Pro - Edit Category
 * Pre-filled form to update a category
 */

require_once '../../middleware/auth.php';
require_once '../../config/database.php';

$pageTitle = 'Edit Category';

$db = getDBConnection();

// Get category ID
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid category ID.'];
    header('Location: index.php');
    exit;
}

// Fetch category
$stmt = $db->prepare("SELECT * FROM categories WHERE id = :id");
$stmt->execute([':id' => $id]);
$category = $stmt->fetch();

if (!$category) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Category not found.'];
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid CSRF token.'];
        header('Location: index.php');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Validation
    if ($name === '') {
        $errors['name'] = 'Category name is required.';
    } elseif (strlen($name) > 100) {
        $errors['name'] = 'Category name must not exceed 100 characters.';
    } else {
        // Check duplicate (excluding self)
        $check = $db->prepare("SELECT id FROM categories WHERE name = :name AND id != :id");
        $check->execute([':name' => $name, ':id' => $id]);
        if ($check->fetch()) {
            $errors['name'] = 'Another category with this name already exists.';
        }
    }

    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE categories SET name = :name, description = :description WHERE id = :id");
        $stmt->execute([
            ':name' => $name,
            ':description' => $description ?: null,
            ':id' => $id,
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Category updated successfully.'];
        header('Location: index.php');
        exit;
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<!-- Main Content -->
<main class="lg:ml-64 pt-16 min-h-screen">
    <div class="p-4 lg:p-8 max-w-2xl">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Edit Category</h1>
            <p class="text-gray-500 mt-1">Update category details</p>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="space-y-5">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Category Name <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? $category['name']); ?>" maxlength="100"
                            class="w-full px-4 py-2 border <?php echo isset($errors['name']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                            placeholder="e.g. MOBA">
                        <?php if (isset($errors['name'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['name']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="description" name="description" rows="3" maxlength="500"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none resize-none"
                            placeholder="Optional description for this category"><?php echo htmlspecialchars($_POST['description'] ?? ($category['description'] ?? '')); ?></textarea>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 mt-8 pt-5 border-t border-gray-100">
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors">Update Category</button>
                    <a href="index.php" class="px-5 py-2.5 text-gray-600 font-medium hover:text-gray-800">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>
