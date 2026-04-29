<?php
/**
 * GameTopUp Pro - Edit User
 * Admin only
 */

require_once '../../middleware/auth.php';

// Admin only access
if (($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Unauthorized access.'];
    header('Location: ../dashboard/index.php');
    exit;
}

require_once '../../config/database.php';

$pageTitle = 'Edit User';

$db = getDBConnection();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'User not found.'];
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

    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'operator';
    $avatar = $user['avatar'];

    // Validation
    if ($fullName === '') {
        $errors['full_name'] = 'Full name is required.';
    }

    if ($username === '') {
        $errors['username'] = 'Username is required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = 'Username can only contain letters, numbers, and underscores.';
    } elseif ($username !== $user['username']) {
        // Check uniqueness if changed
        $stmtCheck = $db->prepare("SELECT id FROM users WHERE username = :username");
        $stmtCheck->execute([':username' => $username]);
        if ($stmtCheck->fetch()) {
            $errors['username'] = 'Username is already taken.';
        }
    }

    if (!empty($password) && strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters.';
    }

    if (!in_array($role, ['admin', 'operator'])) {
        $role = 'operator';
    }

    // Handle avatar upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedMimes)) {
            $errors['avatar'] = 'Invalid image type. Only JPG, PNG, and WebP are allowed.';
        } elseif ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
            $errors['avatar'] = 'Image size must be less than 2MB.';
        } else {
            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $newAvatar = uniqid('avatar_') . '.' . $ext;
            $uploadDir = '../../uploads/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $newAvatar)) {
                // Remove old avatar if exists
                if ($avatar && file_exists($uploadDir . $avatar)) {
                    unlink($uploadDir . $avatar);
                }
                $avatar = $newAvatar;
            } else {
                $errors['avatar'] = 'Failed to upload image.';
            }
        }
    }

    if (empty($errors)) {
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $updateStmt = $db->prepare("UPDATE users SET username = :username, password = :password, full_name = :full_name, avatar = :avatar, role = :role WHERE id = :id");
            $updateParams = [
                ':username' => $username,
                ':password' => $hashedPassword,
                ':full_name' => $fullName,
                ':avatar' => $avatar,
                ':role' => $role,
                ':id' => $id
            ];
        } else {
            $updateStmt = $db->prepare("UPDATE users SET username = :username, full_name = :full_name, avatar = :avatar, role = :role WHERE id = :id");
            $updateParams = [
                ':username' => $username,
                ':full_name' => $fullName,
                ':avatar' => $avatar,
                ':role' => $role,
                ':id' => $id
            ];
        }

        $updateStmt->execute($updateParams);

        // If editing self, update session
        if ($id == $_SESSION['user_id']) {
            $_SESSION['username'] = $username;
            $_SESSION['full_name'] = $fullName;
            $_SESSION['role'] = $role;
            $_SESSION['avatar'] = $avatar;
        }

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'User updated successfully.'];
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
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit User</h1>
                <p class="text-gray-500 mt-1">@<?php echo htmlspecialchars($user['username']); ?></p>
            </div>
            <a href="index.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">← Back to list</a>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="space-y-5">
                    <!-- Current Avatar Preview -->
                    <?php if (!empty($user['avatar']) && file_exists('../../uploads/avatars/' . $user['avatar'])): ?>
                        <div>
                            <p class="block text-sm font-medium text-gray-700 mb-2">Current Avatar</p>
                            <img src="../../uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar" class="w-20 h-20 rounded-full object-cover border border-gray-200">
                        </div>
                    <?php endif; ?>

                    <!-- Full Name -->
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? $user['full_name']); ?>" maxlength="100" class="w-full px-4 py-2 border <?php echo isset($errors['full_name']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        <?php if (isset($errors['full_name'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['full_name']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? $user['username']); ?>" maxlength="50" class="w-full px-4 py-2 border <?php echo isset($errors['username']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        <?php if (isset($errors['username'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['username']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" id="password" name="password" class="w-full px-4 py-2 border <?php echo isset($errors['password']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" placeholder="Leave blank to keep current password">
                        <?php if (isset($errors['password'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['password']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Role -->
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select id="role" name="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" <?php echo ($id == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                            <?php $selectedRole = $_POST['role'] ?? $user['role']; ?>
                            <option value="operator" <?php echo ($selectedRole === 'operator') ? 'selected' : ''; ?>>Operator</option>
                            <option value="admin" <?php echo ($selectedRole === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                        <?php if ($id == $_SESSION['user_id']): ?>
                            <input type="hidden" name="role" value="<?php echo htmlspecialchars($user['role']); ?>">
                            <p class="mt-1 text-xs text-gray-500">You cannot change your own role.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Avatar Upload -->
                    <div>
                        <label for="avatar" class="block text-sm font-medium text-gray-700 mb-1">Update Avatar (Optional)</label>
                        <input type="file" id="avatar" name="avatar" accept="image/jpeg, image/png, image/webp" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="mt-1 text-xs text-gray-500">JPG, PNG or WebP. Max 2MB.</p>
                        <?php if (isset($errors['avatar'])): ?>
                            <p class="mt-1 text-sm text-red-600"><?php echo $errors['avatar']; ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Submit -->
                    <div class="pt-4">
                        <button type="submit" class="w-full bg-indigo-600 text-white font-medium py-2.5 rounded-lg hover:bg-indigo-700 transition-colors">
                            Update User
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>
