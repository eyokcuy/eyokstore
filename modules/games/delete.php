<?php
/**
 * GameTopUp Pro - Delete Game
 * POST handler - blocks deletion if transactions are linked
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

// Get game ID
$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Invalid game ID.'];
    header('Location: index.php');
    exit;
}

// Check if game exists
$stmt = $db->prepare("SELECT id, thumbnail FROM games WHERE id = :id");
$stmt->execute([':id' => $id]);
$game = $stmt->fetch();

if (!$game) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Game not found.'];
    header('Location: index.php');
    exit;
}

// Check if game has linked transactions
$check = $db->prepare("SELECT COUNT(*) FROM transactions WHERE game_id = :id");
$check->execute([':id' => $id]);
$transactionsCount = $check->fetchColumn();

if ($transactionsCount > 0) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Cannot delete this game because it has ' . $transactionsCount . ' transaction(s) linked to it. Remove those transactions first.'
    ];
    header('Location: index.php');
    exit;
}

// Delete thumbnail file if exists
if (!empty($game['thumbnail']) && file_exists('../../uploads/thumbnails/' . $game['thumbnail'])) {
    unlink('../../uploads/thumbnails/' . $game['thumbnail']);
}

// Delete game
$stmt = $db->prepare("DELETE FROM games WHERE id = :id");
$stmt->execute([':id' => $id]);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Game deleted successfully.'];
header('Location: index.php');
exit;
