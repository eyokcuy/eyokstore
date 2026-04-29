<?php
require_once '../../middleware/auth.php';
require_once '../../config/database.php';

$db = getDBConnection();
$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $db->prepare("DELETE FROM game_items WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: index.php');
exit;
