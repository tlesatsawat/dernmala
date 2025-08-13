<?php
// API endpoint to fetch table statuses and notifications for staff or main display
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$role = isset($_GET['role']) ? $_GET['role'] : 'staff';
$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

$tables = getAllTableStatuses();
$notifications = fetchNotifications($lastId);

echo json_encode([
    'tables' => $tables,
    'notifications' => $notifications,
]);