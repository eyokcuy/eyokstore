<?php
/**
 * Eyok Store - Delete User
 * Admin only
 */

require_once '../../middleware/auth.php';
requireAdmin();

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid CSRF token.'];
        header('Location: index.php');
        exit;
    }

    $id = intval($_POST['id'] ?? 0);
    
    // Prevent deleting self
    if ($id === intval($_SESSION['user_id'])) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'You cannot delete yourself.'];
        header('Location: index.php');
        exit;
    }

    if ($id > 0) {
        $db = getDBConnection();

        // Optional: Block deletion if user has transactions
        $checkStmt = $db->prepare("SELECT id FROM transactions WHERE user_id = :id LIMIT 1");
        $checkStmt->execute([':id' => $id]);
        if ($checkStmt->fetch()) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cannot delete user. They have associated transactions.'];
            header('Location: index.php');
            exit;
        }

        // Get avatar to delete file
        $userStmt = $db->prepare("SELECT avatar FROM users WHERE id = :id");
        $userStmt->execute([':id' => $id]);
        $user = $userStmt->fetch();

        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
        if ($stmt->execute([':id' => $id])) {
            if ($user && !empty($user['avatar'])) {
                $avatarPath = '../../uploads/avatars/' . $user['avatar'];
                if (file_exists($avatarPath)) {
                    unlink($avatarPath);
                }
            }
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'User deleted successfully.'];
        }
    }
}

header('Location: index.php');
exit;
