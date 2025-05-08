<?php
session_start();
require_once 'config/database.php';
require_once 'notifications.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$count = getUnreadNotificationCount($db, $_SESSION['user_id']);
echo json_encode(['count' => $count]);
?>
