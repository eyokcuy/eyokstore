<?php
/**
 * GameTopUp Pro - Update Transaction Status
 */

require_once '../../middleware/auth.php';
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid CSRF token.'];
        header('Location: index.php');
        exit;
    }

    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if ($id > 0 && in_array($status, ['success', 'failed'])) {
        $db = getDBConnection();
        
        // When an operator clicks success/failed, we associate their user_id if it was null (public order)
        // or just update it anyway to show who processed it.
        $stmt = $db->prepare("UPDATE transactions SET status = :status, user_id = :user_id WHERE id = :id");
        $stmt->execute([
            ':status' => $status,
            ':user_id' => $_SESSION['user_id'],
            ':id' => $id
        ]);
        
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Transaction status updated to ' . ucfirst($status)];
    }
}

header('Location: index.php');
exit;
