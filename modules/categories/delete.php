<?php
/**
 * GameTopUp Pro - Delete Category
 * POST handler - blocks deletion if games are linked
 */

require_once '../../middleware/auth.php';
require_once '../../config/database.php';

$db = getDBConnection();

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Validate CSRF
if (!validateCSRF($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid CSRF token.'];
    header('Location: index.php');
    exit;
}

// Get category ID
$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid category ID.'];
    header('Location: index.php');
    exit;
}

// Check if category exists
$stmt = $db->prepare("SELECT id FROM categories WHERE id = :id");
$stmt->execute([':id' => $id]);
if (!$stmt->fetch()) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Category not found.'];
    header('Location: index.php');
    exit;
}

// Check if category has linked games
$check = $db->prepare("SELECT COUNT(*) FROM games WHERE category_id = :id");
$check->execute([':id' => $id]);
$gamesCount = $check->fetchColumn();

if ($gamesCount > 0) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Cannot delete this category because it has ' . $gamesCount . ' game(s) linked to it. Remove or reassign those games first.'
    ];
    header('Location: index.php');
    exit;
}

// Delete category
$stmt = $db->prepare("DELETE FROM categories WHERE id = :id");
$stmt->execute([':id' => $id]);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Category deleted successfully.'];
header('Location: index.php');
exit;
