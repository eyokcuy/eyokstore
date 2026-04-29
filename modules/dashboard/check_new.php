<?php
/**
 * Eyok Store V2 - Real-time Check
 */
require_once '../../middleware/auth.php';
require_once '../../config/database.php';

$db = getDBConnection();
$lastId = intval($_GET['last_id'] ?? 0);

// Check if there is any transaction ID greater than the last one seen
$stmt = $db->prepare("SELECT id FROM transactions ORDER BY id DESC LIMIT 1");
$stmt->execute();
$latestId = $stmt->fetchColumn();

echo json_encode([
    'new_order' => ($latestId > $lastId),
    'latest_id' => $latestId ?: 0
]);
