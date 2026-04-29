<?php
/**
 * GameTopUp Pro - Delete Transaction
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
    if ($id > 0) {
        $db = getDBConnection();
        $stmt = $db->prepare("DELETE FROM transactions WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Transaction deleted successfully.'];
    }
}

header('Location: index.php');
exit;
