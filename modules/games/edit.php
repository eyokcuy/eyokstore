<?php
/**
 * GameTopUp Pro - Edit Game
 * Pre-filled form with current thumbnail preview and replace option
 */

require_once '../../middleware/auth.php';
require_once '../../config/database.php';

$pageTitle = 'Edit Game';

$db = getDBConnection();

// Get game ID
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid game ID.'];
    header('Location: index.php');
    exit;
}

// Fetch game
$stmt = $db->prepare("SELECT * FROM games WHERE id = :id");
$stmt->execute([':id' => $id]);
$game = $stmt->fetch();

if (!$game) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Game not found.'];
    header('Location: index.php');
    exit;
}

// Fetch categories for dropdown
$categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid CSRF token.'];
        header('Location: index.php');
        exit;
    }

    $categoryId = intval($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $status = $_POST['status'] ?? 'active';

    // Validation
    if ($categoryId <= 0) {
        $errors['category_id'] = 'Please select a category.';
    }

    if ($name === '') {
        $errors['name'] = 'Game name is required.';
    } elseif (strlen($name) > 100) {
        $errors['name'] = 'Game name must not exceed 100 characters.';
    } else {
        // Check duplicate (excluding self)
        $check = $db->prepare("SELECT id FROM games WHERE name = :name AND id != :id");
        $check->execute([':name' => $name, ':id' => $id]);
        if ($check->fetch()) {
            $errors['name'] = 'Another game with this name already exists.';
        }
    }

    if ($status !== 'active' && $status !== 'inactive') {
        $status = 'active';
    }

    // Handle thumbnail upload (replace if new one provided)
    $thumbnail = $game['thumbnail']; // Keep existing by default
    if (!empty($_FILES['thumbnail']['tmp_name'])) {
        $file = $_FILES['thumbnail'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

        // Validate file size
        if ($file['size'] > $maxSize) {
            $errors['thumbnail'] = 'Thumbnail must not exceed 2MB.';
        } else {
            // Validate MIME type using finfo
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            if (!in_array($mimeType, $allowedTypes)) {
                $errors['thumbnail'] = 'Only JPG, PNG, and WEBP images are allowed.';
            } else {
                // Delete old thumbnail if exists
                if (!empty($game['thumbnail']) && file_exists('../../uploads/thumbnails/' . $game['thumbnail'])) {
                    unlink('../../uploads/thumbnails/' . $game['thumbnail']);
                }

                // Generate unique filename
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $thumbnail = uniqid('game_', true) . '.' . $ext;
                $uploadPath = '../../uploads/thumbnails/' . $thumbnail;

                if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $errors['thumbnail'] = 'Failed to upload thumbnail. Please try again.';
                    $thumbnail = $game['thumbnail']; // Revert to old
                }
            }
        }
    }

    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE games SET category_id = :category_id, name = :name, thumbnail = :thumbnail, publisher = :publisher, status = :status WHERE id = :id");
        $stmt->execute([
            ':category_id' => $categoryId,
            ':name' => $name,
            ':thumbnail' => $thumbnail,
            ':publisher' => $publisher ?: null,
            ':status' => $status,
            ':id' => $id,
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Game updated successfully.'];
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
            <h1 class="text-2xl font-bold text-gray-900">Edit Game</h1>
            <p class="text-gray-500 mt-1">Update game details</p>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="space-y-5">
                    <!-- Current Thumbnail Preview -->
                    <?php if (!empty($game['thumbnail']) && file_exists('../../uploads/thumbnails/' . $game['thumbnail'])): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Thumbnail</label>
                        <div class="flex items-center gap-4">
                            <img src="../../uploads/thumbnails/<?php echo htmlspecialchars($game['thumbnail']); ?>" alt="Current thumbnail" class="w-24 h-24 rounded-lg object-cover border border-gray-200">
                            <p class="text-sm text-gray-500">Upload a new image below to replace.</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Category -->
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                        <select id="category_id" name="category_id" class="w-full px-4 py-2 border <?php echo isset($errors['category_id']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo (($_POST['category_id'] ?? $game['category_id']) == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['category_id'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['category_id']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Game Name <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? $game['name']); ?>" maxlength="100" class="w-full px-4 py-2 border <?php echo isset($errors['name']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" placeholder="e.g. Mobile Legends">
                        <?php if (isset($errors['name'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['name']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Publisher -->
                    <div>
                        <label for="publisher" class="block text-sm font-medium text-gray-700 mb-1">Publisher</label>
                        <input type="text" id="publisher" name="publisher" value="<?php echo htmlspecialchars($_POST['publisher'] ?? ($game['publisher'] ?? '')); ?>" maxlength="100" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" placeholder="e.g. Moonton">
                    </div>

                    <!-- Thumbnail -->
                    <div>
                        <label for="thumbnail" class="block text-sm font-medium text-gray-700 mb-1">Replace Thumbnail</label>
                        <input type="file" id="thumbnail" name="thumbnail" accept="image/jpeg,image/png,image/webp" class="w-full px-4 py-2 border <?php echo isset($errors['thumbnail']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="mt-1 text-xs text-gray-500">JPG, PNG, or WEBP. Max 2MB.</p>
                        <?php if (isset($errors['thumbnail'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['thumbnail']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <div class="flex items-center gap-4">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="status" value="active" <?php echo (($_POST['status'] ?? $game['status']) === 'active') ? 'checked' : ''; ?> class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Active</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="status" value="inactive" <?php echo (($_POST['status'] ?? $game['status']) === 'inactive') ? 'checked' : ''; ?> class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Inactive</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 mt-8 pt-5 border-t border-gray-100">
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors">Update Game</button>
                    <a href="index.php" class="px-5 py-2.5 text-gray-600 font-medium hover:text-gray-800">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>
